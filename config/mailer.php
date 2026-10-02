<?php
/**
 * Core SMTP Mailer Service - ' . getCompanyProfile()['nama'] . '
 * Path: config/mailer.php
 * Standalone Engine: PHPMailer 7.1.1 (Native / No Composer Required)
 */

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendSmtpEmail')) {
    /**
     * Kirim Email via PHPMailer 7.1.1 menggunakan Server SMTP aktif dari tabel database smtp_server
     * 
     * @param mysqli $conn
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $htmlBody
     * @param array $attachments Array of file paths or array of ['path' => ..., 'name' => ...]
     * @return array [success => bool, message => string]
     */
    function sendSmtpEmail($conn, $toEmail, $toName, $subject, $htmlBody, $attachments = []) {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Alamat email penerima tidak valid.'];
        }

        // 1. Ambil Server SMTP Aktif yang memiliki sisa kuota
        $stmt = $conn->prepare("SELECT id_stmp, nama_provider, stmp_server, port, user_login, password, limit_harian, sisa_harian 
                                FROM smtp_server 
                                WHERE aktif = 1 AND (sisa_harian > 0 OR sisa_harian IS NULL) 
                                ORDER BY id_stmp ASC LIMIT 1");
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res || $res->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Tidak ada Server SMTP aktif yang memiliki sisa kuota harian. Hubungi administrator.'];
        }

        $smtp = $res->fetch_assoc();
        $stmt->close();

        $idSmtp = (int)$smtp['id_stmp'];
        $host = trim($smtp['stmp_server']);
        $port = (int)$smtp['port'];
        $user = trim($smtp['user_login']);
        $pass = $smtp['password'];

        // Tentukan From Email & From Name resmi
        $fromEmail = 'info@jayateknis.com';
        $fromName = '' . getCompanyProfile()['nama'] . ' System';

        // Jika Brevo atau relay provider yang memerlukan verified sender
        if (strpos($host, 'brevo.com') !== false || strpos($user, '@smtp-brevo.com') !== false) {
            $fromEmail = 'shem1990@gmail.com';
        } elseif (filter_var($user, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $user;
        }

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug   = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host        = $host;
            $mail->SMTPAuth    = true;
            $mail->Username    = $user;
            $mail->Password    = $pass;

            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port        = $port;
            $mail->CharSet     = 'UTF-8';
            $mail->Timeout     = 25;

            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($fromEmail, $fromName);

            // Attachments
            if (!empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $att) {
                    if (is_string($att) && file_exists($att)) {
                        $mail->addAttachment($att);
                    } elseif (is_array($att) && !empty($att['path']) && file_exists($att['path'])) {
                        $attName = !empty($att['name']) ? $att['name'] : basename($att['path']);
                        $mail->addAttachment($att['path'], $attName);
                    }
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject     = $subject;
            $mail->Body        = $htmlBody;
            $mail->AltBody     = strip_tags($htmlBody);

            $mail->send();

            // Potong sisa kuota harian SMTP
            $conn->query("UPDATE smtp_server SET sisa_harian = GREATEST(0, sisa_harian - 1) WHERE id_stmp = $idSmtp");

            return ['success' => true, 'message' => "Email berhasil dikirim ke {$toEmail}"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Gagal mengirim email via PHPMailer: {$mail->ErrorInfo}"];
        }
    }
}

if (!function_exists('renderOtpEmailTemplate')) {
    /**
     * Template Email HTML Elegan untuk Kode OTP
     */
    function renderOtpEmailTemplate($nama, $otpCode, $expiresMinutes = 60) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Kode Verifikasi Ganti Password</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 40px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 32px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">' . strtoupper(getCompanyProfile()['nama']) . '</h1>
                                    <p style="margin: 6px 0 0 0; font-size: 13px; opacity: 0.85;">Web-Based Purchasing &amp; Logistics System</p>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 35px 30px; color: #1e293b;">
                                    <h2 style="margin: 0 0 12px 0; font-size: 18px; color: #0f2744; font-weight: 600;">Halo, ' . htmlspecialchars($nama) . '!</h2>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Kami menerima permintaan perubahan kata sandi untuk akun Anda pada sistem <strong>' . getCompanyProfile()['nama'] . '</strong>. Gunakan kode verifikasi (OTP) berikut untuk menyelesaikan proses:
                                    </p>

                                    <!-- OTP Code Box -->
                                    <div style="background-color: #f8fafc; border: 2px dashed #0284c7; border-radius: 10px; padding: 22px 15px; text-align: center; margin: 25px 0;">
                                        <span style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">KODE VERIFIKASI OTP (6 DIGIT)</span>
                                        <span style="font-family: \'Courier New\', Courier, monospace; font-size: 36px; font-weight: 800; color: #0284c7; letter-spacing: 8px; display: inline-block;">' . htmlspecialchars($otpCode) . '</span>
                                    </div>

                                    <!-- Expiration & Security Info -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #eff6ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 25px;">
                                        <tr>
                                            <td style="font-size: 13px; color: #1e40af; line-height: 1.5;">
                                                ⏱️ Kode OTP ini berlaku selama <strong>' . $expiresMinutes . ' menit (1 Jam)</strong>. Jangan berikan kode ini kepada siapapun termasuk pihak teknisi/IT.
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #94a3b8;">
                                        Jika Anda tidak merasa melakukan permintaan ini, segera laporkan ke bagian IT &amp; Administrator untuk mengamankan akun Anda.
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' ' . getCompanyProfile()['nama'] . '. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pesan ini dikirim otomatis oleh sistem keamanan. Mohon tidak membalas email ini.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}

if (!function_exists('getLogistikApproverEmails')) {
    /**
     * Mengambil daftar email dan nama tim Logistik aktif untuk approval RO tahap 1
     * 
     * @param mysqli $conn
     * @return array Array of ['email' => string, 'nama' => string, 'role' => string]
     */
    function getLogistikApproverEmails($conn) {
        $recipients = [];
        $addedEmails = [];

        // 1. Ambil Karyawan aktif di Divisi Logistik / Jabatan Logistik
        $sql = "SELECT k.id_karyawan, k.nama_karyawan, k.email, j.nama_jabatan, d.nama_divisi, k.id_divisi, k.id_jabatan
                FROM karyawan k
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                WHERE k.aktif = 1 AND k.email IS NOT NULL AND k.email != ''";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $email = strtolower(trim($row['email']));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || in_array($email, $addedEmails)) {
                    continue;
                }

                $idDiv = (int)($row['id_divisi'] ?? 0);
                $idJab = (int)($row['id_jabatan'] ?? 0);
                $divLower = strtolower($row['nama_divisi'] ?? '');
                $jabLower = strtolower($row['nama_jabatan'] ?? '');

                // Hanya Logistik
                $isLogistik = (
                    $idDiv === 2 ||
                    $idJab === 2 ||
                    strpos($divLower, 'logistik') !== false ||
                    strpos($jabLower, 'logistik') !== false
                );

                if ($isLogistik) {
                    $recipients[] = [
                        'email' => $row['email'],
                        'nama'  => $row['nama_karyawan'],
                        'role'  => $row['nama_jabatan'] ?: 'Logistik & Gudang'
                    ];
                    $addedEmails[] = $email;
                }
            }
        }

        // 2. Ambil User admin dengan email valid jika ada
        try {
            $resU = $conn->query("SELECT id_users, nama_users, email FROM users WHERE aktif = 1 AND email IS NOT NULL AND email != ''");
            if ($resU) {
                while ($u = $resU->fetch_assoc()) {
                    $email = strtolower(trim($u['email']));
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $addedEmails)) {
                        // User admin default
                    }
                }
            }
        } catch (\Throwable $e) {}

        return $recipients;
    }
}

if (!function_exists('getPurchasingEmails')) {
    /**
     * Mengambil daftar email dan nama tim Purchasing aktif untuk pemrosesan RO ke PO
     * 
     * @param mysqli $conn
     * @return array Array of ['email' => string, 'nama' => string, 'role' => string]
     */
    function getPurchasingEmails($conn) {
        $recipients = [];
        $addedEmails = [];

        // 1. Ambil Karyawan aktif di Divisi Purchasing / Jabatan Purchasing
        $sql = "SELECT k.id_karyawan, k.nama_karyawan, k.email, j.nama_jabatan, d.nama_divisi, k.id_divisi, k.id_jabatan
                FROM karyawan k
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                WHERE k.aktif = 1 AND k.email IS NOT NULL AND k.email != ''";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $email = strtolower(trim($row['email']));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || in_array($email, $addedEmails)) {
                    continue;
                }

                $idDiv = (int)($row['id_divisi'] ?? 0);
                $idJab = (int)($row['id_jabatan'] ?? 0);
                $divLower = strtolower($row['nama_divisi'] ?? '');
                $jabLower = strtolower($row['nama_jabatan'] ?? '');

                // Hanya Purchasing
                $isPurchasing = (
                    $idDiv === 3 ||
                    $idJab === 5 ||
                    strpos($divLower, 'purchasing') !== false ||
                    strpos($jabLower, 'purchasing') !== false ||
                    strpos($divLower, 'pengadaan') !== false
                );

                if ($isPurchasing) {
                    $recipients[] = [
                        'email' => $row['email'],
                        'nama'  => $row['nama_karyawan'],
                        'role'  => $row['nama_jabatan'] ?: 'Purchasing & Pengadaan'
                    ];
                    $addedEmails[] = $email;
                }
            }
        }

        return $recipients;
    }
}

if (!function_exists('getRoApproverEmails')) {
    /**
     * Alias backward-compatible: saat RO baru dibuat, hanya dikirimkan ke Logistik
     * 
     * @param mysqli $conn
     * @return array
     */
    function getRoApproverEmails($conn) {
        return getLogistikApproverEmails($conn);
    }
}

