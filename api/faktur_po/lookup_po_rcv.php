<?php
/**
 * REST API: Lookup Purchase Order & Receiving Order untuk Faktur PO (3-Way Matching)
 * Endpoint: /api/faktur_po/lookup_po_rcv.php
 * Path: api/faktur_po/lookup_po_rcv.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idRcv = isset($_GET['id_rcv']) ? intval($_GET['id_rcv']) : 0;
$idPo = isset($_GET['id_po']) ? intval($_GET['id_po']) : 0;

// MODE 1: Ambil detail lengkap 3-Way Matching untuk satu dokumen RCV yang dipilih
if ($idRcv > 0) {
    // 1. Ambil Header RCV, PO, Vendor, Site
    $sqlH = "SELECT r.id_rcv, r.nomor_rcv, r.nomor_sj, r.tanggal_rcv, r.tanggal_diterima, r.file_sj,
                    po.id_po, po.nomor_po, po.tanggal_po, po.pajak AS rate_pajak, po.total_termasuk_pajak,
                    po.pajak_PPnBM AS rate_ppnbm, po.total_termasuk_PPnBM,
                    po.diskon AS diskon_po, po.term_of_payment,
                    v.id_vendor, v.kode_vendor, v.nama_perusahaan AS nama_vendor, v.nama_bank, v.nomor_rekening,
                    COALESCE(NULLIF(v.kontak_person, ''), NULLIF(v.person, ''), '-') AS kontak_person,
                    v.no_telepon, v.email,
                    s.id_site, s.nama_site, s.kode_site
             FROM receiving_order r
             JOIN purchase_order po ON r.id_po = po.id_po
             JOIN vendor v ON po.id_vendor = v.id_vendor
             JOIN site s ON po.id_site = s.id_site
             WHERE r.id_rcv = ? LIMIT 1";

    $stmtH = $conn->prepare($sqlH);
    $stmtH->bind_param("i", $idRcv);
    $stmtH->execute();
    $header = $stmtH->get_result()->fetch_assoc();
    $stmtH->close();

    if (!$header) {
        sendJson(false, 'Dokumen Penerimaan (RCV) tidak ditemukan.', null, 404);
    }

    $actualIdPo = (int)$header['id_po'];

    // 2. Cek apakah ada Dokumen Retur PO terkait RCV / PO ini
    $sqlRet = "SELECT id_po_retur, nomor_po_retur, kompensasi, total AS total_retur, nominal_pajak AS pajak_retur,
                      nomor_sj_retur, nomor_nota_retur_pajak, status AS status_retur
               FROM retur_po
               WHERE (id_rcv = ? OR id_po = ?) AND status != 'DRAFT' AND status != 'TIDAK DISETUJUI VENDOR'
               ORDER BY id_po_retur DESC LIMIT 1";
    $stmtRet = $conn->prepare($sqlRet);
    $stmtRet->bind_param("ii", $idRcv, $actualIdPo);
    $stmtRet->execute();
    $returDoc = $stmtRet->get_result()->fetch_assoc();
    $stmtRet->close();

    // 3. Ambil Detail Barang (PO + RCV + Retur Matching)
    $sqlItems = "SELECT rod.id_rcv_detail, rod.id_barang, rod.qty AS qty_rcv, rod.status_qc,
                        b.kode_barang, b.nama_barang, b.satuan AS satuan_master,
                        b.foto1, b.foto2, b.PPnBM, b.rate_PPnBM,
                        kat.nama_kategori, mrk.nama_merk,
                        COALESCE(pod.qty, 0) AS qty_po,
                        COALESCE(pod.harga, 0) AS harga_satuan,
                        COALESCE(pod.diskon, 0) AS diskon_item,
                        COALESCE(rpd.qty_retur, 0) AS qty_retur,
                        COALESCE(rpd.qty_diganti, 0) AS qty_diganti
                 FROM receiving_order_detail rod
                 JOIN barang b ON rod.id_barang = b.id_barang
                 LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
                 LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
                 LEFT JOIN purchase_order_detail pod ON (pod.id_po = ? AND pod.id_barang = rod.id_barang)
                 LEFT JOIN retur_po_detail rpd ON (rpd.id_po_retur = ? AND rpd.id_barang = rod.id_barang)
                 WHERE rod.id_rcv = ?
                 ORDER BY rod.id_rcv_detail ASC";

    $idReturParam = $returDoc ? (int)$returDoc['id_po_retur'] : 0;
    $stmtItems = $conn->prepare($sqlItems);
    $stmtItems->bind_param("iii", $actualIdPo, $idReturParam, $idRcv);
    $stmtItems->execute();
    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtItems->close();

    // Hitung Kuantitas Tagih & Subtotal per Item
    $subtotalPoTotal = 0;
    $subtotalRcvTotal = 0;
    $nilaiReturPotong = 0;

    $isPotongTagihan = ($returDoc && (int)$returDoc['kompensasi'] === 0);

    foreach ($items as &$it) {
        $qtyPo = (float)$it['qty_po'];
        $qtyRcv = (float)$it['qty_rcv'];
        $qtyRetur = (float)$it['qty_retur'];
        $hargaSatuan = (float)$it['harga_satuan'];
        $diskonItem = (float)$it['diskon_item'];

        // Jika skema Potong Tagihan, qty_tagih berkurang sebesar qty_retur
        // Jika skema Tukar Unit, qty_tagih tetap sesuai qty_rcv karena unit baru telah diganti
        $qtyTagih = $isPotongTagihan ? max(0, $qtyRcv - $qtyRetur) : $qtyRcv;

        $subtotalItem = max(0, $qtyTagih * ($hargaSatuan - $diskonItem));

        $it['qty_tagih'] = $qtyTagih;
        $it['subtotal'] = $subtotalItem;

        $subtotalPoTotal += ($qtyPo * ($hargaSatuan - $diskonItem));
        $subtotalRcvTotal += ($qtyRcv * ($hargaSatuan - $diskonItem));

        if ($isPotongTagihan) {
            $nilaiReturPotong += ($qtyRetur * ($hargaSatuan - $diskonItem));
        }
    }
    unset($it);

    $header['subtotal_po'] = $subtotalPoTotal;
    $header['subtotal_diterima'] = $subtotalRcvTotal;
    $header['nilai_retur'] = $nilaiReturPotong;
    $header['retur_doc'] = $returDoc;
    $header['items'] = $items;

    sendJson(true, 'Data dokumen 3-Way Matching berhasil dimuat.', $header);
}

// MODE 2: Ambil daftar ringkas Dokumen RCV & PO yang siap dibuatkan Faktur (belum difakturkan)
$search = trim($_GET['q'] ?? '');

$sqlList = "SELECT r.id_rcv, r.nomor_rcv, r.nomor_sj, r.tanggal_diterima, r.tanggal_rcv,
                   po.id_po, po.nomor_po, po.tanggal_po, po.term_of_payment,
                   v.id_vendor, v.kode_vendor, v.nama_perusahaan AS nama_vendor,
                   s.id_site, s.nama_site
            FROM receiving_order r
            JOIN purchase_order po ON r.id_po = po.id_po
            JOIN vendor v ON po.id_vendor = v.id_vendor
            JOIN site s ON po.id_site = s.id_site
            WHERE (
                r.status = 1 
                OR EXISTS (
                    SELECT 1 FROM retur_po rp 
                    WHERE rp.id_rcv = r.id_rcv AND rp.status IN ('DISETUJUI VENDOR', 'DITERIMA')
                )
            )
              AND NOT EXISTS (SELECT 1 FROM faktur_po fp WHERE fp.id_rcv = r.id_rcv AND fp.status != 'BATAL') ";

$params = [];
$types = "";

if ($search !== '') {
    $sqlList .= " AND (r.nomor_rcv LIKE ? OR po.nomor_po LIKE ? OR v.nama_perusahaan LIKE ? OR r.nomor_sj LIKE ?) ";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= "ssss";
}

$sqlList .= " ORDER BY r.tanggal_diterima ASC, r.id_rcv ASC LIMIT 50";

$stmtList = $conn->prepare($sqlList);
if (!empty($params)) {
    $stmtList->bind_param($types, ...$params);
}
$stmtList->execute();
$listRcv = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtList->close();

sendJson(true, 'Daftar dokumen penerimaan siap faktur berhasil dimuat.', $listRcv);
