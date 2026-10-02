<?php
/**
 * Halaman Laporan Rekapitulasi Retur Pembelian
 * Path: admin/pages/laporan/retur_pembelian.php
 * Khusus Role: LOGISTIK, PURCHASING, MEKANIK, ADMIN, MANAGER, FINANCE
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection: Logistik, Purchasing, Mekanik, Admin, Manager, Finance
$user = requireAuth([ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MEKANIK, ROLE_ADMIN, ROLE_MANAGER, ROLE_FINANCE]);

$pageTitle = 'Laporan Rekapitulasi Retur Pembelian';
$pageHeading = 'Laporan Rekapitulasi Retur Pembelian';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Rekapitulasi Retur Pembelian</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadReport(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
            <button type="button" class="btn btn-primary filter-btn px-3 shadow-sm fw-semibold" onclick="printReport()">
                <i class="bi bi-printer-fill me-1"></i> Cetak
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Searchable Vendor Filter Dropdown (Persis sama seperti di hutang_vendor.php) -->
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
                <div style="min-width: 140px; flex: 1 1 140px;">
                    <input type="date" class="form-control filter-select" id="filterStartDate" placeholder="Tgl Mulai Retur" onchange="loadReport(1)">
                </div>

                <!-- Tanggal Selesai -->
                <div style="min-width: 140px; flex: 1 1 140px;">
                    <input type="date" class="form-control filter-select" id="filterEndDate" placeholder="Tgl Selesai Retur" onchange="loadReport(1)">
                </div>

                <!-- Filter Status -->
                <div style="min-width: 180px; flex: 1 1 180px;">
                    <select class="form-select filter-select" id="filterStatus" onchange="loadReport(1)">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="MENUNGGU KONFIRMASI VENDOR">MENUNGGU KONFIRMASI VENDOR</option>
                        <option value="DISETUJUI VENDOR">DISETUJUI VENDOR</option>
                        <option value="TIDAK DISETUJUI VENDOR">TIDAK DISETUJUI VENDOR</option>
                        <option value="DIKIRIM KE VENDOR">DIKIRIM KE VENDOR</option>
                        <option value="DITERIMA">DITERIMA</option>
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
                <table class="table table-hover align-middle mb-0" id="tableReturReport">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 text-center align-middle" style="width: 50px;">No</th>
                            <th class="py-3 align-middle" style="min-width: 250px;">Vendor</th>
                            <th class="text-center py-3 align-middle" style="width: 160px;">Kompensasi</th>
                            <th class="py-3 align-middle" style="width: 180px;">No Retur</th>
                            <th class="text-center py-3 align-middle" style="width: 200px;">Status</th>
                            <th class="text-center pe-3 py-3 align-middle" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="returReportTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan retur pembelian...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINATION FOOTER (Standar Sistem) -->
        <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top" id="paginationContainer" style="display: none !important;">
            <div class="text-muted small" id="paginationInfo">
                Menampilkan data...
            </div>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                    <!-- Pagination links -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL POPUP DETAIL RETUR PEMBELIAN (5 TAB SESUAI FUNGSI) -->
<!-- ============================================================= -->
<div class="modal fade" id="modalDetailRetur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 950px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- Modal Header dengan Nav Tabs Seragam -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalHeaderNoRetur">
                            RET-0000-0000
                        </h5>
                        <div id="modalHeaderStatusBadge"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Nav Tabs Modal 4 Tab Sesuai Fungsi -->
                <ul class="nav nav-tabs border-bottom-0" id="modalReturTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="modal-tab-retur-info" data-bs-toggle="tab" data-bs-target="#modal-pane-retur-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Informasi Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-retur-vendor" data-bs-toggle="tab" data-bs-target="#modal-pane-retur-vendor" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 2. Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-retur-pengiriman" data-bs-toggle="tab" data-bs-target="#modal-pane-retur-pengiriman" type="button" role="tab">
                            <i class="bi bi-truck me-1 text-primary"></i> 3. Pengiriman &amp; Persetujuan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-retur-rincian" data-bs-toggle="tab" data-bs-target="#modal-pane-retur-rincian" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> 4. Rincian Barang &amp; Biaya
                            <span class="badge bg-primary text-white ms-1" id="modalTotalItemBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 4 TAB PANE TERSTRUKTUR RAPI -->
            <div class="modal-body p-4">
                <div class="tab-content" id="modalReturTabContent">
                    
                    <!-- TAB 1: INFORMASI UTAMA -->
                    <div class="tab-pane fade show active" id="modal-pane-retur-info" role="tabpanel">
                        <div class="row g-3">
                            <!-- Kolom Kiri: Dokumen Asal Penerimaan (Receiving) -->
                            <div class="col-md-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Dokumen Asal Penerimaan (Receiving)</h6>
                                    
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">No. Penerimaan (RCV)</span>
                                        <strong class="font-monospace text-dark small" id="modalNoRcv">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">No. Purchase Order (PO)</span>
                                        <strong class="font-monospace text-primary small" id="modalNoPo">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">No. Surat Jalan Vendor</span>
                                        <span class="font-monospace text-dark small" id="modalNoSjVendor">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2">
                                        <span class="text-muted small">Site / Lokasi Fisik</span>
                                        <strong class="text-dark small text-end" id="modalSite">-</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Identitas Retur & Skema -->
                            <div class="col-md-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Identitas Retur &amp; Skema</h6>
                                    
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">Nomor Retur PO</span>
                                        <strong class="font-monospace text-primary small" id="modalNomorReturCard">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">Tanggal Pengajuan</span>
                                        <strong class="text-dark font-monospace small" id="modalTanggalRetur">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">Skema Kompensasi</span>
                                        <strong class="text-dark small" id="modalKompensasiBadge">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2">
                                        <span class="text-muted small">Status Dokumen</span>
                                        <span class="text-dark small" id="modalStatusInfoBadge">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: VENDOR -->
                    <div class="tab-pane fade" id="modal-pane-retur-vendor" role="tabpanel">
                        <div class="card border border-light-subtle rounded-3 p-4 bg-light-subtle">
                            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Vendor Rekanan</h6>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-7">
                                    <span class="text-muted small d-block mb-1">Nama Perusahaan Vendor</span>
                                    <strong class="text-dark fs-5 d-block" id="modalVendorNama">-</strong>
                                </div>
                                <div class="col-md-5 text-md-end">
                                    <span class="text-muted small d-block mb-1">Kode Identitas Vendor</span>
                                    <span class="badge bg-white text-dark border font-monospace px-3 py-2 fs-6 shadow-sm" id="modalVendorKode">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: PENGIRIMAN & PERSETUJUAN -->
                    <div class="tab-pane fade" id="modal-pane-retur-pengiriman" role="tabpanel">
                        <div class="row g-3">
                            <!-- Kolom Kiri: Logistik & Dokumen Pengiriman -->
                            <div class="col-md-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Pengiriman &amp; Dokumen Retur</h6>
                                    
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">Jalur &amp; Armada</span>
                                        <strong class="text-dark small text-end" id="modalPengiriman">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">No. Surat Jalan Retur</span>
                                        <strong class="text-dark font-monospace small" id="modalNoSjRetur">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                        <span class="text-muted small">No. Nota Retur Pajak</span>
                                        <strong class="text-dark font-monospace small" id="modalNoNotaPajak">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2">
                                        <span class="text-muted small">Keterangan Pajak PPN</span>
                                        <span class="text-dark font-monospace small" id="modalPajakSummary">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Pejabat Penyetuju Dokumen -->
                            <div class="col-md-6">
                                <div class="card border border-light-subtle rounded-3 p-3 h-100 bg-light-subtle">
                                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Persetujuan Dokumen</h6>
                                    
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                        <span class="text-muted small">Pejabat Penyetuju (Approval)</span>
                                        <strong class="text-dark small" id="modalPenyetuju">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2">
                                        <span class="text-muted small">Status Verifikasi</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                            <i class="bi bi-check-circle me-1"></i>Terverifikasi Sistem
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: RINCIAN BARANG & BIAYA -->
                    <div class="tab-pane fade" id="modal-pane-retur-rincian" role="tabpanel">
                        <!-- Ringkasan Finansial -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 col-6">
                                <div class="p-3 border border-light-subtle rounded-3 bg-light-subtle d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted small d-block">Pajak PPN (<span id="modalRatePajak">0%</span>)</span>
                                        <span class="text-secondary" style="font-size: 0.75rem;">Nominal Pajak Masukan/Keluaran</span>
                                    </div>
                                    <strong class="text-primary font-monospace fs-5" id="modalNominalPajak">Rp 0</strong>
                                </div>
                            </div>
                            <div class="col-md-6 col-6">
                                <div class="p-3 border border-light-subtle rounded-3 bg-light-subtle d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted small d-block">Biaya Pengiriman</span>
                                        <span class="text-secondary" style="font-size: 0.75rem;">Ongkos Kirim Retur Fisik</span>
                                    </div>
                                    <strong class="text-dark font-monospace fs-5" id="modalBiayaReturTab2">Rp 0</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Barang -->
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th style="min-width: 120px;">Kode Barang</th>
                                        <th style="min-width: 200px;">Nama Barang</th>
                                        <th style="min-width: 100px;" class="text-center">Serial No</th>
                                        <th style="min-width: 100px;" class="text-end">KTS Retur</th>
                                        <th style="min-width: 130px;" class="text-end">Harga &amp; Subtotal</th>
                                        <th style="min-width: 150px;">Alasan Retur</th>
                                    </tr>
                                </thead>
                                <tbody id="modalTableItemsBody">
                                    <!-- Dynamic Items -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
#modalReturTabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    color: #475569;
    background: transparent;
    margin-bottom: -1px;
    padding: 0.55rem 0.95rem;
    font-size: 0.85rem;
    transition: all 0.15s ease-in-out;
}
#modalReturTabs .nav-link:hover {
    border-color: #e2e8f0 #e2e8f0 transparent;
    color: #0d6efd;
    background-color: #f8fafc;
}
#modalReturTabs .nav-link.active {
    color: #0f172a !important;
    background-color: #ffffff !important;
    border-color: #dee2e6 #dee2e6 #ffffff !important;
    border-bottom: 1px solid #ffffff !important;
    font-weight: 700 !important;
}
.filter-select {
    padding-top: 0.15rem !important;
    padding-bottom: 0.35rem !important;
    height: 38px !important;
    font-size: 0.875rem !important;
}
.filter-btn {
    height: 38px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 0.875rem !important;
}
.hover-bg-light:hover {
    background-color: #f8f9fa;
}
.cursor-pointer {
    cursor: pointer;
}
.pagination .page-item .page-link {
    color: #495057;
    border-color: #dee2e6;
    padding: 0.35rem 0.65rem;
    font-size: 0.825rem;
}
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
    font-weight: 600;
}
.pagination .page-item.disabled .page-link {
    color: #6c757d;
    background-color: #f8f9fa;
}
</style>

<script>
let allVendorsList = [];
let currentPage = 1;
const fixedLimit = 20;

function formatShortDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const mon = months[d.getMonth()];
    const yr = d.getFullYear();
    return `${day} ${mon} ${yr}`;
}

function formatAlasanRetur(alasan) {
    const map = {
        'RUSAK_FISIK': 'Rusak Fisik / Kirim',
        'CACAT_PRODUKSI': 'Cacat Pabrik Vendor',
        'SALAH_SPESIFIKASI': 'Salah Spesifikasi',
        'KURANG_PENGIRIMAN': 'Kurang Kuantitas',
        'KADALUARSA_EXP': 'Kadaluarsa'
    };
    return map[alasan] || (alasan ? escapeHtml(alasan) : '-');
}

function getStatusBadge(status) {
    switch (status) {
        case 'DRAFT':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1"><i class="bi bi-pencil me-1"></i>DRAFT</span>';
        case 'MENUNGGU KONFIRMASI VENDOR':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1"><i class="bi bi-hourglass-split me-1"></i>MENUNGGU KONFIRMASI VENDOR</span>';
        case 'DISETUJUI VENDOR':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1"><i class="bi bi-check2-circle me-1"></i>DISETUJUI VENDOR</span>';
        case 'TIDAK DISETUJUI VENDOR':
        case 'DITOLAK':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1"><i class="bi bi-x-circle me-1"></i>TIDAK DISETUJUI VENDOR</span>';
        case 'DIKIRIM KE VENDOR':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1"><i class="bi bi-truck me-1"></i>DIKIRIM KE VENDOR</span>';
        case 'DITERIMA':
        case 'SELESAI':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1"><i class="bi bi-check-circle-fill me-1"></i>DITERIMA</span>';
        default:
            return `<span class="badge bg-light text-dark border px-3 py-1">${escapeHtml(status || '-')}</span>`;
    }
}

// -------------------------------------------------------------
// VENDOR DROPDOWN LOGIC (SAMA DENGAN HUTANG_VENDOR.PHP)
// -------------------------------------------------------------
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
    loadReport(1);
}

function resetFilters() {
    document.getElementById('filterVendor').value = '';
    document.getElementById('vendorDropdownLabel').textContent = 'Semua Vendor';
    document.getElementById('vendorDropdownLabel').title = 'Semua Vendor';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    document.getElementById('filterStatus').value = '';
    closeVendorDropdown();
    loadReport(1);
}

function goToPage(page) {
    currentPage = page;
    loadReport(page);
}

// -------------------------------------------------------------
// LOAD REPORT DATA
// -------------------------------------------------------------
async function loadReport(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('returReportTableBody');
    const pagContainer = document.getElementById('paginationContainer');

    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-5 text-muted align-middle">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan...
            </td>
        </tr>
    `;

    const idVendor = document.getElementById('filterVendor').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams({
        page: currentPage,
        limit: fixedLimit
    });
    if (idVendor) params.append('id_vendor', idVendor);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (status) params.append('status', status);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/laporan/retur_pembelian.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            if (json.data.vendors && allVendorsList.length === 0) {
                allVendorsList = json.data.vendors;
                filterVendorList('');
            }

            renderTable(json.data.items || [], json.data.pagination);
            renderPaginationControls(json.data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger align-middle">${json.message || 'Gagal memuat data.'}</td></tr>`;
            pagContainer.style.setProperty('display', 'none', 'important');
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
        pagContainer.style.setProperty('display', 'none', 'important');
    }
}

function renderTable(items, pagination) {
    const tbody = document.getElementById('returReportTableBody');
    const pagContainer = document.getElementById('paginationContainer');

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5 text-muted align-middle">
                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                    Tidak ada data retur pembelian yang sesuai filter.
                </td>
            </tr>
        `;
        pagContainer.style.setProperty('display', 'none', 'important');
        return;
    }

    let html = '';
    const startIndex = pagination ? (pagination.per_page * (pagination.current_page - 1)) : 0;

    items.forEach((it, idx) => {
        const no = startIndex + idx + 1;
        // Format Vendor: Nama Vendor [kode_vendor]
        const vendorText = `${escapeHtml(it.nama_vendor || '-')} <span class="text-muted font-monospace">[${escapeHtml(it.kode_vendor || '-')}]</span>`;
        // Format Kompensasi teks biasa
        const kompensasiText = (parseInt(it.kompensasi, 10) === 1) ? 'Tukar Unit' : 'Potong Tagihan';
        // Status sesuai enum murni (tanpa badge / improve)
        const statusText = escapeHtml(it.status || '-');

        html += `
            <tr class="align-middle">
                <!-- 1. No -->
                <td class="ps-3 text-center text-muted font-monospace small">${no}</td>

                <!-- 2. Vendor: PT ABC [kode_vendor] -->
                <td>
                    <span class="fw-semibold text-dark">${vendorText}</span>
                </td>

                <!-- 3. Kompensasi -->
                <td class="text-center text-dark">
                    ${kompensasiText}
                </td>

                <!-- 4. No Retur -->
                <td>
                    <span class="font-monospace fw-bold text-dark">${escapeHtml(it.nomor_po_retur || '-')}</span>
                </td>

                <!-- 5. Status: Sesuai ENUM Murni -->
                <td class="text-center text-dark">
                    ${statusText}
                </td>

                <!-- 6. Aksi: Cukup Icon Mata Saja -->
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" title="Lihat Detail" onclick="showDetailModal(${it.id_po_retur})" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    pagContainer.style.removeProperty('display');
    pagContainer.style.display = 'flex';
}

function renderPaginationControls(pag) {
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');

    if (!pag || pag.total_items === 0) {
        info.textContent = 'Menampilkan 0 dari 0 data';
        controls.innerHTML = '';
        return;
    }

    const startItem = (pag.current_page - 1) * pag.per_page + 1;
    const endItem = Math.min(pag.current_page * pag.per_page, pag.total_items);
    info.textContent = `Menampilkan ${startItem} - ${endItem} dari ${pag.total_items} data (Total: ${pag.total_pages} Halaman)`;

    if (pag.total_pages <= 1) {
        controls.innerHTML = '';
        return;
    }

    let html = `
        <li class="page-item ${pag.current_page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.current_page - 1})">&laquo; Prev</a>
        </li>
    `;
    
    const startPage = Math.max(1, pag.current_page - 2);
    const endPage = Math.min(pag.total_pages, pag.current_page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === pag.current_page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${p})">${p}</a>
            </li>
        `;
    }
    
    if (endPage < pag.total_pages) {
        if (endPage < pag.total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.total_pages})">${pag.total_pages}</a></li>`;
    }
    
    html += `
        <li class="page-item ${pag.current_page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.current_page + 1})">Next &raquo;</a>
        </li>
    `;

    controls.innerHTML = html;
}

// -------------------------------------------------------------
// SHOW DETAIL POPUP MODAL
// -------------------------------------------------------------
async function showDetailModal(idRetur) {
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/laporan/retur_pembelian.php?id_po_retur=${idRetur}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            const d = json.data;

            document.getElementById('modalHeaderNoRetur').textContent = d.nomor_po_retur || '-';
            // Status Badge kanan atas
            document.getElementById('modalHeaderStatusBadge').innerHTML = getStatusBadge(d.status);

            // Tab 1: Informasi Utama
            document.getElementById('modalNoRcv').textContent = d.nomor_rcv || '-';
            document.getElementById('modalNoPo').textContent = d.nomor_po ? `${d.nomor_po} (${formatShortDate(d.tanggal_po)})` : '-';
            document.getElementById('modalNoSjVendor').textContent = d.nomor_sj_rcv || '-';
            document.getElementById('modalSite').textContent = d.nama_site || '-';
            document.getElementById('modalNomorReturCard').textContent = d.nomor_po_retur || '-';
            document.getElementById('modalTanggalRetur').textContent = d.tanggal_formatted || '-';
            document.getElementById('modalKompensasiBadge').textContent = (parseInt(d.kompensasi, 10) === 1) ? 'Tukar Unit' : 'Potong Tagihan';
            document.getElementById('modalStatusInfoBadge').textContent = d.status || '-';

            // Tab 2: Vendor
            document.getElementById('modalVendorNama').textContent = d.nama_vendor || '-';
            document.getElementById('modalVendorKode').textContent = `Kode: ${d.kode_vendor || '-'}`;

            // Tab 3: Pengiriman & Persetujuan
            document.getElementById('modalPengiriman').textContent = d.pengiriman || 'Ekspedisi / Logistik Internal';
            document.getElementById('modalNoSjRetur').textContent = d.nomor_sj_retur || '-';
            document.getElementById('modalNoNotaPajak').textContent = d.nomor_nota_retur_pajak || '-';
            document.getElementById('modalPajakSummary').textContent = `Rate: ${d.rate_pajak_formatted || '0%'} (${d.nominal_pajak_formatted || 'Rp 0'})`;
            document.getElementById('modalPenyetuju').textContent = d.nama_penyetuju || '-';

            // Tab 4: Rincian Barang & Biaya
            document.getElementById('modalRatePajak').textContent = d.rate_pajak_formatted || '0%';
            document.getElementById('modalNominalPajak').textContent = d.nominal_pajak_formatted || 'Rp 0';
            document.getElementById('modalBiayaReturTab2').textContent = d.biaya_retur_formatted || 'Rp 0';

            // Items Table
            const tItems = document.getElementById('modalTableItemsBody');
            document.getElementById('modalTotalItemBadge').textContent = `${(d.items || []).length} Item`;

            if (d.items && d.items.length > 0) {
                let itHtml = '';
                d.items.forEach((it, idx) => {
                    itHtml += `
                        <tr>
                            <td class="text-center text-muted small">${idx + 1}</td>
                            <td class="font-monospace fw-semibold text-primary">${escapeHtml(it.kode_barang || '-')}</td>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(it.nama_barang || '-')}</div>
                                ${it.keterangan_kerusakan ? `<div class="text-muted small fst-italic">${escapeHtml(it.keterangan_kerusakan)}</div>` : ''}
                            </td>
                            <td class="font-monospace small text-center">${escapeHtml(it.serial_number || '-')}</td>
                            <td class="text-end font-monospace fw-bold fs-6 text-dark">
                                ${Number(it.qty_retur || 0).toLocaleString('id-ID')} <span class="small text-muted">${escapeHtml(it.satuan || 'PCS')}</span>
                                ${(parseInt(d.kompensasi, 10) === 1 && it.qty_diganti > 0) ? `<div class="small text-success fw-normal">Diganti: ${Number(it.qty_diganti).toLocaleString('id-ID')}</div>` : ''}
                            </td>
                            <td class="text-end font-monospace">
                                <div class="text-dark fw-semibold">${it.subtotal_formatted || 'Rp 0'}</div>
                                <div class="text-muted small">@ ${it.harga_satuan_formatted || 'Rp 0'}</div>
                            </td>
                            <td>
                                <span class="text-dark small">${formatAlasanRetur(it.alasan_retur)}</span>
                            </td>
                        </tr>
                    `;
                });
                tItems.innerHTML = itHtml;
            } else {
                tItems.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted small">Tidak ada rincian barang retur.</td></tr>`;
            }

            // Reset Tab ke Tab 1 (Informasi Utama)
            const firstTabEl = document.getElementById('modal-tab-retur-info');
            if (firstTabEl) {
                const tabInstance = bootstrap.Tab.getOrCreateInstance(firstTabEl);
                tabInstance.show();
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailRetur'));
            modal.show();

        } else {
            alert(json.message || 'Gagal mengambil rincian retur.');
        }
    } catch (err) {
        alert('Terjadi kesalahan: ' + err.message);
    }
}

// -------------------------------------------------------------
// PRINT REPORT ACTION
// -------------------------------------------------------------
function printReport() {
    const idVendor = document.getElementById('filterVendor').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams();
    if (idVendor) params.append('id_vendor', idVendor);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (status) params.append('status', status);

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_retur_pembelian.php?${params.toString()}`, '_blank');
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
    loadReport(1);
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
