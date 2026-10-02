<?php
/**
 * Master Data Karyawan / User - PT Jaya Teknik
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Karyawan';
$pageHeading = 'Master Data Karyawan & Personil';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Daftar Karyawan &amp; Personil</h2>
    </div>
    <!-- Search di kiri, Tombol Tambah di paling kanan -->
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama / NIK / jabatan / site..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahKaryawanModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Karyawan
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Divisi</th>
                    <th>Jabatan</th>
                    <th>Status Karyawan</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="karyawanTableBody">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data karyawan...
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

<!-- Modal Form Tambah / Edit Karyawan dengan 4 Tab Terfokus (2-Kolom) -->
<div class="modal fade" id="karyawanFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="karyawanForm" onsubmit="handleSaveKaryawan(event)">
                <input type="hidden" id="formIdKaryawan" name="id_karyawan">
                
                <!-- Modal Header dengan Nav Tabs Terpadu -->
                <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="karyawanFormModalTitle">
                            <i class="bi bi-person-plus-fill text-primary"></i> Tambah Karyawan Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="karyawanFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark small py-2 px-3" id="kform-tab-1" data-bs-toggle="tab" data-bs-target="#kform-pane-1" type="button" role="tab">
                                <i class="bi bi-person-vcard me-1 text-primary"></i> 1. Identitas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="kform-tab-2" data-bs-toggle="tab" data-bs-target="#kform-pane-2" type="button" role="tab">
                                <i class="bi bi-geo-alt me-1 text-primary"></i> 2. Penempatan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="kform-tab-3" data-bs-toggle="tab" data-bs-target="#kform-pane-3" type="button" role="tab">
                                <i class="bi bi-telephone me-1 text-primary"></i> 3. Kontak
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="kform-tab-4" data-bs-toggle="tab" data-bs-target="#kform-pane-4" type="button" role="tab">
                                <i class="bi bi-shield-lock me-1 text-primary"></i> 4. Akun
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="karyawanFormTabContent">
                        
                        <!-- TAB 1: IDENTITAS (2 KOLOM) -->
                        <div class="tab-pane fade show active" id="kform-pane-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">NIK / Kode Karyawan</label>
                                    <input type="text" class="form-control" id="formKodeKaryawan" placeholder="Otomatis jika kosong">
                                    <div class="form-text small">Biarkan kosong untuk penomoran otomatis format <code>KRY001</code>.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Lengkap Karyawan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formNamaKaryawan" required placeholder="Contoh: Budi Santoso">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Jenis Kelamin</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formGenderRadio" id="genderLaki" value="1" checked>
                                        <label class="btn btn-outline-primary btn-sm" for="genderLaki">Laki-Laki</label>
                                        
                                        <input type="radio" class="btn-check" name="formGenderRadio" id="genderPerempuan" value="0">
                                        <label class="btn btn-outline-primary btn-sm" for="genderPerempuan">Perempuan</label>
                                    </div>
                                </div>
                                <div class="col-md-6"></div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tempat Lahir</label>
                                    <input type="text" class="form-control" id="formTempatLahir" placeholder="Kota kelahiran (contoh: Samarinda)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Lahir</label>
                                    <input type="date" class="form-control" id="formTanggalLahir">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: PENEMPATAN (2 KOLOM) -->
                        <div class="tab-pane fade" id="kform-pane-2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Divisi <span class="text-danger">*</span></label>
                                    <select class="form-select" id="formDivisi" required onchange="handleDivisiChange()">
                                        <option value="">-- Pilih Divisi --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jabatan</label>
                                    <select class="form-select" id="formJabatan">
                                        <option value="">-- Pilih Jabatan (Opsional) --</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Lokasi Site / Workshop</label>
                                    <select class="form-select" id="formSite">
                                        <option value="">-- Pilih Site Penempatan (Opsional) --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Status Ikatan Kerja</label>
                                    <select class="form-select" id="formStatusKaryawan">
                                        <option value="0">Magang (Internship)</option>
                                        <option value="1">PKWT (Perjanjian Kerja Waktu Tertentu)</option>
                                        <option value="2" selected>PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)</option>
                                        <option value="3">Pekerja paruh waktu (Part-time)</option>
                                        <option value="4">Harian Lepas (Casual Workers)</option>
                                        <option value="5">Freelance / Pekerja Lepas</option>
                                        <option value="6">Outsourcing / Alih Daya</option>
                                        <option value="7">Volunteer / Sukarelawan</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: KONTAK (2 KOLOM) -->
                        <div class="tab-pane fade" id="kform-pane-3" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Alamat Email</label>
                                    <input type="email" class="form-control" id="formEmail" placeholder="budi@jayateknik.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor Handphone / WhatsApp</label>
                                    <input type="text" class="form-control" id="formNoHp" placeholder="08123456789">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: AKUN (2 KOLOM) -->
                        <div class="tab-pane fade" id="kform-pane-4" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Password Akun</label>
                                    <input type="password" class="form-control" id="formPassword" placeholder="Kosongkan jika tidak diubah">
                                    <div class="form-text small" id="formPasswordHelp">Default password baru: <code>123456</code>.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Hak Akses Login Web</label>
                                    <select class="form-select" id="formLoginWeb">
                                        <option value="1" selected>Diizinkan Login ke Sistem</option>
                                        <option value="0">Hanya Data Pegawai (Non-Login)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Status Keaktifan</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="karyawanAktifYes" value="1" checked>
                                        <label class="btn btn-outline-success btn-sm" for="karyawanAktifYes">Aktif</label>
                                        
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="karyawanAktifNo" value="0">
                                        <label class="btn btn-outline-danger btn-sm" for="karyawanAktifNo">Non-aktif</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveKaryawan" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lengkap Karyawan dengan 4 Tab Terfokus (2-Kolom) -->
<div class="modal fade" id="karyawanDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- Modal Header dengan Nav Tabs Terpadu -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="karyawanDetailModalTitle">
                        <i class="bi bi-person-badge-fill text-primary"></i> Rincian Data Karyawan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="karyawanDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="kdetail-tab-1" data-bs-toggle="tab" data-bs-target="#kdetail-pane-1" type="button" role="tab">
                            <i class="bi bi-person-vcard me-1 text-primary"></i> 1. Identitas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="kdetail-tab-2" data-bs-toggle="tab" data-bs-target="#kdetail-pane-2" type="button" role="tab">
                            <i class="bi bi-geo-alt me-1 text-primary"></i> 2. Penempatan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="kdetail-tab-3" data-bs-toggle="tab" data-bs-target="#kdetail-pane-3" type="button" role="tab">
                            <i class="bi bi-telephone me-1 text-primary"></i> 3. Kontak
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="kdetail-tab-4" data-bs-toggle="tab" data-bs-target="#kdetail-pane-4" type="button" role="tab">
                            <i class="bi bi-shield-lock me-1 text-primary"></i> 4. Akun
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="karyawanDetailTabContent">
                    
                    <!-- TAB 1: IDENTITAS (2 KOLOM) -->
                    <div class="tab-pane fade show active" id="kdetail-pane-1" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">NIK / Kode Karyawan</label>
                                <div class="fw-semibold font-monospace text-primary fs-6" id="modalKodeKaryawan">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nama Lengkap</label>
                                <div class="fw-bold text-dark fs-5" id="modalNamaKaryawan">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jenis Kelamin</label>
                                <div id="modalJenisKelamin">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Tempat, Tanggal Lahir</label>
                                <div class="text-dark" id="modalTtl">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PENEMPATAN (2 KOLOM) -->
                    <div class="tab-pane fade" id="kdetail-pane-2" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Divisi</label>
                                <div id="modalDivisi">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jabatan</label>
                                <div id="modalJabatan">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Site Penempatan</label>
                                <div id="modalSite">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Status Ikatan Kerja</label>
                                <div id="modalStatusKerja">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: KONTAK (2 KOLOM) -->
                    <div class="tab-pane fade" id="kdetail-pane-3" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Alamat Email</label>
                                <div class="text-dark fw-semibold" id="modalEmail">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">No. Handphone / WhatsApp</label>
                                <div class="text-dark fw-semibold" id="modalNoHp">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: AKUN (2 KOLOM) -->
                    <div class="tab-pane fade" id="kdetail-pane-4" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Akses Login Web</label>
                                <div id="modalLoginWeb">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Status Keaktifan</label>
                                <div id="modalAktif">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Tanggal Bergabung</label>
                                <div class="text-muted" id="modalTglBergabung">-</div>
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
let karyawanDataStore = [];
let divisiListCache = [];
let jabatanListCache = [];
let siteListCache = [];

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadKaryawan();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadKaryawan();
}

async function loadDependencies() {
    // 1. Divisi
    const resD = await apiRequest('/api/master/divisi.php?limit=100');
    if (resD && resD.success) {
        divisiListCache = resD.data.items || [];
        const selectD = document.getElementById('formDivisi');
        selectD.innerHTML = '<option value="">-- Pilih Divisi --</option>';
        divisiListCache.forEach(d => {
            selectD.innerHTML += `<option value="${d.id_divisi}">${d.nama_divisi} (${d.kode_divisi})</option>`;
        });
    }

    // 2. Jabatan
    const resJ = await apiRequest('/api/master/jabatan.php?limit=100');
    if (resJ && resJ.success) {
        jabatanListCache = resJ.data.items || [];
        renderJabatanSelect();
    }

    // 3. Site
    const resS = await apiRequest('/api/master/site.php?limit=100');
    if (resS && resS.success) {
        siteListCache = resS.data.items || [];
        const selectS = document.getElementById('formSite');
        selectS.innerHTML = '<option value="">-- Pilih Site Penempatan (Opsional) --</option>';
        siteListCache.forEach(s => {
            selectS.innerHTML += `<option value="${s.id_site}">${s.nama_site} (${s.jenis_site || 'Site'})</option>`;
        });
    }
}

function renderJabatanSelect(filterDivisiId = null) {
    const selectJ = document.getElementById('formJabatan');
    selectJ.innerHTML = '<option value="">-- Pilih Jabatan (Opsional) --</option>';
    
    let filtered = jabatanListCache;
    if (filterDivisiId) {
        filtered = jabatanListCache.filter(j => !j.id_divisi || j.id_divisi == filterDivisiId);
    }
    
    filtered.forEach(j => {
        selectJ.innerHTML += `<option value="${j.id_jabatan}">${j.nama_jabatan} (${j.kode_jabatan})</option>`;
    });
}

function handleDivisiChange() {
    const selectedDivisi = document.getElementById('formDivisi').value;
    renderJabatanSelect(selectedDivisi ? parseInt(selectedDivisi) : null);
}

async function loadKaryawan() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('karyawanTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/karyawan.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        karyawanDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (karyawanDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data karyawan ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        karyawanDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_karyawan || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_karyawan}</td>
                    <td><span class="badge bg-primary-subtle text-primary">${item.nama_divisi}</span></td>
                    <td><span class="badge bg-info-subtle text-dark fw-semibold">${item.nama_jabatan}</span></td>
                    <td><span class="badge bg-secondary-subtle text-secondary">${item.status_karyawan_label}</span></td>
                    <td>
                        <span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${item.aktif ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="showKaryawanDetail(${idx})" title="Lihat Rincian">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditKaryawanModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteKaryawan(${item.id_karyawan}, '${item.kode_karyawan}', '${item.nama_karyawan.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Gagal memuat data karyawan.</td></tr>`;
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

function openTambahKaryawanModal() {
    document.getElementById('karyawanForm').reset();
    document.getElementById('formIdKaryawan').value = '';
    document.getElementById('formStatusKaryawan').value = 3;
    document.getElementById('formLoginWeb').value = 1;
    document.getElementById('karyawanAktifYes').checked = true;
    document.getElementById('genderLaki').checked = true;
    document.getElementById('formSite').value = '';
    document.getElementById('formTempatLahir').value = '';
    document.getElementById('formTanggalLahir').value = '';
    document.getElementById('formPassword').required = false;
    document.getElementById('formPasswordHelp').innerHTML = 'Default password baru: <code>123456</code>.';
    
    renderJabatanSelect();
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('kform-tab-1')).show();
    document.getElementById('karyawanFormModalTitle').innerHTML = '<i class="bi bi-person-plus-fill text-primary"></i> Tambah Karyawan Baru';
    const modal = new bootstrap.Modal(document.getElementById('karyawanFormModal'));
    modal.show();
}

function openTambahUserModal() {
    openTambahKaryawanModal();
}

function openEditKaryawanModal(idx) {
    const item = karyawanDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdKaryawan').value = item.id_karyawan;
    document.getElementById('formKodeKaryawan').value = item.kode_karyawan;
    document.getElementById('formNamaKaryawan').value = item.nama_karyawan;
    document.getElementById('formDivisi').value = item.id_divisi || '';
    
    renderJabatanSelect(item.id_divisi ? parseInt(item.id_divisi) : null);
    document.getElementById('formJabatan').value = item.id_jabatan || '';
    document.getElementById('formSite').value = item.id_site || '';
    
    // Gender Radio
    if (item.jenis_kelamin === 0) {
        document.getElementById('genderPerempuan').checked = true;
    } else {
        document.getElementById('genderLaki').checked = true;
    }

    document.getElementById('formTempatLahir').value = item.tempat_lahir || '';
    document.getElementById('formTanggalLahir').value = item.tanggal_lahir || '';
    
    document.getElementById('formStatusKaryawan').value = item.status_karyawan_id;
    document.getElementById('formEmail').value = item.email !== '-' ? item.email : '';
    document.getElementById('formNoHp').value = item.no_handphone !== '-' ? item.no_handphone : '';
    document.getElementById('formPassword').value = '';
    document.getElementById('formPasswordHelp').innerHTML = 'Biarkan kosong jika tidak ingin mengubah password.';
    document.getElementById('formLoginWeb').value = item.login_web;
    
    // Aktif Radio
    if (item.aktif === 1) {
        document.getElementById('karyawanAktifYes').checked = true;
    } else {
        document.getElementById('karyawanAktifNo').checked = true;
    }
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('kform-tab-1')).show();
    document.getElementById('karyawanFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Data Karyawan';
    const modal = new bootstrap.Modal(document.getElementById('karyawanFormModal'));
    modal.show();
}

async function handleSaveKaryawan(e) {
    e.preventDefault();
    const id = document.getElementById('formIdKaryawan').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveKaryawan');
    
    const namaKaryawan = document.getElementById('formNamaKaryawan').value.trim();
    if (!namaKaryawan) {
        showToast('Nama karyawan wajib diisi.', 'error');
        return;
    }

    const aktifVal = document.querySelector('input[name="formAktifRadio"]:checked')?.value || '1';
    const genderVal = document.querySelector('input[name="formGenderRadio"]:checked')?.value || '1';
    
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const payload = {
        id_karyawan: id,
        kode_karyawan: document.getElementById('formKodeKaryawan').value.trim(),
        nama_karyawan: namaKaryawan,
        id_divisi: document.getElementById('formDivisi').value,
        id_jabatan: document.getElementById('formJabatan').value || null,
        id_site: document.getElementById('formSite').value || null,
        tempat_lahir: document.getElementById('formTempatLahir').value.trim(),
        tanggal_lahir: document.getElementById('formTanggalLahir').value || null,
        jenis_kelamin: parseInt(genderVal),
        status_karyawan: document.getElementById('formStatusKaryawan').value,
        email: document.getElementById('formEmail').value.trim(),
        no_handphone: document.getElementById('formNoHp').value.trim(),
        password: document.getElementById('formPassword').value.trim(),
        login_web: document.getElementById('formLoginWeb').value,
        aktif: parseInt(aktifVal),
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/karyawan.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data Karyawan';
    
    if (res && res.success) {
        showToast(res.message || 'Data karyawan berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('karyawanFormModal')).hide();
        loadKaryawan();
    } else {
        showToast(res.message || 'Gagal menyimpan data karyawan.', 'error');
    }
}

async function deleteKaryawan(id, kode, name) {
    showDeleteConfirm(
        `Apakah Anda yakin ingin menghapus karyawan <strong>${escapeHtml(kode)} - ${escapeHtml(name)}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.`,
        kode,
        async function() {
            const res = await apiRequest('/api/master/karyawan.php', {
                method: 'POST',
                body: JSON.stringify({ id_karyawan: id, _method: 'DELETE' })
            });
            
            if (res && res.success) {
                showToast('Karyawan berhasil dihapus.', 'success');
                loadKaryawan();
            } else {
                showToast(res.message || 'Gagal menghapus karyawan.', 'error');
            }
        }
    );
}

function showKaryawanDetail(idx) {
    const item = karyawanDataStore[idx];
    if (!item) return;
    
    // Tab 1
    document.getElementById('modalKodeKaryawan').textContent = item.kode_karyawan || '-';
    document.getElementById('modalNamaKaryawan').textContent = item.nama_karyawan;
    document.getElementById('modalJenisKelamin').innerHTML = `<span class="badge ${item.jenis_kelamin === 1 ? 'bg-primary-subtle text-primary' : 'bg-pink-subtle text-danger'}">${item.jenis_kelamin_label}</span>`;
    
    const ttlStr = (item.tempat_lahir || item.tanggal_lahir_formatted !== '-') 
        ? `${item.tempat_lahir || ''}${item.tempat_lahir && item.tanggal_lahir_formatted !== '-' ? ', ' : ''}${item.tanggal_lahir_formatted !== '-' ? item.tanggal_lahir_formatted : ''}`
        : 'Belum diisi';
    document.getElementById('modalTtl').textContent = ttlStr;

    document.getElementById('modalDivisi').innerHTML = `<span class="badge bg-primary-subtle text-primary">${item.nama_divisi} (${item.kode_divisi})</span>`;
    document.getElementById('modalJabatan').innerHTML = `<span class="badge bg-info-subtle text-dark fw-semibold">${item.nama_jabatan}</span>`;
    document.getElementById('modalSite').innerHTML = `<span class="badge bg-secondary-subtle text-secondary">${item.nama_site}</span>`;
    document.getElementById('modalStatusKerja').innerHTML = `<span class="badge bg-secondary-subtle text-secondary">${item.status_karyawan_label}</span>`;
    
    // Tab 2
    document.getElementById('modalEmail').textContent = item.email || '-';
    document.getElementById('modalNoHp').textContent = item.no_handphone || '-';
    document.getElementById('modalLoginWeb').innerHTML = item.login_web ? '<span class="badge bg-success">Diizinkan</span>' : '<span class="badge bg-secondary">Tidak Diizinkan</span>';
    document.getElementById('modalAktif').innerHTML = item.aktif ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Non-aktif</span>';
    document.getElementById('modalTglBergabung').textContent = item.tanggal_bergabung;
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('kdetail-tab-1')).show();
    const modal = new bootstrap.Modal(document.getElementById('karyawanDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadDependencies();
    loadKaryawan();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
