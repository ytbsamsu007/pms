<?php
/**
 * Halaman Manajemen Daftar Pembayaran Faktur PO (Payment Purchase)
 * Path: admin/pages/pembayaran_po/index.php
 * Khusus Role: FINANCE, PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Pembayaran Faktur PO';
$pageHeading = 'Daftar Pembayaran Faktur Pembelian';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
/* Standardize Height of all form inputs & controls */
.form-control,
.form-control-sm,
.form-select,
.form-select-sm,
.input-group > .form-control,
.input-group > .btn,
.input-group > .input-group-text,
.input-group-sm > .form-control,
.input-group-sm > .btn,
.input-group-sm > .input-group-text {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 0.875rem !important;
}

.table-hover tbody tr:hover {
    background-color: #f8fafc;
}
.cursor-pointer {
    cursor: pointer;
}
.hover-underline:hover {
    text-decoration: underline;
}

/* STAT CARD STYLE (SERAGAM DENGAN RETUR PO) */
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
.stat-icon.danger {
    background-color: #ffe4e6;
    color: #e11d48;
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
</style>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Pembayaran Faktur PO</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/create.php" class="btn btn-primary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                <i class="bi bi-plus-lg me-1"></i> Pembayaran Baru
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="loadPaymentList(1)" style="height: 38px; display: inline-flex; align-items: center;">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- 4 KARTU METRIK RINGKASAN PEMBAYARAN (SERAGAM DENGAN RETUR PO) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Kas Keluar -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card cursor-pointer" onclick="resetFilter()" title="Klik untuk reset filter">
                <div class="stat-icon primary">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label">Total Kas Keluar</div>
                    <div class="stat-value" id="metricTotalKasKeluar">Rp 0</div>
                </div>
            </div>
        </div>

        <!-- 2. Faktur Lunas -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card cursor-pointer" onclick="quickFilterJenis(1)" title="Filter 1x Lunas">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label text-success">Faktur Lunas</div>
                    <div class="stat-value text-success" id="metricTotalLunas">0 Faktur</div>
                </div>
            </div>
        </div>

        <!-- 3. Cicilan / Kredit Aktif -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card cursor-pointer" onclick="quickFilterJenis(0)" title="Filter Kredit / Termin">
                <div class="stat-icon warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label text-warning-emphasis">Kredit Aktif</div>
                    <div class="stat-value text-warning-emphasis" id="metricTotalKredit">0 Faktur</div>
                </div>
            </div>
        </div>

        <!-- 4. Sisa Hutang Faktur -->
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-label text-danger">Hutang Faktur</div>
                    <div class="stat-value text-danger" id="metricTotalHutang">Rp 0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="filterSearch" placeholder="Cari Kode Bayar, No. Faktur, Vendor, Bank..." onkeyup="if(event.key === 'Enter') applyFilter()">
                    </div>
                </div>

                <div class="col-md-3">
                    <select class="form-select text-truncate" style="padding-right: 2.5rem;" id="filterJenis" onchange="applyFilter()">
                        <option value="">Semua Skema Pembayaran</option>
                        <option value="1">1x Bayar (Langsung Lunas)</option>
                        <option value="0">Kredit / Sebagian (Termin)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" class="form-control" id="filterStartDate" title="Tanggal Awal" onchange="applyFilter()">
                </div>

                <div class="col-md-2">
                    <input type="date" class="form-control" id="filterEndDate" title="Tanggal Akhir" onchange="applyFilter()">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="button" class="btn btn-outline-secondary w-100" title="Reset Filter" onclick="resetFilter()" style="height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL RIWAYAT PEMBAYARAN -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="paymentTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="text-center" style="width: 45px;">No</th>
                            <th>No. Faktur</th>
                            <th>No. PO</th>
                            <th>Vendor</th>
                            <th class="text-center">Skema</th>
                            <th class="text-end">Nominal Transfer</th>
                            <th class="text-center" style="width: 130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="paymentTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data pembayaran...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-3 flex-wrap gap-2">
            <div class="small text-muted" id="paginationInfo">Menampilkan 0 dari 0 pembayaran</div>
            <div id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL PEMBAYARAN (DILENGKAPI 5 TAB PEMISAH FUNGSI) -->
<div class="modal fade" id="modalDetailPayment" tabindex="-1" aria-labelledby="modalDetailPaymentLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- MODAL HEADER DENGAN 5 NAV TABS -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailPaymentLabel">
                        Rincian Pembayaran Faktur PO
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Nav Tabs Modal (5 Tab Lengkap & Seragam) -->
                <ul class="nav nav-tabs border-bottom-0" id="modalDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="modal-tab-faktur" data-bs-toggle="tab" data-bs-target="#modal-pane-faktur" type="button" role="tab">
                            <i class="bi bi-receipt me-1 text-primary"></i> 1. Tagihan &amp; Faktur
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-nominal" data-bs-toggle="tab" data-bs-target="#modal-pane-nominal" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1 text-primary"></i> 2. Rincian Transfer
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-vendor-rek" data-bs-toggle="tab" data-bs-target="#modal-pane-vendor-rek" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 3. Rekening Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-rekening" data-bs-toggle="tab" data-bs-target="#modal-pane-rekening" type="button" role="tab">
                            <i class="bi bi-bank me-1 text-primary"></i> 4. Rekening Pengirim
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-approval" data-bs-toggle="tab" data-bs-target="#modal-pane-approval" type="button" role="tab">
                            <i class="bi bi-shield-check me-1 text-primary"></i> 5. Approval &amp; Bukti
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 5 TAB CONTENT -->
            <div class="modal-body p-4">
                <div class="tab-content" id="modalDetailTabContent">
                    <!-- Diisi dinamis oleh JS -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let paymentModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    paymentModalInstance = new bootstrap.Modal(document.getElementById('modalDetailPayment'));
    loadPaymentList(1);
});

