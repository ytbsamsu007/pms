<?php
/**
 * Halaman Cetak Rekapitulasi Laporan Pembatalan Transaksi (Print / PDF)
 * Path: admin/pages/laporan/print_pembatalan_transaksi.php
 * Format: Pure Black & White, Terintegrasi REST API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

$idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
$jenisDokumen = trim($_GET['jenis_dokumen'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
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
    <title>Laporan Pembatalan Transaksi - <?= htmlspecialchars($companyName) ?></title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Rekapitulasi Pembatalan Transaksi
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'id_vendor' => $idVendor,
                'jenis_dokumen' => $jenisDokumen,
                'start_date' => $startDate,
                'end_date' => $endDate,
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

            <a href="<?= BASE_URL ?>/admin/pages/laporan/pembatalan_transaksi.php" class="btn btn-outline-light btn-sm px-3">
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
        <div class="doc-title" style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">LAPORAN PEMBATALAN TRANSAKSI &amp; BERITA ACARA (BAP)</div>
        <div class="text-muted" style="font-size: 11px;" id="reportPeriodText">
            Dicetak: <?= date('d/m/Y H:i') ?> | Oleh: <?= htmlspecialchars($user['nama_karyawan'] ?? $user['username']) ?>
        </div>
    </div>

    <!-- TABEL DATA PEMBATALAN -->
    <table class="table-items-main mb-4" id="tablePrintData">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th class="text-center" style="width: 140px;">No. BAP</th>
                <th class="text-center" style="width: 130px;">No. Ref</th>
                <th class="text-center" style="width: 115px;">State</th>
                <th style="text-align: left;">Vendor</th>
                <th class="text-end" style="width: 130px;">Nilai</th>
            </tr>
        </thead>
        <tbody id="printTableBody">
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data laporan pembatalan...
                </td>
            </tr>
        </tbody>
        <tfoot id="printTableFoot" style="display: none;">
            <tr class="total-row">
                <td colspan="5" class="text-center text-uppercase fw-bold">GRAND TOTAL</td>
                <td class="text-end fw-bold font-monospace" id="printFootTotal" style="font-size: 11px;">Rp 0</td>
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
                <div class="sig-footer-note">Staff Administrasi</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diperiksa Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">........................................</span>
                </div>
                <div class="sig-footer-note">Head of Dept / Finance</div>
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
function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID');
}

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
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/pembatalan.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();

        if (res.success && res.data) {
            const items = res.data.items || [];
            const metrics = res.data.metrics || {};

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data pembatalan transaksi pada parameter filter ini.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach((item, idx) => {
                const cleanAlasan = (item.alasan_detail || '-').replace(/\[BATAL:\s*[^\]]+\]/, '').trim();
                const katBadge = item.kategori_alasan ? `<strong>[${escapeHtml(item.kategori_alasan)}]</strong> ` : '';
                
                html += `
                <tr>
                    <td rowspan="2" class="text-center align-middle font-monospace" style="font-size: 11px;">${idx + 1}</td>
                    <td class="text-center font-monospace fw-bold" style="font-size: 10.5px;">${escapeHtml(item.nomor_bap)}</td>
                    <td class="text-center font-monospace" style="font-size: 10.5px;">${escapeHtml(item.nomor_referensi)}</td>
                    <td class="text-center font-monospace" style="font-size: 10px;">${escapeHtml(item.state_tahapan || 'BATAL')}</td>
                    <td style="font-size: 11px;">${escapeHtml(item.nama_vendor || '-')}</td>
                    <td class="text-end font-monospace fw-bold" style="font-size: 11px;">${formatRupiah(item.nilai_transaksi)}</td>
                </tr>
                <tr>
                    <td colspan="5" style="padding: 6px 10px; background-color: #fafafa;">
                        <div style="font-size: 10px; font-weight: bold; color: #444; margin-bottom: 2px;">Catatan:</div>
                        <div style="font-size: 10.5px; line-height: 1.4; color: #111;">${katBadge}${escapeHtml(cleanAlasan)}</div>
                    </td>
                </tr>`;
            });

            tbody.innerHTML = html;

            if (metrics.total_nilai_batal !== undefined) {
                document.getElementById('printFootTotal').textContent = formatRupiah(metrics.total_nilai_batal || 0);
                tfoot.style.display = 'table-footer-group';
            }
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${res.message || 'Gagal memuat data cetak.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadPrintData();
});
</script>

</body>
</html>
