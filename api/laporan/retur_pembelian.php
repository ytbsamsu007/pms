<?php
/**
 * REST API: Laporan Rekapitulasi Retur Pembelian
 * Endpoint: /api/laporan/retur_pembelian.php
 * Path: api/laporan/retur_pembelian.php
 * Akses: ADMIN, LOGISTIK, PURCHASING, MEKANIK, MANAGER, FINANCE
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

$user = apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MEKANIK, ROLE_MANAGER, ROLE_FINANCE]);

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
    $idRetur = isset($_GET['id_po_retur']) && is_numeric($_GET['id_po_retur']) ? (int)$_GET['id_po_retur'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

    // 1. DETAIL SINGLE RETUR UNTUK POPUP MODAL
    if ($idRetur > 0) {
        $sqlH = "SELECT r.*,
                        v.kode_vendor, v.nama_perusahaan AS nama_vendor, v.no_telepon AS telepon_vendor, v.email AS email_vendor,
                        po.nomor_po, po.tanggal_po,
                        rcv.nomor_rcv, rcv.nomor_sj AS nomor_sj_rcv, rcv.tanggal_diterima,
                        s.nama_site,
                        k_buat.nama_karyawan AS nama_pembuat,
                        k_app.nama_karyawan AS nama_penyetuju
                 FROM retur_po r
                 LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                 LEFT JOIN purchase_order po ON r.id_po = po.id_po
                 LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                 LEFT JOIN site s ON r.id_site = s.id_site
                 LEFT JOIN karyawan k_buat ON r.id_karyawan = k_buat.id_karyawan
                 LEFT JOIN karyawan k_app ON r.id_karyawan_approved = k_app.id_karyawan
                 WHERE r.id_po_retur = ?";
        $stmtH = $conn->prepare($sqlH);
        $stmtH->bind_param("i", $idRetur);
        $stmtH->execute();
        $header = $stmtH->get_result()->fetch_assoc();
        $stmtH->close();

        if (!$header) {
            sendJson(false, 'Data Retur tidak ditemukan.', null, 404);
        }

        // Ambil item detail barang yang diretur
        $sqlD = "SELECT d.*, b.kode_barang, b.nama_barang, b.satuan AS master_satuan, b.serial_number
                 FROM retur_po_detail d
                 LEFT JOIN barang b ON d.id_barang = b.id_barang
                 WHERE d.id_po_retur = ?
                 ORDER BY d.id_po_retur_detail ASC";
        $stmtD = $conn->prepare($sqlD);
        $stmtD->bind_param("i", $idRetur);
        $stmtD->execute();
        $resD = $stmtD->get_result();
        $items = [];
        while ($it = $resD->fetch_assoc()) {
            $it['foto_url'] = !empty($it['foto_bukti']) ? BASE_URL . '/uploads/retur/' . $it['foto_bukti'] : null;
            $items[] = $it;
        }
        $stmtD->close();

        // Hitung Subtotal jika 0
        $subtotal = isset($header['subtotal']) && (float)$header['subtotal'] > 0 ? (float)$header['subtotal'] : 0;
        if ($subtotal == 0) {
            foreach ($items as $it) {
                $subtotal += (float)($it['subtotal'] ?? ((float)$it['qty_retur'] * (float)($it['harga_satuan'] ?? 0)));
            }
        }

        foreach ($items as &$it) {
            $it['harga_satuan_formatted'] = 'Rp ' . number_format((float)($it['harga_satuan'] ?? 0), 0, ',', '.');
            $itSubtotal = (float)($it['subtotal'] ?? ((float)$it['qty_retur'] * (float)($it['harga_satuan'] ?? 0)));
            $it['subtotal_formatted'] = 'Rp ' . number_format($itSubtotal, 0, ',', '.');
        }
        unset($it);

        $header['items'] = $items;
        $header['subtotal_calculated'] = $subtotal;
        $header['subtotal_formatted'] = 'Rp ' . number_format($subtotal, 0, ',', '.');
        $header['nominal_pajak_formatted'] = 'Rp ' . number_format((float)($header['nominal_pajak'] ?? 0), 0, ',', '.');
        $header['rate_pajak_formatted'] = number_format((float)($header['rate_pajak'] ?? 0), 0) . '%';
        $header['biaya_retur_formatted'] = 'Rp ' . number_format((float)($header['biaya_retur'] ?? 0), 0, ',', '.');
        $header['total_formatted'] = 'Rp ' . number_format((float)($header['total'] ?? 0), 0, ',', '.');
        $header['tanggal_formatted'] = $header['tanggal_po_retur'] ? date('d-m-Y H:i', strtotime($header['tanggal_po_retur'])) : '-';

        sendJson(true, 'Detail Retur PO berhasil diambil.', $header);
    }

    // 2. LISTING REKAPITULASI RETUR PEMBELIAN DENGAN FILTER & PAGINATION
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
    $status = trim($_GET['status'] ?? '');
    $q = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
    $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
    $limit = max(1, min(100, isset($_GET['limit']) ? (int)$_GET['limit'] : 20));
    $offset = ($page - 1) * $limit;

    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $where .= " AND DATE(r.tanggal_po_retur) >= ? ";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where .= " AND DATE(r.tanggal_po_retur) <= ? ";
        $params[] = $endDate;
        $types .= "s";
    }

    if ($idVendor > 0) {
        $where .= " AND r.id_vendor = ? ";
        $params[] = $idVendor;
        $types .= "i";
    }

    if (!empty($status)) {
        $where .= " AND r.status = ? ";
        $params[] = $status;
        $types .= "s";
    }

    if (!empty($q)) {
        $where .= " AND (r.nomor_po_retur LIKE ? OR v.nama_perusahaan LIKE ? OR v.kode_vendor LIKE ? OR po.nomor_po LIKE ?) ";
        $searchParam = "%{$q}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ssss";
    }

    // Hitung total data
    $sqlCount = "SELECT COUNT(*) AS total
                 FROM retur_po r
                 LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                 LEFT JOIN purchase_order po ON r.id_po = po.id_po
                 {$where}";
    $stmtC = $conn->prepare($sqlCount);
    if (!empty($params)) {
        $stmtC->bind_param($types, ...$params);
    }
    $stmtC->execute();
    $totalRows = (int)$stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();

    // Query Data Listing
    $sqlList = "SELECT r.id_po_retur, r.nomor_po_retur, r.tanggal_po_retur, r.kompensasi, r.status,
                       r.total, r.nominal_pajak, r.rate_pajak, r.nomor_sj_retur, r.biaya_retur,
                       r.id_karyawan, r.id_karyawan_approved,
                       v.id_vendor, v.kode_vendor, v.nama_perusahaan AS nama_vendor,
                       po.id_po, po.nomor_po,
                       rcv.id_rcv, rcv.nomor_rcv,
                       s.nama_site,
                       k.nama_karyawan AS nama_pembuat,
                       k_app.nama_karyawan AS nama_penyetuju,
                       COUNT(d.id_po_retur_detail) AS total_items,
                       COALESCE(SUM(d.qty_retur), 0) AS total_qty_retur
                FROM retur_po r
                LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                LEFT JOIN purchase_order po ON r.id_po = po.id_po
                LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                LEFT JOIN site s ON r.id_site = s.id_site
                LEFT JOIN karyawan k ON r.id_karyawan = k.id_karyawan
                LEFT JOIN karyawan k_app ON r.id_karyawan_approved = k_app.id_karyawan
                LEFT JOIN retur_po_detail d ON r.id_po_retur = d.id_po_retur
                {$where}
                GROUP BY r.id_po_retur
                ORDER BY r.id_po_retur DESC
                LIMIT ? OFFSET ?";

    $paramsList = $params;
    $paramsList[] = $limit;
    $paramsList[] = $offset;
    $typesList = $types . "ii";

    $stmtL = $conn->prepare($sqlList);
    $stmtL->bind_param($typesList, ...$paramsList);
    $stmtL->execute();
    $resL = $stmtL->get_result();
    $items = [];

    while ($r = $resL->fetch_assoc()) {
        $r['tanggal_formatted'] = $r['tanggal_po_retur'] ? date('d/m/Y', strtotime($r['tanggal_po_retur'])) : '-';
        $r['total_formatted'] = 'Rp ' . number_format((float)$r['total'], 0, ',', '.');
        $r['biaya_retur_formatted'] = 'Rp ' . number_format((float)$r['biaya_retur'], 0, ',', '.');
        $r['kompensasi_label'] = ((int)$r['kompensasi'] === 1) ? 'Tukar Unit' : 'Potong Tagihan';
        $items[] = $r;
    }
    $stmtL->close();

    // Data Vendor untuk Dropdown Filter Searchable
    $vendors = [];
    $vRes = $conn->query("SELECT id_vendor, kode_vendor, nama_perusahaan FROM vendor ORDER BY nama_perusahaan ASC");
    if ($vRes) {
        while ($v = $vRes->fetch_assoc()) {
            $vendors[] = $v;
        }
    }

    $generatedBy = !empty($user['nama']) ? $user['nama'] : (!empty($user['username']) ? $user['username'] : 'Petugas');

    sendJson(true, 'Data Laporan Rekapitulasi Retur Pembelian berhasil diambil.', [
        'items' => $items,
        'vendors' => $vendors,
        'meta' => [
            'generated_by' => $generatedBy,
            'generated_at' => date('d/m/Y H:i') . ' WITA',
            'tanggal_cetak' => date('d/m/Y')
        ],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_items' => $totalRows,
            'total_pages' => ($totalRows > 0) ? (int)ceil($totalRows / $limit) : 1
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
