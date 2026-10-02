<?php
/**
 * REST API: Laporan Barang Keluar (Mutasi Keluar Antar-Site)
 * Path: api/laporan/barang_keluar.php
 * Khusus Role: ADMIN, LOGISTIK, MANAGER, PURCHASING
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
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER, ROLE_PURCHASING]);

try {
    $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
    $idSiteAsal = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');

    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 25;
    if ($limit < 0) $limit = 25;

    // Filter kondisi mutasi barang keluar: hanya menghitung status yang sudah diterima (DITERIMA SITE TUJUAN)
    $where = ["mo.status = 'DITERIMA SITE TUJUAN'"];
    $params = [];
    $types = "";

    if ($idSiteAsal > 0) {
        $where[] = "mo.id_site_asal = ?";
        $params[] = $idSiteAsal;
        $types .= "i";
    }

    if (!empty($startDate)) {
        $where[] = "DATE(mo.tanggal_mutasi) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where[] = "DATE(mo.tanggal_mutasi) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($search)) {
        $where[] = "(
            b.kode_barang LIKE ? OR 
            b.nama_barang LIKE ? OR 
            b.serial_number LIKE ? OR
            mo.kode_mutasi LIKE ? OR 
            mo.nomor_surat_mutasi LIKE ? OR 
            sa.nama_site LIKE ? OR 
            st.nama_site LIKE ? OR 
            kr.nama_karyawan LIKE ?
        )";
        $wildcard = "%$search%";
        for ($i = 0; $i < 8; $i++) {
            $params[] = $wildcard;
            $types .= "s";
        }
    }

    $whereSql = implode(" AND ", $where);

    // Ambil Data Sites untuk Filter Dropdown
    $sites = [];
    $resSites = $conn->query("SELECT id_site, kode_site, nama_site, jenis_site FROM site ORDER BY nama_site ASC");
    while ($s = $resSites->fetch_assoc()) {
        $sites[] = $s;
    }

    // Query Utama: Rincian Barang Keluar per Mutasi Order Detail
    $sql = "
        SELECT 
            modt.id_mutasi_detail,
            modt.id_mutasi,
            modt.id_barang,
            modt.qty,
            b.kode_barang,
            b.nama_barang,
            b.satuan,
            b.serial_number,
            b.deskripsi,
            mo.kode_mutasi,
            mo.nomor_surat_mutasi,
            mo.tanggal_mutasi,
            mo.status AS status_mutasi,
            mo.keterangan AS catatan_mutasi,
            sa.id_site AS id_site_asal,
            sa.nama_site AS nama_site_asal,
            st.id_site AS id_site_tujuan,
            st.nama_site AS nama_site_tujuan,
            kr.nama_karyawan AS nama_pemohon,
            kp.nama_karyawan AS nama_petugas
        FROM mutasi_order_detail modt
        INNER JOIN mutasi_order mo ON modt.id_mutasi = mo.id_mutasi
        INNER JOIN barang b ON modt.id_barang = b.id_barang
        LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
        LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
        LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
        LEFT JOIN karyawan kp ON mo.id_karyawan = kp.id_karyawan
        WHERE $whereSql
        ORDER BY mo.tanggal_mutasi DESC, modt.id_mutasi_detail DESC
    ";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $allItems = [];
    $totalQtyKeluar = 0;
    $distinctBarang = [];
    $distinctMutasi = [];

    while ($row = $res->fetch_assoc()) {
        $qtyNum = (float)$row['qty'];
        $totalQtyKeluar += $qtyNum;
        $distinctBarang[$row['id_barang']] = true;
        $distinctMutasi[$row['id_mutasi']] = true;

        $row['qty_formatted'] = number_format($qtyNum, 0, ',', '.');
        $row['tanggal_formatted'] = $row['tanggal_mutasi'] ? date('d-m-Y', strtotime($row['tanggal_mutasi'])) : '-';
        $row['waktu_formatted'] = $row['tanggal_mutasi'] ? date('H:i', strtotime($row['tanggal_mutasi'])) : '-';

        $allItems[] = $row;
    }
    $stmt->close();

    // Pagination Calculation & Slicing
    $totalRecords = count($allItems);
    $totalPages = ($limit > 0) ? (int)ceil($totalRecords / $limit) : 1;
    if ($totalPages < 1) $totalPages = 1;
    if ($page > $totalPages) $page = $totalPages;

    $offset = ($limit > 0) ? ($page - 1) * $limit : 0;
    $pagedItems = ($limit > 0) ? array_slice($allItems, $offset, $limit) : $allItems;

    $from = $totalRecords > 0 ? ($offset + 1) : 0;
    $to = ($limit > 0) ? min($offset + $limit, $totalRecords) : $totalRecords;

    echo json_encode([
        'success' => true,
        'message' => 'Laporan barang keluar berhasil dimuat.',
        'data' => [
            'items' => $pagedItems,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'from' => $from,
                'to' => $to
            ],
            'metrics' => [
                'total_baris' => $totalRecords,
                'total_qty' => $totalQtyKeluar,
                'total_barang' => count($distinctBarang),
                'total_transaksi' => count($distinctMutasi)
            ],
            'sites' => $sites
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