if (!function_exists('renderNewRoEmailTemplate')) {
    /**
     * Template Email HTML Elegan untuk Pemberitahuan Request Order Baru (Butuh Persetujuan)
     */
    function renderNewRoEmailTemplate($roData, $items, $pemohonData) {
        $nomorRo = htmlspecialchars($roData['nomor'] ?? '-');
        $tglRo = !empty($roData['tanggal_ro']) ? date('d/m/Y H:i', strtotime($roData['tanggal_ro'])) : date('d/m/Y H:i');
        $prioritas = (strtoupper($roData['prioritas'] ?? '') === 'URGENT') ? 'URGENT' : 'NORMAL';
        $namaSite = htmlspecialchars($roData['nama_site'] ?? 'Semua Lokasi / Site Utama');
        $namaVendor = !empty($roData['nama_vendor']) ? htmlspecialchars($roData['nama_vendor']) : 'Belum Ditentukan';
        if (!empty($roData['nama_vendor']) && !empty($roData['kode_vendor'])) {
            $namaVendor .= ' <span style="font-size: 11px; color: #64748b; font-family: monospace;">[' . htmlspecialchars($roData['kode_vendor']) . ']</span>';
        }
        $keterangan = !empty($roData['keterangan']) ? htmlspecialchars($roData['keterangan']) : '-';
        $namaPemohon = htmlspecialchars($pemohonData['nama_karyawan'] ?? 'Karyawan');
        $jabatanPemohon = htmlspecialchars($pemohonData['nama_jabatan'] ?? ($pemohonData['nama_divisi'] ?? 'Operasional'));

        $badgeColor = ($prioritas === 'URGENT') ? '#dc2626' : '#2563eb';
        $badgeBg = ($prioritas === 'URGENT') ? '#fee2e2' : '#dbeafe';
        $prioritasLabel = $prioritas;

        $itemsHtml = '';
        $no = 1;
        foreach ($items as $it) {
            $namaBarang = htmlspecialchars($it['nama_barang'] ?? '-');
            $kategori = htmlspecialchars($it['nama_kategori'] ?? ($it['kategori'] ?? '-'));
            $merk = htmlspecialchars($it['nama_merk'] ?? ($it['merk'] ?? '-'));
            $qty = isset($it['qty']) ? (float)$it['qty'] : 0;
            $satuan = htmlspecialchars($it['satuan'] ?? 'PCS');
            $harga = isset($it['harga']) && (float)$it['harga'] > 0 ? 'Rp ' . number_format((float)$it['harga'], 0, ',', '.') : '-';

            $itemsHtml .= '
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 8px; font-size: 12px; color: #64748b; text-align: center;">' . $no++ . '</td>
                <td style="padding: 10px 10px; font-size: 13px; color: #1e293b; font-weight: 600;">' . $namaBarang . '</td>
                <td style="padding: 10px 8px; font-size: 12px; color: #475569;">' . $kategori . '</td>
                <td style="padding: 10px 8px; font-size: 12px; color: #475569;">' . $merk . '</td>
                <td style="padding: 10px 8px; font-size: 13px; color: #1e293b; text-align: center; font-weight: 600;">' . $qty . ' ' . $satuan . '</td>
                <td style="padding: 10px 10px; font-size: 12px; color: #64748b; text-align: right; font-family: monospace;">' . $harga . '</td>
            </tr>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Request Order Baru - Butuh Persetujuan</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 30px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 650px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 28px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">' . strtoupper(getCompanyProfile()['nama']) . '</h1>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;">Notifikasi Sistem Pengadaan &amp; Logistik</p>
                                </td>
                            </tr>

                            <!-- Status Badge Banner -->
                            <tr>
                                <td style="background-color: #eff6ff; padding: 14px 24px; border-bottom: 1px solid #dbeafe;">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td style="font-size: 13px; font-weight: 700; color: #1e40af;">
                                                📋 REQUEST ORDER BARU MASUK
                                            </td>
                                            <td style="text-align: right;">
                                                <span style="background-color: ' . $badgeBg . '; color: ' . $badgeColor . '; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                                                    ' . $prioritas . '
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 28px 24px; color: #1e293b;">
                                    <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                        Yth. <strong>Bapak/Ibu Approver &amp; Tim Pengadaan</strong>,
                                    </p>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Terdapat pengajuan <strong>Request Order (RO)</strong> baru yang telah dikirimkan dan membutuhkan peninjauan / persetujuan Anda:
                                    </p>

                                    <!-- Summary Card -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 13px;">
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; width: 35%; border-bottom: 1px solid #e2e8f0;">Nomor RO</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: #0f2744; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Tanggal Pengajuan</td>
                                            <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;">' . $tglRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Pemohon</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">' . $namaPemohon . ' <span style="font-weight: normal; color: #64748b;">(' . $jabatanPemohon . ')</span></td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Tingkat Prioritas Kebutuhan</td>
                                            <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0;">
                                                <span style="background-color: ' . $badgeBg . '; color: ' . $badgeColor . '; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                                                    ' . $prioritasLabel . '
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Lokasi / Site Penempatan</td>
                                            <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;">' . $namaSite . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Vendor Rekanan Tujuan</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">' . $namaVendor . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b;">Catatan / Keterangan</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-style: italic;">' . $keterangan . '</td>
                                        </tr>
                                    </table>

                                    <!-- Tabel Item Material -->
                                    <div style="margin-bottom: 15px;">
                                        <div style="font-size: 13px; font-weight: 700; color: #0f2744; margin-bottom: 8px;">RINCIAN MATERIAL / BARANG:</div>
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                            <thead>
                                                <tr style="background-color: #f1f5f9; text-align: left;">
                                                    <th style="padding: 8px 8px; font-size: 12px; color: #475569; width: 30px; text-align: center;">No</th>
                                                    <th style="padding: 8px 10px; font-size: 12px; color: #475569;">Nama Material / Barang</th>
                                                    <th style="padding: 8px 8px; font-size: 12px; color: #475569; width: 90px;">Kategori</th>
                                                    <th style="padding: 8px 8px; font-size: 12px; color: #475569; width: 90px;">Merk</th>
                                                    <th style="padding: 8px 8px; font-size: 12px; color: #475569; text-align: center; width: 75px;">Qty</th>
                                                    <th style="padding: 8px 10px; font-size: 12px; color: #475569; text-align: right; width: 85px;">Est. Harga</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ' . $itemsHtml . '
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' ' . getCompanyProfile()['nama'] . '. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pemberitahuan otomatis dari Sistem Pengadaan &amp; Pembelian ' . getCompanyProfile()['nama'] . '.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}

if (!function_exists('renderRoStatusEmailTemplate')) {
    /**
     * Template Email HTML Elegan untuk Pemberitahuan Status Persetujuan RO kepada Pemohon
     */
    function renderRoStatusEmailTemplate($roData, $status, $approverName, $keterangan = '', $extraData = []) {
        $nomorRo = htmlspecialchars($roData['nomor'] ?? '-');
        $namaPemohon = htmlspecialchars($roData['nama_pemohon'] ?? 'Karyawan');
        $statusUpper = strtoupper(trim($status));
        $isApproved = (strpos($statusUpper, 'DISETUJUI') !== false && strpos($statusUpper, 'TIDAK') === false);
        $loginUrl = defined('BASE_URL') ? BASE_URL . '/admin/pages/request_order/index.php' : 'http://localhost/JT_Purchase/admin/pages/request_order/index.php';

        // Tentukan Banner, Judul, dan Pesan berdasarkan status spesifik
        if ($statusUpper === 'DISETUJUI PURCHASING') {
            $bannerBg = '#ecfdf5';
            $bannerBorder = '#a7f3d0';
            $bannerTextColor = '#065f46';
            $statusTitle = '🎉 REQUEST ORDER TELAH DITERBITKAN MENJADI PO';
            $statusText = 'DISETUJUI OLEH PURCHASING (PO RESMI TERBIT)';
            $roleLabel = 'Purchasing / Pengadaan';
            $introMsg = 'Kabar baik! Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah disetujui oleh tim Purchasing dan <strong>Purchase Order (PO)</strong> resmi telah diterbitkan.';
        } elseif ($statusUpper === 'TIDAK DISETUJUI PURCHASING') {
            $bannerBg = '#fef2f2';
            $bannerBorder = '#fecaca';
            $bannerTextColor = '#991b1b';
            $statusTitle = '❌ REQUEST ORDER TIDAK DISETUJUI PURCHASING';
            $statusText = 'TIDAK DISETUJUI / DITOLAK PURCHASING';
            $roleLabel = 'Purchasing / Pengadaan';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau oleh tim Purchasing dan dinyatakan <strong>tidak disetujui / ditolak</strong>.';
        } elseif ($statusUpper === 'DISETUJUI LOGISTIK') {
            $bannerBg = '#dcfce7';
            $bannerBorder = '#bbf7d0';
            $bannerTextColor = '#166534';
            $statusTitle = '✅ REQUEST ORDER TELAH DISETUJUI LOGISTIK';
            $statusText = 'DISETUJUI OLEH LOGISTIK (SIAP PROSES PO)';
            $roleLabel = 'Logistik & Gudang';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau dan <strong>disetujui oleh Logistik</strong>. Dokumen ini diteruskan ke tim Purchasing untuk penerbitan Purchase Order.';
        } elseif ($statusUpper === 'TIDAK DISETUJUI LOGISTIK') {
            $bannerBg = '#fee2e2';
            $bannerBorder = '#fecaca';
            $bannerTextColor = '#991b1b';
            $statusTitle = '❌ REQUEST ORDER TIDAK DISETUJUI LOGISTIK';
            $statusText = 'TIDAK DISETUJUI / DITOLAK LOGISTIK';
            $roleLabel = 'Logistik & Gudang';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau oleh tim Logistik dan dinyatakan <strong>tidak disetujui / ditolak</strong>.';
        } else {
            $bannerBg = $isApproved ? '#dcfce7' : '#fee2e2';
            $bannerBorder = $isApproved ? '#bbf7d0' : '#fecaca';
            $bannerTextColor = $isApproved ? '#166534' : '#991b1b';
            $statusTitle = $isApproved ? '✅ REQUEST ORDER TELAH DISETUJUI' : '❌ REQUEST ORDER TIDAK DISETUJUI';
            $statusText = htmlspecialchars($statusUpper);
            $roleLabel = 'Pihak Berwenang';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau dengan status terbaru: <strong>' . htmlspecialchars($statusUpper) . '</strong>.';
        }

        // Info Tambahan PO & Vendor jika ada
        $nomorPo = htmlspecialchars($extraData['nomor_po'] ?? ($roData['nomor_po'] ?? ''));
        $namaVendor = htmlspecialchars($extraData['nama_vendor'] ?? ($roData['nama_vendor'] ?? ''));
        $poRowHtml = '';
        if (!empty($nomorPo)) {
            $poRowHtml .= '
            <tr>
                <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Nomor PO Terbit</td>
                <td style="padding: 10px 14px; font-weight: 700; color: #1d4ed8; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorPo . '</td>
            </tr>';
        }
        if (!empty($namaVendor)) {
            $poRowHtml .= '
            <tr>
                <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Vendor Rekanan</td>
                <td style="padding: 10px 14px; font-weight: 600; color: #1e293b; border-bottom: 1px solid #e2e8f0;">' . $namaVendor . '</td>
            </tr>';
        }

        // Row Catatan/Alasan jika ada
        $catatanRowHtml = '';
        if (!empty($keterangan)) {
            $catatanRowHtml = '
            <tr>
                <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Catatan / Alasan</td>
                <td style="padding: 10px 14px; color: #334155; font-style: italic; border-bottom: 1px solid #e2e8f0;">' . nl2br(htmlspecialchars($keterangan)) . '</td>
            </tr>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pembaruan Status Request Order - ' . $nomorRo . '</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 30px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 28px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">' . strtoupper(getCompanyProfile()['nama']) . '</h1>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;">Pembaruan Status Request Order</p>
                                </td>
                            </tr>

                            <!-- Status Badge Banner -->
                            <tr>
                                <td style="background-color: ' . $bannerBg . '; padding: 14px 24px; border-bottom: 1px solid ' . $bannerBorder . ';">
                                    <span style="font-size: 13px; font-weight: 700; color: ' . $bannerTextColor . ';">
                                        ' . $statusTitle . '
                                    </span>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 28px 24px; color: #1e293b;">
                                    <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                        Halo <strong>' . $namaPemohon . '</strong>,
                                    </p>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        ' . $introMsg . '
                                    </p>

                                    <!-- Summary Card -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 13px;">
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; width: 35%; border-bottom: 1px solid #e2e8f0;">Nomor RO</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: #0f2744; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Status Terbaru</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: ' . $bannerTextColor . '; border-bottom: 1px solid #e2e8f0;">' . $statusText . '</td>
                                        </tr>
                                        ' . $poRowHtml . '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Ditinjau Oleh</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($approverName) . ' <span style="font-weight: normal; color: #64748b;">(' . $roleLabel . ')</span></td>
                                        </tr>
                                        ' . $catatanRowHtml . '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b;">Waktu Pembaruan</td>
                                            <td style="padding: 10px 14px; color: #1e293b;">' . date('d/m/Y H:i') . '</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' ' . getCompanyProfile()['nama'] . '. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pemberitahuan otomatis dari Sistem Pengadaan &amp; Pembelian ' . getCompanyProfile()['nama'] . '.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}

if (!function_exists('sendRoApprovalNotification')) {
    /**
     * Mengirim notifikasi email Request Order baru kepada seluruh Approver aktif
     * 
     * @param mysqli $conn
     * @param int $idRequest
     * @return array [success => bool, sent_count => int, message => string]
     */
    function sendRoApprovalNotification($conn, $idRequest) {
        $idRequest = (int)$idRequest;
        if ($idRequest <= 0) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'ID Request Order tidak valid.'];
        }

        // 1. Ambil data Header RO & Pemohon beserta Vendor Rekanan Tujuan
        $sqlRo = "SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.prioritas, ro.keterangan, ro.status, ro.id_vendor,
                         s.nama_site, 
                         v.nama_perusahaan AS nama_vendor, v.kode_vendor,
                         k.nama_karyawan, k.email as email_pemohon, j.nama_jabatan, d.nama_divisi
                  FROM request_order ro
                  LEFT JOIN site s ON ro.id_site = s.id_site
                  LEFT JOIN vendor v ON ro.id_vendor = v.id_vendor
                  LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                  LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                  LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                  WHERE ro.id_request = ? LIMIT 1";
        $stmt = $conn->prepare($sqlRo);
        $stmt->bind_param("i", $idRequest);
        $stmt->execute();
        $resRo = $stmt->get_result();

        if (!$resRo || $resRo->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'sent_count' => 0, 'message' => 'Data Request Order tidak ditemukan.'];
        }
        $roData = $resRo->fetch_assoc();
        $stmt->close();

        // 2. Ambil Rincian Item Barang beserta Kategori dan Merk
        $sqlItems = "SELECT rod.id_request_detail, rod.id_barang, rod.kode_barang, rod.nama_barang, rod.qty, rod.satuan, rod.harga, rod.subtotal,
                            COALESCE(k.nama_kategori, 'Umum') AS nama_kategori,
                            COALESCE(m.nama_merk, 'Umum') AS nama_merk
                     FROM request_order_detail rod
                     LEFT JOIN barang b ON rod.id_barang = b.id_barang
                     LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori
                     LEFT JOIN merk_barang m ON b.id_merk = m.id_merk
                     WHERE rod.id_request = ? 
                     ORDER BY rod.id_request_detail ASC";
        $stmtIt = $conn->prepare($sqlItems);
        $stmtIt->bind_param("i", $idRequest);
        $stmtIt->execute();
        $resIt = $stmtIt->get_result();

        $items = [];
        while ($it = $resIt->fetch_assoc()) {
            $items[] = $it;
        }
        $stmtIt->close();

        // 3. Render HTML Email
        $pemohonData = [
            'nama_karyawan' => $roData['nama_karyawan'],
            'nama_jabatan'  => $roData['nama_jabatan'],
            'nama_divisi'   => $roData['nama_divisi'],
            'email'         => $roData['email_pemohon']
        ];
        $htmlBody = renderNewRoEmailTemplate($roData, $items, $pemohonData);
        $prioritasTag = ($roData['prioritas'] === 'URGENT') ? '[URGENT] ' : '';
        $subject = $prioritasTag . "Permintaan Persetujuan Request Order: " . $roData['nomor'] . " - " . ($roData['nama_karyawan'] ?? 'Pemohon');

        // 4. Ambil Daftar Approver
        $approvers = getRoApproverEmails($conn);
        if (empty($approvers)) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'Tidak ada email Approver aktif yang terdaftar di sistem.'];
        }

        $sentCount = 0;
        $errors = [];
        foreach ($approvers as $appr) {
            $resSend = sendSmtpEmail($conn, $appr['email'], $appr['nama'], $subject, $htmlBody);
            if (!empty($resSend['success'])) {
                $sentCount++;
            } else {
                $errors[] = $appr['email'] . ': ' . ($resSend['message'] ?? 'Gagal');
            }
        }

        return [
            'success' => ($sentCount > 0),
            'sent_count' => $sentCount,
            'message' => "Notifikasi RO berhasil dikirim ke {$sentCount} Approver." . (!empty($errors) ? ' Errors: ' . implode('; ', $errors) : '')
        ];
    }
}

if (!function_exists('renderRoApprovedForPurchasingTemplate')) {
    /**
     * Template Email HTML untuk Tim Purchasing saat RO Disetujui Logistik (Siap Buat PO)
     */
    function renderRoApprovedForPurchasingTemplate($roData, $items, $approverName, $keterangan = '') {
        $nomorRo = htmlspecialchars($roData['nomor'] ?? '-');
        $namaPemohon = htmlspecialchars($roData['nama_pemohon'] ?? 'Karyawan');
        $divisiPemohon = htmlspecialchars($roData['nama_divisi'] ?? 'Operasional');
        $prioritas = htmlspecialchars($roData['prioritas'] ?? 'NORMAL');
        $namaSite = htmlspecialchars($roData['nama_site'] ?? 'Pusat');
        $idRequest = (int)($roData['id_request'] ?? 0);
        $prosesPoUrl = defined('BASE_URL') 
            ? BASE_URL . '/admin/pages/request_order/proses_po.php?id=' . $idRequest 
            : 'http://localhost/JT_Purchase/admin/pages/request_order/proses_po.php?id=' . $idRequest;

        $badgePrioritas = ($prioritas === 'URGENT')
            ? '<span style="background-color: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">URGENT</span>'
            : '<span style="background-color: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">NORMAL</span>';

        // Render tabel items
        $itemsHtml = '';
        $no = 1;
        foreach ($items as $item) {
            $namaBarang = htmlspecialchars($item['nama_barang'] ?? '-');
            $qty = (float)($item['qty'] ?? 0);
            $satuan = htmlspecialchars($item['satuan'] ?? 'pcs');
            
            $bgRow = ($no % 2 === 0) ? '#f8fafc' : '#ffffff';
            $itemsHtml .= '
            <tr style="background-color: ' . $bgRow . ';">
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 12px;">' . $no++ . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #1e293b;">' . $namaBarang . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-size: 13px; font-weight: 700; color: #0f2744; font-family: monospace;">' . number_format($qty, 0, ',', '.') . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">' . $satuan . '</td>
            </tr>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>RO Siap Diproses Menjadi PO - ' . $nomorRo . '</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 30px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 28px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">' . strtoupper(getCompanyProfile()['nama']) . '</h1>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;">Pemberitahuan Purchasing &amp; Pengadaan</p>
                                </td>
                            </tr>

                            <!-- Status Badge Banner -->
                            <tr>
                                <td style="background-color: #dcfce7; padding: 14px 24px; border-bottom: 1px solid #bbf7d0;">
                                    <span style="font-size: 13px; font-weight: 700; color: #166534;">
                                        ⚡ RO TELAH DISETUJUI LOGISTIK &bull; SIAP DIPROSES MENJADI PO
                                    </span>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 28px 24px; color: #1e293b;">
                                    <p style="margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                        Halo <strong>Tim Purchasing</strong>,
                                    </p>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Request Order dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah <strong>disetujui oleh Logistik</strong> dan kini siap untuk diproses ke tahap pembuatan <strong>Purchase Order (PO)</strong>.
                                    </p>

                                    <!-- Summary Card -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 13px;">
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; width: 35%; border-bottom: 1px solid #e2e8f0;">Nomor RO</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: #0f2744; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Pemohon / Divisi</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">' . $namaPemohon . ' (' . $divisiPemohon . ')</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Disetujui Oleh</td>
                                            <td style="padding: 10px 14px; color: #166534; font-weight: 700; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($approverName) . ' (Logistik)</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Prioritas / Site</td>
                                            <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0;">' . $badgePrioritas . ' &bull; ' . $namaSite . '</td>
                                        </tr>
                                        ' . (!empty($keterangan) ? '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Catatan Logistik</td>
                                            <td style="padding: 10px 14px; color: #334155; font-style: italic; border-bottom: 1px solid #e2e8f0;">' . nl2br(htmlspecialchars($keterangan)) . '</td>
                                        </tr>' : '') . '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b;">Waktu Persetujuan</td>
                                            <td style="padding: 10px 14px; color: #1e293b;">' . date('d/m/Y H:i') . '</td>
                                        </tr>
                                    </table>

                                    <!-- Table Item Header -->
                                    <div style="font-size: 13px; font-weight: 700; color: #0f2744; margin-bottom: 8px;">
                                        📦 Rincian Barang yang Diajukan:
                                    </div>
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 25px;">
                                        <thead>
                                            <tr style="background-color: #f1f5f9;">
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; width: 40px; text-align: center;">No</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: left;">Nama Barang</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right; width: 60px;">Qty</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: left; width: 60px;">Satuan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ' . $itemsHtml . '
                                        </tbody>
                                    </table>

                                    <!-- Action Button CTA -->
                                    <div style="text-align: center; margin: 25px 0 10px 0;">
                                        <a href="' . $prosesPoUrl . '" target="_blank" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-size: 14px; font-weight: 700; display: inline-block; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">
                                            ⚡ Proses Menjadi Purchase Order (PO)
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' ' . getCompanyProfile()['nama'] . '. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pemberitahuan otomatis dari Sistem Pengadaan &amp; Pembelian ' . getCompanyProfile()['nama'] . '.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}

if (!function_exists('sendRoReadyForPurchasingNotification')) {
    /**
     * Mengirim notifikasi email kepada Tim Purchasing saat RO telah disetujui Logistik
     * 
     * @param mysqli $conn
     * @param int $idRequest
     * @param string $approverName
     * @param string $keterangan
     * @return array [success => bool, sent_count => int, message => string]
     */
    function sendRoReadyForPurchasingNotification($conn, $idRequest, $approverName, $keterangan = '') {
        $idRequest = (int)$idRequest;
        if ($idRequest <= 0) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'ID Request Order tidak valid.'];
        }

        // 1. Ambil Data Header RO & Pemohon
        $sqlRo = "SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.prioritas, ro.keterangan, ro.status,
                         s.nama_site, 
                         k.nama_karyawan as nama_pemohon, k.email as email_pemohon, j.nama_jabatan, d.nama_divisi
                  FROM request_order ro
                  LEFT JOIN site s ON ro.id_site = s.id_site
                  LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                  LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                  LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                  WHERE ro.id_request = ? LIMIT 1";
        $stmt = $conn->prepare($sqlRo);
        $stmt->bind_param("i", $idRequest);
        $stmt->execute();
        $resRo = $stmt->get_result();

        if (!$resRo || $resRo->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'sent_count' => 0, 'message' => 'Data Request Order tidak ditemukan.'];
        }
        $roData = $resRo->fetch_assoc();
        $stmt->close();

        // 2. Ambil Rincian Item Barang
        $sqlItems = "SELECT id_request_detail, nama_barang, qty, satuan, harga, subtotal 
                     FROM request_order_detail 
                     WHERE id_request = ? ORDER BY id_request_detail ASC";
        $stmtIt = $conn->prepare($sqlItems);
        $stmtIt->bind_param("i", $idRequest);
        $stmtIt->execute();
        $resIt = $stmtIt->get_result();

        $items = [];
        while ($it = $resIt->fetch_assoc()) {
            $items[] = $it;
        }
        $stmtIt->close();

        // 3. Render HTML Body
        $htmlBody = renderRoApprovedForPurchasingTemplate($roData, $items, $approverName, $keterangan);
        $subject = "[SIAP PROSES PO] Request Order Disetujui Logistik: " . $roData['nomor'] . " - " . ($roData['nama_pemohon'] ?? 'Pemohon');

        // 4. Ambil Daftar Email Purchasing
        $purchasingList = getPurchasingEmails($conn);
        if (empty($purchasingList)) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'Tidak ada email tim Purchasing aktif yang terdaftar.'];
        }

        $sentCount = 0;
        $errors = [];
        foreach ($purchasingList as $purch) {
            $resSend = sendSmtpEmail($conn, $purch['email'], $purch['nama'], $subject, $htmlBody);
            if (!empty($resSend['success'])) {
                $sentCount++;
            } else {
                $errors[] = $purch['email'] . ': ' . ($resSend['message'] ?? 'Gagal');
            }
        }

        return [
            'success' => ($sentCount > 0),
            'sent_count' => $sentCount,
            'message' => "Notifikasi siap PO berhasil dikirim ke {$sentCount} tim Purchasing." . (!empty($errors) ? ' Errors: ' . implode('; ', $errors) : '')
        ];
    }
}

