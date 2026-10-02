<?php
/**
 * Master Data Rekening Bank - PT Jaya Teknik
 * Khusus Role: FINANCE, ADMIN, MANAGER
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);
$pageTitle = 'Master Rekening Bank';
$pageHeading = 'Master Data Rekening Bank Perusahaan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Daftar Rekening Bank Perusahaan</h2>
    </div>
    <!-- Search di kiri, Tombol Tambah di paling kanan -->
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari bank / no. rek / atas nama..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahBankModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Rekening
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Bank</th>
                    <th>Nomor</th>
                    <th>Atas Nama</th>
                    <th>Dibuat</th>
                    <th>Tanggal</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="bankTableBody">
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data rekening bank...
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

<!-- Modal Form Tambah / Edit Rekening Bank -->
<div class="modal fade" id="bankFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 600px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="bankFormModalTitle">
                    <i class="bi bi-credit-card-2-front-fill text-primary"></i> Tambah Rekening Bank
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bankForm" onsubmit="handleSaveBank(event)">
                <input type="hidden" id="formIdBank" name="id_bank">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Nama Bank <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formNamaBank" list="bankSuggestions" required placeholder="Contoh: Bank Central Asia (BCA) / Mandiri / BRI">
                            <datalist id="bankSuggestions">
                                <option value="Bank Central Asia (BCA)">
                                <option value="Bank Mandiri">
                                <option value="Bank Rakyat Indonesia (BRI)">
                                <option value="Bank Negara Indonesia (BNI)">
                                <option value="Bank Danamon">
                                <option value="Bank CIMB Niaga">
                                <option value="Bank Permata">
                                <option value="Bank Syariah Indonesia (BSI)">
                                <option value="Bank Mega">
                                <option value="Bank BTPN">
                            </datalist>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Nomor Rekening <span class="text-danger">*</span></label>
                            <input type="text" class="form-control font-monospace" id="formNoRekening" required placeholder="Contoh: 8830123456 / 1400012345678">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Atas Nama Rekening <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formAtasNama" required placeholder="Contoh: PT JAYA TEKNIK">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Personil / Penanggung Jawab</label>
                            <select class="form-select" id="formIdKaryawan">
                                <option value="">-- Gunakan User Saat Ini --</option>
                            </select>
                            <div class="form-text small text-muted">
                                Karyawan yang mencatat atau bertanggung jawab atas rekening ini.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveBank" class="btn btn-primary btn-sm fw-semibold">
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
let bankDataStore = [];
let karyawanListCache = [];

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
        loadBankList();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadBankList();
}

async function loadKaryawanOptions() {
    try {
        const res = await apiRequest('/api/master/karyawan.php?limit=100');
        if (res && res.success) {
            karyawanListCache = res.data.items || [];
            const select = document.getElementById('formIdKaryawan');
            select.innerHTML = '<option value="">-- Gunakan User Saat Ini --</option>';
            karyawanListCache.forEach(k => {
                const jab = k.nama_jabatan ? ` (${k.nama_jabatan})` : '';
                select.innerHTML += `<option value="${k.id_karyawan}">${escapeHtml(k.nama_karyawan)}${jab}</option>`;
            });
        }
    } catch (e) {
        console.error('Gagal memuat opsi karyawan:', e);
    }
}

async function loadBankList() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('bankTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/rekening_bank.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        bankDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (bankDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data rekening bank.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        bankDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            const karyawanText = item.nama_karyawan && item.nama_karyawan !== '-' 
                ? `<span class="fw-semibold text-dark">${escapeHtml(item.nama_karyawan)}</span>` 
                : `<span class="text-muted fst-italic">-</span>`;

            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td class="fw-bold text-dark">
                         ${escapeHtml(item.nama_bank)}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border font-monospace fs-6 px-2 py-1">
                            ${escapeHtml(item.no_rekening)}
                        </span>
                    </td>
                    <td class="fw-semibold text-dark">
                        ${escapeHtml(item.atasnama_rekening)}
                    </td>
                    <td>${karyawanText}</td>
                    <td class="text-muted small">${escapeHtml(item.created_at)}</td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditBankModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteBank(${item.id_bank}, '${escapeHtml(item.nama_bank)} - ${escapeHtml(item.no_rekening)}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal memuat data rekening bank.</td></tr>`;
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

function openTambahBankModal() {
    document.getElementById('bankForm').reset();
    document.getElementById('formIdBank').value = '';
    document.getElementById('formIdKaryawan').value = '';
    document.getElementById('bankFormModalTitle').innerHTML = '<i class="bi bi-credit-card-2-front-fill text-primary"></i> Tambah Rekening Bank';
    const modal = new bootstrap.Modal(document.getElementById('bankFormModal'));
    modal.show();
}

function openEditBankModal(idx) {
    const item = bankDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdBank').value = item.id_bank;
    document.getElementById('formNamaBank').value = item.nama_bank;
    document.getElementById('formNoRekening').value = item.no_rekening;
    document.getElementById('formAtasNama').value = item.atasnama_rekening;
    document.getElementById('formIdKaryawan').value = item.id_karyawan || '';
    
    document.getElementById('bankFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Rekening Bank';
    const modal = new bootstrap.Modal(document.getElementById('bankFormModal'));
    modal.show();
}

async function handleSaveBank(e) {
    e.preventDefault();
    const id = document.getElementById('formIdBank').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveBank');
    
    const payload = {
        id_bank: id,
        nama_bank: document.getElementById('formNamaBank').value.trim(),
        no_rekening: document.getElementById('formNoRekening').value.trim(),
        atasnama_rekening: document.getElementById('formAtasNama').value.trim(),
        id_karyawan: document.getElementById('formIdKaryawan').value || null,
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    
    try {
        const res = await apiRequest('/api/master/rekening_bank.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        
        if (res && res.success) {
            showToast(res.message || 'Rekening bank berhasil disimpan!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('bankFormModal')).hide();
            loadBankList();
        } else {
            showToast(res.message || 'Gagal menyimpan rekening bank.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan koneksi sistem.', 'error');
    } finally {
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data';
    }
}

async function deleteBank(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus rekening bank "${name}"?`)) return;
    
    const res = await apiRequest('/api/master/rekening_bank.php', {
        method: 'POST',
        body: JSON.stringify({ id_bank: id, _method: 'DELETE' })
    });
    
    if (res && res.success) {
        showToast('Rekening bank berhasil dihapus.', 'success');
        loadBankList();
    } else {
        showToast(res.message || 'Gagal menghapus rekening bank.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadKaryawanOptions();
    loadBankList();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
