<?php
/**
 * Halaman Buat Dokumen Faktur Purchase Order (Faktur Pembelian)
 * Path: admin/pages/faktur_po/create.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Buat Faktur PO';
$pageHeading = 'Formulir Faktur Pembelian';

// Include Layout Components
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
.input-group-sm > .input-group-text,
.rcv-custom-select {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 0.875rem !important;
}
textarea.form-control {
    height: auto !important;
    min-height: 100px !important;
}
.rcv-option-item {
    transition: background-color 0.15s ease, transform 0.1s ease;
    border-radius: 6px;
    cursor: pointer !important;
}
.rcv-option-item:hover {
    background-color: #e9ecef !important;
}
.rcv-option-item:hover strong.text-primary {
    color: #0a58ca !important;
    text-decoration: underline;
}
.rcv-custom-select {
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.rcv-custom-select:hover {
    border-color: #0d6efd !important;
    background-color: #f8fafc !important;
}
.transition-chevron {
    transition: transform 0.2s ease;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Faktur Purchase Order (PO)</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/faktur_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <form id="formFaktur" onsubmit="event.preventDefault();">
        <input type="hidden" id="selectRcv" name="id_rcv" required>
        <input type="hidden" id="hiddenIdPo" name="id_po">
        <input type="hidden" id="hiddenIdVendor" name="id_vendor">
        <input type="hidden" id="hiddenIdSite" name="id_site">
        <input type="hidden" id="hiddenIdPoRetur" name="id_po_retur">

        <!-- CARD 1: 5 TAB KONTEN DOKUMEN, VENDOR, TAGIHAN, BARANG & CATATAN -->
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
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Pilih Dokumen Penerimaan Barang</h6>
                                
                                <!-- Nomor Faktur Sistem (Readonly & Dinamis) -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nomor Faktur (Sistem) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control font-monospace fw-bold text-primary bg-light" id="inputNomorFaktur" readonly required placeholder="Memuat nomor Faktur...">
                                        <button type="button" class="btn btn-outline-secondary" onclick="fetchNextFakturNumber()" title="Generate Ulang Nomor">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3 position-relative" id="rcvSearchableWrapper">
                                    <label class="form-label small fw-semibold text-dark">
                                        Dokumen Penerimaan (RCV) <span class="text-danger">*</span>
                                    </label>

                                    <!-- Trigger Search Box (Searchable UI) -->
                                    <div class="rcv-custom-select d-flex align-items-center justify-content-between p-2 px-3 border rounded-3 bg-white cursor-pointer shadow-sm" id="rcvTriggerBox" onclick="toggleRcvDropdown(event)" style="cursor: pointer;">
                                        <div id="rcvSelectedDisplay" class="text-truncate me-2">
                                            <span class="text-muted"><i class="bi bi-search me-2 text-primary"></i>Pilih Dokumen Penerimaan Belum Difakturkan (No. RCV, PO, Vendor)...</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 me-1" id="rcvClearBtn" onclick="clearRcvSelection(event)" style="display: none;" title="Hapus Pilihan">
                                                <i class="bi bi-x-circle-fill fs-6"></i>
                                            </button>
                                            <i class="bi bi-chevron-down text-muted small transition-chevron" id="rcvChevronIcon"></i>
                                        </div>
                                    </div>

                                    

                                    <!-- Searchable Dropdown Menu -->
                                    <div class="rcv-dropdown-menu shadow-lg border rounded-3 p-2 bg-white" id="rcvDropdownMenu" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050;">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control form-control-sm border-start-0" id="rcvSearchInput" placeholder="Ketik No. RCV, No. PO, Vendor, atau SJ..." autocomplete="off" oninput="filterRcvList()">
                                        </div>
                                        <div class="overflow-auto" id="rcvOptionsContainer" style="max-height: 260px;">
                                            <!-- Populated dynamically by JS -->
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">No. Surat Jalan Vendor (RCV)</label>
                                    <input type="text" class="form-control form-control-sm font-monospace bg-light" id="displayNoSj"  readonly>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Referensi Dokumen</h6>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">No. Purchase Order (PO)</label>
                                        <input type="text" class="form-control form-control-sm font-monospace bg-light fw-bold text-primary" id="displayNoPo"  readonly>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Penerimaan di Gudang</label>
                                        <input type="text" class="form-control form-control-sm bg-light" id="displayTglDiterima"  readonly>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Lokasi Site / Gudang</label>
                                        <input type="text" class="form-control form-control-sm bg-light" id="displayNamaSite"  readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-vendor')">
                                Lanjut ke Vendor &amp; Rekening Bank
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
                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-3" type="button" onclick="toggleBankDropdown(event)" title="Pilih Bank"></button>
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
                                Kembali ke Dokumen Asal
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-tagihan')">
                                Lanjut ke Tagihan &amp; Pajak
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
                                        <div class="form-text small text-muted">Nomor invoice fisik resmi dari vendor.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Invoice Vendor <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalFaktur" name="tanggal_faktur" value="<?= date('Y-m-d') ?>" onchange="calculateDueDate(); fetchNextFakturNumber();" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark" id="labelNomorFakturPajak">
                                            No. Seri e-Faktur Pajak <span id="reqAsteriskPajak" class="text-danger" style="display: none;">*</span>
                                        </label>
                                        <input type="text" class="form-control form-control-sm font-monospace" id="nomorFakturPajak" name="nomor_faktur_pajak" placeholder="Contoh: 010.000-26.12345678">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal e-Faktur Pajak</label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalFakturPajak" name="tanggal_faktur_pajak" value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Terima Fisik Tagihan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalTerimaFaktur" name="tanggal_terima_faktur" value="<?= date('Y-m-d') ?>" required>
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
                                        <label class="form-label small fw-semibold text-dark">Scan Invoice / Tagihan Vendor</label>
                                        <input type="file" class="form-control form-control-sm" id="fileFakturVendor" accept="image/*,application/pdf" onchange="handleFileBase64(this, 'hiddenFileVendorBase64')">
                                        <input type="hidden" id="hiddenFileVendorBase64" name="file_faktur_vendor_base64">
                                        <div class="form-text small text-muted">Format berkas didukung: PDF, JPG, PNG (Maks. 5MB).</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Scan e-Faktur Pajak</label>
                                        <input type="file" class="form-control form-control-sm" id="fileFakturPajak" accept="image/*,application/pdf" onchange="handleFileBase64(this, 'hiddenFilePajakBase64')">
                                        <input type="hidden" id="hiddenFilePajakBase64" name="file_faktur_pajak_base64">
                                        <div class="form-text small text-muted">Lampiran faktur pajak resmi untuk klaim Pajak Masukan.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-vendor')">
                                Kembali ke Vendor &amp; Bank
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-barang')">
                                Lanjut ke Rincian Barang
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: RINCIAN BARANG -->
                    <div class="tab-pane fade" id="tab-barang" role="tabpanel">
                        
                        <!-- Banner Status Retur PO (Jika Ada) -->
                        <div id="returAlertContainer" style="display: none;"></div>

                        <!-- Tabel Rincian Kuantitas & Nilai Barang -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Rincian Kuantitas &amp; Nilai Barang Tagihan</h6>
                        </div>
                        
                        <div class="table-responsive mb-0 border rounded-3">
                            <table class="table table-hover align-middle mb-0" id="tableMatchingItems" style="font-size: 0.86rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">No</th>
                                        <th class="text-center" style="width: 60px;">Foto</th>
                                        <th style="width: 120px;">Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center" style="width: 85px;">Kts Tagih</th>
                                        <th class="text-center" style="width: 75px;">Satuan</th>
                                        <th class="text-end" style="width: 130px;">Harga Satuan</th>
                                        <th class="text-end" style="width: 110px;">Diskon Item</th>
                                        <th class="text-end" style="width: 140px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="matchingItemsBody">
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            Silakan pilih Dokumen Penerimaan (RCV) pada Tab 1 untuk memuat rincian barang.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-tagihan')">
                                Kembali ke Tagihan &amp; Pajak
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-catatan')">
                                Lanjut ke Catatan Faktur
                            </button>
                        </div>
                    </div>

                    <!-- TAB 5: CATATAN FAKTUR -->
                    <div class="tab-pane fade" id="tab-catatan" role="tabpanel">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Catatan Faktur Pembelian (Internal &amp; Pelunasan)</label>
                            <textarea class="form-control" id="keteranganFaktur" name="keterangan" rows="6" ></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-start">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-barang')">
                                Kembali ke Rincian Barang
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- CARD 2: RINGKASAN FINANSIAL & PEMBAYARAN (SELALU TERLIHAT DI BAWAH) -->
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

                <!-- Tombol Aksi Simpan -->
                <div class="mt-4 pt-3 border-top d-flex justify-content-end align-items-center flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm fw-semibold" id="btnSaveDraft" onclick="submitFaktur('DRAFT')">
                        <i class="bi bi-save me-1"></i> Simpan Draft
                    </button>
                    <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm fw-semibold" id="btnSaveFaktur" onclick="submitFaktur('BELUM DIBAYAR')">
                        <i class="bi bi-check2-circle me-1"></i> Terbitkan Faktur
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let rawRcvData = [];
let active3WayData = null;

document.addEventListener('DOMContentLoaded', async () => {
    await fetchNextFakturNumber();
    await loadRcvReadyList();
    calculateDueDate();

    // Tutup dropdown RCV & Bank jika klik di luar
    document.addEventListener('click', (e) => {
        const wrapper = document.getElementById('rcvSearchableWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            closeRcvDropdown();
        }
        const bankWrapper = document.getElementById('bankComboboxWrapper');
        if (bankWrapper && !bankWrapper.contains(e.target)) {
            hideBankDropdown();
        }
    });
});

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
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
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

function toggleRcvDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('rcvDropdownMenu');
    if (!menu) return;
    if (menu.style.display === 'block') {
        closeRcvDropdown();
    } else {
        openRcvDropdown();
    }
}

function openRcvDropdown() {
    const menu = document.getElementById('rcvDropdownMenu');
    const chevron = document.getElementById('rcvChevronIcon');
    if (menu) menu.style.display = 'block';
    if (chevron) chevron.style.transform = 'rotate(180deg)';
    setTimeout(() => {
        const searchInput = document.getElementById('rcvSearchInput');
        if (searchInput) {
            searchInput.value = '';
            filterRcvList();
            searchInput.focus();
        }
    }, 50);
}

function closeRcvDropdown() {
    const menu = document.getElementById('rcvDropdownMenu');
    const chevron = document.getElementById('rcvChevronIcon');
    if (menu) menu.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
}

function populateRcvDropdownOptions(items) {
    const container = document.getElementById('rcvOptionsContainer');
    if (!container) return;
    if (!items || items.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted small">Tidak ada dokumen RCV yang cocok.</div>';
        return;
    }

    let html = '';
    items.forEach(it => {
        const tglDisplay = formatDate(it.tanggal_diterima || it.tanggal_rcv);
        html += `
        <div class="rcv-item p-2 border-bottom hover-bg-light" 
             style="cursor: pointer;"
             onclick="selectRcvDoc(${it.id_rcv})">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <strong class="text-primary font-monospace small">${it.nomor_rcv}</strong>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0" style="font-size: 0.65rem;">Belum Difakturkan</span>
                </div>
                <span class="badge bg-light text-secondary border px-2 py-1 small fw-normal">${tglDisplay}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <div class="small text-dark fw-semibold">${it.nama_vendor}</div>
                <div class="small text-muted font-monospace" style="font-size: 0.76rem;">PO: ${it.nomor_po}</div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

async function fetchNextFakturNumber() {
    const tgl = document.getElementById('tanggalFaktur') ? document.getElementById('tanggalFaktur').value : '';
    const res = await apiRequest(`/api/faktur_po/get_next_number.php?tanggal=${encodeURIComponent(tgl || '')}`);
    if (res && res.success && res.data && res.data.nomor_faktur) {
        document.getElementById('inputNomorFaktur').value = res.data.nomor_faktur;
    }
}

async function loadRcvReadyList() {
    try {
        const res = await apiRequest('/api/faktur_po/lookup_po_rcv.php');
        if (res && res.success && Array.isArray(res.data)) {
            rawRcvData = res.data;
            populateRcvDropdownOptions(rawRcvData);
        } else {
            rawRcvData = [];
            populateRcvDropdownOptions([]);
        }
    } catch (e) {
        rawRcvData = [];
        populateRcvDropdownOptions([]);
    }
}

function filterRcvList() {
    const query = document.getElementById('rcvSearchInput').value.toLowerCase().trim();
    if (!query) {
        populateRcvDropdownOptions(rawRcvData);
        return;
    }
    const filtered = rawRcvData.filter(it => {
        return (it.nomor_rcv && it.nomor_rcv.toLowerCase().includes(query)) ||
               (it.nomor_po && it.nomor_po.toLowerCase().includes(query)) ||
               (it.nama_vendor && it.nama_vendor.toLowerCase().includes(query)) ||
               (it.nomor_sj && it.nomor_sj.toLowerCase().includes(query));
    });
    populateRcvDropdownOptions(filtered);
}

async function selectRcvDoc(idRcv) {
    closeRcvDropdown();
    showToast('Memuat rincian barang...', 'info');

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/faktur_po/lookup_po_rcv.php?id_rcv=${idRcv}`);
        const result = await res.json();

        if (result && result.success && result.data) {
            active3WayData = result.data;
            apply3WayDataToForm(result.data);
            showToast('Dokumen berhasil dimuat!', 'success');
        } else {
            showToast(result.message || 'Gagal memuat data dokumen.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
    }
}

function apply3WayDataToForm(d) {
    document.getElementById('selectRcv').value = d.id_rcv;
    document.getElementById('hiddenIdPo').value = d.id_po;
    document.getElementById('hiddenIdVendor').value = d.id_vendor;
    document.getElementById('hiddenIdSite').value = d.id_site;
    document.getElementById('hiddenIdPoRetur').value = d.retur_doc ? d.retur_doc.id_po_retur : '';

    // Display trigger box
    document.getElementById('rcvSelectedDisplay').innerHTML = `
        <strong class="text-primary font-monospace">${d.nomor_rcv}</strong>
        <span class="text-muted mx-1">|</span>
        <span class="fw-semibold text-dark">${d.nama_vendor}</span>
        <span class="text-muted mx-1">|</span>
        <span class="font-monospace small text-muted">PO: ${d.nomor_po}</span>
    `;
    document.getElementById('rcvClearBtn').style.display = 'inline-block';

    // Tab 1 Info Dokumen
    if (document.getElementById('displayNoPo')) document.getElementById('displayNoPo').value = d.nomor_po || '-';
    if (document.getElementById('displayNoSj')) document.getElementById('displayNoSj').value = d.nomor_sj || '-';
    if (document.getElementById('displayTglDiterima')) document.getElementById('displayTglDiterima').value = formatDate(d.tanggal_diterima);
    if (document.getElementById('displayNamaSite')) document.getElementById('displayNamaSite').value = d.nama_site || '-';

    // Tab 2 Vendor & Rekening Bank
    if (document.getElementById('displayNamaVendor')) document.getElementById('displayNamaVendor').value = d.nama_vendor || '-';
    if (document.getElementById('namaBank')) document.getElementById('namaBank').value = d.nama_bank || '';
    if (document.getElementById('nomorRekening')) document.getElementById('nomorRekening').value = d.nomor_rekening || '';
    if (document.getElementById('atasNamaRekening')) document.getElementById('atasNamaRekening').value = d.nama_vendor || '';

    // Tab 3 TOP & Pajak
    if (d.term_of_payment !== undefined && d.term_of_payment !== null) {
        document.getElementById('termOfPayment').value = d.term_of_payment;
    }
    
    // Auto sync PPN dan PPnBM dari PO
    const ratePpnbm = parseInt(d.rate_ppnbm || 0);
    let lockedRatePajak = 0;
    if (d.rate_pajak !== undefined && d.rate_pajak !== null) {
        lockedRatePajak = parseInt(d.rate_pajak) || 0;
    } else {
        lockedRatePajak = (ratePpnbm > 0) ? 12 : 11;
    }
    document.getElementById('selectRatePajak').value = lockedRatePajak;
    if (document.getElementById('displayRatePajak')) {
        document.getElementById('displayRatePajak').textContent = `${lockedRatePajak}%`;
    }
    
    // Status Inklusif
    const isTermasukPajak = (parseInt(d.total_termasuk_pajak) === 1);
    const badgeInclusive = document.getElementById('badgePajakInclusive');
    if (badgeInclusive) {
        badgeInclusive.style.display = isTermasukPajak ? 'inline-block' : 'none';
    }

    const isTermasukPpnbm = (parseInt(d.total_termasuk_PPnBM) === 1);
    const badgePpnbmInclusive = document.getElementById('badgePpnbmInclusive');
    if (badgePpnbmInclusive) {
        badgePpnbmInclusive.style.display = isTermasukPpnbm ? 'inline-block' : 'none';
    }

    calculateDueDate();

    // Tab 4 Table Items
    renderMatchingTable(d);

    // Tab 4 Retur Alert
    renderReturAlert(d.retur_doc);

    // Validasi Nomor e-Faktur Pajak wajib jika ada PPN
    const asterisk = document.getElementById('reqAsteriskPajak');
    if (asterisk) {
        asterisk.style.display = (lockedRatePajak > 0) ? 'inline' : 'none';
    }
    
    // Update Ringkasan Finansial Tagihan
    calculateFinancials();
}

function clearRcvSelection(e) {
    if (e) e.stopPropagation();
    document.getElementById('selectRcv').value = '';
    document.getElementById('hiddenIdPo').value = '';
    document.getElementById('hiddenIdVendor').value = '';
    document.getElementById('hiddenIdSite').value = '';
    document.getElementById('hiddenIdPoRetur').value = '';

    document.getElementById('rcvSelectedDisplay').innerHTML = '<span class="text-muted"><i class="bi bi-search me-2 text-primary"></i>Cari Dokumen Penerimaan (No. RCV, PO, Vendor)...</span>';
    document.getElementById('rcvClearBtn').style.display = 'none';

    if (document.getElementById('displayNoPo')) document.getElementById('displayNoPo').value = '';
    if (document.getElementById('displayNoSj')) document.getElementById('displayNoSj').value = '';
    if (document.getElementById('displayTglDiterima')) document.getElementById('displayTglDiterima').value = '';
    if (document.getElementById('displayNamaSite')) document.getElementById('displayNamaSite').value = '';

    if (document.getElementById('displayNamaVendor')) document.getElementById('displayNamaVendor').value = '';
    if (document.getElementById('namaBank')) document.getElementById('namaBank').value = '';
    if (document.getElementById('nomorRekening')) document.getElementById('nomorRekening').value = '';
    if (document.getElementById('atasNamaRekening')) document.getElementById('atasNamaRekening').value = '';

    document.getElementById('selectRatePajak').value = '0';
    if (document.getElementById('displayRatePajak')) {
        document.getElementById('displayRatePajak').textContent = '0%';
    }
    const badgeInclusive = document.getElementById('badgePajakInclusive');
    if (badgeInclusive) badgeInclusive.style.display = 'none';

    active3WayData = null;
    document.getElementById('matchingItemsBody').innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-4 text-muted">
                <i class="bi bi-box-seam fs-3 d-block mb-1 text-secondary"></i>
                Silakan pilih Dokumen Penerimaan (RCV) pada Tab 1 untuk memuat rincian barang.
            </td>
        </tr>`;
    document.getElementById('badgeItemCount').textContent = '0 Item';
    document.getElementById('returAlertContainer').style.display = 'none';
    calculateFinancials();
}

function renderMatchingTable(d) {
    const tbody = document.getElementById('matchingItemsBody');
    const items = d.items || [];
    const badgeCount = document.getElementById('badgeItemCount');
    if (badgeCount) badgeCount.textContent = `${items.length} Item`;

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-3 text-muted">Tidak ada rincian barang.</td></tr>';
        return;
    }

    let html = '';
    items.forEach((it, idx) => {
        const qTagih = parseFloat(it.qty_tagih) || 0;
        const harga = parseFloat(it.harga_satuan) || 0;
        const disc = parseFloat(it.diskon_item) || 0;
        const sub = parseFloat(it.subtotal) || 0;

        let fotoHtml = `<div class="rounded bg-light border d-inline-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;"><i class="bi bi-image" style="font-size: 0.9rem;"></i></div>`;
        if (it.foto1) {
            fotoHtml = `<img src="<?= BASE_URL ?>/uploads/barang/${it.foto1}" alt="Foto" class="rounded border" style="width: 40px; height: 40px; object-fit: cover;">`;
        }

        const isPpnbmItem = (parseInt(it.PPnBM) === 1 || parseFloat(it.rate_PPnBM) > 0);
        const ppnbmRateVal = parseFloat(it.rate_PPnBM || d.rate_ppnbm || 0);

        html += `
        <tr>
            <td class="text-center text-muted">${idx + 1}</td>
            <td class="text-center p-1">${fotoHtml}</td>
            <td class="font-monospace text-center small fw-semibold text-secondary">${it.kode_barang || '-'}</td>
            <td>
                <div class="fw-bold text-dark">${it.nama_barang}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    ${it.nama_kategori ? `<span class="badge bg-light text-secondary border" style="font-size:0.68rem;">${it.nama_kategori}</span>` : ''}
                    ${it.nama_merk ? `<span class="badge bg-light text-muted border" style="font-size:0.68rem;">${it.nama_merk}</span>` : ''}
                    ${isPpnbmItem ? `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.68rem;"><i class="bi bi-gem me-1"></i>PPnBM ${ppnbmRateVal}%</span>` : ''}
                </div>
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

function renderReturAlert(retDoc) {
    const box = document.getElementById('returAlertContainer');
    const rowRetur = document.getElementById('rowNilaiRetur');
    if (!retDoc) {
        box.style.display = 'none';
        if (rowRetur) rowRetur.style.setProperty('display', 'none', 'important');
        return;
    }

    box.style.display = 'block';
    const isPotongTagihan = ((intVal(retDoc.kompensasi)) === 0);

    if (isPotongTagihan) {
        if (rowRetur) rowRetur.style.removeProperty('display');
        box.innerHTML = `
            <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center mb-3 py-2 px-3 small">
                <i class="bi bi-exclamation-triangle-fill text-warning-emphasis me-2 fs-5"></i>
                <div>
                    Dokumen ini memiliki <strong>Retur PO (${retDoc.nomor_po_retur})</strong> dengan skema <strong>Potong Tagihan (Credit Note)</strong>. Nilai retur otomatis memotong kuantitas tagih dan mengurangi DPP faktur.
                </div>
            </div>`;
    } else {
        if (rowRetur) rowRetur.style.setProperty('display', 'none', 'important');
        box.innerHTML = `
            <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center mb-3 py-2 px-3 small">
                <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>
                <div>
                    Dokumen ini memiliki <strong>Retur PO (${retDoc.nomor_po_retur})</strong> dengan skema <strong>Tukar Unit</strong>. Unit pengganti baru telah tuntas diterima di gudang, sehingga tagihan tetap dihitung penuh.
                </div>
            </div>`;
    }
}

function intVal(val) {
    return parseInt(val) || 0;
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

function formatRupiahInputVal(input) {
    const raw = (input.value || '').replace(/\D/g, '');
    if (!raw) {
        input.value = '0';
        return;
    }
    input.value = new Intl.NumberFormat('id-ID').format(parseInt(raw, 10));
}

function calculateFinancials() {
    if (!active3WayData) {
        document.getElementById('displaySubtotalPo').textContent = 'Rp 0';
        document.getElementById('displaySubtotalDiterima').textContent = 'Rp 0';
        document.getElementById('displayNilaiRetur').textContent = '- Rp 0';
        document.getElementById('displayDpp').textContent = 'Rp 0';
        document.getElementById('displayNominalPpnbm').textContent = 'Rp 0';
        document.getElementById('displayNominalPajak').textContent = 'Rp 0';
        document.getElementById('displayTotalTagihan').textContent = 'Rp 0';
        const rowPpnbm = document.getElementById('rowPpnbm');
        if (rowPpnbm) rowPpnbm.style.setProperty('display', 'none', 'important');
        return;
    }

    const subPo = parseFloat(active3WayData.subtotal_po) || 0;
    const subRcv = parseFloat(active3WayData.subtotal_diterima) || 0;
    const nilaiRetur = parseFloat(active3WayData.nilai_retur) || 0;
    const diskon = parseInputCurrency(document.getElementById('inputDiskon').value);
    const biayaLain = parseInputCurrency(document.getElementById('inputBiayaLain').value);
    const ratePajak = parseInt(document.getElementById('selectRatePajak').value) || 0;
    const ratePpnbm = parseInt(active3WayData.rate_ppnbm) || 0;
    const isTermasukPajak = (parseInt(active3WayData.total_termasuk_pajak) === 1);
    const isTermasukPpnbm = (parseInt(active3WayData.total_termasuk_PPnBM) === 1);

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
    if (ratePpnbm > 0) {
        if (rowPpnbm) rowPpnbm.style.removeProperty('display');
        if (labelPpnbm) labelPpnbm.textContent = `PPnBM (${ratePpnbm}%):`;
        if (badgePpnbmInclusive) badgePpnbmInclusive.style.display = isTermasukPpnbm ? 'inline-block' : 'none';
    } else {
        if (rowPpnbm) rowPpnbm.style.setProperty('display', 'none', 'important');
    }

    document.getElementById('displaySubtotalPo').textContent = formatRupiah(subPo);
    document.getElementById('displaySubtotalDiterima').textContent = formatRupiah(subRcv);
    document.getElementById('displayNilaiRetur').textContent = `- ${formatRupiah(nilaiRetur)}`;
    document.getElementById('displayDpp').textContent = formatRupiah(dpp);
    document.getElementById('displayNominalPpnbm').textContent = formatRupiah(nominalPpnbm);
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

async function submitFaktur(statusDokumen) {
    const idRcv = document.getElementById('selectRcv').value;
    if (!idRcv) {
        showToast('Silakan pilih Dokumen Penerimaan (RCV) terlebih dahulu pada Tab 1.', 'warning');
        goToTab('tab-dokumen');
        return;
    }

    const nomorFakturVendor = document.getElementById('nomorFakturVendor').value.trim();
    if (!nomorFakturVendor) {
        showToast('Nomor Faktur / Invoice Vendor wajib diisi pada Tab 3.', 'warning');
        goToTab('tab-tagihan');
        document.getElementById('nomorFakturVendor').focus();
        return;
    }

    if (!active3WayData || !active3WayData.items || active3WayData.items.length === 0) {
        showToast('Dokumen tidak memiliki rincian barang untuk difakturkan.', 'danger');
        goToTab('tab-barang');
        return;
    }

    const diskon = parseInputCurrency(document.getElementById('inputDiskon').value);
    const biayaLain = parseInputCurrency(document.getElementById('inputBiayaLain').value);
    const ratePajak = parseInt(document.getElementById('selectRatePajak').value) || 0;
    const ratePpnbm = parseInt(active3WayData.rate_ppnbm) || 0;
    const subRcv = parseFloat(active3WayData.subtotal_diterima) || 0;
    const subPo = parseFloat(active3WayData.subtotal_po) || 0;
    const nilaiRetur = parseFloat(active3WayData.nilai_retur) || 0;
    const isTermasukPajak = (parseInt(active3WayData.total_termasuk_pajak) === 1);
    const isTermasukPpnbm = (parseInt(active3WayData.total_termasuk_PPnBM) === 1);

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

    const nomorFakturSistem = (document.getElementById('inputNomorFaktur') ? document.getElementById('inputNomorFaktur').value : '').trim();
    const payload = {
        nomor_faktur: nomorFakturSistem,
        id_rcv: parseInt(idRcv),
        id_po: parseInt(document.getElementById('hiddenIdPo').value),
        id_vendor: parseInt(document.getElementById('hiddenIdVendor').value),
        id_site: parseInt(document.getElementById('hiddenIdSite').value),
        id_po_retur: document.getElementById('hiddenIdPoRetur').value ? parseInt(document.getElementById('hiddenIdPoRetur').value) : null,
        nomor_faktur_vendor: nomorFakturVendor,
        nomor_faktur_pajak: document.getElementById('nomorFakturPajak').value.trim(),
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
        items: active3WayData.items.map(it => ({
            id_barang: it.id_barang,
            qty_po: parseFloat(it.qty_po) || 0,
            qty_rcv: parseFloat(it.qty_rcv) || 0,
            qty_retur: parseFloat(it.qty_retur) || 0,
            qty_tagih: parseFloat(it.qty_tagih) || 0,
            satuan: it.satuan || it.satuan_master || 'Unit',
            harga_satuan: parseFloat(it.harga_satuan) || 0,
            diskon_item: parseFloat(it.diskon_item) || 0,
            subtotal: parseFloat(it.subtotal) || 0,
            keterangan: ''
        }))
    };

    const btnSave = document.getElementById('btnSaveFaktur');
    const btnDraft = document.getElementById('btnSaveDraft');
    btnSave.disabled = true;
    btnDraft.disabled = true;

    try {
        const res = await fetch('<?= BASE_URL ?>/api/faktur_po/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result && result.success) {
            showToast(result.message || 'Faktur PO berhasil diterbitkan!', 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/faktur_po/index.php';
            }, 1200);
        } else {
            showToast(result.message || 'Gagal menerbitkan Faktur PO.', 'danger');
            btnSave.disabled = false;
            btnDraft.disabled = false;
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
        btnSave.disabled = false;
        btnDraft.disabled = false;
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
