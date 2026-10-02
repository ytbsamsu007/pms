<?php
/**
 * Halaman Laporan Pengeluaran Lain-lain (Biaya Retur, Biaya Admin Pembayaran, Biaya Mutasi)
 * Path: admin/pages/laporan/pengeluaran_lain.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$pageTitle = 'Laporan Pengeluaran Lain-lain';
$pageHeading = 'Laporan Pengeluaran Lain-lain';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Pengeluaran Lain-lain</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadPengeluaranReport(1)">
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
                <!-- Filter Kategori Biaya -->
                <div style="width: 250px; min-width: 220px;">
                    <select class="form-select filter-select text-truncate" id="filterKategori" onchange="loadPengeluaranReport(1)">
                        <option value="">Semua Kategori Biaya</option>
                        <option value="Biaya Retur">Biaya Retur</option>
                        <option value="Biaya Admin Pembayaran">Biaya Admin Pembayaran</option>
                        <option value="Biaya Mutasi">Biaya Mutasi</option>
                    </select>
                </div>

                <!-- Filter Tanggal Mulai -->
                <div style="min-width: 150px;">
                    <input type="date" class="form-control filter-select" id="filterStartDate" placeholder="Tgl Mulai" onchange="loadPengeluaranReport(1)">
                </div>

                <!-- Filter Tanggal Selesai -->
                <div style="min-width: 150px;">
                    <input type="date" class="form-control filter-select" id="filterEndDate" placeholder="Tgl Selesai" onchange="loadPengeluaranReport(1)">
                </div>

                <!-- Search Input -->
                <div class="position-relative" style="min-width: 220px; flex: 1 1 220px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="filterSearch" placeholder="Cari nomor ref..." onkeyup="handleSearchKey(event)">
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
                <table class="table table-hover align-middle mb-0" id="tablePengeluaranLain">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-2 align-middle text-center" style="width: 50px;">No</th>
                            <th class="py-2 align-middle" style="min-width: 180px;">Keterangan</th>
                            <th class="py-2 align-middle" style="min-width: 200px;">Nomor Ref</th>
                            <th class="py-2 align-middle" style="min-width: 160px;">Tanggal Ref</th>
                            <th class="pe-3 py-2 align-middle text-end" style="width: 180px;">Biaya</th>
                        </tr>
                    </thead>
                    <tbody id="pengeluaranTableBody">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data pengeluaran lain-lain...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold" id="pengeluaranTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="4" class="ps-3 py-2 text-end text-uppercase">Grand Total:</td>
                            <td class="pe-3 py-2 text-end text-danger font-monospace" id="footTotalBiaya">Rp 0</td>
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

<script>
let currentPage = 1;
let currentLimit = 15;
let cachedData = [];
let debounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    loadPengeluaranReport(1);
});

function formatRupiah(num) {
    if (num === null || num === undefined || isNaN(num)) return 'Rp 0';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function formatDateYMD(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
    const tParts = dateStr.split(' ');
    return tParts[0];
}

function handleSearchKey(e) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadPengeluaranReport(1);
    }, 400);
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
    document.getElementById('filterKategori').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    document.getElementById('filterSearch').value = '';
    loadPengeluaranReport(1);
}

async function loadPengeluaranReport(page = 1) {
    currentPage = page;
    const kategori = encodeURIComponent(document.getElementById('filterKategori').value);
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const search = encodeURIComponent(document.getElementById('filterSearch').value.trim());

    const tbody = document.getElementById('pengeluaranTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data pengeluaran lain-lain...
            </td>
        </tr>
    `;

    try {
        const url = `<?= BASE_URL ?>/api/laporan/pengeluaran_lain.php?page=${currentPage}&limit=${currentLimit}&kategori=${kategori}&start_date=${startDate}&end_date=${endDate}&q=${search}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> ${escapeHtml(json.message || 'Gagal memuat data.')}
                    </td>
                </tr>
            `;
            return;
        }

        const data = json.data;
        cachedData = data.rows || [];

        // Render Table Grand Total Footer
        const grandTotal = data.grand_totals || { total_transaksi: 0, grand_total_biaya: 0 };
        document.getElementById('footTotalBiaya').innerText = formatRupiah(grandTotal.grand_total_biaya);
        document.getElementById('pengeluaranTableFoot').style.display = cachedData.length > 0 ? '' : 'none';

        // Render Table Rows
        if (cachedData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                        Tidak ada data pengeluaran lain-lain yang sesuai.
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
            let badgeKategori = 'bg-secondary';
            if (row.keterangan === 'Biaya Retur') badgeKategori = 'bg-danger-subtle text-danger border border-danger-subtle';
            else if (row.keterangan === 'Biaya Admin Pembayaran') badgeKategori = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            else if (row.keterangan === 'Biaya Mutasi') badgeKategori = 'bg-info-subtle text-info-emphasis border border-info-subtle';

            html += `
                <tr class="align-middle">
                    <td class="ps-3 py-2 text-center text-muted fw-semibold">${no}</td>
                    <td class="py-2">
                        <span class="badge ${badgeKategori} px-2 py-1 fw-semibold">${escapeHtml(row.keterangan)}</span>
                    </td>
                    <td class="py-2 font-monospace fw-bold text-dark">
                        ${escapeHtml(row.nomor_ref || '-')}
                    </td>
                    <td class="py-2 text-dark font-monospace">
                        ${formatDateYMD(row.tanggal_ref)}
                    </td>
                    <td class="pe-3 py-2 text-end fw-bold text-danger font-monospace">
                        ${formatRupiah(row.biaya)}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        renderPagination(data.pagination.total, data.pagination.total_pages, data.pagination.page, data.pagination.limit);

    } catch (e) {
        console.error('Error fetching pengeluaran lain:', e);
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-danger">
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
            <button class="page-link" onclick="loadPengeluaranReport(${curPage - 1})" aria-label="Previous">
                <i class="bi bi-chevron-left"></i>
            </button>
        </li>
    `;

    // Pages
    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= curPage - 2 && p <= curPage + 2)) {
            html += `
                <li class="page-item ${p === curPage ? 'active' : ''}">
                    <button class="page-link" onclick="loadPengeluaranReport(${p})">${p}</button>
                </li>
            `;
        } else if (p === curPage - 3 || p === curPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Next
    html += `
        <li class="page-item ${curPage === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="loadPengeluaranReport(${curPage + 1})" aria-label="Next">
                <i class="bi bi-chevron-right"></i>
            </button>
        </li>
    `;

    list.innerHTML = html;
}

function printReport() {
    const kategori = encodeURIComponent(document.getElementById('filterKategori').value);
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const search = encodeURIComponent(document.getElementById('filterSearch').value.trim());

    const url = `<?= BASE_URL ?>/admin/pages/laporan/print_pengeluaran_lain.php?kategori=${kategori}&start_date=${startDate}&end_date=${endDate}&q=${search}&kop=1`;
    window.open(url, '_blank');
}
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
