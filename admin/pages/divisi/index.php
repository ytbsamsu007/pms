<?php
/**
 * Master Data Divisi - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Divisi';
$pageHeading = 'Master Data Divisi & Departemen';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Daftar Divisi &amp; Departemen</h2>
    </div>
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari divisi / kode..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahDivisiModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Divisi
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Divisi</th>
                    <th>Nama Divisi / Bagian</th>
                    <th>Tingkatan (Level)</th>
                    <th>Kepala Divisi (Head of)</th>
                    <th class="text-center" style="width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="divisiTableBody">
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data divisi...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Pagination Footer -->
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

<!-- Modal Form Tambah / Edit Divisi dengan Layout 2-Kolom Lapang -->
<div class="modal fade" id="divisiFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 640px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="divisiFormModalTitle">
                    <i class="bi bi-diagram-3-fill text-primary"></i> Tambah Divisi Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="divisiForm" onsubmit="handleSaveDivisi(event)">
                <input type="hidden" id="formIdDivisi" name="id_divisi">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kode Divisi</label>
                            <input type="text" class="form-control" id="formKodeDivisi" placeholder="Auto jika kosong">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Level / Tingkatan</label>
                            <select class="form-select" id="formLevel">
                                <option value="1">Level 1</option>
                                <option value="2">Level 2</option>
                                <option value="3">Level 3</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Nama Divisi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formNamaDivisi" required placeholder="Contoh: Logistik / Mekanik / Purchasing">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Kepala Divisi / Head of (Level 1)</label>
                            <select class="form-select" id="formHeadOf">
                                <option value="">-- Tanpa Kepala Divisi --</option>
                            </select>
                            <div class="form-text small text-muted" id="formHeadOfHelp">
                                Menampilkan karyawan pada divisi ini dengan jabatan Level 1.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveDivisi" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data
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
let divisiDataStore = [];
let karyawanLevel1Cache = [];

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadDivisi();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadDivisi();
}

async function loadKaryawanLevel1() {
    try {
        const res = await apiRequest('/api/master/karyawan.php?levels=1&limit=100');
        if (res && res.success) {
            karyawanLevel1Cache = res.data.items || [];
        }
    } catch (e) {
        console.error('Gagal memuat data karyawan level 1:', e);
    }
}

async function loadDivisi() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('divisiTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/divisi.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        divisiDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (divisiDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data divisi ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        divisiDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            const headOfText = item.nama_headof && item.nama_headof !== '-' 
                ? `<span class="fw-semibold text-dark">${escapeHtml(item.nama_headof)}</span>` 
                : `<span class="text-muted fst-italic">-</span>`;

            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${escapeHtml(item.kode_divisi || '-')}</span></td>
                    <td class="fw-bold text-dark">${escapeHtml(item.nama_divisi)}</td>
                    <td><span class="badge bg-primary-subtle text-primary">Level ${item.level}</span></td>
                    <td>${headOfText}</td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditDivisiModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteDivisi(${item.id_divisi}, '${item.nama_divisi.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat data divisi.</td></tr>`;
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

function openTambahDivisiModal() {
    document.getElementById('divisiForm').reset();
    document.getElementById('formIdDivisi').value = '';
    document.getElementById('formLevel').value = 1;
    
    const select = document.getElementById('formHeadOf');
    select.innerHTML = '<option value="">-- Tanpa Kepala Divisi --</option>';
    if (karyawanLevel1Cache.length === 0) {
        select.innerHTML += '<option value="" disabled>-- Belum ada master karyawan Level 1 --</option>';
    } else {
        karyawanLevel1Cache.forEach(k => {
            const divName = k.nama_divisi ? ` - Divisi ${k.nama_divisi}` : '';
            const jabName = k.nama_jabatan ? ` (${k.nama_jabatan})` : '';
            select.innerHTML += `<option value="${k.id_karyawan}">${escapeHtml(k.nama_karyawan)}${jabName}${divName}</option>`;
        });
    }
    select.value = '';
    document.getElementById('formHeadOfHelp').textContent = 'Pilih karyawan dengan jabatan Level 1 sebagai Kepala Divisi.';
    
    document.getElementById('divisiFormModalTitle').innerHTML = '<i class="bi bi-diagram-3-fill text-primary"></i> Tambah Divisi Baru';
    const modal = new bootstrap.Modal(document.getElementById('divisiFormModal'));
    modal.show();
}

function openEditDivisiModal(idx) {
    const item = divisiDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdDivisi').value = item.id_divisi;
    document.getElementById('formKodeDivisi').value = item.kode_divisi;
    document.getElementById('formNamaDivisi').value = item.nama_divisi;
    document.getElementById('formLevel').value = item.level;
    
    // Filter karyawan yang memiliki divisi yang sama dan level jabatan = 1
    const select = document.getElementById('formHeadOf');
    select.innerHTML = '<option value="">-- Tanpa Kepala Divisi --</option>';
    
    const sameDivisiKaryawan = karyawanLevel1Cache.filter(k => 
        Number(k.id_divisi) === Number(item.id_divisi) || 
        (item.id_karyawan_headof && Number(k.id_karyawan) === Number(item.id_karyawan_headof))
    );
    
    if (sameDivisiKaryawan.length > 0) {
        sameDivisiKaryawan.forEach(k => {
            const jabName = k.nama_jabatan ? ` (${k.nama_jabatan})` : '';
            select.innerHTML += `<option value="${k.id_karyawan}">${escapeHtml(k.nama_karyawan)}${jabName}</option>`;
        });
        
        const otherKaryawan = karyawanLevel1Cache.filter(k => 
            Number(k.id_divisi) !== Number(item.id_divisi) && 
            (!item.id_karyawan_headof || Number(k.id_karyawan) !== Number(item.id_karyawan_headof))
        );
        if (otherKaryawan.length > 0) {
            let optgroup = '<optgroup label="Karyawan Level 1 Divisi Lain">';
            otherKaryawan.forEach(k => {
                const divName = k.nama_divisi ? ` - ${k.nama_divisi}` : '';
                const jabName = k.nama_jabatan ? ` (${k.nama_jabatan})` : '';
                optgroup += `<option value="${k.id_karyawan}">${escapeHtml(k.nama_karyawan)}${jabName}${divName}</option>`;
            });
            optgroup += '</optgroup>';
            select.innerHTML += optgroup;
        }
    } else {
        select.innerHTML += '<option value="" disabled>-- Tidak ada karyawan Level 1 di divisi ini --</option>';
        if (karyawanLevel1Cache.length > 0) {
            let optgroup = '<optgroup label="Pilih dari Karyawan Level 1 Lainnya">';
            karyawanLevel1Cache.forEach(k => {
                const divName = k.nama_divisi ? ` - ${k.nama_divisi}` : '';
                const jabName = k.nama_jabatan ? ` (${k.nama_jabatan})` : '';
                optgroup += `<option value="${k.id_karyawan}">${escapeHtml(k.nama_karyawan)}${jabName}${divName}</option>`;
            });
            optgroup += '</optgroup>';
            select.innerHTML += optgroup;
        }
    }
    
    select.value = item.id_karyawan_headof || '';
    document.getElementById('formHeadOfHelp').textContent = `Menampilkan personil jabatan Level 1 untuk Divisi ${item.nama_divisi}.`;
    
    document.getElementById('divisiFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Data Divisi';
    const modal = new bootstrap.Modal(document.getElementById('divisiFormModal'));
    modal.show();
}

async function handleSaveDivisi(e) {
    e.preventDefault();
    const id = document.getElementById('formIdDivisi').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveDivisi');
    
    const payload = {
        id_divisi: id,
        kode_divisi: document.getElementById('formKodeDivisi').value.trim(),
        nama_divisi: document.getElementById('formNamaDivisi').value.trim(),
        level: document.getElementById('formLevel').value,
        id_karyawan_headof: document.getElementById('formHeadOf').value || null,
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    
    try {
        const res = await apiRequest('/api/master/divisi.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        
        if (res && res.success) {
            showToast(res.message || 'Data divisi berhasil disimpan!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('divisiFormModal')).hide();
            loadDivisi();
        } else {
            showToast(res.message || 'Gagal menyimpan data divisi.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan koneksi sistem.', 'error');
    } finally {
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data';
    }
}

async function deleteDivisi(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus divisi "${name}"?`)) return;
    
    const res = await apiRequest('/api/master/divisi.php', {
        method: 'POST',
        body: JSON.stringify({ id_divisi: id, _method: 'DELETE' })
    });
    
    if (res && res.success) {
        showToast('Divisi berhasil dihapus.', 'success');
        loadDivisi();
    } else {
        showToast(res.message || 'Gagal menghapus divisi.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadKaryawanLevel1();
    loadDivisi();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>

