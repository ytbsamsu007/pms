<?php
/**
 * REST API Service: Notifikasi & Mailer Terpusat - PT Jaya Teknis
 * Path: api/notification/send.php
 * Akses: Sesi Pengguna Aktif (Admin, Mekanik, Logistik, Purchasing, Finance, Manager)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak didukung. Gunakan POST.']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/mailer.php';

// Verifikasi Sesi Pengguna
$currentUser = requireAuth([
    ROLE_ADMIN,
    ROLE_MEKANIK,
    ROLE_LOGISTIK,
    ROLE_PURCHASING,
    ROLE_MANAGER,
    ROLE_FINANCE
]);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;

$action = trim($input['action'] ?? '');
if (empty($action)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Parameter action wajib disertakan.']);
    exit;
}

$idRequest = isset($input['id_request']) && is_numeric($input['id_request']) ? (int)$input['id_request'] : 0;
$status = trim($input['status'] ?? '');
$actorName = trim($input['actor_name'] ?? ($input['approver_name'] ?? ($currentUser['nama_karyawan'] ?? ($currentUser['nama_users'] ?? ($currentUser['username'] ?? 'Petugas')))));
$keterangan = trim($input['keterangan'] ?? ($input['alasan'] ?? ($input['catatan'] ?? '')));
$extraData = isset($input['extra_data']) && is_array($input['extra_data']) ? $input['extra_data'] : [];

// =========================================================================
// ROUTING AKSI NOTIFIKASI
// =========================================================================
switch ($action) {

    // 1. Notifikasi Permohonan RO Baru ke Tim Logistik
    case 'ro_created':
        if ($idRequest <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'id_request wajib disertakan untuk action ro_created.']);
            exit;
        }

        $resNotify = sendRoApprovalNotification($conn, $idRequest);
        http_response_code($resNotify['success'] ? 200 : 500);
        echo json_encode([
            'success' => $resNotify['success'],
            'message' => $resNotify['message'],
            'data' => [
                'action' => 'ro_created',
                'id_request' => $idRequest,
                'sent_count' => $resNotify['sent_count'] ?? 0
            ]
        ]);
        exit;

    // 2. Notifikasi Pembaruan Status RO ke Pemohon (dan ke Purchasing jika Disetujui Logistik)
    case 'ro_status_update':
        if ($idRequest <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'id_request wajib disertakan untuk action ro_status_update.']);
            exit;
        }
        if (empty($status)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Parameter status wajib disertakan untuk action ro_status_update.']);
            exit;
        }

        $resNotify = sendRoStatusNotification($conn, $idRequest, $status, $actorName, $keterangan, $extraData);
        http_response_code($resNotify['success'] ? 200 : 500);
        echo json_encode([
            'success' => $resNotify['success'],
            'message' => $resNotify['message'],
            'data' => [
                'action' => 'ro_status_update',
                'id_request' => $idRequest,
                'status' => $status,
                'actor_name' => $actorName
            ]
        ]);
        exit;

    // 3. Notifikasi Khusus RO Siap Diproses ke Tim Purchasing
    case 'ro_ready_purchasing':
        if ($idRequest <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'id_request wajib disertakan untuk action ro_ready_purchasing.']);
            exit;
        }

        $resNotify = sendRoReadyForPurchasingNotification($conn, $idRequest, $actorName, $keterangan);
        http_response_code($resNotify['success'] ? 200 : 500);
        echo json_encode([
            'success' => $resNotify['success'],
            'message' => $resNotify['message'],
            'data' => [
                'action' => 'ro_ready_purchasing',
                'id_request' => $idRequest,
                'sent_count' => $resNotify['sent_count'] ?? 0
            ]
        ]);
        exit;

    // 4. Peringatan Jatuh Tempo Tagihan Vendor (H-3) ke Tim Finance
    case 'due_bills_reminder':
    case 'tagihan_jatuh_tempo':
        $hDays = isset($input['h_days']) && is_numeric($input['h_days']) ? (int)$input['h_days'] : 3;
        $resNotify = sendDueBillsReminderNotification($conn, $hDays);
        http_response_code($resNotify['success'] ? 200 : 500);
        echo json_encode([
            'success' => $resNotify['success'],
            'message' => $resNotify['message'],
            'data' => [
                'action' => 'due_bills_reminder',
                'h_days' => $hDays,
                'due_count' => $resNotify['due_count'] ?? 0,
                'overdue_count' => $resNotify['overdue_count'] ?? 0,
                'approaching_count' => $resNotify['approaching_count'] ?? 0,
                'sent_count' => $resNotify['sent_count'] ?? 0,
                'total_sisa_tagihan' => $resNotify['total_sisa_tagihan'] ?? 0,
                'recipients' => $resNotify['recipients'] ?? []
            ]
        ]);
        exit;

    // 5. Pengiriman Custom Email (Direct Mailer API)
    case 'custom_email':
        $toEmail = trim($input['to_email'] ?? '');
        $toName = trim($input['to_name'] ?? 'Penerima');
        $subject = trim($input['subject'] ?? '');
        $bodyHtml = trim($input['body_html'] ?? '');

        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Alamat to_email tidak valid.']);
            exit;
        }
        if (empty($subject) || empty($bodyHtml)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'subject dan body_html wajib diisi.']);
            exit;
        }

        $resSend = sendSmtpEmail($conn, $toEmail, $toName, $subject, $bodyHtml);
        http_response_code($resSend['success'] ? 200 : 500);
        echo json_encode([
            'success' => $resSend['success'],
            'message' => $resSend['message'],
            'data' => [
                'action' => 'custom_email',
                'to_email' => $toEmail
            ]
        ]);
        exit;

    default:
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => "Action '{$action}' tidak dikenali. Pilihan valid: ro_created, ro_status_update, ro_ready_purchasing, due_bills_reminder, custom_email."
        ]);
        exit;
}
