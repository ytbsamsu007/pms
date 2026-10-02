<?php
/**
 * Halaman Edit Dokumen Faktur Purchase Order (Faktur Pembelian)
 * Path: admin/pages/faktur_po/edit.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

$idFaktur = isset($_GET['id']) ? decodeId($_GET['id']) : (isset($_GET['id_faktur']) ? decodeId($_GET['id_faktur']) : 0);

if ($idFaktur <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/faktur_po/index.php');
    exit;
}

$pageTitle = 'Edit Faktur PO';
$pageHeading = 'Formulir Edit Faktur Pembelian';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
/* Standardize Height of all form inputs & input-groups */
.form-control,
.form-control-sm,
.form-select,
.form-select-sm,
.input-group > .form-control,
.input-group > .btn,
.input-group > .input-group-text,
.input-group-sm > .form-control,
.input-group-sm > .btn,
.input-group-sm > .input-group-text {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 0.875rem !important;
}
textarea.form-control {
    height: auto !important;
    min-height: 100px !important;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Edit Faktur Purchase Order (PO)</h4>
            <div class="small text-muted mt-1">
               <span class="badge bg-secondary" id="badgeFakturStatus">-</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/faktur_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- Alert Status Locked -->
    <div class="alert alert-warning border-0 shadow-sm rounded-3 align-items-center mb-4 p-3" id="alertLockedNotice" style="display: none !important;">
        <i class="bi bi-lock-fill text-warning-emphasis fs-4 me-3"></i>
        <div>
            <strong class="d-block text-warning-emphasis">Dokumen Faktur Terkunci (Hanya Baca)</strong>
            Dokumen faktur ini berstatus <strong id="lockedStatusText">-</strong> (atau telah memiliki riwayat pembayaran). Perubahan dokumen faktur tidak diizinkan.
        </div>
    </div>

    <!-- Alert Loading -->
    <div id="loadingNotice" class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span>Memuat data dokumen Faktur PO...</span>
    </div>

    <form id="formEditFaktur" onsubmit="event.preventDefault();" style="display: none;">
        <input type="hidden" id="editIdFaktur" name="id_faktur" value="<?= $idFaktur ?>">
        <input type="hidden" id="selectRcv" name="id_rcv" value="">
        <input type="hidden" id="hiddenIdPo" name="id_po" value="">
        <input type="hidden" id="hiddenIdVendor" name="id_vendor" value="">
        <input type="hidden" id="hiddenIdSite" name="id_site" value="">

        <!-- CARD 1: 5 TAB MODULAR -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="fakturTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-3 px-3" id="tab-dokumen-btn" data-bs-toggle="tab" data-bs-target="#tab-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Dokumen Asal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-vendor-btn" data-bs-toggle="tab" data-bs-target="#tab-vendor" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 2. Vendor &amp; Rekening Bank
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-tagihan-btn" data-bs-toggle="tab" data-bs-target="#tab-tagihan" type="button" role="tab">
                            <i class="bi bi-receipt me-1 text-primary"></i> 3. Tagihan &amp; Pajak
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-barang-btn" data-bs-toggle="tab" data-bs-target="#tab-barang" type="button" role="tab">
                            <i class="bi bi-box-seam me-1 text-primary"></i> 4. Rincian Barang <span class="badge bg-primary ms-1" id="badgeItemCount">0 Item</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-catatan-btn" data-bs-toggle="tab" data-bs-target="#tab-catatan" type="button" role="tab">
                            <i class="bi bi-card-text me-1 text-primary"></i> 5. Catatan Faktur
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="fakturTabContent">
                    
                    <!-- TAB 1: DOKUMEN ASAL -->
                    <div class="tab-pane fade show active" id="tab-dokumen" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Dokumen Penerimaan Barang</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Dokumen Penerimaan (RCV)</label>
                                    <input type="text" class="form-control font-monospace bg-light fw-bold text-primary" id="displayNomorRcv" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">No. Surat Jalan Vendor (RCV)</label>
                                    <input type="text" class="form-control font-monospace bg-light" id="displayNomorSjRcv" readonly>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Referensi Dokumen</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">No. Purchase Order (PO)</label>
                                        <input type="text" class="form-control font-monospace bg-light fw-bold text-primary" id="displayNomorPo" readonly>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Penerimaan di Gudang</label>
                                        <input type="text" class="form-control bg-light" id="displayTanggalRcv" readonly>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Lokasi Site / Gudang</label>
                                        <input type="text" class="form-control bg-light" id="displayNamaSite" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-vendor')">
                                Lanjut ke Vendor &amp; Rekening Bank <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: VENDOR & REKENING BANK -->
                    <div class="tab-pane fade" id="tab-vendor" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Rekening Bank Tujuan Transfer</h6>
                        <div class="row g-3">
                            <div class="col-sm-4 position-relative" id="bankComboboxWrapper">
                                <label class="form-label small fw-semibold text-dark">Nama Bank / Metode Bayar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace fw-semibold" id="namaBank" name="nama_bank" placeholder="Pilih atau ketik bank..." autocomplete="off" onfocus="showBankDropdown()" oninput="filterBankDropdown()">
                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-3" type="button" id="btnToggleBank" onclick="toggleBankDropdown(event)" title="Pilih Bank"></button>
                                </div>
                                <!-- Dropdown List Bank -->
                                <div class="dropdown-menu shadow-sm w-100 p-1" id="bankDropdownMenu" style="max-height: 220px; overflow-y: auto; display: none; position: absolute; top: calc(100% + 2px); left: 0; z-index: 1050;">
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('CASH')">CASH</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('QRIS')">QRIS</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BRI')">BRI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('Bank Mandiri')">Bank Mandiri</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BCA')">BCA</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BNI')">BNI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BTN')">BTN</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BRIS')">BRIS</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BSI')">BSI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('CIMB Niaga')">CIMB Niaga</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('OCBC')">OCBC</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('Bank Permata')">Bank Permata</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('Danamon')">Danamon</button>
                                    <div id="noBankFound" class="text-muted small px-3 py-2 d-none">Tekan Enter atau gunakan nama yang diketik manual.</div>
                                </div>
                            </div>
                            <div class="col-sm-8">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening</label>
                                <input type="text" class="form-control font-monospace fw-bold" id="nomorRekening" name="nomor_rekening" placeholder="Nomor Rekening Vendor">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening</label>
                                <input type="text" class="form-control fw-semibold" id="atasNamaRekening" name="atas_nama_rekening" placeholder="Nama Pemilik Rekening sesuai Invoice">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-dokumen')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dokumen Asal
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-tagihan')">
                                Lanjut ke Tagihan &amp; Pajak <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: TAGIHAN & PAJAK -->
                    <div class="tab-pane fade" id="tab-tagihan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Nomor &amp; Waktu Tagihan Vendor</h6>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Nomor Faktur / Invoice Vendor <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm font-monospace fw-bold text-dark" id="nomorFakturVendor" name="nomor_faktur_vendor" placeholder="Contoh: INV-2026/08/991" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Invoice Vendor <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalFaktur" name="tanggal_faktur" onchange="calculateDueDate()" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark" id="labelNomorFakturPajak">
                                            No. Seri e-Faktur Pajak <span id="reqAsteriskPajak" class="text-danger" style="display: none;">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-sm font-monospace" id="nomorFakturPajak" name="nomor_faktur_pajak" placeholder="Contoh: 010.000-26.12345678">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal e-Faktur Pajak</label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalFakturPajak" name="tanggal_faktur_pajak">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Terima Fisik Tagihan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalTerimaFaktur" name="tanggal_terima_faktur" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Terms of Payment (TOP)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" min="0" class="form-control form-control-sm text-center fw-bold" id="termOfPayment" name="term_of_payment" value="30" oninput="calculateDueDate()">
                                            <span class="input-group-text">Hari</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Jatuh Tempo</label>
                                        <input type="text" class="form-control form-control-sm fw-bold text-danger bg-light" id="displayJatuhTempo" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Lampiran Berkas Tagihan (Opsional)</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Ganti Scan Invoice / Tagihan Vendor</label>
                                        <input type="file" class="form-control form-control-sm" id="fileFakturVendor" accept="image/*,application/pdf" onchange="handleFileBase64(this, 'hiddenFileVendorBase64')">
                                        <input type="hidden" id="hiddenFileVendorBase64" name="file_faktur_vendor_base64">
                                        <div id="fileVendorExistingContainer" class="form-text small text-primary mt-1" style="display: none;"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Ganti Scan e-Faktur Pajak</label>
                                        <input type="file" class="form-control form-control-sm" id="fileFakturPajak" accept="image/*,application/pdf" onchange="handleFileBase64(this, 'hiddenFilePajakBase64')">
                                        <input type="hidden" id="hiddenFilePajakBase64" name="file_faktur_pajak_base64">
                                        <div id="filePajakExistingContainer" class="form-text small text-primary mt-1" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-vendor')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Vendor &amp; Bank
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-barang')">
                                Lanjut ke Rincian Barang <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: RINCIAN BARANG -->
                    <div class="tab-pane fade" id="tab-barang" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Rincian Kuantitas &amp; Nilai Barang Tagihan</h6>
                        </div>
                        
                        <div class="table-responsive mb-0 border rounded-3">
                            <table class="table table-hover align-middle mb-0" id="tableMatchingItems" style="font-size: 0.86rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">No</th>
                                        <th style="width: 120px;">Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center" style="width: 95px;">Kts</th>
                                        <th class="text-center" style="width: 80px;">Satuan</th>
                                        <th class="text-end" style="width: 140px;">Harga Satuan</th>
                                        <th class="text-end" style="width: 120px;">Diskon Item</th>
                                        <th class="text-end" style="width: 150px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="matchingItemsBody">
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-1"></div> Memuat rincian barang...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-tagihan')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Tagihan &amp; Pajak
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-catatan')">
                                Lanjut ke Catatan Faktur <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 5: CATATAN FAKTUR -->
                    <div class="tab-pane fade" id="tab-catatan" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Catatan &amp; Instruksi Khusus Faktur</h6>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Catatan Faktur Pembelian (Internal &amp; Pelunasan)</label>
                            <textarea class="form-control" id="keteranganFaktur" name="keterangan" rows="6" placeholder="Tambahkan catatan..."></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-start">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-barang')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Rincian Barang
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- CARD 2: RINGKASAN FINANSIAL & PEMBAYARAN -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-3">
                <h6 class="fw-bold text-dark mb-0">Ringkasan Finansial Tagihan</h6>
            </div>
            
            <div class="card-body p-4">
                <div class="row justify-content-end">
                    <div class="col-lg-6 col-md-8">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-muted">Subtotal Kontrak PO (Ref):</span>
                                <span class="font-monospace fw-semibold" id="displaySubtotalPo">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-dark fw-semibold">Subtotal Barang Diterima (RCV):</span>
                                <span class="font-monospace fw-bold text-dark" id="displaySubtotalDiterima">Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-danger" id="rowNilaiRetur" style="display: none !important;">
                                <span>Potongan Retur PO (Credit Note):</span>
                                <span class="font-monospace fw-bold" id="displayNilaiRetur">- Rp 0</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-muted">Diskon Tambahan Faktur:</span>
                                <div class="input-group input-group-sm" style="max-width: 170px;">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control form-control-sm text-end font-monospace" id="inputDiskon" value="0" oninput="formatRupiahInputVal(this); calculateFinancials();">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small pt-2 border-top">
                                <span class="fw-bold text-dark">DPP (Dasar Pengenaan Pajak):</span>
                                <span class="font-monospace fw-bold text-dark fs-6" id="displayDpp">Rp 0</span>
                            </div>

                            <!-- ROW PPNBM (JIKA BARANG MEWAH) -->
                            <div class="d-flex justify-content-between align-items-center mb-2 small" id="rowPpnbm" style="display: none !important;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" id="labelPpnbm">PPnBM (0%):</span>
                                    <span id="badgePpnbmInclusive" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:0.68rem; display:none;">Termasuk PPnBM</span>
                                </div>
                                <span class="font-monospace fw-semibold text-warning-emphasis" id="displayNominalPpnbm">Rp 0</span>
                            </div>

                            <!-- ROW PPN (FIXED / SESUAI PO) -->
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Tarif PPN:</span>
                                    <input type="hidden" id="selectRatePajak" value="0">
                                    <span class="badge bg-light text-dark border font-monospace fw-bold px-2 py-1" id="displayRatePajak">0%</span>
                                    <span id="badgePajakInclusive" class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:0.68rem; display:none;">Termasuk PPN</span>
                                </div>
                                <span class="font-monospace fw-semibold" id="displayNominalPajak">Rp 0</span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 small">
                                <span class="text-muted">Biaya Lain-lain / Ongkir:</span>
                                <div class="input-group input-group-sm" style="max-width: 170px;">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" class="form-control form-control-sm text-end font-monospace" id="inputBiayaLain" value="0" oninput="formatRupiahInputVal(this); calculateFinancials();">
                                </div>
                            </div>

                            <!-- GRAND TOTAL -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top border-2 border-dark">
                                <span class="fw-bold text-dark fs-6">TOTAL TAGIHAN:</span>
                                <span class="font-monospace fw-bold text-primary fs-5" id="displayTotalTagihan">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Simpan Edit -->
                <div class="mt-4 pt-3 border-top d-flex justify-content-end align-items-center flex-wrap gap-2" id="actionButtonsContainer">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm fw-semibold" id="btnSaveDraft" onclick="submitEditFaktur('DRAFT')">
                        Simpan Sebagai Draft
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm fw-semibold" id="btnSaveFaktur" onclick="submitEditFaktur('BELUM DIBAYAR')">
                        Simpan Perubahan Faktur
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let initialFakturData = null;
const FAKTUR_ID = <?= $idFaktur ?>;

