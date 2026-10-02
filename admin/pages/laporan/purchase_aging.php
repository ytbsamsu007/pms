<?php
/**
 * Halaman Laporan Purchase Order Aging (PO Aging)
 * Path: admin/pages/laporan/purchase_aging.php
 * Khusus Role: ADMIN, FINANCE, MANAGER, PURCHASING, LOGISTIK
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

$pageTitle = 'Laporan Purchase Aging';
$pageHeading = 'Laporan Purchase Aging';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Purchase Aging</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadAgingReport()">
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
                            <input type="text" class="form-control border-start-0" id="searchVendorInput" placeholder="Ketik nama / kode vendor..." onkeyup="filterVendorList(this.value)" autocomplete="off">
                        </div>
                        <div class="overflow-auto" id="vendorOptionsList" style="max-height: 210px;">
                            <!-- Options populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Status PO Filter -->
                <div style="min-width: 180px; flex: 1 1 180px;">
                    <select class="form-select filter-select" id="filterStatus" onchange="loadAgingReport()">
                        <option value="">Semua Status PO</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="REVIEW INTERNAL">REVIEW INTERNAL</option>
                        <option value="DISETUJUI INTERNAL">DISETUJUI INTERNAL</option>
                        <option value="TIDAK DISETUJUI INTERNAL">TIDAK DISETUJUI INTERNAL</option>
                        <option value="REVIEW VENDOR">REVIEW VENDOR</option>
                        <option value="DIPROSES VENDOR">DIPROSES VENDOR</option>
                        <option value="BATAL">BATAL</option>
                    </select>
                </div>

                <!-- Aging Range Filter -->
                <div style="min-width: 170px; flex: 1 1 170px;">
                    <select class="form-select filter-select" id="filterAgingRange" onchange="loadAgingReport()">
                        <option value="">Semua Umur PO</option>
                        <option value="1-7">0 - 7 Hari</option>
                        <option value="8-14">8 - 14 Hari</option>
                        <option value="15-30">15 - 30 Hari</option>
                        <option value=">30">> 30 Hari</option>
                    </select>
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
                            <th class="py-3 align-middle" style="width: 140px;">Nomor PO</th>
                            <th class="py-3 align-middle" style="width: 110px;">Tgl PO</th>
                            <th class="py-3 align-middle">Nama Vendor</th>
                            <th class="py-3 align-middle" style="width: 160px;">Pembuat PO</th>
                            <th class="text-center py-3 align-middle" style="width: 170px;">Status</th>
                            <th class="py-3 align-middle" style="width: 150px;">Tgl Update</th>
                            <th class="text-center pe-3 py-3 align-middle" style="width: 130px;">Umur PO</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data purchase aging...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold align-middle" id="summaryTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="7" class="ps-3 py-3 text-uppercase align-middle">Total Outstanding PO</td>
                            <td class="text-center pe-3 py-3 align-middle text-primary fs-6 font-monospace" id="footTotalPo">0 PO</td>
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
    loadAgingReport();
}

function resetFilters() {
    document.getElementById('filterVendor').value = '';
    document.getElementById('vendorDropdownLabel').textContent = 'Semua Vendor';
    document.getElementById('vendorDropdownLabel').title = 'Semua Vendor';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterAgingRange').value = '';
    closeVendorDropdown();
    loadAgingReport();
}

function printReport() {
    const vendor = document.getElementById('filterVendor').value;
    const status = document.getElementById('filterStatus').value;
    const aging = document.getElementById('filterAgingRange').value;

    const params = new URLSearchParams({
        id_vendor: vendor,
        status: status,
        aging_range: aging
    });

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_purchase_aging.php?${params.toString()}`, '_blank');
}

async function loadAgingReport() {
    const sumTbody = document.getElementById('summaryTableBody');
    const sumTfoot = document.getElementById('summaryTableFoot');

    sumTbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted align-middle"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data purchase aging...</td></tr>`;
    sumTfoot.style.display = 'none';

    const vendor = document.getElementById('filterVendor').value;
    const status = document.getElementById('filterStatus').value;
    const aging = document.getElementById('filterAgingRange').value;

    const params = new URLSearchParams({
        id_vendor: vendor,
        status: status,
        aging_range: aging
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/purchase_aging.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            populateVendorDropdown(res.data.vendors_list, vendor);
            renderSummaryTable(res.data.summary, res.data.grand_total);
        } else {
            sumTbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">${res.message || 'Gagal memuat laporan.'}</td></tr>`;
        }
    } catch (err) {
        sumTbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function populateVendorDropdown(vendors, currentSelected) {
    if (!vendors) return;
    allVendorsList = vendors;

    let selectedLabel = 'Semua Vendor';
    if (currentSelected) {
        const found = vendors.find(v => String(v.id_vendor) === String(currentSelected));
        if (found) {
            selectedLabel = (found.kode_vendor ? `[${found.kode_vendor}] ` : '') + found.nama_perusahaan;
        }
    }
    document.getElementById('vendorDropdownLabel').textContent = selectedLabel;
    document.getElementById('vendorDropdownLabel').title = selectedLabel;
    document.getElementById('filterVendor').value = currentSelected || '';
}

function getStatusBadge(status) {
    switch (status) {
        case 'DISETUJUI INTERNAL':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-semibold">DISETUJUI INTERNAL</span>';
        case 'REVIEW INTERNAL':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small fw-semibold">REVIEW INTERNAL</span>';
        case 'REVIEW VENDOR':
        case 'DIPROSES VENDOR':
            return `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 rounded-pill small fw-semibold">${escapeHtml(status)}</span>`;
        case 'TIDAK DISETUJUI INTERNAL':
        case 'BATAL':
            return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill small fw-semibold">${escapeHtml(status)}</span>`;
        default:
            return `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 rounded-pill small fw-semibold">${escapeHtml(status || '-')}</span>`;
    }
}

function getAgingBadge(umur) {
    const u = parseInt(umur, 10) || 0;
    if (u <= 7) {
        return `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small font-monospace fw-semibold">${u} Hari</span>`;
    } else if (u <= 14) {
        return `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small font-monospace fw-semibold">${u} Hari</span>`;
    } else {
        return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill small font-monospace fw-semibold">${u} Hari</span>`;
    }
}

function renderSummaryTable(summary, grand) {
    const tbody = document.getElementById('summaryTableBody');
    const tfoot = document.getElementById('summaryTableFoot');

    if (!summary || summary.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted align-middle">Tidak ada data Purchase Aging pada filter ini.</td></tr>`;
        tfoot.style.display = 'none';
        return;
    }

    let html = '';
    summary.forEach((item, idx) => {
        html += `
        <tr class="align-middle">
            <td class="ps-3 text-muted align-middle">${idx + 1}</td>
            <td class="font-monospace fw-semibold text-primary align-middle">${escapeHtml(item.nomor_po || '-')}</td>
            <td class="text-muted small align-middle">${item.formatted_tanggal_po || '-'}</td>
            <td class="fw-semibold text-dark align-middle">${escapeHtml(item.nama_perusahaan || '-')}</td>
            <td class="small align-middle">${escapeHtml(item.pembuat_po || '-')}</td>
            <td class="text-center align-middle">${getStatusBadge(item.status)}</td>
            <td class="text-muted small align-middle">${item.formatted_tanggal_update || '-'}</td>
            <td class="text-center pe-3 align-middle">${getAgingBadge(item.umur_po)}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    if (grand) {
        document.getElementById('footTotalPo').textContent = (grand.total_po || 0) + ' PO';
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
    loadAgingReport();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
