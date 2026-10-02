<?php
/**
 * REST API: Laporan Hutang Vendor (Sudah Difakturkan & Belum Difakturkan)
 * Endpoint: /api/laporan/hutang_vendor.php
 * Path: api/laporan/hutang_vendor.php
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
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
    $statusFakturFilter = trim($_GET['status_faktur'] ?? ''); // 'SUDAH', 'BELUM', or empty
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Base WHERE: PO aktif yang belum lunas atau belum difakturkan
    $where = " WHERE po.status NOT IN ('DRAFT', 'BATAL') ";
    $params = [];
    $types = "";

    // Kondisi hutang aktif
    $where .= " AND (
        (fp.id_faktur IS NOT NULL AND fp.sisa_tagihan > 0)
        OR (fp.id_faktur IS NULL AND po.status IN ('DITERIMA', 'DISETUJUI INTERNAL', 'DIPROSES VENDOR'))
    ) ";

    if (!empty($startDate)) {
        $where .= " AND DATE(po.tanggal_po) >= ? ";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where .= " AND DATE(po.tanggal_po) <= ? ";
        $params[] = $endDate;
        $types .= "s";
    }

    if ($idVendor > 0) {
        $where .= " AND po.id_vendor = ? ";
        $params[] = $idVendor;
        $types .= "i";
    }

    if ($statusFakturFilter === 'SUDAH') {
        $where .= " AND fp.id_faktur IS NOT NULL ";
    } elseif ($statusFakturFilter === 'BELUM') {
        $where .= " AND fp.id_faktur IS NULL ";
    }

    if (!empty($search)) {
        $where .= " AND (v.nama_perusahaan LIKE ? OR v.kode_vendor LIKE ? OR po.nomor_po LIKE ? OR fp.nomor_faktur LIKE ?) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like]);
        $types .= "ssss";
    }

    // QUERY DATA DETAIL ITEM HUTANG VENDOR
    $sql = "SELECT 
                po.id_po,
                po.nomor_po,
                po.tanggal_po,
                po.status AS status_po,
                v.id_vendor,
                COALESCE(v.kode_vendor, '-') AS kode_vendor,
                v.nama_perusahaan AS nama_vendor,
                fp.id_faktur,
                COALESCE(fp.nomor_faktur, '-') AS nomor_faktur,
                fp.tanggal_faktur_vendor,
                fp.tanggal_jatuh_tempo,
                COALESCE((SELECT SUM(pod.subtotal) FROM purchase_order_detail pod WHERE pod.id_po = po.id_po), 0) AS nilai_po,
                COALESCE(fp.total_tagihan, 0) AS total_faktur,
                COALESCE(fp.terbayar, 0) AS total_terbayar,
                CASE 
                    WHEN fp.id_faktur IS NOT NULL THEN fp.sisa_tagihan
                    ELSE COALESCE((SELECT SUM(pod.subtotal) FROM purchase_order_detail pod WHERE pod.id_po = po.id_po), 0)
                END AS sisa_hutang,
                CASE 
                    WHEN fp.id_faktur IS NOT NULL THEN 'Sudah Difakturkan'
                    ELSE 'Belum Difakturkan'
                END AS status_faktur_ket,
                fp.status AS status_faktur
            FROM purchase_order po
            INNER JOIN vendor v ON po.id_vendor = v.id_vendor
            LEFT JOIN faktur_po fp ON po.id_po = fp.id_po
            {$where}
            ORDER BY v.nama_perusahaan ASC, po.nomor_po ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    $groupedVendors = [];
    $grandTotalPo = 0;
    $grandTotalFaktur = 0;
    $grandTotalTerbayar = 0;
    $grandTotalHutang = 0;

    while ($row = $res->fetch_assoc()) {
        $vId = (int)$row['id_vendor'];
        $nPo = (float)$row['nilai_po'];
        $nFak = (float)$row['total_faktur'];
        $nBayar = (float)$row['total_terbayar'];
        $nHutang = (float)$row['sisa_hutang'];

        $grandTotalPo += $nPo;
        $grandTotalFaktur += $nFak;
        $grandTotalTerbayar += $nBayar;
        $grandTotalHutang += $nHutang;

        $itemData = [
            'id_po' => (int)$row['id_po'],
            'nomor_po' => $row['nomor_po'],
            'tanggal_po' => $row['tanggal_po'],
            'formatted_tanggal_po' => !empty($row['tanggal_po']) ? date('d/m/Y', strtotime($row['tanggal_po'])) : '-',
            'status_po' => $row['status_po'],
            'id_vendor' => $vId,
            'kode_vendor' => $row['kode_vendor'],
            'nama_vendor' => $row['nama_vendor'],
            'id_faktur' => $row['id_faktur'] ? (int)$row['id_faktur'] : null,
            'nomor_faktur' => $row['nomor_faktur'],
            'tanggal_faktur_vendor' => $row['tanggal_faktur_vendor'],
            'formatted_tanggal_faktur' => !empty($row['tanggal_faktur_vendor']) ? date('d/m/Y', strtotime($row['tanggal_faktur_vendor'])) : '-',
            'tanggal_jatuh_tempo' => $row['tanggal_jatuh_tempo'],
            'formatted_jatuh_tempo' => !empty($row['tanggal_jatuh_tempo']) ? date('d/m/Y', strtotime($row['tanggal_jatuh_tempo'])) : '-',
            'nilai_po' => $nPo,
            'total_faktur' => $nFak,
            'total_terbayar' => $nBayar,
            'sisa_hutang' => $nHutang,
            'formatted_nilai_po' => number_format($nPo, 0, ',', '.'),
            'formatted_total_faktur' => $nFak > 0 ? number_format($nFak, 0, ',', '.') : '-',
            'formatted_total_terbayar' => number_format($nBayar, 0, ',', '.'),
            'formatted_sisa_hutang' => number_format($nHutang, 0, ',', '.'),
            'status_faktur_ket' => $row['status_faktur_ket'],
            'status_faktur' => $row['status_faktur']
        ];

        $items[] = $itemData;

        // Grouping per vendor
        if (!isset($groupedVendors[$vId])) {
            $groupedVendors[$vId] = [
                'id_vendor' => $vId,
                'kode_vendor' => $row['kode_vendor'],
                'nama_vendor' => $row['nama_vendor'],
                'total_po' => 0,
                'total_faktur' => 0,
                'total_terbayar' => 0,
                'sisa_hutang' => 0,
                'count_sudah_faktur' => 0,
                'count_belum_faktur' => 0,
                'items' => [],
                'payments' => []
            ];
        }

        $groupedVendors[$vId]['total_po'] += $nPo;
        $groupedVendors[$vId]['total_faktur'] += $nFak;
        $groupedVendors[$vId]['total_terbayar'] += $nBayar;
        $groupedVendors[$vId]['sisa_hutang'] += $nHutang;

        if ($row['id_faktur']) {
            $groupedVendors[$vId]['count_sudah_faktur']++;
        } else {
            $groupedVendors[$vId]['count_belum_faktur']++;
        }

        $groupedVendors[$vId]['items'][] = $itemData;
    }
    $stmt->close();

    // AMBIL RIWAYAT PEMBAYARAN UNTUK VENDOR-VENDOR YANG ADA
    $vendorIds = array_keys($groupedVendors);
    if (!empty($vendorIds)) {
        $inPlaceholders = implode(',', array_fill(0, count($vendorIds), '?'));
        $inTypes = str_repeat('i', count($vendorIds));
        
        $sqlPay = "SELECT 
                    ppd.id_pembayaran_detail,
                    ppd.kode_pembayaran,
                    ppd.tanggal_bayar,
                    ppd.bank_pengirim,
                    ppd.norek_pengirim,
                    ppd.bank_tujuan,
                    ppd.norek_tujuan,
                    ppd.an_pengiriman,
                    ppd.nominal_pengiriman,
                    ppd.biaya_admin,
                    ppd.no_ref,
                    ppd.keterangan,
                    fp.id_vendor,
                    fp.nomor_faktur,
                    po.nomor_po
                   FROM payment_purchase_detail ppd
                   INNER JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
                   INNER JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
                   INNER JOIN purchase_order po ON fp.id_po = po.id_po
                   WHERE fp.id_vendor IN ({$inPlaceholders})
                   ORDER BY ppd.tanggal_bayar DESC";
        
        $stmtPay = $conn->prepare($sqlPay);
        $stmtPay->bind_param($inTypes, ...$vendorIds);
        $stmtPay->execute();
        $resPay = $stmtPay->get_result();

        while ($pRow = $resPay->fetch_assoc()) {
            $pVid = (int)$pRow['id_vendor'];
            if (isset($groupedVendors[$pVid])) {
                $pRow['formatted_tanggal_bayar'] = !empty($pRow['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($pRow['tanggal_bayar'])) : '-';
                $pRow['formatted_nominal'] = number_format((float)$pRow['nominal_pengiriman'], 0, ',', '.');
                $pRow['formatted_biaya_admin'] = number_format((float)$pRow['biaya_admin'], 0, ',', '.');
                $groupedVendors[$pVid]['payments'][] = $pRow;
            }
        }
        $stmtPay->close();
    }

    // Format final summary vendor
    $vendorsSummary = [];
    foreach ($groupedVendors as $vId => $v) {
        if ($v['count_belum_faktur'] === 0) {
            $v['keterangan'] = 'Sudah Difakturkan';
        } elseif ($v['count_sudah_faktur'] === 0) {
            $v['keterangan'] = 'Belum Difakturkan';
        } else {
            $v['keterangan'] = 'Sebagian Difakturkan';
        }

        $v['formatted_total_po'] = number_format($v['total_po'], 0, ',', '.');
        $v['formatted_total_faktur'] = number_format($v['total_faktur'], 0, ',', '.');
        $v['formatted_total_terbayar'] = number_format($v['total_terbayar'], 0, ',', '.');
        $v['formatted_sisa_hutang'] = number_format($v['sisa_hutang'], 0, ',', '.');
        $vendorsSummary[] = $v;
    }

    // DAFTAR VENDOR UNTUK DROPDOWN
    $vendorsList = [];
    $resV = $conn->query("SELECT DISTINCT v.id_vendor, v.kode_vendor, v.nama_perusahaan 
                          FROM purchase_order po
                          INNER JOIN vendor v ON po.id_vendor = v.id_vendor
                          WHERE po.status NOT IN ('DRAFT', 'BATAL')
                          ORDER BY v.nama_perusahaan ASC");
    if ($resV) {
        while ($v = $resV->fetch_assoc()) {
            $vendorsList[] = [
                'id_vendor' => (int)$v['id_vendor'],
                'kode_vendor' => $v['kode_vendor'],
                'nama_perusahaan' => $v['nama_perusahaan']
            ];
        }
    }

    sendJson(true, 'Laporan Hutang Vendor berhasil dimuat.', [
        'filters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'id_vendor' => $idVendor,
            'status_faktur' => $statusFakturFilter,
            'search' => $search
        ],
        'vendors_list' => $vendorsList,
        'summary_vendor' => $vendorsSummary,
        'items' => $items,
        'grand_total' => [
            'total_nilai_po' => $grandTotalPo,
            'total_faktur' => $grandTotalFaktur,
            'total_terbayar' => $grandTotalTerbayar,
            'total_sisa_hutang' => $grandTotalHutang,
            'formatted_nilai_po' => number_format($grandTotalPo, 0, ',', '.'),
            'formatted_total_faktur' => number_format($grandTotalFaktur, 0, ',', '.'),
            'formatted_total_terbayar' => number_format($grandTotalTerbayar, 0, ',', '.'),
            'formatted_sisa_hutang' => 'Rp ' . number_format($grandTotalHutang, 0, ',', '.')
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Gagal memuat laporan hutang vendor: ' . $e->getMessage(), null, 500);
}
