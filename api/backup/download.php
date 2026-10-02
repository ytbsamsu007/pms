<?php
/**
 * API Backup: Download File
 * Mengunduh berkas .sql backup secara aman setelah autentikasi sesi.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Pastikan user telah login
if (!isLoggedIn() && empty($_SESSION['user_id']) && empty($_SESSION['id_users']) && empty($_SESSION['id_karyawan'])) {
    http_response_code(401);
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

$idBackup = (int)($_GET['id'] ?? 0);

if ($idBackup <= 0) {
    http_response_code(400);
    die('ID backup tidak valid.');
}

$stmt = $conn->prepare("SELECT id_backup, nama_file, lokasi FROM backup WHERE id_backup = ? LIMIT 1");
$stmt->bind_param("i", $idBackup);
$stmt->execute();
$backup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$backup) {
    http_response_code(404);
    die('Data arsip backup tidak ditemukan.');
}

$backupDir = dirname(__DIR__, 2) . '/backups/';
$filePath = $backupDir . $backup['nama_file'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Berkas backup fisik tidak ditemukan pada server.');
}

// Log activity jika ada
if (function_exists('logActivity')) {
    $user = $_SESSION['nama_users'] ?? $_SESSION['nama_karyawan'] ?? 'User';
    logActivity($conn, 'DOWNLOAD_BACKUP', "Mengunduh file backup database: {$backup['nama_file']} oleh {$user}");
}

// Stream download
header('Content-Description: File Transfer');
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($backup['nama_file']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
