<?php
/**
 * Surat Keterangan / Bukti Mutasi Barang Antar-Site
 * Path: admin/pages/mutasi_barang/print_surat.php
 * Format: Terintegrasi REST API & External CSS (styles/print_document.css) - Pure Decoupled Architecture
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER, ROLE_PURCHASING]);

$idMutasi = isset($_GET['id_mutasi']) ? decodeId($_GET['id_mutasi']) : (isset($_GET['id']) ? decodeId($_GET['id']) : 0);
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

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
    <title>Surat Mutasi Barang - <?= htmlspecialchars($companyName) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet Standar -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Surat Mutasi &amp; Transfer Barang
            </span>
            <span class="badge bg-secondary font-monospace" id="tbKodeMutasi">-</span>
            <span class="badge bg-primary" id="tbStatusMutasi">-</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group">
                <a href="?id_mutasi=<?= encodeId($idMutasi) ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id_mutasi=<?= encodeId($idMutasi) ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <button type="button" class="btn btn-outline-light btn-sm px-3" onclick="window.close()">
                <i class="bi bi-x-lg me-1"></i> Tutup
            </button>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Surat (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- CONTAINER UTAMA DOKUMEN -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>" id="printContainer">
    
    <?php if ($useKop): ?>
    <!-- KOP SURAT RESMI (STANDAR SISTEM) -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars(ltrim($companyLogo, '/')) ?>" alt="Logo" style="max-height: 55px; max-width: 140px; object-fit: contain; flex-shrink: 0;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-dark text-white rounded p-2" style="width: 50px; height: 50px;">
                    <i class="bi bi-buildings fs-3"></i>
                </div>
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
                SURAT JALAN<br>&amp; TRANSFER<br>MATERIAL<br>LOGISTIK
            </div>
        </div>
    </div>
    <div class="kop-divider"></div>
    <?php endif; ?>

    <!-- TITLE DOKUMEN -->
    <div class="doc-title-container mt-3">
        <div class="doc-title-main">SURAT KETERANGAN MUTASI BARANG</div>
        <div class="doc-title-sub" id="docNoSurat">Nomor Dokumen: -</div>
    </div>

    <!-- META DATA INFORMASI -->
    <div class="row g-3 mt-2 mb-3">
        <div class="col-6">
            <table class="w-100" style="font-size: 11px; line-height: 1.6;">
                <tr>
                    <td style="width: 130px;" class="fw-bold">Tanggal Mutasi</td>
                    <td style="width: 10px;">:</td>
                    <td id="dtTanggalMutasi">-</td>
                </tr>
                <tr>
                    <td class="fw-bold">Site Asal</td>
                    <td>:</td>
                    <td id="dtSiteAsal">-</td>
                </tr>
                <tr>
                    <td class="fw-bold">Site Tujuan</td>
                    <td>:</td>
                    <td id="dtSiteTujuan">-</td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="w-100" style="font-size: 11px; line-height: 1.6;">
                <tr>
                    <td style="width: 140px;" class="fw-bold">Pemohon Transfer</td>
                    <td style="width: 10px;">:</td>
                    <td id="dtPemohon">-</td>
                </tr>
                <tr>
                    <td class="fw-bold">Persetujuan Otorisasi</td>
                    <td>:</td>
                    <td id="dtPenyetuju">-</td>
                </tr>
                <tr>
                    <td class="fw-bold">Petugas Penginput</td>
                    <td>:</td>
                    <td id="dtPembuat">-</td>
                </tr>
                <tr>
                    <td class="fw-bold">Status Mutasi</td>
                    <td>:</td>
                    <td id="dtStatus"><strong>-</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL RINCIAN BARANG -->
    <table class="table-items-main mb-3" id="tablePrintItems">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th class="text-center" style="width: 120px;">Kode Barang</th>
                <th style="text-align: left;">Nama Barang</th>
                <th class="text-center" style="width: 120px;">Serial Number</th>
                <th class="text-end" style="width: 90px;">Jumlah</th>
                <th class="text-center" style="width: 70px;">Satuan</th>
            </tr>
        </thead>
        <tbody id="printTableItemsBody">
            <tr>
                <td colspan="6" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data surat mutasi...
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-end pe-2">TOTAL KUANTITAS DIMUTASI:</td>
                <td class="text-end font-monospace fw-bold" id="footTotalQty">0</td>
                <td class="text-center">ITEM</td>
            </tr>
        </tfoot>
    </table>

    <!-- SECTION BIAYA OPERASIONAL PENGIRIMAN -->
    <div class="p-2 border rounded bg-light mb-2 d-flex justify-content-between align-items-center" id="biayaOperasionalSection" style="font-size: 11px;">
        <span class="fw-bold text-dark">Biaya Operasional Pengiriman:</span>
        <strong class="font-monospace fs-6 text-dark" id="dtBiayaOperasional">Rp 0</strong>
    </div>

    <!-- KETERANGAN / CATATAN ALASAN MUTASI -->
    <div class="p-2 border rounded bg-light mb-4 d-none" id="keteranganBox" style="font-size: 11px;">
        <strong>Keterangan / Alasan Transfer:</strong> <span id="dtKeterangan">-</span>
    </div>

    <!-- TANDA TANGAN 3 KOLOM -->
    <div class="row text-center mt-4" style="font-size: 11px;">
        <div class="col-4">
            <div>Yang Meminta,</div>
            <div class="text-muted small">(Pemohon)</div>
            <div style="height: 60px;"></div>
            <div class="fw-bold text-decoration-underline" id="ttdPemohon">-</div>
            <div class="small text-muted" id="ttdJabatanPemohon">Staff</div>
        </div>
        <div class="col-4">
            <div>Disetujui Oleh,</div>
            <div class="text-muted small">(Pejabat Level 1)</div>
            <div style="height: 60px;"></div>
            <div class="fw-bold text-decoration-underline" id="ttdPenyetuju">...........................</div>
            <div class="small text-muted" id="ttdJabatanPenyetuju">Manager</div>
        </div>
        <div class="col-4">
            <div>Penerima Material,</div>
            <div class="text-muted small">(Logistik Site Tujuan)</div>
            <div style="height: 60px;"></div>
            <div class="fw-bold text-decoration-underline">...........................</div>
            <div class="small text-muted" id="ttdSiteTujuan">Site Tujuan</div>
        </div>
    </div>

    <!-- FOOTER BAWAH DOKUMEN -->
    <div class="footer-line-container mt-4">
        <div class="footer-right">
            <div class="fw-bold">Halaman 1 dari 1</div>
            <div>Dicetak pada: <?= date('d/m/Y H:i') ?> | Oleh: <?= htmlspecialchars($user['nama_karyawan'] ?? $user['username'] ?? 'Petugas') ?></div>
            <div>Inventory &amp; Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<script>
const ID_MUTASI = <?= (int)$idMutasi ?>;

async function loadMutasiDetail() {
    if (!ID_MUTASI || ID_MUTASI <= 0) {
        document.getElementById('printTableItemsBody').innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">ID Mutasi tidak valid.</td></tr>';
        return;
    }

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php?id_mutasi=${ID_MUTASI}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            renderDocument(json.data);
        } else {
            document.getElementById('printTableItemsBody').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${json.message || 'Data mutasi tidak ditemukan.'}</td></tr>`;
        }
    } catch (err) {
        document.getElementById('printTableItemsBody').innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function renderDocument(data) {
    if (data.kode_mutasi) {
        document.title = `Surat Mutasi Barang - ${data.kode_mutasi}`;
    }

    // Toolbar Header
    document.getElementById('tbKodeMutasi').textContent = data.kode_mutasi || '-';
    document.getElementById('tbStatusMutasi').textContent = data.status || '-';

    // Title Box
    document.getElementById('docNoSurat').textContent = `Nomor Dokumen: ${data.kode_mutasi}` + (data.nomor_surat_mutasi ? ` / ${data.nomor_surat_mutasi}` : '');

    // Metadata Left
    document.getElementById('dtTanggalMutasi').textContent = (data.tanggal_mutasi_formatted || '-') + ' WITA';
    document.getElementById('dtSiteAsal').innerHTML = `<strong>${escapeHtml(data.nama_site_asal || '-')}</strong> (${escapeHtml(data.jenis_site_asal || 'Site')})`;
    document.getElementById('dtSiteTujuan').innerHTML = `<strong>${escapeHtml(data.nama_site_tujuan || '-')}</strong> (${escapeHtml(data.jenis_site_tujuan || 'Site')})`;
    document.getElementById('dtBiayaOperasional').textContent = data.biaya_operasional_formatted || 'Rp 0';

    // Metadata Right
    document.getElementById('dtPemohon').textContent = `${data.nama_pemohon || '-'} (${data.jabatan_pemohon || 'Staff'})`;
    document.getElementById('dtPenyetuju').textContent = data.nama_penyetuju ? `${data.nama_penyetuju} (${data.jabatan_penyetuju || 'Level 1'})` : '-';
    document.getElementById('dtPembuat').textContent = `${data.nama_pembuat || '-'} (Logistik)`;
    document.getElementById('dtStatus').innerHTML = `<strong>${escapeHtml(data.status || '-')}</strong>`;

    // Keterangan
    if (data.keterangan) {
        document.getElementById('dtKeterangan').textContent = data.keterangan;
        document.getElementById('keteranganBox').classList.remove('d-none');
    }

    // Items Table
    const tbody = document.getElementById('printTableItemsBody');
    if (data.items && data.items.length > 0) {
        let html = '';
        data.items.forEach((it, idx) => {
            html += `
                <tr>
                    <td class="text-center">${idx + 1}</td>
                    <td class="font-monospace text-center fw-semibold">${escapeHtml(it.kode_barang)}</td>
                    <td>
                        <div class="fw-bold">${escapeHtml(it.nama_barang)}</div>
                       
                    </td>
                    <td class="font-monospace small text-center">${escapeHtml(it.serial_number || '-')}</td>
                    <td class="text-end font-monospace fw-bold">${Number(it.qty).toLocaleString('id-ID')}</td>
                    <td class="text-center">${escapeHtml(it.satuan || 'PCS')}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        document.getElementById('footTotalQty').textContent = Number(data.total_qty || 0).toLocaleString('id-ID');
    } else {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">Tidak ada rincian barang.</td></tr>';
    }

    // Tanda Tangan
    document.getElementById('ttdPemohon').textContent = data.nama_pemohon || '...........................';
    document.getElementById('ttdJabatanPemohon').textContent = data.jabatan_pemohon || 'Staff';
    document.getElementById('ttdPenyetuju').textContent = data.nama_penyetuju || '...........................';
    document.getElementById('ttdJabatanPenyetuju').textContent = data.jabatan_penyetuju || 'Manager';
    document.getElementById('ttdSiteTujuan').textContent = data.nama_site_tujuan || 'Site Tujuan';
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
    loadMutasiDetail();
});
</script>

</body>
</html>
