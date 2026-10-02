<?php
/**
 * API Auth: Reset Password Karyawan via Email - PT Jaya Teknik
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$tanggalLahir = trim($_POST['tanggal_lahir'] ?? '');

if (empty($email) || empty($tanggalLahir)) {
    echo json_encode(['success' => false, 'message' => 'Email dan Tanggal Lahir wajib diisi.']);
    exit;
}

// Cari karyawan yang cocok
$stmt = $conn->prepare("SELECT id_karyawan, nama_karyawan, aktif, login_web FROM karyawan WHERE email = ? AND tanggal_lahir = ? LIMIT 1");
$stmt->bind_param("ss", $email, $tanggalLahir);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $stmt->close();

    if ((int)$row['aktif'] !== 1) {
        echo json_encode(['success' => false, 'message' => 'Akun karyawan tidak aktif. Hubungi administrator.']);
        exit;
    }

    if (!isset($row['login_web']) || (int)$row['login_web'] !== 1) {
        echo json_encode(['success' => false, 'message' => 'Akun Anda tidak memiliki hak akses login.']);
        exit;
    }

    // Generate random password
    $tempPass = 'JTEK-' . substr(str_shuffle("23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ"), 0, 6);
    $hashedPass = password_hash($tempPass, PASSWORD_DEFAULT);

    // Update DB
    $up = $conn->prepare("UPDATE karyawan SET password = ? WHERE id_karyawan = ?");
    $up->bind_param("si", $hashedPass, $row['id_karyawan']);
    if ($up->execute()) {
        $up->close();

        // Kirim email
        $namaKaryawan = $row['nama_karyawan'];
        $subject = "Reset Password Sistem JT_Purchase";
        $htmlBody = "
            <div style='font-family: Arial, sans-serif; line-height: 1.5; color: #333;'>
                <h2>Permintaan Reset Password</h2>
                <p>Halo <strong>" . htmlspecialchars($namaKaryawan) . "</strong>,</p>
                <p>Kami telah menerima permintaan reset password untuk akun Anda di Sistem JT_Purchase.</p>
                <p>Berikut adalah password sementara Anda:</p>
                <h3 style='background-color: #f4f4f4; padding: 10px; display: inline-block; border-radius: 4px; letter-spacing: 1px;'>" . htmlspecialchars($tempPass) . "</h3>
                <p>Silakan login menggunakan password sementara tersebut dan <strong>segera ubah password Anda di menu profil</strong> demi keamanan.</p>
                <br>
                <p>Salam,<br><strong>Tim IT Administrator PT Jaya Teknik</strong></p>
            </div>
        ";

        $mailRes = sendSmtpEmail($conn, $email, $namaKaryawan, $subject, $htmlBody);
        
        if ($mailRes['success']) {
            echo json_encode(['success' => true, 'message' => 'Password sementara telah berhasil dikirim ke email Anda.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Password berhasil direset, tetapi gagal mengirim email: ' . $mailRes['message']]);
        }

    } else {
        $up->close();
        echo json_encode(['success' => false, 'message' => 'Gagal mereset password di database.']);
    }

} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Karyawan dengan Email dan Tanggal Lahir tersebut tidak ditemukan.']);
}