function applyFilter() {
    currentPage = 1;
    loadPaymentList(1);
}

function resetFilter() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterJenis').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    applyFilter();
}

function quickFilterJenis(val) {
    document.getElementById('filterJenis').value = val;
    applyFilter();
}

async function loadPaymentList(page = 1) {
    currentPage = page;
    const search = encodeURIComponent(document.getElementById('filterSearch').value.trim());
    const jenis = encodeURIComponent(document.getElementById('filterJenis').value);
    const startDate = encodeURIComponent(document.getElementById('filterStartDate').value);
    const endDate = encodeURIComponent(document.getElementById('filterEndDate').value);

    let url = `<?= BASE_URL ?>/api/pembayaran_po/index.php?page=${page}&limit=10`;
    if (search) url += `&q=${search}`;
    if (jenis !== '') url += `&jenis=${jenis}`;
    if (startDate) url += `&start_date=${startDate}`;
    if (endDate) url += `&end_date=${endDate}`;

    try {
        const res = await fetch(url);
        const result = await res.json();

        if (result && result.success) {
            renderPaymentTable(result.data.rows, result.data.pagination);
            renderMetrics(result.data.metrics);
        } else {
            showEmptyTable(result.message || 'Gagal memuat data pembayaran.');
        }
    } catch (e) {
        showEmptyTable('Terjadi kesalahan jaringan: ' + e.message);
    }
}

function renderMetrics(m) {
    if (!m) return;
    document.getElementById('metricTotalKasKeluar').textContent = formatRupiah(m.total_kas_keluar);
    document.getElementById('metricTotalLunas').textContent = `${m.total_faktur_lunas || 0} Faktur`;
    document.getElementById('metricTotalKredit').textContent = `${m.total_faktur_kredit || 0} Faktur`;
    document.getElementById('metricTotalHutang').textContent = formatRupiah(m.total_sisa_hutang);
}

