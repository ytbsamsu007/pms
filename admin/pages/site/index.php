<?php
/**
 * Master Data Site - PT Jaya Teknik
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Site';
$pageHeading = 'Master Data Site & Bengkel Galangan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Daftar Lokasi Site &amp; Bengkel Workshop</h2>
    </div>
    <!-- Search di kiri, Tombol Tambah di paling kanan -->
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama site / jenis..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahSiteModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Site
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Site</th>
                    <th>Nama Site / Galangan</th>
                    <th>Jenis Site</th>
                    <th>Penyimpanan Stok</th>
                    <th>Kepala Site (Head of)</th>
                    <th>No. Telepon / HP</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="siteTableBody">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data site...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Pagination Footer (50 Baku) -->
    <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top">
        <div class="text-muted small" id="paginationInfo">
            Menampilkan data...
        </div>
        <nav aria-label="Navigasi Halaman">
            <ul class="pagination pagination-sm mb-0" id="paginationControls">
            </ul>
        </nav>
    </div>
</div>

<!-- Modal Form Tambah / Edit Site dengan Tab Terfokus (2-Kolom) -->
<div class="modal fade" id="siteFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="siteForm" onsubmit="handleSaveSite(event)">
                <input type="hidden" id="formIdSite" name="id_site">
                
                <!-- Modal Header dengan Nav Tabs Terpadu -->
                <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="siteFormModalTitle">
                            <i class="bi bi-geo-alt-fill text-primary"></i> Tambah Site Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="siteFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark small py-2 px-3" id="sform-tab-1" data-bs-toggle="tab" data-bs-target="#sform-pane-1" type="button" role="tab">
                                <i class="bi bi-building me-1 text-primary"></i> 1. Utama
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="sform-tab-2" data-bs-toggle="tab" data-bs-target="#sform-pane-2" type="button" role="tab">
                                <i class="bi bi-geo-alt me-1 text-primary"></i> 2. Lokasi
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="siteFormTabContent">
                        
                        <!-- TAB 1: UTAMA (2 KOLOM) -->
                        <div class="tab-pane fade show active" id="sform-pane-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kode Site</label>
                                    <input type="text" class="form-control" id="formKodeSite" placeholder="Otomatis jika kosong">
                                    <div class="form-text small">Biarkan kosong untuk format otomatis <code>SIT001</code>.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Site / Workshop <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formNamaSite" required placeholder="Contoh: Galangan Utama Dok 1">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jenis Site <span class="text-danger">*</span></label>
                                    <select class="form-select" id="formJenisSite" required>
                                        <option value="Bengkel">Bengkel (Workshop Bubut / Las)</option>
                                        <option value="Logistik">Gudang Logistik / Material</option>
                                        <option value="Office">Office / Kantor Operasional</option>
                                        <option value="Pusat">Pusat Galangan</option>
                                        <option value="Lain Lain">Lain-Lain</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kepala Site / Head of (Approval)</label>
                                    <select class="form-select" id="formHeadOf">
                                        <option value="">-- Pilih Penanggung Jawab (Level 1 &amp; 2) --</option>
                                    </select>
                                    <div class="form-text small">Hanya menampilkan personil dengan Jabatan Level 1 &amp; 2.</div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold d-block">Penyimpanan Stok Fisik</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formPenyimpananStokRadio" id="stokSiteYes" value="1" checked>
                                        <label class="btn btn-outline-success btn-sm" for="stokSiteYes">
                                            <i class="bi bi-box-seam me-1"></i> Ya (Dapat Menyimpan Stok Material)
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="formPenyimpananStokRadio" id="stokSiteNo" value="0">
                                        <label class="btn btn-outline-secondary btn-sm" for="stokSiteNo">
                                            <i class="bi bi-x-circle me-1"></i> Tidak (Bukan Tempat Stok / Kantor)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: LOKASI (2 KOLOM) -->
                        <div class="tab-pane fade" id="sform-pane-2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Nomor Telepon / HP Site</label>
                                    <input type="text" class="form-control" id="formNoHpSite" placeholder="Contoh: 031-889901 / 08123456789">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Alamat Fisik Lengkap</label>
                                    <textarea class="form-control" id="formAlamatSite" rows="4" placeholder="Jalan, kawasan industri, dermaga pelabuhan"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Koordinat GPS / Link Google Maps</label>
                                    <textarea class="form-control" id="formGpsSite" rows="4" placeholder="-7.2000, 112.7300 atau link Google Maps"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveSite" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Site
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lengkap Site (2 Tab, 2-Kolom) -->
<div class="modal fade" id="siteDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- Modal Header dengan Nav Tabs Terpadu -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="siteDetailModalTitle">
                        <i class="bi bi-geo-alt-fill text-primary"></i> Rincian Lokasi Site &amp; Bengkel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="siteDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="sdetail-tab-1" data-bs-toggle="tab" data-bs-target="#sdetail-pane-1" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 1. Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="sdetail-tab-2" data-bs-toggle="tab" data-bs-target="#sdetail-pane-2" type="button" role="tab">
                            <i class="bi bi-geo-alt me-1 text-primary"></i> 2. Lokasi
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="siteDetailTabContent">
                    
                    <!-- TAB 1: UTAMA (2 KOLOM) -->
                    <div class="tab-pane fade show active" id="sdetail-pane-1" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kode Site</label>
                                <div class="fw-semibold font-monospace text-primary fs-6" id="modalKodeSite">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jenis Site</label>
                                <div id="modalJenisSite">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nama Site / Galangan</label>
                                <div class="fw-bold text-dark fs-5" id="modalNamaSite">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kepala Site (Head Of)</label>
                                <div class="fw-semibold text-dark fs-6" id="modalKepalaSite">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kontak Kepala Site</label>
                                <div id="modalKontakKepalaSite">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Penyimpanan Stok</label>
                                <div id="modalPenyimpananStok">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: LOKASI (2 KOLOM) -->
                    <div class="tab-pane fade" id="sdetail-pane-2" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">No. Telepon / HP Site</label>
                                <div class="fw-semibold text-dark" id="modalNoHpSite">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Alamat Fisik Lengkap</label>
                                <div class="p-3 bg-light rounded border small" id="modalAlamatSite">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Koordinat GPS / Map</label>
                                <div class="p-3 bg-light rounded border font-monospace small text-muted" id="modalGpsSite">-</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const fixedLimit = 50;
let searchTimeout = null;
let siteDataStore = [];
let karyawanListCache = [];

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadSite();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadSite();
}

async function loadKaryawanOptions() {
    // Filter HANYA karyawan yang memiliki Jabatan Level 1 & 2
    const res = await apiRequest('/api/master/karyawan.php?levels=1,2&limit=100');
    if (res && res.success) {
        karyawanListCache = res.data.items || [];
        const select = document.getElementById('formHeadOf');
        if (select) {
            select.innerHTML = '<option value="">-- Pilih Penanggung Jawab (Level 1 &amp; 2) --</option>';
            karyawanListCache.forEach(k => {
                const jabatanInfo = k.nama_jabatan !== '-' ? ` - ${k.nama_jabatan}` : '';
                select.innerHTML += `<option value="${k.id_karyawan}">${k.nama_karyawan}${jabatanInfo}</option>`;
            });
        }
    }
}

async function loadSite() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('siteTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/site.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        siteDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (siteDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data site ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        siteDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_site || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_site}</td>
                    <td><span class="badge bg-info-subtle text-info">${item.jenis_site || 'Site'}</span></td>
                    <td>
                        <span class="badge ${item.penyimpanan_stok ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}">
                            <i class="bi ${item.penyimpanan_stok ? 'bi-check-circle-fill' : 'bi-dash-circle'} me-1"></i>
                            ${item.penyimpanan_stok ? 'Penyimpanan Stok' : 'Non-Stok'}
                        </span>
                    </td>
                    <td class="fw-semibold text-dark">${item.kepala_site}</td>
                    <td>${item.no_hp || '-'}</td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="showSiteDetail(${idx})" title="Lihat Rincian">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditSiteModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                                                    <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteSite(${item.id_site}, '${item.kode_site}', '${item.nama_site.replace(/'/g, "\\'")}')" title="Hapus">
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
    } else {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Gagal memuat data site.</td></tr>`;
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

function openTambahSiteModal() {
    document.getElementById('siteForm').reset();
    document.getElementById('formIdSite').value = '';
    document.getElementById('formHeadOf').value = '';
    document.getElementById('stokSiteYes').checked = true;
    bootstrap.Tab.getOrCreateInstance(document.getElementById('sform-tab-1')).show();
    document.getElementById('siteFormModalTitle').innerHTML = '<i class="bi bi-geo-alt-fill text-primary"></i> Tambah Site Baru';
    const modal = new bootstrap.Modal(document.getElementById('siteFormModal'));
    modal.show();
}

function openEditSiteModal(idx) {
    const item = siteDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdSite').value = item.id_site;
    document.getElementById('formKodeSite').value = item.kode_site;
    document.getElementById('formNamaSite').value = item.nama_site;
    document.getElementById('formJenisSite').value = item.jenis_site;
    document.getElementById('formHeadOf').value = item.id_karyawan_headof || '';
    
    if (item.penyimpanan_stok === 1) {
        document.getElementById('stokSiteYes').checked = true;
    } else {
        document.getElementById('stokSiteNo').checked = true;
    }

    document.getElementById('formNoHpSite').value = item.no_hp !== '-' ? item.no_hp : '';
    document.getElementById('formGpsSite').value = item.alamat_gps !== '-' ? item.alamat_gps : '';
    document.getElementById('formAlamatSite').value = item.alamat !== '-' ? item.alamat : '';
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('sform-tab-1')).show();
    document.getElementById('siteFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Data Site';
    const modal = new bootstrap.Modal(document.getElementById('siteFormModal'));
    modal.show();
}

async function handleSaveSite(e) {
    e.preventDefault();
    const id = document.getElementById('formIdSite').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveSite');
    
    const stokVal = document.querySelector('input[name="formPenyimpananStokRadio"]:checked')?.value || '0';

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const payload = {
        id_site: id,
        kode_site: document.getElementById('formKodeSite').value.trim(),
        nama_site: document.getElementById('formNamaSite').value.trim(),
        jenis_site: document.getElementById('formJenisSite').value,
        id_karyawan_headof: document.getElementById('formHeadOf').value || null,
        penyimpanan_stok: parseInt(stokVal),
        no_hp: document.getElementById('formNoHpSite').value.trim(),
        alamat_gps: document.getElementById('formGpsSite').value.trim(),
        alamat: document.getElementById('formAlamatSite').value.trim(),
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/site.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data Site';
    
    if (res && res.success) {
        showToast(res.message || 'Data site berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('siteFormModal')).hide();
        loadSite();
    } else {
        showToast(res.message || 'Gagal menyimpan data site.', 'error');
    }
}

async function deleteSite(id, kode, name) {
    showDeleteConfirm(
        `Apakah Anda yakin ingin menghapus site <strong>${escapeHtml(kode)} - ${escapeHtml(name)}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.`,
        kode,
        async function() {
            const res = await apiRequest('/api/master/site.php', {
                method: 'POST',
                body: JSON.stringify({ id_site: id, _method: 'DELETE' })
            });
            
            if (res && res.success) {
                showToast('Site berhasil dihapus.', 'success');
                loadSite();
            } else {
                showToast(res.message || 'Gagal menghapus site.', 'error');
            }
        }
    );
}

function showSiteDetail(idx) {
    const item = siteDataStore[idx];
    if (!item) return;
    
    document.getElementById('modalKodeSite').textContent = item.kode_site || '-';
    document.getElementById('modalNamaSite').textContent = item.nama_site;
    document.getElementById('modalJenisSite').innerHTML = `<span class="badge bg-info-subtle text-info">${item.jenis_site}</span>`;
    document.getElementById('modalPenyimpananStok').innerHTML = item.penyimpanan_stok === 1
        ? '<span class="badge bg-success-subtle text-success fs-6"><i class="bi bi-check-circle-fill me-1"></i>Ya (Dapat Menyimpan Stok Material)</span>'
        : '<span class="badge bg-secondary-subtle text-muted fs-6"><i class="bi bi-x-circle me-1"></i>Tidak (Bukan Tempat Stok / Kantor)</span>';
    document.getElementById('modalNoHpSite').textContent = item.no_hp || '-';
    document.getElementById('modalKepalaSite').textContent = item.kepala_site;
    document.getElementById('modalKontakKepalaSite').textContent = `${item.email_kepala_site} / ${item.hp_kepala_site}`;
    document.getElementById('modalAlamatSite').textContent = item.alamat;
    document.getElementById('modalGpsSite').textContent = item.alamat_gps;
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('sdetail-tab-1')).show();
    const modal = new bootstrap.Modal(document.getElementById('siteDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadKaryawanOptions();
    loadSite();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
