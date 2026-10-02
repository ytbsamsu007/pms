<?php
/**
 * Halaman Manajemen & Arsip Lampiran Dokumen - PT Jaya Teknis
 * Path: admin/pages/dokumen/index.php
 */
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Arsip & Lampiran Dokumen';
$pageHeading = 'Arsip & Lampiran Dokumen';

// Include Header, Sidebar & Navbar Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- KONTEN UTAMA -->
<div class="container-fluid px-0">
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-0">Arsip &amp; Lampiran Dokumen</h4>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" onclick="openModalUploadDokumen('PURCHASE', 'PAGE_INDEX')">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Dokumen
            </button>
        </div>
    </div>

    <style>
    .doc-filter-bar .form-control,
    .doc-filter-bar .form-select,
    .doc-filter-bar .input-group-text,
    .doc-filter-bar .btn {
        height: 38px;
        font-size: 0.85rem;
    }
    .doc-filter-bar .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: 12px;
        padding-right: 12px;
    }
    </style>

    <!-- FILTER & SEARCH CARD -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4 doc-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="searchKeyword" placeholder="Cari No. Dokumen, Label Nama, atau Pengunggah..." oninput="debounceSearch()">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <select class="form-select" id="filterTipe" onchange="loadTableDokumen(1)">
                        <option value="">-- Semua Tipe Dokumen --</option>
                        <option value="REQUEST">REQUEST ORDER</option>
                        <option value="PURCHASE">PURCHASE ORDER</option>
                        <option value="RECEIVING">RECEIVING (PENERIMAAN)</option>
                        <option value="RETUR PO">RETUR PO</option>
                        <option value="FAKTUR PO">FAKTUR PO</option>
                        <option value="PAYMENT PO">PAYMENT PO (PEMBAYARAN)</option>
                        <option value="MUTASI BARANG">MUTASI BARANG</option>
                        <option value="ADJUSTMENT STOK">ADJUSTMENT STOK</option>
                    </select>
                </div>
                <div class="col-md-4 col-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                        <input type="date" class="form-control border-start-0" id="filterTanggal" onchange="loadTableDokumen(1)">
                    </div>
                </div>
                <div class="col-md-1 col-2 text-end">
                    <button type="button" class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center" onclick="resetFilterDokumen()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL LIST DOKUMEN -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="tableDokumenMain" style="font-size: 0.86rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama Dokumen</th>
                            <th style="width: 170px;">No. Transaksi</th>
                            <th style="width: 120px;">Tanggal</th>
                            <th style="width: 170px;">Uploader</th>
                            <th class="text-center" style="width: 80px;">Unduh</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDokumenMain">
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat daftar arsip dokumen...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted" id="paginationInfo">Menampilkan 0 data</div>
            <nav id="paginationControls">
                <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI HAPUS ARSIP DOKUMEN (SEDERHANA) -->
<div class="modal fade" id="modalConfirmDeleteDokumen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title fw-bold mb-0">
                    Hapus Dokumen
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <p class="small text-dark mb-2">Apakah Anda yakin ingin menghapus arsip dokumen ini?</p>
                <div class="bg-light p-2 rounded border small mb-1">
                    <div class="fw-bold text-dark" id="deleteTargetNamaDokumen">-</div>
                    <div class="text-primary font-monospace" id="deleteTargetNomorDokumen">-</div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-0 d-flex justify-content-end gap-2">
                
                <button type="button" class="btn btn-danger btn-sm fw-semibold" id="btnConfirmDeleteDokumenExecute" onclick="executeDeleteDokumen()">
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
let targetDeleteDocId = null;
let deleteDocModalInstance = null;

// Simpan ID Karyawan & Role User yang sedang login
const currentUserIdKaryawan = parseInt(CURRENT_USER?.id_karyawan || 0);
const currentUserRole = String(CURRENT_USER?.role || CURRENT_USER?.nama_role || '').toUpperCase();
const isSuperOrAdmin = ['ADMIN', 'SUPERADMIN', 'DEVELOPER'].includes(currentUserRole);

document.addEventListener('DOMContentLoaded', () => {
    loadTableDokumen(1);
});

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadTableDokumen(1);
    }, 300);
}

function resetFilterDokumen() {
    document.getElementById('searchKeyword').value = '';
    document.getElementById('filterTipe').value = '';
    document.getElementById('filterTanggal').value = '';
    loadTableDokumen(1);
}

