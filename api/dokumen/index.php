<?php
/**
 * API Dokumen: Attachment System Universal untuk Semua Role & Modul
 * PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Cek Login
$user = getCurrentUser();
if (!$user) {
    sendJson(false, 'Sesi Anda telah berakhir. Silakan login kembali.', null, 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// =============================================================
// 1. GET: Ambil Daftar Attachment Dokumen
// =============================================================
if ($method === 'GET' && ($action === 'list' || empty($action))) {
    $nomorDokumen = trim($_GET['nomor_dokumen'] ?? '');
    $tipeDokumen = trim($_GET['tipe_dokumen'] ?? '');

    if (empty($nomorDokumen)) {
        sendJson(false, 'Parameter nomor_dokumen wajib disertakan.', null, 422);
    }

    $sql = "SELECT d.*, 
                   k.nama_karyawan, 
                   k.kode_karyawan,
                   j.nama_jabatan,
                   (CASE WHEN d.password_open IS NOT NULL AND d.password_open != '' THEN 1 ELSE 0 END) AS is_protected
            FROM dokumen d
            LEFT JOIN karyawan k ON d.id_karyawan = k.id_karyawan
            LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            WHERE d.nomor_dokumen = ?";
    
    $params = [$nomorDokumen];
    $types = "s";

    if (!empty($tipeDokumen)) {
        $sql .= " AND d.tipe_dokumen = ?";
        $params[] = $tipeDokumen;
        $types .= "s";
    }

    $sql .= " ORDER BY d.id_dokumen DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        // Hilangkan raw password_open dari output JSON untuk keamanan
        unset($row['password_open']);
        
        // URL akses file
        if (!empty($row['file'])) {
            $row['file_url'] = BASE_URL . '/uploads/dokumen/' . $row['file'];
            $filePath = __DIR__ . '/../../uploads/dokumen/' . $row['file'];
            $row['file_size'] = file_exists($filePath) ? filesize($filePath) : 0;
            $row['file_ext'] = strtolower(pathinfo($row['file'], PATHINFO_EXTENSION));
        } else {
            $row['file_url'] = null;
            $row['file_size'] = 0;
            $row['file_ext'] = null;
        }

        $items[] = $row;
    }
    $stmt->close();

    sendJson(true, 'Data lampiran dokumen berhasil dimuat.', [
        'nomor_dokumen' => $nomorDokumen,
        'total' => count($items),
        'items' => $items
    ]);
}

// =============================================================
// 1b. GET: Ambil 10 Nomor Transaksi Terakhir Berdasarkan Tipe
// =============================================================
if ($method === 'GET' && $action === 'get_recent_transactions') {
    $tipe = trim($_GET['tipe'] ?? 'PURCHASE');
    $items = [];

    switch ($tipe) {
        case 'REQUEST':
            $sql = "SELECT nomor AS nomor, tanggal_ro AS tanggal, status, keterangan FROM request_order ORDER BY id_request DESC LIMIT 10";
            break;
        case 'PURCHASE':
            $sql = "SELECT nomor_po AS nomor, tanggal_po AS tanggal, status, keterangan FROM purchase_order ORDER BY id_po DESC LIMIT 10";
            break;
        case 'RECEIVING':
            $sql = "SELECT nomor_rcv AS nomor, tanggal_rcv AS tanggal, IF(status=1, 'SELESAI', 'DRAFT') AS status, keterangan FROM receiving_order ORDER BY id_rcv DESC LIMIT 10";
            break;
        case 'RETUR PO':
            $sql = "SELECT nomor_po_retur AS nomor, tanggal_po_retur AS tanggal, status, keterangan FROM retur_po ORDER BY id_po_retur DESC LIMIT 10";
            break;
        case 'FAKTUR PO':
            $sql = "SELECT nomor_faktur AS nomor, tanggal_faktur_vendor AS tanggal, status, keterangan FROM faktur_po ORDER BY id_faktur DESC LIMIT 10";
            break;
        case 'PAYMENT PO':
            $sql = "SELECT kode_pembayaran AS nomor, tanggal_bayar AS tanggal, IF(id_karyawan_approved IS NOT NULL, 'APPROVED', 'MENUNGGU') AS status, keterangan FROM payment_purchase_detail ORDER BY id_pembayaran_detail DESC LIMIT 10";
            break;
        case 'MUTASI BARANG':
            $sql = "SELECT kode_mutasi AS nomor, tanggal_mutasi AS tanggal, status, keterangan FROM mutasi_order ORDER BY id_mutasi DESC LIMIT 10";
            break;
        case 'ADJUSTMENT STOK':
            $sql = "SELECT nomor_adjustment AS nomor, tanggal_adjustment AS tanggal, status, alasan AS keterangan FROM adjustment_stok ORDER BY id_adjustment DESC LIMIT 10";
            break;
        default:
            $sql = "SELECT nomor_po AS nomor, tanggal_po AS tanggal, status, keterangan FROM purchase_order ORDER BY id_po DESC LIMIT 10";
            break;
    }

    try {
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                if (!empty($row['nomor'])) {
                    $items[] = [
                        'nomor' => $row['nomor'],
                        'tanggal' => $row['tanggal'] ?? null,
                        'status' => $row['status'] ?? null,
                        'keterangan' => $row['keterangan'] ?? ''
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // Fallback gracefully
    }

    sendJson(true, 'Data transaksi terakhir berhasil dimuat.', [
        'tipe' => $tipe,
        'items' => $items
    ]);
}
if ($method === 'POST' && $action === 'verify_download') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) $input = $_POST;

    $idDokumen = intval($input['id_dokumen'] ?? 0);
    $passwordInput = trim($input['password'] ?? '');

    if ($idDokumen <= 0) {
        sendJson(false, 'ID Dokumen tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("SELECT id_dokumen, password_open, file, external_url, unduh FROM dokumen WHERE id_dokumen = ? LIMIT 1");
    $stmt->bind_param("i", $idDokumen);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$doc) {
        sendJson(false, 'Dokumen tidak ditemukan.', null, 404);
    }

    // Jika berpassword, validasi password
    if (!empty($doc['password_open'])) {
        if ($doc['password_open'] !== $passwordInput && !password_verify($passwordInput, $doc['password_open'])) {
            sendJson(false, 'Password yang Anda masukkan salah.', null, 403);
        }
    }

    // Tambah counter unduh
    $conn->query("UPDATE dokumen SET unduh = unduh + 1 WHERE id_dokumen = $idDokumen");

    $targetUrl = !empty($doc['file']) ? (BASE_URL . '/uploads/dokumen/' . $doc['file']) : $doc['external_url'];

    sendJson(true, 'Verifikasi berhasil.', [
        'url' => $targetUrl,
        'is_external' => empty($doc['file'])
    ]);
}

// =============================================================
// 3. POST: Upload File Baru atau Simpan Link Eksternal
// =============================================================
if ($method === 'POST' && ($action === 'upload' || empty($action))) {
    $tipeDokumen = trim($_POST['tipe_dokumen'] ?? '');
    $nomorDokumen = trim($_POST['nomor_dokumen'] ?? '');
    $namaDokumen = trim($_POST['nama_dokumen'] ?? '');
    $tanggalDokumen = !empty($_POST['tanggal_dokumen']) ? trim($_POST['tanggal_dokumen']) : date('Y-m-d');
    $externalUrl = trim($_POST['external_url'] ?? '');
    $passwordOpen = trim($_POST['password_open'] ?? '');
    
    // Tentukan user id_karyawan
    $idKaryawan = $user['id_karyawan'] ?? null;
    if (empty($idKaryawan) && !empty($user['id_users'])) {
        // Cek id_karyawan default
        $idKaryawan = 1;
    }

    // Validasi
    $allowedTypes = ['REQUEST','PURCHASE','RECEIVING','RETUR PO','FAKTUR PO','PAYMENT PO','MUTASI BARANG','ADJUSTMENT STOK'];
    if (!in_array($tipeDokumen, $allowedTypes)) {
        sendJson(false, 'Tipe dokumen tidak valid. Pilihan: ' . implode(', ', $allowedTypes), null, 422);
    }

    if (empty($nomorDokumen)) {
        sendJson(false, 'Nomor dokumen transaksi wajib diisi.', null, 422);
    }

    if (empty($namaDokumen)) {
        sendJson(false, 'Nama / Keterangan label dokumen wajib diisi.', null, 422);
    }

    $fileName = null;

    // Handle Upload File Fisik (jika ada file diunggah)
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['file']['tmp_name'];
        $origName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Validasi ekstensi (Hanya PDF dan JPG/JPEG)
        $allowedExts = ['pdf', 'jpg', 'jpeg'];
        if (!in_array($ext, $allowedExts)) {
            sendJson(false, 'Format file tidak diizinkan. Hanya mendukung file PDF dan JPG/JPEG.', null, 422);
        }

        // Validasi ukuran maks 5MB
        if ($fileSize > 5 * 1024 * 1024) {
            sendJson(false, 'Ukuran file melebihi batas maksimal 5MB.', null, 422);
        }

        $uploadDir = __DIR__ . '/../../uploads/dokumen/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Format nama file: DOK_[TIPE]_[NOMORCLEAN]_[TIMESTAMP]_[RAND].[EXT]
        $cleanNomor = preg_replace('/[^A-Za-z0-9]/', '_', $nomorDokumen);
        $fileName = 'DOC_' . $cleanNomor . '_' . date('YmdHis') . '_' . rand(100, 999) . '.' . $ext;

        if (!move_uploaded_file($fileTmp, $uploadDir . $fileName)) {
            sendJson(false, 'Gagal memindahkan file yang diunggah ke folder server.', null, 500);
        }
    }

    // Jika tidak upload file fisik, wajib ada external_url
    if (empty($fileName) && empty($externalUrl)) {
        sendJson(false, 'Harap sertakan file yang diupload atau URL Link Eksternal (Google Drive / Cloud).', null, 422);
    }

    $finalPassword = !empty($passwordOpen) ? $passwordOpen : null;
    $finalExternalUrl = !empty($externalUrl) ? $externalUrl : null;

    $stmt = $conn->prepare("INSERT INTO dokumen 
        (tipe_dokumen, nomor_dokumen, nama_dokumen, tanggal_dokumen, id_karyawan, password_open, file, external_url, unduh, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
    
    $stmt->bind_param("ssssisss", $tipeDokumen, $nomorDokumen, $namaDokumen, $tanggalDokumen, $idKaryawan, $finalPassword, $fileName, $finalExternalUrl);

    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $stmt->close();

        sendJson(true, 'Lampiran dokumen berhasil ditambahkan!', [
            'id_dokumen' => $insertId,
            'nomor_dokumen' => $nomorDokumen,
            'nama_dokumen' => $namaDokumen,
            'file' => $fileName,
            'external_url' => $finalExternalUrl
        ]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        sendJson(false, 'Gagal menyimpan data lampiran ke database: ' . $err, null, 500);
    }
}

// =============================================================
// 4. DELETE / POST action=delete: Hapus Lampiran
// =============================================================
if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) $input = $_POST;

    $idDokumen = intval($input['id_dokumen'] ?? $_GET['id'] ?? 0);

    if ($idDokumen <= 0) {
        sendJson(false, 'ID Dokumen tidak valid.', null, 422);
    }

    // Cek data dokumen
    $stmt = $conn->prepare("SELECT id_dokumen, file, id_karyawan FROM dokumen WHERE id_dokumen = ? LIMIT 1");
    $stmt->bind_param("i", $idDokumen);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$doc) {
        sendJson(false, 'Dokumen tidak ditemukan.', null, 404);
    }

    // Hak akses: hanya uploader (atau role Admin) yang berhak menghapus dokumen
    $currentIdKaryawan = intval($user['id_karyawan'] ?? 0);
    $docOwnerId = intval($doc['id_karyawan'] ?? 0);
    $userRole = strtoupper($user['role'] ?? $user['nama_role'] ?? '');

    if ($currentIdKaryawan !== $docOwnerId && !in_array($userRole, ['ADMIN', 'SUPERADMIN', 'DEVELOPER'])) {
        sendJson(false, 'Anda tidak memiliki hak akses untuk menghapus dokumen ini. Dokumen hanya dapat dihapus oleh pengunggah.', null, 403);
    }

    // Hapus file fisik jika ada
    if (!empty($doc['file'])) {
        $filePath = __DIR__ . '/../../uploads/dokumen/' . $doc['file'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Hapus dari database
    $del = $conn->prepare("DELETE FROM dokumen WHERE id_dokumen = ?");
    $del->bind_param("i", $idDokumen);
    if ($del->execute()) {
        $del->close();
        sendJson(true, 'Lampiran dokumen berhasil dihapus.');
    } else {
        $err = $del->error;
        $del->close();
        sendJson(false, 'Gagal menghapus dokumen dari database: ' . $err, null, 500);
    }
}

sendJson(false, 'Aksi tidak dikenali atau metode HTTP tidak didukung.', null, 400);