function renderPaymentTable(rows, pagination) {
    const tbody = document.getElementById('paymentTableBody');
    if (!rows || rows.length === 0) {
        showEmptyTable('Tidak ada riwayat pembayaran yang sesuai.');
        document.getElementById('paginationInfo').textContent = 'Menampilkan 0 dari 0 pembayaran';
        document.getElementById('paginationControls').innerHTML = '';
        return;
    }

    let html = '';
    const startNo = (pagination.page - 1) * pagination.limit;

    rows.forEach((r, idx) => {
        const no = startNo + idx + 1;
        const nominal = parseFloat(r.nominal_pengiriman) || 0;
        const skemaBadge = parseInt(r.jenis_pembayaran) === 1
            ? '<span class="badge bg-success-subtle text-success border px-2 py-1"><i class="bi bi-check-circle me-1"></i>1x Lunas</span>'
            : '<span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1"><i class="bi bi-clock-history me-1"></i>Kredit / Termin</span>';

        const encPayId = encodeId(r.id_pembayaran_detail);

        html += `
        <tr>
            <td class="text-center">${no}</td>
            <td>
                <strong class="font-monospace text-primary cursor-pointer hover-underline" onclick="viewPaymentDetail(${r.id_pembayaran_detail})" title="Klik lihat rincian">${r.nomor_faktur}</strong>
            </td>
            <td>
                <span class="font-monospace fw-semibold text-dark">${r.nomor_po}</span>
            </td>
            <td>
                <span class="fw-semibold text-dark">${r.nama_vendor}</span>
            </td>
            <td class="text-center">
                ${skemaBadge}
            </td>
            <td class="text-end font-monospace fw-bold text-dark fs-6">
                ${formatRupiah(nominal)}
            </td>
            <td class="text-center">
                <div class="d-inline-flex gap-1">
                    <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="viewPaymentDetail(${r.id_pembayaran_detail})" title="Detail Transaksi">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                    <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/edit.php?id=${encPayId}" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" title="Edit Pembayaran">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                    <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/print.php?id=${encPayId}" target="_blank" class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-xs" title="Cetak Bukti Pembayaran">
                        <i class="bi bi-printer-fill"></i>
                    </a>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    document.getElementById('paginationInfo').textContent = `Menampilkan ${rows.length} dari ${pagination.total} pembayaran (Halaman ${pagination.page} dari ${pagination.total_pages || 1})`;
    renderPaginationControls(pagination);
}

function showEmptyTable(message) {
    document.getElementById('paymentTableBody').innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                ${message}
            </td>
        </tr>`;
}

function renderPaginationControls(p) {
    const container = document.getElementById('paginationControls');
    if (!container) return;

    const totalPages = Math.max(1, parseInt(p.total_pages) || 1);
    const currPage = parseInt(p.page) || 1;

    let html = '<ul class="pagination pagination-sm mb-0">';
    html += `<li class="page-item ${currPage <= 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="loadPaymentList(${currPage - 1})" ${currPage <= 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>
             </li>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currPage - 1 && i <= currPage + 1)) {
            html += `<li class="page-item ${i === currPage ? 'active' : ''}">
                        <button class="page-link" onclick="loadPaymentList(${i})">${i}</button>
                     </li>`;
        } else if (i === currPage - 2 || i === currPage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${currPage >= totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="loadPaymentList(${currPage + 1})" ${currPage >= totalPages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>
             </li>`;
    html += '</ul>';

    container.innerHTML = html;
}

async function viewPaymentDetail(idDetail) {
    paymentModalInstance.show();
    document.getElementById('modalDetailTabContent').innerHTML = `
        <div class="text-center py-5 text-muted">
            <div class="spinner-border text-primary me-2"></div>
            <div class="mt-2 small">Memuat rincian pembayaran...</div>
        </div>`;

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/pembayaran_po/index.php?id_detail=${idDetail}`);
        const result = await res.json();

        if (result && result.success && result.data) {
            renderModalContent(result.data);
        } else {
            document.getElementById('modalDetailTabContent').innerHTML = `<div class="alert alert-danger">${result.message || 'Gagal memuat detail.'}</div>`;
        }
    } catch (e) {
        document.getElementById('modalDetailTabContent').innerHTML = `<div class="alert alert-danger">Terjadi kesalahan: ${e.message}</div>`;
    }
}

function renderModalContent(d) {
    // Reset ke Tab 1 saat modal dibuka
    const firstTabBtn = document.getElementById('modal-tab-faktur');
    if (firstTabBtn) {
        const tabTrigger = new bootstrap.Tab(firstTabBtn);
        tabTrigger.show();
    }

    const isLunas = parseInt(d.jenis_pembayaran) === 1;
    const badgeSkema = isLunas 
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">1x Bayar (Lunas)</span>'
        : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold">Kredit / Termin</span>';

    const sisa = parseFloat(d.sisa_piutang) || 0;
    const badgeSisa = sisa <= 0
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">LUNAS</span>'
        : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-semibold">BELUM LUNAS</span>';

    const proofHtml = d.file_bukti_bayar ? `
        <div class="p-3 bg-white rounded-3 border">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <div class="fw-semibold text-dark small">Bukti Transfer Valid</div>
                    <div class="text-muted" style="font-size: 0.72rem;">File: ${d.file_bukti_bayar}</div>
                </div>
                <a href="<?= BASE_URL ?>/uploads/pembayaran/${d.file_bukti_bayar}" target="_blank" class="btn btn-sm btn-outline-primary px-3 fw-semibold">
                    Buka / Unduh File
                </a>
            </div>
        </div>` : `
        <div class="p-3 bg-white rounded-3 border text-center text-muted small fst-italic">
            Tidak ada lampiran file bukti transfer.
        </div>`;

    document.getElementById('modalDetailTabContent').innerHTML = `
        <!-- TAB 1: TAGIHAN & FAKTUR -->
        <div class="tab-pane fade show active" id="modal-pane-faktur" role="tabpanel">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3">Identitas Faktur</h6>
                        <div class="mb-2">
                            <span class="text-muted small d-block">No. Faktur Sistem:</span>
                            <strong class="text-primary font-monospace fs-6">${d.nomor_faktur}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">No. Invoice Vendor:</span>
                            <strong class="text-dark font-monospace">${d.nomor_faktur_vendor || '-'}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Referensi Purchase Order (PO):</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">${d.nomor_po}</span>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Jatuh Tempo Faktur (TOP):</span>
                            <strong class="text-danger font-monospace">${formatDate(d.tanggal_jatuh_tempo)}</strong>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3">Data Vendor &amp; Lokasi</h6>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Nama Vendor:</span>
                            <strong class="text-dark fs-6">${d.nama_vendor}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Site / Gudang Operasional:</span>
                            <span class="text-dark fw-semibold">${d.nama_site}</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Total Tagihan Faktur:</span>
                            <strong class="text-dark font-monospace fs-6">${formatRupiah(d.total_tagihan)}</strong>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Status Faktur Pasca Pembayaran:</span>
                            ${badgeSisa}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: RINCIAN TRANSFER -->
        <div class="tab-pane fade" id="modal-pane-nominal" role="tabpanel">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Waktu &amp; Skema</h6>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Kode Pembayaran:</span>
                            <strong class="text-primary font-monospace fs-6">${d.kode_pembayaran}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Tanggal Bayar:</span>
                            <strong class="text-dark font-monospace">${formatDateTime(d.tanggal_bayar)}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Skema Pembayaran:</span>
                            ${badgeSkema}
                        </div>
                        <div>
                            <span class="text-muted small d-block">No. Referensi Mutasi:</span>
                            <span class="font-monospace fw-semibold text-dark">${d.no_ref || '-'}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Kalkulasi Finansial</h6>
                        
                        <div class="mb-2">
                            <span class="text-muted small d-block">Nominal Transfer Pembayaran:</span>
                            <strong class="text-primary font-monospace fs-5">${formatRupiah(d.nominal_pengiriman)}</strong>
                        </div>

                        <!-- DISKON PEMBAYARAN -->
                        <div class="mb-2">
                            <span class="text-muted small d-block">Diskon / Potongan Tagihan:</span>
                            <strong class="font-monospace ${parseFloat(d.nominal_diskon || 0) > 0 ? 'text-danger' : 'text-muted'}">
                                ${parseFloat(d.nominal_diskon || 0) > 0 ? '- ' + formatRupiah(d.nominal_diskon) : 'Rp 0'}
                            </strong>
                            ${d.keterangan_diskon ? `<div class="mt-1"><span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.72rem;">${escapeHtml(d.keterangan_diskon)}</span></div>` : ''}
                        </div>

                        <div class="mb-2">
                            <span class="text-muted small d-block">Biaya Admin Bank (Operasional Kas):</span>
                            <span class="font-monospace text-muted">${formatRupiah(d.biaya_admin)}</span>
                        </div>

                        <div class="mb-2 pt-2 border-top">
                            <span class="text-muted small d-block">Total Beban Kas Keluar (Transfer + Admin):</span>
                            <strong class="font-monospace text-dark">${formatRupiah(parseFloat(d.nominal_pengiriman) + parseFloat(d.biaya_admin || 0))}</strong>
                        </div>

                        <div class="pt-2 border-top">
                            <span class="text-muted small d-block">Sisa Tagihan Faktur:</span>
                            <strong class="font-monospace fs-6 ${sisa <= 0 ? 'text-success' : 'text-danger'}">${formatRupiah(d.sisa_piutang)}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: REKENING VENDOR -->
        <div class="tab-pane fade" id="modal-pane-vendor-rek" role="tabpanel">
            <div class="card bg-light border-0 rounded-3 p-3">
                <h6 class="fw-bold text-dark mb-3">Rekening Vendor (Tujuan Transfer)</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Bank Tujuan:</span>
                        <span class="badge bg-white text-dark border px-2 py-1 font-monospace fs-6">${d.bank_tujuan || d.bank_vendor || '-'}</span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Nomor Rekening Tujuan:</span>
                        <strong class="font-monospace fs-5 text-primary">${d.norek_tujuan || d.norek_vendor || '-'}</strong>
                    </div>
                    <div class="col-12">
                        <span class="text-muted small d-block">Atas Nama Rekening Tujuan:</span>
                        <strong class="text-dark fs-6">${d.an_pengiriman || d.an_vendor || d.nama_vendor || '-'}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: REKENING PENGIRIM -->
        <div class="tab-pane fade" id="modal-pane-rekening" role="tabpanel">
            <div class="card bg-light border-0 rounded-3 p-3">
                <h6 class="fw-bold text-dark mb-3">Rekening Asal Pengirim (Kas Perusahaan)</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Bank Asal / Kas Pengirim:</span>
                        <span class="badge bg-white text-dark border px-2 py-1 font-monospace fs-6">${d.bank_pengirim || '-'}</span>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Nomor Rekening Pengirim:</span>
                        <strong class="font-monospace fs-5 text-dark">${d.norek_pengirim || '-'}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Atas Nama Rekening Pengirim:</span>
                        <strong class="text-dark fs-6">${d.an_pengirim || '-'}</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">No. Referensi Transfer:</span>
                        <span class="font-monospace text-dark">${d.no_ref || '-'}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 5: APPROVAL & BUKTI -->
        <div class="tab-pane fade" id="modal-pane-approval" role="tabpanel">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3">Otorisasi &amp; Petugas</h6>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Disetujui Oleh (Lisan):</span>
                            <strong class="text-success fs-6">${d.nama_approver || '-'}</strong>
                            ${d.jabatan_approver ? `<div class="text-muted small">(${d.jabatan_approver})</div>` : ''}
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Diinput Oleh Petugas:</span>
                            <span class="text-dark fw-semibold">${d.nama_pembuat || 'Admin'}</span>
                        </div>
                        <div>
                            <span class="text-muted small d-block mb-1">Catatan / Keterangan Pembayaran:</span>
                            <div class="p-2 bg-white rounded border small text-dark">${d.keterangan || '<span class="text-muted fst-italic">Tidak ada catatan.</span>'}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3">Lampiran Bukti Transfer Bank</h6>
                        ${proofHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatDateTime(dateStr) {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} ${hh}:${mm}`;
}

function formatRupiah(num) {
    const n = parseFloat(num) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
