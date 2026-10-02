<?php
/**
 * Halaman Laporan Pembelian per Vendor
 * Path: admin/pages/laporan/pembelian_vendor.php
 * Khusus Role: ADMIN, FINANCE, MANAGER, PURCHASING
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING]);

$pageTitle = 'Laporan Pembelian per Vendor';
$pageHeading = 'Laporan Pembelian per Vendor';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Pembelian per Vendor</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadVendorReport()">
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
                <!-- Searchable Vendor Filter Dropdown -->
                <div class="position-relative" style="width: 320px; max-width: 100%;">
                    <input type="hidden" id="filterVendor" value="">
                    <div class="form-control filter-control d-flex align-items-center justify-content-between bg-white cursor-pointer px-3" 
                         id="vendorDropdownBtn" 
                         onclick="toggleVendorDropdown(event)" 
                         style="height: 38px; cursor: pointer; user-select: none; background-image: none;">
                        <span id="vendorDropdownLabel" class="text-truncate text-dark" style="max-width: calc(100% - 20px);">Semua Vendor</span>
                        <i class="bi bi-chevron-down text-muted small ms-1" id="vendorDropdownIcon"></i>
                    </div>
                    <div class="dropdown-menu shadow border p-2 w-100" 
                         id="vendorDropdownMenu" 
                         style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050; margin-top: 4px; max-height: 300px;">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0" id="searchVendorInput" placeholder="Cari nama / kode vendor..." onkeyup="filterVendorList(this.value)" autocomplete="off">
                        </div>
                        <div class="overflow-auto" id="vendorOptionsList" style="max-height: 210px;">
                            <!-- Options populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Tanggal Mulai -->
                <div style="width: 180px;">
                    <input type="date" class="form-control filter-control" id="filterStartDate" title="Tanggal Mulai" onchange="loadVendorReport()">
                </div>

                <!-- Tanggal Selesai -->
                <div style="width: 180px;">
                    <input type="date" class="form-control filter-control" id="filterEndDate" title="Tanggal Selesai" onchange="loadVendorReport()">
                </div>

                <!-- Reset Button (Icon Only) -->
                <div>
                    <button type="button" class="btn btn-outline-secondary filter-btn" title="Reset Filter" onclick="resetFilters()" style="width: 38px; height: 38px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableSummary">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 align-middle" style="width: 50px;">No</th>
                            <th class="py-3 align-middle">Nama Vendor</th>
                            <th class="text-end py-3 align-middle" style="width: 260px;">Total Terbayar</th>
                            <th class="text-center py-3 align-middle" style="width: 200px;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody">
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data pembelian per vendor...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold align-middle" id="summaryTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="2" class="ps-3 py-3 text-uppercase align-middle">Grand Total</td>
                            <td class="text-end py-3 align-middle text-primary fs-6" id="footTotal">Rp 0</td>
                            <td class="text-center py-3 align-middle text-muted small">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportData = null;
let allVendorsList = [];

function toggleVendorDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('vendorDropdownMenu');
    const isOpen = menu.style.display === 'block';
    
    if (isOpen) {
        closeVendorDropdown();
    } else {
        menu.style.display = 'block';
        document.getElementById('vendorDropdownBtn').classList.add('border-primary', 'shadow-sm');
        const sInput = document.getElementById('searchVendorInput');
        sInput.value = '';
        filterVendorList('');
        setTimeout(() => sInput.focus(), 50);
    }
}

function closeVendorDropdown() {
    const menu = document.getElementById('vendorDropdownMenu');
    if (menu) menu.style.display = 'none';
    const btn = document.getElementById('vendorDropdownBtn');
    if (btn) btn.classList.remove('border-primary', 'shadow-sm');
}

document.addEventListener('click', function(e) {
    const btn = document.getElementById('vendorDropdownBtn');
    const menu = document.getElementById('vendorDropdownMenu');
    if (menu && menu.style.display === 'block') {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            closeVendorDropdown();
        }
    }
});

function filterVendorList(query) {
    const q = (query || '').toLowerCase().trim();
    const listContainer = document.getElementById('vendorOptionsList');
    const currentVal = document.getElementById('filterVendor').value;
    
    let html = `
        <div class="dropdown-item py-2 px-2 rounded-2 text-truncate cursor-pointer ${currentVal === '' ? 'active fw-bold' : ''}" 
             onclick="selectVendor('', 'Semua Vendor')" 
             style="cursor: pointer; font-size: 0.875rem;" title="Semua Vendor">
            <i class="bi bi-people me-2"></i>Semua Vendor
        </div>
    `;
    
    let matchCount = 0;
    allVendorsList.forEach(v => {
        const text = (v.kode_vendor ? `[${v.kode_vendor}] ` : '') + (v.nama_perusahaan || '');
        if (!q || text.toLowerCase().includes(q)) {
            matchCount++;
            const isSel = String(v.id_vendor) === String(currentVal);
            html += `
                <div class="dropdown-item py-2 px-2 rounded-2 text-truncate cursor-pointer ${isSel ? 'active fw-bold' : ''}" 
                     onclick="selectVendor('${v.id_vendor}', '${escapeHtml(text)}')" 
                     style="cursor: pointer; font-size: 0.875rem;" title="${escapeHtml(text)}">
                    ${escapeHtml(text)}
                </div>
            `;
        }
    });
    
    if (matchCount === 0 && q !== '') {
        html += `<div class="p-2 text-muted text-center small">Tidak ada vendor yang cocok.</div>`;
    }
    
    listContainer.innerHTML = html;
}

function selectVendor(id, label) {
    document.getElementById('filterVendor').value = id;
    document.getElementById('vendorDropdownLabel').textContent = label;
    document.getElementById('vendorDropdownLabel').title = label;
    closeVendorDropdown();
    loadVendorReport();
}

function resetFilters() {
    document.getElementById('filterVendor').value = '';
    document.getElementById('vendorDropdownLabel').textContent = 'Semua Vendor';
    document.getElementById('vendorDropdownLabel').title = 'Semua Vendor';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    loadVendorReport();
}

function printReport() {
    const vendor = document.getElementById('filterVendor').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
        id_vendor: vendor
    });

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_pembelian_vendor.php?${params.toString()}`, '_blank');
}

async function loadVendorReport() {
    const sumTbody = document.getElementById('summaryTableBody');
    const sumTfoot = document.getElementById('summaryTableFoot');

    sumTbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted align-middle"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data rekapitulasi...</td></tr>`;
    sumTfoot.style.display = 'none';

    const vendor = document.getElementById('filterVendor').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
        id_vendor: vendor
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/pembelian_vendor.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            if (res.data.vendors_list && allVendorsList.length === 0) {
                allVendorsList = res.data.vendors_list;
                filterVendorList('');
            }
            renderSummaryTable(res.data.summary, res.data.grand_total);
        } else {
            sumTbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger align-middle">${res.message || 'Gagal memuat laporan.'}</td></tr>`;
        }
    } catch (err) {
        sumTbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function renderSummaryTable(summary, grand) {
    const tbody = document.getElementById('summaryTableBody');
    const tfoot = document.getElementById('summaryTableFoot');

    if (!summary || summary.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted align-middle">Tidak ada data pembelian per vendor pada periode/filter ini.</td></tr>`;
        tfoot.style.display = 'none';
        return;
    }

    let html = '';
    summary.forEach((item, idx) => {
        html += `
        <tr class="align-middle">
            <td class="ps-3 text-muted align-middle">${idx + 1}</td>
            <td class="fw-semibold text-dark align-middle">
                ${escapeHtml(item.nama_perusahaan || '-')}
            </td>
            <td class="text-end font-monospace fw-bold text-dark align-middle">${Number(item.total_terbayar || 0).toLocaleString('id-ID')}</td>
            <td class="text-center align-middle">
                <span class="badge bg-light text-secondary border font-monospace">${escapeHtml(item.keterangan || '-')}</span>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    if (grand) {
        document.getElementById('footTotal').textContent = 'Rp ' + Number(grand.total_terbayar || 0).toLocaleString('id-ID');
        tfoot.style.display = 'table-footer-group';
    }
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
    loadVendorReport();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
