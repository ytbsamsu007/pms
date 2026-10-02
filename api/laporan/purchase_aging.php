<?php
/**
 * REST API: Laporan Purchase Order Aging (PO Aging)
 * Endpoint: /api/laporan/purchase_aging.php
 * Path: api/laporan/purchase_aging.php
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

$user = apiAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

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
    $idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
    $statusFilter = trim($_GET['status'] ?? '');
    $agingFilter = trim($_GET['aging_range'] ?? ''); // '1-7', '8-14', '15-30', '>30'
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Base WHERE: status <> 'DITERIMA'
    $where = " WHERE purchase_order.`status` <> 'DITERIMA' ";
    $params = [];
    $types = "";

    if ($idVendor > 0) {
        $where .= " AND purchase_order.id_vendor = ? ";
        $params[] = $idVendor;
        $types .= "i";
    }

    if (!empty($statusFilter)) {
        $where .= " AND purchase_order.`status` = ? ";
        $params[] = $statusFilter;
        $types .= "s";
    }

    if (!empty($search)) {
        $where .= " AND (purchase_order.nomor_po LIKE ? OR vendor.nama_perusahaan LIKE ? OR karyawan.nama_karyawan LIKE ?) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like]);
        $types .= "sss";
    }

    // Aging filter condition using HAVING or WHERE on DATEDIFF
    if ($agingFilter === '1-7') {
        $where .= " AND DATEDIFF(CURDATE(), purchase_order.tanggal_po) BETWEEN 0 AND 7 ";
    } elseif ($agingFilter === '8-14') {
        $where .= " AND DATEDIFF(CURDATE(), purchase_order.tanggal_po) BETWEEN 8 AND 14 ";
    } elseif ($agingFilter === '15-30') {
        $where .= " AND DATEDIFF(CURDATE(), purchase_order.tanggal_po) BETWEEN 15 AND 30 ";
    } elseif ($agingFilter === '>30') {
        $where .= " AND DATEDIFF(CURDATE(), purchase_order.tanggal_po) > 30 ";
    }

    $sql = "SELECT
                purchase_order.id_po,
                purchase_order.nomor_po, 
                purchase_order.tanggal_po, 
                vendor.id_vendor,
                vendor.nama_perusahaan,
                karyawan.nama_karyawan AS pembuat_po, 
                purchase_order.`status`, 
                purchase_order.tanggal_status AS tanggal_update, 
                DATEDIFF(CURDATE(), purchase_order.tanggal_po) AS umur_po,
                COALESCE((SELECT SUM(pod.subtotal) FROM purchase_order_detail pod WHERE pod.id_po = purchase_order.id_po), 0) AS total_nilai_po
            FROM purchase_order
            INNER JOIN karyawan ON purchase_order.id_karyawan = karyawan.id_karyawan
            INNER JOIN vendor ON purchase_order.id_vendor = vendor.id_vendor
            {$where}
            ORDER BY DATEDIFF(CURDATE(), purchase_order.tanggal_po) DESC, purchase_order.tanggal_po ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    $grandTotalNilai = 0;
    $count0to7 = 0;
    $count8to14 = 0;
    $count15to30 = 0;
    $countOver30 = 0;

    while ($row = $res->fetch_assoc()) {
        $nilai = (float)$row['total_nilai_po'];
        $umur = (int)$row['umur_po'];
        $grandTotalNilai += $nilai;

        if ($umur <= 7) $count0to7++;
        elseif ($umur <= 14) $count8to14++;
        elseif ($umur <= 30) $count15to30++;
        else $countOver30++;

        $row['total_nilai_po'] = $nilai;
        $row['umur_po'] = $umur;
        $row['formatted_nilai_po'] = number_format($nilai, 0, ',', '.');
        $row['formatted_tanggal_po'] = !empty($row['tanggal_po']) ? date('d/m/Y', strtotime($row['tanggal_po'])) : '-';
        $row['formatted_tanggal_update'] = !empty($row['tanggal_update']) ? date('d/m/Y H:i', strtotime($row['tanggal_update'])) : '-';

        $items[] = $row;
    }
    $stmt->close();

    // VENDORS LIST FOR DROPDOWN
    $vendorsList = [];
    $resV = $conn->query("SELECT DISTINCT v.id_vendor, v.nama_perusahaan 
                          FROM purchase_order po
                          INNER JOIN vendor v ON po.id_vendor = v.id_vendor
                          WHERE po.`status` <> 'DITERIMA'
                          ORDER BY v.nama_perusahaan ASC");
    if ($resV) {
        while ($v = $resV->fetch_assoc()) {
            $vendorsList[] = [
                'id_vendor' => (int)$v['id_vendor'],
                'nama_perusahaan' => $v['nama_perusahaan']
            ];
        }
    }

    sendJson(true, 'Laporan Purchase Aging berhasil dimuat.', [
        'filters' => [
            'id_vendor' => $idVendor,
            'status' => $statusFilter,
            'aging_range' => $agingFilter,
            'search' => $search
        ],
        'vendors_list' => $vendorsList,
        'summary' => $items,
        'aging_stats' => [
            'range_0_7' => $count0to7,
            'range_8_14' => $count8to14,
            'range_15_30' => $count15to30,
            'range_over_30' => $countOver30
        ],
        'grand_total' => [
            'total_po' => count($items),
            'total_nilai_po' => $grandTotalNilai,
            'formatted_total_nilai_po' => 'Rp ' . number_format($grandTotalNilai, 0, ',', '.')
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Gagal memuat laporan purchase aging: ' . $e->getMessage(), null, 500);
}
