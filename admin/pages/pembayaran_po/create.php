<?php
/**
 * Halaman Formulir Catat Pembayaran Faktur PO (Payment Purchase)
 * Path: admin/pages/pembayaran_po/create.php
 * Khusus Role: FINANCE, PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection: Khusus Finance, Admin, dan Manager
$user = requireAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Catat Pembayaran PO';
$pageHeading = 'Formulir Pembayaran Faktur Pembelian';

// Ambil parameter id_faktur jika dibuka langsung dari faktur detail
$preselectedIdFaktur = isset($_GET['id_faktur']) ? decodeId($_GET['id_faktur']) : 0;

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
/* Standardize Height of all form inputs & controls */
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
.custom-select-trigger,
.faktur-custom-select {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 0.875rem !important;
}

textarea.form-control {
    height: auto !important;
    min-height: 100px !important;
}

.faktur-custom-select {
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.faktur-custom-select:hover {
    border-color: #0d6efd !important;
    background-color: #f8fafc !important;
}
.transition-chevron {
    transition: transform 0.2s ease;
}
.faktur-option-item {
    transition: background-color 0.15s ease, transform 0.1s ease;
    border-radius: 6px;
    cursor: pointer !important;
}
.faktur-option-item:hover {
    background-color: #e9ecef !important;
}
.faktur-option-item:hover strong.text-primary {
    color: #0a58ca !important;
    text-decoration: underline;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Pembayaran Faktur PO</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                Kembali ke Riwayat
            </a>
        </div>
    </div>

    <form id="formPayment" onsubmit="event.preventDefault(); openConfirmPaymentModal();">
        <input type="hidden" id="selectedIdFaktur" name="id_faktur" value="<?= $preselectedIdFaktur ?>" required>
        <input type="hidden" id="hiddenBuktiBase64" name="file_bukti_bayar_base64">

        <!-- CARD TAB MODULAR SESUAI KONTEKS & FUNGSI (5 TAB) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="paymentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-3 px-3" id="tab-faktur-btn" data-bs-toggle="tab" data-bs-target="#tab-faktur" type="button" role="tab">
                            <i class="bi bi-receipt me-1 text-primary"></i> 1. Tagihan &amp; Faktur
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-nominal-btn" data-bs-toggle="tab" data-bs-target="#tab-nominal" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1 text-primary"></i> 2. Rincian Pembayaran
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-vendor-rek-btn" data-bs-toggle="tab" data-bs-target="#tab-vendor-rek" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 3. Rekening Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-rekening-btn" data-bs-toggle="tab" data-bs-target="#tab-rekening" type="button" role="tab">
                            <i class="bi bi-bank me-1 text-primary"></i> 4. Kas &amp; Rekening Pengirim
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-approval-btn" data-bs-toggle="tab" data-bs-target="#tab-approval" type="button" role="tab">
                            <i class="bi bi-shield-check me-1 text-primary"></i> 5. Approval &amp; Bukti
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="paymentTabContent">
                    
                    <!-- TAB 1: TAGIHAN & FAKTUR -->
                    <div class="tab-pane fade show active" id="tab-faktur" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Pilih Dokumen Faktur Tagihan</h6>

                                <!-- Nomor Pembayaran Sistem (Readonly & Dinamis) -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nomor / Kode Pembayaran <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control font-monospace fw-bold text-primary bg-light" id="inputKodePembayaran" readonly required placeholder="Memuat kode pembayaran...">
                                        <button type="button" class="btn btn-outline-secondary" onclick="fetchNextPaymentNumber()" title="Generate Ulang Nomor">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3 position-relative" id="fakturSelectWrapper">
                                    <label class="form-label small fw-semibold text-dark">
                                        Dokumen Faktur PO Belum Lunas <span class="text-danger">*</span>
                                    </label>

                                    <!-- Trigger Search Box (Searchable UI) -->
                                    <div class="faktur-custom-select d-flex align-items-center justify-content-between p-2 px-3 border rounded-3 bg-white cursor-pointer shadow-sm" id="fakturTriggerBox" onclick="toggleFakturDropdown(event)" style="cursor: pointer;">
                                        <div id="fakturSelectedDisplay" class="text-truncate me-2">
                                            <span class="text-muted"><i class="bi bi-search me-2 text-primary"></i>Pilih Dokumen Faktur Belum Lunas (No. Faktur, Vendor, Inv)...</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 me-1" id="fakturClearBtn" onclick="clearFakturSelection(event)" style="display: none;" title="Hapus Pilihan">
                                                <i class="bi bi-x-circle-fill fs-6"></i>
                                            </button>
                                            <i class="bi bi-chevron-down text-muted small transition-chevron" id="fakturChevronIcon"></i>
                                        </div>
                                    </div>

                                    <!-- Searchable Dropdown Menu -->
                                    <div class="faktur-dropdown-menu shadow-lg border rounded-3 p-2 bg-white" id="fakturDropdownMenu" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050;">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control form-control-sm border-start-0" id="fakturSearchInput" placeholder="Ketik No. Faktur, Vendor, atau No. Invoice..." autocomplete="off" oninput="filterFakturList()">
                                        </div>
                                        <div class="overflow-auto" id="fakturOptionsContainer" style="max-height: 260px;">
                                            <!-- Populated dynamically by JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Status &amp; Nilai Tagihan</h6>

                                <div id="fakturSummaryContainer" class="p-3 bg-light rounded-3 border">
                                    <div class="row g-2 small mb-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">No. Faktur Sistem:</span>
                                            <strong class="font-monospace text-primary" id="dispNomorFaktur">-</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">No. Invoice Vendor:</span>
                                            <strong class="font-monospace text-dark" id="dispNomorFakturVendor">-</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Nama Vendor:</span>
                                            <span class="fw-bold text-dark" id="dispNamaVendor">-</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Jatuh Tempo (TOP):</span>
                                            <strong class="text-danger" id="dispTanggalJatuhTempo">-</strong>
                                        </div>
                                    </div>

                                    <!-- KOTAK FINANSIAL -->
                                    <div class="row g-2 pt-3 border-top text-center">
                                        <div class="col-4">
                                            <div class="text-muted small">Total Tagihan</div>
                                            <div class="fw-bold font-monospace fs-6 text-dark" id="dispTotalTagihan">Rp 0</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">Sudah Dibayar</div>
                                            <div class="fw-bold font-monospace fs-6 text-success" id="dispTerbayar">Rp 0</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">Sisa Tagihan</div>
                                            <div class="fw-bold font-monospace fs-6 text-danger" id="dispSisaTagihan">Rp 0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RIWAYAT ANGSURAN SEBELUMNYA (FULL WIDTH / MELEBAR MERGE KEDUA KOLOM) -->
                        <div id="fakturHistoryContainer" class="mt-4" style="display: none;">
                            <div class="card border border-light-subtle rounded-3 shadow-none overflow-hidden bg-white">
                                <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark small">Riwayat Mutasi &amp; Pembayaran Sebelumnya</span>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1" id="fakturHistoryCount">0 Transaksi</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8125rem;">
                                        <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">No</th>
                                                <th style="min-width: 140px;">Kode Bayar</th>
                                                <th style="min-width: 130px;" class="text-center">Tanggal Pembayaran</th>
                                                <th style="min-width: 130px;">Kas / Bank Asal</th>
                                                <th style="min-width: 120px;">No. Ref</th>
                                                <th style="min-width: 130px;" class="text-end">Transfer</th>
                                                <th style="min-width: 120px;" class="text-end">Diskon</th>
                                                <th style="min-width: 130px;" class="text-end">Sisa Tagihan</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fakturHistoryTableBody">
                                        </tbody>
                                        <tfoot class="table-light border-top">
                                            <tr class="fw-bold">
                                                <td colspan="5" class="text-end text-muted small py-2">Total Akumulasi Terbayar:</td>
                                                <td class="text-end font-monospace text-success py-2" id="fakturHistoryTotalTerbayar">Rp 0</td>
                                                <td class="text-end font-monospace text-danger py-2" id="fakturHistoryTotalDiskon">Rp 0</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-nominal')">
                                Lanjut ke Rincian Pembayaran
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: RINCIAN PEMBAYARAN -->
                    <div class="tab-pane fade" id="tab-nominal" role="tabpanel">
                        <div class="row g-4">
                            <!-- Kolom Kiri: Skema & Tanggal Transaksi -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Skema &amp; Tanggal Pembayaran</h6>

                                <!-- SKEMA PEMBAYARAN -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Skema Pembayaran <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="jenis_pembayaran" id="jenisLunas" value="1" checked onchange="handleJenisPembayaranChange()">
                                            <label class="btn btn-outline-success w-100 p-2 text-start rounded-3" for="jenisLunas">
                                                <div class="fw-bold small">1x Bayar (Lunas)</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">Bayar penuh sisa tagihan</div>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="jenis_pembayaran" id="jenisKredit" value="0" onchange="handleJenisPembayaranChange()">
                                            <label class="btn btn-outline-warning w-100 p-2 text-start rounded-3" for="jenisKredit">
                                                <div class="fw-bold small">Kredit / Sebagian</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">Angsuran / Termin</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggalBayar" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required onchange="handleTanggalBayarChange()">
                                </div>

                                <!-- DISKON PEMBAYARAN -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Diskon / Potongan Pembayaran</label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace text-danger fw-bold">- Rp</span>
                                        <input type="text" class="form-control text-end font-monospace fw-bold text-danger" id="nominalDiskon" placeholder="0" oninput="handleDiskonInput(this)">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Keterangan / Alasan Diskon</label>
                                    <input type="text" class="form-control" id="keteranganDiskon" placeholder="Contoh: Diskon Pelunasan Awal / Negosiasi / Pembulatan">
                                </div>
                            </div>

                            <!-- Kolom Kanan: Rincian Nominal, Biaya Admin & Estimasi Sisa Tagihan -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Nominal &amp; Ringkasan Kalkulasi</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nominal Pembayaran Transfer <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace fw-bold">Rp</span>
                                        <input type="text" class="form-control text-end font-monospace fw-bold fs-6 text-primary" id="nominalPengiriman" placeholder="0" required oninput="handleNominalInput(this)">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Jenis Transfer &amp; Biaya Admin</label>
                                    <div class="input-group">
                                        <select class="form-select" id="selectJenisTransfer" onchange="handleSelectJenisTransfer(this)" style="max-width: 55%;">
                                            <option value="0" data-fee="0" selected>Sesama Bank - Gratis (Rp 0)</option>
                                            <option value="2500" data-fee="2500">BI-FAST - Rp 2.500</option>
                                            <option value="6500" data-fee="6500">Transfer Online (RTO) - Rp 6.500</option>
                                            <option value="2900" data-fee="2900">Kliring (SKNBI) - Rp 2.900</option>
                                            <option value="25000" data-fee="25000">RTGS - Rp 25.000</option>
                                            <option value="custom" data-fee="">Lainnya / Manual</option>
                                        </select>
                                        <span class="input-group-text font-monospace">Rp</span>
                                        <input type="text" class="form-control text-end font-monospace fw-semibold" id="biayaAdmin" value="0" placeholder="0" oninput="handleBiayaAdminInput(this)">
                                    </div>
                                    
                                </div>

                                <!-- KARTU ESTIMASI & RINGKASAN TAGIHAN -->
                                <div class="card border border-light-subtle rounded-3 bg-light p-3 mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                        <span class="text-muted small">Total Tagihan Berjalan:</span>
                                        <span class="font-monospace fw-semibold text-dark" id="calcTagihanAwal">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1 text-primary">
                                        <span class="small">Nominal Pembayaran:</span>
                                        <span class="font-monospace fw-bold" id="calcNominalBayar">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom text-danger">
                                        <span class="small">Diskon Pembayaran:</span>
                                        <span class="font-monospace fw-bold" id="calcNominalDiskon">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-1">
                                        <span class="fw-bold small text-dark">Estimasi Sisa Tagihan:</span>
                                        <span class="font-monospace fw-bold fs-6 text-danger" id="displaySisaSesudah">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top text-secondary" style="font-size: 0.75rem;">
                                        <span>Total Pengeluaran Kas (Transfer + Admin):</span>
                                        <span class="font-monospace fw-semibold" id="calcTotalKasKeluar">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-faktur')">
                                Kembali ke Tagihan &amp; Faktur
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-vendor-rek')">
                                Lanjut ke Rekening Vendor
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: REKENING VENDOR (TUJUAN TRANSFER) -->
                    <div class="tab-pane fade" id="tab-vendor-rek" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Rekening Vendor (Tujuan Transfer Pembayaran)</h6>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="applyRekeningFromFaktur()" title="Gunakan rekening yang tercatat pada Faktur PO">
                                    <i class="bi bi-receipt me-1"></i> Rekening Faktur
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm px-3" onclick="applyRekeningFromMasterVendor()" title="Gunakan rekening default dari Master Vendor">
                                    <i class="bi bi-building me-1"></i> Rekening Master Vendor
                                </button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Bank Tujuan Vendor (Combobox Persis Bank Asal) -->
                            <div class="col-sm-6 position-relative" id="bankTujuanWrapper">
                                <label class="form-label small fw-semibold text-dark">Bank Tujuan Vendor</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace fw-semibold" id="bankTujuan" name="bank_tujuan" placeholder="Pilih atau ketik bank..." autocomplete="off" onfocus="showBankTujuanDropdown()" oninput="filterBankTujuanDropdown()">
                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-3" type="button" onclick="toggleBankTujuanDropdown(event)" title="Pilih Bank"></button>
                                </div>
                                <div class="dropdown-menu shadow-sm w-100 p-1" id="bankTujuanMenu" style="max-height: 220px; overflow-y: auto; display: none; position: absolute; top: calc(100% + 2px); left: 0; z-index: 1050;">
                                    <!-- Populated dynamically by updateBankTujuanOptions() -->
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening Tujuan Vendor</label>
                                <input type="text" class="form-control font-monospace fw-bold text-primary" id="norekTujuan" name="norek_tujuan" placeholder="Nomor Rekening Vendor">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening Tujuan</label>
                                <input type="text" class="form-control fw-semibold text-dark" id="anPengiriman" name="an_pengiriman" placeholder="Atas Nama Pemilik Rekening Vendor">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-nominal')">
                                Kembali ke Rincian Pembayaran
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-rekening')">
                                Lanjut ke Kas &amp; Rekening Pengirim
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: KAS & REKENING PENGIRIM -->
                    <div class="tab-pane fade" id="tab-rekening" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Pilih Rekening Bank Resmi Perusahaan <span class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="selectRekeningBankPengirim" onchange="handleSelectRekeningBankPengirim(this)" required>
                                    <option value="">-- Pilih Rekening Bank Perusahaan --</option>
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Bank Asal / Kas Pengirim <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace fw-semibold bg-light" id="bankPengirim" name="bank_pengirim" placeholder="Nama Bank Pengirim" readonly required>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening Pengirim</label>
                                <input type="text" class="form-control font-monospace fw-bold bg-light" id="norekPengirim" name="norek_pengirim" placeholder="Nomor Rekening Kas / Tabungan" readonly>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening Pengirim</label>
                                <input type="text" class="form-control fw-semibold bg-light" id="anPengirim" name="an_pengirim" placeholder="Nama Pemilik Rekening / PT Jaya Teknis" readonly>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">No. Referensi / Mutasi Bank</label>
                                <input type="text" class="form-control font-monospace" id="noRef" name="no_ref" placeholder="Contoh: TRF-2608-88129">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-vendor-rek')">
                                Kembali ke Rekening Vendor
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-approval')">
                                Lanjut ke Approval &amp; Bukti
                            </button>
                        </div>
                    </div>

                    <!-- TAB 5: APPROVAL & BUKTI -->
                    <div class="tab-pane fade" id="tab-approval" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Persetujuan Lisan &amp; Lampiran Bukti Transfer</h6>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Disetujui Oleh (Lisan) <span class="text-danger">*</span></label>
                                <select class="form-select" id="selectApprover" name="id_karyawan_approved" required>
                                    <option value="">-- Pilih Pejabat / Finance --</option>
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Upload Bukti Transfer Bank (PDF/Foto)</label>
                                <input type="file" class="form-control" id="fileBuktiBayar" accept="image/*,application/pdf" onchange="handleProofUpload(this)">
                                <div class="form-text small text-muted" id="buktiFileInfo">Format: PDF, JPG, JPEG, PNG (Maks. 5MB)</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Catatan / Keterangan Pembayaran</label>
                                <textarea class="form-control" id="keteranganPayment" name="keterangan" rows="3" placeholder="Catatan opsional mengenai pembayaran ini..."></textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-rekening')">
                                Kembali ke Kas &amp; Rekening Pengirim
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitPayment" style="height: 38px; display: inline-flex; align-items: center;">
                                <i class="bi bi-check-circle-fill me-1"></i> Simpan Transaksi Pembayaran
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<!-- =============================================================
     MODAL VERIFIKASI & KONFIRMASI PEMBAYARAN FAKTUR PO
     ============================================================= -->
<div class="modal fade" id="modalVerifyPayment" tabindex="-1" aria-labelledby="modalVerifyPaymentLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check fs-5"></i>
                    <h5 class="modal-title fs-6 fw-bold mb-0" id="modalVerifyPaymentLabel">
                        Verifikasi &amp; Konfirmasi Pembayaran Faktur PO
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 bg-light">
                
                <!-- Ringkasan Singkat Pembayaran -->
                <div class="d-flex flex-wrap align-items-center justify-content-between p-2 px-3 bg-white border rounded-2 mb-2 gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <span class="text-muted" style="font-size: 0.72rem; display: block;">Kode Bayar:</span>
                            <strong class="text-dark font-monospace small" id="verifyKodePaymentDisplay">-</strong>
                        </div>
                        <div class="border-start ps-3">
                            <span class="text-muted" style="font-size: 0.72rem; display: block;">Faktur PO &amp; Vendor:</span>
                            <span class="fw-semibold text-dark small" id="verifyFakturVendorDisplay">-</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted" style="font-size: 0.72rem; display: block;">Total Kas Keluar (Transfer + Admin):</span>
                        <strong class="text-success font-monospace fs-6" id="verifyTotalKasKeluarDisplay">Rp 0</strong>
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
                    <!-- 1. Dokumen Faktur & TOP -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyFaktur" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyFaktur">
                                    1. Dokumen Faktur &amp; TOP
                                </label>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-truncate" style="font-size: 0.7rem; max-width: 170px;" id="verifyValFaktur">-</span>
                        </div>
                    </div>

                    <!-- 4. Nominal Pembayaran -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyNominal" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyNominal">
                                    4. Nominal Transfer Bank
                                </label>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace" style="font-size: 0.7rem;" id="verifyValNominal">Rp 0</span>
                        </div>
                    </div>

                    <!-- 2. Skema & Tanggal Pembayaran -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifySkema" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifySkema">
                                    2. Skema &amp; Tanggal Bayar
                                </label>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 0.7rem;" id="verifyValSkema">1x Bayar</span>
                        </div>
                    </div>

                    <!-- 5. Potongan Diskon & Admin -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyDiskonAdmin" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyDiskonAdmin">
                                    5. Diskon &amp; Biaya Admin
                                </label>
                            </div>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace" style="font-size: 0.7rem;" id="verifyValDiskonAdmin">Diskon: Rp 0</span>
                        </div>
                    </div>

                    <!-- 3. Rekening Vendor Tujuan -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyRekVendor" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyRekVendor">
                                    3. Rekening Vendor Tujuan
                                </label>
                            </div>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace text-truncate" style="font-size: 0.7rem; max-width: 170px;" id="verifyValRekVendor">-</span>
                        </div>
                    </div>

                    <!-- 6. Bank Pengirim & Approver -->
                    <div class="col-md-6">
                        <div class="p-2 bg-white border rounded-2 d-flex align-items-center justify-content-between h-100 shadow-xs">
                            <div class="form-check m-0 d-flex align-items-center gap-2">
                                <input class="form-check-input verify-check-item m-0" type="checkbox" id="checkVerifyBankApprover" onchange="checkVerifyCompleteness()">
                                <label class="form-check-label small fw-semibold text-dark cursor-pointer" for="checkVerifyBankApprover">
                                    6. Bank Pengirim &amp; Approver
                                </label>
                            </div>
                            <span class="badge bg-light text-dark border font-monospace text-truncate" style="font-size: 0.7rem; max-width: 170px;" id="verifyValBankApprover">-</span>
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
                                   onkeydown="if(event.key === 'Enter') { event.preventDefault(); if(!document.getElementById('btnFinalSubmitPayment').disabled) submitFinalPayment(); }"
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
                <button type="button" class="btn btn-primary btn-sm px-3 fw-bold shadow-sm" id="btnFinalSubmitPayment" onclick="submitFinalPayment()" disabled>
                    <i class="bi bi-check-circle-fill me-1"></i> Konfirmasi &amp; Simpan Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let fakturList = [];
let approverList = [];
let currentSelectedFaktur = null;
let modalVerifyPaymentInstance = null;

document.addEventListener('DOMContentLoaded', async () => {
    const modalEl = document.getElementById('modalVerifyPayment');
    if (modalEl) {
        modalVerifyPaymentInstance = new bootstrap.Modal(modalEl);
    }
    await fetchNextPaymentNumber();
    loadLookupData();

    document.addEventListener('click', (e) => {
        const fakturWrapper = document.getElementById('fakturSelectWrapper');
        if (fakturWrapper && !fakturWrapper.contains(e.target)) {
            closeFakturDropdown();
        }
    });
});

function goToTab(tabId) {
    const btn = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (btn) {
        const tab = new bootstrap.Tab(btn);
        tab.show();
    }
}

let rekeningBankPerusahaanList = [];

async function loadLookupData() {
    try {
        const [fakturRes, bankRes] = await Promise.all([
            fetch('<?= BASE_URL ?>/api/pembayaran_po/lookup_faktur.php'),
            fetch('<?= BASE_URL ?>/api/master/rekening_bank.php?all=1')
        ]);
        
        const result = await fakturRes.json();
        const bankResult = await bankRes.json();

        if (result && result.success && result.data) {
            fakturList = result.data.faktur_list || [];
            approverList = result.data.approvers || [];

            renderFakturOptions(fakturList);
            renderApproverOptions(approverList);
            updateBankTujuanOptions('');

            // Cek jika ada preselected id_faktur dari query parameter
            const preselectedId = parseInt(document.getElementById('selectedIdFaktur').value);
            if (preselectedId > 0) {
                selectFaktur(preselectedId);
            }
        }

        if (bankResult && bankResult.success && bankResult.data && bankResult.data.items) {
            rekeningBankPerusahaanList = bankResult.data.items || [];
            renderRekeningBankOptions(rekeningBankPerusahaanList);
        } else {
            renderRekeningBankOptions([]);
        }
    } catch (e) {
        console.error('Gagal memuat data lookup faktur/rekening:', e);
    }
}

function renderRekeningBankOptions(banks) {
    const sel = document.getElementById('selectRekeningBankPengirim');
    if (!sel) return;

    let html = '<option value="">-- Pilih Rekening Bank Perusahaan --</option>';
    if (banks && banks.length > 0) {
        banks.forEach((b) => {
            const namaBank = escapeHtml(b.nama_bank || '');
            const noRek = escapeHtml(b.no_rekening || '');
            const anRek = escapeHtml(b.atasnama_rekening || 'PT JAYA TEKNIK');
            html += `<option value="${b.id_bank}" data-bank="${namaBank}" data-norek="${noRek}" data-an="${anRek}">
                ${namaBank} - ${noRek} (a.n ${anRek})
            </option>`;
        });
    }
    sel.innerHTML = html;

    // Auto select first bank if available
    if (banks && banks.length > 0) {
        sel.selectedIndex = 1;
        handleSelectRekeningBankPengirim(sel);
    }
}

function handleSelectRekeningBankPengirim(sel) {
    const val = sel.value;
    const selectedOpt = sel.options[sel.selectedIndex];
    const bankInput = document.getElementById('bankPengirim');
    const norekInput = document.getElementById('norekPengirim');
    const anInput = document.getElementById('anPengirim');

    if (!val || !selectedOpt) {
        bankInput.value = '';
        norekInput.value = '';
        anInput.value = '';
        return;
    }

    const bankName = selectedOpt.getAttribute('data-bank') || '';
    const noRek = selectedOpt.getAttribute('data-norek') || '';
    const an = selectedOpt.getAttribute('data-an') || '';

    bankInput.value = bankName;
    norekInput.value = noRek;
    anInput.value = an;
}

function renderApproverOptions(approvers) {
    const sel = document.getElementById('selectApprover');
    sel.innerHTML = '<option value="">-- Pilih Pejabat / Finance --</option>';
    approvers.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id_karyawan;
        opt.textContent = `${a.nama_karyawan} (${a.nama_jabatan || a.nama_divisi || 'Staff'})`;
        sel.appendChild(opt);
    });
}

