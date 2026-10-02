<?php
/**
 * REST API: Detail 1 Transaksi Stock Adjustment
 * Endpoint: /api/adjustment_stok/detail.php?id=XX
 * Path: api/adjustment_stok/detail.php
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
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        sendJson(false, 'ID Adjustment tidak valid.', null, 400);
    }

    // 1. Query Header
    $sqlHead = "SELECT 
                    adj.id_adjustment,
                    adj.nomor_adjustment,
                    adj.tanggal_adjustment,
                    adj.id_site,
                    s.nama_site,
                    s.alamat AS alamat_site,
                    adj.jenis_adjustment,
                    adj.alasan,
                    adj.keterangan,
                    adj.status,
                    adj.id_karyawan,
                    k.nama_karyawan AS nama_pembuat,
                    k.kode_karyawan AS kode_pembuat,
                    j.nama_jabatan AS jabatan_pembuat,
                    adj.id_karyawan_approved,
                    ka.nama_karyawan AS nama_approver,
                    ja.nama_jabatan AS jabatan_approver,
                    adj.tanggal_approved,
                    adj.created_at,
                    adj.updated_at
                FROM adjustment_stok adj
                LEFT JOIN site s ON s.id_site = adj.id_site
                LEFT JOIN karyawan k ON k.id_karyawan = adj.id_karyawan
                LEFT JOIN jabatan j ON j.id_jabatan = k.id_jabatan
                LEFT JOIN karyawan ka ON ka.id_karyawan = adj.id_karyawan_approved
                LEFT JOIN jabatan ja ON ja.id_jabatan = ka.id_jabatan
                WHERE adj.id_adjustment = ?
                LIMIT 1";

    $stmtHead = $conn->prepare($sqlHead);
    $stmtHead->bind_param("i", $id);
    $stmtHead->execute();
    $head = $stmtHead->get_result()->fetch_assoc();
    $stmtHead->close();

    if (!$head) {
        sendJson(false, 'Data adjustment stok tidak ditemukan.', null, 404);
    }

    // 2. Query Detail Items
    $sqlItems = "SELECT 
                    d.id_adjustment_detail,
                    d.id_adjustment,
                    d.id_barang,
                    b.kode_barang,
                    b.nama_barang,
                    b.satuan,
                    kb.nama_kategori,
                    mb.nama_merk,
                    d.qty_sistem,
                    d.qty_fisik,
                    d.qty_adjustment,
                    d.qty_akhir,
                    d.keterangan,
                    d.harga_satuan,
                    d.subtotal_adjustment
                 FROM adjustment_stok_detail d
                 INNER JOIN barang b ON b.id_barang = d.id_barang
                 LEFT JOIN kategori_barang kb ON kb.id_kategori = b.id_kategori
                 LEFT JOIN merk_barang mb ON mb.id_merk = b.id_merk
                 WHERE d.id_adjustment = ?
                 ORDER BY d.id_adjustment_detail ASC";

    $stmtItems = $conn->prepare($sqlItems);
    $stmtItems->bind_param("i", $id);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();

    $items = [];
    $totalItem = 0;
    $totalQtyAdj = 0;
    $totalNilaiAdj = 0;

    while ($item = $resItems->fetch_assoc()) {
        $totalItem++;
        $totalQtyAdj += (float)$item['qty_adjustment'];
        $totalNilaiAdj += (float)$item['subtotal_adjustment'];

        $items[] = [
            'id_adjustment_detail' => (int)$item['id_adjustment_detail'],
            'id_barang' => (int)$item['id_barang'],
            'kode_barang' => $item['kode_barang'],
            'nama_barang' => $item['nama_barang'],
            'satuan' => $item['satuan'] ?? 'PCS',
            'nama_kategori' => $item['nama_kategori'] ?? '-',
            'nama_merk' => $item['nama_merk'] ?? '-',
            'qty_sistem' => (float)$item['qty_sistem'],
            'qty_fisik' => (float)$item['qty_fisik'],
            'qty_adjustment' => (float)$item['qty_adjustment'],
            'qty_akhir' => (float)$item['qty_akhir'],
            'keterangan' => $item['keterangan'] ?? '',
            'harga_satuan' => (float)$item['harga_satuan'],
            'subtotal_adjustment' => (float)$item['subtotal_adjustment']
        ];
    }
    $stmtItems->close();

    $head['total_item'] = $totalItem;
    $head['total_qty_adjustment'] = $totalQtyAdj;
    $head['total_nilai_adjustment'] = $totalNilaiAdj;
    $head['items'] = $items;

    sendJson(true, 'Detail adjustment stok berhasil dimuat.', $head);

} catch (Exception $e) {
    sendJson(false, 'Gagal memuat detail adjustment stok: ' . $e->getMessage(), null, 500);
}