async function loadTableDokumen(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('tbodyDokumenMain');
    const badgeTotal = document.getElementById('badgeTotalRecords');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');

    const q = document.getElementById('searchKeyword').value.trim();
    const tipe = document.getElementById('filterTipe').value;
    const tgl = document.getElementById('filterTanggal').value;

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data...</td></tr>`;

    try {
        const url = `${BASE_URL}/api/dokumen/list_all.php?page=${page}&limit=15&q=${encodeURIComponent(q)}&tipe=${encodeURIComponent(tipe)}&tanggal=${encodeURIComponent(tgl)}`;
        const res = await fetch(url);
        const json = await res.json();

        if (json.success && json.data) {
            const items = json.data.items || [];
            const pagination = json.data.pagination || {};
            const total = pagination.total_records || 0;
            const totalPages = pagination.total_pages || 1;

            if (badgeTotal) badgeTotal.innerText = `${total} Data`;
            if (paginationInfo) paginationInfo.innerText = `Menampilkan ${items.length} dari ${total} dokumen (Halaman ${page} dari ${totalPages})`;

            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-folder2-open d-block fs-2 mb-2 opacity-50"></i>
                            Tidak ada data dokumen ditemukan.
                        </td>
                    </tr>`;
                if (paginationControls) paginationControls.innerHTML = '';
                return;
            }

            let html = '';
            items.forEach((item, idx) => {
                const no = (page - 1) * 15 + idx + 1;
                
                // Badge Ekstensi / Source
                let badgeExt = '';
                let isExt = !item.file && item.external_url;
                if (isExt) {
                    badgeExt = `<span class="badge bg-warning-subtle text-warning-emphasis border ms-1"><i class="bi bi-google me-1"></i>GDrive</span>`;
                } else {
                    const ext = (item.file_ext || '').toUpperCase();
                    let color = 'secondary';
                    if (ext === 'PDF') color = 'danger';
                    else if (['JPG','JPEG','PNG','WEBP'].includes(ext)) color = 'primary';
                    else if (['XLS','XLSX'].includes(ext)) color = 'success';
                    else if (['DOC','DOCX'].includes(ext)) color = 'info';
                    else if (['ZIP','RAR'].includes(ext)) color = 'dark';
                    badgeExt = `<span class="badge bg-${color}-subtle text-${color} border font-monospace ms-1" style="font-size: 0.72rem;">${ext || 'FILE'}</span>`;
                }

                const lockBadge = item.is_protected ? `<span class="badge bg-danger-subtle text-danger ms-1" title="Dilindungi Password"><i class="bi bi-lock-fill"></i></span>` : '';

                // Hak hapus: hanya uploader atau Admin
                const itemOwnerId = parseInt(item.id_karyawan || 0);
                const canDelete = isSuperOrAdmin || (currentUserIdKaryawan > 0 && currentUserIdKaryawan === itemOwnerId);

                const deleteBtnHtml = canDelete ? `
                    <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="confirmDeleteDokumen(${item.id_dokumen}, '${escapeHtml(item.nama_dokumen || '')}', '${escapeHtml(item.nomor_dokumen || '')}')" title="Hapus Dokumen">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                ` : '';

                html += `
                    <tr>
                        <td class="text-center text-muted fw-semibold">${no}</td>
                        <td>
                            <div class="fw-semibold text-dark d-flex align-items-center flex-wrap">
                                <span>${escapeHtml(item.nama_dokumen || '-')}</span>
                                ${badgeExt}
                                ${lockBadge}
                            </div>
                        </td>
                        <td>
                            <strong class="font-monospace text-primary">${escapeHtml(item.nomor_dokumen || '-')}</strong>
                        </td>
                        <td class="small text-muted font-monospace">${item.tanggal_dokumen || '-'}</td>
                        <td>
                            <span class="text-dark fw-semibold">${escapeHtml(item.nama_karyawan || 'Internal / Sistem')}</span>
                        </td>
                        <td class="text-center font-monospace small">${item.unduh || 0}</td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="handleOpenDokumen(${item.id_dokumen}, ${item.is_protected ? 'true' : 'false'}, '${item.file_url ? item.file_url.replace(/'/g, "\\'") : ''}', '${item.external_url ? item.external_url.replace(/'/g, "\\'") : ''}', 'PAGE_INDEX')" title="Buka / Download">
                                    <i class="bi ${isExt ? 'bi-box-arrow-up-right' : 'bi-download'}"></i>
                                </button>
                                ${deleteBtnHtml}
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            // Render pagination seragam
            renderPaginationControls(pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-danger">Gagal memuat daftar dokumen: ${json.message || ''}</td></tr>`;
        }
    } catch (e) {
        console.error('Error load all dokumen:', e);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-danger">Terjadi kesalahan saat memuat data dari server.</td></tr>`;
    }
}

function renderPaginationControls(p) {
    const container = document.getElementById('paginationControls');
    if (!container) return;

    const totalPages = Math.max(1, parseInt(p.total_pages) || 1);
    const currPage = parseInt(p.current_page || p.page) || 1;

    let html = '<ul class="pagination pagination-sm mb-0">';
    html += `<li class="page-item ${currPage <= 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="loadTableDokumen(${currPage - 1})" ${currPage <= 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>
             </li>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currPage - 1 && i <= currPage + 1)) {
            html += `<li class="page-item ${i === currPage ? 'active' : ''}">
                        <button class="page-link" onclick="loadTableDokumen(${i})">${i}</button>
                     </li>`;
        } else if (i === currPage - 2 || i === currPage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${currPage >= totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="loadTableDokumen(${currPage + 1})" ${currPage >= totalPages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>
             </li>`;
    html += '</ul>';

    container.innerHTML = html;
}

function confirmDeleteDokumen(idDokumen, namaDokumen, nomorDokumen) {
    targetDeleteDocId = idDokumen;
    document.getElementById('deleteTargetNamaDokumen').textContent = namaDokumen || '-';
    document.getElementById('deleteTargetNomorDokumen').textContent = nomorDokumen || '-';

    const modalEl = document.getElementById('modalConfirmDeleteDokumen');
    if (!deleteDocModalInstance) {
        deleteDocModalInstance = new bootstrap.Modal(modalEl);
    }
    deleteDocModalInstance.show();
}

async function executeDeleteDokumen() {
    if (!targetDeleteDocId) return;

    const btn = document.getElementById('btnConfirmDeleteDokumenExecute');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

    try {
        const res = await fetch(`${BASE_URL}/api/dokumen/index.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_dokumen: parseInt(targetDeleteDocId) })
        });
        const json = await res.json();

        if (json.success) {
            showToast(json.message || 'Dokumen berhasil dihapus.', 'success');
            if (deleteDocModalInstance) {
                deleteDocModalInstance.hide();
            }
            loadTableDokumen(currentPage);
        } else {
            showToast(json.message || 'Gagal menghapus dokumen.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan koneksi saat menghapus dokumen.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
        targetDeleteDocId = null;
    }
}
</script>
