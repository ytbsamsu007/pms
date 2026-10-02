<?php
/**
 * Buat Request Order (RO) Baru - PT Jaya Teknis
 * Decoupled Architecture: 100% RESTful API Data Binding
 * Tab Navigation, 2 Kolom Layout, Searchable Vendor, Role-based Mekanik view (Harga disembunyikan/0)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$isMekanik = ($user['role'] === ROLE_MEKANIK);
$targetRoleName = $isMekanik ? 'Logistik' : 'Purchasing';
$btnSubmitLabel = $isMekanik ? 'Kirim ke Logistik' : 'Kirim ke Purchasing';

$pageTitle = 'Buat Request Order';
$pageHeading = 'Request Order (RO)';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Title -->
    <div class="mb-4">
        <h2 class="fs-4 fw-bold text-dark mb-0">
            Buat Request Order
        </h2>
    </div>

    <!-- FORM DENGAN TAB NAVIGASI (INFORMASI UTAMA & DAFTAR KEBUTUHAN) -->
    <form id="roCreateForm" onsubmit="event.preventDefault();">
        <div class="card border-0 shadow-sm rounded-3">
            <!-- Nav Tabs Header -->
            <div class="card-header bg-white pt-3 pb-0 px-4 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="roFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="tab-info-utama" data-bs-toggle="tab" data-bs-target="#pane-info-utama" type="button" role="tab">
                            <i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>1. Informasi Utama Dokumen
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-daftar-kebutuhan" data-bs-toggle="tab" data-bs-target="#pane-daftar-kebutuhan" type="button" role="tab">
                            <i class="bi bi-boxes me-2 text-primary"></i>2. Daftar Barang 
                            <span class="badge bg-primary text-white ms-2" id="tabItemCountBadge">1</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="roFormTabContent">
                    
                    <!-- TAB 1: INFORMASI UTAMA (DIBAGI 2 KOLOM) -->
                    <div class="tab-pane fade show active" id="pane-info-utama" role="tabpanel">
                        <div class="row g-4">
                            
                            <!-- KOLOM KIRI (3 Input) -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        Identitas &amp; Pengajuan
                                    </h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Nomor RO <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-hash"></i></span>
                                            <input type="text" class="form-control font-monospace fw-bold text-primary bg-light" id="roNomor" readonly required placeholder="Otomatis digenerate">
                                            <button type="button" class="btn btn-outline-primary" onclick="fetchNextRoNumber()" title="Generate Ulang">
                                                <i class="bi bi-arrow-clockwise me-1"></i> Generate
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Tanggal Pengajuan <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
                                            <input type="date" class="form-control" id="roTanggal" required value="<?= date('Y-m-d') ?>" onchange="onTanggalRoChange()">
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Pemohon / Dibuat Oleh <span class="text-danger">*</span></label>
                                        <?php if ($user['role'] === ROLE_ADMIN): ?>
                                            <select class="form-select form-select-sm" id="roIdKaryawan" required>
                                                <option value="">-- Memuat Karyawan --</option>
                                            </select>
                                        <?php else: ?>
                                            <input type="hidden" id="roIdKaryawan" value="<?= $user['id_karyawan'] ?? '' ?>">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white"><i class="bi bi-person-badge"></i></span>
                                                <input type="text" class="form-control bg-white text-dark fw-semibold" readonly value="<?= htmlspecialchars($user['nama']) ?> (<?= htmlspecialchars($user['nama_jabatan'] ?: $user['role']) ?>)">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- KOLOM KANAN (4 Input) -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        Penempatan &amp; Prioritas
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Site / Workshop Tujuan <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="roIdSite" required>
                                            <option value="">-- Pilih Site Penempatan --</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark d-block">Tingkat Prioritas Kebutuhan <span class="text-danger">*</span></label>
                                        <div class="btn-group w-100 btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="roPrioritas" id="prioritasNormal" value="NORMAL" checked>
                                            <label class="btn btn-outline-primary fw-semibold" for="prioritasNormal">
                                                <i class="bi bi-check2-circle me-1"></i> Normal (Reguler)
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="roPrioritas" id="prioritasUrgent" value="URGENT">
                                            <label class="btn btn-outline-danger fw-semibold" for="prioritasUrgent">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Urgent / Mendesak
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Searchable Vendor Selector -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Referensi Vendor(Opsional)</label>
                                        <div class="ro-vendor-search-wrapper position-relative" id="roVendorSearchWrapper">
                                            <input type="hidden" id="roIdVendor" value="">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white"><i class="bi bi-truck"></i></span>
                                                <input type="text" class="form-control" id="roVendorSearchInput" placeholder="Cari / pilih vendor..." autocomplete="off" onfocus="openRoVendorDropdown()" onclick="openRoVendorDropdown()" oninput="debounceRoVendorSearch()">
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearRoVendorSelection()" title="Hapus Pilihan">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div class="ro-vendor-dropdown d-none" id="roVendorDropdown">
                                                <div id="roVendorDropdownList">
                                                    <div class="p-2 text-center text-muted small">Memuat vendor...</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Keperluan / Catatan Pekerjaan</label>
                                        <textarea class="form-control form-control-sm" id="roKeterangan" rows="2" placeholder="Contoh: Overhaul Mesin Genset KM Samudra 02..."></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TAB 2: DAFTAR KEBUTUHAN MATERIAL (DYNAMIC ITEMS) -->
                    <div class="tab-pane fade" id="pane-daftar-kebutuhan" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">
                                    Daftar Kebutuhan Barang
                                </h6>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" onclick="addNewItemRow()">
                                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Barang
                            </button>
                        </div>

                        <!-- Info Warning & Status Klasifikasi RO -->
                        <div id="roClassificationBanner" class="alert alert-warning d-flex align-items-center py-2 px-3 mb-3 border-0 rounded-3 shadow-none text-dark small" style="background-color: #fff3cd;">
                            <i id="roClassificationIcon" class="bi bi-exclamation-triangle-fill text-warning me-2 fs-6"></i>
                            <div id="roClassificationText">
                                Barang baru termasuk mewah harus input di master barang
                            </div>
                        </div>

                        <div class="table-container bg-white" style="overflow: visible; position: relative;">
                            <table class="table table-hover align-middle mb-0" id="roItemsTable">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">No</th>
                                        <th style="min-width: 320px;">Nama Barang / Jasa <span class="text-danger">*</span></th>
                                        <th style="width: 150px; text-align: center;">Kode</th>
                                        <th style="width: 120px; text-align: center;">Jumlah <span class="text-danger">*</span></th>
                                        <th style="width: 120px; text-align: center;">Satuan</th>
                                        <th style="width: 50px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="roItemsTableBody">
                                    <!-- Dynamic rows rendered via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- REAKTIF TOTALS RINGKASAN -->
                        <div class="card-footer bg-light p-3 border rounded-3 mt-3 shadow-none">
                            <div class="d-flex gap-4 text-muted small">
                                <div>Total Kuantitas: <strong class="text-dark" id="summaryTotalQty">0</strong></div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Bawah Form -->
                        <div class="d-flex justify-content-end align-items-center mt-4 pt-3 border-top gap-2">
                            <button type="button" class="btn btn-secondary btn-sm px-3 fw-semibold" onclick="submitRequestOrder('DRAFT')" id="btnSaveDraftBottom">
                                <i class="bi bi-save me-1"></i> Simpan Draft
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="submitRequestOrder('TERKIRIM')" id="btnSubmitRoBottom">
                                <i class="bi bi-send-fill me-1"></i> <?= $btnSubmitLabel ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<!-- DATALIST OPSIONAL REKOMENDASI SATUAN (SEARCHABLE + BISA INPUT MANUAL APAPUN) -->
<datalist id="satuanListOptions">
    <option value="PCS">
    <option value="UNIT">
    <option value="BOX">
    <option value="SET">
    <option value="ROLL">
    <option value="MTR">
    <option value="LTR">
    <option value="KG">
    <option value="BATANG">
    <option value="LEMBAR">
    <option value="PAIL">
    <option value="CAN">
    <option value="DRUM">
    <option value="PACK">
    <option value="ZAK">
    <option value="DUS">
</datalist>

<!-- STYLING AUTOCOMPLETE & SEARCHABLE SELECT LAYER ATAS -->
<style>
/* Tab styling: tidak aktif = biru cerah, aktif = hitam */
#roFormTabs .nav-link {
    color: #0d6efd !important;
    font-weight: 600;
}
#roFormTabs .nav-link.active {
    color: #000000 !important;
    font-weight: 700 !important;
}

