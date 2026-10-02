<?php
/**
 * Halaman Cetak Laporan Pajak Keluaran (Print / PDF)
 * Path: admin/pages/laporan/print_pajak_keluaran.php
 * Format: Standard Clean Corporate Table
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$tahun = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan = isset($_GET['bulan']) && is_numeric($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$q = trim($_GET['q'] ?? '');
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

// Nama Bulan Indo
$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$periodeText = ($bulan > 0 && isset($namaBulan[$bulan])) 
    ? "Masa Pajak: {$namaBulan[$bulan]} {$tahun}" 
    : ($tahun > 0 ? "Tahun Pajak: {$tahun} (Semua Masa)" : "Semua Periode");

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
    <title>Pajak Keluaran - <?= htmlspecialchars($companyName) ?></title>
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
            padding: 5px 8px !important;
            font-size: 11px;
            color: #000;
        }
        .table-pajak th {
            font-weight: bold;
            text-align: center;
            background-color: #fff !important;
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Laporan Pajak Keluaran (PPN Keluaran)
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'tahun' => $tahun,
                'bulan' => $bulan,
                'q' => $q
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

            <a href="<?= BASE_URL ?>/admin/pages/laporan/pajak_keluaran.php" class="btn btn-outline-light btn-sm px-3">
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
            <h5 class="fw-bold text-dark mb-0">PAJAK KELUARAN (PPN KELUARAN)</h5>
            <div class="small fw-semibold text-dark text-uppercase mt-1"><?= htmlspecialchars($periodeText) ?></div>
        </div>

        <div class="border border-dark p-2 text-center" style="min-width: 160px; font-size: 11px;">
            <div class="text-muted small">Tanggal Cetak</div>
            <div class="fw-bold font-monospace text-dark"><?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <!-- TABEL DATA REKAPITULASI PAJAK KELUARAN SESUAI FORMAT STANDAR LAPORAN -->
    <div class="table-responsive mb-4">
        <table class="table-pajak align-middle">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th style="width: 180px;">PERIODE (BULAN / TAHUN)</th>
                    <th>KETERANGAN</th>
                    <th style="width: 160px;">DIINPUT OLEH</th>
                    <th style="width: 180px;" class="text-end">NILAI PPN KELUARAN</th>
                </tr>
            </thead>
            <tbody id="printTableBody">
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan pajak keluaran...
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="fw-bold text-dark">
                    <td colspan="4" class="text-end py-2 pe-3 text-uppercase">Grand Total:</td>
                    <td class="text-end font-monospace py-2" id="printGrandPpn">0</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- TANDA TANGAN / OTORISASI (HANYA DIBUAT OLEH) -->
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
    loadPrintData();
});

async function loadPrintData() {
    const params = new URLSearchParams({
        tahun: '<?= $tahun ?>',
        bulan: '<?= $bulan ?>',
        q: '<?= urlencode($q) ?>',
        all: '1'
    });

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/pajak_keluaran/index.php?${params.toString()}`);
        const result = await res.json();

        if (result && result.success && result.data) {
            renderPrintRows(result.data.items || [], result.data.summary || {});
        } else {
            showPrintEmpty(result.message || 'Gagal memuat data laporan.');
        }
    } catch (e) {
        showPrintEmpty('Terjadi kesalahan: ' + e.message);
    }
}

function renderPrintRows(rows, summary) {
    const tbody = document.getElementById('printTableBody');
    if (!rows || rows.length === 0) {
        showPrintEmpty('Tidak ada data pajak keluaran pada periode ini.');
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        html += `
        <tr>
            <td class="text-center font-monospace">${idx + 1}</td>
            <td>
                <div class="fw-bold text-dark font-monospace">${escapeHtml(r.nama_bulan)} ${escapeHtml(r.tahun)}</div>
            </td>
            <td>${escapeHtml(r.keterangan || '-')}</td>
            <td>${escapeHtml(r.nama_karyawan || 'Finance System')}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatAngka(r.ppn_keluaran)}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    // Grand Total
    const totalPpn = summary ? summary.total_ppn_keluaran : 0;
    document.getElementById('printGrandPpn').textContent = formatAngka(totalPpn);
}

function showPrintEmpty(msg) {
    document.getElementById('printTableBody').innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4 text-muted">${msg}</td>
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
