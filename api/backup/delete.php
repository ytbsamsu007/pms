<?php
/**
 * API Backup: Delete File & Record
 * Menghapus record backup dari database dan menghapus berkas fisik .sql di folder backups/.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(false, 'Metode HTTP tidak diizinkan. Gunakan POST atau DELETE.', null, 405);
}

// Auth Protection
$currentUser = apiAuth();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$idBackup = (int)($input['id_backup'] ?? 0);

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

// Hapus berkas fisik jika ada
$backupDir = dirname(__DIR__, 2) . '/backups/';
$filePath = $backupDir . $backup['nama_file'];

if (file_exists($filePath)) {
    @unlink($filePath);
}

// Hapus data dari tabel backup
$stmtDel = $conn->prepare("DELETE FROM backup WHERE id_backup = ?");
$stmtDel->bind_param("i", $idBackup);

if ($stmtDel->execute()) {
    $stmtDel->close();
    
    if (function_exists('logActivity')) {
        $user = $currentUser['nama'] ?: 'User';
        logActivity($conn, 'DELETE_BACKUP', "Menghapus arsip backup ID #{$idBackup} ({$backup['nama_file']}) oleh {$user}");
    }

    jsonResponse(true, "Arsip backup '{$backup['nama_file']}' berhasil dihapus dari sistem.");
} else {
    $err = $stmtDel->error;
    $stmtDel->close();
    jsonResponse(false, 'Gagal menghapus data backup dari database: ' . $err, null, 500);
}
