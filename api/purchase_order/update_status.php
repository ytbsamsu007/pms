<?php
/**
 * API Purchase Order: Update Status Endpoint - PT Jaya Teknis
 * Path: api/purchase_order/update_status.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
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
require_once __DIR__ . '/../../config/activity_logger.php';

// Auth Protection: Purchasing, Manager, Admin
$currentUser = apiAuth();
$userRole = strtoupper($currentUser['nama_jabatan'] ?? $currentUser['level'] ?? ($currentUser['role'] ?? ''));
$allowedRoles = ['ADMIN', 'ADMINISTRATOR', 'PURCHASING', 'STAFF PURCHASING', 'MANAGER', 'MANAGER CABANG'];
if (!in_array($userRole, $allowedRoles)) {
    jsonResponse(false, 'Akses ditolak. Fitur pembaruan status PO hanya dapat diakses oleh Purchasing atau Manajemen.', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan POST.', null, 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;

$idPo = isset($input['id_po']) && is_numeric($input['id_po']) ? (int)$input['id_po'] : 0;
$newStatus = trim($input['status'] ?? '');
$catatan = trim($input['keterangan'] ?? $input['catatan'] ?? '');
$tanggalPengiriman = !empty($input['tanggal_pengiriman']) ? trim($input['tanggal_pengiriman']) : null;

if ($idPo <= 0) {
    jsonResponse(false, 'ID Purchase Order tidak valid.', null, 422);
}

$allowedStatuses = ['DRAFT', 'REVIEW INTERNAL', 'DISETUJUI INTERNAL', 'TIDAK DISETUJUI INTERNAL', 'REVIEW VENDOR', 'DIPROSES VENDOR', 'DITERIMA', 'BATAL'];
if (!in_array($newStatus, $allowedStatuses)) {
    jsonResponse(false, 'Status Purchase Order yang dipilih tidak valid.', null, 422);
}

// 1. Ambil data PO saat ini
$stmt = $conn->prepare("SELECT id_po, nomor_po, status, keterangan, tanggal_pengiriman FROM purchase_order WHERE id_po = ? LIMIT 1");
$stmt->bind_param("i", $idPo);
$stmt->execute();
$currentPo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$currentPo) {
    jsonResponse(false, 'Purchase Order tidak ditemukan.', null, 404);
}

$oldStatus = $currentPo['status'];

// Cek status terkunci
if (in_array(strtoupper($oldStatus), ['DITERIMA', 'BATAL'])) {
    jsonResponse(false, "Purchase Order {$currentPo['nomor_po']} sudah berstatus '{$oldStatus}' (Terkunci permanen).", null, 400);
}

// 2. Susun keterangan baru jika ada catatan
$keteranganLama = trim($currentPo['keterangan'] ?? '');
$keteranganBaru = $keteranganLama;
if (!empty($catatan)) {
    $petugas = $currentUser['nama_karyawan'] ?? ($currentUser['nama_users'] ?? 'Petugas');
    $timeNow = date('d/m/Y H:i');
    $logNote = "[{$timeNow} - {$petugas} (Status: {$newStatus})]: {$catatan}";
    $keteranganBaru = !empty($keteranganLama) ? ($keteranganLama . "\n" . $logNote) : $logNote;
}

// 3. Eksekusi Update
if (!empty($tanggalPengiriman) && strtotime($tanggalPengiriman)) {
    $tglKirimDb = date('Y-m-d H:i:s', strtotime($tanggalPengiriman));
    $stmtUp = $conn->prepare("UPDATE purchase_order SET status = ?, tanggal_status = NOW(), tanggal_pengiriman = ?, keterangan = ? WHERE id_po = ?");
    $stmtUp->bind_param("sssi", $newStatus, $tglKirimDb, $keteranganBaru, $idPo);
} else {
    $stmtUp = $conn->prepare("UPDATE purchase_order SET status = ?, tanggal_status = NOW(), keterangan = ? WHERE id_po = ?");
    $stmtUp->bind_param("ssi", $newStatus, $keteranganBaru, $idPo);
}

if ($stmtUp->execute()) {
    $stmtUp->close();

    // 4. Catat ke Activity Log
    try {
        logActivity($conn, [
            'modul'           => 'PURCHASE_ORDER',
            'aksi'            => 'UPDATE_STATUS',
            'id_referensi'    => $idPo,
            'nomor_referensi' => $currentPo['nomor_po'],
            'deskripsi'       => "Status PO {$currentPo['nomor_po']} diperbarui dari '{$oldStatus}' menjadi '{$newStatus}'",
            'data_sebelumnya' => ['status' => $oldStatus],
            'data_sesudahnya' => ['status' => $newStatus, 'keterangan' => $catatan],
            'id_karyawan'     => $currentUser['id_karyawan'] ?? null,
            'nama_pengguna'   => $currentUser['nama_karyawan'] ?? ($currentUser['nama_users'] ?? 'Petugas'),
            'role'            => $userRole
        ]);
    } catch (\Throwable $t) {
        error_log("Gagal mencatat log update status PO: " . $t->getMessage());
    }

    jsonResponse(true, "Status Purchase Order {$currentPo['nomor_po']} berhasil diperbarui menjadi '{$newStatus}'.", [
        'id_po' => $idPo,
        'nomor_po' => $currentPo['nomor_po'],
        'old_status' => $oldStatus,
        'new_status' => $newStatus
    ]);
} else {
    $err = $stmtUp->error;
    $stmtUp->close();
    jsonResponse(false, 'Gagal memperbarui status Purchase Order: ' . $err, null, 500);
}
