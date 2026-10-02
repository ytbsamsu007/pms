<?php
/**
 * REST API: Action Workflow Stock Adjustment (Submit, Approve, Reject, Batal)
 * Endpoint: /api/adjustment_stok/action.php
 * Path: api/adjustment_stok/action.php
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
    $action = trim($data['action'] ?? ''); // 'SUBMIT', 'APPROVE', 'REJECT', 'BATAL'
    $catatan = trim($data['catatan'] ?? '');

    if ($id <= 0) {
        sendJson(false, 'ID Adjustment tidak valid.', null, 400);
    }

    // Ambil data adjustment saat ini
    $stmtCheck = $conn->prepare("SELECT id_adjustment, nomor_adjustment, id_site, status, jenis_adjustment FROM adjustment_stok WHERE id_adjustment = ? LIMIT 1");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $adj = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if (!$adj) {
        sendJson(false, 'Data Stock Adjustment tidak ditemukan.', null, 404);
    }

    $currentStatus = $adj['status'];
    $idSite = (int)$adj['id_site'];
    $noAdj = $adj['nomor_adjustment'];
    $idKaryawan = $user['id_karyawan'] ?? $user['id'] ?? 1;

    // OTORISASI KHUSUS APPROVAL & REJECTION: HANYA LOGISTIK & ADMIN
    if (in_array($action, ['APPROVE', 'REJECT'])) {
        if (!in_array($user['role'], [ROLE_ADMIN, ROLE_LOGISTIK])) {
            sendJson(false, 'Akses Ditolak: Otorisasi persetujuan / penolakan penyesuaian stok hanya dapat dilakukan oleh Divisi Logistik.', null, 403);
        }
    }

    $conn->begin_transaction();

    // ==========================================
    // 1. ACTION: SUBMIT (DRAFT -> PENDING)
    // ==========================================
    if ($action === 'SUBMIT') {
        if ($currentStatus !== 'DRAFT') {
            sendJson(false, "Hanya dokumen status DRAFT yang dapat diajukan (status saat ini: {$currentStatus}).", null, 422);
        }

        $stmtUpd = $conn->prepare("UPDATE adjustment_stok SET status = 'PENDING' WHERE id_adjustment = ?");
        $stmtUpd->bind_param("i", $id);
        $stmtUpd->execute();
        $stmtUpd->close();

        logActivityAdj($conn, $idKaryawan, $user['nama'] ?? $user['username'], $user['role'], 'SUBMIT', $id, $noAdj, "Mengajukan Stock Adjustment {$noAdj} untuk verifikasi approval Logistik");

        $conn->commit();
        sendJson(true, "Stock Adjustment {$noAdj} berhasil diajukan untuk persetujuan Logistik.", ['status' => 'PENDING']);
    }

    // ==========================================
    // 2. ACTION: APPROVE (PENDING / DRAFT -> APPROVED)
    // ==========================================
    elseif ($action === 'APPROVE') {
        if (!in_array($currentStatus, ['PENDING', 'DRAFT'])) {
            sendJson(false, "Hanya dokumen status PENDING atau DRAFT yang dapat disetujui (status saat ini: {$currentStatus}).", null, 422);
        }

        $now = date('Y-m-d H:i:s');

        // Update status header ke APPROVED
        $stmtApprove = $conn->prepare("UPDATE adjustment_stok SET status = 'APPROVED', id_karyawan_approved = ?, tanggal_approved = ? WHERE id_adjustment = ?");
        $stmtApprove->bind_param("isi", $idKaryawan, $now, $id);
        $stmtApprove->execute();
        $stmtApprove->close();

        // EKSEKUSI PEMBARUAN STOK FISIK KE TABEL `barang_stok`
        $jenisAdjustment = $adj['jenis_adjustment'];
        $qDetails = $conn->query("SELECT id_barang, qty_sistem, qty_fisik, qty_adjustment, qty_akhir FROM adjustment_stok_detail WHERE id_adjustment = {$id}");
        while ($d = $qDetails->fetch_assoc()) {
            $idBarang = (int)$d['id_barang'];
            $qtyFisik = (float)$d['qty_fisik'];
            $storedQtyAdj = (float)$d['qty_adjustment'];

            // Cek data barang_stok per site
            $currentStokDb = 0;
            $qCheckStok = $conn->query("SELECT id_stok, stok FROM barang_stok WHERE id_barang = {$idBarang} AND id_site = {$idSite} LIMIT 1");
            $hasExistingStok = ($qCheckStok && $qCheckStok->num_rows > 0);
            
            if ($hasExistingStok) {
                $rowStok = $qCheckStok->fetch_assoc();
                $currentStokDb = (float)$rowStok['stok'];
            }

            // Hitung stok baru berdasarkan jenis penyesuaian
            if ($jenisAdjustment === 'PENAMBAHAN') {
                $qtyAdj = abs($storedQtyAdj > 0 ? $storedQtyAdj : $qtyFisik);
                $newStok = $currentStokDb + $qtyAdj;
            } elseif ($jenisAdjustment === 'PENGURANGAN') {
                $qtyAdj = -abs($storedQtyAdj < 0 ? $storedQtyAdj : $qtyFisik);
                $newStok = max(0, $currentStokDb + $qtyAdj);
            } else { // SET_STOK
                $newStok = $qtyFisik;
                $qtyAdj = $newStok - $currentStokDb;
            }

            if ($hasExistingStok) {
                // Update stok
                $stmtStok = $conn->prepare("UPDATE barang_stok SET stok = ? WHERE id_barang = ? AND id_site = ?");
                $stmtStok->bind_param("dii", $newStok, $idBarang, $idSite);
                $stmtStok->execute();
                $stmtStok->close();
            } else {
                // Insert stok baru
                $stmtStokIns = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");
                $stmtStokIns->bind_param("iid", $idBarang, $idSite, $newStok);
                $stmtStokIns->execute();
                $stmtStokIns->close();
            }

            // Sinkronisasi rincian detail agar qty_sistem, qty_adjustment, dan qty_akhir presisi
            $stmtUpdDetail = $conn->prepare("UPDATE adjustment_stok_detail SET qty_sistem = ?, qty_adjustment = ?, qty_akhir = ? WHERE id_adjustment = ? AND id_barang = ?");
            $stmtUpdDetail->bind_param("dddii", $currentStokDb, $qtyAdj, $newStok, $id, $idBarang);
            $stmtUpdDetail->execute();
            $stmtUpdDetail->close();
        }

        logActivityAdj($conn, $idKaryawan, $user['nama'] ?? $user['username'], $user['role'], 'APPROVE', $id, $noAdj, "Menyetujui Stock Adjustment {$noAdj} ({$jenisAdjustment}) dan memperbarui saldo stok fisik pada Site ID: {$idSite}");

        $conn->commit();
        sendJson(true, "Stock Adjustment {$noAdj} telah DISETUJUI dan saldo stok barang telah diperbarui secara otomatis.", ['status' => 'APPROVED']);
    }

    // ==========================================
    // 3. ACTION: REJECT (PENDING -> REJECTED)
    // ==========================================
    elseif ($action === 'REJECT') {
        if ($currentStatus !== 'PENDING') {
            sendJson(false, "Hanya dokumen status PENDING yang dapat ditolak (status saat ini: {$currentStatus}).", null, 422);
        }

        $now = date('Y-m-d H:i:s');
        $ketTambahan = !empty($catatan) ? " [Alasan Penolakan: {$catatan}]" : "";

        $stmtRej = $conn->prepare("UPDATE adjustment_stok SET status = 'REJECTED', id_karyawan_approved = ?, tanggal_approved = ?, keterangan = CONCAT(COALESCE(keterangan, ''), ?) WHERE id_adjustment = ?");
        $stmtRej->bind_param("issi", $idKaryawan, $now, $ketTambahan, $id);
        $stmtRej->execute();
        $stmtRej->close();

        logActivityAdj($conn, $idKaryawan, $user['nama'] ?? $user['username'], $user['role'], 'REJECT', $id, $noAdj, "Menolak Stock Adjustment {$noAdj}. Catatan: {$catatan}");

        $conn->commit();
        sendJson(true, "Stock Adjustment {$noAdj} telah DITOLAK.", ['status' => 'REJECTED']);
    }

    // ==========================================
    // 4. ACTION: BATAL (DRAFT/PENDING -> BATAL)
    // ==========================================
    elseif ($action === 'BATAL') {
        if (in_array($currentStatus, ['APPROVED', 'BATAL'])) {
            sendJson(false, "Dokumen yang sudah disetujui atau sudah batal tidak dapat dibatalkan kembali.", null, 422);
        }

        $ketTambahan = !empty($catatan) ? " [Dibatalkan: {$catatan}]" : "";

        $stmtBatal = $conn->prepare("UPDATE adjustment_stok SET status = 'BATAL', keterangan = CONCAT(COALESCE(keterangan, ''), ?) WHERE id_adjustment = ?");
        $stmtBatal->bind_param("si", $ketTambahan, $id);
        $stmtBatal->execute();
        $stmtBatal->close();

        logActivityAdj($conn, $idKaryawan, $user['nama'] ?? $user['username'], $user['role'], 'BATAL', $id, $noAdj, "Membatalkan Stock Adjustment {$noAdj}. Alasan: {$catatan}");

        $conn->commit();
        sendJson(true, "Stock Adjustment {$noAdj} berhasil dibatalkan.", ['status' => 'BATAL']);
    }

    // ==========================================
    // 5. ACTION: DELETE (DRAFT/PENDING -> HAPUS DARI DB)
    // ==========================================
    elseif ($action === 'DELETE') {
        if (!in_array($currentStatus, ['DRAFT', 'PENDING'])) {
            sendJson(false, "Hanya transaksi berstatus DRAFT atau PENDING yang dapat dihapus.", null, 422);
        }

        // Hapus rincian detail barang
        $stmtDelDetail = $conn->prepare("DELETE FROM adjustment_stok_detail WHERE id_adjustment = ?");
        $stmtDelDetail->bind_param("i", $id);
        $stmtDelDetail->execute();
        $stmtDelDetail->close();

        // Hapus header transaksi
        $stmtDelHead = $conn->prepare("DELETE FROM adjustment_stok WHERE id_adjustment = ?");
        $stmtDelHead->bind_param("i", $id);
        $stmtDelHead->execute();
        $stmtDelHead->close();

        logActivityAdj($conn, $idKaryawan, $user['nama'] ?? $user['username'], $user['role'], 'DELETE', $id, $noAdj, "Menghapus Stock Adjustment {$noAdj} (Status awal: {$currentStatus})");

        $conn->commit();
        sendJson(true, "Stock Adjustment {$noAdj} berhasil dihapus permanen.", ['status' => 'DELETED']);
    }

    else {
        sendJson(false, "Aksi '{$action}' tidak dikenal.", null, 400);
    }

} catch (Exception $e) {
    $conn->rollback();
    sendJson(false, 'Gagal memproses aksi: ' . $e->getMessage(), null, 500);
}
