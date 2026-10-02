<?php
/**
 * Manajemen Hak Akses Menu Dinamis per Jabatan - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Manajemen Menu Jabatan';
$pageHeading = 'Manajemen Menu & Hak Akses Jabatan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- Action Bar: Jabatan Selector, Search, dan Action Buttons -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Pengaturan Menu Dinamis Sidebar</h2>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="input-group input-group-sm" style="width: 240px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" id="menuSearchInput" class="form-control" placeholder="Cari nama menu / link..." oninput="filterMenuTable()">
        </div>
        <button class="btn btn-outline-secondary btn-sm fw-semibold" onclick="openCopyMenuModal()">
            <i class="bi bi-copy me-1"></i> Salin Menu Antar Jabatan
        </button>
        <button class="btn btn-primary btn-sm fw-semibold" onclick="openTambahMenuModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Menu Baru
        </button>
    </div>
</div>

<!-- Filter Divisi & Jabatan Card -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3 bg-white rounded">
        <div class="row align-items-center g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-buildings me-1 text-primary"></i>1. Filter Divisi:</label>
                <select id="selectDivisiFilter" class="form-select form-select-sm fw-semibold" onchange="onFilterDivisiChange()">
                    <option value="">Semua Divisi</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="bi bi-person-badge me-1 text-primary"></i>2. Pilih Jabatan Target:</label>
                <select id="selectJabatanFilter" class="form-select form-select-sm fw-semibold" onchange="loadMenusForJabatan()">
                    <option value="">Memuat daftar jabatan...</option>
                </select>
            </div>
            <div class="col-md-5 text-md-end">
                <div id="jabatanInfoBadge" class="small text-muted">
                    Memuat informasi struktur jabatan...
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Menu Dinamis per Kategori -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableMenuLevel">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 70px;">Urutan</th>
                        <th class="text-center" style="width: 60px;">Icon</th>
                        <th>Nama Menu</th>
                        <th style="width: 140px;">Kategori</th>
                        <th style="width: 130px;">Tipe Menu</th>
                        <th>Link / Target URL</th>
                        <th class="text-center" style="width: 100px;">Akses</th>
                        <th class="text-center" style="width: 100px;">Terlihat</th>
                        <th class="text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="menuTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted small">
                            <span class="spinner-border spinner-border-sm me-2"></span>Memuat daftar konfigurasi menu...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL TAMBAH / EDIT MENU DINAMIS (SERAGAM & TERPADU)
     ============================================================= -->
<div class="modal fade" id="menuFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 800px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="menuForm" onsubmit="handleSaveMenu(event)">
                <input type="hidden" id="formIdMenu" name="id_levelmenu">

                <!-- MODAL HEADER DENGAN NAV TABS TERPADU (SERAGAM DENGAN RO & MASTER LAIN) -->
                <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="menuFormModalTitle">
                            <i class="bi bi-list-check text-primary"></i> Tambah Menu Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <!-- Nav Tabs Modal Form: 3 Tab Spesifik -->
                    <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="menuFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark small py-2 px-3" id="mtab-target" data-bs-toggle="tab" data-bs-target="#mpane-target" type="button" role="tab">
                                <i class="bi bi-buildings me-1 text-primary"></i> 1. Target Jabatan &amp; Divisi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="mtab-struktur" data-bs-toggle="tab" data-bs-target="#mpane-struktur" type="button" role="tab">
                                <i class="bi bi-diagram-3 me-1 text-primary"></i> 2. Struktur &amp; Navigasi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="mtab-icon" data-bs-toggle="tab" data-bs-target="#mpane-icon" type="button" role="tab">
                                <i class="bi bi-palette me-1 text-primary"></i> 3. Icon &amp; Izin Akses
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content">
                        
                        <!-- TAB 1: TARGET JABATAN & DIVISI -->
                        <div class="tab-pane fade show active" id="mpane-target" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">1. Pilih Divisi Organisasi <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="formIdDivisi" required onchange="onFormDivisiChange()">
                                        <option value="">Pilih divisi...</option>
                                    </select>
                                    <div class="form-text small text-muted">Pilih divisi untuk menyaring jabatan di sampingnya.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">2. Pilih Jabatan Target <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm fw-semibold" id="formIdJabatan" required>
                                        <option value="">Pilih divisi terlebih dahulu...</option>
                                    </select>
                                    <div class="form-text small text-muted">Jabatan yang akan memiliki hak akses menu ini.</div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-1 text-muted opacity-25">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">3. Kategori Header Menu di Sidebar <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="formKategoriMenu" required onchange="updateParentMenuOptions()">
                                        <option value="MENU UTAMA">MENU UTAMA</option>
                                        <option value="OPERASIONAL" selected>OPERASIONAL</option>
                                        <option value="MASTER DATA">MASTER DATA</option>
                                        <option value="LAPORAN">LAPORAN</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: STRUKTUR & NAVIGASI -->
                        <div class="tab-pane fade" id="mpane-struktur" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Nama Menu <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="formNamaMenu" required placeholder="Contoh: Request Order, Master Barang, dll">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Urutan Tampil (Sort) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-sm" id="formUrutan" required min="1" value="1">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold d-block">Tipe Struktur Menu</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formTipeMenuRadio" id="tipeSingle" value="single" checked onchange="handleTipeMenuChange()">
                                        <label class="btn btn-outline-secondary btn-sm" for="tipeSingle">Item Menu Tunggal</label>

                                        <input type="radio" class="btn-check" name="formTipeMenuRadio" id="tipeParent" value="parent" onchange="handleTipeMenuChange()">
                                        <label class="btn btn-outline-secondary btn-sm" for="tipeParent">Parent (Dropdown Induk)</label>

                                        <input type="radio" class="btn-check" name="formTipeMenuRadio" id="tipeChild" value="child" onchange="handleTipeMenuChange()">
                                        <label class="btn btn-outline-secondary btn-sm" for="tipeChild">Sub-Menu (Anak Parent)</label>
                                    </div>
                                </div>

                                <div class="col-md-12 d-none" id="parentSelectWrapper">
                                    <label class="form-label small fw-bold">Pilih Induk (Parent Menu) <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="formIdParent">
                                        <option value="">Pilih menu induk...</option>
                                    </select>
                                </div>

                                <div class="col-12" id="linkInputWrapper">
                                    <label class="form-label small fw-bold">Link / Target Path URL</label>
                                    <input type="text" class="form-control form-control-sm" id="formLink" placeholder="/admin/pages/.../index.php atau /admin/dashboard.php">
                                    <div class="form-text small text-muted">Untuk Parent Dropdown, gunakan tanda pagar (#) atau kosongkan.</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: ICON & IZIN AKSES DENGAN REFERENSI -->
                        <div class="tab-pane fade" id="mpane-icon" role="tabpanel">
                            <div class="row g-3">
                                
                                <!-- ICON INPUT DENGAN PREVIEW -->
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold">Class Icon Bootstrap</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text bg-white" id="iconPreviewBox" style="width: 44px; justify-content: center;">
                                            <i class="bi bi-circle fs-5 text-primary" id="iconPreview"></i>
                                        </span>
                                        <input type="text" class="form-control" id="formIcon" value="bi-circle" placeholder="bi-grid-1x2-fill" oninput="updateIconPreview(this.value)">
                                    </div>
                                    <div class="form-text small">Ketik nama class ikon Bootstrap (contoh: <code>bi-box-seam</code>, <code>bi-truck</code>, <code>bi-tags</code>).</div>
                                </div>

                                <!-- LINK REFERENSI OFFICIAL BOOTSTRAP ICONS -->
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold text-muted d-block">Referensi Katalog Icon</label>
                                    <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm w-100 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                        <span>Buka Katalog Icon Bootstrap</span>
                                    </a>
                                    <div class="form-text small text-muted mt-1 text-center">Tersedia ribuan ikon vektor resmi Bootstrap.</div>
                                </div>

                                <!-- PRESET QUICK PICKER -->
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted mb-2">Pilih Cepat Icon Populer:</label>
                                    <div class="d-flex flex-wrap gap-2 p-2 border rounded bg-light" id="iconPresetContainer">
                                        <!-- Rendered Presets -->
                                    </div>
                                </div>

                                <hr class="my-2 text-muted opacity-25">

                                <!-- TOGGLE AKSES & TERLIHAT -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Izin Hak Akses (Akses URL)</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAksesRadio" id="aksesYes" value="1" checked>
                                        <label class="btn btn-outline-success btn-sm" for="aksesYes"><i class="bi bi-check-circle me-1"></i>Diberikan Akses</label>

                                        <input type="radio" class="btn-check" name="formAksesRadio" id="aksesNo" value="0">
                                        <label class="btn btn-outline-danger btn-sm" for="aksesNo"><i class="bi bi-x-circle me-1"></i>Blokir Akses</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Visibilitas di Sidebar Menu</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formTerlihatRadio" id="terlihatYes" value="1" checked>
                                        <label class="btn btn-outline-primary btn-sm" for="terlihatYes"><i class="bi bi-eye me-1"></i>Tampilkan di Sidebar</label>

                                        <input type="radio" class="btn-check" name="formTerlihatRadio" id="terlihatNo" value="0">
                                        <label class="btn btn-outline-secondary btn-sm" for="terlihatNo"><i class="bi bi-eye-slash me-1"></i>Sembunyikan</label>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveMenu" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Konfigurasi Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL SALIN MENU ANTAR JABATAN
     ============================================================= -->
<div class="modal fade" id="copyMenuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-copy text-primary"></i> Salin Template Menu Antar Jabatan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="copyMenuForm" onsubmit="handleCopyMenu(event)">
                <div class="modal-body p-4">
                    <div class="alert alert-warning py-2 px-3 small mb-3 border-0 shadow-sm">
                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i>
                        <strong>Perhatian:</strong> Proses ini akan menggantikan seluruh susunan menu yang ada pada jabatan tujuan dengan susunan menu dari jabatan asal.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Salin Dari Jabatan (Sumber/Template) <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="copyFromJabatan" required>
                            <!-- Rendered dynamically -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Terapkan Ke Jabatan (Tujuan) <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="copyTargetJabatan" required>
                            <!-- Rendered dynamically -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnExecuteCopy" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-check2-all me-1"></i> Mulai Salin Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<!-- Client-side Logic Script for Dynamic Menu Management -->
<script>
let divisiListCache = [];
let jabatanListCache = [];
let menuDataStore = [];
let currentSelectedJabatanId = 0;

// Preset Ikon Populer untuk Quick Picker
const popularIcons = [
    'bi-grid-1x2-fill', 'bi-file-earmark-text-fill', 'bi-plus-circle', 'bi-buildings',
    'bi-diagram-3-fill', 'bi-briefcase-fill', 'bi-geo-alt-fill', 'bi-people-fill',
    'bi-truck', 'bi-boxes', 'bi-tags', 'bi-bookmark-star', 'bi-box-seam',
    'bi-list-check', 'bi-gear-fill', 'bi-receipt', 'bi-cash-coin', 'bi-cart-fill',
    'bi-shield-check', 'bi-bell-fill', 'bi-folder-fill', 'bi-file-bar-graph'
];

document.addEventListener('DOMContentLoaded', async () => {
    renderIconPresets();
    await loadDivisiAndJabatanOptions();
    if (jabatanListCache.length > 0) {
        currentSelectedJabatanId = jabatanListCache[0].id_jabatan;
        document.getElementById('selectJabatanFilter').value = currentSelectedJabatanId;
        await loadMenusForJabatan();
    }
});

function renderIconPresets() {
    const container = document.getElementById('iconPresetContainer');
    if (!container) return;
    let html = '';
    popularIcons.forEach(ic => {
        html += `
            <button type="button" class="btn btn-sm btn-white border px-2 py-1 shadow-sm d-flex align-items-center gap-1" onclick="selectPresetIcon('${ic}')" title="${ic}">
                <i class="bi ${ic} text-primary"></i>
                <span style="font-size: 0.72rem;">${ic.replace('bi-', '')}</span>
            </button>
        `;
    });
    container.innerHTML = html;
}

function selectPresetIcon(iconClass) {
    document.getElementById('formIcon').value = iconClass;
    updateIconPreview(iconClass);
}

function updateIconPreview(val) {
    const preview = document.getElementById('iconPreview');
    const clean = val.trim() || 'bi-circle';
    preview.className = `bi ${clean} fs-5 text-primary`;
}

// -------------------------------------------------------------
// LOAD DIVISI & JABATAN OPTIONS (HIERARKIS)
// -------------------------------------------------------------
async function loadDivisiAndJabatanOptions() {
    // 1. Ambil list Divisi
    const resDiv = await apiRequest('/api/master/divisi.php?limit=100');
    if (resDiv && resDiv.success) {
        divisiListCache = resDiv.data.items || [];
        
        let filterDivHtml = '<option value="">Semua Divisi Organisasi</option>';
        let formDivHtml = '<option value="">Pilih Divisi Organisasi...</option>';
        
        divisiListCache.forEach(d => {
            filterDivHtml += `<option value="${d.id_divisi}">${d.nama_divisi}</option>`;
            formDivHtml += `<option value="${d.id_divisi}">${d.nama_divisi}</option>`;
        });
        
        document.getElementById('selectDivisiFilter').innerHTML = filterDivHtml;
        document.getElementById('formIdDivisi').innerHTML = formDivHtml;
    }

    // 2. Ambil list Jabatan
    const resJab = await apiRequest('/api/master/jabatan.php?limit=100');
    if (resJab && resJab.success) {
        jabatanListCache = resJab.data.items || [];
        populateJabatanFilter();
        populateCopyModalJabatan();
    }
}

function populateJabatanFilter(selectedDivisiId = null) {
    const filterSelect = document.getElementById('selectJabatanFilter');
    let filtered = jabatanListCache;
    if (selectedDivisiId) {
        filtered = jabatanListCache.filter(j => parseInt(j.id_divisi, 10) === parseInt(selectedDivisiId, 10));
    }

    let html = '';
    filtered.forEach(j => {
        html += `<option value="${j.id_jabatan}">${j.nama_jabatan}</option>`;
    });

    if (filtered.length === 0) {
        html = '<option value="">(Tidak ada jabatan di divisi ini)</option>';
    }

    filterSelect.innerHTML = html;
}

function populateCopyModalJabatan() {
    const copyFromSelect = document.getElementById('copyFromJabatan');
    const copyTargetSelect = document.getElementById('copyTargetJabatan');

    let formHtml = '';
    jabatanListCache.forEach(j => {
        formHtml += `<option value="${j.id_jabatan}">${j.nama_jabatan}</option>`;
    });

    copyFromSelect.innerHTML = formHtml;
    copyTargetSelect.innerHTML = formHtml;
}

function onFilterDivisiChange() {
    const divId = document.getElementById('selectDivisiFilter').value;
    populateJabatanFilter(divId || null);
    const filterSelect = document.getElementById('selectJabatanFilter');
    if (filterSelect.value) {
        loadMenusForJabatan();
    } else {
        document.getElementById('menuTableBody').innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted small">Pilih jabatan untuk melihat konfigurasi menu.</td></tr>`;
    }
}

function onFormDivisiChange(selectedJabatanId = null) {
    const divId = document.getElementById('formIdDivisi').value;
    const formJabSelect = document.getElementById('formIdJabatan');
    
    let filtered = jabatanListCache;
    if (divId) {
        filtered = jabatanListCache.filter(j => parseInt(j.id_divisi, 10) === parseInt(divId, 10));
    }

    let html = '';
    if (!divId) {
        html = '<option value="">Pilih divisi terlebih dahulu...</option>';
    } else if (filtered.length === 0) {
        html = '<option value="">(Tidak ada jabatan di divisi ini)</option>';
    } else {
        filtered.forEach(j => {
            const isSel = (selectedJabatanId && parseInt(selectedJabatanId, 10) === j.id_jabatan) ? 'selected' : '';
            html += `<option value="${j.id_jabatan}" ${isSel}>${j.nama_jabatan}</option>`;
        });
    }

    formJabSelect.innerHTML = html;
}

// -------------------------------------------------------------
// LOAD MENUS FOR SELECTED JABATAN
// -------------------------------------------------------------
async function loadMenusForJabatan() {
    const filterSelect = document.getElementById('selectJabatanFilter');
    if (!filterSelect.value) return;
    currentSelectedJabatanId = parseInt(filterSelect.value, 10);
    const tbody = document.getElementById('menuTableBody');

    // Update Jabatan Info Badge
    const activeJ = jabatanListCache.find(j => j.id_jabatan === currentSelectedJabatanId);
    if (activeJ) {
        document.getElementById('jabatanInfoBadge').innerHTML = `
            <span class="badge bg-primary-subtle text-primary me-1"><i class="bi bi-award me-1"></i>Level ${activeJ.level}</span>
            <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-diagram-3 me-1"></i>${activeJ.nama_divisi}</span>
        `;
    }

    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted small"><span class="spinner-border spinner-border-sm me-2"></span>Memuat menu jabatan...</td></tr>`;

    const res = await apiRequest(`/api/master/menu_level.php?id_jabatan=${currentSelectedJabatanId}`);
    if (res && res.success) {
        menuDataStore = res.data.items || [];
        renderMenuTable(menuDataStore);
    } else {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger small">Gagal memuat menu: ${res.message || 'Error'}</td></tr>`;
    }
}

function renderMenuTable(items) {
    const tbody = document.getElementById('menuTableBody');
    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                    Belum ada menu yang dikonfigurasi untuk jabatan ini.<br>
                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="openCopyMenuModal()"><i class="bi bi-copy me-1"></i>Salin dari Jabatan Lain</button>
                </td>
            </tr>`;
        return;
    }

    let html = '';
    let currentCategory = '';

    items.forEach((m, idx) => {
        // Render Group Category Separator Row
        if (m.kategori_menu !== currentCategory) {
            currentCategory = m.kategori_menu;
            html += `
                <tr class="table-light border-top border-2">
                    <td colspan="9" class="fw-bold small text-primary py-2 px-3 text-uppercase">
                        <i class="bi bi-bookmark-fill me-1"></i> Kategori: ${currentCategory}
                    </td>
                </tr>
            `;
        }

        const isChild = m.id_parent !== null && m.id_parent > 0;
        const isParent = m.is_parent === 1;
        const iconDisplay = `<i class="bi ${m.icon} fs-5 text-primary"></i>`;
        
        let typeBadge = `<span class="badge bg-light text-dark border">Item Tunggal</span>`;
        if (isParent) typeBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-folder me-1"></i>Parent</span>`;
        if (isChild) typeBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-arrow-return-right me-1"></i>Sub-Menu</span>`;

        const aksesBadge = m.akses === 1 
            ? `<button class="btn btn-sm btn-outline-success p-1 px-2" onclick="toggleMenuField(${m.id_levelmenu}, 'akses', 0)" title="Akses Aktif (Klik untuk mematikan)"><i class="bi bi-check-circle-fill"></i></button>`
            : `<button class="btn btn-sm btn-outline-danger p-1 px-2" onclick="toggleMenuField(${m.id_levelmenu}, 'akses', 1)" title="Akses Diblokir (Klik untuk mengaktifkan)"><i class="bi bi-x-circle-fill"></i></button>`;

        const terlihatBadge = m.terlihat === 1
            ? `<button class="btn btn-sm btn-outline-primary p-1 px-2" onclick="toggleMenuField(${m.id_levelmenu}, 'terlihat', 0)" title="Tampil di Sidebar (Klik untuk menyembunyikan)"><i class="bi bi-eye-fill"></i></button>`
            : `<button class="btn btn-sm btn-outline-secondary p-1 px-2" onclick="toggleMenuField(${m.id_levelmenu}, 'terlihat', 1)" title="Disembunyikan (Klik untuk menampilkan)"><i class="bi bi-eye-slash-fill"></i></button>`;

        const indentStyle = isChild ? 'padding-left: 2rem;' : '';
        const nameDisplay = isChild ? `<i class="bi bi-arrow-return-right me-2 text-muted"></i>${m.nama_menu}` : m.nama_menu;

        html += `
            <tr class="${!m.akses ? 'table-secondary opacity-75' : ''}">
                <td class="text-center font-monospace fw-bold text-muted">${m.urutan}</td>
                <td class="text-center">${iconDisplay}</td>
                <td class="fw-semibold text-dark" style="${indentStyle}">${nameDisplay}</td>
                <td><span class="badge bg-secondary-subtle text-secondary small">${m.kategori_menu}</span></td>
                <td>${typeBadge}</td>
                <td><code class="small text-dark">${m.link || '#'}</code></td>
                <td class="text-center">${aksesBadge}</td>
                <td class="text-center">${terlihatBadge}</td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditMenuModal(${idx})" title="Edit Menu">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteMenu(${m.id_levelmenu}, '${m.nama_menu.replace(/'/g, "\\'")}')" title="Hapus Menu">
                            <i class="bi bi-x-octagon-fill"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function filterMenuTable() {
    const q = document.getElementById('menuSearchInput').value.toLowerCase().trim();
    if (!q) {
        renderMenuTable(menuDataStore);
        return;
    }
    const filtered = menuDataStore.filter(m => 
        m.nama_menu.toLowerCase().includes(q) || 
        (m.link && m.link.toLowerCase().includes(q)) ||
        m.kategori_menu.toLowerCase().includes(q)
    );
    renderMenuTable(filtered);
}

// -------------------------------------------------------------
// TOGGLE STATUS DIRECT
// -------------------------------------------------------------
async function toggleMenuField(idMenu, field, nextVal) {
    const res = await apiRequest('/api/master/menu_level.php', {
        method: 'PUT',
        body: JSON.stringify({
            id_levelmenu: idMenu,
            toggle_field: field,
            toggle_value: nextVal
        })
    });
    if (res && res.success) {
        showToast(res.message, 'success');
        loadMenusForJabatan();
    } else {
        showToast(res.message || 'Gagal mengubah status', 'error');
    }
}

// -------------------------------------------------------------
// MODAL CRUD (3 TAB STRUKTURAL)
// -------------------------------------------------------------
function handleTipeMenuChange() {
    const tipe = document.querySelector('input[name="formTipeMenuRadio"]:checked')?.value || 'single';
    const parentSelectWrapper = document.getElementById('parentSelectWrapper');
    const linkInput = document.getElementById('formLink');

    if (tipe === 'child') {
        parentSelectWrapper.classList.remove('d-none');
        document.getElementById('formIdParent').required = true;
    } else {
        parentSelectWrapper.classList.add('d-none');
        document.getElementById('formIdParent').required = false;
    }

    if (tipe === 'parent') {
        linkInput.value = '#';
    }
}

function updateParentMenuOptions(selectedParentId = null) {
    const parentSelect = document.getElementById('formIdParent');
    const kategori = document.getElementById('formKategoriMenu').value;

    // Filter parent menus for this category
    const parents = menuDataStore.filter(m => m.is_parent === 1 && m.kategori_menu === kategori);
    let html = '<option value="">Pilih menu induk parent...</option>';
    parents.forEach(p => {
        const isSel = (selectedParentId && parseInt(selectedParentId, 10) === p.id_levelmenu) ? 'selected' : '';
        html += `<option value="${p.id_levelmenu}" ${isSel}>${p.nama_menu} (${p.kategori_menu})</option>`;
    });
    parentSelect.innerHTML = html;
}

function openTambahMenuModal() {
    document.getElementById('menuForm').reset();
    document.getElementById('formIdMenu').value = '';
    
    // Set default divisi & jabatan target
    const activeJ = jabatanListCache.find(j => j.id_jabatan === currentSelectedJabatanId);
    if (activeJ && activeJ.id_divisi) {
        document.getElementById('formIdDivisi').value = activeJ.id_divisi;
        onFormDivisiChange(currentSelectedJabatanId);
    } else {
        onFormDivisiChange();
    }

    document.getElementById('tipeSingle').checked = true;
    handleTipeMenuChange();
    updateParentMenuOptions();
    
    document.getElementById('formIcon').value = 'bi-circle';
    updateIconPreview('bi-circle');
    document.getElementById('aksesYes').checked = true;
    document.getElementById('terlihatYes').checked = true;

    // Set next urutan
    const maxUrutan = menuDataStore.reduce((max, m) => Math.max(max, m.urutan), 0);
    document.getElementById('formUrutan').value = maxUrutan + 1;

    // Switch ke Tab 1 (Target & Kategori)
    bootstrap.Tab.getOrCreateInstance(document.getElementById('mtab-target')).show();
    document.getElementById('menuFormModalTitle').innerHTML = '<i class="bi bi-list-check text-primary"></i> Tambah Menu Baru';
    const modal = new bootstrap.Modal(document.getElementById('menuFormModal'));
    modal.show();
}

function openEditMenuModal(idx) {
    const item = menuDataStore[idx];
    if (!item) return;

    document.getElementById('formIdMenu').value = item.id_levelmenu;
    
    // Temukan divisi dari jabatan menu ini
    const jObj = jabatanListCache.find(j => j.id_jabatan === item.id_jabatan);
    if (jObj && jObj.id_divisi) {
        document.getElementById('formIdDivisi').value = jObj.id_divisi;
        onFormDivisiChange(item.id_jabatan);
    } else {
        onFormDivisiChange(item.id_jabatan);
    }

    document.getElementById('formKategoriMenu').value = item.kategori_menu;
    document.getElementById('formNamaMenu').value = item.nama_menu;
    document.getElementById('formLink').value = item.link || '';
    document.getElementById('formUrutan').value = item.urutan;
    document.getElementById('formIcon').value = item.icon || 'bi-circle';
    updateIconPreview(item.icon || 'bi-circle');

    if (item.is_parent === 1) {
        document.getElementById('tipeParent').checked = true;
    } else if (item.id_parent !== null && item.id_parent > 0) {
        document.getElementById('tipeChild').checked = true;
    } else {
        document.getElementById('tipeSingle').checked = true;
    }

    handleTipeMenuChange();
    updateParentMenuOptions(item.id_parent);

    if (item.akses === 1) document.getElementById('aksesYes').checked = true;
    else document.getElementById('aksesNo').checked = true;

    if (item.terlihat === 1) document.getElementById('terlihatYes').checked = true;
    else document.getElementById('terlihatNo').checked = true;

    // Switch ke Tab 1 (Target & Kategori)
    bootstrap.Tab.getOrCreateInstance(document.getElementById('mtab-target')).show();
    document.getElementById('menuFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Konfigurasi Menu';
    const modal = new bootstrap.Modal(document.getElementById('menuFormModal'));
    modal.show();
}

async function handleSaveMenu(e) {
    e.preventDefault();
    const id = document.getElementById('formIdMenu').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveMenu');

    const targetJabatan = document.getElementById('formIdJabatan').value;
    if (!targetJabatan) {
        showToast('Harap pilih Jabatan Target terlebih dahulu.', 'warning');
        bootstrap.Tab.getOrCreateInstance(document.getElementById('mtab-target')).show();
        return;
    }

    const tipe = document.querySelector('input[name="formTipeMenuRadio"]:checked')?.value || 'single';
    const isParent = tipe === 'parent' ? 1 : 0;
    const idParent = tipe === 'child' ? document.getElementById('formIdParent').value : null;

    const payload = {
        id_levelmenu: id,
        id_jabatan: parseInt(targetJabatan, 10),
        kategori_menu: document.getElementById('formKategoriMenu').value,
        nama_menu: document.getElementById('formNamaMenu').value.trim(),
        is_parent: isParent,
        id_parent: idParent ? parseInt(idParent, 10) : null,
        link: document.getElementById('formLink').value.trim(),
        icon: document.getElementById('formIcon').value.trim() || 'bi-circle',
        urutan: parseInt(document.getElementById('formUrutan').value, 10) || 1,
        akses: parseInt(document.querySelector('input[name="formAksesRadio"]:checked').value, 10),
        terlihat: parseInt(document.querySelector('input[name="formTerlihatRadio"]:checked').value, 10)
    };

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const res = await apiRequest('/api/master/menu_level.php', {
        method: isEdit ? 'PUT' : 'POST',
        body: JSON.stringify(payload)
    });

    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Konfigurasi Menu';

    if (res && res.success) {
        showToast(res.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('menuFormModal')).hide();
        loadMenusForJabatan();
    } else {
        showToast(res.message || 'Gagal menyimpan menu', 'error');
    }
}

async function deleteMenu(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus menu "${name}"?`)) return;

    const res = await apiRequest(`/api/master/menu_level.php?id=${id}`, {
        method: 'DELETE'
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        loadMenusForJabatan();
    } else {
        showToast(res.message || 'Gagal menghapus menu', 'error');
    }
}

// -------------------------------------------------------------
// SALIN TEMPLATE MENU ANTAR JABATAN
// -------------------------------------------------------------
function openCopyMenuModal() {
    document.getElementById('copyMenuForm').reset();
    document.getElementById('copyFromJabatan').value = currentSelectedJabatanId;
    const modal = new bootstrap.Modal(document.getElementById('copyMenuModal'));
    modal.show();
}

async function handleCopyMenu(e) {
    e.preventDefault();
    const fromId = parseInt(document.getElementById('copyFromJabatan').value, 10);
    const targetId = parseInt(document.getElementById('copyTargetJabatan').value, 10);

    if (fromId === targetId) {
        showToast('Jabatan asal dan tujuan tidak boleh sama.', 'warning');
        return;
    }

    if (!confirm('Apakah Anda yakin ingin menyalin menu? Konfigurasi menu lama pada jabatan target akan digantikan.')) return;

    const btn = document.getElementById('btnExecuteCopy');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyalin...';

    const res = await apiRequest('/api/master/menu_level.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'copy_from',
            from_id_jabatan: fromId,
            target_id_jabatan: targetId
        })
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-all me-1"></i> Mulai Salin Menu';

    if (res && res.success) {
        showToast(res.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('copyMenuModal')).hide();
        document.getElementById('selectJabatanFilter').value = targetId;
        loadMenusForJabatan();
    } else {
        showToast(res.message || 'Gagal menyalin menu', 'error');
    }
}
</script>