// -------------------------------------------------------------
// FAKTUR SELECTOR (SEARCHABLE DROPDOWN)
// -------------------------------------------------------------
function toggleFakturDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('fakturDropdownMenu');
    if (menu && menu.style.display === 'block') {
        closeFakturDropdown();
    } else {
        openFakturDropdown();
    }
}

function openFakturDropdown() {
    const menu = document.getElementById('fakturDropdownMenu');
    const chevron = document.getElementById('fakturChevronIcon');
    if (menu) menu.style.display = 'block';
    if (chevron) chevron.style.transform = 'rotate(180deg)';
    setTimeout(() => {
        const searchInput = document.getElementById('fakturSearchInput');
        if (searchInput) {
            searchInput.value = '';
            filterFakturList();
            searchInput.focus();
        }
    }, 50);
}

function closeFakturDropdown() {
    const menu = document.getElementById('fakturDropdownMenu');
    const chevron = document.getElementById('fakturChevronIcon');
    if (menu) menu.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
}

function renderFakturOptions(list) {
    const container = document.getElementById('fakturOptionsContainer');
    if (!container) return;
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="p-3 text-center text-muted small">Tidak ada dokumen Faktur PO yang cocok.</div>';
        return;
    }

    let html = '';
    list.forEach(f => {
        const sisaTagihan = parseFloat(f.sisa_tagihan) || 0;
        const tglDisplay = formatDate(f.tanggal_jatuh_tempo);
        html += `
        <div class="faktur-option-item p-2 border-bottom" 
             style="cursor: pointer;"
             onclick="selectFaktur(${f.id_faktur})">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <strong class="text-primary font-monospace small">${escapeHtml(f.nomor_faktur)}</strong>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-1 py-0" style="font-size: 0.65rem;">Sisa: ${formatRupiah(sisaTagihan)}</span>
                </div>
                <span class="badge bg-light text-secondary border px-2 py-1 small fw-normal font-monospace">${tglDisplay}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <div class="small text-dark fw-semibold">${escapeHtml(f.nama_vendor)}</div>
                <div class="small text-muted font-monospace" style="font-size: 0.76rem;">Inv: ${escapeHtml(f.nomor_faktur_vendor || '-')} | PO: ${escapeHtml(f.nomor_po)}</div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function filterFakturList() {
    const query = document.getElementById('fakturSearchInput').value.toLowerCase().trim();
    const filtered = fakturList.filter(f => {
        return (f.nomor_faktur || '').toLowerCase().includes(query) ||
               (f.nomor_faktur_vendor || '').toLowerCase().includes(query) ||
               (f.nama_vendor || '').toLowerCase().includes(query) ||
               (f.nomor_po || '').toLowerCase().includes(query);
    });
    renderFakturOptions(filtered);
}

async function selectFaktur(idFaktur) {
    closeFakturDropdown();
    document.getElementById('selectedIdFaktur').value = idFaktur;

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/pembayaran_po/lookup_faktur.php?id_faktur=${idFaktur}`);
        const result = await res.json();

        if (result && result.success && result.data && result.data.faktur) {
            const f = result.data.faktur;
            currentSelectedFaktur = f;

            // Update Trigger Display
            document.getElementById('fakturSelectedDisplay').innerHTML = `
                <strong class="font-monospace text-primary">${f.nomor_faktur}</strong>
                <span class="text-muted ms-1">| ${f.nama_vendor} | Sisa: ${formatRupiah(f.sisa_tagihan)}</span>
            `;
            document.getElementById('fakturClearBtn').style.display = 'inline-block';

            // Show Summary
            document.getElementById('dispNomorFaktur').textContent = f.nomor_faktur;
            document.getElementById('dispNomorFakturVendor').textContent = f.nomor_faktur_vendor || '-';
            document.getElementById('dispNamaVendor').textContent = f.nama_vendor;
            document.getElementById('dispTanggalJatuhTempo').textContent = `${formatDate(f.tanggal_jatuh_tempo)} (${f.term_of_payment || 0} Hari TOP)`;
            
            // Set Default Nilai Rekening Vendor Tujuan
            resetRekeningVendorToDefault();

            document.getElementById('dispTotalTagihan').textContent = formatRupiah(f.total_tagihan);
            document.getElementById('dispTerbayar').textContent = formatRupiah(f.terbayar);
            document.getElementById('dispSisaTagihan').textContent = formatRupiah(f.sisa_tagihan);

            // History Table
            const histBody = document.getElementById('fakturHistoryTableBody');
            const histContainer = document.getElementById('fakturHistoryContainer');
            const histCount = document.getElementById('fakturHistoryCount');
            const histTotalTerbayar = document.getElementById('fakturHistoryTotalTerbayar');
            const histTotalDiskon = document.getElementById('fakturHistoryTotalDiskon');

            if (f.history_pembayaran && f.history_pembayaran.length > 0) {
                let histHtml = '';
                let totalNominal = 0;
                let totalDiskon = 0;
                f.history_pembayaran.forEach((h, idx) => {
                    const nominal = parseFloat(h.nominal_pengiriman) || 0;
                    const diskon = parseFloat(h.nominal_diskon) || 0;
                    const sisa = parseFloat(h.sisa_piutang) || 0;
                    totalNominal += nominal;
                    totalDiskon += diskon;
                    const bankKas = h.bank_pengirim ? `<span class="badge bg-light text-dark border font-monospace" style="font-size: 0.75rem;">${escapeHtml(h.bank_pengirim)}</span>` : '<span class="text-muted small">-</span>';
                    const noRef = h.no_ref ? `<span class="font-monospace text-muted small">${escapeHtml(h.no_ref)}</span>` : '<span class="text-muted small">-</span>';

                    histHtml += `
                    <tr>
                        <td class="text-center text-muted font-monospace small">${idx + 1}</td>
                        <td>
                            <span class="fw-bold font-monospace text-primary">${escapeHtml(h.kode_pembayaran)}</span>
                        </td>
                        <td class="text-center font-monospace text-secondary small">${formatDate(h.tanggal_bayar)}</td>
                        <td>${bankKas}</td>
                        <td>${noRef}</td>
                        <td class="text-end font-monospace fw-bold text-success">${formatRupiah(nominal)}</td>
                        <td class="text-end font-monospace ${diskon > 0 ? 'text-danger fw-bold' : 'text-muted'}">${diskon > 0 ? formatRupiah(diskon) : '-'}</td>
                        <td class="text-end font-monospace text-danger fw-semibold">${formatRupiah(sisa)}</td>
                    </tr>`;
                });
                histBody.innerHTML = histHtml;
                if (histCount) histCount.textContent = `${f.history_pembayaran.length} Transaksi`;
                if (histTotalTerbayar) histTotalTerbayar.textContent = formatRupiah(totalNominal);
                if (histTotalDiskon) histTotalDiskon.textContent = formatRupiah(totalDiskon);
                histContainer.style.display = 'block';
            } else {
                histContainer.style.display = 'none';
            }

            // Reset Diskon Input
            document.getElementById('nominalDiskon').value = '';
            document.getElementById('keteranganDiskon').value = '';

            // Sync Nominal Pembayaran jika 1x Bayar (Lunas)
            handleJenisPembayaranChange();
        }
    } catch (e) {
        console.error('Gagal mengambil detail faktur terpilih:', e);
    }
}

