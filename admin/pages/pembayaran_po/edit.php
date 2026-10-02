<?php
/**
 * Halaman Formulir Edit Pembayaran Faktur PO
 * Path: admin/pages/pembayaran_po/edit.php
 * Khusus Role: FINANCE, PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Edit Pembayaran PO';
$pageHeading = 'Perbarui Data Pembayaran Faktur';

$idDetail = isset($_GET['id']) ? decodeId($_GET['id']) : (isset($_GET['id_detail']) ? decodeId($_GET['id_detail']) : 0);
if ($idDetail <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/pembayaran_po/index.php');
    exit;
}

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
            <h4 class="fw-bold text-dark mb-0">Edit Transaksi Pembayaran PO</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                Kembali ke Riwayat
            </a>
        </div>
    </div>

    <form id="formEditPayment" onsubmit="event.preventDefault(); updatePayment();">
        <input type="hidden" id="editIdDetail" value="<?= $idDetail ?>">
        <input type="hidden" id="hiddenBuktiBase64" name="file_bukti_bayar_base64">

        <!-- CARD TAB MODULAR (5 TAB) -->
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
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Dokumen Faktur Terkait</h6>

                                <!-- Nomor Pembayaran Sistem -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nomor / Kode Pembayaran</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control font-monospace fw-bold text-primary bg-light" id="inputKodePembayaran" readonly placeholder="Memuat kode pembayaran...">
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill"></i></span>
                                    </div>
                                </div>

                                <!-- Box Dokumen Faktur (Locked State) -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Dokumen Faktur PO Terkait</label>
                                    <div class="faktur-custom-select d-flex align-items-center justify-content-between p-2 px-3 border rounded-3 bg-light shadow-none" id="fakturSelectedBox">
                                        <div id="fakturSelectedDisplay" class="text-truncate me-2">
                                            <strong class="font-monospace text-primary" id="dispTriggerNomorFaktur">-</strong>
                                            <span class="text-muted ms-1" id="dispTriggerVendor">-</span>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace px-2 py-1 small">
                                            <i class="bi bi-lock-fill me-1"></i>Terkunci
                                        </span>
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
                                        <span class="fw-bold text-dark small">Riwayat Mutasi &amp; Pembayaran Faktur Ini</span>
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
                                                <th style="min-width: 130px;">Kas / Bank Pengirim</th>
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
                                    <label class="form-label small fw-semibold text-dark">Skema Pembayaran</label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="edit_jenis_pembayaran" id="jenisLunas" value="1" disabled>
                                            <label class="btn btn-outline-success w-100 p-2 text-start rounded-3 opacity-75" for="jenisLunas">
                                                <div class="fw-bold small">1x Bayar (Lunas)</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">Bayar penuh sisa tagihan</div>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="edit_jenis_pembayaran" id="jenisKredit" value="0" disabled>
                                            <label class="btn btn-outline-warning w-100 p-2 text-start rounded-3 opacity-75" for="jenisKredit">
                                                <div class="fw-bold small">Kredit / Sebagian</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">Angsuran / Termin</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Tanggal Pembayaran</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light font-monospace" id="editTanggalBayar" readonly>
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-event"></i></span>
                                    </div>
                                </div>

                                <!-- DISKON PEMBAYARAN -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Diskon / Potongan Pembayaran</label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace text-danger fw-bold">- Rp</span>
                                        <input type="text" class="form-control text-end font-monospace fw-bold text-danger bg-light" id="nominalDiskon" readonly value="0">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Keterangan / Alasan Diskon</label>
                                    <input type="text" class="form-control bg-light" id="keteranganDiskon" readonly placeholder="Tidak ada potongan diskon">
                                </div>
                            </div>

                            <!-- Kolom Kanan: Rincian Nominal, Biaya Admin & Ringkasan -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Nominal &amp; Ringkasan Kalkulasi</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nominal Pembayaran Transfer</label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace fw-bold">Rp</span>
                                        <input type="text" class="form-control text-end font-monospace fw-bold fs-6 text-primary bg-light" id="nominalPengiriman" readonly value="0">
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
                                        <span class="fw-bold small text-dark">Sisa Tagihan Faktur Sesudah:</span>
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
                                <select class="form-select fw-semibold" id="selectRekeningBankPengirim" onchange="handleSelectRekeningBankPengirimEdit(this)" required>
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
                                <label class="form-label small fw-semibold text-dark">Ganti / Upload Bukti Transfer Baru</label>
                                <input type="file" class="form-control" id="fileBuktiBayar" accept="image/*,application/pdf" onchange="handleProofUpload(this)">
                                <div class="form-text small text-muted" id="buktiFileInfo">Format: PDF, JPG, JPEG, PNG (Maks. 5MB)</div>
                                <div id="currentProofWrapper" class="mt-2" style="display: none;"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Catatan / Keterangan Pembayaran</label>
                                <textarea class="form-control" id="keteranganPayment" name="keterangan" rows="3" placeholder="Catatan transaksi..."></textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-rekening')">
                                Kembali ke Kas &amp; Rekening Pengirim
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-semibold" id="btnUpdatePayment" style="height: 38px; display: inline-flex; align-items: center;">
                                <i class="bi bi-check-circle-fill me-1"></i> Perbarui Transaksi Pembayaran
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<script>
const idDetail = <?= $idDetail ?>;
let paymentData = null;
let rekeningBankPerusahaanList = [];
let approverList = [];

document.addEventListener('DOMContentLoaded', () => {
    loadEditData();
    
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#bankTujuanWrapper')) hideBankTujuanDropdown();
    });
});

function goToTab(tabId) {
    const btn = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (btn) {
        const tab = new bootstrap.Tab(btn);
        tab.show();
    }
}

async function loadEditData() {
    try {
        const [resLookup, resBank, resDetail] = await Promise.all([
            fetch('<?= BASE_URL ?>/api/pembayaran_po/lookup_faktur.php'),
            fetch('<?= BASE_URL ?>/api/master/rekening_bank.php?all=1'),
            fetch(`<?= BASE_URL ?>/api/pembayaran_po/index.php?id_detail=${idDetail}`)
        ]);

        const resL = await resLookup.json();
        const resB = await resBank.json();
        const result = await resDetail.json();

        if (resL && resL.success && resL.data && resL.data.approvers) {
            approverList = resL.data.approvers;
            renderApproverOptions(approverList);
        }

        if (resB && resB.success && resB.data && resB.data.items) {
            rekeningBankPerusahaanList = resB.data.items || [];
            renderRekeningBankOptions(rekeningBankPerusahaanList);
        } else {
            renderRekeningBankOptions([]);
        }

        if (result && result.success && result.data) {
            paymentData = result.data;
            populateForm(paymentData);
        } else {
            showToast(result.message || 'Gagal memuat data pembayaran.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
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
}

function handleSelectRekeningBankPengirimEdit(sel) {
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

function populateForm(d) {
    // Tab 1 Identifiers
    document.getElementById('inputKodePembayaran').value = d.kode_pembayaran;
    document.getElementById('dispTriggerNomorFaktur').textContent = d.nomor_faktur;
    document.getElementById('dispTriggerVendor').textContent = `| ${d.nama_vendor} | Sisa: ${formatRupiah(d.sisa_tagihan_faktur)}`;

    // Tab 1 Summary
    document.getElementById('dispNomorFaktur').textContent = d.nomor_faktur;
    document.getElementById('dispNomorFakturVendor').textContent = d.nomor_faktur_vendor || '-';
    document.getElementById('dispNamaVendor').textContent = d.nama_vendor;
    document.getElementById('dispTanggalJatuhTempo').textContent = `${formatDate(d.tanggal_jatuh_tempo)}`;

    document.getElementById('dispTotalTagihan').textContent = formatRupiah(d.total_tagihan);
    document.getElementById('dispTerbayar').textContent = formatRupiah(d.total_terbayar_faktur);
    document.getElementById('dispSisaTagihan').textContent = formatRupiah(d.sisa_tagihan_faktur);

    // Tab 1 History Table
    const histBody = document.getElementById('fakturHistoryTableBody');
    const histContainer = document.getElementById('fakturHistoryContainer');
    const histCount = document.getElementById('fakturHistoryCount');
    const histTotalTerbayar = document.getElementById('fakturHistoryTotalTerbayar');
    const histTotalDiskon = document.getElementById('fakturHistoryTotalDiskon');

    if (d.history_pembayaran && d.history_pembayaran.length > 0) {
        let histHtml = '';
        let totalNominal = 0;
        let totalDiskon = 0;
        d.history_pembayaran.forEach((h, idx) => {
            const nominal = parseFloat(h.nominal_pengiriman) || 0;
            const diskon = parseFloat(h.nominal_diskon) || 0;
            const sisa = parseFloat(h.sisa_piutang) || 0;
            totalNominal += nominal;
            totalDiskon += diskon;
            const isCurrent = parseInt(h.id_pembayaran_detail) === idDetail;
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
        if (histCount) histCount.textContent = `${d.history_pembayaran.length} Transaksi`;
        if (histTotalTerbayar) histTotalTerbayar.textContent = formatRupiah(totalNominal);
        if (histTotalDiskon) histTotalDiskon.textContent = formatRupiah(totalDiskon);
        histContainer.style.display = 'block';
    } else {
        histContainer.style.display = 'none';
    }

    // Tab 2 Rincian Pembayaran
    if (parseInt(d.jenis_pembayaran) === 1) {
        document.getElementById('jenisLunas').checked = true;
    } else {
        document.getElementById('jenisKredit').checked = true;
    }

    document.getElementById('editTanggalBayar').value = formatDateTime(d.tanggal_bayar);
    document.getElementById('nominalPengiriman').value = formatThousands(d.nominal_pengiriman);
    
    const diskonVal = parseFloat(d.nominal_diskon) || 0;
    document.getElementById('nominalDiskon').value = formatThousands(diskonVal);
    document.getElementById('keteranganDiskon').value = d.keterangan_diskon || (diskonVal > 0 ? 'Potongan Diskon' : 'Tidak ada potongan diskon');

    // Biaya Admin & Transfer Type Preset
    const biayaAdminNum = parseRawNumber(d.biaya_admin);
    document.getElementById('biayaAdmin').value = formatThousands(biayaAdminNum);
    syncSelectJenisTransferWithFee(biayaAdminNum);

    // Tab 3 Rekening Vendor Tujuan
    updateBankTujuanOptions(d.bank_vendor || '', d.bank_vendor_master || '');
    document.getElementById('bankTujuan').value = d.bank_tujuan || d.bank_vendor || '';
    document.getElementById('norekTujuan').value = d.norek_tujuan || d.norek_vendor || '';
    document.getElementById('anPengiriman').value = d.an_pengiriman || d.an_vendor || d.nama_vendor || '';

    // Tab 4 Rekening Pengirim
    document.getElementById('bankPengirim').value = d.bank_pengirim || '';
    document.getElementById('norekPengirim').value = d.norek_pengirim || '';
    document.getElementById('anPengirim').value = d.an_pengirim || '';
    document.getElementById('noRef').value = d.no_ref || '';

    // Match Rekening Bank Pengirim
    const selRek = document.getElementById('selectRekeningBankPengirim');
    if (selRek && rekeningBankPerusahaanList.length > 0) {
        for (let i = 0; i < selRek.options.length; i++) {
            const opt = selRek.options[i];
            const optBank = opt.getAttribute('data-bank') || '';
            const optNorek = opt.getAttribute('data-norek') || '';
            if (d.bank_pengirim && optBank.toLowerCase() === d.bank_pengirim.toLowerCase() && (!optNorek || optNorek === '-' || optNorek === (d.norek_pengirim || ''))) {
                selRek.selectedIndex = i;
                break;
            }
        }
    }

    // Tab 5 Approval & Bukti
    if (d.id_karyawan_approved) {
        document.getElementById('selectApprover').value = d.id_karyawan_approved;
    }
    document.getElementById('keteranganPayment').value = d.keterangan || '';

    if (d.file_bukti_bayar) {
        const wrap = document.getElementById('currentProofWrapper');
        wrap.style.display = 'block';
        wrap.innerHTML = `<span class="small text-muted me-2">Bukti saat ini:</span> <a href="<?= BASE_URL ?>/uploads/pembayaran/${d.file_bukti_bayar}" target="_blank" class="btn btn-outline-primary btn-sm px-2 py-1"><i class="bi bi-file-earmark-check me-1"></i>Lihat File</a>`;
    }

    // Hitung Estimasi Finansial
    calculateFinancialSummary();
}

function applyRekeningFromFaktur() {
    if (!paymentData) return;
    const d = paymentData;
    let vendorBank = d.bank_vendor || d.nama_bank || '';
    let vendorNorek = d.norek_vendor || d.nomor_rekening || '';
    let vendorAn = d.an_vendor || d.atas_nama_rekening || d.nama_vendor || '';

    if (!vendorBank && d.bank_vendor_master) {
        vendorBank = d.bank_vendor_master;
        vendorNorek = d.norek_vendor_master || '';
    }

    document.getElementById('bankTujuan').value = vendorBank;
    document.getElementById('norekTujuan').value = vendorNorek;
    document.getElementById('anPengiriman').value = vendorAn;
}

function applyRekeningFromMasterVendor() {
    if (!paymentData) return;
    const d = paymentData;
    let vendorBank = d.bank_vendor_master || d.bank_vendor || d.nama_bank || '';
    let vendorNorek = d.norek_vendor_master || d.norek_vendor || d.nomor_rekening || '';
    let vendorAn = d.an_vendor || d.atas_nama_rekening || d.nama_vendor || '';

    document.getElementById('bankTujuan').value = vendorBank;
    document.getElementById('norekTujuan').value = vendorNorek;
    document.getElementById('anPengiriman').value = vendorAn;
}

// -------------------------------------------------------------
// TRANSFER TYPE & BIAYA ADMIN
// -------------------------------------------------------------
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
    
    if (val !== 'custom' && fee !== null && fee !== '') {
        const numFee = parseInt(fee, 10) || 0;
        adminInput.value = numFee > 0 ? numFee.toLocaleString('id-ID') : '0';
    }
    
    calculateFinancialSummary();
}

function handleBiayaAdminInput(input) {
    let raw = parseRawNumber(input.value);
    input.value = raw > 0 ? raw.toLocaleString('id-ID') : '0';
    syncSelectJenisTransferWithFee(raw);
    calculateFinancialSummary();
}

function syncSelectJenisTransferWithFee(feeNum) {
    const sel = document.getElementById('selectJenisTransfer');
    if (!sel) return;
    let matched = false;
    for (let i = 0; i < sel.options.length; i++) {
        const optVal = sel.options[i].value;
        const optFee = sel.options[i].getAttribute('data-fee');
        if (optVal !== 'custom' && optFee !== null && optFee !== '' && parseInt(optFee, 10) === feeNum) {
            sel.selectedIndex = i;
            matched = true;
            break;
        }
    }
    if (!matched) {
        sel.value = 'custom';
    }
}

function calculateFinancialSummary() {
    if (!paymentData) return;
    const d = paymentData;
    const totalTagihan = parseFloat(d.total_tagihan) || 0;
    const nominalBayar = parseRawNumber(document.getElementById('nominalPengiriman').value);
    const nominalDiskon = parseRawNumber(document.getElementById('nominalDiskon').value);
    const biayaAdmin = parseRawNumber(document.getElementById('biayaAdmin').value);
    const sisaPiutang = parseFloat(d.sisa_piutang) || 0;

    document.getElementById('calcTagihanAwal').textContent = formatRupiah(totalTagihan);
    document.getElementById('calcNominalBayar').textContent = formatRupiah(nominalBayar);
    document.getElementById('calcNominalDiskon').textContent = formatRupiah(nominalDiskon);
    document.getElementById('displaySisaSesudah').textContent = formatRupiah(sisaPiutang);
    document.getElementById('calcTotalKasKeluar').textContent = formatRupiah(nominalBayar + biayaAdmin);
}

// -------------------------------------------------------------
// BANK TUJUAN VENDOR COMBOBOX (PERSIS CREATE.PHP)
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

    if (fakturBank && fakturBank.trim() !== '') {
        const fBankTrim = fakturBank.trim();
        html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt fw-bold text-primary bg-primary-subtle mb-1" onclick="selectBankTujuan('${escapeHtml(fBankTrim)}')">
            <i class="bi bi-receipt me-1"></i> ${escapeHtml(fBankTrim)} <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">Dari Faktur</span>
        </button>`;
    }

    if (masterBank && masterBank.trim() !== '' && masterBank.trim().toLowerCase() !== (fakturBank || '').trim().toLowerCase()) {
        const mBankTrim = masterBank.trim();
        html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt fw-bold text-success bg-success-subtle mb-1" onclick="selectBankTujuan('${escapeHtml(mBankTrim)}')">
            <i class="bi bi-building me-1"></i> ${escapeHtml(mBankTrim)} <span class="badge bg-success ms-1" style="font-size: 0.65rem;">Master Vendor</span>
        </button>`;
    }

    DEFAULT_BANK_LIST.forEach(b => {
        const isFaktur = fakturBank && (b.toLowerCase() === fakturBank.trim().toLowerCase());
        const isMaster = masterBank && (b.toLowerCase() === masterBank.trim().toLowerCase());
        if (!isFaktur && !isMaster) {
            html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('${escapeHtml(b)}')">${escapeHtml(b)}</button>`;
        }
    });

    menu.innerHTML = html;
}

function showBankTujuanDropdown() { document.getElementById('bankTujuanMenu').style.display = 'block'; }
function hideBankTujuanDropdown() { document.getElementById('bankTujuanMenu').style.display = 'none'; }
function toggleBankTujuanDropdown(e) {
    if (e) e.stopPropagation();
    const m = document.getElementById('bankTujuanMenu');
    m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
function filterBankTujuanDropdown() {
    const query = (document.getElementById('bankTujuan').value || '').toLowerCase().trim();
    showBankTujuanDropdown();
    const items = document.querySelectorAll('.bank-t-opt');
    items.forEach(el => {
        const text = el.textContent.toLowerCase();
        el.style.display = text.includes(query) ? 'block' : 'none';
    });
}
function selectBankTujuan(val) {
    document.getElementById('bankTujuan').value = val;
    hideBankTujuanDropdown();
}

// -------------------------------------------------------------
// UPLOAD FILE BUKTI BAYAR HANDLER
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
            document.getElementById('buktiFileInfo').innerHTML = `<span class="text-success fw-semibold">File baru terpilih: ${file.name}</span>`;
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('hiddenBuktiBase64').value = '';
        document.getElementById('buktiFileInfo').textContent = 'Format: PDF, JPG, JPEG, PNG (Maks. 5MB)';
    }
}

// -------------------------------------------------------------
// SUBMIT UPDATE (PUT)
// -------------------------------------------------------------
async function updatePayment() {
    const bankPengirim = document.getElementById('bankPengirim').value.trim();
    if (!bankPengirim) {
        showToast('Nama Bank Pengirim wajib diisi pada Tab 4.', 'warning');
        goToTab('tab-rekening');
        return;
    }

    const idApprover = document.getElementById('selectApprover').value;
    if (!idApprover) {
        showToast('Silakan pilih Pejabat / Finance yang menyetujui transfer secara lisan pada Tab 5.', 'warning');
        goToTab('tab-approval');
        return;
    }

    const payload = {
        id_pembayaran_detail: idDetail,
        bank_pengirim: bankPengirim,
        norek_pengirim: document.getElementById('norekPengirim').value.trim(),
        an_pengirim: document.getElementById('anPengirim').value.trim(),
        bank_tujuan: document.getElementById('bankTujuan').value.trim(),
        norek_tujuan: document.getElementById('norekTujuan').value.trim(),
        an_pengiriman: document.getElementById('anPengiriman').value.trim(),
        no_ref: document.getElementById('noRef').value.trim(),
        biaya_admin: parseRawNumber(document.getElementById('biayaAdmin').value),
        id_karyawan_approved: parseInt(idApprover),
        file_bukti_bayar_base64: document.getElementById('hiddenBuktiBase64').value,
        keterangan: document.getElementById('keteranganPayment').value.trim()
    };

    const btn = document.getElementById('btnUpdatePayment');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...';

    try {
        const res = await fetch('<?= BASE_URL ?>/api/pembayaran_po/index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result && result.success) {
            showToast(result.message || 'Data pembayaran berhasil diperbarui!', 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php';
            }, 1200);
        } else {
            showToast(result.message || 'Gagal memperbarui data pembayaran.', 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Perbarui Transaksi Pembayaran';
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Perbarui Transaksi Pembayaran';
    }
}

// -------------------------------------------------------------
// HELPER FUNCTIONS
// -------------------------------------------------------------
function formatThousands(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    if (!clean) return '0';
    let n = parseInt(clean, 10);
    return n.toLocaleString('id-ID');
}

function parseRawNumber(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    return parseInt(clean, 10) || 0;
}

function formatRupiah(num) {
    const n = parseFloat(num) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatDateTime(dateStr) {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} ${hh}:${mm}`;
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
