<?php
/**
 * API Receiving: Perbarui Dokumen Penerimaan Barang (Hanya jika belum di-print)
 * Path: api/receiving/update.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
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

$input = $_POST;
if (empty($input)) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

$idRcv = isset($input['id_rcv']) && is_numeric($input['id_rcv']) ? (int)$input['id_rcv'] : 0;
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

if ($idRcv <= 0) {
    jsonResponse(false, 'Parameter id_rcv tidak valid.', null, 400);
}

// 1. Cek status print dokumen saat ini
$stmtRcv = $conn->prepare("SELECT ro.id_rcv, ro.nomor_rcv, ro.id_po, ro.file_sj, ro.print, po.id_site 
                           FROM receiving_order ro 
                           LEFT JOIN purchase_order po ON ro.id_po = po.id_po
                           WHERE ro.id_rcv = ? LIMIT 1");
$stmtRcv->bind_param("i", $idRcv);
$stmtRcv->execute();
$currentRcv = $stmtRcv->get_result()->fetch_assoc();
$stmtRcv->close();

if (!$currentRcv) {
    jsonResponse(false, 'Dokumen Penerimaan Barang tidak ditemukan.', null, 404);
}

if ((int)$currentRcv['print'] === 1) {
    jsonResponse(false, 'Dokumen Penerimaan Barang sudah dicetak (print). Data terkunci permanen dan tidak dapat diedit.', null, 403);
}

$idSite = (int)$currentRcv['id_site'];

// 2. Upload File Surat Jalan jika ada berkas baru
$filenameSj = $currentRcv['file_sj'];
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

$conn->begin_transaction();

try {
    // 3. Rollback stok lama dari receiving_order_detail sebelumnya
    $stmtOldItems = $conn->prepare("SELECT id_barang, qty, status_qc FROM receiving_order_detail WHERE id_rcv = ?");
    $stmtOldItems->bind_param("i", $idRcv);
    $stmtOldItems->execute();
    $oldDetails = $stmtOldItems->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtOldItems->close();

    $stmtReduceStok = $conn->prepare("UPDATE barang_stok SET stok = GREATEST(0, stok - ?) WHERE id_barang = ? AND id_site = ?");
    foreach ($oldDetails as $old) {
        if ((int)$old['status_qc'] === 1 && (int)$old['qty'] > 0) {
            $stmtReduceStok->bind_param("iii", $old['qty'], $old['id_barang'], $idSite);
            $stmtReduceStok->execute();
        }
    }
    $stmtReduceStok->close();

    // Hapus detail lama
    $stmtDel = $conn->prepare("DELETE FROM receiving_order_detail WHERE id_rcv = ?");
    $stmtDel->bind_param("i", $idRcv);
    $stmtDel->execute();
    $stmtDel->close();

    // 4. Hitung Status Dokumen Receiving baru
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

    // 5. Update Header receiving_order
    $stmtUp = $conn->prepare("UPDATE receiving_order SET 
        nomor_sj = ?, 
        tanggal_diterima = ?, 
        file_sj = ?, 
        keterangan = ?, 
        status = ? 
        WHERE id_rcv = ?");
    $stmtUp->bind_param("ssssii", $nomorSj, $tanggalDiterima, $filenameSj, $keterangan, $statusReceiving, $idRcv);
    if (!$stmtUp->execute()) {
        throw new Exception("Gagal memperbarui data penerimaan: " . $stmtUp->error);
    }
    $stmtUp->close();

    // 6. Insert Detail Baru & Update Stok
    $stmtDetail = $conn->prepare("INSERT INTO receiving_order_detail 
        (id_rcv, id_barang, qty, status_qc, keterangan)
        VALUES (?, ?, ?, ?, ?)");

    $stmtCheckStok = $conn->prepare("SELECT id_stok, stok FROM barang_stok WHERE id_barang = ? AND id_site = ? LIMIT 1");
    $stmtUpStok = $conn->prepare("UPDATE barang_stok SET stok = stok + ? WHERE id_stok = ?");
    $stmtInsStok = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");

    foreach ($items as $item) {
        $idBarang = (int)($item['id_barang'] ?? 0);
        $qtyBaik = (int)($item['qty_baik'] ?? $item['qty'] ?? 0);
        $qtyRusak = (int)($item['qty_rusak'] ?? 0);
        $catatanItem = isset($item['catatan']) ? trim($item['catatan']) : (isset($item['keterangan']) ? trim($item['keterangan']) : '');

        if ($idBarang <= 0) continue;

        if ($qtyBaik > 0) {
            $statusQcBaik = 1;
            $ketBaik = $catatanItem ?: 'Kondisi Baik (Passed)';
            $stmtDetail->bind_param("iiiis", $idRcv, $idBarang, $qtyBaik, $statusQcBaik, $ketBaik);
            $stmtDetail->execute();

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

        if ($qtyRusak > 0) {
            $statusQcRusak = 0;
            $ketRusak = $catatanItem ? "[Cacat/Rusak]: " . $catatanItem : 'Barang Cacat / Rusak Fisik';
            $stmtDetail->bind_param("iiiis", $idRcv, $idBarang, $qtyRusak, $statusQcRusak, $ketRusak);
            $stmtDetail->execute();
        }
    }

    $stmtDetail->close();
    $stmtCheckStok->close();
    $stmtUpStok->close();
    $stmtInsStok->close();

    $conn->commit();

    logActivity($conn, [
        'modul' => 'RECEIVING',
        'aksi' => 'UPDATE',
        'id_referensi' => $idRcv,
        'nomor_referensi' => $currentRcv['nomor_rcv'],
        'deskripsi' => "Memperbarui data dokumen Penerimaan Barang {$currentRcv['nomor_rcv']} (No SPB: {$nomorSj})",
        'data_sebelumnya' => [
            'id_rcv' => $currentRcv['id_rcv'],
            'nomor_rcv' => $currentRcv['nomor_rcv'],
            'file_sj' => $currentRcv['file_sj']
        ],
        'data_sesudahnya' => [
            'nomor_sj' => $nomorSj,
            'keterangan' => $keterangan,
            'file_sj' => $filenameSj,
            'total_items' => count($items)
        ]
    ]);

    jsonResponse(true, "Data Penerimaan Barang {$currentRcv['nomor_rcv']} berhasil diperbarui.", [
        'id_rcv' => $idRcv,
        'nomor_rcv' => $currentRcv['nomor_rcv'],
        'file_sj' => $filenameSj
    ]);

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
}
