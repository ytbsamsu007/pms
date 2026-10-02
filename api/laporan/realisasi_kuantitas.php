<?php
/**
 * REST API: Laporan Realisasi Kuantitas PO vs Penerimaan (RCV) & Retur
 * Endpoint: /api/laporan/realisasi_kuantitas.php
 * Path: api/laporan/realisasi_kuantitas.php
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
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Dynamic WHERE on Vendor
    $whereVendor = " WHERE po.qty_po IS NOT NULL ";
    $params = [];
    $types = "";

    if ($idVendor > 0) {
        $whereVendor .= " AND v.id_vendor = ? ";
        $params[] = $idVendor;
        $types .= "i";
    }

    if (!empty($search)) {
        $whereVendor .= " AND (v.nama_perusahaan LIKE ? OR v.nama_kontak LIKE ?) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like]);
        $types .= "ss";
    }

    // QUERY REALISASI KUANTITAS PER VENDOR
    $sql = "SELECT
                v.id_vendor,
                v.nama_perusahaan,
                COALESCE(po.qty_po, 0) AS qty_po,
                COALESCE(rcv.qty_rcv, 0) AS qty_rcv,
                COALESCE(retur.qty_retur, 0) AS qty_retur,
                ROUND(
                    (
                        (
                            COALESCE(rcv.qty_rcv, 0)
                            + COALESCE(retur.qty_retur, 0)
                        )
                        / NULLIF(COALESCE(po.qty_po, 0), 0)
                    ) * 100,
                    2
                ) AS persentase
            FROM vendor v
            LEFT JOIN (
                SELECT
                    purchase_order.id_vendor,
                    SUM(purchase_order_detail.qty) AS qty_po
                FROM purchase_order
                INNER JOIN purchase_order_detail
                    ON purchase_order.id_po = purchase_order_detail.id_po
                WHERE purchase_order.status = 'DITERIMA'
                GROUP BY purchase_order.id_vendor
            ) po
                ON v.id_vendor = po.id_vendor
            LEFT JOIN (
                SELECT
                    purchase_order.id_vendor,
                    SUM(receiving_order_detail.qty) AS qty_rcv
                FROM receiving_order
                INNER JOIN receiving_order_detail
                    ON receiving_order.id_rcv = receiving_order_detail.id_rcv
                INNER JOIN purchase_order
                    ON receiving_order.id_po = purchase_order.id_po
                WHERE receiving_order_detail.status_qc = 1
                GROUP BY purchase_order.id_vendor
            ) rcv
                ON v.id_vendor = rcv.id_vendor
            LEFT JOIN (
                SELECT
                    retur_po.id_vendor,
                    SUM(retur_po_detail.qty_diganti) AS qty_retur
                FROM retur_po
                INNER JOIN retur_po_detail
                    ON retur_po.id_po_retur = retur_po_detail.id_po_retur
                GROUP BY retur_po.id_vendor
            ) retur
                ON v.id_vendor = retur.id_vendor
            {$whereVendor}
            ORDER BY v.nama_perusahaan ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $summary = [];
    $grandQtyPo = 0;
    $grandQtyRcv = 0;
    $grandQtyRetur = 0;

    while ($row = $res->fetch_assoc()) {
        $qPo = (float)$row['qty_po'];
        $qRcv = (float)$row['qty_rcv'];
        $qRetur = (float)$row['qty_retur'];
        $persen = $row['persentase'] !== null ? (float)$row['persentase'] : 0;

        $grandQtyPo += $qPo;
        $grandQtyRcv += $qRcv;
        $grandQtyRetur += $qRetur;

        $row['qty_po'] = $qPo;
        $row['qty_rcv'] = $qRcv;
        $row['qty_retur'] = $qRetur;
        $row['persentase'] = $persen;
        $row['formatted_qty_po'] = number_format($qPo, 0, ',', '.');
        $row['formatted_qty_rcv'] = number_format($qRcv, 0, ',', '.');
        $row['formatted_qty_retur'] = number_format($qRetur, 0, ',', '.');
        $row['formatted_persentase'] = number_format($persen, 2, ',', '.') . '%';

        $summary[] = $row;
    }
    $stmt->close();

    // Kalkulasi Total Persentase Realisasi
    $grandPersen = $grandQtyPo > 0 ? round((($grandQtyRcv + $grandQtyRetur) / $grandQtyPo) * 100, 2) : 0;

    // DAFTAR VENDOR AKTIF UNTUK FILTER DROPDOWN
    $vendorsList = [];
    $resV = $conn->query("SELECT DISTINCT v.id_vendor, v.nama_perusahaan 
                          FROM vendor v 
                          INNER JOIN purchase_order po ON v.id_vendor = po.id_vendor 
                          WHERE po.status = 'DITERIMA' 
                          ORDER BY v.nama_perusahaan ASC");
    if ($resV) {
        while ($v = $resV->fetch_assoc()) {
            $vendorsList[] = [
                'id_vendor' => (int)$v['id_vendor'],
                'nama_perusahaan' => $v['nama_perusahaan']
            ];
        }
    }

    sendJson(true, 'Laporan Realisasi Kuantitas berhasil dimuat.', [
        'filters' => [
            'id_vendor' => $idVendor,
            'search' => $search
        ],
        'vendors_list' => $vendorsList,
        'summary' => $summary,
        'grand_total' => [
            'qty_po' => $grandQtyPo,
            'qty_rcv' => $grandQtyRcv,
            'qty_retur' => $grandQtyRetur,
            'persentase' => $grandPersen,
            'formatted_qty_po' => number_format($grandQtyPo, 0, ',', '.'),
            'formatted_qty_rcv' => number_format($grandQtyRcv, 0, ',', '.'),
            'formatted_qty_retur' => number_format($grandQtyRetur, 0, ',', '.'),
            'formatted_persentase' => number_format($grandPersen, 2, ',', '.') . '%'
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Gagal memuat laporan realisasi kuantitas: ' . $e->getMessage(), null, 500);
}
