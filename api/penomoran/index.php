<?php
/**
 * REST API: Daftar Master Penomoran Transaksi
 * Endpoint: /api/penomoran/index.php
 * Path: api/penomoran/index.php
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
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
    $tipeTransaksi = trim($_GET['tipe_transaksi'] ?? '');

    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if (!empty($tipeTransaksi)) {
        $where .= " AND p.tipe_transaksi = ? ";
        $params[] = $tipeTransaksi;
        $types .= "s";
    }

    if (!empty($search)) {
        $where .= " AND (p.nama_penomoran LIKE ? OR p.format LIKE ? OR p.tipe_transaksi LIKE ?) ";
        $searchPattern = "%{$search}%";
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $types .= "sss";
    }

    // Count Total
    $countSql = "SELECT COUNT(*) as total FROM penomoran p {$where}";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRecords = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // Pagination Parameter
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $totalPages = ceil($totalRecords / $limit) ?: 1;
    $offset = ($page - 1) * $limit;

    $sql = "
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
        {$where}
        ORDER BY p.id_nomor DESC
        LIMIT ? OFFSET ?
    ";

    $stmtParams = $params;
    $stmtParams[] = $limit;
    $stmtParams[] = $offset;
    $stmtTypes = $types . "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($stmtTypes, ...$stmtParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
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
    }
    $stmt->close();

    sendJson(true, 'Data penomoran berhasil dimuat', [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
