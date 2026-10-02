<?php
/**
 * API Purchase Order: Update Endpoint - PT Jaya Teknis
 * Path: api/purchase_order/update.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

// Wajib Login sebagai Purchasing, Admin, atau Manager (Logistik tidak dapat mengedit PO)
$currentUser = apiAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);
$currentRole = strtoupper($currentUser['role'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(false, 'Metode request tidak diizinkan. Gunakan POST/PUT.', null, 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input) {
    $input = $_POST;
}

$idPo = isset($input['id_po']) && is_numeric($input['id_po']) ? (int)$input['id_po'] : 0;

if ($idPo <= 0) {
    jsonResponse(false, 'Parameter id_po tidak valid.', null, 400);
}

// 1. Cek keberadaan PO dan validasi status penguncian
$stmtCheck = $conn->prepare("SELECT id_po, nomor_po, status FROM purchase_order WHERE id_po = ? LIMIT 1");
$stmtCheck->bind_param("i", $idPo);
$stmtCheck->execute();
$currentPo = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$currentPo) {
    jsonResponse(false, 'Dokumen Purchase Order tidak ditemukan.', null, 404);
}

$currentStatusUpper = strtoupper(trim($currentPo['status'] ?? ''));

// Aturan Penguncian:
// Jika status 'DIPROSES VENDOR', 'DITERIMA', atau 'BATAL' -> Terkunci dari edit data formulir
if (in_array($currentStatusUpper, ['DIPROSES VENDOR', 'DITERIMA', 'BATAL'])) {
    jsonResponse(false, "Dokumen Purchase Order {$currentPo['nomor_po']} berstatus '{$currentPo['status']}' dan sudah tidak dapat diubah datanya.", null, 422);
}

// 2. Ekstrak data yang diperbarui
$tanggalPo = !empty($input['tanggal_po']) ? trim($input['tanggal_po']) : date('Y-m-d');
$prioritas = (!empty($input['prioritas']) && strtoupper($input['prioritas']) === 'URGENT') ? 'URGENT' : 'NORMAL';
$alamat = isset($input['alamat']) ? trim($input['alamat']) : '';
$pengiriman = isset($input['pengiriman']) ? trim($input['pengiriman']) : 'Vendor';
$tanggalPengiriman = !empty($input['tanggal_pengiriman']) ? trim($input['tanggal_pengiriman']) : null;
$top = isset($input['term_of_payment']) ? (int)$input['term_of_payment'] : 30;
$pajak = isset($input['pajak']) ? (int)$input['pajak'] : 0;
$totalTermasukPajak = !empty($input['total_termasuk_pajak']) ? 1 : 0;
$pajakPpnbm = isset($input['pajak_PPnBM']) ? (int)$input['pajak_PPnBM'] : 0;
$totalTermasukPpnbm = !empty($input['total_termasuk_PPnBM']) ? 1 : 0;
$diskon = isset($input['diskon']) ? (float)$input['diskon'] : 0;
$keterangan = isset($input['keterangan']) ? trim($input['keterangan']) : '';
$newStatus = !empty($input['status']) ? trim($input['status']) : $currentPo['status'];

// Validasi status baru
$allowedNewStatuses = ['DRAFT', 'REVIEW INTERNAL', 'DISETUJUI INTERNAL', 'TIDAK DISETUJUI INTERNAL', 'REVIEW VENDOR', 'DIPROSES VENDOR', 'DITERIMA', 'BATAL'];
if (!in_array($newStatus, $allowedNewStatuses)) {
    $newStatus = $currentPo['status'];
}

$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

$conn->begin_transaction();

try {
    // 3. Update Header Purchase Order
    $stmtUpdate = $conn->prepare("UPDATE purchase_order SET
        tanggal_po = ?,
        prioritas = ?,
        alamat = ?,
        pengiriman = ?,
        tanggal_pengiriman = ?,
        term_of_payment = ?,
        pajak = ?,
        total_termasuk_pajak = ?,
        pajak_PPnBM = ?,
        total_termasuk_PPnBM = ?,
        diskon = ?,
        keterangan = ?,
        status = ?,
        tanggal_status = NOW()
        WHERE id_po = ?");

    $stmtUpdate->bind_param(
        "sssssiiiiidssi",
        $tanggalPo,
        $prioritas,
        $alamat,
        $pengiriman,
        $tanggalPengiriman,
        $top,
        $pajak,
        $totalTermasukPajak,
        $pajakPpnbm,
        $totalTermasukPpnbm,
        $diskon,
        $keterangan,
        $newStatus,
        $idPo
    );

    if (!$stmtUpdate->execute()) {
        throw new Exception("Gagal memperbarui header PO: " . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    // 4. Update Detail Items (Harga, Diskon, Subtotal)
    if (!empty($items)) {
        $stmtItemUp = $conn->prepare("UPDATE purchase_order_detail SET 
            qty = ?, 
            harga = ?, 
            diskon = ?, 
            subtotal = ?,
            keterangan = ?
            WHERE id_po_detail = ? AND id_po = ?");

        foreach ($items as $item) {
            $idPoDetail = (int)($item['id_po_detail'] ?? 0);
            if ($idPoDetail <= 0) continue;

            $qty = (float)($item['qty'] ?? 1);
            $harga = (float)($item['harga'] ?? 0);
            $itemDiskon = (float)($item['diskon'] ?? 0);
            $subtotal = max(0, ($qty * $harga) - $itemDiskon);
            $itemKet = isset($item['keterangan']) ? trim($item['keterangan']) : '';

            $stmtItemUp->bind_param("ddddsii", $qty, $harga, $itemDiskon, $subtotal, $itemKet, $idPoDetail, $idPo);
            if (!$stmtItemUp->execute()) {
                throw new Exception("Gagal memperbarui item PO detail: " . $stmtItemUp->error);
            }
        }
        $stmtItemUp->close();
    }

    $conn->commit();

    // Catat Log Aktivitas Pengguna
    require_once __DIR__ . '/../../config/activity_logger.php';
    $aksiLog = 'UPDATE';
    if ($newStatus === 'BATAL') $aksiLog = 'BATAL';
    elseif ($newStatus === 'REVIEW VENDOR') $aksiLog = 'STATUS_REVIEW';
    elseif ($newStatus === 'DIPROSES VENDOR') $aksiLog = 'STATUS_DIPROSES';

    logActivity($conn, [
        'modul'           => 'PURCHASE_ORDER',
        'aksi'            => $aksiLog,
        'id_referensi'    => $idPo,
        'nomor_referensi' => $currentPo['nomor_po'],
        'deskripsi'       => "Memperbarui Purchase Order {$currentPo['nomor_po']} (Status: {$currentPo['status']} -> {$newStatus}).",
        'data_sebelumnya' => [
            'status'      => $currentPo['status'],
            'pajak'       => $currentPo['pajak'] ?? null,
            'diskon'      => $currentPo['diskon'] ?? null
        ],
        'data_sesudahnya' => [
            'status'      => $newStatus,
            'pajak'       => $pajak,
            'diskon'      => $diskon,
            'keterangan'  => $keterangan,
            'total_items' => count($items)
        ]
    ]);

    jsonResponse(true, "Purchase Order {$currentPo['nomor_po']} berhasil diperbarui.", [
        'id_po' => $idPo,
        'nomor_po' => $currentPo['nomor_po'],
        'status' => $newStatus
    ]);

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
}
