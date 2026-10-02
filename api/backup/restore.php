<?php
/**
 * API Backup: Restore Database
 * Memverifikasi Password + OTP, lalu mengeksekusi restore berkas SQL ke database.
 * Mendukung Quick Restore berdasarkan id_backup atau upload manual berkas .sql.
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

set_time_limit(600);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../middleware/auth.php';

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
$password = trim($input['password'] ?? '');
$otpCode = trim($input['otp_code'] ?? '');

if (empty($password)) {
    jsonResponse(false, 'Password konfirmasi wajib diisi.', null, 200);
}
if (empty($otpCode)) {
    jsonResponse(false, 'Kode OTP verifikasi wajib diisi.', null, 200);
}

// 0. Cek Apakah Sesi Sedang Dibekukan (Freeze 5 Menit)
$now = time();
$freezeUntil = (int)($_SESSION['backup_security_freeze_until'] ?? 0);
if ($freezeUntil > $now) {
    $sisaDetik = $freezeUntil - $now;
    $sisaMenit = ceil($sisaDetik / 60);
    jsonResponse(false, "Sistem sedang dibekukan sementara karena salah 3 kali. Silakan tunggu {$sisaDetik} detik ({$sisaMenit} menit) sebelum mencoba kembali.", [
        'frozen' => true,
        'freeze_seconds' => $sisaDetik
    ], 200);
}

// ==========================================
// 1. Verifikasi Password Pengguna Aktif
// ==========================================
$idUser = (int)$currentUser['id'];
$userType = !empty($currentUser['id_karyawan']) ? 'karyawan' : 'users';
$userIdentifier = $currentUser['kode_karyawan'] ?: ($currentUser['nama'] ?: 'User #' . $idUser);
$userName = $currentUser['nama'] ?: 'Administrator';
$isPasswordValid = false;
$idKaryawanInt = !empty($currentUser['id_karyawan']) ? (int)$currentUser['id_karyawan'] : (int)$currentUser['id'];

if (!empty($currentUser['id_karyawan'])) {
    $idUser = (int)$currentUser['id_karyawan'];
    $userType = 'karyawan';
    $stmt = $conn->prepare("SELECT id_karyawan, kode_karyawan, nama_karyawan, email, password FROM karyawan WHERE id_karyawan = ? AND aktif = 1 LIMIT 1");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $kRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($kRow) {
        $storedHash = $kRow['password'];
        if (password_verify($password, $storedHash) || $password === $storedHash) {
            $isPasswordValid = true;
            $userName = $kRow['nama_karyawan'];
            $userIdentifier = $kRow['kode_karyawan'] ?: (string)$kRow['id_karyawan'];
        }
    }
} else {
    $stmt = $conn->prepare("SELECT id_users, nama_users, email, password FROM users WHERE id_users = ? AND aktif = 1 LIMIT 1");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $uRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($uRow) {
        $storedHash = $uRow['password'];
        if (password_verify($password, $storedHash) || $password === $storedHash) {
            $isPasswordValid = true;
            $userName = $uRow['nama_users'];
            $userIdentifier = $uRow['nama_users'] ?: ('User #' . $uRow['id_users']);
        }
    }
}

if (!$isPasswordValid) {
    $failCount = (int)($_SESSION['backup_auth_fails'] ?? 0) + 1;
    $_SESSION['backup_auth_fails'] = $failCount;

    if ($failCount >= 3) {
        $_SESSION['backup_security_freeze_until'] = $now + 300; // 5 menit
        $_SESSION['backup_auth_fails'] = 0;
        jsonResponse(false, "Password salah sebanyak 3 kali berturut-turut! Sistem dibekukan sementara selama 5 menit untuk keamanan.", [
            'frozen' => true,
            'freeze_seconds' => 300,
            'remaining_attempts' => 0
        ], 200);
    } else {
        $sisa = 3 - $failCount;
        jsonResponse(false, "Password konfirmasi salah. Sisa kesempatan: {$sisa} kali lagi.", [
            'frozen' => false,
            'remaining_attempts' => $sisa
        ], 200);
    }
}

// ==========================================
// 2. Verifikasi Kode OTP (action_type: RESTORE_DATABASE)
// ==========================================
$stmtOtp = $conn->prepare("SELECT id_otp, otp_code, attempts, is_used, expires_at, created_at 
                          FROM otp_verification 
                          WHERE user_type = ? AND id_user = ? AND action_type = 'RESTORE_DATABASE' 
                          ORDER BY id_otp DESC LIMIT 1");
$stmtOtp->bind_param("si", $userType, $idUser);
$stmtOtp->execute();
$otpData = $stmtOtp->get_result()->fetch_assoc();
$stmtOtp->close();

if (!$otpData) {
    jsonResponse(false, 'Kode OTP belum diminta atau telah kadaluarsa. Silakan minta kode OTP baru.', null, 200);
}

if ((int)$otpData['is_used'] === 1) {
    jsonResponse(false, 'Kode OTP sudah pernah digunakan. Silakan minta kode OTP baru.', null, 200);
}

if ((int)$otpData['attempts'] >= 3) {
    $elapsed = time() - strtotime($otpData['created_at']);
    if ($elapsed < 300) {
        $sisaDetik = 300 - $elapsed;
        $sisaMenit = ceil($sisaDetik / 60);
        jsonResponse(false, "Batas percobaan OTP (3x) telah terlampaui. Sistem dibekukan sementara selama 5 menit. Silakan tunggu {$sisaDetik} detik ({$sisaMenit} menit) sebelum meminta OTP baru.", [
            'frozen' => true,
            'freeze_seconds' => $sisaDetik
        ], 200);
    } else {
        jsonResponse(false, 'Batas percobaan OTP sebelumnya telah habis (3x). Silakan minta kode OTP baru.', null, 200);
    }
}

if (strtotime($otpData['expires_at']) < time()) {
    jsonResponse(false, 'Kode OTP telah kadaluarsa (melewati batas waktu 15 menit). Silakan minta kode OTP baru.', null, 200);
}

if ($otpData['otp_code'] !== $otpCode) {
    $newAttempts = (int)$otpData['attempts'] + 1;
    $idOtp = (int)$otpData['id_otp'];
    $conn->query("UPDATE otp_verification SET attempts = {$newAttempts} WHERE id_otp = {$idOtp}");

    $failCount = (int)($_SESSION['backup_auth_fails'] ?? 0) + 1;
    $_SESSION['backup_auth_fails'] = $failCount;

    if ($newAttempts >= 3 || $failCount >= 3) {
        $_SESSION['backup_security_freeze_until'] = $now + 300;
        $_SESSION['backup_auth_fails'] = 0;
        jsonResponse(false, "Kode OTP salah! Anda telah salah sebanyak 3 kali. Sistem dibekukan selama 5 menit sebelum dapat mencoba lagi.", [
            'frozen' => true,
            'freeze_seconds' => 300,
            'attempts' => $newAttempts,
            'remaining_attempts' => 0
        ], 200);
    } else {
        $sisaPercobaan = min(3 - $newAttempts, 3 - $failCount);
        jsonResponse(false, "Kode OTP yang Anda masukkan salah. Sisa kesempatan: {$sisaPercobaan} kali lagi.", [
            'frozen' => false,
            'attempts' => $newAttempts,
            'remaining_attempts' => $sisaPercobaan
        ], 200);
    }
}

// Berhasil: reset fail counters
$_SESSION['backup_auth_fails'] = 0;
$_SESSION['backup_security_freeze_until'] = 0;

// Mark OTP as used
$idOtp = (int)$otpData['id_otp'];
$conn->query("UPDATE otp_verification SET is_used = 1 WHERE id_otp = {$idOtp}");

// ==========================================
// 3. Tentukan Berkas SQL Restore
// ==========================================
$sqlFilePath = '';
$backupRow = null;

if ($idBackup > 0) {
    $stmtB = $conn->prepare("SELECT * FROM backup WHERE id_backup = ? LIMIT 1");
    $stmtB->bind_param("i", $idBackup);
    $stmtB->execute();
    $backupRow = $stmtB->get_result()->fetch_assoc();
    $stmtB->close();

    if (!$backupRow) {
        jsonResponse(false, 'Data riwayat backup tidak ditemukan di database.', null, 404);
    }

    $backupDir = dirname(__DIR__, 2) . '/backups/';
    $sqlFilePath = $backupDir . $backupRow['nama_file'];

    if (!file_exists($sqlFilePath)) {
        jsonResponse(false, "Berkas backup '{$backupRow['nama_file']}' tidak ditemukan di folder backups/ server.", null, 404);
    }
} elseif (isset($_FILES['file_sql']) && $_FILES['file_sql']['error'] === UPLOAD_ERR_OK) {
    $uploadedTmp = $_FILES['file_sql']['tmp_name'];
    $fileName = basename($_FILES['file_sql']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($ext !== 'sql') {
        jsonResponse(false, 'Format berkas harus berekstensi .sql', null, 422);
    }

    $sqlFilePath = $uploadedTmp;
} else {
    jsonResponse(false, 'ID Backup atau berkas SQL restore wajib dipilih.', null, 422);
}

// ==========================================
// 4. Eksekusi Restore SQL File
// ==========================================
try {
    $fileContent = file_get_contents($sqlFilePath);
    if (empty($fileContent)) {
        jsonResponse(false, 'Berkas SQL kosong atau tidak dapat dibaca.', null, 422);
    }

    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
    $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");
    
    if (!$conn->multi_query($fileContent)) {
        throw new Exception("Gagal mengeksekusi multi query SQL: " . $conn->error);
    }

    $queriesExecuted = 0;
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
        $queriesExecuted++;
    } while ($conn->more_results() && $conn->next_result());

    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");

    $now = date('Y-m-d H:i:s');

    if ($idBackup > 0) {
        $stmtUp = $conn->prepare("UPDATE backup SET id_karyawan_restore = ?, tanggal_restore = ? WHERE id_backup = ?");
        $stmtUp->bind_param("isi", $idKaryawanInt, $now, $idBackup);
        $stmtUp->execute();
        $stmtUp->close();
    }

    if (function_exists('logActivity')) {
        $backupName = $backupRow ? $backupRow['nama_file'] : 'Manual Upload';
        logActivity($conn, 'RESTORE_DATABASE', "Pemulihan (Restore) database berhasil dieksekusi ({$backupName}) oleh {$userIdentifier}");
    }

    jsonResponse(true, 'Database berhasil dipulihkan (restore) ke kondisi arsip cadangan.', [
        'id_backup'        => $idBackup,
        'nama_file'        => $backupRow ? $backupRow['nama_file'] : 'Manual File',
        'tanggal_restore'  => $now,
        'queries_count'    => $queriesExecuted,
        'pelaksana'        => $userName ?: $userIdentifier
    ]);
} catch (Throwable $e) {
    $conn->query("SET FOREIGN_KEY_CHECKS = 1;");
    jsonResponse(false, 'Terjadi kesalahan saat memulihkan database: ' . $e->getMessage(), null, 500);
}
