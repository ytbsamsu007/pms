<?php
/**
 * Halaman Cetak Laporan Data Barang Berdasarkan Site (Print / PDF)
 * Path: admin/pages/laporan/print_barang_site.php
 * Format: Pure Black & White, Terintegrasi REST API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_FINANCE, ROLE_MANAGER]);

$idSite = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
$jenis = isset($_GET['jenis']) && $_GET['jenis'] !== '' ? trim($_GET['jenis']) : '';
$asset = isset($_GET['asset']) && $_GET['asset'] !== '' ? trim($_GET['asset']) : '';
$search = trim($_GET['search'] ?? '');
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

// Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'purchasing@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Barang per Site - <?= htmlspecialchars($companyName) ?></title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Laporan Data Barang per Site
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'id_site' => $idSite,
                'jenis' => $jenis,
                'asset' => $asset,
                'search' => $search
            ]);
            ?>
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?<?= $queryUrl ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?<?= $queryUrl ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/laporan/barang_site.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak
            </button>
        </div>
    </div>
</div>

<!-- CONTAINER UTAMA DOKUMEN -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>" id="printContainer">
    
    <?php if ($useKop): ?>
    <!-- KOP PERUSAHAAN RESMI -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/../../../uploads/profile/' . $companyLogo)): ?>
                <img src="<?= BASE_URL ?>/uploads/profile/<?= htmlspecialchars($companyLogo) ?>" alt="Logo Perusahaan" style="width: 54px; height: 54px; object-fit: contain; flex-shrink: 0;">
            <?php else: ?>
                <svg width="54" height="54" viewBox="0 0 100 100" style="flex-shrink: 0;">
                    <polygon points="50,4 92,27 92,73 50,96 8,73 8,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <polyline points="8,27 50,50 92,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <line x1="50" y1="50" x2="50" y2="96" stroke="#000" stroke-width="8"/>
                    <polygon points="50,22 74,35 50,48 26,35" fill="#000"/>
                    <polygon points="26,41 46,51 46,76 26,65" fill="#000"/>
                    <polygon points="74,41 54,51 54,76 74,65" fill="#000"/>
                </svg>
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
        <div class="tagline-container">
            <div class="tagline-divider"></div>
            <div class="tagline-text">
                SOLUSI<br>LOGISTIK<br>UNTUK<br>INDUSTRI
            </div>
        </div>
    </div>
    <div class="kop-separator" style="border-bottom: 2px solid #000; margin-bottom: 12px;"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>: Bagian atas dikosongkan untuk dicetak pada kertas berkop resmi perusahaan.
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & PERIODE -->
    <div class="doc-title-box" style="text-align: center; margin: 16px 0 16px 0; border-bottom: none;">
        <div class="doc-title" style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">LAPORAN DATA BARANG BERDASARKAN SITE</div>
        <div class="text-muted" style="font-size: 11px;" id="reportPeriodText">
            Dicetak: <?= date('d/m/Y H:i') ?> | Oleh: <?= htmlspecialchars($user['nama_karyawan'] ?? $user['username']) ?>
        </div>
    </div>

    <!-- TABEL DATA BARANG SITE -->
    <table class="table-items-main mb-4" id="tablePrintData">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th class="text-center" style="width: 110px;">Kode</th>
                <th>Nama Barang</th>
                <th style="width: 190px;">Lokasi Site</th>
                <th class="text-center" style="width: 90px;">Jenis</th>
                <th class="text-center" style="width: 75px;">Aset</th>
                <th class="text-end" style="width: 110px;">Kuantitas</th>
                <th class="text-center" style="width: 65px;">Satuan</th>
            </tr>
        </thead>
        <tbody id="printTableBody">
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data laporan barang per site...
                </td>
            </tr>
        </tbody>
        <tfoot id="printTableFoot" style="display: none;">
            <tr class="total-row">
                <td colspan="6" class="text-center text-uppercase fw-bold">Grand Total Kuantitas Fisik</td>
                <td class="text-end fw-bold font-monospace" id="printFootTotalStok" style="font-size: 11px;">0</td>
                <td class="text-center text-muted small">-</td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN REKAP LAPORAN (3 KOLOM STANDAR) -->
    <div class="sig-section mt-4">
        <div class="row">
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name"><?= htmlspecialchars($user['nama_karyawan'] ?? $user['username']) ?></span>
                </div>
                <div class="sig-footer-note">Staff Logistik / Inventori</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diperiksa Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">........................................</span>
                </div>
                <div class="sig-footer-note">Head of Logistics / Site Manager</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Mengetahui,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">........................................</span>
                </div>
                <div class="sig-footer-note">Branch / General Manager</div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH -->
    <div class="footer-line-container mt-4">
        <div class="footer-right">
            <div>Dicetak pada: <?= date('d/m/Y H:i') ?> | Oleh: <?= htmlspecialchars($user['nama_karyawan'] ?? $user['username']) ?></div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<script>
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function loadPrintData() {
    const tbody = document.getElementById('printTableBody');
    const tfoot = document.getElementById('printTableFoot');

    const params = new URLSearchParams(window.location.search);

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/barang_site.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();

        if (res.success && res.data) {
            const items = res.data.items || [];
            const metrics = res.data.metrics || {};

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data barang yang sesuai dengan parameter filter ini.</td></tr>`;
                return;
            }

            let html = '';
            let grandTotalStok = 0;

            items.forEach((item, idx) => {
                grandTotalStok += (parseFloat(item.stok) || 0);

                html += `
                <tr>
                    <td class="text-center font-monospace" style="font-size: 10px;">${idx + 1}</td>
                    <td class="text-center font-monospace fw-bold" style="font-size: 10.5px;">${escapeHtml(item.kode_barang)}</td>
                    <td>
                        <div class="fw-bold">${escapeHtml(item.nama_barang)}</div>
                    </td>
                    <td style="font-size: 10.5px;">
                        <div>${escapeHtml(item.nama_site)}</div>
                    </td>
                    <td class="text-center font-monospace" style="font-size: 10px;">${escapeHtml(item.jenis_barang)}</td>
                    <td class="text-center font-monospace" style="font-size: 10px;">${escapeHtml(item.asset)}</td>
                    <td class="text-end font-monospace fw-bold" style="font-size: 11px;">${Number(item.stok || 0).toLocaleString('id-ID')}</td>
                    <td class="text-center" style="font-size: 10px;">${escapeHtml(item.satuan || 'PCS')}</td>
                </tr>`;
            });

            tbody.innerHTML = html;

            document.getElementById('printFootTotalStok').textContent = grandTotalStok.toLocaleString('id-ID');
            tfoot.style.display = 'table-footer-group';
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${res.message || 'Gagal memuat data cetak.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPrintData();
});
</script>

</body>
</html>
