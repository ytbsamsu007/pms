<?php
/**
 * REST API: Daftar Transaksi Stock Adjustment
 * Endpoint: /api/adjustment_stok/index.php
 * Path: api/adjustment_stok/index.php
 * Khusus Role: ADMIN, LOGISTIK, MEKANIK, MANAGER
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

$user = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MEKANIK, ROLE_MANAGER]);

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
    $status = trim($_GET['status'] ?? '');
    $idSite = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
    $jenis = trim($_GET['jenis'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 15)));
    $isAll = isset($_GET['all']) && (int)$_GET['all'] === 1;

    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if (!empty($status)) {
        $where .= " AND adj.status = ? ";
        $params[] = $status;
        $types .= "s";
    }

    if ($idSite > 0) {
        $where .= " AND adj.id_site = ? ";
        $params[] = $idSite;
        $types .= "i";
    }

    if (!empty($jenis)) {
        $where .= " AND adj.jenis_adjustment = ? ";
        $params[] = $jenis;
        $types .= "s";
    }

    if (!empty($startDate)) {
        $where .= " AND DATE(adj.tanggal_adjustment) >= ? ";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where .= " AND DATE(adj.tanggal_adjustment) <= ? ";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($search)) {
        $where .= " AND (
            adj.nomor_adjustment LIKE ? OR 
            adj.alasan LIKE ? OR 
            adj.keterangan LIKE ? OR 
            s.nama_site LIKE ? OR 
            k.nama_karyawan LIKE ?
        ) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
        $types .= "sssss";
    }

    // 1. KPI Metrics Summary
    $sqlMetrics = "SELECT 
                    COUNT(*) AS total_transaksi,
                    SUM(CASE WHEN adj.status = 'DRAFT' THEN 1 ELSE 0 END) AS total_draft,
                    SUM(CASE WHEN adj.status = 'PENDING' THEN 1 ELSE 0 END) AS total_pending,
                    SUM(CASE WHEN adj.status = 'APPROVED' THEN 1 ELSE 0 END) AS total_approved,
                    SUM(CASE WHEN adj.status = 'REJECTED' THEN 1 ELSE 0 END) AS total_rejected
                   FROM adjustment_stok adj
                   LEFT JOIN site s ON s.id_site = adj.id_site
                   LEFT JOIN karyawan k ON k.id_karyawan = adj.id_karyawan";
    $resMetrics = $conn->query($sqlMetrics)->fetch_assoc();

    // 2. Count Total Rows Filtered
    $sqlCount = "SELECT COUNT(*) AS total_rows
                 FROM adjustment_stok adj
                 LEFT JOIN site s ON s.id_site = adj.id_site
                 LEFT JOIN karyawan k ON k.id_karyawan = adj.id_karyawan
                 {$where}";
    $stmtCount = $conn->prepare($sqlCount);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRows = (int)($stmtCount->get_result()->fetch_assoc()['total_rows'] ?? 0);
    $stmtCount->close();

    // 3. Query Data List
    $sqlList = "SELECT 
                    adj.id_adjustment,
                    adj.nomor_adjustment,
                    adj.tanggal_adjustment,
                    adj.id_site,
                    s.nama_site,
                    adj.jenis_adjustment,
                    adj.alasan,
                    adj.keterangan,
                    adj.status,
                    adj.id_karyawan,
                    k.nama_karyawan AS nama_pembuat,
                    j.nama_jabatan AS jabatan_pembuat,
                    adj.id_karyawan_approved,
                    ka.nama_karyawan AS nama_approver,
                    adj.tanggal_approved,
                    adj.created_at,
                    COUNT(d.id_adjustment_detail) AS total_item,
                    COALESCE(SUM(d.qty_adjustment), 0) AS total_qty_adjustment,
                    COALESCE(SUM(d.subtotal_adjustment), 0) AS total_nilai_adjustment
                FROM adjustment_stok adj
                LEFT JOIN site s ON s.id_site = adj.id_site
                LEFT JOIN karyawan k ON k.id_karyawan = adj.id_karyawan
                LEFT JOIN jabatan j ON j.id_jabatan = k.id_jabatan
                LEFT JOIN karyawan ka ON ka.id_karyawan = adj.id_karyawan_approved
                LEFT JOIN adjustment_stok_detail d ON d.id_adjustment = adj.id_adjustment
                {$where}
                GROUP BY adj.id_adjustment
                ORDER BY adj.id_adjustment DESC";

    if (!$isAll) {
        $offset = ($page - 1) * $limit;
        $sqlList .= " LIMIT ?, ? ";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }

    $stmtList = $conn->prepare($sqlList);
    if (!empty($params)) {
        $stmtList->bind_param($types, ...$params);
    }
    $stmtList->execute();
    $resList = $stmtList->get_result();

    $rows = [];
    while ($r = $resList->fetch_assoc()) {
        $rows[] = [
            'id_adjustment' => (int)$r['id_adjustment'],
            'nomor_adjustment' => $r['nomor_adjustment'],
            'tanggal_adjustment' => $r['tanggal_adjustment'],
            'id_site' => (int)$r['id_site'],
            'nama_site' => $r['nama_site'] ?? '-',
            'jenis_adjustment' => $r['jenis_adjustment'],
            'alasan' => $r['alasan'],
            'keterangan' => $r['keterangan'],
            'status' => $r['status'],
            'id_karyawan' => (int)$r['id_karyawan'],
            'nama_pembuat' => $r['nama_pembuat'] ?? '-',
            'jabatan_pembuat' => $r['jabatan_pembuat'] ?? '-',
            'id_karyawan_approved' => $r['id_karyawan_approved'] ? (int)$r['id_karyawan_approved'] : null,
            'nama_approver' => $r['nama_approver'] ?? '-',
            'tanggal_approved' => $r['tanggal_approved'],
            'created_at' => $r['created_at'],
            'total_item' => (int)$r['total_item'],
            'total_qty_adjustment' => (float)$r['total_qty_adjustment'],
            'total_nilai_adjustment' => (float)$r['total_nilai_adjustment']
        ];
    }
    $stmtList->close();

    // Ambil list site untuk filter dropdown
    $sites = [];
    $resS = $conn->query("SELECT id_site, nama_site FROM site ORDER BY nama_site ASC");
    if ($resS) {
        while ($s = $resS->fetch_assoc()) {
            $sites[] = [
                'id_site' => (int)$s['id_site'],
                'nama_site' => $s['nama_site']
            ];
        }
    }

    sendJson(true, 'Data adjustment stok berhasil dimuat.', [
        'rows' => $rows,
        'metrics' => [
            'total' => (int)($resMetrics['total_transaksi'] ?? 0),
            'draft' => (int)($resMetrics['total_draft'] ?? 0),
            'pending' => (int)($resMetrics['total_pending'] ?? 0),
            'approved' => (int)($resMetrics['total_approved'] ?? 0),
            'rejected' => (int)($resMetrics['total_rejected'] ?? 0)
        ],
        'filter_options' => [
            'sites' => $sites
        ],
        'pagination' => [
            'total' => $totalRows,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $limit > 0 ? (int)ceil($totalRows / $limit) : 1
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
