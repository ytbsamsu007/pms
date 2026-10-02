<?php
/**
 * Halaman Cetak Surat Penerimaan Barang (Receiving Report untuk Vendor)
 * Path: admin/pages/receiving/print.php
 * Format: Terintegrasi API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$idRcv = isset($_GET['id']) ? decodeId($_GET['id']) : (isset($_GET['id_rcv']) ? decodeId($_GET['id_rcv']) : 0);
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idRcv <= 0) {
    die("ID Penerimaan Barang tidak valid.");
}

// Ambil Data Profil Perusahaan
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
    <title>Surat Penerimaan Barang (SPB)</title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Surat Penerimaan Barang (SPB)
            </span>
            <span class="badge bg-secondary font-monospace" id="toolbarNomorRcv">...</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= encodeId($idRcv) ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= encodeId($idRcv) ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/receiving/index.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="doPrintReceiving()">
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
            <div class="doc-title-main">SURAT PENERIMAAN BARANG</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">RECEIVING &nbsp; REPORT</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Dokumen</div>
            <div class="box-row-val font-monospace" id="docNomorRcv">-</div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal</div>
            <div class="box-row-val" id="docTanggalRcv">-</div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM -->
    <div class="info-grid">
        <!-- Kolom Kiri -->
        <div class="info-col-left">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">No. Purchase Order</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold" id="docNomorPo">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Surat Jalan</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorSj">-</td>
                </tr>
                <tr>
                    <td class="lbl">Vendor / Pengirim</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold" id="docNamaVendor">-</td>
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
                    <td class="lbl">Lokasi Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val" id="docSiteTujuan">-</td>
                </tr>
                <tr>
                    <td class="lbl">Petugas Logistik</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold" id="docNamaPenerima">-</td>
                </tr>
                <tr>
                    <td class="lbl">Tanggal Penerimaan</td>
                    <td class="colon">:</td>
                    <td class="val" id="docTanggalDiterima">-</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL DAFTAR BARANG / MATERIAL -->
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 40px;">NO.</th>
                <th style="width: 85px;">KODE</th>
                <th>NM BARANG</th>
                <th style="width: 60px;">PO</th>
                <th style="width: 60px;">RCV</th>
                <th style="width: 75px;">SATUAN</th>
                <th style="width: 60px;">QC</th>
                <th style="width: 185px;">KET</th>
            </tr>
        </thead>
        <tbody id="docItemsTableBody">
            <tr>
                <td colspan="8" class="text-center py-3 text-muted">Memuat data Penerimaan Barang...</td>
            </tr>
        </tbody>
    </table>

    <!-- KOTAK CATATAN PENERIMAAN -->
    <div class="catatan-penerimaan-section">
        <div class="notes-title">CATATAN PENERIMAAN :</div>
        <div class="catatan-box" id="docCatatanRcv"></div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Diserahkan Oleh -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diserahkan Oleh</div>
                <div class="sig-header-sub">(Vendor / Ekspedisi)</div>
                <div class="sig-line-box">
                    ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
                </div>
            </div>

            <!-- 2. Diterima Oleh -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diterima Oleh</div>
                <div class="sig-header-sub" id="docSigRolePenerima">(Petugas Logistik)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigPenerima">-</span> &nbsp; )
                </div>
            </div>

            <!-- 3. Mengetahui -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Mengetahui</div>
                <div class="sig-header-sub">(Kepala Gudang / Site Manager)</div>
                <div class="sig-line-box">
                    ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
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

<!-- SCRIPT LOGIC MEMUAT DATA DARI API RECEIVING -->
<script>
const ID_RCV = <?= $idRcv ?>;
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

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`${BASE_URL}/api/receiving/index.php?id=${ID_RCV}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (!result || !result.success || !result.data) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4 text-danger fw-bold">
                        ${result ? result.message : 'Gagal memuat dokumen Penerimaan Barang dari API.'}
                    </td>
                </tr>
            `;
            return;
        }

        const rcv = result.data;

        // Toolbar & Title
        document.getElementById('toolbarNomorRcv').textContent = rcv.nomor_rcv || '-';
        document.title = `Surat Penerimaan Barang - ${rcv.nomor_rcv || 'RCV'}`;

        // Header Metadata
        const tglTerima = rcv.tanggal_diterima || rcv.tanggal_rcv;
        const tglIndo = formatTanggalIndo(tglTerima);
        document.getElementById('docNomorRcv').textContent = rcv.nomor_rcv || '-';
        document.getElementById('docTanggalRcv').textContent = tglIndo;

        // Details
        document.getElementById('docNomorPo').textContent = rcv.nomor_po || '-';
        document.getElementById('docNomorSj').textContent = rcv.nomor_sj || '-';
        document.getElementById('docNamaVendor').textContent = rcv.nama_vendor || '-';
        document.getElementById('docAlamatVendor').innerHTML = escapeHtml(rcv.alamat_vendor || '-').replace(/\n/g, '<br>');
        
        document.getElementById('docSiteTujuan').textContent = rcv.nama_site || '-';
        document.getElementById('docNamaPenerima').textContent = rcv.nama_penerima || 'Petugas Logistik';
        document.getElementById('docTanggalDiterima').textContent = tglIndo;

        // Items Table
        const items = rcv.items || [];
        if (items.length === 0) {
            document.getElementById('docItemsTableBody').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-3 text-muted">Tidak ada rincian barang fisik.</td>
                </tr>
            `;
        } else {
            let itemsHtml = '';
            let totalQtyPo = 0;
            let totalQtyRcv = 0;

            items.forEach((it, idx) => {
                const statusQc = (it.status_qc !== undefined && it.status_qc !== null && it.status_qc !== '') ? parseInt(it.status_qc) : 1;
                const isPassed = (statusQc === 1);
                const qcText = isPassed ? 'BAIK' : 'CACAT';
                const qtyPo = parseFloat(it.qty_po) || 0;
                const qtyRcv = parseFloat(it.qty_diterima) || 0;
                const ket = it.keterangan_item || '-';

                totalQtyPo += qtyPo;
                if (isPassed) {
                    totalQtyRcv += qtyRcv;
                }

                const rcvDisplay = isPassed ? `${qtyPo > 0 && qtyRcv === 0 ? '0' : qtyRcv}` : `-${qtyRcv}`;

                itemsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td class="text-center font-monospace" style="font-size: 10px;">${escapeHtml(it.kode_barang || '-')}</td>
                        <td>${escapeHtml(it.nama_barang || '')}</td>
                        <td class="text-center font-monospace">${qtyPo}</td>
                        <td class="text-center font-monospace fw-bold ${!isPassed ? 'text-danger' : ''}">${rcvDisplay}</td>
                        <td class="text-center" style="font-size: 10px;">${escapeHtml(it.satuan || 'PCS').toUpperCase()}</td>
                        <td class="text-center fw-bold ${!isPassed ? 'text-danger' : ''}">${qcText}</td>
                        <td>${escapeHtml(ket)}</td>
                    </tr>
                `;
            });

            // Baris Total
            itemsHtml += `
                <tr class="total-row">
                    <td colspan="3" class="text-center fw-bold">TOTAL</td>
                    <td class="text-center font-monospace fw-bold">${totalQtyPo}</td>
                    <td class="text-center font-monospace fw-bold">${totalQtyRcv}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            `;

            document.getElementById('docItemsTableBody').innerHTML = itemsHtml;
        }

        // Catatan RCV
        document.getElementById('docCatatanRcv').innerHTML = rcv.catatan_rcv ? escapeHtml(rcv.catatan_rcv).replace(/\n/g, '<br>') : '-';

        // Signature Penerima (Nama, Jabatan & Divisi Dinamis)
        const jabatanPenerima = rcv.jabatan_penerima || '';
        const divisiPenerima = rcv.divisi_penerima || '';
        let rolePenerimaText = '';
        if (jabatanPenerima && divisiPenerima) {
            rolePenerimaText = `(${jabatanPenerima} - ${divisiPenerima})`;
        } else if (jabatanPenerima) {
            rolePenerimaText = `(${jabatanPenerima})`;
        } else {
            rolePenerimaText = `(Petugas Logistik)`;
        }
        document.getElementById('docSigRolePenerima').textContent = rolePenerimaText;
        document.getElementById('docSigPenerima').textContent = rcv.nama_penerima || 'Petugas Logistik';

    } catch (e) {
        console.error('Error fetching receiving print data:', e);
        document.getElementById('docItemsTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-danger fw-bold">
                    Terjadi kesalahan saat memproses data dokumen.
                </td>
            </tr>
        `;
    }
});

async function doPrintReceiving() {
    try {
        await fetch(`${BASE_URL}/api/receiving/mark_print.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_rcv: ID_RCV })
        });
    } catch (e) {
        console.error('Error marking print:', e);
    }
    window.print();
}
</script>
</body>
</html>
