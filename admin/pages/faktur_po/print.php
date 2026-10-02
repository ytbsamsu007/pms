<?php
/**
 * Halaman Cetak Dokumen Faktur Purchase Order (Faktur Pembelian)
 * Path: admin/pages/faktur_po/print.php
 * Format: Terintegrasi API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_PURCHASING, ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);
$idFaktur = isset($_GET['id']) ? decodeId($_GET['id']) : (isset($_GET['id_faktur']) ? decodeId($_GET['id_faktur']) : 0);
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idFaktur <= 0) {
    die("ID Faktur Purchase Order tidak valid.");
}

// Ambil Data Profil Perusahaan
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
    <title>Faktur Purchase Order</title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak
            </span>
            <span class="badge bg-secondary font-monospace" id="toolbarNomorFaktur">...</span>
            <span class="badge bg-primary" id="toolbarStatusFaktur">...</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= encodeId($idFaktur) ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= encodeId($idFaktur) ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/faktur_po/index.php" class="btn btn-outline-light btn-sm px-3">
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
    <!-- KOP SURAT (SESUAI PROFIL PERUSAHAAN) -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/../../../uploads/profile/' . $companyLogo)): ?>
                <img src="<?= BASE_URL ?>/uploads/profile/<?= htmlspecialchars($companyLogo) ?>" alt="Logo" style="width: 54px; height: 54px; object-fit: contain; flex-shrink: 0;">
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
                <div class="company-addr"><?= htmlspecialchars($companyAddr) ?><?= $companyCity ? ' - ' . htmlspecialchars($companyCity) : '' ?></div>
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
                FAKTUR &amp;<br>TAGIHAN PEMBELIAN<br>
            </div>
        </div>
    </div>
    
    <div class="header-divider-line"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>: Bagian atas dikosongkan untuk dicetak pada kertas berkop resmi perusahaan.
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & KOTAK NOMOR DOKUMEN -->
    <div class="title-box-row">
        <div class="title-area">
            <div class="doc-title-main">FAKTUR PEMBELIAN BARANG</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">PURCHASE &nbsp; INVOICE</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Faktur Sistem</div>
            <div class="box-row-val font-monospace" id="docNomorFaktur">-</div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal Faktur</div>
            <div class="box-row-val font-monospace" id="docTanggalFaktur">-</div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM (DOKUMEN ASAL, VENDOR, & TERMIN PEMBAYARAN) -->
    <div class="info-grid">
        <!-- Kolom Kiri: Vendor & Referensi Dokumen -->
        <div class="info-col-left">
            <div class="small text-muted mb-1" style="font-size: 11px;">Tagihan Dari Rekanan Vendor:</div>
            <div class="fw-bold text-dark mb-1" style="font-size: 13px;" id="docNamaVendor">-</div>
            <div class="text-dark mb-2" style="font-size: 11.5px; line-height: 1.35;" id="docAlamatVendor">-</div>
            
            <table class="table-meta-details mt-2">
                <tr>
                    <td class="lbl">No. Invoice Vendor</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold" id="docNomorFakturVendor">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Faktur Pajak</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorFakturPajak">-</td>
                </tr>
                <tr id="docRowTglFakturPajak" class="d-none">
                    <td class="lbl">Tanggal Faktur Pajak</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docTanggalFakturPajak">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Purchase Order (PO)</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorPo">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Penerimaan (RCV)</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorRcv">-</td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan: Rekening & Syarat Pembayaran -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Term of Payment (TOP)</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docTop">-</td>
                </tr>
                <tr>
                    <td class="lbl">Tgl Jatuh Tempo</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold text-dark" id="docTanggalJatuhTempo">-</td>
                </tr>
                <tr>
                    <td class="lbl">Rekening Vendor</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docRekeningVendor">-</td>
                </tr>
                <tr>
                    <td class="lbl">Site Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val" id="docNamaSite">-</td>
                </tr>
                <tr>
                    <td class="lbl">Status Tagihan</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold font-monospace" id="docStatus">-</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL RINCIAN BARANG -->
    <div class="table-title fw-bold text-dark mb-1" style="font-size: 11px; text-transform: uppercase;">
        Rincian Barang &amp; Penagihan Berdasarkan Penerimaan:
    </div>
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 30px;">NO.</th>
                <th style="width: 75px;">KODE</th>
                <th>NAMA BARANG</th>
                <th style="width: 55px;" class="text-center">KTS PO</th>
                <th style="width: 65px;" class="text-center">KTS RCV</th>
                <th style="width: 55px;" class="text-center">RETUR</th>
                <th style="width: 55px;" class="text-center">SATUAN</th>
                <th style="width: 100px;" class="text-end">HARGA</th>
                <th style="width: 85px;" class="text-end">DISKON</th>
                <th style="width: 110px;" class="text-end">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody id="docItemsTableBody">
            <tr>
                <td colspan="10" class="text-center py-3 text-muted">Memuat rincian barang...</td>
            </tr>
        </tbody>
    </table>

    <!-- GRID CATATAN & RINGKASAN FINANSIAL FAKTUR -->
    <div class="summary-notes-grid">
        <!-- Kolom Kiri: Terbilang & Catatan -->
        <div class="notes-column">
            <div class="notes-card">
                <div class="notes-title">Terbilang:</div>
                <div class="fw-bold text-dark font-monospace mb-2" style="font-size: 11.5px; line-height: 1.4;" id="docTerbilangTotal">
                    # - #
                </div>
                
                <div class="pt-2 border-top border-dark d-none" style="font-size: 11px;" id="docKeteranganContainer">
                    <strong>Catatan Faktur:</strong><br>
                    <span id="docKeterangan"></span>
                </div>

                <div class="pt-2 mt-2 border-top text-muted" style="font-size: 10px;">
                    * Pembayaran tagihan ditransfer resmi ke rekening vendor sesuai data yang tertera di atas.<br>
                    * Harap konfirmasi bukti transfer jika pembayaran telah berhasil diproses.
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Nilai Finansial -->
        <div class="summary-column">
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td class="lbl">Subtotal Barang Diterima</td>
                        <td class="val" id="docSubtotalRcv">Rp 0</td>
                    </tr>
                    <tr id="docRowRetur" class="d-none">
                        <td class="lbl">Potongan Retur PO (Credit Note)</td>
                        <td class="val" style="color: #dc3545;" id="docNilaiRetur">- Rp 0</td>
                    </tr>
                    <tr id="docRowDiskon" class="d-none">
                        <td class="lbl">Diskon Tambahan Faktur</td>
                        <td class="val" style="color: #dc3545;" id="docDiskon">- Rp 0</td>
                    </tr>
                    <tr>
                        <td class="lbl">DPP (Dasar Pengenaan Pajak)</td>
                        <td class="val font-monospace fw-bold" id="docDpp">Rp 0</td>
                    </tr>
                    <tr id="docRowPpnbm" class="d-none">
                        <td class="lbl" id="docLabelPpnbm">PPnBM (0%):</td>
                        <td class="val" id="docNominalPpnbm">Rp 0</td>
                    </tr>
                    <tr id="docRowPpn">
                        <td class="lbl" id="docLabelPpn">PPN (11%):</td>
                        <td class="val" id="docNominalPpn">Rp 0</td>
                    </tr>
                    <tr id="docRowBiayaLain" class="d-none">
                        <td class="lbl">Biaya Lain-lain / Ongkir</td>
                        <td class="val" id="docBiayaLain">Rp 0</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td class="lbl">TOTAL TAGIHAN FAKTUR</td>
                        <td class="val" id="docTotalTagihan">Rp 0</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM RESMI) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Dibuat Oleh (Purchasing / Petugas) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh</div>
                <div class="sig-header-sub" id="docSigRolePembuat">(Staff Purchasing)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigPembuat">Staff</span> &nbsp; )
                </div>
            </div>

            <!-- 2. Disetujui Oleh (Manager Finance / Direksi) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh</div>
                <div class="sig-header-sub">(Manager Finance / Direksi)</div>
                <div class="sig-line-box">
                    ( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; )
                </div>
            </div>

            <!-- 3. Pihak Rekanan Vendor -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diterima / Rekanan</div>
                <div class="sig-header-sub">(Pihak Rekanan Vendor)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigVendor">Pihak Rekanan Vendor</span> &nbsp; )
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH -->
    <div class="footer-line-container">
        <div class="footer-right">
            <div class="fw-bold">Halaman 1 dari 1</div>
            <div>Dicetak: <?= date('d/m/Y H:i') ?></div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<!-- SCRIPT FETCH DATA API & RENDER PRINT DOCUMENT -->
<script>
const BASE_URL = '<?= BASE_URL ?>';
const idFaktur = <?= $idFaktur ?>;

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatNumber(num) {
    return (parseFloat(num) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function formatTglPanjang(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const bulan = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
}

function terbilang(angka) {
    angka = Math.abs(parseFloat(angka) || 0);
    const satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    if (angka < 12) return " " + satuan[Math.floor(angka)];
    if (angka < 20) return terbilang(angka - 10) + " Belas";
    if (angka < 100) return terbilang(Math.floor(angka / 10)) + " Puluh" + terbilang(angka % 10);
    if (angka < 200) return " Seratus" + terbilang(angka - 100);
    if (angka < 1000) return terbilang(Math.floor(angka / 100)) + " Ratus" + terbilang(angka % 100);
    if (angka < 2000) return " Seribu" + terbilang(angka - 1000);
    if (angka < 1000000) return terbilang(Math.floor(angka / 1000)) + " Ribu" + terbilang(angka % 1000);
    if (angka < 1000000000) return terbilang(Math.floor(angka / 1000000)) + " Juta" + terbilang(angka % 1000000);
    if (angka < 1000000000000) return terbilang(Math.floor(angka / 1000000000)) + " Miliar" + terbilang(angka % 1000000000);
    if (angka < 1000000000000000) return terbilang(Math.floor(angka / 1000000000000)) + " Triliun" + terbilang(angka % 1000000000000);
    return "";
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`${BASE_URL}/api/faktur_po/index.php?id=${idFaktur}`);
        const result = await response.json();

        if (!result.success || !result.data) {
            alert(result.message || 'Gagal memuat data faktur PO.');
            return;
        }

        const fp = result.data;

        // Toolbar
        document.getElementById('toolbarNomorFaktur').textContent = fp.nomor_faktur || '-';
        document.getElementById('toolbarStatusFaktur').textContent = fp.status || '-';
        document.title = `Faktur Purchase Order - ${fp.nomor_faktur || ''}`;

        // Header Meta
        document.getElementById('docNomorFaktur').textContent = fp.nomor_faktur || '-';
        document.getElementById('docTanggalFaktur').textContent = formatTglPanjang(fp.tanggal_faktur_vendor || fp.created_at);

        // Vendor & Dokumen Info
        document.getElementById('docNamaVendor').textContent = fp.nama_vendor || '-';
        document.getElementById('docAlamatVendor').textContent = fp.alamat_vendor || '-';
        document.getElementById('docNomorFakturVendor').textContent = fp.nomor_faktur_vendor || '-';
        document.getElementById('docNomorFakturPajak').textContent = fp.nomor_faktur_pajak || '-';
        if (fp.tanggal_faktur_pajak) {
            document.getElementById('docRowTglFakturPajak').classList.remove('d-none');
            document.getElementById('docTanggalFakturPajak').textContent = formatTglPanjang(fp.tanggal_faktur_pajak);
        } else {
            document.getElementById('docRowTglFakturPajak').classList.add('d-none');
        }
        document.getElementById('docNomorPo').textContent = fp.nomor_po || '-';
        
        let rcvText = fp.nomor_rcv || '-';
        if (fp.nomor_sj_rcv) {
            rcvText += ` (SJ: ${fp.nomor_sj_rcv})`;
        }
        document.getElementById('docNomorRcv').textContent = rcvText;

        // Right Info
        document.getElementById('docTop').textContent = `${parseInt(fp.term_of_payment || 0)} Hari`;
        document.getElementById('docTanggalJatuhTempo').textContent = formatTglPanjang(fp.tanggal_jatuh_tempo);
        
        let rekVendor = `${escapeHtml(fp.nama_bank || '-')} • <strong>${escapeHtml(fp.nomor_rekening || '-')}</strong><br>`;
        rekVendor += `<span class="text-muted" style="font-size: 10.5px;">a.n. ${escapeHtml(fp.atas_nama_rekening || fp.nama_vendor || '-')}</span>`;
        document.getElementById('docRekeningVendor').innerHTML = rekVendor;

        document.getElementById('docNamaSite').textContent = fp.nama_site || '-';
        document.getElementById('docStatus').textContent = `[ ${fp.status || '-'} ]`;

        // Render Items Table (No "Rp" in item table cells)
        const items = fp.items || [];
        const ratePpnbmPo = parseFloat(fp.rate_ppnbm || fp.pajak_PPnBM || 0);

        if (items.length === 0) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="10" class="text-center py-3 text-muted">Tidak ada rincian barang.</td>
                </tr>
            `;
        } else {
            let itemsHtml = '';
            items.forEach((it, idx) => {
                const qtyPo = parseFloat(it.qty_po) || 0;
                const qtyRcv = parseFloat(it.qty_rcv) || 0;
                const qtyRet = parseFloat(it.qty_retur) || 0;
                const harga = parseFloat(it.harga_satuan) || 0;
                const disc = parseFloat(it.diskon_item) || 0;
                const sub = parseFloat(it.subtotal) || 0;
                const isPpnbmItem = (parseInt(it.PPnBM) === 1 || parseFloat(it.rate_PPnBM) > 0);
                const itemRatePpnbm = parseFloat(it.rate_PPnBM || ratePpnbmPo);

                itemsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td class="font-monospace">${escapeHtml(it.kode_barang || '-')}</td>
                        <td>
                            <strong class="text-dark">${escapeHtml(it.nama_barang || '')}</strong>
                            ${it.nama_kategori && it.nama_kategori !== 'Umum' ? `<span class="text-muted small" style="font-size: 10px;">(${escapeHtml(it.nama_kategori)})</span>` : ''}
                            ${isPpnbmItem ? `<span class="badge bg-light text-dark border ms-1" style="font-size: 9px;">PPnBM ${itemRatePpnbm}%</span>` : ''}
                            ${it.keterangan ? `<div class="text-muted small" style="font-size: 10px;">${escapeHtml(it.keterangan)}</div>` : ''}
                        </td>
                        <td class="text-center font-monospace">${qtyPo}</td>
                        <td class="text-center font-monospace fw-bold text-dark">${qtyRcv}</td>
                        <td class="text-center font-monospace">${qtyRet > 0 ? qtyRet : '-'}</td>
                        <td class="text-center">${escapeHtml(it.satuan || it.satuan_master || 'Unit')}</td>
                        <td class="text-end font-monospace">${formatNumber(harga)}</td>
                        <td class="text-end font-monospace">${disc > 0 ? formatNumber(disc) : '-'}</td>
                        <td class="text-end font-monospace fw-bold text-dark">${formatNumber(sub)}</td>
                    </tr>
                `;
            });
            document.getElementById('docItemsTableBody').innerHTML = itemsHtml;
        }

        // Catatan Faktur
        if (fp.keterangan && fp.keterangan.trim() !== '') {
            document.getElementById('docKeteranganContainer').classList.remove('d-none');
            document.getElementById('docKeterangan').innerHTML = escapeHtml(fp.keterangan).replace(/\n/g, '<br>');
        }

        // Financial Calculations & Inclusive Tax Handling
        const subtotalRcv = parseFloat(fp.subtotal_diterima) || 0;
        const nilaiRetur = parseFloat(fp.nilai_retur) || 0;
        const diskon = parseFloat(fp.diskon) || 0;
        const biayaLain = parseFloat(fp.biaya_lain) || 0;
        const ratePpn = parseFloat(fp.rate_pajak) || 0;
        const ratePpnbm = parseFloat(fp.rate_ppnbm || fp.pajak_PPnBM) || 0;
        const isPpnInclusive = parseInt(fp.total_termasuk_pajak) === 1;
        const isPpnbmInclusive = parseInt(fp.total_termasuk_PPnBM) === 1;

        const dasarSetelahDiskon = Math.max(0, subtotalRcv - nilaiRetur - diskon);

        let divisor = 1.0;
        if (isPpnbmInclusive && ratePpnbm > 0) divisor += (ratePpnbm / 100);
        if (isPpnInclusive && ratePpn > 0) divisor += (ratePpn / 100);

        let dpp = parseFloat(fp.dpp) || Math.round(dasarSetelahDiskon / divisor);
        let nominalPpnbm = parseFloat(fp.nominal_ppnbm) || ((ratePpnbm > 0) ? Math.round(dpp * (ratePpnbm / 100)) : 0);
        let nominalPpn = parseFloat(fp.nominal_pajak) || ((ratePpn > 0) ? Math.round(dpp * (ratePpn / 100)) : 0);
        let totalTagihan = parseFloat(fp.total_tagihan) || (dpp + (ratePpnbm > 0 ? nominalPpnbm : 0) + (ratePpn > 0 ? nominalPpn : 0) + biayaLain);

        document.getElementById('docSubtotalRcv').textContent = formatRupiah(subtotalRcv);

        if (nilaiRetur > 0) {
            document.getElementById('docRowRetur').classList.remove('d-none');
            document.getElementById('docNilaiRetur').textContent = `- ${formatRupiah(nilaiRetur)}`;
        }

        if (diskon > 0) {
            document.getElementById('docRowDiskon').classList.remove('d-none');
            document.getElementById('docDiskon').textContent = `- ${formatRupiah(diskon)}`;
        }

        document.getElementById('docDpp').textContent = formatRupiah(dpp);

        // PPnBM
        const rowPpnbm = document.getElementById('docRowPpnbm');
        if (ratePpnbm > 0 || nominalPpnbm > 0) {
            if (rowPpnbm) rowPpnbm.classList.remove('d-none');
            document.getElementById('docLabelPpnbm').textContent = `PPnBM (${ratePpnbm}%)${isPpnbmInclusive ? ' (Inklusif)' : ''}:`;
            document.getElementById('docNominalPpnbm').textContent = formatRupiah(nominalPpnbm);
        } else {
            if (rowPpnbm) rowPpnbm.classList.add('d-none');
        }

        // PPN
        document.getElementById('docLabelPpn').textContent = `PPN (${ratePpn}%)${isPpnInclusive ? ' (Inklusif)' : ''}:`;
        document.getElementById('docNominalPpn').textContent = formatRupiah(nominalPpn);

        if (biayaLain > 0) {
            document.getElementById('docRowBiayaLain').classList.remove('d-none');
            document.getElementById('docBiayaLain').textContent = formatRupiah(biayaLain);
        }

        document.getElementById('docTotalTagihan').textContent = formatRupiah(totalTagihan);
        document.getElementById('docTerbilangTotal').textContent = `# ${terbilang(totalTagihan).trim()} Rupiah #`;

        // Signatures
        const rolePembuat = fp.nama_jabatan ? `(${fp.nama_jabatan}${fp.nama_divisi ? ' - ' + fp.nama_divisi : ''})` : '(Staff Purchasing)';
        document.getElementById('docSigRolePembuat').textContent = rolePembuat;
        document.getElementById('docSigPembuat').textContent = fp.nama_pembuat || 'Staff';
        document.getElementById('docSigVendor').textContent = fp.nama_vendor || 'Pihak Rekanan Vendor';

    } catch (e) {
        console.error('Error fetching Faktur PO print data:', e);
        document.getElementById('docItemsTableBody').innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-4 text-danger fw-bold">
                    Terjadi kesalahan saat memproses data dokumen.
                </td>
            </tr>
        `;
    }
});
</script>
</body>
</html>
