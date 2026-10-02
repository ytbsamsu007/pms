<?php
/**
 * Halaman Cetak Surat Jalan Pengembalian Barang (SJ Retur ke Vendor)
 * Path: admin/pages/retur_po/print_sj.php
 * Format: Terintegrasi API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$idRetur = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idRetur <= 0) {
    die("ID Dokumen Retur PO tidak valid.");
}

// Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'logistik@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan Retur</title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Surat Jalan Retur Barang
            </span>
            <span class="badge bg-secondary font-monospace" id="toolbarNomorSj">...</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= $idRetur ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= $idRetur ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/retur_po/detail.php?id=<?= $idRetur ?>" class="btn btn-outline-light btn-sm px-3">
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
            <div class="doc-title-main">SURAT JALAN PENGEMBALIAN BARANG</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">DELIVERY &nbsp; ORDER &nbsp; (RETUR)</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Surat Jalan</div>
            <div class="box-row-val font-monospace" id="docNomorSj">-</div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal Kirim</div>
            <div class="box-row-val" id="docTanggalKirim">-</div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM -->
    <div class="info-grid">
        <!-- Kolom Kiri: Tujuan Pengembalian -->
        <div class="info-col-left">
            <div class="small text-muted mb-1" style="font-size: 11px;">Tujuan Pengembalian (Vendor):</div>
            <div class="fw-bold text-dark mb-1" style="font-size: 13px;" id="docNamaVendor">-</div>
            <div class="text-dark mb-2" style="font-size: 11.5px; line-height: 1.35;" id="docAlamatVendor">-</div>
            
            <table class="table-meta-details mt-2">
                <tr>
                    <td class="lbl">No. Retur PO</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-semibold text-primary" id="docNomorRetur">-</td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan: Pengiriman & Referensi -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Site Pengirim</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docSite">-</td>
                </tr>
                <tr>
                    <td class="lbl">Ref. Purchase Order</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorPo">-</td>
                </tr>
                <tr>
                    <td class="lbl">Ref. Penerimaan Awal</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorRcv">-</td>
                </tr>
                <tr>
                    <td class="lbl">Petugas Logistik</td>
                    <td class="colon">:</td>
                    <td class="val" id="docPembuat">-</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL DAFTAR BARANG -->
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 40px;">NO.</th>
                <th style="width: 90px;">KODE</th>
                <th>NAMA BARANG / MATERIAL</th>
                <th style="width: 80px;">QTY RETUR</th>
                <th style="width: 80px;">SATUAN</th>
                <th>ALASAN RETUR &amp; KETERANGAN</th>
            </tr>
        </thead>
        <tbody id="docItemsTableBody">
            <tr>
                <td colspan="6" class="text-center py-3 text-muted">Memuat data barang surat jalan...</td>
            </tr>
        </tbody>
    </table>

    <!-- KOTAK CATATAN RETUR -->
    <div id="docCatatanContainer" class="catatan-penerimaan-section d-none mb-3">
        <div class="notes-title">INSTRUKSI / CATATAN KHUSUS :</div>
        <div class="catatan-box" id="docCatatanRetur"></div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Pengirim / Logistik -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Pengirim</div>
                <div class="sig-header-sub" id="docSigRolePembuat">(Petugas Logistik)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigPembuat">-</span> &nbsp; )
                </div>
            </div>

            <!-- 2. Pembawa / Ekspedisi -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Pembawa / Kurir</div>
                <div class="sig-header-sub">(Sopir / Ekspedisi)</div>
                <div class="sig-line-box">
                    ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
                </div>
            </div>

            <!-- 3. Penerima / Vendor -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Penerima</div>
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

<!-- SCRIPT LOGIC MEMUAT DATA DARI API RETUR PO -->
<script>
const ID_RETUR = <?= $idRetur ?>;
const BASE_URL = '<?= BASE_URL ?>';

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

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

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`${BASE_URL}/api/retur_po/index.php?id=${ID_RETUR}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (!result || !result.success || !result.data) {
            alert(result ? result.message : 'Gagal memuat data retur PO dari API.');
            return;
        }

        const retur = result.data;

        // Toolbar & Title
        const nomorSj = retur.nomor_sj_retur || `SJ-RET-${retur.nomor_po_retur}`;
        document.getElementById('toolbarNomorSj').textContent = nomorSj;
        document.title = `Surat Jalan Retur - ${nomorSj}`;

        // Header Metadata
        document.getElementById('docNomorSj').textContent = nomorSj;
        document.getElementById('docTanggalKirim').textContent = formatTanggalIndo(retur.tanggal_kirim_kembali || retur.tanggal_po_retur);

        // Details
        document.getElementById('docNamaVendor').textContent = retur.nama_vendor || '-';
        document.getElementById('docAlamatVendor').innerHTML = escapeHtml(retur.alamat_vendor || '-').replace(/\n/g, '<br>');
        document.getElementById('docNomorRetur').textContent = retur.nomor_po_retur || '-';

        document.getElementById('docSite').textContent = retur.nama_site || '-';
        document.getElementById('docNomorPo').textContent = retur.nomor_po || '-';
        document.getElementById('docNomorRcv').textContent = retur.nomor_rcv || '-';
        document.getElementById('docPembuat').textContent = retur.nama_pembuat || '-';

        // Items Table
        const items = retur.items || [];
        if (items.length === 0) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted">Tidak ada rincian barang retur.</td>
                </tr>
            `;
        } else {
            let itemsHtml = '';
            let totalQtyRetur = 0;

            items.forEach((it, idx) => {
                const qtyRetur = parseFloat(it.qty_retur) || 0;
                totalQtyRetur += qtyRetur;

                itemsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td class="text-center font-monospace" style="font-size: 10px;">${escapeHtml(it.kode_barang || '-')}</td>
                        <td class="fw-bold">${escapeHtml(it.nama_barang || '')}</td>
                        <td class="text-center font-monospace fw-bold">${qtyRetur}</td>
                        <td class="text-center" style="font-size: 10px;">${escapeHtml(it.satuan || it.satuan_master || 'PCS').toUpperCase()}</td>
                        <td>${escapeHtml(it.alasan || it.keterangan || '-')}</td>
                    </tr>
                `;
            });

            // Total row
            itemsHtml += `
                <tr class="total-row">
                    <td colspan="3" class="text-center fw-bold">TOTAL PENGIRIMAN</td>
                    <td class="text-center font-monospace fw-bold">${totalQtyRetur}</td>
                    <td colspan="2"></td>
                </tr>
            `;

            document.getElementById('docItemsTableBody').innerHTML = itemsHtml;
        }

        // Catatan
        if (retur.keterangan && retur.keterangan.trim() !== '') {
            document.getElementById('docCatatanContainer').classList.remove('d-none');
            document.getElementById('docCatatanRetur').innerHTML = escapeHtml(retur.keterangan).replace(/\n/g, '<br>');
        }

        // Signatures
        document.getElementById('docSigPembuat').textContent = retur.nama_pembuat || 'Staff Logistik';
        document.getElementById('docSigVendor').textContent = retur.nama_vendor || 'Pihak Rekanan Vendor';

    } catch (e) {
        console.error('Error fetching retur SJ data:', e);
    }
});
</script>
</body>
</html>
