<?php
/**
 * API Master: Profile Perusahaan - PT Jaya Teknis
 * Path: api/master/profile.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// 1. GET: Ambil data profil perusahaan
if ($method === 'GET') {
    $profile = getCompanyProfile($conn);
    jsonResponse(true, 'Profil perusahaan berhasil diambil.', ['profile' => $profile], 200);
}

// Hanya Role ADMIN yang dapat mengubah data profil perusahaan
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengubah profil perusahaan.', null, 403);
}

// 2. DELETE: Hapus Logo Perusahaan
if ($method === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete_logo')) {
    $check = $conn->query("SELECT id_perusahaan, picture FROM profile LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        $oldPic = $row['picture'];
        if (!empty($oldPic)) {
            $oldPath = __DIR__ . '/../../' . $oldPic;
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }
        $stmtDel = $conn->prepare("UPDATE profile SET picture = '' WHERE id_perusahaan = ?");
        $stmtDel->bind_param("i", $row['id_perusahaan']);
        $stmtDel->execute();
        $stmtDel->close();
    }
    jsonResponse(true, 'Logo perusahaan berhasil dihapus.', [
        'profile' => getCompanyProfile($conn)
    ], 200);
}

// 3. POST / PUT: Update Profil Perusahaan
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $nama = trim($input['nama'] ?? '');
    $telepon1 = trim($input['telepon1'] ?? '');
    $whatsapp = trim($input['whatsapp'] ?? '');
    $email = trim($input['email'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $alamatGps = trim($input['alamat_gps'] ?? '');
    $kota = trim($input['kota'] ?? '');
    $provinsi = trim($input['provinsi'] ?? '');
    $npwp = trim($input['npwp'] ?? '');
    $klu = trim($input['KLU'] ?? '');
    $nitku = trim($input['NITKU'] ?? '');
    $timezone = trim($input['timezone'] ?? 'Asia/Makassar');
    if (empty($timezone) || !in_array($timezone, DateTimeZone::listIdentifiers())) {
        $timezone = 'Asia/Makassar';
    }
    $pajak12 = isset($input['pajak12']) ? (int)$input['pajak12'] : 1;
    $picture = trim($input['picture'] ?? '');

    if (empty($nama)) {
        jsonResponse(false, 'Nama perusahaan tidak boleh kosong.', null, 422);
    }

    // Cek record profil
    $check = $conn->query("SELECT id_perusahaan, picture FROM profile LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $idPerusahaan = (int)$row['id_perusahaan'];
        if (!isset($input['picture'])) {
            $picture = $row['picture'] ?? '';
        }

        $stmt = $conn->prepare("UPDATE profile SET nama = ?, telepon1 = ?, whatsapp = ?, email = ?, alamat = ?, alamat_gps = ?, 
                                kota = ?, provinsi = ?, npwp = ?, KLU = ?, NITKU = ?, timezone = ?, pajak12 = ?, picture = ? 
                                WHERE id_perusahaan = ?");
        $stmt->bind_param("ssssssssssssisi", $nama, $telepon1, $whatsapp, $email, $alamat, $alamatGps, $kota, $provinsi, $npwp, $klu, $nitku, $timezone, $pajak12, $picture, $idPerusahaan);
    } else {
        $stmt = $conn->prepare("INSERT INTO profile (nama, telepon1, whatsapp, email, alamat, alamat_gps, kota, provinsi, npwp, KLU, NITKU, timezone, pajak12, picture) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssis", $nama, $telepon1, $whatsapp, $email, $alamat, $alamatGps, $kota, $provinsi, $npwp, $klu, $nitku, $timezone, $pajak12, $picture);
    }

    if ($stmt->execute()) {
        $stmt->close();
        if (function_exists('applyAppTimezone')) {
            applyAppTimezone($conn);
        }
        jsonResponse(true, 'Profil perusahaan berhasil diperbarui.', [
            'profile' => getCompanyProfile($conn)
        ], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui profil perusahaan.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
