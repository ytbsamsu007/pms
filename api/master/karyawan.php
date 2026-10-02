<?php
/**
 * API Master: Karyawan CRUD Endpoint - PT Jaya Teknik
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// -------------------------------------------------------------
// GET: Mengambil Data Karyawan (List / Detail)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $divisiId = isset($_GET['divisi_id']) && is_numeric($_GET['divisi_id']) ? (int)$_GET['divisi_id'] : null;
    $jabatanId = isset($_GET['jabatan_id']) && is_numeric($_GET['jabatan_id']) ? (int)$_GET['jabatan_id'] : null;
    $siteId = isset($_GET['site_id']) && is_numeric($_GET['site_id']) ? (int)$_GET['site_id'] : null;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 50;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (k.nama_karyawan LIKE ? OR k.kode_karyawan LIKE ? OR d.nama_divisi LIKE ? OR j.nama_jabatan LIKE ? OR s.nama_site LIKE ? OR k.email LIKE ? OR k.no_handphone LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 7; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    if ($divisiId !== null) {
        $whereSql .= " AND k.id_divisi = ?";
        $params[] = $divisiId;
        $types .= "i";
    }

    if ($jabatanId !== null) {
        $whereSql .= " AND k.id_jabatan = ?";
        $params[] = $jabatanId;
        $types .= "i";
    }

    if ($siteId !== null) {
        $whereSql .= " AND k.id_site = ?";
        $params[] = $siteId;
        $types .= "i";
    }

    $maxLevel = isset($_GET['max_level']) && is_numeric($_GET['max_level']) ? (int)$_GET['max_level'] : null;
    $levelsParam = trim($_GET['levels'] ?? '');

    if ($maxLevel !== null) {
        $whereSql .= " AND j.level <= ?";
        $params[] = $maxLevel;
        $types .= "i";
    } elseif (!empty($levelsParam)) {
        $levelArr = array_filter(array_map('intval', explode(',', $levelsParam)), fn($n) => $n > 0);
        if (!empty($levelArr)) {
            $inPlaceholders = implode(',', array_fill(0, count($levelArr), '?'));
            $whereSql .= " AND j.level IN ($inPlaceholders)";
            foreach ($levelArr as $lvl) {
                $params[] = $lvl;
                $types .= "i";
            }
        }
    }

    $countSql = "SELECT COUNT(*) as total 
                 FROM karyawan k 
                 LEFT JOIN divisi d ON k.id_divisi = d.id_divisi 
                 LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                 LEFT JOIN site s ON k.id_site = s.id_site" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $sql = "SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.id_divisi, k.id_jabatan, k.id_site,
                   k.tanggal_lahir, k.tempat_lahir, k.jenis_kelamin,
                   k.tanggal_bergabung, k.aktif, k.email, k.no_handphone, k.status_karyawan, k.login_web, 
                   d.nama_divisi, d.kode_divisi,
                   j.nama_jabatan, j.kode_jabatan,
                   s.nama_site, s.kode_site
            FROM karyawan k
            LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
            LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            LEFT JOIN site s ON k.id_site = s.id_site"
            . $whereSql . " ORDER BY k.id_karyawan DESC LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $res = $stmt->get_result();

    $statusKaryawanMap = [
        0 => 'Magang (Internship)',
        1 => 'PKWT (Perjanjian Kerja Waktu Tertentu)',
        2 => 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)',
        3 => 'Pekerja paruh waktu (Part-time)',
        4 => 'Harian Lepas (Casual Workers)',
        5 => 'Freelance / Pekerja Lepas',
        6 => 'Outsourcing / Alih Daya',
        7 => 'Volunteer / Sukarelawan'
    ];

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $statusId = isset($row['status_karyawan']) && $row['status_karyawan'] !== null ? (int)$row['status_karyawan'] : 2;
        $jk = isset($row['jenis_kelamin']) ? (int)$row['jenis_kelamin'] : 1;
        
        $items[] = [
            'id_karyawan' => (int)$row['id_karyawan'],
            'kode_karyawan' => $row['kode_karyawan'] ?? '',
            'nama_karyawan' => $row['nama_karyawan'] ?? '',
            'id_divisi' => (int)$row['id_divisi'],
            'nama_divisi' => $row['nama_divisi'] ?? '-',
            'kode_divisi' => $row['kode_divisi'] ?? '-',
            'id_jabatan' => $row['id_jabatan'] ? (int)$row['id_jabatan'] : null,
            'nama_jabatan' => $row['nama_jabatan'] ?? '-',
            'kode_jabatan' => $row['kode_jabatan'] ?? '-',
            'id_site' => $row['id_site'] ? (int)$row['id_site'] : null,
            'nama_site' => $row['nama_site'] ?? 'Semua Site / Pusat',
            'kode_site' => $row['kode_site'] ?? '-',
            'tempat_lahir' => $row['tempat_lahir'] ?? '',
            'tanggal_lahir' => $row['tanggal_lahir'] ?? '',
            'tanggal_lahir_formatted' => $row['tanggal_lahir'] ? date('d-m-Y', strtotime($row['tanggal_lahir'])) : '-',
            'jenis_kelamin' => $jk,
            'jenis_kelamin_label' => $jk === 1 ? 'Laki-Laki' : 'Perempuan',
            'tanggal_bergabung' => $row['tanggal_bergabung'] ? date('d-m-Y H:i', strtotime($row['tanggal_bergabung'])) : '-',
            'email' => $row['email'] ?? '-',
            'no_handphone' => $row['no_handphone'] ?? '-',
            'status_karyawan_id' => $statusId,
            'status_karyawan_label' => $statusKaryawanMap[$statusId] ?? 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)',
            'login_web' => (int)($row['login_web'] ?? 1),
            'aktif' => (int)($row['aktif'] ?? 1)
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data karyawan berhasil diambil.', [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ], 200);
}

// -------------------------------------------------------------
// Hanya Role ADMIN yang diizinkan melakukan CREATE, UPDATE, DELETE
// -------------------------------------------------------------
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data karyawan.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Karyawan Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaKaryawan = trim($input['nama_karyawan'] ?? '');
    $kodeKaryawan = trim($input['kode_karyawan'] ?? '');
    $idDivisi = isset($input['id_divisi']) ? (int)$input['id_divisi'] : 1;
    $idJabatan = !empty($input['id_jabatan']) ? (int)$input['id_jabatan'] : null;
    $idSite = !empty($input['id_site']) ? (int)$input['id_site'] : null;
    $tempatLahir = trim($input['tempat_lahir'] ?? '');
    $tanggalLahir = !empty($input['tanggal_lahir']) ? $input['tanggal_lahir'] : null;
    $jenisKelamin = isset($input['jenis_kelamin']) ? (int)$input['jenis_kelamin'] : 1;
    
    $email = trim($input['email'] ?? '');
    $noHp = trim($input['no_handphone'] ?? '');
    $password = trim($input['password'] ?? '');
    if (empty($password)) {
        $password = '123456';
    }
    $statusKaryawan = isset($input['status_karyawan']) && $input['status_karyawan'] !== '' ? (int)$input['status_karyawan'] : 2;
    $loginWeb = isset($input['login_web']) ? (int)$input['login_web'] : 1;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaKaryawan)) {
        jsonResponse(false, 'Nama karyawan wajib diisi.', null, 422);
    }

    if (empty($kodeKaryawan)) {
        $resCount = $conn->query("SELECT MAX(id_karyawan) as max_id FROM karyawan");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeKaryawan = 'KRY' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    $passHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO karyawan (kode_karyawan, nama_karyawan, id_divisi, id_jabatan, id_site, tempat_lahir, tanggal_lahir, jenis_kelamin, tanggal_bergabung, aktif, email, no_handphone, password, status_karyawan, login_web) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiissiisssii", $kodeKaryawan, $namaKaryawan, $idDivisi, $idJabatan, $idSite, $tempatLahir, $tanggalLahir, $jenisKelamin, $aktif, $email, $noHp, $passHash, $statusKaryawan, $loginWeb);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();

        // Karyawan akan login melalui tabel karyawan (login.php akan mengecek field login_web), 
        // sehingga tidak perlu lagi di-insert ke tabel users (karena tabel users khusus Super Admin).

        jsonResponse(true, 'Karyawan berhasil ditambahkan.', ['id_karyawan' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan karyawan.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Karyawan
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idKaryawan = isset($input['id_karyawan']) ? (int)$input['id_karyawan'] : 0;
    $namaKaryawan = trim($input['nama_karyawan'] ?? '');
    $kodeKaryawan = trim($input['kode_karyawan'] ?? '');
    $idDivisi = isset($input['id_divisi']) ? (int)$input['id_divisi'] : 1;
    $idJabatan = !empty($input['id_jabatan']) ? (int)$input['id_jabatan'] : null;
    $idSite = !empty($input['id_site']) ? (int)$input['id_site'] : null;
    $tempatLahir = trim($input['tempat_lahir'] ?? '');
    $tanggalLahir = !empty($input['tanggal_lahir']) ? $input['tanggal_lahir'] : null;
    $jenisKelamin = isset($input['jenis_kelamin']) ? (int)$input['jenis_kelamin'] : 1;

    $email = trim($input['email'] ?? '');
    $noHp = trim($input['no_handphone'] ?? '');
    $statusKaryawan = isset($input['status_karyawan']) && $input['status_karyawan'] !== '' ? (int)$input['status_karyawan'] : 2;
    $loginWeb = isset($input['login_web']) ? (int)$input['login_web'] : 1;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if ($idKaryawan <= 0 || empty($namaKaryawan)) {
        jsonResponse(false, 'ID karyawan dan nama karyawan wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE karyawan SET kode_karyawan = ?, nama_karyawan = ?, id_divisi = ?, id_jabatan = ?, id_site = ?, 
                            tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?, email = ?, 
                            no_handphone = ?, status_karyawan = ?, login_web = ?, aktif = ? 
                            WHERE id_karyawan = ?");
    $stmt->bind_param("ssiiississiiii", $kodeKaryawan, $namaKaryawan, $idDivisi, $idJabatan, $idSite, $tempatLahir, $tanggalLahir, $jenisKelamin, $email, $noHp, $statusKaryawan, $loginWeb, $aktif, $idKaryawan);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        if (!empty($input['password'])) {
            $newPassHash = password_hash(trim($input['password']), PASSWORD_DEFAULT);
            $upK = $conn->prepare("UPDATE karyawan SET password = ? WHERE id_karyawan = ?");
            $upK->bind_param("si", $newPassHash, $idKaryawan);
            $upK->execute();
            $upK->close();
        }

        jsonResponse(true, 'Data karyawan berhasil diperbarui.', ['id_karyawan' => $idKaryawan], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui data karyawan.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Karyawan
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idKaryawan = isset($input['id_karyawan']) ? (int)$input['id_karyawan'] : (int)($_GET['id'] ?? 0);

    if ($idKaryawan <= 0) {
        jsonResponse(false, 'ID karyawan tidak valid.', null, 422);
    }

    // --- Pengecekan Relasi Database ---
    $relations = [
        ['table' => 'request_order', 'col' => 'id_karyawan_request', 'label' => 'Request Order (Peminta)'],
        ['table' => 'purchase_order', 'col' => 'id_karyawan_po', 'label' => 'Purchase Order (Purchasing)'],
        ['table' => 'users', 'col' => 'id_karyawan', 'label' => 'Akun Pengguna Sistem'],
        ['table' => 'receiving_order', 'col' => 'id_karyawan_terima', 'label' => 'Penerimaan Barang'],
        ['table' => 'site', 'col' => 'id_karyawan_headof', 'label' => 'Kepala Site / Bengkel']
    ];
    
    $inUse = [];
    foreach ($relations as $rel) {
        $q = "SELECT COUNT(*) as count FROM {$rel['table']} WHERE {$rel['col']} = ?";
        $stmtRel = $conn->prepare($q);
        if ($stmtRel) {
            $stmtRel->bind_param("i", $idKaryawan);
            $stmtRel->execute();
            $count = $stmtRel->get_result()->fetch_assoc()['count'] ?? 0;
            $stmtRel->close();
            
            if ($count > 0) {
                $inUse[] = $count . " data " . $rel['label'];
            }
        }
    }
    
    if (count($inUse) > 0) {
        $msg = "Gagal menghapus! Karyawan ini sedang digunakan pada: " . implode(", ", $inUse) . ".";
        jsonResponse(false, $msg, null, 409); // 409 Conflict
    }
    // -----------------------------------

    $stmt = $conn->prepare("DELETE FROM karyawan WHERE id_karyawan = ?");
    $stmt->bind_param("i", $idKaryawan);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Karyawan berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus karyawan. Terjadi kesalahan server.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
