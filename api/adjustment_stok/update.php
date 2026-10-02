<?php
/**
 * REST API: Update / Edit Transaksi Stock Adjustment
 * Endpoint: /api/adjustment_stok/update.php
 * Path: api/adjustment_stok/update.php
 * Khusus: Status DRAFT & PENDING
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

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MEKANIK, ROLE_MANAGER]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 'Metode HTTP tidak diizinkan. Gunakan POST.', null, 405);
}

// Log activity helper
function logActivityAdj($conn, $idKaryawan, $namaPengguna, $role, $aksi, $idRef, $noRef, $deskripsi) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Antigravity IDE';
    $stmt = $conn->prepare("INSERT INTO activity_log (id_karyawan, nama_pengguna, role, modul, aksi, id_referensi, nomor_referensi, deskripsi, ip_address, user_agent) VALUES (?, ?, ?, 'ADJUSTMENT_STOK', ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isssissss", $idKaryawan, $namaPengguna, $role, $aksi, $idRef, $noRef, $deskripsi, $ip, $ua);
        $stmt->execute();
        $stmt->close();
    }
}

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        sendJson(false, 'Data JSON tidak valid atau kosong.', null, 400);
    }

    $id = isset($data['id_adjustment']) && is_numeric($data['id_adjustment']) ? (int)$data['id_adjustment'] : 0;
    if ($id <= 0) {
        sendJson(false, 'ID Adjustment tidak valid.', null, 400);
    }

    // Cek status saat ini
    $stmtCheck = $conn->prepare("SELECT id_adjustment, nomor_adjustment, id_site, status FROM adjustment_stok WHERE id_adjustment = ? LIMIT 1");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $existing = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if (!$existing) {
        sendJson(false, 'Data Stock Adjustment tidak ditemukan.', null, 404);
    }

    if (!in_array($existing['status'], ['DRAFT', 'PENDING'])) {
        sendJson(false, "Dokumen berstatus {$existing['status']} tidak dapat diubah.", null, 422);
    }

    $idSite = isset($data['id_site']) && is_numeric($data['id_site']) ? (int)$data['id_site'] : (int)$existing['id_site'];
    $jenisAdjustment = trim($data['jenis_adjustment'] ?? 'PENAMBAHAN');
    $alasan = trim($data['alasan'] ?? '');
    $keterangan = trim($data['keterangan'] ?? '');
    $statusTarget = trim($data['status'] ?? $existing['status']); // DRAFT atau PENDING
    $tanggalAdj = !empty($data['tanggal_adjustment']) ? date('Y-m-d H:i:s', strtotime($data['tanggal_adjustment'])) : date('Y-m-d H:i:s');
    $idKaryawanApproved = isset($data['id_karyawan_approved']) && is_numeric($data['id_karyawan_approved']) && (int)$data['id_karyawan_approved'] > 0 ? (int)$data['id_karyawan_approved'] : null;
    $items = $data['items'] ?? [];

    if ($idSite <= 0) {
        sendJson(false, 'Pilih Site / Gudang lokasi adjustment stok.', null, 422);
    }
    if (empty($alasan)) {
        sendJson(false, 'Alasan penyesuaian stok wajib diisi.', null, 422);
    }
    if (!in_array($jenisAdjustment, ['PENAMBAHAN', 'PENGURANGAN', 'SET_STOK'])) {
        $jenisAdjustment = 'PENAMBAHAN';
    }
    if ($statusTarget === 'REJECT') {
        $statusTarget = 'REJECTED';
    }
    if (!in_array($statusTarget, ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED', 'BATAL'])) {
        $statusTarget = 'DRAFT';
    }
    if (empty($items) || !is_array($items)) {
        sendJson(false, 'Daftar barang yang disesuaikan wajib diisi minimal 1 barang.', null, 422);
    }

    $idKaryawan = $user['id_karyawan'] ?? $user['id'] ?? 1;
    $tanggalApproved = ($statusTarget === 'APPROVED' || $statusTarget === 'REJECTED') ? date('Y-m-d H:i:s') : null;

    $conn->begin_transaction();

    // 1. Update Header
    $stmtUpd = $conn->prepare("UPDATE adjustment_stok SET 
        tanggal_adjustment = ?,
        id_site = ?,
        jenis_adjustment = ?,
        alasan = ?,
        keterangan = ?,
        status = ?,
        id_karyawan_approved = ?,
        tanggal_approved = ?
        WHERE id_adjustment = ?");
    if (!$stmtUpd) {
        throw new Exception("Gagal prepare update header: " . $conn->error);
    }
    $stmtUpd->bind_param("sissssisi", $tanggalAdj, $idSite, $jenisAdjustment, $alasan, $keterangan, $statusTarget, $idKaryawanApproved, $tanggalApproved, $id);
    if (!$stmtUpd->execute()) {
        throw new Exception("Gagal update header: " . $stmtUpd->error);
    }
    $stmtUpd->close();

    // 2. Re-insert items
    $stmtDel = $conn->prepare("DELETE FROM adjustment_stok_detail WHERE id_adjustment = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
    $stmtDel->close();

    $stmtItem = $conn->prepare("INSERT INTO adjustment_stok_detail (
        id_adjustment, id_barang, qty_sistem, qty_fisik, qty_adjustment, qty_akhir, keterangan, harga_satuan, subtotal_adjustment
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmtItem) {
        throw new Exception("Gagal prepare item: " . $conn->error);
    }

    foreach ($items as $idx => $item) {
        $idBarang = (int)($item['id_barang'] ?? 0);
        if ($idBarang <= 0) continue;

        $stokSistem = 0;
        $qStok = $conn->query("SELECT stok FROM barang_stok WHERE id_barang = {$idBarang} AND id_site = {$idSite} LIMIT 1");
        $hasExistingStok = ($qStok && $qStok->num_rows > 0);
        if ($hasExistingStok) {
            $stokSistem = (float)$qStok->fetch_assoc()['stok'];
        }

        $inputQty = (float)($item['qty'] ?? $item['qty_adjustment'] ?? $item['qty_fisik'] ?? 0);
        $hargaSatuan = (float)($item['harga_satuan'] ?? 0);
        $itemKet = trim($item['keterangan'] ?? '');

        if ($jenisAdjustment === 'PENAMBAHAN') {
            $qtyAdj = abs($inputQty);
            $qtyAkhir = $stokSistem + $qtyAdj;
        } elseif ($jenisAdjustment === 'PENGURANGAN') {
            $qtyAdj = -abs($inputQty);
            $qtyAkhir = max(0, $stokSistem + $qtyAdj);
        } else {
            $qtyAkhir = $inputQty;
            $qtyAdj = $qtyAkhir - $stokSistem;
        }

        $subtotal = abs($qtyAdj) * $hargaSatuan;

        // JIKA STATUS TARGET = APPROVED, LAKUKAN AKUMULASI STOK FISIK KE TABEL barang_stok
        if ($statusTarget === 'APPROVED') {
            $newStok = $qtyAkhir;
            if ($hasExistingStok) {
                $stmtStok = $conn->prepare("UPDATE barang_stok SET stok = ? WHERE id_barang = ? AND id_site = ?");
                $stmtStok->bind_param("dii", $newStok, $idBarang, $idSite);
                $stmtStok->execute();
                $stmtStok->close();
            } else {
                $stmtStokIns = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");
                $stmtStokIns->bind_param("iid", $idBarang, $idSite, $newStok);
                $stmtStokIns->execute();
                $stmtStokIns->close();
            }
        }

        $stmtItem->bind_param("iiddddsdd", $id, $idBarang, $stokSistem, $qtyAkhir, $qtyAdj, $qtyAkhir, $itemKet, $hargaSatuan, $subtotal);
        if (!$stmtItem->execute()) {
            throw new Exception("Gagal simpan rincian barang: " . $stmtItem->error);
        }
    }
    $stmtItem->close();

    logActivityAdj(
        $conn,
        $idKaryawan,
        $user['nama'] ?? $user['username'],
        $user['role'] ?? 'USER',
        'UPDATE',
        $id,
        $existing['nomor_adjustment'],
        "Memperbarui Stock Adjustment {$existing['nomor_adjustment']} (Status: {$statusTarget})" . ($statusTarget === 'APPROVED' ? " dan mengakumulasi stok barang di site {$idSite}" : "")
    );

    $conn->commit();

    sendJson(true, "Stock Adjustment {$existing['nomor_adjustment']} berhasil diperbarui (Status: {$statusTarget}).", [
        'id_adjustment' => $id,
        'nomor_adjustment' => $existing['nomor_adjustment'],
        'status' => $statusTarget
    ]);

} catch (Exception $e) {
    $conn->rollback();
    sendJson(false, 'Gagal memperbarui stock adjustment: ' . $e->getMessage(), null, 500);
}