#roItemsTable {
    border-collapse: collapse !important;
    width: 100% !important;
    margin-bottom: 0 !important;
}
#roItemsTable th,
#roItemsTable td {
    vertical-align: middle !important;
    border: 1px solid #dee2e6 !important;
    background-clip: padding-box;
}
#roItemsTable thead th {
    background-color: #f8f9fa !important;
    border-bottom: 2px solid #dee2e6 !important;
}
.table-container {
    overflow: visible !important;
    position: relative;
    border-radius: 0.375rem;
}
.table-container > .table {
    border-radius: inherit;
}
.table-container thead th:first-child {
    border-top-left-radius: 0.375rem;
}
.table-container thead th:last-child {
    border-top-right-radius: 0.375rem;
}
.table-container tbody tr:last-child td:first-child {
    border-bottom-left-radius: 0.375rem;
}
.table-container tbody tr:last-child td:last-child {
    border-bottom-right-radius: 0.375rem;
}
.ro-item-row {
    position: relative;
}
.ro-item-row:focus-within {
    z-index: 1055;
}
.ro-item-search-wrapper {
    position: relative;
}
.ro-item-dropdown, .ro-vendor-dropdown {
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
.ro-item-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s ease-in-out;
}
.ro-item-dropdown-item:hover, .ro-item-dropdown-item.active {
    background-color: #f0f7ff;
}

/* Select2 Style Searchable Dropdown for Satuan */
.ro-satuan-select-wrapper {
    position: relative;
    width: 100%;
}
#roItemsTable .form-control,
#roItemsTable .form-control-sm,
#roItemsTable .ro-satuan-btn,
#roItemsTable .btn-sm {
    height: 33px !important;
    min-height: 33px !important;
    max-height: 33px !important;
    font-size: 0.85rem !important;
    box-sizing: border-box !important;
}
#roItemsTable .btn-outline-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 33px;
    height: 33px !important;
    padding: 0 !important;
}
.ro-satuan-btn {
    cursor: pointer;
    background-color: #ffffff;
    border-color: #dee2e6;
    height: 33px !important;
    padding: 0 22px 0 8px !important;
    font-size: 0.85rem !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.45rem center;
    background-size: 10px 8px;
}
.ro-satuan-btn:focus, .ro-satuan-btn:hover {
    border-color: #86b7fe;
    outline: 0;
}
.ro-satuan-dropdown {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    width: 165px;
    background: #ffffff;
    border: 1px solid #b6d4fe;
    border-radius: 0.5rem;
    box-shadow: 0 12px 28px rgba(0,0,0,0.18);
    z-index: 9999 !important;
}
.ro-satuan-list {
    max-height: 180px;
    overflow-y: auto;
}
.ro-satuan-item {
    cursor: pointer;
    font-size: 0.82rem;
    padding: 6px 10px;
    border-radius: 4px;
    margin-bottom: 2px;
    transition: background 0.12s ease-in-out;
}
.ro-satuan-item:hover:not(.active) {
    background-color: #f1f5f9;
}
.ro-satuan-item.active {
    background-color: #0d6efd;
    color: #ffffff;
    font-weight: 600;
}
.ro-satuan-custom-new {
    background-color: #f0f7ff;
    border: 1px dashed #93c5fd;
}
.ro-satuan-custom-new:hover {
    background-color: #dbeafe !important;
}
</style>

<script>
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

const IS_MEKANIK = <?= json_encode($isMekanik) ?>;
let nextRowIndex = 0;
let masterBarangCache = [];
let masterSiteCache = [];
let masterVendorCache = [];
let masterKaryawanCache = [];
let vendorSearchTimeout = null;

document.addEventListener('DOMContentLoaded', async () => {
    await fetchNextRoNumber();
    await loadMasterDependencies();
    
    // Default 1 baris item awal
    addNewItemRow();
});

