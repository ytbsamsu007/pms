<?php
/**
 * REST API: Pembatalan Purchase Order
 * Path: api/purchase_order/cancel.php
 * Khusus Role: ADMIN, PURCHASING, MANAGER
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak didukung. Gunakan POST.']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/mailer.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;

$idPo = isset($input['id_po']) && is_numeric($input['id_po']) ? (int)$input['id_po'] : 0;
$kategoriAlasan = trim($input['kategori_alasan'] ?? '');
$alasanDetail = trim($input['alasan'] ?? ($input['alasan_detail'] ?? ($input['keterangan'] ?? '')));

if ($idPo <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID Purchase Order tidak valid.']);
    exit;
}

if (empty($kategoriAlasan)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Kategori alasan pembatalan wajib dipilih.']);
    exit;
}

if (empty($alasanDetail) || mb_strlen($alasanDetail) < 5) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Rincian alasan pembatalan wajib diisi minimal 5 karakter.']);
    exit;
}

try {
    // 1. Ambil data PO
    $stmtPo = $conn->prepare("
        SELECT p.*, v.nama_perusahaan as nama_vendor, k.nama_karyawan as pembuat_po 
        FROM purchase_order p 
        LEFT JOIN vendor v ON p.id_vendor = v.id_vendor 
        LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan 
        WHERE p.id_po = ? LIMIT 1
    ");
    $stmtPo->bind_param("i", $idPo);
    $stmtPo->execute();
    $resPo = $stmtPo->get_result();
    $po = $resPo ? $resPo->fetch_assoc() : null;
    $stmtPo->close();

    if (!$po) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Data Purchase Order tidak ditemukan.']);
        exit;
    }

    if ($po['status'] === 'BATAL') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Purchase Order ini sudah berstatus BATAL sebelumnya.']);
        exit;
    }

    // 2. Cek apakah PO sudah pernah Receiving fisik di site
    $stmtRcv = $conn->prepare("SELECT COUNT(*) as total_rcv FROM receiving_order WHERE id_po = ?");
    $stmtRcv->bind_param("i", $idPo);
    $stmtRcv->execute();
    $resRcv = $stmtRcv->get_result();
    $rcvCheck = $resRcv ? $resRcv->fetch_assoc() : ['total_rcv' => 0];
    $stmtRcv->close();

    if ((int)$rcvCheck['total_rcv'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'PO tidak dapat dibatalkan karena barang sudah pernah diterima fisik di site (Receiving). Silakan gunakan modul Retur PO untuk pengembalian barang cacat/rusak ke vendor.'
        ]);
        exit;
    }

    // 3. Cek apakah PO sudah ada Faktur
    $stmtFaktur = $conn->prepare("SELECT COUNT(*) as total_faktur FROM faktur_po WHERE id_po = ? AND status != 'BATAL'");
    $stmtFaktur->bind_param("i", $idPo);
    $stmtFaktur->execute();
    $resFaktur = $stmtFaktur->get_result();
    $fakturCheck = $resFaktur ? $resFaktur->fetch_assoc() : ['total_faktur' => 0];
    $stmtFaktur->close();

    if ((int)$fakturCheck['total_faktur'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'PO tidak dapat dibatalkan karena sudah memiliki Faktur Tagihan aktif. Silakan batalkan Faktur terlebih dahulu.'
        ]);
        exit;
    }

    // State sebelum batal
    $oldState = $po['status'] ?: 'DRAFT';
    $namaPetugas = $user['nama_karyawan'] ?? ($user['nama_users'] ?? ($user['username'] ?? 'Petugas'));
    $idKaryawan = $user['id_karyawan'] ?? ($user['id_users'] ?? 0);

    // Susun keterangan pembatalan
    $keteranganLengkap = "[BATAL: {$kategoriAlasan}] {$alasanDetail} (Dibatalkan oleh: {$namaPetugas})";

    // 4. Update Status PO menjadi BATAL & simpan keterangan
    $stmtUpPo = $conn->prepare("UPDATE purchase_order SET status = 'BATAL', tanggal_status = NOW(), keterangan = ? WHERE id_po = ?");
    $stmtUpPo->bind_param("si", $keteranganLengkap, $idPo);
    $stmtUpPo->execute();
    $stmtUpPo->close();

    // 5. Catat ke Activity Log
    try {
        $logDesc = "Membatalkan Purchase Order {$po['nomor_po']} (State: {$oldState}). Alasan: {$kategoriAlasan} - {$alasanDetail}";
        $stmtLog = $conn->prepare("
            INSERT INTO activity_log (id_karyawan, nama_karyawan, modul, aksi, deskripsi, ip_address, user_agent) 
            VALUES (?, ?, 'PURCHASE_ORDER', 'BATAL', ?, ?, ?)
        ");
        if ($stmtLog) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Browser';
            $stmtLog->bind_param("issss", $idKaryawan, $namaPetugas, $logDesc, $ip, $ua);
            $stmtLog->execute();
            $stmtLog->close();
        }
    } catch (\Throwable $eLog) {
        // Non-blocking log error
    }

    // 6. Kirim Notifikasi Email ke Manager
    $emailResult = ['success' => false, 'sent_count' => 0];
    try {
        if (function_exists('sendCancellationNotificationDirect')) {
            $emailResult = sendCancellationNotificationDirect($conn, 'PO', $idPo, $po['nomor_po'], $po['nama_vendor'], $kategoriAlasan, $alasanDetail, $oldState, $namaPetugas);
        }
    } catch (\Throwable $eMail) {
        $emailResult = ['success' => false, 'message' => $eMail->getMessage(), 'sent_count' => 0];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Purchase Order {$po['nomor_po']} berhasil dibatalkan.",
        'data' => [
            'type' => 'PO',
            'id_po' => $idPo,
            'nomor_po' => $po['nomor_po'],
            'email_sent' => $emailResult['success'],
            'email_message' => $emailResult['message'] ?? ''
        ]
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem saat memproses pembatalan PO: ' . $e->getMessage()
    ]);
}
