<?php
/**
 * API Receiving: List & Detail Penerimaan Barang (Logistik)
 * Path: api/receiving/index.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 * Database: receiving_order & receiving_order_detail
 * PERHATIAN: TIDAK MENAMPILKAN INFO HARGA / SUBTOTAL / PAJAK
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

$currentUser = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    // 1. DETAIL SINGLE RECEIVING ORDER
    if ($id) {
        $stmt = $conn->prepare("SELECT ro.id_rcv, ro.nomor_rcv, ro.nomor_sj, ro.tanggal_rcv, ro.tanggal_diterima,
                                       ro.file_sj, ro.keterangan as catatan_rcv, ro.status as status_rcv,
                                       ro.print, ro.print_date,
                                       po.id_po, po.nomor_po, po.tanggal_po,
                                       v.id_vendor, v.kode_vendor, v.nama_perusahaan as nama_vendor, v.no_telepon as telepon_vendor, v.alamat as alamat_vendor,
                                       s.id_site, s.nama_site, s.kode_site, s.alamat as alamat_site,
                                       k.nama_karyawan as nama_penerima,
                                       jk.nama_jabatan as jabatan_penerima,
                                       dk.nama_divisi as divisi_penerima
                                FROM receiving_order ro
                                LEFT JOIN purchase_order po ON ro.id_po = po.id_po
                                LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
                                LEFT JOIN site s ON po.id_site = s.id_site
                                LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                                LEFT JOIN jabatan jk ON k.id_jabatan = jk.id_jabatan
                                LEFT JOIN divisi dk ON k.id_divisi = dk.id_divisi
                                WHERE ro.id_rcv = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $rcv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$rcv) {
            jsonResponse(false, 'Data Penerimaan Barang (Receiving Order) tidak ditemukan.', null, 404);
        }

        // Ambil detail item fisik dari receiving_order_detail & purchase_order_detail (TANPA HARGA)
        $stmtItems = $conn->prepare("SELECT rod.id_rcv_detail, rod.id_rcv, rod.id_barang, rod.qty as qty_diterima, 
                                            rod.status_qc, rod.keterangan as keterangan_item,
                                            b.kode_barang, b.nama_barang, b.satuan,
                                            kat.nama_kategori, mrk.nama_merk,
                                            pod.qty as qty_po
                                     FROM receiving_order_detail rod
                                     LEFT JOIN barang b ON rod.id_barang = b.id_barang
                                     LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
                                     LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
                                     LEFT JOIN purchase_order_detail pod ON (pod.id_po = ? AND pod.id_barang = rod.id_barang)
                                     WHERE rod.id_rcv = ?
                                     ORDER BY rod.id_rcv_detail ASC");
        $idPo = (int)$rcv['id_po'];
        $stmtItems->bind_param("ii", $idPo, $id);
        $stmtItems->execute();
        $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtItems->close();

        $rcv['items'] = $items;
        $rcv['total_item'] = count($items);

        jsonResponse(true, 'Detail Penerimaan Barang berhasil dimuat.', $rcv);
    }

    // 2. LIST RECEIVING ORDER DENGAN FILTER & PAGINATION
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $siteId = isset($_GET['site_id']) && is_numeric($_GET['site_id']) ? (int)$_GET['site_id'] : '';

    $where = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($search)) {
        $where[] = "(ro.nomor_rcv LIKE ? OR ro.nomor_sj LIKE ? OR po.nomor_po LIKE ? OR v.nama_perusahaan LIKE ?)";
        $searchWild = "%{$search}%";
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
        $params[] = $searchWild;
        $types .= "ssss";
    }

    if (!empty($siteId)) {
        $where[] = "po.id_site = ?";
        $params[] = $siteId;
        $types .= "i";
    }

    $whereClause = implode(" AND ", $where);

    // Total Records
    $countSql = "SELECT COUNT(*) as total 
                 FROM receiving_order ro 
                 LEFT JOIN purchase_order po ON ro.id_po = po.id_po
                 LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
                 WHERE {$whereClause}";
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)$stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    // Query Data List
    $sql = "SELECT ro.id_rcv, ro.nomor_rcv, ro.nomor_sj, ro.tanggal_rcv, ro.tanggal_diterima, ro.status,
                   ro.file_sj, ro.print, ro.print_date,
                   po.id_po, po.nomor_po,
                   v.id_vendor, v.nama_perusahaan as nama_vendor,
                   s.id_site, s.nama_site, s.kode_site,
                   k.nama_karyawan as nama_penerima,
                   (SELECT COUNT(*) FROM receiving_order_detail rod WHERE rod.id_rcv = ro.id_rcv) as total_item,
                   (SELECT COALESCE(SUM(rod.qty), 0) FROM receiving_order_detail rod WHERE rod.id_rcv = ro.id_rcv) as total_qty_diterima
            FROM receiving_order ro
            LEFT JOIN purchase_order po ON ro.id_po = po.id_po
            LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
            LEFT JOIN site s ON po.id_site = s.id_site
            LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
            WHERE {$whereClause}
            ORDER BY ro.id_rcv DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmtList = $conn->prepare($sql);
    $stmtList->bind_param($types, ...$params);
    $stmtList->execute();
    $list = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtList->close();

    jsonResponse(true, 'Data Penerimaan Barang berhasil diambil.', [
        'items' => $list,
        'pagination' => [
            'total_records' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit),
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);
}

jsonResponse(false, 'Metode tidak didukung.', null, 405);
