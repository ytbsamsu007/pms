<?php
/**
 * REST API: Laporan PPN Masa (PPN Keluaran vs PPN Masukan)
 * Endpoint: /api/laporan/ppn_masa.php
 * Path: api/laporan/ppn_masa.php
 * Akses: Role FINANCE, ADMIN, MANAGER
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
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$namaBulanIndo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$action = trim($_GET['action'] ?? '');
$tahun = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan = isset($_GET['bulan']) && is_numeric($_GET['bulan']) ? (int)$_GET['bulan'] : 0;

// =========================================================================
// 1. DETAIL BULANAN (RINCIAN DAFTAR TRANSAKSI PPN MASUKAN & KELUARAN)
// =========================================================================
if ($action === 'detail') {
    if ($bulan < 1 || $bulan > 12) {
        sendJson(false, 'Bulan tidak valid untuk rincian masa.', null, 422);
    }

    $bulanPad = str_pad((string)$bulan, 2, '0', STR_PAD_LEFT);

    // 1.1 Ambil Rincian Faktur PPN Masukan
    $sqlMasukan = "SELECT 
                        f.id_faktur,
                        f.nomor_faktur,
                        f.nomor_faktur_pajak,
                        f.tanggal_faktur_pajak,
                        f.nomor_faktur_vendor,
                        p.nomor_po,
                        v.nama_vendor,
                        f.dpp,
                        f.rate_pajak,
                        f.nominal_pajak AS ppn_masukan,
                        f.status
                   FROM faktur_po f
                   LEFT JOIN purchase_orders p ON f.id_po = p.id_po
                   LEFT JOIN vendor v ON f.id_vendor = v.id_vendor
                   WHERE f.status <> 'BATAL'
                     AND f.tanggal_faktur_pajak IS NOT NULL
                     AND YEAR(f.tanggal_faktur_pajak) = ?
                     AND MONTH(f.tanggal_faktur_pajak) = ?
                   ORDER BY f.tanggal_faktur_pajak ASC, f.id_faktur ASC";

    $stmtM = $conn->prepare($sqlMasukan);
    $stmtM->bind_param("ii", $tahun, $bulan);
    $stmtM->execute();
    $resM = $stmtM->get_result();

    $listMasukan = [];
    $totalMasukan = 0;
    $totalDppMasukan = 0;

    while ($row = $resM->fetch_assoc()) {
        $ppn = (float)$row['ppn_masukan'];
        $dpp = (float)$row['dpp'];
        $totalMasukan += $ppn;
        $totalDppMasukan += $dpp;

        $listMasukan[] = [
            'id_faktur' => (int)$row['id_faktur'],
            'nomor_faktur' => $row['nomor_faktur'],
            'nomor_faktur_pajak' => $row['nomor_faktur_pajak'],
            'tanggal_faktur_pajak' => $row['tanggal_faktur_pajak'],
            'nomor_faktur_vendor' => $row['nomor_faktur_vendor'],
            'nomor_po' => $row['nomor_po'],
            'nama_vendor' => $row['nama_vendor'],
            'dpp' => $dpp,
            'rate_pajak' => (int)$row['rate_pajak'],
            'ppn_masukan' => $ppn,
            'status' => $row['status']
        ];
    }
    $stmtM->close();

    // 1.2 Ambil Rincian PPN Keluaran
    $sqlKeluaran = "SELECT 
                        pk.id_pajak_keluaran,
                        pk.tahun,
                        pk.bulan,
                        pk.ppn_keluaran,
                        pk.keterangan,
                        pk.created_at,
                        k.nama_karyawan,
                        k.kode_karyawan,
                        j.nama_jabatan
                    FROM pajak_keluaran pk
                    LEFT JOIN karyawan k ON pk.id_karyawan = k.id_karyawan
                    LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                    WHERE pk.tahun = ?
                      AND pk.bulan = ?
                    ORDER BY pk.id_pajak_keluaran ASC";

    $stmtK = $conn->prepare($sqlKeluaran);
    $stmtK->bind_param("ss", (string)$tahun, $bulanPad);
    $stmtK->execute();
    $resK = $stmtK->get_result();

    $listKeluaran = [];
    $totalKeluaran = 0;

    while ($rowK = $resK->fetch_assoc()) {
        $ppnK = (float)$rowK['ppn_keluaran'];
        $totalKeluaran += $ppnK;

        $listKeluaran[] = [
            'id_pajak_keluaran' => (int)$rowK['id_pajak_keluaran'],
            'tahun' => $rowK['tahun'],
            'bulan' => $rowK['bulan'],
            'ppn_keluaran' => $ppnK,
            'keterangan' => $rowK['keterangan'],
            'nama_karyawan' => $rowK['nama_karyawan'],
            'nama_jabatan' => $rowK['nama_jabatan'],
            'created_at' => $rowK['created_at']
        ];
    }
    $stmtK->close();

    $selisih = $totalKeluaran - $totalMasukan;
    $statusPajak = 'NIHIL';
    $statusBadge = 'secondary';
    if ($selisih > 0) {
        $statusPajak = 'PPN Kurang Bayar';
        $statusBadge = 'danger';
    } elseif ($selisih < 0) {
        $statusPajak = 'PPN Lebih Bayar';
        $statusBadge = 'success';
    }

    sendJson(true, "Detail PPN Masa {$namaBulanIndo[$bulan]} {$tahun} berhasil dimuat.", [
        'tahun' => $tahun,
        'bulan' => $bulan,
        'nama_bulan' => $namaBulanIndo[$bulan],
        'periode_formatted' => "{$namaBulanIndo[$bulan]} {$tahun}",
        'total_ppn_masukan' => $totalMasukan,
        'total_dpp_masukan' => $totalDppMasukan,
        'total_ppn_keluaran' => $totalKeluaran,
        'selisih' => $selisih,
        'status_pajak' => $statusPajak,
        'status_badge' => $statusBadge,
        'rincian_masukan' => $listMasukan,
        'rincian_keluaran' => $listKeluaran
    ]);
}

// =========================================================================
// 2. REKAPITULASI TAHUNAN / BULANAN MASA PPN
// =========================================================================

// 2.1 Query PPN Masukan per Bulan
$sqlRekapMasukan = "SELECT 
                        MONTH(f.tanggal_faktur_pajak) AS bulan,
                        COUNT(f.id_faktur) AS count_faktur,
                        SUM(COALESCE(f.dpp, 0)) AS total_dpp,
                        SUM(COALESCE(f.nominal_pajak, 0)) AS total_ppn_masukan
                    FROM faktur_po f
                    WHERE f.status <> 'BATAL'
                      AND f.tanggal_faktur_pajak IS NOT NULL
                      AND YEAR(f.tanggal_faktur_pajak) = ?
                    GROUP BY MONTH(f.tanggal_faktur_pajak)";

$stmtM = $conn->prepare($sqlRekapMasukan);
$stmtM->bind_param("i", $tahun);
$stmtM->execute();
$resM = $stmtM->get_result();

$masukanMap = [];
while ($rm = $resM->fetch_assoc()) {
    $bInt = (int)$rm['bulan'];
    $masukanMap[$bInt] = [
        'count_faktur' => (int)$rm['count_faktur'],
        'total_dpp' => (float)$rm['total_dpp'],
        'total_ppn_masukan' => (float)$rm['total_ppn_masukan']
    ];
}
$stmtM->close();

// 2.2 Query PPN Keluaran per Bulan
$sqlRekapKeluaran = "SELECT 
                        CAST(pk.bulan AS UNSIGNED) AS bulan,
                        COUNT(pk.id_pajak_keluaran) AS count_record,
                        SUM(COALESCE(pk.ppn_keluaran, 0)) AS total_ppn_keluaran
                     FROM pajak_keluaran pk
                     WHERE pk.tahun = ?
                     GROUP BY CAST(pk.bulan AS UNSIGNED)";

$stmtK = $conn->prepare($sqlRekapKeluaran);
$thnStr = (string)$tahun;
$stmtK->bind_param("s", $thnStr);
$stmtK->execute();
$resK = $stmtK->get_result();

$keluaranMap = [];
while ($rk = $resK->fetch_assoc()) {
    $bInt = (int)$rk['bulan'];
    $keluaranMap[$bInt] = [
        'count_record' => (int)$rk['count_record'],
        'total_ppn_keluaran' => (float)$rk['total_ppn_keluaran']
    ];
}
$stmtK->close();

// 2.3 Buat Array 12 Bulan (atau Filter Bulan tertentu jika $bulan > 0)
$rows = [];
$grandTotalMasukan = 0;
$grandTotalKeluaran = 0;
$grandTotalSelisih = 0;
$grandTotalKurangBayar = 0;
$grandTotalLebihBayar = 0;

$bulanStart = ($bulan > 0) ? $bulan : 1;
$bulanEnd = ($bulan > 0) ? $bulan : 12;

for ($m = $bulanStart; $m <= $bulanEnd; $m++) {
    $mPad = str_pad((string)$m, 2, '0', STR_PAD_LEFT);
    $namaBln = $namaBulanIndo[$m] ?? "Bulan $m";

    $dataMasukan = $masukanMap[$m] ?? ['count_faktur' => 0, 'total_dpp' => 0, 'total_ppn_masukan' => 0];
    $dataKeluaran = $keluaranMap[$m] ?? ['count_record' => 0, 'total_ppn_keluaran' => 0];

    $ppnMasukan = (float)$dataMasukan['total_ppn_masukan'];
    $ppnKeluaran = (float)$dataKeluaran['total_ppn_keluaran'];
    $selisih = $ppnKeluaran - $ppnMasukan;

    $statusPajak = 'NIHIL';
    $statusBadge = 'secondary';
    if ($selisih > 0) {
        $statusPajak = 'PPN Kurang Bayar';
        $statusBadge = 'danger';
        $grandTotalKurangBayar += $selisih;
    } elseif ($selisih < 0) {
        $statusPajak = 'PPN Lebih Bayar';
        $statusBadge = 'success';
        $grandTotalLebihBayar += abs($selisih);
    }

    $grandTotalMasukan += $ppnMasukan;
    $grandTotalKeluaran += $ppnKeluaran;
    $grandTotalSelisih += $selisih;

    $rows[] = [
        'tahun' => $tahun,
        'bulan' => $m,
        'bulan_formatted' => $mPad,
        'nama_bulan' => $namaBln,
        'periode_formatted' => "$namaBln $tahun",
        'ppn_masukan' => $ppnMasukan,
        'count_faktur_masukan' => $dataMasukan['count_faktur'],
        'total_dpp_masukan' => $dataMasukan['total_dpp'],
        'ppn_keluaran' => $ppnKeluaran,
        'count_record_keluaran' => $dataKeluaran['count_record'],
        'selisih' => $selisih,
        'selisih_abs' => abs($selisih),
        'status_pajak' => $statusPajak,
        'status_badge' => $statusBadge
    ];
}

$statusGrandTotal = 'NIHIL';
if ($grandTotalSelisih > 0) {
    $statusGrandTotal = 'PPN Kurang Bayar';
} elseif ($grandTotalSelisih < 0) {
    $statusGrandTotal = 'PPN Lebih Bayar';
}

sendJson(true, "Laporan PPN Masa Tahun {$tahun} berhasil dimuat.", [
    'tahun' => $tahun,
    'bulan_filter' => $bulan,
    'rows' => $rows,
    'summary' => [
        'grand_total_masukan' => $grandTotalMasukan,
        'grand_total_keluaran' => $grandTotalKeluaran,
        'grand_total_selisih' => $grandTotalSelisih,
        'grand_total_selisih_abs' => abs($grandTotalSelisih),
        'grand_total_kurang_bayar' => $grandTotalKurangBayar,
        'grand_total_lebih_bayar' => $grandTotalLebihBayar,
        'status_grand_total' => $statusGrandTotal
    ]
]);
