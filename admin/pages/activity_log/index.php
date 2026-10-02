<?php
/**
 * Halaman Audit Trail & Log Aktivitas Pengguna
 * Path: admin/pages/activity_log/index.php
 * Khusus Role: ADMIN, MANAGER, FINANCE, PURCHASING, LOGISTIK
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_MANAGER, ROLE_FINANCE, ROLE_PURCHASING, ROLE_LOGISTIK]);

$pageTitle = 'Audit Trail & Log Aktivitas';
$pageHeading = 'Riwayat Aktivitas Pengguna';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
.filter-select,
.filter-control,
.filter-btn {
    height: 38px !important;
    font-size: 0.875rem !important;
}
.filter-select {
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
}
.filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Sembunyikan icon kalender pada input date (Chrome, Edge, Safari, Firefox) */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="date"]::-webkit-inner-spin-button,
input[type="date"]::-webkit-clear-button {
    display: none !important;
    -webkit-appearance: none !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
    position: absolute !important;
    right: -9999px !important;
}
input[type="date"] {
    -moz-appearance: textfield !important;
    appearance: none !important;
}

.badge-modul-RO { background-color: #6366f1; color: #fff; }
.badge-modul-PO { background-color: #0284c7; color: #fff; }
.badge-modul-RECEIVING { background-color: #0d9488; color: #fff; }
.badge-modul-RETUR { background-color: #ea580c; color: #fff; }
.badge-modul-FAKTUR { background-color: #2563eb; color: #fff; }
.badge-modul-PAYMENT { background-color: #16a34a; color: #fff; }
.badge-modul-AUTH { background-color: #64748b; color: #fff; }

.badge-aksi-CREATE { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.badge-aksi-UPDATE { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
.badge-aksi-UPDATE_STATUS { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
.badge-aksi-APPROVE_LOGISTIK, .badge-aksi-APPROVE_PURCHASING { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.badge-aksi-REJECT_LOGISTIK, .badge-aksi-REJECT_PURCHASING { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.badge-aksi-BATAL, .badge-aksi-DELETE { background-color: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
.badge-aksi-PELUNASAN { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }
.badge-aksi-PEMBAYARAN_SEBAGIAN { background-color: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
.badge-aksi-PRINT_MIGRASI_STOK { background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }

.diff-box {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.825rem;
    max-height: 350px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
}
.diff-box-before {
    background-color: #fff1f2;
    border-color: #fecdd3;
    color: #9f1239;
}
.diff-box-after {
    background-color: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">
               Audit Trail & Log Aktivitas
            </h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary filter-btn px-3" onclick="loadActivityLogs()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Search Input -->
                <div class="flex-grow-1" style="min-width: 220px;">
                    <div class="input-group" style="height: 38px;">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 filter-control" id="filterSearch" placeholder="Cari Dokumen, User, Deskripsi, IP..." oninput="debounceLoadLogs()">
                    </div>
                </div>

                <!-- Modul Filter -->
                <div style="width: 200px;">
                    <select class="form-select filter-select" id="filterModul" onchange="loadActivityLogs(1)">
                        <option value="">Semua Modul</option>
                        <option value="RO">RO (Request Order)</option>
                        <option value="PO">PO (Purchase Order)</option>
                        <option value="RECEIVING">Penerimaan (RCV)</option>
                        <option value="RETUR">Retur PO</option>
                        <option value="FAKTUR">Faktur PO</option>
                        <option value="PAYMENT">Pembayaran PO</option>
                        <option value="AUTH">Autentikasi / Login</option>
                    </select>
                </div>

                <!-- Aksi Filter -->
                <div style="width: 210px;">
                    <select class="form-select filter-select" id="filterAksi" onchange="loadActivityLogs(1)">
                        <option value="">Semua Aksi</option>
                        <option value="CREATE">CREATE (Tambah Baru)</option>
                        <option value="UPDATE">UPDATE (Edit Data)</option>
                        <option value="APPROVE_LOGISTIK">Approve Logistik</option>
                        <option value="APPROVE_PURCHASING">Approve Purchasing</option>
                        <option value="REJECT_LOGISTIK">Tolak Logistik</option>
                        <option value="REJECT_PURCHASING">Tolak Purchasing</option>
                        <option value="PELUNASAN">Pelunasan Faktur</option>
                        <option value="PEMBAYARAN_SEBAGIAN">Bayar Sebagian</option>
                        <option value="PRINT_MIGRASI_STOK">Cetak & Migrasi Stok</option>
                        <option value="BATAL">Pembatalan</option>
                        <option value="DELETE">Penghapusan</option>
                    </select>
                </div>

                <!-- Tanggal Mulai -->
                <div style="width: 155px;">
                    <input type="date" class="form-control filter-control" id="filterStartDate" title="Tanggal Mulai" onchange="loadActivityLogs(1)">
                </div>

                <!-- Tanggal Selesai -->
                <div style="width: 155px;">
                    <input type="date" class="form-control filter-control" id="filterEndDate" title="Tanggal Selesai" onchange="loadActivityLogs(1)">
                </div>

                <!-- Reset Button (Icon Only) -->
                <div>
                    <button type="button" class="btn btn-outline-secondary filter-btn" title="Reset Filter" onclick="resetFilters()" style="width: 38px; height: 38px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- TABLE CONTAINER -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableLogs">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-3 py-3" style="width: 140px;">Waktu</th>
                            <th style="width: 180px;">Pengguna & Role</th>
                            <th style="width: 120px;">Modul</th>
                            <th style="width: 170px;">Aksi</th>
                            <th>No. Referensi</th>
                            <th class="text-center pe-3" style="width: 80px;">Detail</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                <span class="text-muted">Memuat data log aktivitas...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CARD FOOTER / PAGINATION -->
        <div class="card-footer bg-white border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted small" id="paginationInfo">
                Menampilkan 0 data
            </div>
            <div class="d-flex align-items-center gap-2">
                <select class="form-select form-select-sm" id="pageLimit" style="width: 75px;" onchange="loadActivityLogs(1)">
                    <option value="15">15</option>
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0" id="paginationControls">
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL & DIFF -->
<div class="modal fade" id="modalDetailLog" tabindex="-1" aria-labelledby="modalDetailLogLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold" id="modalDetailLogLabel">
                    <i class="bi bi-file-earmark-diff text-primary me-2"></i>Rincian Audit Log & Perubahan Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalDetailContent">
                <!-- Dynamic Content via JS -->
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let debounceTimer = null;
let currentLogsData = [];

function debounceLoadLogs() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadActivityLogs(1);
    }, 400);
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterModul').value = '';
    document.getElementById('filterAksi').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    loadActivityLogs(1);
}

async function loadActivityLogs(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('logsTableBody');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary me-2"></div><span class="text-muted">Memuat data...</span></td></tr>`;

    const search = document.getElementById('filterSearch').value.trim();
    const modul = document.getElementById('filterModul').value;
    const aksi = document.getElementById('filterAksi').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const limit = document.getElementById('pageLimit').value;

    const params = new URLSearchParams({
        page: page,
        limit: limit,
        q: search,
        modul: modul,
        aksi: aksi,
        start_date: startDate,
        end_date: endDate
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/activity_log/index.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            currentLogsData = res.data.rows || [];
            renderTable(currentLogsData);
            renderPagination(res.data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>${res.message || 'Gagal memuat data log.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger"><i class="bi bi-wifi-off me-2"></i>Terjadi kesalahan koneksi server: ${err.message}</td></tr>`;
    }
}

function getModulBadge(modul) {
    if (!modul) return '<span class="badge bg-secondary px-2 py-1">-</span>';
    const m = String(modul).toUpperCase();
    switch (m) {
        case 'RO':
        case 'REQUEST_ORDER':
            return '<span class="badge text-white px-2 py-1" style="background-color: #6366f1;">RO</span>';
        case 'PO':
        case 'PURCHASE_ORDER':
            return '<span class="badge text-white px-2 py-1" style="background-color: #0284c7;">PO</span>';
        case 'RECEIVING':
        case 'RCV':
            return '<span class="badge text-white px-2 py-1" style="background-color: #0d9488;">RECEIVING</span>';
        case 'RETUR':
        case 'RETUR_PO':
            return '<span class="badge text-white px-2 py-1" style="background-color: #ea580c;">RETUR</span>';
        case 'FAKTUR':
        case 'FAKTUR_PO':
            return '<span class="badge text-white px-2 py-1" style="background-color: #2563eb;">FAKTUR</span>';
        case 'PAYMENT':
        case 'PEMBAYARAN':
            return '<span class="badge text-white px-2 py-1" style="background-color: #16a34a;">PAYMENT</span>';
        case 'AUTH':
            return '<span class="badge text-white px-2 py-1" style="background-color: #64748b;">AUTH</span>';
        default:
            return `<span class="badge bg-dark text-white px-2 py-1">${escapeHtml(modul)}</span>`;
    }
}

function getAksiBadge(aksi) {
    if (!aksi) return '<span class="badge bg-light text-dark border px-2 py-1">-</span>';
    const a = String(aksi).toUpperCase();
    if (a === 'CREATE') {
        return '<span class="badge bg-success text-white px-2 py-1">CREATE</span>';
    } else if (a === 'UPDATE') {
        return '<span class="badge bg-info text-dark px-2 py-1">UPDATE</span>';
    } else if (a.startsWith('APPROVE') || a === 'PELUNASAN') {
        return `<span class="badge bg-success text-white px-2 py-1">${escapeHtml(aksi)}</span>`;
    } else if (a.startsWith('REJECT') || a === 'BATAL' || a === 'DELETE') {
        return `<span class="badge bg-danger text-white px-2 py-1">${escapeHtml(aksi)}</span>`;
    } else if (a.startsWith('STATUS_') || a === 'PEMBAYARAN_SEBAGIAN' || a === 'UPDATE_STATUS') {
        return `<span class="badge bg-warning text-dark px-2 py-1">${escapeHtml(aksi)}</span>`;
    } else if (a.startsWith('PRINT')) {
        return `<span class="badge bg-purple text-white px-2 py-1" style="background-color: #9333ea;">PRINT</span>`;
    } else {
        return `<span class="badge bg-secondary text-white px-2 py-1">${escapeHtml(aksi)}</span>`;
    }
}

function getDocumentLink(modul, refId, refNo) {
    if (!refId && !refNo) return '<span class="text-muted">-</span>';
    
    let url = '#';
    let label = escapeHtml(refNo || '#' + refId);
    const m = String(modul || '').toUpperCase();
    
    if (m === 'RO' || m === 'REQUEST_ORDER') {
        url = `<?= BASE_URL ?>/admin/pages/request_order/index.php?q=${encodeURIComponent(refNo || refId)}`;
    } else if (m === 'PO' || m === 'PURCHASE_ORDER') {
        url = `<?= BASE_URL ?>/admin/pages/purchase_order/index.php?q=${encodeURIComponent(refNo || refId)}`;
    } else if (m === 'RECEIVING' || m === 'RCV') {
        url = `<?= BASE_URL ?>/admin/pages/receiving/index.php?q=${encodeURIComponent(refNo || refId)}`;
    } else if (m === 'RETUR' || m === 'RETUR_PO') {
        url = `<?= BASE_URL ?>/admin/pages/retur_po/index.php?q=${encodeURIComponent(refNo || refId)}`;
    } else if (m === 'FAKTUR' || m === 'FAKTUR_PO') {
        url = `<?= BASE_URL ?>/admin/pages/faktur_po/index.php?q=${encodeURIComponent(refNo || refId)}`;
    } else if (m === 'PAYMENT' || m === 'PEMBAYARAN') {
        url = `<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php?q=${encodeURIComponent(refNo || refId)}`;
    } else {
        return `<span class="fw-semibold text-dark">${label}</span>`;
    }

    return `<a href="${url}" class="fw-semibold text-primary text-decoration-none" title="Buka Dokumen">${label}</a>`;
}

function renderTable(rows) {
    const tbody = document.getElementById('logsTableBody');
    if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Tidak ada catatan log aktivitas yang sesuai filter.</td></tr>`;
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        const modulBadge = getModulBadge(r.modul);
        const aksiBadge = getAksiBadge(r.aksi);
        const docLink = getDocumentLink(r.modul, r.id_referensi, r.nomor_referensi);

        let timeFormatted = r.formatted_time || r.created_at || '';
        if (timeFormatted.length > 16) {
            timeFormatted = timeFormatted.substring(0, 16);
        }

        html += `
        <tr>
            <td class="ps-3">
                <div class="fw-semibold text-dark small">${timeFormatted}</div>
            </td>
            <td>
                <div class="fw-semibold text-dark small">${escapeHtml(r.nama_pengguna || 'System')}</div>
                <div class="text-muted" style="font-size: 0.78rem;">${escapeHtml(r.role || '-')}</div>
            </td>
            <td>
                ${modulBadge}
            </td>
            <td>
                ${aksiBadge}
            </td>
            <td>
                ${docLink}
            </td>
            <td class="text-center pe-3">
                <button class="btn btn-sm btn-outline-primary py-1 px-2" title="Lihat Rincian & Diff" onclick="showDetailLog(${idx})">
                    <i class="bi bi-eye"></i>
                </button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function renderPagination(p) {
    if (!p) return;
    const info = document.getElementById('paginationInfo');
    const start = p.total > 0 ? ((p.page - 1) * p.limit) + 1 : 0;
    const end = Math.min(p.page * p.limit, p.total);
    info.textContent = p.total > 0 ? `Menampilkan ${start} - ${end} dari ${p.total} data` : 'Menampilkan 0 data';

    const ul = document.getElementById('paginationControls');
    ul.innerHTML = '';

    const totalPages = Math.max(1, p.total_pages || 1);

    // Prev Button
    ul.innerHTML += `
        <li class="page-item ${p.page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadActivityLogs(${p.page - 1})">&laquo;</a>
        </li>`;

    // Pages
    const maxBtns = 5;
    let startPage = Math.max(1, p.page - Math.floor(maxBtns / 2));
    let endPage = Math.min(totalPages, startPage + maxBtns - 1);
    if (endPage - startPage < maxBtns - 1) {
        startPage = Math.max(1, endPage - maxBtns + 1);
    }

    for (let i = startPage; i <= endPage; i++) {
        ul.innerHTML += `
            <li class="page-item ${i === p.page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="loadActivityLogs(${i})">${i}</a>
            </li>`;
    }

    // Next Button
    ul.innerHTML += `
        <li class="page-item ${p.page >= totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="loadActivityLogs(${p.page + 1})">&raquo;</a>
        </li>`;
}

function showDetailLog(idx) {
    const item = currentLogsData[idx];
    if (!item) return;

    const modalContent = document.getElementById('modalDetailContent');
    const beforeStr = item.data_sebelumnya ? JSON.stringify(item.data_sebelumnya, null, 2) : null;
    const afterStr = item.data_sesudahnya ? JSON.stringify(item.data_sesudahnya, null, 2) : null;

    let diffHtml = '';
    if (!beforeStr && !afterStr) {
        diffHtml = `<div class="text-muted text-center py-3 fst-italic">Tidak ada payload data snapshot (sebelum/sesudah) yang dicatat untuk aksi ini.</div>`;
    } else {
        diffHtml = `
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold text-danger mb-2 small"><i class="bi bi-dash-circle me-1"></i>Data Sebelumnya (Before):</h6>
                    <div class="diff-box diff-box-before">${beforeStr ? escapeHtml(beforeStr) : '<span class="text-muted">Tidak ada data sebelumnya (Record Baru / Insert)</span>'}</div>
                </div>
                <div class="col-12 col-md-6">
                    <h6 class="fw-bold text-success mb-2 small"><i class="bi bi-plus-circle me-1"></i>Data Sesudahnya (After):</h6>
                    <div class="diff-box diff-box-after">${afterStr ? escapeHtml(afterStr) : '<span class="text-muted">Tidak ada data sesudahnya (Record Dihapus / Void)</span>'}</div>
                </div>
            </div>`;
    }

    modalContent.innerHTML = `
        <div class="bg-light p-3 rounded-3 mb-3">
            <div class="row g-2 small">
                <div class="col-6 col-md-4">
                    <div class="text-muted">Waktu Eksekusi:</div>
                    <div class="fw-semibold text-dark">${item.formatted_time || item.created_at}</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">Pengguna:</div>
                    <div class="fw-semibold text-dark">${escapeHtml(item.nama_pengguna || 'System')} <span class="badge bg-secondary">${escapeHtml(item.role || '-')}</span></div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">Modul & Aksi:</div>
                    <div class="fw-semibold text-dark"><span class="badge bg-primary">${escapeHtml(item.modul || '-')}</span> <span class="badge bg-info">${escapeHtml(item.aksi || '-')}</span></div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">Nomor Referensi:</div>
                    <div class="fw-semibold text-primary">${escapeHtml(item.nomor_referensi || '-' )}</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">IP Address:</div>
                    <div class="fw-semibold font-monospace">${escapeHtml(item.ip_address || '-')}</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="text-muted">User Agent:</div>
                    <div class="text-truncate text-muted" title="${escapeHtml(item.user_agent || '-')}">${escapeHtml(item.user_agent || '-')}</div>
                </div>
            </div>
            <hr class="my-2 text-muted">
            <div class="small">
                <span class="text-muted">Deskripsi Aktivitas:</span>
                <div class="fw-semibold text-dark mt-1">${escapeHtml(item.deskripsi || '-')}</div>
            </div>
        </div>

        ${diffHtml}
    `;

    const modal = new bootstrap.Modal(document.getElementById('modalDetailLog'));
    modal.show();
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
    loadActivityLogs(1);
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
