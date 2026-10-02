<?php
/**
 * REST API: CRUD Dokumen Faktur Purchase Order (Faktur Pembelian / 3-Way Matching)
 * Endpoint: /api/faktur_po/index.php
 * Path: api/faktur_po/index.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/activity_logger.php';
require_once __DIR__ . '/../../config/penomoran_helper.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_PURCHASING, ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER, ROLE_LOGISTIK]);
$method = $_SERVER['REQUEST_METHOD'];

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function generateNomorFaktur($conn, ?string $tanggal = null, bool $forUpdate = false) {
    $gen = generateNomorTransaksi($conn, 'FAKTUR PO', $tanggal, $forUpdate);
    if ($gen['success'] && !empty($gen['nomor'])) {
        return $gen['nomor'];
    }
    $prefix = 'FP' . date('y', strtotime($tanggal ?? 'now')) . '/' . date('md', strtotime($tanggal ?? 'now')) . '/';
    $sql = "SELECT nomor_faktur FROM faktur_po WHERE nomor_faktur LIKE '{$prefix}%' ORDER BY id_faktur DESC LIMIT 1" . ($forUpdate ? " FOR UPDATE" : "");
    $res = $conn->query($sql);
    $lastNum = 0;
    if ($res && $row = $res->fetch_assoc()) {
        $parts = explode('/', $row['nomor_faktur']);
        if (isset($parts[2])) {
            $lastNum = intval($parts[2]);
        }
    }
    $newNum = str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
    return $prefix . $newNum;
}

// -------------------------------------------------------------
// 1. GET: Ambil Detail Tunggal atau Daftar Faktur PO
// -------------------------------------------------------------
if ($method === 'GET') {
    $idFaktur = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_faktur']) ? intval($_GET['id_faktur']) : 0);

    // Detail Tunggal
    if ($idFaktur > 0) {
        $sql = "SELECT fp.*,
                       po.nomor_po, po.tanggal_po, po.total_termasuk_pajak, po.total_termasuk_PPnBM, po.pajak_PPnBM,
                       rcv.nomor_rcv, rcv.nomor_sj AS nomor_sj_rcv, rcv.tanggal_diterima AS tanggal_rcv_diterima,
                       rp.nomor_po_retur, rp.kompensasi AS retur_kompensasi, rp.total AS retur_total,
                       v.kode_vendor, v.nama_perusahaan AS nama_vendor, v.no_telepon AS telepon_vendor, v.email AS email_vendor, v.alamat AS alamat_vendor,
                       s.nama_site, s.kode_site, s.alamat AS alamat_site,
                       k.nama_karyawan AS nama_pembuat,
                       j.nama_jabatan, dvs.nama_divisi
                FROM faktur_po fp
                JOIN purchase_order po ON fp.id_po = po.id_po
                JOIN receiving_order rcv ON fp.id_rcv = rcv.id_rcv
                LEFT JOIN retur_po rp ON (rp.id_rcv = fp.id_rcv OR rp.id_po = fp.id_po)
                JOIN vendor v ON fp.id_vendor = v.id_vendor
                JOIN site s ON fp.id_site = s.id_site
                LEFT JOIN karyawan k ON fp.id_karyawan = k.id_karyawan
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                LEFT JOIN divisi dvs ON j.id_divisi = dvs.id_divisi
                WHERE fp.id_faktur = ? LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idFaktur);
        $stmt->execute();
        $faktur = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$faktur) {
            sendJson(false, 'Dokumen Faktur PO tidak ditemukan.', null, 404);
        }

        // Ambil Detail Items
        $sqlD = "SELECT fpd.*, b.kode_barang, b.nama_barang, b.satuan AS satuan_master, b.PPnBM, b.rate_PPnBM,
                        kat.nama_kategori, mrk.nama_merk
                 FROM faktur_po_detail fpd
                 JOIN barang b ON fpd.id_barang = b.id_barang
                 LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
                 LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
                 WHERE fpd.id_faktur = ?
                 ORDER BY fpd.id_faktur_detail ASC";
        $stmtD = $conn->prepare($sqlD);
        $stmtD->bind_param("i", $idFaktur);
        $stmtD->execute();
        $faktur['items'] = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtD->close();

        sendJson(true, 'Data Faktur PO berhasil dimuat.', $faktur);
    }

    // List Faktur PO dengan Filter & Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(5, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['q'] ?? '');
    $siteId = intval($_GET['site_id'] ?? 0);
    $vendorId = intval($_GET['vendor_id'] ?? 0);
    $status = trim($_GET['status'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');

    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if ($search !== '') {
        $where .= " AND (fp.nomor_faktur LIKE ? OR fp.nomor_faktur_vendor LIKE ? OR po.nomor_po LIKE ? OR rcv.nomor_rcv LIKE ? OR v.nama_perusahaan LIKE ?) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
        $types .= "sssss";
    }

    if ($siteId > 0) {
        $where .= " AND fp.id_site = ? ";
        $params[] = $siteId;
        $types .= "i";
    }

    if ($vendorId > 0) {
        $where .= " AND fp.id_vendor = ? ";
        $params[] = $vendorId;
        $types .= "i";
    }

    if ($status !== '') {
        $where .= " AND fp.status = ? ";
        $params[] = $status;
        $types .= "s";
    }

    if ($startDate !== '' && $endDate !== '') {
        $where .= " AND fp.tanggal_faktur_vendor BETWEEN ? AND ? ";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= "ss";
    }

    // Total Count
    $sqlCount = "SELECT COUNT(*) as total FROM faktur_po fp
                 JOIN purchase_order po ON fp.id_po = po.id_po
                 JOIN receiving_order rcv ON fp.id_rcv = rcv.id_rcv
                 JOIN vendor v ON fp.id_vendor = v.id_vendor
                 $where";
    $stmtC = $conn->prepare($sqlCount);
    if (!empty($params)) {
        $stmtC->bind_param($types, ...$params);
    }
    $stmtC->execute();
    $totalRows = (int)$stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();

    // Data List
    $sqlList = "SELECT fp.*,
                       po.nomor_po, rcv.nomor_rcv, rcv.nomor_sj AS nomor_sj_rcv,
                       v.nama_perusahaan AS nama_vendor, v.kode_vendor,
                       s.nama_site, s.kode_site,
                       k.nama_karyawan AS nama_pembuat,
                       DATEDIFF(fp.tanggal_jatuh_tempo, CURRENT_DATE) AS sisa_hari_tempo
                FROM faktur_po fp
                JOIN purchase_order po ON fp.id_po = po.id_po
                JOIN receiving_order rcv ON fp.id_rcv = rcv.id_rcv
                JOIN vendor v ON fp.id_vendor = v.id_vendor
                JOIN site s ON fp.id_site = s.id_site
                LEFT JOIN karyawan k ON fp.id_karyawan = k.id_karyawan
                $where
                ORDER BY fp.id_faktur DESC
                LIMIT ? OFFSET ?";

    $paramsList = array_merge($params, [$limit, $offset]);
    $typesList = $types . "ii";

    $stmtL = $conn->prepare($sqlList);
    $stmtL->bind_param($typesList, ...$paramsList);
    $stmtL->execute();
    $rows = $stmtL->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtL->close();

    // Ringkasan Metrik
    $sqlMetrics = "SELECT 
                    COUNT(*) as total_faktur,
                    COALESCE(SUM(total_tagihan), 0) as grand_total_tagihan,
                    COALESCE(SUM(terbayar), 0) as total_terbayar,
                    COALESCE(SUM(sisa_tagihan), 0) as total_sisa_tagihan,
                    COUNT(CASE WHEN status = 'BELUM DIBAYAR' THEN 1 END) as total_belum_bayar,
                    COUNT(CASE WHEN status = 'LUNAS' THEN 1 END) as total_lunas,
                    COUNT(CASE WHEN status != 'LUNAS' AND tanggal_jatuh_tempo < CURRENT_DATE THEN 1 END) as total_overdue
                   FROM faktur_po";
    $resM = $conn->query($sqlMetrics);
    $metrics = $resM ? $resM->fetch_assoc() : [];

    sendJson(true, 'Daftar faktur berhasil dimuat.', [
        'rows' => $rows,
        'pagination' => [
            'total' => $totalRows,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRows / $limit)
        ],
        'metrics' => $metrics
    ]);
}

// -------------------------------------------------------------
// 2. POST: Tambah Faktur PO Baru (3-Way Matching)
// -------------------------------------------------------------
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        $input = $_POST;
    }

    $idPo = intval($input['id_po'] ?? 0);
    $idRcv = intval($input['id_rcv'] ?? 0);
    $idVendor = intval($input['id_vendor'] ?? 0);
    $idSite = intval($input['id_site'] ?? 0);
    $idPoRetur = !empty($input['id_po_retur']) ? intval($input['id_po_retur']) : null;

    $nomorFakturVendor = trim($input['nomor_faktur_vendor'] ?? '');
    $nomorFakturPajak = trim($input['nomor_faktur_pajak'] ?? '');
    $tanggalFakturPajak = !empty($input['tanggal_faktur_pajak']) ? trim($input['tanggal_faktur_pajak']) : null;
    $tanggalFaktur = trim($input['tanggal_faktur'] ?? date('Y-m-d'));
    $tanggalTerimaFaktur = trim($input['tanggal_terima_faktur'] ?? date('Y-m-d'));
    $top = max(0, intval($input['term_of_payment'] ?? 0));

    $namaBank = trim($input['nama_bank'] ?? '');
    $nomorRekening = trim($input['nomor_rekening'] ?? '');
    $atasNamaRekening = trim($input['atas_nama_rekening'] ?? '');

    $subtotalPo = floatval($input['subtotal_po'] ?? 0);
    $subtotalDiterima = floatval($input['subtotal_diterima'] ?? 0);
    $nilaiRetur = floatval($input['nilai_retur'] ?? 0);
    $diskon = floatval($input['diskon'] ?? 0);
    $dpp = floatval($input['dpp'] ?? ($subtotalDiterima - $nilaiRetur - $diskon));
    $ratePajak = intval($input['rate_pajak'] ?? 0);
    $nominalPajak = floatval($input['nominal_pajak'] ?? ($dpp * ($ratePajak / 100)));
    $ratePpnbm = intval($input['rate_ppnbm'] ?? 0);
    $nominalPpnbm = floatval($input['nominal_ppnbm'] ?? ($ratePpnbm > 0 ? ($dpp * ($ratePpnbm / 100)) : 0));
    $biayaLain = floatval($input['biaya_lain'] ?? 0);
    $totalTagihan = floatval($input['total_tagihan'] ?? ($dpp + $nominalPajak + $nominalPpnbm + $biayaLain));
    $keterangan = trim($input['keterangan'] ?? '');

    $status = in_array($input['status'] ?? '', ['DRAFT', 'BELUM DIBAYAR']) ? $input['status'] : 'BELUM DIBAYAR';

    // Validasi Wajib
    if ($idPo <= 0 || $idRcv <= 0) {
        sendJson(false, 'Dokumen PO dan RCV wajib dipilih.', null, 422);
    }
    if (empty($nomorFakturVendor)) {
        sendJson(false, 'Nomor Faktur Vendor wajib diisi.', null, 422);
    }
    if (($ratePajak > 0 || $nominalPajak > 0) && empty($nomorFakturPajak)) {
        sendJson(false, 'Transaksi memiliki PPN. No. Seri e-Faktur Pajak wajib diisi.', null, 422);
    }

    // Cek apakah RCV sudah pernah dibuatkan Faktur PO aktif sebelumnya
    $stmtCheck = $conn->prepare("SELECT id_faktur, nomor_faktur FROM faktur_po WHERE id_rcv = ? AND status != 'BATAL' LIMIT 1");
    $stmtCheck->bind_param("i", $idRcv);
    $stmtCheck->execute();
    $existingFaktur = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if ($existingFaktur) {
        sendJson(false, "Dokumen Penerimaan ini sudah pernah dibuatkan Faktur ({$existingFaktur['nomor_faktur']}).", null, 422);
    }

    // Hitung Tanggal Jatuh Tempo
    $tanggalJatuhTempo = date('Y-m-d', strtotime("$tanggalFaktur + $top days"));

    $idKaryawan = $user['id_karyawan'] ?? 1;

    // Handle Upload File jika dikirim via Base64 atau FormData
    $fileFakturVendor = null;
    $fileFakturPajak = null;
    $uploadDir = __DIR__ . '/../../uploads/faktur/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($input['file_faktur_vendor_base64'])) {
        $ext = preg_match('/^data:application\/pdf/', $input['file_faktur_vendor_base64']) ? 'pdf' : 'jpg';
        $dataImg = preg_replace('/^data:[^;]+;base64,/', '', $input['file_faktur_vendor_base64']);
        $filename = 'inv_vendor_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, base64_decode($dataImg))) {
            $fileFakturVendor = $filename;
        }
    }

    if (!empty($input['file_faktur_pajak_base64'])) {
        $ext = preg_match('/^data:application\/pdf/', $input['file_faktur_pajak_base64']) ? 'pdf' : 'jpg';
        $dataImg = preg_replace('/^data:[^;]+;base64,/', '', $input['file_faktur_pajak_base64']);
        $filename = 'fp_pajak_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, base64_decode($dataImg))) {
            $fileFakturPajak = $filename;
        }
    }

    $conn->begin_transaction();
    try {
        $nomorFaktur = !empty($input['nomor_faktur']) ? trim($input['nomor_faktur']) : generateNomorFaktur($conn, $tanggalFaktur, true);
        $sisaTagihan = $totalTagihan;
        $terbayar = 0;

        $sqlIns = "INSERT INTO faktur_po (
                    nomor_faktur, nomor_faktur_vendor, nomor_faktur_pajak, tanggal_faktur_pajak,
                    tanggal_faktur_vendor, tanggal_terima_faktur_vendor, term_of_payment, tanggal_jatuh_tempo,
                    id_po, id_rcv, id_vendor, id_site,
                    nama_bank, nomor_rekening, atas_nama_rekening, id_karyawan,
                    subtotal_po, subtotal_diterima, nilai_retur, diskon, dpp,
                    rate_pajak, nominal_pajak, rate_ppnbm, nominal_ppnbm, biaya_lain, total_tagihan,
                    status, terbayar, sisa_tagihan,
                    file_faktur_vendor, file_faktur_pajak, keterangan
                   ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?
                   )";

        $stmtIns = $conn->prepare($sqlIns);
        $stmtIns->bind_param(
            "ssssssisiiiisssidddddididddsddsss",
            $nomorFaktur, $nomorFakturVendor, $nomorFakturPajak, $tanggalFakturPajak,
            $tanggalFaktur, $tanggalTerimaFaktur, $top, $tanggalJatuhTempo,
            $idPo, $idRcv, $idVendor, $idSite,
            $namaBank, $nomorRekening, $atasNamaRekening, $idKaryawan,
            $subtotalPo, $subtotalDiterima, $nilaiRetur, $diskon, $dpp,
            $ratePajak, $nominalPajak, $ratePpnbm, $nominalPpnbm, $biayaLain, $totalTagihan,
            $status, $terbayar, $sisaTagihan,
            $fileFakturVendor, $fileFakturPajak, $keterangan
        );
        $stmtIns->execute();
        $idFaktur = $conn->insert_id;
        $stmtIns->close();

        // Insert Detail Item Faktur
        $items = $input['items'] ?? [];
        if (!empty($items) && is_array($items)) {
            $sqlD = "INSERT INTO faktur_po_detail (
                        id_faktur, id_barang, qty_po, qty_rcv, qty_retur, qty_tagih,
                        satuan, harga_satuan, diskon_item, subtotal, keterangan
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtD = $conn->prepare($sqlD);

            foreach ($items as $it) {
                $idBarang = intval($it['id_barang'] ?? 0);
                $qPo = floatval($it['qty_po'] ?? 0);
                $qRcv = floatval($it['qty_rcv'] ?? 0);
                $qRetur = floatval($it['qty_retur'] ?? 0);
                $qTagih = floatval($it['qty_tagih'] ?? 0);
                $satuan = trim($it['satuan'] ?? 'Unit');
                $harga = floatval($it['harga_satuan'] ?? 0);
                $discItem = floatval($it['diskon_item'] ?? 0);
                $sub = floatval($it['subtotal'] ?? ($qTagih * ($harga - $discItem)));
                $ketItem = trim($it['keterangan'] ?? '');

                if ($idBarang > 0 && $qTagih >= 0) {
                    $stmtD->bind_param("iiddddsddds", $idFaktur, $idBarang, $qPo, $qRcv, $qRetur, $qTagih, $satuan, $harga, $discItem, $sub, $ketItem);
                    $stmtD->execute();
                }
            }
            $stmtD->close();
        }

        $conn->commit();

        logActivity($conn, [
            'modul' => 'FAKTUR',
            'aksi' => 'CREATE',
            'id_referensi' => $idFaktur,
            'nomor_referensi' => $nomorFaktur,
            'deskripsi' => "Menerbitkan Faktur PO {$nomorFaktur} (Inv Vendor: {$nomorFakturVendor}) senilai Rp " . number_format($totalTagihan, 0, ',', '.'),
            'data_sesudahnya' => [
                'id_faktur' => $idFaktur,
                'nomor_faktur' => $nomorFaktur,
                'nomor_faktur_vendor' => $nomorFakturVendor,
                'id_po' => $idPo,
                'id_rcv' => $idRcv,
                'id_vendor' => $idVendor,
                'total_tagihan' => $totalTagihan,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'status' => $status
            ]
        ]);

        sendJson(true, "Dokumen Faktur PO {$nomorFaktur} berhasil diterbitkan!", [
            'id_faktur' => $idFaktur,
            'nomor_faktur' => $nomorFaktur,
            'total_tagihan' => $totalTagihan
        ], 201);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal membuat Faktur PO: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 3. PUT: Update Faktur PO (Hanya jika status belum SEBAGIAN DIBAYAR / LUNAS / BATAL)
// -------------------------------------------------------------
if ($method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        sendJson(false, 'Format data tidak valid.', null, 400);
    }

    $idFaktur = intval($input['id_faktur'] ?? ($input['id'] ?? ($_GET['id'] ?? 0)));
    if ($idFaktur <= 0) {
        sendJson(false, 'ID Faktur tidak valid.', null, 422);
    }

    // Cek Faktur Existing
    $stmtC = $conn->prepare("SELECT id_faktur, nomor_faktur, status, terbayar FROM faktur_po WHERE id_faktur = ? LIMIT 1");
    $stmtC->bind_param("i", $idFaktur);
    $stmtC->execute();
    $currFaktur = $stmtC->get_result()->fetch_assoc();
    $stmtC->close();

    if (!$currFaktur) {
        sendJson(false, 'Faktur PO tidak ditemukan.', null, 404);
    }

    // Validasi aturan bisnis: Hanya boleh diedit jika belum SEBAGIAN DIBAYAR / LUNAS / BATAL
    $statusExisting = strtoupper((string)$currFaktur['status']);
    if (in_array($statusExisting, ['SEBAGIAN DIBAYAR', 'LUNAS', 'BATAL']) || floatval($currFaktur['terbayar']) > 0) {
        sendJson(false, "Faktur dengan status '{$statusExisting}' tidak dapat diedit karena sudah masuk proses pembayaran / dibatalkan.", null, 400);
    }

    $nomorFakturVendor = trim($input['nomor_faktur_vendor'] ?? '');
    $nomorFakturPajak = trim($input['nomor_faktur_pajak'] ?? '');
    $tanggalFakturPajak = !empty($input['tanggal_faktur_pajak']) ? trim($input['tanggal_faktur_pajak']) : null;
    $tanggalFaktur = trim($input['tanggal_faktur'] ?? ($input['tanggal_faktur_vendor'] ?? date('Y-m-d')));
    $tanggalTerimaFaktur = trim($input['tanggal_terima_faktur'] ?? ($input['tanggal_terima_faktur_vendor'] ?? date('Y-m-d')));
    $top = max(0, intval($input['term_of_payment'] ?? 0));

    $namaBank = trim($input['nama_bank'] ?? '');
    $nomorRekening = trim($input['nomor_rekening'] ?? '');
    $atasNamaRekening = trim($input['atas_nama_rekening'] ?? '');

    $subtotalPo = floatval($input['subtotal_po'] ?? 0);
    $subtotalDiterima = floatval($input['subtotal_diterima'] ?? 0);
    $nilaiRetur = floatval($input['nilai_retur'] ?? 0);
    $diskon = floatval($input['diskon'] ?? 0);
    $dpp = floatval($input['dpp'] ?? ($subtotalDiterima - $nilaiRetur - $diskon));
    $ratePajak = intval($input['rate_pajak'] ?? 0);
    $nominalPajak = floatval($input['nominal_pajak'] ?? ($dpp * ($ratePajak / 100)));
    $ratePpnbm = intval($input['rate_ppnbm'] ?? 0);
    $nominalPpnbm = floatval($input['nominal_ppnbm'] ?? ($ratePpnbm > 0 ? ($dpp * ($ratePpnbm / 100)) : 0));
    $biayaLain = floatval($input['biaya_lain'] ?? 0);
    $totalTagihan = floatval($input['total_tagihan'] ?? ($dpp + $nominalPajak + $nominalPpnbm + $biayaLain));
    $keterangan = trim($input['keterangan'] ?? '');

    // Status target edit hanya boleh DRAFT atau BELUM DIBAYAR
    $statusTarget = in_array($input['status'] ?? '', ['DRAFT', 'BELUM DIBAYAR']) ? $input['status'] : $statusExisting;

    if (empty($nomorFakturVendor)) {
        sendJson(false, 'Nomor Faktur Vendor wajib diisi.', null, 422);
    }
    if (($ratePajak > 0 || $nominalPajak > 0) && empty($nomorFakturPajak)) {
        sendJson(false, 'Transaksi memiliki PPN. No. Seri e-Faktur Pajak wajib diisi.', null, 422);
    }

    $tanggalJatuhTempo = date('Y-m-d', strtotime("$tanggalFaktur + $top days"));
    $sisaTagihan = $totalTagihan; // karena belum ada pembayaran
    $terbayar = 0;

    // Handle Upload File jika dikirim via Base64
    $uploadDir = __DIR__ . '/../../uploads/faktur/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $paramsUp = [
        $nomorFakturVendor, $nomorFakturPajak, $tanggalFakturPajak,
        $tanggalFaktur, $tanggalTerimaFaktur, $top, $tanggalJatuhTempo,
        $namaBank, $nomorRekening, $atasNamaRekening,
        $subtotalPo, $subtotalDiterima, $nilaiRetur, $diskon, $dpp,
        $ratePajak, $nominalPajak, $ratePpnbm, $nominalPpnbm, $biayaLain, $totalTagihan,
        $statusTarget, $terbayar, $sisaTagihan,
        $keterangan
    ];
    $typesUp = "ssssssissssdddddididddsddss";

    $fileFieldsSql = "";
    if (!empty($input['file_faktur_vendor_base64'])) {
        $ext = preg_match('/^data:application\/pdf/', $input['file_faktur_vendor_base64']) ? 'pdf' : 'jpg';
        $dataImg = preg_replace('/^data:[^;]+;base64,/', '', $input['file_faktur_vendor_base64']);
        $filename = 'inv_vendor_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, base64_decode($dataImg))) {
            $fileFieldsSql .= ", file_faktur_vendor = ?";
            $paramsUp[] = $filename;
            $typesUp .= "s";
        }
    }

    if (!empty($input['file_faktur_pajak_base64'])) {
        $ext = preg_match('/^data:application\/pdf/', $input['file_faktur_pajak_base64']) ? 'pdf' : 'jpg';
        $dataImg = preg_replace('/^data:[^;]+;base64,/', '', $input['file_faktur_pajak_base64']);
        $filename = 'fp_pajak_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, base64_decode($dataImg))) {
            $fileFieldsSql .= ", file_faktur_pajak = ?";
            $paramsUp[] = $filename;
            $typesUp .= "s";
        }
    }

    $paramsUp[] = $idFaktur;
    $typesUp .= "i";

    $conn->begin_transaction();
    try {
        $sqlUp = "UPDATE faktur_po SET
                    nomor_faktur_vendor = ?, nomor_faktur_pajak = ?, tanggal_faktur_pajak = ?,
                    tanggal_faktur_vendor = ?, tanggal_terima_faktur_vendor = ?, term_of_payment = ?, tanggal_jatuh_tempo = ?,
                    nama_bank = ?, nomor_rekening = ?, atas_nama_rekening = ?,
                    subtotal_po = ?, subtotal_diterima = ?, nilai_retur = ?, diskon = ?, dpp = ?,
                    rate_pajak = ?, nominal_pajak = ?, rate_ppnbm = ?, nominal_ppnbm = ?, biaya_lain = ?, total_tagihan = ?,
                    status = ?, terbayar = ?, sisa_tagihan = ?,
                    keterangan = ?
                    {$fileFieldsSql},
                    updated_at = NOW()
                  WHERE id_faktur = ?";

        $stmtUp = $conn->prepare($sqlUp);
        $stmtUp->bind_param($typesUp, ...$paramsUp);
        $stmtUp->execute();
        $stmtUp->close();

        // Update Detail Items (Hapus detail lama dan masukkan detail baru)
        $items = $input['items'] ?? [];
        if (!empty($items) && is_array($items)) {
            $conn->query("DELETE FROM faktur_po_detail WHERE id_faktur = {$idFaktur}");

            $sqlD = "INSERT INTO faktur_po_detail (
                        id_faktur, id_barang, qty_po, qty_rcv, qty_retur, qty_tagih,
                        satuan, harga_satuan, diskon_item, subtotal, keterangan
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtD = $conn->prepare($sqlD);

            foreach ($items as $it) {
                $idBarang = intval($it['id_barang'] ?? 0);
                $qPo = floatval($it['qty_po'] ?? 0);
                $qRcv = floatval($it['qty_rcv'] ?? 0);
                $qRetur = floatval($it['qty_retur'] ?? 0);
                $qTagih = floatval($it['qty_tagih'] ?? 0);
                $satuan = trim($it['satuan'] ?? 'Unit');
                $harga = floatval($it['harga_satuan'] ?? 0);
                $discItem = floatval($it['diskon_item'] ?? 0);
                $sub = floatval($it['subtotal'] ?? ($qTagih * ($harga - $discItem)));
                $ketItem = trim($it['keterangan'] ?? '');

                if ($idBarang > 0 && $qTagih >= 0) {
                    $stmtD->bind_param("iiddddsddds", $idFaktur, $idBarang, $qPo, $qRcv, $qRetur, $qTagih, $satuan, $harga, $discItem, $sub, $ketItem);
                    $stmtD->execute();
                }
            }
            $stmtD->close();
        }

        $conn->commit();

        logActivity($conn, [
            'modul' => 'FAKTUR',
            'aksi' => 'UPDATE',
            'id_referensi' => $idFaktur,
            'nomor_referensi' => $currFaktur['nomor_faktur'],
            'deskripsi' => "Memperbarui Faktur PO {$currFaktur['nomor_faktur']}",
            'data_sebelumnya' => [
                'nomor_faktur' => $currFaktur['nomor_faktur'],
                'status' => $currFaktur['status'],
                'terbayar' => $currFaktur['terbayar']
            ],
            'data_sesudahnya' => [
                'nomor_faktur_vendor' => $nomorFakturVendor,
                'total_tagihan' => $totalTagihan,
                'status' => $statusTarget
            ]
        ]);

        sendJson(true, "Dokumen Faktur PO {$currFaktur['nomor_faktur']} berhasil diperbarui!", [
            'id_faktur' => $idFaktur,
            'nomor_faktur' => $currFaktur['nomor_faktur'],
            'total_tagihan' => $totalTagihan
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal memperbarui Faktur PO: ' . $e->getMessage(), null, 500);
    }
}
