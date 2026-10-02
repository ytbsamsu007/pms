<?php
/**
 * API Receiving: Ambil Daftar PO Yang Siap Diterima (Status DIPROSES VENDOR)
 * Path: api/receiving/po_ready.php
 * Khusus Role: LOGISTIK, PURCHASING, ADMIN, MANAGER
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

$idPo = isset($_GET['id_po']) && is_numeric($_GET['id_po']) ? (int)$_GET['id_po'] : null;

// 1. DETAIL PO SPESIFIK UNTUK FORM PENERIMAAN
if ($idPo) {
    $stmt = $conn->prepare("SELECT po.id_po, po.nomor_po, po.tanggal_po, po.status, po.prioritas,
                                   po.alamat, po.pengiriman, po.tanggal_pengiriman,
                                   v.id_vendor, v.nama_perusahaan as nama_vendor, v.no_telepon as telepon_vendor, v.alamat as alamat_vendor,
                                   s.id_site, s.nama_site, s.kode_site, s.alamat as alamat_site
                            FROM purchase_order po
                            LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
                            LEFT JOIN site s ON po.id_site = s.id_site
                            WHERE po.id_po = ? AND po.status = 'DIPROSES VENDOR' 
                              AND NOT EXISTS (SELECT 1 FROM receiving_order ro WHERE ro.id_po = po.id_po)
                            LIMIT 1");
    $stmt->bind_param("i", $idPo);
    $stmt->execute();
    $po = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$po) {
        jsonResponse(false, 'Purchase Order tidak ditemukan, belum berstatus DIPROSES VENDOR, atau sudah pernah dibuatkan penerimaan barang.', null, 404);
    }

    // Ambil detail item barang (HANYA QTY & NAMA, TANPA HARGA)
    $stmtItems = $conn->prepare("SELECT pd.id_po_detail, pd.id_barang, pd.qty, pd.keterangan,
                                        b.kode_barang, b.nama_barang, b.satuan,
                                        kat.nama_kategori, mrk.nama_merk
                                 FROM purchase_order_detail pd
                                 LEFT JOIN barang b ON pd.id_barang = b.id_barang
                                 LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
                                 LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
                                 WHERE pd.id_po = ?
                                 ORDER BY pd.id_po_detail ASC");
    $stmtItems->bind_param("i", $idPo);
    $stmtItems->execute();
    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItems->close();

    $po['items'] = $items;
    $po['total_item'] = count($items);

    jsonResponse(true, 'Data Purchase Order siap terima berhasil dimuat.', $po);
}

// 2. DAFTAR SELURUH PO YANG SEDANG DIPROSES VENDOR (YANG BELUM DITERIMA)
$stmtList = $conn->prepare("SELECT po.id_po, po.nomor_po, po.tanggal_po, po.status, po.prioritas,
                                   po.pengiriman, po.tanggal_pengiriman,
                                   v.id_vendor, v.nama_perusahaan as nama_vendor,
                                   s.id_site, s.nama_site, s.kode_site,
                                   (SELECT COUNT(*) FROM purchase_order_detail pd WHERE pd.id_po = po.id_po) as total_item,
                                   (SELECT SUM(pd.qty) FROM purchase_order_detail pd WHERE pd.id_po = po.id_po) as total_qty
                            FROM purchase_order po
                            LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
                            LEFT JOIN site s ON po.id_site = s.id_site
                            WHERE po.status = 'DIPROSES VENDOR'
                              AND NOT EXISTS (SELECT 1 FROM receiving_order ro WHERE ro.id_po = po.id_po)
                            ORDER BY po.id_po DESC");
$stmtList->execute();
$list = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtList->close();

jsonResponse(true, 'Daftar Purchase Order siap diterima berhasil diambil.', $list);
