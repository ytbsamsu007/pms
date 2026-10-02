<?php
/**
 * API Backup: Resend Email
 * Mengirimkan kembali berkas SQL backup ke alamat email yang ditentukan.
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
require_once __DIR__ . '/../../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak diizinkan. Gunakan POST.', null, 405);
}

// Auth Protection
$currentUser = apiAuth();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$idBackup = (int)($input['id_backup'] ?? 0);
$targetEmail = trim($input['email'] ?? '');

if ($idBackup <= 0) {
    jsonResponse(false, 'ID backup tidak valid.', null, 422);
}

$stmt = $conn->prepare("SELECT * FROM backup WHERE id_backup = ? LIMIT 1");
$stmt->bind_param("i", $idBackup);
$stmt->execute();
$backup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$backup) {
    jsonResponse(false, 'Data arsip backup tidak ditemukan.', null, 404);
}

$recipientEmail = !empty($targetEmail) ? $targetEmail : $backup['email_backup'];

if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Alamat email tujuan tidak valid.', null, 422);
}

$backupDir = dirname(__DIR__, 2) . '/backups/';
$filePath = $backupDir . $backup['nama_file'];

if (!file_exists($filePath)) {
    jsonResponse(false, "Berkas backup '{$backup['nama_file']}' tidak ditemukan pada server.", null, 404);
}

$fileSizeBytes = filesize($filePath);
$fileSizeFormatted = $fileSizeBytes >= 1048576 
    ? round($fileSizeBytes / 1048576, 2) . ' MB' 
    : ($fileSizeBytes >= 1024 ? round($fileSizeBytes / 1024, 2) . ' KB' : $fileSizeBytes . ' B');

$scopeDisplay = ($backup['scope'] === 'ALL') ? 'Semua Tabel (Full Backup)' : 'Tabel Pilihan (' . $backup['scope'] . ')';
$subject = "Kirim Ulang: Berkas Cadangan Database {$backup['nama_file']} - PT Jaya Teknis";
$userName = $currentUser['nama'] ?: 'Pengguna Sistem';

$htmlEmail = renderBackupFileEmailTemplate($userName, $backup['nama_file'], $fileSizeFormatted, $scopeDisplay, $backup['tanggal_backup'], $backup['keterangan']);

$attachments = [
    [
        'path' => $filePath,
        'name' => $backup['nama_file']
    ]
];

$emailSent = sendSmtpEmail($conn, $recipientEmail, $userName, $subject, $htmlEmail, $attachments);

if ($emailSent['success']) {
    if ($recipientEmail !== $backup['email_backup']) {
        $stmtUp = $conn->prepare("UPDATE backup SET email_backup = ? WHERE id_backup = ?");
        $stmtUp->bind_param("si", $recipientEmail, $idBackup);
        $stmtUp->execute();
        $stmtUp->close();
    }

    if (function_exists('logActivity')) {
        logActivity($conn, 'RESEND_BACKUP_EMAIL', "Kirim ulang berkas backup ID #{$idBackup} ke {$recipientEmail}");
    }

    jsonResponse(true, "Berkas backup '{$backup['nama_file']}' berhasil dikirim ulang ke {$recipientEmail}.");
} else {
    jsonResponse(false, "Gagal mengirim email: " . $emailSent['message'], null, 500);
}