document.addEventListener('DOMContentLoaded', async () => {
    await loadFakturDetail();

    document.addEventListener('click', (e) => {
        const bankWrapper = document.getElementById('bankComboboxWrapper');
        if (bankWrapper && !bankWrapper.contains(e.target)) {
            hideBankDropdown();
        }
    });
});

async function loadFakturDetail() {
    try {
        const url = `${BASE_URL}/api/faktur_po/index.php?id=${FAKTUR_ID}`;
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Authorization': typeof API_TOKEN !== 'undefined' ? 'Bearer ' + API_TOKEN : ''
            },
            credentials: 'include'
        });
        const res = await response.json();

        if (res && res.success && res.data) {
            initialFakturData = res.data;
            populateForm(res.data);
            const notice = document.getElementById('loadingNotice');
            if (notice) notice.style.setProperty('display', 'none', 'important');
            const form = document.getElementById('formEditFaktur');
            if (form) form.style.display = 'block';
        } else {
            showToast(res ? res.message : 'Gagal memuat dokumen Faktur PO.', 'danger');
            document.getElementById('loadingNotice').innerHTML = `<span class="text-danger">${res ? res.message : 'Dokumen Faktur PO tidak ditemukan.'}</span>`;
        }
    } catch (e) {
        console.error('Error loadFakturDetail:', e);
        showToast('Terjadi kesalahan saat memuat data: ' + e.message, 'danger');
        document.getElementById('loadingNotice').innerHTML = `<span class="text-danger">Gagal memuat dokumen: ${e.message}</span>`;
    }
}