function goToTab(tabButtonId) {
    const tabEl = document.getElementById(tabButtonId);
    if (tabEl) {
        bootstrap.Tab.getOrCreateInstance(tabEl).show();
    }
}

// -------------------------------------------------------------
// 1. GENERATE NOMOR RO (Menggunakan Pengaturan Tabel Penomoran)
// -------------------------------------------------------------
async function fetchNextRoNumber() {
    const inputNomor = document.getElementById('roNomor');
    const selectedDate = document.getElementById('roTanggal').value || new Date().toISOString().split('T')[0];

    inputNomor.value = 'Memuat nomor...';
    const res = await apiRequest(`/api/request_order/get_next_number.php?date=${encodeURIComponent(selectedDate)}`);
    if (res && res.success && res.data) {
        inputNomor.value = res.data.nomor_ro;
    } else {
        const d = new Date(selectedDate);
        const yymm = String(d.getFullYear()).slice(-2) + String(d.getMonth() + 1).padStart(2, '0');
        inputNomor.value = `PR/${yymm}${String(d.getDate()).padStart(2, '0')}/00001`;
    }
}

function onTanggalRoChange() {
    fetchNextRoNumber();
}

// -------------------------------------------------------------
// 2. LOAD DEPENDENCIES (SITE, VENDOR, KARYAWAN, BARANG)
// -------------------------------------------------------------
async function loadMasterDependencies() {
    // A. Sites
    const resSite = await apiRequest('/api/master/site.php?limit=100');
    if (resSite && resSite.success) {
        masterSiteCache = resSite.data.items || [];
        const siteSelect = document.getElementById('roIdSite');
        siteSelect.innerHTML = '<option value="">-- Pilih Site Penempatan --</option>';
        masterSiteCache.forEach(s => {
            siteSelect.innerHTML += `<option value="${s.id_site}">${s.nama_site} (${s.kode_site || 'SITE'}) - ${s.jenis_site || 'Site'}</option>`;
        });
    }

    // B. Vendors (Searchable Dropdown Cache)
    const resVendor = await apiRequest('/api/master/vendor.php?limit=100');
    if (resVendor && resVendor.success) {
        masterVendorCache = resVendor.data.items || [];
    }

    // C. Karyawan (Jika Admin)
    const karyawanSelect = document.getElementById('roIdKaryawan');
    if (karyawanSelect && karyawanSelect.tagName === 'SELECT') {
        const resKaryawan = await apiRequest('/api/master/karyawan.php?limit=100');
        if (resKaryawan && resKaryawan.success) {
            masterKaryawanCache = resKaryawan.data.items || [];
            karyawanSelect.innerHTML = '<option value="">-- Pilih Karyawan Pemohon --</option>';
            masterKaryawanCache.forEach(k => {
                const displayName = k.nama_karyawan || k.nama_lengkap || k.nama || 'Karyawan';
                karyawanSelect.innerHTML += `<option value="${k.id_karyawan}">${displayName} (${k.kode_karyawan || 'KRY'}) - ${k.nama_jabatan || 'Staf'}</option>`;
            });
        }
    }

    // D. Preload Master Barang Cache
    const resBarang = await apiRequest('/api/master/barang.php?limit=100');
    if (resBarang && resBarang.success) {
        masterBarangCache = resBarang.data.items || [];
    }
}

// -------------------------------------------------------------
// SEARCHABLE VENDOR DROPDOWN
// -------------------------------------------------------------
function openRoVendorDropdown() {
    renderRoVendorList(masterVendorCache);
    document.getElementById('roVendorDropdown')?.classList.remove('d-none');
}

function debounceRoVendorSearch() {
    clearTimeout(vendorSearchTimeout);
    vendorSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('roVendorSearchInput').value.trim();
        const res = await apiRequest(`/api/master/vendor.php?limit=50&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderRoVendorList(res.data.items || []);
        }
    }, 250);
}

function renderRoVendorList(items) {
    const list = document.getElementById('roVendorDropdownList');
    if (!list) return;

    if (items.length === 0) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada vendor ditemukan.</div>`;
        return;
    }
    let html = '';
    items.forEach(v => {
        html += `
            <div class="ro-item-dropdown-item" onclick="selectRoVendor(${v.id_vendor}, '${v.nama_perusahaan.replace(/'/g, "\\'")}', '${v.kode_vendor || ''}')">
                <div class="fw-bold text-dark small">${v.nama_perusahaan}</div>
                <div class="text-muted" style="font-size: 0.72rem;">${v.kode_vendor || '-'} &bull; ${v.kota || '-'}</div>
            </div>
        `;
    });
    list.innerHTML = html;
}

function selectRoVendor(id, name, code) {
    document.getElementById('roIdVendor').value = id;
    document.getElementById('roVendorSearchInput').value = `${name} (${code || 'VND'})`;
    document.getElementById('roVendorDropdown')?.classList.add('d-none');
}

function clearRoVendorSelection() {
    document.getElementById('roIdVendor').value = '';
    document.getElementById('roVendorSearchInput').value = '';
}

