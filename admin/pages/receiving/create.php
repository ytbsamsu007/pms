<?php
/**
 * Halaman Form Input Penerimaan Barang (Receiving / SPB Vendor)
 * Path: admin/pages/receiving/create.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 * Database: receiving_order & receiving_order_detail
 * PERHATIAN: TIDAK MENAMPILKAN INFO HARGA / SUBTOTAL / PAJAK
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Khusus Logistik, Manager, dan Admin
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);
$preselectedPoId = isset($_GET['id_po']) ? decodeId($_GET['id_po']) : (isset($_GET['id']) ? decodeId($_GET['id']) : 0);

$pageTitle = 'Penerimaan Barang Baru (Receiving)';
$pageHeading = 'Input Penerimaan Barang (SPB Vendor)';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Title -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Penerimaan Barang Baru</h4>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/pages/receiving/index.php" class="btn btn-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Penerimaan
            </a>
        </div>
    </div>

    <!-- FORM DENGAN TAB NAVIGASI -->
    <form id="formCreateReceiving" onsubmit="handleSaveReceiving(event)">
        <div class="card border-0 shadow-sm rounded-3">
            <!-- Nav Tabs Header -->
            <div class="card-header bg-white pt-3 pb-0 px-4 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="rcvFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="tab-info-utama" data-bs-toggle="tab" data-bs-target="#pane-info-utama" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-2 text-primary"></i>1. Dokumen &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-verifikasi-material" data-bs-toggle="tab" data-bs-target="#pane-verifikasi-material" type="button" role="tab">
                            <i class="bi bi-boxes me-2 text-primary"></i>2. Verifikasi Fisik Material &amp; QC
                            <span class="badge bg-primary text-white ms-2" id="tabItemCountBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-surat-jalan" data-bs-toggle="tab" data-bs-target="#pane-surat-jalan" type="button" role="tab">
                            <i class="bi bi-truck me-2 text-primary"></i>3. Surat Jalan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-keterangan" data-bs-toggle="tab" data-bs-target="#pane-keterangan" type="button" role="tab">
                            <i class="bi bi-chat-left-text me-2 text-primary"></i>4. Keterangan
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="rcvFormTabContent">
                    
                    <!-- TAB 1: INFORMASI DOKUMEN & VENDOR (LAYOUT 2 KOLOM) -->
                    <div class="tab-pane fade show active" id="pane-info-utama" role="tabpanel">
                        <div class="row g-4">
                            
                            <!-- KOLOM KIRI: IDENTITAS DOKUMEN & PO -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">Identitas Penerimaan</h6>
                                    
                                    <!-- Nomor RCV -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Nomor Receiving (RCV) <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control font-monospace fw-bold text-primary bg-light" id="rcvNomor" readonly required placeholder="Memuat nomor RCV...">
                                            <button type="button" class="btn btn-outline-secondary" onclick="fetchNextRcvNumber()" title="Generate Ulang Nomor">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Searchable Purchase Order Selector -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Referensi Purchase Order (PO) <span class="text-danger">*</span></label>
                                        <div class="rcv-po-search-wrapper position-relative" id="rcvPoSearchWrapper">
                                            <input type="hidden" id="selectPo" name="id_po" value="" required>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control fw-semibold" id="rcvPoSearchInput" 
                                                       placeholder="Ketik untuk mencari Nomor PO atau Nama Vendor..." 
                                                       autocomplete="off" 
                                                       onfocus="openRcvPoDropdown()" 
                                                       onclick="openRcvPoDropdown()" 
                                                       oninput="debounceRcvPoSearch()">
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearRcvPoSelection()" title="Hapus Pilihan">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div class="rcv-po-dropdown d-none" id="rcvPoDropdown">
                                                <div id="rcvPoDropdownList">
                                                    <div class="p-2 text-center text-muted small">Memuat daftar PO...</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tanggal Penerimaan Fisik -->
                                    <div class="mb-0">
                                        <label for="rcvTanggalDiterima" class="form-label small fw-bold text-dark">Tanggal Diterima Fisik <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="rcvTanggalDiterima" required value="<?= date('Y-m-d') ?>" onchange="onTanggalRcvChange()">
                                    </div>
                                </div>
                            </div>

                            <!-- KOLOM KANAN: INFORMASI VENDOR & LOKASI -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">Informasi Vendor &amp; Lokasi</h6>

                                    <!-- Vendor Pengirim (Readonly) -->
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Vendor Pengirim</label>
                                        <input type="text" class="form-control form-control-sm bg-white" id="displayVendor" readonly value="-">
                                    </div>

                                    <!-- Site Tujuan (Readonly) -->
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Site / Gudang Tujuan</label>
                                        <input type="text" class="form-control form-control-sm bg-white" id="displaySite" readonly value="-">
                                    </div>

                                    <!-- Petugas Logistik Penerima -->
                                    <div class="mb-0">
                                        <label class="form-label small text-muted">Petugas Penerima (Logistik)</label>
                                        <input type="text" class="form-control form-control-sm bg-white fw-semibold" readonly value="<?= htmlspecialchars($user['nama'] ?? $user['username'] ?? 'Petugas Logistik') ?>">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TAB 2: VERIFIKASI FISIK MATERIAL & QC -->
                    <div class="tab-pane fade" id="pane-verifikasi-material" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Rincian Barang</h6>
                            <span class="badge bg-secondary-subtle text-secondary" id="countItemDetailBadge">0 Item</span>
                        </div>

                        <div class="table-responsive border rounded-3 mb-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th style="width: 45px;" class="text-center align-middle">No</th>
                                        <th style="min-width: 220px;" class="align-middle">Nama Barang &amp; Kode</th>
                                        <th style="width: 100px;" class="text-center align-middle">Qty Dipesan</th>
                                        <th style="width: 130px;" class="text-center text-success align-middle">Qty Baik (Passed) <span class="text-danger">*</span></th>
                                        <th style="width: 130px;" class="text-center text-danger align-middle">Qty Cacat / Rusak</th>
                                        <th style="width: 80px;" class="text-center align-middle">Satuan</th>
                                        <th style="width: 90px;" class="text-center align-middle">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody id="receivingItemTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            Silakan pilih dokumen Purchase Order (PO) pada Tab 1 terlebih dahulu.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: SURAT JALAN -->
                    <div class="tab-pane fade" id="pane-surat-jalan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">Surat Jalan Dari Vendor</h6>

                                    <!-- Nomor SPB / Surat Jalan Vendor -->
                                    <div class="mb-3">
                                        <label for="rcvNomorSj" class="form-label small fw-bold text-dark">No. Surat Pengantar Barang (SPB) / Surat Jalan Vendor <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm font-monospace fw-bold" id="rcvNomorSj" placeholder="Nomor Surat Jalan Vendor..." required>
                                    </div>

                                    <!-- Upload File Surat Jalan Dari Vendor -->
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Upload File Surat Jalan Dari Vendor</label>
                                        <input type="file" id="inputUploadSj" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png" onchange="handleFileSjChange(event)">
                                        <div class="form-text small text-muted">Format: PDF, JPG, PNG (Maks. 2 MB)</div>
                                    </div>

                                    <div id="fileSjPreviewBadge" class="d-none align-items-center justify-content-between bg-white border rounded p-2 small mt-2">
                                        <div>
                                            <div class="font-monospace text-dark fw-semibold" id="fileSjName" style="font-size: 0.82rem;">file.pdf</div>
                                            <span class="text-muted small" id="fileSjSize" style="font-size: 0.72rem;">(0 MB)</span>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" onclick="clearFileSj()" title="Hapus File"><i class="bi bi-trash-fill"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: KETERANGAN -->
                    <div class="tab-pane fade" id="pane-keterangan" role="tabpanel">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3">Catatan / Keterangan Penerimaan</h6>
                            <div class="mb-0">
                                <textarea class="form-control" id="rcvKeterangan" rows="5" placeholder="Keterangan / catatan penerimaan..."></textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER ACTION BUTTONS -->
            <div class="card-footer bg-light p-3 border-top d-flex justify-content-end align-items-center">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSubmitReceiving" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Simpan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- MODAL POP-UP CATATAN ITEM QC -->
<div class="modal fade" id="modalItemNote" tabindex="-1" aria-labelledby="modalItemNoteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalItemNoteLabel">
                    <i class="bi bi-chat-left-text text-primary"></i>
                    <span>Catatan Kondisi / QC Material</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small text-muted">Nama Barang:</label>
                    <div class="fw-bold text-dark fs-6" id="modalNoteBarangNama">-</div>
                </div>
                <div class="mb-3">
                    <label for="modalNoteText" class="form-label small fw-bold text-dark">Keterangan / Catatan Fisik Barang:</label>
                    <textarea class="form-control" id="modalNoteText" rows="3" placeholder="Contoh: 1 unit kemasan penyok / cacat fisik, 1 unit kondisi baik..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" onclick="saveItemNoteFromModal()">
                    <i class="bi bi-check2 me-1"></i> Simpan Catatan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- STYLING AUTOCOMPLETE & SEARCHABLE PO DROPDOWN -->
<style>
.rcv-po-search-wrapper {
    position: relative;
}
.rcv-po-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #b6d4fe;
    border-radius: 0.375rem;
    box-shadow: 0 12px 32px rgba(0,0,0,0.18);
    z-index: 9999 !important;
    max-height: 250px;
    overflow-y: auto;
}
.rcv-po-dropdown-item {
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s ease-in-out;
}
.rcv-po-dropdown-item:hover, .rcv-po-dropdown-item.active {
    background-color: #f0f7ff;
}
</style>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<script>
const PRESELECTED_PO_ID = <?= $preselectedPoId ?>;
let masterPoReadyCache = [];
let currentPoData = null;
let poSearchTimeout = null;
let currentEditingItemIdx = null;
let modalItemNoteInstance = null;
let selectedFileSj = null;

document.addEventListener('DOMContentLoaded', async () => {
    modalItemNoteInstance = new bootstrap.Modal(document.getElementById('modalItemNote'));

    await fetchNextRcvNumber();
    await loadPoReadyOptions();

    // Event listener klik di luar dropdown untuk menutup
    document.addEventListener('click', (e) => {
        const wrapper = document.getElementById('rcvPoSearchWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            closeRcvPoDropdown();
        }
    });

    if (PRESELECTED_PO_ID > 0) {
        selectPoById(PRESELECTED_PO_ID);
    }
});

// -------------------------------------------------------------
// 1. FILE UPLOAD HANDLER (SURAT JALAN VENDOR)
// -------------------------------------------------------------
function handleFileSjChange(e) {
    const file = e.target.files[0];
    if (!file) return;

    // Validasi Ukuran File (Maks 2MB = 2097152 bytes)
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) {
        showToast('Ukuran file melebihi batas maksimal 2 MB.', 'danger');
        clearFileSj();
        return;
    }

    // Validasi Ekstensi (.pdf, .jpg, .jpeg, .png)
    const ext = file.name.split('.').pop().toLowerCase();
    if (file.size > 2 * 1024 * 1024) {
        showToast('Ukuran file surat jalan maksimal 2 MB.', 'warning');
        clearFileSj();
        return;
    }

    selectedFileSj = file;
    const sizeMb = (file.size / (1024 * 1024)).toFixed(2);

    const nameEl = document.getElementById('fileSjName');
    const sizeEl = document.getElementById('fileSjSize');
    const badgeEl = document.getElementById('fileSjPreviewBadge');

    if (nameEl) nameEl.textContent = file.name;
    if (sizeEl) sizeEl.textContent = `(${sizeMb} MB)`;
    if (badgeEl) {
        badgeEl.classList.remove('d-none');
        badgeEl.classList.add('d-flex');
    }
}

function clearFileSj() {
    selectedFileSj = null;
    const input = document.getElementById('inputUploadSj');
    if (input) input.value = '';
    const badgeEl = document.getElementById('fileSjPreviewBadge');
    if (badgeEl) {
        badgeEl.classList.add('d-none');
        badgeEl.classList.remove('d-flex');
    }
}

// -------------------------------------------------------------
// 2. GENERATE NOMOR RCV OTOMATIS
// -------------------------------------------------------------
async function fetchNextRcvNumber() {
    const tgl = document.getElementById('rcvTanggalDiterima').value || '';
    const res = await apiRequest(`/api/receiving/get_next_number.php?tanggal=${encodeURIComponent(tgl)}`);
    if (res && res.success && res.data && res.data.nomor_rcv) {
        document.getElementById('rcvNomor').value = res.data.nomor_rcv;
    }
}

function onTanggalRcvChange() {
    fetchNextRcvNumber();
}

// -------------------------------------------------------------
// 3. LOAD PO YANG SIAP DITERIMA (STATUS: DIPROSES VENDOR)
// -------------------------------------------------------------
async function loadPoReadyOptions() {
    const res = await apiRequest('/api/receiving/po_ready.php');
    if (res && res.success && Array.isArray(res.data)) {
        masterPoReadyCache = res.data;
        renderPoDropdownList(masterPoReadyCache);
    } else {
        masterPoReadyCache = [];
        document.getElementById('rcvPoDropdownList').innerHTML = '<div class="p-2 text-center text-muted small">Tidak ada PO yang siap diterima</div>';
    }
}

function openRcvPoDropdown() {
    const dropdown = document.getElementById('rcvPoDropdown');
    dropdown.classList.remove('d-none');
    renderPoDropdownList(masterPoReadyCache);
}

function closeRcvPoDropdown() {
    const dropdown = document.getElementById('rcvPoDropdown');
    dropdown.classList.add('d-none');
}

function debounceRcvPoSearch() {
    clearTimeout(poSearchTimeout);
    poSearchTimeout = setTimeout(() => {
        const query = document.getElementById('rcvPoSearchInput').value.trim().toLowerCase();
        if (!query) {
            renderPoDropdownList(masterPoReadyCache);
            return;
        }

        const filtered = masterPoReadyCache.filter(po => {
            const noPo = (po.nomor_po || '').toLowerCase();
            const vendor = (po.nama_vendor || '').toLowerCase();
            return noPo.includes(query) || vendor.includes(query);
        });

        renderPoDropdownList(filtered);
    }, 200);
}

function renderPoDropdownList(list) {
    const container = document.getElementById('rcvPoDropdownList');
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1"></i> Tidak ditemukan PO yang cocok</div>';
        return;
    }

    let html = '';
    list.forEach(po => {
        const noPo = escapeHtml(po.nomor_po || '-');
        const vendor = escapeHtml(po.nama_vendor || 'Vendor Umum');

        html += `
            <div class="rcv-po-dropdown-item d-flex justify-content-between align-items-center py-2 px-3" onclick="selectPoItem(${po.id_po})">
                <div class="fw-semibold text-dark font-monospace">[${noPo}] - [${vendor}]</div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle small font-monospace">Pilih</span>
            </div>
        `;
    });

    container.innerHTML = html;
}

function selectPoItem(idPo) {
    const selected = masterPoReadyCache.find(p => parseInt(p.id_po) === parseInt(idPo));
    if (selected) {
        const noPo = selected.nomor_po || '-';
        const vendor = selected.nama_vendor || 'Vendor Umum';
        document.getElementById('selectPo').value = selected.id_po;
        document.getElementById('rcvPoSearchInput').value = `[${noPo}] - [${vendor}]`;
        closeRcvPoDropdown();
        handlePoSelected(selected.id_po);
    }
}

function selectPoById(idPo) {
    selectPoItem(idPo);
}

function clearRcvPoSelection() {
    document.getElementById('selectPo').value = '';
    document.getElementById('rcvPoSearchInput').value = '';
    document.getElementById('displayVendor').value = '-';
    document.getElementById('displaySite').value = '-';
    document.getElementById('tabItemCountBadge').textContent = '0';
    document.getElementById('countItemDetailBadge').textContent = '0 Item';
    document.getElementById('btnSubmitReceiving').disabled = true;
    document.getElementById('receivingItemTableBody').innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-5 text-muted">
                <i class="bi bi-arrow-up-circle fs-3 d-block mb-2 text-primary"></i>
                Silakan pilih dokumen Purchase Order (PO) pada Tab 1 terlebih dahulu.
            </td>
        </tr>
    `;
    closeRcvPoDropdown();
}

// -------------------------------------------------------------
// 4. SAAT PO DIPILIH (FETCH DETAIL ITEMS)
// -------------------------------------------------------------
async function handlePoSelected(idPo) {
    const btnSubmit = document.getElementById('btnSubmitReceiving');
    const tbody = document.getElementById('receivingItemTableBody');

    if (!idPo) {
        clearRcvPoSelection();
        return;
    }

    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat rincian barang dari PO...
            </td>
        </tr>
    `;

    const res = await apiRequest(`/api/receiving/po_ready.php?id_po=${idPo}`);
    if (!res || !res.success || !res.data) {
        showToast(res ? res.message : 'Gagal memuat rincian PO.', 'danger');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-danger">Gagal memuat rincian barang dari PO.</td>
            </tr>
        `;
        btnSubmit.disabled = true;
        return;
    }

    currentPoData = res.data;
    const po = currentPoData;

    document.getElementById('displayVendor').value = po.nama_vendor || '-';
    document.getElementById('displaySite').value = po.nama_site ? `${po.nama_site} (${po.kode_site || '-'})` : '-';

    const items = po.items || [];
    document.getElementById('tabItemCountBadge').textContent = items.length;
    document.getElementById('countItemDetailBadge').textContent = `${items.length} Item Barang`;

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">PO ini tidak memiliki rincian barang.</td>
            </tr>
        `;
        btnSubmit.disabled = true;
        return;
    }

    let rowsHtml = '';
    items.forEach((item, idx) => {
        const qtyPo = parseFloat(item.qty) || 0;

        rowsHtml += `
            <tr data-id-barang="${item.id_barang}" data-item-idx="${idx}">
                <td class="text-center font-monospace text-muted small">${idx + 1}</td>
                <td>
                    <div class="fw-bold text-dark item-nama-text">${escapeHtml(item.nama_barang || '')}</div>
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                        ${item.nama_kategori ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-tag me-1"></i>${escapeHtml(item.nama_kategori)}</span>` : ''}
                        ${item.nama_merk ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-bookmark me-1"></i>${escapeHtml(item.nama_merk)}</span>` : ''}
                    </div>
                </td>
                <td class="text-center font-monospace text-muted fw-bold">
                    <span class="item-qty-po">${qtyPo}</span>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-center font-monospace fw-bold item-qty-baik text-success" 
                           min="0" max="${qtyPo}" step="1" value="${qtyPo}" required oninput="validateQtyRow(this)">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-center font-monospace fw-bold item-qty-rusak text-danger" 
                           min="0" max="${qtyPo}" step="1" value="0" required oninput="validateQtyRow(this)">
                </td>
                <td class="text-center font-monospace small text-muted">
                    <span class="item-satuan">${escapeHtml(item.satuan || 'PCS')}</span>
                </td>
                <td class="text-center">
                    <input type="hidden" class="item-catatan-val" value="">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 item-btn-note shadow-xs" onclick="openItemNoteModal(${idx})" title="Tambah Catatan QC">
                        <i class="bi bi-chat-left-text"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    btnSubmit.disabled = false;
}

// -------------------------------------------------------------
// 5. VALIDASI BARIS QTY (QTY BAIK + QTY RUSAK <= QTY PO)
// -------------------------------------------------------------
function validateQtyRow(inputEl) {
    const tr = inputEl.closest('tr');
    const qtyPo = parseInt(tr.querySelector('.item-qty-po').textContent) || 0;
    const inpBaik = tr.querySelector('.item-qty-baik');
    const inpRusak = tr.querySelector('.item-qty-rusak');

    let vBaik = parseInt(inpBaik.value) || 0;
    let vRusak = parseInt(inpRusak.value) || 0;

    if (vBaik < 0) { vBaik = 0; inpBaik.value = 0; }
    if (vRusak < 0) { vRusak = 0; inpRusak.value = 0; }

    if (vBaik + vRusak > qtyPo) {
        showToast(`Total Qty Diterima (${vBaik + vRusak}) tidak boleh melebihi Qty Dipesan (${qtyPo}).`, 'warning');
        if (inputEl === inpBaik) {
            inpBaik.value = Math.max(0, qtyPo - vRusak);
        } else {
            inpRusak.value = Math.max(0, qtyPo - vBaik);
        }
    }
}

// -------------------------------------------------------------
// 6. MODAL CATATAN ITEM QC (POP-UP)
// -------------------------------------------------------------
function openItemNoteModal(idx) {
    currentEditingItemIdx = idx;
    const tr = document.querySelector(`tr[data-item-idx="${idx}"]`);
    if (!tr) return;

    const namaBarang = tr.querySelector('.item-nama-text').textContent;
    const existingNote = tr.querySelector('.item-catatan-val').value;

    document.getElementById('modalNoteBarangNama').textContent = namaBarang;
    document.getElementById('modalNoteText').value = existingNote;

    modalItemNoteInstance.show();
}

function saveItemNoteFromModal() {
    if (currentEditingItemIdx === null) return;
    const tr = document.querySelector(`tr[data-item-idx="${currentEditingItemIdx}"]`);
    if (!tr) return;

    const note = document.getElementById('modalNoteText').value.trim();
    tr.querySelector('.item-catatan-val').value = note;

    const btn = tr.querySelector('.item-btn-note');
    if (note) {
        btn.className = 'btn btn-primary btn-sm px-2 py-1 item-btn-note shadow-xs text-white';
        btn.innerHTML = '<i class="bi bi-chat-left-text-fill"></i>';
        btn.title = `Catatan: ${note}`;
    } else {
        btn.className = 'btn btn-outline-secondary btn-sm px-2 py-1 item-btn-note shadow-xs';
        btn.innerHTML = '<i class="bi bi-chat-left-text"></i>';
        btn.title = 'Tambah Catatan QC';
    }

    modalItemNoteInstance.hide();
    showToast('Catatan item berhasil diperbarui.', 'success');
}

// -------------------------------------------------------------
// 7. HANDLE SAVE RECEIVING ORDER (WITH FORMDATA / MULTIPART)
// -------------------------------------------------------------
async function handleSaveReceiving(e) {
    e.preventDefault();

    const nomorRcv = document.getElementById('rcvNomor').value.trim();
    const idPo = document.getElementById('selectPo').value;
    const nomorSj = document.getElementById('rcvNomorSj').value.trim();
    const tanggalDiterima = document.getElementById('rcvTanggalDiterima').value;
    const keterangan = document.getElementById('rcvKeterangan').value.trim();

    if (!nomorRcv) {
        showToast('Nomor Receiving (RCV) wajib diisi.', 'warning');
        return;
    }

    if (!idPo) {
        showToast('Silakan pilih Purchase Order terlebih dahulu.', 'warning');
        return;
    }

    if (!nomorSj) {
        showToast('Nomor SPB / Surat Jalan vendor wajib diisi.', 'warning');
        return;
    }

    // Ambil item dari tabel
    const trItems = document.querySelectorAll('#receivingItemTableBody tr[data-id-barang]');
    if (trItems.length === 0) {
        showToast('Tidak ada rincian barang untuk diterima.', 'warning');
        return;
    }

    const itemsPayload = [];
    let totalDiterimaFisik = 0;

    trItems.forEach(tr => {
        const idBarang = tr.getAttribute('data-id-barang');
        const qtyPo = parseInt(tr.querySelector('.item-qty-po').textContent) || 0;
        const qtyBaik = parseInt(tr.querySelector('.item-qty-baik').value) || 0;
        const qtyRusak = parseInt(tr.querySelector('.item-qty-rusak').value) || 0;
        const catatan = tr.querySelector('.item-catatan-val').value.trim();

        totalDiterimaFisik += (qtyBaik + qtyRusak);

        itemsPayload.push({
            id_barang: idBarang,
            qty_po: qtyPo,
            qty_baik: qtyBaik,
            qty_rusak: qtyRusak,
            catatan: catatan
        });
    });

    if (totalDiterimaFisik === 0) {
        showToast('Kuantitas barang yang diterima fisik tidak boleh 0 semua.', 'warning');
        return;
    }

    // Gunakan FormData untuk mengirim text + file upload surat jalan
    const formData = new FormData();
    formData.append('nomor_rcv', nomorRcv);
    formData.append('id_po', idPo);
    formData.append('nomor_sj', nomorSj);
    formData.append('tanggal_diterima', tanggalDiterima);
    formData.append('keterangan', keterangan);
    formData.append('items', JSON.stringify(itemsPayload));

    if (selectedFileSj) {
        formData.append('file_sj', selectedFileSj);
    }

    const btnSubmit = document.getElementById('btnSubmitReceiving');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan & Menambah Stok...';

    try {
        const res = await apiRequest('/api/receiving/create.php', {
            method: 'POST',
            body: formData
        });

        if (res && res.success) {
            showToast(res.message, 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/receiving/index.php';
            }, 1200);
        } else {
            showToast(res ? res.message : 'Gagal menyimpan penerimaan barang.', 'danger');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan';
        }
    } catch (err) {
        showToast('Terjadi kesalahan koneksi server.', 'danger');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