function populateForm(f) {
    // Status Badge & Lock Check
    const statusUpper = (f.status || '').toUpperCase();
    const isLocked = ['SEBAGIAN DIBAYAR', 'LUNAS', 'BATAL'].includes(statusUpper) || (parseFloat(f.terbayar) > 0);
    
    document.getElementById('badgeFakturStatus').textContent = f.status || '-';
    if (isLocked) {
        const lockNotice = document.getElementById('alertLockedNotice');
        if (lockNotice) {
            lockNotice.style.removeProperty('display');
            lockNotice.classList.add('d-flex');
        }
        document.getElementById('lockedStatusText').textContent = f.status;
        document.getElementById('actionButtonsContainer').style.display = 'none';
    }

    // Hidden values
    document.getElementById('selectRcv').value = f.id_rcv || '';
    document.getElementById('hiddenIdPo').value = f.id_po || '';
    document.getElementById('hiddenIdVendor').value = f.id_vendor || '';
    document.getElementById('hiddenIdSite').value = f.id_site || '';

    // Tab 1 Dokumen Asal
    document.getElementById('displayNomorRcv').value = f.nomor_rcv || '-';
    document.getElementById('displayNomorSjRcv').value = f.nomor_sj_rcv || f.nomor_sj || '-';
    document.getElementById('displayNomorPo').value = f.nomor_po || '-';
    document.getElementById('displayTanggalRcv').value = formatDate(f.tanggal_rcv_diterima || f.created_at);
    document.getElementById('displayNamaSite').value = f.nama_site || '-';

    // Tab 2 Vendor & Rekening Bank
    document.getElementById('namaBank').value = f.nama_bank || '';
    document.getElementById('nomorRekening').value = f.nomor_rekening || '';
    document.getElementById('atasNamaRekening').value = f.atas_nama_rekening || '';

    // Tab 3 Tagihan & Pajak
    document.getElementById('nomorFakturVendor').value = f.nomor_faktur_vendor || '';
    document.getElementById('tanggalFaktur').value = f.tanggal_faktur_vendor || f.tanggal_faktur || '';
    document.getElementById('nomorFakturPajak').value = f.nomor_faktur_pajak || '';
    document.getElementById('tanggalFakturPajak').value = f.tanggal_faktur_pajak || '';
    document.getElementById('tanggalTerimaFaktur').value = f.tanggal_terima_faktur_vendor || f.tanggal_terima_faktur || '';
    document.getElementById('termOfPayment').value = f.term_of_payment || 0;

    // Files Existing
    if (f.file_faktur_vendor) {
        const vendorBox = document.getElementById('fileVendorExistingContainer');
        vendorBox.innerHTML = `<i class="bi bi-file-earmark-check me-1"></i>Berkas saat ini: <a href="<?= BASE_URL ?>/uploads/faktur/${f.file_faktur_vendor}" target="_blank">${f.file_faktur_vendor}</a>`;
        vendorBox.style.display = 'block';
    }
    if (f.file_faktur_pajak) {
        const pajakBox = document.getElementById('filePajakExistingContainer');
        pajakBox.innerHTML = `<i class="bi bi-file-earmark-check me-1"></i>Berkas saat ini: <a href="<?= BASE_URL ?>/uploads/faktur/${f.file_faktur_pajak}" target="_blank">${f.file_faktur_pajak}</a>`;
        pajakBox.style.display = 'block';
    }

    // Tab 4 Items Table
    renderTableItems(f.items || []);

    // Tab 5 Catatan
    document.getElementById('keteranganFaktur').value = f.keterangan || '';

    // Summary Card Inputs
    document.getElementById('inputDiskon').value = formatRupiahNumberOnly(f.diskon || 0);
    document.getElementById('inputBiayaLain').value = formatRupiahNumberOnly(f.biaya_lain || 0);

    const ratePajak = parseInt(f.rate_pajak || 0);
    document.getElementById('selectRatePajak').value = ratePajak;
    document.getElementById('displayRatePajak').textContent = `${ratePajak}%`;

    const isTermasukPajak = (parseInt(f.total_termasuk_pajak) === 1);
    const badgeInclusive = document.getElementById('badgePajakInclusive');
    if (badgeInclusive) badgeInclusive.style.display = isTermasukPajak ? 'inline-block' : 'none';

    const ratePpnbm = parseInt(f.rate_ppnbm || 0);
    const isTermasukPpnbm = (parseInt(f.total_termasuk_PPnBM) === 1);
    const rowPpnbm = document.getElementById('rowPpnbm');
    const labelPpnbm = document.getElementById('labelPpnbm');
    const badgePpnbm = document.getElementById('badgePpnbmInclusive');
    if (ratePpnbm > 0) {
        if (rowPpnbm) rowPpnbm.style.removeProperty('display');
        if (labelPpnbm) labelPpnbm.textContent = `PPnBM (${ratePpnbm}%):`;
        if (badgePpnbm) badgePpnbm.style.display = isTermasukPpnbm ? 'inline-block' : 'none';
    } else {
        if (rowPpnbm) rowPpnbm.style.setProperty('display', 'none', 'important');
    }

    if (isLocked) {
        ['namaBank', 'nomorRekening', 'atasNamaRekening', 'nomorFakturVendor', 'tanggalFaktur', 
         'nomorFakturPajak', 'tanggalFakturPajak', 'tanggalTerimaFaktur', 'termOfPayment', 
         'fileFakturVendor', 'fileFakturPajak', 'keteranganFaktur', 'inputDiskon', 'inputBiayaLain'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') el.readOnly = true;
                if (el.type === 'file' || el.tagName === 'BUTTON') el.disabled = true;
            }
        });
        const btnBank = document.getElementById('btnToggleBank');
        if (btnBank) btnBank.disabled = true;
    }

    calculateDueDate();
    calculateFinancials();
}

