<?php
/**
 * API Request Order: Approve Endpoint - PT Jaya Teknis
 * Path: api/request_order/approve.php
 * Digunakan oleh Divisi Logistik / Admin / Manager untuk menyetujui RO dari Mekanik
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/session.php';

// Hanya Logistik, Manager, dan Admin yang berhak menyetujui RO dari Mekanik
$currentUser = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan POST.', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$idRequest = isset($input['id_request']) ? (int)$input['id_request'] : 0;

if ($idRequest <= 0) {
    jsonResponse(false, 'ID Request Order tidak valid.', null, 400);
}

// 1. Cek keberadaan dan status RO
$stmtCheck = $conn->prepare("SELECT id_request, nomor, status, id_karyawan_approved FROM request_order WHERE id_request = ? LIMIT 1");
$stmtCheck->bind_param("i", $idRequest);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if (!$resCheck || $resCheck->num_rows === 0) {
    jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
}

$ro = $resCheck->fetch_assoc();
$stmtCheck->close();

if ($ro['status'] !== 'TERKIRIM') {
    jsonResponse(false, "Hanya Request Order dengan status 'TERKIRIM' yang dapat disetujui.", null, 422);
}

$idKaryawanApprover = !empty($currentUser['id_karyawan']) ? (int)$currentUser['id_karyawan'] : 1;
$action = trim($input['action'] ?? 'approve');
$newStatus = ($action === 'reject') ? 'TIDAK DISETUJUI LOGISTIK' : 'DISETUJUI LOGISTIK';
$msgAction = ($action === 'reject') ? 'ditolak' : 'disetujui';

// 2. Update status dan id_karyawan_approved
$stmtUpdate = $conn->prepare("UPDATE request_order 
                              SET status = ?, id_karyawan_approved = ?, tanggal_status = NOW() 
                              WHERE id_request = ?");
$stmtUpdate->bind_param("sii", $newStatus, $idKaryawanApprover, $idRequest);

if (!$stmtUpdate->execute()) {
    jsonResponse(false, "Gagal memproses persetujuan Request Order: " . $stmtUpdate->error, null, 500);
}
$stmtUpdate->close();

// Kirim Notifikasi Email ke Pemohon RO (Fault-Tolerant)
$emailSent = false;
try {
    require_once __DIR__ . '/../../config/mailer.php';
    if (function_exists('sendNotificationEvent')) {
        $approverName = $currentUser['nama'] ?? ($currentUser['nama_karyawan'] ?? ($currentUser['nama_users'] ?? ($currentUser['username'] ?? 'Approver Logistik')));
        if (!empty($currentUser['kode_karyawan'])) {
            $approverName = $approverName . ' (' . $currentUser['kode_karyawan'] . ')';
        }
        $catatan = trim($input['catatan'] ?? ($input['keterangan'] ?? ''));
        $mailRes = sendNotificationEvent($conn, 'ro_status_update', [
            'id_request' => $idRequest,
            'status' => $newStatus,
            'actor_name' => $approverName,
            'keterangan' => $catatan
        ]);
        $emailSent = !empty($mailRes['success']);
    }
} catch (Throwable $t) {
    error_log("Gagal mengirim notifikasi status RO {$ro['nomor']}: " . $t->getMessage());
}

// Catat Log Aktivitas Pengguna
require_once __DIR__ . '/../../config/activity_logger.php';
$aksiLog = ($action === 'reject') ? 'REJECT_LOGISTIK' : 'APPROVE_LOGISTIK';
$catatanLog = trim($input['catatan'] ?? ($input['keterangan'] ?? ''));
$deskripsiLog = ($action === 'reject')
    ? "Menolak Request Order {$ro['nomor']} pada tahap Logistik." . ($catatanLog ? " Alasan: {$catatanLog}" : "")
    : "Menyetujui Request Order {$ro['nomor']} pada tahap Logistik (Stok gudang telah diverifikasi).";

logActivity($conn, [
    'modul'           => 'REQUEST_ORDER',
    'aksi'            => $aksiLog,
    'id_referensi'    => $idRequest,
    'nomor_referensi' => $ro['nomor'],
    'deskripsi'       => $deskripsiLog,
    'data_sebelumnya' => ['status' => $ro['status'] ?? ''],
    'data_sesudahnya' => ['status' => $newStatus, 'id_karyawan_approved' => $idKaryawanApprover, 'catatan' => $catatanLog]
]);

jsonResponse(true, "Request Order {$ro['nomor']} berhasil {$msgAction} oleh Logistik.", [
    'id_request' => $idRequest,
    'status' => $newStatus,
    'id_karyawan_approved' => $idKaryawanApprover,
    'nomor' => $ro['nomor'],
    'email_sent' => $emailSent
]);
