<?php
/**
 * Halaman Manajemen Pajak Keluaran (PPN Keluaran Penjualan / Hasil)
 * Path: admin/pages/pajak_keluaran/index.php
 * Akses: Role FINANCE, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Pajak Keluaran';
$pageHeading = 'Pajak Keluaran';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
.filter-btn {
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.filter-select, .filter-input {
    height: 38px;
    font-size: 0.875rem;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Pajak Keluaran</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm filter-btn fw-semibold" onclick="openModalCreatePajak()">
                <i class="bi bi-plus-lg me-1"></i>Pajak Keluaran
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 filter-btn shadow-sm" onclick="loadPajakList(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-4 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control filter-input border-start-0" id="searchKeyword" placeholder="Cari keterangan atau penginput..." oninput="debounceSearch()">
                    </div>
                </div>

                <!-- Filter Tahun -->
                <div class="col-md-3 col-6">
                    <select class="form-select filter-select font-monospace fw-semibold" id="filterTahun" onchange="loadPajakList(1)">
                        <option value="0">Semua Tahun</option>
                        <?php
                        $curYr = (int)date('Y');
                        for ($y = $curYr - 5; $y <= $curYr + 5; $y++) {
                            $sel = ($y === $curYr) ? 'selected' : '';
                            echo "<option value=\"{$y}\" {$sel}>{$y}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div class="col-md-4 col-4">
                    <select class="form-select filter-select" id="filterBulan" onchange="loadPajakList(1)">
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

                <!-- Reset Filter Button (Icon Saja) -->
                <div class="col-md-1 col-2 text-end">
                    <button type="button" class="btn btn-outline-secondary w-100 filter-btn d-inline-flex align-items-center justify-content-center shadow-xs" onclick="resetFilterPajak()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- TABEL DATA -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="tablePajakKeluaran" style="font-size: 0.875rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Periode</th>
                            <th class="text-end" style="width: 220px;">Nilai</th>
                            <th class="text-center" style="width: 160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPajakKeluaran">
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data pajak keluaran...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINATION & FOOTER -->
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted" id="paginationInfo">Menampilkan 0 data</div>
            <nav id="paginationControls">
                <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL RINCIAN PAJAK KELUARAN -->
<div class="modal fade" id="modalDetailRincianPajak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header py-2 px-3 border-bottom bg-light">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    Rincian Pajak Keluaran
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="list-group list-group-flush small">
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Periode:</span>
                        <strong class="text-dark font-monospace" id="rincianPeriode">-</strong>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                        <span class="text-muted">Nilai PPN Keluaran:</span>
                        <strong class="text-primary font-monospace fs-6" id="rincianNilai">0</strong>
                    </div>
                    <div class="list-group-item px-0 py-2">
                        <span class="text-muted d-block mb-1">Dibuat / Diinput Oleh:</span>
                        <div class="bg-light p-2 rounded border">
                            <div class="fw-bold text-dark" id="rincianPembuat">-</div>
                            <div class="text-muted font-monospace" style="font-size: 0.75rem;" id="rincianJabatanPembuat">-</div>
                        </div>
                    </div>
                    <div class="list-group-item px-0 py-2">
                        <span class="text-muted d-block mb-1">Keterangan:</span>
                        <div class="bg-light p-2 rounded border text-secondary" id="rincianKeterangan" style="min-height: 50px; white-space: pre-wrap;">-</div>
                    </div>
                    <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center text-muted" style="font-size: 0.75rem;">
                        <span>Waktu Input:</span>
                        <span class="font-monospace" id="rincianWaktuInput">-</span>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- MODAL INPUT / EDIT PAJAK KELUARAN -->
<div class="modal fade" id="modalPajakForm" tabindex="-1" aria-labelledby="modalPajakFormLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header py-3 px-4 border-bottom bg-light">
                <h6 class="modal-title fw-bold text-dark mb-0" id="modalPajakFormLabel">
                    <i class="bi bi-receipt-cutoff text-primary me-1"></i> Form Pajak Keluaran
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPajakKeluaran" onsubmit="submitPajakForm(event)">
                <input type="hidden" id="pajakId" value="0">
                <div class="modal-body p-4">
                    <!-- Periode: Tahun & Bulan -->
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark">Tahun <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm font-monospace fw-bold" id="inputTahun" required>
                                <?php
                                $curYr = (int)date('Y');
                                for ($y = $curYr - 3; $y <= $curYr + 5; $y++) {
                                    $sel = ($y === $curYr) ? 'selected' : '';
                                    echo "<option value=\"{$y}\" {$sel}>{$y}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark">Bulan <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm fw-bold" id="inputBulan" required>
                                <option value="01">01 - Januari</option>
                                <option value="02">02 - Februari</option>
                                <option value="03">03 - Maret</option>
                                <option value="04">04 - April</option>
                                <option value="05">05 - Mei</option>
                                <option value="06">06 - Juni</option>
                                <option value="07">07 - Juli</option>
                                <option value="08">08 - Agustus</option>
                                <option value="09">09 - September</option>
                                <option value="10">10 - Oktober</option>
                                <option value="11">11 - November</option>
                                <option value="12">12 - Desember</option>
                            </select>
                        </div>
                    </div>

                    <!-- Nilai PPN Keluaran -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Nilai PPN Keluaran (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                            <input type="text" class="form-control font-monospace fw-bold text-end fs-6" id="inputPpnKeluaran" placeholder="0" required oninput="formatRupiahInput(this)">
                        </div>
                        
                    </div>

                    <!-- Keterangan -->
                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-dark">Keterangan / Catatan</label>
                        <textarea class="form-control form-control-sm" id="inputKeterangan" rows="3" placeholder="Tambahkan catatan opsional (misal: Rekap faktur penjualan masa pajak X)"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-0 d-flex justify-content-end gap-2">
                    
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold shadow-sm" id="btnSubmitPajak">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS (SEDERHANA) -->
<div class="modal fade" id="modalConfirmDeletePajak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title fw-bold mb-0">
                     Hapus Pajak Keluaran
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="small text-dark mb-2">Apakah Anda yakin ingin menghapus data pajak keluaran ini?</p>
                <div class="bg-light p-2 rounded border small mb-1">
                    <div class="fw-bold text-dark" id="deleteTargetPeriode">-</div>
                    <div class="text-primary font-monospace fw-bold" id="deleteTargetNominal">Rp 0</div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-0 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-danger btn-sm fw-semibold" id="btnConfirmDeletePajakExecute" onclick="executeDeletePajak()">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<script>
let currentPage = 1;
let debounceTimer = null;
let formModalInstance = null;
let deleteModalInstance = null;
let targetDeleteId = null;

document.addEventListener('DOMContentLoaded', () => {
    // Set default bulan saat ini di modal
    const curMonth = String(new Date().getMonth() + 1).padStart(2, '0');
    document.getElementById('inputBulan').value = curMonth;

    loadPajakList(1);
});

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadPajakList(1);
    }, 300);
}

function resetFilterPajak() {
    document.getElementById('searchKeyword').value = '';
    document.getElementById('filterTahun').value = '<?= date('Y') ?>';
    document.getElementById('filterBulan').value = '0';
    loadPajakList(1);
}

function formatRupiah(number) {
    return 'Rp ' + Number(number || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function formatRupiahInput(input) {
    let val = input.value.replace(/[^0-9]/g, '');
    if (!val) {
        input.value = '';
        return;
    }
    input.value = Number(val).toLocaleString('id-ID');
}

function getNumericFromInput(val) {
    if (!val) return 0;
    return parseFloat(String(val).replace(/\./g, '').replace(/,/g, '.')) || 0;
}

async function loadPajakList(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('tbodyPajakKeluaran');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');

    const q = document.getElementById('searchKeyword').value.trim();
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data...</td></tr>`;

    try {
        const url = `${BASE_URL}/api/pajak_keluaran/index.php?page=${page}&limit=15&tahun=${encodeURIComponent(tahun)}&bulan=${encodeURIComponent(bulan)}&q=${encodeURIComponent(q)}`;
        const res = await fetch(url);
        const json = await res.json();

        if (json.success && json.data) {
            const items = json.data.items || [];
            const summary = json.data.summary || {};
            const pagination = json.data.pagination || {};

            if (paginationInfo) {
                paginationInfo.innerText = `Menampilkan ${items.length} dari ${pagination.total_records || 0} data (Halaman ${page} dari ${pagination.total_pages || 1})`;
            }

            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox d-block fs-2 mb-2 opacity-50"></i>
                            Tidak ada data pajak keluaran ditemukan.
                        </td>
                    </tr>`;
                if (paginationControls) paginationControls.innerHTML = '';
                return;
            }

            let html = '';
            items.forEach((item, idx) => {
                const no = (page - 1) * 15 + idx + 1;
                const formattedNilai = Number(item.ppn_keluaran || 0).toLocaleString('id-ID');
                const encodedData = encodeURIComponent(JSON.stringify(item));
                
                html += `
                    <tr>
                        <td class="text-center text-muted fw-semibold">${no}</td>
                        <td>
                            <span class="fw-bold text-dark">${escapeHtml(item.nama_bulan)} ${escapeHtml(item.tahun)}</span>
                        </td>
                        <td class="text-end font-monospace fw-bold text-primary fs-6">
                            ${formattedNilai}
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-outline-info btn-sm px-2 py-1 shadow-xs text-dark" onclick="showDetailPajak('${encodedData}')" title="Lihat Rincian">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openModalEditPajak(${item.id_pajak_keluaran})" title="Edit Data">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="confirmDeletePajak(${item.id_pajak_keluaran}, '${escapeHtml(item.periode_formatted)}', ${item.ppn_keluaran})" title="Hapus Data">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            renderPaginationControls(pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Gagal memuat data: ${json.message || ''}</td></tr>`;
        }
    } catch (e) {
        console.error('Error load pajak keluaran:', e);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">Terjadi kesalahan saat memuat data.</td></tr>`;
    }
}

function showDetailPajak(encodedJson) {
    try {
        const item = JSON.parse(decodeURIComponent(encodedJson));
        document.getElementById('rincianPeriode').textContent = `${item.nama_bulan} ${item.tahun}`;
        document.getElementById('rincianNilai').textContent = Number(item.ppn_keluaran || 0).toLocaleString('id-ID');
        document.getElementById('rincianPembuat').textContent = item.nama_karyawan || 'Finance System';
        document.getElementById('rincianJabatanPembuat').textContent = item.nama_jabatan ? `Jabatan: ${item.nama_jabatan}` : (item.kode_karyawan ? `Kode: ${item.kode_karyawan}` : 'Staff');
        document.getElementById('rincianKeterangan').textContent = item.keterangan ? item.keterangan : '(Tidak ada catatan/keterangan tambahan)';
        document.getElementById('rincianWaktuInput').textContent = item.created_at || '-';

        const modalEl = document.getElementById('modalDetailRincianPajak');
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    } catch (e) {
        console.error('Error show detail:', e);
        showToast('Gagal menampilkan rincian data.', 'danger');
    }
}

function renderPaginationControls(p) {
    const container = document.getElementById('paginationControls');
    if (!container) return;

    const totalPages = Math.max(1, parseInt(p.total_pages) || 1);
    const currPage = parseInt(p.current_page || p.page) || 1;

    let html = '<ul class="pagination pagination-sm mb-0">';
    html += `<li class="page-item ${currPage <= 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="loadPajakList(${currPage - 1})" ${currPage <= 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>
             </li>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currPage - 1 && i <= currPage + 1)) {
            html += `<li class="page-item ${i === currPage ? 'active' : ''}">
                        <button class="page-link" onclick="loadPajakList(${i})">${i}</button>
                     </li>`;
        } else if (i === currPage - 2 || i === currPage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${currPage >= totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="loadPajakList(${currPage + 1})" ${currPage >= totalPages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>
             </li>`;
    html += '</ul>';

    container.innerHTML = html;
}

function openModalCreatePajak() {
    document.getElementById('formPajakKeluaran').reset();
    document.getElementById('pajakId').value = '0';
    document.getElementById('modalPajakFormLabel').innerHTML = 'Input Pajak Keluaran';
    
    // Set default ke bulan dan tahun ini
    document.getElementById('inputTahun').value = '<?= date('Y') ?>';
    document.getElementById('inputBulan').value = String(new Date().getMonth() + 1).padStart(2, '0');

    const modalEl = document.getElementById('modalPajakForm');
    if (!formModalInstance) formModalInstance = new bootstrap.Modal(modalEl);
    formModalInstance.show();
}

async function openModalEditPajak(id) {
    try {
        const res = await fetch(`${BASE_URL}/api/pajak_keluaran/index.php?id=${id}`);
        const json = await res.json();

        if (json.success && json.data) {
            const d = json.data;
            document.getElementById('pajakId').value = d.id_pajak_keluaran;
            document.getElementById('inputTahun').value = d.tahun;
            document.getElementById('inputBulan').value = d.bulan_formatted;
            document.getElementById('inputPpnKeluaran').value = Number(d.ppn_keluaran || 0).toLocaleString('id-ID');
            document.getElementById('inputKeterangan').value = d.keterangan || '';

            document.getElementById('modalPajakFormLabel').innerHTML = 'Edit Pajak Keluaran';

            const modalEl = document.getElementById('modalPajakForm');
            if (!formModalInstance) formModalInstance = new bootstrap.Modal(modalEl);
            formModalInstance.show();
        } else {
            showToast(json.message || 'Gagal memuat detail data.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan saat memuat data.', 'danger');
    }
}

async function submitPajakForm(e) {
    e.preventDefault();
    const id = parseInt(document.getElementById('pajakId').value || 0);
    const tahun = document.getElementById('inputTahun').value;
    const bulan = document.getElementById('inputBulan').value;
    const ppnKeluaran = getNumericFromInput(document.getElementById('inputPpnKeluaran').value);
    const keterangan = document.getElementById('inputKeterangan').value.trim();

    const btn = document.getElementById('btnSubmitPajak');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const payload = {
        id_pajak_keluaran: id,
        tahun: tahun,
        bulan: bulan,
        ppn_keluaran: ppnKeluaran,
        keterangan: keterangan
    };

    try {
        const isEdit = id > 0;
        const res = await fetch(`${BASE_URL}/api/pajak_keluaran/index.php`, {
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.success) {
            showToast(json.message || 'Data berhasil disimpan!', 'success');
            const modalEl = document.getElementById('modalPajakForm');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            if (modal) modal.hide();
            loadPajakList(currentPage);
        } else {
            showToast(json.message || 'Gagal menyimpan data.', 'danger');
        }
    } catch (err) {
        console.error('Error submit pajak:', err);
        showToast('Terjadi kesalahan koneksi atau server.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function confirmDeletePajak(id, periode, nominal) {
    targetDeleteId = id;
    document.getElementById('deleteTargetPeriode').textContent = `Periode: ${periode}`;
    document.getElementById('deleteTargetNominal').textContent = formatRupiah(nominal);

    const modalEl = document.getElementById('modalConfirmDeletePajak');
    if (!deleteModalInstance) deleteModalInstance = new bootstrap.Modal(modalEl);
    deleteModalInstance.show();
}

async function executeDeletePajak() {
    if (!targetDeleteId) return;

    const btn = document.getElementById('btnConfirmDeletePajakExecute');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

    try {
        const res = await fetch(`${BASE_URL}/api/pajak_keluaran/index.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_pajak_keluaran: parseInt(targetDeleteId) })
        });
        const json = await res.json();

        if (json.success) {
            showToast(json.message || 'Data berhasil dihapus.', 'success');
            if (deleteModalInstance) deleteModalInstance.hide();
            loadPajakList(currentPage);
        } else {
            showToast(json.message || 'Gagal menghapus data.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan koneksi saat menghapus data.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        targetDeleteId = null;
    }
}
</script>
