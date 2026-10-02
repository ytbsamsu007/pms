<?php
/**
 * Master Data Merk Barang - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Merk';
$pageHeading = 'Master Data Merk Barang';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Merk &amp; Brand Material</h2>
    </div>
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari merk / kode..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahMerkModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Merk
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Merk</th>
                    <th>Nama Merk / Brand</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="merkTableBody">
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data merk...
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

<!-- Modal Form Tambah / Edit Merk dengan Tab 2-Kolom -->
<div class="modal fade" id="merkFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 640px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="merkFormModalTitle">
                    <i class="bi bi-bookmark-star-fill text-primary"></i> Tambah Merk Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="merkForm" onsubmit="handleSaveMerk(event)">
                <input type="hidden" id="formIdMerk" name="id_merk">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kode Merk</label>
                            <input type="text" class="form-control" id="formKodeMerk" placeholder="Auto jika kosong">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Status</label>
                            <select class="form-select" id="formAktif">
                                <option value="1">Aktif</option>
                                <option value="0">Non-aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Nama Merk / Brand <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formNamaMerk" required placeholder="Contoh: Kobe Steel / Yanmar">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveMerk" class="btn btn-primary btn-sm fw-semibold">
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
let merkDataStore = [];

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadMerk();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadMerk();
}

async function loadMerk() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('merkTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/merk.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        merkDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (merkDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data merk ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        merkDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_merk || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_merk}</td>
                    <td>
                        <span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}">
                            ${item.aktif ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditMerkModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteMerk(${item.id_merk}, '${item.kode_merk}', '${item.nama_merk.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat data merk.</td></tr>`;
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

function openTambahMerkModal() {
    document.getElementById('merkForm').reset();
    document.getElementById('formIdMerk').value = '';
    document.getElementById('formAktif').value = 1;
    document.getElementById('merkFormModalTitle').innerHTML = '<i class="bi bi-bookmark-star-fill text-primary"></i> Tambah Merk Baru';
    const modal = new bootstrap.Modal(document.getElementById('merkFormModal'));
    modal.show();
}

function openEditMerkModal(idx) {
    const item = merkDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdMerk').value = item.id_merk;
    document.getElementById('formKodeMerk').value = item.kode_merk;
    document.getElementById('formNamaMerk').value = item.nama_merk;
    document.getElementById('formAktif').value = item.aktif;
    
    document.getElementById('merkFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Data Merk';
    const modal = new bootstrap.Modal(document.getElementById('merkFormModal'));
    modal.show();
}

async function handleSaveMerk(e) {
    e.preventDefault();
    const id = document.getElementById('formIdMerk').value;
    const isEdit = id !== '';
    
    const payload = {
        id_merk: id,
        kode_merk: document.getElementById('formKodeMerk').value.trim(),
        nama_merk: document.getElementById('formNamaMerk').value.trim(),
        aktif: document.getElementById('formAktif').value,
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/merk.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    if (res && res.success) {
        showToast(res.message || 'Data merk berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('merkFormModal')).hide();
        loadMerk();
    } else {
        showToast(res.message || 'Gagal menyimpan data merk.', 'error');
    }
}

async function deleteMerk(id, kode, name) {
    showDeleteConfirm(
        `Apakah Anda yakin ingin menghapus merk <strong>${escapeHtml(kode)} - ${escapeHtml(name)}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.`,
        kode,
        async function() {
            const res = await apiRequest('/api/master/merk.php', {
                method: 'POST',
                body: JSON.stringify({ id_merk: id, _method: 'DELETE' })
            });
            
            if (res && res.success) {
                showToast('Merk berhasil dihapus.', 'success');
                loadMerk();
            } else {
                showToast(res.message || 'Gagal menghapus merk.', 'error');
            }
        }
    );
}

document.addEventListener('DOMContentLoaded', loadMerk);
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
