<?php
/**
 * Halaman Daftar Request Order (RO)
 * Path: admin/pages/request_order/index.php
 * Decoupled Architecture: All data communication via /api/request_order/index.php
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Daftar Request Order';
$pageHeading = 'Daftar Request Order (RO)';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- KONTEN UTAMA -->
<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Daftar Request Order (RO)</h4>
        </div>
        <?php if ($user['role'] === ROLE_MEKANIK || $user['role'] === ROLE_LOGISTIK || $user['role'] === ROLE_ADMIN): ?>
        <div>
            <a href="<?= BASE_URL ?>/admin/pages/request_order/create.php" class="btn btn-primary btn-sm px-3 fw-semibold">
                <i class="bi bi-plus-lg me-1"></i> Buat Request Order
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- FILTER & PENCARIAN -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 ro-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-3 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari No. RO / Pemohon..." autocomplete="off">
                    </div>
                </div>

                <!-- Site Filter -->
                <div class="col-md-3 col-lg-3" style="min-width: 210px;">
                    <select class="form-select form-select-sm" id="filterSite">
                        <option value="">Semua Site / Lokasi</option>
                    </select>
                </div>

                <!-- Range Tanggal: Dari Tanggal (Lebar 220px, Ikon) -->
                <div class="col-6 col-md-2" style="min-width: 170px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Dari Tanggal" onclick="const el=document.getElementById('filterStartDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterStartDate" placeholder="mm / dd / yyyy" title="Dari Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="applyFilters()">
                    </div>
                </div>

                <!-- Range Tanggal: Sampai Tanggal (Lebar 220px, Ikon) -->
                <div class="col-6 col-md-2" style="min-width: 170px;">
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

    <style>
    .ro-filter-bar .form-control,
    .ro-filter-bar .form-select,
    .ro-filter-bar .input-group-text,
    .ro-filter-bar .btn {
        height: 36px;
        font-size: 0.85rem;
    }
    .ro-filter-bar .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: 10px;
        padding-right: 10px;
    }
    .ro-filter-bar .form-select {
        padding-top: 0.15rem !important;
        padding-bottom: 0.35rem !important;
        padding-right: 2rem !important;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        line-height: 1.5 !important;
    }
    .ro-filter-bar .form-control {
        padding-top: 0.22rem !important;
        padding-bottom: 0.28rem !important;
        line-height: 1.5 !important;
    }
    .ro-filter-bar input[type="date"] {
        -moz-appearance: textfield !important;
        appearance: none !important;
        padding-top: 0.22rem !important;
        padding-bottom: 0.28rem !important;
    }
    .ro-filter-bar input[type="date"]::-webkit-calendar-picker-indicator {
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

    <!-- TABEL DATA REQUEST ORDER -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="roTable" style="min-width: 950px;">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 150px;">Nomor RO</th>
                            <th style="min-width: 120px;">Tanggal</th>
                            <th style="min-width: 90px;">Waktu</th>
                            <th style="min-width: 170px;">Pemohon</th>
                            <th style="min-width: 140px;">Site</th>
                            <th style="min-width: 160px;" class="text-center">Status</th>
                            <th style="min-width: 130px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="roTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Request Order...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FOOTER PAGINATION -->
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small" id="paginationInfo">Menampilkan 0 data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL REQUEST ORDER -->
<div class="modal fade" id="modalDetailRo" tabindex="-1" aria-labelledby="modalDetailRoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1000px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- MODAL HEADER DENGAN STATUS & PRIORITAS DI SEBELAH TOMBOL X -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text-fill text-primary fs-5"></i>
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="detailNomorRo">
                            RO-XXXX-XXXX
                        </h5>
                    </div>
                    <!-- Status & Prioritas di bagian atas di samping X -->
                    <div class="d-flex align-items-center gap-2">
                        <span id="detailBadgeStatus">-</span>
                        <span id="detailBadgePrioritas">-</span>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Nav Tabs Modal Sesuai Fungsi -->
                <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="roModalTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="ro-tab-dokumen" data-bs-toggle="tab" data-bs-target="#ro-pane-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Informasi Dokumen
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="ro-tab-rincian" data-bs-toggle="tab" data-bs-target="#ro-pane-rincian" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> 2. Rincian Material / Barang
                            <span class="badge bg-primary text-white ms-1" id="detailBadgeItemCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation" id="ro-tab-fulfillment-li" style="display: none;">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="ro-tab-fulfillment" data-bs-toggle="tab" data-bs-target="#ro-pane-fulfillment" type="button" role="tab">
                            <i class="bi bi-truck me-1 text-primary"></i> 3. Penerimaan &amp; Retur PO
                            <span class="badge bg-success text-white ms-1" id="detailBadgeFulfillment">Ada</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 3 TAB -->
            <div class="modal-body p-4">
                <div class="tab-content" id="roModalTabContent">
                    
                    <!-- TAB 1: INFORMASI DOKUMEN -->
                    <div class="tab-pane fade show active" id="ro-pane-dokumen" role="tabpanel">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <!-- Tanggal Pengajuan -->
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Tanggal Pengajuan:</span>
                                        <strong class="text-dark font-monospace fs-6" id="detailTanggalRo">-</strong>
                                    </div>
                                    <!-- Pemohon -->
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Pemohon:</span>
                                        <strong class="text-dark fs-6 d-block" id="detailPemohon">-</strong>
                                        <span class="text-secondary small" id="detailJabatanDivisi">-</span>
                                    </div>
                                    <!-- Referensi Vendor -->
                                    <div>
                                        <span class="text-muted small d-block">Referensi Vendor:</span>
                                        <strong class="text-dark" id="detailVendor">-</strong>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <!-- Site Tujuan -->
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Site Tujuan:</span>
                                        <span class="badge bg-secondary-subtle text-secondary px-2 py-1 fs-6 font-monospace" id="detailSite">-</span>
                                    </div>
                                    <!-- Persetujuan Logistik -->
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Persetujuan Logistik:</span>
                                        <span id="detailApproverBadge">-</span>
                                    </div>
                                    <!-- Keperluan / Catatan -->
                                    <div>
                                        <span class="text-muted small d-block">Keperluan / Catatan:</span>
                                        <div class="text-dark small fst-italic" id="detailKeterangan">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="detailQuickFulfillmentAlert" class="mt-3" style="display: none;"></div>
                    </div>

                    <!-- TAB 2: RINCIAN MATERIAL / BARANG -->
                    <div class="tab-pane fade" id="ro-pane-rincian" role="tabpanel">
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th style="width: 40px;" class="text-center align-middle">No</th>
                                        <th class="align-middle">Nama Barang / Material</th>
                                        <th style="width: 140px;" class="align-middle">Kode</th>
                                        <th style="width: 110px;" class="text-center align-middle">Qty</th>
                                        <th style="width: 100px;" class="text-center align-middle">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody id="detailItemsTableBody">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: STATUS PENERIMAAN & RETUR PO -->
                    <div class="tab-pane fade" id="ro-pane-fulfillment" role="tabpanel">
                        <div id="detailFulfillmentContent">
                            <!-- Populated dynamically -->
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer bg-light py-2 justify-content-between">
                <div>
                    <button type="button" class="btn btn-success btn-sm px-3 fw-semibold" id="btnModalApproveRo" style="display: none;" onclick="approveRequestOrderFromModal()">
                        <i class="bi bi-check-circle-fill me-1"></i> Setujui RO (Approve)
                    </button>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LOGIC (DECOUPLED VIA RESTFUL API) -->
<script>
const CURRENT_USER_ROLE = <?= json_encode($user['role']) ?>;
const CURRENT_USER_ID_KARYAWAN = <?= json_encode($user['id_karyawan'] ?? 0) ?>;
let currentPage = 1;
let currentLimit = 15;
let searchDebounceTimer = null;
let masterSiteList = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadMasterSites();
    await loadRequestOrders();

    // Event listeners
    const searchInput = document.getElementById('filterSearch');
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            currentPage = 1;
            loadRequestOrders();
        }, 300);
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchDebounceTimer);
            currentPage = 1;
            loadRequestOrders();
        }
    });

    document.getElementById('filterSite').addEventListener('change', () => { currentPage = 1; loadRequestOrders(); });
    document.getElementById('filterStartDate').addEventListener('change', () => { currentPage = 1; loadRequestOrders(); });
    document.getElementById('filterEndDate').addEventListener('change', () => { currentPage = 1; loadRequestOrders(); });
});

// -------------------------------------------------------------
// 1. LOAD MASTER SITES
// -------------------------------------------------------------
async function loadMasterSites() {
    const res = await apiRequest('/api/master/site.php?limit=100');
    if (res && res.success) {
        masterSiteList = res.data.items || [];
        const select = document.getElementById('filterSite');
        masterSiteList.forEach(s => {
            select.innerHTML += `<option value="${s.id_site}">${escapeHtml(s.nama_site)}</option>`;
        });
    }
}

let currentSearchAbortController = null;

// -------------------------------------------------------------
// 2. LOAD LIST REQUEST ORDER (WITH SKELETON LOADING)
// -------------------------------------------------------------
function renderTableSkeleton(rowCount = 5) {
    const tbody = document.getElementById('roTableBody');
    let skeletonHtml = '';
    for (let i = 0; i < rowCount; i++) {
        skeletonHtml += `
            <tr>
                <td class="text-center"><div class="skeleton-shimmer" style="width: 20px; height: 16px;"></div></td>
                <td><div class="skeleton-shimmer" style="width: 120px; height: 16px;"></div></td>
                <td><div class="skeleton-shimmer" style="width: 75px; height: 16px;"></div></td>
                <td><div class="skeleton-shimmer" style="width: 60px; height: 16px;"></div></td>
                <td><div class="skeleton-shimmer" style="width: 130px; height: 16px;"></div></td>
                <td><div class="skeleton-shimmer" style="width: 90px; height: 20px; border-radius: 10px;"></div></td>
                <td class="text-center"><div class="skeleton-shimmer" style="width: 110px; height: 22px; border-radius: 12px;"></div></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <div class="skeleton-shimmer" style="width: 28px; height: 26px; border-radius: 4px;"></div>
                        <div class="skeleton-shimmer" style="width: 28px; height: 26px; border-radius: 4px;"></div>
                    </div>
                </td>
            </tr>
        `;
    }
    tbody.innerHTML = skeletonHtml;
}

async function loadRequestOrders(page = currentPage) {
    currentPage = page;
    renderTableSkeleton(5);

    const search = document.getElementById('filterSearch').value.trim();
    const siteId = document.getElementById('filterSite').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    let url = `/api/request_order/index.php?page=${currentPage}&limit=${currentLimit}`;
    if (search) url += `&q=${encodeURIComponent(search)}`;
    if (siteId) url += `&site_id=${encodeURIComponent(siteId)}`;
    if (startDate) url += `&start_date=${encodeURIComponent(startDate)}`;
    if (endDate) url += `&end_date=${encodeURIComponent(endDate)}`;

    if (currentSearchAbortController) {
        currentSearchAbortController.abort();
    }
    currentSearchAbortController = new AbortController();

    try {
        const res = await apiRequest(url, { signal: currentSearchAbortController.signal });
        const tbody = document.getElementById('roTableBody');

        if (res && res.success) {
            renderTableRows(res.data.items || [], (currentPage - 1) * currentLimit);
            renderPagination(res.data.pagination || {});
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat data. Silakan coba lagi.</td></tr>`;
        }
    } catch (e) {
        if (e.name !== 'AbortError') {
            const tbody = document.getElementById('roTableBody');
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Terjadi kesalahan saat memuat data.</td></tr>`;
        }
    }
}

function renderTableRows(items, offset) {
    const tbody = document.getElementById('roTableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>Belum ada data Request Order yang sesuai dengan filter.</td></tr>`;
        return;
    }

    let html = '';
    items.forEach((ro, idx) => {
        const no = offset + idx + 1;
        
        // Status Badge Terstandar Berdasarkan Nilai ENUM Baru & Role
        const roleUpper = (CURRENT_USER_ROLE || '').toUpperCase();
        let statusBadge = '';

        if (ro.status === 'DRAFT') {
            statusBadge = `<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-pencil me-1"></i>Draft</span>`;
        } else if (ro.status === 'TERKIRIM') {
            if (roleUpper === 'MEKANIK') {
                statusBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-send me-1"></i>Terkirim</span>`;
            } else {
                statusBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Menunggu Logistik</span>`;
            }
        } else if (ro.status === 'DISETUJUI LOGISTIK') {
            if (roleUpper === 'MEKANIK') {
                statusBadge = `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Disetujui Logistik</span>`;
            } else if (roleUpper === 'LOGISTIK') {
                statusBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-send-check me-1"></i>Menunggu Purchasing</span>`;
            } else {
                statusBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-cart-plus me-1"></i>Menunggu Purchasing</span>`;
            }
        } else if (ro.status === 'TIDAK DISETUJUI LOGISTIK') {
            statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Ditolak Logistik</span>`;
        } else if (ro.status === 'DISETUJUI PURCHASING') {
            statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Disetujui Purchasing (PO Terbit)</span>`;
        } else if (ro.status === 'TIDAK DISETUJUI PURCHASING') {
            statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Ditolak Purchasing</span>`;
        } else if (ro.status === 'DITERIMA FULL') {
            statusBadge = `<span class="badge bg-success text-white border border-success px-2 py-1"><i class="bi bi-box-seam-fill me-1"></i>Diterima Full</span>`;
        } else if (ro.status === 'DITERIMA SEBAGIAN') {
            statusBadge = `<span class="badge bg-warning text-dark border border-warning px-2 py-1"><i class="bi bi-box-seam me-1"></i>Diterima Sebagian</span>`;
        } else if (ro.status === 'BATAL') {
            statusBadge = `<span class="badge bg-dark-subtle text-dark border px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Dibatalkan</span>`;
        } else {
            statusBadge = `<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">${ro.status}</span>`;
        }

        // Info Retur jika ada
        if (ro.retur_info && ro.retur_info.nomor_retur) {
            if (ro.retur_info.status === 'DITERIMA' && parseInt(ro.retur_info.kompensasi) === 1) {
                statusBadge += `<div class="mt-1"><span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size: 0.72rem;" title="Retur ${escapeHtml(ro.retur_info.nomor_retur)} (Tukar Unit Selesai)"><i class="bi bi-arrow-repeat me-1"></i>Tukar Unit Selesai (${escapeHtml(ro.retur_info.nomor_retur)})</span></div>`;
            } else if (ro.retur_info.status === 'DITERIMA' && parseInt(ro.retur_info.kompensasi) === 0) {
                statusBadge += `<div class="mt-1"><span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 0.72rem;" title="Retur ${escapeHtml(ro.retur_info.nomor_retur)} (Potong Tagihan)"><i class="bi bi-percent me-1"></i>Potong Tagihan (${escapeHtml(ro.retur_info.nomor_retur)})</span></div>`;
            } else {
                statusBadge += `<div class="mt-1"><span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1" style="font-size: 0.72rem;" title="Proses Retur: ${escapeHtml(ro.retur_info.status)}"><i class="bi bi-arrow-return-left me-1"></i>Retur: ${escapeHtml(ro.retur_info.status)}</span></div>`;
            }
        }

        // Format Tanggal & Waktu Terpisah (Tanpa detik/milidetik)
        let tanggalStr = '-';
        let waktuStr = '-';
        if (ro.tanggal_ro) {
            const d = new Date(ro.tanggal_ro.replace(/-/g, '/'));
            if (!isNaN(d.getTime())) {
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                const hours = String(d.getHours()).padStart(2, '0');
                const minutes = String(d.getMinutes()).padStart(2, '0');
                tanggalStr = `${day}/${month}/${year}`;
                waktuStr = `${hours}:${minutes}`;
            }
        }

        // Cek Hak Akses & Aksi:
        const isOwner = (ro.id_karyawan == CURRENT_USER_ID_KARYAWAN);
        const isPurchasingOrAdmin = ['PURCHASING', 'STAFF PURCHASING', 'MANAGER', 'MANAGER CABANG', 'ADMIN', 'ADMINISTRATOR'].includes(roleUpper);
        const isStaffLogistikOrAdmin = ['LOGISTIK', 'ADMIN', 'ADMINISTRATOR'].includes(roleUpper);

        // Hak Edit Item/RO: 
        // - DRAFT: Hanya Pembuat / Admin
        // - TERKIRIM: Pembuat (Mekanik) & Logistik / Admin
        // - DISETUJUI LOGISTIK: Khusus Logistik & Admin (Mekanik dikunci)
        let canEdit = false;
        if (ro.status === 'DRAFT') {
            canEdit = isOwner || roleUpper === 'ADMIN' || roleUpper === 'ADMINISTRATOR';
        } else if (ro.status === 'TERKIRIM') {
            canEdit = (isOwner || isStaffLogistikOrAdmin) && !['PURCHASING', 'STAFF PURCHASING'].includes(roleUpper);
        } else if (ro.status === 'DISETUJUI LOGISTIK') {
            canEdit = isStaffLogistikOrAdmin && !['PURCHASING', 'STAFF PURCHASING'].includes(roleUpper);
        }

        // Tombol Proses PO (Khusus Purchasing, Manager, Admin saat DISETUJUI LOGISTIK)
        let btnProsesPo = '';
        const encRoId = encodeId(ro.id_request);
        if (ro.status === 'DISETUJUI LOGISTIK' && isPurchasingOrAdmin) {
            btnProsesPo = `
                <a href="${BASE_URL}/admin/pages/request_order/proses_po.php?id=${encRoId}" class="btn btn-success btn-sm px-2 py-1 text-white shadow-xs" title="Proses ke Purchase Order (PO)">
                    <i class="bi bi-cart-check-fill"></i>
                </a>
            `;
        } else if (ro.status === 'DISETUJUI PURCHASING' && isPurchasingOrAdmin) {
            btnProsesPo = `
                <a href="${BASE_URL}/admin/pages/request_order/proses_po.php?id=${encRoId}" class="btn btn-outline-success btn-sm px-2 py-1" title="Lihat Rincian PO">
                    <i class="bi bi-file-earmark-check"></i>
                </a>
            `;
        }

        html += `
            <tr>
                <td class="text-center text-muted fw-semibold small">${no}</td>
                <td class="text-nowrap">
                    <span class="fw-bold text-dark font-monospace">${ro.nomor}</span>
                </td>
                <td class="small text-dark font-monospace text-nowrap">${tanggalStr}</td>
                <td class="small text-muted font-monospace text-nowrap">${waktuStr}</td>
                <td>
                    <div class="fw-semibold text-dark small">${ro.nama_karyawan}</div>
                    <div class="text-muted" style="font-size: 0.73rem;">${ro.nama_jabatan || '-'}</div>
                </td>
                <td class="text-nowrap">
                    <span class="badge bg-light text-dark border font-monospace small">${ro.nama_site || '-'}</span>
                </td>
                <td class="text-center text-nowrap">${statusBadge}</td>
                <td class="text-center text-nowrap">
                    <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="openDetailModal(${ro.id_request})" title="Lihat Detail RO">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        ${canEdit ? `
                            <a href="${BASE_URL}/admin/pages/request_order/edit.php?id=${encRoId}" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" title="Edit / Tinjau Request Order">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                        ` : ''}
                        ${btnProsesPo}
                    </div>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// -------------------------------------------------------------
// 3. MODAL DETAIL REQUEST ORDER
// -------------------------------------------------------------
function formatRoDetailDate(dateStr) {
    if (!dateStr) return '-';
    const parts = dateStr.split(' ')[0].split('-');
    if (parts.length === 3) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const mIdx = parseInt(parts[1], 10) - 1;
        const day = parts[2].padStart(2, '0');
        const month = months[mIdx] || parts[1];
        const year = parts[0];
        return `${day} ${month} ${year}`;
    }
    return dateStr.split(' ')[0];
}

function formatDateTimeModal(dateStr) {
    return formatRoDetailDate(dateStr);
}

async function openDetailModal(idRequest) {
    // Reset ke tab pertama (Informasi Dokumen)
    const tabTrigger = document.querySelector('#ro-tab-dokumen');
    if (tabTrigger) {
        const tab = bootstrap.Tab.getInstance(tabTrigger) || new bootstrap.Tab(tabTrigger);
        tab.show();
    }

    const res = await apiRequest(`/api/request_order/index.php?id=${idRequest}`);
    if (!res || !res.success) {
        showToast('Gagal memuat detail data Request Order.', 'danger');
        return;
    }

    const ro = res.data;
    document.getElementById('detailNomorRo').textContent = ro.nomor;
    document.getElementById('detailTanggalRo').textContent = formatRoDetailDate(ro.tanggal_ro);
    document.getElementById('detailPemohon').textContent = ro.nama_karyawan || 'Pemohon';
    document.getElementById('detailJabatanDivisi').textContent = ro.nama_jabatan || '-';
    document.getElementById('detailSite').textContent = ro.nama_site ? `${ro.nama_site} (${ro.kode_site || 'SITE'})` : '-';
    document.getElementById('detailVendor').textContent = ro.nama_vendor ? ro.nama_vendor : 'Tidak Ada (Umum)';
    document.getElementById('detailKeterangan').textContent = ro.keterangan || 'Tidak ada catatan khusus.';

    currentDetailRo = ro;
    const isApprovedDetail = (ro.id_karyawan_approved && parseInt(ro.id_karyawan_approved) > 0);
    const roleUpperModal = (CURRENT_USER_ROLE || '').toUpperCase();

    // Badge Approver Logistik
    const approverBadgeEl = document.getElementById('detailApproverBadge');
    if (approverBadgeEl) {
        if (isApprovedDetail) {
            approverBadgeEl.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Disetujui oleh ${ro.nama_approver || 'Logistik'}</span>`;
        } else {
            approverBadgeEl.innerHTML = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Belum Disetujui Logistik</span>`;
        }
    }

    // Tombol Approve di Modal Footer (Khusus Logistik/Admin saat status TERKIRIM)
    const btnModalApprove = document.getElementById('btnModalApproveRo');
    if (btnModalApprove) {
        if (ro.status === 'TERKIRIM' && ['LOGISTIK', 'ADMIN', 'ADMINISTRATOR', 'MANAGER'].includes(roleUpperModal)) {
            btnModalApprove.style.display = 'inline-block';
        } else {
            btnModalApprove.style.display = 'none';
        }
    }

    let statusBadge = '';
    if (ro.status === 'DRAFT') {
        statusBadge = `<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Draft</span>`;
    } else if (ro.status === 'TERKIRIM') {
        if (roleUpperModal === 'MEKANIK') {
            statusBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Terkirim</span>`;
        } else {
            statusBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Menunggu Logistik</span>`;
        }
    } else if (ro.status === 'DISETUJUI LOGISTIK') {
        if (roleUpperModal === 'MEKANIK') {
            statusBadge = `<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">Disetujui Logistik</span>`;
        } else if (roleUpperModal === 'LOGISTIK') {
            statusBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Menunggu Purchasing</span>`;
        } else {
            statusBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Menunggu Purchasing</span>`;
        }
    } else if (ro.status === 'TIDAK DISETUJUI LOGISTIK') {
        statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Ditolak Logistik</span>`;
    } else if (ro.status === 'DISETUJUI PURCHASING') {
        statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">PO Terbit</span>`;
    } else if (ro.status === 'TIDAK DISETUJUI PURCHASING') {
        statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Ditolak Purchasing</span>`;
    } else if (ro.status === 'DITERIMA FULL') {
        statusBadge = `<span class="badge bg-success text-white border border-success px-2 py-1"><i class="bi bi-box-seam-fill me-1"></i>DITERIMA FULL</span>`;
    } else if (ro.status === 'DITERIMA SEBAGIAN') {
        statusBadge = `<span class="badge bg-warning text-dark border border-warning px-2 py-1"><i class="bi bi-box-seam me-1"></i>DITERIMA SEBAGIAN</span>`;
    } else if (ro.status === 'BATAL') {
        statusBadge = `<span class="badge bg-dark-subtle text-dark border px-2 py-1">Dibatalkan</span>`;
    } else {
        statusBadge = `<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">${escapeHtml(ro.status)}</span>`;
    }
    document.getElementById('detailBadgeStatus').innerHTML = statusBadge;

    // Prioritas Badge
    const prioritasBadge = (ro.prioritas === 'URGENT' || ro.prioritas === 'TINGGI') 
        ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>`
        : `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Normal</span>`;
    document.getElementById('detailBadgePrioritas').innerHTML = prioritasBadge;

    // Render Items
    const items = ro.items || [];
    document.getElementById('detailBadgeItemCount').textContent = `${items.length} Item`;
    const tbodyItems = document.getElementById('detailItemsTableBody');
    let itemsHtml = '';

    if (ro.items && ro.items.length > 0) {
        ro.items.forEach((item, idx) => {
            const imgSrc = item.foto1 ? `${BASE_URL}/${item.foto1}` : '';
            const imgHtml = imgSrc 
                ? `<img src="${imgSrc}" class="rounded border me-2 flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover;">` 
                : `<div class="rounded border bg-light text-secondary d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 36px; height: 36px;"><i class="bi bi-box-seam"></i></div>`;

            itemsHtml += `
                <tr>
                    <td class="text-center text-muted fw-bold small">${idx + 1}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${imgHtml}
                            <div>
                                <div class="fw-bold text-dark small">${item.nama_barang}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <span class="badge bg-secondary-subtle text-secondary me-1">${item.nama_merk || 'Umum'}</span>
                                    <span class="badge bg-primary-subtle text-primary">${item.nama_kategori || 'Material'}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="font-monospace small">${item.kode_barang || '-'}</td>
                    <td class="text-center fw-bold text-dark font-monospace">${item.qty}</td>
                    <td class="small"><span class="badge bg-light text-dark border">${item.satuan}</span></td>
                </tr>
            `;
        });
    } else {
        itemsHtml = `<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada rincian material.</td></tr>`;
    }
    tbodyItems.innerHTML = itemsHtml;

    // Render Fulfillment & Retur (Tab 3)
    const tabFulfillmentLi = document.getElementById('ro-tab-fulfillment-li');
    const fulfillmentContent = document.getElementById('detailFulfillmentContent');
    const quickAlert = document.getElementById('detailQuickFulfillmentAlert');

    let hasFulfillment = false;
    let fulfillmentHtml = '<div class="row g-3">';

    if (ro.po_info) {
        hasFulfillment = true;
        fulfillmentHtml += `
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <div class="small fw-bold text-dark mb-2"><i class="bi bi-receipt me-1 text-primary"></i>Purchase Order (PO)</div>
                    <div class="font-monospace fw-bold text-primary">${escapeHtml(ro.po_info.nomor_po)}</div>
                    <div class="small text-muted mt-1">Tgl: ${formatDateTimeModal(ro.po_info.tanggal_po)}</div>
                    <div class="mt-2"><span class="badge bg-success-subtle text-success border">${escapeHtml(ro.po_info.status_po || 'PROSES')}</span></div>
                </div>
            </div>
        `;
    }

    if (ro.receiving_info) {
        hasFulfillment = true;
        fulfillmentHtml += `
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <div class="small fw-bold text-dark mb-2"><i class="bi bi-box-seam me-1 text-success"></i>Penerimaan (Receiving)</div>
                    <div class="font-monospace fw-bold text-dark">${escapeHtml(ro.receiving_info.nomor_rcv)}</div>
                    <div class="small text-muted mt-1">No. Surat Jalan: <strong>${escapeHtml(ro.receiving_info.nomor_sj || '-')}</strong></div>
                    <div class="mt-2"><span class="badge ${parseInt(ro.receiving_info.status_rcv) === 1 ? 'bg-success text-white' : 'bg-warning text-dark'} border">${parseInt(ro.receiving_info.status_rcv) === 1 ? 'Diterima Penuh' : 'Diterima Sebagian'}</span></div>
                </div>
            </div>
        `;
    }

    if (ro.retur_info) {
        hasFulfillment = true;
        const isTukar = parseInt(ro.retur_info.kompensasi) === 1;
        fulfillmentHtml += `
            <div class="col-md-4">
                <div class="p-3 bg-light rounded-3 border h-100">
                    <div class="small fw-bold text-dark mb-2"><i class="bi bi-arrow-return-left me-1 text-danger"></i>Retur Purchase Order</div>
                    <div class="font-monospace fw-bold text-danger">${escapeHtml(ro.retur_info.nomor_po_retur)}</div>
                    <div class="small text-muted mt-1">Kompensasi: <strong>${isTukar ? 'Tukar Unit / Ganti Barang' : 'Potong Tagihan'}</strong></div>
                    <div class="mt-2"><span class="badge ${ro.retur_info.status_retur === 'DITERIMA' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-warning-subtle text-warning-emphasis border'}">${escapeHtml(ro.retur_info.status_retur)}</span></div>
                </div>
            </div>
        `;
    }
    fulfillmentHtml += '</div>';

    if (hasFulfillment) {
        tabFulfillmentLi.style.display = 'block';
        fulfillmentContent.innerHTML = fulfillmentHtml;

        if (ro.retur_info && ro.retur_info.status_retur === 'DITERIMA' && parseInt(ro.retur_info.kompensasi) === 1) {
            quickAlert.style.display = 'block';
            quickAlert.className = 'alert alert-info py-2 px-3 small d-flex align-items-center mb-0 mt-3';
            quickAlert.innerHTML = `<i class="bi bi-info-circle-fill me-2 fs-5"></i> <div>Barang dari Request Order ini sempat mengalami retur kerusakan, namun telah <strong>selesai diganti unit baru</strong> via Retur <strong>${escapeHtml(ro.retur_info.nomor_po_retur)}</strong> sehingga status pengajuan menjadi <strong>DITERIMA FULL</strong>.</div>`;
        } else {
            quickAlert.style.display = 'none';
        }
    } else {
        tabFulfillmentLi.style.display = 'none';
        quickAlert.style.display = 'none';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalDetailRo'));
    modal.show();
}

// -------------------------------------------------------------
// 4. APPROVE REQUEST ORDER (LOGISTIK)
// -------------------------------------------------------------
let currentDetailRo = null;

async function approveRequestOrder(idRequest, nomorRo) {
    if (!confirm(`Apakah Anda yakin ingin menyetujui (Approve) Request Order "${nomorRo}"?\n\nSetelah disetujui, dokumen ini akan diverifikasi dan dapat diproses oleh divisi Purchasing.`)) {
        return;
    }

    try {
        const res = await apiRequest('/api/request_order/approve.php', {
            method: 'POST',
            body: JSON.stringify({ id_request: idRequest })
        });

        if (res && res.success) {
            showToast(res.message || 'Request Order berhasil disetujui.', 'success');
            loadRequestOrders(currentPage);
        } else {
            showToast(res ? res.message : 'Gagal menyetujui Request Order.', 'danger');
        }
    } catch (err) {
        console.error('Approve RO Error:', err);
        showToast('Terjadi kesalahan koneksi jaringan saat menyetujui RO.', 'danger');
    }
}

async function approveRequestOrderFromModal() {
    if (!currentDetailRo) return;
    const idRequest = currentDetailRo.id_request;
    const nomorRo = currentDetailRo.nomor;
    const modalEl = document.getElementById('modalDetailRo');
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (modalInstance) modalInstance.hide();
    await approveRequestOrder(idRequest, nomorRo);
}

// -------------------------------------------------------------
// 5. HAPUS & BATALKAN RO
// -------------------------------------------------------------
async function deleteDraftRo(idRequest, nomorRo) {
    if (!confirm(`Apakah Anda yakin ingin menghapus Draft Request Order "${nomorRo}"?`)) return;

    const res = await apiRequest(`/api/request_order/index.php?id=${idRequest}`, {
        method: 'DELETE'
    });
    if (res && res.success) {
        showToast(res.message, 'success');
        loadRequestOrders();
    } else {
        showToast(res ? res.message : 'Gagal menghapus draft RO.', 'danger');
    }
}

async function cancelRo(idRequest, nomorRo) {
    if (!confirm(`Apakah Anda yakin ingin membatalkan Request Order "${nomorRo}"?`)) return;

    const res = await apiRequest(`/api/request_order/index.php?action=cancel`, {
        method: 'POST',
        body: JSON.stringify({ id_request: idRequest })
    });
    if (res && res.success) {
        showToast(res.message, 'success');
        loadRequestOrders();
    } else {
        showToast(res ? res.message : 'Gagal membatalkan RO.', 'danger');
    }
}

// -------------------------------------------------------------
// 6. PAGINATION & RESET FILTERS
// -------------------------------------------------------------
function renderPagination(p) {
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('paginationList');
    if (!p || p.total_records === 0) {
        info.textContent = 'Menampilkan 0 data';
        list.innerHTML = '';
        return;
    }

    const start = (p.current_page - 1) * p.limit + 1;
    const end = Math.min(p.current_page * p.limit, p.total_records);
    info.textContent = `Menampilkan ${start} - ${end} dari ${p.total_records} data`;

    let html = '';
    html += `<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadRequestOrders(${p.current_page - 1})"><i class="bi bi-chevron-left"></i></a></li>`;
    
    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.current_page - 1 && i <= p.current_page + 1)) {
            html += `<li class="page-item ${i === p.current_page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadRequestOrders(${i})">${i}</a></li>`;
        } else if (i === p.current_page - 2 || i === p.current_page + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${p.current_page === p.total_pages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadRequestOrders(${p.current_page + 1})"><i class="bi bi-chevron-right"></i></a></li>`;
    list.innerHTML = html;
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    currentPage = 1;
    loadRequestOrders();
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
