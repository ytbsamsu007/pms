<?php
/**
 * API Backup: Create Backup
 * Memvalidasi Password + OTP, meng-generate dump SQL dari database (sesuai scope tabel),
 * menyimpan file ke direktori backups/, mencatat ke tabel `backup`, dan mengirimkan file SQL ke email.
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
$otpCode = trim($input['otp_code'] ?? '');
$targetEmail = trim($input['email_backup'] ?? '');
$scopeInput = $input['scope'] ?? 'ALL'; // Bisa string 'ALL' atau array tabel ['users', 'po', ...]
$keterangan = trim($input['keterangan'] ?? '');

if (empty($password)) {
    jsonResponse(false, 'Password konfirmasi wajib diisi.', null, 200);
}
if (empty($otpCode)) {
    jsonResponse(false, 'Kode OTP verifikasi wajib diisi.', null, 200);
}
if (empty($targetEmail) || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Alamat email tujuan backup tidak valid atau kosong.', null, 200);
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
// 2. Verifikasi Kode OTP
// ==========================================
$stmtOtp = $conn->prepare("SELECT id_otp, otp_code, attempts, is_used, expires_at, created_at 
                          FROM otp_verification 
                          WHERE user_type = ? AND id_user = ? AND action_type = 'BACKUP_DATABASE' 
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
// 3. Tentukan Scope Tabel yang Akan di-Backup
// ==========================================
$allTablesRes = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
$existingTables = [];
while ($tRow = $allTablesRes->fetch_array()) {
    $existingTables[] = $tRow[0];
}

$selectedTables = [];
$scopeString = 'ALL';

if (is_array($scopeInput)) {
    foreach ($scopeInput as $t) {
        $cleanT = trim($t);
        if (in_array($cleanT, $existingTables)) {
            $selectedTables[] = $cleanT;
        }
    }
    if (empty($selectedTables) || count($selectedTables) === count($existingTables)) {
        $selectedTables = $existingTables;
        $scopeString = 'ALL';
    } else {
        $scopeString = implode(',', $selectedTables);
    }
} elseif (strtoupper((string)$scopeInput) === 'ALL' || empty($scopeInput)) {
    $selectedTables = $existingTables;
    $scopeString = 'ALL';
} else {
    $rawTables = explode(',', (string)$scopeInput);
    foreach ($rawTables as $t) {
        $cleanT = trim($t);
        if (in_array($cleanT, $existingTables)) {
            $selectedTables[] = $cleanT;
        }
    }
    if (empty($selectedTables) || count($selectedTables) === count($existingTables)) {
        $selectedTables = $existingTables;
        $scopeString = 'ALL';
    } else {
        $scopeString = implode(',', $selectedTables);
    }
}

// ==========================================
// 4. Generate Konten Backup SQL
// ==========================================
$now = date('Y-m-d H:i:s');
$timestamp = date('Ymd_His');
$fileName = "backup_db_" . DB_NAME . "_" . $timestamp . ".sql";
$targetDir = dirname(__DIR__, 2) . '/backups/';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$filePath = $targetDir . $fileName;

$sqlHeader  = "-- =========================================================\n";
$sqlHeader .= "-- PT JAYA TEKNIS - DATABASE BACKUP DUMP\n";
$sqlHeader .= "-- Database      : " . DB_NAME . "\n";
$sqlHeader .= "-- Generated At  : " . $now . "\n";
$sqlHeader .= "-- Generated By  : " . $userIdentifier . " (" . $userName . ")\n";
$sqlHeader .= "-- Target Scope  : " . ($scopeString === 'ALL' ? 'ALL TABLES' : count($selectedTables) . ' TABLES (' . $scopeString . ')') . "\n";
$sqlHeader .= "-- Description   : " . addslashes($keterangan ?: 'Database backup via system management') . "\n";
$sqlHeader .= "-- =========================================================\n\n";
$sqlHeader .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sqlHeader .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sqlHeader .= "SET AUTOCOMMIT = 0;\n";
$sqlHeader .= "START TRANSACTION;\n";
$sqlHeader .= "SET time_zone = \"+08:00\";\n\n";

$fileHandle = fopen($filePath, 'w');
if (!$fileHandle) {
    jsonResponse(false, 'Gagal membuat file backup di server (izin direktori backups/ ditolak).', null, 500);
}

fwrite($fileHandle, $sqlHeader);

foreach ($selectedTables as $table) {
    fwrite($fileHandle, "\n-- --------------------------------------------------------\n");
    fwrite($fileHandle, "-- Table structure for table `{$table}`\n");
    fwrite($fileHandle, "-- --------------------------------------------------------\n\n");
    fwrite($fileHandle, "DROP TABLE IF EXISTS `{$table}`;\n");

    $createTableRes = $conn->query("SHOW CREATE TABLE `{$table}`");
    if ($createTableRes && $ctRow = $createTableRes->fetch_row()) {
        fwrite($fileHandle, $ctRow[1] . ";\n\n");
    }

    // Dump Data Baris Tabel
    $rowsRes = $conn->query("SELECT * FROM `{$table}`");
    $rowCount = $rowsRes ? $rowsRes->num_rows : 0;

    if ($rowCount > 0) {
        fwrite($fileHandle, "-- Dumping data for table `{$table}` ({$rowCount} rows)\n");
        
        $fieldsRes = $conn->query("SHOW COLUMNS FROM `{$table}`");
        $columns = [];
        while ($col = $fieldsRes->fetch_assoc()) {
            $columns[] = '`' . $col['Field'] . '`';
        }
        $colList = implode(', ', $columns);

        $batchSize = 200;
        $batchValues = [];
        $counter = 0;

        while ($row = $rowsRes->fetch_row()) {
            $escapedRow = array_map(function ($val) use ($conn) {
                if ($val === null) {
                    return 'NULL';
                }
                return "'" . $conn->real_escape_string($val) . "'";
            }, $row);

            $batchValues[] = "(" . implode(', ', $escapedRow) . ")";
            $counter++;

            if (count($batchValues) >= $batchSize) {
                fwrite($fileHandle, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $batchValues) . ";\n");
                $batchValues = [];
            }
        }

        if (!empty($batchValues)) {
            fwrite($fileHandle, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $batchValues) . ";\n");
        }
        fwrite($fileHandle, "\n");
    }
}

$sqlFooter  = "\nSET FOREIGN_KEY_CHECKS=1;\n";
$sqlFooter .= "COMMIT;\n";
$sqlFooter .= "-- Dump completed on " . date('Y-m-d H:i:s') . "\n";

fwrite($fileHandle, $sqlFooter);
fclose($fileHandle);

// Ukuran file
$fileSizeBytes = file_exists($filePath) ? filesize($filePath) : 0;
$fileSizeFormatted = $fileSizeBytes >= 1048576 
    ? round($fileSizeBytes / 1048576, 2) . ' MB' 
    : ($fileSizeBytes >= 1024 ? round($fileSizeBytes / 1024, 2) . ' KB' : $fileSizeBytes . ' B');

// ==========================================
// 5. Simpan Record ke Tabel `backup`
// ==========================================
$lokasiDb = "backups/" . $fileName;
$stmtInsertBackup = $conn->prepare("INSERT INTO backup 
                                    (email_backup, tanggal_backup, id_karyawan, scope, keterangan, lokasi, nama_file) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmtInsertBackup->bind_param("sssssss", $targetEmail, $now, $userIdentifier, $scopeString, $keterangan, $lokasiDb, $fileName);

if (!$stmtInsertBackup->execute()) {
    $insertErr = $stmtInsertBackup->error;
    $stmtInsertBackup->close();
    jsonResponse(false, 'Gagal mencatat data riwayat backup ke database: ' . $insertErr, null, 500);
}

$idBackup = $stmtInsertBackup->insert_id;
$stmtInsertBackup->close();

// Log activity
if (function_exists('logActivity')) {
    logActivity($conn, 'CREATE_BACKUP', "Pencadangan database ID #{$idBackup} ({$fileName}) berhasil digenerate oleh {$userIdentifier}");
}

// ==========================================
// 6. Kirim Berkas Backup ke Email Target via SMTP
// ==========================================
$scopeDisplay = ($scopeString === 'ALL') ? 'Semua Tabel (Full Backup)' : count($selectedTables) . ' Tabel Terpilih (' . $scopeString . ')';
$subject = "Berkas Cadangan Database: {$fileName} - PT Jaya Teknis";
$htmlEmail = renderBackupFileEmailTemplate($userName ?: 'Pengguna Sistem', $fileName, $fileSizeFormatted, $scopeDisplay, $now, $keterangan);

$attachments = [
    [
        'path' => $filePath,
        'name' => $fileName
    ]
];

$emailSent = sendSmtpEmail($conn, $targetEmail, $userName ?: 'Pengguna Sistem', $subject, $htmlEmail, $attachments);

jsonResponse(true, 'Proses backup database berhasil diselesaikan dan dicatat ke sistem.', [
    'id_backup'           => $idBackup,
    'nama_file'           => $fileName,
    'file_size'           => $fileSizeFormatted,
    'scope'               => $scopeString,
    'total_tables'        => count($selectedTables),
    'tanggal_backup'      => $now,
    'email_sent'          => $emailSent['success'],
    'email_message'       => $emailSent['message'],
    'target_email'        => $targetEmail
]);
