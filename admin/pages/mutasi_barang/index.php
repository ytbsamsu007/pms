<?php
/**
 * Halaman Daftar Mutasi Barang (Transfer Antar Site)
 * Path: admin/pages/mutasi_barang/index.php
 * Khusus Role: ADMIN, LOGISTIK, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);
$pageTitle = 'Daftar Mutasi Barang';
$pageHeading = 'Mutasi & Transfer Barang Antar Site';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Mutasi &amp; Transfer Barang Antar Site</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/mutasi_barang/create.php" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Mutasi Barang
            </a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 mutasi-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-3 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari Kode / Pemohon / Ket..." autocomplete="off" oninput="debounceSearch()">
                    </div>
                </div>

                <!-- Site Asal Filter -->
                <div class="col-md-2 col-lg-2" style="min-width: 170px;">
                    <select class="form-select form-select-sm" id="filterSiteAsal" onchange="applyFilters()">
                        <option value="0">Semua Site Asal</option>
                    </select>
                </div>

                <!-- Site Tujuan Filter -->
                <div class="col-md-2 col-lg-2" style="min-width: 170px;">
                    <select class="form-select form-select-sm" id="filterSiteTujuan" onchange="applyFilters()">
                        <option value="0">Semua Site Tujuan</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 col-lg-2" style="min-width: 160px;">
                    <select class="form-select form-select-sm" id="filterStatus" onchange="applyFilters()">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="MENUNGGU PERSETUJUAN">MENUNGGU PERSETUJUAN</option>
                        <option value="DISETUJUI">DISETUJUI</option>
                        <option value="DIKIRIM SITE ASAL">DIKIRIM SITE ASAL</option>
                        <option value="DITERIMA SITE TUJUAN">DITERIMA SITE TUJUAN</option>
                        <option value="BATAL">BATAL</option>
                    </select>
                </div>

                <!-- Tombol Reset (Rata Kanan) -->
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .mutasi-filter-bar .form-control,
    .mutasi-filter-bar .form-select,
    .mutasi-filter-bar .input-group-text,
    .mutasi-filter-bar .btn {
        height: 36px;
        font-size: 0.85rem;
    }
    .mutasi-filter-bar .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: 10px;
        padding-right: 10px;
    }
    .mutasi-filter-bar .form-select {
        padding-top: 0.15rem !important;
        padding-bottom: 0.35rem !important;
        padding-right: 2.25rem !important;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        line-height: 1.5 !important;
    }
    .status-badge {
        font-size: 0.72rem;
        letter-spacing: 0.3px;
    }
    </style>

    <!-- TABEL DATA MUTASI ORDER -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="mutasiTable" style="min-width: 950px;">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 140px;">Kode Mutasi</th>
                            <th style="min-width: 110px;">Tanggal</th>
                            <th style="min-width: 90px;" class="text-center">Waktu</th>
                            <th style="min-width: 190px;">Rute Mutasi</th>
                            <th style="min-width: 150px;" class="text-center">Status</th>
                            <th style="min-width: 120px;" class="text-center">Total</th>
                            <th style="min-width: 110px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="mutasiTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data mutasi...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination Footer -->
        <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top" id="paginationContainer" style="display: none !important;">
            <div class="text-muted small" id="paginationInfo">
                Menampilkan data...
            </div>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL DETAIL MUTASI LENGKAP -->
<div class="modal fade" id="modalDetailMutasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- Header Modal Modern dengan Status & Close Button (X) di Kanan Atas -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalHeaderKodeMutasi">
                            DI-XXXX-00000
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="modalHeaderStatusBadge">-</span>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Nav Tabs Modal Detail (Tanpa Icon) -->
                <ul class="nav nav-tabs border-bottom-0" id="mutasiDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="mdetail-tab-utama" data-bs-toggle="tab" data-bs-target="#mdetail-pane-utama" type="button" role="tab">
                            1. Informasi Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="mdetail-tab-barang" data-bs-toggle="tab" data-bs-target="#mdetail-pane-barang" type="button" role="tab">
                            2. Daftar Barang Dimutasi
                            <span class="badge bg-primary text-white ms-1" id="modalTabBarangBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="mdetail-tab-opsi" data-bs-toggle="tab" data-bs-target="#mdetail-pane-opsi" type="button" role="tab">
                            3. Update Status Mutasi
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="mutasiDetailTabContent">
                    
                    <!-- TAB 1: INFORMASI UTAMA (TANPA ICON) -->
                    <div class="tab-pane fade show active" id="mdetail-pane-utama" role="tabpanel">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="row g-3">
                                <div class="col-md-6 border-end-md">
                                    <div class="mb-3">
                                        <span class="text-muted small d-block mb-1">Kode Mutasi:</span>
                                        <strong class="text-primary font-monospace fs-6" id="modalKodeMutasi">-</strong>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block mb-1">No. Surat Keterangan Mutasi:</span>
                                        <span class="font-monospace fw-semibold text-dark" id="modalNomorSurat">-</span>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block mb-1">Tanggal &amp; Waktu Transaksi:</span>
                                        <span class="text-dark fw-medium" id="modalTanggalMutasi">-</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block mb-1">Biaya Operasional Pengiriman:</span>
                                        <strong class="text-success font-monospace fs-6" id="modalBiayaOperasional">Rp 0</strong>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <span class="text-muted small d-block mb-1">Rute Mutasi:</span>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="fw-semibold text-dark" id="modalSiteAsal">-</span>
                                            <span class="text-muted small">&rarr;</span>
                                            <span class="fw-semibold text-dark" id="modalSiteTujuan">-</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block mb-1">Karyawan Pemohon Transfer:</span>
                                        <strong class="text-dark" id="modalPemohon">-</strong>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block mb-1">Disetujui Oleh (Level 1):</span>
                                        <span class="text-dark fw-medium" id="modalPenyetuju">-</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block mb-1">Dibuat Oleh (Petugas Logistik):</span>
                                        <span class="text-muted" id="modalPembuat">-</span>
                                    </div>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top">
                                    <span class="text-muted small d-block mb-1">Keterangan / Alasan Transfer:</span>
                                    <div class="p-2 bg-white rounded border text-dark small" id="modalKeterangan">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: DAFTAR BARANG (TANPA ICON) -->
                    <div class="tab-pane fade" id="mdetail-pane-barang" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-dark small">Rincian Barang Dimutasi</span>
                            <span class="badge bg-primary px-3 py-2 fw-semibold" id="modalTotalQtyBadge">Total: 0 Qty</span>
                        </div>
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">No</th>
                                        <th style="min-width: 130px;">Kode Barang</th>
                                        <th style="min-width: 220px;">Nama Barang</th>
                                        <th style="min-width: 110px;" class="text-center">Serial No</th>
                                        <th style="min-width: 120px;" class="text-end pe-3">Jumlah Mutasi</th>
                                    </tr>
                                </thead>
                                <tbody id="modalBarangList">
                                    <!-- Dynamic Items -->
                                </tbody>
                            </table>
                        </div>

                        <!-- SECTION BIAYA OPERASIONAL DIBAWAH TABEL BARANG -->
                        <div class="p-3 bg-light rounded-3 border mt-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-bold">Biaya Operasional Pengiriman:</span>
                            <strong class="text-success font-monospace fs-6" id="modalBarangTabBiayaOperasional">Rp 0</strong>
                        </div>
                    </div>

                    <!-- TAB 3: OPSI & STATUS FLOW (TANPA ICON) -->
                    <div class="tab-pane fade" id="mdetail-pane-opsi" role="tabpanel">
                        <div class="p-4 bg-light border rounded-3">
                            <h6 class="fw-bold text-dark mb-2">
                                Perbarui Status Transaksi Mutasi
                            </h6>
                            <p class="text-muted small mb-4" id="modalStatusInfoText" style="line-height: 1.6;">
                                Stok fisik antar site akan diperbarui secara otomatis saat status diubah menjadi <strong>DITERIMA SITE TUJUAN</strong>.
                            </p>

                            <!-- Alert jika status terkunci / final -->
                            <div class="alert alert-success d-none mb-3 py-2 px-3 small border-0 shadow-sm" id="modalStatusLockedAlert">
                                <strong>Status Final (Selesai):</strong> Barang telah berhasil diterima di site tujuan dan mutasi stok telah dibukukan. Status tidak dapat diubah lagi.
                            </div>
                            <div class="alert alert-secondary d-none mb-3 py-2 px-3 small border-0 shadow-sm" id="modalStatusBatalAlert">
                                <strong>Status Final (Batal):</strong> Transaksi mutasi ini telah dibatalkan dan tidak dapat diubah lagi.
                            </div>

                            <div class="row g-3 align-items-end" id="modalStatusFormRow">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold text-dark">Pilih Status Baru:</label>
                                    <select class="form-select form-select-sm" id="modalUpdateStatusSelect">
                                        <option value="DRAFT">DRAFT</option>
                                        <option value="MENUNGGU PERSETUJUAN">MENUNGGU PERSETUJUAN</option>
                                        <option value="DISETUJUI">DISETUJUI</option>
                                        <option value="DIKIRIM SITE ASAL">DIKIRIM SITE ASAL</option>
                                        <option value="DITERIMA SITE TUJUAN">DITERIMA SITE TUJUAN</option>
                                        <option value="BATAL">BATAL</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <button type="button" class="btn btn-primary btn-sm fw-semibold w-100 py-2 shadow-sm" id="btnUpdateStatusModal" onclick="submitUpdateStatus()">
                                        Simpan Perubahan Status
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentMutasiList = [];
let selectedMutasiId = null;
let searchTimer = null;
let currentPage = 1;
const fixedLimit = 25;

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadMutasi();
    }, 300);
}

function applyFilters() {
    currentPage = 1;
    loadMutasi();
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSiteAsal').value = '0';
    document.getElementById('filterSiteTujuan').value = '0';
    document.getElementById('filterStatus').value = '';
    currentPage = 1;
    loadMutasi();
}

function goToPage(page) {
    currentPage = page;
    loadMutasi();
}

async function loadMutasi() {
    const tbody = document.getElementById('mutasiTableBody');
    const pagContainer = document.getElementById('paginationContainer');
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4 text-muted align-middle">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data mutasi...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const siteAsal = document.getElementById('filterSiteAsal').value;
    const siteTujuan = document.getElementById('filterSiteTujuan').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('limit', fixedLimit);
    if (search) params.append('search', search);
    if (siteAsal && siteAsal !== '0') params.append('id_site_asal', siteAsal);
    if (siteTujuan && siteTujuan !== '0') params.append('id_site_tujuan', siteTujuan);
    if (status) params.append('status', status);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            currentMutasiList = json.data.items || [];
            populateSiteOptions(json.data.sites || []);
            renderTable(currentMutasiList, json.data.pagination);
            renderPaginationControls(json.data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">${json.message || 'Gagal memuat data.'}</td></tr>`;
            pagContainer.style.display = 'none';
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
        pagContainer.style.display = 'none';
    }
}

function populateSiteOptions(sites) {
    const selAsal = document.getElementById('filterSiteAsal');
    const selTujuan = document.getElementById('filterSiteTujuan');
    const curAsal = selAsal.value;
    const curTujuan = selTujuan.value;

    if (selAsal.options.length <= 1) {
        sites.forEach(s => {
            const opt1 = document.createElement('option');
            opt1.value = s.id_site;
            opt1.textContent = s.nama_site;
            selAsal.appendChild(opt1);

            const opt2 = document.createElement('option');
            opt2.value = s.id_site;
            opt2.textContent = s.nama_site;
            selTujuan.appendChild(opt2);
        });
    }
    selAsal.value = curAsal;
    selTujuan.value = curTujuan;
}

function getStatusBadge(status) {
    switch (status) {
        case 'DRAFT':
            return '<span class="badge bg-secondary status-badge">DRAFT</span>';
        case 'MENUNGGU PERSETUJUAN':
            return '<span class="badge bg-warning text-dark status-badge">MENUNGGU PERSETUJUAN</span>';
        case 'DISETUJUI':
            return '<span class="badge bg-info text-white status-badge">DISETUJUI</span>';
        case 'DIKIRIM SITE ASAL':
            return '<span class="badge bg-primary status-badge">DIKIRIM SITE ASAL</span>';
        case 'DITERIMA SITE TUJUAN':
            return '<span class="badge bg-success status-badge">DITERIMA SITE TUJUAN</span>';
        case 'BATAL':
            return '<span class="badge bg-danger status-badge">BATAL</span>';
        default:
            return `<span class="badge bg-light text-dark border status-badge">${status}</span>`;
    }
}

function renderTable(items, pagination) {
    const tbody = document.getElementById('mutasiTableBody');
    const pagContainer = document.getElementById('paginationContainer');

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted align-middle">Tidak ada data mutasi yang sesuai filter.</td></tr>`;
        pagContainer.style.display = 'none';
        return;
    }

    let html = '';
    const startIndex = pagination ? (pagination.from - 1) : 0;

    items.forEach((item, idx) => {
        const canPrint = ['DITERIMA SITE TUJUAN', 'DIKIRIM SITE ASAL', 'BATAL'].includes(item.status);

        html += `
            <tr class="align-middle">
                <!-- 1. No -->
                <td class="ps-3 text-center text-muted font-monospace small">${startIndex + idx + 1}</td>
                
                <!-- 2. Kode Mutasi -->
                <td>
                    <div class="font-monospace fw-bold text-primary">${escapeHtml(item.kode_mutasi)}</div>
                    ${item.nomor_surat_mutasi ? `<div class="text-muted small font-monospace">${escapeHtml(item.nomor_surat_mutasi)}</div>` : ''}
                </td>

                <!-- 3. Tanggal -->
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(item.tanggal_mutasi_formatted)}</div>
                </td>

                <!-- 4. Waktu -->
                <td class="text-center font-monospace text-dark">
                    ${escapeHtml(item.waktu_mutasi_formatted)}
                </td>

                <!-- 5. Rute Mutasi -->
                <td>
                    <span class="text-dark fw-medium">${escapeHtml(item.nama_site_asal || '-')}</span>
                    <i class="bi bi-arrow-right mx-1 text-muted small"></i>
                    <span class="text-dark fw-medium">${escapeHtml(item.nama_site_tujuan || '-')}</span>
                </td>

                <!-- 6. Status -->
                <td class="text-center">
                    ${getStatusBadge(item.status)}
                </td>

                <!-- 7. Total -->
                <td class="text-center font-monospace fw-semibold text-dark">
                    ${item.total_qty.toLocaleString('id-ID')}
                </td>

                <!-- 8. Aksi -->
                <td class="pe-3 text-center">
                    <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="showMutasiDetail(${item.id_mutasi})" title="Lihat Detail">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        ${canPrint ? `
                            <a href="<?= BASE_URL ?>/admin/pages/mutasi_barang/print_surat.php?id_mutasi=${encodeId(item.id_mutasi)}" target="_blank" class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-xs" title="Cetak Surat Keterangan Mutasi">
                                <i class="bi bi-printer-fill"></i>
                            </a>
                        ` : ''}
                        ${item.status === 'DRAFT' ? `
                            <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteMutasi(${item.id_mutasi}, '${item.kode_mutasi}')" title="Hapus Mutasi">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    pagContainer.style.display = 'flex';
}

function renderPaginationControls(pag) {
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');

    if (!pag || pag.total_records === 0) {
        info.textContent = 'Menampilkan 0 dari 0 data';
        controls.innerHTML = '';
        return;
    }

    info.textContent = `Menampilkan ${pag.from} - ${pag.to} dari ${pag.total_records} data (Total: ${pag.total_pages} Halaman)`;

    if (pag.total_pages <= 1) {
        controls.innerHTML = '';
        return;
    }

    let html = '';
    html += `
        <li class="page-item ${pag.page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page - 1})">&laquo; Prev</a>
        </li>
    `;
    
    const startPage = Math.max(1, pag.page - 2);
    const endPage = Math.min(pag.total_pages, pag.page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === pag.page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${p})">${p}</a>
            </li>
        `;
    }
    
    if (endPage < pag.total_pages) {
        if (endPage < pag.total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.total_pages})">${pag.total_pages}</a></li>`;
    }
    
    html += `
        <li class="page-item ${pag.page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page + 1})">Next &raquo;</a>
        </li>
    `;
    
    controls.innerHTML = html;
}

async function showMutasiDetail(idMutasi) {
    selectedMutasiId = idMutasi;
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php?id_mutasi=${idMutasi}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            const data = json.data;

            // Header Modal
            document.getElementById('modalHeaderKodeMutasi').textContent = data.kode_mutasi;
            document.getElementById('modalHeaderStatusBadge').innerHTML = getStatusBadge(data.status);

            // Tab 1: Utama
            document.getElementById('modalKodeMutasi').textContent = data.kode_mutasi;
            document.getElementById('modalNomorSurat').textContent = data.nomor_surat_mutasi || '-';
            document.getElementById('modalTanggalMutasi').textContent = data.tanggal_mutasi_formatted;
            document.getElementById('modalBiayaOperasional').textContent = data.biaya_operasional_formatted;
            document.getElementById('modalSiteAsal').textContent = data.nama_site_asal || '-';
            document.getElementById('modalSiteTujuan').textContent = data.nama_site_tujuan || '-';
            document.getElementById('modalPemohon').textContent = `${data.nama_pemohon || '-'} (${data.jabatan_pemohon || 'Staff'})`;
            document.getElementById('modalPenyetuju').textContent = data.nama_penyetuju ? `${data.nama_penyetuju} (${data.jabatan_penyetuju || 'Manager'})` : 'Belum disetujui';
            document.getElementById('modalPembuat').textContent = data.nama_pembuat || '-';
            document.getElementById('modalKeterangan').textContent = data.keterangan || 'Tidak ada keterangan.';

            // Tab 2: Barang
            document.getElementById('modalTabBarangBadge').textContent = data.total_items;
            document.getElementById('modalTotalQtyBadge').textContent = `Total: ${data.total_qty} Qty (${data.total_items} Item)`;
            document.getElementById('modalBarangTabBiayaOperasional').textContent = data.biaya_operasional_formatted || 'Rp 0';
            
            const bList = document.getElementById('modalBarangList');
            if (data.items && data.items.length > 0) {
                let bHtml = '';
                data.items.forEach((it, idx) => {
                    bHtml += `
                        <tr>
                            <td class="text-center text-muted small">${idx + 1}</td>
                            <td class="font-monospace fw-bold text-primary">${escapeHtml(it.kode_barang)}</td>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(it.nama_barang)}</div>
                                ${it.deskripsi ? `<div class="text-muted small">${escapeHtml(it.deskripsi)}</div>` : ''}
                            </td>
                            <td class="font-monospace small">${escapeHtml(it.serial_number || '-')}</td>
                            <td class="text-end font-monospace fw-bold fs-6 text-dark">${it.qty} <span class="small text-muted">${escapeHtml(it.satuan || 'PCS')}</span></td>
                        </tr>
                    `;
                });
                bList.innerHTML = bHtml;
            } else {
                bList.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted small">Tidak ada barang terlampir.</td></tr>`;
            }

            // Tab 3: Status
            const isDiterima = data.status === 'DITERIMA SITE TUJUAN';
            const isBatal = data.status === 'BATAL';
            const isLocked = isDiterima || isBatal;

            const lockedAlert = document.getElementById('modalStatusLockedAlert');
            const batalAlert = document.getElementById('modalStatusBatalAlert');
            const statusFormRow = document.getElementById('modalStatusFormRow');
            const statusSelect = document.getElementById('modalUpdateStatusSelect');
            const btnSubmitStatus = document.getElementById('btnUpdateStatusModal');
            const infoText = document.getElementById('modalStatusInfoText');

            statusSelect.value = data.status;

            if (isDiterima) {
                lockedAlert.classList.remove('d-none');
                batalAlert.classList.add('d-none');
                statusFormRow.classList.add('d-none');
                infoText.classList.add('d-none');
            } else if (isBatal) {
                batalAlert.classList.remove('d-none');
                lockedAlert.classList.add('d-none');
                statusFormRow.classList.add('d-none');
                infoText.classList.add('d-none');
            } else {
                lockedAlert.classList.add('d-none');
                batalAlert.classList.add('d-none');
                statusFormRow.classList.remove('d-none');
                infoText.classList.remove('d-none');
                statusSelect.disabled = false;
                btnSubmitStatus.disabled = false;
            }

            // Reset ke tab 1
            const tabTrigger = document.querySelector('#mdetail-tab-utama');
            if (tabTrigger) {
                const tab = bootstrap.Tab.getInstance(tabTrigger) || new bootstrap.Tab(tabTrigger);
                tab.show();
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetailMutasi')) || new bootstrap.Modal(document.getElementById('modalDetailMutasi'));
            modal.show();
        } else {
            showToast(json.message || 'Gagal mengambil detail mutasi.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    }
}

async function submitUpdateStatus() {
    if (!selectedMutasiId) return;
    const newStatus = document.getElementById('modalUpdateStatusSelect').value;
    const btn = document.getElementById('btnUpdateStatusModal');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _method: 'PUT',
                action: 'update_status',
                id_mutasi: selectedMutasiId,
                status: newStatus
            })
        });
        const json = await res.json();

        btn.disabled = false;
        btn.innerHTML = 'Simpan Perubahan Status';

        if (json.success) {
            showToast(json.message || 'Status mutasi berhasil diperbarui.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalDetailMutasi')).hide();
            loadMutasi();
        } else {
            showToast(json.message || 'Gagal mengubah status.', 'error');
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = 'Simpan Perubahan Status';
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    }
}

async function deleteMutasi(idMutasi, kode) {
    if (!confirm(`Apakah Anda yakin ingin menghapus transaksi mutasi "${kode}"?`)) return;

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                _method: 'DELETE',
                id_mutasi: idMutasi
            })
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message || 'Transaksi mutasi berhasil dihapus.', 'success');
            loadMutasi();
        } else {
            showToast(json.message || 'Gagal menghapus mutasi.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    }
}

function printCurrentMutasi() {
    if (!selectedMutasiId) return;
    window.open(`<?= BASE_URL ?>/admin/pages/mutasi_barang/print_surat.php?id_mutasi=${selectedMutasiId}`, '_blank');
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
    // Cek flash toast message dari create page
    const flashMsg = sessionStorage.getItem('flash_toast_msg');
    const flashType = sessionStorage.getItem('flash_toast_type') || 'success';
    if (flashMsg) {
        sessionStorage.removeItem('flash_toast_msg');
        sessionStorage.removeItem('flash_toast_type');
        showToast(flashMsg, flashType);
    }

    loadMutasi();
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
