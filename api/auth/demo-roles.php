<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/koneksi.php';

header('Content-Type: application/json');

$demoRoles = [
    'admin'      => ['label' => 'Admin', 'email' => 'admin', 'password' => 'admin123', 'name' => 'Administrator'],
    'mekanik'    => ['label' => 'Mekanik', 'email' => 'mekanik@jayateknis.com', 'password' => 'admin123', 'name' => 'Mekanik'],
    'logistik'   => ['label' => 'Logistik', 'email' => 'logistik@jayateknis.com', 'password' => 'admin123', 'name' => 'Logistik'],
    'purchasing' => ['label' => 'Purchasing', 'email' => 'purchasing@jayateknis.com', 'password' => 'admin123', 'name' => 'Purchasing'],
    'finance'    => ['label' => 'Finance', 'email' => 'finance@jayateknis.com', 'password' => 'admin123', 'name' => 'Finance'],
    'manager'    => ['label' => 'Manager', 'email' => 'manager@jayateknis.com', 'password' => 'admin123', 'name' => 'Manager'],
];

try {
    $qUser = $conn->query("SELECT id_users, nama_users, email FROM users WHERE aktif = 1 ORDER BY id_users ASC LIMIT 1");
    if ($qUser && $u = $qUser->fetch_assoc()) {
        $demoRoles['admin']['email'] = !empty($u['email']) ? $u['email'] : 'admin';
        $demoRoles['admin']['name'] = $u['nama_users'] ?? 'Administrator';
    }

    $sqlK = "SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.email, 
                    k.id_jabatan, j.nama_jabatan, j.level as level_jabatan, 
                    k.id_divisi, d.nama_divisi 
             FROM karyawan k 
             LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan 
             LEFT JOIN divisi d ON k.id_divisi = d.id_divisi 
             WHERE k.aktif = 1 AND k.login_web = 1 AND k.email IS NOT NULL AND k.email != '' 
             ORDER BY k.id_karyawan ASC";
    $qK = $conn->query($sqlK);
    if ($qK) {
        while ($row = $qK->fetch_assoc()) {
            $idDiv = !empty($row['id_divisi']) ? (int)$row['id_divisi'] : null;
            $idJ = !empty($row['id_jabatan']) ? (int)$row['id_jabatan'] : null;
            $divisiLower = strtolower($row['nama_divisi'] ?? '');
            $jabatanLower = strtolower($row['nama_jabatan'] ?? '');
            $emailLower = strtolower($row['email'] ?? '');
            $lvl = isset($row['level_jabatan']) ? (int)$row['level_jabatan'] : null;
            $emailK = $row['email'];
            $namaK = $row['nama_karyawan'];

            if ($idDiv === 1 || $idJ === 4 || $lvl === 1 || strpos($divisiLower, 'manajemen') !== false || strpos($jabatanLower, 'manager') !== false || strpos($jabatanLower, 'direktur') !== false) {
                if (empty($demoRoles['manager']['email']) || $demoRoles['manager']['email'] === 'manager@jayateknis.com') {
                    $demoRoles['manager']['email'] = $emailK;
                    $demoRoles['manager']['name'] = $namaK;
                }
            } elseif ($idDiv === 4 || $idJ === 3 || strpos($divisiLower, 'mekanik') !== false || strpos($jabatanLower, 'mekanik') !== false || strpos($emailLower, 'mekanik') !== false) {
                if (empty($demoRoles['mekanik']['email']) || $demoRoles['mekanik']['email'] === 'mekanik@jayateknis.com') {
                    $demoRoles['mekanik']['email'] = $emailK;
                    $demoRoles['mekanik']['name'] = $namaK;
                }
            } elseif ($idDiv === 2 || $idJ === 2 || strpos($divisiLower, 'logistik') !== false || strpos($jabatanLower, 'logistik') !== false || strpos($emailLower, 'logistik') !== false) {
                if (empty($demoRoles['logistik']['email']) || $demoRoles['logistik']['email'] === 'logistik@jayateknis.com') {
                    $demoRoles['logistik']['email'] = $emailK;
                    $demoRoles['logistik']['name'] = $namaK;
                }
            } elseif ($idDiv === 3 || $idJ === 5 || strpos($divisiLower, 'purchasing') !== false || strpos($jabatanLower, 'purchasing') !== false || strpos($emailLower, 'purchasing') !== false) {
                if (empty($demoRoles['purchasing']['email']) || $demoRoles['purchasing']['email'] === 'purchasing@jayateknis.com') {
                    $demoRoles['purchasing']['email'] = $emailK;
                    $demoRoles['purchasing']['name'] = $namaK;
                }
            } elseif ($idDiv === 6 || $idJ === 6 || $idJ === 7 || strpos($divisiLower, 'finance') !== false || strpos($jabatanLower, 'finance') !== false || strpos($emailLower, 'finance') !== false) {
                if (empty($demoRoles['finance']['email']) || $demoRoles['finance']['email'] === 'finance@jayateknis.com') {
                    $demoRoles['finance']['email'] = $emailK;
                    $demoRoles['finance']['name'] = $namaK;
                }
            } elseif ($idDiv === 5 || $idJ === 1 || strpos($divisiLower, 'admin') !== false || strpos($divisiLower, 'it') !== false || strpos($jabatanLower, 'admin') !== false || strpos($emailLower, 'admin') !== false) {
                if (empty($demoRoles['admin']['email']) || $demoRoles['admin']['email'] === 'admin' || $demoRoles['admin']['email'] === 'admin@jayateknis.com') {
                    $demoRoles['admin']['email'] = $emailK;
                    $demoRoles['admin']['name'] = $namaK;
                }
            }
        }
    }
} catch (\Throwable $e) {}

echo json_encode(['success' => true, 'data' => array_values($demoRoles)]);
