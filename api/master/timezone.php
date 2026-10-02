<?php
/**
 * API Master: Pengaturan Timezone Aplikasi - PT Jaya Teknis
 * Path: api/master/timezone.php
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// GET: Ambil data timezone yang aktif saat ini
if ($method === 'GET') {
    $tz = 'Asia/Makassar';
    try {
        $res = $conn->query("SELECT timezone FROM profile LIMIT 1");
        if ($res && ($row = $res->fetch_assoc()) && !empty($row['timezone'])) {
            $tz = $row['timezone'];
        }
    } catch (\Throwable $e) {}

    jsonResponse(true, 'Pengaturan timezone berhasil diambil.', [
        'timezone' => $tz,
        'server_time' => date('Y-m-d H:i:s'),
        'timezone_name' => date_default_timezone_get()
    ], 200);
}

// Hanya Role ADMIN yang dapat mengubah timezone sistem
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengubah pengaturan timezone.', null, 403);
}

// POST / PUT: Simpan timezone baru
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $timezone = trim($input['timezone'] ?? '');

    if (empty($timezone) || !in_array($timezone, DateTimeZone::listIdentifiers())) {
        jsonResponse(false, 'Zona waktu yang dipilih tidak valid.', null, 422);
    }

    // Update di tabel profile
    $check = $conn->query("SELECT id_perusahaan FROM profile LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $stmt = $conn->prepare("UPDATE profile SET timezone = ? WHERE id_perusahaan = ?");
        $stmt->bind_param("si", $timezone, $row['id_perusahaan']);
    } else {
        $stmt = $conn->prepare("INSERT INTO profile (nama, timezone) VALUES ('PT Jaya Teknik', ?)");
        $stmt->bind_param("s", $timezone);
    }

    if ($stmt->execute()) {
        $stmt->close();
        if (function_exists('applyAppTimezone')) {
            applyAppTimezone($conn);
        }
        jsonResponse(true, 'Pengaturan timezone berhasil diperbarui.', [
            'timezone' => $timezone,
            'server_time' => date('Y-m-d H:i:s')
        ], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui pengaturan timezone.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