function applyRekeningFromFaktur() {
    if (!currentSelectedFaktur) return;
    const f = currentSelectedFaktur;

    // Prioritas 1: Data dari Faktur PO
    let vendorBank = f.nama_bank || '';
    let vendorNorek = f.nomor_rekening || '';
    let vendorAn = f.atas_nama_rekening || f.nama_vendor || '';

    // Jika di faktur kosong, fallback ke master vendor
    if (!vendorBank && f.bank_vendor_master) {
        vendorBank = f.bank_vendor_master;
        vendorNorek = f.norek_vendor_master || '';
    }

    updateBankTujuanOptions(f.nama_bank || '', f.bank_vendor_master || '');
    document.getElementById('bankTujuan').value = vendorBank;
    document.getElementById('norekTujuan').value = vendorNorek;
    document.getElementById('anPengiriman').value = vendorAn;
}

function applyRekeningFromMasterVendor() {
    if (!currentSelectedFaktur) return;
    const f = currentSelectedFaktur;

    // Prioritas 2: Data dari Master Vendor
    let vendorBank = f.bank_vendor_master || f.nama_bank || '';
    let vendorNorek = f.norek_vendor_master || f.nomor_rekening || '';
    let vendorAn = f.atas_nama_rekening || f.nama_vendor || '';

    updateBankTujuanOptions(f.nama_bank || '', f.bank_vendor_master || '');
    document.getElementById('bankTujuan').value = vendorBank;
    document.getElementById('norekTujuan').value = vendorNorek;
    document.getElementById('anPengiriman').value = vendorAn;
}

