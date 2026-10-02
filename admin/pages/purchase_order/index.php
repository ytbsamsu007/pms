<?php
/**
 * Halaman Daftar Purchase Order (PO)
 * Path: admin/pages/purchase_order/index.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Khusus Purchasing, Manager, dan Admin (Logistik diblokir)
$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Daftar Purchase Order (PO)';
$pageHeading = 'Purchase Order (PO)';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- KONTEN UTAMA -->
<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Purchase Order (PO)</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="loadPoList(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- FILTER & PENCARIAN -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 po-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-3 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari No. PO / Vendor / Pembuat..." autocomplete="off">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 col-lg-2">
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">Draft</option>
                        <option value="REVIEW INTERNAL">Review Internal</option>
                        <option value="DISETUJUI INTERNAL">Disetujui Internal</option>
                        <option value="DIPROSES VENDOR">Diproses Vendor</option>
                        <option value="BATAL">Batal</option>
                    </select>
                </div>

                <!-- Site Filter -->
                <div class="col-md-2 col-lg-2">
                    <select class="form-select form-select-sm text-truncate" id="filterSite" style="text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" title="Filter Site">
                        <option value="">Semua Site / Lokasi</option>
                    </select>
                </div>

                <!-- Range Tanggal: Dari Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 170px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Dari Tanggal" onclick="const el=document.getElementById('filterStartDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterStartDate" placeholder="mm / dd / yyyy" title="Dari Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="applyFilters()">
                    </div>
                </div>

                <!-- Range Tanggal: Sampai Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 170px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Sampai Tanggal" onclick="const el=document.getElementById('filterEndDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-check"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterEndDate" placeholder="mm / dd / yyyy" title="Sampai Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="applyFilters()">
                    </div>
                </div>

                <!-- Tombol Reset (Rata Kanan) -->
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .po-filter-bar .form-control,
    .po-filter-bar .form-select,
    .po-filter-bar .input-group-text,
    .po-filter-bar .btn {
        height: 36px;
        font-size: 0.85rem;
    }
    .po-filter-bar .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: 10px;
        padding-right: 10px;
    }
    .po-filter-bar .form-select {
        padding-top: 0.15rem !important;
        padding-bottom: 0.35rem !important;
        padding-right: 2rem !important;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        line-height: 1.5 !important;
    }
    .po-filter-bar .form-control {
        padding-top: 0.22rem !important;
        padding-bottom: 0.28rem !important;
        line-height: 1.5 !important;
    }
    .po-filter-bar input[type="date"] {
        -moz-appearance: textfield !important;
        appearance: none !important;
        padding-top: 0.22rem !important;
        padding-bottom: 0.28rem !important;
    }
    .po-filter-bar input[type="date"]::-webkit-calendar-picker-indicator {
        display: none !important;
        -webkit-appearance: none !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .table-container {
        overflow: visible !important;
        position: relative;
    }
    #modalPoTabNav {
        border-bottom: 0 !important;
        margin-bottom: -1px !important;
    }
    #modalPoTabNav .nav-link {
        border: 1px solid transparent !important;
        border-top-left-radius: 8px !important;
        border-top-right-radius: 8px !important;
        color: #475569 !important;
        background: transparent !important;
        margin-bottom: -1px !important;
        padding: 0.65rem 1.15rem !important;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    #modalPoTabNav .nav-link:hover {
        border-color: #e2e8f0 #e2e8f0 transparent !important;
        color: #0d6efd !important;
        background: #f8fafc !important;
    }
    #modalPoTabNav .nav-link.active {
        color: #0f172a !important;
        background-color: #ffffff !important;
        border-color: #e2e8f0 #e2e8f0 #ffffff !important;
        border-bottom: 2px solid #ffffff !important;
        font-weight: 700 !important;
    }
    .po-info-card {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px;
    }
    .po-info-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .po-info-val {
        color: #1e293b;
        font-size: 0.9rem;
    }
    </style>

    <!-- TABEL DATA PURCHASE ORDER -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="poTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 140px;">Nomor</th>
                            <th style="min-width: 170px;">Vendor</th>
                            <th style="width: 110px;" class="text-center">Prioritas</th>
                            <th style="width: 150px;" class="text-center">Status</th>
                            <th style="min-width: 140px;">Site</th>
                            <th style="min-width: 130px;" class="text-end">Total</th>
                            <th style="width: 75px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="poTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Purchase Order...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FOOTER PAGINATION -->
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small" id="paginationInfo">Menampilkan 0 data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL PURCHASE ORDER (DESAIN ELEGAN & BERSIH) -->
<div class="modal fade" id="modalDetailPo" tabindex="-1" aria-labelledby="modalDetailPoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 960px; width: 100%;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- MODAL HEADER -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Purchase Order Detail</span>
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalDetailPoLabel">
                            <span id="detailNomorPo">PO-XXXX-XXXX</span>
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="detailPrioritasBadgeHeader"></span>
                        <div id="detailStatusBadgeHeader"></div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Nav Tabs Modal Sesuai Fungsi -->
                <ul class="nav nav-tabs border-bottom-0" id="modalPoTabNav" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="tab-m-info-btn" data-bs-toggle="tab" data-bs-target="#tab-m-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1"></i> 1. Informasi PO &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-m-shipping-btn" data-bs-toggle="tab" data-bs-target="#tab-m-shipping" type="button" role="tab">
                            <i class="bi bi-truck me-1"></i> 2. Pengiriman &amp; Pembayaran
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-m-items-btn" data-bs-toggle="tab" data-bs-target="#tab-m-items" type="button" role="tab">
                            <i class="bi bi-boxes me-1"></i> 3. Rincian Barang &amp; Finansial
                            <span class="badge bg-primary text-white ms-1 rounded-pill px-2" id="modalItemCountBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 3 TAB PANE -->
            <div class="modal-body p-4 bg-light-subtle">
                <div class="tab-content" id="modalPoTabContent">
                    
                    <!-- TAB 1: INFORMASI PO & VENDOR -->
                    <div class="tab-pane fade show active" id="tab-m-info" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="po-info-card p-3 h-100 shadow-xs">
                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                        Dokumen Purchase Order
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <div class="po-info-label">Tanggal PO</div>
                                        <div class="po-info-val fw-bold font-monospace" id="detailTanggalPo">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="po-info-label">Referensi Request Order (RO)</div>
                                        <div class="po-info-val">
                                            <span class="badge bg-secondary-subtle text-dark border font-monospace" id="detailReferensiRo">-</span>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="po-info-label">Prioritas Dokumen</div>
                                        <div id="detailPrioritasBadge">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="po-info-label">Dibuat Oleh (Purchasing)</div>
                                        <div class="po-info-val fw-semibold" id="detailPembuat">-</div>
                                    </div>
                                    <div>
                                        <div class="po-info-label">Disetujui Oleh (Approver)</div>
                                        <div class="po-info-val fw-semibold text-primary" id="detailApprover">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="po-info-card p-3 h-100 shadow-xs">
                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                        Informasi Vendor / Rekanan
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <div class="po-info-label">Nama Vendor / Perusahaan</div>
                                        <div class="po-info-val fw-bold text-dark fs-6" id="detailVendor">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="po-info-label">Kontak / No. Telepon</div>
                                        <div class="po-info-val fw-semibold font-monospace" id="detailTeleponVendor">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="po-info-label">Email Vendor</div>
                                        <div class="po-info-val" id="detailEmailVendor">-</div>
                                    </div>
                                    <div>
                                        <div class="po-info-label">Alamat Lengkap Vendor</div>
                                        <div class="po-info-val text-muted small" id="detailAlamatVendor">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="po-info-card p-3 shadow-xs">
                                    <div class="po-info-label mb-1">Catatan &amp; Keterangan PO</div>
                                    <p class="mb-0 text-dark small" id="detailCatatan">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PENGIRIMAN & PEMBAYARAN -->
                    <div class="tab-pane fade" id="tab-m-shipping" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="po-info-card p-3 h-100 shadow-xs">
                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                        Lokasi &amp; Pengiriman
                                    </h6>
                                    
                                    <div class="mb-2">
                                        <div class="po-info-label">Site / Lokasi Tujuan</div>
                                        <div class="po-info-val fw-bold text-dark" id="detailSite">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="po-info-label">Metode Pengiriman</div>
                                        <div class="po-info-val">
                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" id="detailPengiriman">-</span>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <div class="po-info-label">Estimasi Tanggal Tiba</div>
                                        <div class="po-info-val font-monospace" id="detailTanggalKirim">-</div>
                                    </div>
                                    <div>
                                        <div class="po-info-label">Alamat Tujuan Pengiriman</div>
                                        <div class="po-info-val text-muted small" id="detailAlamatKirim">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="po-info-card p-3 h-100 shadow-xs">
                                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                        Ketentuan Pembayaran
                                    </h6>
                                    
                                    <div class="mb-3">
                                        <div class="po-info-label">Term of Payment (T.O.P)</div>
                                        <div class="po-info-val mt-1">
                                            <span class="badge bg-white text-dark border shadow-xs px-3 py-2 fs-6 font-monospace" id="detailTop">-</span>
                                        </div>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: RINCIAN BARANG & BIAYA -->
                    <div class="tab-pane fade" id="tab-m-items" role="tabpanel">
                        <!-- Tabel Item Barang PO -->
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-hover align-middle mb-0 bg-white">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center align-middle">#</th>
                                        <th style="min-width: 260px;" class="align-middle">Barang &amp; Spesifikasi</th>
                                        <th style="width: 90px;" class="text-center align-middle">Kts</th>
                                        <th style="width: 160px;" class="text-end align-middle">Harga Satuan</th>
                                        <th style="width: 140px;" class="text-end align-middle">Diskon Item</th>
                                        <th style="width: 165px;" class="text-end align-middle">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="detailItemsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">Memuat daftar barang...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Financial Calculation Card (Konsisten dengan Proses PO) -->
                        <div class="row justify-content-end">
                            <div class="col-md-6 col-lg-5">
                                <div class="p-3 border rounded-3 bg-white shadow-sm">
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Subtotal Barang:</span>
                                        <span class="fw-semibold text-dark font-monospace" id="calcSubtotalBarang">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Diskon Akhir PO:</span>
                                        <span class="fw-semibold text-danger font-monospace" id="calcDiskonPo">- Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">DPP (Dasar Pengenaan Pajak):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="calcDpp">Rp 0</span>
                                    </div>
                                    <!-- Baris Pajak PPnBM (Muncul jika ada PPnBM) -->
                                    <div class="d-flex justify-content-between mb-2 small d-none" id="calcRowPpnbm">
                                        <span class="text-muted" id="calcLabelPpnbm">PPnBM (0%):</span>
                                        <span class="fw-semibold text-warning-emphasis font-monospace" id="calcNominalPpnbm">Rp 0</span>
                                    </div>
                                    <!-- Baris Pajak PPN (11% atau 12%) -->
                                    <div class="d-flex justify-content-between mb-2 small" id="calcRowPpn">
                                        <span class="text-muted" id="calcLabelPpn">PPN (11%):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="calcNominalPajak">Rp 0</span>
                                    </div>
                                    <hr class="my-2 border-secondary-subtle">
                                    <div class="d-flex justify-content-between align-items-center pt-1">
                                        <span class="fw-bold text-dark fs-6">GRAND TOTAL:</span>
                                        <span class="fs-5 fw-bold text-primary font-monospace" id="calcGrandTotal">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VERIFIKASI PEMBATALAN PURCHASE ORDER & PENERBITAN BAP -->
<div class="modal fade" id="modalCancelPo" tabindex="-1" aria-labelledby="modalCancelPoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px; width: 100%;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-danger text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0" id="modalCancelPoLabel">Batalkan Purchase Order (BAP)</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCancelPo" onsubmit="submitCancelPo(event)">
                <input type="hidden" id="cancelPoId" value="">
                <div class="modal-body p-4">
                    <!-- Alert Dokumen Target -->
                    <div class="alert alert-danger bg-danger-subtle border-danger-subtle d-flex align-items-start gap-2 mb-3 py-2 px-3">
                        <i class="bi bi-info-circle-fill text-danger fs-5 mt-1 flex-shrink-0"></i>
                        <div class="small">
                            <div>Anda akan membatalkan <strong>Purchase Order</strong>:</div>
                            <div class="fw-bold font-monospace text-dark fs-6" id="cancelPoNomorDisplay">PO-XXXX-XXXX</div>
                            <div class="text-muted" id="cancelPoVendorDisplay">Vendor: -</div>
                            <div class="text-dark font-monospace fw-semibold" id="cancelPoNilaiDisplay">Nilai: Rp 0</div>
                        </div>
                    </div>

                    <!-- Kategori Alasan -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Kategori Alasan Pembatalan <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="cancelPoKategori" required>
                            <option value="">-- Pilih Kategori Alasan --</option>
                            <option value="Vendor Kehabisan Stok / Discontinued">Vendor Kehabisan Stok / Discontinued</option>
                            <option value="Perubahan Spek / Kebutuhan Operasional">Perubahan Spek / Kebutuhan Operasional</option>
                            <option value="Kenaikan Harga Sepihak oleh Vendor">Kenaikan Harga Sepihak oleh Vendor</option>
                            <option value="Keterlambatan Konfirmasi Vendor">Keterlambatan Konfirmasi Vendor</option>
                            <option value="Kesalahan Administrasi / Input Ganda">Kesalahan Administrasi / Input Ganda</option>
                            <option value="Lainnya">Lainnya (Jelaskan pada uraian)</option>
                        </select>
                    </div>

                    <!-- Uraian Kronologis / Berita Acara -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Uraian Kronologis Berita Acara <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-sm" id="cancelPoAlasan" rows="4" placeholder="Jelaskan alasan pembatalan secara detail untuk arsip resmi Berita Acara Pembatalan (BAP)..." required minlength="5"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold" id="btnSubmitCancelPo">
                        <i class="bi bi-x-octagon-fill me-1"></i> Konfirmasi &amp; Terbitkan BAP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL UPDATE STATUS PURCHASE ORDER -->
<div class="modal fade" id="modalUpdateStatusPo" tabindex="-1" aria-labelledby="modalUpdateStatusPoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalUpdateStatusPoLabel">Update Status PO</h6>
                    <div class="text-muted small font-monospace" style="font-size: 0.78rem;" id="updateStatusNomorPo">PO-XXXX-XXXX</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1" id="updateStatusBadgeCurrent">DISETUJUI INTERNAL</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <form id="formUpdateStatusPo" onsubmit="event.preventDefault(); submitUpdateStatusPo();">
                <input type="hidden" id="updateStatusPoId">
                <div class="modal-body p-4">
                    <!-- Pilihan Status Baru -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Status Baru <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="selectNewStatusPo" required onchange="handleNewStatusChange(this.value)">
                            <option value="DIPROSES VENDOR" selected>DIPROSES VENDOR</option>
                            <option value="REVIEW VENDOR">REVIEW VENDOR</option>
                            <option value="REVIEW INTERNAL">REVIEW INTERNAL</option>
                            <option value="TIDAK DISETUJUI INTERNAL">TIDAK DISETUJUI INTERNAL</option>
                            <option value="BATAL">BATAL</option>
                        </select>
                    </div>

                    <!-- Input Estimasi Pengiriman (Muncul jika DIPROSES VENDOR) -->
                    <div class="mb-3" id="wrapperEstimasiPengiriman">
                        <label class="form-label small fw-semibold text-dark">Estimasi Pengiriman</label>
                        <input type="date" class="form-control form-control-sm" id="inputEstimasiPengiriman" value="<?= date('Y-m-d') ?>">
                    </div>

                    <!-- Catatan / Keterangan -->
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-dark">Keterangan</label>
                        <textarea class="form-control form-control-sm" id="inputCatatanStatusPo" rows="3" placeholder="Keterangan opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold" id="btnSubmitUpdateStatusPo">
                        <i class="bi bi-check2-circle me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<!-- Client-side Logic Script for Purchase Order List -->
<script>
const CURRENT_USER_ROLE = '<?= strtoupper($user['role'] ?? '') ?>';
let currentPage = 1;
let currentLimit = 10;
let modalDetailInstance = null;
let modalCancelPoInstance = null;
let modalUpdateStatusInstance = null;

document.addEventListener('DOMContentLoaded', async () => {
    modalDetailInstance = new bootstrap.Modal(document.getElementById('modalDetailPo'));
    modalCancelPoInstance = new bootstrap.Modal(document.getElementById('modalCancelPo'));
    modalUpdateStatusInstance = new bootstrap.Modal(document.getElementById('modalUpdateStatusPo'));

    // Inisialisasi Filter Site
    await loadSiteOptions();

    // Event Listener Filter
    document.getElementById('filterSearch').addEventListener('input', debounce(() => loadPoList(1), 350));
    document.getElementById('filterStatus').addEventListener('change', () => loadPoList(1));
    document.getElementById('filterSite').addEventListener('change', () => loadPoList(1));
    document.getElementById('filterStartDate').addEventListener('change', () => loadPoList(1));
    document.getElementById('filterEndDate').addEventListener('change', () => loadPoList(1));

    // Load Data Awal
    loadPoList(1);
});

// -------------------------------------------------------------
// LOAD DAFTAR SITE FILTER
// -------------------------------------------------------------
async function loadSiteOptions() {
    try {
        const res = await apiRequest('/api/master/site.php?limit=100');
        if (res && res.success && res.data) {
            const select = document.getElementById('filterSite');
            const items = res.data.items || res.data;
            let options = '<option value="">Semua Site / Lokasi</option>';
            if (Array.isArray(items)) {
                items.forEach(site => {
                    options += `<option value="${site.id_site}">${escapeHtml(site.nama_site)}</option>`;
                });
            }
            select.innerHTML = options;
        }
    } catch (e) {
        console.error('Gagal memuat site filter:', e);
    }
}

// -------------------------------------------------------------
// LOAD DATA DAFTAR PURCHASE ORDER
// -------------------------------------------------------------
async function loadPoList(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('poTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Purchase Order...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const status = document.getElementById('filterStatus').value;
    const siteId = document.getElementById('filterSite').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        page: currentPage,
        limit: currentLimit
    });

    if (search) params.append('q', search);
    if (status) params.append('status', status);
    if (siteId) params.append('site_id', siteId);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);

    const res = await apiRequest(`/api/purchase_order/index.php?${params.toString()}`);

    if (!res || !res.success || !res.data) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-circle me-1"></i> ${res ? res.message : 'Gagal memuat data Purchase Order.'}
                </td>
            </tr>
        `;
        return;
    }

    const items = res.data.items || [];
    const pagination = res.data.pagination || { total_records: 0, total_pages: 1, current_page: 1 };

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                    Belum ada dokumen Purchase Order yang ditemukan.
                </td>
            </tr>
        `;
        renderPagination(pagination);
        return;
    }

    let rowsHtml = '';
    const startIndex = (pagination.current_page - 1) * pagination.limit;

    items.forEach((item, idx) => {
        const no = startIndex + idx + 1;
        const statusBadge = renderStatusBadge(item.status, item);
        const nomorPo = item.nomor_po ? escapeHtml(item.nomor_po) : '-';
        const vendorName = item.nama_vendor ? escapeHtml(item.nama_vendor) : '<span class="text-muted italic">Vendor Umum</span>';
        const siteName = item.nama_site ? escapeHtml(item.nama_site) : '-';
        const tgl = item.tanggal_po ? item.tanggal_po.split(' ')[0] : '-';
        const nilaiFormatted = formatNumber(item.grand_total || 0);

        const prio = (item.prioritas || 'NORMAL').toUpperCase();
        const prioBadge = (prio === 'URGENT') 
            ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-1"><i class="bi bi-lightning-fill me-1"></i>URGENT</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace px-2 py-1">NORMAL</span>';

        const statusUpper = (item.status || '').toUpperCase();
        let isEditable = false;
        let lockTooltip = 'PO Terkunci';

        if (CURRENT_USER_ROLE === 'LOGISTIK') {
            isEditable = false;
            lockTooltip = 'Bagian Logistik tidak memiliki wewenang mengedit Purchase Order';
        } else if (['DIPROSES VENDOR', 'DITERIMA', 'BATAL'].includes(statusUpper)) {
            isEditable = false;
            lockTooltip = `PO berstatus ${statusUpper} (Data formulir terkunci)`;
        } else {
            // Status DRAFT, REVIEW INTERNAL, DISETUJUI INTERNAL, TIDAK DISETUJUI INTERNAL, REVIEW VENDOR
            isEditable = true;
        }

        const encPoId = encodeId(item.id_po);
        const editBtnHtml = isEditable 
            ? `<a href="${BASE_URL}/admin/pages/purchase_order/edit.php?id=${encPoId}" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" title="Edit Purchase Order"><i class="bi bi-pencil-fill"></i></a>`
            : `<button type="button" class="btn btn-light btn-sm px-2 py-1 text-muted border opacity-50" disabled title="${lockTooltip}"><i class="bi bi-lock-fill"></i></button>`;

        let printBtnHtml = '';
        if (['DIPROSES VENDOR', 'DITERIMA', 'SELESAI'].includes(statusUpper)) {
            printBtnHtml = `
                <a href="${BASE_URL}/admin/pages/purchase_order/print.php?id=${encPoId}" target="_blank" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" title="Cetak / Download Surat Pesanan Barang (PO)">
                    <i class="bi bi-printer-fill"></i>
                </a>
            `;
        }

        let cancelBtnHtml = '';
        if (!['DITERIMA', 'BATAL'].includes(statusUpper) && ['ADMIN', 'PURCHASING', 'MANAGER'].includes(CURRENT_USER_ROLE)) {
            cancelBtnHtml = `
                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="openCancelPoModal(${item.id_po}, '${escapeHtml(nomorPo)}', '${escapeHtml(item.nama_vendor || '')}', '${escapeHtml(nilaiFormatted)}')" title="Batalkan PO &amp; Terbitkan Berita Acara (BAP)">
                    <i class="bi bi-x-octagon-fill"></i>
                </button>
            `;
        }

        rowsHtml += `
            <tr>
                <td class="text-center font-monospace text-muted small">${no}</td>
                <td>
                    <div class="fw-bold font-monospace text-primary">${nomorPo}</div>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${vendorName}</div>
                </td>
                <td class="text-center">
                    ${prioBadge}
                </td>
                <td class="text-center">
                    ${statusBadge}
                </td>
                <td class="small text-dark">${siteName}</td>
                <td class="text-end font-monospace fw-bold text-dark">
                    ${nilaiFormatted}
                </td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="openDetailModal(${item.id_po})" title="Lihat Rincian PO">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        ${printBtnHtml}
                        ${editBtnHtml}
                        ${cancelBtnHtml}
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    renderPagination(pagination);
}

// -------------------------------------------------------------
// RENDER STATUS BADGE
// -------------------------------------------------------------
function renderStatusBadge(status, item = null) {
    const st = (status || '').toUpperCase();
    if (st === 'DISETUJUI INTERNAL' && item) {
        const idPo = item.id_po;
        const nomorPo = item.nomor_po ? escapeHtml(item.nomor_po) : '';
        const vendorName = item.nama_vendor ? escapeHtml(item.nama_vendor) : '';
        const currentStatus = escapeHtml(item.status || '');
        return `<button type="button" class="btn btn-link p-0 text-decoration-none border-0 bg-transparent" onclick="openUpdateStatusModal(${idPo}, '${nomorPo}', '${vendorName}', '${currentStatus}')" title="Klik untuk Update Status Dokumen PO">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 shadow-xs" style="cursor: pointer; transition: all 0.2s ease;">
                <i class="bi bi-check2-circle me-1"></i>DISETUJUI INTERNAL <i class="bi bi-pencil-square ms-1 text-primary opacity-75"></i>
            </span>
        </button>`;
    }

    switch (st) {
        case 'DRAFT':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-pencil me-1"></i>DRAFT</span>';
        case 'REVIEW INTERNAL':
            return '<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>REVIEW INTERNAL</span>';
        case 'DISETUJUI INTERNAL':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>DISETUJUI INTERNAL</span>';
        case 'REVIEW VENDOR':
            return '<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>REVIEW VENDOR</span>';
        case 'DIPROSES VENDOR':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-truck me-1"></i>DIPROSES VENDOR</span>';
        case 'DITERIMA':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-box-seam me-1"></i>DITERIMA</span>';
        case 'BATAL':
            return '<span class="badge bg-dark-subtle text-muted border border-dark-subtle px-2 py-1"><i class="bi bi-slash-circle me-1"></i>BATAL</span>';
        case 'TIDAK DISETUJUI INTERNAL':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>DITOLAK</span>';
        default:
            return `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
    }
}

// -------------------------------------------------------------
// PAGINATION RENDERING
// -------------------------------------------------------------
function renderPagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('paginationList');

    const totalRecords = pagination.total_records || 0;
    const totalPages = pagination.total_pages || 1;
    const current = pagination.current_page || 1;

    info.textContent = `Menampilkan ${totalRecords} data Purchase Order (Halaman ${current} dari ${totalPages})`;

    if (totalPages <= 1) {
        list.innerHTML = '';
        return;
    }

    let paginationHtml = '';
    paginationHtml += `
        <li class="page-item ${current === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="loadPoList(${current - 1})">&laquo;</button>
        </li>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= current - 2 && p <= current + 2)) {
            paginationHtml += `
                <li class="page-item ${p === current ? 'active' : ''}">
                    <button class="page-link" onclick="loadPoList(${p})">${p}</button>
                </li>
            `;
        } else if (p === current - 3 || p === current + 3) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    paginationHtml += `
        <li class="page-item ${current === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="loadPoList(${current + 1})">&raquo;</button>
        </li>
    `;

    list.innerHTML = paginationHtml;
}

// -------------------------------------------------------------
// BUKA MODAL DETAIL PURCHASE ORDER
// -------------------------------------------------------------
async function openDetailModal(idPo) {
    modalDetailInstance.show();

    // Reset ke Tab 1
    const firstTabEl = document.getElementById('tab-m-info-btn');
    if (firstTabEl) {
        const tabTrigger = new bootstrap.Tab(firstTabEl);
        tabTrigger.show();
    }

    document.getElementById('detailNomorPo').textContent = 'Memuat...';
    document.getElementById('detailStatusBadgeHeader').innerHTML = '';
    const headerPrioEl = document.getElementById('detailPrioritasBadgeHeader');
    if (headerPrioEl) headerPrioEl.innerHTML = '';
    document.getElementById('detailTanggalPo').textContent = '-';
    document.getElementById('detailReferensiRo').textContent = '-';
    document.getElementById('detailPrioritasBadge').innerHTML = '-';
    document.getElementById('detailPembuat').textContent = '-';
    document.getElementById('detailApprover').textContent = '-';
    document.getElementById('detailVendor').textContent = '-';
    document.getElementById('detailTeleponVendor').textContent = '-';
    document.getElementById('detailEmailVendor').textContent = '-';
    document.getElementById('detailAlamatVendor').textContent = '-';
    document.getElementById('detailCatatan').textContent = '-';

    document.getElementById('detailSite').textContent = '-';
    document.getElementById('detailPengiriman').textContent = '-';
    document.getElementById('detailTanggalKirim').textContent = '-';
    document.getElementById('detailAlamatKirim').textContent = '-';
    document.getElementById('detailTop').textContent = '-';

    document.getElementById('calcSubtotalBarang').textContent = 'Rp 0';
    document.getElementById('calcDiskonPo').textContent = '- Rp 0';
    document.getElementById('calcDpp').textContent = 'Rp 0';
    document.getElementById('calcLabelPpn').textContent = 'PPN (11%):';
    document.getElementById('calcNominalPajak').textContent = 'Rp 0';
    document.getElementById('calcGrandTotal').textContent = 'Rp 0';
    document.getElementById('modalItemCountBadge').textContent = '0';

    document.getElementById('detailItemsTableBody').innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat rincian barang...
            </td>
        </tr>
    `;

    const res = await apiRequest(`/api/purchase_order/index.php?id=${idPo}`);
    if (!res || !res.success || !res.data) {
        showToast(res ? res.message : 'Gagal memuat detail Purchase Order.', 'danger');
        modalDetailInstance.hide();
        return;
    }

    const po = res.data;

    // Header Info
    document.getElementById('detailNomorPo').textContent = po.nomor_po || '-';
    document.getElementById('detailStatusBadgeHeader').innerHTML = renderStatusBadge(po.status);
    
    const prio = (po.prioritas || 'NORMAL').toUpperCase();
    const prioBadgeHtml = (prio === 'URGENT')
        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-1"><i class="bi bi-lightning-fill me-1"></i>URGENT</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace px-2 py-1">NORMAL</span>';

    if (headerPrioEl) headerPrioEl.innerHTML = prioBadgeHtml;

    // Tab 1 Info
    document.getElementById('detailTanggalPo').textContent = po.tanggal_po ? po.tanggal_po.split(' ')[0] : '-';
    document.getElementById('detailReferensiRo').textContent = po.nomor_ro ? `RO: ${po.nomor_ro}` : 'Tanpa RO';
    document.getElementById('detailPrioritasBadge').innerHTML = prioBadgeHtml;
        
    document.getElementById('detailPembuat').textContent = po.nama_pembuat || 'Staff Purchasing';
    document.getElementById('detailApprover').textContent = po.nama_approver || 'Menunggu Persetujuan';
    
    // Vendor Info
    document.getElementById('detailVendor').textContent = po.nama_vendor ? `${po.nama_vendor} (${po.kode_vendor || 'VND'})` : '-';
    document.getElementById('detailTeleponVendor').textContent = po.telepon_vendor || 'Tidak ada nomor telepon';
    document.getElementById('detailEmailVendor').textContent = po.email_vendor || 'Tidak ada alamat email';
    document.getElementById('detailAlamatVendor').textContent = po.alamat_vendor || 'Alamat vendor tidak tersedia';
    document.getElementById('detailCatatan').textContent = po.keterangan ? po.keterangan : 'Tidak ada catatan khusus.';

    // Tab 2 Shipping
    document.getElementById('detailSite').textContent = po.nama_site ? `${po.nama_site} (${po.kode_site || '-'})` : '-';
    document.getElementById('detailPengiriman').textContent = po.pengiriman ? `Kirim via ${po.pengiriman}` : 'Belum ditentukan';
    document.getElementById('detailTanggalKirim').textContent = po.tanggal_pengiriman ? po.tanggal_pengiriman.split(' ')[0] : 'Sesuai jadwal';
    document.getElementById('detailAlamatKirim').textContent = po.alamat || (po.alamat_site || 'Alamat Site');
    
    const topNum = parseInt(po.term_of_payment) || 0;
    document.getElementById('detailTop').textContent = (topNum === 0) ? 'C.O.D (Cash On Delivery)' : `Tempo ${topNum} Hari`;

    // Tab 3 Financial Calculation (Konsisten dengan Perhitungan Proses PO)
    const subtotalBarang = parseFloat(po.subtotal_barang) || 0;
    const diskonPo = parseFloat(po.nominal_diskon || po.diskon) || 0;
    const ratePajak = parseFloat(po.rate_pajak || po.pajak) || 0;
    const isTermasukPajak = parseInt(po.total_termasuk_pajak) === 1;
    const ratePpnbm = parseFloat(po.rate_ppnbm || po.pajak_PPnBM) || 0;
    const isTermasukPpnbm = parseInt(po.total_termasuk_PPnBM) === 1;

    let dpp = parseFloat(po.dpp) || 0;
    let nominalPpnbm = parseFloat(po.nominal_ppnbm) || 0;
    let nominalPajak = parseFloat(po.nominal_pajak) || 0;
    let grandTotal = parseFloat(po.grand_total) || 0;

    // Fallback perhitungan jika API belum menghitung
    if (dpp === 0 && subtotalBarang > 0) {
        const dasarSetelahDiskon = Math.max(0, subtotalBarang - diskonPo);
        let divisor = 1.0;
        if (isTermasukPpnbm && ratePpnbm > 0) divisor += (ratePpnbm / 100);
        if (isTermasukPajak && ratePajak > 0) divisor += (ratePajak / 100);

        dpp = dasarSetelahDiskon / divisor;
        nominalPpnbm = (ratePpnbm > 0) ? (dpp * (ratePpnbm / 100)) : 0;
        nominalPajak = (ratePajak > 0) ? (dpp * (ratePajak / 100)) : 0;
        grandTotal = dpp + nominalPpnbm + nominalPajak;
    }

    document.getElementById('calcSubtotalBarang').textContent = formatRupiah(subtotalBarang);
    document.getElementById('calcDiskonPo').textContent = `- ${formatRupiah(diskonPo)}`;
    document.getElementById('calcDpp').textContent = formatRupiah(dpp);

    // Render Baris PPnBM jika ada
    const rowPpnbmEl = document.getElementById('calcRowPpnbm');
    const labelPpnbmEl = document.getElementById('calcLabelPpnbm');
    const nominalPpnbmEl = document.getElementById('calcNominalPpnbm');
    if (ratePpnbm > 0) {
        rowPpnbmEl.classList.remove('d-none');
        labelPpnbmEl.textContent = `PPnBM (${ratePpnbm}%)${isTermasukPpnbm ? ' (Inklusif)' : ''}:`;
        nominalPpnbmEl.textContent = formatRupiah(nominalPpnbm);
    } else {
        rowPpnbmEl.classList.add('d-none');
    }

    // Render Baris PPN
    const rowPpnEl = document.getElementById('calcRowPpn');
    const labelPpnEl = document.getElementById('calcLabelPpn');
    const nominalPpnEl = document.getElementById('calcNominalPajak');
    if (ratePajak > 0) {
        rowPpnEl.classList.remove('d-none');
        labelPpnEl.textContent = `PPN (${ratePajak}%)${isTermasukPajak ? ' (Inklusif)' : ''}:`;
        nominalPpnEl.textContent = formatRupiah(nominalPajak);
    } else {
        rowPpnEl.classList.remove('d-none');
        labelPpnEl.textContent = 'PPN (0%):';
        nominalPpnEl.textContent = formatRupiah(0);
    }

    document.getElementById('calcGrandTotal').textContent = formatRupiah(grandTotal);

    // Tombol Cetak di Footer Modal (Khusus PO yang sudah diproses ke vendor / diterima / selesai)
    const printContainer = document.getElementById('modalPrintPoContainer');
    if (printContainer) {
        const allowedStatuses = ['DIPROSES VENDOR', 'DITERIMA', 'SELESAI'];
        if (allowedStatuses.includes((po.status || '').toUpperCase())) {
            printContainer.innerHTML = `
                <a href="${BASE_URL}/admin/pages/purchase_order/print.php?id=${po.id_po}" target="_blank" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm">
                    <i class="bi bi-printer-fill me-1"></i> Cetak / Download PO
                </a>
            `;
        } else {
            printContainer.innerHTML = '';
        }
    }

    // Render Items
    const items = po.items || [];
    document.getElementById('modalItemCountBadge').textContent = items.length;

    if (items.length === 0) {
        document.getElementById('detailItemsTableBody').innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-3 text-muted">Tidak ada rincian barang dalam dokumen ini.</td>
            </tr>
        `;
        return;
    }

    let itemsHtml = '';
    items.forEach((item, idx) => {
        const itemQty = parseFloat(item.qty) || 0;
        const itemHarga = parseFloat(item.harga) || 0;
        const itemDiskon = parseFloat(item.diskon) || 0;
        const itemSubtotal = parseFloat(item.subtotal) || 0;

        const isItemPpnbm = (item.PPnBM == 1 || item.PPnBM === '1' || item.kena_pajak_ppnbm == 1 || item.kena_pajak_ppnbm === '1');
        const itemPpnbmRate = parseFloat(item.rate_PPnBM || item.rate_ppnbm || ratePpnbm) || 0;
        const ppnbmBadge = isItemPpnbm 
            ? `<span class="badge bg-warning text-dark border border-warning-subtle fw-bold" style="font-size: 0.68rem;">PPnBM (${itemPpnbmRate}%)</span>`
            : '';

        const imgSrc = item.foto1 ? `${BASE_URL}/${escapeHtml(item.foto1)}` : '';
        const imgHtml = imgSrc 
            ? `<a href="${imgSrc}" target="_blank" class="d-inline-block flex-shrink-0" title="Klik untuk perbesar foto">
                   <img src="${imgSrc}" alt="${escapeHtml(item.nama_barang || '')}" class="rounded border bg-white object-fit-cover" style="width: 44px; height: 44px;" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\\'rounded border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0\\' style=\\'width: 44px; height: 44px;\\'><i class=\\'bi bi-box-seam fs-5\\'></i></div>';">
               </a>`
            : `<div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 44px; height: 44px;">
                   <i class="bi bi-box-seam fs-5"></i>
               </div>`;

        itemsHtml += `
            <tr>
                <td class="text-center font-monospace text-muted small align-middle">${idx + 1}</td>
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
                        </div>
                    </div>
                </td>
                <td class="text-center align-middle">
                    <span class="fw-bold font-monospace fs-6">${itemQty}</span>
                    <span class="small text-muted d-block" style="font-size: 0.72rem;">${escapeHtml(item.satuan || 'PCS')}</span>
                </td>
                <td class="text-end font-monospace align-middle">${formatNumber(itemHarga)}</td>
                <td class="text-end font-monospace text-danger align-middle">${itemDiskon > 0 ? ('- ' + formatNumber(itemDiskon)) : '0'}</td>
                <td class="text-end font-monospace fw-bold text-dark align-middle">${formatNumber(itemSubtotal)}</td>
            </tr>
        `;
    });

    document.getElementById('detailItemsTableBody').innerHTML = itemsHtml;
}

// -------------------------------------------------------------
// RESET FILTERS
// -------------------------------------------------------------
function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSite').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    loadPoList(1);
}

// -------------------------------------------------------------
// HELPER UTILITIES
// -------------------------------------------------------------
function formatRupiah(amount) {
    return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatNumber(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
// -------------------------------------------------------------
// PEMBATALAN PURCHASE ORDER & BERITA ACARA PEMBATALAN (BAP)
// -------------------------------------------------------------
function openCancelPoModal(idPo, nomorPo, vendorName, nilaiFormatted) {
    document.getElementById('cancelPoId').value = idPo;
    document.getElementById('cancelPoNomorDisplay').textContent = nomorPo;
    document.getElementById('cancelPoVendorDisplay').textContent = 'Vendor: ' + (vendorName || '-');
    document.getElementById('cancelPoNilaiDisplay').textContent = 'Nilai Dokumen: ' + nilaiFormatted;
    document.getElementById('cancelPoKategori').value = '';
    document.getElementById('cancelPoAlasan').value = '';

    modalCancelPoInstance.show();
}

async function submitCancelPo(e) {
    e.preventDefault();
    const idPo = document.getElementById('cancelPoId').value;
    const kategori = document.getElementById('cancelPoKategori').value;
    const alasan = document.getElementById('cancelPoAlasan').value.trim();
    const btn = document.getElementById('btnSubmitCancelPo');

    if (!idPo) return;
    if (!kategori) {
        showToast('Pilih kategori alasan pembatalan.', 'warning');
        return;
    }
    if (alasan.length < 5) {
        showToast('Uraian kronologis alasan wajib diisi minimal 5 karakter.', 'warning');
        return;
    }

    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses Pembatalan & BAP...';

    try {
        const res = await apiRequest('/api/purchase_order/cancel.php', 'POST', {
            id_po: idPo,
            kategori_alasan: kategori,
            alasan: alasan
        });

        if (res && res.success) {
            modalCancelPoInstance.hide();
            showToast(res.message, 'success');
            
            // Buka lembar cetak BAP di tab baru
            if (res.data && res.data.id_po) {
                window.open(`${BASE_URL}/admin/pages/laporan/print_bap.php?type=PO&id=${res.data.id_po}`, '_blank');
            }

            loadPoList(currentPage);
        } else {
            showToast(res ? res.message : 'Gagal membatalkan Purchase Order.', 'danger');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
}
// -------------------------------------------------------------
// UPDATE STATUS PURCHASE ORDER MODAL HANDLERS
// -------------------------------------------------------------
function handleNewStatusChange(val) {
    const wrapper = document.getElementById('wrapperEstimasiPengiriman');
    if (wrapper) {
        if (val === 'DIPROSES VENDOR') {
            wrapper.classList.remove('d-none');
        } else {
            wrapper.classList.add('d-none');
        }
    }
}

function openUpdateStatusModal(idPo, nomorPo, vendor, currentStatus) {
    document.getElementById('updateStatusPoId').value = idPo;
    document.getElementById('updateStatusNomorPo').textContent = nomorPo || '-';
    document.getElementById('updateStatusBadgeCurrent').textContent = currentStatus || 'DISETUJUI INTERNAL';
    document.getElementById('selectNewStatusPo').value = 'DIPROSES VENDOR';
    document.getElementById('inputCatatanStatusPo').value = '';
    handleNewStatusChange('DIPROSES VENDOR');
    modalUpdateStatusInstance.show();
}

async function submitUpdateStatusPo() {
    const idPo = document.getElementById('updateStatusPoId').value;
    const newStatus = document.getElementById('selectNewStatusPo').value;
    const catatan = document.getElementById('inputCatatanStatusPo').value.trim();
    const tglKirim = document.getElementById('inputEstimasiPengiriman')?.value || '';

    if (!idPo || !newStatus) {
        showToast('Pilih status baru dokumen.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSubmitUpdateStatusPo');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    try {
        const payload = {
            id_po: parseInt(idPo),
            status: newStatus,
            keterangan: catatan,
            tanggal_pengiriman: (newStatus === 'DIPROSES VENDOR') ? tglKirim : null
        };

        const res = await apiRequest('/api/purchase_order/update_status.php', 'POST', payload);

        if (res && res.success) {
            modalUpdateStatusInstance.hide();
            showToast(res.message, 'success');
            loadPoList(currentPage);
        } else {
            showToast(res ? res.message : 'Gagal memperbarui status Purchase Order.', 'danger');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    }
}
</script>
