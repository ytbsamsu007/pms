<?php
/**
 * Monitoring PO Outstanding & Delivery Aging - PT Jaya Teknis
 * Akses: Khusus Purchasing, Admin, dan Manager
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'PO Outstanding & Tracking Pengiriman';
$pageHeading = 'Monitoring PO Outstanding';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Monitoring PO Outstanding</h4>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm fw-semibold" onclick="loadOutstandingData()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- 3 KARTU METRIK RINGKASAN PO OUTSTANDING -->
    <div class="row g-3 mb-4">
        <!-- 1. Total PO Outstanding -->
        <div class="col-12 col-md-4">
            <div class="stat-card cursor-pointer" id="cardMetricTotal" onclick="setAgingQuickFilter('ALL')">
                <div class="stat-icon primary">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label">Total PO Outstanding</div>
                    <div class="stat-value" id="metricTotalPo">0</div>
                </div>
            </div>
        </div>

        <!-- 2. PO Terlambat (Overdue) -->
        <div class="col-12 col-md-4">
            <div class="stat-card cursor-pointer" id="cardMetricOverdue" onclick="setAgingQuickFilter('OVERDUE')">
                <div class="stat-icon danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label text-danger">Terlambat (Overdue)</div>
                    <div class="stat-value text-danger" id="metricTotalOverdue">0</div>
                </div>
            </div>
        </div>

        <!-- 3. Jadwal Tiba Hari Ini -->
        <div class="col-12 col-md-4">
            <div class="stat-card cursor-pointer" id="cardMetricDueToday" onclick="setAgingQuickFilter('DUE_TODAY')">
                <div class="stat-icon warning">
                    <i class="bi bi-truck"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label text-warning-emphasis">Tiba Hari Ini</div>
                    <div class="stat-value text-warning-emphasis" id="metricTotalDueToday">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & PENCARIAN -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 po-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-6 col-lg-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari No. PO / No. RO / Vendor..." autocomplete="off" onkeydown="if(event.key==='Enter') applyFilters()">
                    </div>
                </div>

                <!-- Filter Site -->
                <div class="col-md-4 col-lg-4">
                    <select class="form-select form-select-sm" id="filterSite" onchange="applyFilters()">
                        <option value="">Semua Site Tujuan</option>
                    </select>
                </div>

                <!-- Tombol Reset & Filter -->
                <div class="col-auto ms-auto d-flex gap-1">
                    <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" onclick="applyFilters()" title="Terapkan Filter">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .stat-card {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .stat-card .stat-icon {
        border-radius: 50% !important;
        flex-shrink: 0;
    }
    .stat-card.card-metric-active {
        box-shadow: 0 0 0 2px #0d6efd, 0 4px 12px rgba(13, 110, 253, 0.15) !important;
        border-color: #0d6efd !important;
        background-color: #f8fbff !important;
    }
    .po-filter-bar .form-control,
    .po-filter-bar .form-select,
    .po-filter-bar .input-group-text,
    .po-filter-bar .btn {
        height: 36px;
        font-size: 0.85rem;
    }
    .po-table th, .po-table td {
        vertical-align: middle;
        font-size: 0.85rem;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    </style>

    <!-- TABEL MONITORING PO OUTSTANDING -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 po-table" id="poOutstandingTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="text-center" style="width: 50px;">NO</th>
                            <th style="width: 180px;">NOMOR PO</th>
                            <th>VENDOR</th>
                            <th>SITE TUJUAN</th>
                            <th style="width: 210px;" class="text-center">ESTIMASI TIBA &amp; AGING</th>
                            <th class="text-end pe-4" style="width: 180px;">TOTAL BIAYA</th>
                        </tr>
                    </thead>
                    <tbody id="poOutstandingTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data PO Outstanding...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION & FOOTER -->
            <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="small text-muted" id="paginationInfo">Menampilkan 0 dari 0 data</span>
                <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let currentFilters = {
    q: '',
    site_id: '',
    aging_status: 'ALL'
};

document.addEventListener('DOMContentLoaded', () => {
    loadSites();
    loadOutstandingData();
});

// -------------------------------------------------------------
// 1. LOAD SITES UNTUK DROPDOWN FILTER
// -------------------------------------------------------------
async function loadSites() {
    try {
        const res = await apiRequest('/api/master/site.php?limit=100');
        if (res && res.success && res.data) {
            const items = Array.isArray(res.data) ? res.data : (res.data.items || []);
            const select = document.getElementById('filterSite');
            items.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id_site;
                opt.textContent = s.nama_site;
                select.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('Gagal memuat master site:', e);
    }
}

// -------------------------------------------------------------
// 2. LOAD DATA PO OUTSTANDING DARI API
// -------------------------------------------------------------
async function loadOutstandingData(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('poOutstandingTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data PO Outstanding...</td></tr>`;

    const params = new URLSearchParams({
        page: currentPage,
        limit: 20,
        q: currentFilters.q,
        site_id: currentFilters.site_id,
        aging_status: currentFilters.aging_status
    });

    try {
        const res = await apiRequest(`/api/purchase_order/outstanding.php?${params.toString()}`);
        if (res && res.success) {
            renderMetrics(res.data.metrics);
            renderTable(res.data.items, res.data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${escapeHtml(res ? res.message : 'Gagal memuat data.')}</td></tr>`;
        }
    } catch (err) {
        console.error('Error load outstanding PO:', err);
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Terjadi kesalahan koneksi server.</td></tr>`;
    }
}

// -------------------------------------------------------------
// 3. RENDER METRIK DI ATAS
// -------------------------------------------------------------
function renderMetrics(metrics) {
    if (!metrics) return;
    const elPo = document.getElementById('metricTotalPo');
    const elOverdue = document.getElementById('metricTotalOverdue');
    const elDue = document.getElementById('metricTotalDueToday');
    if (elPo) elPo.textContent = (metrics.total_outstanding || 0).toLocaleString('id-ID');
    if (elOverdue) elOverdue.textContent = (metrics.total_overdue || 0).toLocaleString('id-ID');
    if (elDue) elDue.textContent = (metrics.total_due_today || 0).toLocaleString('id-ID');
}

// -------------------------------------------------------------
// HELPER FORMAT TANGGAL PENDEK (28 Aug 2026)
// -------------------------------------------------------------
function formatShortDate(dateStr) {
    if (!dateStr || dateStr === 'Belum Ditentukan') return '-';
    const parts = dateStr.substring(0, 10).split('-');
    if (parts.length === 3) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'];
        const year = parts[0];
        const monthIndex = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const monthName = months[monthIndex] || parts[1];
        return `${day} ${monthName} ${year}`;
    }
    return dateStr;
}

// -------------------------------------------------------------
// 4. RENDER TABEL UTAMA
// -------------------------------------------------------------
function renderTable(items, pagination) {
    const tbody = document.getElementById('poOutstandingTableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i>
                    <strong class="d-block text-dark">Tidak Ada PO Outstanding Sesuai Filter!</strong>
                    <span class="small">Semua barang telah diterima atau belum ada data yang sesuai.</span>
                </td>
            </tr>
        `;
        document.getElementById('paginationInfo').textContent = 'Menampilkan 0 data';
        document.getElementById('paginationControls').innerHTML = '';
        return;
    }

    const startNo = ((pagination.current_page - 1) * pagination.per_page) + 1;
    let html = '';

    items.forEach((item, index) => {
        const no = startNo + index;
        const noPo = escapeHtml(item.nomor_po || '-');
        const vendor = escapeHtml(item.nama_vendor || '-');
        const site = escapeHtml(item.nama_site || '-');
        const tglKirim = formatShortDate(item.tanggal_pengiriman);
        const grandTotal = formatRupiah(item.grand_total || 0);

        html += `
            <tr>
                <td class="text-center fw-bold text-muted">${no}</td>
                <td>
                    <span class="fw-bold font-monospace text-dark">${noPo}</span>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${vendor}</div>
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace">${site}</span>
                </td>
                <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-2 justify-content-center flex-wrap">
                        <span class="font-monospace small fw-bold text-dark">${tglKirim}</span>
                        <span class="badge ${item.aging_badge_class} px-2 py-1" style="font-size: 0.72rem;">
                            ${escapeHtml(item.aging_label)}
                        </span>
                    </div>
                </td>
                <td class="text-end fw-bold font-monospace text-dark pe-4">
                    ${grandTotal}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    renderPagination(pagination);
}

// -------------------------------------------------------------
// 5. RENDER PAGINATION
// -------------------------------------------------------------
function renderPagination(p) {
    const info = document.getElementById('paginationInfo');
    const start = ((p.current_page - 1) * p.per_page) + 1;
    const end = Math.min(p.current_page * p.per_page, p.total_items);
    info.textContent = `Menampilkan ${start} - ${end} dari ${p.total_items} data`;

    const controls = document.getElementById('paginationControls');
    if (p.total_pages <= 1) {
        controls.innerHTML = '';
        return;
    }

    let html = '';
    html += `<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}"><button class="page-link" onclick="loadOutstandingData(${p.current_page - 1})">&laquo;</button></li>`;

    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.current_page - 1 && i <= p.current_page + 1)) {
            html += `<li class="page-item ${p.current_page === i ? 'active' : ''}"><button class="page-link" onclick="loadOutstandingData(${i})">${i}</button></li>`;
        } else if (i === p.current_page - 2 || i === p.current_page + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${p.current_page === p.total_pages ? 'disabled' : ''}"><button class="page-link" onclick="loadOutstandingData(${p.current_page + 1})">&raquo;</button></li>`;
    controls.innerHTML = html;
}

// -------------------------------------------------------------
// 6. FILTER LOGIC & INTERACTIVE CARD SELECTION
// -------------------------------------------------------------
function applyFilters() {
    currentFilters.q = document.getElementById('filterSearch').value.trim();
    currentFilters.site_id = document.getElementById('filterSite').value;
    updateActiveCardStyles();
    loadOutstandingData(1);
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '';
    currentFilters = { q: '', site_id: '', aging_status: 'ALL' };
    updateActiveCardStyles();
    loadOutstandingData(1);
}

function setAgingQuickFilter(status) {
    if (currentFilters.aging_status === status && status !== 'ALL') {
        currentFilters.aging_status = 'ALL';
    } else {
        currentFilters.aging_status = status;
    }
    updateActiveCardStyles();
    loadOutstandingData(1);
}

function updateActiveCardStyles() {
    document.getElementById('cardMetricTotal')?.classList.remove('card-metric-active');
    document.getElementById('cardMetricOverdue')?.classList.remove('card-metric-active');
    document.getElementById('cardMetricDueToday')?.classList.remove('card-metric-active');

    if (currentFilters.aging_status === 'OVERDUE') {
        document.getElementById('cardMetricOverdue')?.classList.add('card-metric-active');
    } else if (currentFilters.aging_status === 'DUE_TODAY') {
        document.getElementById('cardMetricDueToday')?.classList.add('card-metric-active');
    } else {
        document.getElementById('cardMetricTotal')?.classList.add('card-metric-active');
    }
}

function formatRupiah(val) {
    return 'Rp ' + parseFloat(val || 0).toLocaleString('id-ID');
}
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