function resetRekeningVendorToDefault() {
    applyRekeningFromFaktur();
}

function clearFakturSelection(e) {
    if (e) e.stopPropagation();
    currentSelectedFaktur = null;
    document.getElementById('selectedIdFaktur').value = '';
    document.getElementById('fakturSelectedDisplay').innerHTML = '<span class="text-muted"><i class="bi bi-search me-2 text-primary"></i>Pilih Dokumen Faktur Belum Lunas (No. Faktur, Vendor, Inv)...</span>';
    document.getElementById('fakturClearBtn').style.display = 'none';
    
    document.getElementById('dispNomorFaktur').textContent = '-';
    document.getElementById('dispNomorFakturVendor').textContent = '-';
    document.getElementById('dispNamaVendor').textContent = '-';
    document.getElementById('dispTanggalJatuhTempo').textContent = '-';
    document.getElementById('bankTujuan').value = '';
    document.getElementById('norekTujuan').value = '';
    document.getElementById('anPengiriman').value = '';
    updateBankTujuanOptions('', '');

    document.getElementById('dispTotalTagihan').textContent = 'Rp 0';
    document.getElementById('dispTerbayar').textContent = 'Rp 0';
    document.getElementById('dispSisaTagihan').textContent = 'Rp 0';

    document.getElementById('fakturHistoryContainer').style.display = 'none';
    document.getElementById('nominalDiskon').value = '';
    document.getElementById('keteranganDiskon').value = '';
    document.getElementById('nominalPengiriman').value = '';
    
    if (document.getElementById('selectJenisTransfer')) {
        document.getElementById('selectJenisTransfer').value = '0';
    }
    if (document.getElementById('infoJenisTransfer')) {
        document.getElementById('infoJenisTransfer').textContent = TRANSFER_TYPES_INFO['0'] || '';
    }
    document.getElementById('biayaAdmin').value = '0';
    document.getElementById('displaySisaSesudah').textContent = 'Rp 0';
    if (document.getElementById('calcTagihanAwal')) document.getElementById('calcTagihanAwal').textContent = 'Rp 0';
    if (document.getElementById('calcNominalBayar')) document.getElementById('calcNominalBayar').textContent = 'Rp 0';
    if (document.getElementById('calcNominalDiskon')) document.getElementById('calcNominalDiskon').textContent = 'Rp 0';
    if (document.getElementById('calcTotalKasKeluar')) document.getElementById('calcTotalKasKeluar').textContent = 'Rp 0';
}

