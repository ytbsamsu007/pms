<?php
/**
 * Core Activity Logger & Audit Trail Helper - PT Jaya Teknis
 * Path: config/activity_logger.php
 */

if (!function_exists('logActivity')) {
    /**
     * Mencatat riwayat aktivitas pengguna ke tabel activity_log
     * 
     * @param mysqli $conn Koneksi database aktif
     * @param array $params [
     *    'modul'           => string (REQUEST_ORDER, PURCHASE_ORDER, RECEIVING, RETUR_PO, FAKTUR, PEMBAYARAN, MASTER_DATA, AUTH),
     *    'aksi'            => string (CREATE, UPDATE, DELETE, APPROVE, REJECT, BATAL, PELUNASAN, etc),
     *    'id_referensi'    => int|null,
     *    'nomor_referensi' => string|null,
     *    'deskripsi'       => string,
     *    'data_sebelumnya' => array|string|null,
     *    'data_sesudahnya' => array|string|null,
     *    'id_karyawan'     => int|null,
     *    'nama_pengguna'   => string|null,
     *    'role'            => string|null
     * ]
     * @return bool True jika berhasil tercatat, False jika gagal
     */
    function logActivity($conn, array $params = []): bool {
        if (!$conn || !($conn instanceof mysqli)) {
            return false;
        }

        try {
            // 1. Ekstrak data pengguna dari sesi aktif jika tidak dispesifikasikan manual
            $idKaryawan = $params['id_karyawan'] ?? ($_SESSION['id_karyawan'] ?? ($_SESSION['user_id'] ?? null));
            $namaPengguna = trim($params['nama_pengguna'] ?? ($_SESSION['nama'] ?? ($_SESSION['username'] ?? 'System')));
            $role = strtoupper(trim($params['role'] ?? ($_SESSION['role'] ?? 'SYSTEM')));

            $modul = strtoupper(trim($params['modul'] ?? 'GENERAL'));
            $aksi = strtoupper(trim($params['aksi'] ?? 'ACTION'));
            $idReferensi = isset($params['id_referensi']) && is_numeric($params['id_referensi']) ? (int)$params['id_referensi'] : null;
            $nomorReferensi = isset($params['nomor_referensi']) ? trim((string)$params['nomor_referensi']) : null;
            $deskripsi = trim($params['deskripsi'] ?? ($modul . ' - ' . $aksi));

            // Format snapshot JSON jika berupa array
            $dataSebelumnya = null;
            if (isset($params['data_sebelumnya'])) {
                $dataSebelumnya = is_array($params['data_sebelumnya']) 
                    ? json_encode($params['data_sebelumnya'], JSON_UNESCAPED_UNICODE) 
                    : (string)$params['data_sebelumnya'];
            }

            $dataSesudahnya = null;
            if (isset($params['data_sesudahnya'])) {
                $dataSesudahnya = is_array($params['data_sesudahnya']) 
                    ? json_encode($params['data_sesudahnya'], JSON_UNESCAPED_UNICODE) 
                    : (string)$params['data_sesudahnya'];
            }

            // Dapatkan IP Address dan User Agent Klien
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            if (strpos($ipAddress, ',') !== false) {
                $ipAddress = trim(explode(',', $ipAddress)[0]);
            }
            $ipAddress = substr($ipAddress, 0, 45);

            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System', 0, 255);

            // Prepared Statement Insert
            $sql = "INSERT INTO activity_log 
                    (id_karyawan, nama_pengguna, role, modul, aksi, id_referensi, nomor_referensi, deskripsi, data_sebelumnya, data_sesudahnya, ip_address, user_agent, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return false;
            }

            $stmt->bind_param(
                "issssissssss",
                $idKaryawan,
                $namaPengguna,
                $role,
                $modul,
                $aksi,
                $idReferensi,
                $nomorReferensi,
                $deskripsi,
                $dataSebelumnya,
                $dataSesudahnya,
                $ipAddress,
                $userAgent
            );

            $success = $stmt->execute();
            $stmt->close();

            return $success;

        } catch (\Throwable $e) {
            // Log silently agar tidak pernah mengganggu alur transaksi utama
            error_log("Activity Logger Error: " . $e->getMessage());
            return false;
        }
    }
}
