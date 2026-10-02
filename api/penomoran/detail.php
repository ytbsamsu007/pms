<?php
/**
 * REST API: Detail Master Penomoran Transaksi
 * Endpoint: /api/penomoran/detail.php?id=1
 * Path: api/penomoran/detail.php
 * Khusus Role: ADMIN
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_ADMIN]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(false, 'Metode HTTP tidak diizinkan. Gunakan GET.', null, 405);
}

try {
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        sendJson(false, 'ID penomoran tidak valid', null, 400);
    }

    $stmt = $conn->prepare("
        SELECT 
            p.id_nomor,
            p.nama_penomoran,
            p.tipe_transaksi,
            p.tipe_penomoran,
            p.digit_counter,
            p.format,
            p.created_at,
            p.updated_at,
            p.id_karyawan,
            k.nama_karyawan AS pembuat_nama
        FROM penomoran p
        LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan
        WHERE p.id_nomor = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        sendJson(false, 'Data penomoran tidak ditemukan', null, 404);
    }

    $row = $res->fetch_assoc();
    $stmt->close();

    $data = [
        'id_nomor' => (int)$row['id_nomor'],
        'nama_penomoran' => $row['nama_penomoran'],
        'tipe_transaksi' => $row['tipe_transaksi'],
        'tipe_penomoran' => (int)$row['tipe_penomoran'],
        'digit_counter' => (int)$row['digit_counter'],
        'format' => $row['format'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'id_karyawan' => (int)$row['id_karyawan'],
        'pembuat_nama' => $row['pembuat_nama'] ?: '-'
    ];

    sendJson(true, 'Detail penomoran ditemukan', $data);

} catch (Exception $e) {
    sendJson(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
}