// -------------------------------------------------------------
// AUTO THOUSAND SEPARATOR & FINANSIAL LOGIC
// -------------------------------------------------------------
function formatThousands(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    if (!clean) return '';
    let n = parseInt(clean, 10);
    return n.toLocaleString('id-ID');
}

function parseRawNumber(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    return parseInt(clean, 10) || 0;
}

function handleNominalInput(input) {
    let raw = parseRawNumber(input.value);
    input.value = raw > 0 ? raw.toLocaleString('id-ID') : '';
    calculateRemainingBalance();
}

function handleDiskonInput(input) {
    let raw = parseRawNumber(input.value);
    input.value = raw > 0 ? raw.toLocaleString('id-ID') : '';

    const isLunas = document.getElementById('jenisLunas').checked;
    if (isLunas && currentSelectedFaktur) {
        const sisa = parseFloat(currentSelectedFaktur.sisa_tagihan) || 0;
        const sisaSetelahDiskon = Math.max(0, sisa - raw);
        document.getElementById('nominalPengiriman').value = sisaSetelahDiskon > 0 ? sisaSetelahDiskon.toLocaleString('id-ID') : '0';
    }
    calculateRemainingBalance();
}

const TRANSFER_TYPES_INFO = {
    '0': 'Sesama Bank: Bebas biaya admin (Gratis) • Real-time (Seketika)',
    '2500': 'BI-FAST: Rp 2.500 • Real-time (Seketika) • Batas s/d Rp 250 Juta / transaksi',
    '6500': 'Transfer Online (RTO): Rp 6.500 • Real-time (Seketika) • Batas Rp 50 Juta - Rp 100 Juta / hari',
    '2900': 'Kliring (SKNBI): Rp 2.900 • 2 - 4 Jam (Hari Kerja) • Batas s/d Rp 1 Miliar / hari',
    '25000': 'RTGS: Rp 25.000 • Hari yang sama (Hari Kerja) • Di atas Rp 100 Juta (Tanpa limit atas)',
    'custom': 'Lainnya / Manual: Nominal biaya admin diisi manual sesuai kebijakan bank pengirim.'
};

