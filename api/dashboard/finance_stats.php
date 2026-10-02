<?php
/**
 * RESTful API: Dashboard Statistics khusus Role FINANCE & Management
 * Endpoint: /api/dashboard/finance_stats.php
 * Path: api/dashboard/finance_stats.php
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

$currentUser = apiAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Metode HTTP tidak diizinkan. Gunakan GET.', null, 405);
}

// -------------------------------------------------------------
// 1. KPI UTAMA KEUANGAN & PIPELINE
// -------------------------------------------------------------

// A. PO Disetujui (Approved) yang Menunggu Faktur
$poWaitingInvoice = 0;
$resPo = $conn->query("
    SELECT COUNT(*) as c 
    FROM purchase_order po 
    WHERE po.status IN ('APPROVED', 'DISETUJUI') 
      AND po.id_po NOT IN (SELECT DISTINCT id_po FROM faktur_po WHERE id_po IS NOT NULL AND status NOT IN ('BATAL', 'DIBATALKAN'))
");
if ($resPo) {
    $poWaitingInvoice = (int)$resPo->fetch_assoc()['c'];
}

// B. Total Faktur Belum Lunas (Outstanding AP)
$fakturBelumLunasCount = 0;
$fakturBelumLunasNominal = 0;
$resFaktur = $conn->query("
    SELECT COUNT(*) as c, COALESCE(SUM(sisa_tagihan), 0) as total_nom 
    FROM faktur_po 
    WHERE sisa_tagihan > 0 
      AND status NOT IN ('BATAL', 'DIBATALKAN')
");
if ($resFaktur) {
    $rowF = $resFaktur->fetch_assoc();
    $fakturBelumLunasCount = (int)$rowF['c'];
    $fakturBelumLunasNominal = (float)$rowF['total_nom'];
}

// C. Tagihan Jatuh Tempo & Lewat Tempo (Urgent <= 7 Hari)
$jatuhTempoCount = 0;
$jatuhTempoNominal = 0;
$resDue = $conn->query("
    SELECT COUNT(*) as c, COALESCE(SUM(sisa_tagihan), 0) as total_nom 
    FROM faktur_po 
    WHERE sisa_tagihan > 0 
      AND status NOT IN ('BATAL', 'DIBATALKAN')
      AND tanggal_jatuh_tempo <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
if ($resDue) {
    $rowDue = $resDue->fetch_assoc();
    $jatuhTempoCount = (int)$rowDue['c'];
    $jatuhTempoNominal = (float)$rowDue['total_nom'];
}

// D. Total Pengeluaran Kas/Bank Bulan Ini (Cash Outflow)
$cashOutMonthCount = 0;
$cashOutMonthNominal = 0;
$resCash = $conn->query("
    SELECT COUNT(*) as c, COALESCE(SUM(nominal_pengiriman), 0) as total_nom 
    FROM payment_purchase_detail 
    WHERE MONTH(tanggal_bayar) = MONTH(CURDATE()) 
      AND YEAR(tanggal_bayar) = YEAR(CURDATE())
");
if ($resCash) {
    $rowCash = $resCash->fetch_assoc();
    $cashOutMonthCount = (int)$rowCash['c'];
    $cashOutMonthNominal = (float)$rowCash['total_nom'];
}

// -------------------------------------------------------------
// 2. PENGELUARAN PER REKENING BANK / KAS (BULAN INI)
// -------------------------------------------------------------
$cashOutByBank = [];
$resBank = $conn->query("
    SELECT 
        COALESCE(NULLIF(TRIM(bank_pengirim), ''), 'KAS / TUNAI') as nama_bank,
        COALESCE(SUM(nominal_pengiriman), 0) as total_nominal,
        COUNT(*) as total_transaksi
    FROM payment_purchase_detail 
    WHERE MONTH(tanggal_bayar) = MONTH(CURDATE()) 
      AND YEAR(tanggal_bayar) = YEAR(CURDATE())
    GROUP BY nama_bank 
    ORDER BY total_nominal DESC
");
if ($resBank) {
    while ($r = $resBank->fetch_assoc()) {
        $cashOutByBank[] = [
            'nama_bank' => $r['nama_bank'],
            'total_nominal' => (float)$r['total_nominal'],
            'total_transaksi' => (int)$r['total_transaksi'],
            'persentase' => $cashOutMonthNominal > 0 ? round(((float)$r['total_nominal'] / $cashOutMonthNominal) * 100, 1) : 0
        ];
    }
}

// -------------------------------------------------------------
// 3. TOP 5 VENDOR DENGAN TAGIHAN TERBESAR (TOP PAYABLES)
// -------------------------------------------------------------
$topVendors = [];
$resVendors = $conn->query("
    SELECT 
        v.id_vendor,
        v.nama_perusahaan as nama_vendor,
        COUNT(fp.id_faktur) as total_faktur,
        COALESCE(SUM(fp.sisa_tagihan), 0) as total_sisa_tagihan,
        COALESCE(SUM(fp.total_tagihan), 0) as total_tagihan
    FROM faktur_po fp
    JOIN vendor v ON fp.id_vendor = v.id_vendor
    WHERE fp.sisa_tagihan > 0 
      AND fp.status NOT IN ('BATAL', 'DIBATALKAN')
    GROUP BY v.id_vendor, v.nama_perusahaan
    ORDER BY total_sisa_tagihan DESC
    LIMIT 5
");
if ($resVendors) {
    while ($r = $resVendors->fetch_assoc()) {
        $topVendors[] = [
            'id_vendor' => (int)$r['id_vendor'],
            'nama_vendor' => $r['nama_vendor'],
            'total_faktur' => (int)$r['total_faktur'],
            'total_sisa_tagihan' => (float)$r['total_sisa_tagihan'],
            'total_tagihan' => (float)$r['total_tagihan']
        ];
    }
}

// -------------------------------------------------------------
// 4. 5 TAGIHAN JATUH TEMPO TERDEKAT (URGENT AP QUEUE)
// -------------------------------------------------------------
$urgentTagihan = [];
$resUrgent = $conn->query("
    SELECT 
        fp.id_faktur,
        fp.nomor_faktur,
        fp.nomor_faktur_vendor,
        fp.tanggal_jatuh_tempo,
        fp.total_tagihan,
        fp.terbayar,
        fp.sisa_tagihan,
        fp.status,
        fp.nama_bank,
        fp.nomor_rekening,
        fp.atas_nama_rekening,
        v.nama_perusahaan as nama_vendor,
        DATEDIFF(fp.tanggal_jatuh_tempo, CURDATE()) as sisa_hari
    FROM faktur_po fp
    JOIN vendor v ON fp.id_vendor = v.id_vendor
    WHERE fp.sisa_tagihan > 0 
      AND fp.status NOT IN ('BATAL', 'DIBATALKAN')
    ORDER BY fp.tanggal_jatuh_tempo ASC
    LIMIT 5
");
if ($resUrgent) {
    while ($r = $resUrgent->fetch_assoc()) {
        $urgentTagihan[] = [
            'id_faktur' => (int)$r['id_faktur'],
            'nomor_faktur' => $r['nomor_faktur'],
            'nomor_faktur_vendor' => $r['nomor_faktur_vendor'],
            'tanggal_jatuh_tempo' => $r['tanggal_jatuh_tempo'],
            'sisa_hari' => (int)$r['sisa_hari'],
            'total_tagihan' => (float)$r['total_tagihan'],
            'terbayar' => (float)$r['terbayar'],
            'sisa_tagihan' => (float)$r['sisa_tagihan'],
            'status' => $r['status'],
            'nama_bank' => $r['nama_bank'],
            'nomor_rekening' => $r['nomor_rekening'],
            'atas_nama_rekening' => $r['atas_nama_rekening'],
            'nama_vendor' => $r['nama_vendor']
        ];
    }
}

// -------------------------------------------------------------
// 5. 5 TRANSAKSI PEMBAYARAN TERAKHIR (RECENT PAYMENTS)
// -------------------------------------------------------------
$recentPayments = [];
$resRecent = $conn->query("
    SELECT 
        ppd.id_pembayaran_detail as id_detail,
        ppd.kode_pembayaran,
        ppd.no_ref as nomor_transaksi_bank,
        ppd.tanggal_bayar,
        ppd.nominal_pengiriman as nominal_bayar,
        ppd.bank_pengirim,
        ppd.bank_tujuan,
        fp.id_faktur,
        fp.nomor_faktur,
        v.nama_perusahaan as nama_vendor
    FROM payment_purchase_detail ppd
    JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
    JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
    JOIN vendor v ON fp.id_vendor = v.id_vendor
    ORDER BY ppd.tanggal_bayar DESC, ppd.id_pembayaran_detail DESC
    LIMIT 5
");
if ($resRecent) {
    while ($r = $resRecent->fetch_assoc()) {
        $recentPayments[] = [
            'id_detail' => (int)$r['id_detail'],
            'id_faktur' => (int)$r['id_faktur'],
            'kode_pembayaran' => $r['kode_pembayaran'],
            'nomor_transaksi_bank' => $r['nomor_transaksi_bank'],
            'tanggal_bayar' => $r['tanggal_bayar'],
            'nominal_bayar' => (float)$r['nominal_bayar'],
            'bank_pengirim' => $r['bank_pengirim'],
            'bank_tujuan' => $r['bank_tujuan'],
            'nomor_faktur' => $r['nomor_faktur'],
            'nama_vendor' => $r['nama_vendor']
        ];
    }
}

// Response JSON
jsonResponse(true, 'Data dashboard finance berhasil dimuat.', [
    'kpi' => [
        'po_waiting_invoice' => $poWaitingInvoice,
        'faktur_belum_lunas_count' => $fakturBelumLunasCount,
        'faktur_belum_lunas_nominal' => $fakturBelumLunasNominal,
        'jatuh_tempo_count' => $jatuhTempoCount,
        'jatuh_tempo_nominal' => $jatuhTempoNominal,
        'cash_out_month_count' => $cashOutMonthCount,
        'cash_out_month_nominal' => $cashOutMonthNominal
    ],
    'cash_out_by_bank' => $cashOutByBank,
    'top_vendors' => $topVendors,
    'urgent_tagihan' => $urgentTagihan,
    'recent_payments' => $recentPayments
], 200);
