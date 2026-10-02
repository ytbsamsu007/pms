<?php
/**
 * REST API: Pembatalan / Void Faktur PO
 * Path: api/faktur_po/cancel.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
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
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;

$idFaktur = isset($input['id_faktur']) && is_numeric($input['id_faktur']) ? (int)$input['id_faktur'] : 0;
$kategoriAlasan = trim($input['kategori_alasan'] ?? '');
$alasanDetail = trim($input['alasan'] ?? ($input['alasan_detail'] ?? ($input['keterangan'] ?? '')));

if ($idFaktur <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ID Faktur PO tidak valid.']);
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
    // 1. Ambil data Faktur PO
    $stmtFaktur = $conn->prepare("
        SELECT fp.*, v.nama_perusahaan as nama_vendor 
        FROM faktur_po fp 
        LEFT JOIN vendor v ON fp.id_vendor = v.id_vendor 
        WHERE fp.id_faktur = ? LIMIT 1
    ");
    $stmtFaktur->bind_param("i", $idFaktur);
    $stmtFaktur->execute();
    $resFaktur = $stmtFaktur->get_result();
    $faktur = $resFaktur ? $resFaktur->fetch_assoc() : null;
    $stmtFaktur->close();

    if (!$faktur) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Data Faktur PO tidak ditemukan.']);
        exit;
    }

    if ($faktur['status'] === 'BATAL') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Faktur PO ini sudah berstatus BATAL sebelumnya.']);
        exit;
    }

    // 2. Cek apakah sudah ada pembayaran (terbayar > 0 atau ada di payment_purchase_detail)
    $terbayar = (float)($faktur['terbayar'] ?? 0);
    $stmtPay = $conn->prepare("SELECT COUNT(*) as total_pay FROM payment_purchase_detail WHERE id_faktur = ?");
    $stmtPay->bind_param("i", $idFaktur);
    $stmtPay->execute();
    $resPay = $stmtPay->get_result();
    $payCheck = $resPay ? $resPay->fetch_assoc() : ['total_pay' => 0];
    $stmtPay->close();

    if ($terbayar > 0 || (int)$payCheck['total_pay'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Faktur tidak dapat dibatalkan karena sudah memiliki riwayat transaksi pembayaran. Faktur yang sudah terbayar tidak dapat di-void.'
        ]);
        exit;
    }

    // State sebelum batal
    $oldState = $faktur['status'] ?: 'BELUM DIBAYAR';
    $namaPetugas = $user['nama_karyawan'] ?? ($user['nama_users'] ?? ($user['username'] ?? 'Petugas'));
    $idKaryawan = $user['id_karyawan'] ?? ($user['id_users'] ?? 0);

    // Susun keterangan pembatalan
    $keteranganLengkap = "[BATAL: {$kategoriAlasan}] {$alasanDetail} (Dibatalkan oleh: {$namaPetugas})";

    // 3. Update Status Faktur menjadi BATAL
    $stmtUp = $conn->prepare("UPDATE faktur_po SET status = 'BATAL', updated_at = NOW(), keterangan = ? WHERE id_faktur = ?");
    $stmtUp->bind_param("si", $keteranganLengkap, $idFaktur);
    $stmtUp->execute();
    $stmtUp->close();

    // 4. Catat ke Activity Log
    try {
        $logDesc = "Membatalkan / Void Faktur PO {$faktur['nomor_faktur']} (State: {$oldState}). Alasan: {$kategoriAlasan} - {$alasanDetail}";
        $stmtLog = $conn->prepare("
            INSERT INTO activity_log (id_karyawan, nama_karyawan, modul, aksi, deskripsi, ip_address, user_agent) 
            VALUES (?, ?, 'FAKTUR_PO', 'BATAL', ?, ?, ?)
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

    // 5. Kirim Notifikasi Email ke Manager
    $emailResult = ['success' => false, 'sent_count' => 0];
    try {
        if (function_exists('sendCancellationNotificationDirect')) {
            $emailResult = sendCancellationNotificationDirect($conn, 'FAKTUR', $idFaktur, $faktur['nomor_faktur'], $faktur['nama_vendor'], $kategoriAlasan, $alasanDetail, $oldState, $namaPetugas);
        }
    } catch (\Throwable $eMail) {
        $emailResult = ['success' => false, 'message' => $eMail->getMessage(), 'sent_count' => 0];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Faktur PO {$faktur['nomor_faktur']} berhasil dibatalkan.",
        'data' => [
            'type' => 'FAKTUR',
            'id_faktur' => $idFaktur,
            'nomor_faktur' => $faktur['nomor_faktur'],
            'email_sent' => $emailResult['success'],
            'email_message' => $emailResult['message'] ?? ''
        ]
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem saat memproses pembatalan Faktur PO: ' . $e->getMessage()
    ]);
}