function handleSelectJenisTransfer(sel) {
    const val = sel.value;
    const fee = sel.options[sel.selectedIndex].getAttribute('data-fee');
    const adminInput = document.getElementById('biayaAdmin');
    const infoEl = document.getElementById('infoJenisTransfer');
    
    if (val !== 'custom' && fee !== null && fee !== '') {
        const numFee = parseInt(fee, 10) || 0;
        adminInput.value = numFee > 0 ? numFee.toLocaleString('id-ID') : '0';
    }
    
    if (infoEl) {
        infoEl.textContent = TRANSFER_TYPES_INFO[val] || '';
    }
    
    calculateRemainingBalance();
}

function handleBiayaAdminInput(input) {
    let raw = parseRawNumber(input.value);
    input.value = raw > 0 ? raw.toLocaleString('id-ID') : '0';
    
    // Sinkronkan pilihan dropdown dengan nominal yang diketik
    const sel = document.getElementById('selectJenisTransfer');
    const infoEl = document.getElementById('infoJenisTransfer');
    if (sel) {
        let matched = false;
        for (let i = 0; i < sel.options.length; i++) {
            const optVal = sel.options[i].value;
            const optFee = sel.options[i].getAttribute('data-fee');
            if (optVal !== 'custom' && optFee !== null && optFee !== '' && parseInt(optFee, 10) === raw) {
                sel.selectedIndex = i;
                matched = true;
                if (infoEl) infoEl.textContent = TRANSFER_TYPES_INFO[optVal] || '';
                break;
            }
        }
        if (!matched) {
            sel.value = 'custom';
            if (infoEl) infoEl.textContent = TRANSFER_TYPES_INFO['custom'] || '';
        }
    }
    
    calculateRemainingBalance();
}

function handleJenisPembayaranChange() {
    const isLunas = document.getElementById('jenisLunas').checked;
    const nominalInput = document.getElementById('nominalPengiriman');
    const diskonVal = parseRawNumber(document.getElementById('nominalDiskon').value);

    if (!currentSelectedFaktur) return;

    const sisa = parseFloat(currentSelectedFaktur.sisa_tagihan) || 0;
    const sisaSetelahDiskon = Math.max(0, sisa - diskonVal);

    if (isLunas) {
        nominalInput.value = sisaSetelahDiskon > 0 ? sisaSetelahDiskon.toLocaleString('id-ID') : '0';
        nominalInput.readOnly = true;
    } else {
        nominalInput.readOnly = false;
        let currentVal = parseRawNumber(nominalInput.value);
        if (currentVal >= sisaSetelahDiskon || currentVal === 0) {
            let half = Math.round(sisaSetelahDiskon / 2);
            nominalInput.value = half > 0 ? half.toLocaleString('id-ID') : '';
        }
    }
    calculateRemainingBalance();
    validateDueDateLimit();
}

function calculateRemainingBalance() {
    if (!currentSelectedFaktur) return;
    const sisaTagihan = parseFloat(currentSelectedFaktur.sisa_tagihan) || 0;
    const nominalBayar = parseRawNumber(document.getElementById('nominalPengiriman').value);
    const nominalDiskon = parseRawNumber(document.getElementById('nominalDiskon').value);
    const biayaAdmin = parseRawNumber(document.getElementById('biayaAdmin').value);
    
    // Perhitungan Sisa Tagihan Faktur: Hanya dikurangi Pembayaran Transfer + Diskon
    // Biaya Admin TIDAK mengurangi hutang/tagihan faktur
    const totalPengurangTagihan = nominalBayar + nominalDiskon;
    const sisaSesudah = Math.max(0, sisaTagihan - totalPengurangTagihan);
    
    // Update Tampilan Kalkulasi Tab 2
    if (document.getElementById('calcTagihanAwal')) {
        document.getElementById('calcTagihanAwal').textContent = formatRupiah(sisaTagihan);
    }
    if (document.getElementById('calcNominalBayar')) {
        document.getElementById('calcNominalBayar').textContent = formatRupiah(nominalBayar);
    }
    if (document.getElementById('calcNominalDiskon')) {
        document.getElementById('calcNominalDiskon').textContent = formatRupiah(nominalDiskon);
    }
    if (document.getElementById('displaySisaSesudah')) {
        document.getElementById('displaySisaSesudah').textContent = formatRupiah(sisaSesudah);
    }
    if (document.getElementById('calcTotalKasKeluar')) {
        document.getElementById('calcTotalKasKeluar').textContent = formatRupiah(nominalBayar + biayaAdmin);
    }
}

