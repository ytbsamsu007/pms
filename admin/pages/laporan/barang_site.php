<?php
/**
 * Halaman Laporan Data Barang Berdasarkan Site
 * Path: admin/pages/laporan/barang_site.php
 * Khusus Role: ADMIN, LOGISTIK, PURCHASING, FINANCE, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_FINANCE, ROLE_MANAGER]);

$pageTitle = 'Laporan Data Barang per Site';
$pageHeading = 'Laporan Data Barang Berdasarkan Site';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
.filter-select {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    padding-right: 2.25rem !important;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Data Barang per Site</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="goToPage(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
            <button type="button" class="btn btn-primary filter-btn px-3 shadow-sm fw-semibold" onclick="printReport()">
                <i class="bi bi-printer-fill me-1"></i> Cetak
            </button>
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Search Box -->
                <div style="min-width: 220px; flex: 1 1 220px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0" style="height: 38px;"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control filter-control border-start-0 ps-0" id="filterSearch" placeholder="Cari Kode, Nama Barang, Deskripsi..." onkeyup="debounceSearch()" style="height: 38px;">
                    </div>
                </div>

                <!-- Filter Site -->
                <div style="width: 240px; max-width: 100%;">
                    <select class="form-select filter-select" id="filterSite" onchange="goToPage(1)" style="height: 38px;">
                        <option value="0">Semua Lokasi Site</option>
                    </select>
                </div>

                <!-- Filter Jenis Barang -->
                <div style="width: 170px;">
                    <select class="form-select filter-select" id="filterJenis" onchange="goToPage(1)" style="height: 38px;">
                        <option value="">Semua Jenis</option>
                        <option value="1">Persediaan</option>
                        <option value="0">Jasa</option>
                    </select>
                </div>

                <!-- Filter Asset -->
                <div style="width: 160px;">
                    <select class="form-select filter-select" id="filterAsset" onchange="goToPage(1)" style="height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="1">Aset (Ya)</option>
                        <option value="0">Bukan Aset (Tidak)</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <div>
                    <button type="button" class="btn btn-outline-secondary filter-btn" title="Reset Filter" onclick="resetFilters()" style="width: 38px; height: 38px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableBarangSite">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 align-middle text-center" style="width: 45px;">No</th>
                            <th class="py-3 align-middle" style="width: 120px;">Kode</th>
                            <th class="py-3 align-middle">Nama Barang &amp; Deskripsi</th>
                            <th class="py-3 align-middle" style="width: 220px;">Lokasi Site</th>
                            <th class="py-3 align-middle text-center" style="width: 110px;">Jenis</th>
                            <th class="py-3 align-middle text-center" style="width: 90px;">Aset</th>
                            <th class="text-end py-3 align-middle" style="width: 140px;">Stok Fisik</th>
                            <th class="text-center pe-3 py-3 align-middle" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="barangSiteTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data barang per site...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold align-middle" id="barangSiteTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="6" class="ps-3 py-3 text-uppercase align-middle">Total Kuantitas Stok (Halaman Ini)</td>
                            <td class="text-end py-3 align-middle font-monospace text-success fs-6" id="footTotalStok">0</td>
                            <td class="pe-3 py-3 text-center">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Pagination Footer (Sesuai Standar Halaman Project) -->
        <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top" id="paginationContainer" style="display: none;">
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

<!-- MODAL DETAIL LENGKAP BARANG (Sama seperti gaya pop-up di admin/pages/barang/index.php) -->
<div class="modal fade" id="modalDetailBarang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- Header Modal Modern dengan Status & Close Button di Kanan Atas -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam-fill text-primary fs-5"></i>
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalHeaderKodeBarang">
                            BRG-XXXX
                        </h5>
                    </div>
                    <!-- Status & Jenis di sebelah tombol X -->
                    <div class="d-flex align-items-center gap-2">
                        <span id="modalHeaderJenisBadge">-</span>
                        <span id="modalHeaderAssetBadge">-</span>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Nav Tabs Modal Detail -->
                <ul class="nav nav-tabs border-bottom-0" id="barangDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="bdetail-tab-utama" data-bs-toggle="tab" data-bs-target="#bdetail-pane-utama" type="button" role="tab">
                            <i class="bi bi-tag-fill me-1 text-primary"></i> Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="bdetail-tab-stok" data-bs-toggle="tab" data-bs-target="#bdetail-pane-stok" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> Stok Site
                            <span class="badge bg-primary text-white ms-1" id="modalTabStokBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="bdetail-tab-tambahan" data-bs-toggle="tab" data-bs-target="#bdetail-pane-tambahan" type="button" role="tab">
                            <i class="bi bi-sliders me-1 text-primary"></i> Spesifikasi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="bdetail-tab-foto" data-bs-toggle="tab" data-bs-target="#bdetail-pane-foto" type="button" role="tab">
                            <i class="bi bi-images me-1 text-primary"></i> Foto
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="barangDetailTabContent">
                    
                    <!-- TAB 1: INFORMASI UTAMA -->
                    <div class="tab-pane fade show active" id="bdetail-pane-utama" role="tabpanel">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Kode Barang:</span>
                                        <strong class="text-primary font-monospace fs-6" id="modalKodeBarang">-</strong>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Nama Barang / Material:</span>
                                        <strong class="text-dark fs-6 d-block" id="modalNamaBarang">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Satuan Pengadaan:</span>
                                        <span id="modalSatuanBarang">-</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Lokasi Site Terpilih:</span>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <strong class="text-dark fs-6" id="modalNamaSite">-</strong>
                                            <span class="badge bg-info-subtle text-info font-monospace" id="modalJenisSite">-</span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Kuantitas Stok:</span>
                                        <strong class="text-success font-monospace fs-5" id="modalStokJumlah">0</strong>
                                        <span class="text-dark fw-bold ms-1" id="modalStokSatuan">PCS</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">ID Database Barang:</span>
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace" id="modalIdBarang">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: STOK SITE DETAIL -->
                    <div class="tab-pane fade" id="bdetail-pane-stok" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small">Rincian Persediaan Stok pada Site</span>
                            <span class="badge bg-primary" id="modalTotalStokBadge">Stok: 0 PCS</span>
                        </div>
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th>Lokasi Site / Workshop</th>
                                        <th>Jenis Site</th>
                                        <th class="text-end">Jumlah Stok Fisik</th>
                                    </tr>
                                </thead>
                                <tbody id="modalStokSiteList">
                                    <!-- Dynamic Stok List -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: SPESIFIKASI & TAMBAHAN -->
                    <div class="tab-pane fade" id="bdetail-pane-tambahan" role="tabpanel">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Klasifikasi Asset:</span>
                                    <div id="modalAssetDetail">-</div>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Serial Number / Part No.:</span>
                                    <strong class="font-monospace text-dark" id="modalSerialBarang">-</strong>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Jenis Kategori:</span>
                                    <div id="modalJenisDetail">-</div>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Satuan:</span>
                                    <strong class="text-dark font-monospace" id="modalSatuanDetail">-</strong>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted small d-block">Deskripsi &amp; Spesifikasi Teknis Material:</span>
                                    <div class="p-3 bg-white rounded border text-dark small mt-1" id="modalDeskripsiBarang">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: FOTO -->
                    <div class="tab-pane fade" id="bdetail-pane-foto" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border rounded-3 p-3 bg-light text-center h-100">
                                    <span class="text-muted small fw-bold text-uppercase d-block mb-2">Foto 1 (Utama)</span>
                                    <div class="d-flex align-items-center justify-content-center" style="min-height: 180px;" id="modalFoto1Container">
                                        <span class="text-muted small">Tidak ada foto</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border rounded-3 p-3 bg-light text-center h-100">
                                    <span class="text-muted small fw-bold text-uppercase d-block mb-2">Foto 2 (Detail)</span>
                                    <div class="d-flex align-items-center justify-content-center" style="min-height: 180px;" id="modalFoto2Container">
                                        <span class="text-muted small">Tidak ada foto</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportData = null;
let searchTimer = null;
let currentPage = 1;
const fixedLimit = 25;

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadBarangSiteReport();
    }, 300);
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '0';
    document.getElementById('filterJenis').value = '';
    document.getElementById('filterAsset').value = '';
    currentPage = 1;
    loadBarangSiteReport();
}

function goToPage(page) {
    currentPage = page;
    loadBarangSiteReport();
}

async function loadBarangSiteReport() {
    const tbody = document.getElementById('barangSiteTableBody');
    const tfoot = document.getElementById('barangSiteTableFoot');
    const pagContainer = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4 text-muted align-middle">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data barang per site...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const idSite = document.getElementById('filterSite').value;
    const jenis = document.getElementById('filterJenis').value;
    const asset = document.getElementById('filterAsset').value;

    const params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('limit', fixedLimit);
    if (search) params.append('search', search);
    if (idSite && idSite !== '0') params.append('id_site', idSite);
    if (jenis !== '') params.append('jenis', jenis);
    if (asset !== '') params.append('asset', asset);

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/barang_site.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            renderSiteOptions(res.data.sites || []);
            renderTable(res.data.items || [], res.data.pagination);
            renderPaginationControls(res.data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">${res.message || 'Gagal memuat data.'}</td></tr>`;
            tfoot.style.display = 'none';
            pagContainer.style.display = 'none';
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
        tfoot.style.display = 'none';
        pagContainer.style.display = 'none';
    }
}

function renderSiteOptions(sites) {
    const select = document.getElementById('filterSite');
    const currentVal = select.value;
    
    // Only populate if only has 1 default option
    if (select.options.length <= 1) {
        sites.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id_site;
            opt.textContent = s.nama_site;
            opt.title = `${s.nama_site} (${s.jenis_site || 'Site'})`;
            select.appendChild(opt);
        });
    }
    select.value = currentVal;
}

function renderTable(items, pagination) {
    const tbody = document.getElementById('barangSiteTableBody');
    const tfoot = document.getElementById('barangSiteTableFoot');
    const pagContainer = document.getElementById('paginationContainer');

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted align-middle">Tidak ada data barang yang sesuai dengan filter.</td></tr>`;
        tfoot.style.display = 'none';
        pagContainer.style.display = 'none';
        return;
    }

    let html = '';
    let pageTotalStok = 0;
    const startIndex = pagination ? (pagination.from - 1) : 0;

    items.forEach((item, idx) => {
        pageTotalStok += (parseFloat(item.stok) || 0);

        const jenisBadge = item.jenis_barang === 'Persediaan'
            ? '<span class="badge bg-primary-subtle text-primary font-monospace">Persediaan</span>'
            : '<span class="badge bg-secondary-subtle text-secondary font-monospace">Jasa</span>';

        const assetBadge = item.asset === 'Ya'
            ? '<span class="badge bg-warning-subtle text-warning font-monospace"><i class="bi bi-check-circle me-1"></i>Aset</span>'
            : '<span class="badge bg-light text-muted border font-monospace">Bukan</span>';

        html += `
            <tr class="align-middle">
                <td class="ps-3 text-center text-muted font-monospace small">${startIndex + idx + 1}</td>
                <td class="font-monospace fw-bold text-dark">${escapeHtml(item.kode_barang)}</td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(item.nama_barang)}</div>
                    ${item.deskripsi ? `<div class="text-muted small text-truncate" style="max-width: 380px;">${escapeHtml(item.deskripsi)}</div>` : ''}
                </td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(item.nama_site)}</div>
                </td>
                <td class="text-center">${jenisBadge}</td>
                <td class="text-center">${assetBadge}</td>
                <td class="text-end font-monospace">
                    <strong class="text-dark fs-6">${Number(item.stok || 0).toLocaleString('id-ID')}</strong>
                    <span class="text-muted small ms-1">${escapeHtml(item.satuan || 'PCS')}</span>
                </td>
                <td class="pe-3 text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary p-1 px-2" onclick="showDetailModal(${idx})" title="Lihat Detail & Foto">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('footTotalStok').textContent = pageTotalStok.toLocaleString('id-ID');
    tfoot.style.display = 'table-footer-group';
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
    
    // Tombol Prev
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
    
    // Tombol Next
    html += `
        <li class="page-item ${pag.page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page + 1})">Next &raquo;</a>
        </li>
    `;
    
    controls.innerHTML = html;
}

function showDetailModal(idx) {
    if (!currentReportData || !currentReportData.items || !currentReportData.items[idx]) return;
    const item = currentReportData.items[idx];

    // Header Modal
    document.getElementById('modalHeaderKodeBarang').textContent = item.kode_barang || 'BRG-DETAIL';
    document.getElementById('modalHeaderJenisBadge').innerHTML = `<span class="badge ${item.jenis_barang === 'Persediaan' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border'} px-2 py-1">${item.jenis_barang}</span>`;
    document.getElementById('modalHeaderAssetBadge').innerHTML = `<span class="badge ${item.asset === 'Ya' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted border'} px-2 py-1">${item.asset === 'Ya' ? 'Aset' : 'Bukan Aset'}</span>`;

    // Tab 1: Utama
    document.getElementById('modalKodeBarang').textContent = item.kode_barang || '-';
    document.getElementById('modalNamaBarang').textContent = item.nama_barang || '-';
    document.getElementById('modalSatuanBarang').innerHTML = `<span class="badge bg-secondary-subtle text-secondary font-monospace border">${item.satuan || 'PCS'}</span>`;
    document.getElementById('modalNamaSite').textContent = item.nama_site || '-';
    document.getElementById('modalJenisSite').textContent = item.jenis_site || 'Site';
    document.getElementById('modalStokJumlah').textContent = Number(item.stok || 0).toLocaleString('id-ID');
    document.getElementById('modalStokSatuan').textContent = item.satuan || 'PCS';
    document.getElementById('modalIdBarang').textContent = `ID: ${item.id_barang}`;

    // Tab 2: Stok Site
    const stokList = document.getElementById('modalStokSiteList');
    document.getElementById('modalTabStokBadge').textContent = `${Number(item.stok || 0).toLocaleString('id-ID')} ${item.satuan || 'PCS'}`;
    document.getElementById('modalTotalStokBadge').textContent = `Stok: ${Number(item.stok || 0).toLocaleString('id-ID')} ${item.satuan || 'PCS'}`;
    
    stokList.innerHTML = `
        <tr>
            <td class="fw-semibold"><i class="bi bi-geo-alt me-1 text-primary"></i>${escapeHtml(item.nama_site)}</td>
            <td><span class="badge bg-info-subtle text-info border font-monospace">${escapeHtml(item.jenis_site || 'Site')}</span></td>
            <td class="text-end font-monospace fw-bold text-primary">${Number(item.stok || 0).toLocaleString('id-ID')} ${escapeHtml(item.satuan || 'PCS')}</td>
        </tr>
    `;

    // Tab 3: Spesifikasi & Tambahan
    document.getElementById('modalAssetDetail').innerHTML = `<span class="badge ${item.asset === 'Ya' ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-secondary-subtle text-secondary border'}">${item.asset === 'Ya' ? 'Ya (Aset Perusahaan)' : 'Tidak (Bukan Aset)'}</span>`;
    document.getElementById('modalSerialBarang').textContent = item.serial_number || '-';
    document.getElementById('modalJenisDetail').innerHTML = `<span class="badge bg-primary-subtle text-primary border">${item.jenis_barang}</span>`;
    document.getElementById('modalSatuanDetail').textContent = item.satuan || 'PCS';
    document.getElementById('modalDeskripsiBarang').textContent = item.deskripsi || 'Tidak ada deskripsi atau catatan khusus.';

    // Tab 4: Foto
    const f1Cont = document.getElementById('modalFoto1Container');
    if (item.foto1_url) {
        f1Cont.innerHTML = `<a href="${item.foto1_url}" target="_blank"><img src="${item.foto1_url}" alt="Foto 1" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;"></a>`;
    } else {
        f1Cont.innerHTML = `<span class="text-muted small"><i class="bi bi-image me-1"></i>Tidak ada Foto 1</span>`;
    }

    const f2Cont = document.getElementById('modalFoto2Container');
    if (item.foto2_url) {
        f2Cont.innerHTML = `<a href="${item.foto2_url}" target="_blank"><img src="${item.foto2_url}" alt="Foto 2" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;"></a>`;
    } else {
        f2Cont.innerHTML = `<span class="text-muted small"><i class="bi bi-image me-1"></i>Tidak ada Foto 2</span>`;
    }

    // Reset ke tab pertama (Utama)
    const tabTrigger = document.querySelector('#bdetail-tab-utama');
    if (tabTrigger) {
        const tab = bootstrap.Tab.getInstance(tabTrigger) || new bootstrap.Tab(tabTrigger);
        tab.show();
    }

    const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetailBarang')) || new bootstrap.Modal(document.getElementById('modalDetailBarang'));
    modal.show();
}

function printReport() {
    const search = document.getElementById('filterSearch').value.trim();
    const idSite = document.getElementById('filterSite').value;
    const jenis = document.getElementById('filterJenis').value;
    const asset = document.getElementById('filterAsset').value;

    const params = new URLSearchParams();
    params.append('limit', '0'); // Ambil seluruh data untuk dicetak
    if (search) params.append('search', search);
    if (idSite && idSite !== '0') params.append('id_site', idSite);
    if (jenis !== '') params.append('jenis', jenis);
    if (asset !== '') params.append('asset', asset);

    const printUrl = `<?= BASE_URL ?>/admin/pages/laporan/print_barang_site.php?${params.toString()}`;
    window.open(printUrl, '_blank');
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
    goToPage(1);
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
