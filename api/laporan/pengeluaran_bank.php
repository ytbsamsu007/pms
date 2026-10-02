<?php
/**
 * REST API: Laporan Rekapitulasi & Rincian Pengeluaran Kas / Bank
 * Endpoint: /api/laporan/pengeluaran_bank.php
 * Path: api/laporan/pengeluaran_bank.php
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

$user = apiAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING]);

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
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $bankPengirim = trim($_GET['bank_pengirim'] ?? '');
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Siapkan WHERE clause dinamis
    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if (!empty($startDate)) {
        $where .= " AND DATE(ppd.tanggal_bayar) >= ? ";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where .= " AND DATE(ppd.tanggal_bayar) <= ? ";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($bankPengirim)) {
        $where .= " AND ppd.bank_pengirim = ? ";
        $params[] = $bankPengirim;
        $types .= "s";
    }

    if (!empty($search)) {
        $where .= " AND (ppd.kode_pembayaran LIKE ? OR fp.nomor_faktur LIKE ? OR po.nomor_po LIKE ? OR v.nama_perusahaan LIKE ? OR ppd.no_ref LIKE ? OR ppd.bank_pengirim LIKE ?) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        $types .= "ssssss";
    }

    // 1. QUERY SUMMARY PER BANK & NO REKENING (REKAPITULASI)
    $sqlSummary = "SELECT
                        ppd.bank_pengirim,
                        COALESCE(ppd.norek_pengirim, '-') AS norek_pengirim,
                        COUNT(ppd.id_pembayaran_detail) AS total_transaksi,
                        COALESCE(SUM(ppd.nominal_pengiriman), 0) AS total_nominal,
                        COALESCE(SUM(ppd.biaya_admin), 0) AS total_biaya_admin,
                        COALESCE(SUM(ppd.nominal_pengiriman + ppd.biaya_admin), 0) AS total_pembayaran
                   FROM
                        payment_purchase pp
                        INNER JOIN payment_purchase_detail ppd ON pp.id_pembayaran = ppd.id_pembayaran
                        INNER JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
                        LEFT JOIN purchase_order po ON fp.id_po = po.id_po
                        LEFT JOIN vendor v ON fp.id_vendor = v.id_vendor
                   {$where}
                   GROUP BY
                        ppd.bank_pengirim, ppd.norek_pengirim
                   ORDER BY
                        total_pembayaran DESC, ppd.bank_pengirim ASC, ppd.norek_pengirim ASC";

    $stmtSum = $conn->prepare($sqlSummary);
    if (!empty($params)) {
        $stmtSum->bind_param($types, ...$params);
    }
    $stmtSum->execute();
    $resSum = $stmtSum->get_result();

    $summary = [];
    $grandTotalNominal = 0;
    $grandTotalAdmin = 0;
    $grandTotalPembayaran = 0;
    $grandTotalTransaksi = 0;

    while ($row = $resSum->fetch_assoc()) {
        $nom = (float)$row['total_nominal'];
        $adm = (float)$row['total_biaya_admin'];
        $tot = (float)$row['total_pembayaran'];
        $trx = (int)$row['total_transaksi'];

        $grandTotalNominal += $nom;
        $grandTotalAdmin += $adm;
        $grandTotalPembayaran += $tot;
        $grandTotalTransaksi += $trx;

        $row['total_nominal'] = $nom;
        $row['total_biaya_admin'] = $adm;
        $row['total_pembayaran'] = $tot;
        $row['total_transaksi'] = $trx;
        $row['formatted_nominal'] = 'Rp ' . number_format($nom, 0, ',', '.');
        $row['formatted_admin'] = 'Rp ' . number_format($adm, 0, ',', '.');
        $row['formatted_total'] = 'Rp ' . number_format($tot, 0, ',', '.');

        $summary[] = $row;
    }
    $stmtSum->close();

    // 2. QUERY DETAIL TRANSAKSI
    $sqlDetails = "SELECT
                        ppd.id_pembayaran_detail,
                        ppd.kode_pembayaran,
                        ppd.tanggal_bayar,
                        ppd.bank_pengirim,
                        ppd.norek_pengirim,
                        ppd.an_pengirim,
                        ppd.nominal_pengiriman,
                        ppd.biaya_admin,
                        (ppd.nominal_pengiriman + ppd.biaya_admin) AS total_bayar,
                        ppd.no_ref,
                        ppd.bank_tujuan,
                        ppd.norek_tujuan,
                        ppd.an_pengiriman,
                        ppd.keterangan,
                        fp.id_faktur,
                        fp.nomor_faktur,
                        fp.nomor_faktur_vendor,
                        po.id_po,
                        po.nomor_po,
                        v.id_vendor,
                        v.nama_perusahaan AS nama_vendor,
                        s.nama_site,
                        k.nama_karyawan AS nama_pembuat
                   FROM
                        payment_purchase pp
                        INNER JOIN payment_purchase_detail ppd ON pp.id_pembayaran = ppd.id_pembayaran
                        INNER JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
                        LEFT JOIN purchase_order po ON fp.id_po = po.id_po
                        LEFT JOIN vendor v ON fp.id_vendor = v.id_vendor
                        LEFT JOIN site s ON fp.id_site = s.id_site
                        LEFT JOIN karyawan k ON ppd.id_karyawan = k.id_karyawan
                   {$where}
                   ORDER BY
                        ppd.tanggal_bayar DESC, ppd.id_pembayaran_detail DESC";

    $stmtDet = $conn->prepare($sqlDetails);
    if (!empty($params)) {
        $stmtDet->bind_param($types, ...$params);
    }
    $stmtDet->execute();
    $resDet = $stmtDet->get_result();

    $details = [];
    while ($rowD = $resDet->fetch_assoc()) {
        $nom = (float)$rowD['nominal_pengiriman'];
        $adm = (float)$rowD['biaya_admin'];
        $tot = (float)$rowD['total_bayar'];

        $rowD['nominal_pengiriman'] = $nom;
        $rowD['biaya_admin'] = $adm;
        $rowD['total_bayar'] = $tot;
        $rowD['formatted_nominal'] = 'Rp ' . number_format($nom, 0, ',', '.');
        $rowD['formatted_admin'] = 'Rp ' . number_format($adm, 0, ',', '.');
        $rowD['formatted_total'] = 'Rp ' . number_format($tot, 0, ',', '.');
        $rowD['formatted_tanggal'] = date('d/m/Y H:i', strtotime($rowD['tanggal_bayar']));

        $details[] = $rowD;
    }
    $stmtDet->close();

    // 3. DAFTAR SELURUH BANK PENGIRIM UNTUK DROPDOWN
    $banksList = [];
    $resB = $conn->query("SELECT DISTINCT bank_pengirim FROM payment_purchase_detail WHERE bank_pengirim IS NOT NULL AND bank_pengirim != '' ORDER BY bank_pengirim ASC");
    if ($resB) {
        while ($b = $resB->fetch_assoc()) {
            $banksList[] = $b['bank_pengirim'];
        }
    }

    sendJson(true, 'Laporan Pengeluaran Bank berhasil dimuat.', [
        'filters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'bank_pengirim' => $bankPengirim,
            'search' => $search
        ],
        'banks_list' => $banksList,
        'summary' => $summary,
        'grand_total' => [
            'total_transaksi' => $grandTotalTransaksi,
            'total_nominal' => $grandTotalNominal,
            'total_biaya_admin' => $grandTotalAdmin,
            'total_pembayaran' => $grandTotalPembayaran,
            'formatted_nominal' => 'Rp ' . number_format($grandTotalNominal, 0, ',', '.'),
            'formatted_admin' => 'Rp ' . number_format($grandTotalAdmin, 0, ',', '.'),
            'formatted_total' => 'Rp ' . number_format($grandTotalPembayaran, 0, ',', '.')
        ],
        'details' => $details
    ]);

} catch (Exception $e) {
    sendJson(false, 'Gagal memuat laporan pengeluaran bank: ' . $e->getMessage(), null, 500);
}