// -------------------------------------------------------------
// 3. PENGELOLAAN TABEL ITEM DINAMIS
// -------------------------------------------------------------
function addNewItemRow(data = {}) {
    nextRowIndex++;
    const rowId = `itemRow_${nextRowIndex}`;
    const tbody = document.getElementById('roItemsTableBody');

    const isLuxury = parseInt(data.PPnBM || data.ppnbm || 0, 10) === 1;
    const ratePpnbm = parseFloat(data.rate_PPnBM || data.rate_ppnbm || 0);

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'ro-item-row';
    tr.dataset.ppnbm = isLuxury ? '1' : (data.id_barang ? '0' : '0');
    tr.innerHTML = `
        <td class="text-center text-muted fw-bold row-number"></td>
        <td>
            <div class="ro-item-search-wrapper" id="wrapper_${rowId}">
                <input type="hidden" class="item-id-barang" value="${data.id_barang || ''}">
                <input type="hidden" class="item-ppnbm" value="${isLuxury ? '1' : '0'}">
                <input type="hidden" class="item-rate-ppnbm" value="${ratePpnbm}">
                <input type="hidden" class="item-harga" value="0">
                <div class="position-relative">
                    <input type="text" class="form-control form-control-sm item-nama-barang ${isLuxury ? 'pe-5' : ''}" 
                           placeholder="Ketik / cari nama barang..." 
                           value="${data.nama_barang || ''}" 
                           autocomplete="off" 
                           onfocus="openItemDropdown('${rowId}')" 
                           onclick="openItemDropdown('${rowId}')" 
                           oninput="handleItemSearch('${rowId}')" 
                           required>
                    <span class="luxury-badge-container position-absolute top-50 end-0 translate-middle-y me-2 ${isLuxury ? '' : 'd-none'}" style="pointer-events: none; z-index: 5;">
                        <span class="badge bg-warning text-dark border border-warning-subtle fw-bold luxury-badge-text" style="font-size: 0.65rem;"><i class="bi bi-stars me-1"></i>MEWAH (${ratePpnbm}%)</span>
                    </span>
                </div>
                <div class="ro-item-dropdown d-none" id="dropdown_${rowId}"></div>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm item-kode-barang font-monospace" 
                   placeholder="Kode" value="${data.kode_barang || ''}">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-qty text-center fw-bold" 
                   value="${data.qty || 1}" min="0.1" step="any" required oninput="calculateGrandTotal()">
        </td>
        <td style="width: 125px; min-width: 115px;">
            <div class="ro-satuan-select-wrapper position-relative" id="satuanWrapper_${rowId}">
                <input type="hidden" class="item-satuan" value="${escapeHtml(data.satuan || 'PCS')}">
                <button type="button" class="form-select form-select-sm text-uppercase fw-bold text-center ro-satuan-btn" 
                        id="satuanBtn_${rowId}" 
                        onclick="toggleSatuanDropdown('${rowId}')">
                    ${escapeHtml(data.satuan || 'PCS')}
                </button>
                <div class="ro-satuan-dropdown d-none shadow-lg border rounded-3 bg-white position-absolute p-2" id="satuanDropdown_${rowId}">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-white py-1 px-2 border-end-0"><i class="bi bi-search text-muted" style="font-size: 0.75rem;"></i></span>
                        <input type="text" class="form-control form-control-sm border-start-0 ps-1 font-monospace text-uppercase" 
                                id="satuanSearch_${rowId}" 
                                placeholder="Cari / baru..." 
                                autocomplete="off" 
                                oninput="filterSatuanList('${rowId}')" 
                                onkeydown="handleSatuanKeydown(event, '${rowId}')">
                    </div>
                    <div class="ro-satuan-list" id="satuanList_${rowId}"></div>
                </div>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeItemRow('${rowId}')" title="Hapus Baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    reindexRows();
    updateRoClassificationStatus();
    calculateGrandTotal();
}

function removeItemRow(rowId) {
    const rows = document.querySelectorAll('.ro-item-row');
    if (rows.length <= 1) {
        showToast('Minimal harus ada 1 baris barang dalam Request Order.', 'warning');
        return;
    }
    const tr = document.getElementById(rowId);
    if (tr) tr.remove();
    reindexRows();
    updateRoClassificationStatus();
    calculateGrandTotal();
}

function reindexRows() {
    const rows = document.querySelectorAll('.ro-item-row');
    rows.forEach((row, idx) => {
        const numCell = row.querySelector('.row-number');
        if (numCell) numCell.textContent = idx + 1;
    });
    document.getElementById('tabItemCountBadge').textContent = rows.length;
}

// -------------------------------------------------------------
// KLASIFIKASI & ATURAN BARANG MEWAH VS REGULER & TARIF PPNBM
// -------------------------------------------------------------
function getRoCurrentClassification(excludeRowId = null) {
    const rows = document.querySelectorAll('.ro-item-row');
    let hasLuxury = false;
    let hasNonLuxury = false;
    let luxuryRates = [];

    rows.forEach(r => {
        if (excludeRowId && r.id === excludeRowId) return;
        const idBarang = r.querySelector('.item-id-barang')?.value;
        const namaBarang = r.querySelector('.item-nama-barang')?.value.trim();
        const ppnbm = parseInt(r.querySelector('.item-ppnbm')?.value || '0', 10);
        const rate = parseFloat(r.querySelector('.item-rate-ppnbm')?.value || '0');

        if (idBarang || namaBarang) {
            if (ppnbm === 1) {
                hasLuxury = true;
                luxuryRates.push(rate);
            } else {
                hasNonLuxury = true;
            }
        }
    });

    if (hasLuxury && hasNonLuxury) return { type: 'MIXED_TYPE', rate: null };
    if (hasLuxury) {
        const uniqueRates = [...new Set(luxuryRates)];
        if (uniqueRates.length > 1) {
            return { type: 'MIXED_RATE', rate: null, rates: uniqueRates };
        }
        return { type: 'LUXURY', rate: uniqueRates[0] || 0 };
    }
    if (hasNonLuxury) return { type: 'REGULAR', rate: 0 };
    return { type: 'EMPTY', rate: null };
}

function updateRoClassificationStatus() {
    const cl = getRoCurrentClassification();
    const banner = document.getElementById('roClassificationBanner');
    const icon = document.getElementById('roClassificationIcon');
    const text = document.getElementById('roClassificationText');
    if (!banner || !icon || !text) return;

    if (cl.type === 'LUXURY') {
        banner.className = 'alert alert-warning d-flex align-items-center py-2 px-3 mb-3 border-0 rounded-3 shadow-none text-dark small';
        banner.style.backgroundColor = '#fff3cd';
        icon.className = 'bi bi-stars text-warning fs-6 me-2';
        text.innerHTML = `<strong>RO Khusus Barang Mewah (PPnBM ${cl.rate}%):</strong> Dokumen ini khusus barang mewah dengan tarif ${cl.rate}%.`;
    } else if (cl.type === 'MIXED_RATE') {
        banner.className = 'alert alert-danger d-flex align-items-center py-2 px-3 mb-3 border-0 rounded-3 shadow-none text-dark small';
        banner.style.backgroundColor = '#f8d7da';
        icon.className = 'bi bi-exclamation-octagon-fill text-danger fs-6 me-2';
        text.innerHTML = '<strong>Peringatan:</strong> Tarif PPnBM dalam satu RO harus sama (tidak boleh berbeda).';
    } else if (cl.type === 'MIXED_TYPE') {
        banner.className = 'alert alert-danger d-flex align-items-center py-2 px-3 mb-3 border-0 rounded-3 shadow-none text-dark small';
        banner.style.backgroundColor = '#f8d7da';
        icon.className = 'bi bi-exclamation-octagon-fill text-danger fs-6 me-2';
        text.innerHTML = '<strong>Peringatan:</strong> Barang Mewah (PPnBM) & Non-Mewah tidak boleh digabung dalam satu RO.';
    } else {
        banner.className = 'alert alert-warning d-flex align-items-center py-2 px-3 mb-3 border-0 rounded-3 shadow-none text-dark small';
        banner.style.backgroundColor = '#fff3cd';
        icon.className = 'bi bi-exclamation-triangle-fill text-warning me-2 fs-6';
        text.innerHTML = 'Barang baru termasuk mewah harus input di master barang';
    }
}

// -------------------------------------------------------------
// 4. AUTOCOMPLETE & SELECTION BARANG (Min 3 Karakter, Max 10 Data, Gambar & Merk)
// -------------------------------------------------------------
let itemSearchTimeout = null;

function openItemDropdown(rowId) {
    const input = document.querySelector(`#${rowId} .item-nama-barang`);
    const query = input.value.trim();
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    if (query.length < 3) {
        dropdown.innerHTML = `<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1 text-primary"></i>Ketik minimal 3 karakter untuk mencari barang...</div>`;
        dropdown.classList.remove('d-none');
        return;
    }
    handleItemSearch(rowId);
}

