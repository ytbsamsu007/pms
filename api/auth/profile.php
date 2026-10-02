<?php
/**
 * API Auth / Profile: Edit Profil Karyawan / User yang Sedang Login
 * Path: api/auth/profile.php
 * Endpoint: GET & POST / PUT
 * Fitur: Validasi Ganti Password dengan OTP 6 Digit via SMTP Server
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/mailer.php';

// Wajib Login
$currentUser = requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($rawInput['_method'])) {
        $method = strtoupper($rawInput['_method']);
    }
}

// Helper Masking Email (Contoh: budi.santoso@gmail.com -> b***o@gmail.com)
function maskEmailAddress($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    $name = $parts[0];
    $len = strlen($name);
    if ($len <= 2) {
        $maskedName = substr($name, 0, 1) . '*';
    } else {
        $maskedName = substr($name, 0, 1) . str_repeat('*', max(1, $len - 2)) . substr($name, -1);
    }
    return $maskedName . '@' . $parts[1];
}

// =========================================================================
// 1. GET: Ambil Data Profil Karyawan / User yang Sedang Login
// =========================================================================
if ($method === 'GET') {
    $idKaryawan = $currentUser['id_karyawan'] ?? null;
    $idUsers = $currentUser['id_users'] ?? ($currentUser['id'] ?? null);

    $statusKaryawanMap = [
        0 => 'Magang (Internship)',
        1 => 'PKWT (Perjanjian Kerja Waktu Tertentu)',
        2 => 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)',
        3 => 'Pekerja paruh waktu (Part-time)',
        4 => 'Harian Lepas (Casual Workers)',
        5 => 'Freelance / Pekerja Lepas',
        6 => 'Outsourcing / Alih Daya',
        7 => 'Volunteer / Sukarelawan'
    ];

    if (!empty($idKaryawan)) {
        // Ambil data lengkap dari tabel karyawan
        $stmt = $conn->prepare("SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.email, k.no_handphone,
                                       k.tempat_lahir, k.tanggal_lahir, k.jenis_kelamin, k.tanggal_bergabung,
                                       k.id_jabatan, j.nama_jabatan, j.level as level_jabatan,
                                       k.id_divisi, d.nama_divisi,
                                       k.id_site, s.nama_site,
                                       k.status_karyawan, k.aktif
                                FROM karyawan k
                                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                                LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                                LEFT JOIN site s ON k.id_site = s.id_site
                                WHERE k.id_karyawan = ? LIMIT 1");
        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $row['source'] = 'karyawan';
            $row['role'] = $currentUser['role'];
            $stId = isset($row['status_karyawan']) && $row['status_karyawan'] !== null ? (int)$row['status_karyawan'] : 2;
            $row['status_karyawan_id'] = $stId;
            $row['status_karyawan_label'] = $statusKaryawanMap[$stId] ?? 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)';
            $stmt->close();
            jsonResponse(true, 'Profil karyawan berhasil dimuat.', $row);
        }
        $stmt->close();
    }

    // Fallback akun Super Admin di tabel users
    if (!empty($idUsers)) {
        $stmt = $conn->prepare("SELECT id_users, nama_users as nama_karyawan, email, 'ADMIN' as kode_karyawan,
                                       'Administrator' as nama_jabatan, 'IT & Manajemen' as nama_divisi,
                                       'Head Office' as nama_site, '' as no_handphone, 'Tetap' as status_karyawan,
                                       '' as tempat_lahir, NULL as tanggal_lahir, 1 as jenis_kelamin, NOW() as tanggal_bergabung
                                FROM users WHERE id_users = ? LIMIT 1");
        $stmt->bind_param("i", $idUsers);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $row['source'] = 'users';
            $row['role'] = ROLE_ADMIN;
            $row['status_karyawan_id'] = 2;
            $row['status_karyawan_label'] = 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)';
            $stmt->close();
            jsonResponse(true, 'Profil admin berhasil dimuat.', $row);
        }
        $stmt->close();
    }

    jsonResponse(false, 'Data profil pengguna tidak ditemukan.', null, 404);
}

// =========================================================================
// 2. POST ?action=request_password_otp : Kirim OTP 6 Digit ke Email via SMTP
// =========================================================================
if ($method === 'POST' && (isset($_GET['action']) && $_GET['action'] === 'request_password_otp')) {
    $idKaryawan = $currentUser['id_karyawan'] ?? null;
    $idUsers = $currentUser['id_users'] ?? ($currentUser['id'] ?? null);

    $userType = 'karyawan';
    $idUser = $idKaryawan;
    $userEmail = '';
    $userName = '';

    if (!empty($idKaryawan)) {
        $stmt = $conn->prepare("SELECT nama_karyawan, email, aktif FROM karyawan WHERE id_karyawan = ? LIMIT 1");
        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $userRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$userRow) {
            jsonResponse(false, 'Data karyawan tidak ditemukan.', null, 404);
        }
        if ((int)$userRow['aktif'] !== 1) {
            jsonResponse(false, 'Akun karyawan Anda berstatus Non-Aktif / Terkunci. Hubungi administrator.', null, 403);
        }

        $userType = 'karyawan';
        $idUser = $idKaryawan;
        $userEmail = trim($userRow['email'] ?? '');
        $userName = trim($userRow['nama_karyawan'] ?? 'Karyawan');
    } elseif (!empty($idUsers)) {
        $stmt = $conn->prepare("SELECT nama_users, email FROM users WHERE id_users = ? LIMIT 1");
        $stmt->bind_param("i", $idUsers);
        $stmt->execute();
        $userRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$userRow) {
            jsonResponse(false, 'Data pengguna admin tidak ditemukan.', null, 404);
        }

        $userType = 'users';
        $idUser = $idUsers;
        $userEmail = trim($userRow['email'] ?? '');
        $userName = trim($userRow['nama_users'] ?? 'Administrator');
    }

    if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Alamat email akun Anda belum valid. Lengkapi email terlebih dahulu.', null, 422);
    }

    // 1. Cek Cooldown Pengiriman Ulang (Wajib 90 Detik dengan sinkronisasi waktu database)
    $stmtCooldown = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed_seconds, created_at 
                                    FROM otp_verification 
                                    WHERE user_type = ? AND id_user = ? AND action_type = 'CHANGE_PASSWORD' 
                                    ORDER BY id_otp DESC LIMIT 1");
    $stmtCooldown->bind_param("si", $userType, $idUser);
    $stmtCooldown->execute();
    $lastOtp = $stmtCooldown->get_result()->fetch_assoc();
    $stmtCooldown->close();

    if ($lastOtp && isset($lastOtp['elapsed_seconds'])) {
        $elapsed = (int)$lastOtp['elapsed_seconds'];
        $cooldownLimit = 90; // 90 Detik (1.5 Menit)

        if ($elapsed >= 0 && $elapsed < $cooldownLimit) {
            $sisaDetik = $cooldownLimit - $elapsed;
            $formatWaktu = ($sisaDetik > 60) ? (ceil($sisaDetik / 60) . ' menit') : ($sisaDetik . ' detik');
            jsonResponse(false, "Mohon tunggu {$formatWaktu} lagi sebelum meminta kode OTP baru.", [
                'cooldown_seconds' => $sisaDetik
            ], 429);
        }
    }

    // 2. Generate 6 Digit Angka OTP Acak
    $otpCode = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    // 3. Simpan ke Database (Berlaku 1 Jam)
    $stmtInsert = $conn->prepare("INSERT INTO otp_verification 
                                 (user_type, id_user, email, otp_code, action_type, attempts, is_used, expires_at, created_at) 
                                 VALUES (?, ?, ?, ?, 'CHANGE_PASSWORD', 0, 0, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())");
    $stmtInsert->bind_param("siss", $userType, $idUser, $userEmail, $otpCode);
    
    if (!$stmtInsert->execute()) {
        $err = $stmtInsert->error;
        $stmtInsert->close();
        jsonResponse(false, 'Gagal membuat token OTP: ' . $err, null, 500);
    }
    $stmtInsert->close();

    // 4. Kirim Email melalui Core Mailer SMTP
    $subject = 'Kode Verifikasi Ganti Password (' . $otpCode . ') - PT Jaya Teknis';
    $htmlBody = renderOtpEmailTemplate($userName, $otpCode, 60);

    $mailResult = sendSmtpEmail($conn, $userEmail, $userName, $subject, $htmlBody);

    if ($mailResult['success']) {
        $masked = maskEmailAddress($userEmail);
        jsonResponse(true, "Kode OTP 6 digit berhasil dikirim ke {$masked}. Kode berlaku selama 1 Jam.", [
            'cooldown_seconds' => 90,
            'masked_email' => $masked,
            'expires_minutes' => 60
        ], 200);
    } else {
        jsonResponse(false, "Gagal mengirim email OTP: " . $mailResult['message'], null, 500);
    }
}

// =========================================================================
// 3. POST / PUT: Update Data Profil & Password (Wajib OTP jika Ganti Password)
// =========================================================================
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $nama = trim($input['nama_karyawan'] ?? $input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $noHp = trim($input['no_handphone'] ?? $input['telepon'] ?? '');
    $tempatLahir = trim($input['tempat_lahir'] ?? '');
    $tanggalLahir = !empty($input['tanggal_lahir']) ? trim($input['tanggal_lahir']) : null;
    $jenisKelamin = isset($input['jenis_kelamin']) && $input['jenis_kelamin'] !== '' ? (int)$input['jenis_kelamin'] : null;

    $passwordLama = trim($input['password_lama'] ?? '');
    $passwordBaru = trim($input['password_baru'] ?? '');
    $konfirmasiPassword = trim($input['konfirmasi_password'] ?? '');
    $otpInput = trim($input['otp_code'] ?? '');

    if (empty($nama)) {
        jsonResponse(false, 'Nama lengkap tidak boleh kosong.', null, 422);
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Alamat email tidak valid.', null, 422);
    }

    $idKaryawan = $currentUser['id_karyawan'] ?? null;
    $idUsers = $currentUser['id_users'] ?? ($currentUser['id'] ?? null);
    $userType = !empty($idKaryawan) ? 'karyawan' : 'users';
    $idUser = !empty($idKaryawan) ? $idKaryawan : $idUsers;
    $isAdmin = ($currentUser['role'] === ROLE_ADMIN);

    // -------------------------------------------------------------
    // VALIDASI KHUSUS JIKA INGIN GANTI PASSWORD
    // -------------------------------------------------------------
    $isChangingPassword = !empty($passwordBaru);

    if ($isChangingPassword) {
        if (strlen($passwordBaru) < 5) {
            jsonResponse(false, 'Password baru minimal harus 5 karakter.', null, 422);
        }
        if ($passwordBaru !== $konfirmasiPassword) {
            jsonResponse(false, 'Konfirmasi kata sandi baru tidak cocok.', null, 422);
        }
        if (empty($otpInput)) {
            jsonResponse(false, 'Kode verifikasi OTP 6 digit wajib dimasukkan untuk mengganti kata sandi.', null, 422);
        }

        // Ambil Record OTP Aktif Terbaru
        $stmtOtp = $conn->prepare("SELECT id_otp, otp_code, attempts, is_used, expires_at 
                                   FROM otp_verification 
                                   WHERE user_type = ? AND id_user = ? AND action_type = 'CHANGE_PASSWORD' AND is_used = 0 
                                   ORDER BY id_otp DESC LIMIT 1");
        $stmtOtp->bind_param("si", $userType, $idUser);
        $stmtOtp->execute();
        $activeOtp = $stmtOtp->get_result()->fetch_assoc();
        $stmtOtp->close();

        if (!$activeOtp) {
            jsonResponse(false, 'Tidak ditemukan permintaan kode OTP yang aktif. Silakan klik "Kirim Kode OTP" terlebih dahulu.', null, 404);
        }

        $idOtp = (int)$activeOtp['id_otp'];
        $currentAttempts = (int)$activeOtp['attempts'];

        // Cek apakah akun sudah terkunci karena melebihi 10x salah (kecuali Admin)
        if (!$isAdmin && $currentAttempts >= 10) {
            if ($userType === 'karyawan') {
                $conn->query("UPDATE karyawan SET aktif = 0 WHERE id_karyawan = $idKaryawan");
            }
            jsonResponse(false, 'Akun Anda telah dinonaktifkan karena telah salah memasukkan kode OTP sebanyak 10 kali. Silakan hubungi Administrator.', null, 403);
        }

        // Cek Masa Berlaku OTP (1 Jam)
        if (strtotime($activeOtp['expires_at']) < time()) {
            jsonResponse(false, 'Kode OTP telah kedaluwarsa (masa berlaku 1 jam telah habis). Silakan minta kode OTP baru.', null, 410);
        }

        // Cek Kesalahan Kode OTP
        if ($otpInput !== $activeOtp['otp_code']) {
            $newAttempts = $currentAttempts + 1;
            $conn->query("UPDATE otp_verification SET attempts = $newAttempts WHERE id_otp = $idOtp");

            if (!$isAdmin && $newAttempts >= 10) {
                // Nonaktifkan akun karyawan seketika
                if ($userType === 'karyawan') {
                    $conn->query("UPDATE karyawan SET aktif = 0 WHERE id_karyawan = $idKaryawan");
                }
                jsonResponse(false, 'Kode OTP salah. Anda telah mencapai batas maksimal 10x kesalahan. Akun Anda otomatis dinonaktifkan untuk keamanan. Hubungi Administrator.', [
                    'account_locked' => true,
                    'attempts' => $newAttempts
                ], 403);
            }

            if ($isAdmin) {
                jsonResponse(false, "Kode OTP yang Anda masukkan salah. (Percobaan ke-{$newAttempts}, Admin bebas mencoba).", [
                    'attempts' => $newAttempts,
                    'is_admin' => true
                ], 422);
            } else {
                $sisaKesempatan = 10 - $newAttempts;
                jsonResponse(false, "Kode OTP yang Anda masukkan salah. Sisa kesempatan: {$sisaKesempatan} kali lagi.", [
                    'attempts' => $newAttempts,
                    'remaining_attempts' => $sisaKesempatan
                ], 422);
            }
        }

        // Kode OTP BENAR! Tandai OTP telah digunakan
        $conn->query("UPDATE otp_verification SET is_used = 1 WHERE id_otp = $idOtp");
    }

    // -------------------------------------------------------------
    // A. UPDATE UNTUK AKUN KARYAWAN
    // -------------------------------------------------------------
    if (!empty($idKaryawan)) {
        // Cek duplikasi email ke karyawan lain
        $stmtChk = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE email = ? AND id_karyawan != ? LIMIT 1");
        $stmtChk->bind_param("si", $email, $idKaryawan);
        $stmtChk->execute();
        if ($stmtChk->get_result()->num_rows > 0) {
            $stmtChk->close();
            jsonResponse(false, 'Email tersebut sudah digunakan oleh akun karyawan lain.', null, 422);
        }
        $stmtChk->close();

        if ($isChangingPassword) {
            $newHash = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $stmtUp = $conn->prepare("UPDATE karyawan SET nama_karyawan = ?, email = ?, no_handphone = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?, password = ? WHERE id_karyawan = ?");
            $stmtUp->bind_param("sssssisi", $nama, $email, $noHp, $tempatLahir, $tanggalLahir, $jenisKelamin, $newHash, $idKaryawan);
        } else {
            $stmtUp = $conn->prepare("UPDATE karyawan SET nama_karyawan = ?, email = ?, no_handphone = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE id_karyawan = ?");
            $stmtUp->bind_param("sssssii", $nama, $email, $noHp, $tempatLahir, $tanggalLahir, $jenisKelamin, $idKaryawan);
        }

        if ($stmtUp->execute()) {
            $stmtUp->close();
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['no_handphone'] = $noHp;
            $_SESSION['nama'] = $nama;

            jsonResponse(true, $isChangingPassword ? 'Profil dan kata sandi baru Anda berhasil diperbarui!' : 'Profil akun Anda berhasil diperbarui.', [
                'nama_karyawan' => $nama,
                'email' => $email,
                'no_handphone' => $noHp
            ]);
        } else {
            $err = $stmtUp->error;
            $stmtUp->close();
            jsonResponse(false, 'Gagal memperbarui profil: ' . $err, null, 500);
        }
    }

    // -------------------------------------------------------------
    // B. UPDATE UNTUK AKUN USERS (ADMIN)
    // -------------------------------------------------------------
    if (!empty($idUsers)) {
        if ($isChangingPassword) {
            $newHash = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $stmtUp = $conn->prepare("UPDATE users SET nama_users = ?, email = ?, password = ? WHERE id_users = ?");
            $stmtUp->bind_param("sssi", $nama, $email, $newHash, $idUsers);
        } else {
            $stmtUp = $conn->prepare("UPDATE users SET nama_users = ?, email = ? WHERE id_users = ?");
            $stmtUp->bind_param("ssi", $nama, $email, $idUsers);
        }

        if ($stmtUp->execute()) {
            $stmtUp->close();
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['email'] = $email;
            $_SESSION['nama'] = $nama;

            jsonResponse(true, $isChangingPassword ? 'Profil dan kata sandi admin berhasil diperbarui!' : 'Profil admin berhasil diperbarui.', [
                'nama_karyawan' => $nama,
                'email' => $email
            ]);
        } else {
            $err = $stmtUp->error;
            $stmtUp->close();
            jsonResponse(false, 'Gagal memperbarui profil admin: ' . $err, null, 500);
        }
    }

    jsonResponse(false, 'Pengguna tidak valid.', null, 400);
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
