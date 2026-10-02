<?php
/**
 * REST API: Riwayat Log Aktivitas Pengguna & Audit Trail
 * Endpoint: /api/activity_log/index.php
 * Path: api/activity_log/index.php
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

$user = apiAuth([ROLE_ADMIN, ROLE_MANAGER, ROLE_FINANCE, ROLE_PURCHASING, ROLE_LOGISTIK]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    sendJson(false, 'Metode HTTP tidak diizinkan. Gunakan GET.', null, 405);
}

// 1. DETAIL SINGLE LOG
$idLog = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idLog > 0) {
    $stmt = $conn->prepare("SELECT l.*, k.nama_karyawan, j.nama_jabatan, d.nama_divisi 
                            FROM activity_log l
                            LEFT JOIN karyawan k ON l.id_karyawan = k.id_karyawan
                            LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                            LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                            WHERE l.id_log = ? LIMIT 1");
    $stmt->bind_param("i", $idLog);
    $stmt->execute();
    $log = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$log) {
        sendJson(false, 'Catatan log tidak ditemukan.', null, 404);
    }

    if (!empty($log['data_sebelumnya'])) {
        $log['data_sebelumnya'] = json_decode($log['data_sebelumnya'], true);
    }
    if (!empty($log['data_sesudahnya'])) {
        $log['data_sesudahnya'] = json_decode($log['data_sesudahnya'], true);
    }

    sendJson(true, 'Detail log aktivitas berhasil dimuat.', $log);
}

// 2. LIST LOG DENGAN FILTER & PAGINATION
$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(100, intval($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
$modul = trim($_GET['modul'] ?? '');
$aksi = trim($_GET['aksi'] ?? '');
$idKaryawan = intval($_GET['id_karyawan'] ?? 0);
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$where = " WHERE 1=1 ";
$params = [];
$types = "";

if ($search !== '') {
    $where .= " AND (l.nomor_referensi LIKE ? OR l.nama_pengguna LIKE ? OR l.deskripsi LIKE ? OR l.ip_address LIKE ?) ";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

if (!empty($modul)) {
    $m = strtoupper($modul);
    if ($m === 'RO' || $m === 'REQUEST_ORDER') {
        $where .= " AND l.modul IN ('RO', 'REQUEST_ORDER') ";
    } elseif ($m === 'PO' || $m === 'PURCHASE_ORDER') {
        $where .= " AND l.modul IN ('PO', 'PURCHASE_ORDER') ";
    } else {
        $where .= " AND l.modul = ? ";
        $params[] = $m;
        $types .= "s";
    }
}

if (!empty($aksi)) {
    $where .= " AND l.aksi = ? ";
    $params[] = strtoupper($aksi);
    $types .= "s";
}

if ($idKaryawan > 0) {
    $where .= " AND l.id_karyawan = ? ";
    $params[] = $idKaryawan;
    $types .= "i";
}

if (!empty($startDate)) {
    $where .= " AND DATE(l.created_at) >= ? ";
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $where .= " AND DATE(l.created_at) <= ? ";
    $params[] = $endDate;
    $types .= "s";
}

// Hitung Total Data
$sqlCount = "SELECT COUNT(*) AS total FROM activity_log l {$where}";
$stmtC = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmtC->bind_param($types, ...$params);
}
$stmtC->execute();
$totalRows = (int)($stmtC->get_result()->fetch_assoc()['total'] ?? 0);
$stmtC->close();

// Query Data List
$sqlList = "SELECT l.id_log, l.id_karyawan, l.nama_pengguna, l.role, 
                   l.modul, l.aksi, l.id_referensi, l.nomor_referensi, 
                   l.deskripsi, l.data_sebelumnya, l.data_sesudahnya, 
                   l.ip_address, l.user_agent, l.created_at,
                   k.nama_karyawan, j.nama_jabatan, d.nama_divisi
            FROM activity_log l
            LEFT JOIN karyawan k ON l.id_karyawan = k.id_karyawan
            LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
            {$where}
            ORDER BY l.id_log DESC
            LIMIT ? OFFSET ?";

$paramsList = $params;
$paramsList[] = $limit;
$paramsList[] = $offset;
$typesList = $types . "ii";

$stmtL = $conn->prepare($sqlList);
if (!empty($paramsList)) {
    $stmtL->bind_param($typesList, ...$paramsList);
}
$stmtL->execute();
$resL = $stmtL->get_result();

$rows = [];
while ($row = $resL->fetch_assoc()) {
    $row['has_diff'] = (!empty($row['data_sebelumnya']) || !empty($row['data_sesudahnya']));
    if (!empty($row['data_sebelumnya'])) {
        $row['data_sebelumnya'] = json_decode($row['data_sebelumnya'], true);
    }
    if (!empty($row['data_sesudahnya'])) {
        $row['data_sesudahnya'] = json_decode($row['data_sesudahnya'], true);
    }
    
    // Format tanggal tanpa milidetik/detik (d/m/Y H:i)
    $time = strtotime($row['created_at']);
    $row['formatted_time'] = date('d/m/Y H:i', $time);
    $rows[] = $row;
}
$stmtL->close();

// Metrik Ringkasan
$today = date('Y-m-d');
$sqlMetrics = "SELECT 
                COUNT(*) AS total_semua,
                SUM(CASE WHEN DATE(created_at) = '{$today}' THEN 1 ELSE 0 END) AS total_hari_ini,
                SUM(CASE WHEN modul = 'RO' THEN 1 ELSE 0 END) AS count_ro,
                SUM(CASE WHEN modul = 'PO' THEN 1 ELSE 0 END) AS count_po,
                SUM(CASE WHEN modul = 'RECEIVING' THEN 1 ELSE 0 END) AS count_rcv,
                SUM(CASE WHEN modul = 'RETUR' THEN 1 ELSE 0 END) AS count_retur,
                SUM(CASE WHEN modul = 'FAKTUR' THEN 1 ELSE 0 END) AS count_faktur,
                SUM(CASE WHEN modul = 'PAYMENT' THEN 1 ELSE 0 END) AS count_payment
               FROM activity_log";
$resM = $conn->query($sqlMetrics);
$metrics = $resM ? $resM->fetch_assoc() : [];

sendJson(true, 'Daftar riwayat aktivitas pengguna berhasil dimuat.', [
    'rows' => $rows,
    'pagination' => [
        'total' => $totalRows,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalRows / $limit)
    ],
    'metrics' => [
        'total_semua' => (int)($metrics['total_semua'] ?? 0),
        'total_hari_ini' => (int)($metrics['total_hari_ini'] ?? 0),
        'by_modul' => [
            'RO' => (int)($metrics['count_ro'] ?? 0),
            'PO' => (int)($metrics['count_po'] ?? 0),
            'RECEIVING' => (int)($metrics['count_rcv'] ?? 0),
            'RETUR' => (int)($metrics['count_retur'] ?? 0),
            'FAKTUR' => (int)($metrics['count_faktur'] ?? 0),
            'PAYMENT' => (int)($metrics['count_payment'] ?? 0)
        ]
    ]
]);