function handleItemSearch(rowId) {
    clearTimeout(itemSearchTimeout);
    const input = document.querySelector(`#${rowId} .item-nama-barang`);
    const query = input.value.trim();
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    if (query.length < 3) {
        dropdown.innerHTML = `<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1 text-primary"></i>Ketik minimal 3 karakter untuk mencari barang...</div>`;
        dropdown.classList.remove('d-none');
        return;
    }

    dropdown.innerHTML = `<div class="p-3 text-center text-muted small"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Mencari barang "${query}"...</div>`;
    dropdown.classList.remove('d-none');

    itemSearchTimeout = setTimeout(async () => {
        const res = await apiRequest(`/api/master/barang.php?limit=10&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderItemDropdown(rowId, (res.data.items || []).slice(0, 10), query);
        } else {
            renderItemDropdown(rowId, [], query);
        }
    }, 250);
}

function renderItemDropdown(rowId, items, query = '') {
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    const currentRo = getRoCurrentClassification(rowId);

    let html = '';
    const cleanQ = query.trim().toLowerCase();

    if (items.length > 0) {
        items.forEach(item => {
            const isItemLuxury = parseInt(item.PPnBM ?? item.ppnbm ?? 0, 10) === 1;
            const itemRate = parseFloat(item.rate_PPnBM ?? item.rate_ppnbm ?? 0);
            const imgSrc = item.foto1 ? `${BASE_URL}/${item.foto1}` : '';
            const imgHtml = imgSrc 
                ? `<img src="${imgSrc}" class="rounded border me-2 flex-shrink-0" style="width: 42px; height: 42px; object-fit: cover;" onerror="this.outerHTML='<div class=\\\'rounded border bg-light text-secondary d-flex align-items-center justify-content-center me-2 flex-shrink-0\\\' style=\\\'width: 42px; height: 42px;\\\'><i class=\\\'bi bi-box-seam fs-5\\\'></i></div>'">` 
                : `<div class="rounded border bg-light text-secondary d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 42px; height: 42px;"><i class="bi bi-box-seam fs-5"></i></div>`;
            
            const namaMerk = item.nama_merk || 'Umum';
            const namaKategori = item.nama_kategori || 'Material';

            let conflictBadge = '';
            let isConflict = false;
            if (currentRo.type === 'LUXURY') {
                if (!isItemLuxury) {
                    isConflict = true;
                    conflictBadge = `<span class="badge bg-danger-subtle text-danger ms-auto"><i class="bi bi-x-circle me-1"></i>Bukan Mewah</span>`;
                } else if (currentRo.rate !== null && itemRate !== currentRo.rate) {
                    isConflict = true;
                    conflictBadge = `<span class="badge bg-danger-subtle text-danger ms-auto"><i class="bi bi-x-circle me-1"></i>Beda Tarif (${itemRate}%)</span>`;
                }
            } else if (currentRo.type === 'REGULAR' && isItemLuxury) {
                isConflict = true;
                conflictBadge = `<span class="badge bg-danger-subtle text-danger ms-auto"><i class="bi bi-x-circle me-1"></i>Barang Mewah</span>`;
            }

            const luxuryTag = isItemLuxury 
                ? `<span class="badge bg-warning text-dark border border-warning-subtle fw-bold"><i class="bi bi-stars me-1"></i>MEWAH (PPnBM ${itemRate}%)</span>` 
                : '';

            html += `
                <div class="ro-item-dropdown-item d-flex align-items-center p-2 ${isConflict ? 'opacity-75 bg-light' : ''}" 
                     onclick="selectMasterBarang('${rowId}', ${item.id_barang}, '${item.kode_barang.replace(/'/g, "\\'")}', '${item.nama_barang.replace(/'/g, "\\'")}', '${item.satuan}', ${isItemLuxury ? 1 : 0}, ${itemRate})">
                    ${imgHtml}
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
                            <span class="text-truncate">${item.nama_barang}</span>
                            ${conflictBadge}
                        </div>
                        <div class="d-flex align-items-center flex-wrap gap-1" style="font-size: 0.72rem;">
                            <span class="badge bg-light text-dark border font-monospace">${item.kode_barang || 'BRG'}</span>
                            <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-tag-fill me-1"></i>${namaMerk}</span>
                            <span class="badge bg-primary-subtle text-primary">${namaKategori}</span>
                            ${luxuryTag}
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += `<div class="p-3 text-center text-muted small">Tidak ada barang katalog yang cocok dengan "<strong>${query}</strong>".</div>`;
    }

    if (cleanQ) {
        let allowCustom = true;
        if (currentRo.type === 'LUXURY') {
            allowCustom = false;
        }

        if (allowCustom) {
            html += `
                <div class="ro-item-dropdown-item text-primary bg-primary-subtle border-top border-primary-subtle py-2 px-3 d-flex align-items-center justify-content-between" onclick="useCustomItemName('${rowId}', '${query.replace(/'/g, "\\'")}')">
                    <span class="small"><i class="bi bi-pencil-square me-1"></i> Gunakan Barang Kustom: <strong>"${query}"</strong></span>
                    <span class="badge bg-primary text-white" style="font-size: 0.68rem;">Input Manual</span>
                </div>
            `;
        } else {
            html += `
                <div class="p-2 text-muted bg-light border-top text-center small">
                    <i class="bi bi-info-circle me-1"></i> Barang input manual tidak dapat digabung ke RO Mewah.
                </div>
            `;
        }
    }

    dropdown.innerHTML = html;
    dropdown.classList.remove('d-none');
}