if (!function_exists('sendRoStatusNotification')) {
    /**
     * Mengirim notifikasi status persetujuan RO kepada Karyawan Pemohon dan Tim Purchasing (jika relevan)
     * 
     * @param mysqli $conn
     * @param int $idRequest
     * @param string $status
     * @param string $approverName
     * @param string $keterangan
     * @param array $extraData
     * @return array [success => bool, message => string]
     */
    function sendRoStatusNotification($conn, $idRequest, $status, $approverName, $keterangan = '', $extraData = []) {
        $idRequest = (int)$idRequest;
        if ($idRequest <= 0) {
            return ['success' => false, 'message' => 'ID Request Order tidak valid.'];
        }

        // 1. Ambil Data RO & Pemohon beserta Approver, relasi PO & Vendor jika ada
        $sqlRo = "SELECT ro.id_request, ro.nomor, ro.status, ro.id_po, ro.id_vendor, ro.id_karyawan_approved,
                         k.nama_karyawan as nama_pemohon, k.email as email_pemohon,
                         appr.kode_karyawan as approver_kode, appr.nama_karyawan as approver_nama,
                         po.nomor_po, v.nama_perusahaan as nama_vendor
                  FROM request_order ro
                  LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                  LEFT JOIN karyawan appr ON ro.id_karyawan_approved = appr.id_karyawan
                  LEFT JOIN purchase_order po ON ro.id_po = po.id_po
                  LEFT JOIN vendor v ON (ro.id_vendor = v.id_vendor OR po.id_vendor = v.id_vendor)
                  WHERE ro.id_request = ? LIMIT 1";
        $stmt = $conn->prepare($sqlRo);
        $stmt->bind_param("i", $idRequest);
        $stmt->execute();
        $resRo = $stmt->get_result();

        if (!$resRo || $resRo->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Data Request Order tidak ditemukan.'];
        }
        $roData = $resRo->fetch_assoc();
        $stmt->close();

        // Susun nama approver lengkap dengan kode karyawan
        $approverDisplay = $approverName;
        if (!empty($roData['approver_nama'])) {
            if (!empty($roData['approver_kode'])) {
                $approverDisplay = $roData['approver_nama'] . ' (' . $roData['approver_kode'] . ')';
            } else {
                $approverDisplay = $roData['approver_nama'];
            }
        } elseif (!empty($approverName)) {
            $approverDisplay = $approverName;
        }

        // Gabungkan extraData jika ada nomor_po atau vendor yang baru saja dibuat
        if (!empty($extraData) && is_array($extraData)) {
            foreach ($extraData as $k => $v) {
                if (!empty($v)) {
                    $roData[$k] = $v;
                }
            }
        }

        $toEmail = trim($roData['email_pemohon'] ?? '');
        $toName = trim($roData['nama_pemohon'] ?? 'Karyawan');

        $statusUpper = strtoupper(trim($status));
        $emailSentPemohon = false;

        if (!empty($toEmail) && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $htmlBody = renderRoStatusEmailTemplate($roData, $statusUpper, $approverDisplay, $keterangan, $extraData);

            if ($statusUpper === 'DISETUJUI PURCHASING') {
                $poInfo = !empty($roData['nomor_po']) ? " (PO: {$roData['nomor_po']})" : "";
                $poNum = !empty($roData['nomor_po']) ? $roData['nomor_po'] : '-';
                $subject = "[PO: {$poNum}] - " . getCompanyProfile()['nama'];
            } elseif ($statusUpper === 'TIDAK DISETUJUI PURCHASING') {
                $subject = "[DITOLAK PURCHASING] Request Order Tidak Disetujui: {$roData['nomor']}";
            } elseif ($statusUpper === 'DISETUJUI LOGISTIK') {
                $subject = "[DISETUJUI LOGISTIK] Request Order Disetujui: {$roData['nomor']}";
            } elseif ($statusUpper === 'TIDAK DISETUJUI LOGISTIK') {
                $subject = "[DITOLAK LOGISTIK] Request Order Tidak Disetujui: {$roData['nomor']}";
            } else {
                $statusTag = (strpos($statusUpper, 'DISETUJUI') !== false && strpos($statusUpper, 'TIDAK') === false) ? '[DISETUJUI]' : '[DITOLAK]';
                $subject = "{$statusTag} Pembaruan Status Request Order: {$roData['nomor']}";
            }

            $resSend = sendSmtpEmail($conn, $toEmail, $toName, $subject, $htmlBody);
            $emailSentPemohon = !empty($resSend['success']);
        }

        // 2. Jika status adalah DISETUJUI LOGISTIK, kirim email notifikasi ke Tim Purchasing untuk proses PO
        $isLogistikApproved = ($statusUpper === 'DISETUJUI LOGISTIK');
        if ($isLogistikApproved && function_exists('sendRoReadyForPurchasingNotification')) {
            sendRoReadyForPurchasingNotification($conn, $idRequest, $approverName, $keterangan);
        }

        return [
            'success' => $emailSentPemohon || $isLogistikApproved,
            'message' => 'Notifikasi status RO berhasil diproses.'
        ];
    }
}

