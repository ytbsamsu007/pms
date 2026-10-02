<?php
/**
 * Halaman Cetak Rekapitulasi Tahunan Pajak Masukan per Bulan (Print / PDF)
 * Path: admin/pages/laporan/print_rekapitulasi_pajak.php
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
    <title>Rekapitulasi Pajak Masukan Tahun <?= $tahun ?> - <?= htmlspecialchars($companyName) ?></title>
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
            font-size: 12px !important;
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Rekapitulasi Pajak Masukan Tahun <?= $tahun ?>
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group">
                <a href="?tahun=<?= $tahun ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?tahun=<?= $tahun ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/laporan/pajak_masukan.php" class="btn btn-outline-light btn-sm px-3">
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
    <div class="kop-container d-flex justify-content-between align-items-center pb-2">
        <div class="kop-left d-flex align-items-center gap-3">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyLogo) ?>" alt="Logo Perusahaan" style="max-height: 55px; max-width: 140px; object-fit: contain;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-dark text-white rounded p-2" style="width: 50px; height: 50px;">
                    <i class="bi bi-buildings fs-3"></i>
                </div>
            <?php endif; ?>
            <div>
                <div class="company-title fw-bold fs-5 text-dark"><?= htmlspecialchars($companyName) ?></div>
                <div class="company-addr small text-dark"><?= htmlspecialchars($companyAddr) ?><?= !empty($companyCity) ? ', ' . htmlspecialchars($companyCity) : '' ?></div>
                <div class="company-contacts small text-dark">
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
            <h5 class="fw-bold text-dark mb-0">REKAPITULASI PAJAK MASUKAN (PPN &amp; PPNBM)</h5>
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
                    <th style="width: 40px;">No</th>
                    <th>MASA PAJAK</th>
                    <th style="width: 80px;" class="text-center">FAKTUR</th>
                    <th style="width: 140px;" class="text-end">DPP</th>
                    <th style="width: 140px;" class="text-end">PPN MASUKAN</th>
                    <th style="width: 130px;" class="text-end">PPNBM</th>
                    <th style="width: 160px;" class="text-end">TOTAL TAGIHAN</th>
                </tr>
            </thead>
            <tbody id="rekapTableBody">
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data rekapitulasi...
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="fw-bold text-dark">
                    <td colspan="2" class="text-end py-2 pe-3 text-uppercase">Grand Total:</td>
                    <td class="text-center font-monospace py-2" id="rekapGrandFaktur">0</td>
                    <td class="text-end font-monospace py-2" id="rekapGrandDpp">0</td>
                    <td class="text-end font-monospace py-2" id="rekapGrandPpn">0</td>
                    <td class="text-end font-monospace py-2" id="rekapGrandPpnbm">0</td>
                    <td class="text-end font-monospace py-2" id="rekapGrandTotalTagihan">0</td>
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
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/laporan/pajak_masukan.php?action=rekapitulasi&tahun=<?= $tahun ?>`);
        const result = await res.json();

        if (result && result.success && result.data) {
            renderRekapRows(result.data.rows, result.data.grand_totals);
        } else {
            showRekapEmpty(result.message || 'Gagal memuat data rekapitulasi.');
        }
    } catch (e) {
        showRekapEmpty('Terjadi kesalahan: ' + e.message);
    }
}

function renderRekapRows(rows, totals) {
    const tbody = document.getElementById('rekapTableBody');
    if (!rows || rows.length === 0) {
        showRekapEmpty('Tidak ada data faktur pajak masukan pada tahun <?= $tahun ?>.');
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        html += `
        <tr>
            <td class="text-center font-monospace">${idx + 1}</td>
            <td>
                <strong class="text-dark">${escapeHtml(r.nama_bulan)} ${r.tahun}</strong>
            </td>
            <td class="text-center font-monospace">${r.jumlah_faktur}</td>
            <td class="text-end font-monospace">${formatAngka(r.total_dpp)}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatAngka(r.total_ppn_masukan)}</td>
            <td class="text-end font-monospace">${parseFloat(r.total_ppnbm) > 0 ? formatAngka(r.total_ppnbm) : '0'}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatAngka(r.total_tagihan)}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    // Grand Totals
    if (totals) {
        document.getElementById('rekapGrandFaktur').textContent = totals.total_faktur || 0;
        document.getElementById('rekapGrandDpp').textContent = formatAngka(totals.grand_total_dpp);
        document.getElementById('rekapGrandPpn').textContent = formatAngka(totals.grand_total_ppn);
        document.getElementById('rekapGrandPpnbm').textContent = formatAngka(totals.grand_total_ppnbm);
        document.getElementById('rekapGrandTotalTagihan').textContent = formatAngka(totals.grand_total_tagihan);
    }
}

function showRekapEmpty(msg) {
    document.getElementById('rekapTableBody').innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">${msg}</td>
        </tr>`;
}

function formatAngka(val) {
    const n = parseFloat(val) || 0;
    return n.toLocaleString('id-ID');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

</body>
</html>
