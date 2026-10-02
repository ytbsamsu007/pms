<?php
/**
 * Halaman Laporan Barang Keluar (Mutasi Antar-Site)
 * Path: admin/pages/laporan/barang_keluar.php
 * Khusus Role: ADMIN, LOGISTIK, MANAGER, PURCHASING
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER, ROLE_PURCHASING]);
$pageTitle = 'Laporan Barang Keluar';
$pageHeading = 'Laporan Rekapitulasi Barang Keluar (Mutasi Site)';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Rekapitulasi Barang Keluar</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="loadReport()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" onclick="printReport()">
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
                        <input type="text" class="form-control border-start-0 ps-0" id="filterSearch" placeholder="Cari Kode, Nama Barang, No. Mutasi..." autocomplete="off" oninput="debounceSearch()" style="height: 38px;">
                    </div>
                </div>

                <!-- Site Asal -->
                <div style="width: 240px; max-width: 100%;">
                    <select class="form-select filter-select" id="filterSite" onchange="applyFilters()" style="height: 38px;">
                        <option value="0">Semua Site Asal (Pengirim)</option>
                    </select>
                </div>

                <!-- Range Tanggal: Dari -->
                <div style="width: 170px;">
                    <input type="date" class="form-control" id="filterStartDate" onchange="applyFilters()" title="Dari Tanggal" style="height: 38px;">
                </div>

                <!-- Range Tanggal: Sampai -->
                <div style="width: 170px;">
                    <input type="date" class="form-control" id="filterEndDate" onchange="applyFilters()" title="Sampai Tanggal" style="height: 38px;">
                </div>

                <!-- Tombol Reset -->
                <div>
                    <button type="button" class="btn btn-outline-secondary px-3" onclick="resetFilters()" title="Reset Filter" style="height: 38px;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="reportTable" style="min-width: 850px;">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 110px;">Tanggal</th>
                            <th style="min-width: 130px;">Kode Mutasi</th>
                            <th style="min-width: 250px;">Nama Barang</th>
                            <th style="min-width: 200px;">Rute</th>
                            <th style="min-width: 110px;" class="text-center">KTS</th>
                        </tr>
                    </thead>
                    <tbody id="reportBody">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat laporan barang keluar...
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

<style>
.filter-select {
    padding-top: 0.15rem !important;
    padding-bottom: 0.35rem !important;
    padding-right: 2.25rem !important;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    line-height: 1.5 !important;
}
</style>

<script>
let searchTimer = null;
let currentPage = 1;
const fixedLimit = 25;

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadReport();
    }, 300);
}

function applyFilters() {
    currentPage = 1;
    loadReport();
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '0';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    currentPage = 1;
    loadReport();
}

function goToPage(page) {
    currentPage = page;
    loadReport();
}

async function loadReport() {
    const tbody = document.getElementById('reportBody');
    const pagContainer = document.getElementById('paginationContainer');

    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4 text-muted align-middle">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat laporan barang keluar...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const idSite = document.getElementById('filterSite').value;
    const start = document.getElementById('filterStartDate').value;
    const end = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('limit', fixedLimit);
    if (search) params.append('search', search);
    if (idSite && idSite !== '0') params.append('id_site', idSite);
    if (start) params.append('start_date', start);
    if (end) params.append('end_date', end);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/laporan/barang_keluar.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            populateSiteOptions(json.data.sites || []);
            renderTable(json.data.items || [], json.data.pagination);
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
    const select = document.getElementById('filterSite');
    const currentVal = select.value;

    if (select.options.length <= 1) {
        sites.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id_site;
            opt.textContent = s.nama_site;
            select.appendChild(opt);
        });
    }
    select.value = currentVal;
}

function renderTable(items, pagination) {
    const tbody = document.getElementById('reportBody');
    const pagContainer = document.getElementById('paginationContainer');

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted align-middle">Tidak ada data barang keluar yang sesuai filter.</td></tr>`;
        pagContainer.style.display = 'none';
        return;
    }

    let html = '';
    const startIndex = pagination ? (pagination.from - 1) : 0;

    items.forEach((item, idx) => {
        html += `
            <tr class="align-middle">
                <!-- 1. No -->
                <td class="ps-3 text-center text-muted font-monospace small">${startIndex + idx + 1}</td>
                
                <!-- 2. Tanggal (Hanya tanggal) -->
                <td>
                    <div class="fw-medium text-dark">${escapeHtml(item.tanggal_formatted)}</div>
                </td>

                <!-- 3. Kode Mutasi -->
                <td>
                    <a href="<?= BASE_URL ?>/admin/pages/mutasi_barang/print_surat.php?id_mutasi=${item.id_mutasi}" target="_blank" class="font-monospace fw-bold text-primary text-decoration-none" title="Cetak Surat Keterangan Mutasi">
                        ${escapeHtml(item.kode_mutasi)}
                    </a>
                </td>

                <!-- 4. Nama Barang: kode_barang - nama_barang -->
                <td>
                    <span class="font-monospace fw-bold text-dark">${escapeHtml(item.kode_barang)}</span>
                    <span class="text-muted mx-1">-</span>
                    <span class="fw-semibold text-dark">${escapeHtml(item.nama_barang)}</span>
                    ${item.serial_number ? `<div class="text-muted small font-monospace">SN: ${escapeHtml(item.serial_number)}</div>` : ''}
                </td>

                <!-- 5. Rute: teks biasa -->
                <td>
                    <span class="text-dark fw-medium">${escapeHtml(item.nama_site_asal || '-')}</span>
                    <i class="bi bi-arrow-right mx-1 text-muted small"></i>
                    <span class="text-dark fw-medium">${escapeHtml(item.nama_site_tujuan || '-')}</span>
                </td>

                <!-- 6. KTS: X Satuan -->
                <td class="text-center font-monospace">
                    <span class="fw-bold text-dark">${item.qty_formatted}</span>
                    <span class="text-muted small ms-1">${escapeHtml(item.satuan || 'PCS')}</span>
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

function printReport() {
    const search = document.getElementById('filterSearch').value.trim();
    const idSite = document.getElementById('filterSite').value;
    const start = document.getElementById('filterStartDate').value;
    const end = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (idSite && idSite !== '0') params.append('id_site', idSite);
    if (start) params.append('start_date', start);
    if (end) params.append('end_date', end);

    const queryStr = params.toString();
    const targetUrl = `<?= BASE_URL ?>/admin/pages/laporan/print_barang_keluar.php` + (queryStr ? `?${queryStr}` : '');
    window.open(targetUrl, '_blank');
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
    loadReport();
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
