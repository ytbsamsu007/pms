<?php
/**
 * API Purchase Order: CRUD / List Endpoint - PT Jaya Teknis
 * Path: api/purchase_order/index.php
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
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    // 1. Single PO Detail
    if ($id) {
        $stmt = $conn->prepare("SELECT p.*, 
                                       COALESCE(v.nama_perusahaan, '') as nama_vendor,
                                       v.kode_vendor,
                                       COALESCE(v.no_telepon, '') as telepon_vendor,
                                       v.email as email_vendor,
                                       v.alamat as alamat_vendor,
                                       s.nama_site, s.kode_site, s.alamat as alamat_site,
                                       COALESCE(k.nama_karyawan, u.nama_users, 'Staff Purchasing') as nama_pembuat,
                                       jk.nama_jabatan as jabatan_pembuat,
                                       dk.nama_divisi as divisi_pembuat,
                                       ka.nama_karyawan as nama_approver,
                                       jka.nama_jabatan as jabatan_approver,
                                       dka.nama_divisi as divisi_approver,
                                       ro.id_request, ro.nomor as nomor_ro
                                FROM purchase_order p
                                LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
                                LEFT JOIN site s ON p.id_site = s.id_site
                                LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan
                                LEFT JOIN jabatan jk ON k.id_jabatan = jk.id_jabatan
                                LEFT JOIN divisi dk ON k.id_divisi = dk.id_divisi
                                LEFT JOIN users u ON p.id_karyawan = u.id_users
                                LEFT JOIN karyawan ka ON p.id_karyawan_approved = ka.id_karyawan
                                LEFT JOIN jabatan jka ON ka.id_jabatan = jka.id_jabatan
                                LEFT JOIN divisi dka ON ka.id_divisi = dka.id_divisi
                                LEFT JOIN request_order ro ON ro.id_po = p.id_po
                                WHERE p.id_po = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $po = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$po) {
            jsonResponse(false, 'Purchase Order tidak ditemukan.', null, 404);
        }

        // Ambil detail item
        $stmtItems = $conn->prepare("SELECT pd.*, b.kode_barang, b.nama_barang, b.satuan,
                                            b.PPnBM, b.rate_PPnBM, b.foto1, b.foto2,
                                            kat.nama_kategori, mrk.nama_merk
                                     FROM purchase_order_detail pd
                                     LEFT JOIN barang b ON pd.id_barang = b.id_barang
                                     LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
                                     LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
                                     WHERE pd.id_po = ?
                                     ORDER BY pd.id_po_detail ASC");
        $stmtItems->bind_param("i", $id);
        $stmtItems->execute();
        $itemsRes = $stmtItems->get_result();
        
        $items = [];
        $subtotalBarang = 0;
        while ($item = $itemsRes->fetch_assoc()) {
            $subtotalBarang += (float)$item['subtotal'];
            $items[] = $item;
        }
        $stmtItems->close();

        $diskonPo = (float)($po['diskon'] ?? 0);
        $dasarSetelahDiskon = max(0, $subtotalBarang - $diskonPo);

        $ratePpn = (float)($po['pajak'] ?? 0);
        $isPpnInclusive = ((int)($po['total_termasuk_pajak'] ?? 0) === 1);

        $ratePpnbm = (float)($po['pajak_PPnBM'] ?? 0);
        $isPpnbmInclusive = ((int)($po['total_termasuk_PPnBM'] ?? 0) === 1);

        // Hitung pembagi untuk inklusif
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

        $po['items'] = $items;
        $po['total_item'] = count($items);
        $po['subtotal_barang'] = $subtotalBarang;
        $po['nominal_diskon'] = $diskonPo;
        $po['dpp'] = $dpp;
        $po['rate_pajak'] = $ratePpn;
        $po['nominal_pajak'] = $nominalPpn;
        $po['rate_ppnbm'] = $ratePpnbm;
        $po['nominal_ppnbm'] = $nominalPpnbm;
        $po['grand_total'] = $grandTotal;
        $po['total_termasuk_pajak'] = $isPpnInclusive ? 1 : 0;
        $po['total_termasuk_PPnBM'] = $isPpnbmInclusive ? 1 : 0;

        jsonResponse(true, 'Detail Purchase Order berhasil diambil.', $po);
    }

    // 2. List PO & Global Metrics
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $siteId = isset($_GET['site_id']) && is_numeric($_GET['site_id']) ? (int)$_GET['site_id'] : null;
    $vendorId = isset($_GET['vendor_id']) && is_numeric($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
    $status = trim($_GET['status'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');

    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (p.nomor_po LIKE ? OR v.nama_perusahaan LIKE ? OR k.nama_karyawan LIKE ? OR p.keterangan LIKE ?)";
        $wildcard = "%" . $search . "%";
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
        $types .= "ssss";
    }

    if ($siteId) {
        $whereSql .= " AND p.id_site = ?";
        $params[] = $siteId;
        $types .= "i";
    }

    if ($vendorId) {
        $whereSql .= " AND p.id_vendor = ?";
        $params[] = $vendorId;
        $types .= "i";
    }

    if (!empty($status)) {
        $whereSql .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if (!empty($startDate) && !empty($endDate)) {
        $whereSql .= " AND DATE(p.tanggal_po) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= "ss";
    }

    // Count Total Filtered
    $countSql = "SELECT COUNT(*) as total FROM purchase_order p 
                 LEFT JOIN vendor v ON p.id_vendor = v.id_vendor 
                 LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    // Global Metrics (Unfiltered summary)
    $metrics = [
        'total_po' => 0,
        'total_draft' => 0,
        'total_review_internal' => 0,
        'total_disetujui_internal' => 0,
        'total_diproses_vendor' => 0,
        'total_diterima' => 0,
        'total_batal' => 0,
        'total_nominal' => 0
    ];

    $metricRes = $conn->query("SELECT 
        COUNT(*) as total_po,
        SUM(CASE WHEN status = 'DRAFT' THEN 1 ELSE 0 END) as total_draft,
        SUM(CASE WHEN status = 'REVIEW INTERNAL' THEN 1 ELSE 0 END) as total_review_internal,
        SUM(CASE WHEN status = 'DISETUJUI INTERNAL' THEN 1 ELSE 0 END) as total_disetujui_internal,
        SUM(CASE WHEN status = 'DIPROSES VENDOR' THEN 1 ELSE 0 END) as total_diproses_vendor,
        SUM(CASE WHEN status = 'DITERIMA' THEN 1 ELSE 0 END) as total_diterima,
        SUM(CASE WHEN status = 'BATAL' THEN 1 ELSE 0 END) as total_batal
    FROM purchase_order");
    if ($metricRes && $mRow = $metricRes->fetch_assoc()) {
        $metrics['total_po'] = (int)($mRow['total_po'] ?? 0);
        $metrics['total_draft'] = (int)($mRow['total_draft'] ?? 0);
        $metrics['total_review_internal'] = (int)($mRow['total_review_internal'] ?? 0);
        $metrics['total_disetujui_internal'] = (int)($mRow['total_disetujui_internal'] ?? 0);
        $metrics['total_diproses_vendor'] = (int)($mRow['total_diproses_vendor'] ?? 0);
        $metrics['total_diterima'] = (int)($mRow['total_diterima'] ?? 0);
        $metrics['total_batal'] = (int)($mRow['total_batal'] ?? 0);
    }

    $sumNominalRes = $conn->query("SELECT COALESCE(SUM(subtotal), 0) as total_nominal FROM purchase_order_detail");
    if ($sumNominalRes && $snRow = $sumNominalRes->fetch_assoc()) {
        $metrics['total_nominal'] = (float)($snRow['total_nominal'] ?? 0);
    }

    // Fetch Data
    $sql = "SELECT p.*, 
                   COALESCE(v.nama_perusahaan, '') as nama_vendor,
                   v.kode_vendor,
                   s.nama_site, s.kode_site,
                   COALESCE(k.nama_karyawan, u.nama_users, 'Staff Purchasing') as nama_pembuat,
                   ro.id_request, ro.nomor as nomor_ro,
                   (SELECT COUNT(*) FROM purchase_order_detail pd WHERE pd.id_po = p.id_po) as total_item,
                   (SELECT COALESCE(SUM(pd.subtotal), 0) FROM purchase_order_detail pd WHERE pd.id_po = p.id_po) as subtotal_barang
            FROM purchase_order p
            LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
            LEFT JOIN site s ON p.id_site = s.id_site
            LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan
            LEFT JOIN users u ON p.id_karyawan = u.id_users
            LEFT JOIN request_order ro ON ro.id_po = p.id_po
            $whereSql
            ORDER BY p.tanggal_po DESC, p.id_po DESC
            LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    if (!empty($paramsWithLimit)) {
        $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $subtotal = (float)($row['subtotal_barang'] ?? 0);
        $diskon = (float)($row['diskon'] ?? 0);
        $dasarSetelahDiskon = max(0, $subtotal - $diskon);

        $ratePpn = (float)($row['pajak'] ?? 0);
        $isPpnInclusive = ((int)($row['total_termasuk_pajak'] ?? 0) === 1);

        $ratePpnbm = (float)($row['pajak_PPnBM'] ?? 0);
        $isPpnbmInclusive = ((int)($row['total_termasuk_PPnBM'] ?? 0) === 1);

        // Hitung pembagi untuk inklusif
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

        $row['grand_total'] = $grandTotal;
        $row['grand_total_formatted'] = number_format($grandTotal, 0, ',', '.');
        $items[] = $row;
    }
    $stmt->close();

    jsonResponse(true, 'Daftar Purchase Order berhasil dimuat.', [
        'items' => $items,
        'metrics' => $metrics,
        'pagination' => [
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);
}