if (!function_exists('getFinanceEmails')) {
    /**
     * Mengambil daftar email dan nama tim Finance aktif untuk notifikasi jatuh tempo tagihan & pembayaran
     * 
     * @param mysqli $conn
     * @return array Array of ['email' => string, 'nama' => string, 'role' => string]
     */
    function getFinanceEmails($conn) {
        $recipients = [];
        $addedEmails = [];

        // 1. Ambil Karyawan aktif di Divisi Finance / Jabatan Finance
        $sql = "SELECT k.id_karyawan, k.nama_karyawan, k.email, j.nama_jabatan, d.nama_divisi, k.id_divisi, k.id_jabatan
                FROM karyawan k
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                WHERE k.aktif = 1 AND k.email IS NOT NULL AND k.email != ''";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $email = strtolower(trim($row['email']));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || in_array($email, $addedEmails)) {
                    continue;
                }

                $idDiv = (int)($row['id_divisi'] ?? 0);
                $idJab = (int)($row['id_jabatan'] ?? 0);
                $divLower = strtolower($row['nama_divisi'] ?? '');
                $jabLower = strtolower($row['nama_jabatan'] ?? '');

                // Finance & Manajemen
                $isFinance = (
                    $idDiv === 6 ||
                    $idJab === 6 ||
                    $idJab === 7 ||
                    strpos($divLower, 'finance') !== false ||
                    strpos($jabLower, 'finance') !== false ||
                    strpos($divLower, 'keuangan') !== false ||
                    strpos($jabLower, 'keuangan') !== false
                );

                if ($isFinance) {
                    $recipients[] = [
                        'email' => $row['email'],
                        'nama'  => $row['nama_karyawan'],
                        'role'  => $row['nama_jabatan'] ?: 'Finance & Akuntansi'
                    ];
                    $addedEmails[] = $email;
                }
            }
        }

        return $recipients;
    }
}

