<?php
/**
 * Halaman Laporan Pajak Masukan (PPN & PPnBM Faktur Pembelian)
 * Path: admin/pages/laporan/pajak_masukan.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$pageTitle = 'Laporan Pajak Masukan';
$pageHeading = 'Laporan Pajak Masukan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Pajak Masukan</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadPajakReport()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
            <button type="button" class="btn btn-outline-primary filter-btn px-3 shadow-sm fw-semibold" onclick="openModalPilihTahunRekap()">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Cetak Rekapitulasi
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
                <!-- Filter Tahun (Dibuat otomatis dari 5 tahun lalu hingga 10 tahun ke depan) -->
                <div style="min-width: 130px;">
                    <select class="form-select filter-select" id="filterTahun" onchange="loadPajakReport(1)">
                        <?php
                        $curYr = (int)date('Y');
                        for ($y = $curYr - 5; $y <= $curYr + 10; $y++) {
                            $sel = ($y === $curYr) ? 'selected' : '';
                            echo "<option value=\"{$y}\" {$sel}>{$y}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div style="min-width: 160px;">
                    <select class="form-select filter-select" id="filterBulan" onchange="loadPajakReport(1)">
                        <option value="0">Semua Bulan</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>

                <!-- Searchable Vendor Filter Dropdown -->
                <div class="position-relative" style="min-width: 260px; flex: 1 1 260px;">
                    <input type="hidden" id="filterVendor" value="0">
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
                            <input type="text" class="form-control border-start-0" id="searchVendorInput" placeholder="Cari nama vendor..." onkeyup="filterVendorList(this.value)" autocomplete="off">
                        </div>
                        <div class="overflow-auto" id="vendorOptionsList" style="max-height: 210px;">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
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
                <table class="table table-hover align-middle mb-0" id="tablePajakMasukan">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-2 align-middle text-center" style="width: 50px;">No</th>
                            <th class="py-2 align-middle" style="min-width: 200px;">Tanggal Faktur Pajak</th>
                            <th class="py-2 align-middle" style="min-width: 200px;">Vendor</th>
                            <th class="py-2 align-middle text-center" style="width: 140px;">PPN/PPnBM</th>
                            <th class="py-2 align-middle text-end" style="width: 160px;">Nominal PPN</th>
                            <th class="py-2 align-middle text-end" style="width: 160px;">Nominal PPnBM</th>
                            <th class="pe-3 py-2 align-middle text-center" style="width: 60px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pajakTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data pajak masukan...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold" id="pajakTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="4" class="ps-3 py-2 text-end text-uppercase">Grand Total:</td>
                            <td class="py-2 text-end text-success font-monospace" id="footTotalPpn">Rp 0</td>
                            <td class="py-2 text-end text-warning-emphasis font-monospace" id="footTotalPpnbm">Rp 0</td>
                            <td class="pe-3 py-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- CARD FOOTER: PAGINATION -->
        <div class="card-footer bg-white border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted small" id="paginationInfo">
                Menampilkan 0 dari 0 data
            </div>
            <nav aria-label="Page navigation" id="paginationNav">
                <ul class="pagination pagination-sm mb-0" id="paginationList">
                    <!-- Pagination populated dynamically -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL RINCIAN FAKTUR PAJAK -->
<div class="modal fade" id="modalRincianPajak" tabindex="-1" aria-labelledby="modalRincianPajakLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white py-3 px-4 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0" id="modalRincianPajakLabel">Rincian Faktur Pajak Masukan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalRincianBody">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- MODAL PILIH TAHUN REKAPITULASI -->
<div class="modal fade" id="modalPilihTahunRekap" tabindex="-1" aria-labelledby="modalPilihTahunRekapLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white py-3 px-4 border-bottom">
                <h6 class="modal-title fw-bold text-dark mb-0" id="modalPilihTahunRekapLabel">Cetak Rekapitulasi Pajak Masukan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="rekapPilihTahun" class="form-label small fw-semibold text-dark">Pilih Tahun Pajak:</label>
                    <select class="form-select" id="rekapPilihTahun">
                        <?php
                        $curYr = (int)date('Y');
                        for ($y = $curYr - 5; $y <= $curYr + 10; $y++) {
                            $sel = ($y === $curYr) ? 'selected' : '';
                            echo "<option value=\"{$y}\" {$sel}>Tahun {$y}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary fw-semibold" onclick="submitCetakRekapitulasi()">
                        <i class="bi bi-printer-fill me-1"></i> Buka Dokumen Rekapitulasi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentLimit = 15;
let cachedVendors = [];
let cachedData = [];

document.addEventListener('DOMContentLoaded', () => {
    // Set default filter bulan sesuai waktu sekarang
    const today = new Date();
    const currentMonth = today.getMonth() + 1; // 1 - 12

    const selBulan = document.getElementById('filterBulan');
    if (selBulan) selBulan.value = currentMonth;

    loadFilterOptions();
    loadPajakReport(1);

    // Close vendor dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('vendorDropdownMenu');
        const btn = document.getElementById('vendorDropdownBtn');
        if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});

function formatRupiah(num) {
    if (num === null || num === undefined || isNaN(num)) return 'Rp 0';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function formatDateIndo(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const year = parts[0];
    const month = parseInt(parts[1], 10);
    const day = parseInt(parts[2], 10);
    const monthNames = [
        '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return `${day} ${monthNames[month] || parts[1]} ${year}`;
}

function toggleVendorDropdown(e) {
    e.stopPropagation();
    const menu = document.getElementById('vendorDropdownMenu');
    if (!menu) return;
    const isShown = menu.style.display === 'block';
    menu.style.display = isShown ? 'none' : 'block';
    if (!isShown) {
        setTimeout(() => {
            const input = document.getElementById('searchVendorInput');
            if (input) input.focus();
        }, 50);
    }
}

function filterVendorList(query) {
    const q = (query || '').toLowerCase().trim();
    const list = document.getElementById('vendorOptionsList');
    if (!list) return;

    let filtered = cachedVendors;
    if (q !== '') {
        filtered = cachedVendors.filter(v => 
            (v.nama_perusahaan && v.nama_perusahaan.toLowerCase().includes(q))
        );
    }
    renderVendorOptions(filtered);
}

function renderVendorOptions(vendors) {
    const list = document.getElementById('vendorOptionsList');
    if (!list) return;

    const currentVal = parseInt(document.getElementById('filterVendor').value, 10) || 0;

    let html = `
        <div class="dropdown-item py-2 px-3 cursor-pointer ${currentVal === 0 ? 'active bg-primary text-white' : ''}" 
             onclick="selectVendor(0, 'Semua Vendor')" style="cursor: pointer;">
            <div class="fw-semibold">Semua Vendor</div>
        </div>
    `;

    if (vendors.length === 0) {
        html += `<div class="p-2 text-muted small text-center">Tidak ada vendor ditemukan</div>`;
    } else {
        vendors.forEach(v => {
            const isSel = currentVal === parseInt(v.id_vendor, 10);
            html += `
                <div class="dropdown-item py-2 px-3 cursor-pointer ${isSel ? 'active bg-primary text-white' : ''}" 
                     onclick="selectVendor(${v.id_vendor}, '${escapeHtml(v.nama_perusahaan)}')" style="cursor: pointer;">
                    <div class="fw-semibold text-truncate">${escapeHtml(v.nama_perusahaan)}</div>
                </div>
            `;
        });
    }

    list.innerHTML = html;
}

function selectVendor(id, name) {
    document.getElementById('filterVendor').value = id;
    document.getElementById('vendorDropdownLabel').innerText = name;
    document.getElementById('vendorDropdownMenu').style.display = 'none';
    loadPajakReport(1);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function resetFilters() {
    const today = new Date();
    document.getElementById('filterTahun').value = today.getFullYear();
    document.getElementById('filterBulan').value = today.getMonth() + 1;
    document.getElementById('filterVendor').value = 0;
    document.getElementById('vendorDropdownLabel').innerText = 'Semua Vendor';
    loadPajakReport(1);
}

async function loadFilterOptions() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/laporan/pajak_masukan.php?limit=1');
        const json = await res.json();
        if (json.success && json.data) {
            // Populate Vendors
            if (json.data.filter_options && json.data.filter_options.vendors) {
                cachedVendors = json.data.filter_options.vendors;
                renderVendorOptions(cachedVendors);
            }
        }
    } catch (e) {
        console.error('Gagal memuat filter options:', e);
    }
}

async function loadPajakReport(page = 1) {
    currentPage = page;
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;
    const idVendor = document.getElementById('filterVendor').value;

    const tbody = document.getElementById('pajakTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data laporan pajak masukan...
            </td>
        </tr>
    `;

    try {
        const url = `<?= BASE_URL ?>/api/laporan/pajak_masukan.php?page=${currentPage}&limit=${currentLimit}&tahun=${tahun}&bulan=${bulan}&id_vendor=${idVendor}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> ${escapeHtml(json.message || 'Gagal memuat data.')}
                    </td>
                </tr>
            `;
            return;
        }

        const data = json.data;
        cachedData = data.rows || [];

        // Render Table Grand Total Footer
        const grandTotal = data.grand_totals || { grand_total_dpp: 0, grand_total_ppn: 0, grand_total_ppnbm: 0, grand_total_tagihan: 0 };
        document.getElementById('footTotalPpn').innerText = formatRupiah(grandTotal.grand_total_ppn);
        document.getElementById('footTotalPpnbm').innerText = formatRupiah(grandTotal.grand_total_ppnbm);
        document.getElementById('pajakTableFoot').style.display = cachedData.length > 0 ? '' : 'none';

        // Render Table Rows
        if (cachedData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                        Tidak ada data faktur pajak masukan yang sesuai dengan kriteria filter.
                    </td>
                </tr>
            `;
            renderPagination(0, 0, 1, currentLimit);
            return;
        }

        let html = '';
        const offset = (data.pagination.page - 1) * data.pagination.limit;

        cachedData.forEach((row, idx) => {
            const no = offset + idx + 1;
            const tglPajak = row.tanggal_faktur_pajak || '-';
            const noPo = row.nomor_po || '-';
            const ratePpn = parseFloat(row.rate_ppn) || 0;
            const ratePpnbm = parseFloat(row.rate_ppnbm) || 0;
            const rateDisplay = ratePpnbm > 0 ? `${ratePpn} / ${ratePpnbm}` : `${ratePpn}%`;

            html += `
                <tr class="align-middle">
                    <td class="ps-3 py-2 text-center text-muted fw-semibold">${no}</td>
                    <td class="py-2">
                        <div class="fw-bold text-dark font-monospace">${escapeHtml(tglPajak)}</div>
                        <div class="small text-primary font-monospace fw-semibold" style="font-size: 11.5px;">${escapeHtml(noPo)}</div>
                    </td>
                    <td class="py-2">
                        <div class="fw-semibold text-dark">${escapeHtml(row.vendor || '-')}</div>
                    </td>
                    <td class="py-2 text-center">
                        <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fw-bold">${escapeHtml(rateDisplay)}</span>
                    </td>
                    <td class="py-2 text-end fw-bold text-success font-monospace">
                        ${formatRupiah(row.ppn_masukan)}
                    </td>
                    <td class="py-2 text-end fw-bold text-warning-emphasis font-monospace">
                        ${formatRupiah(row.ppnbm)}
                    </td>
                    <td class="pe-3 py-2 text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary p-0 d-inline-flex align-items-center justify-content-center" title="Lihat Rincian" onclick="showRincian(${idx})" style="width: 28px; height: 28px;">
                            <i class="bi bi-eye-fill small"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        renderPagination(data.pagination.total, data.pagination.total_pages, data.pagination.page, data.pagination.limit);

    } catch (e) {
        console.error('Error fetching pajak masukan:', e);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Terjadi kesalahan saat memuat data.
                </td>
            </tr>
        `;
    }
}

function renderPagination(totalRecords, totalPages, curPage, perPage) {
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('paginationList');

    if (totalRecords === 0) {
        info.innerText = 'Menampilkan 0 dari 0 data';
        list.innerHTML = '';
        return;
    }

    const start = (curPage - 1) * perPage + 1;
    const end = Math.min(curPage * perPage, totalRecords);
    info.innerText = `Menampilkan ${start} - ${end} dari ${totalRecords} data`;

    if (totalPages <= 1) {
        list.innerHTML = '';
        return;
    }

    let html = '';
    // Previous
    html += `
        <li class="page-item ${curPage === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="loadPajakReport(${curPage - 1})" aria-label="Previous">
                <i class="bi bi-chevron-left"></i>
            </button>
        </li>
    `;

    // Pages
    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= curPage - 2 && p <= curPage + 2)) {
            html += `
                <li class="page-item ${p === curPage ? 'active' : ''}">
                    <button class="page-link" onclick="loadPajakReport(${p})">${p}</button>
                </li>
            `;
        } else if (p === curPage - 3 || p === curPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Next
    html += `
        <li class="page-item ${curPage === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="loadPajakReport(${curPage + 1})" aria-label="Next">
                <i class="bi bi-chevron-right"></i>
            </button>
        </li>
    `;

    list.innerHTML = html;
}

function showRincian(index) {
    const item = cachedData[index];
    if (!item) return;

    const ratePpn = parseFloat(item.rate_ppn) || 0;
    const ratePpnbm = parseFloat(item.rate_ppnbm) || 0;

    const body = document.getElementById('modalRincianBody');
    body.innerHTML = `
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Faktur Pajak</h6>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Nomor Faktur Pajak:</span>
                        <strong class="text-dark font-monospace fs-6">${escapeHtml(item.nomor_faktur_pajak || '-')}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Tanggal Faktur Pajak:</span>
                        <strong class="text-dark">${formatDateIndo(item.tanggal_faktur_pajak)}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Nama Vendor / Rekanan:</span>
                        <strong class="text-primary fs-6">${escapeHtml(item.vendor || '-')}</strong>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Nomor Invoice Vendor:</span>
                        <span class="font-monospace fw-semibold text-dark">${escapeHtml(item.nomor_faktur_vendor || '-')}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Referensi Pembelian</h6>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Nomor Purchase Order:</span>
                        <strong class="text-dark font-monospace fs-6">${escapeHtml(item.nomor_po || '-')}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Nomor Faktur Pembelian:</span>
                        <span class="font-monospace fw-semibold text-dark">${escapeHtml(item.nomor_faktur || '-')}</span>
                    </div>
                    <div>
                        <span class="text-muted small d-block mb-1">Status Faktur:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-semibold">${escapeHtml(item.status || 'SELESAI')}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-light border-0 rounded-3 p-3">
            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Rincian Perhitungan Pajak</h6>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <span class="text-muted small d-block">DPP</span>
                    <strong class="text-dark font-monospace fs-6">${formatRupiah(item.dpp)}</strong>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted small d-block">PPN Masukan (${ratePpn}%)</span>
                    <strong class="text-success font-monospace fs-6">${formatRupiah(item.ppn_masukan)}</strong>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted small d-block">PPnBM (${ratePpnbm}%)</span>
                    <strong class="text-warning-emphasis font-monospace fs-6">${parseFloat(item.ppnbm) > 0 ? formatRupiah(item.ppnbm) : 'Rp 0'}</strong>
                </div>
                <div class="col-6 col-md-3">
                    <span class="text-muted small d-block">Total Tagihan</span>
                    <strong class="text-primary font-monospace fs-6">${formatRupiah(item.total_tagihan)}</strong>
                </div>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('modalRincianPajak'));
    modal.show();
}

function printReport() {
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;
    const idVendor = document.getElementById('filterVendor').value;

    const url = `<?= BASE_URL ?>/admin/pages/laporan/print_pajak_masukan.php?tahun=${tahun}&bulan=${bulan}&id_vendor=${idVendor}&kop=1`;
    window.open(url, '_blank');
}

function openModalPilihTahunRekap() {
    const currentSelectedYear = document.getElementById('filterTahun').value;
    const rekapYearSelect = document.getElementById('rekapPilihTahun');
    if (rekapYearSelect && currentSelectedYear) {
        rekapYearSelect.value = currentSelectedYear;
    }
    const modal = new bootstrap.Modal(document.getElementById('modalPilihTahunRekap'));
    modal.show();
}

function submitCetakRekapitulasi() {
    const tahun = document.getElementById('rekapPilihTahun').value;
    const modalEl = document.getElementById('modalPilihTahunRekap');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    const url = `<?= BASE_URL ?>/admin/pages/laporan/print_rekapitulasi_pajak.php?tahun=${tahun}&kop=1`;
    window.open(url, '_blank');
}
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
