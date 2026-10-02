<?php
/**
 * Halaman Cetak Laporan Rekapitulasi Retur Pembelian (Print / PDF)
 * Path: admin/pages/laporan/print_retur_pembelian.php
 * Format: Pure Black & White, Terintegrasi REST API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MEKANIK, ROLE_MANAGER, ROLE_FINANCE]);

$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
$status = trim($_GET['status'] ?? '');
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
    <title>Laporan Rekapitulasi Retur Pembelian - <?= htmlspecialchars($companyName) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
    <style>
        .table-summary-box {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 11px;
        }
        .table-summary-box th, .table-summary-box td {
            border: 1px solid #333;
            padding: 5px 8px;
        }
        .table-summary-box th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Laporan Rekapitulasi Retur Pembelian
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'id_vendor' => $idVendor,
                'status' => $status
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

            <a href="<?= BASE_URL ?>/admin/pages/laporan/retur_pembelian.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- CONTAINER UTAMA DOKUMEN CETAK -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>" id="printContainer">
    
    <?php if ($useKop): ?>
    <!-- KOP PERUSAHAAN RESMI -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars(ltrim($companyLogo, '/')) ?>" alt="Logo Perusahaan" style="max-height: 55px; max-width: 140px; object-fit: contain;">
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
        <div class="doc-title" style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0;" id="printDocTitle">Laporan Rekapitulasi Retur Pembelian</div>
        <div class="doc-subtitle" style="font-size: 11px; margin-top: 4px; color: #555;" id="printDocSubtitle">Periode: Semua Tanggal</div>
    </div>

    <!-- TABEL UTAMA LAPORAN: No, Vendor, Kompensasi, No. Retur, Kuantitas, Nominal, Biaya Retur, Status -->
    <table class="table-items-main mb-3" id="tablePrintReport">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th style="text-align: left;">Vendor</th>
                <th class="text-center" style="width: 105px;">Kompensasi</th>
                <th class="text-center" style="width: 100px;">No. Retur</th>
                <th class="text-end" style="width: 80px;">KTS</th>
                <th class="text-end" style="width: 80px;">Nominal</th>
                <th class="text-end" style="width: 80px;">Biaya</th>
                <th class="text-center" style="width: 80px;">Status</th>
            </tr>
        </thead>
        <tbody id="printTableBody">
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data laporan retur pembelian...
                </td>
            </tr>
        </tbody>
        <tfoot id="printTableFoot" style="display: none;">
            <tr class="total-row" style="font-weight: bold; background-color: #f8f9fa;">
                <td colspan="4" class="text-end pe-2">TOTAL:</td>
                <td class="text-end font-monospace" id="footTotalQty">-</td>
                <td class="text-end font-monospace" id="footTotalNominal">-</td>
                <td class="text-end font-monospace" id="footTotalBiayaRetur">-</td>
                <td class="text-center" id="footTotalDokumen">0 Dokumen</td>
            </tr>
        </tfoot>
    </table>

    <!-- RINGKASAN REKAPITULASI DITERIMA & BELUM DITERIMA -->
    <div id="printSummaryContainer" style="display: none; margin-top: 15px; page-break-inside: avoid;">
        <div class="row g-2">
            <div class="col-6">
                <table class="table-summary-box">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-start fw-bold">1. Rekapitulasi Tukar Unit (Stok)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Stok Telah Diterima:</td>
                            <td class="text-end font-monospace fw-bold" id="sumQtyDiterima">0 Unit</td>
                        </tr>
                        <tr>
                            <td>Stok Belum Diterima:</td>
                            <td class="text-end font-monospace fw-bold" id="sumQtyBelum">0 Unit</td>
                        </tr>
                        <tr style="background-color: #f8f9fa;">
                            <td class="fw-bold">Total Stok Tukar Unit:</td>
                            <td class="text-end font-monospace fw-bold" id="sumQtyTotal">0 Unit</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-6">
                <table class="table-summary-box">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-start fw-bold">2. Rekapitulasi Potong Tagihan (-Rp) &amp; Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Potong Tagihan Diterima:</td>
                            <td class="text-end font-monospace fw-bold" id="sumNominalDiterima">Rp 0</td>
                        </tr>
                        <tr>
                            <td>Potong Tagihan Belum Diterima:</td>
                            <td class="text-end font-monospace fw-bold" id="sumNominalBelum">Rp 0</td>
                        </tr>
                        <tr style="background-color: #f8f9fa;">
                            <td class="fw-bold">Total Potong Tagihan:</td>
                            <td class="text-end font-monospace fw-bold" id="sumNominalTotal">Rp 0</td>
                        </tr>
                        <tr>
                            <td>Total Biaya Retur:</td>
                            <td class="text-end font-monospace fw-bold" id="sumBiayaReturTotal">Rp 0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (2 TANDA TANGAN: DIBUAT & DISETUJUI) -->
    <div class="sig-section mt-4" style="page-break-inside: avoid;">
        <div class="row justify-content-between">
            <div class="col-5 sig-col text-center">
                <div class="sig-header-main fw-bold">Dibuat Oleh,</div>
                <div class="sig-header-sub text-muted" id="sigRolePembuat">(Staff Logistik / Purchasing)</div>
                <div class="sig-line-box" style="margin-top: 50px;">
                    <span class="sig-person-name fw-bold" id="sigNamaPembuat"><?= htmlspecialchars($user['nama'] ?? $user['username'] ?? 'Petugas') ?></span>
                </div>
                <div class="sig-footer-note text-muted small" id="sigTanggalPembuat">Tanggal: <?= date('d/m/Y') ?></div>
            </div>
            <div class="col-5 sig-col text-center">
                <div class="sig-header-main fw-bold">Disetujui Oleh,</div>
                <div class="sig-header-sub text-muted" id="sigRoleApprover">(Manager / Kepala Logistik)</div>
                <div class="sig-line-box" style="margin-top: 50px;">
                    <span class="sig-person-name fw-bold" id="sigNamaApprover">..........................................</span>
                </div>
                <div class="sig-footer-note text-muted small">Tanggal: ....................</div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH DOKUMEN -->
    <div class="footer-line-container mt-4">
        <div class="footer-right">
            <div id="printFooterText">Dokumen dicetak pada: <?= date('d/m/Y H:i') ?> WITA | Oleh: <?= htmlspecialchars($user['nama'] ?? $user['username'] ?? 'Petugas') ?></div>
            <div>Purchasing &amp; Logistics Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<script>
const START_DATE = "<?= addslashes($startDate) ?>";
const END_DATE = "<?= addslashes($endDate) ?>";
const ID_VENDOR = "<?= (int)$idVendor ?>";
const STATUS_FILTER = "<?= addslashes($status) ?>";

function formatShortDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const mon = months[d.getMonth()];
    const yr = d.getFullYear();
    return `${day} ${mon} ${yr}`;
}

async function loadPrintData() {
    const tbody = document.getElementById('printTableBody');
    const tfoot = document.getElementById('printTableFoot');
    const sumBox = document.getElementById('printSummaryContainer');

    const params = new URLSearchParams({
        page: 1,
        limit: 1000
    });
    if (START_DATE) params.append('start_date', START_DATE);
    if (END_DATE) params.append('end_date', END_DATE);
    if (ID_VENDOR && ID_VENDOR > 0) params.append('id_vendor', ID_VENDOR);
    if (STATUS_FILTER) params.append('status', STATUS_FILTER);

    // Subtitle Periode
    if (START_DATE && END_DATE) {
        document.getElementById('printDocSubtitle').textContent = `Periode: ${formatShortDate(START_DATE)} s/d ${formatShortDate(END_DATE)}`;
    } else if (START_DATE) {
        document.getElementById('printDocSubtitle').textContent = `Periode Mulai: ${formatShortDate(START_DATE)}`;
    } else if (END_DATE) {
        document.getElementById('printDocSubtitle').textContent = `Periode Sampai: ${formatShortDate(END_DATE)}`;
    }

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/retur_pembelian.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            // Update info penandatangan & footer dari meta API
            if (res.data.meta) {
                if (res.data.meta.generated_by) {
                    document.getElementById('sigNamaPembuat').textContent = res.data.meta.generated_by;
                }
                if (res.data.meta.tanggal_cetak) {
                    document.getElementById('sigTanggalPembuat').textContent = `Tanggal: ${res.data.meta.tanggal_cetak}`;
                }
                if (res.data.meta.generated_at && res.data.meta.generated_by) {
                    document.getElementById('printFooterText').textContent = `Dokumen dicetak pada: ${res.data.meta.generated_at} | Oleh: ${res.data.meta.generated_by}`;
                }
            }
            renderPrintTable(res.data.items || []);
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-3">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function renderPrintTable(items) {
    const tbody = document.getElementById('printTableBody');
    const tfoot = document.getElementById('printTableFoot');
    const sumBox = document.getElementById('printSummaryContainer');

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-3">Tidak ada data retur pembelian pada kriteria ini.</td></tr>`;
        tfoot.style.display = 'none';
        if (sumBox) sumBox.style.display = 'none';
        return;
    }

    let html = '';
    let totalQtyTukarUnit = 0;
    let totalNominalPotong = 0;
    let totalBiayaRetur = 0;

    let qtyDiterima = 0;
    let qtyBelum = 0;

    let nominalDiterima = 0;
    let nominalBelum = 0;

    items.forEach((it, idx) => {
        const vendorText = `${escapeHtml(it.nama_vendor || '-')} [${escapeHtml(it.kode_vendor || '-')}]`;
        const isTukarUnit = (parseInt(it.kompensasi, 10) === 1);
        const kompText = isTukarUnit ? 'Tukar Unit' : 'Potong Tagihan';
        const statusText = escapeHtml(it.status || '-');

        const qtyVal = Number(it.total_qty_retur || 0);
        const nominalVal = Number(it.total || 0);
        const biayaVal = Number(it.biaya_retur || 0);

        totalBiayaRetur += biayaVal;

        // Status diterima vs belum
        const isDiterima = ['DITERIMA', 'SELESAI'].includes(it.status);
        const isBatal = ['DITOLAK', 'BATAL'].includes(it.status);

        let qtyDisplay = '-';
        let nominalDisplay = '-';
        const biayaDisplay = (biayaVal > 0) ? `Rp ${biayaVal.toLocaleString('id-ID')}` : '-';

        if (isTukarUnit) {
            qtyDisplay = `${qtyVal.toLocaleString('id-ID')} Unit`;
            totalQtyTukarUnit += qtyVal;
            if (isDiterima) {
                qtyDiterima += qtyVal;
            } else if (!isBatal) {
                qtyBelum += qtyVal;
            }
        } else {
            nominalDisplay = `Rp ${nominalVal.toLocaleString('id-ID')}`;
            totalNominalPotong += nominalVal;
            if (isDiterima) {
                nominalDiterima += nominalVal;
            } else if (!isBatal) {
                nominalBelum += nominalVal;
            }
        }

        html += `
        <tr>
            <td class="text-center">${idx + 1}</td>
            <td>
                <span class="fw-semibold">${vendorText}</span>
            </td>
            <td class="text-center">${kompText}</td>
            <td class="text-center font-monospace fw-bold">${escapeHtml(it.nomor_po_retur || '-')}</td>
            <td class="text-end font-monospace">${qtyDisplay}</td>
            <td class="text-end font-monospace">${nominalDisplay}</td>
            <td class="text-end font-monospace">${biayaDisplay}</td>
            <td class="text-center fw-medium">${statusText}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    // Footer Table
    document.getElementById('footTotalQty').textContent = `${totalQtyTukarUnit.toLocaleString('id-ID')} Unit`;
    document.getElementById('footTotalNominal').textContent = `Rp ${totalNominalPotong.toLocaleString('id-ID')}`;
    document.getElementById('footTotalBiayaRetur').textContent = `Rp ${totalBiayaRetur.toLocaleString('id-ID')}`;
    document.getElementById('footTotalDokumen').textContent = `${items.length} Doc`;
    tfoot.style.display = 'table-footer-group';

    // Summary Box
    document.getElementById('sumQtyDiterima').textContent = `${qtyDiterima.toLocaleString('id-ID')} Unit`;
    document.getElementById('sumQtyBelum').textContent = `${qtyBelum.toLocaleString('id-ID')} Unit`;
    document.getElementById('sumQtyTotal').textContent = `${totalQtyTukarUnit.toLocaleString('id-ID')} Unit`;

    document.getElementById('sumNominalDiterima').textContent = `Rp ${nominalDiterima.toLocaleString('id-ID')}`;
    document.getElementById('sumNominalBelum').textContent = `Rp ${nominalBelum.toLocaleString('id-ID')}`;
    document.getElementById('sumNominalTotal').textContent = `Rp ${totalNominalPotong.toLocaleString('id-ID')}`;
    document.getElementById('sumBiayaReturTotal').textContent = `Rp ${totalBiayaRetur.toLocaleString('id-ID')}`;

    if (sumBox) sumBox.style.display = 'block';
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
