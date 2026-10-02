<?php
/**
 * Halaman Manajemen Pengumuman & Informasi Sistem (Tabel `info`)
 * Path: admin/pages/info/index.php
 * Akses: Khusus ROLE_ADMIN
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Terintegrasi dengan izin menu dinamis
$user = requireAuth();
$pageTitle = 'Informasi & Pengumuman';
$pageHeading = 'Manajemen Pengumuman & Informasi';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Halaman & Search Bar & Tombol Tambah -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Manajemen Pengumuman &amp; Informasi</h4>
        </div>
        <div class="d-flex gap-2 align-items-stretch flex-wrap">
            <div class="input-group input-group-sm" style="width: 260px;">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="filterSearch" placeholder="Cari judul / isi..." oninput="debounceInfoSearch()">
            </div>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openAddInfoModal()">
                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Pengumuman Baru
            </button>
        </div>
    </div>

    <!-- Tabel Daftar Pengumuman -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableInfo">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px;">#</th>
                            <th style="min-width: 260px;">Judul &amp; Ringkasan Isi</th>
                            <th style="width: 200px;">Divisi</th>
                            <th class="text-center" style="width: 100px;">Tayang</th>
                            <th class="text-center" style="width: 110px;">Login</th>
                            <th style="width: 160px;">Dibuat Pada</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="infoTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted small">
                                <span class="spinner-border spinner-border-sm me-2 text-primary"></span>Memuat daftar informasi...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Paginasi Footer -->
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted" id="infoPaginationInfo">Menampilkan 0 data</div>
            <nav aria-label="Pagination">
                <ul class="pagination pagination-sm mb-0" id="infoPaginationList"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL TAMBAH / EDIT INFORMASI & PENGUMUMAN (DENGAN TAB)
     ============================================================= -->
<div class="modal fade" id="modalInfoForm" tabindex="-1" aria-labelledby="modalInfoFormLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="modalInfoFormLabel">
                    <i class="bi bi-megaphone-fill me-2"></i>Tambah Pengumuman Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Nav Tabs Fungsional -->
            <div class="bg-light border-bottom px-3 pt-2">
                <ul class="nav nav-tabs border-bottom-0" id="infoFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold small" id="itab-konten" data-bs-toggle="tab" data-bs-target="#ipane-konten" type="button" role="tab">
                            <i class="bi bi-pencil-square me-1 text-primary"></i> 1. Konten Pengumuman
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="itab-sasaran" data-bs-toggle="tab" data-bs-target="#ipane-sasaran" type="button" role="tab">
                            <i class="bi bi-people-fill me-1 text-primary"></i> 2. Sasaran &amp; Publikasi
                        </button>
                    </li>
                </ul>
            </div>

            <form id="formInfoSubmit" onsubmit="handleSaveInfo(event)" enctype="multipart/form-data">
                <input type="hidden" id="infoFormId" name="id_info">
                
                <div class="modal-body p-4">
                    <div class="tab-content">
                        
                        <!-- TAB 1: KONTEN PENGUMUMAN -->
                        <div class="tab-pane fade show active" id="ipane-konten" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label small fw-bold">Judul Pengumuman <span class="text-danger">*</span></label>
                                        <span class="small text-muted" id="charCounterTitle">0 / 60</span>
                                    </div>
                                    <input type="text" class="form-control" id="infoFormJudul" name="judul" maxlength="60" required placeholder="Contoh: Jadwal Maintenance Sistem ERP Bulanan" oninput="updateCharCounter(this, 60, 'charCounterTitle')">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold">Isi Pesan / Informasi <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="infoFormIsi" name="isi" rows="6" required placeholder="Tuliskan rincian detail pengumuman atau instruksi di sini..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: SASARAN & PUBLIKASI -->
                        <div class="tab-pane fade" id="ipane-sasaran" role="tabpanel">
                            <div class="row g-3">
                                <!-- Target Divisi (Checkboxes Grid) -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label small fw-bold mb-0">Divisi Sasaran Penerima Informasi <span class="text-danger">*</span></label>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" id="checkSelectAllDivisi" onchange="toggleSelectAllDivisi(this.checked)">
                                            <label class="form-check-label small fw-semibold text-primary" for="checkSelectAllDivisi">Pilih Semua Divisi (Publik)</label>
                                        </div>
                                    </div>
                                    <div class="border rounded p-3 bg-light" id="divisiCheckboxContainer" style="max-height: 140px; overflow-y: auto;">
                                        <div class="text-muted small">Memuat divisi...</div>
                                    </div>
                                    <div class="form-text small text-muted">Centang divisi yang berhak melihat pengumuman ini.</div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-1 text-muted opacity-25">
                                </div>

                                <!-- Lampiran File / Gambar -->
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Lampiran File / Gambar (Opsional)</label>
                                    <input type="file" class="form-control form-control-sm" id="infoFormFile" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx" onchange="previewFileSelected(this)">
                                    <div class="form-text small text-muted">Format: JPG, PNG, WEBP, PDF, Word, Excel (Maks. 5MB).</div>
                                    
                                    <!-- File lama indicator -->
                                    <div id="existingFileWrapper" class="d-none mt-2">
                                        <span class="badge bg-light text-dark border p-2 d-inline-flex align-items-center gap-2">
                                            <i class="bi bi-file-earmark-text text-primary fs-6"></i>
                                            <span id="existingFileName" class="text-truncate" style="max-width: 220px;">lampiran.png</span>
                                            <a href="javascript:void(0)" id="existingFileLink" target="_blank" class="text-primary small fw-semibold text-decoration-none ms-1">Lihat</a>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-1 text-muted opacity-25">
                                </div>

                                <!-- Status Publikasi & Tampil Login -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Status Tayang Sistem</label>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="infoFormAktif" name="aktif" value="1" checked>
                                        <label class="form-check-label fw-semibold small text-success" id="labelStatusAktif" for="infoFormAktif">Aktif</label>
                                    </div>
                                    <div class="form-text small text-muted">Aktif untuk dashboard &amp; operasional.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Tampil di Halaman Login</label>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" role="switch" id="infoFormTampilLogin" name="tampil_login" value="1" checked>
                                        <label class="form-check-label fw-semibold small text-primary" id="labelStatusTampilLogin" for="infoFormTampilLogin">Tampil di Login</label>
                                    </div>
                                    <div class="form-text small text-muted">Muncul pada feed sebelum user login.</div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveInfoSubmit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                        <i class="bi bi-save me-1"></i> Simpan Informasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL PREVIEW VISUAL PENGUMUMAN
     ============================================================= -->
<div class="modal fade" id="modalInfoPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-eye me-2 text-info"></i>Preview Tampilan Pengumuman
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="previewModalBody">
                <!-- Rendered dynamically -->
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<!-- Client-side Logic Script for Info Management -->
<script>
const CURRENT_USER_ROLE = <?= json_encode($user['role'] ?? '') ?>;
const CURRENT_USER_ID_KARYAWAN = <?= json_encode($user['id_karyawan'] ?? null) ?>;

let infoListCache = [];
let divisiListCache = [];
let currentPage = 1;
let searchDebounceTimer = null;
let infoModalInstance = null;
let previewModalInstance = null;

document.addEventListener('DOMContentLoaded', async () => {
    infoModalInstance = new bootstrap.Modal(document.getElementById('modalInfoForm'));
    previewModalInstance = new bootstrap.Modal(document.getElementById('modalInfoPreview'));
    
    // Listen status switch label toggle (Aktif)
    const aktifSwitch = document.getElementById('infoFormAktif');
    const labelStatus = document.getElementById('labelStatusAktif');
    aktifSwitch.addEventListener('change', () => {
        if (aktifSwitch.checked) {
            labelStatus.textContent = 'Aktif';
            labelStatus.className = 'form-check-label fw-semibold small text-success';
        } else {
            labelStatus.textContent = 'Draft (Non-Aktif)';
            labelStatus.className = 'form-check-label fw-semibold small text-muted';
        }
    });

    // Listen status switch label toggle (Tampil Login)
    const tampilLoginSwitch = document.getElementById('infoFormTampilLogin');
    const labelTampilLogin = document.getElementById('labelStatusTampilLogin');
    tampilLoginSwitch.addEventListener('change', () => {
        if (tampilLoginSwitch.checked) {
            labelTampilLogin.textContent = 'Tampil di Login';
            labelTampilLogin.className = 'form-check-label fw-semibold small text-primary';
        } else {
            labelTampilLogin.textContent = 'Tidak Tampil di Login';
            labelTampilLogin.className = 'form-check-label fw-semibold small text-muted';
        }
    });

    await loadDivisiOptions();
    await loadInfoData(1);
});

function updateCharCounter(inputEl, max, counterId) {
    const len = inputEl.value.length;
    const counterEl = document.getElementById(counterId);
    if (counterEl) {
        counterEl.textContent = `${len} / ${max}`;
        counterEl.className = len >= max ? 'small text-danger fw-bold' : 'small text-muted';
    }
}

// -------------------------------------------------------------
// LOAD DIVISI OPTIONS
// -------------------------------------------------------------
async function loadDivisiOptions() {
    const res = await apiRequest('/api/master/divisi.php?limit=100');
    if (res && res.success) {
        divisiListCache = res.data.items || [];

        // Render Checkbox Grid di Form Modal
        const checkContainer = document.getElementById('divisiCheckboxContainer');
        let checkHtml = '<div class="row g-2">';
        divisiListCache.forEach(d => {
            checkHtml += `
                <div class="col-sm-6">
                    <div class="form-check mb-0">
                        <input class="form-check-input divisi-check" type="checkbox" value="${d.id_divisi}" id="chkDivisi_${d.id_divisi}" onchange="checkIndividualDivisiState()">
                        <label class="form-check-label small" for="chkDivisi_${d.id_divisi}">
                            ${d.nama_divisi}
                        </label>
                    </div>
                </div>
            `;
        });
        checkHtml += '</div>';
        checkContainer.innerHTML = checkHtml;
    }
}

function toggleSelectAllDivisi(isChecked) {
    const checkboxes = document.querySelectorAll('.divisi-check');
    checkboxes.forEach(cb => {
        cb.checked = isChecked;
    });
}

function checkIndividualDivisiState() {
    const checkboxes = document.querySelectorAll('.divisi-check');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    document.getElementById('checkSelectAllDivisi').checked = allChecked;
}

// -------------------------------------------------------------
// LOAD INFO DATA & RENDER TABLE
// -------------------------------------------------------------
async function loadInfoData(page = 1) {
    currentPage = page;
    const search = encodeURIComponent(document.getElementById('filterSearch').value.trim());

    const tbody = document.getElementById('infoTableBody');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted small"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>Memuat daftar informasi...</td></tr>`;

    let url = `/api/master/info.php?page=${page}&limit=10`;
    if (search) url += `&q=${search}`;

    const res = await apiRequest(url);
    if (res && res.success) {
        infoListCache = res.data.items || [];
        renderInfoTable(infoListCache, res.data.pagination);
    } else {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-danger small">Gagal memuat data: ${res ? res.message : 'Koneksi error'}</td></tr>`;
    }
}

function renderInfoTable(items, pagination) {
    const tbody = document.getElementById('infoTableBody');
    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                    Tidak ada pengumuman atau informasi yang ditemukan.
                </td>
            </tr>
        `;
        document.getElementById('infoPaginationInfo').textContent = 'Menampilkan 0 data';
        document.getElementById('infoPaginationList').innerHTML = '';
        return;
    }

    let html = '';
    const startIndex = ((pagination.current_page - 1) * pagination.limit) + 1;

    items.forEach((item, idx) => {
        // Hak akses edit: Hanya Admin atau Karyawan Pembuat
        const canEdit = (CURRENT_USER_ROLE === 'ADMIN') || (CURRENT_USER_ID_KARYAWAN && item.id_karyawan && parseInt(CURRENT_USER_ID_KARYAWAN, 10) === parseInt(item.id_karyawan, 10));

        // Target Divisi Badges
        let divisiBadgeHtml = '';
        if (item.is_all_divisi || (item.divisi_names && item.divisi_names.length === divisiListCache.length)) {
            divisiBadgeHtml = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-globe2 me-1"></i>Semua Divisi (Publik)</span>`;
        } else if (item.divisi_names && item.divisi_names.length > 0) {
            divisiBadgeHtml = '<div class="d-flex flex-wrap gap-1">';
            item.divisi_names.forEach(dName => {
                divisiBadgeHtml += `<span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">${dName}</span>`;
            });
            divisiBadgeHtml += '</div>';
        } else {
            divisiBadgeHtml = `<span class="badge bg-secondary-subtle text-secondary">Semua Divisi</span>`;
        }

        // Status Aktif Toggle Switch
        const isChecked = item.aktif === 1 ? 'checked' : '';
        const statusLabel = item.aktif === 1 ? '<span class="badge bg-success-subtle text-success">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary">Draft</span>';
        const switchAktifDisabled = canEdit ? '' : 'disabled';
        const switchAktifTitle = canEdit ? 'Klik untuk mengaktifkan/menonaktifkan' : 'Hanya pembuat pengumuman atau Administrator yang dapat mengubah status tayang';

        // Tampil Login Toggle Switch
        const isTampilLoginChecked = item.tampil_login === 1 ? 'checked' : '';
        const tampilLoginLabel = item.tampil_login === 1 ? '<span class="badge bg-primary-subtle text-primary">Tampil</span>' : '<span class="badge bg-light text-muted border">Hide</span>';
        const switchLoginDisabled = canEdit ? '' : 'disabled';
        const switchLoginTitle = canEdit ? 'Klik untuk mengatur tayang di login' : 'Hanya pembuat pengumuman atau Administrator yang dapat mengatur tayang di login';

        // Action Buttons
        let actionButtonsHtml = `
            <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="openPreviewModal(${idx})" title="Lihat Rincian Pengumuman">
                <i class="bi bi-eye-fill"></i>
            </button>
        `;
        if (canEdit) {
            actionButtonsHtml += `
                <button type="button" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditInfoModal(${idx})" title="Edit Pengumuman">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteInfo(${item.id_info}, '${escapeHtml(item.judul)}')" title="Hapus Pengumuman">
                    <i class="bi bi-x-octagon-fill"></i>
                </button>
            `;
        }

        html += `
            <tr class="${item.aktif !== 1 ? 'table-light opacity-75' : ''}">
                <td class="text-center font-monospace text-muted small">${startIndex + idx}</td>
                <td>
                    <div class="fw-bold text-dark mb-1" role="button" onclick="openPreviewModal(${idx})" title="Klik untuk membaca selengkapnya">
                        ${escapeHtml(item.judul)}
                    </div>
                    <div class="text-muted small text-truncate" style="max-width: 380px;">
                        ${escapeHtml(item.isi)}
                    </div>
                </td>
                <td>${divisiBadgeHtml}</td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" role="switch" ${isChecked} ${switchAktifDisabled} onchange="toggleInfoStatus(${item.id_info}, this.checked)" title="${switchAktifTitle}">
                    </div>
                    <div style="font-size: 0.72rem;">${statusLabel}</div>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" role="switch" ${isTampilLoginChecked} ${switchLoginDisabled} onchange="toggleInfoTampilLogin(${item.id_info}, this.checked)" title="${switchLoginTitle}">
                    </div>
                    <div style="font-size: 0.72rem;">${tampilLoginLabel}</div>
                </td>
                <td>
                    <div class="small fw-semibold text-dark">${item.tanggal_format}</div>
                    <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-person me-1"></i>${escapeHtml(item.pembuat)}</div>
                </td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        ${actionButtonsHtml}
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Pagination info & controls
    const totalRecords = pagination.total_records;
    const endCount = Math.min(pagination.current_page * pagination.limit, totalRecords);
    document.getElementById('infoPaginationInfo').textContent = `Menampilkan ${startIndex} - ${endCount} dari total ${totalRecords} data`;

    renderPaginationControls(pagination);
}

function renderPaginationControls(p) {
    const container = document.getElementById('infoPaginationList');
    if (p.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    html += `<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadInfoData(${p.current_page - 1})">&laquo;</a></li>`;

    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.current_page - 2 && i <= p.current_page + 2)) {
            html += `<li class="page-item ${i === p.current_page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadInfoData(${i})">${i}</a></li>`;
        } else if (i === p.current_page - 3 || i === p.current_page + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${p.current_page === p.total_pages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadInfoData(${p.current_page + 1})">&raquo;</a></li>`;
    container.innerHTML = html;
}

function debounceInfoSearch() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        loadInfoData(1);
    }, 400);
}

// -------------------------------------------------------------
// TOGGLE STATUS AKTIF & TAMPIL LOGIN
// -------------------------------------------------------------
async function toggleInfoStatus(id, isChecked) {
    const res = await apiRequest('/api/master/info.php', {
        method: 'PUT',
        body: JSON.stringify({
            id_info: id,
            action: 'toggle_aktif',
            aktif: isChecked ? 1 : 0
        })
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        loadInfoData(currentPage);
    } else {
        showToast(res ? res.message : 'Gagal mengubah status', 'error');
        loadInfoData(currentPage);
    }
}

async function toggleInfoTampilLogin(id, isChecked) {
    const res = await apiRequest('/api/master/info.php', {
        method: 'PUT',
        body: JSON.stringify({
            id_info: id,
            action: 'toggle_tampil_login',
            tampil_login: isChecked ? 1 : 0
        })
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        loadInfoData(currentPage);
    } else {
        showToast(res ? res.message : 'Gagal mengubah status tampil login', 'error');
        loadInfoData(currentPage);
    }
}

// -------------------------------------------------------------
// MODAL TAMBAH & EDIT INFORMASI
// -------------------------------------------------------------
function openAddInfoModal() {
    document.getElementById('formInfoSubmit').reset();
    document.getElementById('infoFormId').value = '';
    document.getElementById('existingFileWrapper').classList.add('d-none');
    
    document.getElementById('infoFormAktif').checked = true;
    document.getElementById('labelStatusAktif').textContent = 'Aktif';
    document.getElementById('labelStatusAktif').className = 'form-check-label fw-semibold small text-success';
    
    document.getElementById('infoFormTampilLogin').checked = true;
    document.getElementById('labelStatusTampilLogin').textContent = 'Tampil di Login';
    document.getElementById('labelStatusTampilLogin').className = 'form-check-label fw-semibold small text-primary';

    // Default: Check all divisi
    toggleSelectAllDivisi(true);
    document.getElementById('checkSelectAllDivisi').checked = true;
    updateCharCounter(document.getElementById('infoFormJudul'), 60, 'charCounterTitle');

    // Switch ke Tab 1 (Konten)
    bootstrap.Tab.getOrCreateInstance(document.getElementById('itab-konten')).show();

    document.getElementById('modalInfoFormLabel').innerHTML = '<i class="bi bi-megaphone-fill me-2"></i>Tambah Pengumuman Baru';
    infoModalInstance.show();
}

function openEditInfoModal(idx) {
    const item = infoListCache[idx];
    if (!item) return;

    document.getElementById('formInfoSubmit').reset();
    document.getElementById('infoFormId').value = item.id_info;
    document.getElementById('infoFormJudul').value = item.judul;
    document.getElementById('infoFormIsi').value = item.isi;
    
    updateCharCounter(document.getElementById('infoFormJudul'), 60, 'charCounterTitle');

    // Set Divisi Checkboxes
    if (item.is_all_divisi) {
        toggleSelectAllDivisi(true);
        document.getElementById('checkSelectAllDivisi').checked = true;
    } else {
        toggleSelectAllDivisi(false);
        document.getElementById('checkSelectAllDivisi').checked = false;
        
        const ids = (item.divisi_array || '').split(',').map(s => s.trim());
        ids.forEach(dId => {
            const cb = document.getElementById(`chkDivisi_${dId}`);
            if (cb) cb.checked = true;
        });
        checkIndividualDivisiState();
    }

    // Set Existing File
    const fileWrapper = document.getElementById('existingFileWrapper');
    if (item.file_url) {
        fileWrapper.classList.remove('d-none');
        document.getElementById('existingFileName').textContent = item.file.split('/').pop();
        document.getElementById('existingFileLink').href = item.file_url;
    } else {
        fileWrapper.classList.add('d-none');
    }

    // Set Status Aktif
    const aktifSwitch = document.getElementById('infoFormAktif');
    const labelStatus = document.getElementById('labelStatusAktif');
    aktifSwitch.checked = item.aktif === 1;
    if (item.aktif === 1) {
        labelStatus.textContent = 'Aktif';
        labelStatus.className = 'form-check-label fw-semibold small text-success';
    } else {
        labelStatus.textContent = 'Draft (Non-Aktif)';
        labelStatus.className = 'form-check-label fw-semibold small text-muted';
    }

    // Set Status Tampil Login
    const tampilLoginSwitch = document.getElementById('infoFormTampilLogin');
    const labelTampilLogin = document.getElementById('labelStatusTampilLogin');
    tampilLoginSwitch.checked = item.tampil_login === 1;
    if (item.tampil_login === 1) {
        labelTampilLogin.textContent = 'Tampil di Login';
        labelTampilLogin.className = 'form-check-label fw-semibold small text-primary';
    } else {
        labelTampilLogin.textContent = 'Tidak Tampil di Login';
        labelTampilLogin.className = 'form-check-label fw-semibold small text-muted';
    }

    // Switch ke Tab 1 (Konten)
    bootstrap.Tab.getOrCreateInstance(document.getElementById('itab-konten')).show();

    document.getElementById('modalInfoFormLabel').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Pengumuman';
    infoModalInstance.show();
}

function previewFileSelected(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            showToast('Ukuran file maksimal 5MB.', 'warning');
            input.value = '';
        }
    }
}

// -------------------------------------------------------------
// SAVE INFO (SUBMIT FORM DENGAN MULTIPART UPLOAD)
// -------------------------------------------------------------
async function handleSaveInfo(e) {
    e.preventDefault();
    const id = document.getElementById('infoFormId').value;
    const isEdit = id !== '';
    const btnSubmit = document.getElementById('btnSaveInfoSubmit');

    // Kumpulkan array divisi terpilih
    const selectedDivisi = [];
    document.querySelectorAll('.divisi-check:checked').forEach(cb => {
        selectedDivisi.push(cb.value);
    });

    if (selectedDivisi.length === 0) {
        showToast('Pilih minimal satu divisi sasaran pada Tab 2.', 'warning');
        bootstrap.Tab.getOrCreateInstance(document.getElementById('itab-sasaran')).show();
        return;
    }

    // Buat FormData untuk multipart upload
    const formData = new FormData(document.getElementById('formInfoSubmit'));
    formData.set('divisi_array', selectedDivisi.join(','));
    formData.set('aktif', document.getElementById('infoFormAktif').checked ? '1' : '0');
    formData.set('tampil_login', document.getElementById('infoFormTampilLogin').checked ? '1' : '0');

    if (isEdit) {
        formData.append('_method', 'PUT');
    }

    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    try {
        const res = await fetch(BASE_URL + '/api/master/info.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + API_TOKEN
            },
            body: formData
        });
        const data = await res.json();

        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Informasi';

        if (data && data.success) {
            showToast(data.message, 'success');
            infoModalInstance.hide();
            loadInfoData(isEdit ? currentPage : 1);
        } else {
            showToast(data ? data.message : 'Gagal menyimpan pengumuman', 'error');
        }
    } catch (err) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Informasi';
        showToast('Terjadi kesalahan koneksi server.', 'error');
    }
}

// -------------------------------------------------------------
// DELETE INFO
// -------------------------------------------------------------
async function deleteInfo(id, title) {
    if (!confirm(`Apakah Anda yakin ingin menghapus pengumuman "${title}"?`)) return;

    const res = await apiRequest(`/api/master/info.php?id=${id}`, {
        method: 'DELETE'
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        loadInfoData(currentPage);
    } else {
        showToast(res ? res.message : 'Gagal menghapus pengumuman', 'error');
    }
}

// -------------------------------------------------------------
// PREVIEW MODAL
// -------------------------------------------------------------
function openPreviewModal(idx) {
    const item = infoListCache[idx];
    if (!item) return;

    const isImg = item.file && /\.(jpg|jpeg|png|webp|gif)$/i.test(item.file);
    const body = document.getElementById('previewModalBody');

    let imageHtml = '';
    if (item.file_url && isImg) {
        imageHtml = `
            <div class="rounded-3 overflow-hidden mb-3 border bg-light text-center">
                <img src="${item.file_url}" alt="Attachment" class="img-fluid" style="max-height: 260px; object-fit: contain;">
            </div>
        `;
    }

    let fileDownloadHtml = '';
    if (item.file_url && !isImg) {
        fileDownloadHtml = `
            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                <span class="small text-muted"><i class="bi bi-paperclip me-1"></i>Dokumen Lampiran</span>
                <a href="${item.file_url}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="bi bi-download me-1"></i>Unduh File
                </a>
            </div>
        `;
    }

    body.innerHTML = `
        ${imageHtml}
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
            <div class="d-flex gap-1">
                <span class="badge ${item.aktif === 1 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'} font-monospace">
                    ${item.aktif === 1 ? 'Aktif' : 'Draft'}
                </span>
                <span class="badge ${item.tampil_login === 1 ? 'bg-primary-subtle text-primary' : 'bg-light text-muted border'} font-monospace">
                    ${item.tampil_login === 1 ? 'Tampil di Login' : 'Tidak di Login'}
                </span>
            </div>
            <span class="small text-muted">${item.tanggal_format}</span>
        </div>
        <h5 class="fw-bold text-dark mb-3">${escapeHtml(item.judul)}</h5>
        <div class="p-3 bg-light rounded-3 text-secondary mb-3" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.95rem;">
            ${escapeHtml(item.isi)}
        </div>
        ${fileDownloadHtml}
        <div class="border-top pt-2 text-muted small d-flex justify-content-between">
            <span>Sasaran: <strong>${item.is_all_divisi ? 'Semua Divisi' : item.divisi_names.join(', ')}</strong></span>
            <span>Oleh: <strong>${escapeHtml(item.pembuat)}</strong></span>
        </div>
    `;

    previewModalInstance.show();
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