function isBarangAlreadySelected(idBarang, namaBarang, currentRowId) {
    const allRows = document.querySelectorAll('.ro-item-row');
    for (let r of allRows) {
        if (r.id === currentRowId) continue;
        const existId = r.querySelector('.item-id-barang')?.value;
        const existNama = r.querySelector('.item-nama-barang')?.value.trim().toLowerCase();
        if (idBarang && existId && parseInt(existId, 10) === parseInt(idBarang, 10)) {
            return true;
        }
        if (!idBarang && namaBarang && existNama && existNama === namaBarang.trim().toLowerCase()) {
            return true;
        }
    }
    return false;
}

function selectMasterBarang(rowId, idBarang, kode, nama, satuan, isLuxury = 0, ratePpnbm = 0) {
    const row = document.getElementById(rowId);
    if (!row) return;

    // CEK ATURAN TIDAK BISA MEMUAT MEWAH & NON-MEWAH SERTA PERBEDAAN TARIF DALAM 1 RO
    const currentRo = getRoCurrentClassification(rowId);
    if (currentRo.type === 'LUXURY') {
        if (!isLuxury) {
            showToast('Barang Mewah (PPnBM) & Non-Mewah tidak boleh digabung!', 'error');
            document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
            return;
        }
        if (currentRo.rate !== null && ratePpnbm !== currentRo.rate) {
            showToast(`Tarif PPnBM berbeda (${ratePpnbm}% vs ${currentRo.rate}%). Tarif PPnBM harus sama dalam satu RO.`, 'error');
            document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
            return;
        }
    }
    if (currentRo.type === 'REGULAR' && isLuxury) {
        showToast('Barang Mewah (PPnBM) & Non-Mewah tidak boleh digabung!', 'error');
        document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
        return;
    }

    // CEK VALIDASI GANDA
    if (isBarangAlreadySelected(idBarang, nama, rowId)) {
        showToast(`Barang "${nama}" sudah dipilih pada baris lain.`, 'warning');
        row.querySelector('.item-id-barang').value = '';
        row.querySelector('.item-nama-barang').value = '';
        row.querySelector('.item-kode-barang').value = '';
        row.querySelector('.item-ppnbm').value = '0';
        row.querySelector('.item-rate-ppnbm').value = '0';
        row.querySelector('.luxury-badge-container')?.classList.add('d-none');
        row.querySelector('.item-nama-barang')?.classList.remove('pe-5');
        document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
        return;
    }

    const inputNama = row.querySelector('.item-nama-barang');
    row.querySelector('.item-id-barang').value = idBarang;
    if (inputNama) inputNama.value = nama;
    row.querySelector('.item-kode-barang').value = kode;
    row.querySelector('.item-ppnbm').value = isLuxury ? '1' : '0';
    row.querySelector('.item-rate-ppnbm').value = isLuxury ? ratePpnbm : '0';
    
    const badgeContainer = row.querySelector('.luxury-badge-container');
    const badgeText = row.querySelector('.luxury-badge-text');
    if (badgeContainer) {
        if (isLuxury) {
            if (badgeText) badgeText.innerHTML = `<i class="bi bi-stars me-1"></i>MEWAH (${ratePpnbm}%)`;
            badgeContainer.classList.remove('d-none');
            if (inputNama) inputNama.classList.add('pe-5');
        } else {
            badgeContainer.classList.add('d-none');
            if (inputNama) inputNama.classList.remove('pe-5');
        }
    }

    const chosenSatuan = satuan ? satuan.toUpperCase() : 'PCS';
    row.querySelector('.item-satuan').value = chosenSatuan;
    const btn = document.getElementById(`satuanBtn_${rowId}`);
    if (btn) btn.textContent = chosenSatuan;
    
    row.querySelector('.item-harga').value = 0;

    document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
    updateRoClassificationStatus();
    calculateGrandTotal();
}

function useCustomItemName(rowId, customName) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const currentRo = getRoCurrentClassification(rowId);
    if (currentRo.type === 'LUXURY') {
        showToast('Barang input manual tidak dapat digabung ke RO Barang Mewah.', 'error');
        document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
        return;
    }

    if (isBarangAlreadySelected(null, customName, rowId)) {
        showToast(`Barang "${customName}" sudah ada pada baris lain.`, 'warning');
        row.querySelector('.item-id-barang').value = '';
        row.querySelector('.item-nama-barang').value = '';
        row.querySelector('.item-kode-barang').value = '';
        row.querySelector('.item-ppnbm').value = '0';
        row.querySelector('.item-rate-ppnbm').value = '0';
        row.querySelector('.luxury-badge-container')?.classList.add('d-none');
        row.querySelector('.item-nama-barang')?.classList.remove('pe-5');
        document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
        return;
    }

    const inputNama = row.querySelector('.item-nama-barang');
    row.querySelector('.item-id-barang').value = '';
    if (inputNama) {
        inputNama.value = customName;
        inputNama.classList.remove('pe-5');
    }
    row.querySelector('.item-ppnbm').value = '0';
    row.querySelector('.item-rate-ppnbm').value = '0';
    row.querySelector('.luxury-badge-container')?.classList.add('d-none');
    row.querySelector('.item-harga').value = 0;
    document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
    updateRoClassificationStatus();
    calculateGrandTotal();
}

