<?php
/**
 * Halaman Cetak Rekapitulasi Tahunan Pajak Keluaran per Bulan (Print / PDF)
 * Path: admin/pages/laporan/print_rekapitulasi_pajak_keluaran.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$tahun = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
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
    <title>Rekapitulasi Pajak Keluaran Tahun <?= $tahun ?> - <?= htmlspecialchars($companyName) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
        }
        .table-pajak {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .table-pajak th, .table-pajak td {
            border: 1px solid #000 !important;
            padding: 6px 10px !important;
            font-size: 11.5px;
            color: #000;
        }
        .table-pajak th {
            font-weight: bold;
            text-align: center;
            background-color: #fff !important;
            text-transform: uppercase;
        }
        .table-pajak tfoot td {
            font-weight: bold !important;
            font-size: 11.5px !important;
            border-top: 2px solid #000 !important;
        }
        @media print {
            .table-pajak th, .table-pajak td {
                padding: 4px 6px !important;
                font-size: 10px !important;
            }
            .table-pajak tfoot td {
                font-size: 10.5px !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-file-earmark-bar-graph text-info me-1"></i> Cetak Rekapitulasi Tahunan Pajak Keluaran
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Toggle Kop Surat -->
            <button type="button" class="btn btn-sm btn-outline-light" onclick="toggleKop()">
                <i class="bi bi-card-heading me-1"></i> <?= $useKop ? 'Sembunyikan Kop' : 'Tampilkan Kop' ?>
            </button>
            <button type="button" class="btn btn-sm btn-info text-dark fw-bold px-3 shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="window.close()">
                <i class="bi bi-x-lg me-1"></i> Tutup
            </button>
        </div>
    </div>
</div>

<div class="print-page container my-2">

    <?php if ($useKop): ?>
    <!-- KOP SURAT STANDAR JAYA TEKNIS -->
    <div class="header-kop d-flex align-items-center mb-2 pb-2">
        <div class="company-logo me-3" style="width: 80px; text-align: center;">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?= BASE_URL ?>/uploads/profile/<?= htmlspecialchars($companyLogo) ?>" alt="Logo Perusahaan" class="img-fluid" style="max-height: 70px; object-fit: contain;">
            <?php else: ?>
                <div class="p-2 border rounded text-muted small"><i class="bi bi-building fs-3"></i></div>
            <?php endif; ?>
        </div>
        <div class="company-details flex-grow-1">
            <h4 class="company-name mb-0 fw-bold text-dark text-uppercase"><?= htmlspecialchars($companyName) ?></h4>
            <div class="company-address small text-dark mt-1">
                <?= htmlspecialchars($companyAddr) ?> <?= !empty($companyCity) ? ' - ' . htmlspecialchars($companyCity) : '' ?>
            </div>
            <div class="company-contact small text-dark mt-1">
                <?php if (!empty($companyPhone)): ?>
                    <span class="me-2"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($companyPhone) ?></span>
                <?php endif; ?>
                <?php if (!empty($companyWa)): ?>
                    <span class="me-2"><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($companyWa) ?></span>
                <?php endif; ?>
                <?php if (!empty($companyEmail)): ?>
                    <span><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($companyEmail) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="header-divider-line mb-3" style="border-bottom: 2px solid #000;"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & INFO PERIODE -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold text-dark mb-0">REKAPITULASI PAJAK KELUARAN (PPN KELUARAN)</h5>
            <div class="small fw-semibold text-dark text-uppercase mt-1">TAHUN PAJAK: <?= $tahun ?></div>
        </div>

        <div class="border border-dark p-2 text-center" style="min-width: 160px; font-size: 11px;">
            <div class="text-muted small">Tanggal Cetak</div>
            <div class="fw-bold font-monospace text-dark"><?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <!-- TABEL DATA REKAPITULASI BULANAN -->
    <div class="table-responsive mb-4">
        <table class="table-pajak align-middle">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 200px;">MASA PAJAK</th>
                    <th style="width: 220px;" class="text-end">NILAI PPN KELUARAN</th>
                    <th>CATATAN / KETERANGAN</th>
                </tr>
            </thead>
            <tbody id="rekapTableBody">
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data rekapitulasi...
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="fw-bold text-dark">
                    <td colspan="2" class="text-end py-2 pe-3 text-uppercase">Grand Total:</td>
                    <td class="text-end font-monospace py-2" id="rekapGrandPpn">0</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- TANDA TANGAN / OTORISASI -->
    <div class="row mt-4 pt-2" style="font-size: 11.5px; page-break-inside: avoid;">
        <div class="col-4">
            <div class="text-muted mb-1">Dibuat Oleh:</div>
            <div class="fw-bold text-dark mb-4">Staff Perpajakan / Finance</div>
            <div style="height: 45px;"></div>
            <div class="fw-bold text-decoration-underline text-dark">( <?= htmlspecialchars($user['nama'] ?? 'Staff Finance') ?> )</div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadRekapData();
});

async function loadRekapData() {
    const params = new URLSearchParams({
        action: 'rekapitulasi',
        tahun: '<?= $tahun ?>'
    });

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/pajak_keluaran/index.php?${params.toString()}`);
        const result = await res.json();

        if (result && result.success && result.data) {
            renderRekapRows(result.data.rows, result.data.grand_totals);
        } else {
            showRekapEmpty(result.message || 'Gagal memuat data rekapitulasi.');
        }
    } catch (e) {
        showRekapEmpty('Gagal menghubungi server untuk memuat data rekapitulasi.');
    }
}

function formatRupiah(num) {
    if (num === null || num === undefined || isNaN(num)) return '0';
    return Number(num).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderRekapRows(rows, grandTotals) {
    const tbody = document.getElementById('rekapTableBody');
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                    Tidak ada data rekapitulasi untuk tahun <?= $tahun ?>.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        const totalPpn = parseFloat(r.total_ppn_keluaran) || 0;
        const ket = r.keterangan || '-';

        html += `
            <tr>
                <td class="text-center">${idx + 1}</td>
                <td><strong>${escapeHtml(r.nama_bulan)}</strong></td>
                <td class="text-end font-monospace fw-bold">${totalPpn > 0 ? formatRupiah(totalPpn) : '0'}</td>
                <td class="text-secondary small">${escapeHtml(ket)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    const gt = grandTotals || { grand_total_ppn_keluaran: 0 };
    document.getElementById('rekapGrandPpn').innerText = formatRupiah(gt.grand_total_ppn_keluaran);
}

function showRekapEmpty(msg) {
    const tbody = document.getElementById('rekapTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="4" class="text-center py-4 text-danger">
                ${escapeHtml(msg)}
            </td>
        </tr>
    `;
}

function toggleKop() {
    const url = new URL(window.location.href);
    const currentKop = url.searchParams.get('kop') !== '0';
    url.searchParams.set('kop', currentKop ? '0' : '1');
    window.location.href = url.toString();
}
</script>

</body>
</html>
