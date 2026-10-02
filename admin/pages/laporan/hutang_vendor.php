<?php
/**
 * Halaman Laporan Hutang Vendor (Tergrup per Vendor dengan Rincian Faktur & Pembayaran)
 * Path: admin/pages/laporan/hutang_vendor.php
 * Khusus Role: ADMIN, FINANCE, MANAGER, PURCHASING, LOGISTIK
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

$pageTitle = 'Laporan Hutang Vendor';
$pageHeading = 'Laporan Hutang Vendor';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Hutang Vendor</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadHutangReport()">
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

                <!-- Status Faktur Filter -->
                <div style="min-width: 180px; flex: 1 1 180px;">
                    <select class="form-select filter-select" id="filterStatusFaktur" onchange="loadHutangReport()">
                        <option value="">Semua Status Faktur</option>
                        <option value="SUDAH">Sudah Difakturkan</option>
                        <option value="BELUM">Belum Difakturkan</option>
                    </select>
                </div>

                <!-- Start Date Filter -->
                <div style="min-width: 140px; flex: 1 1 140px;">
                    <input type="date" class="form-control filter-select" id="filterStartDate" placeholder="Tgl Mulai PO" onchange="loadHutangReport()">
                </div>

                <!-- End Date Filter -->
                <div style="min-width: 140px; flex: 1 1 140px;">
                    <input type="date" class="form-control filter-select" id="filterEndDate" placeholder="Tgl Selesai PO" onchange="loadHutangReport()">
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
                            <th class="py-3 align-middle" style="width: 140px;">Kode Vendor</th>
                            <th class="py-3 align-middle">Nama Vendor</th>
                            <th class="text-end py-3 align-middle" style="width: 160px;">Total Faktur</th>
                            <th class="text-end py-3 align-middle text-danger fw-bold" style="width: 170px;">Sisa Hutang</th>
                            <th class="text-center py-3 align-middle" style="width: 220px;">Keterangan</th>
                            <th class="text-center pe-3 py-3 align-middle" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data hutang vendor...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold align-middle" id="summaryTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="3" class="ps-3 py-3 text-uppercase align-middle">Grand Total</td>
                            <td class="text-end py-3 align-middle font-monospace" id="footTotalFaktur">0</td>
                            <td class="text-end py-3 align-middle text-danger fs-6 font-monospace" id="footSisaHutang">Rp 0</td>
                            <td colspan="2" class="pe-3 py-3 align-middle"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RIWAYAT PEMBAYARAN -->
<div class="modal fade" id="modalBreakdownHutang" tabindex="-1" aria-labelledby="modalBreakdownTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark mb-0" id="modalBreakdownTitle">Riwayat Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase align-middle">
                                <th class="ps-3 py-2 align-middle" style="width: 40px;">No</th>
                                <th class="py-2 align-middle" style="width: 140px;">Kode Bayar</th>
                                <th class="py-2 align-middle" style="width: 130px;">Tgl Bayar</th>
                                <th class="py-2 align-middle" style="width: 150px;">No. Faktur / PO</th>
                                <th class="py-2 align-middle">Bank / Akun Pengirim</th>
                                <th class="py-2 align-middle">Bank Tujuan</th>
                                <th class="text-end py-2 align-middle" style="width: 140px;">Nominal Bayar</th>
                                <th class="pe-3 py-2 align-middle" style="width: 120px;">No. Ref</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBayarBody">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportData = null;
let currentVendorsMap = {};
let allVendorsList = [];
let breakdownModalInstance = null;

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
    loadHutangReport();
}

function resetFilters() {
    document.getElementById('filterVendor').value = '';
    document.getElementById('vendorDropdownLabel').textContent = 'Semua Vendor';
    document.getElementById('vendorDropdownLabel').title = 'Semua Vendor';
    document.getElementById('filterStatusFaktur').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    closeVendorDropdown();
    loadHutangReport();
}

function printReport(singleVendorId = null) {
    const vendor = singleVendorId !== null ? singleVendorId : document.getElementById('filterVendor').value;
    const statusFaktur = document.getElementById('filterStatusFaktur').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        id_vendor: vendor,
        status_faktur: statusFaktur,
        start_date: startDate,
        end_date: endDate
    });

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_hutang_vendor.php?${params.toString()}`, '_blank');
}

async function loadHutangReport() {
    const sumTbody = document.getElementById('summaryTableBody');
    const sumTfoot = document.getElementById('summaryTableFoot');

    sumTbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted align-middle"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data hutang vendor...</td></tr>`;
    sumTfoot.style.display = 'none';

    const vendor = document.getElementById('filterVendor').value;
    const statusFaktur = document.getElementById('filterStatusFaktur').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        id_vendor: vendor,
        status_faktur: statusFaktur,
        start_date: startDate,
        end_date: endDate
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/hutang_vendor.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            currentVendorsMap = {};
            
            if (res.data.summary_vendor) {
                res.data.summary_vendor.forEach(v => {
                    currentVendorsMap[v.id_vendor] = v;
                });
            }

            populateVendorDropdown(res.data.vendors_list, vendor);
            renderSummaryTable(res.data.summary_vendor, res.data.grand_total);
        } else {
            sumTbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger align-middle">${res.message || 'Gagal memuat laporan.'}</td></tr>`;
        }
    } catch (err) {
        sumTbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
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

function renderSummaryTable(vendors, grand) {
    const tbody = document.getElementById('summaryTableBody');
    const tfoot = document.getElementById('summaryTableFoot');

    if (!vendors || vendors.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted align-middle">Tidak ada data hutang vendor pada filter ini.</td></tr>`;
        tfoot.style.display = 'none';
        return;
    }

    let html = '';
    vendors.forEach((item, idx) => {
        const countSudah = item.count_sudah_faktur || 0;
        const countBelum = item.count_belum_faktur || 0;

        let ketBadgesHtml = '<div class="d-flex justify-content-center flex-wrap gap-1">';
        if (countSudah > 0) {
            ketBadgesHtml += `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small fw-semibold">
                <i class="bi bi-check-circle-fill me-1"></i>${countSudah} Difakturkan
            </span>`;
        }
        if (countBelum > 0) {
            ketBadgesHtml += `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small fw-semibold">
                <i class="bi bi-hourglass-split me-1"></i>${countBelum} Belum Difakturkan
            </span>`;
        }
        if (countSudah === 0 && countBelum === 0) {
            ketBadgesHtml += `<span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded-pill small">-</span>`;
        }
        ketBadgesHtml += '</div>';

        html += `
        <tr class="align-middle">
            <td class="ps-3 text-muted align-middle">${idx + 1}</td>
            <td class="font-monospace fw-semibold text-secondary align-middle">${escapeHtml(item.kode_vendor || '-')}</td>
            <td class="fw-semibold text-dark align-middle">
                ${escapeHtml(item.nama_vendor || '-')}
            </td>
            <td class="text-end font-monospace align-middle">${item.formatted_total_faktur}</td>
            <td class="text-end font-monospace fw-bold text-danger align-middle">${item.formatted_sisa_hutang}</td>
            <td class="text-center align-middle">
                ${ketBadgesHtml}
            </td>
            <td class="text-center pe-3 align-middle">
                <button type="button" class="btn btn-sm btn-outline-primary" title="Lihat Rincian" onclick="showBreakdown(${item.id_vendor})" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-eye-fill"></i>
                </button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    if (grand) {
        document.getElementById('footTotalFaktur').textContent = grand.formatted_total_faktur || '0';
        document.getElementById('footSisaHutang').textContent = grand.formatted_sisa_hutang || 'Rp 0';
        tfoot.style.display = 'table-footer-group';
    }
}

function showBreakdown(vendorId) {
    const vendor = currentVendorsMap[vendorId];
    if (!vendor) return;

    document.getElementById('modalBreakdownTitle').textContent = `Riwayat Pembayaran: ${vendor.nama_vendor || '-'}`;

    // Render Riwayat Pembayaran
    const bayarTbody = document.getElementById('modalTableBayarBody');
    const payments = vendor.payments || [];

    if (payments.length === 0) {
        bayarTbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Belum ada riwayat pembayaran untuk vendor ini.</td></tr>`;
    } else {
        let bayarHtml = '';
        payments.forEach((p, idx) => {
            bayarHtml += `
            <tr class="align-middle">
                <td class="ps-3 text-muted small">${idx + 1}</td>
                <td class="font-monospace small fw-semibold text-primary">${escapeHtml(p.kode_pembayaran || '-')}</td>
                <td class="text-muted small">${p.formatted_tanggal_bayar || '-'}</td>
                <td class="font-monospace small">${escapeHtml(p.nomor_faktur || p.nomor_po || '-')}</td>
                <td class="small">${escapeHtml(p.bank_pengirim || '-')}${p.norek_pengirim ? ' (' + escapeHtml(p.norek_pengirim) + ')' : ''}</td>
                <td class="small">${escapeHtml(p.bank_tujuan || '-')}${p.norek_tujuan ? ' (' + escapeHtml(p.norek_tujuan) + ')' : ''}</td>
                <td class="text-end font-monospace small fw-bold text-success">${p.formatted_nominal}</td>
                <td class="pe-3 small text-muted font-monospace">${escapeHtml(p.no_ref || '-')}</td>
            </tr>`;
        });
        bayarTbody.innerHTML = bayarHtml;
    }

    // Show Modal
    if (!breakdownModalInstance) {
        breakdownModalInstance = new bootstrap.Modal(document.getElementById('modalBreakdownHutang'));
    }
    breakdownModalInstance.show();
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
    loadHutangReport();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
