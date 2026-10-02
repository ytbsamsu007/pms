<?php
/**
 * Halaman Utama: Daftar Retur Purchase Order (Retur PO)
 * Path: admin/pages/retur_po/index.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER (Dikelola oleh Logistik)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection: Khusus Logistik, Admin, dan Manager
$user = requireAuth([ROLE_LOGISTIK, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Retur Purchase Order';
$pageHeading = 'Daftar Retur Purchase Order';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- KONTEN UTAMA -->
<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Retur Purchase Order (PO)</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/retur_po/create.php" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold">
                <i class="bi bi-plus-lg me-1"></i> Buat Retur PO
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="loadReturList(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- 3 KARTU METRIK RINGKASAN RETUR PO -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Retur PO -->
        <div class="col-12 col-md-4">
            <div class="stat-card cursor-pointer" id="cardMetricTotal" onclick="quickFilterStatus('')">
                <div class="stat-icon primary">
                    <i class="bi bi-arrow-return-left"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label">Total Retur PO</div>
                    <div class="stat-value" id="statTotalRetur">0</div>
                </div>
            </div>
        </div>

        <!-- 2. Menunggu Respon Vendor -->
        <div class="col-12 col-md-4">
            <div class="stat-card cursor-pointer" id="cardMetricProses" onclick="quickFilterStatus('MENUNGGU KONFIRMASI VENDOR')">
                <div class="stat-icon warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label text-warning-emphasis">Menunggu Vendor</div>
                    <div class="stat-value text-warning-emphasis" id="statProses">0</div>
                </div>
            </div>
        </div>

        <!-- 3. Selesai / Diterima -->
        <div class="col-12 col-md-4">
            <div class="stat-card cursor-pointer" id="cardMetricSelesai" onclick="quickFilterStatus('DITERIMA')">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label text-success">Selesai / Diterima</div>
                    <div class="stat-value text-success" id="statSelesai">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & PENCARIAN -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 retur-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-3 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari No. Retur / PO / RCV / Vendor..." autocomplete="off">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-6 col-md-2" style="min-width: 175px;">
                    <select class="form-select form-select-sm text-truncate" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">Draft</option>
                        <option value="MENUNGGU KONFIRMASI VENDOR">Menunggu Konfirmasi</option>
                        <option value="DISETUJUI VENDOR">Disetujui Vendor</option>
                        <option value="TIDAK DISETUJUI VENDOR">Tidak Disetujui</option>
                        <option value="DIKIRIM KE VENDOR">Dikirim ke Vendor</option>
                        <option value="DITERIMA">Selesai / Diterima</option>
                    </select>
                </div>

                <!-- Kompensasi Filter -->
                <div class="col-6 col-md-2" style="min-width: 175px;">
                    <select class="form-select form-select-sm text-truncate" id="filterKompensasi">
                        <option value="">Semua Kompensasi</option>
                        <option value="1">Tukar Unit</option>
                        <option value="0">Potong Tagihan</option>
                    </select>
                </div>

                <!-- Range Tanggal: Dari Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 150px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Dari Tanggal" onclick="const el=document.getElementById('filterStartDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterStartDate" placeholder="mm / dd / yyyy" title="Dari Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="applyFilters()">
                    </div>
                </div>

                <!-- Range Tanggal: Sampai Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 150px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Sampai Tanggal" onclick="const el=document.getElementById('filterEndDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-check"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterEndDate" placeholder="mm / dd / yyyy" title="Sampai Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="applyFilters()">
                    </div>
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

    <!-- TABEL DATA RETUR PO -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="returTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 140px;">Nomor Retur</th>
                            <th style="min-width: 120px;">Tanggal</th>
                            <th style="min-width: 130px;">Ref PO</th>
                            <th style="min-width: 160px;">Vendor</th>
                            <th style="min-width: 130px;" class="text-center">Kompensasi</th>
                            <th style="min-width: 130px;" class="text-end pe-3">Total Nilai</th>
                            <th style="min-width: 140px;" class="text-center">Status</th>
                            <th style="width: 80px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="returTableBody">
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Retur PO...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- PAGINATION -->
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2" id="paginationWrapper" style="display: none !important;">
            <span class="text-muted small" id="paginationInfo">Menampilkan 0 data</span>
            <ul class="pagination pagination-sm mb-0" id="paginationNav"></ul>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 0.85rem 1.25rem;
    background: #ffffff;
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 50% !important;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}
.stat-icon.primary {
    background-color: #e0f2fe;
    color: #0284c7;
}
.stat-icon.warning {
    background-color: #fef3c7;
    color: #d97706;
}
.stat-icon.success {
    background-color: #dcfce7;
    color: #16a34a;
}
.stat-details {
    flex: 1;
}
.stat-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 2px;
}
.stat-value {
    font-size: 1.45rem;
    font-weight: 800;
    line-height: 1.1;
    color: #1e293b;
    font-family: var(--bs-font-monospace, monospace);
}
.retur-filter-bar .form-control,
.retur-filter-bar .form-select,
.retur-filter-bar .input-group-text,
.retur-filter-bar .btn {
    height: 36px;
    font-size: 0.85rem;
}
.retur-filter-bar .input-group-text {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 10px;
    padding-right: 10px;
}
.retur-filter-bar .form-select {
    padding-top: 0.15rem !important;
    padding-bottom: 0.35rem !important;
    padding-right: 2.25rem !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    line-height: 1.5 !important;
}
.retur-filter-bar .form-control {
    padding-top: 0.22rem !important;
    padding-bottom: 0.28rem !important;
    line-height: 1.5 !important;
}
.retur-filter-bar input[type="date"] {
    -moz-appearance: textfield !important;
    appearance: none !important;
    padding-top: 0.22rem !important;
    padding-bottom: 0.28rem !important;
}
.retur-filter-bar input[type="date"]::-webkit-calendar-picker-indicator {
    display: none !important;
    -webkit-appearance: none !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
let currentPage = 1;
let currentFilters = {
    q: '',
    status: '',
    kompensasi: '',
    start_date: '',
    end_date: ''
};
let searchDebounce = null;

document.addEventListener('DOMContentLoaded', () => {
    loadReturList(1);

    // Bind Filter Events
    document.getElementById('filterSearch').addEventListener('input', (e) => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            currentFilters.q = e.target.value.trim();
            loadReturList(1);
        }, 400);
    });

    document.getElementById('filterStatus').addEventListener('change', (e) => {
        currentFilters.status = e.target.value;
        loadReturList(1);
    });

    document.getElementById('filterKompensasi').addEventListener('change', (e) => {
        currentFilters.kompensasi = e.target.value;
        loadReturList(1);
    });
});

function applyFilters() {
    currentFilters.start_date = document.getElementById('filterStartDate').value;
    currentFilters.end_date = document.getElementById('filterEndDate').value;
    loadReturList(1);
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterKompensasi').value = '';
    const elStart = document.getElementById('filterStartDate');
    elStart.value = '';
    elStart.type = 'text';
    const elEnd = document.getElementById('filterEndDate');
    elEnd.value = '';
    elEnd.type = 'text';

    currentFilters = { q: '', status: '', kompensasi: '', start_date: '', end_date: '' };
    loadReturList(1);
}

function quickFilterStatus(status) {
    document.getElementById('filterStatus').value = status;
    currentFilters.status = status;
    loadReturList(1);
}

function formatShortDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const day = String(d.getDate()).padStart(2, '0');
    const mon = months[d.getMonth()];
    const yr = d.getFullYear();
    return `${day} ${mon} ${yr}`;
}

function formatRupiah(num) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}

function getStatusBadge(status) {
    switch (status) {
        case 'DRAFT':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-pencil me-1"></i>Draft</span>';
        case 'MENUNGGU KONFIRMASI VENDOR':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Menunggu Vendor</span>';
        case 'DISETUJUI VENDOR':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Disetujui Vendor</span>';
        case 'TIDAK DISETUJUI VENDOR':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Ditolak Vendor</span>';
        case 'DIKIRIM KE VENDOR':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-truck me-1"></i>Dikirim ke Vendor</span>';
        case 'DITERIMA':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Selesai / Diterima</span>';
        default:
            return `<span class="badge bg-light text-dark">${status || '-'}</span>`;
    }
}

async function loadReturList(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('returTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Retur PO...
            </td>
        </tr>`;

    try {
        let url = `/api/retur_po/index.php?page=${page}&limit=20`;
        if (currentFilters.q) url += `&q=${encodeURIComponent(currentFilters.q)}`;
        if (currentFilters.status) url += `&status=${encodeURIComponent(currentFilters.status)}`;
        if (currentFilters.kompensasi !== '') url += `&kompensasi=${encodeURIComponent(currentFilters.kompensasi)}`;
        if (currentFilters.start_date) url += `&start_date=${encodeURIComponent(currentFilters.start_date)}`;
        if (currentFilters.end_date) url += `&end_date=${encodeURIComponent(currentFilters.end_date)}`;

        const res = await apiRequest(url, 'GET');

        if (res && res.success) {
            const data = res.data;

            // Render Metrics
            if (data.metrics) {
                const elTotal = document.getElementById('statTotalRetur');
                const elProses = document.getElementById('statProses');
                const elSelesai = document.getElementById('statSelesai');
                if (elTotal) elTotal.textContent = parseInt(data.metrics.total_retur || 0).toLocaleString('id-ID');
                if (elProses) elProses.textContent = parseInt(data.metrics.total_proses || 0).toLocaleString('id-ID');
                if (elSelesai) elSelesai.textContent = parseInt(data.metrics.total_selesai || 0).toLocaleString('id-ID');
            }

            // Render Table Rows
            if (!data.items || data.items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-arrow-return-left fs-2 text-secondary d-block mb-2"></i>
                            <strong class="d-block text-dark">Belum Ada Data Retur PO Sesuai Filter!</strong>
                            <span class="small text-muted">Klik tombol "+ Buat Retur PO" di atas untuk mengajukan dokumen klaim barang baru.</span>
                        </td>
                    </tr>`;
                document.getElementById('paginationWrapper').style.setProperty('display', 'none', 'important');
                return;
            }

            let html = '';
            const startNo = (data.pagination.current_page - 1) * data.pagination.per_page + 1;

            data.items.forEach((item, index) => {
                const no = startNo + index;
                const kompensasiBadge = (item.kompensasi == 1) 
                    ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-arrow-repeat me-1"></i>Tukar Unit</span>'
                    : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-cash-coin me-1"></i>Potong Tagihan</span>';

                html += `
                <tr>
                    <td class="text-center text-muted fw-semibold">${no}</td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/pages/retur_po/detail.php?id=${item.id_po_retur}" class="fw-bold font-monospace text-primary text-decoration-none">
                            ${item.nomor_po_retur || '-'}
                        </a>
                    </td>
                    <td>
                        <span class="fw-semibold text-dark">${formatShortDate(item.tanggal_po_retur)}</span>
                    </td>
                    <td>
                        <span class="font-monospace fw-semibold text-dark">${item.nomor_po || '-'}</span>
                    </td>
                    <td>
                        <span class="fw-semibold text-dark">${item.nama_vendor || '-'}</span>
                    </td>
                    <td class="text-center">${kompensasiBadge}</td>
                    <td class="text-end fw-bold text-dark font-monospace pe-3">${formatRupiah(item.total)}</td>
                    <td class="text-center">${getStatusBadge(item.status)}</td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <a href="<?= BASE_URL ?>/admin/pages/retur_po/detail.php?id=${item.id_po_retur}" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" title="Lihat Detail &amp; Tindak Lanjut">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                        </div>
                    </td>
                </tr>`;
            });

            tbody.innerHTML = html;

            // Render Pagination
            renderPagination(data.pagination);

        } else {
            throw new Error(res.message || 'Gagal memuat data');
        }

    } catch (err) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle fs-2 d-block mb-2"></i>
                    ${err.message || 'Terjadi kesalahan saat memuat data Retur PO.'}
                </td>
            </tr>`;
        document.getElementById('paginationWrapper').style.setProperty('display', 'none', 'important');
    }
}

function renderPagination(p) {
    const wrapper = document.getElementById('paginationWrapper');
    if (!p || p.total_pages <= 1) {
        wrapper.style.setProperty('display', 'none', 'important');
        return;
    }

    wrapper.style.removeProperty('display');
    const startItem = (p.current_page - 1) * p.per_page + 1;
    const endItem = Math.min(p.current_page * p.per_page, p.total_items);
    document.getElementById('paginationInfo').textContent = `Menampilkan ${startItem} - ${endItem} dari ${p.total_items} dokumen`;

    const nav = document.getElementById('paginationNav');
    let navHtml = '';

    // Previous Button
    navHtml += `
        <li class="page-item ${p.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadReturList(${p.current_page - 1})"><i class="bi bi-chevron-left"></i></a>
        </li>`;

    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.current_page - 1 && i <= p.current_page + 1)) {
            navHtml += `
                <li class="page-item ${i === p.current_page ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="loadReturList(${i})">${i}</a>
                </li>`;
        } else if (i === p.current_page - 2 || i === p.current_page + 2) {
            navHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Next Button
    navHtml += `
        <li class="page-item ${p.current_page === p.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadReturList(${p.current_page + 1})"><i class="bi bi-chevron-right"></i></a>
        </li>`;

    nav.innerHTML = navHtml;
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
