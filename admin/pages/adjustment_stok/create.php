<?php
/**
 * Buat Transaksi Penyesuaian Stok (Stock Adjustment) Baru
 * Path: admin/pages/adjustment_stok/create.php
 * Khusus Role: ADMIN, LOGISTIK, MEKANIK, MANAGER
 * (Catatan: Hak Approval hanya dimiliki oleh LOGISTIK dan ADMIN)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MEKANIK, ROLE_MANAGER]);
$pageTitle = 'Buat Stock Adjustment';
$pageHeading = 'Formulir Penyesuaian Stok Barang';

$namaPembuat = $user['nama'] ?? $user['nama_lengkap'] ?? $user['username'] ?? 'Petugas';
$kodeKaryawan = $user['kode_karyawan'] ?? '';
$displayPetugas = $namaPembuat . ($kodeKaryawan ? " ({$kodeKaryawan})" : "");

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Formulir Stock Adjustment</h4>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/pages/adjustment_stok/index.php" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- MAIN FORM DENGAN NAV TABS -->
    <form id="formAdjustment" onsubmit="handleSaveAdjustment(event)">
        <div class="card border-0 shadow-sm rounded-3">
            
            <!-- Nav Tabs Header -->
            <div class="card-header bg-white pt-3 pb-0 px-4 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="adjFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="tab-dokumen" data-bs-toggle="tab" data-bs-target="#pane-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>1. Informasi Penyesuaian
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-barang" data-bs-toggle="tab" data-bs-target="#pane-barang" type="button" role="tab">
                            <i class="bi bi-boxes me-2 text-primary"></i>2. Daftar Barang Disesuaikan 
                            <span class="badge bg-primary text-white ms-2" id="tabItemCountBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-keterangan" data-bs-toggle="tab" data-bs-target="#pane-keterangan" type="button" role="tab">
                            <i class="bi bi-card-text me-2 text-primary"></i>3. Keterangan &amp; Alasan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-persetujuan" data-bs-toggle="tab" data-bs-target="#pane-persetujuan" type="button" role="tab">
                            <i class="bi bi-check2-circle me-2 text-primary"></i>4. Persetujuan
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="adjFormTabContent">
                    
                    <!-- TAB 1: INFORMASI PENYESUAIAN -->
                    <div class="tab-pane fade show active" id="pane-dokumen" role="tabpanel">
                        <div class="row g-4">
                            <!-- KOLOM KIRI: Site, Tanggal & Petugas -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        Lokasi &amp; Waktu Penyesuaian
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Nomor Adjustment</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control font-monospace fw-bold text-primary bg-light" id="nomorAdjustment" readonly placeholder="[Otomatis]">
                                            <button class="btn btn-outline-secondary" type="button" onclick="fetchNextNomorAdjustment()" title="Refresh Nomor">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Lokasi Gudang / Site <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm fw-semibold" id="idSite" required onchange="handleSiteChange()">
                                            <option value="">-- Memuat daftar site... --</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Tanggal &amp; Waktu <span class="text-danger">*</span></label>
                                        <div class="row g-2">
                                            <div class="col-7">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white"><i class="bi bi-calendar3"></i></span>
                                                    <input type="date" class="form-control" id="tanggalAdjustmentDate" required value="<?= date('Y-m-d') ?>" onchange="fetchNextNomorAdjustment()">
                                                </div>
                                            </div>
                                            <div class="col-5">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white"><i class="bi bi-clock"></i></span>
                                                    <input type="text" class="form-control text-center font-monospace" id="tanggalAdjustmentTime" required maxlength="5" placeholder="HH:MM" value="<?= date('H:i') ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Petugas Pembuat</label>
                                        <input type="text" class="form-control form-control-sm bg-white" readonly value="<?= htmlspecialchars($displayPetugas) ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- KOLOM KANAN: Jenis Penyesuaian -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        Jenis Penyesuaian
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Jenis Penyesuaian <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm fw-semibold" id="jenisAdjustment" required onchange="handleJenisAdjustmentChange()">
                                            <option value="PENAMBAHAN" selected>PENAMBAHAN</option>
                                            <option value="PENGURANGAN">PENGURANGAN</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Navigasi Tab 1 -->
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-barang')">
                                Lanjut ke Daftar Barang <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: DAFTAR BARANG YANG DISESUAIKAN -->
                    <div class="tab-pane fade" id="pane-barang" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Rincian Barang yang Disesuaikan</h6>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" onclick="addNewItemRow()">
                                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Barang
                            </button>
                        </div>

                        <div class="table-container bg-white mb-3" style="overflow: visible; position: relative;">
                            <table class="table table-bordered table-hover align-middle mb-0" id="tableAdjItems">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 45px;">No</th>
                                        <th style="min-width: 280px;">Nama Barang <span class="text-danger">*</span></th>
                                        <th class="text-center" style="width: 95px;">Satuan</th>
                                        <th class="text-end" style="width: 120px;">Kts Sistem</th>
                                        <th class="text-end" style="width: 130px;" id="thQtyInput">Kts Tambah</th>
                                        <th class="text-end" style="width: 120px;">Kts Akhir</th>
                                        <th class="text-end" style="width: 160px;">Harga</th>
                                        <th class="text-center" style="width: 90px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyAdjItems">
                                    <!-- Dynamic rows rendered via JS -->
                                </tbody>
                                <tfoot class="table-light" id="tfootAdjItems">
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end text-uppercase pe-3">Total:</td>
                                        <td class="text-end font-monospace text-primary" id="footerTotalQty">0</td>
                                        <td></td>
                                        <td class="text-end font-monospace text-primary" id="footerTotalHarga">0</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Tombol Navigasi Tab 2 -->
                        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-dokumen')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Info Dokumen
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-keterangan')">
                                Lanjut ke Alasan &amp; Keterangan <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: ALASAN & KETERANGAN -->
                    <div class="tab-pane fade" id="pane-keterangan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="p-3 bg-light rounded-3 border">
                                    <h6 class="fw-bold text-dark mb-3">
                                        Alasan &amp; Catatan Tambahan
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Alasan Penyesuaian <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="alasan" required placeholder="Contoh: Koreksi Fisik / Barang Rusak / Pemakaian Internal">
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Keterangan Tambahan (Opsional)</label>
                                        <textarea class="form-control form-control-sm" id="keterangan" rows="4" placeholder="Catatan opsional mengenai kronologi atau keperluan penyesuaian stok"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">Ringkasan Dokumen</h6>
                                    <table class="table table-sm table-borderless small mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 50%;">Total Item:</td>
                                            <td class="fw-bold text-end" id="summaryTotalItems">0 Item</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Navigasi Tab 3 -->
                        <div class="d-flex justify-content-between align-items-center mt-4 border-top pt-3 flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-barang')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Barang
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-persetujuan')">
                                Lanjut ke Persetujuan <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: PERSETUJUAN -->
                    <div class="tab-pane fade" id="pane-persetujuan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border">
                                   

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Disetujui Oleh (Kepala Site / Head of) <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm fw-semibold" id="idKaryawanApproved" required>
                                            <option value="">-- Pilih Site pada Tab 1 terlebih dahulu --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit Akhir -->
                        <div class="d-flex justify-content-between align-items-center mt-4 border-top pt-3 flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-keterangan')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Alasan &amp; Keterangan
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light border btn-sm px-3" onclick="handleSaveAction('DRAFT')">
                                    <i class="bi bi-floppy me-1"></i> Simpan Draft
                                </button>
                                <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="handleSaveAction('PENDING')">
                                    <i class="bi bi-send-check me-1"></i> Simpan &amp; Ajukan Approval
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL INPUT CATATAN PER BARIS ITEM -->
<div class="modal fade" id="modalCatatanItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    Catatan Rincian Barang
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modalTargetRowId" value="">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark">Nama Barang:</label>
                    <div class="fw-semibold text-dark p-2 bg-light rounded border small" id="modalBarangTitle">-</div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark">Catatan / Keterangan Item:</label>
                    <textarea class="form-control form-control-sm" id="modalCatatanInput" rows="3" placeholder="Masukkan catatan kondisi fisik, nomor seri, atau detail alasan item ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" onclick="saveCatatanFromModal()">
                    <i class="bi bi-check-lg me-1"></i> Simpan Catatan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- STYLING AUTOCOMPLETE & SEARCH DROPDOWN -->
<style>
.table-container {
    overflow: visible !important;
    position: relative;
}
#tableAdjItems {
    border-collapse: collapse !important;
    width: 100% !important;
    margin-bottom: 0 !important;
    box-shadow: none !important;
    border: 1px solid #ced4da !important;
}
#tableAdjItems th,
#tableAdjItems td {
    vertical-align: middle !important;
    border: 1px solid #ced4da !important;
    background-clip: padding-box !important;
}
#tableAdjItems thead th {
    background-color: #f8f9fa !important;
    border-bottom: 2px solid #adb5bd !important;
    font-weight: 600;
}
#tableAdjItems tbody tr:hover td {
    background-color: #f1f5f9 !important;
    border-color: #ced4da !important;
}
#tableAdjItems tfoot td {
    background-color: #f8f9fa !important;
    border-top: 2px solid #adb5bd !important;
}
.adj-item-row {
    position: relative;
}
.adj-item-row:focus-within {
    z-index: 1055;
}
.adj-item-search-wrapper {
    position: relative;
}
.adj-item-dropdown {
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
.adj-item-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s ease-in-out;
}
.adj-item-dropdown-item:hover {
    background-color: #f0f7ff;
}
</style>

<script>
let nextRowIndex = 0;
let itemSearchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchNextNomorAdjustment();
    loadSitesList();
    addNewItemRow();
    updateThQtyHeader();

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.adj-item-search-wrapper')) {
            document.querySelectorAll('.adj-item-dropdown').forEach(el => el.classList.add('d-none'));
        }
    });
});

async function fetchNextNomorAdjustment() {
    const tgl = document.getElementById('tanggalAdjustmentDate')?.value || '';
    const nomorInput = document.getElementById('nomorAdjustment');
    if (!nomorInput) return;
    
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/adjustment_stok/get_next_number.php?tanggal=${encodeURIComponent(tgl)}`);
        const json = await res.json();
        if (json.success && json.data && json.data.nomor) {
            nomorInput.value = json.data.nomor;
        } else {
            nomorInput.value = '[Otomatis]';
        }
    } catch (e) {
        console.error('Error fetching next nomor adjustment:', e);
    }
}

async function loadSitesList() {
    const siteSelect = document.getElementById('idSite');
    try {
        const res = await fetch('<?= BASE_URL ?>/api/master/site.php?limit=100');
        const json = await res.json();
        if (json.success && json.data && json.data.items) {
            let html = '<option value="">-- Pilih Site Gudang --</option>';
            json.data.items.forEach(s => {
                html += `<option value="${s.id_site}" data-headof-id="${s.id_karyawan_headof || ''}" data-headof-name="${escapeHtml(s.kepala_site || '')}">
                    ${escapeHtml(s.nama_site)} (${escapeHtml(s.kode_site || 'SITE')})
                </option>`;
            });
            siteSelect.innerHTML = html;
        } else {
            siteSelect.innerHTML = '<option value="">Gagal memuat site</option>';
        }
    } catch (e) {
        console.error('Error load sites:', e);
        siteSelect.innerHTML = '<option value="">Gagal memuat site</option>';
    }
}

function goToTab(tabButtonId) {
    const tabTrigger = document.getElementById(tabButtonId);
    if (tabTrigger) {
        const tab = new bootstrap.Tab(tabTrigger);
        tab.show();
    }
}

function updateThQtyHeader() {
    const jenis = document.getElementById('jenisAdjustment').value;
    const th = document.getElementById('thQtyInput');
    if (th) {
        th.innerHTML = (jenis === 'PENAMBAHAN' ? 'Kts Tambah' : 'Kts Kurang') + ' <span class="text-danger">*</span>';
    }
}

function handleJenisAdjustmentChange() {
    updateThQtyHeader();
    recalculateAllRows();
}

function handleSiteChange() {
    updateApproverOptions();
    refreshAllRowsStok();
}

function updateApproverOptions() {
    const siteSelect = document.getElementById('idSite');
    const selectedOption = siteSelect.selectedOptions[0];
    const approverSelect = document.getElementById('idKaryawanApproved');
    if (!approverSelect) return;

    approverSelect.innerHTML = '';

    if (!selectedOption || !selectedOption.value) {
        approverSelect.innerHTML = '<option value="">-- Pilih Site pada Tab 1 terlebih dahulu --</option>';
        return;
    }

    const headOfId = selectedOption.getAttribute('data-headof-id');
    const headOfName = selectedOption.getAttribute('data-headof-name');

    if (headOfId && headOfId !== 'null' && headOfId !== '0') {
        const opt = document.createElement('option');
        opt.value = headOfId;
        opt.textContent = `${headOfName && headOfName !== '-' ? headOfName : 'Kepala Site'} (Kepala Site / Head of)`;
        opt.selected = true;
        approverSelect.appendChild(opt);
    } else {
        approverSelect.innerHTML = '<option value="">-- Site ini belum memiliki Kepala Site (Head of) --</option>';
    }
}

async function refreshAllRowsStok() {
    const siteId = document.getElementById('idSite').value;
    const jenis = document.getElementById('jenisAdjustment').value;
    if (!siteId) return;

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/adjustment_stok/barang.php?id_site=${siteId}&jenis=${encodeURIComponent(jenis)}`);
        const result = await res.json();
        if (result.success && result.data) {
            const mapStok = {};
            result.data.forEach(b => {
                mapStok[b.id_barang] = {
                    stok: parseFloat(b.stok_sistem) || 0,
                    harga: parseFloat(b.estimasi_harga) || 0
                };
            });

            document.querySelectorAll('.adj-item-row').forEach(row => {
                const idBarang = row.querySelector('.item-id-barang')?.value;
                if (idBarang && mapStok[idBarang]) {
                    row.querySelector('.item-stok-sistem').value = mapStok[idBarang].stok;
                    row.querySelector('.cell-stok-sistem').innerText = formatNumber(mapStok[idBarang].stok);
                    const currentHarga = parseFormattedNumber(row.querySelector('.item-harga-display').value);
                    if (!currentHarga) {
                        row.querySelector('.item-harga').value = mapStok[idBarang].harga;
                        row.querySelector('.item-harga-display').value = formatNumber(mapStok[idBarang].harga);
                    }
                    recalculateRowElement(row);
                }
            });
            updateTableFooterTotals();
        }
    } catch (e) {
        console.error(e);
    }
}

function addNewItemRow(data = {}) {
    nextRowIndex++;
    const rowId = `itemRow_${nextRowIndex}`;
    const tbody = document.getElementById('tbodyAdjItems');
    if (!tbody) return;

    const hargaNum = parseFloat(data.harga_satuan) || 0;

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'adj-item-row';
    tr.innerHTML = `
        <td class="text-center text-muted fw-bold row-number"></td>
        <td>
            <div class="adj-item-search-wrapper" id="wrapper_${rowId}">
                <input type="hidden" class="item-id-barang" value="${data.id_barang || ''}">
                <input type="hidden" class="item-satuan" value="${data.satuan || 'PCS'}">
                <input type="hidden" class="item-stok-sistem" value="${data.stok_sistem || 0}">
                <input type="hidden" class="item-catatan" value="${escapeHtml(data.keterangan || '')}">
                <input type="text" class="form-control form-control-sm item-nama-barang" 
                       placeholder="Ketik nama barang..." 
                       value="${escapeHtml(data.nama_barang || '')}" 
                       autocomplete="off" 
                       onfocus="openItemDropdown('${rowId}')" 
                       onclick="openItemDropdown('${rowId}')" 
                       oninput="handleItemSearch('${rowId}')" 
                       required>
                <div class="adj-item-dropdown d-none" id="dropdown_${rowId}"></div>
            </div>
        </td>
        <td class="text-center font-monospace small text-muted cell-satuan">
            ${escapeHtml(data.satuan || '-')}
        </td>
        <td class="text-end font-monospace cell-stok-sistem">
            ${formatNumber(data.stok_sistem || 0)}
        </td>
        <td>
            <input type="number" step="any" min="0.0001" class="form-control form-control-sm item-qty text-end font-monospace fw-bold" 
                   value="${data.qty || 1}" required oninput="recalculateRowElement(document.getElementById('${rowId}'))">
        </td>
        <td class="text-end font-monospace cell-akhir">
            <span class="fw-bold text-dark">0</span>
        </td>
        <td>
            <input type="hidden" class="item-harga" value="${hargaNum}">
            <input type="text" class="form-control form-control-sm item-harga-display text-end font-monospace" 
                   value="${formatNumber(hargaNum)}" oninput="handleHargaInput(this, '${rowId}')">
        </td>
        <td class="text-center">
            <div class="d-flex justify-content-center gap-1">
                <button type="button" class="btn btn-outline-secondary btn-sm p-1 btn-catatan-modal" onclick="openCatatanModal('${rowId}')" title="Catatan Item">
                    <i class="bi bi-chat-left-text"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeItemRow('${rowId}')" title="Hapus Baris">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    `;

    tbody.appendChild(tr);
    reindexRows();
    recalculateRowElement(tr);
}

function handleHargaInput(el, rowId) {
    let cleanVal = el.value.replace(/[^0-9]/g, '');
    let numVal = parseFloat(cleanVal) || 0;
    el.value = formatNumber(numVal);
    
    const tr = document.getElementById(rowId);
    if (tr) {
        tr.querySelector('.item-harga').value = numVal;
        updateTableFooterTotals();
    }
}

function openCatatanModal(rowId) {
    const tr = document.getElementById(rowId);
    if (!tr) return;

    const namaBarang = tr.querySelector('.item-nama-barang')?.value.trim() || 'Barang Baru (Belum Dipilih)';
    const catatan = tr.querySelector('.item-catatan')?.value || '';

    document.getElementById('modalTargetRowId').value = rowId;
    document.getElementById('modalBarangTitle').innerText = namaBarang;
    document.getElementById('modalCatatanInput').value = catatan;

    const modal = new bootstrap.Modal(document.getElementById('modalCatatanItem'));
    modal.show();
}

function saveCatatanFromModal() {
    const rowId = document.getElementById('modalTargetRowId').value;
    const catatanVal = document.getElementById('modalCatatanInput').value.trim();
    const tr = document.getElementById(rowId);
    if (tr) {
        tr.querySelector('.item-catatan').value = catatanVal;
        const btnCatatan = tr.querySelector('.btn-catatan-modal');
        if (catatanVal) {
            btnCatatan.classList.remove('btn-outline-secondary');
            btnCatatan.classList.add('btn-primary');
        } else {
            btnCatatan.classList.remove('btn-primary');
            btnCatatan.classList.add('btn-outline-secondary');
        }
    }
    const modalEl = document.getElementById('modalCatatanItem');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function removeItemRow(rowId) {
    const rows = document.querySelectorAll('.adj-item-row');
    if (rows.length <= 1) {
        showToast('Minimal harus ada 1 baris barang.', 'warning');
        return;
    }
    const tr = document.getElementById(rowId);
    if (tr) tr.remove();
    reindexRows();
    updateTableFooterTotals();
}

function reindexRows() {
    const rows = document.querySelectorAll('.adj-item-row');
    rows.forEach((row, idx) => {
        const numCell = row.querySelector('.row-number');
        if (numCell) numCell.textContent = idx + 1;
    });
    const count = rows.length;
    const badge = document.getElementById('tabItemCountBadge');
    const summary = document.getElementById('summaryTotalItems');
    if (badge) badge.textContent = count;
    if (summary) summary.textContent = `${count} Item`;
}

function updateTableFooterTotals() {
    let sumQty = 0;
    let sumHarga = 0;
    document.querySelectorAll('.adj-item-row').forEach(tr => {
        const q = parseFloat(tr.querySelector('.item-qty')?.value) || 0;
        const h = parseFloat(tr.querySelector('.item-harga')?.value) || 0;
        sumQty += q;
        sumHarga += h;
    });

    const elQty = document.getElementById('footerTotalQty');
    const elHarga = document.getElementById('footerTotalHarga');
    if (elQty) elQty.innerText = formatNumber(sumQty);
    if (elHarga) elHarga.innerText = formatNumber(sumHarga);
}

function openItemDropdown(rowId) {
    const siteId = document.getElementById('idSite').value;
    if (!siteId) {
        showToast('Silakan pilih Lokasi Gudang / Site pada Tab 1 terlebih dahulu.', 'warning');
        goToTab('tab-dokumen');
        document.getElementById('idSite').focus();
        return;
    }

    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    handleItemSearch(rowId);
}

function handleItemSearch(rowId) {
    clearTimeout(itemSearchTimeout);
    const siteId = document.getElementById('idSite').value;
    const jenis = document.getElementById('jenisAdjustment').value;
    const input = document.querySelector(`#${rowId} .item-nama-barang`);
    const query = input.value.trim();
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    dropdown.innerHTML = `<div class="p-3 text-center text-muted small"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Mencari barang...</div>`;
    dropdown.classList.remove('d-none');

    itemSearchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(`<?= BASE_URL ?>/api/adjustment_stok/barang.php?id_site=${siteId}&jenis=${encodeURIComponent(jenis)}&q=${encodeURIComponent(query)}`);
            const result = await res.json();
            if (result && result.success) {
                renderItemDropdown(rowId, result.data || [], query);
            } else {
                renderItemDropdown(rowId, [], query);
            }
        } catch (e) {
            dropdown.innerHTML = `<div class="p-2 text-center text-danger small">Gagal mencari barang.</div>`;
        }
    }, 250);
}

function renderItemDropdown(rowId, items, query = '') {
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    if (items.length === 0) {
        dropdown.innerHTML = `<div class="p-3 text-center text-muted small">Tidak ada barang yang cocok dengan "<strong>${escapeHtml(query)}</strong>".</div>`;
        return;
    }

    let html = '';
    items.forEach(item => {
        html += `
            <div class="adj-item-dropdown-item d-flex align-items-center justify-content-between p-2" 
                 onclick="selectMasterBarang('${rowId}', ${item.id_barang}, '${item.kode_barang.replace(/'/g, "\\'")}', '${item.nama_barang.replace(/'/g, "\\'")}', '${item.satuan || 'PCS'}', ${item.stok_sistem || 0}, ${item.estimasi_harga || 0})">
                <div class="flex-grow-1 overflow-hidden me-2">
                    <div class="fw-bold text-dark small text-truncate">${escapeHtml(item.nama_barang)}</div>
                    <div class="d-flex align-items-center gap-1 small text-muted" style="font-size: 0.75rem;">
                        <span class="badge bg-light text-dark border font-monospace">${escapeHtml(item.kode_barang || '-')}</span>
                        <span>${escapeHtml(item.nama_kategori || '')}</span>
                        ${item.nama_merk ? `&bull; <span>${escapeHtml(item.nama_merk)}</span>` : ''}
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <div class="small font-monospace fw-bold text-primary">Stok: ${formatNumber(item.stok_sistem)} ${escapeHtml(item.satuan || '')}</div>
                    <div class="text-muted" style="font-size: 0.72rem;">${formatNumber(item.estimasi_harga)}</div>
                </div>
            </div>
        `;
    });
    dropdown.innerHTML = html;
}

function selectMasterBarang(rowId, idBarang, kode, nama, satuan, stokSistem, harga) {
    const tr = document.getElementById(rowId);
    if (!tr) return;

    tr.querySelector('.item-id-barang').value = idBarang;
    tr.querySelector('.item-nama-barang').value = nama;
    tr.querySelector('.item-satuan').value = satuan;
    const cellSatuan = tr.querySelector('.cell-satuan');
    if (cellSatuan) cellSatuan.innerText = satuan || '-';
    tr.querySelector('.item-stok-sistem').value = stokSistem;
    tr.querySelector('.cell-stok-sistem').innerText = formatNumber(stokSistem);
    tr.querySelector('.item-harga').value = harga;
    tr.querySelector('.item-harga-display').value = formatNumber(harga);

    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (dropdown) dropdown.classList.add('d-none');

    recalculateRowElement(tr);
}

function recalculateRowElement(tr) {
    if (!tr) return;

    const jenis = document.getElementById('jenisAdjustment').value;
    const stokSistem = parseFloat(tr.querySelector('.item-stok-sistem')?.value) || 0;
    const inputQty = parseFloat(tr.querySelector('.item-qty')?.value) || 0;

    let akhir = 0;
    if (jenis === 'PENAMBAHAN') {
        akhir = stokSistem + Math.abs(inputQty);
    } else { // PENGURANGAN
        akhir = stokSistem - Math.abs(inputQty);
    }

    const tdAkhir = tr.querySelector('.cell-akhir');
    if (tdAkhir) {
        tdAkhir.innerHTML = `<span class="fw-bold text-dark font-monospace">${formatNumber(akhir)}</span>`;
    }
    updateTableFooterTotals();
}

function recalculateAllRows() {
    document.querySelectorAll('.adj-item-row').forEach(tr => {
        recalculateRowElement(tr);
    });
    updateTableFooterTotals();
}

let pendingStatusAction = 'DRAFT';

function handleSaveAction(status) {
    pendingStatusAction = status;
    const form = document.getElementById('formAdjustment');
    if (form.checkValidity()) {
        executeSaveAdjustment();
    } else {
        if (!document.getElementById('idSite').value || !document.getElementById('tanggalAdjustmentDate').value) {
            goToTab('tab-dokumen');
        } else if (!document.getElementById('alasan').value.trim()) {
            goToTab('tab-keterangan');
        } else if (!document.getElementById('idKaryawanApproved').value) {
            goToTab('tab-persetujuan');
        }
        form.reportValidity();
    }
}

function handleSaveAdjustment(e) {
    e.preventDefault();
    executeSaveAdjustment();
}

async function executeSaveAdjustment() {
    const siteId = document.getElementById('idSite').value;
    const jenisAdj = document.getElementById('jenisAdjustment').value;
    const tglDate = document.getElementById('tanggalAdjustmentDate').value;
    const tglTime = document.getElementById('tanggalAdjustmentTime').value;
    const alasan = document.getElementById('alasan').value.trim();
    const keterangan = document.getElementById('keterangan').value.trim();
    const idKaryawanApproved = document.getElementById('idKaryawanApproved')?.value;

    if (!siteId) {
        showToast('Silakan pilih Lokasi Gudang / Site pada Tab 1 terlebih dahulu.', 'warning');
        goToTab('tab-dokumen');
        return;
    }

    const rows = document.querySelectorAll('.adj-item-row');
    const items = [];
    let hasInvalid = false;

    rows.forEach(tr => {
        const idBarang = tr.querySelector('.item-id-barang')?.value;
        const nama = tr.querySelector('.item-nama-barang')?.value.trim();
        const stokSistem = parseFloat(tr.querySelector('.item-stok-sistem')?.value) || 0;
        const inputQty = parseFloat(tr.querySelector('.item-qty')?.value) || 0;
        const harga = parseFloat(tr.querySelector('.item-harga')?.value) || 0;
        const catatan = tr.querySelector('.item-catatan')?.value.trim();

        if (!idBarang || !nama || inputQty <= 0) {
            hasInvalid = true;
            return;
        }

        const adj = jenisAdj === 'PENAMBAHAN' ? Math.abs(inputQty) : -Math.abs(inputQty);
        const akhir = stokSistem + adj;
        const subtotal = Math.abs(adj) * harga;

        items.push({
            id_barang: parseInt(idBarang),
            qty_sistem: stokSistem,
            qty_fisik: akhir,
            qty_adjustment: adj,
            qty_akhir: akhir,
            harga_satuan: harga,
            subtotal_adjustment: subtotal,
            keterangan: catatan
        });
    });

    if (hasInvalid || items.length === 0) {
        showToast('Pastikan semua barang telah dipilih dari katalog dan kuantitas penyesuaian > 0.', 'warning');
        goToTab('tab-barang');
        return;
    }

    if (!alasan) {
        showToast('Silakan isi Alasan Penyesuaian pada Tab 3.', 'warning');
        goToTab('tab-keterangan');
        return;
    }

    const nomorAdjInput = document.getElementById('nomorAdjustment')?.value.trim();

    const payload = {
        nomor_adjustment: nomorAdjInput && nomorAdjInput !== '[Otomatis]' ? nomorAdjInput : null,
        id_site: parseInt(siteId),
        jenis_adjustment: jenisAdj,
        tanggal_adjustment: `${tglDate} ${tglTime}:00`,
        alasan: alasan,
        keterangan: keterangan,
        status: pendingStatusAction,
        id_karyawan_approved: idKaryawanApproved ? parseInt(idKaryawanApproved) : null,
        items: items
    };

    try {
        const res = await fetch('<?= BASE_URL ?>/api/adjustment_stok/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await res.json();
        if (result.success) {
            const finalNomor = result.data?.nomor_adjustment || payload.nomor_adjustment || '-';
            showSuccessTransactionModal({
                title: 'Stock Adjustment Berhasil Disimpan',
                subtitle: 'Berhasil Disimpan Dengan Nomor :',
                nomor: finalNomor,
                redirectUrl: '<?= BASE_URL ?>/admin/pages/adjustment_stok/index.php',
                delay: 2000
            });
        } else {
            showToast(result.message || 'Terjadi kesalahan pada server.', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Gagal menghubungi server.', 'error');
    }
}

function parseFormattedNumber(val) {
    if (!val) return 0;
    let clean = String(val).replace(/[^0-9]/g, '');
    return parseFloat(clean) || 0;
}

function formatNumber(num) {
    return (parseFloat(num) || 0).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