function renderTableItems(items) {
    const tbody = document.getElementById('matchingItemsBody');
    document.getElementById('badgeItemCount').textContent = `${items.length} Item`;

    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada rincian barang.</td></tr>';
        return;
    }

    let html = '';
    items.forEach((it, idx) => {
        const qTagih = parseFloat(it.qty_tagih) || 0;
        const harga = parseFloat(it.harga_satuan) || 0;
        const disc = parseFloat(it.diskon_item) || 0;
        const sub = parseFloat(it.subtotal) || 0;

        html += `
        <tr>
            <td class="text-center text-muted">${idx + 1}</td>
            <td class="font-monospace text-center small fw-semibold text-secondary">${it.kode_barang || '-'}</td>
            <td>
                <div class="fw-bold text-dark">${it.nama_barang}</div>
                ${it.nama_kategori ? `<div class="small text-muted" style="font-size:0.75rem;">${it.nama_kategori}</div>` : ''}
            </td>
            <td class="text-center font-monospace fw-bold text-success fs-6">${qTagih}</td>
            <td class="text-center text-muted small">${it.satuan || it.satuan_master || 'Unit'}</td>
            <td class="text-end font-monospace">${formatRupiah(harga)}</td>
            <td class="text-end font-monospace text-muted">${disc > 0 ? formatRupiah(disc) : '-'}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(sub)}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function showBankDropdown() {
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) menu.style.display = 'block';
}

function hideBankDropdown() {
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) menu.style.display = 'none';
}

function toggleBankDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
}

function filterBankDropdown() {
    const query = (document.getElementById('namaBank').value || '').toLowerCase().trim();
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) menu.style.display = 'block';

    const items = document.querySelectorAll('.bank-opt');
    let visibleCount = 0;
    items.forEach(el => {
        const text = el.textContent.toLowerCase();
        if (text.includes(query)) {
            el.style.display = 'block';
            visibleCount++;
        } else {
            el.style.display = 'none';
        }
    });

    const noFound = document.getElementById('noBankFound');
    if (noFound) {
        if (visibleCount === 0) {
            noFound.classList.remove('d-none');
        } else {
            noFound.classList.add('d-none');
        }
    }
}

function selectBank(val) {
    document.getElementById('namaBank').value = val;
    hideBankDropdown();
}

function calculateDueDate() {
    const tgl = document.getElementById('tanggalFaktur').value;
    const top = parseInt(document.getElementById('termOfPayment').value) || 0;
    const display = document.getElementById('displayJatuhTempo');

    if (!tgl) {
        display.value = '-';
        return;
    }
    const d = new Date(tgl);
    d.setDate(d.getDate() + top);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    display.value = `${dd}/${mm}/${yyyy} (${top} Hari)`;
}

function parseInputCurrency(val) {
    if (typeof val === 'number') return val;
    if (!val) return 0;
    const cleaned = String(val).replace(/\D/g, '');
    return parseFloat(cleaned) || 0;
}

function formatRupiahNumberOnly(num) {
    return new Intl.NumberFormat('id-ID').format(Math.round(parseFloat(num) || 0));
}

function formatRupiahInputVal(input) {
    const raw = (input.value || '').replace(/\D/g, '');
    if (!raw) {
        input.value = '0';
        return;
    }
    input.value = new Intl.NumberFormat('id-ID').format(parseInt(raw, 10));
}

function calculateFinancials() {
    if (!initialFakturData) return;
    const subPo = parseFloat(initialFakturData.subtotal_po) || 0;
    const subRcv = parseFloat(initialFakturData.subtotal_diterima) || 0;
    const nilaiRetur = parseFloat(initialFakturData.nilai_retur) || 0;
    const diskon = parseInputCurrency(document.getElementById('inputDiskon').value);
    const biayaLain = parseInputCurrency(document.getElementById('inputBiayaLain').value);
    const ratePajak = parseInt(document.getElementById('selectRatePajak').value) || 0;
    const ratePpnbm = parseInt(initialFakturData.rate_ppnbm) || 0;
    const isTermasukPajak = (parseInt(initialFakturData.total_termasuk_pajak) === 1);
    const isTermasukPpnbm = (parseInt(initialFakturData.total_termasuk_PPnBM) === 1);

    const dasarSetelahDiskon = Math.max(0, subRcv - nilaiRetur - diskon);

    let divisor = 1.0;
    if (isTermasukPpnbm && ratePpnbm > 0) {
        divisor += (ratePpnbm / 100);
    }
    if (isTermasukPajak && ratePajak > 0) {
        divisor += (ratePajak / 100);
    }

    const rawDpp = dasarSetelahDiskon / divisor;
    const dpp = Math.round(rawDpp);
    const nominalPpnbm = (ratePpnbm > 0) ? Math.round(rawDpp * (ratePpnbm / 100)) : 0;
    const nominalPajak = (ratePajak > 0) ? Math.round(rawDpp * (ratePajak / 100)) : 0;

    let grandTotal = 0;
    if (divisor > 1.0) {
        grandTotal = dpp + (ratePpnbm > 0 ? nominalPpnbm : 0) + (ratePajak > 0 ? nominalPajak : 0) + biayaLain;
    } else {
        grandTotal = dpp + nominalPpnbm + nominalPajak + biayaLain;
    }

    // Tampilkan / Sembunyikan Baris PPnBM
    const rowPpnbm = document.getElementById('rowPpnbm');
    const labelPpnbm = document.getElementById('labelPpnbm');
    const badgePpnbmInclusive = document.getElementById('badgePpnbmInclusive');
    const displayPpnbm = document.getElementById('displayNominalPpnbm');
    if (ratePpnbm > 0) {
        if (rowPpnbm) rowPpnbm.style.removeProperty('display');
        if (labelPpnbm) labelPpnbm.textContent = `PPnBM (${ratePpnbm}%):`;
        if (badgePpnbmInclusive) badgePpnbmInclusive.style.display = isTermasukPpnbm ? 'inline-block' : 'none';
        if (displayPpnbm) displayPpnbm.textContent = formatRupiah(nominalPpnbm);
    } else {
        if (rowPpnbm) rowPpnbm.style.setProperty('display', 'none', 'important');
    }

    // Tampilkan / Sembunyikan Baris Retur
    const rowRetur = document.getElementById('rowNilaiRetur');
    if (rowRetur) {
        if (nilaiRetur > 0) {
            rowRetur.style.removeProperty('display');
            document.getElementById('displayNilaiRetur').textContent = `- ${formatRupiah(nilaiRetur)}`;
        } else {
            rowRetur.style.setProperty('display', 'none', 'important');
        }
    }

    document.getElementById('displaySubtotalPo').textContent = formatRupiah(subPo);
    document.getElementById('displaySubtotalDiterima').textContent = formatRupiah(subRcv);
    document.getElementById('displayDpp').textContent = formatRupiah(dpp);
    document.getElementById('displayNominalPajak').textContent = formatRupiah(nominalPajak);
    document.getElementById('displayTotalTagihan').textContent = formatRupiah(grandTotal);

    // Update indikator wajib isi No. Seri e-Faktur Pajak jika ada PPN
    const asterisk = document.getElementById('reqAsteriskPajak');
    if (asterisk) {
        asterisk.style.display = (nominalPajak > 0 || ratePajak > 0) ? 'inline' : 'none';
    }
}

function goToTab(tabId) {
    const btn = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (btn) {
        const tab = new bootstrap.Tab(btn);
        tab.show();
    }
}

function handleFileBase64(input, targetHiddenId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetHiddenId).value = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        document.getElementById(targetHiddenId).value = '';
    }
}

async function submitEditFaktur(statusDokumen) {
    const nomorFakturVendor = document.getElementById('nomorFakturVendor').value.trim();
    if (!nomorFakturVendor) {
        showToast('Nomor Faktur / Invoice Vendor wajib diisi pada Tab 3.', 'warning');
        goToTab('tab-tagihan');
        document.getElementById('nomorFakturVendor').focus();
        return;
    }

    const diskon = parseInputCurrency(document.getElementById('inputDiskon').value);
    const biayaLain = parseInputCurrency(document.getElementById('inputBiayaLain').value);
    const ratePajak = parseInt(document.getElementById('selectRatePajak').value) || 0;
    const ratePpnbm = parseInt(initialFakturData.rate_ppnbm) || 0;
    const subRcv = parseFloat(initialFakturData.subtotal_diterima) || 0;
    const subPo = parseFloat(initialFakturData.subtotal_po) || 0;
    const nilaiRetur = parseFloat(initialFakturData.nilai_retur) || 0;
    const isTermasukPajak = (parseInt(initialFakturData.total_termasuk_pajak) === 1);
    const isTermasukPpnbm = (parseInt(initialFakturData.total_termasuk_PPnBM) === 1);

    const dasarSetelahDiskon = Math.max(0, subRcv - nilaiRetur - diskon);

    let divisor = 1.0;
    if (isTermasukPpnbm && ratePpnbm > 0) {
        divisor += (ratePpnbm / 100);
    }
    if (isTermasukPajak && ratePajak > 0) {
        divisor += (ratePajak / 100);
    }

    const rawDpp = dasarSetelahDiskon / divisor;
    const dpp = Math.round(rawDpp);
    const nominalPpnbm = (ratePpnbm > 0) ? Math.round(rawDpp * (ratePpnbm / 100)) : 0;
    const nominalPajak = (ratePajak > 0) ? Math.round(rawDpp * (ratePajak / 100)) : 0;

    // Validasi Wajib: Jika ada PPN (nominalPajak > 0 atau ratePajak > 0), No. Seri e-Faktur Pajak wajib diisi
    const nomorFakturPajak = document.getElementById('nomorFakturPajak').value.trim();
    if ((nominalPajak > 0 || ratePajak > 0) && !nomorFakturPajak) {
        showToast('Transaksi memiliki PPN. Mohon isi No. Seri e-Faktur Pajak pada Tab 3.', 'warning');
        goToTab('tab-tagihan');
        document.getElementById('nomorFakturPajak').focus();
        return;
    }

    let grandTotal = 0;
    if (divisor > 1.0) {
        grandTotal = dpp + (ratePpnbm > 0 ? nominalPpnbm : 0) + (ratePajak > 0 ? nominalPajak : 0) + biayaLain;
    } else {
        grandTotal = dpp + nominalPpnbm + nominalPajak + biayaLain;
    }

    const payload = {
        id_faktur: parseInt(document.getElementById('editIdFaktur').value),
        id_rcv: parseInt(document.getElementById('selectRcv').value),
        id_po: parseInt(document.getElementById('hiddenIdPo').value),
        id_vendor: parseInt(document.getElementById('hiddenIdVendor').value),
        id_site: parseInt(document.getElementById('hiddenIdSite').value),
        nomor_faktur_vendor: nomorFakturVendor,
        nomor_faktur_pajak: nomorFakturPajak,
        tanggal_faktur_pajak: document.getElementById('tanggalFakturPajak').value || null,
        tanggal_faktur: document.getElementById('tanggalFaktur').value,
        tanggal_terima_faktur: document.getElementById('tanggalTerimaFaktur').value,
        term_of_payment: parseInt(document.getElementById('termOfPayment').value) || 0,
        nama_bank: document.getElementById('namaBank').value.trim(),
        nomor_rekening: document.getElementById('nomorRekening').value.trim(),
        atas_nama_rekening: document.getElementById('atasNamaRekening').value.trim(),
        subtotal_po: subPo,
        subtotal_diterima: subRcv,
        nilai_retur: nilaiRetur,
        diskon: diskon,
        dpp: dpp,
        rate_pajak: ratePajak,
        nominal_pajak: nominalPajak,
        rate_ppnbm: ratePpnbm,
        nominal_ppnbm: nominalPpnbm,
        biaya_lain: biayaLain,
        total_tagihan: grandTotal,
        status: statusDokumen,
        keterangan: document.getElementById('keteranganFaktur').value.trim(),
        file_faktur_vendor_base64: document.getElementById('hiddenFileVendorBase64').value,
        file_faktur_pajak_base64: document.getElementById('hiddenFilePajakBase64').value,
        items: (initialFakturData.items || []).map(it => ({
            id_barang: it.id_barang,
            qty_po: parseFloat(it.qty_po) || 0,
            qty_rcv: parseFloat(it.qty_rcv) || 0,
            qty_retur: parseFloat(it.qty_retur) || 0,
            qty_tagih: parseFloat(it.qty_tagih) || 0,
            satuan: it.satuan || it.satuan_master || 'Unit',
            harga_satuan: parseFloat(it.harga_satuan) || 0,
            diskon_item: parseFloat(it.diskon_item) || 0,
            subtotal: parseFloat(it.subtotal) || 0,
            keterangan: it.keterangan || ''
        }))
    };

    const btnSave = document.getElementById('btnSaveFaktur');
    const btnDraft = document.getElementById('btnSaveDraft');
    if (btnSave) btnSave.disabled = true;
    if (btnDraft) btnDraft.disabled = true;

    try {
        const res = await fetch('<?= BASE_URL ?>/api/faktur_po/index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result && result.success) {
            showToast(result.message || 'Perubahan Faktur PO berhasil disimpan!', 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/faktur_po/index.php';
            }, 1200);
        } else {
            showToast(result.message || 'Gagal menyimpan perubahan Faktur PO.', 'danger');
            if (btnSave) btnSave.disabled = false;
            if (btnDraft) btnDraft.disabled = false;
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
        if (btnSave) btnSave.disabled = false;
        if (btnDraft) btnDraft.disabled = false;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${String(d.getDate()).padStart(2, '0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