function validateDueDateLimit() {
    const isKredit = document.getElementById('jenisKredit').checked;
    const tglBayar = document.getElementById('tanggalBayar').value;

    if (isKredit && currentSelectedFaktur && currentSelectedFaktur.tanggal_jatuh_tempo && tglBayar) {
        const dBayar = new Date(tglBayar);
        const dTempo = new Date(currentSelectedFaktur.tanggal_jatuh_tempo);

        if (dBayar > dTempo) {
            showToast(`Peringatan: Tanggal pembayaran (${formatDate(tglBayar)}) melewati Tanggal Jatuh Tempo TOP Faktur (${formatDate(currentSelectedFaktur.tanggal_jatuh_tempo)}).`, 'warning');
            document.getElementById('tanggalBayar').value = currentSelectedFaktur.tanggal_jatuh_tempo;
        }
    }
}

async function handleTanggalBayarChange() {
    validateDueDateLimit();
    await fetchNextPaymentNumber();
}

async function fetchNextPaymentNumber() {
    const tgl = document.getElementById('tanggalBayar') ? document.getElementById('tanggalBayar').value : '';
    try {
        const res = await apiRequest(`/api/pembayaran_po/get_next_number.php?tanggal=${encodeURIComponent(tgl || '')}`);
        if (res && res.success && res.data && res.data.kode_pembayaran) {
            const inputEl = document.getElementById('inputKodePembayaran');
            if (inputEl) inputEl.value = res.data.kode_pembayaran;
        }
    } catch (e) {
        console.error('Gagal memuat nomor pembayaran:', e);
    }
}



// -------------------------------------------------------------
// BANK TUJUAN VENDOR COMBOBOX (PERSIS BANK PENGIRIM & DINAMIS DARI VENDOR)
// -------------------------------------------------------------
const DEFAULT_BANK_LIST = [
    'BCA', 'Bank Mandiri', 'BRI', 'BNI', 'CIMB Niaga', 
    'BSI', 'Bank Danamon', 'Bank Permata', 'Bank Jatim', 'Bank BJB',
    'Bank Mega', 'Bank Sinarmas', 'Bank BTN', 'Bank OCBC NISP',
    'CASH', 'QRIS'
];

function updateBankTujuanOptions(fakturBank, masterBank) {
    const menu = document.getElementById('bankTujuanMenu');
    if (!menu) return;

    let html = '';

    // 1. Opsi dari Faktur PO (Prioritas 1)
    if (fakturBank && fakturBank.trim() !== '') {
        const fBankTrim = fakturBank.trim();
        html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt fw-bold text-primary bg-primary-subtle mb-1" onclick="selectBankTujuan('${escapeHtml(fBankTrim)}')">
            <i class="bi bi-receipt me-1"></i> ${escapeHtml(fBankTrim)} <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">Dari Faktur</span>
        </button>`;
    }

    // 2. Opsi dari Master Vendor (Prioritas 2 / Pilihan Alternatif)
    if (masterBank && masterBank.trim() !== '' && masterBank.trim().toLowerCase() !== (fakturBank || '').trim().toLowerCase()) {
        const mBankTrim = masterBank.trim();
        html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt fw-bold text-success bg-success-subtle mb-1" onclick="selectBankTujuan('${escapeHtml(mBankTrim)}')">
            <i class="bi bi-building me-1"></i> ${escapeHtml(mBankTrim)} <span class="badge bg-success ms-1" style="font-size: 0.65rem;">Master Vendor</span>
        </button>`;
    }

    // 3. Daftar Bank Umum Standar
    DEFAULT_BANK_LIST.forEach(b => {
        const isFaktur = fakturBank && (b.toLowerCase() === fakturBank.trim().toLowerCase());
        const isMaster = masterBank && (b.toLowerCase() === masterBank.trim().toLowerCase());
        if (!isFaktur && !isMaster) {
            html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('${escapeHtml(b)}')">${escapeHtml(b)}</button>`;
        }
    });

    html += `<div id="noBankTujuanFound" class="text-muted small px-3 py-2 d-none">Gunakan nama bank yang diketik manual.</div>`;
    menu.innerHTML = html;
}

function showBankTujuanDropdown() {
    document.getElementById('bankTujuanMenu').style.display = 'block';
}
function hideBankTujuanDropdown() {
    document.getElementById('bankTujuanMenu').style.display = 'none';
}
function toggleBankTujuanDropdown(e) {
    if (e) e.stopPropagation();
    const m = document.getElementById('bankTujuanMenu');
    m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
function filterBankTujuanDropdown() {
    const query = (document.getElementById('bankTujuan').value || '').toLowerCase().trim();
    showBankTujuanDropdown();
    const items = document.querySelectorAll('.bank-t-opt');
    let count = 0;
    items.forEach(el => {
        const text = el.textContent.toLowerCase();
        if (text.includes(query)) {
            el.style.display = 'block';
            count++;
        } else {
            el.style.display = 'none';
        }
    });

    const noFound = document.getElementById('noBankTujuanFound');
    if (noFound) {
        if (count === 0) {
            noFound.classList.remove('d-none');
        } else {
            noFound.classList.add('d-none');
        }
    }
}
function selectBankTujuan(val) {
    document.getElementById('bankTujuan').value = val;
    hideBankTujuanDropdown();
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#bankTujuanWrapper')) hideBankTujuanDropdown();
    if (!e.target.closest('#fakturSelectWrapper')) hideFakturDropdown();
});

// -------------------------------------------------------------
// UPLOAD BUKTI BAYAR HANDLER
// -------------------------------------------------------------
function handleProofUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            showToast('Ukuran file maksimal 5MB.', 'warning');
            input.value = '';
            document.getElementById('hiddenBuktiBase64').value = '';
            document.getElementById('buktiFileInfo').textContent = 'Format: PDF, JPG, JPEG, PNG (Maks. 5MB)';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('hiddenBuktiBase64').value = e.target.result;
            document.getElementById('buktiFileInfo').innerHTML = `<span class="text-success fw-semibold">File terpilih: ${file.name}</span>`;
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('hiddenBuktiBase64').value = '';
        document.getElementById('buktiFileInfo').textContent = 'Format: PDF, JPG, JPEG, PNG (Maks. 5MB)';
    }
}

