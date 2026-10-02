<?php
/**
 * REST API: Monitoring Tagihan Jatuh Tempo Faktur PO
 * Endpoint: /api/pembayaran_po/tagihan_jatuh_tempo.php
 * Path: api/pembayaran_po/tagihan_jatuh_tempo.php
 * Khusus Role: FINANCE, ADMIN, MANAGER
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

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
    sendJson(false, 'Metode HTTP tidak didukung. Hanya menerima GET.', null, 405);
}

try {
    $searchVendor = trim($_GET['vendor'] ?? ($_GET['search'] ?? ''));
    $namaBank = trim($_GET['bank'] ?? ($_GET['nama_bank'] ?? ''));
    $bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
    $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;

    $whereClause = [
        "faktur_po.sisa_tagihan > 0",
        "faktur_po.status != 'BATAL'"
    ];
    $params = [];
    $types = "";

    // 1. Filter Nama Vendor / No Faktur
    if (!empty($searchVendor)) {
        $whereClause[] = "(vendor.nama_perusahaan LIKE ? OR faktur_po.nomor_faktur LIKE ? OR faktur_po.nomor_faktur_vendor LIKE ?)";
        $like = "%{$searchVendor}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= "sss";
    }

    // 2. Filter Nama Bank
    if (!empty($namaBank)) {
        $whereClause[] = "faktur_po.nama_bank = ?";
        $params[] = $namaBank;
        $types .= "s";
    }

    // 3. Filter Bulan Jatuh Tempo (1 - 12)
    if ($bulan >= 1 && $bulan <= 12) {
        $whereClause[] = "MONTH(faktur_po.tanggal_jatuh_tempo) = ?";
        $params[] = $bulan;
        $types .= "i";
    }

    // 4. Filter Tahun Jatuh Tempo (contoh: 2026)
    if ($tahun > 0) {
        $whereClause[] = "YEAR(faktur_po.tanggal_jatuh_tempo) = ?";
        $params[] = $tahun;
        $types .= "i";
    }

    $whereSql = implode(" AND ", $whereClause);

    $sql = "SELECT
                faktur_po.id_faktur,
                faktur_po.nomor_faktur, 
                faktur_po.nomor_faktur_vendor,
                faktur_po.tanggal_jatuh_tempo,
                faktur_po.tanggal_terima_faktur_vendor,

                DATEDIFF(
                    faktur_po.tanggal_jatuh_tempo,
                    CURDATE()
                ) AS sisa_hari,

                vendor.id_vendor,
                vendor.nama_perusahaan, 
                faktur_po.nama_bank, 
                faktur_po.nomor_rekening, 
                faktur_po.atas_nama_rekening, 
                faktur_po.total_tagihan,
                faktur_po.terbayar,
                faktur_po.sisa_tagihan,

                CASE 
                    WHEN payment_purchase.status_pembayaran = 0 THEN 'BELUM LUNAS'
                    ELSE 'BELUM BAYAR'
                END AS keterangan

            FROM
                faktur_po

            INNER JOIN
                vendor
                ON faktur_po.id_vendor = vendor.id_vendor

            LEFT JOIN
                payment_purchase
                ON faktur_po.id_faktur = payment_purchase.id_faktur

            WHERE
                {$whereSql}

            ORDER BY
                faktur_po.tanggal_jatuh_tempo ASC, faktur_po.id_faktur ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];

    $grandTotalSisaTagihan = 0;
    $countBelumLunas = 0;
    $countBelumBayar = 0;
    $countOverdue = 0;

    while ($row = $result->fetch_assoc()) {
        $sisaTagihan = (float)$row['sisa_tagihan'];
        $sisaHari = (int)$row['sisa_hari'];
        $grandTotalSisaTagihan += $sisaTagihan;

        if ($row['keterangan'] === 'BELUM LUNAS') {
            $countBelumLunas++;
        } else {
            $countBelumBayar++;
        }

        if ($sisaHari < 0) {
            $countOverdue++;
        }

        $items[] = [
            'id_faktur'             => (int)$row['id_faktur'],
            'nomor_faktur'          => $row['nomor_faktur'],
            'nomor_faktur_vendor'   => $row['nomor_faktur_vendor'],
            'tanggal_jatuh_tempo'   => $row['tanggal_jatuh_tempo'],
            'sisa_hari'             => $sisaHari,
            'id_vendor'             => (int)$row['id_vendor'],
            'nama_perusahaan'       => $row['nama_perusahaan'],
            'nama_bank'             => $row['nama_bank'] ?: '-',
            'nomor_rekening'        => $row['nomor_rekening'] ?: '-',
            'atas_nama_rekening'    => $row['atas_nama_rekening'] ?: '-',
            'total_tagihan'         => (float)$row['total_tagihan'],
            'terbayar'              => (float)$row['terbayar'],
            'sisa_tagihan'          => $sisaTagihan,
            'keterangan'            => $row['keterangan']
        ];
    }
    $stmt->close();

    sendJson(true, 'Data tagihan jatuh tempo berhasil dimuat.', [
        'items'                     => $items,
        'grand_total_sisa_tagihan'  => $grandTotalSisaTagihan,
        'total_faktur'              => count($items),
        'total_belum_lunas'         => $countBelumLunas,
        'total_belum_bayar'         => $countBelumBayar,
        'total_overdue'             => $countOverdue,
        'filter_applied'            => [
            'vendor' => $searchVendor,
            'bank'   => $namaBank,
            'bulan'  => $bulan,
            'tahun'  => $tahun
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
