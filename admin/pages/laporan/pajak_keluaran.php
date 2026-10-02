<?php
/**
 * Halaman Laporan Pajak Keluaran (PPN Keluaran Hasil Penjualan)
 * Path: admin/pages/laporan/pajak_keluaran.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$pageTitle = 'Laporan Pajak Keluaran';
$pageHeading = 'Laporan Pajak Keluaran';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Pajak Keluaran</h4>
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
                <div style="min-width: 140px;">
                    <select class="form-select filter-select font-monospace fw-semibold" id="filterTahun" onchange="loadPajakReport(1)">
                        <option value="0">Semua Tahun</option>
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

                <!-- Search Input -->
                <div class="position-relative" style="min-width: 260px; flex: 1 1 260px;">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control filter-input border-start-0" id="searchKeyword" placeholder="Cari keterangan atau penginput..." oninput="debounceSearch()">
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
                <table class="table table-hover align-middle mb-0" id="tablePajakKeluaran">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-2 align-middle text-center" style="width: 50px;">No</th>
                            <th class="py-2 align-middle" style="min-width: 180px;">Periode</th>
                            <th class="py-2 align-middle" style="min-width: 240px;">Keterangan</th>
                            <th class="py-2 align-middle" style="min-width: 160px;">Diinput Oleh</th>
                            <th class="py-2 align-middle text-end" style="min-width: 180px;">Nilai PPN Keluaran</th>
                            <th class="pe-3 py-2 align-middle text-center" style="width: 60px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="pajakTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data laporan pajak keluaran...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold" id="pajakTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="4" class="ps-3 py-2 text-end text-uppercase">Grand Total:</td>
                            <td class="py-2 text-end text-primary font-monospace fs-6" id="footTotalPpn">0</td>
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

<!-- MODAL RINCIAN PAJAK KELUARAN -->
<div class="modal fade" id="modalRincianPajak" tabindex="-1" aria-labelledby="modalRincianPajakLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white py-3 px-4 border-bottom">
                <h6 class="modal-title fw-bold text-dark mb-0" id="modalRincianPajakLabel">Rincian Pajak Keluaran</h6>
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
                <h6 class="modal-title fw-bold text-dark mb-0" id="modalPilihTahunRekapLabel">Cetak Rekapitulasi Pajak Keluaran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="rekapPilihTahun" class="form-label small fw-semibold text-dark">Pilih Tahun Pajak:</label>
                    <select class="form-select font-monospace fw-bold" id="rekapPilihTahun">
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
let cachedData = [];
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
    // Set default filter bulan sesuai waktu sekarang
    const today = new Date();
    const currentMonth = today.getMonth() + 1; // 1 - 12

    const selBulan = document.getElementById('filterBulan');
    if (selBulan) selBulan.value = currentMonth;

    loadPajakReport(1);
});

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadPajakReport(1);
    }, 400);
}

function resetFilters() {
    const today = new Date();
    document.getElementById('filterTahun').value = today.getFullYear();
    document.getElementById('filterBulan').value = today.getMonth() + 1;
    document.getElementById('searchKeyword').value = '';
    loadPajakReport(1);
}

function formatRupiah(num) {
    if (num === null || num === undefined || isNaN(num)) return '0';
    return Number(num).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function loadPajakReport(page = 1) {
    currentPage = page;
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;
    const q = document.getElementById('searchKeyword').value.trim();

    const tbody = document.getElementById('pajakTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data laporan pajak keluaran...
            </td>
        </tr>
    `;

    try {
        const url = `<?= BASE_URL ?>/api/pajak_keluaran/index.php?page=${currentPage}&limit=${currentLimit}&tahun=${tahun}&bulan=${bulan}&q=${encodeURIComponent(q)}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> ${escapeHtml(json.message || 'Gagal memuat data.')}
                    </td>
                </tr>
            `;
            return;
        }

        const data = json.data;
        cachedData = data.items || [];
        const summary = data.summary || { total_ppn_keluaran: 0 };
        const pagination = data.pagination || { total_records: 0, total_pages: 1, current_page: 1 };

        // Render Table Grand Total Footer
        document.getElementById('footTotalPpn').innerText = formatRupiah(summary.total_ppn_keluaran);
        document.getElementById('pajakTableFoot').style.display = cachedData.length > 0 ? '' : 'none';

        // Render Table Rows
        if (cachedData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                        Tidak ada data pajak keluaran yang sesuai dengan kriteria filter.
                    </td>
                </tr>
            `;
            renderPagination(0, 1, 1, currentLimit);
            return;
        }

        let html = '';
        const offset = (pagination.current_page - 1) * pagination.limit;

        cachedData.forEach((row, idx) => {
            const no = offset + idx + 1;
            const periode = `${row.nama_bulan} ${row.tahun}`;
            const nilai = formatRupiah(row.ppn_keluaran);
            const ket = row.keterangan || '-';
            const penginput = row.nama_karyawan || 'Finance System';

            html += `
                <tr class="align-middle">
                    <td class="ps-3 py-2 text-center text-muted fw-semibold">${no}</td>
                    <td class="py-2">
                        <div class="fw-bold text-dark">${escapeHtml(periode)}</div>
                    </td>
                    <td class="py-2">
                        <div class="text-secondary small text-truncate" style="max-width: 260px;" title="${escapeHtml(ket)}">${escapeHtml(ket)}</div>
                    </td>
                    <td class="py-2">
                        <span class="text-dark fw-semibold small">${escapeHtml(penginput)}</span>
                    </td>
                    <td class="py-2 text-end fw-bold text-primary font-monospace fs-6">
                        ${nilai}
                    </td>
                    <td class="pe-3 py-2 text-center">
                        <button type="button" class="btn btn-sm btn-outline-info p-0 d-inline-flex align-items-center justify-content-center text-dark" title="Lihat Rincian" onclick="showRincian(${idx})" style="width: 28px; height: 28px;">
                            <i class="bi bi-eye-fill small"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        renderPagination(pagination.total_records, pagination.total_pages, pagination.current_page, pagination.limit);

    } catch (e) {
        console.error('Error fetching pajak keluaran:', e);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-danger">
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

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= curPage - 1 && i <= curPage + 1)) {
            html += `
                <li class="page-item ${i === curPage ? 'active' : ''}">
                    <button class="page-link" onclick="loadPajakReport(${i})">${i}</button>
                </li>
            `;
        } else if (i === curPage - 2 || i === curPage + 2) {
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

    const body = document.getElementById('modalRincianBody');
    body.innerHTML = `
        <div class="list-group list-group-flush small">
            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                <span class="text-muted">Periode:</span>
                <strong class="text-dark font-monospace">${item.nama_bulan} ${item.tahun}</strong>
            </div>
            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                <span class="text-muted">Nilai PPN Keluaran:</span>
                <strong class="text-primary font-monospace fs-6">${formatRupiah(item.ppn_keluaran)}</strong>
            </div>
            <div class="list-group-item px-0 py-2">
                <span class="text-muted d-block mb-1">Dibuat / Diinput Oleh:</span>
                <div class="bg-light p-2 rounded border">
                    <div class="fw-bold text-dark">${escapeHtml(item.nama_karyawan || 'Finance System')}</div>
                    <div class="text-muted font-monospace" style="font-size: 0.75rem;">${item.nama_jabatan ? `Jabatan: ${item.nama_jabatan}` : (item.kode_karyawan ? `Kode: ${item.kode_karyawan}` : 'Staff')}</div>
                </div>
            </div>
            <div class="list-group-item px-0 py-2">
                <span class="text-muted d-block mb-1">Keterangan:</span>
                <div class="bg-light p-2 rounded border text-secondary" style="min-height: 50px; white-space: pre-wrap;">${escapeHtml(item.keterangan || '(Tidak ada catatan/keterangan tambahan)')}</div>
            </div>
            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center text-muted" style="font-size: 0.75rem;">
                <span>Waktu Input:</span>
                <span class="font-monospace">${escapeHtml(item.created_at || '-')}</span>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('modalRincianPajak'));
    modal.show();
}

function printReport() {
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;
    const q = document.getElementById('searchKeyword').value.trim();

    const url = `<?= BASE_URL ?>/admin/pages/laporan/print_pajak_keluaran.php?tahun=${tahun}&bulan=${bulan}&q=${encodeURIComponent(q)}&kop=1`;
    window.open(url, '_blank');
}

function openModalPilihTahunRekap() {
    const currentSelectedYear = document.getElementById('filterTahun').value;
    const rekapYearSelect = document.getElementById('rekapPilihTahun');
    if (rekapYearSelect && currentSelectedYear && currentSelectedYear !== '0') {
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

    const url = `<?= BASE_URL ?>/admin/pages/laporan/print_rekapitulasi_pajak_keluaran.php?tahun=${tahun}&kop=1`;
    window.open(url, '_blank');
}
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
