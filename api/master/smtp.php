<?php
/**
 * API Master: SMTP Server Mailer Management
 * Path: api/master/smtp.php
 * Akses: Khusus ROLE_ADMIN
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Auth Protection: Hanya Admin yang diizinkan mengakses API ini
$currentUser = requireAuth([ROLE_ADMIN]);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($rawInput['_method'])) {
        $method = strtoupper($rawInput['_method']);
    }
}

// =========================================================================
// 1. GET: Ambil Daftar Server SMTP / Detail Single Server
// =========================================================================
if ($method === 'GET') {
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    // Single item detail
    if ($id) {
        $stmt = $conn->prepare("SELECT id_stmp, nama_provider, link_provider, stmp_server, port, user_login, password, created_at, limit_harian, sisa_harian, aktif 
                                FROM smtp_server WHERE id_stmp = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $stmt->close();
            // Masking password for response
            $row['password_masked'] = !empty($row['password']) ? '••••••••' : '';
            jsonResponse(true, 'Data SMTP Server berhasil diambil.', $row);
        }
        $stmt->close();
        jsonResponse(false, 'Data SMTP Server tidak ditemukan.', null, 404);
    }

    // List all items with stats
    $search = trim($_GET['q'] ?? '');
    $statusFilter = isset($_GET['aktif']) && $_GET['aktif'] !== '' ? (int)$_GET['aktif'] : null;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (nama_provider LIKE ? OR stmp_server LIKE ? OR user_login LIKE ?)";
        $wildcard = "%" . $search . "%";
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
        $types .= "sss";
    }

    if ($statusFilter !== null) {
        $whereSql .= " AND aktif = ?";
        $params[] = $statusFilter;
        $types .= "i";
    }

    $sql = "SELECT id_stmp, nama_provider, link_provider, stmp_server, port, user_login, password, created_at, limit_harian, sisa_harian, aktif 
            FROM smtp_server " . $whereSql . " ORDER BY aktif DESC, id_stmp DESC";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    $totalKuota = 0;
    $totalSisa = 0;
    $totalAktif = 0;

    while ($row = $res->fetch_assoc()) {
        $row['limit_harian'] = (int)($row['limit_harian'] ?? 300);
        $row['sisa_harian'] = isset($row['sisa_harian']) ? (int)$row['sisa_harian'] : $row['limit_harian'];
        $row['aktif'] = (int)($row['aktif'] ?? 1);
        $row['password_masked'] = !empty($row['password']) ? '••••••••' : '';

        if ($row['aktif'] === 1) {
            $totalAktif++;
            $totalKuota += $row['limit_harian'];
            $totalSisa += $row['sisa_harian'];
        }

        $items[] = $row;
    }
    $stmt->close();

    jsonResponse(true, 'Daftar SMTP Server berhasil dimuat.', [
        'items' => $items,
        'stats' => [
            'total_server' => count($items),
            'total_aktif' => $totalAktif,
            'total_kuota' => $totalKuota,
            'total_sisa' => $totalSisa
        ]
    ]);
}

// =========================================================================
// 2. POST: Tambah Server SMTP Baru / Test Koneksi
// =========================================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = trim($_GET['action'] ?? $input['action'] ?? '');

    // Action: Test Koneksi & Autentikasi SMTP Penuh (RFC Protocol Handshake)
    if ($action === 'test') {
        $id = isset($input['id_stmp']) ? (int)$input['id_stmp'] : 0;
        $host = trim($input['stmp_server'] ?? '');
        $port = (int)($input['port'] ?? 587);
        $user = trim($input['user_login'] ?? '');
        $pass = trim($input['password'] ?? '');

        // Jika testing ID tersimpan dan password kosong di input form, ambil password dari database
        if ($id > 0 && empty($pass)) {
            $stmtP = $conn->prepare("SELECT stmp_server, port, user_login, password FROM smtp_server WHERE id_stmp = ? LIMIT 1");
            $stmtP->bind_param("i", $id);
            $stmtP->execute();
            if ($rowP = $stmtP->get_result()->fetch_assoc()) {
                if (empty($host)) $host = $rowP['stmp_server'];
                if ($port <= 0) $port = (int)$rowP['port'];
                if (empty($user)) $user = $rowP['user_login'];
                $pass = $rowP['password'];
            }
            $stmtP->close();
        }

        if (empty($host)) {
            jsonResponse(false, 'Host SMTP server wajib diisi untuk pengujian koneksi.', null, 422);
        }

        $timeout = 8;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $protocol = ($port === 465) ? 'ssl://' : '';
        $socket = @stream_socket_client($protocol . $host . ':' . $port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            jsonResponse(false, "Gagal terhubung ke host $host:$port. Pesan Error: $errstr (Kode: $errno)", null, 500);
        }

        stream_set_timeout($socket, $timeout);

        $readFn = function() use ($socket) {
            $data = "";
            while ($str = fgets($socket, 515)) {
                $data .= $str;
                if (substr($str, 3, 1) === " ") break;
            }
            return $data;
        };
        $writeFn = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        // 1. Baca Banner Awal
        $banner = $readFn();
        if (strpos($banner, '220') === false) {
            fclose($socket);
            jsonResponse(false, "Respon awal server bukan SMTP yang valid: " . trim($banner), null, 500);
        }

        // 2. Handshake EHLO
        $writeFn("EHLO localhost");
        $ehloRes = $readFn();

        // 3. Negosiasi STARTTLS jika port 587 atau 25
        if ($port !== 465 && strpos($ehloRes, 'STARTTLS') !== false) {
            $writeFn("STARTTLS");
            $tlsRes = $readFn();
            if (strpos($tlsRes, '220') !== false) {
                $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$crypto) {
                    fclose($socket);
                    jsonResponse(false, "Koneksi terbuka, namun gagal mengaktifkan enkripsi TLS ke $host.", null, 500);
                }
                // Kirim ulang EHLO setelah enkripsi TLS aktif
                $writeFn("EHLO localhost");
                $readFn();
            }
        }

        // 4. Uji Kredensial Autentikasi jika username dan password tersedia
        if (!empty($user) && !empty($pass)) {
            $writeFn("AUTH LOGIN");
            $authRes = $readFn();
            if (strpos($authRes, '334') === false) {
                $writeFn("QUIT");
                fclose($socket);
                jsonResponse(false, "Server menolak metode AUTH LOGIN: " . trim($authRes), null, 500);
            }

            $writeFn(base64_encode($user));
            $userRes = $readFn();
            if (strpos($userRes, '334') === false) {
                $writeFn("QUIT");
                fclose($socket);
                jsonResponse(false, "Username ditolak oleh server SMTP: " . trim($userRes), null, 500);
            }

            $writeFn(base64_encode($pass));
            $passRes = $readFn();
            $writeFn("QUIT");
            fclose($socket);

            if (strpos($passRes, '235') !== false) {
                jsonResponse(true, "✅ Autentikasi Berhasil! Host $host:$port dan Kredensial Akun (Username & Password) 100% VALID dan SIAP mengirim email.");
            } else {
                jsonResponse(false, "❌ Koneksi ke Host Berhasil, namun Autentikasi Ditolak ($passRes). Periksa kembali Username atau App Password Anda.", null, 401);
            }
        }

        $writeFn("QUIT");
        fclose($socket);
        jsonResponse(true, "✅ Koneksi ke Host $host:$port Berhasil! Server SMTP aktif dan siap menerima perintah (Banner: " . trim($banner) . ").");
    }

    // Standard Create
    $namaProvider = trim($input['nama_provider'] ?? '');
    $linkProvider = trim($input['link_provider'] ?? '');
    $stmpServer = trim($input['stmp_server'] ?? '');
    $port = trim($input['port'] ?? '587');
    $userLogin = trim($input['user_login'] ?? '');
    $password = trim($input['password'] ?? '');
    $limitHarian = isset($input['limit_harian']) && is_numeric($input['limit_harian']) ? (int)$input['limit_harian'] : 300;
    $sisaHarian = isset($input['sisa_harian']) && is_numeric($input['sisa_harian']) ? (int)$input['sisa_harian'] : $limitHarian;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaProvider)) {
        jsonResponse(false, 'Nama provider wajib diisi.', null, 422);
    }
    if (empty($stmpServer)) {
        jsonResponse(false, 'Alamat SMTP Server (host) wajib diisi.', null, 422);
    }
    if (empty($port)) {
        jsonResponse(false, 'Port SMTP wajib diisi (contoh: 587, 465, atau 25).', null, 422);
    }
    if (empty($userLogin)) {
        jsonResponse(false, 'Username / User login SMTP wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("INSERT INTO smtp_server (nama_provider, link_provider, stmp_server, port, user_login, password, created_at, limit_harian, sisa_harian, aktif) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
    $stmt->bind_param("ssssssiii", $namaProvider, $linkProvider, $stmpServer, $port, $userLogin, $password, $limitHarian, $sisaHarian, $aktif);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'SMTP Server berhasil ditambahkan.', ['id_stmp' => $newId], 201);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan SMTP Server: ' . $err, null, 500);
    }
}

// =========================================================================
// 3. PUT: Update Server SMTP / Toggle Status
// =========================================================================
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = isset($input['id_stmp']) ? (int)$input['id_stmp'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

    if ($id <= 0) {
        jsonResponse(false, 'ID SMTP Server tidak valid.', null, 422);
    }

    // Toggle Aktif Cepat
    if (isset($input['toggle_aktif'])) {
        $newAktif = (int)$input['toggle_aktif'];
        $stmt = $conn->prepare("UPDATE smtp_server SET aktif = ? WHERE id_stmp = ?");
        $stmt->bind_param("ii", $newAktif, $id);
        if ($stmt->execute()) {
            $stmt->close();
            $statusText = $newAktif === 1 ? 'diaktifkan' : 'dinonaktifkan';
            jsonResponse(true, "Status SMTP Server berhasil $statusText.");
        } else {
            $err = $stmt->error;
            $stmt->close();
            jsonResponse(false, "Gagal mengubah status: $err", null, 500);
        }
    }

    $namaProvider = trim($input['nama_provider'] ?? '');
    $linkProvider = trim($input['link_provider'] ?? '');
    $stmpServer = trim($input['stmp_server'] ?? '');
    $port = trim($input['port'] ?? '587');
    $userLogin = trim($input['user_login'] ?? '');
    $password = trim($input['password'] ?? '');
    $limitHarian = isset($input['limit_harian']) && is_numeric($input['limit_harian']) ? (int)$input['limit_harian'] : 300;
    $sisaHarian = isset($input['sisa_harian']) && is_numeric($input['sisa_harian']) ? (int)$input['sisa_harian'] : $limitHarian;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaProvider) || empty($stmpServer) || empty($port) || empty($userLogin)) {
        jsonResponse(false, 'Nama provider, host server, port, dan username wajib diisi.', null, 422);
    }

    // Jika password dikosongkan saat update, pertahankan password lama
    if (empty($password)) {
        $stmt = $conn->prepare("UPDATE smtp_server SET nama_provider = ?, link_provider = ?, stmp_server = ?, port = ?, user_login = ?, limit_harian = ?, sisa_harian = ?, aktif = ? WHERE id_stmp = ?");
        $stmt->bind_param("sssssiiii", $namaProvider, $linkProvider, $stmpServer, $port, $userLogin, $limitHarian, $sisaHarian, $aktif, $id);
    } else {
        $stmt = $conn->prepare("UPDATE smtp_server SET nama_provider = ?, link_provider = ?, stmp_server = ?, port = ?, user_login = ?, password = ?, limit_harian = ?, sisa_harian = ?, aktif = ? WHERE id_stmp = ?");
        $stmt->bind_param("ssssssiiii", $namaProvider, $linkProvider, $stmpServer, $port, $userLogin, $password, $limitHarian, $sisaHarian, $aktif, $id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data SMTP Server berhasil diperbarui.');
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui SMTP Server: ' . $err, null, 500);
    }
}

// =========================================================================
// 4. DELETE: Hapus Server SMTP
// =========================================================================
if ($method === 'DELETE') {
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int)($input['id_stmp'] ?? 0);
    }

    if ($id <= 0) {
        jsonResponse(false, 'ID SMTP Server tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("DELETE FROM smtp_server WHERE id_stmp = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'SMTP Server berhasil dihapus.');
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus SMTP Server: ' . $err, null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak diizinkan.', null, 405);
