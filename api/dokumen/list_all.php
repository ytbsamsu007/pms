<?php
/**
 * API Dokumen: Ambil Semua Daftar Arsip Dokumen dengan Pagination & Filter
 * PT Jaya Teknis
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
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    sendJson(false, 'Sesi Anda telah berakhir. Silakan login kembali.', null, 401);
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(5, min(100, intval($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$q = trim($_GET['q'] ?? '');
$tipe = trim($_GET['tipe'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($q)) {
    $where[] = "(d.nomor_dokumen LIKE ? OR d.nama_dokumen LIKE ? OR k.nama_karyawan LIKE ?)";
    $likeQ = "%$q%";
    $params[] = $likeQ;
    $params[] = $likeQ;
    $params[] = $likeQ;
    $types .= "sss";
}

if (!empty($tipe)) {
    $where[] = "d.tipe_dokumen = ?";
    $params[] = $tipe;
    $types .= "s";
}

if (!empty($tanggal)) {
    $where[] = "d.tanggal_dokumen = ?";
    $params[] = $tanggal;
    $types .= "s";
}

$whereClause = implode(" AND ", $where);

// Count Total
$sqlCount = "SELECT COUNT(*) as total 
             FROM dokumen d 
             LEFT JOIN karyawan k ON d.id_karyawan = k.id_karyawan 
             WHERE $whereClause";
$stmtCount = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalRecords = (int)$stmtCount->get_result()->fetch_assoc()['total'];
$stmtCount->close();

// Fetch Data
$sqlData = "SELECT d.*, 
                   k.nama_karyawan, 
                   k.kode_karyawan,
                   j.nama_jabatan,
                   (CASE WHEN d.password_open IS NOT NULL AND d.password_open != '' THEN 1 ELSE 0 END) AS is_protected
            FROM dokumen d
            LEFT JOIN karyawan k ON d.id_karyawan = k.id_karyawan
            LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            WHERE $whereClause
            ORDER BY d.id_dokumen DESC
            LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sqlData);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$items = [];
while ($row = $res->fetch_assoc()) {
    unset($row['password_open']);
    if (!empty($row['file'])) {
        $row['file_url'] = BASE_URL . '/uploads/dokumen/' . $row['file'];
        $filePath = __DIR__ . '/../../uploads/dokumen/' . $row['file'];
        $row['file_size'] = file_exists($filePath) ? filesize($filePath) : 0;
        $row['file_ext'] = strtolower(pathinfo($row['file'], PATHINFO_EXTENSION));
    } else {
        $row['file_url'] = null;
        $row['file_size'] = 0;
        $row['file_ext'] = null;
    }
    $items[] = $row;
}
$stmt->close();

sendJson(true, 'Data arsip dokumen berhasil dimuat.', [
    'items' => $items,
    'pagination' => [
        'total_records' => $totalRecords,
        'current_page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalRecords / $limit)
    ]
]);