// -------------------------------------------------------------
// 5. LOGIKA SELECT2 SEARCHABLE DROPDOWN SATUAN & CUSTOM TAGGING
// -------------------------------------------------------------
const STANDARD_SATUAN_LIST = ['PCS', 'UNIT', 'BOX', 'SET', 'ROLL', 'MTR', 'LTR', 'KG', 'BATANG', 'LEMBAR', 'PAIL', 'CAN', 'DRUM', 'PACK', 'ZAK', 'DUS'];

function toggleSatuanDropdown(rowId) {
    const dropdown = document.getElementById(`satuanDropdown_${rowId}`);
    if (!dropdown) return;
    
    const isHidden = dropdown.classList.contains('d-none');
    
    // Tutup dropdown lain
    document.querySelectorAll('.ro-satuan-dropdown').forEach(d => d.classList.add('d-none'));
    document.querySelectorAll('.ro-item-dropdown').forEach(d => d.classList.add('d-none'));
    document.querySelectorAll('.ro-vendor-dropdown').forEach(d => d.classList.add('d-none'));

    if (isHidden) {
        dropdown.classList.remove('d-none');
        const searchInput = document.getElementById(`satuanSearch_${rowId}`);
        if (searchInput) {
            searchInput.value = '';
            setTimeout(() => searchInput.focus(), 50);
        }
        renderSatuanList(rowId, '');
    }
}

function filterSatuanList(rowId) {
    const searchInput = document.getElementById(`satuanSearch_${rowId}`);
    const query = searchInput ? searchInput.value.trim().toUpperCase() : '';
    renderSatuanList(rowId, query);
}

function renderSatuanList(rowId, query) {
    const listContainer = document.getElementById(`satuanList_${rowId}`);
    const row = document.getElementById(rowId);
    if (!listContainer || !row) return;

    const currentVal = (row.querySelector('.item-satuan')?.value || 'PCS').toUpperCase();
    const filtered = STANDARD_SATUAN_LIST.filter(s => s.includes(query));

    let html = '';
    
    // Jika user mengetik kata baru yang tidak persis sama dengan salah satu list standar
    if (query && !STANDARD_SATUAN_LIST.includes(query)) {
        html += `
            <div class="ro-satuan-item ro-satuan-custom-new p-2 rounded d-flex align-items-center justify-content-between mb-1" 
                 onclick="selectSatuanOption('${rowId}', '${escapeHtml(query)}')">
                <span class="small fw-bold text-primary"><i class="bi bi-plus-circle me-1"></i>Gunakan "${escapeHtml(query)}"</span>
                <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.7rem;">Kustom</span>
            </div>
        `;
    }

    if (filtered.length > 0) {
        filtered.forEach(item => {
            const isSelected = (item === currentVal);
            html += `
                <div class="ro-satuan-item p-2 rounded d-flex align-items-center justify-content-between ${isSelected ? 'active' : ''}" 
                     onclick="selectSatuanOption('${rowId}', '${escapeHtml(item)}')">
                    <span class="small font-monospace">${escapeHtml(item)}</span>
                    ${isSelected ? '<i class="bi bi-check2"></i>' : ''}
                </div>
            `;
        });
    } else if (!query) {
        html += `<div class="p-2 text-center text-muted small">Pilih satuan...</div>`;
    }

    listContainer.innerHTML = html;
}

function selectSatuanOption(rowId, val) {
    const row = document.getElementById(rowId);
    if (!row) return;

    const cleanVal = (val || 'PCS').toUpperCase().trim();
    const hiddenInput = row.querySelector('.item-satuan');
    const btn = document.getElementById(`satuanBtn_${rowId}`);
    const dropdown = document.getElementById(`satuanDropdown_${rowId}`);

    if (hiddenInput) hiddenInput.value = cleanVal;
    if (btn) btn.textContent = cleanVal;
    if (dropdown) dropdown.classList.add('d-none');
}

function handleSatuanKeydown(e, rowId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const searchInput = document.getElementById(`satuanSearch_${rowId}`);
        const query = searchInput ? searchInput.value.trim().toUpperCase() : '';
        if (query) {
            selectSatuanOption(rowId, query);
        } else {
            const firstItem = document.querySelector(`#satuanList_${rowId} .ro-satuan-item`);
            if (firstItem) firstItem.click();
        }
    } else if (e.key === 'Escape') {
        document.getElementById(`satuanDropdown_${rowId}`)?.classList.add('d-none');
    }
}

// Tutup dropdown jika klik di luar
document.addEventListener('click', (e) => {
    if (!e.target.closest('.ro-item-search-wrapper')) {
        document.querySelectorAll('.ro-item-search-wrapper .ro-item-dropdown').forEach(d => d.classList.add('d-none'));
    }
    if (!e.target.closest('#roVendorSearchWrapper')) {
        document.getElementById('roVendorDropdown')?.classList.add('d-none');
    }
    if (!e.target.closest('.ro-satuan-select-wrapper')) {
        document.querySelectorAll('.ro-satuan-dropdown').forEach(d => d.classList.add('d-none'));
    }
});

// -------------------------------------------------------------
// 5. HITUNG REAKTIF TOTAL KUANTITAS
// -------------------------------------------------------------
function calculateGrandTotal() {
    const rows = document.querySelectorAll('.ro-item-row');
    let totalQty = 0;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        totalQty += qty;
    });

    const elTotalQty = document.getElementById('summaryTotalQty');
    if (elTotalQty) elTotalQty.textContent = totalQty.toLocaleString('id-ID');
}

