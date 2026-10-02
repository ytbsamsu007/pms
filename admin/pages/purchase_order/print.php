<?php
/**
 * Halaman Cetak Surat Pesanan Barang / Purchase Order Report
 * Path: admin/pages/purchase_order/print.php
 * Format: Terintegrasi API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_LOGISTIK, ROLE_MANAGER]);
$idPo = isset($_GET['id']) ? decodeId($_GET['id']) : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idPo <= 0) {
    die("ID Purchase Order tidak valid.");
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
    <title>Surat Pesanan Barang (PO)</title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Surat Pesanan Barang (PO)
            </span>
            <span class="badge bg-secondary font-monospace" id="toolbarNomorPo">...</span>
            <span class="badge bg-primary" id="toolbarStatusPo">...</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= encodeId($idPo) ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= encodeId($idPo) ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/purchase_order/index.php" class="btn btn-outline-light btn-sm px-3">
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
                SOLUSI<br>LOGISTIK<br>UNTUK<br>INDUSTRI
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
            <div class="doc-title-main">SURAT PESANAN BARANG</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">PURCHASE &nbsp; ORDER &nbsp; REPORT</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Dokumen</div>
            <div class="box-row-val font-monospace" id="docNomorPo">-</div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal PO</div>
            <div class="box-row-val" id="docTanggalPo">-</div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM -->
    <div class="info-grid">
        <!-- Kolom Kiri: Vendor & Pembayaran -->
        <div class="info-col-left">
            <div class="small text-muted mb-1" style="font-size: 11px;">Kepada Yth,</div>
            <div class="fw-bold text-dark mb-1" style="font-size: 13px;" id="docNamaVendor">-</div>
            <div class="text-dark mb-2" style="font-size: 11.5px; line-height: 1.35;" id="docAlamatVendor">-</div>
            
            <table class="table-meta-details mt-2">
                <tr>
                    <td class="lbl">Term of Payment (T.O.P)</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docTop">-</td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan: Pengiriman & Site -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Site Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docSiteTujuan">-</td>
                </tr>
                <tr>
                    <td class="lbl">Metode Pengiriman</td>
                    <td class="colon">:</td>
                    <td class="val" id="docPengiriman">-</td>
                </tr>
                <tr>
                    <td class="lbl">Estimasi Tiba</td>
                    <td class="colon">:</td>
                    <td class="val" id="docTanggalKirim">-</td>
                </tr>
                <tr>
                    <td class="lbl">Alamat Pengiriman</td>
                    <td class="colon">:</td>
                    <td class="val" id="docAlamatKirim">-</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL DAFTAR BARANG / MATERIAL -->
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 35px;">NO.</th>
                <th style="width: 75px;">KODE</th>
                <th>BARANG &amp; SPESIFIKASI</th>
                <th style="width: 50px;">QTY</th>
                <th style="width: 65px;">SATUAN</th>
                <th style="width: 110px;">HARGA SATUAN</th>
                <th style="width: 95px;">DISKON ITEM</th>
                <th style="width: 120px;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody id="docItemsTableBody">
            <tr>
                <td colspan="8" class="text-center py-3 text-muted">Memuat data Purchase Order...</td>
            </tr>
        </tbody>
    </table>

    <!-- GRID CATATAN & RINGKASAN BIAYA -->
    <div class="summary-notes-grid">
        <!-- Kolom Kiri: Catatan Resmi PO -->
        <div class="notes-column">
            <div class="notes-card">
                <div class="notes-title">Catatan:</div>
                <ol class="notes-list">
                    <li>Mohon konfirmasi penerimaan pesanan ini melalui telepon atau email.</li>
                    <li>Barang harus sesuai dengan spesifikasi yang tercantum dalam PO.</li>
                    <li>Sertakan surat jalan dan dokumen pendukung lainnya.</li>
                </ol>
                <div id="docCatatanKhususContainer" class="d-none mt-2 pt-2 border-top" style="font-size: 10px; color: #222;">
                    <strong>Catatan Khusus:</strong> <span id="docCatatanKhusus"></span>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Total Finansial -->
        <div class="summary-column">
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td class="lbl">Subtotal Barang</td>
                        <td class="val" id="docSubtotalBarang">Rp 0</td>
                    </tr>
                    <tr>
                        <td class="lbl">Diskon Akhir</td>
                        <td class="val" style="color: #dc3545;" id="docDiskonAkhir">- Rp 0</td>
                    </tr>
                    <tr>
                        <td class="lbl">DPP (Dasar Pengenaan Pajak)</td>
                        <td class="val" id="docDpp">Rp 0</td>
                    </tr>
                    <tr id="docRowPpnbm" class="d-none">
                        <td class="lbl" id="docLabelPpnbm">PPnBM (0%):</td>
                        <td class="val" id="docNominalPpnbm">Rp 0</td>
                    </tr>
                    <tr id="docRowPpn">
                        <td class="lbl" id="docLabelPpn">PPN (11%):</td>
                        <td class="val" id="docNominalPpn">Rp 0</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td class="lbl">GRAND TOTAL</td>
                        <td class="val" id="docGrandTotal">Rp 0</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Dibuat Oleh (Purchasing) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh</div>
                <div class="sig-header-sub" id="docSigRolePembuat">(Staff Purchasing)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigPembuat">-</span> &nbsp; )
                </div>
            </div>

            <!-- 2. Disetujui Oleh (Approval) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh</div>
                <div class="sig-header-sub" id="docSigRoleApprover">(Manager / Direksi)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigApprover">-</span> &nbsp; )
                </div>
            </div>

            <!-- 3. Dikonfirmasi Oleh (Vendor) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dikonfirmasi Oleh</div>
                <div class="sig-header-sub">(Pihak Vendor)</div>
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

<!-- SCRIPT LOGIC MEMUAT DATA DARI API PO -->
<script>
const ID_PO = <?= $idPo ?>;
const BASE_URL = '<?= BASE_URL ?>';

function formatTanggalIndo(tanggalStr) {
    if (!tanggalStr || tanggalStr === '0000-00-00') return '-';
    const bulan = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const parts = tanggalStr.split(' ')[0].split('-');
    if (parts.length === 3) {
        const d = parseInt(parts[2], 10);
        const m = parseInt(parts[1], 10);
        const y = parts[0];
        return `${d} ${bulan[m] || ''} ${y}`;
    }
    return tanggalStr;
}

function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`${BASE_URL}/api/purchase_order/index.php?id=${ID_PO}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (!result || !result.success || !result.data) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger fw-bold">
                        ${result ? result.message : 'Gagal memuat dokumen Purchase Order dari API.'}
                    </td>
                </tr>
            `;
            return;
        }

        const po = result.data;

        // Toolbar
        document.getElementById('toolbarNomorPo').textContent = po.nomor_po || '-';
        document.getElementById('toolbarStatusPo').textContent = po.status || '-';
        document.title = `Surat Pesanan Barang - ${po.nomor_po || 'PO'}`;

        // Header Metadata
        document.getElementById('docNomorPo').textContent = po.nomor_po || '-';
        document.getElementById('docTanggalPo').textContent = formatTanggalIndo(po.tanggal_po);

        // Vendor & Payment
        document.getElementById('docNamaVendor').textContent = po.nama_vendor || '-';
        document.getElementById('docAlamatVendor').innerHTML = escapeHtml(po.alamat_vendor || '-').replace(/\n/g, '<br>');
        
        const topNum = parseInt(po.term_of_payment) || 0;
        document.getElementById('docTop').textContent = (topNum === 0) ? 'C.O.D (Cash On Delivery)' : `Tempo ${topNum} Hari`;

        // Shipping Info
        document.getElementById('docSiteTujuan').textContent = po.nama_site || '-';
        document.getElementById('docPengiriman').textContent = po.pengiriman || 'Vendor';
        document.getElementById('docTanggalKirim').textContent = po.tanggal_pengiriman ? formatTanggalIndo(po.tanggal_pengiriman) : 'Sesuai Jadwal';
        document.getElementById('docAlamatKirim').innerHTML = escapeHtml(po.alamat || po.alamat_site || '-').replace(/\n/g, '<br>');

        // Items Table
        const items = po.items || [];
        if (items.length === 0) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-3 text-muted">Tidak ada rincian barang dalam Purchase Order ini.</td>
                </tr>
            `;
        } else {
            let itemsHtml = '';
            items.forEach((it, idx) => {
                const qty = parseFloat(it.qty) || 0;
                const harga = parseFloat(it.harga) || 0;
                const diskon = parseFloat(it.diskon) || 0;
                const subtotal = parseFloat(it.subtotal) || 0;

                itemsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td class="text-center font-monospace" style="font-size: 10px;">${escapeHtml(it.kode_barang || '-')}</td>
                        <td>
                            <div class="fw-bold">${escapeHtml(it.nama_barang || '')}</div>
                        </td>
                        <td class="text-center font-monospace fw-bold">${qty}</td>
                        <td class="text-center" style="font-size: 10px;">${escapeHtml(it.satuan || 'PCS').toUpperCase()}</td>
                        <td class="text-end font-monospace">${formatRupiah(harga)}</td>
                        <td class="text-end font-monospace">${diskon > 0 ? ('- ' + formatRupiah(diskon)) : 'Rp 0'}</td>
                        <td class="text-end font-monospace fw-bold">${formatRupiah(subtotal)}</td>
                    </tr>
                `;
            });
            document.getElementById('docItemsTableBody').innerHTML = itemsHtml;
        }

        // Catatan Khusus
        if (po.keterangan && po.keterangan.trim() !== '') {
            document.getElementById('docCatatanKhususContainer').classList.remove('d-none');
            document.getElementById('docCatatanKhusus').innerHTML = escapeHtml(po.keterangan).replace(/\n/g, '<br>');
        }

        // Financial Calculation (Konsisten dari API)
        const subtotalBarang = parseFloat(po.subtotal_barang) || 0;
        const diskonPo = parseFloat(po.nominal_diskon || po.diskon) || 0;
        const ratePpn = parseFloat(po.rate_pajak || po.pajak) || 0;
        const isPpnInclusive = parseInt(po.total_termasuk_pajak) === 1;

        const ratePpnbm = parseFloat(po.rate_ppnbm || po.pajak_PPnBM) || 0;
        const isPpnbmInclusive = parseInt(po.total_termasuk_PPnBM) === 1;

        const dasarSetelahDiskon = Math.max(0, subtotalBarang - diskonPo);

        let divisor = 1.0;
        if (isPpnbmInclusive && ratePpnbm > 0) divisor += (ratePpnbm / 100);
        if (isPpnInclusive && ratePpn > 0) divisor += (ratePpn / 100);

        const dpp = dasarSetelahDiskon / divisor;
        const nominalPpnbm = ratePpnbm > 0 ? (dpp * (ratePpnbm / 100)) : 0;
        const nominalPpn = ratePpn > 0 ? (dpp * (ratePpn / 100)) : 0;

        let grandTotal = 0;
        if (divisor > 1.0) {
            grandTotal = dpp + (ratePpnbm > 0 ? nominalPpnbm : 0) + (ratePpn > 0 ? nominalPpn : 0);
        } else {
            grandTotal = dpp + nominalPpnbm + nominalPpn;
        }

        document.getElementById('docSubtotalBarang').textContent = formatRupiah(subtotalBarang);
        document.getElementById('docDiskonAkhir').textContent = diskonPo > 0 ? (`- ${formatRupiah(diskonPo)}`) : 'Rp 0';
        document.getElementById('docDpp').textContent = formatRupiah(dpp);

        // PPnBM
        const docRowPpnbm = document.getElementById('docRowPpnbm');
        if (ratePpnbm > 0) {
            if (docRowPpnbm) docRowPpnbm.classList.remove('d-none');
            document.getElementById('docLabelPpnbm').textContent = `PPnBM (${ratePpnbm}%)${isPpnbmInclusive ? ' (Inklusif)' : ''}:`;
            document.getElementById('docNominalPpnbm').textContent = formatRupiah(nominalPpnbm);
        } else {
            if (docRowPpnbm) docRowPpnbm.classList.add('d-none');
        }

        // PPN
        document.getElementById('docLabelPpn').textContent = `PPN (${ratePpn}%)${isPpnInclusive ? ' (Inklusif)' : ''}:`;
        document.getElementById('docNominalPpn').textContent = formatRupiah(nominalPpn);
        document.getElementById('docGrandTotal').textContent = formatRupiah(grandTotal);

        // Signatures (Nama, Jabatan & Divisi Dinamis)
        const jabatanPembuat = po.jabatan_pembuat || '';
        const divisiPembuat = po.divisi_pembuat || '';
        let rolePembuatText = '';
        if (jabatanPembuat && divisiPembuat) {
            rolePembuatText = `(${jabatanPembuat} - ${divisiPembuat})`;
        } else if (jabatanPembuat) {
            rolePembuatText = `(${jabatanPembuat})`;
        } else {
            rolePembuatText = `(Staff Purchasing)`;
        }
        document.getElementById('docSigRolePembuat').textContent = rolePembuatText;

        const jabatanApprover = po.jabatan_approver || '';
        const divisiApprover = po.divisi_approver || '';
        let roleApproverText = '';
        if (jabatanApprover && divisiApprover) {
            roleApproverText = `(${jabatanApprover} - ${divisiApprover})`;
        } else if (jabatanApprover) {
            roleApproverText = `(${jabatanApprover})`;
        } else {
            roleApproverText = `(Pimpinan / Direksi)`;
        }
        document.getElementById('docSigRoleApprover').textContent = roleApproverText;

        document.getElementById('docSigPembuat').textContent = po.nama_pembuat || 'Staff Purchasing';
        document.getElementById('docSigApprover').textContent = po.nama_approver || 'Pimpinan Perusahaan';
        document.getElementById('docSigVendor').textContent = po.nama_vendor || 'Pihak Rekanan Vendor';

    } catch (e) {
        console.error('Error fetching PO print data:', e);
        document.getElementById('docItemsTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-danger fw-bold">
                    Terjadi kesalahan saat memproses data dokumen.
                </td>
            </tr>
        `;
    }
});
</script>
</body>
</html>
