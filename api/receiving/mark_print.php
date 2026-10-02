<?php
/**
 * API Receiving: Mark Document as Printed, Migrasikan Stok ke Gudang, & Update Status RO
 * Path: api/receiving/mark_print.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 * Aturan:
 * - Penambahan stok fisik dan penguncian permanen terjadi saat SPB dicetak (print)
 * - Status request_order yang terhubung ke PO otomatis diupdate menjadi:
 *   - 'DITERIMA FULL' (jika receiving_order.status = 1)
 *   - 'DITERIMA SEBAGIAN' (jika receiving_order.status = 0)
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

$currentUser = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode request tidak diizinkan. Gunakan POST.', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$idRcv = isset($input['id_rcv']) && is_numeric($input['id_rcv']) ? (int)$input['id_rcv'] : 0;

if ($idRcv <= 0) {
    jsonResponse(false, 'Parameter id_rcv tidak valid.', null, 400);
}

// 1. Ambil detail dokumen receiving & PO
$stmt = $conn->prepare("SELECT ro.id_rcv, ro.nomor_rcv, ro.id_po, ro.status as status_rcv, ro.print, po.id_site, po.nomor_po 
                        FROM receiving_order ro 
                        LEFT JOIN purchase_order po ON ro.id_po = po.id_po 
                        WHERE ro.id_rcv = ? LIMIT 1");
$stmt->bind_param("i", $idRcv);
$stmt->execute();
$rcv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rcv) {
    jsonResponse(false, 'Dokumen Penerimaan Barang tidak ditemukan.', null, 404);
}

$idSite = (int)$rcv['id_site'];
$idPo = (int)$rcv['id_po'];

// Jika sudah pernah diprint, tidak perlu migrasikan stok ganda
if ((int)$rcv['print'] === 1) {
    jsonResponse(true, 'Dokumen Penerimaan Barang sudah dicetak sebelumnya.', [
        'id_rcv' => $idRcv,
        'print' => 1,
        'already_printed' => true
    ]);
}

$conn->begin_transaction();

try {
    // 2. Ambil barang-barang yang berstatus BAIK (status_qc = 1) untuk dimasukkan ke stok
    $stmtItems = $conn->prepare("SELECT id_barang, qty, status_qc FROM receiving_order_detail WHERE id_rcv = ? AND status_qc = 1");
    $stmtItems->bind_param("i", $idRcv);
    $stmtItems->execute();
    $itemsBaik = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItems->close();

    $stmtCheckStok = $conn->prepare("SELECT id_stok, stok FROM barang_stok WHERE id_barang = ? AND id_site = ? LIMIT 1");
    $stmtUpStok = $conn->prepare("UPDATE barang_stok SET stok = stok + ? WHERE id_stok = ?");
    $stmtInsStok = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");

    foreach ($itemsBaik as $item) {
        $idBarang = (int)$item['id_barang'];
        $qtyBaik = (int)$item['qty'];

        if ($idBarang > 0 && $qtyBaik > 0) {
            $stmtCheckStok->bind_param("ii", $idBarang, $idSite);
            $stmtCheckStok->execute();
            $stokRow = $stmtCheckStok->get_result()->fetch_assoc();

            if ($stokRow) {
                $idStok = (int)$stokRow['id_stok'];
                $stmtUpStok->bind_param("ii", $qtyBaik, $idStok);
                $stmtUpStok->execute();
            } else {
                $stmtInsStok->bind_param("iii", $idBarang, $idSite, $qtyBaik);
                $stmtInsStok->execute();
            }
        }
    }

    $stmtCheckStok->close();
    $stmtUpStok->close();
    $stmtInsStok->close();

    // 3. Tandai dokumen print = 1 dan tanggal cetak
    $stmtPrint = $conn->prepare("UPDATE receiving_order SET `print` = 1, `print_date` = NOW() WHERE id_rcv = ?");
    $stmtPrint->bind_param("i", $idRcv);
    $stmtPrint->execute();
    $stmtPrint->close();

    // 4. Update status Purchase Order menjadi DITERIMA
    $stmtPo = $conn->prepare("UPDATE purchase_order SET status = 'DITERIMA', tanggal_status = NOW(), id_receiving = ? WHERE id_po = ?");
    $stmtPo->bind_param("ii", $idRcv, $idPo);
    $stmtPo->execute();
    $stmtPo->close();

    // 5. Update status Request Order yang terhubung ke PO ini menjadi DITERIMA FULL / DITERIMA SEBAGIAN
    $roStatus = ((int)$rcv['status_rcv'] === 1) ? 'DITERIMA FULL' : 'DITERIMA SEBAGIAN';
    $stmtUpRo = $conn->prepare("UPDATE request_order SET status = ?, tanggal_status = NOW() WHERE id_po = ?");
    $stmtUpRo->bind_param("si", $roStatus, $idPo);
    $stmtUpRo->execute();
    $stmtUpRo->close();

    $conn->commit();

    logActivity($conn, [
        'modul' => 'RECEIVING',
        'aksi' => 'PRINT_MIGRASI_STOK',
        'id_referensi' => $idRcv,
        'nomor_referensi' => $rcv['nomor_rcv'],
        'deskripsi' => "Mencetak SPB {$rcv['nomor_rcv']}, mengunci dokumen & memigrasikan stok barang ke Gudang (Site ID: {$idSite})",
        'data_sesudahnya' => [
            'id_rcv' => $idRcv,
            'nomor_rcv' => $rcv['nomor_rcv'],
            'id_po' => $idPo,
            'nomor_po' => $rcv['nomor_po'] ?? null,
            'id_site' => $idSite,
            'status_ro' => $roStatus
        ]
    ]);

    jsonResponse(true, "Surat Penerimaan Barang berhasil dicetak. Stok barang resmi dimigrasikan ke gudang, status PO menjadi DITERIMA, dan status RO menjadi {$roStatus}.", [
        'id_rcv' => $idRcv,
        'nomor_rcv' => $rcv['nomor_rcv'],
        'print' => 1,
        'print_date' => date('Y-m-d H:i:s'),
        'stok_migrated' => true,
        'ro_status' => $roStatus
    ]);

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Gagal memproses cetak dan migrasi stok: ' . $e->getMessage(), null, 500);
}