// -------------------------------------------------------------
// MODAL VERIFIKASI & KONFIRMASI PEMBAYARAN (SEPERTI PO)
// -------------------------------------------------------------
function openConfirmPaymentModal() {
    const idFaktur = parseInt(document.getElementById('selectedIdFaktur').value);
    if (!idFaktur) {
        showToast('Silakan pilih dokumen Faktur PO terlebih dahulu pada Tab 1.', 'warning');
        goToTab('tab-faktur');
        return;
    }

    const nominal = parseRawNumber(document.getElementById('nominalPengiriman').value);
    const nominalDiskon = parseRawNumber(document.getElementById('nominalDiskon').value);
    const keteranganDiskon = document.getElementById('keteranganDiskon').value.trim();

    if (nominal <= 0 && nominalDiskon <= 0) {
        showToast('Nominal pembayaran transfer atau potongan diskon harus lebih besar dari 0.', 'warning');
        goToTab('tab-nominal');
        document.getElementById('nominalPengiriman').focus();
        return;
    }

    const bankPengirim = document.getElementById('bankPengirim').value.trim();
    if (!bankPengirim) {
        showToast('Nama Bank Pengirim wajib diisi pada Tab 4.', 'warning');
        goToTab('tab-rekening');
        document.getElementById('bankPengirim').focus();
        return;
    }

    const idApprover = document.getElementById('selectApprover').value;
    if (!idApprover) {
        showToast('Silakan pilih Pejabat / Finance yang menyetujui transfer secara lisan pada Tab 5.', 'warning');
        goToTab('tab-approval');
        document.getElementById('selectApprover').focus();
        return;
    }

    const biayaAdmin = parseRawNumber(document.getElementById('biayaAdmin').value);
    const kodeBayar = document.getElementById('inputKodePembayaran') ? document.getElementById('inputKodePembayaran').value.trim() : 'AUTO';
    const isLunas = document.getElementById('jenisLunas').checked;
    const tglBayar = document.getElementById('tanggalBayar').value;

    const approverSelect = document.getElementById('selectApprover');
    const approverText = approverSelect.options[approverSelect.selectedIndex]?.textContent || '-';

    const norekPengirim = document.getElementById('norekPengirim').value.trim();
    const bankTujuan = document.getElementById('bankTujuan').value.trim() || 'CASH';
    const norekTujuan = document.getElementById('norekTujuan').value.trim();

    // 1. Populate Modal Summary
    document.getElementById('verifyKodePaymentDisplay').textContent = kodeBayar;
    document.getElementById('verifyFakturVendorDisplay').textContent = currentSelectedFaktur ? `${currentSelectedFaktur.nomor_faktur} (${currentSelectedFaktur.nama_vendor})` : '-';
    document.getElementById('verifyTotalKasKeluarDisplay').textContent = formatRupiah(nominal + biayaAdmin);

    // 2. Populate 6 Parameter Verification Badges
    document.getElementById('verifyValFaktur').textContent = currentSelectedFaktur ? `${currentSelectedFaktur.nomor_faktur} (${currentSelectedFaktur.term_of_payment || 0} Hari TOP)` : '-';
    document.getElementById('verifyValSkema').textContent = `${isLunas ? '1x Bayar (Lunas)' : 'Kredit / Sebagian'} (${formatDate(tglBayar)})`;
    document.getElementById('verifyValNominal').textContent = formatRupiah(nominal);
    document.getElementById('verifyValDiskonAdmin').textContent = `Diskon: ${nominalDiskon > 0 ? formatRupiah(nominalDiskon) : '0'} | Admin: ${biayaAdmin > 0 ? formatRupiah(biayaAdmin) : '0'}`;
    document.getElementById('verifyValRekVendor').textContent = `${bankTujuan} ${norekTujuan ? '- ' + norekTujuan : ''}`;
    document.getElementById('verifyValBankApprover').textContent = `${bankPengirim} (${approverText.split(' ')[0]})`;

    // 3. Reset Checkboxes & Password
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
    document.getElementById('btnFinalSubmitPayment').disabled = true;

    // 4. Open Modal
    if (!modalVerifyPaymentInstance) {
        modalVerifyPaymentInstance = new bootstrap.Modal(document.getElementById('modalVerifyPayment'));
    }
    modalVerifyPaymentInstance.show();
}

function toggleCheckAllVerify(checkAll) {
    document.querySelectorAll('.verify-check-item').forEach(cb => {
        cb.checked = checkAll;
    });
    checkVerifyCompleteness();
}

function checkVerifyCompleteness() {
    const checkboxes = document.querySelectorAll('.verify-check-item');
    let allChecked = true;
    checkboxes.forEach(cb => {
        if (!cb.checked) allChecked = false;
    });

    const passwordVal = document.getElementById('inputVerifyPassword').value.trim();
    const hasPassword = passwordVal.length > 0;

    const btn = document.getElementById('btnFinalSubmitPayment');
    if (btn) {
        btn.disabled = !(allChecked && hasPassword);
    }
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

async function submitFinalPayment() {
    const passwordVal = document.getElementById('inputVerifyPassword').value.trim();
    if (!passwordVal) {
        showToast('Password otorisasi akun wajib dimasukkan.', 'warning');
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

    const idFaktur = parseInt(document.getElementById('selectedIdFaktur').value);
    const nominal = parseRawNumber(document.getElementById('nominalPengiriman').value);
    const nominalDiskon = parseRawNumber(document.getElementById('nominalDiskon').value);
    const keteranganDiskon = document.getElementById('keteranganDiskon').value.trim();
    const bankPengirim = document.getElementById('bankPengirim').value.trim();
    const idApprover = document.getElementById('selectApprover').value;

    const payload = {
        id_faktur: idFaktur,
        confirm_password: passwordVal,
        kode_pembayaran: document.getElementById('inputKodePembayaran') ? document.getElementById('inputKodePembayaran').value.trim() : '',
        jenis_pembayaran: document.getElementById('jenisLunas').checked ? 1 : 0,
        tanggal_bayar: document.getElementById('tanggalBayar').value,
        nominal_pengiriman: nominal,
        nominal_diskon: nominalDiskon,
        keterangan_diskon: keteranganDiskon,
        biayaAdmin: parseRawNumber(document.getElementById('biayaAdmin').value),
        biaya_admin: parseRawNumber(document.getElementById('biayaAdmin').value),
        bank_pengirim: bankPengirim,
        norek_pengirim: document.getElementById('norekPengirim').value.trim(),
        an_pengirim: document.getElementById('anPengirim').value.trim(),
        bank_tujuan: document.getElementById('bankTujuan').value.trim(),
        norek_tujuan: document.getElementById('norekTujuan').value.trim(),
        an_pengiriman: document.getElementById('anPengiriman').value.trim(),
        no_ref: document.getElementById('noRef').value.trim(),
        id_karyawan_approved: parseInt(idApprover),
        file_bukti_bayar_base64: document.getElementById('hiddenBuktiBase64').value,
        keterangan: document.getElementById('keteranganPayment').value.trim()
    };

    const btn = document.getElementById('btnFinalSubmitPayment');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memverifikasi &amp; Menyimpan Pembayaran...';

    try {
        const res = await fetch('<?= BASE_URL ?>/api/pembayaran_po/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result && result.success) {
            if (modalVerifyPaymentInstance) modalVerifyPaymentInstance.hide();
            const finalKode = result.data?.kode_pembayaran || payload.kode_pembayaran || '-';
            showSuccessTransactionModal({
                title: 'Pembayaran PO Berhasil Disimpan',
                subtitle: 'Berhasil Disimpan Dengan Nomor :',
                nomor: finalKode,
                redirectUrl: '<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php'
            });
        } else {
            const errorMsg = result ? result.message : 'Gagal menyimpan transaksi pembayaran.';
            showToast(errorMsg, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Konfirmasi &amp; Simpan Pembayaran';

            const pwInput = document.getElementById('inputVerifyPassword');
            const errText = document.getElementById('verifyPasswordErrorText');
            if (pwInput) {
                pwInput.classList.add('is-invalid');
                pwInput.focus();
                pwInput.select();
            }
            if (errText) {
                errText.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${escapeHtml(errorMsg)}`;
                errText.classList.remove('d-none');
            }
        }
    } catch (e) {
        showToast('Terjadi kesalahan jaringan: ' + e.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Konfirmasi &amp; Simpan Pembayaran';
    }
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatRupiah(num) {
    const n = parseFloat(num) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
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
