<?php
/**
 * Halaman Cetak Laporan Hutang Vendor (Print / PDF)
 * Path: admin/pages/laporan/print_hutang_vendor.php
 * Format: Pure Black & White, Terintegrasi REST API & External CSS (No Direct SQL Queries)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

$idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
$statusFaktur = trim($_GET['status_faktur'] ?? '');
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
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'finance@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Hutang Vendor - <?= htmlspecialchars($companyName) ?></title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Laporan Hutang Vendor
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'id_vendor' => $idVendor,
                'status_faktur' => $statusFaktur,
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

            <a href="<?= BASE_URL ?>/admin/pages/laporan/hutang_vendor.php" class="btn btn-outline-light btn-sm px-3">
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
        <div class="doc-title" style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0;" id="printDocTitle">Laporan Rekapitulasi Hutang Vendor</div>
    </div>

    <!-- AREA KONTEN LAPORAN DINAMIS -->
    <div id="printReportContent">
        <div class="text-center py-4 text-muted">
            <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data laporan...
        </div>
    </div>

    <!-- TANDA TANGAN / OTORISASI -->
    <div class="sig-section mt-4">
        <div class="row">
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Staf Finance / Purchasing</span>
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
const ID_VENDOR = "<?= (int)$idVendor ?>";
const STATUS_FAKTUR = "<?= addslashes($statusFaktur) ?>";
const START_DATE = "<?= addslashes($startDate) ?>";
const END_DATE = "<?= addslashes($endDate) ?>";

async function loadPrintData() {
    const container = document.getElementById('printReportContent');

    const params = new URLSearchParams({
        id_vendor: ID_VENDOR,
        status_faktur: STATUS_FAKTUR,
        start_date: START_DATE,
        end_date: END_DATE
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/hutang_vendor.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            renderPrintView(res.data);
        } else {
            container.innerHTML = `<div class="text-center py-3 text-danger">${res.message || 'Gagal memuat data.'}</div>`;
        }
    } catch (err) {
        container.innerHTML = `<div class="text-center py-3 text-danger">Terjadi kesalahan: ${err.message}</div>`;
    }
}

function renderPrintView(data) {
    const container = document.getElementById('printReportContent');
    const vendors = data.summary_vendor || [];
    const grand = data.grand_total || {};

    if (vendors.length === 0) {
        container.innerHTML = `<div class="text-center py-3 text-muted">Tidak ada data hutang vendor pada filter ini.</div>`;
        return;
    }

    // JIKA HANYA 1 VENDOR SPESIFIK DIPILIH
    if (ID_VENDOR && ID_VENDOR !== "0" && vendors.length === 1) {
        const v = vendors[0];
        document.getElementById('printDocTitle').textContent = `Laporan Rincian Hutang Vendor: ${v.nama_vendor}`;
        
        let html = `
        <div class="mb-3">
            <table class="table table-sm table-borderless mb-2" style="font-size: 11px;">
                <tr>
                    <td style="width: 120px; font-weight: bold;">Kode Vendor</td>
                    <td style="width: 10px;">:</td>
                    <td>${escapeHtml(v.kode_vendor || '-')}</td>
                    <td style="width: 130px; font-weight: bold;">Total Nilai Faktur</td>
                    <td style="width: 10px;">:</td>
                    <td class="text-end" style="font-weight: bold;">Rp ${v.formatted_total_faktur}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Nama Vendor</td>
                    <td>:</td>
                    <td style="font-weight: bold;">${escapeHtml(v.nama_vendor)}</td>
                    <td style="font-weight: bold;">Total Terbayar</td>
                    <td>:</td>
                    <td class="text-end" style="font-weight: bold;">Rp ${v.formatted_total_terbayar}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status Faktur</td>
                    <td>:</td>
                    <td>${escapeHtml(v.keterangan)}</td>
                    <td style="font-weight: bold;">Sisa Hutang</td>
                    <td>:</td>
                    <td class="text-end" style="font-weight: bold; font-size: 12px;">Rp ${v.formatted_sisa_hutang}</td>
                </tr>
            </table>
        </div>

        <div class="fw-bold mb-1 text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">1. Rincian Tagihan & Purchase Order (PO)</div>
        <table class="table-items-main mb-3">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">No</th>
                    <th style="text-align: left; width: 100px;">Nomor PO</th>
                    <th class="text-center" style="width: 75px;">Tgl PO</th>
                    <th style="text-align: left; width: 100px;">No. Faktur</th>
                    <th class="text-center" style="width: 80px;">Jatuh Tempo</th>
                    <th class="text-end" style="width: 85px;">Nilai PO</th>
                    <th class="text-end" style="width: 85px;">Nilai Faktur</th>
                    <th class="text-end" style="width: 80px;">Terbayar</th>
                    <th class="text-end" style="width: 95px;">Sisa Hutang</th>
                    <th class="text-center" style="width: 105px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>`;

        v.items.forEach((it, idx) => {
            html += `
            <tr>
                <td class="text-center">${idx + 1}</td>
                <td>${escapeHtml(it.nomor_po || '-')}</td>
                <td class="text-center">${it.formatted_tanggal_po || '-'}</td>
                <td>${escapeHtml(it.nomor_faktur || '-')}</td>
                <td class="text-center">${it.formatted_jatuh_tempo || '-'}</td>
                <td class="text-end">${it.formatted_nilai_po}</td>
                <td class="text-end">${it.formatted_total_faktur}</td>
                <td class="text-end">${it.formatted_total_terbayar}</td>
                <td class="text-end fw-bold">${it.formatted_sisa_hutang}</td>
                <td class="text-center">${escapeHtml(it.status_faktur_ket)}</td>
            </tr>`;
        });

        html += `
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-center text-uppercase">Grand Total</td>
                    <td class="text-end">${v.formatted_total_po || '0'}</td>
                    <td class="text-end">${v.formatted_total_faktur || '0'}</td>
                    <td class="text-end">${v.formatted_total_terbayar || '0'}</td>
                    <td class="text-end fw-bold">${v.formatted_sisa_hutang || '0'}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>`;

        if (v.payments && v.payments.length > 0) {
            html += `
            <div class="fw-bold mb-1 text-uppercase mt-3" style="font-size: 11px; letter-spacing: 0.5px;">2. Riwayat Pembayaran</div>
            <table class="table-items-main mb-3">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 30px;">No</th>
                        <th style="text-align: left; width: 110px;">Kode Bayar</th>
                        <th class="text-center" style="width: 100px;">Tgl Bayar</th>
                        <th style="text-align: left; width: 110px;">No. Faktur / PO</th>
                        <th style="text-align: left;">Bank Pengirim</th>
                        <th style="text-align: left;">Bank Tujuan</th>
                        <th class="text-end" style="width: 100px;">Nominal</th>
                        <th style="text-align: left; width: 90px;">No. Ref</th>
                    </tr>
                </thead>
                <tbody>`;

            v.payments.forEach((p, pIdx) => {
                html += `
                <tr>
                    <td class="text-center">${pIdx + 1}</td>
                    <td>${escapeHtml(p.kode_pembayaran || '-')}</td>
                    <td class="text-center">${p.formatted_tanggal_bayar || '-'}</td>
                    <td>${escapeHtml(p.nomor_faktur || p.nomor_po || '-')}</td>
                    <td>${escapeHtml(p.bank_pengirim || '-')}${p.norek_pengirim ? ' (' + escapeHtml(p.norek_pengirim) + ')' : ''}</td>
                    <td>${escapeHtml(p.bank_tujuan || '-')}${p.norek_tujuan ? ' (' + escapeHtml(p.norek_tujuan) + ')' : ''}</td>
                    <td class="text-end fw-bold">${p.formatted_nominal}</td>
                    <td>${escapeHtml(p.no_ref || '-')}</td>
                </tr>`;
            });

            html += `
                </tbody>
            </table>`;
        }

        container.innerHTML = html;
        return;
    }

    // JIKA CETAK REKAPITULASI SEMUA VENDOR
    let html = `
    <table class="table-items-main mb-4">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th style="text-align: left; width: 110px;">Kode Vendor</th>
                <th style="text-align: left;">Nama Vendor</th>
                <th class="text-end" style="width: 130px;">Total Faktur</th>
                <th class="text-end" style="width: 140px;">Sisa Hutang</th>
                <th class="text-center" style="width: 150px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>`;

    vendors.forEach((item, idx) => {
        const countSudah = item.count_sudah_faktur || 0;
        const countBelum = item.count_belum_faktur || 0;
        let ketList = [];
        if (countSudah > 0) ketList.push(`${countSudah} Difakturkan`);
        if (countBelum > 0) ketList.push(`${countBelum} Belum Difakturkan`);
        const ketText = ketList.join(', ') || '-';

        html += `
        <tr>
            <td class="text-center">${idx + 1}</td>
            <td>${escapeHtml(item.kode_vendor || '-')}</td>
            <td class="fw-semibold">${escapeHtml(item.nama_vendor || '-')}</td>
            <td class="text-end">${item.formatted_total_faktur}</td>
            <td class="text-end fw-bold">${item.formatted_sisa_hutang}</td>
            <td class="text-center">${escapeHtml(ketText)}</td>
        </tr>`;
    });

    html += `
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" class="text-center text-uppercase">Grand Total</td>
                <td class="text-end">${grand.formatted_total_faktur || '0'}</td>
                <td class="text-end fw-bold" style="font-size: 11px;">${grand.formatted_sisa_hutang || '0'}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>`;

    container.innerHTML = html;
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
