<?php
/**
 * Halaman Master Format Penomoran Transaksi
 * Path: admin/pages/penomoran/index.php
 * Khusus Role: ADMIN
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection Khusus ADMIN
$user = requireAuth([ROLE_ADMIN]);

$pageTitle = 'Format Penomoran Transaksi';
$pageHeading = 'Format Penomoran';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Pengaturan Format Penomoran</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/penomoran/create.php" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center">
                <i class="bi bi-plus-circle-fill me-2"></i> Tambah Format
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm d-inline-flex align-items-center" onclick="loadPenomoranList(currentPage)">
                <i class="bi bi-arrow-clockwise me-2"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- TABEL DATA -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablePenomoran">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th style="min-width: 200px;">Nama Penomoran</th>
                            <th style="min-width: 160px;">Tipe Transaksi</th>
                            <th style="min-width: 150px;">Tipe Reset</th>
                            <th style="min-width: 220px;">Format</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPenomoran">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Memuat data penomoran...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Card Footer Pagination -->
        <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top">
            <div class="text-muted small" id="paginationInfo">
                Menampilkan data...
            </div>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS -->
<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle text-danger display-4 mb-3 d-block"></i>
                <h6 class="fw-bold text-dark mb-2">Hapus Format Penomoran?</h6>
                <p class="text-muted small mb-4" id="deletePromptText">Data format penomoran yang dihapus tidak dapat dikembalikan.</p>
                <input type="hidden" id="deleteIdNomor" value="">
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-sm btn-light px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-sm btn-danger px-3 fw-semibold" id="btnConfirmDelete" onclick="executeDelete()">
                        Hapus Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const fixedLimit = 10;
let currentPenomoranList = [];

document.addEventListener('DOMContentLoaded', () => {
    loadPenomoranList(1);

    // Check flash toast from create / edit redirect
    const flash = sessionStorage.getItem('flashToast');
    if (flash) {
        try {
            const data = JSON.parse(flash);
            if (data && data.message) {
                showToast(data.message, data.type || 'success');
            }
        } catch (e) {}
        sessionStorage.removeItem('flashToast');
    }
});

function goToPage(page) {
    if (page < 1) return;
    loadPenomoranList(page);
}

function getRomanMonth(m) {
    const map = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV', 5: 'V', 6: 'VI', 7: 'VII', 8: 'VIII', 9: 'IX', 10: 'X', 11: 'XI', 12: 'XII' };
    return map[parseInt(m)] || 'I';
}

function generatePreviewRealtime(formatStr, digits) {
    if (!formatStr) return '-';
    const now = new Date();
    const year = now.getFullYear().toString();
    const shortYear = year.slice(-2);
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const romanMonth = getRomanMonth(now.getMonth() + 1);
    const day = String(now.getDate()).padStart(2, '0');
    const mockCounter = '123'.padStart(parseInt(digits) || 5, '0');

    let res = formatStr;
    res = res.replaceAll('[YEAR]', year);
    res = res.replaceAll('[SHORT_YEAR]', shortYear);
    res = res.replaceAll('[MONTH]', month);
    res = res.replaceAll('[ROMAN_MONTH]', romanMonth);
    res = res.replaceAll('[DAY]', day);
    res = res.replaceAll('[COUNTER]', mockCounter);
    return res;
}

function getTipeResetBadge(tipe) {
    switch (parseInt(tipe)) {
        case 0:
            return '<span class="badge bg-secondary">Tidak Reset</span>';
        case 1:
            return '<span class="badge bg-info text-dark">Reset Harian</span>';
        case 2:
            return '<span class="badge bg-primary">Reset Bulanan</span>';
        case 3:
            return '<span class="badge bg-warning text-dark">Reset Tahunan</span>';
        default:
            return '<span class="badge bg-light text-dark">-</span>';
    }
}

function getTipeTransaksiBadge(tipe) {
    const map = {
        'REQUEST': '<span class="badge bg-info-subtle text-info border border-info-subtle">Request Order</span>',
        'PURCHASE': '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Purchase Order</span>',
        'RECEIVING': '<span class="badge bg-success-subtle text-success border border-success-subtle">Penerimaan Barang</span>',
        'RETUR PO': '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Retur PO</span>',
        'FAKTUR PO': '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Faktur Pembelian</span>',
        'PAYMENT PO': '<span class="badge bg-dark-subtle text-dark border border-dark-subtle">Pembayaran PO</span>',
        'MUTASI BARANG': '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Mutasi Barang</span>',
        'ADJUSTMENT STOK': '<span class="badge bg-primary-subtle text-dark border border-primary-subtle">Stock Adjustment</span>'
    };
    return map[tipe] || `<span class="badge bg-light text-dark">${escapeHtml(tipe)}</span>`;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

async function loadPenomoranList(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('tbodyPenomoran');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');

    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                Memuat data penomoran...
            </td>
        </tr>
    `;

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/penomoran/index.php?page=${currentPage}&limit=${fixedLimit}`);
        const json = await res.json();

        if (!json.success || !json.data || !json.data.items) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        ${escapeHtml(json.message || 'Gagal memuat data penomoran.')}
                    </td>
                </tr>
            `;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }

        currentPenomoranList = json.data.items;
        const pag = json.data.pagination || {
            page: 1,
            limit: fixedLimit,
            total_records: currentPenomoranList.length,
            total_pages: 1,
            from: currentPenomoranList.length > 0 ? 1 : 0,
            to: currentPenomoranList.length
        };

        if (currentPenomoranList.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox display-6 d-block mb-2 text-secondary"></i>
                        Belum ada format penomoran yang dibuat.
                    </td>
                </tr>
            `;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }

        let html = '';
        currentPenomoranList.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            const previewRealtime = generatePreviewRealtime(item.format, item.digit_counter);
            html += `
                <tr>
                    <td class="text-center text-muted">${rowNumber}</td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(item.nama_penomoran)}</div>
                    </td>
                    <td>${getTipeTransaksiBadge(item.tipe_transaksi)}</td>
                    <td>${getTipeResetBadge(item.tipe_penomoran)}</td>
                    <td>
                        <span class="fw-bold font-monospace text-dark">${escapeHtml(previewRealtime)}</span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <a href="<?= BASE_URL ?>/admin/pages/penomoran/edit.php?id=${encodeId(item.id_nomor)}" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" title="Edit Format Penomoran">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" title="Hapus Format Penomoran" onclick="confirmDelete(${item.id_nomor}, '${escapeHtml(item.nama_penomoran)}')">
                                <i class="bi bi-x-octagon-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        paginationInfo.textContent = `Menampilkan ${pag.from} - ${pag.to} dari ${pag.total_records} data (Total: ${pag.total_pages} Halaman)`;
        renderPagination(pag);

    } catch (err) {
        console.error('Error load penomoran:', err);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-danger">
                    Terjadi kesalahan koneksi server.
                </td>
            </tr>
        `;
    }
}

function renderPagination(pag) {
    const controls = document.getElementById('paginationControls');
    let html = '';

    html += `
        <li class="page-item ${pag.page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page - 1})">&laquo; Prev</a>
        </li>
    `;

    const startPage = Math.max(1, pag.page - 2);
    const endPage = Math.min(pag.total_pages, pag.page + 2);

    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === pag.page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${p})">${p}</a>
            </li>
        `;
    }

    if (endPage < pag.total_pages) {
        if (endPage < pag.total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.total_pages})">${pag.total_pages}</a></li>`;
    }

    html += `
        <li class="page-item ${pag.page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page + 1})">Next &raquo;</a>
        </li>
    `;

    controls.innerHTML = html;
}

function confirmDelete(id, name) {
    document.getElementById('deleteIdNomor').value = id;
    document.getElementById('deletePromptText').innerHTML = `Apakah Anda yakin ingin menghapus format penomoran <strong>${escapeHtml(name)}</strong>?`;
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
    modal.show();
}

async function executeDelete() {
    const id = document.getElementById('deleteIdNomor').value;
    const btn = document.getElementById('btnConfirmDelete');
    if (!id) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/penomoran/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_nomor: id })
        });
        const json = await res.json();

        bootstrap.Modal.getInstance(document.getElementById('modalConfirmDelete')).hide();

        if (json.success) {
            showToast('Format penomoran berhasil dihapus!', 'success');
            loadPenomoranList(currentPage);
        } else {
            showToast(json.message || 'Gagal menghapus format penomoran.', 'danger');
        }
    } catch (err) {
        console.error(err);
        showToast('Terjadi kesalahan jaringan.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Hapus Data';
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
