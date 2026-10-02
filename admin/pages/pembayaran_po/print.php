<?php
/**
 * Halaman Cetak Bukti Pembayaran Faktur PO (Payment Voucher)
 * Path: admin/pages/pembayaran_po/print.php
 * Format: Siap Print B/W (Hitam Putih Resmi) Terintegrasi API & External CSS
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);
$idDetail = isset($_GET['id']) ? decodeId($_GET['id']) : (isset($_GET['id_detail']) ? decodeId($_GET['id_detail']) : 0);
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idDetail <= 0) {
    die("ID Pembayaran tidak valid.");
}

// Ambil Data Profil Perusahaan
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
    <title>Bukti Pembayaran Faktur PO</title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Bukti Pembayaran (Voucher Kas)
            </span>
            <span class="badge bg-secondary font-monospace" id="toolbarKodeBayar">...</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= encodeId($idDetail) ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= encodeId($idDetail) ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
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
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/../../../' . $companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyLogo) ?>" alt="Logo" style="width: 54px; height: 54px; object-fit: contain; flex-shrink: 0;">
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
                VOUCHER<br>PENGELUARAN<br>KAS / BANK
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
            <div class="doc-title-main">BUKTI PENGELUARAN KAS / BANK</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">PAYMENT &nbsp; VOUCHER &nbsp; (FAKTUR &nbsp; PO)</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Transaksi</div>
            <div class="box-row-val font-monospace" id="docKodeBayar">-</div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal Bayar</div>
            <div class="box-row-val font-monospace" id="docTanggalBayar">-</div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM (BERSIH & FORMAL B/W) -->
    <div class="info-grid">
        <!-- Kolom Kiri: Referensi Dokumen -->
        <div class="info-col-left">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">No. Faktur Sistem</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold" id="docNomorFaktur">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Invoice Vendor</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docInvoiceVendor">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Purchase Order</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorPo">-</td>
                </tr>
                <tr>
                    <td class="lbl">Skema Pembayaran</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docJenisBayar">-</td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan: Pihak Vendor & Rekening -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Dibayarkan Kepada</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold" id="docNamaVendor">-</td>
                </tr>
                <tr>
                    <td class="lbl">Rekening Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val" id="docRekeningTujuan">-</td>
                </tr>
                <tr>
                    <td class="lbl">Site Operasional</td>
                    <td class="colon">:</td>
                    <td class="val" id="docSite">-</td>
                </tr>
                <tr>
                    <td class="lbl">Status Faktur</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold" id="docStatusBayar">-</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- KOTAK JUMLAH PEMBAYARAN SAAT INI (PENEKANAN UTAMA) -->
    <div class="amount-box-bw my-3 p-3 border rounded-2" style="border: 1.5px solid #000 !important; background: #fafafa;">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <div class="text-uppercase fw-bold text-dark" style="font-size: 10.5px; letter-spacing: 0.5px;">
                    Jumlah Uang Yang Ditransfer (Kas Keluar) :
                </div>
                <div class="fs-4 fw-bold text-dark font-monospace mt-1" id="docNominalTransfer">Rp 0</div>
                <div id="docRowDiskonPayment" class="d-none mt-1" style="font-size: 11px;">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">Potongan Diskon: <span id="docNominalDiskon">Rp 0</span></span>
                    <span class="text-muted ms-1" id="docKetDiskon"></span>
                </div>
            </div>
            <div class="text-end" style="font-size: 11.5px;">
                <div>Kas / Bank Pengirim: <strong class="font-monospace text-dark" id="docBankAsal">-</strong> (No. Rek: <span class="font-monospace text-dark" id="docNorekAsal">-</span>)</div>
                <div>No. Referensi / Ref: <strong class="font-monospace text-dark" id="docNoRef">-</strong> | Biaya Admin: <span class="font-monospace text-dark" id="docBiayaAdmin">Rp 0</span></div>
            </div>
        </div>
        <div class="mt-2 pt-2 border-top border-dark small text-dark" style="border-top: 1px dashed #333 !important;">
            <strong>Terbilang (Nominal Transfer):</strong> <em id="docTerbilang"># Nol Rupiah #</em>
        </div>
    </div>

    <!-- TABEL MUTASI & RIWAYAT PEMBAYARAN FAKTUR -->
    <div class="table-title fw-bold text-dark mb-1" style="font-size: 11px; text-transform: uppercase;">
        Rincian Mutasi &amp; Riwayat Pembayaran Tagihan Faktur :
    </div>
    <table class="table-items-main mb-3">
        <thead>
            <tr>
                <th style="width: 30px;">NO.</th>
                <th style="width: 110px;">KODE BAYAR</th>
                <th style="width: 90px;">TANGGAL</th>
                <th style="width: 100px;">BANK PENGIRIM</th>
                <th style="width: 90px;">NO. REF</th>
                <th style="width: 110px;" class="text-end">TRANSFER</th>
                <th style="width: 95px;" class="text-end">DISKON</th>
                <th style="width: 110px;" class="text-end">SISA HUTANG</th>
            </tr>
        </thead>
        <tbody id="docHistoryTableBody">
            <tr>
                <td colspan="8" class="text-center py-3 text-muted">Memuat data rincian pembayaran...</td>
            </tr>
        </tbody>
        <tfoot id="docHistoryTableFoot">
            <!-- Populated dynamically by JS -->
        </tfoot>
    </table>

    <!-- CATATAN TRANSAKSI -->
    <div id="docCatatanContainer" class="d-none mb-3">
        <div class="catatan-penerimaan-section mb-0">
            <div class="notes-title">Catatan / Keterangan Pembayaran:</div>
            <div class="catatan-box" id="docCatatanTransaksi"></div>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM RESMI B/W) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Dibuat Oleh (Finance) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh</div>
                <div class="sig-header-sub" id="docSigRolePembuat">(Staff Finance)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigPembuat">-</span> &nbsp; )
                </div>
            </div>

            <!-- 2. Disetujui Oleh (Approval) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh</div>
                <div class="sig-header-sub" id="docSigRoleApprover">(Manager Finance / Direksi)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigApprover">-</span> &nbsp; )
                </div>
            </div>

            <!-- 3. Diterima Oleh (Vendor) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diterima Oleh</div>
                <div class="sig-header-sub">(Pihak Rekanan Vendor)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigVendor">-</span> &nbsp; )
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

<!-- SCRIPT LOGIC MEMUAT DATA DARI API PEMBAYARAN PO -->
<script>
const ID_DETAIL = <?= $idDetail ?>;
const BASE_URL = '<?= BASE_URL ?>';

function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID');
}

function formatNumber(num) {
    return (parseFloat(num) || 0).toLocaleString('id-ID');
}

function formatNoRef(ref) {
    if (!ref || ref === '0' || String(ref).trim() === '' || String(ref).trim() === '0') {
        return '-';
    }
    return String(ref).trim();
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function formatTanggalIndo(tanggalStr) {
    if (!tanggalStr || tanggalStr === '0000-00-00') return '-';
    const parts = tanggalStr.split(' ')[0].split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return tanggalStr;
}

function terbilangIndo(angka) {
    angka = Math.abs(parseFloat(angka) || 0);
    const satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    
    if (angka < 12) return " " + satuan[Math.floor(angka)];
    if (angka < 20) return terbilangIndo(angka - 10) + " Belas";
    if (angka < 100) return terbilangIndo(Math.floor(angka / 10)) + " Puluh" + terbilangIndo(angka % 10);
    if (angka < 200) return " Seratus" + terbilangIndo(angka - 100);
    if (angka < 1000) return terbilangIndo(Math.floor(angka / 100)) + " Ratus" + terbilangIndo(angka % 100);
    if (angka < 2000) return " Seribu" + terbilangIndo(angka - 1000);
    if (angka < 1000000) return terbilangIndo(Math.floor(angka / 1000)) + " Ribu" + terbilangIndo(angka % 1000);
    if (angka < 1000000000) return terbilangIndo(Math.floor(angka / 1000000)) + " Juta" + terbilangIndo(angka % 1000000);
    if (angka < 1000000000000) return terbilangIndo(Math.floor(angka / 1000000000)) + " Miliar" + terbilangIndo(angka % 1000000000);
    if (angka < 1000000000000000) return terbilangIndo(Math.floor(angka / 1000000000000)) + " Triliun" + terbilangIndo(angka % 1000000000000);
    return "";
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`${BASE_URL}/api/pembayaran_po/index.php?id=${ID_DETAIL}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (!result || !result.success || !result.data) {
            alert(result ? result.message : 'Gagal memuat data pembayaran dari API.');
            return;
        }

        const pay = result.data;

        // Toolbar
        document.getElementById('toolbarKodeBayar').textContent = pay.kode_pembayaran || '-';
        document.title = `Bukti Pembayaran - ${pay.kode_pembayaran || 'VOUCHER'}`;

        // Header Metadata
        document.getElementById('docKodeBayar').textContent = pay.kode_pembayaran || '-';
        document.getElementById('docTanggalBayar').textContent = pay.tanggal_bayar ? pay.tanggal_bayar.replace('T', ' ').substring(0, 16) : '-';

        // Details
        document.getElementById('docNomorFaktur').textContent = pay.nomor_faktur || '-';
        document.getElementById('docInvoiceVendor').textContent = pay.nomor_faktur_vendor || '-';
        document.getElementById('docNomorPo').textContent = pay.nomor_po || '-';
        document.getElementById('docJenisBayar').textContent = (parseInt(pay.jenis_pembayaran) === 1) 
            ? '1x Bayar (Lunas)' 
            : 'Kredit / Termin (Sebagian)';

        document.getElementById('docNamaVendor').textContent = pay.nama_vendor || '-';
        
        const destBank = pay.bank_tujuan || pay.bank_vendor || '-';
        const destNorek = pay.norek_tujuan || pay.norek_vendor || '-';
        const destAn = pay.an_pengiriman || pay.an_vendor || pay.nama_vendor || '-';
        document.getElementById('docRekeningTujuan').innerHTML = `${escapeHtml(destBank)} &bull; <strong class="font-monospace">${escapeHtml(destNorek)}</strong><br><span class="text-muted" style="font-size: 10.5px;">a.n. ${escapeHtml(destAn)}</span>`;

        document.getElementById('docSite').textContent = pay.nama_site || '-';

        // Financials & Status
        const nominal = parseFloat(pay.nominal_pengiriman) || 0;
        const diskon = parseFloat(pay.nominal_diskon) || 0;
        const ketDiskon = pay.keterangan_diskon || '';
        const biayaAdmin = parseFloat(pay.biaya_admin) || 0;
        const totalTagihan = parseFloat(pay.total_tagihan) || 0;
        const sisaPiutang = parseFloat(pay.sisa_piutang) || 0;
        const totalTerbayarFaktur = parseFloat(pay.total_terbayar_faktur) || 0;
        const isLunas = sisaPiutang <= 0 || parseInt(pay.status_pembayaran) === 1 || pay.status_faktur === 'LUNAS';

        document.getElementById('docStatusBayar').textContent = isLunas ? 'LUNAS' : 'SEBAGIAN DIBAYAR (BELUM LUNAS)';

        // Box Penekanan Pembayaran Saat Ini
        document.getElementById('docNominalTransfer').textContent = formatRupiah(nominal);
        
        if (diskon > 0) {
            document.getElementById('docRowDiskonPayment').classList.remove('d-none');
            document.getElementById('docNominalDiskon').textContent = formatRupiah(diskon);
            document.getElementById('docKetDiskon').textContent = ketDiskon ? `(${escapeHtml(ketDiskon)})` : '';
        } else {
            document.getElementById('docRowDiskonPayment').classList.add('d-none');
        }

        document.getElementById('docBankAsal').textContent = pay.bank_pengirim || '-';
        document.getElementById('docNorekAsal').textContent = pay.norek_pengirim || '-';
        document.getElementById('docNoRef').textContent = formatNoRef(pay.no_ref);
        document.getElementById('docBiayaAdmin').textContent = formatRupiah(biayaAdmin);
        
        const terbilangStr = terbilangIndo(nominal).trim() + " Rupiah";
        document.getElementById('docTerbilang').textContent = `# ${terbilangStr} #`;

        // TABEL MUTASI & RIWAYAT PEMBAYARAN FAKTUR
        const historyList = pay.history_pembayaran || [];
        const tbody = document.getElementById('docHistoryTableBody');
        const tfoot = document.getElementById('docHistoryTableFoot');

        let rowsHtml = '';
        let cumulativePaid = 0;
        let cumulativeDiskon = 0;

        if (historyList.length === 0) {
            // Jika tidak ada list, render transaksi saat ini
            rowsHtml = `
                <tr>
                    <td class="text-center font-monospace">1</td>
                    <td class="font-monospace">${escapeHtml(pay.kode_pembayaran)}</td>
                    <td class="text-center font-monospace">${formatTanggalIndo(pay.tanggal_bayar)}</td>
                    <td class="font-monospace">${escapeHtml(pay.bank_pengirim || '-')}</td>
                    <td class="font-monospace">${escapeHtml(formatNoRef(pay.no_ref))}</td>
                    <td class="text-end font-monospace">${formatNumber(nominal)}</td>
                    <td class="text-end font-monospace ${diskon > 0 ? 'text-danger' : ''}">${diskon > 0 ? formatNumber(diskon) : '-'}</td>
                    <td class="text-end font-monospace">${formatNumber(sisaPiutang)}</td>
                </tr>
            `;
            cumulativePaid = nominal;
            cumulativeDiskon = diskon;
        } else {
            historyList.forEach((h, idx) => {
                const isCurrent = (parseInt(h.id_pembayaran_detail) === ID_DETAIL);
                const hNominal = parseFloat(h.nominal_pengiriman) || 0;
                const hDiskon = parseFloat(h.nominal_diskon) || 0;
                const hSisa = parseFloat(h.sisa_piutang) || 0;
                cumulativePaid += hNominal;
                cumulativeDiskon += hDiskon;

                rowsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td class="font-monospace">${escapeHtml(h.kode_pembayaran)}</td>
                        <td class="text-center font-monospace">${formatTanggalIndo(h.tanggal_bayar)}</td>
                        <td class="font-monospace">${escapeHtml(h.bank_pengirim || '-')}</td>
                        <td class="font-monospace">${escapeHtml(formatNoRef(h.no_ref))}</td>
                        <td class="text-end font-monospace">${formatNumber(hNominal)}</td>
                        <td class="text-end font-monospace ${hDiskon > 0 ? 'text-danger' : ''}">${hDiskon > 0 ? formatNumber(hDiskon) : '-'}</td>
                        <td class="text-end font-monospace">${formatNumber(hSisa)}</td>
                    </tr>
                `;
            });
        }

        tbody.innerHTML = rowsHtml;

        // Footer Tabel Rincian Ringkasan (Background Putih kecuali Sisa Tagihan)
        tfoot.innerHTML = `
            <tr style="border-top: 2px solid #000; font-weight: bold; background: #ffffff;">
                <td colspan="5" class="text-end font-monospace">TOTAL NILAI TAGIHAN FAKTUR :</td>
                <td colspan="3" class="text-end font-monospace">${formatNumber(totalTagihan)}</td>
            </tr>
            <tr style="font-weight: bold; background: #ffffff;">
                <td colspan="5" class="text-end font-monospace">TOTAL TERBAYAR S.D. SAAT INI :</td>
                <td colspan="3" class="text-end font-monospace">${formatNumber(totalTerbayarFaktur || (cumulativePaid + cumulativeDiskon))}</td>
            </tr>
            <tr style="border-top: 1.5px solid #000; font-weight: bold; background: #d9d9d9;">
                <td colspan="5" class="text-end font-monospace">SISA TAGIHAN / HUTANG FAKTUR :</td>
                <td colspan="3" class="text-end font-monospace fs-6">${formatNumber(sisaPiutang)}</td>
            </tr>
        `;

        // Catatan Transaksi
        const ketClean = (pay.keterangan && pay.keterangan !== '0' && String(pay.keterangan).trim() !== '0' && String(pay.keterangan).trim() !== '') ? String(pay.keterangan).trim() : '';
        if (ketClean !== '') {
            document.getElementById('docCatatanContainer').classList.remove('d-none');
            document.getElementById('docCatatanTransaksi').innerHTML = escapeHtml(ketClean).replace(/\n/g, '<br>');
        } else {
            document.getElementById('docCatatanContainer').classList.add('d-none');
        }

        // Signatures (Nama, Jabatan & Divisi Dinamis)
        const jabatanPembuat = pay.jabatan_pembuat || '';
        const divisiPembuat = pay.divisi_pembuat || '';
        let rolePembuatText = '';
        if (jabatanPembuat && divisiPembuat) {
            rolePembuatText = `(${jabatanPembuat} - ${divisiPembuat})`;
        } else if (jabatanPembuat) {
            rolePembuatText = `(${jabatanPembuat})`;
        } else {
            rolePembuatText = `(Staff Finance)`;
        }
        document.getElementById('docSigRolePembuat').textContent = rolePembuatText;

        const jabatanApprover = pay.jabatan_approver || '';
        const divisiApprover = pay.divisi_approver || '';
        let roleApproverText = '';
        if (jabatanApprover && divisiApprover) {
            roleApproverText = `(${jabatanApprover} - ${divisiApprover})`;
        } else if (jabatanApprover) {
            roleApproverText = `(${jabatanApprover})`;
        } else {
            roleApproverText = `(Manager Finance / Direksi)`;
        }
        document.getElementById('docSigRoleApprover').textContent = roleApproverText;

        document.getElementById('docSigPembuat').textContent = pay.nama_pembuat || 'Staff Finance';
        document.getElementById('docSigApprover').textContent = pay.nama_approver || 'Manager Finance';
        document.getElementById('docSigVendor').textContent = pay.nama_vendor || 'Pihak Rekanan Vendor';

    } catch (e) {
        console.error('Error fetching payment print data:', e);
    }
});
</script>
</body>
</html>
