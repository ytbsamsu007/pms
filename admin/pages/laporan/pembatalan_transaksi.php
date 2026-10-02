<?php
/**
 * Halaman Laporan Pembatalan Transaksi & Berita Acara Pembatalan (BAP)
 * Path: admin/pages/laporan/pembatalan_transaksi.php
 * Khusus Role: ADMIN, FINANCE, MANAGER, PURCHASING, LOGISTIK
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

$pageTitle = 'Laporan Pembatalan Transaksi';
$pageHeading = 'Laporan Pembatalan Transaksi & Berita Acara (BAP)';

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
            <h4 class="fw-bold text-dark mb-0">Laporan Pembatalan Transaksi</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadPembatalanReport()">
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
                <div style="min-width: 200px; flex: 1 1 200px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0" style="height: 38px;"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control filter-control border-start-0 ps-0" id="filterSearch" placeholder="Cari No BAP, No PO/Faktur..." onkeyup="debounceSearch(event)" style="height: 38px;">
                    </div>
                </div>

                <!-- Searchable Vendor Filter Dropdown -->
                <div class="position-relative" style="width: 300px; max-width: 100%;">
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

                <!-- Filter Jenis Dokumen -->
                <div style="width: 200px;">
                    <select class="form-select filter-select" id="filterJenis" onchange="loadPembatalanReport()" style="height: 38px;">
                        <option value="">Semua Dokumen</option>
                        <option value="PO">Purchase Order (PO)</option>
                        <option value="FAKTUR">Faktur PO</option>
                    </select>
                </div>

                <!-- Tanggal Mulai -->
                <div style="width: 160px;">
                    <input type="date" class="form-control filter-control" id="filterStartDate" title="Tanggal Batal Mulai" onchange="loadPembatalanReport()">
                </div>

                <!-- Tanggal Selesai -->
                <div style="width: 160px;">
                    <input type="date" class="form-control filter-control" id="filterEndDate" title="Tanggal Batal Selesai" onchange="loadPembatalanReport()">
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
                <table class="table table-hover align-middle mb-0" id="tablePembatalan">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 align-middle text-center" style="width: 45px;">No</th>
                            <th class="py-3 align-middle" style="width: 180px;">No. Dokumen BAP</th>
                            <th class="py-3 align-middle" style="width: 170px;">Dokumen Ref</th>
                            <th class="py-3 align-middle text-center" style="width: 150px;">State saat Batal</th>
                            <th class="py-3 align-middle">Vendor Rekanan</th>
                            <th class="text-end py-3 align-middle" style="width: 160px;">Nilai Transaksi</th>
                            <th class="text-center pe-3 py-3 align-middle" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pembatalanTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan pembatalan transaksi...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportData = null;
let allVendorsList = [];
let searchTimer = null;

function debounceSearch(e) {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        loadPembatalanReport();
    }, 400);
}

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
    loadPembatalanReport();
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterVendor').value = '';
    document.getElementById('vendorDropdownLabel').textContent = 'Semua Vendor';
    document.getElementById('vendorDropdownLabel').title = 'Semua Vendor';
    document.getElementById('filterJenis').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    loadPembatalanReport();
}

function printReport() {
    const search = document.getElementById('filterSearch').value.trim();
    const vendor = document.getElementById('filterVendor').value;
    const jenis = document.getElementById('filterJenis').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        search: search,
        start_date: startDate,
        end_date: endDate,
        jenis_dokumen: jenis,
        id_vendor: vendor
    });

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_pembatalan_transaksi.php?${params.toString()}`, '_blank');
}

async function loadPembatalanReport() {
    const tbody = document.getElementById('pembatalanTableBody');
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-muted align-middle"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan pembatalan...</td></tr>`;

    const search = document.getElementById('filterSearch').value.trim();
    const vendor = document.getElementById('filterVendor').value;
    const jenis = document.getElementById('filterJenis').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        search: search,
        start_date: startDate,
        end_date: endDate,
        jenis_dokumen: jenis,
        id_vendor: vendor
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/pembatalan.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            if (res.data.vendors_list && allVendorsList.length === 0) {
                allVendorsList = res.data.vendors_list;
                filterVendorList('');
            }
            renderMetrics(res.data.metrics);
            renderTable(res.data.items);
        } else {
            tbody.innerHTML = `<tr><td colspan="10" class="text-center py-4 text-danger align-middle">${res.message || 'Gagal memuat laporan.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function renderMetrics(m) {
    // Metrics display removed per layout request
}

function renderTable(items) {
    const tbody = document.getElementById('pembatalanTableBody');

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted align-middle"><i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>Tidak ada riwayat pembatalan transaksi pada filter ini.</td></tr>`;
        return;
    }

    let html = '';
    items.forEach((item, idx) => {
        const tgl = item.tanggal_batal ? item.tanggal_batal.replace(' ', ' <small class="text-muted">') + '</small>' : '-';
        const docBadge = item.jenis_dokumen === 'PO'
            ? '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2 py-1">PURCHASE ORDER</span>'
            : '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace px-2 py-1">FAKTUR PO</span>';

        html += `
        <tr class="align-middle">
            <td class="ps-3 text-center text-muted align-middle">${idx + 1}</td>
            <td class="align-middle">
                <div class="fw-bold font-monospace text-danger">${escapeHtml(item.nomor_bap)}</div>
            </td>
            <td class="align-middle">
                <div class="fw-bold font-monospace text-primary">${escapeHtml(item.nomor_referensi)}</div>
            </td>
            <td class="text-center align-middle">
                <span class="badge bg-secondary-subtle text-dark border font-monospace px-2 py-1" style="font-size: 0.75rem;">
                    ${escapeHtml(item.state_tahapan || '-')}
                </span>
            </td>
            <td class="align-middle">
                <div class="fw-semibold text-dark">${escapeHtml(item.nama_vendor || '-')}</div>
            </td>
            <td class="text-end font-monospace fw-bold text-dark align-middle">
                Rp ${Number(item.nilai_transaksi || 0).toLocaleString('id-ID')}
            </td>
            <td class="text-center pe-3 align-middle">
                <a href="<?= BASE_URL ?>/admin/pages/laporan/print_bap.php?type=${item.jenis_dokumen}&id=${item.id_referensi}" target="_blank" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" title="Lihat / Cetak Berita Acara Pembatalan (BAP)">
                    <i class="bi bi-printer-fill"></i>
                </a>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
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
    loadPembatalanReport();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
