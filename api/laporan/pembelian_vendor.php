<?php
/**
 * REST API: Laporan Pembelian per Vendor
 * Endpoint: /api/laporan/pembelian_vendor.php
 * Path: api/laporan/pembelian_vendor.php
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

$user = apiAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING]);

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
    $idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Dynamic WHERE clause
    $where = " WHERE fp.terbayar > 0 ";
    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $where .= " AND DATE(fp.tanggal_terima_faktur_vendor) >= ? ";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where .= " AND DATE(fp.tanggal_terima_faktur_vendor) <= ? ";
        $params[] = $endDate;
        $types .= "s";
    }

    if ($idVendor > 0) {
        $where .= " AND fp.id_vendor = ? ";
        $params[] = $idVendor;
        $types .= "i";
    }

    if (!empty($search)) {
        $where .= " AND (v.nama_perusahaan LIKE ? OR v.nama_kontak LIKE ? OR fp.nomor_faktur LIKE ?) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like]);
        $types .= "sss";
    }

    // 1. QUERY SUMMARY PER VENDOR
    $sqlSummary = "SELECT
                        v.id_vendor,
                        v.nama_perusahaan,
                        COUNT(fp.id_faktur) AS total_faktur,
                        COALESCE(SUM(fp.terbayar), 0) AS total_terbayar,
                        'NILAI_FAKTUR' AS keterangan
                   FROM
                        faktur_po fp
                        INNER JOIN vendor v ON fp.id_vendor = v.id_vendor
                   {$where}
                   GROUP BY
                        v.id_vendor, v.nama_perusahaan
                   ORDER BY
                        v.nama_perusahaan ASC";

    $stmtSum = $conn->prepare($sqlSummary);
    if (!empty($params)) {
        $stmtSum->bind_param($types, ...$params);
    }
    $stmtSum->execute();
    $resSum = $stmtSum->get_result();

    $summary = [];
    $grandTotalTerbayar = 0;
    $grandTotalFaktur = 0;

    while ($row = $resSum->fetch_assoc()) {
        $terbayar = (float)$row['total_terbayar'];
        $fakturCount = (int)$row['total_faktur'];

        $grandTotalTerbayar += $terbayar;
        $grandTotalFaktur += $fakturCount;

        $row['total_terbayar'] = $terbayar;
        $row['total_faktur'] = $fakturCount;
        $row['formatted_terbayar'] = number_format($terbayar, 0, ',', '.');

        $summary[] = $row;
    }
    $stmtSum->close();

    // 2. DAFTAR VENDOR UNTUK FILTER DROPDOWN
    $vendorsList = [];
    $resV = $conn->query("SELECT DISTINCT v.id_vendor, v.nama_perusahaan 
                          FROM faktur_po fp 
                          INNER JOIN vendor v ON fp.id_vendor = v.id_vendor 
                          WHERE fp.terbayar > 0 
                          ORDER BY v.nama_perusahaan ASC");
    if ($resV) {
        while ($v = $resV->fetch_assoc()) {
            $vendorsList[] = [
                'id_vendor' => (int)$v['id_vendor'],
                'nama_perusahaan' => $v['nama_perusahaan']
            ];
        }
    }

    sendJson(true, 'Laporan Pembelian per Vendor berhasil dimuat.', [
        'filters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'id_vendor' => $idVendor,
            'search' => $search
        ],
        'vendors_list' => $vendorsList,
        'summary' => $summary,
        'grand_total' => [
            'total_faktur' => $grandTotalFaktur,
            'total_terbayar' => $grandTotalTerbayar,
            'formatted_total_terbayar' => 'Rp ' . number_format($grandTotalTerbayar, 0, ',', '.')
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Gagal memuat laporan pembelian per vendor: ' . $e->getMessage(), null, 500);
}
