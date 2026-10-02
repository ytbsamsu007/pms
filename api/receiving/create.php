<?php
/**
 * API Receiving: Simpan Penerimaan Barang (Logistik)
 * Path: api/receiving/create.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 * Database: receiving_order & receiving_order_detail
 * Catatan: Migrasi stok fisik gudang terjadi saat dokumen SPB dicetak (print)
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

// Wajib Login sebagai Logistik, Admin, atau Manager
$currentUser = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode request tidak diizinkan. Gunakan POST.', null, 405);
}

// Support FormData (multipart/form-data) and JSON
$input = $_POST;
if (empty($input)) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

$idPo = isset($input['id_po']) && is_numeric($input['id_po']) ? (int)$input['id_po'] : 0;
$nomorRcv = isset($input['nomor_rcv']) ? trim($input['nomor_rcv']) : '';
$nomorSj = isset($input['nomor_sj']) ? trim($input['nomor_sj']) : '';
$tanggalDiterima = !empty($input['tanggal_diterima']) ? trim($input['tanggal_diterima']) : date('Y-m-d H:i:s');
$keterangan = isset($input['keterangan']) ? trim($input['keterangan']) : '';

// Parse items jika dikirim dalam bentuk JSON string dari FormData
$items = [];
if (isset($input['items'])) {
    if (is_array($input['items'])) {
        $items = $input['items'];
    } else {
        $items = json_decode($input['items'], true) ?? [];
    }
}

if ($idPo <= 0) {
    jsonResponse(false, 'Parameter id_po tidak valid.', null, 400);
}

if (empty($nomorSj)) {
    jsonResponse(false, 'Nomor Surat Pengantar Barang (SPB) / Surat Jalan Vendor wajib diisi.', null, 422);
}

if (empty($items)) {
    jsonResponse(false, 'Rincian barang yang diterima tidak boleh kosong.', null, 422);
}

// 1. Upload File Surat Jalan (PDF / JPG / PNG, Max 2MB)
$filenameSj = null;
if (isset($_FILES['file_sj']) && $_FILES['file_sj']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file_sj'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if ($file['size'] > $maxSize) {
        jsonResponse(false, 'Ukuran file surat jalan melebihi batas maksimal 2 MB.', null, 422);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowedExts)) {
        jsonResponse(false, 'Format file surat jalan tidak valid. Gunakan PDF, JPG, atau PNG.', null, 422);
    }

    $uploadDir = __DIR__ . '/../../uploads/surat_jalan/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filenameSj = "SJ_" . date('ymd_His') . "_" . substr(md5(uniqid()), 0, 6) . "." . $ext;
    $targetPath = $uploadDir . $filenameSj;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        jsonResponse(false, 'Gagal mengunggah file surat jalan ke server.', null, 500);
    }
}

// 2. Verifikasi Dokumen PO
$stmtPo = $conn->prepare("SELECT id_po, nomor_po, id_site, id_vendor, status FROM purchase_order WHERE id_po = ? LIMIT 1");
$stmtPo->bind_param("i", $idPo);
$stmtPo->execute();
$currentPo = $stmtPo->get_result()->fetch_assoc();
$stmtPo->close();

if (!$currentPo) {
    jsonResponse(false, 'Purchase Order tidak ditemukan.', null, 404);
}

if ($currentPo['status'] === 'DITERIMA') {
    jsonResponse(false, "Purchase Order {$currentPo['nomor_po']} sudah berstatus DITERIMA sebelumnya.", null, 422);
}

// Cek apakah PO sudah pernah dibuatkan receiving order
$stmtCheckExisting = $conn->prepare("SELECT id_rcv, nomor_rcv FROM receiving_order WHERE id_po = ? LIMIT 1");
$stmtCheckExisting->bind_param("i", $idPo);
$stmtCheckExisting->execute();
$existingRcv = $stmtCheckExisting->get_result()->fetch_assoc();
$stmtCheckExisting->close();

if ($existingRcv) {
    jsonResponse(false, "Purchase Order {$currentPo['nomor_po']} sudah pernah dibuatkan Penerimaan Barang ({$existingRcv['nomor_rcv']}).", null, 422);
}

$idSite = (int)$currentPo['id_site'];
$idKaryawan = (int)($currentUser['id_karyawan'] ?? $currentUser['id'] ?? $currentUser['id_users'] ?? 1);

$conn->begin_transaction();

try {
    // 3. Generate Nomor Receiving Unik Resmi Terkunci (Atomik untuk Multi-User)
    require_once __DIR__ . '/../../config/penomoran_helper.php';
    $gen = generateNomorTransaksiLocked($conn, 'RECEIVING', $tanggalDiterima);
    if ($gen['success']) {
        $nomorRcv = $gen['nomor'];
    } else {
        $nomorRcv = 'PN/' . date('ymd', strtotime($tanggalDiterima)) . '/0001';
    }

    // 4. Hitung Status Dokumen Receiving (1 = diterima semua, 0 = diterima sebagian / ada cacat)
    $isAllComplete = true;
    foreach ($items as $it) {
        $qtyPo = (int)($it['qty_po'] ?? 0);
        $qtyBaik = (int)($it['qty_baik'] ?? $it['qty'] ?? 0);
        $qtyRusak = (int)($it['qty_rusak'] ?? 0);

        if ($qtyRusak > 0 || $qtyBaik < $qtyPo) {
            $isAllComplete = false;
        }
    }
    $statusReceiving = $isAllComplete ? 1 : 0;

    // 5. Insert Header receiving_order (print default 0)
    $stmtInsRcv = $conn->prepare("INSERT INTO receiving_order 
        (nomor_rcv, id_po, id_karyawan, tanggal_rcv, tanggal_diterima, nomor_sj, file_sj, keterangan, status, `print`)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, 0)");
    $stmtInsRcv->bind_param("siissssi", $nomorRcv, $idPo, $idKaryawan, $tanggalDiterima, $nomorSj, $filenameSj, $keterangan, $statusReceiving);
    if (!$stmtInsRcv->execute()) {
        throw new Exception("Gagal menyimpan data penerimaan: " . $stmtInsRcv->error);
    }
    $idRcv = $conn->insert_id;
    $stmtInsRcv->close();

    // 6. Insert Items ke receiving_order_detail
    $stmtDetail = $conn->prepare("INSERT INTO receiving_order_detail 
        (id_rcv, id_barang, qty, status_qc, keterangan)
        VALUES (?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $idBarang = (int)($item['id_barang'] ?? 0);
        $qtyBaik = (int)($item['qty_baik'] ?? $item['qty'] ?? 0);
        $qtyRusak = (int)($item['qty_rusak'] ?? 0);
        $catatanItem = isset($item['catatan']) ? trim($item['catatan']) : (isset($item['keterangan']) ? trim($item['keterangan']) : '');

        if ($idBarang <= 0) continue;

        // A. Simpan Barang Kondisi Baik (status_qc = 1) jika qty_baik > 0
        if ($qtyBaik > 0) {
            $statusQcBaik = 1;
            $ketBaik = $catatanItem ?: 'Kondisi Baik (Passed)';
            $stmtDetail->bind_param("iiiis", $idRcv, $idBarang, $qtyBaik, $statusQcBaik, $ketBaik);
            if (!$stmtDetail->execute()) {
                throw new Exception("Gagal menyimpan item rincian: " . $stmtDetail->error);
            }
        }

        // B. Simpan Barang Kondisi Rusak/Cacat (status_qc = 0) jika qty_rusak > 0
        if ($qtyRusak > 0) {
            $statusQcRusak = 0;
            $ketRusak = $catatanItem ? "[Cacat/Rusak]: " . $catatanItem : 'Barang Cacat / Rusak Fisik';
            $stmtDetail->bind_param("iiiis", $idRcv, $idBarang, $qtyRusak, $statusQcRusak, $ketRusak);
            if (!$stmtDetail->execute()) {
                throw new Exception("Gagal menyimpan item cacat: " . $stmtDetail->error);
            }
        }
    }

    $stmtDetail->close();

    // 7. Update Status Purchase Order menjadi DITERIMA
    $stmtUpPo = $conn->prepare("UPDATE purchase_order SET status = 'DITERIMA', tanggal_status = NOW(), id_receiving = ? WHERE id_po = ?");
    $stmtUpPo->bind_param("ii", $idRcv, $idPo);
    if (!$stmtUpPo->execute()) {
        throw new Exception("Gagal mengupdate status Purchase Order: " . $stmtUpPo->error);
    }
    $stmtUpPo->close();

    $conn->commit();

    logActivity($conn, [
        'modul' => 'RECEIVING',
        'aksi' => 'CREATE',
        'id_referensi' => $idRcv,
        'nomor_referensi' => $nomorRcv,
        'deskripsi' => "Menerbitkan Penerimaan Barang {$nomorRcv} (No. SPB Vendor: {$nomorSj}) untuk PO: {$currentPo['nomor_po']}",
        'data_sesudahnya' => [
            'id_rcv' => $idRcv,
            'nomor_rcv' => $nomorRcv,
            'nomor_sj' => $nomorSj,
            'id_po' => $idPo,
            'nomor_po' => $currentPo['nomor_po'],
            'total_items' => count($items)
        ]
    ]);

    jsonResponse(true, "Penerimaan barang {$nomorRcv} (No. SPB: {$nomorSj}) berhasil disimpan.", [
        'id_rcv' => $idRcv,
        'nomor_rcv' => $nomorRcv,
        'nomor_sj' => $nomorSj,
        'file_sj' => $filenameSj,
        'status' => $statusReceiving,
        'nomor_po' => $currentPo['nomor_po']
    ]);

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
}
