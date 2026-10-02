<?php
/**
 * API Purchase Order: Monitoring PO Outstanding & Delivery Aging Endpoint
 * Path: api/purchase_order/outstanding.php
 * Access: Khusus Role PURCHASING, ADMIN, dan MANAGER
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

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();

// Pembatasan Akses: Hanya Purchasing, Admin, dan Manager
$allowedRoles = [ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER];
if (!in_array($currentUser['role'], $allowedRoles, true)) {
    jsonResponse(false, 'Akses ditolak. Modul PO Outstanding hanya dapat diakses oleh Purchasing dan Manajemen.', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
}

// -------------------------------------------------------------
// FILTER & PAGINATION PARAMETERS
// -------------------------------------------------------------
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$limit = max(1, min(100, isset($_GET['limit']) ? (int)$_GET['limit'] : 20));
$offset = ($page - 1) * $limit;

$search = trim($_GET['q'] ?? '');
$siteId = isset($_GET['site_id']) && is_numeric($_GET['site_id']) ? (int)$_GET['site_id'] : null;
$vendorId = isset($_GET['vendor_id']) && is_numeric($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
$agingFilter = strtoupper(trim($_GET['aging_status'] ?? '')); // OVERDUE, DUE_TODAY, ON_SCHEDULE

// Kondisi dasar: PO yang belum selesai diterima dan belum dibatalkan
$whereClauses = ["p.status NOT IN ('DITERIMA', 'BATAL')"];
$params = [];
$types = '';

if (!empty($search)) {
    $whereClauses[] = "(p.nomor_po LIKE ? OR v.nama_perusahaan LIKE ? OR ro.nomor LIKE ? OR p.keterangan LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if ($siteId) {
    $whereClauses[] = "p.id_site = ?";
    $params[] = $siteId;
    $types .= 'i';
}

if ($vendorId) {
    $whereClauses[] = "p.id_vendor = ?";
    $params[] = $vendorId;
    $types .= 'i';
}

$whereSql = implode(' AND ', $whereClauses);

// -------------------------------------------------------------
// 1. HITUNG METRIK GLOBAL (PO OUTSTANDING, OVERDUE, DUE TODAY, KOMITMEN RP)
// -------------------------------------------------------------
$metricSql = "SELECT p.id_po, p.status, p.tanggal_po, p.tanggal_pengiriman, p.diskon, p.pajak, p.total_termasuk_pajak,
                     DATEDIFF(DATE(p.tanggal_pengiriman), CURRENT_DATE()) as diff_days,
                     COALESCE((SELECT SUM(subtotal) FROM purchase_order_detail WHERE id_po = p.id_po), 0) as subtotal_barang
              FROM purchase_order p
              LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
              LEFT JOIN request_order ro ON ro.id_po = p.id_po
              WHERE p.status NOT IN ('DITERIMA', 'BATAL')";

$metricRes = $conn->query($metricSql);

$metricTotalOutstanding = 0;
$metricTotalOverdue = 0;
$metricTotalDueToday = 0;
$metricTotalOnSchedule = 0;
$metricTotalNominalKomitmen = 0;

if ($metricRes) {
    while ($m = $metricRes->fetch_assoc()) {
        $metricTotalOutstanding++;
        
        // Overdue dan Due Today hanya berlaku jika PO sudah DIPROSES VENDOR dan ada tanggal pengiriman
        if ($m['status'] === 'DIPROSES VENDOR' && !empty($m['tanggal_pengiriman'])) {
            $diff = (int)$m['diff_days'];
            if ($diff < 0) {
                $metricTotalOverdue++;
            } elseif ($diff === 0) {
                $metricTotalDueToday++;
            } else {
                $metricTotalOnSchedule++;
            }
        }

        // Kalkulasi Grand Total per PO
        $subtotal = (float)$m['subtotal_barang'];
        $diskon = (float)$m['diskon'];
        $dpp = max(0, $subtotal - $diskon);
        $ratePajak = (float)$m['pajak'];
        $isInclusive = ((int)$m['total_termasuk_pajak'] === 1);

        $grandTotal = $dpp;
        if ($ratePajak > 0 && !$isInclusive) {
            $grandTotal += ($dpp * ($ratePajak / 100));
        }

        $metricTotalNominalKomitmen += $grandTotal;
    }
}

// -------------------------------------------------------------
// 2. QUERY DAFTAR PO OUTSTANDING
// -------------------------------------------------------------
$sql = "SELECT p.*, 
               COALESCE(v.nama_perusahaan, 'Vendor Belum Ditentukan') as nama_vendor,
               COALESCE(v.no_telepon, '-') as telepon_vendor,
               COALESCE(v.email, '-') as email_vendor,
               COALESCE(s.nama_site, '-') as nama_site,
               COALESCE(s.alamat, '-') as alamat_site,
               COALESCE(k.nama_karyawan, u.nama_users, 'Staff Purchasing') as nama_pembuat,
               ro.id_request, 
               ro.nomor as nomor_ro,
               DATEDIFF(DATE(p.tanggal_pengiriman), CURRENT_DATE()) as diff_days,
               COALESCE((SELECT SUM(subtotal) FROM purchase_order_detail WHERE id_po = p.id_po), 0) as subtotal_barang,
               COALESCE((SELECT SUM(qty) FROM purchase_order_detail WHERE id_po = p.id_po), 0) as total_qty,
               COALESCE((SELECT COUNT(id_po_detail) FROM purchase_order_detail WHERE id_po = p.id_po), 0) as total_items
        FROM purchase_order p
        LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
        LEFT JOIN site s ON p.id_site = s.id_site
        LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan
        LEFT JOIN users u ON p.id_karyawan = u.id_users
        LEFT JOIN request_order ro ON ro.id_po = p.id_po
        WHERE $whereSql
        ORDER BY 
            CASE 
                WHEN p.status = 'DIPROSES VENDOR' AND p.tanggal_pengiriman IS NOT NULL AND DATEDIFF(DATE(p.tanggal_pengiriman), CURRENT_DATE()) < 0 THEN 1 
                WHEN p.status = 'DIPROSES VENDOR' AND p.tanggal_pengiriman IS NOT NULL AND DATEDIFF(DATE(p.tanggal_pengiriman), CURRENT_DATE()) = 0 THEN 2
                WHEN p.status = 'DIPROSES VENDOR' THEN 3
                ELSE 4 
            END ASC,
            p.tanggal_po DESC,
            p.id_po DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $statusPO = $row['status'];
    $tglKirim = $row['tanggal_pengiriman'];
    $diffDays = ($row['diff_days'] !== null) ? (int)$row['diff_days'] : null;
    
    // Tentukan status waktu pengiriman / aging
    if ($statusPO !== 'DIPROSES VENDOR') {
        if ($statusPO === 'DISETUJUI INTERNAL') {
            $agingStatus = 'BELUM_PROSES_VENDOR';
            $agingLabel = 'Belum Diproses Vendor';
            $agingBadgeClass = 'bg-secondary text-white';
        } elseif ($statusPO === 'REVIEW INTERNAL') {
            $agingStatus = 'REVIEW_INTERNAL';
            $agingLabel = 'Review Internal';
            $agingBadgeClass = 'bg-warning text-dark';
        } elseif ($statusPO === 'REVIEW VENDOR') {
            $agingStatus = 'REVIEW_VENDOR';
            $agingLabel = 'Review Vendor';
            $agingBadgeClass = 'bg-info text-white';
        } else {
            $agingStatus = 'PENDING';
            $agingLabel = $statusPO;
            $agingBadgeClass = 'bg-secondary text-white';
        }
    } else {
        // Status sudah DIPROSES VENDOR
        if (empty($tglKirim)) {
            $agingStatus = 'DIPROSES_VENDOR';
            $agingLabel = 'Diproses Vendor';
            $agingBadgeClass = 'bg-info text-white';
        } else {
            $diffDays = (int)$row['diff_days'];
            if ($diffDays < 0) {
                $agingStatus = 'OVERDUE';
                $agingLabel = 'Terlambat ' . abs($diffDays) . ' Hari';
                $agingBadgeClass = 'bg-danger text-white';
            } elseif ($diffDays === 0) {
                $agingStatus = 'DUE_TODAY';
                $agingLabel = 'Tiba Hari Ini';
                $agingBadgeClass = 'bg-warning text-dark';
            } else {
                $agingStatus = 'ON_SCHEDULE';
                $agingLabel = 'Sisa ' . $diffDays . ' Hari';
                $agingBadgeClass = 'bg-success text-white';
            }
        }
    }

    // Filter status waktu jika diminta oleh frontend
    if (!empty($agingFilter) && $agingFilter !== 'ALL' && $agingStatus !== $agingFilter) {
        continue;
    }

    // Kalkulasi Finansial
    $subtotal = (float)$row['subtotal_barang'];
    $diskon = (float)$row['diskon'];
    $dasarSetelahDiskon = max(0, $subtotal - $diskon);

    $ratePpn = (float)($row['pajak'] ?? 0);
    $isPpnInclusive = ((int)($row['total_termasuk_pajak'] ?? 0) === 1);

    $ratePpnbm = (float)($row['pajak_PPnBM'] ?? 0);
    $isPpnbmInclusive = ((int)($row['total_termasuk_PPnBM'] ?? 0) === 1);

    $divisor = 1.0;
    if ($isPpnbmInclusive && $ratePpnbm > 0) {
        $divisor += ($ratePpnbm / 100);
    }
    if ($isPpnInclusive && $ratePpn > 0) {
        $divisor += ($ratePpn / 100);
    }

    $dpp = $dasarSetelahDiskon / $divisor;
    $nominalPpnbm = ($ratePpnbm > 0) ? ($dpp * ($ratePpnbm / 100)) : 0;
    $nominalPpn = ($ratePpn > 0) ? ($dpp * ($ratePpn / 100)) : 0;

    if ($divisor > 1.0) {
        $grandTotal = $dpp + ($ratePpnbm > 0 ? $nominalPpnbm : 0) + ($ratePpn > 0 ? $nominalPpn : 0);
    } else {
        $grandTotal = $dpp + $nominalPpnbm + $nominalPpn;
    }

    $items[] = [
        'id_po' => (int)$row['id_po'],
        'nomor_po' => $row['nomor_po'],
        'tanggal_po' => $row['tanggal_po'],
        'tanggal_pengiriman' => $row['tanggal_pengiriman'] ? date('Y-m-d', strtotime($row['tanggal_pengiriman'])) : null,
        'pengiriman' => $row['pengiriman'] ?: 'Vendor',
        'prioritas' => $row['prioritas'] ?: 'NORMAL',
        'status' => $row['status'],
        'term_of_payment' => (int)$row['term_of_payment'],
        'alamat' => $row['alamat'],
        'keterangan' => $row['keterangan'],
        'id_vendor' => (int)$row['id_vendor'],
        'nama_vendor' => $row['nama_vendor'],
        'telepon_vendor' => $row['telepon_vendor'],
        'email_vendor' => $row['email_vendor'],
        'id_site' => (int)$row['id_site'],
        'nama_site' => $row['nama_site'],
        'alamat_site' => $row['alamat_site'],
        'nama_pembuat' => $row['nama_pembuat'],
        'id_request' => $row['id_request'] ? (int)$row['id_request'] : null,
        'nomor_ro' => $row['nomor_ro'] ?: '-',
        'total_items' => (int)$row['total_items'],
        'total_qty' => (float)$row['total_qty'],
        'subtotal' => $subtotal,
        'diskon' => $diskon,
        'dpp' => $dpp,
        'rate_pajak' => $ratePpn,
        'nominal_pajak' => $nominalPpn,
        'grand_total' => $grandTotal,
        'diff_days' => $diffDays,
        'aging_status' => $agingStatus,
        'aging_label' => $agingLabel,
        'aging_badge_class' => $agingBadgeClass
    ];
}
$stmt->close();

// Pagination slice setelah filter in-memory aging
$totalFilteredItems = count($items);
$pagedItems = array_slice($items, $offset, $limit);
$totalPages = ceil($totalFilteredItems / $limit);

jsonResponse(true, 'Data PO Outstanding berhasil dimuat.', [
    'items' => $pagedItems,
    'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total_items' => $totalFilteredItems,
        'total_pages' => max(1, $totalPages)
    ],
    'metrics' => [
        'total_outstanding' => $metricTotalOutstanding,
        'total_nominal_komitmen' => $metricTotalNominalKomitmen,
        'total_overdue' => $metricTotalOverdue,
        'total_due_today' => $metricTotalDueToday,
        'total_on_schedule' => $metricTotalOnSchedule
    ]
]);
