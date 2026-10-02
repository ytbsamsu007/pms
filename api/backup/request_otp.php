<?php
/**
 * API Backup: Request OTP
 * Memverifikasi password user terlebih dahulu, jika valid maka kirim kode OTP 6 digit ke email.
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

$password = trim($input['password'] ?? '');
$actionType = strtoupper(trim($input['action_type'] ?? 'BACKUP_DATABASE')); // BACKUP_DATABASE atau RESTORE_DATABASE
$targetEmail = trim($input['email'] ?? '');

if (empty($password)) {
    jsonResponse(false, 'Password konfirmasi wajib diisi untuk verifikasi keamanan.', null, 200);
}

if (!in_array($actionType, ['BACKUP_DATABASE', 'RESTORE_DATABASE'])) {
    jsonResponse(false, 'Tipe aksi OTP tidak valid.', null, 200);
}

// 0. Cek Apakah Sesi Sedang Dibekukan (Freeze 5 Menit)
$now = time();
$freezeUntil = (int)($_SESSION['backup_security_freeze_until'] ?? 0);
if ($freezeUntil > $now) {
    $sisaDetik = $freezeUntil - $now;
    $sisaMenit = ceil($sisaDetik / 60);
    jsonResponse(false, "Sistem sedang dibekukan sementara karena salah 3 kali. Silakan tunggu {$sisaDetik} detik ({$sisaMenit} menit) sebelum meminta kode baru.", [
        'frozen' => true,
        'freeze_seconds' => $sisaDetik
    ], 200);
}

// Cari data akun user aktif di database untuk memverifikasi password
$isPasswordValid = false;
$userEmail = '';
$userName = '';
$idUser = (int)$currentUser['id'];
$userType = !empty($currentUser['id_karyawan']) ? 'karyawan' : 'users';

if (!empty($currentUser['id_karyawan'])) {
    $idUser = (int)$currentUser['id_karyawan'];
    $userType = 'karyawan';
    $stmt = $conn->prepare("SELECT id_karyawan, nama_karyawan, email, password FROM karyawan WHERE id_karyawan = ? AND aktif = 1 LIMIT 1");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    $kRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($kRow) {
        $storedHash = $kRow['password'];
        if (password_verify($password, $storedHash) || $password === $storedHash) {
            $isPasswordValid = true;
            $userEmail = $kRow['email'];
            $userName = $kRow['nama_karyawan'];
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
            $userEmail = $uRow['email'];
            $userName = $uRow['nama_users'];
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
        jsonResponse(false, "Password konfirmasi Anda salah. Sisa kesempatan: {$sisa} kali lagi.", [
            'frozen' => false,
            'remaining_attempts' => $sisa
        ], 200);
    }
}

// Password valid: reset auth fail counter
$_SESSION['backup_auth_fails'] = 0;

// Tentukan email tujuan pengiriman OTP
$recipientEmail = $userEmail;
if (!empty($targetEmail) && filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
    $recipientEmail = $targetEmail;
}

if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    $smtpRes = $conn->query("SELECT user_login FROM smtp_server WHERE aktif = 1 ORDER BY id_stmp ASC LIMIT 1");
    if ($smtpRes && $sRow = $smtpRes->fetch_assoc()) {
        $recipientEmail = $sRow['user_login'];
    }
}

if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Email tujuan tidak ditemukan atau tidak valid. Silakan tentukan alamat email tujuan.', null, 200);
}

// 1. Cek Cooldown Pengiriman Ulang Standar (60 Detik)
$stmtCooldown = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed_seconds, created_at 
                                FROM otp_verification 
                                WHERE user_type = ? AND id_user = ? AND action_type = ? 
                                ORDER BY id_otp DESC LIMIT 1");
$stmtCooldown->bind_param("sis", $userType, $idUser, $actionType);
$stmtCooldown->execute();
$lastOtp = $stmtCooldown->get_result()->fetch_assoc();
$stmtCooldown->close();

if ($lastOtp && isset($lastOtp['elapsed_seconds'])) {
    $elapsed = (int)$lastOtp['elapsed_seconds'];
    $cooldownLimit = 60; // 60 Detik

    if ($elapsed >= 0 && $elapsed < $cooldownLimit) {
        $sisaDetik = $cooldownLimit - $elapsed;
        jsonResponse(false, "Mohon tunggu {$sisaDetik} detik lagi sebelum meminta kode OTP baru.", [
            'cooldown_seconds' => $sisaDetik
        ], 200);
    }
}

// 2. Generate 6 Digit Angka OTP Acak
$otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

// 3. Simpan ke Database (Berlaku 15 Menit)
$stmtInsert = $conn->prepare("INSERT INTO otp_verification 
                             (user_type, id_user, email, otp_code, action_type, attempts, is_used, expires_at, created_at) 
                             VALUES (?, ?, ?, ?, ?, 0, 0, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())");
$stmtInsert->bind_param("sisss", $userType, $idUser, $recipientEmail, $otpCode, $actionType);

if (!$stmtInsert->execute()) {
    $err = $stmtInsert->error;
    $stmtInsert->close();
    jsonResponse(false, 'Gagal membuat token OTP: ' . $err, null, 500);
}
$stmtInsert->close();

// 4. Kirim Email OTP via SMTP
$actionLabel = ($actionType === 'BACKUP_DATABASE') ? 'Pencadangan (Backup) Database' : 'Pemulihan (Restore) Database';
$subject = "Kode OTP Otorisasi Keamanan: {$actionLabel} - PT Jaya Teknis";
$htmlBody = renderBackupOtpEmailTemplate($userName ?: 'Pengguna', $otpCode, $actionLabel, 15);

$sent = sendSmtpEmail($conn, $recipientEmail, $userName ?: 'Pengguna Sistem', $subject, $htmlBody);

if (!$sent['success']) {
    jsonResponse(false, 'Kode OTP berhasil digenerate, namun gagal dikirim ke email: ' . $sent['message'], [
        'email_target'     => $recipientEmail,
        'action_type'      => $actionType,
        'cooldown_seconds' => 60
    ], 500);
}

$parts = explode('@', $recipientEmail);
$maskedEmail = substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');

jsonResponse(true, "Kode OTP telah dikirimkan ke email {$maskedEmail}. Silakan periksa kotak masuk atau spam.", [
    'email_target'     => $recipientEmail,
    'masked_email'     => $maskedEmail,
    'action_type'      => $actionType,
    'cooldown_seconds' => 60
]);
