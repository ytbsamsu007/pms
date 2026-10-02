<?php
/**
 * Buat Mutasi Barang Baru (Transfer Antar Site)
 * Path: admin/pages/mutasi_barang/create.php
 * Khusus Role: ADMIN, LOGISTIK, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);
$pageTitle = 'Buat Mutasi Barang';
$pageHeading = 'Formulir Mutasi & Transfer Barang';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Buat Transaksi Mutasi Barang Baru</h4>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/pages/mutasi_barang/index.php" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- FORM DENGAN 3 NAV TABS (1. Dokumen, 2. Daftar Barang, 3. Keterangan) -->
    <form id="formMutasi" onsubmit="handleSaveMutasi(event)">
        <div class="card border-0 shadow-sm rounded-3">
            <!-- Nav Tabs Header -->
            <div class="card-header bg-white pt-3 pb-0 px-4 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="mutasiFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="tab-dokumen" data-bs-toggle="tab" data-bs-target="#pane-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>1. Informasi Dokumen
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-barang" data-bs-toggle="tab" data-bs-target="#pane-barang" type="button" role="tab">
                            <i class="bi bi-boxes me-2 text-primary"></i>2. Daftar Barang Dimutasi 
                            <span class="badge bg-primary text-white ms-2" id="tabItemCountBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-keterangan" data-bs-toggle="tab" data-bs-target="#pane-keterangan" type="button" role="tab">
                            <i class="bi bi-card-text me-2 text-primary"></i>3. Keterangan / Alasan Transfer
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="mutasiFormTabContent">
                    
                    <!-- TAB 1: INFORMASI DOKUMEN (2 KOLOM) -->
                    <div class="tab-pane fade show active" id="pane-dokumen" role="tabpanel">
                        <div class="row g-4">
                            
                            <!-- KOLOM KIRI: Identitas & Tanggal -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        Identitas &amp; Waktu Transaksi
                                    </h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Kode Mutasi <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-hash"></i></span>
                                            <input type="text" class="form-control font-monospace fw-bold text-primary bg-light" id="kodeMutasi" required readonly placeholder="Memuat nomor mutasi...">
                                            <button type="button" class="btn btn-outline-secondary" onclick="fetchNextKodeMutasi()" title="Generate Ulang Nomor">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">No. Surat Jalan / Mutasi Manual (Opsional)</label>
                                        <input type="text" class="form-control form-control-sm" id="nomorSuratMutasi" placeholder="Contoh: SJ-2026/09/001">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Tanggal &amp; Waktu Mutasi <span class="text-danger">*</span></label>
                                        <div class="row g-2">
                                            <div class="col-7">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
                                                    <input type="date" class="form-control" id="tanggalMutasiDate" required value="<?= date('Y-m-d') ?>" onchange="fetchNextKodeMutasi()">
                                                </div>
                                            </div>
                                            <div class="col-5">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white"><i class="bi bi-clock"></i></span>
                                                    <input type="text" class="form-control text-center font-monospace" id="tanggalMutasiTime" required maxlength="5" placeholder="HH:MM" value="<?= date('H:i') ?>" oninput="formatTime24(this)" onblur="validateTime24(this)" title="Format 24 Jam (Contoh: 14:30)">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Biaya Operasional Pengiriman (Rp)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white">Rp</span>
                                            <input type="text" class="form-control font-monospace text-end" id="biayaOperasionalDisplay" value="0" placeholder="0" onfocus="this.select()" oninput="handleBiayaInput(this)">
                                            <input type="hidden" id="biayaOperasional" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- KOLOM KANAN: Site, Pemohon & Persetujuan -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        Rute Site &amp; Otorisasi
                                    </h6>

                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-dark">Site Asal (Pengirim) <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm filter-select-truncated" id="idSiteAsal" required onchange="onSiteAsalChange()">
                                                <option value="">-- Pilih Site Asal --</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-dark">Site Tujuan (Penerima) <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm filter-select-truncated" id="idSiteTujuan" required>
                                                <option value="">-- Pilih Site Tujuan --</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Searchable Karyawan Pemohon -->
                                    <div class="mb-3 position-relative">
                                        <label class="form-label small fw-bold text-dark">Karyawan Pemohon Transfer <span class="text-danger">*</span></label>
                                        <input type="hidden" id="idKaryawanRequest" value="" required>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-person-fill"></i></span>
                                            <input type="text" class="form-control form-control-sm" id="karyawanRequestSearchInput" placeholder="Cari kode / nama pemohon..." autocomplete="off" onfocus="openKaryawanReqDropdown()" onclick="openKaryawanReqDropdown()" oninput="filterKaryawanReqDropdown()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearKaryawanReqSelection()" title="Hapus Pilihan">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="custom-search-dropdown d-none" id="karyawanReqDropdown">
                                            <div id="karyawanReqDropdownList">
                                                <div class="p-2 text-center text-muted small">Memuat karyawan...</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Searchable / Select Pejabat Penyetuju Level 1 -->
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Disetujui Oleh (Level 1 - Manager/Admin) <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm filter-select-truncated" id="idKaryawanApproved" required>
                                            <option value="">-- Pilih Pejabat Penyetuju (Level 1) --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TAB 2: DAFTAR BARANG DIMUTASI (SEARCHABLE UI MIRIP REQUEST ORDER) -->
                    <div class="tab-pane fade" id="pane-barang" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">
                                    Rincian Barang yang Dimutasi
                                </h6>
                                <div class="text-muted small" id="infoSiteAsalText">Pilih Site Asal pada Tab 1 terlebih dahulu.</div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" onclick="addNewItemRow()">
                                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Barang
                            </button>
                        </div>

                        <div class="table-container border rounded-3 bg-white" style="overflow: visible; position: relative;">
                            <table class="table table-bordered align-middle mb-0" id="mutasiItemsTable">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">No</th>
                                        <th style="min-width: 300px;">Nama Barang<span class="text-danger">*</span></th>
                                        <th style="width: 140px; text-align: center;">Kode</th>
                                        <th style="width: 130px; text-align: center;">Stok Asal</th>
                                        <th style="width: 130px; text-align: center;">Jumlah Mutasi <span class="text-danger">*</span></th>
                                        <th style="width: 110px; text-align: center;">Satuan</th>
                                        <th style="width: 50px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="mutasiItemsTableBody">
                                    <!-- Dynamic rows rendered via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- TOTAL RINGKASAN -->
                        <div class="card-footer bg-light p-3 border rounded-3 mt-3">
                            <div class="d-flex gap-4 text-muted small">
                                <div><i class="bi bi-boxes me-1 text-primary"></i>Total Kuantitas Dimutasi: <strong class="text-dark font-monospace fs-6" id="summaryTotalQty">0</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: KETERANGAN / ALASAN TRANSFER -->
                    <div class="tab-pane fade" id="pane-keterangan" role="tabpanel">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-2">
                                Keterangan &amp; Alasan Pemindahan Material
                            </h6>
                            
                            <textarea class="form-control" id="keterangan" rows="6" placeholder="Tuliskan keterangan lengkap di sini... Contoh: Transfer material elektroda dan plat untuk percepatan pekerjaan reparasi lambung Dok 2..."></textarea>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
                <a href="<?= BASE_URL ?>/admin/pages/mutasi_barang/index.php" class="btn btn-secondary btn-sm px-4">Batal</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold" id="btnSubmitMutasi">
                    <i class="bi bi-send-fill me-1"></i> Simpan Transaksi Mutasi
                </button>
            </div>
        </div>
    </form>
</div>

<!-- STYLING AUTOCOMPLETE & SEARCHABLE SELECT LAYER -->
<style>
.filter-select-truncated {
    padding-right: 2.25rem !important;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}
#mutasiItemsTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dee2e6 !important;
    width: 100% !important;
}
#mutasiItemsTable th,
#mutasiItemsTable td {
    border-right: 1px solid #dee2e6 !important;
    border-bottom: 1px solid #dee2e6 !important;
    vertical-align: middle !important;
    box-shadow: none !important;
}
#mutasiItemsTable th:last-child,
#mutasiItemsTable td:last-child {
    border-right: none !important;
}
#mutasiItemsTable thead th {
    background-color: #f8f9fa !important;
    border-bottom: 2px solid #dee2e6 !important;
}
.table-container {
    overflow: visible !important;
    position: relative;
}
.mutasi-item-row {
    position: relative;
}
.mutasi-item-row:focus-within {
    z-index: 1055;
}
.item-search-wrapper {
    position: relative;
}
.custom-search-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #b6d4fe;
    border-radius: 0.375rem;
    box-shadow: 0 12px 32px rgba(0,0,0,0.2);
    z-index: 9999 !important;
    max-height: 240px;
    overflow-y: auto;
}
.custom-search-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s ease-in-out;
}
.custom-search-dropdown-item:hover, .custom-search-dropdown-item.active {
    background-color: #f0f7ff;
}
</style>

<script>
let nextRowIndex = 0;
let availableBarangList = [];
let karyawanReqList = [];
let karyawanOptionsCache = null;

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// -------------------------------------------------------------
// 1. FORMAT RIBUAN BIAYA OPERASIONAL & WAKTU 24 JAM
// -------------------------------------------------------------
function handleBiayaInput(input) {
    let cleanVal = input.value.replace(/\D/g, '');
    let num = parseInt(cleanVal, 10) || 0;
    document.getElementById('biayaOperasional').value = num;
    input.value = num.toLocaleString('id-ID');
}

function formatTime24(input) {
    let val = input.value.replace(/\D/g, ''); // Hanya angka
    if (val.length > 4) val = val.substring(0, 4);

    let hh = val.substring(0, 2);
    let mm = val.substring(2, 4);

    if (hh && parseInt(hh, 10) > 23) hh = '23';
    if (mm && parseInt(mm, 10) > 59) mm = '59';

    if (val.length >= 3) {
        input.value = `${hh}:${mm}`;
    } else if (val.length === 2 && input.value.length === 2) {
        input.value = `${hh}:`;
    } else {
        input.value = val;
    }
}

function validateTime24(input) {
    let val = input.value.trim();
    if (!val) {
        const d = new Date();
        const curHH = String(d.getHours()).padStart(2, '0');
        const curMM = String(d.getMinutes()).padStart(2, '0');
        input.value = `${curHH}:${curMM}`;
        return;
    }

    const parts = val.split(':');
    let hh = parseInt(parts[0] || '0', 10);
    let mm = parseInt(parts[1] || '0', 10);

    if (isNaN(hh) || hh < 0) hh = 0;
    if (hh > 23) hh = 23;
    if (isNaN(mm) || mm < 0) mm = 0;
    if (mm > 59) mm = 59;

    input.value = `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}`;
}

// -------------------------------------------------------------
// 2. LOAD INITIAL MASTER DATA
// -------------------------------------------------------------
document.addEventListener('DOMContentLoaded', async () => {
    await fetchNextKodeMutasi();
    await loadInitialDependencies();
    
    // Default 1 baris item awal
    addNewItemRow();
});

async function fetchNextKodeMutasi() {
    const tgl = document.getElementById('tanggalMutasiDate') ? document.getElementById('tanggalMutasiDate').value : '';
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/get_next_number.php?tanggal=${encodeURIComponent(tgl || '')}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.success && json.data) {
            document.getElementById('kodeMutasi').value = json.data.kode_mutasi || json.data.nomor;
        }
    } catch (e) {
        console.error('Error next code:', e);
    }
}

async function loadInitialDependencies() {
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php?action=karyawan_options`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.success && json.data) {
            karyawanOptionsCache = json.data;
            populateDropdowns(json.data);
        }
    } catch (err) {
        console.error('Gagal memuat opsi karyawan/site:', err);
    }
}

function populateDropdowns(data) {
    // 1. Sites
    const selAsal = document.getElementById('idSiteAsal');
    const selTujuan = document.getElementById('idSiteTujuan');
    selAsal.innerHTML = '<option value="">-- Pilih Site Asal --</option>';
    selTujuan.innerHTML = '<option value="">-- Pilih Site Tujuan --</option>';

    if (data.sites) {
        data.sites.forEach(s => {
            selAsal.innerHTML += `<option value="${s.id_site}" title="${s.nama_site}">${s.nama_site} (${s.jenis_site || 'Site'})</option>`;
            selTujuan.innerHTML += `<option value="${s.id_site}" title="${s.nama_site}">${s.nama_site} (${s.jenis_site || 'Site'})</option>`;
        });
    }

    // 2. Karyawan Request (Searchable)
    karyawanReqList = data.karyawan_request || [];
    renderKaryawanReqList(karyawanReqList);

    // 3. Karyawan Approved (Level 1)
    const selApp = document.getElementById('idKaryawanApproved');
    selApp.innerHTML = '<option value="">-- Pilih Pejabat Penyetuju (Level 1) --</option>';
    if (data.karyawan_approved) {
        data.karyawan_approved.forEach(a => {
            selApp.innerHTML += `<option value="${a.id_karyawan}" title="${a.nama_karyawan}">[${a.kode_karyawan || 'KRY'}] - ${a.nama_karyawan} (${a.nama_jabatan} - Level 1)</option>`;
        });
    }
}

// -------------------------------------------------------------
// 3. SEARCHABLE KARYAWAN PEMOHON DROPDOWN
// -------------------------------------------------------------
function openKaryawanReqDropdown() {
    document.getElementById('karyawanReqDropdown')?.classList.remove('d-none');
}

function filterKaryawanReqDropdown() {
    const q = document.getElementById('karyawanReqSearchInput').value.toLowerCase().trim();
    const filtered = karyawanReqList.filter(k => 
        (k.nama_karyawan && k.nama_karyawan.toLowerCase().includes(q)) ||
        (k.kode_karyawan && k.kode_karyawan.toLowerCase().includes(q)) ||
        (k.nama_jabatan && k.nama_jabatan.toLowerCase().includes(q))
    );
    renderKaryawanReqList(filtered);
    openKaryawanReqDropdown();
}

function renderKaryawanReqList(items) {
    const list = document.getElementById('karyawanReqDropdownList');
    if (!list) return;

    if (items.length === 0) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada karyawan ditemukan.</div>`;
        return;
    }

    let html = '';
    items.forEach(k => {
        const labelText = `[${k.kode_karyawan || 'KRY'}] - ${k.nama_karyawan}`;
        html += `
            <div class="custom-search-dropdown-item" onclick="selectKaryawanReq(${k.id_karyawan}, '${labelText.replace(/'/g, "\\'")}')">
                <div class="fw-bold text-dark small"><span class="badge bg-primary-subtle text-primary me-1 font-monospace">[${k.kode_karyawan || 'KRY'}]</span> ${escapeHtml(k.nama_karyawan)}</div>
                <div class="text-muted" style="font-size: 0.72rem;">${escapeHtml(k.nama_jabatan || 'Staff')}</div>
            </div>
        `;
    });
    list.innerHTML = html;
}

function selectKaryawanReq(id, displayText) {
    document.getElementById('idKaryawanRequest').value = id;
    document.getElementById('karyawanRequestSearchInput').value = displayText;
    document.getElementById('karyawanReqDropdown')?.classList.add('d-none');
}

function clearKaryawanReqSelection() {
    document.getElementById('idKaryawanRequest').value = '';
    document.getElementById('karyawanRequestSearchInput').value = '';
    renderKaryawanReqList(karyawanReqList);
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', (e) => {
    if (!e.target.closest('#karyawanReqDropdown') && !e.target.closest('#karyawanRequestSearchInput')) {
        document.getElementById('karyawanReqDropdown')?.classList.add('d-none');
    }
    // Item search dropdowns
    if (!e.target.closest('.item-search-wrapper')) {
        document.querySelectorAll('.custom-search-dropdown').forEach(el => {
            if (el.id !== 'karyawanReqDropdown') el.classList.add('d-none');
        });
    }
});

// -------------------------------------------------------------
// 4. SITE ASAL & LOAD BARANG KHUSUS SITE (STOK > 0)
// -------------------------------------------------------------
async function onSiteAsalChange() {
    const idSite = document.getElementById('idSiteAsal').value;
    const infoText = document.getElementById('infoSiteAsalText');
    const selAsal = document.getElementById('idSiteAsal');
    const selectedText = selAsal.options[selAsal.selectedIndex]?.text || '';

    if (!idSite) {
        availableBarangList = [];
        if (infoText) infoText.textContent = 'Pilih Site Asal pada Tab 1 terlebih dahulu.';
        resetItemRows();
        return;
    }

    if (infoText) {
        infoText.innerHTML = `Menampilkan barang dengan stok fisik &gt; 0 di <strong>${escapeHtml(selectedText)}</strong>`;
    }

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php?action=barang_by_site&id_site_asal=${idSite}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.success) {
            availableBarangList = json.data || [];
            resetItemRows();
        }
    } catch (err) {
        console.error('Error load barang:', err);
    }
}

function resetItemRows() {
    document.getElementById('mutasiItemsTableBody').innerHTML = '';
    addNewItemRow();
}

// -------------------------------------------------------------
// 5. PENGELOLAAN TABEL ITEM BARANG SEARCHABLE (MIRIP REQUEST ORDER)
// -------------------------------------------------------------
function addNewItemRow(data = {}) {
    nextRowIndex++;
    const rowId = `itemRow_${nextRowIndex}`;
    const tbody = document.getElementById('mutasiItemsTableBody');

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'mutasi-item-row';
    tr.innerHTML = `
        <td class="text-center text-muted fw-bold row-number"></td>
        <td>
            <div class="item-search-wrapper" id="wrapper_${rowId}">
                <input type="hidden" class="item-id-barang" value="${data.id_barang || ''}">
                <input type="hidden" class="item-max-stok" value="${data.stok_site || 0}">
                <input type="text" class="form-control form-control-sm item-nama-barang" 
                       placeholder="Ketik / cari nama barang..." 
                       value="${data.nama_barang || ''}" 
                       autocomplete="off" 
                       onfocus="openItemDropdown('${rowId}')" 
                       onclick="openItemDropdown('${rowId}')" 
                       oninput="handleItemSearch('${rowId}')" 
                       required>
                <div class="custom-search-dropdown d-none" id="dropdown_${rowId}"></div>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm item-kode-barang font-monospace text-center bg-light" 
                   placeholder="Kode" value="${data.kode_barang || ''}" readonly>
        </td>
        <td class="text-center font-monospace fw-bold text-primary item-stok-display">
            ${data.stok_site ? Number(data.stok_site).toLocaleString('id-ID') : '0'}
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-qty text-center fw-bold font-monospace" 
                   value="${data.qty || 1}" min="1" step="1" required oninput="validateQtyInput('${rowId}')">
        </td>
        <td class="text-center font-monospace small text-dark item-satuan-display">
            ${data.satuan || 'PCS'}
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeItemRow('${rowId}')" title="Hapus Baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    reindexRows();
    calculateGrandTotal();
}

function removeItemRow(rowId) {
    const rows = document.querySelectorAll('.mutasi-item-row');
    if (rows.length <= 1) {
        alert('Minimal harus ada 1 baris barang yang dimutasi.');
        return;
    }
    const tr = document.getElementById(rowId);
    if (tr) tr.remove();
    reindexRows();
    calculateGrandTotal();
}

function reindexRows() {
    const rows = document.querySelectorAll('.mutasi-item-row');
    rows.forEach((r, idx) => {
        const numEl = r.querySelector('.row-number');
        if (numEl) numEl.textContent = idx + 1;
    });
    document.getElementById('tabItemCountBadge').textContent = rows.length;
}

function openItemDropdown(rowId) {
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    if (availableBarangList.length === 0) {
        dropdown.innerHTML = `<div class="p-2 text-center text-muted small">Pilih Site Asal yang memiliki stok terlebih dahulu.</div>`;
    } else {
        renderDropdownItems(rowId, availableBarangList);
    }
    dropdown.classList.remove('d-none');
}

function handleItemSearch(rowId) {
    const input = document.querySelector(`#${rowId} .item-nama-barang`);
    const q = input.value.toLowerCase().trim();
    const filtered = availableBarangList.filter(b => 
        (b.nama_barang && b.nama_barang.toLowerCase().includes(q)) ||
        (b.kode_barang && b.kode_barang.toLowerCase().includes(q)) ||
        (b.serial_number && b.serial_number.toLowerCase().includes(q))
    );
    renderDropdownItems(rowId, filtered);
    document.getElementById(`dropdown_${rowId}`)?.classList.remove('d-none');
}

function renderDropdownItems(rowId, items) {
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    if (items.length === 0) {
        dropdown.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada barang dengan stok tersedia.</div>`;
        return;
    }

    let html = '';
    items.forEach(b => {
        html += `
            <div class="custom-search-dropdown-item" onclick="selectBarangItem('${rowId}', ${b.id_barang})">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark small">${escapeHtml(b.nama_barang)}</strong>
                    <span class="badge bg-success-subtle text-success font-monospace">Stok: ${Number(b.stok_site).toLocaleString('id-ID')} ${escapeHtml(b.satuan || 'PCS')}</span>
                </div>
                <div class="text-muted small font-monospace" style="font-size: 0.72rem;">
                    ${escapeHtml(b.kode_barang)} ${b.serial_number ? `&bull; SN: ${escapeHtml(b.serial_number)}` : ''}
                </div>
            </div>
        `;
    });
    dropdown.innerHTML = html;
}

function selectBarangItem(rowId, idBarang) {
    const item = availableBarangList.find(b => b.id_barang == idBarang);
    if (!item) return;

    const row = document.getElementById(rowId);
    row.querySelector('.item-id-barang').value = item.id_barang;
    row.querySelector('.item-nama-barang').value = item.nama_barang;
    row.querySelector('.item-kode-barang').value = item.kode_barang || '-';
    row.querySelector('.item-max-stok').value = item.stok_site;
    row.querySelector('.item-stok-display').textContent = Number(item.stok_site).toLocaleString('id-ID');
    row.querySelector('.item-satuan-display').textContent = item.satuan || 'PCS';

    const qtyInp = row.querySelector('.item-qty');
    qtyInp.max = item.stok_site;
    if (parseFloat(qtyInp.value) > item.stok_site) {
        qtyInp.value = item.stok_site;
    }

    document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
    validateQtyInput(rowId);
    calculateGrandTotal();
}

function validateQtyInput(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const maxStok = parseFloat(row.querySelector('.item-max-stok').value || 0);
    const qtyInp = row.querySelector('.item-qty');
    const qty = parseFloat(qtyInp.value || 0);

    if (qty > maxStok && maxStok > 0) {
        qtyInp.classList.add('is-invalid');
        qtyInp.title = `Jumlah tidak boleh lebih dari sisa stok (${maxStok})`;
    } else if (qty <= 0) {
        qtyInp.classList.add('is-invalid');
        qtyInp.title = 'Jumlah harus lebih besar dari 0';
    } else {
        qtyInp.classList.remove('is-invalid');
        qtyInp.title = '';
    }
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let totalQty = 0;
    document.querySelectorAll('.mutasi-item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
        if (qty > 0) totalQty += qty;
    });
    document.getElementById('summaryTotalQty').textContent = totalQty.toLocaleString('id-ID');
}

// -------------------------------------------------------------
// 6. SUBMIT TRANSAKSI MUTASI
// -------------------------------------------------------------
async function handleSaveMutasi(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitMutasi');

    const idSiteAsal = document.getElementById('idSiteAsal').value;
    const idSiteTujuan = document.getElementById('idSiteTujuan').value;
    const idReq = document.getElementById('idKaryawanRequest').value;
    const idApp = document.getElementById('idKaryawanApproved').value;

    if (!idSiteAsal || !idSiteTujuan) {
        showToast('Site Asal dan Site Tujuan wajib dipilih!', 'warning');
        goToTab('tab-dokumen');
        return;
    }
    if (idSiteAsal === idSiteTujuan) {
        showToast('Site Tujuan tidak boleh sama dengan Site Asal!', 'warning');
        goToTab('tab-dokumen');
        return;
    }
    if (!idReq) {
        showToast('Karyawan Pemohon Transfer wajib dipilih!', 'warning');
        goToTab('tab-dokumen');
        return;
    }
    if (!idApp) {
        showToast('Pejabat Penyetuju (Level 1) wajib dipilih!', 'warning');
        goToTab('tab-dokumen');
        return;
    }

    const rows = document.querySelectorAll('.mutasi-item-row');
    if (rows.length === 0) {
        showToast('Minimal harus ada 1 item barang yang dimutasi!', 'warning');
        goToTab('tab-barang');
        return;
    }

    const items = [];
    let hasError = false;

    rows.forEach(row => {
        const bId = parseInt(row.querySelector('.item-id-barang')?.value || 0, 10);
        const maxStok = parseFloat(row.querySelector('.item-max-stok')?.value || 0);
        const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
        const nama = row.querySelector('.item-nama-barang')?.value || '';

        if (!bId || qty <= 0) {
            showToast(`Baris barang "${nama || 'Pilihan'}" belum valid atau jumlahnya 0.`, 'warning');
            goToTab('tab-barang');
            hasError = true;
            return;
        }

        if (qty > maxStok) {
            showToast(`Jumlah mutasi barang "${nama}" (${qty}) melebihi sisa stok (${maxStok}).`, 'error');
            goToTab('tab-barang');
            hasError = true;
            return;
        }

        items.push({ id_barang: bId, qty: qty });
    });

    if (hasError || items.length === 0) return;

    const tglDate = document.getElementById('tanggalMutasiDate').value;
    const tglTime = document.getElementById('tanggalMutasiTime').value || '00:00';
    const tanggalMutasiFull = `${tglDate} ${tglTime}:00`;

    const payload = {
        kode_mutasi: document.getElementById('kodeMutasi').value.trim(),
        nomor_surat_mutasi: document.getElementById('nomorSuratMutasi').value.trim(),
        tanggal_mutasi: tanggalMutasiFull,
        id_site_asal: parseInt(idSiteAsal, 10),
        id_site_tujuan: parseInt(idSiteTujuan, 10),
        id_karyawan_request: parseInt(idReq, 10),
        id_karyawan_approved: parseInt(idApp, 10),
        biaya_operasional: parseFloat(document.getElementById('biayaOperasional').value || 0),
        status: 'MENUNGGU PERSETUJUAN', // Status awal baku saat simpan baru
        keterangan: document.getElementById('keterangan').value.trim(),
        items: items
    };

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan Transaksi...';

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/mutasi_order/index.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Simpan Transaksi Mutasi';

        if (json.success) {
            const finalNomor = json.data?.nomor_surat_mutasi || payload.nomor_surat_mutasi || '-';
            showSuccessTransactionModal({
                title: 'Mutasi Barang Berhasil Disimpan',
                subtitle: 'Berhasil Disimpan Dengan Nomor :',
                nomor: finalNomor,
                redirectUrl: `<?= BASE_URL ?>/admin/pages/mutasi_barang/index.php`,
                delay: 2000
            });
        } else {
            showToast(json.message || 'Gagal menyimpan transaksi mutasi.', 'error');
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Simpan Transaksi Mutasi';
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
