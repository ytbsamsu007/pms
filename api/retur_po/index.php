<?php
/**
 * RESTful API Modul Retur Purchase Order (Retur PO)
 * PT Jaya Teknis
 * Khusus role Logistik, Administrator, dan Manager
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../../config/activity_logger.php';
require_once __DIR__ . '/../../config/penomoran_helper.php';

$currentUser = apiAuth([ROLE_LOGISTIK, ROLE_ADMIN, ROLE_MANAGER]);
$method = $_SERVER['REQUEST_METHOD'];

// Helper function JSON Response
function sendJson($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// -------------------------------------------------------------
// 1. GET: Ambil Data List atau Single Detail
// -------------------------------------------------------------
if ($method === 'GET') {
    $idRetur = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_po_retur']) ? intval($_GET['id_po_retur']) : 0);

    try {
        if ($idRetur > 0) {
            // DETAIL RETUR TUNGGAL
            $sqlHeader = "SELECT r.*,
                                 po.nomor_po, po.tanggal_po,
                                 rcv.nomor_rcv, rcv.nomor_sj, rcv.tanggal_diterima,
                                 v.nama_perusahaan AS nama_vendor, v.no_telepon AS telepon_vendor, v.email AS email_vendor,
                                 s.nama_site,
                                 k_buat.nama_karyawan AS nama_pembuat,
                                 k_app.nama_karyawan AS nama_penyetuju
                          FROM retur_po r
                          LEFT JOIN purchase_order po ON r.id_po = po.id_po
                          LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                          LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                          LEFT JOIN site s ON r.id_site = s.id_site
                          LEFT JOIN karyawan k_buat ON r.id_karyawan = k_buat.id_karyawan
                          LEFT JOIN karyawan k_app ON r.id_karyawan_approved = k_app.id_karyawan
                          WHERE r.id_po_retur = ?";

            $stmtH = $conn->prepare($sqlHeader);
            $stmtH->bind_param("i", $idRetur);
            $stmtH->execute();
            $header = $stmtH->get_result()->fetch_assoc();
            $stmtH->close();

            if (!$header) {
                sendJson(false, 'Data Retur PO tidak ditemukan.', null, 404);
            }

            // AMBIL ITEMS DETAIL
            $sqlItems = "SELECT d.*, b.kode_barang, b.nama_barang, b.satuan AS master_satuan
                         FROM retur_po_detail d
                         JOIN barang b ON d.id_barang = b.id_barang
                         WHERE d.id_po_retur = ?
                         ORDER BY d.id_po_retur_detail ASC";

            $stmtI = $conn->prepare($sqlItems);
            $stmtI->bind_param("i", $idRetur);
            $stmtI->execute();
            $resItems = $stmtI->get_result();

            $items = [];
            while ($row = $resItems->fetch_assoc()) {
                $row['foto_url'] = !empty($row['foto_bukti']) ? BASE_URL . '/uploads/retur/' . $row['foto_bukti'] : null;
                $items[] = $row;
            }
            $stmtI->close();

            $header['items'] = $items;
            sendJson(true, 'Detail Retur PO berhasil dimuat.', $header);

        } else {
            // LIST RETUR PO (FILTER & PAGINATION)
            $page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
            $limit = max(1, min(100, isset($_GET['limit']) ? intval($_GET['limit']) : 20));
            $offset = ($page - 1) * $limit;

            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $siteId = isset($_GET['site_id']) ? intval($_GET['site_id']) : 0;
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $kompensasi = isset($_GET['kompensasi']) && $_GET['kompensasi'] !== '' ? intval($_GET['kompensasi']) : '';
            $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
            $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

            $where = "WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($q)) {
                $where .= " AND (r.nomor_po_retur LIKE ? OR po.nomor_po LIKE ? OR rcv.nomor_rcv LIKE ? OR v.nama_perusahaan LIKE ? OR r.nomor_sj_retur LIKE ?)";
                $search = "%{$q}%";
                $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
                $types .= "sssss";
            }

            if ($siteId > 0) {
                $where .= " AND r.id_site = ?";
                $params[] = $siteId;
                $types .= "i";
            }

            if (!empty($status)) {
                $where .= " AND r.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            if ($kompensasi !== '') {
                $where .= " AND r.kompensasi = ?";
                $params[] = $kompensasi;
                $types .= "i";
            }

            if (!empty($startDate)) {
                $where .= " AND DATE(r.tanggal_po_retur) >= ?";
                $params[] = $startDate;
                $types .= "s";
            }

            if (!empty($endDate)) {
                $where .= " AND DATE(r.tanggal_po_retur) <= ?";
                $params[] = $endDate;
                $types .= "s";
            }

            // Hitung Total Data
            $sqlCount = "SELECT COUNT(*) AS total
                         FROM retur_po r
                         LEFT JOIN purchase_order po ON r.id_po = po.id_po
                         LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                         LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                         {$where}";

            $stmtCount = $conn->prepare($sqlCount);
            if (!empty($params)) {
                $stmtCount->bind_param($types, ...$params);
            }
            $stmtCount->execute();
            $totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
            $stmtCount->close();

            // Hitung Metrik Ringkasan
            $sqlMetrics = "SELECT 
                            COUNT(*) AS total_retur,
                            COALESCE(SUM(CASE WHEN r.status = 'DRAFT' THEN 1 ELSE 0 END), 0) AS total_draft,
                            COALESCE(SUM(CASE WHEN r.status IN ('MENUNGGU KONFIRMASI VENDOR','DISETUJUI VENDOR','DIKIRIM KE VENDOR') THEN 1 ELSE 0 END), 0) AS total_proses,
                            COALESCE(SUM(CASE WHEN r.status = 'DITERIMA' THEN 1 ELSE 0 END), 0) AS total_selesai,
                            COALESCE(SUM(r.total), 0) AS total_nilai_retur
                           FROM retur_po r
                           LEFT JOIN purchase_order po ON r.id_po = po.id_po
                           LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                           LEFT JOIN vendor v ON r.id_vendor = v.id_vendor";
            $resMetrics = $conn->query($sqlMetrics)->fetch_assoc();

            // Ambil List Data
            $sqlList = "SELECT r.id_po_retur, r.nomor_po_retur, r.tanggal_po_retur, r.kompensasi, r.status,
                               r.total, r.nominal_pajak, r.rate_pajak, r.nomor_sj_retur, r.pic_vendor,
                               po.id_po, po.nomor_po,
                               rcv.id_rcv, rcv.nomor_rcv, rcv.nomor_sj,
                               v.id_vendor, v.nama_perusahaan AS nama_vendor,
                               s.id_site, s.nama_site,
                               k.nama_karyawan AS nama_pembuat,
                               COUNT(d.id_po_retur_detail) AS total_items,
                               SUM(d.qty_retur) AS total_qty_retur
                        FROM retur_po r
                        LEFT JOIN purchase_order po ON r.id_po = po.id_po
                        LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                        LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                        LEFT JOIN site s ON r.id_site = s.id_site
                        LEFT JOIN karyawan k ON r.id_karyawan = k.id_karyawan
                        LEFT JOIN retur_po_detail d ON r.id_po_retur = d.id_po_retur
                        {$where}
                        GROUP BY r.id_po_retur
                        ORDER BY r.id_po_retur DESC
                        LIMIT ? OFFSET ?";

            $paramsList = $params;
            $paramsList[] = $limit;
            $paramsList[] = $offset;
            $typesList = $types . "ii";

            $stmtList = $conn->prepare($sqlList);
            $stmtList->bind_param($typesList, ...$paramsList);
            $stmtList->execute();
            $resList = $stmtList->get_result();

            $items = [];
            while ($row = $resList->fetch_assoc()) {
                $items[] = $row;
            }
            $stmtList->close();

            sendJson(true, 'Data Retur PO berhasil diambil.', [
                'metrics' => $resMetrics,
                'items' => $items,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => (int)$totalRows,
                    'total_pages' => ceil($totalRows / $limit)
                ]
            ]);
        }

    } catch (Exception $e) {
        sendJson(false, 'Gagal mengambil data: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 2. POST: Tambah Dokumen Retur PO Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Header Inputs
    $idPo = isset($input['id_po']) ? intval($input['id_po']) : 0;
    $idRcv = isset($input['id_rcv']) ? intval($input['id_rcv']) : 0;
    $idVendor = isset($input['id_vendor']) ? intval($input['id_vendor']) : 0;
    $idSite = isset($input['id_site']) ? intval($input['id_site']) : 0;
    $picVendor = trim($input['pic_vendor'] ?? '');
    $kompensasi = isset($input['kompensasi']) ? intval($input['kompensasi']) : 1; // 1: Tukar Unit, 0: Potong Tagihan
    $ratePajak = isset($input['rate_pajak']) ? intval($input['rate_pajak']) : 0;
    $nomorSjRetur = trim($input['nomor_sj_retur'] ?? '');
    $nomorNotaReturPajak = trim($input['nomor_nota_retur_pajak'] ?? '');
    $keterangan = trim($input['keterangan'] ?? '');
    $pengirimanRetur = in_array($input['pengiriman_retur'] ?? '', ['Vendor', 'Expedisi', 'Internal']) ? $input['pengiriman_retur'] : 'Vendor';
    $biayaRetur = floatval($input['biaya_retur'] ?? 0);
    $idKaryawanApproved = !empty($input['id_karyawan_approved']) ? intval($input['id_karyawan_approved']) : null;
    if (empty($idKaryawanApproved)) {
        $resAppFallback = $conn->query("SELECT k.id_karyawan FROM karyawan k JOIN jabatan j ON k.id_jabatan = j.id_jabatan WHERE j.level IN (1, 2) AND k.aktif = 1 ORDER BY j.level ASC LIMIT 1");
        if ($resAppFallback && $rApp = $resAppFallback->fetch_assoc()) {
            $idKaryawanApproved = intval($rApp['id_karyawan']);
        }
    }
    $status = in_array($input['status'] ?? '', ['DRAFT', 'MENUNGGU KONFIRMASI VENDOR']) ? $input['status'] : 'MENUNGGU KONFIRMASI VENDOR';
    $tanggalRetur = !empty($input['tanggal_po_retur']) ? trim($input['tanggal_po_retur']) : date('Y-m-d H:i:s');
    if (strlen($tanggalRetur) === 10) { $tanggalRetur .= ' ' . date('H:i:s'); }

    $idKaryawan = !empty($user['id_karyawan']) ? $user['id_karyawan'] : 1;
    $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

    // Fallback: Jika id_rcv belum terisi (misal bernilai 0), coba auto-resolve dari items atau RCV rusak terbaru
    if ($idRcv <= 0) {
        if (!empty($items) && !empty($items[0]['id_barang'])) {
            $firstIdBarang = intval($items[0]['id_barang']);
            $stmtAutoRcv = $conn->prepare("SELECT rod.id_rcv FROM receiving_order_detail rod JOIN receiving_order r ON rod.id_rcv = r.id_rcv WHERE rod.id_barang = ? AND rod.status_qc = 0 ORDER BY r.id_rcv DESC LIMIT 1");
            $stmtAutoRcv->bind_param("i", $firstIdBarang);
            $stmtAutoRcv->execute();
            $resAuto = $stmtAutoRcv->get_result()->fetch_assoc();
            $stmtAutoRcv->close();
            if ($resAuto && !empty($resAuto['id_rcv'])) {
                $idRcv = intval($resAuto['id_rcv']);
            }
        }
        if ($idRcv <= 0 && $idPo > 0) {
            $stmtPoRcv = $conn->prepare("SELECT id_rcv FROM receiving_order WHERE id_po = ? ORDER BY id_rcv DESC LIMIT 1");
            $stmtPoRcv->bind_param("i", $idPo);
            $stmtPoRcv->execute();
            $resPoRcv = $stmtPoRcv->get_result()->fetch_assoc();
            $stmtPoRcv->close();
            if ($resPoRcv && !empty($resPoRcv['id_rcv'])) {
                $idRcv = intval($resPoRcv['id_rcv']);
            }
        }
        if ($idRcv <= 0) {
            $resLatestRcv = $conn->query("SELECT r.id_rcv FROM receiving_order r JOIN receiving_order_detail rod ON r.id_rcv = rod.id_rcv WHERE rod.status_qc = 0 ORDER BY r.id_rcv DESC LIMIT 1");
            if ($resLatestRcv && $rowLat = $resLatestRcv->fetch_assoc()) {
                $idRcv = intval($rowLat['id_rcv']);
            }
        }
    }

    // Auto-resolve id_po, id_vendor, id_site, pic_vendor dari receiving_order jika belum terisi
    if ($idRcv > 0 && ($idPo <= 0 || $idVendor <= 0 || $idSite <= 0 || empty($picVendor))) {
        $stmtRcvCheck = $conn->prepare("SELECT r.id_po, po.id_vendor, po.id_site, v.person AS pic_vendor FROM receiving_order r JOIN purchase_order po ON r.id_po = po.id_po JOIN vendor v ON po.id_vendor = v.id_vendor WHERE r.id_rcv = ?");
        $stmtRcvCheck->bind_param("i", $idRcv);
        $stmtRcvCheck->execute();
        $rcvRow = $stmtRcvCheck->get_result()->fetch_assoc();
        $stmtRcvCheck->close();
        if ($rcvRow) {
            if ($idPo <= 0) $idPo = intval($rcvRow['id_po']);
            if ($idVendor <= 0) $idVendor = intval($rcvRow['id_vendor']);
            if ($idSite <= 0) $idSite = intval($rcvRow['id_site']);
            if (empty($picVendor) && !empty($rcvRow['pic_vendor'])) $picVendor = trim($rcvRow['pic_vendor']);
        }
    }

    if (empty($picVendor)) {
        $picVendor = 'PIC Vendor';
    }

    // Fallback: Jika items kosong, ambil item rusak dari RCV tersebut
    if (empty($items) && $idRcv > 0) {
        $stmtDamaged = $conn->prepare("SELECT rod.id_barang, rod.qty, b.satuan, COALESCE(pod.harga, 0) AS harga_satuan, rod.keterangan AS ket_rcv, b.nama_barang 
                                       FROM receiving_order_detail rod 
                                       JOIN receiving_order r ON rod.id_rcv = r.id_rcv
                                       LEFT JOIN purchase_order_detail pod ON (r.id_po = pod.id_po AND rod.id_barang = pod.id_barang)
                                       JOIN barang b ON rod.id_barang = b.id_barang 
                                       WHERE rod.id_rcv = ? AND rod.status_qc = 0");
        $stmtDamaged->bind_param("i", $idRcv);
        $stmtDamaged->execute();
        $resDamaged = $stmtDamaged->get_result();
        while ($dRow = $resDamaged->fetch_assoc()) {
            $items[] = [
                'id_barang' => intval($dRow['id_barang']),
                'qty_retur' => floatval($dRow['qty']),
                'satuan' => !empty($dRow['satuan']) ? $dRow['satuan'] : 'PCS',
                'harga_satuan' => floatval($dRow['harga_satuan']),
                'alasan_retur' => 'RUSAK_FISIK',
                'keterangan_kerusakan' => !empty($dRow['ket_rcv']) ? $dRow['ket_rcv'] : 'Kondisi rusak saat penerimaan barang',
                'foto_base64' => '',
                'foto_name' => ''
            ];
        }
        $stmtDamaged->close();
    }

    // Validasi
    if ($idRcv <= 0) {
        sendJson(false, 'Dokumen Penerimaan (RCV) wajib dipilih.', null, 422);
    }

    // Validasi Duplikasi Dokumen RCV
    $stmtCheckExisting = $conn->prepare("SELECT id_po_retur, nomor_po_retur FROM retur_po WHERE id_rcv = ? AND status != 'DIBATALKAN' LIMIT 1");
    $stmtCheckExisting->bind_param("i", $idRcv);
    $stmtCheckExisting->execute();
    $existingRetur = $stmtCheckExisting->get_result()->fetch_assoc();
    $stmtCheckExisting->close();
    if ($existingRetur) {
        sendJson(false, "Dokumen Penerimaan (RCV) ini sudah pernah dibuatkan dokumen Retur PO dengan nomor {$existingRetur['nomor_po_retur']}.", null, 422);
    }

    if ($idPo <= 0 || $idVendor <= 0) {
        sendJson(false, 'Dokumen PO dan Vendor tidak valid.', null, 422);
    }
    if (empty($items)) {
        sendJson(false, 'Harap sertakan minimal 1 baris item barang yang diretur.', null, 422);
    }

    $uploadDir = __DIR__ . '/../../uploads/retur/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $conn->begin_transaction();
    try {
        // 1. Ambil / Generate Nomor Retur Transaksi Dinamis Resmi Terkunci Sesuai Format Penomoran
        $nomorRetur = trim($post['nomor_po_retur'] ?? '');
        if (empty($nomorRetur)) {
            $gen = generateNomorTransaksiLocked($conn, 'RETUR PO', $tanggalRetur);
            if ($gen['success'] && !empty($gen['nomor'])) {
                $nomorRetur = $gen['nomor'];
            } else {
                $nomorRetur = 'RT/' . str_pad(1, 4, '0', STR_PAD_LEFT) . '/' . date('ymd', strtotime($tanggalRetur));
            }
        }

        // 2. Hitung Total & Validasi Items
        $totalSubtotal = 0;
        $cleanItems = [];

        foreach ($items as $idx => $it) {
            $idBarang = intval($it['id_barang'] ?? 0);
            $qtyRetur = floatval($it['qty_retur'] ?? 0);
            $satuan = !empty($it['satuan']) ? trim($it['satuan']) : 'PCS';
            $hargaSatuan = floatval($it['harga_satuan'] ?? 0);
            $alasanRetur = in_array($it['alasan_retur'] ?? '', ['RUSAK_FISIK', 'CACAT_PRODUKSI', 'SALAH_SPESIFIKASI', 'KURANG_PENGIRIMAN', 'KADALUARSA_EXP']) ? $it['alasan_retur'] : 'RUSAK_FISIK';
            $ketKerusakan = trim($it['keterangan_kerusakan'] ?? '');
            $fotoBukti = trim($it['foto_bukti'] ?? '');

            if ($idBarang <= 0 || $qtyRetur <= 0) {
                continue;
            }

            // Handle Base64 Photo Upload if present
            if (!empty($it['foto_base64'])) {
                $imgData = $it['foto_base64'];
                if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                    $imgData = substr($imgData, strpos($imgData, ',') + 1);
                    $type = strtolower($type[1]);
                    if ($type === 'jpeg') $type = 'jpg';
                    if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $imgDecoded = base64_decode($imgData);
                        if ($imgDecoded !== false) {
                            $fotoFileName = 'retur_' . $yymm . '_' . uniqid() . '.' . $type;
                            $targetPath = $uploadDir . $fotoFileName;
                            file_put_contents($targetPath, $imgDecoded);
                            $fotoBukti = $fotoFileName;
                        }
                    }
                }
            }

            $subtotal = $qtyRetur * $hargaSatuan;
            $totalSubtotal += $subtotal;

            $cleanItems[] = [
                'id_barang' => $idBarang,
                'qty_retur' => $qtyRetur,
                'satuan' => $satuan,
                'harga_satuan' => $hargaSatuan,
                'subtotal' => $subtotal,
                'alasan_retur' => $alasanRetur,
                'keterangan_kerusakan' => $ketKerusakan,
                'foto_bukti' => $fotoBukti
            ];
        }

        if (empty($cleanItems)) {
            throw new Exception('Barang yang diretur tidak valid atau kuantitasnya 0.');
        }

        // 3. Hitung Nominal Pajak
        $nominalPajak = $totalSubtotal * ($ratePajak / 100);

        // 4. Insert Header `retur_po`
        $sqlInsH = "INSERT INTO retur_po (
                        nomor_po_retur, id_karyawan, id_karyawan_approved, tanggal_po_retur, kompensasi,
                        id_vendor, pic_vendor, id_site, id_po, id_rcv,
                        total, status, nominal_pajak, rate_pajak,
                        nomor_sj_retur, nomor_nota_retur_pajak, keterangan,
                        pengiriman_retur, biaya_retur
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtInsH = $conn->prepare($sqlInsH);
        $stmtInsH->bind_param(
            "siisiisiiidsdissssd",
            $nomorRetur, $idKaryawan, $idKaryawanApproved, $tanggalRetur, $kompensasi,
            $idVendor, $picVendor, $idSite, $idPo, $idRcv,
            $totalSubtotal, $status, $nominalPajak, $ratePajak,
            $nomorSjRetur, $nomorNotaReturPajak, $keterangan,
            $pengirimanRetur, $biayaRetur
        );
        $stmtInsH->execute();
        $idPoRetur = $conn->insert_id;
        $stmtInsH->close();

        // 5. Insert Detail `retur_po_detail`
        $sqlInsD = "INSERT INTO retur_po_detail (
                        id_po_retur, id_barang, qty_retur, satuan,
                        harga_satuan, subtotal, alasan_retur,
                        keterangan_kerusakan, foto_bukti, qty_diganti
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

        $stmtInsD = $conn->prepare($sqlInsD);
        foreach ($cleanItems as $ci) {
            $stmtInsD->bind_param(
                "iidssdsss",
                $idPoRetur, $ci['id_barang'], $ci['qty_retur'], $ci['satuan'],
                $ci['harga_satuan'], $ci['subtotal'], $ci['alasan_retur'],
                $ci['keterangan_kerusakan'], $ci['foto_bukti']
            );
            $stmtInsD->execute();
        }
        $stmtInsD->close();

        $conn->commit();

        logActivity($conn, [
            'modul' => 'RETUR',
            'aksi' => 'CREATE',
            'id_referensi' => $idPoRetur,
            'nomor_referensi' => $nomorRetur,
            'deskripsi' => "Menerbitkan dokumen Retur PO {$nomorRetur} (" . ($kompensasi === 1 ? 'Tukar Unit' : 'Potong Tagihan') . ") untuk PO ID: {$idPo}",
            'data_sesudahnya' => [
                'id_po_retur' => $idPoRetur,
                'nomor_po_retur' => $nomorRetur,
                'id_po' => $idPo,
                'id_rcv' => $idRcv,
                'id_vendor' => $idVendor,
                'kompensasi' => $kompensasi === 1 ? 'Tukar Unit' : 'Potong Tagihan',
                'total' => $totalSubtotal,
                'status' => $status
            ]
        ]);

        sendJson(true, "Dokumen Retur PO {$nomorRetur} berhasil diterbitkan.", [
            'id_po_retur' => $idPoRetur,
            'nomor_po_retur' => $nomorRetur
        ], 201);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal membuat dokumen Retur: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 3. PUT: Update Status Persetujuan & Unit Pengganti
// -------------------------------------------------------------
if ($method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) {
        parse_str($rawInput, $input);
    }
    $input = $input ?? [];

    $idRetur = 0;
    if (isset($input['id_po_retur']) && intval($input['id_po_retur']) > 0) {
        $idRetur = intval($input['id_po_retur']);
    } elseif (isset($input['id']) && intval($input['id']) > 0) {
        $idRetur = intval($input['id']);
    } elseif (isset($_GET['id']) && intval($_GET['id']) > 0) {
        $idRetur = intval($_GET['id']);
    } elseif (isset($_GET['id_po_retur']) && intval($_GET['id_po_retur']) > 0) {
        $idRetur = intval($_GET['id_po_retur']);
    }

    if ($idRetur <= 0) {
        sendJson(false, 'ID Retur PO tidak valid.', null, 422);
    }

    $conn->begin_transaction();
    try {
        // Ambil data lama
        $stmtChk = $conn->prepare("SELECT * FROM retur_po WHERE id_po_retur = ?");
        $stmtChk->bind_param("i", $idRetur);
        $stmtChk->execute();
        $retur = $stmtChk->get_result()->fetch_assoc();
        $stmtChk->close();

        if (!$retur) {
            sendJson(false, 'Dokumen Retur tidak ditemukan.', null, 404);
        }

        // Kunci dokumen jika status sudah DITERIMA
        if ($retur['status'] === 'DITERIMA') {
            sendJson(false, 'Dokumen Retur PO ini sudah berstatus DITERIMA (Selesai) dan terkunci permanen sehingga tidak dapat diubah lagi.', null, 422);
        }

        $newStatus = trim($input['status'] ?? $retur['status']);
        $validStatuses = ['DRAFT', 'MENUNGGU KONFIRMASI VENDOR', 'DISETUJUI VENDOR', 'TIDAK DISETUJUI VENDOR', 'DIKIRIM KE VENDOR', 'DITERIMA'];
        if (!in_array($newStatus, $validStatuses)) {
            sendJson(false, 'Status tidak valid.', null, 422);
        }

        $picVendor = isset($input['pic_vendor']) ? trim($input['pic_vendor']) : $retur['pic_vendor'];
        $nomorSjRetur = isset($input['nomor_sj_retur']) ? trim($input['nomor_sj_retur']) : $retur['nomor_sj_retur'];
        $nomorNotaReturPajak = isset($input['nomor_nota_retur_pajak']) ? trim($input['nomor_nota_retur_pajak']) : $retur['nomor_nota_retur_pajak'];
        $keterangan = isset($input['keterangan']) ? trim($input['keterangan']) : $retur['keterangan'];

        $idKaryawanApproved = $retur['id_karyawan_approved'];
        if (in_array($newStatus, ['DISETUJUI VENDOR', 'DITERIMA']) && empty($idKaryawanApproved)) {
            $idKaryawanApproved = $user['id_karyawan'] ?? 1;
        }

        // Update Header
        $sqlUpdH = "UPDATE retur_po SET 
                        status = ?, 
                        pic_vendor = ?, 
                        nomor_sj_retur = ?, 
                        nomor_nota_retur_pajak = ?, 
                        keterangan = ?, 
                        id_karyawan_approved = ?
                    WHERE id_po_retur = ?";

        $stmtUpdH = $conn->prepare($sqlUpdH);
        $stmtUpdH->bind_param("sssssii", $newStatus, $picVendor, $nomorSjRetur, $nomorNotaReturPajak, $keterangan, $idKaryawanApproved, $idRetur);
        $stmtUpdH->execute();
        $stmtUpdH->close();

        // 2. Update Detail & Stok Barang Gudang (barang_stok)
        $idSite = (int)$retur['id_site'];
        $isTukarUnit = ((int)$retur['kompensasi'] === 1);

        // Ambil data detail lama
        $stmtAllItems = $conn->prepare("SELECT id_po_retur_detail, id_barang, qty_retur, qty_diganti FROM retur_po_detail WHERE id_po_retur = ?");
        $stmtAllItems->bind_param("i", $idRetur);
        $stmtAllItems->execute();
        $existingItems = $stmtAllItems->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtAllItems->close();

        // Petakan item input
        $inputItemsMap = [];
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as $it) {
                $idDet = intval($it['id_po_retur_detail'] ?? 0);
                if ($idDet > 0 && isset($it['qty_diganti'])) {
                    $inputItemsMap[$idDet] = floatval($it['qty_diganti']);
                }
            }
        }

        $stmtUpdItem = $conn->prepare("UPDATE retur_po_detail SET qty_diganti = ? WHERE id_po_retur_detail = ? AND id_po_retur = ?");
        $stmtCheckStok = $conn->prepare("SELECT id_stok, stok FROM barang_stok WHERE id_barang = ? AND id_site = ? LIMIT 1");
        $stmtUpStok = $conn->prepare("UPDATE barang_stok SET stok = GREATEST(0, stok + ?) WHERE id_stok = ?");
        $stmtInsStok = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");

        foreach ($existingItems as $exItem) {
            $idDet = (int)$exItem['id_po_retur_detail'];
            $idBarang = (int)$exItem['id_barang'];
            $qtyRetur = (int)$exItem['qty_retur'];
            $oldQtyGanti = (int)$exItem['qty_diganti'];

            // Tentukan newQtyGanti
            if (isset($inputItemsMap[$idDet])) {
                $newQtyGanti = (int)$inputItemsMap[$idDet];
            } elseif ($newStatus === 'DITERIMA' && $oldQtyGanti <= 0 && $isTukarUnit) {
                $newQtyGanti = $qtyRetur;
            } else {
                $newQtyGanti = $oldQtyGanti;
            }

            $diffStok = $newQtyGanti - $oldQtyGanti;

            // Update qty_diganti pada tabel retur_po_detail
            $stmtUpdItem->bind_param("dii", $newQtyGanti, $idDet, $idRetur);
            $stmtUpdItem->execute();

            // Update stok di tabel barang_stok
            if ($isTukarUnit && $diffStok != 0 && $idSite > 0 && $idBarang > 0) {
                $stmtCheckStok->bind_param("ii", $idBarang, $idSite);
                $stmtCheckStok->execute();
                $stokRow = $stmtCheckStok->get_result()->fetch_assoc();

                if ($stokRow) {
                    $idStok = (int)$stokRow['id_stok'];
                    $stmtUpStok->bind_param("ii", $diffStok, $idStok);
                    $stmtUpStok->execute();
                } else {
                    if ($diffStok > 0) {
                        $stmtInsStok->bind_param("iii", $idBarang, $idSite, $diffStok);
                        $stmtInsStok->execute();
                    }
                }
            }
        }

        $stmtUpdItem->close();
        $stmtCheckStok->close();
        $stmtUpStok->close();
        $stmtInsStok->close();

        // 3. Update receiving_order, receiving_order_detail & request_order jika retur telah disetujui / diterima
        $idRcv = (int)($retur['id_rcv'] ?? 0);
        $idPo = (int)($retur['id_po'] ?? 0);
        $nomorPoRetur = $retur['nomor_po_retur'] ?? '';

        if ($newStatus === 'DITERIMA') {
            if ($isTukarUnit) {
                // A. Tukar Unit: Update receiving_order_detail yang sebelumnya rusak/cacat (status_qc = 0) menjadi pass karena sudah diganti unit baru
                if ($idRcv > 0) {
                    foreach ($existingItems as $exItem) {
                        $idBarangItem = (int)$exItem['id_barang'];
                        $stmtUpdRod = $conn->prepare("UPDATE receiving_order_detail 
                                                     SET status_qc = 1, 
                                                         keterangan = CONCAT(COALESCE(keterangan, ''), ' [Tukar Unit Selesai via Retur ', ?, ']') 
                                                     WHERE id_rcv = ? AND id_barang = ? AND status_qc = 0");
                        if ($stmtUpdRod) {
                            $stmtUpdRod->bind_param("sii", $nomorPoRetur, $idRcv, $idBarangItem);
                            $stmtUpdRod->execute();
                            $stmtUpdRod->close();
                        }
                    }

                    // Update status receiving_order menjadi 1 (Diterima Lengkap / Selesai)
                    $stmtUpdRcv = $conn->prepare("UPDATE receiving_order SET status = 1 WHERE id_rcv = ?");
                    if ($stmtUpdRcv) {
                        $stmtUpdRcv->bind_param("i", $idRcv);
                        $stmtUpdRcv->execute();
                        $stmtUpdRcv->close();
                    }
                }
            } else {
                // B. Potong Tagihan: Catat keterangan bahwa diselesaikan via Potong Tagihan Faktur dan selesaikan RCV
                if ($idRcv > 0) {
                    foreach ($existingItems as $exItem) {
                        $idBarangItem = (int)$exItem['id_barang'];
                        $stmtUpdRod = $conn->prepare("UPDATE receiving_order_detail 
                                                     SET keterangan = CONCAT(COALESCE(keterangan, ''), ' [Potong Tagihan Faktur via Retur ', ?, ']') 
                                                     WHERE id_rcv = ? AND id_barang = ? AND status_qc = 0");
                        if ($stmtUpdRod) {
                            $stmtUpdRod->bind_param("sii", $nomorPoRetur, $idRcv, $idBarangItem);
                            $stmtUpdRod->execute();
                            $stmtUpdRod->close();
                        }
                    }

                    // Update status receiving_order menjadi 1 (Tuntas Diselesaikan via Potong Tagihan)
                    $stmtUpdRcv = $conn->prepare("UPDATE receiving_order SET status = 1 WHERE id_rcv = ?");
                    if ($stmtUpdRcv) {
                        $stmtUpdRcv->bind_param("i", $idRcv);
                        $stmtUpdRcv->execute();
                        $stmtUpdRcv->close();
                    }
                }
            }

            // Update request_order status menjadi 'DITERIMA FULL'
            if ($idPo > 0) {
                $stmtUpdRo = $conn->prepare("UPDATE request_order SET status = 'DITERIMA FULL', tanggal_status = NOW() WHERE id_po = ? AND status = 'DITERIMA SEBAGIAN'");
                if ($stmtUpdRo) {
                    $stmtUpdRo->bind_param("i", $idPo);
                    $stmtUpdRo->execute();
                    $stmtUpdRo->close();
                }
            }
        } elseif ($idRcv > 0 && in_array($newStatus, ['DISETUJUI VENDOR', 'DIKIRIM KE VENDOR'])) {
            $stmtUpdRcv = $conn->prepare("UPDATE receiving_order SET status = 1 WHERE id_rcv = ?");
            if ($stmtUpdRcv) {
                $stmtUpdRcv->bind_param("i", $idRcv);
                $stmtUpdRcv->execute();
                $stmtUpdRcv->close();
            }
        }

        $conn->commit();

        logActivity($conn, [
            'modul' => 'RETUR',
            'aksi' => 'UPDATE_STATUS',
            'id_referensi' => $idRetur,
            'nomor_referensi' => $retur['nomor_po_retur'],
            'deskripsi' => "Memperbarui status Dokumen Retur {$retur['nomor_po_retur']} dari '{$retur['status']}' menjadi '{$newStatus}'",
            'data_sebelumnya' => [
                'status' => $retur['status'],
                'pic_vendor' => $retur['pic_vendor'],
                'nomor_sj_retur' => $retur['nomor_sj_retur']
            ],
            'data_sesudahnya' => [
                'status' => $newStatus,
                'pic_vendor' => $picVendor,
                'nomor_sj_retur' => $nomorSjRetur
            ]
        ]);

        sendJson(true, "Status Dokumen Retur {$retur['nomor_po_retur']} berhasil diperbarui menjadi {$newStatus}.", [
            'id_po_retur' => $idRetur,
            'status' => $newStatus
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal memperbarui status Retur: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 4. DELETE: Hapus Draft Dokumen Retur
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idRetur = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_po_retur']) ? intval($_GET['id_po_retur']) : 0);

    if ($idRetur <= 0) {
        sendJson(false, 'ID Retur tidak valid.', null, 422);
    }

    try {
        $stmtChk = $conn->prepare("SELECT status, nomor_po_retur FROM retur_po WHERE id_po_retur = ?");
        $stmtChk->bind_param("i", $idRetur);
        $stmtChk->execute();
        $retur = $stmtChk->get_result()->fetch_assoc();
        $stmtChk->close();

        if (!$retur) {
            sendJson(false, 'Dokumen Retur tidak ditemukan.', null, 404);
        }

        if ($retur['status'] !== 'DRAFT') {
            sendJson(false, "Dokumen berstatus '{$retur['status']}' tidak dapat dihapus.", null, 403);
        }

        $conn->begin_transaction();

        // Hapus detail
        $stmtDelD = $conn->prepare("DELETE FROM retur_po_detail WHERE id_po_retur = ?");
        $stmtDelD->bind_param("i", $idRetur);
        $stmtDelD->execute();
        $stmtDelD->close();

        // Hapus header
        $stmtDelH = $conn->prepare("DELETE FROM retur_po WHERE id_po_retur = ?");
        $stmtDelH->bind_param("i", $idRetur);
        $stmtDelH->execute();
        $stmtDelH->close();

        $conn->commit();

        logActivity($conn, [
            'modul' => 'RETUR',
            'aksi' => 'DELETE',
            'id_referensi' => $idRetur,
            'nomor_referensi' => $retur['nomor_po_retur'],
            'deskripsi' => "Menghapus draft dokumen Retur PO {$retur['nomor_po_retur']}",
            'data_sebelumnya' => $retur
        ]);

        sendJson(true, "Draft Retur PO {$retur['nomor_po_retur']} berhasil dihapus.");

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal menghapus Retur: ' . $e->getMessage(), null, 500);
    }
}
