<?php
/**
 * Halaman Cetak Laporan Barang Keluar (Print / PDF)
 * Path: admin/pages/laporan/print_barang_keluar.php
 * Format Standar Sistem: print_document.css, Opsi Kop Surat, Tanda Tangan Resmi
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER, ROLE_PURCHASING]);

$search = trim($_GET['search'] ?? $_GET['q'] ?? '');
$idSiteAsal = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

// Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'logistik@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';

// Filter & Query Data
$where = ["mo.status = 'DITERIMA SITE TUJUAN'"];
$params = [];
$types = "";

$namaFilterSite = 'Semua Site Asal';
if ($idSiteAsal > 0) {
    $where[] = "mo.id_site_asal = ?";
    $params[] = $idSiteAsal;
    $types .= "i";

    $sQ = $conn->query("SELECT nama_site FROM site WHERE id_site = $idSiteAsal");
    if ($sRow = $sQ->fetch_assoc()) {
        $namaFilterSite = $sRow['nama_site'];
    }
}

if (!empty($startDate)) {
    $where[] = "DATE(mo.tanggal_mutasi) >= ?";
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $where[] = "DATE(mo.tanggal_mutasi) <= ?";
    $params[] = $endDate;
    $types .= "s";
}

if (!empty($search)) {
    $where[] = "(
        b.kode_barang LIKE ? OR 
        b.nama_barang LIKE ? OR 
        b.serial_number LIKE ? OR
        mo.kode_mutasi LIKE ? OR 
        mo.nomor_surat_mutasi LIKE ? OR 
        sa.nama_site LIKE ? OR 
        st.nama_site LIKE ? OR 
        kr.nama_karyawan LIKE ?
    )";
    $wildcard = "%$search%";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $wildcard;
        $types .= "s";
    }
}

$whereSql = implode(" AND ", $where);

$sql = "
    SELECT 
        modt.id_mutasi_detail,
        modt.id_mutasi,
        modt.id_barang,
        modt.qty,
        b.kode_barang,
        b.nama_barang,
        b.satuan,
        b.serial_number,
        b.deskripsi,
        mo.kode_mutasi,
        mo.nomor_surat_mutasi,
        mo.tanggal_mutasi,
        sa.nama_site AS nama_site_asal,
        st.nama_site AS nama_site_tujuan,
        kr.nama_karyawan AS nama_pemohon
    FROM mutasi_order_detail modt
    INNER JOIN mutasi_order mo ON modt.id_mutasi = mo.id_mutasi
    INNER JOIN barang b ON modt.id_barang = b.id_barang
    LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
    LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
    LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
    WHERE $whereSql
    ORDER BY mo.tanggal_mutasi DESC, modt.id_mutasi_detail DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$totalQty = 0;
while ($row = $res->fetch_assoc()) {
    $totalQty += (float)$row['qty'];
    $items[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Keluar - <?= htmlspecialchars($companyName) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Laporan Barang Keluar
            </span>
            <?php if ($idSiteAsal > 0): ?>
                <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($namaFilterSite) ?></span>
            <?php endif; ?>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'search' => $search,
                'id_site' => $idSiteAsal,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            ?>
            <div class="btn-group btn-group-sm me-2" role="group">
                <a href="?<?= $queryUrl ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?<?= $queryUrl ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/laporan/barang_keluar.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- DOKUMEN UTAMA -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>">
    
    <?php if ($useKop): ?>
    <!-- KOP PERUSAHAAN RESMI -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyLogo) ?>" alt="Logo Perusahaan" style="max-height: 55px; max-width: 140px; object-fit: contain;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-dark text-white rounded p-2" style="width: 50px; height: 50px;">
                    <i class="bi bi-buildings fs-3"></i>
                </div>
            <?php endif; ?>
            <div>
                <div class="company-title"><?= htmlspecialchars($companyName) ?></div>
                <div class="company-addr"><?= htmlspecialchars($companyAddr) ?><?= !empty($companyCity) ? ', ' . htmlspecialchars($companyCity) : '' ?></div>
                <div class="company-contacts">
                    <?php if (!empty($companyPhone)): ?>
                        <span><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($companyPhone) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyWa)): ?>
                        <span><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($companyWa) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyEmail)): ?>
                        <span><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($companyEmail) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="kop-separator" style="border-bottom: 2px solid #000; margin-bottom: 12px;"></div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title-box" style="text-align: center; margin: 16px 0 20px 0; border-bottom: none;">
        <div class="doc-title" style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0;">Laporan Rekapitulasi Barang Keluar (Mutasi Site)</div>
    </div>

    <!-- META INFO FILTER -->
    <div class="row mb-3" style="font-size: 11px;">
        <div class="col-6">
            <div><strong>Lokasi Site Asal:</strong> <?= htmlspecialchars($namaFilterSite) ?></div>
        </div>
        <div class="col-6 text-end">
            <div><strong>Periode:</strong> <?= $startDate ? date('d-m-Y', strtotime($startDate)) : 'Awal' ?> s/d <?= $endDate ? date('d-m-Y', strtotime($endDate)) : 'Sekarang' ?></div>
        </div>
    </div>

    <!-- TABEL DATA BARANG KELUAR -->
    <table class="table-items-main mb-4" id="tablePrintReport">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th class="text-center" style="width: 90px;">Tanggal</th>
                <th class="text-center" style="width: 125px;">Kode Mutasi</th>
                <th style="text-align: left;">Nama Barang</th>
                <th style="text-align: left; width: 220px;">Rute</th>
                <th class="text-center" style="width: 100px;">KTS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td class="text-center"><?= $it['tanggal_mutasi'] ? date('d-m-Y', strtotime($it['tanggal_mutasi'])) : '-' ?></td>
                        <td class="font-monospace text-center"><?= htmlspecialchars($it['kode_mutasi']) ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($it['nama_barang']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($it['kode_barang']) ?><?php if (!empty($it['serial_number'])) echo ' | SN: ' . htmlspecialchars($it['serial_number']); ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($it['nama_site_asal']) ?> &rarr; <?= htmlspecialchars($it['nama_site_tujuan']) ?>
                        </td>
                        <td class="text-center font-monospace">
                            <strong><?= number_format($it['qty'], 0, ',', '.') ?></strong> <?= htmlspecialchars($it['satuan'] ?: 'PCS') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada transaksi barang keluar pada filter ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-center text-uppercase">Grand Total Kuantitas Keluar</td>
                <td class="text-center font-monospace fw-bold" style="font-size: 11px;"><?= number_format($totalQty, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN / OTORISASI -->
    <div class="sig-section mt-4">
        <div class="row">
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Petugas Logistik</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diperiksa Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Kepala Gudang / Site</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Manager Operasional</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH -->
    <div class="footer-line-container mt-4">
        <div class="footer-right">
            <div>Dokumen dikeluarkan pada: <?= date('d/m/Y H:i') ?> WITA</div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

</body>
</html>
