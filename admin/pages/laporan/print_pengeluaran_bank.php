<?php
/**
 * Halaman Cetak Laporan Rekapitulasi Pengeluaran Kas / Bank (Print / PDF)
 * Path: admin/pages/laporan/print_pengeluaran_bank.php
 * Format: Pure Black & White, Terintegrasi REST API & External CSS (No Direct SQL Queries)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING]);

$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$bankPengirim = trim($_GET['bank_pengirim'] ?? '');
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

// Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'finance@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengeluaran Bank - <?= htmlspecialchars($companyName) ?></title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Laporan Pengeluaran Bank
            </span>
            <?php if (!empty($bankPengirim)): ?>
                <span class="badge bg-primary">Bank: <?= htmlspecialchars($bankPengirim) ?></span>
            <?php endif; ?>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'bank_pengirim' => $bankPengirim
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

            <a href="<?= BASE_URL ?>/admin/pages/laporan/pengeluaran_bank.php" class="btn btn-outline-light btn-sm px-3">
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
        <div class="doc-title" style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0;">Laporan Rekapitulasi Pengeluaran Bank</div>
    </div>

    <!-- TABEL REKAPITULASI PENGELUARAN PER BANK -->
    <table class="table-items-main mb-4" id="tablePrintReport">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th style="text-align: left;">Nama Bank</th>
                <th style="text-align: left; width: 150px;">No. Rekening</th>
                <th class="text-end" style="width: 170px;">Nominal Transfer</th>
                <th class="text-end" style="width: 140px;">Biaya Admin</th>
                <th class="text-end" style="width: 180px;">Total</th>
            </tr>
        </thead>
        <tbody id="printTableBody">
            <tr>
                <td colspan="6" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data laporan...
                </td>
            </tr>
        </tbody>
        <tfoot id="printTableFoot" style="display: none;">
            <tr class="total-row">
                <td colspan="3" class="text-center text-uppercase">Grand Total</td>
                <td class="text-end" id="footNominal">Rp 0</td>
                <td class="text-end" id="footAdmin">Rp 0</td>
                <td class="text-end fw-bold" id="footTotal" style="font-size: 11px;">Rp 0</td>
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
                    <span class="sig-person-name">Staf Finance</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diperiksa Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Manager Finance</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Pimpinan / Direksi</span>
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

<script>
const START_DATE = "<?= addslashes($startDate) ?>";
const END_DATE = "<?= addslashes($endDate) ?>";
const BANK_PENGIRIM = "<?= addslashes($bankPengirim) ?>";

async function loadPrintData() {
    const tbody = document.getElementById('printTableBody');
    const tfoot = document.getElementById('printTableFoot');

    const params = new URLSearchParams({
        start_date: START_DATE,
        end_date: END_DATE,
        bank_pengirim: BANK_PENGIRIM
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/pengeluaran_bank.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            renderPrintTable(res.data.summary, res.data.grand_total);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-3">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function renderPrintTable(summary, grand) {
    const tbody = document.getElementById('printTableBody');
    const tfoot = document.getElementById('printTableFoot');

    if (!summary || summary.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-3">Tidak ada transaksi pembayaran pada filter ini.</td></tr>`;
        tfoot.style.display = 'none';
        return;
    }

    let html = '';
    summary.forEach((item, idx) => {
        html += `
        <tr>
            <td class="text-center">${idx + 1}</td>
            <td class="fw-semibold">${escapeHtml(item.bank_pengirim || '-')}</td>
            <td>${escapeHtml(item.norek_pengirim || '-')}</td>
            <td class="text-end">${Number(item.total_nominal || 0).toLocaleString('id-ID')}</td>
            <td class="text-end">${Number(item.total_biaya_admin || 0).toLocaleString('id-ID')}</td>
            <td class="text-end fw-bold">${Number(item.total_pembayaran || 0).toLocaleString('id-ID')}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    if (grand) {
        document.getElementById('footNominal').textContent = 'Rp ' + Number(grand.total_nominal || 0).toLocaleString('id-ID');
        document.getElementById('footAdmin').textContent = 'Rp ' + Number(grand.total_biaya_admin || 0).toLocaleString('id-ID');
        document.getElementById('footTotal').textContent = 'Rp ' + Number(grand.total_pembayaran || 0).toLocaleString('id-ID');
        tfoot.style.display = 'table-footer-group';
    }
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

document.addEventListener('DOMContentLoaded', () => {
    loadPrintData();
});
</script>

</body>
</html>
