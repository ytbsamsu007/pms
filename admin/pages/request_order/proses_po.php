<?php
/**
 * Halaman Verifikasi & Proses Request Order (RO) ke Purchase Order (PO)
 * Path: admin/pages/request_order/proses_po.php
 * Akses: PURCHASING, MANAGER, ADMIN
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Proses ke Purchase Order';
$pageHeading = 'Verifikasi & Proses Purchase Order';

$idRequest = isset($_GET['id']) ? decodeId($_GET['id']) : 0;
if ($idRequest <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/request_order/index.php');
    exit;
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
.ro-vendor-search-wrapper {
    position: relative;
}
.ro-vendor-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #b6d4fe;
    border-radius: 0.375rem;
    box-shadow: 0 12px 32px rgba(0,0,0,0.18);
    z-index: 1060 !important;
    max-height: 240px;
    overflow-y: auto;
}
.ro-vendor-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s ease-in-out;
}
.ro-vendor-dropdown-item:hover {
    background-color: #f0f7ff;
}
</style>

<div class="container-fluid px-0 pb-5">
    
    <!-- HEADER HALAMAN BERSIH -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">Proses ke Purchase Order (PO)</h4>
    </div>

    <!-- SKELETON LOADING -->
    <div id="loadingWrapper" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <h6 class="text-muted fw-semibold">Memuat formulir Purchase Order...</h6>
    </div>

    <!-- MAIN FORM WRAPPER (1 KOLOM PENUH DENGAN 3 TAB FUNGSIONAL) -->
    <div id="mainContentWrapper" class="d-none">
        
        <!-- BANNER DOKUMEN DIKUNCI / SUDAH TERBIT PO -->
        <div id="roLockedBannerContainer" class="d-none"></div>

        <form id="formProsesPo" onsubmit="handleApproveToPo(event)">
            
            <div class="card border-0 shadow-sm rounded-3">
                
                <!-- NAV TABS HEADER (3 TAB FUNGSIONAL) -->
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="poProcessTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold small" id="tab-po" data-bs-toggle="tab" data-bs-target="#pane-po" type="button" role="tab">
                                <i class="bi bi-building text-primary me-1"></i> 1. Data Purchase Order (PO)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="tab-pengiriman" data-bs-toggle="tab" data-bs-target="#pane-pengiriman" type="button" role="tab">
                                <i class="bi bi-truck text-primary me-1"></i> 2. Pengiriman
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="tab-biaya" data-bs-toggle="tab" data-bs-target="#pane-biaya" type="button" role="tab">
                                <i class="bi bi-boxes text-primary me-1"></i> 3. Rincian Barang &amp; Biaya
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content">
                        
                        <!-- =========================================================
                             TAB 1: DATA PURCHASE ORDER (PO) - 2 KOLOM RAPI
                             ========================================================= -->
                        <div class="tab-pane fade show active" id="pane-po" role="tabpanel">
                            
                            <div class="row g-4">
                                
                                <!-- BARIS 1: NOMOR PO (KIRI) - VENDOR READONLY (KANAN) -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor Purchase Order <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light font-monospace"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" class="form-control font-monospace fw-bold bg-light" id="inputNomorPo" name="nomor_po" readonly required placeholder="Memuat nomor PO...">
                                        <button type="button" class="btn btn-outline-secondary" onclick="fetchNextPoNumber()" title="Generate Ulang Nomor">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Vendor <span class="text-danger">*</span></label>
                                    <div class="ro-vendor-search-wrapper position-relative" id="roVendorSearchWrapper">
                                        <input type="hidden" id="inputVendorId" name="id_vendor" required>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-building"></i></span>
                                            <input type="text" class="form-control" id="displayVendorName" placeholder="Cari / pilih vendor rekanan..." autocomplete="off" onfocus="openRoVendorDropdown()" onclick="openRoVendorDropdown()" oninput="debounceRoVendorSearch()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearRoVendorSelection()" title="Hapus / Ganti Vendor">
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

                                <!-- BARIS 2: TANGGAL PO (KIRI) - T.O.P (KANAN) -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Purchase Order <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                                        <input type="date" class="form-control" id="inputTanggalPo" name="tanggal_po" value="<?= date('Y-m-d') ?>" onchange="fetchNextPoNumber()" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-bold mb-0">T.O.P (Term of Payment)</label>
                                        <span class="badge bg-success font-monospace d-none" id="badgeTopKeterangan" style="font-size: 0.72rem;">C.O.D</span>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="form-control font-monospace" id="inputTop" name="term_of_payment" value="30" min="0" max="180" oninput="updateTopKeterangan(this.value)">
                                        <span class="input-group-text bg-light">Hari</span>
                                    </div>
                                    <div class="form-text small text-muted" id="hintTopText">
                                        * Jika 0 hari berarti C.O.D (Cash On Delivery).
                                    </div>
                                </div>

                                <!-- BARIS 3: NOMOR RO (KIRI) - PRIORITAS (KANAN) -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor RO</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-file-earmark-text"></i></span>
                                        <input type="text" class="form-control bg-light font-monospace fw-bold text-dark" id="displayRoNomor" readonly placeholder="Memuat Nomor RO...">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Prioritas</label>
                                    <input type="hidden" id="inputPrioritas" name="prioritas" value="NORMAL">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-flag"></i></span>
                                        <input type="text" class="form-control bg-light fw-bold text-dark font-monospace" id="displayRoPrioritas" readonly placeholder="NORMAL">
                                    </div>
                                </div>

                                <!-- BARIS 4: CATATAN PO (FULL WIDTH) -->
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Catatan / Instruksi Purchase Order</label>
                                    <textarea class="form-control" id="inputKeteranganPo" name="keterangan" rows="3" placeholder="Contoh: Lampirkan faktur pajak asli &amp; surat jalan saat serah terima barang..."></textarea>
                                </div>

                            </div>

                        </div>

                        <!-- =========================================================
                             TAB 2: PENGIRIMAN (METODE, ESTIMASI TANGGAL, ALAMAT SITE)
                             ========================================================= -->
                        <div class="tab-pane fade" id="pane-pengiriman" role="tabpanel">
                            
                            <div class="row g-4">
                                
                                <!-- Metode Pengiriman -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Metode Pengiriman</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span>
                                        <select class="form-select" id="selectPengiriman" name="pengiriman">
                                            <option value="Vendor">Vendor (Diantar oleh Vendor)</option>
                                            <option value="Expedisi">Expedisi (Jasa Kurir / Ekspedisi Logistik)</option>
                                            <option value="Internal">Internal (Armada Perusahaan Sendiri)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Estimasi Tanggal Pengiriman -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Estimasi Tanggal Pengiriman</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-calendar-check"></i></span>
                                        <input type="date" class="form-control" id="inputTanggalPengiriman" name="tanggal_pengiriman">
                                    </div>
                                </div>

                                <!-- Alamat Pengiriman (Default dari Site Alamat) -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-bold mb-0">Alamat Pengiriman</label>
                                        <span class="small text-muted font-monospace" id="labelSiteName">Site: -</span>
                                    </div>
                                    <textarea class="form-control" id="inputAlamatPengiriman" name="alamat" rows="3" placeholder="Alamat lengkap penerimaan barang di lokasi site..."></textarea>
                                </div>

                            </div>

                        </div>

                        <!-- =========================================================
                             TAB 3: RINCIAN BARANG & BIAYA
                             ========================================================= -->
                        <div class="tab-pane fade" id="pane-biaya" role="tabpanel">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Rincian Barang &amp; Penetapan Harga Satuan</h6>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary font-monospace" id="roTotalItemsBadge">0 Item</span>
                            </div>

                            <!-- Notice jika RO memuat Barang Mewah (PPnBM) -->
                            <div id="roPpnbmNoticeContainer" class="d-none"></div>

                            <!-- Tabel Input Harga Barang -->
                            <div class="table-responsive bg-white border rounded-3 overflow-hidden mb-4 shadow-none">
                                <table class="table table-hover align-middle mb-0" id="tablePricingItems">
                                    <thead class="table-light small text-muted text-uppercase">
                                        <tr>
                                            <th class="text-center" style="width: 45px;">#</th>
                                            <th style="min-width: 250px;">Barang &amp; Spesifikasi</th>
                                            <th class="text-center" style="width: 90px;">Qty</th>
                                            <th style="width: 170px;">Harga Satuan (Rp)</th>
                                            <th style="width: 160px;">Diskon Item (Rp)</th>
                                            <th class="text-end" style="width: 160px;">Subtotal (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablePricingItemsBody">
                                        <!-- Rendered dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pengaturan Pajak, Diskon Akhir & Ringkasan Total -->
                            <div class="row g-4 justify-content-between align-items-start">
                                
                                <!-- Pengaturan Pajak & Diskon Akhir -->
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-light">
                                        <h6 class="fw-bold text-dark small mb-3">Pengaturan Pajak &amp; Diskon Akhir PO:</h6>
                                        
                                        <!-- Checkbox & Pengaturan Pajak (PPnBM dan PPN 11%) -->
                                        <div class="mb-3 p-3 bg-white rounded border">
                                            <!-- Baris 1: Pajak PPnBM (Otomatis & Permanen jika Dokumen RO Barang Mewah) -->
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
                                            <input type="hidden" id="inputPajakPpn" name="pajak" value="11">
                                            <input type="hidden" id="inputTotalTermasukPajak" name="total_termasuk_pajak" value="0">
                                            <input type="hidden" id="inputPajakPpnbm" name="pajak_PPnBM" value="0">
                                            <input type="hidden" id="inputTotalTermasukPpnbm" name="total_termasuk_PPnBM" value="0">
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

                                <!-- Ringkasan Total Biaya -->
                                <div class="col-md-5">
                                    <div class="p-3 border rounded-3 bg-white shadow-xs">
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Subtotal Barang:</span>
                                            <span class="fw-semibold text-dark font-monospace" id="summarySubtotal">Rp 0</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Diskon Akhir:</span>
                                            <span class="fw-semibold text-danger font-monospace" id="summaryDiskon">- Rp 0</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">DPP (Dasar Pengenaan Pajak):</span>
                                            <span class="fw-semibold text-dark font-monospace" id="summaryDpp">Rp 0</span>
                                        </div>
                                        <!-- Baris Pajak PPnBM (Khusus Dokumen RO Mewah) -->
                                        <div class="d-flex justify-content-between mb-2 small d-none" id="summaryRowPpnbm">
                                            <span class="text-muted" id="labelSummaryPpnbm">PPnBM (0%):</span>
                                            <span class="fw-semibold text-warning-emphasis font-monospace" id="summaryPpnbm">Rp 0</span>
                                        </div>
                                        <!-- Baris Pajak PPN 11% -->
                                        <div class="d-flex justify-content-between mb-2 small" id="summaryRowPpn">
                                            <span class="text-muted" id="labelSummaryPpn">PPN (11%):</span>
                                            <span class="fw-semibold text-dark font-monospace" id="summaryPpn">Rp 0</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark">GRAND TOTAL:</span>
                                            <span class="fs-5 fw-bold text-primary font-monospace" id="summaryGrandTotal">Rp 0</span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

                <!-- FOOTER BAR AKSI KEPUTUSAN PURCHASING -->
                <div class="card-footer bg-light p-3 border-top d-flex justify-content-end align-items-center flex-wrap gap-2" id="poFooterActions">
                    <!-- Tombol Tolak -->
                    <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold" id="btnRejectPo" onclick="openRejectModal()">
                        <i class="bi bi-x-circle me-1"></i> Tidak Disetujui
                    </button>
                    <!-- Tombol Simpan sebagai Draft -->
                    <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold text-dark shadow-sm" id="btnSaveDraftPo" onclick="handleSaveDraftPo()">
                        <i class="bi bi-file-earmark-diff me-1"></i> Simpan Draft
                    </button>
                    <!-- Tombol Setujui & Terbitkan PO -->
                    <button type="submit" id="btnSubmitPo" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Setujui &amp; Terbitkan
                    </button>
                    <!-- Tombol Print Request Order yang Sudah Disetujui (Muncul saat RO sudah terbit PO / selesai) -->
                    
                </div>

            </div>

        </form>

    </div>

</div>

<!-- =============================================================
     MODAL KONFIRMASI TOLAK RO (TIDAK DISETUJUI)
     ============================================================= -->
<div class="modal fade" id="modalRejectRo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-x-circle-fill me-2"></i>Tolak Request Order (Tidak Disetujui)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Mohon masukkan alasan penolakan Request Order ini. Catatan akan tersimpan dan dapat dilihat oleh pemohon:
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectAlasanText" rows="4" required placeholder="Contoh: Anggaran unit belum disetujui / Stok di gudang masih mencukupi..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm px-4 fw-semibold" id="btnConfirmReject" onclick="submitRejectRo()">
                    <i class="bi bi-x-circle me-1"></i> Konfirmasi Tolak
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL VERIFIKASI & OTORISASI PENERBITAN PURCHASE ORDER (MINIMALIS)
     ============================================================= -->
<div class="modal fade" id="modalVerifyPo" tabindex="-1" aria-labelledby="modalVerifyPoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check fs-5"></i>
                    <h5 class="modal-title fs-6 fw-bold mb-0" id="modalVerifyPoLabel">
                        Verifikasi &amp; Konfirmasi Penerbitan Purchase Order
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 bg-light">
                
                <!-- Ringkasan Singkat PO -->
                <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 bg-white border rounded-2 mb-2 gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <span class="text-muted" style="font-size: 0.72rem; display: block;">Nomor PO:</span>
                            <strong class="text-dark font-monospace small" id="verifyPoNumberDisplay">PO-XXXX-XXXX</strong>
                        </div>
                        <div class="border-start ps-3">
                            <span class="text-muted" style="font-size: 0.72rem; display: block;">Vendor Rekanan:</span>
                            <span class="fw-semibold text-dark small" id="verifyVendorNameDisplay">-</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted" style="font-size: 0.72rem; display: block;">Grand Total Transaksi:</span>
                        <strong class="text-success font-monospace fs-6" id="verifyGrandTotalDisplay">Rp 0</strong>
                    </div>
                </div>

                <!-- 6 Checklist Header Bar -->
                <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                    <span class="fw-bold text-dark" style="font-size: 0.8rem;">
                        <i class="bi bi-card-checklist text-primary me-1"></i> Parameter Verifikasi Wajib (6 Poin):
                    </span>
                    <button type="button" class="btn btn-link text-decoration-none btn-sm p-0 fw-semibold" style="font-size: 0.75rem;" onclick="toggleCheckAllVerify(true)">
                        <i class="bi bi-check-all me-1"></i>Centang Semua
                    </button>
                </div>

                <!-- 6 CHECKLIST VERIFIKASI (2-KOLOM KOMPAK) -->
                <div class="row g-2 mb-2">
                    <!-- 1. T.O.P -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyTop" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyTop">
                                    1. Term of Payment (TOP)
                                </label>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 0.7rem;" id="verifyValTop">0 Hari</span>
                        </div>
                    </div>

                    <!-- 4. Total QTY -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyTotalQty" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyTotalQty">
                                    4. Total Kuantitas (Qty)
                                </label>
                            </div>
                            <span class="badge bg-dark-subtle text-dark border font-monospace" style="font-size: 0.7rem;" id="verifyValTotalQty">0 Qty (0 Item)</span>
                        </div>
                    </div>

                    <!-- 2. Pengiriman -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyPengiriman" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyPengiriman">
                                    2. Metode Pengiriman
                                </label>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.7rem;" id="verifyValPengiriman">Vendor</span>
                        </div>
                    </div>

                    <!-- 5. Kena Pajak (PPN) -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyKenaPajak" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyKenaPajak">
                                    5. Status Kena Pajak (PPN)
                                </label>
                            </div>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace" style="font-size: 0.7rem;" id="verifyValKenaPajak">PPN (12%)</span>
                        </div>
                    </div>

                    <!-- 3. Tanggal Pengiriman -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyTanggalPengiriman" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyTanggalPengiriman">
                                    3. Estimasi Tiba / Kirim
                                </label>
                            </div>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace" style="font-size: 0.7rem;" id="verifyValTanggalPengiriman">-</span>
                        </div>
                    </div>

                    <!-- 6. Skema Termasuk Pajak -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyTermasukPajak" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyTermasukPajak">
                                    6. Skema Pajak Inklusif/Eksklusif
                                </label>
                            </div>
                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.7rem;" id="verifyValTermasukPajak">Eksklusif</span>
                        </div>
                    </div>
                </div>

                <!-- Input Password Otorisasi Kompak -->
                <div class="p-2 px-3 bg-white border rounded-2 shadow-xs">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <label class="form-label small fw-bold text-dark mb-0" for="inputVerifyPassword">
                            <i class="bi bi-key-fill text-warning me-1"></i> Password Akun Anda <span class="text-danger">*</span>:
                        </label>
                        <div class="input-group input-group-sm" style="max-width: 320px;">
                            <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-muted"></i></span>
                            <input type="password" class="form-control" id="inputVerifyPassword" 
                                   placeholder="Password login akun..." 
                                   autocomplete="current-password" 
                                   oninput="this.classList.remove('is-invalid'); checkVerifyCompleteness();" 
                                   onkeydown="if(event.key === 'Enter') { event.preventDefault(); if(!document.getElementById('btnFinalSubmitPo').disabled) submitFinalApprovedPo(); }"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleVerifyPasswordVisibility()" title="Lihat/Sembunyikan Password">
                                <i class="bi bi-eye" id="toggleVerifyEyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block text-danger small mt-1 d-none" id="verifyPasswordErrorText">
                        <i class="bi bi-exclamation-circle me-1"></i>Password otorisasi salah. Silakan coba lagi.
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-success btn-sm px-3 fw-bold shadow-sm" id="btnFinalSubmitPo" onclick="submitFinalApprovedPo()" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Konfirmasi &amp; Terbitkan PO
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<!-- Client-side Logic Script for PO Process -->
<script>
const ID_REQUEST = <?= $idRequest ?>;
let roDataCache = null;
let modalRejectInstance = null;
let modalVerifyPoInstance = null;
let calculatedDiskonNominal = 0;

document.addEventListener('DOMContentLoaded', async () => {
    modalRejectInstance = new bootstrap.Modal(document.getElementById('modalRejectRo'));
    modalVerifyPoInstance = new bootstrap.Modal(document.getElementById('modalVerifyPo'));

    await Promise.all([
        fetchNextPoNumber(),
        loadAllVendors(),
        loadRoDetails()
    ]);
});

// -------------------------------------------------------------
// VENDOR SELECTION & SEARCH LOGIC
// -------------------------------------------------------------
let allVendorListCache = [];
let vendorSearchTimer = null;

async function loadAllVendors() {
    try {
        const res = await apiRequest('/api/master/vendor.php?status=AKTIF&limit=100');
        if (res && res.success) {
            const rawList = res.data?.items || res.data || [];
            allVendorListCache = Array.isArray(rawList) ? rawList.map(v => ({
                id: v.id_vendor || v.id,
                nama: v.nama_perusahaan || v.nama || '',
                kode: v.kode_vendor || v.kode || '',
                kontak_person: v.kontak_person || '',
                telepon: v.no_telepon || v.telepon || '',
                top_hari: (typeof v.term_of_payment !== 'undefined') ? v.term_of_payment : (v.top_hari || 0)
            })) : [];
            
            allVendorListCache.sort((a, b) => a.nama.localeCompare(b.nama));
        } else {
            allVendorListCache = [];
        }
    } catch (e) {
        console.error('Error loading vendors:', e);
        allVendorListCache = [];
    }
}

function openRoVendorDropdown() {
    const isLocked = document.getElementById('displayVendorName').disabled;
    if (isLocked) return;

    const dropdown = document.getElementById('roVendorDropdown');
    if (!dropdown) return;
    dropdown.classList.remove('d-none');
    filterRoVendors();
}

function closeRoVendorDropdown() {
    const dropdown = document.getElementById('roVendorDropdown');
    if (dropdown) dropdown.classList.add('d-none');
}

function debounceRoVendorSearch() {
    clearTimeout(vendorSearchTimer);
    vendorSearchTimer = setTimeout(() => {
        filterRoVendors();
    }, 200);
}

function filterRoVendors() {
    const searchInput = document.getElementById('displayVendorName');
    const term = (searchInput ? searchInput.value : '').toLowerCase().trim();
    const listContainer = document.getElementById('roVendorDropdownList');
    if (!listContainer) return;

    let filtered = allVendorListCache;
    if (term) {
        filtered = allVendorListCache.filter(v => {
            const nama = (v.nama || '').toLowerCase();
            const kode = (v.kode || '').toLowerCase();
            const kontak = (v.kontak_person || '').toLowerCase();
            return nama.includes(term) || kode.includes(term) || kontak.includes(term);
        });
    }

    if (filtered.length === 0) {
        listContainer.innerHTML = `
            <div class="p-3 text-center text-muted small">
                <i class="bi bi-building-slash text-secondary me-1"></i> Tidak ada vendor yang cocok
            </div>
        `;
        return;
    }

    listContainer.innerHTML = filtered.map(v => `
        <div class="ro-vendor-dropdown-item" onclick="selectRoVendor(${v.id}, '${escapeHtml(v.nama || '')}', '${escapeHtml(v.kode || '')}', '${escapeHtml(v.top_hari || '0')}')">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">${escapeHtml(v.nama || '-')}</span>
                <span class="badge bg-secondary font-monospace">${escapeHtml(v.kode || '-')}</span>
            </div>
        </div>
    `).join('');
}

function selectRoVendor(id, nama, kode, topHari) {
    document.getElementById('inputVendorId').value = id || '';
    document.getElementById('displayVendorName').value = kode ? `${nama} (${kode})` : nama;
    
    // Update TOP mengambil vendor.term_of_payment
    const topInput = document.getElementById('inputTop');
    if (topInput) {
        topInput.value = parseInt(topHari) || 0;
        if (typeof updateTopKeterangan === 'function') {
            updateTopKeterangan(topInput.value);
        }
    }
    
    closeRoVendorDropdown();
}

function clearRoVendorSelection() {
    const isLocked = document.getElementById('displayVendorName').disabled;
    if (isLocked) return;

    document.getElementById('inputVendorId').value = '';
    document.getElementById('displayVendorName').value = '';
    document.getElementById('displayVendorName').focus();
    openRoVendorDropdown();
}

document.addEventListener('click', (e) => {
    const wrapper = document.getElementById('roVendorSearchWrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        closeRoVendorDropdown();
    }
});

// -------------------------------------------------------------
// UPDATE T.O.P KETERANGAN (0 = C.O.D)
// -------------------------------------------------------------
function updateTopKeterangan(val) {
    const num = parseInt(val) || 0;
    const badge = document.getElementById('badgeTopKeterangan');
    const hint = document.getElementById('hintTopText');

    if (num === 0) {
        badge.classList.remove('d-none');
        badge.textContent = 'C.O.D (Cash On Delivery)';
        hint.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>0 Hari = Pembayaran Tunai / C.O.D (Cash On Delivery)</span>';
    } else {
        badge.classList.add('d-none');
        hint.innerHTML = '* Jika 0 hari berarti C.O.D (Cash On Delivery).';
    }
}

// -------------------------------------------------------------
// PENGATURAN PAJAK (PPN & PPnBM) & TIPE DISKON
// -------------------------------------------------------------
function togglePajak(enable) {
    const ppnbmRate = parseFloat(document.getElementById('inputPajakPpnbm')?.value) || 0;
    const activePpnRate = (ppnbmRate > 0) ? 12 : 11;
    document.getElementById('inputPajakPpn').value = enable ? activePpnRate : 0;
    calculateAllTotals();
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

    // Bersihkan karakter non-angka (sekaligus cegah tanda minus)
    let cleanVal = el.value.replace(/[^0-9]/g, '');
    let num = parseInt(cleanVal, 10);
    if (isNaN(num) || num < 0) num = 0;

    el.value = num > 0 ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '0';

    // Sesuaikan posisi kursor agar tetap natural saat mengetik
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

function handleDiskonNilaiInput(el) {
    const diskonType = document.getElementById('selectDiskonType').value;
    if (diskonType === 'percent') {
        let cleanVal = el.value.replace(/[^0-9.]/g, '');
        let num = parseFloat(cleanVal);
        if (isNaN(num) || num < 0) num = 0;
        if (num > 100) num = 100;
        el.value = num;
        calculateAllTotals();
    } else {
        handleThousandInput(el, () => calculateAllTotals());
    }
}

function onDiskonTypeChange() {
    const type = document.getElementById('selectDiskonType').value;
    const prefix = document.getElementById('addonDiskonPrefix');
    const suffix = document.getElementById('addonDiskonSuffix');
    const label = document.getElementById('labelNilaiDiskon');
    const input = document.getElementById('inputDiskonNilai');

    if (type === 'percent') {
        prefix.classList.add('d-none');
        suffix.classList.remove('d-none');
        label.textContent = 'Nilai Diskon (%):';
        input.value = '0';
    } else {
        prefix.classList.remove('d-none');
        suffix.classList.add('d-none');
        label.textContent = 'Nilai Diskon (Rp):';
        input.value = '0';
    }
    calculateAllTotals();
}

// -------------------------------------------------------------
// LOAD RO DETAILS
// -------------------------------------------------------------
async function loadRoDetails() {
    const res = await apiRequest(`/api/request_order/index.php?id=${ID_REQUEST}`);
    if (!res || !res.success) {
        showToast(res ? res.message : 'Gagal memuat detail Request Order.', 'danger');
        document.getElementById('loadingWrapper').innerHTML = `
            <div class="alert alert-danger d-inline-block px-4 py-3">
                <i class="bi bi-exclamation-octagon me-2"></i> ${res ? res.message : 'Request Order tidak ditemukan.'}
            </div>
        `;
        return;
    }

    roDataCache = res.data;
    renderRoData();
}

function renderRoData() {
    const ro = roDataCache;

    // 0. Nomor RO (Readonly)
    document.getElementById('displayRoNomor').value = ro.nomor || '-';

    // 1. Vendor (Default dari RO atau dipilih bebas)
    document.getElementById('inputVendorId').value = ro.id_vendor || '';
    if (ro.nama_vendor) {
        document.getElementById('displayVendorName').value = `${ro.nama_vendor} (${ro.kode_vendor || 'VND'})`;
    } else {
        document.getElementById('displayVendorName').value = '';
    }

    // 2. Prioritas PO (Readonly)
    const prio = ro.prioritas ? ro.prioritas.toUpperCase() : 'NORMAL';
    document.getElementById('inputPrioritas').value = prio;
    document.getElementById('displayRoPrioritas').value = prio;

    // 3. Tab Pengiriman: Alamat Pengiriman default dari Site.alamat
    if (ro.nama_site) {
        document.getElementById('labelSiteName').textContent = `Site: ${ro.nama_site} (${ro.kode_site || '-'})`;
    }
    if (ro.alamat_site) {
        document.getElementById('inputAlamatPengiriman').value = ro.alamat_site;
    } else if (ro.nama_site) {
        document.getElementById('inputAlamatPengiriman').value = `Lokasi ${ro.nama_site}`;
    }

    // 4. Inisialisasi T.O.P default dari vendor.term_of_payment (namun tetap dapat diubah)
    const inputTopEl = document.getElementById('inputTop');
    if (inputTopEl) {
        if (typeof ro.vendor_term_of_payment !== 'undefined' && ro.vendor_term_of_payment !== null && ro.vendor_term_of_payment !== '') {
            inputTopEl.value = parseInt(ro.vendor_term_of_payment);
        } else {
            inputTopEl.value = 30;
        }
        updateTopKeterangan(inputTopEl.value);
    }

    // 5. Render Items di Tab Pricing (Lengkap dengan Kategori, Merk, Stok & Status Mewah PPnBM)
    const items = ro.items || [];
    document.getElementById('roTotalItemsBadge').textContent = `${items.length} Item Barang`;

    let hasPpnbmInRo = false;
    let roPpnbmRate = 0;

    let pricingHtml = '';
    items.forEach((item, idx) => {
        const isLuxury = (parseInt(item.PPnBM) === 1 || parseInt(item.ppnbm) === 1);
        const itemRatePpnbm = parseFloat(item.rate_PPnBM || item.rate_ppnbm || 0);

        if (isLuxury) {
            hasPpnbmInRo = true;
            if (itemRatePpnbm > 0 && roPpnbmRate === 0) {
                roPpnbmRate = itemRatePpnbm;
            }
        }

        // Ambil harga_set dari barang_hargavendor yang berlaku paling akhir (atau fallback ke harga RO)
        const defaultHarga = parseFloat(item.harga_set) > 0 ? parseFloat(item.harga_set) : (parseFloat(item.harga) || 0);
        const subtotal = item.qty * defaultHarga;
        const totalStok = parseInt(item.total_stok) || 0;

        const luxuryBadge = isLuxury
            ? `<span class="badge bg-warning text-dark border border-warning-subtle fw-bold" style="font-size: 0.68rem;"><i class="bi bi-stars me-1"></i>PPnBM (${itemRatePpnbm}%)</span>`
            : `<span class="badge bg-light text-muted border" style="font-size: 0.68rem;">Non-PPnBM</span>`;

        pricingHtml += `
            <tr data-item-id="${item.id_barang}">
                <td class="text-center font-monospace small text-muted">${idx + 1}</td>
                <td>
                    <div class="fw-bold text-dark mb-1">${escapeHtml(item.nama_barang)}</div>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                        ${luxuryBadge}
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-tag me-1"></i>${escapeHtml(item.nama_kategori || 'Material')}</span>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-bookmark me-1"></i>${escapeHtml(item.nama_merk || 'Umum')}</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace" style="font-size: 0.68rem;">
                            <i class="bi bi-boxes me-1"></i>Stok: ${totalStok} ${escapeHtml(item.satuan)}
                        </span>
                    </div>
                    <input type="hidden" class="item-id-barang" value="${item.id_barang}">
                </td>
                <td class="text-center">
                    <span class="fw-bold font-monospace">${item.qty}</span>
                    <span class="small text-muted d-block" style="font-size: 0.72rem;">${escapeHtml(item.satuan)}</span>
                    <input type="hidden" class="item-qty" value="${item.qty}">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light font-monospace text-muted px-2" style="font-size: 0.75rem;">Rp</span>
                        <input type="text" class="form-control form-control-sm font-monospace text-end item-harga" value="${formatThousand(defaultHarga)}" oninput="handlePriceInput(this)" placeholder="0" required>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light font-monospace text-muted px-2" style="font-size: 0.75rem;">Rp</span>
                        <input type="text" class="form-control form-control-sm font-monospace text-end item-diskon text-danger" value="0" oninput="handleDiscountInput(this)" placeholder="0">
                    </div>
                </td>
                <td class="text-end font-monospace fw-bold text-dark item-subtotal-display">
                    ${formatRupiah(subtotal)}
                </td>
            </tr>
        `;
    });

    document.getElementById('tablePricingItemsBody').innerHTML = pricingHtml;

    // Sinkronisasi Notice & Pengaturan Pajak PPnBM / PPN
    const noticeContainer = document.getElementById('roPpnbmNoticeContainer');
    const wrapperPajakPpnbm = document.getElementById('wrapperPajakPpnbm');
    const textPpnbmRate = document.getElementById('textPpnbmRate');
    const badgePpnbmRate = document.getElementById('badgePpnbmRate');
    const inputPajakPpnbm = document.getElementById('inputPajakPpnbm');
    const checkEnablePajak = document.getElementById('checkEnablePajak');

    if (hasPpnbmInRo && roPpnbmRate > 0) {
        if (noticeContainer) {
            noticeContainer.classList.remove('d-none');
            noticeContainer.innerHTML = `
                <div class="alert alert-warning py-2 px-3 small mb-3 border-0 rounded-3 shadow-xs d-flex align-items-center">
                    <i class="bi bi-stars text-warning-emphasis fs-5 me-2"></i>
                    <div>
                        <strong>Dokumen RO Barang Mewah (PPnBM):</strong> Seluruh barang dalam RO ini dikenakan pajak barang mewah PPnBM sebesar <strong>${roPpnbmRate}%</strong> dan tarif PPN <strong>12%</strong>.
                    </div>
                </div>
            `;
        }
        if (wrapperPajakPpnbm) wrapperPajakPpnbm.classList.remove('d-none');
        if (textPpnbmRate) textPpnbmRate.textContent = roPpnbmRate;
        if (badgePpnbmRate) badgePpnbmRate.textContent = `${roPpnbmRate}% (PPnBM)`;
        if (inputPajakPpnbm) inputPajakPpnbm.value = roPpnbmRate;

        // Untuk barang mewah, tarif PPN adalah 12%
        if (document.getElementById('textPpnRateLabel')) document.getElementById('textPpnRateLabel').textContent = '12';
        if (document.getElementById('textPpnBadgeRate')) document.getElementById('textPpnBadgeRate').textContent = '12';
        document.getElementById('inputPajakPpn').value = 12;
    } else {
        if (noticeContainer) noticeContainer.classList.add('d-none');
        if (wrapperPajakPpnbm) wrapperPajakPpnbm.classList.add('d-none');
        if (inputPajakPpnbm) inputPajakPpnbm.value = 0;

        // Untuk barang biasa, tarif PPN adalah 11%
        if (document.getElementById('textPpnRateLabel')) document.getElementById('textPpnRateLabel').textContent = '11';
        if (document.getElementById('textPpnBadgeRate')) document.getElementById('textPpnBadgeRate').textContent = '11';
        document.getElementById('inputPajakPpn').value = 11;
    }

    // Default PPN aktif
    if (checkEnablePajak) {
        checkEnablePajak.checked = true;
    }

    // Hitung kalkulasi awal
    calculateAllTotals();

    // Tampilkan Konten Utama
    document.getElementById('loadingWrapper').classList.add('d-none');
    document.getElementById('mainContentWrapper').classList.remove('d-none');

    // Cek apakah RO sudah selesai diproses ke PO atau berstatus akhir
    const isPoApproved = (ro.status === 'DISETUJUI PURCHASING' || (ro.id_po && parseInt(ro.id_po) > 0));
    const isLocked = isPoApproved || ['TIDAK DISETUJUI PURCHASING', 'BATAL'].includes(ro.status);

    if (isLocked) {
        // Nonaktifkan semua input formulir
        document.querySelectorAll('#formProsesPo input, #formProsesPo textarea, #formProsesPo select, #formProsesPo button').forEach(el => {
            if (el.id !== 'btnPrintApprovedRo') {
                el.disabled = true;
            }
        });

        // Sembunyikan semua tombol aksi proses PO
        const btnReject = document.getElementById('btnRejectPo');
        const btnCancel = document.getElementById('btnCancelPo');
        const btnDraft = document.getElementById('btnSaveDraftPo');
        const btnSubmit = document.getElementById('btnSubmitPo');

        if (btnReject) btnReject.classList.add('d-none');
        if (btnCancel) btnCancel.classList.add('d-none');
        if (btnDraft) btnDraft.classList.add('d-none');
        if (btnSubmit) btnSubmit.classList.add('d-none');

        // Tampilkan tombol Print RO Disetujui
        const btnPrint = document.getElementById('btnPrintApprovedRo');
        if (btnPrint) btnPrint.classList.remove('d-none');

        // Tampilkan Banner Terkunci
        const bannerContainer = document.getElementById('roLockedBannerContainer');
        if (bannerContainer) {
            bannerContainer.classList.remove('d-none');
            if (isPoApproved) {
                bannerContainer.innerHTML = `
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0 rounded-3 shadow-xs d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 text-primary"></i>
                        <span>PO telah terbit untuk RO <strong>${escapeHtml(ro.nomor || '')}</strong>. Dokumen ini bersifat <em>Read-Only</em>.</span>
                    </div>
                `;
            } else {
                bannerContainer.innerHTML = `
                    <div class="alert alert-secondary py-2 px-3 small mb-3 border-0 rounded-3 shadow-xs d-flex align-items-center">
                        <i class="bi bi-lock-fill me-2 text-secondary"></i>
                        <span>Status RO: <strong>${escapeHtml(ro.status || '')}</strong>. Dokumen ini bersifat <em>Read-Only</em>.</span>
                    </div>
                `;
            }
        }
    }
}

// -------------------------------------------------------------
// FETCH NEXT PO NUMBER
// -------------------------------------------------------------
async function fetchNextPoNumber() {
    const tanggalPo = document.getElementById('inputTanggalPo')?.value || '';
    const res = await apiRequest(`/api/purchase_order/get_next_number.php?date=${encodeURIComponent(tanggalPo)}`);
    if (res && res.success && res.data && res.data.nomor_po) {
        document.getElementById('inputNomorPo').value = res.data.nomor_po;
    }
}

// -------------------------------------------------------------
// KALKULASI HARGA & TOTAL (PPN 11%/12%, PPnBM, INKLUSIF/EKSKLUSIF, DISKON RP/%)
// -------------------------------------------------------------
function calculateRowSubtotal(inputEl) {
    const row = inputEl.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const harga = parseThousandNumber(row.querySelector('.item-harga').value);
    const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);

    let subtotal = (qty * harga) - diskon;
    if (subtotal < 0) subtotal = 0;

    row.querySelector('.item-subtotal-display').textContent = formatRupiah(subtotal);
    calculateAllTotals();
}

function calculateAllTotals() {
    let subtotalBarang = 0;
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga').value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);
        let rowSubtotal = (qty * harga) - diskon;
        if (rowSubtotal < 0) rowSubtotal = 0;
        subtotalBarang += rowSubtotal;
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

    const dasarSetelahDiskon = subtotalBarang - diskonAkhirNominal;

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

    let dpp = 0;
    let ppnbmAmount = 0;
    let ppnAmount = 0;
    let grandTotal = 0;

    // Basis perhitungan setelah diskon global
    // 1. Hitung DPP berdasarkan status inklusif/eksklusif
    // Jika harga inklusif PPnBM dan/atau PPN
    let divisor = 1.0;
    if (isTermasukPpnbm && ppnbmRate > 0) {
        divisor += (ppnbmRate / 100);
    }
    if (isTermasukPajak && ppnRate > 0) {
        divisor += (ppnRate / 100);
    }

    dpp = dasarSetelahDiskon / divisor;

    // 2. Hitung Nominal Pajak dari DPP
    if (ppnbmRate > 0) {
        ppnbmAmount = (dpp * ppnbmRate) / 100;
    }
    if (ppnRate > 0) {
        ppnAmount = (dpp * ppnRate) / 100;
    }

    // 3. Grand Total
    if (divisor > 1.0) {
        // Jika ada pajak yang inklusif, sesuaikan grand total dengan dasar + pajak eksklusif
        grandTotal = dpp;
        if (ppnbmRate > 0) grandTotal += ppnbmAmount;
        if (ppnRate > 0) grandTotal += ppnAmount;
    } else {
        // Eksklusif murni: DPP + PPnBM + PPN
        grandTotal = dpp + ppnbmAmount + ppnAmount;
    }

    // Tampilkan di Ringkasan Biaya
    document.getElementById('summarySubtotal').textContent = formatRupiah(subtotalBarang);
    document.getElementById('summaryDiskon').textContent = `- ${formatRupiah(diskonAkhirNominal)}`;
    document.getElementById('summaryDpp').textContent = formatRupiah(dpp);

    // Baris PPnBM
    const summaryRowPpnbm = document.getElementById('summaryRowPpnbm');
    const labelSummaryPpnbm = document.getElementById('labelSummaryPpnbm');
    const summaryPpnbm = document.getElementById('summaryPpnbm');
    if (ppnbmRate > 0) {
        if (summaryRowPpnbm) summaryRowPpnbm.classList.remove('d-none');
        if (labelSummaryPpnbm) labelSummaryPpnbm.textContent = `PPnBM (${ppnbmRate}%)${isTermasukPpnbm ? ' (Inklusif)' : ''}:`;
        if (summaryPpnbm) summaryPpnbm.textContent = formatRupiah(ppnbmAmount);
    } else {
        if (summaryRowPpnbm) summaryRowPpnbm.classList.add('d-none');
    }

    // Baris PPN (12% untuk PPnBM, 11% untuk Non-PPnBM)
    document.getElementById('labelSummaryPpn').textContent = `PPN (${standardPpnRate}%)${isTermasukPajak ? ' (Inklusif)' : ''}:`;
    document.getElementById('summaryPpn').textContent = isPajakEnabled ? formatRupiah(ppnAmount) : 'Rp 0';
    document.getElementById('summaryGrandTotal').textContent = formatRupiah(grandTotal);
}

// -------------------------------------------------------------
// 1. POPUP VERIFIKASI SEBELUM TERBIT PO (6 CHECKLIST + PASSWORD)
// -------------------------------------------------------------
function handleApproveToPo(e) {
    if (e) e.preventDefault();

    const idVendor = document.getElementById('inputVendorId').value;
    if (!idVendor) {
        showToast('Vendor belum ditentukan pada permohonan Request Order.', 'warning');
        return;
    }

    const nomorPo = document.getElementById('inputNomorPo').value.trim();
    if (!nomorPo) {
        showToast('Nomor Purchase Order wajib diisi.', 'warning');
        return;
    }

    // Kumpulkan item barang dari tab pricing untuk verifikasi kuantitas
    let totalQtyCount = 0;
    let itemCount = 0;
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        totalQtyCount += qty;
        itemCount++;
    });

    if (itemCount === 0) {
        showToast('Rincian barang permohonan tidak ditemukan.', 'warning');
        return;
    }

    // 1. Ekstrak Nilai untuk 6 Poin Checklist
    const topVal = parseInt(document.getElementById('inputTop')?.value) || 0;
    const pengirimanVal = document.getElementById('selectPengiriman')?.value || 'Vendor';
    const tanggalKirimVal = document.getElementById('inputTanggalPengiriman')?.value || '';
    const isKenaPajak = document.getElementById('checkEnablePajak')?.checked || false;
    const isTermasukPajak = document.getElementById('checkTermasukPajak')?.checked || false;
    const ppnbmRate = parseFloat(document.getElementById('inputPajakPpnbm')?.value) || 0;
    const isTermasukPpnbm = document.getElementById('checkTermasukPpnbm')?.checked || false;

    // 2. Isi Ringkasan Modal
    if (document.getElementById('verifyPoNumberDisplay')) document.getElementById('verifyPoNumberDisplay').textContent = nomorPo;
    if (document.getElementById('verifyVendorNameDisplay')) {
        document.getElementById('verifyVendorNameDisplay').textContent = document.getElementById('displayVendorName')?.value || 'Vendor Rekanan';
    }
    if (document.getElementById('verifyGrandTotalDisplay')) {
        document.getElementById('verifyGrandTotalDisplay').textContent = document.getElementById('summaryGrandTotal')?.textContent || 'Rp 0';
    }

    // 3. Isi Nilai 6 Poin Checklist
    if (document.getElementById('verifyValTop')) document.getElementById('verifyValTop').textContent = topVal === 0 ? '0 Hari (C.O.D / Tunai)' : `${topVal} Hari`;
    if (document.getElementById('verifyValPengiriman')) document.getElementById('verifyValPengiriman').textContent = pengirimanVal;
    if (document.getElementById('verifyValTanggalPengiriman')) document.getElementById('verifyValTanggalPengiriman').textContent = tanggalKirimVal ? tanggalKirimVal : 'Sesuai Jadwal Standar';
    if (document.getElementById('verifyValTotalQty')) document.getElementById('verifyValTotalQty').textContent = `${totalQtyCount} Qty (${itemCount} Item Barang)`;
    
    // Status Pajak di Checklist 5
    let statusPajakText = [];
    const activePpnRate = (ppnbmRate > 0) ? 12 : 11;
    if (ppnbmRate > 0) {
        statusPajakText.push(`PPnBM ${ppnbmRate}%`);
    }
    if (isKenaPajak) {
        statusPajakText.push(`PPN ${activePpnRate}%`);
    }
    if (statusPajakText.length === 0) {
        statusPajakText.push('Bebas Pajak (Non-PPN)');
    }
    if (document.getElementById('verifyValKenaPajak')) {
        document.getElementById('verifyValKenaPajak').textContent = statusPajakText.join(' + ');
    }

    // Status Skema Inklusif/Eksklusif di Checklist 6
    let skemaText = [];
    if (isTermasukPajak) skemaText.push('PPN Inklusif');
    if (isTermasukPpnbm) skemaText.push('PPnBM Inklusif');
    if (skemaText.length === 0) skemaText.push('Eksklusif');
    if (document.getElementById('verifyValTermasukPajak')) {
        document.getElementById('verifyValTermasukPajak').textContent = skemaText.join(' & ');
    }

    // 4. Reset Checkbox & Input Password
    document.querySelectorAll('.verify-check-item').forEach(cb => cb.checked = false);
    const pwInput = document.getElementById('inputVerifyPassword');
    if (pwInput) {
        pwInput.value = '';
        pwInput.classList.remove('is-invalid');
    }
    const errText = document.getElementById('verifyPasswordErrorText');
    if (errText) {
        errText.classList.add('d-none');
    }
    document.getElementById('btnFinalSubmitPo').disabled = true;

    // 5. Buka Modal Verifikasi
    modalVerifyPoInstance.show();
}

function checkVerifyCompleteness() {
    const checkboxes = document.querySelectorAll('.verify-check-item');
    let allChecked = true;
    checkboxes.forEach(cb => {
        if (!cb.checked) allChecked = false;
    });

    const passwordVal = document.getElementById('inputVerifyPassword').value.trim();
    const hasPassword = passwordVal.length > 0;

    const btn = document.getElementById('btnFinalSubmitPo');
    if (btn) {
        btn.disabled = !(allChecked && hasPassword);
    }
}

function toggleCheckAllVerify(checkAll) {
    document.querySelectorAll('.verify-check-item').forEach(cb => {
        cb.checked = checkAll;
    });
    checkVerifyCompleteness();
}

function toggleVerifyPasswordVisibility() {
    const input = document.getElementById('inputVerifyPassword');
    const icon = document.getElementById('toggleVerifyEyeIcon');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// -------------------------------------------------------------
// 2. SUBMIT FINAL PO SETELAH OTORISASI PASSWORD
// -------------------------------------------------------------
async function submitFinalApprovedPo() {
    const passwordVal = document.getElementById('inputVerifyPassword').value.trim();
    if (!passwordVal) {
        showToast('Password otorisasi wajib dimasukkan.', 'warning');
        document.getElementById('inputVerifyPassword').focus();
        return;
    }

    const checkboxes = document.querySelectorAll('.verify-check-item');
    for (let cb of checkboxes) {
        if (!cb.checked) {
            showToast('Semua 6 poin parameter checklist wajib diverifikasi dan dicentang.', 'warning');
            return;
        }
    }

    const btn = document.getElementById('btnFinalSubmitPo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memverifikasi &amp; Menerbitkan PO...';

    // Kumpulkan item barang dari tab pricing
    const items = [];
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const idBarang = parseInt(row.querySelector('.item-id-barang').value);
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga').value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);
        items.push({
            id_barang: idBarang,
            qty: qty,
            harga: harga,
            diskon: diskon,
            kena_pajak: document.getElementById('checkEnablePajak').checked ? 1 : 0
        });
    });

    const payload = {
        action: 'approve',
        id_request: ID_REQUEST,
        confirm_password: passwordVal,
        nomor_po: document.getElementById('inputNomorPo').value.trim(),
        tanggal_po: document.getElementById('inputTanggalPo').value,
        prioritas: document.getElementById('inputPrioritas').value,
        id_vendor: parseInt(document.getElementById('inputVendorId').value),
        term_of_payment: parseInt(document.getElementById('inputTop').value) || 0,
        pengiriman: document.getElementById('selectPengiriman').value,
        tanggal_pengiriman: document.getElementById('inputTanggalPengiriman').value || null,
        alamat: document.getElementById('inputAlamatPengiriman').value.trim(),
        pajak: parseInt(document.getElementById('inputPajakPpn').value) || 0,
        total_termasuk_pajak: document.getElementById('checkTermasukPajak').checked ? 1 : 0,
        pajak_PPnBM: parseInt(document.getElementById('inputPajakPpnbm').value) || 0,
        total_termasuk_PPnBM: document.getElementById('checkTermasukPpnbm')?.checked ? 1 : 0,
        diskon: calculatedDiskonNominal || 0,
        keterangan: document.getElementById('inputKeteranganPo').value.trim(),
        items: items
    };

    const res = await apiRequest('/api/purchase_order/process_ro.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Konfirmasi &amp; Terbitkan PO Sekarang';

    if (res && res.success) {
        modalVerifyPoInstance.hide();
        const finalNomor = res.data?.nomor_po || payload.nomor_po || '-';
        showSuccessTransactionModal({
            title: 'Purchase Order Berhasil Diterbitkan',
            subtitle: 'Berhasil Disimpan Dengan Nomor :',
            nomor: finalNomor,
            redirectUrl: `${BASE_URL}/admin/pages/request_order/index.php`
        });
    } else {
        const errorMsg = res && res.message ? res.message : 'Gagal memproses Purchase Order.';
        showToast(errorMsg, 'error');
        const pwInput = document.getElementById('inputVerifyPassword');
        const errText = document.getElementById('verifyPasswordErrorText');
        if (pwInput) {
            pwInput.classList.add('is-invalid');
            pwInput.focus();
            pwInput.select();
        }
        if (errText) {
            errText.textContent = errorMsg;
            errText.classList.remove('d-none');
        }
    }
}

// -------------------------------------------------------------
// SUBMIT: SIMPAN SEBAGAI DRAFT (NOMOR DRF-PO-YYMM-01)
// -------------------------------------------------------------
async function handleSaveDraftPo() {
    const idVendor = document.getElementById('inputVendorId').value;
    if (!idVendor) {
        showToast('Vendor belum ditentukan pada permohonan Request Order.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSaveDraftPo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan Draft...';

    // 1. Ambil nomor draft PO
    let draftNomor = document.getElementById('inputNomorPo').value.trim();
    if (!draftNomor.startsWith('DRF-') && !draftNomor.startsWith('DRF/')) {
        const tanggalPo = document.getElementById('inputTanggalPo')?.value || '';
        const resDraftNum = await apiRequest(`/api/purchase_order/get_next_number.php?draft=1&date=${encodeURIComponent(tanggalPo)}`);
        if (resDraftNum && resDraftNum.success && resDraftNum.data && resDraftNum.data.nomor_po) {
            draftNomor = resDraftNum.data.nomor_po;
            document.getElementById('inputNomorPo').value = draftNomor;
        }
    }

    // 2. Kumpulkan item barang dari tab pricing
    const items = [];
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const idBarang = parseInt(row.querySelector('.item-id-barang').value);
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga').value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);
        items.push({
            id_barang: idBarang,
            qty: qty,
            harga: harga,
            diskon: diskon,
            kena_pajak: document.getElementById('checkEnablePajak').checked ? 1 : 0
        });
    });

    const payload = {
        action: 'draft',
        id_request: ID_REQUEST,
        nomor_po: draftNomor,
        tanggal_po: document.getElementById('inputTanggalPo').value,
        prioritas: document.getElementById('inputPrioritas').value,
        id_vendor: parseInt(idVendor),
        term_of_payment: parseInt(document.getElementById('inputTop').value) || 0,
        pengiriman: document.getElementById('selectPengiriman').value,
        tanggal_pengiriman: document.getElementById('inputTanggalPengiriman').value || null,
        alamat: document.getElementById('inputAlamatPengiriman').value.trim(),
        pajak: parseInt(document.getElementById('inputPajakPpn').value) || 0,
        total_termasuk_pajak: document.getElementById('checkTermasukPajak').checked ? 1 : 0,
        pajak_PPnBM: parseInt(document.getElementById('inputPajakPpnbm').value) || 0,
        total_termasuk_PPnBM: document.getElementById('checkTermasukPpnbm')?.checked ? 1 : 0,
        diskon: calculatedDiskonNominal || 0,
        keterangan: document.getElementById('inputKeteranganPo').value.trim(),
        items: items
    };

    const res = await apiRequest('/api/purchase_order/process_ro.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-file-earmark-diff me-1"></i> Simpan sebagai Draft';

    if (res && res.success) {
        const finalNomor = res.data?.nomor_po || payload.nomor_po || '-';
        showSuccessTransactionModal({
            title: 'Draft Purchase Order Berhasil Disimpan',
            subtitle: 'Berhasil Disimpan Dengan Nomor :',
            nomor: finalNomor,
            redirectUrl: `${BASE_URL}/admin/pages/request_order/index.php`
        });
    } else {
        showToast(res ? res.message : 'Gagal menyimpan draft Purchase Order.', 'error');
    }
}

// -------------------------------------------------------------
// SUBMIT: TOLAK RO (TIDAK DISETUJUI)
// -------------------------------------------------------------
function openRejectModal() {
    document.getElementById('rejectAlasanText').value = '';
    modalRejectInstance.show();
}

async function submitRejectRo() {
    const alasan = document.getElementById('rejectAlasanText').value.trim();
    if (!alasan) {
        showToast('Wajib memasukkan alasan penolakan.', 'warning');
        document.getElementById('rejectAlasanText').focus();
        return;
    }

    const btn = document.getElementById('btnConfirmReject');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menolak...';

    const res = await apiRequest('/api/purchase_order/process_ro.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'reject',
            id_request: ID_REQUEST,
            alasan: alasan
        })
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Konfirmasi Tolak';

    if (res && res.success) {
        modalRejectInstance.hide();
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/request_order/index.php`;
        }, 1200);
    } else {
        showToast(res ? res.message : 'Gagal menolak Request Order.', 'error');
    }
}

// -------------------------------------------------------------
// HELPER FORMAT RUPIAH
// -------------------------------------------------------------
function formatRupiah(amount) {
    return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const str = String(text);
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return str.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// -------------------------------------------------------------
// PRINT REQUEST ORDER YANG SUDAH DISETUJUI
// -------------------------------------------------------------
function printApprovedRo() {
    // Fungsi print akan diimplementasikan nanti sesuai kebutuhan
}
</script>
