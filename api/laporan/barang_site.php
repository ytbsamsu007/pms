<?php
/**
 * REST API: Laporan Data Barang Berdasarkan Site
 * Path: api/laporan/barang_site.php
 * Khusus Role: ADMIN, LOGISTIK, PURCHASING, FINANCE, MANAGER
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak didukung. Gunakan GET.']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_FINANCE, ROLE_MANAGER]);

try {
    $idSite = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
    $jenis = isset($_GET['jenis']) && $_GET['jenis'] !== '' ? (int)$_GET['jenis'] : null;
    $asset = isset($_GET['asset']) && $_GET['asset'] !== '' ? (int)$_GET['asset'] : null;
    $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
    
    // Pagination parameters (limit = 0 berarti ambil semua tanpa paging, e.g. untuk print)
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if ($limit < 0) $limit = 10;

    // 1. Ambil List Site untuk Dropdown Filter
    $sites = [];
    $resSites = $conn->query("SELECT id_site, kode_site, nama_site, jenis_site FROM site ORDER BY nama_site ASC");
    if ($resSites) {
        while ($s = $resSites->fetch_assoc()) {
            $sites[] = $s;
        }
    }

    // 2. Query Data Barang Berdasarkan Site
    $sql = "
        SELECT
            barang.id_barang, 
            barang.kode_barang,
            barang.nama_barang,
            site.id_site,
            site.kode_site,
            site.nama_site, 
            site.jenis_site,  
            CASE 
                WHEN barang.jenis = 1 THEN 'Persediaan' 
                ELSE 'Jasa'
            END AS jenis_barang, 
            barang.satuan, 
            CASE 
                WHEN barang.asset = 1 THEN 'Ya' 
                ELSE 'Tidak'
            END AS asset, 
            barang.serial_number, 
            barang.foto1, 
            barang.foto2, 
            barang.deskripsi, 
            barang_stok.stok
        FROM
            barang
            INNER JOIN
            barang_stok ON barang.id_barang = barang_stok.id_barang
            INNER JOIN
            site ON barang_stok.id_site = site.id_site
        WHERE barang_stok.stok > 0 AND barang.aktif = 1
    ";

    $params = [];
    $types = "";

    if ($idSite > 0) {
        $sql .= " AND site.id_site = ?";
        $params[] = $idSite;
        $types .= "i";
    }

    if ($jenis !== null) {
        $sql .= " AND barang.jenis = ?";
        $params[] = $jenis;
        $types .= "i";
    }

    if ($asset !== null) {
        $sql .= " AND barang.asset = ?";
        $params[] = $asset;
        $types .= "i";
    }

    if (!empty($search)) {
        $sql .= " AND (
            barang.kode_barang LIKE ? OR 
            barang.nama_barang LIKE ? OR 
            barang.serial_number LIKE ? OR 
            barang.deskripsi LIKE ? OR 
            site.nama_site LIKE ? OR 
            site.kode_site LIKE ?
        )";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 6; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    $sql .= " ORDER BY site.nama_site ASC, barang.nama_barang ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $allItems = [];
    $totalStok = 0;
    $distinctSites = [];
    $countPersediaan = 0;
    $countJasa = 0;
    $countAsset = 0;

    while ($row = $res->fetch_assoc()) {
        $stokNum = (float)$row['stok'];
        $totalStok += $stokNum;
        $distinctSites[$row['id_site']] = true;

        if ($row['jenis_barang'] === 'Persediaan') {
            $countPersediaan++;
        } else {
            $countJasa++;
        }

        if ($row['asset'] === 'Ya') {
            $countAsset++;
        }

        // Format Image URLs jika ada
        $foto1Url = null;
        if (!empty($row['foto1'])) {
            $cleanF1 = ltrim(str_replace('\\', '/', $row['foto1']), '/');
            $foto1Url = BASE_URL . '/' . $cleanF1;
        }

        $foto2Url = null;
        if (!empty($row['foto2'])) {
            $cleanF2 = ltrim(str_replace('\\', '/', $row['foto2']), '/');
            $foto2Url = BASE_URL . '/' . $cleanF2;
        }

        $row['stok_formatted'] = number_format($stokNum, 0, ',', '.');
        $row['foto1_url'] = $foto1Url;
        $row['foto2_url'] = $foto2Url;

        $allItems[] = $row;
    }
    $stmt->close();

    // 3. Ringkasan per Site
    $siteSummary = [];
    foreach ($allItems as $it) {
        $sId = $it['id_site'];
        if (!isset($siteSummary[$sId])) {
            $siteSummary[$sId] = [
                'id_site' => $sId,
                'nama_site' => $it['nama_site'],
                'jenis_site' => $it['jenis_site'],
                'total_item' => 0,
                'total_stok' => 0,
            ];
        }
        $siteSummary[$sId]['total_item']++;
        $siteSummary[$sId]['total_stok'] += (float)$it['stok'];
    }

    // 4. Pagination Calculation & Slicing
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
        'message' => 'Data barang berdasarkan site berhasil dimuat.',
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
                'total_item' => $totalRecords,
                'total_stok' => $totalStok,
                'total_site' => count($distinctSites),
                'total_persediaan' => $countPersediaan,
                'total_jasa' => $countJasa,
                'total_asset' => $countAsset
            ],
            'site_summary' => array_values($siteSummary),
            'sites' => $sites
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
