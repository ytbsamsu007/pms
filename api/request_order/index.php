<?php
/**
 * API Modul Request Order (RO)
 * Endpoint:
 * - GET    /api/request_order/index.php            : Mengambil daftar RO (List, Search, Filter, Pagination, Summary Metrics)
 * - GET    /api/request_order/index.php?id={id}    : Mengambil detail lengkap 1 RO beserta rincian item
 * - DELETE /api/request_order/index.php?id={id}    : Menghapus RO (hanya jika status DRAFT)
 * - POST   /api/request_order/index.php?action=cancel : Membatalkan RO (status menjadi BATAL)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/session.php';

// Verifikasi Sesi & Hak Akses
$currentUser = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// 1. GET: List RO atau Detail RO
// -------------------------------------------------------------
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['id_request']) ? (int)$_GET['id_request'] : null);

    // A. DETAIL RO TUNGGAL
    if ($id && $id > 0) {
        $stmt = $conn->prepare("SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.id_karyawan, ro.id_site, 
                                       ro.status, ro.prioritas, ro.id_vendor, ro.id_karyawan_approved, ro.tanggal_status, ro.keterangan, ro.id_po,
                                       COALESCE(kry.nama_karyawan, u.nama_users, 'Karyawan') AS nama_karyawan,
                                       COALESCE(kry.kode_karyawan, 'KRY') AS kode_karyawan,
                                       COALESCE(j.nama_jabatan, 'Mekanik / Staf') AS nama_jabatan,
                                       COALESCE(d.nama_divisi, '-') AS nama_divisi,
                                       COALESCE(appr.nama_karyawan, u_appr.nama_users) AS nama_approver,
                                       s.nama_site, s.kode_site, s.alamat AS alamat_site,
                                       v.nama_perusahaan AS nama_vendor, v.kode_vendor, v.term_of_payment AS vendor_term_of_payment
                                FROM request_order ro
                                LEFT JOIN karyawan kry ON ro.id_karyawan = kry.id_karyawan
                                LEFT JOIN users u ON ro.id_karyawan = u.id_users
                                LEFT JOIN karyawan appr ON ro.id_karyawan_approved = appr.id_karyawan
                                LEFT JOIN users u_appr ON ro.id_karyawan_approved = u_appr.id_users
                                LEFT JOIN jabatan j ON kry.id_jabatan = j.id_jabatan
                                LEFT JOIN divisi d ON kry.id_divisi = d.id_divisi
                                LEFT JOIN site s ON ro.id_site = s.id_site
                                LEFT JOIN vendor v ON ro.id_vendor = v.id_vendor
                                WHERE ro.id_request = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res || $res->num_rows === 0) {
            jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
        }

        $header = $res->fetch_assoc();
        $stmt->close();

        $idVendorRo = $header['id_vendor'] ? (int)$header['id_vendor'] : 0;

        // Ambil rincian material barang lengkap dengan stok dan harga set vendor terakhir
        $stmtItems = $conn->prepare("SELECT rod.id_request_detail, rod.id_request, rod.id_barang, 
                                            rod.kode_barang, rod.nama_barang, rod.qty, rod.satuan, rod.harga, rod.subtotal,
                                            b.foto1, b.nama_barang AS master_nama_barang, b.PPnBM, b.rate_PPnBM, m.nama_merk, k.nama_kategori,
                                            COALESCE((SELECT SUM(stok) FROM barang_stok bs WHERE bs.id_barang = rod.id_barang), 0) AS total_stok,
                                            COALESCE(
                                                (SELECT bhv.harga_set FROM barang_hargavendor bhv WHERE bhv.id_barang = rod.id_barang AND bhv.id_vendor = ? ORDER BY bhv.berlaku DESC, bhv.id_harga DESC LIMIT 1),
                                                (SELECT bhv.harga_set FROM barang_hargavendor bhv WHERE bhv.id_barang = rod.id_barang ORDER BY bhv.berlaku DESC, bhv.id_harga DESC LIMIT 1),
                                                0
                                            ) AS harga_set_terakhir
                                     FROM request_order_detail rod
                                     LEFT JOIN barang b ON rod.id_barang = b.id_barang
                                     LEFT JOIN merk_barang m ON b.id_merk = m.id_merk
                                     LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori
                                     WHERE rod.id_request = ?
                                     ORDER BY rod.id_request_detail ASC");
        $stmtItems->bind_param("ii", $idVendorRo, $id);
        $stmtItems->execute();
        $resItems = $stmtItems->get_result();

        $items = [];
        $totalQty = 0;
        $grandTotal = 0;

        while ($item = $resItems->fetch_assoc()) {
            $qty = (float)$item['qty'];
            $harga = (float)$item['harga'];
            $hargaSet = (float)($item['harga_set_terakhir'] ?? 0);
            $subtotal = (float)$item['subtotal'];
            $totalQty += $qty;
            $grandTotal += $subtotal;

            $items[] = [
                'id_request_detail' => (int)$item['id_request_detail'],
                'id_barang' => $item['id_barang'] ? (int)$item['id_barang'] : null,
                'kode_barang' => $item['kode_barang'] ?? '',
                'nama_barang' => $item['nama_barang'],
                'foto1' => $item['foto1'] ?? null,
                'nama_merk' => $item['nama_merk'] ?? 'Umum',
                'nama_kategori' => $item['nama_kategori'] ?? 'Material',
                'total_stok' => (int)($item['total_stok'] ?? 0),
                'qty' => $qty,
                'satuan' => $item['satuan'] ?? 'PCS',
                'harga' => $harga,
                'harga_set' => $hargaSet,
                'subtotal' => $subtotal,
                'PPnBM' => (int)($item['PPnBM'] ?? 0),
                'ppnbm' => (int)($item['PPnBM'] ?? 0),
                'rate_PPnBM' => (float)($item['rate_PPnBM'] ?? 0),
                'rate_ppnbm' => (float)($item['rate_PPnBM'] ?? 0)
            ];
        }
        $stmtItems->close();

        // Ambil info PO, Receiving, dan Retur PO jika RO sudah terhubung ke PO
        $header['po_info'] = null;
        $header['receiving_info'] = null;
        $header['retur_info'] = null;

        $idPoRo = (int)($header['id_po'] ?? 0);
        if ($idPoRo > 0) {
            $stmtPo = $conn->prepare("SELECT po.id_po, po.nomor_po, po.tanggal_po, po.status AS status_po, po.id_receiving 
                                      FROM purchase_order po WHERE po.id_po = ? LIMIT 1");
            $stmtPo->bind_param("i", $idPoRo);
            $stmtPo->execute();
            $poData = $stmtPo->get_result()->fetch_assoc();
            $stmtPo->close();

            if ($poData) {
                $header['po_info'] = $poData;
                $idRcv = (int)($poData['id_receiving'] ?? 0);

                // Info Receiving
                $stmtRcv = $conn->prepare("SELECT rcv.id_rcv, rcv.nomor_rcv, rcv.nomor_sj, rcv.tanggal_rcv, rcv.tanggal_diterima, rcv.status AS status_rcv
                                           FROM receiving_order rcv 
                                           WHERE rcv.id_po = ? " . ($idRcv > 0 ? "OR rcv.id_rcv = {$idRcv}" : "") . " 
                                           ORDER BY rcv.id_rcv DESC LIMIT 1");
                $stmtRcv->bind_param("i", $idPoRo);
                $stmtRcv->execute();
                $rcvData = $stmtRcv->get_result()->fetch_assoc();
                $stmtRcv->close();

                if ($rcvData) {
                    $header['receiving_info'] = $rcvData;
                    if ($idRcv <= 0) $idRcv = (int)$rcvData['id_rcv'];
                }

                // Info Retur PO
                $stmtRet = $conn->prepare("SELECT rp.id_po_retur, rp.nomor_po_retur, rp.tanggal_po_retur, rp.kompensasi, rp.status AS status_retur, rp.nomor_sj_retur, rp.total
                                           FROM retur_po rp 
                                           WHERE rp.id_po = ? " . ($idRcv > 0 ? "OR rp.id_rcv = {$idRcv}" : "") . " 
                                           ORDER BY rp.id_po_retur DESC LIMIT 1");
                $stmtRet->bind_param("i", $idPoRo);
                $stmtRet->execute();
                $retData = $stmtRet->get_result()->fetch_assoc();
                $stmtRet->close();

                if ($retData) {
                    $header['retur_info'] = $retData;
                }
            }
        }

        $header['items'] = $items;
        $header['total_items'] = count($items);
        $header['total_qty'] = $totalQty;
        $header['grand_total'] = $grandTotal;

        jsonResponse(true, 'Detail Request Order berhasil dimuat.', $header);
    }

    // B. LIST REQUEST ORDER
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $siteId = isset($_GET['site_id']) && is_numeric($_GET['site_id']) ? (int)$_GET['site_id'] : null;
    $prioritas = trim($_GET['prioritas'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 20;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    // Role filtering: 
    // 1. Mekanik: hanya melihat pengajuan dari site-nya atau yang dibuat olehnya
    if ($currentUser['role'] === ROLE_MEKANIK && !empty($currentUser['id_karyawan'])) {
        if (!empty($currentUser['id_site'])) {
            $whereSql .= " AND (ro.id_karyawan = ? OR ro.id_site = ?)";
            $params[] = (int)$currentUser['id_karyawan'];
            $params[] = (int)$currentUser['id_site'];
            $types .= "ii";
        } else {
            $whereSql .= " AND ro.id_karyawan = ?";
            $params[] = (int)$currentUser['id_karyawan'];
            $types .= "i";
        }
    }

    // 2. Purchasing: HANYA boleh melihat RO yang SUDAH di-approve oleh Logistik (status 'DISETUJUI LOGISTIK') atau status selesai
    // Purchasing TIDAK boleh melihat RO yang belum di-approve oleh Logistik ('DRAFT', 'TERKIRIM', 'TIDAK DISETUJUI LOGISTIK')
    if ($currentUser['role'] === ROLE_PURCHASING) {
        $whereSql .= " AND ro.status IN ('DISETUJUI LOGISTIK', 'DISETUJUI PURCHASING', 'TIDAK DISETUJUI PURCHASING', 'BATAL')";
    }

    if (!empty($search)) {
        $searchWild = "%" . $search . "%";
        
        // Jika pencarian berupa angka (misal: "0001", "1", dsb), cocokkan 4 digit terakhir nomor RO (RO-YYMM-XXXX) & padding
        if (is_numeric($search)) {
            $padded = str_pad($search, 4, '0', STR_PAD_LEFT);
            $paddedWild = "%" . $padded;
            $whereSql .= " AND (ro.nomor LIKE ? OR ro.nomor LIKE ? OR RIGHT(ro.nomor, 4) = ? OR kry.nama_karyawan LIKE ? OR kry.kode_karyawan LIKE ? OR ro.keterangan LIKE ?)";
            $params[] = $searchWild;
            $params[] = $paddedWild;
            $params[] = $padded;
            $params[] = $searchWild;
            $params[] = $searchWild;
            $params[] = $searchWild;
            $types .= "ssssss";
        } else {
            $whereSql .= " AND (ro.nomor LIKE ? OR kry.nama_karyawan LIKE ? OR kry.kode_karyawan LIKE ? OR ro.keterangan LIKE ?)";
            for ($i = 0; $i < 4; $i++) {
                $params[] = $searchWild;
                $types .= "s";
            }
        }
    }

    $validStatuses = ['DRAFT', 'TERKIRIM', 'DISETUJUI LOGISTIK', 'TIDAK DISETUJUI LOGISTIK', 'DISETUJUI PURCHASING', 'TIDAK DISETUJUI PURCHASING', 'BATAL'];
    if (!empty($status) && in_array(strtoupper($status), $validStatuses)) {
        $whereSql .= " AND ro.status = ?";
        $params[] = strtoupper($status);
        $types .= "s";
    }

    if ($siteId !== null && $siteId > 0) {
        $whereSql .= " AND ro.id_site = ?";
        $params[] = $siteId;
        $types .= "i";
    }

    if (!empty($prioritas) && in_array(strtoupper($prioritas), ['NORMAL', 'URGENT'])) {
        $whereSql .= " AND ro.prioritas = ?";
        $params[] = strtoupper($prioritas);
        $types .= "s";
    }

    if (!empty($startDate)) {
        $whereSql .= " AND DATE(ro.tanggal_ro) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $whereSql .= " AND DATE(ro.tanggal_ro) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    // 1. Hitung Total Records
    $countSql = "SELECT COUNT(*) as total FROM request_order ro 
                 LEFT JOIN karyawan kry ON ro.id_karyawan = kry.id_karyawan" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    // 2. Query Summary Metrics (Statistik Status)
    $metricsSql = "SELECT 
        COUNT(*) as total_ro,
        SUM(CASE WHEN status = 'DRAFT' THEN 1 ELSE 0 END) as total_draft,
        SUM(CASE WHEN status = 'TERKIRIM' THEN 1 ELSE 0 END) as total_terkirim,
        SUM(CASE WHEN status = 'DISETUJUI LOGISTIK' THEN 1 ELSE 0 END) as total_disetujui_logistik,
        SUM(CASE WHEN status = 'DISETUJUI PURCHASING' THEN 1 ELSE 0 END) as total_disetujui_purchasing,
        SUM(CASE WHEN status IN ('TIDAK DISETUJUI LOGISTIK', 'TIDAK DISETUJUI PURCHASING') THEN 1 ELSE 0 END) as total_ditolak,
        SUM(CASE WHEN status = 'BATAL' THEN 1 ELSE 0 END) as total_batal,
        SUM(CASE WHEN prioritas = 'URGENT' AND status IN ('DRAFT', 'TERKIRIM', 'DISETUJUI LOGISTIK') THEN 1 ELSE 0 END) as total_urgent
    FROM request_order ro";
    
    // Sesuaikan filter role untuk metrik jika mekanik atau purchasing
    if ($currentUser['role'] === ROLE_MEKANIK && !empty($currentUser['id_karyawan'])) {
        if (!empty($currentUser['id_site'])) {
            $metricsSql .= " WHERE (ro.id_karyawan = " . (int)$currentUser['id_karyawan'] . " OR ro.id_site = " . (int)$currentUser['id_site'] . ")";
        } else {
            $metricsSql .= " WHERE ro.id_karyawan = " . (int)$currentUser['id_karyawan'];
        }
    } else if ($currentUser['role'] === ROLE_PURCHASING) {
        $metricsSql .= " WHERE ro.status IN ('DISETUJUI LOGISTIK', 'DISETUJUI PURCHASING', 'TIDAK DISETUJUI PURCHASING', 'BATAL')";
    }
    
    $resMetrics = $conn->query($metricsSql);
    $metrics = $resMetrics ? $resMetrics->fetch_assoc() : [
        'total_ro' => 0, 'total_draft' => 0, 'total_terkirim' => 0, 
        'total_disetujui_logistik' => 0, 'total_disetujui_purchasing' => 0, 'total_ditolak' => 0, 'total_batal' => 0, 'total_urgent' => 0
    ];

    // 3. Query List Data RO dengan agregasi item + info PO & Retur
    $sql = "SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.id_karyawan, ro.id_site, 
                   ro.status, ro.prioritas, ro.id_vendor, ro.id_karyawan_approved, ro.tanggal_status, ro.keterangan, ro.id_po,
                   COALESCE(kry.nama_karyawan, u.nama_users, 'Karyawan') AS nama_karyawan,
                   COALESCE(kry.kode_karyawan, 'KRY') AS kode_karyawan,
                   COALESCE(j.nama_jabatan, 'Mekanik / Staf') AS nama_jabatan,
                   COALESCE(d.nama_divisi, '-') AS nama_divisi,
                   s.nama_site, s.kode_site,
                   v.nama_perusahaan AS nama_vendor,
                   COALESCE(appr.nama_karyawan, u_appr.nama_users) AS nama_approver,
                   COUNT(rod.id_request_detail) AS total_items,
                   COALESCE(SUM(rod.qty), 0) AS total_qty,
                   COALESCE(SUM(rod.subtotal), 0) AS grand_total,
                   (SELECT po_sub.nomor_po FROM purchase_order po_sub WHERE po_sub.id_po = ro.id_po LIMIT 1) AS nomor_po,
                   (SELECT rcv_sub.nomor_rcv FROM receiving_order rcv_sub WHERE rcv_sub.id_po = ro.id_po LIMIT 1) AS nomor_rcv,
                   (SELECT CONCAT_WS('|', rp.nomor_po_retur, rp.status, rp.kompensasi) 
                    FROM retur_po rp 
                    WHERE rp.id_po = ro.id_po OR rp.id_rcv = (SELECT po_sub.id_receiving FROM purchase_order po_sub WHERE po_sub.id_po = ro.id_po)
                    ORDER BY rp.id_po_retur DESC LIMIT 1) AS retur_raw
            FROM request_order ro
            LEFT JOIN karyawan kry ON ro.id_karyawan = kry.id_karyawan
            LEFT JOIN users u ON ro.id_karyawan = u.id_users
            LEFT JOIN karyawan appr ON ro.id_karyawan_approved = appr.id_karyawan
            LEFT JOIN users u_appr ON ro.id_karyawan_approved = u_appr.id_users
            LEFT JOIN jabatan j ON kry.id_jabatan = j.id_jabatan
            LEFT JOIN divisi d ON kry.id_divisi = d.id_divisi
            LEFT JOIN site s ON ro.id_site = s.id_site
            LEFT JOIN vendor v ON ro.id_vendor = v.id_vendor
            LEFT JOIN request_order_detail rod ON ro.id_request = rod.id_request"
            . $whereSql . " 
            GROUP BY ro.id_request 
            ORDER BY ro.id_request DESC 
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
        $returInfo = null;
        if (!empty($row['retur_raw'])) {
            $parts = explode('|', $row['retur_raw']);
            $returInfo = [
                'nomor_retur' => $parts[0] ?? '',
                'status' => $parts[1] ?? '',
                'kompensasi' => isset($parts[2]) ? (int)$parts[2] : null,
            ];
        }

        $items[] = [
            'id_request' => (int)$row['id_request'],
            'nomor' => $row['nomor'],
            'tanggal_ro' => $row['tanggal_ro'],
            'status' => $row['status'],
            'id_vendor' => $row['id_vendor'] ? (int)$row['id_vendor'] : null,
            'id_karyawan_approved' => $row['id_karyawan_approved'] ? (int)$row['id_karyawan_approved'] : null,
            'nama_approver' => $row['nama_approver'] ?? null,
            'prioritas' => $row['prioritas'] ?? 'NORMAL',
            'id_karyawan' => (int)$row['id_karyawan'],
            'nama_karyawan' => $row['nama_karyawan'] ?? 'Karyawan',
            'kode_karyawan' => $row['kode_karyawan'] ?? '',
            'nama_jabatan' => $row['nama_jabatan'] ?? '',
            'nama_divisi' => $row['nama_divisi'] ?? '',
            'id_site' => (int)$row['id_site'],
            'nama_site' => $row['nama_site'] ?? '-',
            'kode_site' => $row['kode_site'] ?? '',
            'nama_vendor' => $row['nama_vendor'] ?? null,
            'keterangan' => $row['keterangan'] ?? '',
            'total_items' => (int)$row['total_items'],
            'total_qty' => (float)$row['total_qty'],
            'grand_total' => (float)$row['grand_total'],
            'id_po' => $row['id_po'] ? (int)$row['id_po'] : null,
            'nomor_po' => $row['nomor_po'] ?? null,
            'nomor_rcv' => $row['nomor_rcv'] ?? null,
            'retur_info' => $returInfo
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Daftar Request Order berhasil dimuat.', [
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

// -------------------------------------------------------------
// 2. DELETE: Hapus Request Order (Hanya jika status DRAFT)
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        jsonResponse(false, 'ID Request Order tidak valid.', null, 400);
    }

    $chk = $conn->prepare("SELECT id_request, nomor, status FROM request_order WHERE id_request = ? LIMIT 1");
    $chk->bind_param("i", $id);
    $chk->execute();
    $resChk = $chk->get_result();

    if (!$resChk || $resChk->num_rows === 0) {
        jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
    }
    $roData = $resChk->fetch_assoc();
    $chk->close();

    if ($roData['status'] !== 'DRAFT') {
        jsonResponse(false, 'Hanya Request Order dengan status DRAFT yang dapat dihapus.', null, 422);
    }

    $conn->begin_transaction();
    try {
        $delDetail = $conn->prepare("DELETE FROM request_order_detail WHERE id_request = ?");
        $delDetail->bind_param("i", $id);
        $delDetail->execute();
        $delDetail->close();

        $delHeader = $conn->prepare("DELETE FROM request_order WHERE id_request = ?");
        $delHeader->bind_param("i", $id);
        $delHeader->execute();
        $delHeader->close();

        $conn->commit();

        require_once __DIR__ . '/../../config/activity_logger.php';
        logActivity($conn, [
            'modul'           => 'REQUEST_ORDER',
            'aksi'            => 'DELETE',
            'id_referensi'    => $id,
            'nomor_referensi' => $roData['nomor'],
            'deskripsi'       => "Menghapus Draft Request Order {$roData['nomor']}.",
            'data_sebelumnya' => $roData
        ]);

        jsonResponse(true, "Draft Request Order {$roData['nomor']} berhasil dihapus.");
    } catch (Exception $e) {
        $conn->rollback();
        jsonResponse(false, 'Gagal menghapus Draft RO: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 3. POST / PATCH: Pembatalan RO (Status BATAL)
// -------------------------------------------------------------
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = isset($input['id_request']) ? (int)$input['id_request'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

    if ($action === 'cancel' || ($input['status'] ?? '') === 'BATAL') {
        if ($id <= 0) {
            jsonResponse(false, 'ID Request Order tidak valid.', null, 400);
        }

        $stmt = $conn->prepare("SELECT id_request, nomor, status FROM request_order WHERE id_request = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $ro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ro) {
            jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
        }

        if (in_array($ro['status'], ['DISETUJUI PURCHASING', 'BATAL'])) {
            jsonResponse(false, "Request Order dengan status {$ro['status']} tidak dapat dibatalkan.", null, 422);
        }

        $now = date('Y-m-d H:i:s');
        $up = $conn->prepare("UPDATE request_order SET status = 'BATAL', tanggal_status = ? WHERE id_request = ?");
        $up->bind_param("si", $now, $id);
        
        if ($up->execute()) {
            $up->close();

            require_once __DIR__ . '/../../config/activity_logger.php';
            logActivity($conn, [
                'modul'           => 'REQUEST_ORDER',
                'aksi'            => 'BATAL',
                'id_referensi'    => $id,
                'nomor_referensi' => $ro['nomor'],
                'deskripsi'       => "Membatalkan Request Order {$ro['nomor']} (Status sebelumnya: {$ro['status']}).",
                'data_sebelumnya' => ['status' => $ro['status']],
                'data_sesudahnya' => ['status' => 'BATAL']
            ]);

            jsonResponse(true, "Request Order {$ro['nomor']} berhasil dibatalkan.");
        } else {
            $up->close();
            jsonResponse(false, 'Gagal membatalkan Request Order.', null, 500);
        }
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