if (!function_exists('renderDueBillsReminderEmailTemplate')) {
    /**
     * Template Email HTML Elegan untuk Peringatan Tagihan Vendor Mendekati Jatuh Tempo (H-3) & Lewat Jatuh Tempo (Overdue)
     */
    function renderDueBillsReminderEmailTemplate($dueBills, $totalSisaTagihan, $hDays = 3) {
        $totalFaktur = count($dueBills);
        $totalNominalFmt = 'Rp ' . number_format($totalSisaTagihan, 0, ',', '.');
        $tagihanUrl = defined('BASE_URL')
            ? BASE_URL . '/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php'
            : 'http://localhost/JT_Purchase/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php';

        // Hitung breakdown overdue vs mendekati tempo
        $countOverdue = 0;
        $nominalOverdue = 0;
        $countMendekati = 0;
        $nominalMendekati = 0;

        foreach ($dueBills as $b) {
            $sHari = (int)($b['sisa_hari'] ?? 0);
            $sisa = (float)($b['sisa_tagihan'] ?? 0);
            if ($sHari < 0) {
                $countOverdue++;
                $nominalOverdue += $sisa;
            } else {
                $countMendekati++;
                $nominalMendekati += $sisa;
            }
        }

        $rowsHtml = '';
        $no = 1;
        foreach ($dueBills as $bill) {
            $nomorFaktur = htmlspecialchars($bill['nomor_faktur'] ?? '-');
            $nomorFakturVendor = !empty($bill['nomor_faktur_vendor']) ? htmlspecialchars($bill['nomor_faktur_vendor']) : '-';
            $namaVendor = htmlspecialchars($bill['nama_vendor'] ?? '-');
            $namaBank = htmlspecialchars($bill['nama_bank'] ?? 'Bank Transfer');
            $rek = !empty($bill['nomor_rekening']) ? htmlspecialchars($bill['nomor_rekening']) : '';
            $tglJatuhTempo = !empty($bill['tanggal_jatuh_tempo']) ? date('d/m/Y', strtotime($bill['tanggal_jatuh_tempo'])) : '-';
            $sisaHari = (int)($bill['sisa_hari'] ?? 0);
            $sisaTagihan = (float)($bill['sisa_tagihan'] ?? 0);
            $sisaTagihanFmt = 'Rp ' . number_format($sisaTagihan, 0, ',', '.');

            // Badge status sisa hari
            if ($sisaHari < 0) {
                $hariLabel = '🚨 Lewat ' . abs($sisaHari) . ' Hari (Overdue)';
                $badgeBg = '#fee2e2';
                $badgeColor = '#991b1b';
                $rowBorder = 'border-left: 3px solid #dc2626;';
            } elseif ($sisaHari === 0) {
                $hariLabel = '⚠️ Hari Ini (H-0)';
                $badgeBg = '#fee2e2';
                $badgeColor = '#991b1b';
                $rowBorder = 'border-left: 3px solid #ea580c;';
            } elseif ($sisaHari === 1) {
                $hariLabel = '⏱️ Besok (H-1)';
                $badgeBg = '#fef3c7';
                $badgeColor = '#92400e';
                $rowBorder = 'border-left: 3px solid #d97706;';
            } else {
                $hariLabel = '⏱️ ' . $sisaHari . ' Hari Lagi (H-' . $sisaHari . ')';
                $badgeBg = '#fef3c7';
                $badgeColor = '#92400e';
                $rowBorder = 'border-left: 3px solid #eab308;';
            }

            $bgRow = ($no % 2 === 0) ? '#f8fafc' : '#ffffff';
            $rowsHtml .= '
            <tr style="background-color: ' . $bgRow . '; ' . $rowBorder . '">
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 12px;">' . $no++ . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px;">
                    <div style="font-weight: 700; color: #0f2744; font-family: monospace;">' . $nomorFaktur . '</div>
                    <div style="font-size: 11px; color: #64748b;">Vendor: ' . $nomorFakturVendor . '</div>
                </td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px;">
                    <div style="font-weight: 600; color: #1e293b;">' . $namaVendor . '</div>
                    <div style="font-size: 11px; color: #64748b;">' . $namaBank . ($rek ? ' - ' . $rek : '') . '</div>
                </td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-align: center;">
                    <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">' . $tglJatuhTempo . '</div>
                    <span style="background-color: ' . $badgeBg . '; color: ' . $badgeColor . '; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; display: inline-block;">
                        ' . $hariLabel . '
                    </span>
                </td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-size: 13px; font-weight: 700; color: #0284c7; font-family: monospace;">
                    ' . $sisaTagihanFmt . '
                </td>
            </tr>';
        }

        // Tentukan Banner Utama
        if ($countOverdue > 0 && $countMendekati > 0) {
            $bannerBg = '#fef2f2';
            $bannerBorder = '#fecaca';
            $bannerText = '⚠️ PERINGATAN KEUANGAN: TAGIHAN MENDEKATI TEMPO (H-' . $hDays . ') &amp; LEWAT JATUH TEMPO';
            $bannerTextColor = '#991b1b';
        } elseif ($countOverdue > 0) {
            $bannerBg = '#fef2f2';
            $bannerBorder = '#fecaca';
            $bannerText = '🚨 PERINGATAN KRITIS: TAGIHAN VENDOR TELAH LEWAT JATUH TEMPO';
            $bannerTextColor = '#991b1b';
        } else {
            $bannerBg = '#fef3c7';
            $bannerBorder = '#fde68a';
            $bannerText = '⚠️ PERINGATAN JATUH TEMPO: TAGIHAN VENDOR MENDEKATI TEMPO (H-' . $hDays . ')';
            $bannerTextColor = '#92400e';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Peringatan Jatuh Tempo Tagihan Vendor</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 30px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 670px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 28px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">' . strtoupper(getCompanyProfile()['nama']) . '</h1>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;">Pemberitahuan Jadwal Arus Kas &amp; Pembayaran Vendor</p>
                                </td>
                            </tr>

                            <!-- Status Warning Banner -->
                            <tr>
                                <td style="background-color: ' . $bannerBg . '; padding: 14px 24px; border-bottom: 1px solid ' . $bannerBorder . ';">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td style="font-size: 13px; font-weight: 700; color: ' . $bannerTextColor . ';">
                                                ' . $bannerText . '
                                            </td>
                                            <td style="text-align: right; font-size: 12px; font-weight: 700; color: ' . $bannerTextColor . ';">
                                                ' . date('d F Y') . '
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 28px 24px; color: #1e293b;">
                                    <p style="margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                        Yth. <strong>Tim Finance &amp; Manajemen</strong>,
                                    </p>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Sistem mendeteksi terdapat <strong>' . $totalFaktur . ' tagihan faktur vendor</strong> yang <strong>mendekati jatuh tempo (&le; ' . $hDays . ' hari)</strong> maupun yang <strong>telah melewati jatuh tempo</strong>. Mohon segera tinjau dan siapkan realisasi pembayaran:
                                    </p>

                                    <!-- Summary Stat Boxes (Breakdown) -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
                                        <tr>
                                            <td style="width: 50%; padding-right: 8px;">
                                                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; text-align: center;">
                                                    <div style="font-size: 11px; text-transform: uppercase; color: #991b1b; font-weight: 700; margin-bottom: 4px;">🚨 Lewat Jatuh Tempo</div>
                                                    <div style="font-size: 18px; font-weight: 800; color: #991b1b; font-family: monospace;">' . $countOverdue . ' Faktur</div>
                                                    <div style="font-size: 12px; color: #b91c1c; font-weight: 600;">Rp ' . number_format($nominalOverdue, 0, ',', '.') . '</div>
                                                </div>
                                            </td>
                                            <td style="width: 50%; padding-left: 8px;">
                                                <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px; text-align: center;">
                                                    <div style="font-size: 11px; text-transform: uppercase; color: #92400e; font-weight: 700; margin-bottom: 4px;">⏱️ Mendekati Tempo (H-' . $hDays . ')</div>
                                                    <div style="font-size: 18px; font-weight: 800; color: #92400e; font-family: monospace;">' . $countMendekati . ' Faktur</div>
                                                    <div style="font-size: 12px; color: #b45309; font-weight: 600;">Rp ' . number_format($nominalMendekati, 0, ',', '.') . '</div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Table Daftar Tagihan -->
                                    <div style="font-size: 13px; font-weight: 700; color: #0f2744; margin-bottom: 8px;">
                                        📋 Rincian Tagihan Vendor:
                                    </div>
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 25px;">
                                        <thead>
                                            <tr style="background-color: #f1f5f9;">
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; width: 35px; text-align: center;">No</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: left;">No. Faktur</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: left;">Vendor &amp; Bank</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: center; width: 140px;">Jatuh Tempo</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right; width: 130px;">Sisa Tagihan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ' . $rowsHtml . '
                                        </tbody>
                                        <tfoot>
                                            <tr style="background-color: #f8fafc; font-weight: 700;">
                                                <td colspan="4" style="padding: 12px; text-align: right; font-size: 12px; color: #475569; text-transform: uppercase; border-top: 2px solid #e2e8f0;">
                                                    Total Keseluruhan Kewajiban:
                                                </td>
                                                <td style="padding: 12px; text-align: right; font-size: 14px; color: #0f2744; font-family: monospace; border-top: 2px solid #e2e8f0;">
                                                    ' . $totalNominalFmt . '
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <!-- Action Button CTA -->
                                    <div style="text-align: center; margin: 25px 0 10px 0;">
                                        <a href="' . $tagihanUrl . '" target="_blank" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-size: 14px; font-weight: 700; display: inline-block; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);">
                                            💳 Buka Monitoring Tagihan &amp; Catat Pembayaran
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' ' . getCompanyProfile()['nama'] . '. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pemberitahuan otomatis dari Sistem Keuangan &amp; Pembelian ' . getCompanyProfile()['nama'] . '.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}

if (!function_exists('sendDueBillsReminderNotification')) {
    /**
     * Mengirim notifikasi email pengingat tagihan vendor mendekati jatuh tempo (H-3) & lewat jatuh tempo (overdue) ke Tim Finance
     * 
     * @param mysqli $conn
     * @param int $hDays Batas sisa hari (default: 3 hari)
     * @return array [success => bool, due_count => int, sent_count => int, message => string, data => array]
     */
    function sendDueBillsReminderNotification($conn, $hDays = 3) {
        $hDays = (int)$hDays;
        if ($hDays < 0) $hDays = 3;

        // 1. Ambil daftar faktur yang belum lunas dan mendekati jatuh tempo (sisa_hari <= hDays) atau telah lewat tempo
        $sql = "SELECT fp.id_faktur, fp.nomor_faktur, fp.nomor_faktur_vendor, fp.tanggal_jatuh_tempo,
                       DATEDIFF(fp.tanggal_jatuh_tempo, CURDATE()) AS sisa_hari,
                       v.id_vendor, v.nama_perusahaan as nama_vendor,
                       fp.nama_bank, fp.nomor_rekening, fp.atas_nama_rekening,
                       fp.total_tagihan, fp.terbayar, fp.sisa_tagihan, fp.status
                FROM faktur_po fp
                INNER JOIN vendor v ON fp.id_vendor = v.id_vendor
                WHERE fp.sisa_tagihan > 0 
                  AND fp.status NOT IN ('LUNAS', 'BATAL')
                  AND DATEDIFF(fp.tanggal_jatuh_tempo, CURDATE()) <= ?
                ORDER BY fp.tanggal_jatuh_tempo ASC, fp.id_faktur ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $hDays);
        $stmt->execute();
        $res = $stmt->get_result();

        $dueBills = [];
        $totalSisaTagihan = 0;
        $countOverdue = 0;
        $countMendekati = 0;

        while ($row = $res->fetch_assoc()) {
            $dueBills[] = $row;
            $sisa = (float)$row['sisa_tagihan'];
            $totalSisaTagihan += $sisa;
            if ((int)$row['sisa_hari'] < 0) {
                $countOverdue++;
            } else {
                $countMendekati++;
            }
        }
        $stmt->close();

        if (empty($dueBills)) {
            return [
                'success' => true,
                'due_count' => 0,
                'overdue_count' => 0,
                'approaching_count' => 0,
                'sent_count' => 0,
                'total_sisa_tagihan' => 0,
                'message' => "Tidak ada tagihan vendor yang mendekati jatuh tempo (H-{$hDays}) ataupun yang lewat jatuh tempo saat ini."
            ];
        }

        // 2. Ambil daftar email Tim Finance
        $financeList = getFinanceEmails($conn);
        if (empty($financeList)) {
            return [
                'success' => false,
                'due_count' => count($dueBills),
                'overdue_count' => $countOverdue,
                'approaching_count' => $countMendekati,
                'sent_count' => 0,
                'total_sisa_tagihan' => $totalSisaTagihan,
                'message' => 'Ditemukan ' . count($dueBills) . ' tagihan jatuh tempo, namun tidak ada email tim Finance aktif yang terdaftar.'
            ];
        }

        // 3. Render HTML Email
        $htmlBody = renderDueBillsReminderEmailTemplate($dueBills, $totalSisaTagihan, $hDays);
        $totalCount = count($dueBills);

        // Subject Dinamis
        if ($countOverdue > 0 && $countMendekati > 0) {
            $subject = "⚠️ [PENGINGAT KEUANGAN] {$totalCount} Tagihan: {$countOverdue} Overdue & {$countMendekati} Mendekati Tempo (H-{$hDays}) - " . getCompanyProfile()['nama'];
        } elseif ($countOverdue > 0) {
            $subject = "🚨 [PERINGATAN OVERDUE] {$countOverdue} Tagihan Vendor Telah Lewat Jatuh Tempo - " . getCompanyProfile()['nama'];
        } else {
            $subject = "⚠️ [PENGINGAT H-{$hDays}] {$countMendekati} Tagihan Vendor Mendekati Jatuh Tempo - " . getCompanyProfile()['nama'];
        }

        // 4. Kirim Email ke setiap anggota Tim Finance
        $sentCount = 0;
        $errors = [];
        foreach ($financeList as $fin) {
            $resSend = sendSmtpEmail($conn, $fin['email'], $fin['nama'], $subject, $htmlBody);
            if (!empty($resSend['success'])) {
                $sentCount++;
            } else {
                $errors[] = $fin['email'] . ': ' . ($resSend['message'] ?? 'Gagal');
            }
        }

        $resultData = [
            'success' => ($sentCount > 0),
            'due_count' => $totalCount,
            'overdue_count' => $countOverdue,
            'approaching_count' => $countMendekati,
            'sent_count' => $sentCount,
            'total_sisa_tagihan' => $totalSisaTagihan,
            'recipients' => array_column($financeList, 'email'),
            'message' => "Peringatan berhasil dikirim ke {$sentCount} personil Tim Finance ({$countMendekati} mendekati tempo H-{$hDays}, {$countOverdue} lewat jatuh tempo)." . (!empty($errors) ? ' Errors: ' . implode('; ', $errors) : '')
        ];

        // Simpan log pengiriman harian untuk mencegah email ganda (anti-spam)
        try {
            $logFile = __DIR__ . '/last_due_bills_reminder.json';
            $logPayload = [
                'last_sent_date'    => date('Y-m-d'),
                'timestamp'         => date('Y-m-d H:i:s'),
                'due_count'         => $totalCount,
                'overdue_count'     => $countOverdue,
                'approaching_count' => $countMendekati,
                'sent_count'        => $sentCount,
                'total_sisa_tagihan'=> $totalSisaTagihan
            ];
            @file_put_contents($logFile, json_encode($logPayload, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {}

        return $resultData;
    }
}

if (!function_exists('sendNotificationEvent')) {
    /**
     * Dispatcher Notifikasi Terpusat untuk backend API
     * 
     * @param mysqli $conn
     * @param string $action 'ro_created' | 'ro_status_update' | 'ro_ready_purchasing' | 'due_bills_reminder' | 'custom_email'
     * @param array $payload
     * @return array [success => bool, message => string]
     */
    function sendNotificationEvent($conn, $action, $payload = []) {
        $action = trim($action);
        $idRequest = isset($payload['id_request']) ? (int)$payload['id_request'] : 0;
        $status = trim($payload['status'] ?? '');
        $actorName = trim($payload['actor_name'] ?? ($payload['approver_name'] ?? 'Petugas'));
        $keterangan = trim($payload['keterangan'] ?? ($payload['alasan'] ?? ($payload['catatan'] ?? '')));
        $extraData = isset($payload['extra_data']) && is_array($payload['extra_data']) ? $payload['extra_data'] : [];
        $hDays = isset($payload['h_days']) ? (int)$payload['h_days'] : 3;

        switch ($action) {
            case 'cancellation':
            case 'pembatalan':
                $idPembatalan = isset($payload['id_pembatalan']) ? (int)$payload['id_pembatalan'] : 0;
                return sendCancellationNotification($conn, $idPembatalan);

            case 'ro_created':
                return sendRoApprovalNotification($conn, $idRequest);

            case 'ro_status_update':
                return sendRoStatusNotification($conn, $idRequest, $status, $actorName, $keterangan, $extraData);

            case 'ro_ready_purchasing':
                return sendRoReadyForPurchasingNotification($conn, $idRequest, $actorName, $keterangan);

            case 'due_bills_reminder':
            case 'tagihan_jatuh_tempo':
                return sendDueBillsReminderNotification($conn, $hDays);

            case 'custom_email':
                $toEmail = trim($payload['to_email'] ?? '');
                $toName = trim($payload['to_name'] ?? 'Penerima');
                $subject = trim($payload['subject'] ?? '');
                $bodyHtml = trim($payload['body_html'] ?? '');
                return sendSmtpEmail($conn, $toEmail, $toName, $subject, $bodyHtml);

            default:
                return ['success' => false, 'message' => "Action '{$action}' tidak dikenal."];
        }
    }
}

if (!function_exists('sendCancellationNotification')) {
    /**
     * Kirim Notifikasi Email Otomatis ke Manager atas Pembatalan Dokumen (PO / Faktur)
     * 
     * @param mysqli $conn
     * @param int $idPembatalan
     * @return array [success => bool, message => string, sent_count => int]
     */
    function sendCancellationNotification($conn, $idPembatalan) {
        $idPembatalan = (int)$idPembatalan;
        if ($idPembatalan <= 0) {
            return ['success' => false, 'message' => 'ID Pembatalan tidak valid.', 'sent_count' => 0];
        }

        // 1. Ambil Data BAP Pembatalan
        $stmt = $conn->prepare("SELECT * FROM pembatalan_transaksi WHERE id_pembatalan = ? LIMIT 1");
        $stmt->bind_param("i", $idPembatalan);
        $stmt->execute();
        $res = $stmt->get_result();
        $bap = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$bap) {
            return ['success' => false, 'message' => 'Data Berita Acara Pembatalan tidak ditemukan.', 'sent_count' => 0];
        }

        // 2. Ambil Daftar Email Manager & Approver
        $managers = [];
        $resMgr = $conn->query("
            SELECT DISTINCT k.nama_karyawan, k.email, j.nama_jabatan 
            FROM karyawan k 
            INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan 
            WHERE (UPPER(j.nama_jabatan) LIKE '%MANAGER%' OR UPPER(j.nama_jabatan) LIKE '%ADMIN%') 
              AND k.email IS NOT NULL AND k.email != ''
        ");
        if ($resMgr) {
            while ($row = $resMgr->fetch_assoc()) {
                if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $managers[] = $row;
                }
            }
        }

        if (empty($managers)) {
            // Fallback cari di tabel users jika ada email
            $resUsr = $conn->query("SELECT username, email FROM users WHERE role IN ('ADMIN', 'MANAGER') AND email IS NOT NULL AND email != ''");
            if ($resUsr) {
                while ($row = $resUsr->fetch_assoc()) {
                    if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                        $managers[] = ['nama_karyawan' => $row['username'], 'email' => $row['email'], 'nama_jabatan' => 'Manager'];
                    }
                }
            }
        }

        if (empty($managers)) {
            return ['success' => false, 'message' => 'Tidak ditemukan akun Manager dengan email aktif untuk menerima notifikasi.', 'sent_count' => 0];
        }

        // 3. Format Template Email
        $nomorBap = htmlspecialchars($bap['nomor_bap']);
        $jenisDoc = htmlspecialchars($bap['jenis_dokumen']);
        $nomorRef = htmlspecialchars($bap['nomor_referensi']);
        $stateTahapan = htmlspecialchars($bap['state_tahapan']);
        $namaVendor = htmlspecialchars($bap['nama_vendor'] ?? '-');
        $nilaiRp = number_format((float)($bap['nilai_transaksi'] ?? 0), 0, ',', '.');
        $kategoriAlasan = htmlspecialchars($bap['kategori_alasan']);
        $alasanDetail = nl2br(htmlspecialchars($bap['alasan_detail']));
        $namaPetugas = htmlspecialchars($bap['nama_karyawan_batal']);
        $tanggalBatal = date('d/m/Y H:i', strtotime($bap['tanggal_batal']));

        $subject = "[ALERT PEMBATALAN] Berita Acara Pembatalan {$jenisDoc}: {$nomorRef} - PT Jaya Teknik";

        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
                .container { max-width: 620px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
                .header { background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); color: #ffffff; padding: 24px 30px; }
                .header h2 { margin: 0 0 6px 0; font-size: 20px; font-weight: 700; letter-spacing: -0.025em; }
                .header p { margin: 0; font-size: 13px; color: #fecaca; }
                .content { padding: 28px 30px; }
                .badge-batal { display: inline-block; background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase; border: 1px solid #fca5a5; margin-bottom: 16px; }
                .table-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
                .table-info td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
                .table-info td.label { width: 38%; color: #64748b; font-weight: 600; }
                .table-info td.value { width: 62%; color: #0f172a; font-weight: 600; }
                .reason-box { background-color: #f8fafc; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; font-size: 13px; color: #334155; }
                .footer { background-color: #f8fafc; padding: 18px 30px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>NOTIFIKASI PEMBATALAN TRANSAKSI</h2>
                    <p>PT Jaya Teknik — Procure-to-Pay Purchasing System</p>
                </div>
                <div class='content'>
                    <div class='badge-batal'>DOKUMEN {$jenisDoc} RESMI DIBATALKAN</div>
                    <p style='font-size: 14px; margin-top: 0; line-height: 1.5;'>
                        Telah diterbitkan dokumen <strong>Berita Acara Pembatalan (BAP)</strong> dengan rincian data sebagai berikut:
                    </p>
                    
                    <table class='table-info'>
                        <tr>
                            <td class='label'>Nomor Dokumen BAP</td>
                            <td class='value' style='color: #b91c1c;'>{$nomorBap}</td>
                        </tr>
                        <tr>
                            <td class='label'>Jenis &amp; Dokumen Referensi</td>
                            <td class='value'>{$jenisDoc} : <strong>{$nomorRef}</strong></td>
                        </tr>
                        <tr>
                            <td class='label'>Tahapan / State saat Batal</td>
                            <td class='value'><span style='background: #e2e8f0; padding: 2px 6px; border-radius: 4px;'>{$stateTahapan}</span></td>
                        </tr>
                        <tr>
                            <td class='label'>Vendor Rekanan</td>
                            <td class='value'>{$namaVendor}</td>
                        </tr>
                        <tr>
                            <td class='label'>Nilai Transaksi Dokumen</td>
                            <td class='value' style='font-family: monospace;'>Rp {$nilaiRp}</td>
                        </tr>
                        <tr>
                            <td class='label'>Kategori Alasan</td>
                            <td class='value'>{$kategoriAlasan}</td>
                        </tr>
                        <tr>
                            <td class='label'>Dibatalkan Oleh</td>
                            <td class='value'>{$namaPetugas}</td>
                        </tr>
                        <tr>
                            <td class='label'>Waktu Pembatalan</td>
                            <td class='value'>{$tanggalBatal}</td>
                        </tr>
                    </table>

                    <div style='font-weight: 600; font-size: 13px; color: #475569; margin-bottom: 6px;'>Kronologis / Alasan Berita Acara:</div>
                    <div class='reason-box'>
                        {$alasanDetail}
                    </div>

                    <p style='font-size: 12px; color: #64748b; line-height: 1.5; margin-bottom: 0;'>
                        Dokumen Berita Acara Pembatalan (BAP) telah diarsipkan ke dalam sistem. Anda dapat melihat atau mencetak ulang lembar BAP resmi melalui menu <strong>Laporan &rarr; Laporan Pembatalan Transaksi</strong>.
                    </p>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " PT Jaya Teknik. Email ini dikirimkan secara otomatis oleh sistem, mohon tidak membalas langsung ke alamat ini.
                </div>
            </div>
        </body>
        </html>
        ";

        // 4. Kirim ke setiap manager
        $sentCount = 0;
        foreach ($managers as $mgr) {
            $resSend = sendSmtpEmail($conn, $mgr['email'], $mgr['nama_karyawan'], $subject, $htmlBody);
            if ($resSend['success']) {
                $sentCount++;
            }
        }

        if ($sentCount > 0) {
            return ['success' => true, 'message' => "Notifikasi pembatalan berhasil dikirim ke {$sentCount} Manager.", 'sent_count' => $sentCount];
        }

        return ['success' => false, 'message' => 'Gagal mengirim email notifikasi ke Manager (Server SMTP offline atau kuota habis).', 'sent_count' => 0];
    }
}

if (!function_exists('sendCancellationNotificationDirect')) {
    /**
     * Kirim Notifikasi Email Otomatis ke Manager atas Pembatalan Dokumen (PO / Faktur) Langsung
     */
    function sendCancellationNotificationDirect($conn, $jenisDoc, $idRef, $nomorRef, $namaVendor, $kategoriAlasan, $alasanDetail, $stateTahapan, $namaPetugas) {
        $managers = [];
        $resMgr = $conn->query("
            SELECT DISTINCT k.nama_karyawan, k.email 
            FROM karyawan k 
            INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan 
            WHERE (UPPER(j.nama_jabatan) LIKE '%MANAGER%' OR UPPER(j.nama_jabatan) LIKE '%ADMIN%') 
              AND k.email IS NOT NULL AND k.email != ''
        ");
        if ($resMgr) {
            while ($row = $resMgr->fetch_assoc()) {
                if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $managers[] = $row;
                }
            }
        }

        if (empty($managers)) {
            $resUsr = $conn->query("SELECT username, email FROM users WHERE role IN ('ADMIN', 'MANAGER') AND email IS NOT NULL AND email != ''");
            if ($resUsr) {
                while ($row = $resUsr->fetch_assoc()) {
                    if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                        $managers[] = ['nama_karyawan' => $row['username'], 'email' => $row['email']];
                    }
                }
            }
        }

        if (empty($managers)) {
            return ['success' => false, 'message' => 'Tidak ada akun email Manager aktif.', 'sent_count' => 0];
        }

        $subject = "[ALERT PEMBATALAN] Pembatalan {$jenisDoc}: {$nomorRef} - PT Jaya Teknik";
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; padding: 20px; color: #1e293b; background: #f8fafc;'>
            <div style='max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden;'>
                <div style='background: #dc2626; color: #fff; padding: 16px 20px;'>
                    <h3 style='margin: 0; font-size: 18px;'>Pemberitahuan Pembatalan {$jenisDoc}</h3>
                </div>
                <div style='padding: 20px;'>
                    <p style='margin-top: 0; font-size: 14px;'>Telah dilakukan pembatalan dokumen pengadaan dengan rincian:</p>
                    <table style='width: 100%; font-size: 13px; border-collapse: collapse;'>
                        <tr><td style='padding: 6px 0; color: #64748b; width: 35%;'>Dokumen Referensi</td><td style='padding: 6px 0;'><strong>{$jenisDoc} - {$nomorRef}</strong></td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>Vendor Rekanan</td><td style='padding: 6px 0;'><strong>" . htmlspecialchars($namaVendor ?? '-') . "</strong></td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>State saat Batal</td><td style='padding: 6px 0;'><span style='background:#f1f5f9; padding: 2px 6px; border-radius: 4px;'>{$stateTahapan}</span></td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>Kategori Alasan</td><td style='padding: 6px 0;'><strong style='color: #dc2626;'>" . htmlspecialchars($kategoriAlasan) . "</strong></td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>Dibatalkan Oleh</td><td style='padding: 6px 0;'>" . htmlspecialchars($namaPetugas) . "</td></tr>
                        <tr><td style='padding: 6px 0; color: #64748b;'>Waktu Pembatalan</td><td style='padding: 6px 0;'>" . date('d/m/Y H:i') . "</td></tr>
                    </table>
                    <div style='margin-top: 15px; padding: 12px; background: #fef2f2; border-left: 4px solid #dc2626; font-size: 13px; color: #334155;'>
                        <strong>Uraian Alasan:</strong><br>" . nl2br(htmlspecialchars($alasanDetail)) . "
                    </div>
                </div>
                <div style='background: #f8fafc; padding: 12px 20px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                    &copy; " . date('Y') . " PT Jaya Teknik System
                </div>
            </div>
        </div>";

        $sent = 0;
        foreach ($managers as $m) {
            $r = sendSmtpEmail($conn, $m['email'], $m['nama_karyawan'], $subject, $htmlBody);
            if ($r['success']) $sent++;
        }

        return ['success' => ($sent > 0), 'sent_count' => $sent, 'message' => "Email terkirim ke {$sent} Manager."];
    }
}

if (!function_exists('renderBackupOtpEmailTemplate')) {
    /**
     * Template Email OTP Verifikasi Keamanan Backup & Restore Database
     */
    function renderBackupOtpEmailTemplate($nama, $otpCode, $action = 'BACKUP', $expiresMinutes = 10) {
        $actionTitle = ($action === 'RESTORE') ? 'Restore Database' : 'Backup Database';
        $actionDesc = ($action === 'RESTORE') 
            ? 'Permintaan otorisasi pemulihan (RESTORE) data database sistem ' . getCompanyProfile()['nama'] . '.'
            : 'Permintaan otorisasi pencadangan (BACKUP) data database sistem ' . getCompanyProfile()['nama'] . '.';
        $actionColor = ($action === 'RESTORE') ? '#dc2626' : '#0f2744';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Kode OTP Keamanan {$actionTitle}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif;'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='padding: 30px 10px;'>
                <tr>
                    <td align='center'>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 540px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                            <tr>
                                <td style='background: {$actionColor}; padding: 24px 20px; color: #ffffff; text-align: center;'>
                                    <h2 style='margin: 0; font-size: 20px; letter-spacing: 0.5px;'>' . strtoupper(getCompanyProfile()['nama']) . '</h2>
                                    <p style='margin: 5px 0 0 0; font-size: 12px; opacity: 0.85;'>Verifikasi Keamanan {$actionTitle}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 25px 24px; color: #1e293b; font-size: 14px; line-height: 1.6;'>
                                    <p style='margin-top: 0;'>Halo <strong>" . htmlspecialchars($nama) . "</strong>,</p>
                                    <p>{$actionDesc}</p>
                                    <p style='margin-bottom: 8px;'>Gunakan kode verifikasi (OTP) berikut untuk melanjutkan transaksi:</p>
                                    
                                    <div style='text-align: center; margin: 20px 0;'>
                                        <div style='display: inline-block; background: #f8fafc; border: 2px dashed {$actionColor}; padding: 12px 30px; border-radius: 8px;'>
                                            <span style='font-size: 30px; font-weight: 800; font-family: monospace; letter-spacing: 8px; color: {$actionColor};'>{$otpCode}</span>
                                        </div>
                                    </div>

                                    <p style='font-size: 12px; color: #64748b; text-align: center;'>Kode OTP ini berlaku selama <strong>{$expiresMinutes} menit</strong>. Jangan berikan kode ini kepada siapapun.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='background: #f8fafc; padding: 12px 20px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                                    &copy; " . date('Y') . " ' . getCompanyProfile()['nama'] . ' System &bull; Keamanan Database
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}

if (!function_exists('renderBackupFileEmailTemplate')) {
    /**
     * Template Email Pengiriman Berkas Backup Database (.sql)
     */
    function renderBackupFileEmailTemplate($nama, $fileName, $fileSize, $scopeText, $keterangan, $tanggal) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Berkas Cadangan Database</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif;'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%' style='padding: 30px 10px;'>
                <tr>
                    <td align='center'>
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style='max-width: 580px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                            <tr>
                                <td style='background: #0f2744; padding: 24px 20px; color: #ffffff; text-align: center;'>
                                    <h2 style='margin: 0; font-size: 20px; letter-spacing: 0.5px;'>' . strtoupper(getCompanyProfile()['nama']) . '</h2>
                                    <p style='margin: 5px 0 0 0; font-size: 12px; opacity: 0.85;'>Arsip Cadangan Database (Backup .SQL)</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 25px 24px; color: #1e293b; font-size: 14px; line-height: 1.6;'>
                                    <p style='margin-top: 0;'>Halo <strong>" . htmlspecialchars($nama) . "</strong>,</p>
                                    <p>Proses pencadangan database sistem ' . getCompanyProfile()['nama'] . ' telah berhasil dieksekusi. Berkas SQL terlampir pada email ini.</p>
                                    
                                    <table style='width: 100%; font-size: 13px; border-collapse: collapse; margin: 15px 0; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;'>
                                        <tr>
                                            <td style='padding: 10px 14px; color: #64748b; width: 35%; border-bottom: 1px solid #e2e8f0;'>Nama Berkas</td>
                                            <td style='padding: 10px 14px; font-weight: bold; font-family: monospace; border-bottom: 1px solid #e2e8f0; color: #0284c7;'>" . htmlspecialchars($fileName) . "</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;'>Ukuran Berkas</td>
                                            <td style='padding: 10px 14px; font-weight: bold; border-bottom: 1px solid #e2e8f0;'>" . htmlspecialchars($fileSize) . "</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;'>Cakupan (Scope)</td>
                                            <td style='padding: 10px 14px; border-bottom: 1px solid #e2e8f0;'>" . htmlspecialchars($scopeText) . "</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;'>Waktu Backup</td>
                                            <td style='padding: 10px 14px; border-bottom: 1px solid #e2e8f0;'>" . htmlspecialchars($tanggal) . "</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 10px 14px; color: #64748b;'>Keterangan</td>
                                            <td style='padding: 10px 14px;'>" . htmlspecialchars($keterangan ?: 'Pencadangan berkala database.') . "</td>
                                        </tr>
                                    </table>

                                    <div style='background: #ecfdf5; border-left: 4px solid #10b981; padding: 10px 14px; font-size: 12px; color: #065f46;'>
                                        <strong>Informasi Keamanan:</strong> Simpan berkas ini di tempat yang aman. Berkas dapat digunakan untuk Quick Restore melalui panel admin ' . getCompanyProfile()['nama'] . '.
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td style='background: #f8fafc; padding: 12px 20px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                                    &copy; " . date('Y') . " ' . getCompanyProfile()['nama'] . ' System &bull; Database Backup Service
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}



