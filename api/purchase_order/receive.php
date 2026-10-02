<?php
/**
 * API Purchase Order: Update Status Diterima (Penerimaan Barang)
 * Path: api/purchase_order/receive.php
 * Khusus Role: LOGISTIK, PURCHASING, ADMIN, MANAGER
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

require_once __DIR__ . '/../middleware/auth.php';

// Wajib Login sebagai Logistik, Purchasing, Admin, atau Manager
$currentUser = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode request tidak diizinkan. Gunakan POST.', null, 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    $input = $_POST;
}

$idPo = isset($input['id_po']) && is_numeric($input['id_po']) ? (int)$input['id_po'] : 0;
$catatanPenerimaan = isset($input['catatan']) ? trim($input['catatan']) : '';

if ($idPo <= 0) {
    jsonResponse(false, 'Parameter id_po tidak valid.', null, 400);
}

// 1. Cek keberadaan PO dan status saat ini
$stmtCheck = $conn->prepare("SELECT id_po, nomor_po, status, keterangan FROM purchase_order WHERE id_po = ? LIMIT 1");
$stmtCheck->bind_param("i", $idPo);
$stmtCheck->execute();
$currentPo = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$currentPo) {
    jsonResponse(false, 'Dokumen Purchase Order tidak ditemukan.', null, 404);
}

$currentStatusUpper = strtoupper(trim($currentPo['status'] ?? ''));

// Hanya PO berstatus DIPROSES VENDOR (atau REVIEW VENDOR) yang dapat di-update menjadi DITERIMA
if ($currentStatusUpper !== 'DIPROSES VENDOR') {
    jsonResponse(false, "Status Purchase Order {$currentPo['nomor_po']} saat ini adalah '{$currentPo['status']}'. Hanya dokumen berstatus 'DIPROSES VENDOR' yang dapat diupdate menjadi Diterima.", null, 422);
}

// 2. Update Status PO menjadi DITERIMA
$keteranganBaru = $currentPo['keterangan'] ?? '';
if (!empty($catatanPenerimaan)) {
    $keteranganBaru .= ($keteranganBaru ? "\n" : "") . "[Diterima " . date('d/m/Y H:i') . " oleh " . ($currentUser['nama_lengkap'] ?? 'Logistik') . "]: " . $catatanPenerimaan;
}

$stmtUpdate = $conn->prepare("UPDATE purchase_order SET 
    status = 'DITERIMA', 
    tanggal_status = NOW(),
    keterangan = ?
    WHERE id_po = ?");
$stmtUpdate->bind_param("si", $keteranganBaru, $idPo);

if ($stmtUpdate->execute()) {
    $stmtUpdate->close();
    jsonResponse(true, "Purchase Order {$currentPo['nomor_po']} berhasil diupdate menjadi DITERIMA.", [
        'id_po' => $idPo,
        'nomor_po' => $currentPo['nomor_po'],
        'status' => 'DITERIMA'
    ]);
} else {
    $err = $stmtUpdate->error;
    $stmtUpdate->close();
    jsonResponse(false, 'Gagal mengupdate status PO: ' . $err, null, 500);
}
