<?php
/**
 * Halaman Edit Purchase Order (PO)
 * Path: admin/pages/purchase_order/edit.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Khusus Purchasing, Manager, dan Admin (Logistik diblokir)
$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);
$idPo = isset($_GET['id']) ? decodeId($_GET['id']) : 0;

if ($idPo <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/purchase_order/index.php');
    exit;
}

$pageTitle = 'Edit Purchase Order #' . $idPo;
$pageHeading = 'Edit Purchase Order (PO)';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Title -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">
            Edit Purchase Order <span id="headerNomorPoDisplay" class="font-monospace text-primary">...</span>
        </h4>
    </div>

    <!-- Alert Loading Notice / Status Warning -->
    <div id="poAlertNotice" class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span>Memuat data Purchase Order...</span>
    </div>

    <!-- FORMULIR EDIT PURCHASE ORDER -->
    <form id="formEditPo" onsubmit="handleUpdatePo(event)" class="d-none" novalidate>
        <input type="hidden" id="editIdPo" value="<?= $idPo ?>">

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <!-- TAB NAVIGATION HEADER -->
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="poEditTabNav" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-3 px-3" id="tab-info-btn" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Data PO &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-shipping-btn" data-bs-toggle="tab" data-bs-target="#tab-shipping" type="button" role="tab">
                            <i class="bi bi-truck me-1 text-primary"></i> 2. Pengiriman &amp; Pembayaran
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-pricing-btn" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> 3. Rincian Barang &amp; Biaya
                            <span class="badge bg-primary ms-1" id="tabItemCountBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- TAB CONTENTS -->
            <div class="card-body p-4">
                <div class="tab-content" id="poEditTabContent">
                    
                    <!-- TAB 1: DATA PO & VENDOR (HANYA 2 KOLOM) -->
                    <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Nomor PO (Permanen)</label>
                                <input type="text" class="form-control form-control-sm font-monospace bg-light fw-bold text-primary" id="editNomorPo" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Tanggal PO <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="editTanggalPo" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Vendor</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold text-dark" id="editNamaVendor" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Prioritas Pengadaan</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-bold font-monospace text-dark" id="editPrioritas" readonly title="Prioritas ditetapkan dari Request Order dan tidak dapat diubah">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Referensi Request Order (RO)</label>
                                <input type="text" class="form-control form-control-sm bg-light font-monospace" id="editNomorRo" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Pembuat (Purchasing)</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="editNamaPembuat" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Status Dokumen PO</label>
                                <select class="form-select form-select-sm" id="editStatusPo">
                                    <option value="DRAFT">Draft</option>
                                    <option value="REVIEW INTERNAL">Review Internal</option>
                                    <option value="DISETUJUI INTERNAL">Disetujui Internal</option>
                                    <option value="TIDAK DISETUJUI INTERNAL">Tidak Disetujui Internal</option>
                                    <option value="REVIEW VENDOR">Review Vendor</option>
                                    <option value="DIPROSES VENDOR">Diproses Vendor</option>
                                    <option value="BATAL">Batal</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Disetujui Oleh</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="editNamaApprover" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Catatan / Keterangan PO</label>
                                <textarea class="form-control form-control-sm" id="editKeterangan" rows="3" placeholder="Instruksi tambahan untuk vendor..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PENGIRIMAN & PEMBAYARAN -->
                    <div class="tab-pane fade" id="tab-shipping" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Site Tujuan</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold text-dark" id="editNamaSite" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Metode Pengiriman</label>
                                <select class="form-select form-select-sm" id="editPengiriman">
                                    <option value="Vendor">Vendor (Dikirim Vendor)</option>
                                    <option value="Expedisi">Expedisi (Jasa Logistik Luar)</option>
                                    <option value="Internal">Internal (Armada PT Jaya Teknis)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Estimasi Tanggal Tiba</label>
                                <input type="date" class="form-control form-control-sm" id="editTanggalPengiriman">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Term of Payment (T.O.P - Hari)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm font-monospace" id="editTop" min="0" max="180" step="1" oninput="updateTopBadge(this.value)">
                                    <span class="input-group-text bg-light">Hari</span>
                                </div>
                                <div class="mt-1" id="badgeTopKeterangan"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Alamat Lengkap Pengiriman</label>
                                <textarea class="form-control form-control-sm" id="editAlamat" rows="3" placeholder="Alamat gudang / dok tujuan..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: RINCIAN BARANG & BIAYA -->
                    <div class="tab-pane fade" id="tab-pricing" role="tabpanel">
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover align-middle mb-0 bg-white" id="tablePricingItems">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center align-middle">#</th>
                                        <th style="min-width: 280px;" class="align-middle">Barang &amp; Spesifikasi</th>
                                        <th style="width: 95px;" class="text-center align-middle">Qty</th>
                                        <th style="width: 210px; min-width: 200px;" class="text-center align-middle">Harga Satuan</th>
                                        <th style="width: 190px; min-width: 180px;" class="text-center align-middle">Diskon Item</th>
                                        <th style="width: 210px; min-width: 195px;" class="text-end align-middle">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="tablePricingItemsBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Memuat rincian barang...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Financial Calculation Settings & Totals -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark small mb-3">Pengaturan Pajak &amp; Diskon Akhir PO</h6>
                                    
                                    <!-- Checkbox & Pengaturan Pajak (PPnBM dan PPN) -->
                                    <div class="mb-3 p-3 bg-white rounded border">
                                        <!-- Baris 1: Pajak PPnBM (Otomatis & Permanen jika Dokumen PO Barang Mewah) -->
                                        <div id="wrapperPajakPpnbm" class="d-none pb-2 mb-2 border-bottom">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="form-check m-0">
                                                    <input class="form-check-input" type="checkbox" id="checkEnablePpnbm" checked disabled>
                                                    <label class="form-check-label fw-bold small text-dark" id="labelEnablePpnbm" for="checkEnablePpnbm">
                                                        <i class="bi bi-stars text-warning me-1"></i>Pajak PPnBM (<span id="textPpnbmRate">0</span>%)
                                                    </label>
                                                </div>
                                                <span class="badge bg-warning text-dark border border-warning-subtle fw-bold font-monospace" style="font-size: 0.75rem;" id="badgePpnbmRate">
                                                    0% (PPnBM)
                                                </span>
                                            </div>
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" id="checkTermasukPpnbm" onchange="calculateAllTotals()">
                                                <label class="form-check-label small text-muted" for="checkTermasukPpnbm" style="font-size: 0.8rem;">
                                                    Harga Barang Termasuk PPnBM (Tax Inclusive)
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Baris 2: Pajak PPN (12% jika Barang PPnBM, 11% jika Barang Biasa) -->
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                            <div class="form-check m-0">
                                                <input class="form-check-input" type="checkbox" id="checkEnablePajak" checked onchange="togglePajak(this.checked)">
                                                <label class="form-check-label fw-bold small text-dark" id="labelEnablePajak" for="checkEnablePajak">
                                                    Kena Pajak PPN (<span id="textPpnRateLabel">11</span>%)
                                                </label>
                                            </div>
                                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.75rem;" id="badgePpnRate">
                                                Tarif <span id="textPpnBadgeRate">11</span>%
                                            </span>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkTermasukPajak" onchange="calculateAllTotals()">
                                            <label class="form-check-label small text-dark" for="checkTermasukPajak">
                                                Harga Barang Sudah Termasuk PPN (Tax Inclusive)
                                            </label>
                                        </div>

                                        <!-- Hidden Inputs untuk Nilai Pajak -->
                                        <input type="hidden" id="inputPajakPpn" value="11">
                                        <input type="hidden" id="inputTotalTermasukPajak" value="0">
                                        <input type="hidden" id="inputPajakPpnbm" value="0">
                                        <input type="hidden" id="inputTotalTermasukPpnbm" value="0">
                                    </div>

                                    <!-- Diskon Akhir: Nominal (Rp) atau Persentase (%) -->
                                    <div class="p-3 bg-white rounded border">
                                        <div class="row g-2 align-items-center">
                                            <div class="col-sm-5">
                                                <label class="form-label small fw-bold mb-1">Tipe Diskon Akhir:</label>
                                                <select class="form-select form-select-sm" id="selectDiskonType" onchange="onDiskonTypeChange()">
                                                    <option value="nominal">Nominal (Rp)</option>
                                                    <option value="percent">Persentase (%)</option>
                                                </select>
                                            </div>
                                            <div class="col-sm-7">
                                                <label class="form-label small fw-bold mb-1" id="labelNilaiDiskon">Nilai Diskon (Rp):</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light font-monospace" id="addonDiskonPrefix">Rp</span>
                                                    <input type="text" class="form-control font-monospace text-end" id="inputDiskonNilai" value="0" oninput="handleDiskonNilaiInput(this)">
                                                    <span class="input-group-text bg-light font-monospace d-none" id="addonDiskonSuffix">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white shadow-xs">
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Subtotal Barang:</span>
                                        <span class="fw-semibold text-dark font-monospace" id="displaySubtotalBarang">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Diskon Akhir:</span>
                                        <span class="fw-semibold text-danger font-monospace" id="displayDiskonPo">- Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">DPP (Dasar Pengenaan Pajak):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="displayDpp">Rp 0</span>
                                    </div>
                                    <!-- Baris Pajak PPnBM (Muncul jika ada PPnBM) -->
                                    <div class="d-flex justify-content-between mb-2 small d-none" id="displayRowPpnbm">
                                        <span class="text-muted" id="displayLabelPpnbm">PPnBM (0%):</span>
                                        <span class="fw-semibold text-warning-emphasis font-monospace" id="displayNominalPpnbm">Rp 0</span>
                                    </div>
                                    <!-- Baris Pajak PPN (11% atau 12%) -->
                                    <div class="d-flex justify-content-between mb-2 small" id="displayRowPpn">
                                        <span class="text-muted" id="displayLabelPpn">PPN (11%):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="displayNominalPajak">Rp 0</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">GRAND TOTAL:</span>
                                        <span class="fs-5 fw-bold text-primary font-monospace" id="displayGrandTotal">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER ACTION BUTTONS -->
            <div class="card-footer bg-light p-3 border-top d-flex justify-content-end align-items-center gap-2">
                <a href="<?= BASE_URL ?>/admin/pages/purchase_order/index.php" class="btn btn-secondary btn-sm px-3">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSubmitUpdatePo">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Purchase Order
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<script>
const ID_PO = <?= $idPo ?>;
const CURRENT_USER_ROLE = '<?= strtoupper($user['role'] ?? '') ?>';
let poDataCache = null;
let calculatedDiskonNominal = 0;

document.addEventListener('DOMContentLoaded', async () => {
    await loadExistingPoData();
});

// -------------------------------------------------------------
// PENGATURAN PAJAK (PPN & PPnBM) & TIPE DISKON
// -------------------------------------------------------------
function togglePajak(enable) {
    const ppnbmRate = parseFloat(document.getElementById('inputPajakPpnbm')?.value) || 0;
    const activePpnRate = (ppnbmRate > 0) ? 12 : 11;
    document.getElementById('inputPajakPpn').value = enable ? activePpnRate : 0;
    calculateAllTotals();
}

function onDiskonTypeChange() {
    const type = document.getElementById('selectDiskonType').value;
    const label = document.getElementById('labelNilaiDiskon');
    const prefix = document.getElementById('addonDiskonPrefix');
    const suffix = document.getElementById('addonDiskonSuffix');
    const input = document.getElementById('inputDiskonNilai');

    if (type === 'percent') {
        label.textContent = 'Persentase Diskon (%):';
        prefix.classList.add('d-none');
        suffix.classList.remove('d-none');
        let val = parseFloat(input.value.replace(/[^0-9.]/g, '')) || 0;
        if (val > 100) val = 100;
        input.value = val;
    } else {
        label.textContent = 'Nilai Diskon (Rp):';
        prefix.classList.remove('d-none');
        suffix.classList.add('d-none');
        input.value = formatThousand(input.value);
    }
    calculateAllTotals();
}

function handleDiskonNilaiInput(el) {
    const type = document.getElementById('selectDiskonType').value;
    if (type === 'percent') {
        let val = parseFloat(el.value.replace(/[^0-9.]/g, '')) || 0;
        if (val > 100) val = 100;
        if (val < 0) val = 0;
        el.value = val;
        calculateAllTotals();
    } else {
        handleThousandInput(el, () => calculateAllTotals());
    }
}

// -------------------------------------------------------------
// HELPER THOUSAND SEPARATOR & NUMERIC PARSING
// -------------------------------------------------------------
function parseThousandNumber(val) {
    if (typeof val === 'number') return Math.max(0, val);
    if (!val) return 0;
    const clean = String(val).replace(/\./g, '').replace(/,/g, '.').replace(/[^0-9.]/g, '');
    const num = parseFloat(clean);
    return isNaN(num) || num < 0 ? 0 : num;
}

function formatThousand(val) {
    const num = Math.round(parseThousandNumber(val));
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function handleThousandInput(el, onComplete) {
    let cursorPos = el.selectionStart;
    let originalLen = el.value.length;

    let cleanVal = el.value.replace(/[^0-9]/g, '');
    let num = parseInt(cleanVal, 10);
    if (isNaN(num) || num < 0) num = 0;

    el.value = num > 0 ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '0';

    let newLen = el.value.length;
    cursorPos = cursorPos + (newLen - originalLen);
    if (cursorPos < 0) cursorPos = 0;
    try { el.setSelectionRange(cursorPos, cursorPos); } catch(e){}

    if (typeof onComplete === 'function') {
        onComplete();
    }
}

function handlePriceInput(el) {
    handleThousandInput(el, () => calculateRowSubtotal(el));
}

function handleDiscountInput(el) {
    handleThousandInput(el, () => calculateRowSubtotal(el));
}

// -------------------------------------------------------------
// LOAD EXISTING PO DATA
// -------------------------------------------------------------
async function loadExistingPoData() {
    const res = await apiRequest(`/api/purchase_order/index.php?id=${ID_PO}`);
    if (!res || !res.success || !res.data) {
        document.getElementById('poAlertNotice').className = 'alert alert-danger border-0';
        document.getElementById('poAlertNotice').innerHTML = `
            <i class="bi bi-x-circle-fill me-2"></i> ${res ? res.message : 'Purchase Order tidak ditemukan.'}
        `;
        return;
    }

    poDataCache = res.data;
    const po = poDataCache;

    // Cek Status Penguncian Sesuai Role
    const currentStatusUpper = (po.status || '').toUpperCase();
    let isLocked = false;
    let lockReason = '';

    if (['DIPROSES VENDOR', 'DITERIMA', 'BATAL'].includes(currentStatusUpper)) {
        isLocked = true;
        lockReason = `Dokumen <strong>${escapeHtml(po.nomor_po || '')}</strong> berstatus <strong>${escapeHtml(po.status || '')}</strong> dan data formulir sudah tidak dapat diubah lagi.`;
    } else if (CURRENT_USER_ROLE === 'LOGISTIK') {
        isLocked = true;
        lockReason = `Bagian <strong>Logistik</strong> tidak memiliki wewenang untuk mengedit dokumen Purchase Order.`;
    }

    if (isLocked) {
        document.getElementById('poAlertNotice').className = 'alert alert-warning border-0';
        document.getElementById('poAlertNotice').innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-lock-fill fs-3 text-warning"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Dokumen Purchase Order Terkunci</h6>
                    <div class="small text-secondary">${lockReason}</div>
                </div>
            </div>
        `;
        return;
    }

    // Sembunyikan Alert Loading & Tampilkan Form
    document.getElementById('poAlertNotice').classList.add('d-none');
    document.getElementById('formEditPo').classList.remove('d-none');

    // Header Display
    document.getElementById('headerNomorPoDisplay').textContent = po.nomor_po || '-';
    document.getElementById('editNomorPo').value = po.nomor_po || '';
    document.getElementById('editTanggalPo').value = po.tanggal_po ? po.tanggal_po.split(' ')[0] : '';
    document.getElementById('editPrioritas').value = (po.prioritas || 'NORMAL').toUpperCase();
    document.getElementById('editNamaVendor').value = po.nama_vendor || '-';
    document.getElementById('editNomorRo').value = po.nomor_ro ? `RO: ${po.nomor_ro}` : 'Tanpa RO';
    document.getElementById('editStatusPo').value = po.status || 'DRAFT';
    document.getElementById('editNamaPembuat').value = po.nama_pembuat || 'Staff Purchasing';
    document.getElementById('editNamaApprover').value = po.nama_approver || 'Menunggu Persetujuan';
    document.getElementById('editKeterangan').value = po.keterangan || '';

    // Tab 2 Shipping
    document.getElementById('editNamaSite').value = po.nama_site ? `${po.nama_site} (${po.kode_site || '-'})` : '-';
    document.getElementById('editPengiriman').value = po.pengiriman || 'Vendor';
    document.getElementById('editTanggalPengiriman').value = po.tanggal_pengiriman ? po.tanggal_pengiriman.split(' ')[0] : '';
    document.getElementById('editTop').value = po.term_of_payment !== null ? po.term_of_payment : 30;
    updateTopBadge(document.getElementById('editTop').value);
    document.getElementById('editAlamat').value = po.alamat || (po.alamat_site || '');

    // Deteksi Barang Mewah (PPnBM)
    const items = po.items || [];
    let hasPpnbmInPo = false;
    let poPpnbmRate = parseFloat(po.pajak_PPnBM || po.rate_ppnbm || 0);

    items.forEach(item => {
        const isLuxury = (parseInt(item.PPnBM) === 1 || parseInt(item.ppnbm) === 1 || parseInt(item.kena_pajak_ppnbm) === 1);
        const itemRatePpnbm = parseFloat(item.rate_PPnBM || item.rate_ppnbm || 0);
        if (isLuxury) {
            hasPpnbmInPo = true;
            if (itemRatePpnbm > 0 && poPpnbmRate === 0) {
                poPpnbmRate = itemRatePpnbm;
            }
        }
    });

    if (poPpnbmRate > 0) hasPpnbmInPo = true;

    const wrapperPajakPpnbm = document.getElementById('wrapperPajakPpnbm');
    const textPpnbmRate = document.getElementById('textPpnbmRate');
    const badgePpnbmRate = document.getElementById('badgePpnbmRate');
    const inputPajakPpnbm = document.getElementById('inputPajakPpnbm');
    const checkTermasukPpnbm = document.getElementById('checkTermasukPpnbm');

    if (hasPpnbmInPo && poPpnbmRate > 0) {
        if (wrapperPajakPpnbm) wrapperPajakPpnbm.classList.remove('d-none');
        if (textPpnbmRate) textPpnbmRate.textContent = poPpnbmRate;
        if (badgePpnbmRate) badgePpnbmRate.textContent = `${poPpnbmRate}% (PPnBM)`;
        if (inputPajakPpnbm) inputPajakPpnbm.value = poPpnbmRate;
        if (checkTermasukPpnbm) checkTermasukPpnbm.checked = (parseInt(po.total_termasuk_PPnBM) === 1);
    } else {
        if (wrapperPajakPpnbm) wrapperPajakPpnbm.classList.add('d-none');
        if (inputPajakPpnbm) inputPajakPpnbm.value = 0;
        if (checkTermasukPpnbm) checkTermasukPpnbm.checked = false;
    }

    // Konfigurasi Tarif PPN (12% jika Barang PPnBM, 11% jika Barang Non-PPnBM)
    const activePpnRate = (poPpnbmRate > 0) ? 12 : 11;
    document.getElementById('textPpnRateLabel').textContent = activePpnRate;
    document.getElementById('textPpnBadgeRate').textContent = activePpnRate;

    const isPpnEnabled = (parseInt(po.pajak) > 0 || (po.pajak === null && activePpnRate > 0));
    document.getElementById('checkEnablePajak').checked = isPpnEnabled;
    document.getElementById('inputPajakPpn').value = isPpnEnabled ? activePpnRate : 0;
    document.getElementById('checkTermasukPajak').checked = (parseInt(po.total_termasuk_pajak) === 1);

    // Diskon Akhir
    document.getElementById('selectDiskonType').value = 'nominal';
    document.getElementById('inputDiskonNilai').value = formatThousand(po.diskon || 0);
    calculatedDiskonNominal = parseFloat(po.diskon || 0);

    // Render Items
    document.getElementById('tabItemCountBadge').textContent = items.length;

    let itemsHtml = '';
    items.forEach((item, idx) => {
        const itemQty = parseFloat(item.qty) || 0;
        const itemHarga = parseFloat(item.harga) || 0;
        const itemDiskon = parseFloat(item.diskon) || 0;
        const itemSubtotal = maxZero((itemQty * itemHarga) - itemDiskon);

        const isLuxury = (parseInt(item.PPnBM) === 1 || parseInt(item.ppnbm) === 1 || parseInt(item.kena_pajak_ppnbm) === 1);
        const itemRatePpnbm = parseFloat(item.rate_PPnBM || item.rate_ppnbm || poPpnbmRate) || 0;
        const ppnbmBadge = isLuxury
            ? `<span class="badge bg-warning text-dark border border-warning-subtle fw-bold" style="font-size: 0.68rem;">PPnBM (${itemRatePpnbm}%)</span>`
            : '';

        const imgSrc = item.foto1 ? `${BASE_URL}/${escapeHtml(item.foto1)}` : '';
        const imgHtml = imgSrc 
            ? `<a href="${imgSrc}" target="_blank" class="d-inline-block flex-shrink-0" title="Klik untuk perbesar foto">
                   <img src="${imgSrc}" alt="${escapeHtml(item.nama_barang || '')}" class="rounded border bg-white object-fit-cover" style="width: 48px; height: 48px;" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\\'rounded border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0\\' style=\\'width: 48px; height: 48px;\\'><i class=\\'bi bi-box-seam fs-5\\'></i></div>';">
               </a>`
            : `<div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 48px; height: 48px;">
                   <i class="bi bi-box-seam fs-5"></i>
               </div>`;

        itemsHtml += `
            <tr data-detail-id="${item.id_po_detail}">
                <td class="text-center font-monospace small text-muted align-middle">${idx + 1}</td>
                <td class="align-middle">
                    <div class="d-flex align-items-center gap-3">
                        ${imgHtml}
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-bold text-dark mb-1 text-truncate" title="${escapeHtml(item.nama_barang || '')}">${escapeHtml(item.nama_barang || '')}</div>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                                ${item.nama_kategori ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">${escapeHtml(item.nama_kategori)}</span>` : ''}
                                ${item.nama_merk ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">${escapeHtml(item.nama_merk)}</span>` : ''}
                                ${ppnbmBadge}
                            </div>
                            <input type="hidden" class="item-detail-id" value="${item.id_po_detail}">
                        </div>
                    </div>
                </td>
                <td class="text-center align-middle">
                    <span class="fw-bold font-monospace fs-6">${itemQty}</span>
                    <span class="small text-muted d-block" style="font-size: 0.72rem;">${escapeHtml(item.satuan || 'PCS')}</span>
                    <input type="hidden" class="item-qty" value="${itemQty}">
                </td>
                <td class="align-middle">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light font-monospace text-muted px-2" style="font-size: 0.75rem;">Rp</span>
                        <input type="text" class="form-control form-control-sm font-monospace text-end fw-semibold item-harga" value="${formatThousand(itemHarga)}" oninput="handlePriceInput(this)" placeholder="0" required>
                    </div>
                </td>
                <td class="align-middle">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light font-monospace text-muted px-2" style="font-size: 0.75rem;">Rp</span>
                        <input type="text" class="form-control form-control-sm font-monospace text-end item-diskon text-danger" value="${formatThousand(itemDiskon)}" oninput="handleDiscountInput(this)" placeholder="0">
                    </div>
                </td>
                <td class="text-end font-monospace fw-bold text-dark item-subtotal-display align-middle">
                    ${formatRupiah(itemSubtotal)}
                </td>
            </tr>
        `;
    });

    document.getElementById('tablePricingItemsBody').innerHTML = itemsHtml;
    calculateAllTotals();
}

// -------------------------------------------------------------
// KALKULASI TOTAL BIAYA PO (PPN 11%/12%, PPnBM, INKLUSIF/EKSKLUSIF)
// -------------------------------------------------------------
function calculateRowSubtotal(inputEl) {
    const row = inputEl.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const harga = parseThousandNumber(row.querySelector('.item-harga').value);
    const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);

    const subtotal = maxZero((qty * harga) - diskon);
    row.querySelector('.item-subtotal-display').textContent = formatRupiah(subtotal);
    calculateAllTotals();
}

function calculateAllTotals() {
    let subtotalBarang = 0;
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga')?.value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon')?.value);
        subtotalBarang += maxZero((qty * harga) - diskon);
    });

    // Kalkulasi Diskon Akhir (Nominal vs Persentase)
    const diskonType = document.getElementById('selectDiskonType').value;
    const diskonRaw = document.getElementById('inputDiskonNilai').value;
    const diskonInput = (diskonType === 'percent') ? (parseFloat(diskonRaw) || 0) : parseThousandNumber(diskonRaw);
    let diskonAkhirNominal = 0;
    if (diskonType === 'percent') {
        diskonAkhirNominal = (subtotalBarang * diskonInput) / 100;
    } else {
        diskonAkhirNominal = diskonInput;
    }
    if (diskonAkhirNominal > subtotalBarang) diskonAkhirNominal = subtotalBarang;
    if (diskonAkhirNominal < 0) diskonAkhirNominal = 0;
    calculatedDiskonNominal = diskonAkhirNominal;

    const dasarSetelahDiskon = maxZero(subtotalBarang - diskonAkhirNominal);

    // Kalkulasi Pajak PPnBM (Barang Mewah)
    const ppnbmRate = parseFloat(document.getElementById('inputPajakPpnbm')?.value) || 0;
    const isTermasukPpnbm = (ppnbmRate > 0) && (document.getElementById('checkTermasukPpnbm')?.checked || false);
    if (document.getElementById('inputTotalTermasukPpnbm')) {
        document.getElementById('inputTotalTermasukPpnbm').value = isTermasukPpnbm ? 1 : 0;
    }

    // Kalkulasi Pajak PPN (12% jika Barang PPnBM, 11% jika Barang Non-PPnBM)
    const standardPpnRate = (ppnbmRate > 0) ? 12 : 11;
    const isPajakEnabled = document.getElementById('checkEnablePajak').checked;
    const ppnRate = isPajakEnabled ? standardPpnRate : 0;
    document.getElementById('inputPajakPpn').value = ppnRate;

    const isTermasukPajak = isPajakEnabled && (document.getElementById('checkTermasukPajak')?.checked || false);
    document.getElementById('inputTotalTermasukPajak').value = isTermasukPajak ? 1 : 0;

    let divisor = 1.0;
    if (isTermasukPpnbm && ppnbmRate > 0) {
        divisor += (ppnbmRate / 100);
    }
    if (isTermasukPajak && ppnRate > 0) {
        divisor += (ppnRate / 100);
    }

    const dpp = dasarSetelahDiskon / divisor;
    const ppnbmAmount = (ppnbmRate > 0) ? (dpp * ppnbmRate / 100) : 0;
    const ppnAmount = (ppnRate > 0) ? (dpp * ppnRate / 100) : 0;

    let grandTotal = 0;
    if (divisor > 1.0) {
        grandTotal = dpp;
        if (ppnbmRate > 0) grandTotal += ppnbmAmount;
        if (ppnRate > 0) grandTotal += ppnAmount;
    } else {
        grandTotal = dpp + ppnbmAmount + ppnAmount;
    }

    document.getElementById('displaySubtotalBarang').textContent = formatRupiah(subtotalBarang);
    document.getElementById('displayDiskonPo').textContent = `- ${formatRupiah(diskonAkhirNominal)}`;
    document.getElementById('displayDpp').textContent = formatRupiah(dpp);

    // Baris PPnBM
    const rowPpnbmEl = document.getElementById('displayRowPpnbm');
    const labelPpnbmEl = document.getElementById('displayLabelPpnbm');
    const nominalPpnbmEl = document.getElementById('displayNominalPpnbm');
    if (ppnbmRate > 0) {
        if (rowPpnbmEl) rowPpnbmEl.classList.remove('d-none');
        if (labelPpnbmEl) labelPpnbmEl.textContent = `PPnBM (${ppnbmRate}%)${isTermasukPpnbm ? ' (Inklusif)' : ''}:`;
        if (nominalPpnbmEl) nominalPpnbmEl.textContent = formatRupiah(ppnbmAmount);
    } else {
        if (rowPpnbmEl) rowPpnbmEl.classList.add('d-none');
    }

    // Baris PPN
    document.getElementById('displayLabelPpn').textContent = `PPN (${standardPpnRate}%)${isTermasukPajak ? ' (Inklusif)' : ''}:`;
    document.getElementById('displayNominalPajak').textContent = isPajakEnabled ? formatRupiah(ppnAmount) : 'Rp 0';
    document.getElementById('displayGrandTotal').textContent = formatRupiah(grandTotal);
}

function updateTopBadge(val) {
    const num = parseInt(val) || 0;
    const badge = document.getElementById('badgeTopKeterangan');
    if (num === 0) {
        badge.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle">C.O.D (Cash On Delivery)</span>';
    } else {
        badge.innerHTML = `<span class="badge bg-light text-dark border">Tempo ${num} Hari</span>`;
    }
}

// -------------------------------------------------------------
// SUBMIT UPDATE PURCHASE ORDER
// -------------------------------------------------------------
async function handleUpdatePo(event) {
    event.preventDefault();

    const tanggalPo = document.getElementById('editTanggalPo').value;
    if (!tanggalPo) {
        showToast('Tanggal PO wajib diisi.', 'warning');
        goToTab('tab-info-btn');
        document.getElementById('editTanggalPo').focus();
        return;
    }

    const items = [];
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const idDetail = parseInt(row.querySelector('.item-detail-id')?.value) || 0;
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga')?.value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon')?.value);
        if (idDetail > 0) {
            items.push({
                id_po_detail: idDetail,
                qty: qty,
                harga: harga,
                diskon: diskon
            });
        }
    });

    const payload = {
        id_po: ID_PO,
        tanggal_po: tanggalPo,
        prioritas: document.getElementById('editPrioritas').value,
        status: document.getElementById('editStatusPo').value,
        keterangan: document.getElementById('editKeterangan').value,
        pengiriman: document.getElementById('editPengiriman').value,
        tanggal_pengiriman: document.getElementById('editTanggalPengiriman').value,
        term_of_payment: parseInt(document.getElementById('editTop').value) || 0,
        alamat: document.getElementById('editAlamat').value,
        diskon: calculatedDiskonNominal,
        pajak: parseInt(document.getElementById('inputPajakPpn').value) || 0,
        total_termasuk_pajak: document.getElementById('checkTermasukPajak').checked ? 1 : 0,
        pajak_PPnBM: parseInt(document.getElementById('inputPajakPpnbm').value) || 0,
        total_termasuk_PPnBM: document.getElementById('checkTermasukPpnbm')?.checked ? 1 : 0,
        items: items
    };

    const btn = document.getElementById('btnSubmitUpdatePo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan Perubahan...';

    const res = await apiRequest('/api/purchase_order/update.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Purchase Order';

    if (res && res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/purchase_order/index.php`;
        }, 1200);
    } else {
        showToast(res ? res.message : 'Gagal memperbarui Purchase Order.', 'danger');
    }
}

function goToTab(tabBtnId) {
    const tabEl = document.getElementById(tabBtnId);
    if (tabEl) {
        const tabTrigger = new bootstrap.Tab(tabEl);
        tabTrigger.show();
    }
}

function maxZero(num) {
    return num < 0 ? 0 : num;
}

function formatRupiah(amount) {
    return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
