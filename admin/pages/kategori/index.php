<?php
/**
 * Master Data Kategori Barang - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Kategori';
$pageHeading = 'Master Data Kategori Barang';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Kategori Klasifikasi Barang</h2>
    </div>
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama / kode..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahKategoriModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Kategori
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode Kategori</th>
                    <th>Nama Kategori</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="kategoriTableBody">
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data kategori...
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

<!-- Modal Form Tambah / Edit Kategori dengan Tab 2-Kolom -->
<div class="modal fade" id="kategoriFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 640px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="kategoriFormModalTitle">
                    <i class="bi bi-tags-fill text-primary"></i> Tambah Kategori Baru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="kategoriForm" onsubmit="handleSaveKategori(event)">
                <input type="hidden" id="formIdKategori" name="id_kategori">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kode Kategori</label>
                            <input type="text" class="form-control" id="formKodeKategori" placeholder="Auto jika kosong">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Status</label>
                            <select class="form-select" id="formAktif">
                                <option value="1">Aktif</option>
                                <option value="0">Non-aktif</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formNamaKategori" required placeholder="Contoh: Plat Baja / Kawat Las">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveKategori" class="btn btn-primary btn-sm fw-semibold">
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
let kategoriDataStore = [];

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadKategori();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadKategori();
}

async function loadKategori() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('kategoriTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/kategori.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        kategoriDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (kategoriDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data kategori ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        kategoriDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_kategori || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_kategori}</td>
                    <td>
                        <span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}">
                            ${item.aktif ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditKategoriModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteKategori(${item.id_kategori}, '${item.kode_kategori}', '${item.nama_kategori.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat data kategori.</td></tr>`;
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

function openTambahKategoriModal() {
    document.getElementById('kategoriForm').reset();
    document.getElementById('formIdKategori').value = '';
    document.getElementById('formAktif').value = 1;
    document.getElementById('kategoriFormModalTitle').innerHTML = '<i class="bi bi-tags-fill text-primary"></i> Tambah Kategori Baru';
    const modal = new bootstrap.Modal(document.getElementById('kategoriFormModal'));
    modal.show();
}

function openEditKategoriModal(idx) {
    const item = kategoriDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdKategori').value = item.id_kategori;
    document.getElementById('formKodeKategori').value = item.kode_kategori;
    document.getElementById('formNamaKategori').value = item.nama_kategori;
    document.getElementById('formAktif').value = item.aktif;
    
    document.getElementById('kategoriFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Data Kategori';
    const modal = new bootstrap.Modal(document.getElementById('kategoriFormModal'));
    modal.show();
}

async function handleSaveKategori(e) {
    e.preventDefault();
    const id = document.getElementById('formIdKategori').value;
    const isEdit = id !== '';
    
    const payload = {
        id_kategori: id,
        kode_kategori: document.getElementById('formKodeKategori').value.trim(),
        nama_kategori: document.getElementById('formNamaKategori').value.trim(),
        aktif: document.getElementById('formAktif').value,
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/kategori.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    if (res && res.success) {
        showToast(res.message || 'Data kategori berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('kategoriFormModal')).hide();
        loadKategori();
    } else {
        showToast(res.message || 'Gagal menyimpan data kategori.', 'error');
    }
}

async function deleteKategori(id, kode, name) {
    showDeleteConfirm(
        `Apakah Anda yakin ingin menghapus kategori <strong>${escapeHtml(kode)} - ${escapeHtml(name)}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.`,
        kode,
        async function() {
            const res = await apiRequest('/api/master/kategori.php', {
                method: 'POST',
                body: JSON.stringify({ id_kategori: id, _method: 'DELETE' })
            });
            
            if (res && res.success) {
                showToast('Kategori berhasil dihapus.', 'success');
                loadKategori();
            } else {
                showToast(res.message || 'Gagal menghapus kategori.', 'error');
            }
        }
    );
}

document.addEventListener('DOMContentLoaded', loadKategori);
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
