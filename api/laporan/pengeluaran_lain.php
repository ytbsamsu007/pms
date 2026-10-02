<?php
/**
 * REST API: Laporan Biaya Pengeluaran Lain-lain
 * Endpoint: /api/laporan/pengeluaran_lain.php
 * Path: api/laporan/pengeluaran_lain.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
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

$user = apiAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

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
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $kategori = trim($_GET['kategori'] ?? ''); // 'Biaya Retur', 'Biaya Admin Pembayaran', 'Biaya Mutasi', or ''
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 15)));
    $isAll = isset($_GET['all']) && (int)$_GET['all'] === 1;

    // Subquery data_biaya dari UNION ALL
    $baseUnion = "
        SELECT
            'Biaya Retur' AS keterangan,
            retur_po.nomor_po_retur AS Nomor_Ref,
            retur_po.tanggal_po_retur AS Tanggal_Ref,
            retur_po.biaya_retur AS biaya
        FROM retur_po
        WHERE biaya_retur > 0

        UNION ALL

        SELECT
            'Biaya Admin Pembayaran' AS keterangan,
            payment_purchase_detail.kode_pembayaran AS Nomor_Ref,
            payment_purchase_detail.tanggal_bayar AS Tanggal_Ref,
            payment_purchase_detail.biaya_admin AS biaya
        FROM payment_purchase_detail
        WHERE biaya_admin > 0

        UNION ALL

        SELECT
            'Biaya Mutasi' AS keterangan,
            mutasi_order.kode_mutasi AS Nomor_Ref,
            mutasi_order.created_at AS Tanggal_Ref,
            mutasi_order.biaya_operasional AS biaya
        FROM mutasi_order
        WHERE biaya_operasional > 0
    ";

    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $where .= " AND DATE(data_biaya.Tanggal_Ref) >= ? ";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where .= " AND DATE(data_biaya.Tanggal_Ref) <= ? ";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($kategori)) {
        $where .= " AND data_biaya.keterangan = ? ";
        $params[] = $kategori;
        $types .= "s";
    }

    if (!empty($search)) {
        $where .= " AND (data_biaya.keterangan LIKE ? OR data_biaya.Nomor_Ref LIKE ?) ";
        $like = "%{$search}%";
        $params[] = $like;
        $params[] = $like;
        $types .= "ss";
    }

    // 1. Query Grand Total & Count
    $sqlTotal = "SELECT 
                    COUNT(*) AS total_rows,
                    COALESCE(SUM(data_biaya.biaya), 0) AS grand_total_biaya
                 FROM ({$baseUnion}) AS data_biaya
                 {$where}";

    $stmtTotal = $conn->prepare($sqlTotal);
    if (!empty($params)) {
        $stmtTotal->bind_param($types, ...$params);
    }
    $stmtTotal->execute();
    $resTotal = $stmtTotal->get_result()->fetch_assoc();
    $stmtTotal->close();

    $totalRows = (int)($resTotal['total_rows'] ?? 0);
    $grandTotalBiaya = (float)($resTotal['grand_total_biaya'] ?? 0);

    // 2. Query Data Rows
    $sqlList = "SELECT 
                    data_biaya.keterangan,
                    data_biaya.Nomor_Ref,
                    data_biaya.Tanggal_Ref,
                    data_biaya.biaya
                FROM ({$baseUnion}) AS data_biaya
                {$where}
                ORDER BY data_biaya.Tanggal_Ref DESC, data_biaya.Nomor_Ref DESC";

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
            'keterangan' => $r['keterangan'],
            'nomor_ref' => $r['Nomor_Ref'],
            'tanggal_ref' => $r['Tanggal_Ref'],
            'biaya' => (float)$r['biaya']
        ];
    }
    $stmtList->close();

    sendJson(true, 'Data pengeluaran lain-lain berhasil dimuat.', [
        'rows' => $rows,
        'grand_totals' => [
            'total_transaksi' => $totalRows,
            'grand_total_biaya' => $grandTotalBiaya
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
