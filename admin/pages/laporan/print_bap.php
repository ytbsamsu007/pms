<?php
/**
 * Halaman Cetak Dokumen Resmi: Berita Acara Pembatalan (BAP)
 * Path: admin/pages/laporan/print_bap.php
 * Format: Terintegrasi REST API & External CSS (styles/print_document.css) sesuai standar sistem
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_LOGISTIK, ROLE_FINANCE, ROLE_MANAGER]);
$type = strtoupper(trim($_GET['type'] ?? 'PO'));
$idRef = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idRef <= 0) {
    die("Parameter ID dokumen pembatalan tidak valid.");
}

// Ambil Profil Perusahaan (Standar Sistem)
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
    <title>Berita Acara Pembatalan</title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet (Standar Sistem) -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Berita Acara Pembatalan (BAP)
            </span>
            <span class="badge bg-secondary font-monospace" id="toolbarNomorBap">Memuat...</span>
            <span class="badge bg-danger font-monospace">BATAL</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?type=<?= htmlspecialchars($type) ?>&id=<?= $idRef ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?type=<?= htmlspecialchars($type) ?>&id=<?= $idRef ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <button type="button" class="btn btn-outline-light btn-sm px-3" onclick="window.close()">
                <i class="bi bi-x-lg me-1"></i> Tutup
            </button>
            
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
            <div class="doc-title-main">BERITA ACARA PEMBATALAN</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">TRANSACTION &nbsp; VOID &nbsp; REPORT</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Dokumen BAP</div>
            <div class="box-row-val font-monospace text-danger" id="docNomorBap">-</div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal Batal</div>
            <div class="box-row-val" id="docTanggalBatal">-</div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM (INFO GRID) -->
    <div class="info-grid">
        <!-- Kolom Kiri -->
        <div class="info-col-left">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Jenis Dokumen</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold" id="docJenisDokumen">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Referensi</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold text-primary" id="docNomorRef">-</td>
                </tr>
                <tr>
                    <td class="lbl">Vendor Rekanan</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docNamaVendor">-</td>
                </tr>
                <tr>
                    <td class="lbl">Alamat Vendor</td>
                    <td class="colon">:</td>
                    <td class="val" id="docAlamatVendor">-</td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">State Transaksi</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold text-danger" id="docStateBatal">BATAL</td>
                </tr>
                <tr>
                    <td class="lbl">Petugas Pembatalan</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docPetugasBatal">-</td>
                </tr>
                <tr>
                    <td class="lbl">Kategori Alasan</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold text-danger" id="docKategoriAlasan">-</td>
                </tr>
                <tr>
                    <td class="lbl">Waktu Pembatalan</td>
                    <td class="colon">:</td>
                    <td class="val" id="docWaktuBatal">-</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL DAFTAR BARANG / MATERIAL YANG DIBATALKAN -->
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 35px;">NO.</th>
                <th style="width: 85px;">KODE</th>
                <th>BARANG &amp; SPESIFIKASI</th>
                <th style="width: 60px;">QTY</th>
                <th style="width: 70px;">SATUAN</th>
                <th style="width: 120px;">HARGA SATUAN</th>
                <th style="width: 130px;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody id="docItemsTableBody">
            <tr>
                <td colspan="7" class="text-center py-3 text-muted">
                    <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data Berita Acara Pembatalan...
                </td>
            </tr>
        </tbody>
    </table>

    <!-- GRID CATATAN ALASAN & RINGKASAN FINANSIAL -->
    <div class="summary-notes-grid">
        <!-- Kolom Kiri: Uraian Kronologis BAP -->
        <div class="notes-column">
            <div class="notes-card">
                <div class="notes-title">Uraian Kronologis &amp; Alasan Pembatalan:</div>
                <div id="docUraianAlasan" style="font-size: 11px; color: #111; line-height: 1.45; margin-bottom: 6px;">-</div>
                <div class="text-muted" style="font-size: 10px; border-top: 1px dashed #ccc; padding-top: 4px;">
                    Dokumen ini menyatakan secara resmi bahwa transaksi di atas telah dibatalkan (void) dan tidak berlaku lagi untuk proses operasional, penerimaan barang, maupun pembayaran kewajiban selanjutnya.
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Nilai Transaksi -->
        <div class="summary-column">
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td class="lbl">Total Item Terdaftar</td>
                        <td class="val" id="docTotalItem">0 Baris Item</td>
                    </tr>
                    <tr>
                        <td class="lbl">Status Dokumen</td>
                        <td class="val text-danger fw-bold">BATAL (VOID)</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td class="lbl">TOTAL NILAI BATAL</td>
                        <td class="val" style="color: #dc3545;" id="docTotalNilai">Rp 0</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN 3 KOLOM -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Dibuat Oleh -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh</div>
                <div class="sig-header-sub" id="docSigRolePembuat">(Petugas Pembatalan)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigPembuat">-</span> &nbsp; )
                </div>
            </div>

            <!-- 2. Diperiksa Oleh -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diperiksa Oleh</div>
                <div class="sig-header-sub">(Head of Dept / Finance)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name">........................................</span> &nbsp; )
                </div>
            </div>

            <!-- 3. Mengetahui & Menyetujui -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Mengetahui &amp; Menyetujui</div>
                <div class="sig-header-sub">(Branch / General Manager)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name">........................................</span> &nbsp; )
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH DOKUMEN -->
    <div class="footer-line-container">
        <div class="footer-right">
            <div class="fw-bold">Halaman 1 dari 1</div>
            <div>Dicetak: <?= date('d/m/Y H:i') ?> | Oleh: <?= htmlspecialchars($user['nama_karyawan'] ?? ($user['username'] ?? 'Petugas')) ?></div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<!-- SCRIPT LOGIC MEMUAT DATA DARI REST API PEMBATALAN -->
<script>
const TYPE = '<?= htmlspecialchars($type) ?>';
const ID_REF = <?= (int)$idRef ?>;
const BASE_URL = '<?= BASE_URL ?>';

function formatTanggalIndo(tanggalStr) {
    if (!tanggalStr || tanggalStr === '0000-00-00' || tanggalStr === '0000-00-00 00:00:00') return '-';
    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const bulan = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    const dObj = new Date(tanggalStr.replace(/-/g, '/'));
    if (isNaN(dObj.getTime())) {
        const parts = tanggalStr.split(' ')[0].split('-');
        if (parts.length === 3) {
            const d = parseInt(parts[2], 10);
            const m = parseInt(parts[1], 10);
            const y = parts[0];
            return `${d} ${bulan[m] || ''} ${y}`;
        }
        return tanggalStr;
    }
    
    const namaHari = hari[dObj.getDay()];
    const d = dObj.getDate();
    const m = bulan[dObj.getMonth() + 1];
    const y = dObj.getFullYear();
    const jam = String(dObj.getHours()).padStart(2, '0');
    const mnt = String(dObj.getMinutes()).padStart(2, '0');
    
    return {
        tanggal: `${d} ${m} ${y}`,
        lengkap: `${namaHari}, ${d} ${m} ${y} (${jam}:${mnt})`
    };
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
        const response = await fetch(`${BASE_URL}/api/laporan/pembatalan.php?type=${TYPE}&id=${ID_REF}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (!result || !result.success || !result.data) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger fw-bold">
                        ${result ? result.message : 'Gagal memuat Berita Acara Pembatalan dari API.'}
                    </td>
                </tr>
            `;
            return;
        }

        const doc = result.data;
        const tglInfo = formatTanggalIndo(doc.tanggal_batal);

        // Update Toolbar & Title
        document.getElementById('toolbarNomorBap').textContent = doc.nomor_bap || '-';
        document.title = `BAP - ${doc.nomor_bap || 'Berita Acara Pembatalan'}`;

        // Header Meta Box
        document.getElementById('docNomorBap').textContent = doc.nomor_bap || '-';
        document.getElementById('docTanggalBatal').textContent = typeof tglInfo === 'object' ? tglInfo.tanggal : tglInfo;

        // Metadata 2 Kolom
        const jenisLabel = doc.jenis_dokumen === 'PO' ? 'Purchase Order' : 'Faktur Pembelian (Invoice)';
        document.getElementById('docJenisDokumen').textContent = jenisLabel;
        document.getElementById('docNomorRef').textContent = doc.nomor_referensi || '-';
        document.getElementById('docNamaVendor').textContent = (doc.nama_vendor || '-') + (doc.kode_vendor ? ` [${doc.kode_vendor}]` : '');
        document.getElementById('docAlamatVendor').textContent = doc.alamat_vendor || '-';

        document.getElementById('docStateBatal').textContent = doc.status || 'BATAL';
        document.getElementById('docPetugasBatal').textContent = doc.nama_karyawan_batal || 'Petugas Transaksi';
        document.getElementById('docKategoriAlasan').textContent = doc.kategori_alasan || 'Pembatalan Transaksi';
        document.getElementById('docWaktuBatal').textContent = typeof tglInfo === 'object' ? tglInfo.lengkap : tglInfo;

        // Items Table
        const items = doc.items || [];
        if (items.length === 0) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-3 text-muted">Tidak ada rincian item barang dalam transaksi ini.</td>
                </tr>
            `;
        } else {
            let itemsHtml = '';
            items.forEach((it, idx) => {
                const qty = parseFloat(it.qty || it.jumlah || 0);
                const harga = parseFloat(it.harga_satuan || it.harga || 0);
                const subtotal = parseFloat(it.subtotal || 0);

                itemsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td class="text-center font-monospace" style="font-size: 10px;">${escapeHtml(it.kode_barang || '-')}</td>
                        <td>
                            <div class="fw-bold">${escapeHtml(it.nama_barang || it.keterangan || '')}</div>
                        </td>
                        <td class="text-center font-monospace fw-bold">${qty}</td>
                        <td class="text-center" style="font-size: 10px;">${escapeHtml(it.satuan || 'PCS').toUpperCase()}</td>
                        <td class="text-end font-monospace">${formatRupiah(harga)}</td>
                        <td class="text-end font-monospace fw-bold">${formatRupiah(subtotal)}</td>
                    </tr>
                `;
            });
            document.getElementById('docItemsTableBody').innerHTML = itemsHtml;
        }

        // Uraian Alasan & Ringkasan Finansial
        let rawReason = doc.alasan_detail || '-';
        let cleanReason = rawReason.replace(/\[BATAL:\s*[^\]]+\]/, '').trim();
        document.getElementById('docUraianAlasan').innerHTML = `<strong>[${escapeHtml(doc.kategori_alasan || 'Pembatalan Transaksi')}]</strong> ${escapeHtml(cleanReason).replace(/\n/g, '<br>')}`;
        
        document.getElementById('docTotalItem').textContent = `${items.length} Baris Item`;
        document.getElementById('docTotalNilai').textContent = formatRupiah(doc.nilai_transaksi || 0);

        // Signatures
        const rolePembuat = doc.jenis_dokumen === 'PO' ? '(Staff Purchasing)' : '(Staff Finance)';
        document.getElementById('docSigRolePembuat').textContent = rolePembuat;
        document.getElementById('docSigPembuat').textContent = doc.nama_karyawan_batal || 'Petugas Pembatalan';

    } catch (e) {
        console.error('Error fetching BAP print data:', e);
        document.getElementById('docItemsTableBody').innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-danger fw-bold">
                    Terjadi kesalahan saat memproses data dokumen BAP.
                </td>
            </tr>
        `;
    }
});
</script>
</body>
</html>
