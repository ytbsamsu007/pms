<?php
/**
 * REST API: Delete Master Penomoran Transaksi
 * Endpoint: /api/penomoran/delete.php
 * Path: api/penomoran/delete.php
 * Khusus Role: ADMIN
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendJson(false, 'Metode HTTP tidak diizinkan.', null, 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $id = isset($input['id_nomor']) ? (int)$input['id_nomor'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    if ($id <= 0) {
        sendJson(false, 'ID penomoran tidak valid', null, 400);
    }

    $stmt = $conn->prepare("DELETE FROM penomoran WHERE id_nomor = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        sendJson(false, 'Data penomoran tidak ditemukan atau sudah dihapus', null, 404);
    }

    sendJson(true, 'Data penomoran berhasil dihapus');

} catch (Exception $e) {
    sendJson(false, 'Gagal menghapus penomoran: ' . $e->getMessage(), null, 500);
}
