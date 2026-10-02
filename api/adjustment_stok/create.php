<?php
/**
 * REST API: Buat Transaksi Stock Adjustment Baru
 * Endpoint: /api/adjustment_stok/create.php
 * Path: api/adjustment_stok/create.php
 * Khusus Role: ADMIN, LOGISTIK, MEKANIK, MANAGER
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
require_once __DIR__ . '/../../config/penomoran_helper.php';
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

// Helper auto-generate nomor adjustment dinamis
function generateNomorAdjustment($conn, $tanggal = null) {
    if (empty($tanggal) || !strtotime($tanggal)) {
        $tanggal = date('Y-m-d');
    }
    $gen = generateNomorTransaksi($conn, 'ADJUSTMENT STOK', $tanggal);
    if ($gen && !empty($gen['success']) && !empty($gen['nomor'])) {
        return $gen['nomor'];
    }
    $prefix = 'ADJ-' . date('ym', strtotime($tanggal)) . '-';
    $sql = "SELECT nomor_adjustment FROM adjustment_stok WHERE nomor_adjustment LIKE '{$prefix}%' ORDER BY nomor_adjustment DESC LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $lastSeq = (int)substr($row['nomor_adjustment'], -5);
        $nextSeq = str_pad($lastSeq + 1, 5, '0', STR_PAD_LEFT);
    } else {
        $nextSeq = '00001';
    }
    return $prefix . $nextSeq;
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

    $idSite = isset($data['id_site']) && is_numeric($data['id_site']) ? (int)$data['id_site'] : 0;
    $jenisAdjustment = trim($data['jenis_adjustment'] ?? 'SET_STOK');
    $alasan = trim($data['alasan'] ?? '');
    $keterangan = trim($data['keterangan'] ?? '');
    $statusTarget = trim($data['status'] ?? 'DRAFT'); // DRAFT atau PENDING
    $tanggalAdj = !empty($data['tanggal_adjustment']) ? date('Y-m-d H:i:s', strtotime($data['tanggal_adjustment'])) : date('Y-m-d H:i:s');
    $items = $data['items'] ?? [];

    // Validasi
    if ($idSite <= 0) {
        sendJson(false, 'Pilih Site / Gudang lokasi adjustment stok.', null, 422);
    }
    if (empty($alasan)) {
        sendJson(false, 'Alasan penyesuaian stok wajib diisi.', null, 422);
    }
    if (!in_array($jenisAdjustment, ['PENAMBAHAN', 'PENGURANGAN', 'SET_STOK'])) {
        $jenisAdjustment = 'SET_STOK';
    }
    if (!in_array($statusTarget, ['DRAFT', 'PENDING'])) {
        $statusTarget = 'DRAFT';
    }
    if (empty($items) || !is_array($items)) {
        sendJson(false, 'Daftar barang yang disesuaikan wajib diisi minimal 1 barang.', null, 422);
    }

    $idKaryawan = $user['id_karyawan'] ?? $user['id'] ?? 1;
    $idKaryawanApproved = isset($data['id_karyawan_approved']) && is_numeric($data['id_karyawan_approved']) && (int)$data['id_karyawan_approved'] > 0 ? (int)$data['id_karyawan_approved'] : null;

    // Database Transaction
    $conn->begin_transaction();

    $nomorAdj = generateNomorAdjustment($conn);

    // 1. Insert Header
    $stmtHead = $conn->prepare("INSERT INTO adjustment_stok (
        nomor_adjustment, tanggal_adjustment, id_site, jenis_adjustment, alasan, keterangan, status, id_karyawan, id_karyawan_approved
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmtHead) {
        throw new Exception("Gagal prepare header: " . $conn->error);
    }
    $stmtHead->bind_param("ssissssii", $nomorAdj, $tanggalAdj, $idSite, $jenisAdjustment, $alasan, $keterangan, $statusTarget, $idKaryawan, $idKaryawanApproved);
    if (!$stmtHead->execute()) {
        throw new Exception("Gagal insert header: " . $stmtHead->error);
    }
    $idAdjustment = $conn->insert_id;
    $stmtHead->close();

    // 2. Insert Items Detail
    $stmtItem = $conn->prepare("INSERT INTO adjustment_stok_detail (
        id_adjustment, id_barang, qty_sistem, qty_fisik, qty_adjustment, qty_akhir, keterangan, harga_satuan, subtotal_adjustment
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmtItem) {
        throw new Exception("Gagal prepare item: " . $conn->error);
    }

    foreach ($items as $idx => $item) {
        $idBarang = (int)($item['id_barang'] ?? 0);
        if ($idBarang <= 0) continue;

        // Ambil stok sistem aktual per site
        $stokSistem = 0;
        $qStok = $conn->query("SELECT stok FROM barang_stok WHERE id_barang = {$idBarang} AND id_site = {$idSite} LIMIT 1");
        if ($qStok && $qStok->num_rows > 0) {
            $stokSistem = (float)$qStok->fetch_assoc()['stok'];
        }

        $qtyFisik = (float)($item['qty_fisik'] ?? 0);
        $hargaSatuan = (float)($item['harga_satuan'] ?? 0);
        $itemKet = trim($item['keterangan'] ?? '');

        if ($jenisAdjustment === 'PENAMBAHAN') {
            $qtyAdj = abs((float)($item['qty_adjustment'] ?? $qtyFisik));
            $qtyAkhir = $stokSistem + $qtyAdj;
        } elseif ($jenisAdjustment === 'PENGURANGAN') {
            $qtyAdj = -abs((float)($item['qty_adjustment'] ?? $qtyFisik));
            $qtyAkhir = max(0, $stokSistem + $qtyAdj);
        } else { // SET_STOK
            $qtyAkhir = $qtyFisik;
            $qtyAdj = $qtyAkhir - $stokSistem;
        }

        $subtotal = abs($qtyAdj) * $hargaSatuan;

        $stmtItem->bind_param("iiddddsdd", $idAdjustment, $idBarang, $stokSistem, $qtyFisik, $qtyAdj, $qtyAkhir, $itemKet, $hargaSatuan, $subtotal);
        if (!$stmtItem->execute()) {
            throw new Exception("Gagal simpan rincian barang: " . $stmtItem->error);
        }
    }
    $stmtItem->close();

    // Log Activity
    logActivityAdj(
        $conn,
        $idKaryawan,
        $user['nama'] ?? $user['username'],
        $user['role'] ?? 'USER',
        'CREATE',
        $idAdjustment,
        $nomorAdj,
        "Membuat Stock Adjustment {$nomorAdj} (Status: {$statusTarget}) untuk Site ID: {$idSite}"
    );

    $conn->commit();

    sendJson(true, "Stock Adjustment {$nomorAdj} berhasil disimpan dengan status {$statusTarget}.", [
        'id_adjustment' => $idAdjustment,
        'nomor_adjustment' => $nomorAdj,
        'status' => $statusTarget
    ]);

} catch (Exception $e) {
    $conn->rollback();
    sendJson(false, 'Gagal membuat adjustment stok: ' . $e->getMessage(), null, 500);
}
