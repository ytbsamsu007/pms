<?php
/**
 * Master Data Jabatan - PT Jaya Teknik
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Jabatan';
$pageHeading = 'Master Data Jabatan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Daftar Jabatan &amp; Hirarki Posisi</h2>
    </div>
    <!-- Search di kiri, Tombol Tambah di paling kanan -->
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari jabatan / divisi..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahJabatanModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Jabatan
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Jabatan</th>
                    <th>Nama Jabatan / Posisi</th>
                    <th>Divisi Terkait</th>
                    <th style="width: 140px;">Level</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="jabatanTableBody">
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data jabatan...
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

<!-- Modal Form Tambah / Edit Jabatan (2 Kolom) -->
<div class="modal fade" id="jabatanFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="jabatanFormModalTitle">
                    <i class="bi bi-briefcase-fill text-primary"></i> Tambah Jabatan Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="jabatanForm" onsubmit="handleSaveJabatan(event)">
                <input type="hidden" id="formIdJabatan" name="id_jabatan">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kode Jabatan</label>
                            <input type="text" class="form-control" id="formKodeJabatan" placeholder="Otomatis jika kosong">
                            <div class="form-text small">Biarkan kosong untuk format otomatis <code>JB-001</code>.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Jabatan / Posisi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formNamaJabatan" required placeholder="Contoh: Manager Operasional, Staff Logistik">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Divisi Terkait</label>
                            <select class="form-select" id="formDivisi">
                                <option value="">-- Semua Divisi / Umum --</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Level Hirarki <span class="text-danger">*</span></label>
                            <select class="form-select" id="formLevel" required>
                                <option value="1">Level 1</option>
                                <option value="2">Level 2</option>
                                <option value="3" selected>Level 3</option>
                                <option value="4">Level 4</option>
                                <option value="5">Level 5</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveJabatan" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Jabatan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const fixedLimit = 50;
let searchTimeout = null;
let jabatanDataStore = [];
let divisiListCache = [];

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadJabatan();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadJabatan();
}

async function loadDivisiOptions() {
    const res = await apiRequest('/api/master/divisi.php?limit=100');
    if (res && res.success) {
        divisiListCache = res.data.items || [];
        const select = document.getElementById('formDivisi');
        select.innerHTML = '<option value="">-- Semua Divisi / Umum --</option>';
        divisiListCache.forEach(d => {
            select.innerHTML += `<option value="${d.id_divisi}">${d.nama_divisi} (${d.kode_divisi})</option>`;
        });
    }
}

function getLevelBadge(level) {
    const lvl = parseInt(level, 10);
    switch(lvl) {
        case 1:
            return '<span class="badge bg-danger text-white fw-bold">Level 1</span>';
        case 2:
            return '<span class="badge bg-primary text-white fw-bold">Level 2</span>';
        case 3:
            return '<span class="badge bg-info-subtle text-dark fw-semibold">Level 3</span>';
        case 4:
            return '<span class="badge bg-secondary-subtle text-secondary">Level 4</span>';
        default:
            return `<span class="badge bg-light text-muted border">Level ${lvl}</span>`;
    }
}

async function loadJabatan() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('jabatanTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/jabatan.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        jabatanDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (jabatanDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data jabatan ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        jabatanDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_jabatan || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_jabatan}</td>
                    <td><span class="badge bg-primary-subtle text-primary">${item.nama_divisi}</span></td>
                    <td>${getLevelBadge(item.level)}</td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditJabatanModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteJabatan(${item.id_jabatan}, '${item.nama_jabatan.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat data jabatan.</td></tr>`;
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

function openTambahJabatanModal() {
    document.getElementById('jabatanForm').reset();
    document.getElementById('formIdJabatan').value = '';
    document.getElementById('formDivisi').value = '';
    document.getElementById('formLevel').value = '3';
    document.getElementById('jabatanFormModalTitle').innerHTML = '<i class="bi bi-briefcase-fill text-primary"></i> Tambah Jabatan Baru';
    const modal = new bootstrap.Modal(document.getElementById('jabatanFormModal'));
    modal.show();
}

function openEditJabatanModal(idx) {
    const item = jabatanDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdJabatan').value = item.id_jabatan;
    document.getElementById('formKodeJabatan').value = item.kode_jabatan;
    document.getElementById('formNamaJabatan').value = item.nama_jabatan;
    document.getElementById('formDivisi').value = item.id_divisi || '';
    document.getElementById('formLevel').value = item.level || 3;
    
    document.getElementById('jabatanFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Data Jabatan';
    const modal = new bootstrap.Modal(document.getElementById('jabatanFormModal'));
    modal.show();
}

async function handleSaveJabatan(e) {
    e.preventDefault();
    const id = document.getElementById('formIdJabatan').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveJabatan');
    
    const namaJabatan = document.getElementById('formNamaJabatan').value.trim();
    if (!namaJabatan) {
        showToast('Nama jabatan wajib diisi.', 'error');
        return;
    }
    
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const payload = {
        id_jabatan: id,
        kode_jabatan: document.getElementById('formKodeJabatan').value.trim(),
        nama_jabatan: namaJabatan,
        id_divisi: document.getElementById('formDivisi').value || null,
        level: parseInt(document.getElementById('formLevel').value, 10),
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/jabatan.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data Jabatan';
    
    if (res && res.success) {
        showToast(res.message || 'Data jabatan berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('jabatanFormModal')).hide();
        loadJabatan();
    } else {
        showToast(res.message || 'Gagal menyimpan data jabatan.', 'error');
    }
}

async function deleteJabatan(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus jabatan "${name}"?`)) return;
    
    const res = await apiRequest('/api/master/jabatan.php', {
        method: 'POST',
        body: JSON.stringify({ id_jabatan: id, _method: 'DELETE' })
    });
    
    if (res && res.success) {
        showToast('Jabatan berhasil dihapus.', 'success');
        loadJabatan();
    } else {
        showToast(res.message || 'Gagal menghapus jabatan.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadDivisiOptions();
    loadJabatan();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