// -------------------------------------------------------------
// 6. SUBMIT REQUEST ORDER (DRAFT ATAU TERKIRIM)
// -------------------------------------------------------------
async function submitRequestOrder(targetStatus = 'TERKIRIM') {
    const nomor = document.getElementById('roNomor')?.value.trim() || '';
    let tanggalRo = document.getElementById('roTanggal')?.value || '';
    if (tanggalRo && tanggalRo.length === 10) {
        const now = new Date();
        const hh = String(now.getHours()).padStart(2, '0');
        const mm = String(now.getMinutes()).padStart(2, '0');
        const ss = String(now.getSeconds()).padStart(2, '0');
        tanggalRo = `${tanggalRo} ${hh}:${mm}:${ss}`;
    }
    const idKaryawan = document.getElementById('roIdKaryawan')?.value || null;
    const idSite = document.getElementById('roIdSite')?.value || '';
    const idVendor = document.getElementById('roIdVendor')?.value || null;
    const prioritas = document.querySelector('input[name="roPrioritas"]:checked')?.value || 'NORMAL';
    const keterangan = document.getElementById('roKeterangan')?.value.trim() || '';

    // Validasi Form Utama
    if (!nomor) {
        showToast('Nomor Request Order harus diisi.', 'error');
        goToTab('tab-info-utama');
        document.getElementById('roNomor').focus();
        return;
    }
    if (!tanggalRo) {
        showToast('Tanggal pengajuan harus diisi.', 'error');
        goToTab('tab-info-utama');
        document.getElementById('roTanggal').focus();
        return;
    }
    if (!idSite) {
        showToast('Harap pilih Site / Lokasi tujuan pengadaan.', 'error');
        goToTab('tab-info-utama');
        document.getElementById('roIdSite').focus();
        return;
    }

    const rows = document.querySelectorAll('.ro-item-row');
    const items = [];
    let hasError = false;

    for (let idx = 0; idx < rows.length; idx++) {
        const row = rows[idx];
        const idBarang = row.querySelector('.item-id-barang').value || null;
        const namaBarang = row.querySelector('.item-nama-barang').value.trim();
        const kodeBarang = row.querySelector('.item-kode-barang').value.trim();
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const satuan = row.querySelector('.item-satuan').value || 'PCS';
        const harga = parseFloat(row.querySelector('.item-harga').value) || 0;

        if (!namaBarang) {
            showToast(`Nama barang pada baris ke-${idx + 1} tidak boleh kosong.`, 'error');
            goToTab('tab-daftar-kebutuhan');
            hasError = true;
            break;
        }
        if (qty <= 0) {
            showToast(`Jumlah (Qty) untuk "${namaBarang}" harus lebih besar dari 0.`, 'error');
            goToTab('tab-daftar-kebutuhan');
            hasError = true;
            break;
        }

        // Validasi Duplikasi pada payload
        if (idBarang) {
            if (items.some(it => it.id_barang === parseInt(idBarang, 10))) {
                showToast(`Barang "${namaBarang}" dipilih ganda. Harap satukan kuantitasnya dalam satu baris.`, 'warning');
                goToTab('tab-daftar-kebutuhan');
                hasError = true;
                break;
            }
        } else {
            if (items.some(it => it.nama_barang.toLowerCase() === namaBarang.toLowerCase())) {
                showToast(`Barang "${namaBarang}" diinput ganda. Harap satukan kuantitasnya dalam satu baris.`, 'warning');
                goToTab('tab-daftar-kebutuhan');
                hasError = true;
                break;
            }
        }

        // Validasi Aturan Barang Mewah vs Non-Mewah & Keseragaman Tarif
        const currentClassification = getRoCurrentClassification();
        if (currentClassification.type === 'MIXED_TYPE') {
            showToast('Barang Mewah (PPnBM) & Non-Mewah tidak boleh digabung dalam satu RO.', 'error');
            goToTab('tab-daftar-kebutuhan');
            hasError = true;
            break;
        }
        if (currentClassification.type === 'MIXED_RATE') {
            showToast('Tarif PPnBM barang dalam satu RO harus sama (tidak boleh berbeda).', 'error');
            goToTab('tab-daftar-kebutuhan');
            hasError = true;
            break;
        }

        items.push({
            id_barang: idBarang ? parseInt(idBarang, 10) : null,
            kode_barang: kodeBarang,
            nama_barang: namaBarang,
            qty: qty,
            satuan: satuan,
            harga: harga,
            subtotal: qty * harga
        });
    }

    if (hasError) return;

    const payload = {
        nomor: nomor,
        tanggal_ro: tanggalRo,
        id_karyawan: parseInt(idKaryawan, 10),
        id_site: parseInt(idSite, 10),
        id_vendor: idVendor ? parseInt(idVendor, 10) : null,
        prioritas: prioritas,
        status: targetStatus,
        keterangan: keterangan,
        items: items
    };

    const btnDraftBtm = document.getElementById('btnSaveDraftBottom');
    const btnSubmitBtm = document.getElementById('btnSubmitRoBottom');
    const originalSubmitText = btnSubmitBtm ? btnSubmitBtm.innerHTML : '';
    const originalDraftText = btnDraftBtm ? btnDraftBtm.innerHTML : '';

    if (btnDraftBtm) btnDraftBtm.disabled = true;
    if (btnSubmitBtm) btnSubmitBtm.disabled = true;
    if (targetStatus === 'TERKIRIM' && btnSubmitBtm) {
        btnSubmitBtm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim ke <?= $targetRoleName ?>...';
    } else if (btnDraftBtm) {
        btnDraftBtm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan Draft...';
    }

    try {
        const res = await apiRequest('/api/request_order/create.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (btnDraftBtm) {
            btnDraftBtm.disabled = false;
            btnDraftBtm.innerHTML = originalDraftText;
        }
        if (btnSubmitBtm) {
            btnSubmitBtm.disabled = false;
            btnSubmitBtm.innerHTML = originalSubmitText;
        }

        if (res && res.success) {
            const finalNomor = res.data?.nomor_ro || payload.nomor || '-';
            showSuccessTransactionModal({
                title: 'Request Order Berhasil Disimpan',
                subtitle: 'Berhasil Disimpan Dengan Nomor :',
                nomor: finalNomor,
                redirectUrl: '<?= BASE_URL ?>/admin/pages/request_order/index.php'
            });
        } else {
            showToast(res ? res.message : 'Gagal memproses Request Order.', 'error');
        }
    } catch (err) {
        console.error('Submit RO Error:', err);
        showToast('Terjadi kesalahan koneksi jaringan.', 'error');
        if (btnDraftBtm) {
            btnDraftBtm.disabled = false;
            btnDraftBtm.innerHTML = originalDraftText;
        }
        if (btnSubmitBtm) {
            btnSubmitBtm.disabled = false;
            btnSubmitBtm.innerHTML = originalSubmitText;
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
