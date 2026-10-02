<?php
/**
 * REST API: Helper Pencarian Barang & Stok Sistem per Site
 * Endpoint: /api/adjustment_stok/barang.php?id_site=XX&jenis=PENGURANGAN&q=keyword
 * Path: api/adjustment_stok/barang.php
 * Khusus Role: ADMIN, LOGISTIK, MEKANIK, MANAGER
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

$user = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MEKANIK, ROLE_MANAGER]);

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
    $idSite = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
    $jenis = trim($_GET['jenis'] ?? 'PENAMBAHAN');
    $search = trim($_GET['q'] ?? '');

    $where = " WHERE b.aktif = 1 ";
    $params = [];
    $types = "";

    // Jika jenis PENGURANGAN, hanya tampilkan barang yang stok sistem > 0 di site tersebut
    if (strtoupper($jenis) === 'PENGURANGAN') {
        $where .= " AND COALESCE(bs.stok, 0) > 0 ";
    }

    if (!empty($search)) {
        $where .= " AND (b.nama_barang LIKE ? OR b.kode_barang LIKE ? OR kb.nama_kategori LIKE ? OR mb.nama_merk LIKE ?) ";
        $like = "%{$search}%";
        $params = [$like, $like, $like, $like];
        $types = "ssss";
    }

    // Mengambil estimasi_harga paling baru berlaku dari barang_hargavendor per barang
    $sql = "SELECT 
                b.id_barang,
                b.kode_barang,
                b.nama_barang,
                b.satuan,
                kb.nama_kategori,
                mb.nama_merk,
                COALESCE(bs.stok, 0) AS stok_sistem,
                COALESCE(
                    (SELECT bh.harga_set 
                     FROM barang_hargavendor bh 
                     WHERE bh.id_barang = b.id_barang 
                     ORDER BY bh.berlaku DESC, bh.id_harga DESC 
                     LIMIT 1), 
                    0
                ) AS estimasi_harga
            FROM barang b
            LEFT JOIN kategori_barang kb ON kb.id_kategori = b.id_kategori
            LEFT JOIN merk_barang mb ON mb.id_merk = b.id_merk
            LEFT JOIN barang_stok bs ON bs.id_barang = b.id_barang AND bs.id_site = {$idSite}
            {$where}
            ORDER BY b.nama_barang ASC
            LIMIT 50";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($r = $res->fetch_assoc()) {
        $items[] = [
            'id_barang' => (int)$r['id_barang'],
            'kode_barang' => $r['kode_barang'],
            'nama_barang' => $r['nama_barang'],
            'satuan' => $r['satuan'] ?? 'PCS',
            'nama_kategori' => $r['nama_kategori'] ?? '-',
            'nama_merk' => $r['nama_merk'] ?? '-',
            'stok_sistem' => (float)$r['stok_sistem'],
            'estimasi_harga' => (float)$r['estimasi_harga']
        ];
    }
    $stmt->close();

    sendJson(true, 'Data katalog barang & stok berhasil dimuat.', $items);

} catch (Exception $e) {
    sendJson(false, 'Terjadi kesalahan: ' . $e->getMessage(), null, 500);
}
