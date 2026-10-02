<?php
/**
 * Halaman Buat Dokumen Retur Purchase Order (Retur PO)
 * Path: admin/pages/retur_po/create.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_LOGISTIK, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Buat Retur PO';
$pageHeading = 'Formulir Pengajuan Retur PO';

// Ambil daftar Dokumen Penerimaan RCV yang memiliki barang rusak (status_qc = 0) dan BELUM pernah dibuatkan Retur PO
$rcvOptions = [];
$qRcv = "SELECT r.id_rcv, r.nomor_rcv, r.nomor_sj, po.id_po, po.nomor_po, 
                v.id_vendor, v.nama_perusahaan AS nama_vendor, 
                s.id_site, s.nama_site,
                COUNT(rod.id_rcv_detail) AS damaged_count,
                GROUP_CONCAT(CONCAT(b.nama_barang, ' (', rod.qty, ' ', b.satuan, ')') SEPARATOR ', ') AS damaged_items_summary
         FROM receiving_order r
         JOIN purchase_order po ON r.id_po = po.id_po
         JOIN vendor v ON po.id_vendor = v.id_vendor
         JOIN site s ON po.id_site = s.id_site
         JOIN receiving_order_detail rod ON r.id_rcv = rod.id_rcv
         JOIN barang b ON rod.id_barang = b.id_barang
         WHERE rod.status_qc = 0 
           AND r.id_rcv NOT IN (SELECT id_rcv FROM retur_po WHERE status != 'DIBATALKAN')
         GROUP BY r.id_rcv
         ORDER BY r.id_rcv DESC";
$resRcv = $conn->query($qRcv);
if ($resRcv) {
    while ($row = $resRcv->fetch_assoc()) {
        $rcvOptions[] = $row;
    }
}

// Ambil daftar karyawan dengan level jabatan 1 dan 2 untuk Approval
$approvers = [];
$qApprovers = "SELECT k.id_karyawan, k.nama_karyawan, j.nama_jabatan, j.level 
               FROM karyawan k 
               JOIN jabatan j ON k.id_jabatan = j.id_jabatan 
               WHERE j.level IN (1, 2) AND k.aktif = 1
               ORDER BY j.level ASC, k.nama_karyawan ASC";
$resApprovers = $conn->query($qApprovers);
if ($resApprovers) {
    while ($row = $resApprovers->fetch_assoc()) {
        $approvers[] = $row;
    }
}

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Retur Purchase Order</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/retur_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- FORM 5 TAB -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="returTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold py-3 px-3" id="tab-info-btn" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                        <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Informasi Utama
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-vendor-btn" data-bs-toggle="tab" data-bs-target="#tab-vendor" type="button" role="tab">
                        <i class="bi bi-building me-1 text-primary"></i> 2. Vendor
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-pengiriman-btn" data-bs-toggle="tab" data-bs-target="#tab-pengiriman" type="button" role="tab">
                        <i class="bi bi-truck me-1 text-primary"></i> 3. Pengiriman
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
                        <i class="bi bi-box-seam me-1 text-primary"></i> 4. Rincian Barang <span class="badge bg-danger ms-1" id="badgeItemCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-persetujuan-btn" data-bs-toggle="tab" data-bs-target="#tab-persetujuan" type="button" role="tab">
                        <i class="bi bi-check2-circle me-1 text-primary"></i> 5. Persetujuan
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <form id="formRetur" onsubmit="event.preventDefault();">
                <div class="tab-content" id="returTabContent">
                    
                    <!-- TAB 1: INFORMASI UTAMA -->
                    <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                        <div class="row g-4">
                            <!-- Kolom Kiri: Sumber Dokumen -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Dokumen Asal Penerimaan (Receiving)</h6>
                                
                                <!-- Nomor Retur PO (Otomatis & Dinamis) -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nomor Retur PO <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control font-monospace fw-bold text-danger bg-light" id="inputNomorRetur" readonly required placeholder="Memuat nomor Retur...">
                                        <button type="button" class="btn btn-outline-secondary" onclick="fetchNextReturNumber()" title="Generate Ulang Nomor">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3 position-relative" id="rcvSearchableWrapper">
                                    <label class="form-label small fw-semibold text-dark">
                                        Pilih Dokumen Penerimaan (RCV) <span class="text-danger">*</span>
                                    </label>
                                    
                                    <!-- Hidden Input for Form Submission & State -->
                                    <input type="hidden" id="selectRcv" name="id_rcv" required>

                                    <!-- Trigger Search Box (Searchable UI) -->
                                    <div class="rcv-custom-select d-flex align-items-center justify-content-between p-2 px-3 border rounded-3 bg-white cursor-pointer shadow-sm" id="rcvTriggerBox" onclick="toggleRcvDropdown(event)">
                                        <div id="rcvSelectedDisplay" class="text-truncate me-2">
                                            <span class="text-muted">Cari Dokumen Penerimaan (No. RCV, PO, Vendor)...</span>
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
                                            <input type="text" class="form-control form-control-sm" id="rcvSearchInput" placeholder="Ketik No. RCV, No. PO, Vendor, atau Barang..." autocomplete="off" oninput="filterRcvList()">
                                        </div>
                                        <div class="overflow-auto" id="rcvOptionsContainer" style="max-height: 260px;">
                                            <!-- Options populated dynamically by JS -->
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Nomor Purchase Order (PO)</label>
                                        <input type="text" class="form-control form-control-sm bg-light font-monospace" id="displayNoPo" placeholder="-" readonly>
                                        <input type="hidden" id="inputPoId">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Nomor Surat Jalan Vendor</label>
                                        <input type="text" class="form-control form-control-sm bg-light" id="displayNoSj" placeholder="-" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Detail Lokasi & Waktu Retur -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Lokasi &amp; Waktu Pengiriman</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Site / Lokasi Fisik Barang</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="displaySite" placeholder="-" readonly>
                                    <input type="hidden" id="inputSiteId">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Tanggal Kirim Retur <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" id="inputTanggalRetur" value="<?= date('Y-m-d') ?>" onchange="fetchNextReturNumber()" required>
                                </div>

                                <div>
                                    <label class="form-label small fw-semibold text-muted">Keterangan / Catatan Tambahan Dokumen</label>
                                    <textarea class="form-control form-control-sm" id="inputKeterangan" rows="2" placeholder="Catatan pengembalian atau perihal khusus..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToTab('tab-vendor-btn')">
                                Lanjut ke Vendor <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: VENDOR -->
                    <div class="tab-pane fade" id="tab-vendor" role="tabpanel">
                        <div class="row g-4">
                            <!-- Kolom Kiri: Info Vendor -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Identitas Vendor</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Nama Perusahaan Vendor</label>
                                    <input type="text" class="form-control form-control-sm bg-light fw-bold text-dark" id="displayVendor" placeholder="-" readonly>
                                    <input type="hidden" id="inputVendorId">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Telepon / Kontak Vendor</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="displayTeleponVendor" placeholder="-" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">PIC Vendor yang Dihubungi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="inputPicVendor" placeholder="Nama sales / perwakilan kontak vendor" required>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Skema Kompensasi -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Skema Kompensasi</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark mb-2">Skema yang Disepakati Bersama Vendor <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-column gap-2">
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input ms-0 me-2" type="radio" name="kompensasi" id="kompTukarUnit" value="1" checked>
                                            <label class="form-check-label fw-semibold text-dark" for="kompTukarUnit">
                                                Tukar Unit (Ganti Barang Baru)
                                            </label>
                                            <div class="small text-muted ms-4">Vendor akan mengirimkan barang baru yang sesuai sebagai pengganti barang rusak.</div>
                                        </div>
                                        <div class="form-check p-3 border rounded-3 bg-light">
                                            <input class="form-check-input ms-0 me-2" type="radio" name="kompensasi" id="kompPotongTagihan" value="0">
                                            <label class="form-check-label fw-semibold text-dark" for="kompPotongTagihan">
                                                Potong Tagihan (Credit Note / Pemotongan Invoice)
                                            </label>
                                            <div class="small text-muted ms-4">Nilai barang yang diretur akan dipotong langsung dari tagihan/pembayaran PO.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToTab('tab-pengiriman-btn')">
                                Lanjut ke Pengiriman <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: PENGIRIMAN -->
                    <div class="tab-pane fade" id="tab-pengiriman" role="tabpanel">
                        <div class="row g-4">
                            <!-- Kolom Kiri: Metode & Biaya Pengiriman -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Metode &amp; Armada Pengiriman</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark mb-2">Penanggung Jawab / Jalur Pengiriman <span class="text-danger">*</span></label>
                                    <input type="hidden" id="selectedPengiriman" name="pengiriman_retur_val" value="Vendor">
                                    <div class="d-flex flex-column gap-2" id="pengirimanRadioGroup">
                                        <label class="p-2 px-3 border rounded-3 cursor-pointer pengiriman-card d-flex align-items-center mb-0 border-primary bg-primary-subtle" id="cardKirimVendor" onclick="selectPengirimanCard('Vendor')">
                                            <input class="form-check-input me-2 mt-0" type="radio" name="pengiriman_retur" id="kirimVendor" value="Vendor" checked>
                                            <span class="fw-semibold text-dark">
                                                Vendor (Dijemput / Diambil oleh Vendor)
                                            </span>
                                        </label>
                                        <label class="p-2 px-3 border rounded-3 cursor-pointer pengiriman-card d-flex align-items-center mb-0 bg-light" id="cardKirimExpedisi" onclick="selectPengirimanCard('Expedisi')">
                                            <input class="form-check-input me-2 mt-0" type="radio" name="pengiriman_retur" id="kirimExpedisi" value="Expedisi">
                                            <span class="fw-semibold text-dark">
                                                Expedisi (Jasa Ekspedisi / Cargo / Kurir Luar)
                                            </span>
                                        </label>
                                        <label class="p-2 px-3 border rounded-3 cursor-pointer pengiriman-card d-flex align-items-center mb-0 bg-light" id="cardKirimInternal" onclick="selectPengirimanCard('Internal')">
                                            <input class="form-check-input me-2 mt-0" type="radio" name="pengiriman_retur" id="kirimInternal" value="Internal">
                                            <span class="fw-semibold text-dark">
                                                Internal (Diantar oleh Armada Internal Logistik)
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Biaya Pengiriman Retur (IDR)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light fw-bold text-muted">Rp</span>
                                        <input type="text" class="form-control form-control-sm text-end fw-semibold" id="inputBiayaRetur" value="0" placeholder="0" oninput="formatBiayaReturInput(this)">
                                    </div>
                                    <div class="form-text small text-muted">Isi 0 jika ongkos kirim ditanggung oleh pihak vendor.</div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Dokumen Jalan & Pajak -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Dokumen Surat Jalan &amp; Faktur</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">No. Surat Jalan Pengembalian / Retur (Opsional)</label>
                                    <input type="text" class="form-control form-control-sm" id="inputNoSjRetur" placeholder="Contoh: SJ-RET-2026-001">
                                    
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">No. Nota Retur Pajak (Opsional)</label>
                                    <input type="text" class="form-control form-control-sm" id="inputNoNotaPajak" placeholder="Untuk e-Faktur Pembatalan Pajak">
                                    
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToTab('tab-items-btn')">
                                Lanjut ke Rincian Barang <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: RINCIAN BARANG -->
                    <div class="tab-pane fade" id="tab-items" role="tabpanel">
                        

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Daftar Barang Retur</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAddItemModal()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Barang
                            </button>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle mb-0" id="tableReturItems">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">No</th>
                                        <th style="min-width: 250px;">Nama Barang</th>
                                        <th style="width: 130px;" class="text-center">Qty Retur</th>
                                        <th style="width: 90px;" class="text-center">Satuan</th>
                                        <th style="width: 140px;" class="text-end">Harga Satuan</th>
                                        <th style="width: 150px;" class="text-end">Subtotal</th>
                                        <th style="width: 100px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-arrow-in-left fs-3 d-block mb-2 text-secondary"></i>
                                            Silakan pilih Dokumen Penerimaan (RCV) pada Tab 1 terlebih dahulu.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- KOTAK RINGKASAN BIAYA -->
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="card bg-light border rounded-3 p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Subtotal Nilai Retur (DPP):</span>
                                        <span class="fw-bold font-monospace" id="summarySubtotal">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Pajak PPN (<span id="summaryRatePajak">0</span>%):</span>
                                        <span class="fw-bold font-monospace text-primary" id="summaryNominalPajak">Rp 0</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">Grand Total Nilai Klaim:</span>
                                        <span class="fs-5 fw-bold text-success font-monospace" id="summaryGrandTotal">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToTab('tab-persetujuan-btn')">
                                Lanjut ke Persetujuan <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 5: PERSETUJUAN -->
                    <div class="tab-pane fade" id="tab-persetujuan" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6">
                               

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Pilih Pejabat yang Menyetujui Retur <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="selectKaryawanApproved" required>
                                        <?php 
                                        $userIsApprover = false;
                                        foreach ($approvers as $app) {
                                            if ($app['id_karyawan'] == ($user['id_karyawan'] ?? 0)) {
                                                $userIsApprover = true;
                                                break;
                                            }
                                        }
                                        foreach ($approvers as $idx => $app): 
                                            $isSelected = ($userIsApprover && $app['id_karyawan'] == ($user['id_karyawan'] ?? 0)) || (!$userIsApprover && $idx === 0);
                                        ?>
                                            <option value="<?= $app['id_karyawan'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($app['nama_karyawan']) ?> &bull; <?= htmlspecialchars($app['nama_jabatan']) ?> (Level <?= $app['level'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="submitReturForm('DRAFT')">
                                <i class="bi bi-save me-1"></i> Simpan Draft
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="submitReturForm('MENUNGGU KONFIRMASI VENDOR')">
                                <i class="bi bi-send-check me-1"></i> Terbitkan Retur PO
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<!-- STYLES UNTUK SELECT2 CUSTOM SEARCHABLE DROPDOWN -->
<style>
.select2-custom-box {
    min-height: 38px;
    background-color: #ffffff;
    border: 1px solid #ced4da;
    border-radius: 6px;
    padding: 0.35rem 0.75rem;
    transition: all 0.2s ease;
    user-select: none;
}
.select2-custom-box:hover {
    border-color: #86b7fe;
}
.select2-custom-box.is-open {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}
.btn-clear-select2 {
    background: transparent;
    border: none;
    padding: 0 4px;
    color: #94a3b8;
    font-size: 1.1rem;
    line-height: 1;
    display: flex;
    align-items: center;
    border-radius: 50%;
}
.btn-clear-select2:hover {
    color: #ef4444;
}
.transition-icon {
    transition: transform 0.2s ease;
}
.select2-custom-box.is-open .transition-icon {
    transform: rotate(180deg);
}
.select2-custom-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1050;
    margin-top: 4px;
    background: #ffffff;
}
.select2-search-box .form-control:focus {
    box-shadow: none;
}
.rcv-option-card {
    padding: 8px 10px;
    border-radius: 6px;
    border: 1px solid rgba(0, 0, 0, 0.05);
    background: #ffffff;
    transition: all 0.15s ease;
    cursor: pointer;
    margin-bottom: 6px;
}
.rcv-option-card:hover {
    background-color: #f8fafc;
    border-color: #93c5fd;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.rcv-option-card.active {
    background-color: #eff6ff;
    border-color: #3b82f6;
}

/* Modal Detail Tabs Sesuai Request Order (Card Folder Tabs) */
#modalDetailTab {
    border-bottom: 0;
    margin-bottom: -1px;
}
#modalDetailTab .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    color: #334155;
    background: transparent;
    transition: all 0.15s ease;
    margin-bottom: -1px;
    padding: 0.5rem 1rem;
}
#modalDetailTab .nav-link:hover {
    border-color: #e2e8f0 #e2e8f0 transparent;
    color: #0d6efd;
}
#modalDetailTab .nav-link.active {
    color: #0f172a !important;
    background-color: #ffffff !important;
    border-color: #dee2e6 #dee2e6 #ffffff !important;
    font-weight: 700 !important;
    border-bottom: 1px solid #ffffff !important;
}
</style>

<!-- MODAL TAMBAH ITEM LAIN DARI RCV -->
<div class="modal fade" id="modalAddOtherItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Pilih Barang dari Penerimaan (RCV)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group" id="rcvAvailableItemsList">
                    <!-- Items populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL INFORMASI DETAIL (GAYA UI REQUEST ORDER POPUP) -->
<div class="modal fade" id="modalClaimDetail" tabindex="-1" aria-labelledby="modalClaimDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- MODAL HEADER DENGAN TAB INTEGRATED SEPERTI REQUEST ORDER -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-text-fill text-primary fs-5"></i>
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalItemNamaHeader">
                            Informasi Detail
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" id="modalItemKodeHeader">-</span>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Nav Tabs Modal Sesuai Gaya Request Order -->
                <ul class="nav nav-tabs border-bottom-0" id="modalDetailTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="tab-modal-info-btn" data-bs-toggle="tab" data-bs-target="#tab-modal-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Informasi Kerusakan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="tab-modal-foto-btn" data-bs-toggle="tab" data-bs-target="#tab-modal-foto" type="button" role="tab">
                            <i class="bi bi-image me-1 text-primary"></i> 2. Bukti Foto
                            <span class="badge bg-primary text-white ms-1" id="modalFotoBadge" style="display:none;">1</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 2 TAB -->
            <div class="modal-body p-4 bg-light">
                <input type="hidden" id="modalItemIndex">

                <div class="tab-content" id="modalDetailTabContent">
                    
                    <!-- TAB 1: RINCIAN KERUSAKAN -->
                    <div class="tab-pane fade show active" id="tab-modal-info" role="tabpanel">
                        <div class="card bg-white border-0 shadow-sm rounded-3 p-3">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark">Alasan Pengembalian / Retur <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="modalItemAlasan">
                                    <option value="RUSAK_FISIK">Rusak Fisik / Pengiriman</option>
                                    <option value="CACAT_PRODUKSI">Cacat Pabrik / Vendor</option>
                                    <option value="SALAH_SPESIFIKASI">Salah Spesifikasi</option>
                                    <option value="KURANG_PENGIRIMAN">Kurang Kuantitas</option>
                                    <option value="KADALUARSA_EXP">Kadaluarsa / Expired</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label small fw-semibold text-dark">Keterangan / Rincian Kerusakan</label>
                                <textarea class="form-control form-control-sm" id="modalItemKet" rows="4" placeholder="Deskripsikan kondisi cacat, pecah, retak, atau kerusakan fisik barang..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: BUKTI FOTO -->
                    <div class="tab-pane fade" id="tab-modal-foto" role="tabpanel">
                        <div class="card bg-white border-0 shadow-sm rounded-3 p-3">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-dark mb-1">Unggah Foto Bukti Fisik Kerusakan</label>
                                <input type="file" class="form-control form-control-sm" id="modalItemPhotoInput" accept=".jpg,.jpeg,image/jpeg" onchange="previewModalPhoto(this)">
                                <div class="form-text small text-muted">Format yang didukung: JPG / JPEG (Maks 2MB).</div>
                            </div>

                            <!-- PREVIEW CONTAINER SEBELUM UPLOAD / DISIMPAN -->
                            <div class="border rounded-3 p-3 bg-light text-center" id="modalPhotoPreviewWrapper" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-secondary-subtle text-secondary small text-truncate" id="modalPhotoName" style="max-width: 260px;">foto.jpg</span>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="removeModalPhoto()">
                                        <i class="bi bi-trash me-1"></i>Hapus Foto
                                    </button>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <img id="modalPhotoPreviewImg" src="" alt="Pratinjau Foto Kerusakan" class="img-fluid rounded border shadow-sm" style="max-height: 240px; object-fit: contain; background: #fff;">
                                </div>
                            </div>

                            <div class="border rounded-3 p-4 bg-light text-center text-muted" id="modalPhotoEmptyState">
                                <i class="bi bi-image fs-1 d-block mb-1 text-secondary"></i>
                                <div class="small">Belum ada foto yang dipilih. Silakan pilih file foto di atas untuk melihat pratinjau.</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODAL FOOTER SEPERTI REQUEST ORDER -->
            <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="saveClaimModal()">
                    <i class="bi bi-check2 me-1"></i> Simpan Keterangan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRcvData = null;
let returItems = [];
let ratePajak = 0;
let rcvLookupList = <?= json_encode($rcvOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let selectedRcvId = null;

document.addEventListener('DOMContentLoaded', async () => {
    await fetchNextReturNumber();
    renderRcvOptions(rcvLookupList);
    if (rcvLookupList && rcvLookupList.length === 1) {
        selectRcvOption(rcvLookupList[0].id_rcv);
    }
});

async function fetchNextReturNumber() {
    const tgl = document.getElementById('inputTanggalRetur').value || '';
    const res = await apiRequest(`/api/retur_po/get_next_number.php?tanggal=${encodeURIComponent(tgl)}`);
    if (res && res.success && res.data && res.data.nomor_po_retur) {
        document.getElementById('inputNomorRetur').value = res.data.nomor_po_retur;
    }
}

// Click outside untuk menutup dropdown searchable
document.addEventListener('click', (e) => {
    const wrapper = document.getElementById('rcvSearchableWrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        closeRcvDropdown();
    }
});

function toggleRcvDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('rcvDropdownMenu');
    if (!menu) return;
    if (menu.style.display === 'none' || !menu.style.display) {
        openRcvDropdown();
    } else {
        closeRcvDropdown();
    }
}

function openRcvDropdown() {
    const menu = document.getElementById('rcvDropdownMenu');
    const trigger = document.getElementById('rcvTriggerBox');
    if (!menu || !trigger) return;
    menu.style.display = 'block';
    trigger.classList.add('border-primary', 'shadow');
    const searchInput = document.getElementById('rcvSearchInput');
    if (searchInput) {
        setTimeout(() => searchInput.focus(), 50);
    }
}

function closeRcvDropdown() {
    const menu = document.getElementById('rcvDropdownMenu');
    const trigger = document.getElementById('rcvTriggerBox');
    if (menu) menu.style.display = 'none';
    if (trigger) trigger.classList.remove('border-primary', 'shadow');
}

function filterRcvList() {
    const q = (document.getElementById('rcvSearchInput').value || '').toLowerCase().trim();
    if (!q) {
        renderRcvOptions(rcvLookupList);
        return;
    }

    const filtered = rcvLookupList.filter(item => {
        const noRcv = (item.nomor_rcv || '').toLowerCase();
        const noPo = (item.nomor_po || '').toLowerCase();
        const vendor = (item.nama_vendor || '').toLowerCase();
        const sj = (item.nomor_sj || '').toLowerCase();
        const site = (item.nama_site || '').toLowerCase();
        const damaged = (item.damaged_items_summary || '').toLowerCase();
        return noRcv.includes(q) || noPo.includes(q) || vendor.includes(q) || sj.includes(q) || site.includes(q) || damaged.includes(q);
    });

    renderRcvOptions(filtered);
}

function renderRcvOptions(list) {
    const container = document.getElementById('rcvOptionsContainer');
    if (!container) return;

    if (!list || list.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted small">
                <i class="bi bi-box2-check fs-2 text-secondary d-block mb-1"></i>
                Tidak ada dokumen penerimaan yang tersedia untuk retur.
            </div>`;
        return;
    }

    let html = '';
    list.forEach(item => {
        const isSelected = (selectedRcvId == item.id_rcv);
        const damagedSummary = item.damaged_items_summary 
            ? `<div class="small mt-1 p-1 px-2 rounded bg-warning-subtle text-warning-emphasis border border-warning-subtle text-truncate"><strong>Rusak:</strong> ${escapeHtml(item.damaged_items_summary)}</div>` 
            : '';

        html += `
            <div class="rcv-item-card p-2 px-3 rounded-2 border mb-1 cursor-pointer ${isSelected ? 'active-rcv' : ''}" onclick="selectRcvOption(${item.id_rcv})">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <span class="badge bg-primary-subtle text-primary font-monospace fw-bold me-1">${escapeHtml(item.nomor_rcv)}</span>
                        <span class="badge bg-light text-dark border font-monospace me-1">PO: ${escapeHtml(item.nomor_po)}</span>
                    </div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">${item.damaged_count || 1} Rusak</span>
                </div>
                <div class="d-flex justify-content-between align-items-center small text-dark">
                    <span class="fw-bold text-dark text-truncate me-2"><i class="bi bi-building text-muted me-1"></i>${escapeHtml(item.nama_vendor || '-')}</span>
                    <span class="text-muted text-nowrap"><i class="bi bi-geo-alt text-muted me-1"></i>${escapeHtml(item.nama_site || '-')}</span>
                </div>
                ${damagedSummary}
            </div>
        `;
    });

    container.innerHTML = html;
}

function selectRcvOption(idRcv) {
    selectedRcvId = idRcv;
    document.getElementById('selectRcv').value = idRcv;
    closeRcvDropdown();

    const selectedItem = rcvLookupList.find(x => x.id_rcv == idRcv);
    if (selectedItem) {
        document.getElementById('rcvSelectedDisplay').innerHTML = `
            <div class="d-flex align-items-center flex-wrap gap-1">
                <span class="badge bg-primary font-monospace fw-bold">${escapeHtml(selectedItem.nomor_rcv)}</span>
                <span class="badge bg-light text-dark border font-monospace">PO: ${escapeHtml(selectedItem.nomor_po)}</span>
                <span class="fw-bold text-dark small ms-1">${escapeHtml(selectedItem.nama_vendor)}</span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-auto">${selectedItem.damaged_count || 1} Rusak</span>
            </div>
        `;
        document.getElementById('rcvClearBtn').style.display = 'inline-block';
    }

    onRcvSelected(idRcv);
}

function clearRcvSelection(e) {
    if (e) e.stopPropagation();
    selectedRcvId = null;
    document.getElementById('selectRcv').value = '';
    document.getElementById('rcvSelectedDisplay').innerHTML = `<span class="text-muted"><i class="bi bi-search me-2 text-primary"></i>Cari Dokumen Penerimaan (No. RCV, PO, Vendor)...</span>`;
    document.getElementById('rcvClearBtn').style.display = 'none';
    resetRcvDisplay();
    renderRcvOptions(rcvLookupList);
}

async function onRcvSelected(idRcvParam) {
    const idRcv = idRcvParam || (document.getElementById('selectRcv') ? document.getElementById('selectRcv').value : null);
    if (!idRcv) {
        resetRcvDisplay();
        return;
    }

    try {
        const res = await apiRequest(`/api/retur_po/receiving_lookup.php?id_rcv=${idRcv}`, 'GET');
        if (res && res.success && res.data) {
            currentRcvData = res.data;
            const h = res.data.header;

            // Populate Tab 1 & Tab 2
            document.getElementById('displayNoPo').value = h.nomor_po || '-';
            document.getElementById('inputPoId').value = h.id_po || '';
            document.getElementById('displayNoSj').value = h.nomor_sj || '-';
            document.getElementById('displaySite').value = h.nama_site || '-';
            document.getElementById('inputSiteId').value = h.id_site || '';
            document.getElementById('displayVendor').value = h.nama_vendor || '-';
            document.getElementById('inputVendorId').value = h.id_vendor || '';
            document.getElementById('displayTeleponVendor').value = h.telepon_vendor || '-';
            document.getElementById('inputPicVendor').value = h.pic_vendor || '';
            ratePajak = parseInt(h.rate_pajak) || 0;
            document.getElementById('summaryRatePajak').textContent = ratePajak;

            // Populate Tab 2 dengan Barang Cacat (status_qc = 0)
            returItems = [];
            if (res.data.damaged_items && res.data.damaged_items.length > 0) {
                res.data.damaged_items.forEach(d => {
                    returItems.push({
                        id_barang: d.id_barang,
                        kode_barang: d.kode_barang,
                        nama_barang: d.nama_barang,
                        qty_max: parseFloat(d.qty_rcv) || 1,
                        qty_retur: parseFloat(d.qty_rcv) || 1,
                        satuan: d.master_satuan || 'PCS',
                        harga_satuan: parseFloat(d.harga_satuan) || 0,
                        alasan_retur: 'RUSAK_FISIK',
                        keterangan_kerusakan: d.ket_rcv || 'Kondisi rusak saat penerimaan barang di gudang',
                        foto_base64: '',
                        foto_name: ''
                    });
                });
            } else if (res.data.items && res.data.items.length > 0) {
                const first = res.data.items[0];
                returItems.push({
                    id_barang: first.id_barang,
                    kode_barang: first.kode_barang,
                    nama_barang: first.nama_barang,
                    qty_max: parseFloat(first.qty_rcv) || 1,
                    qty_retur: parseFloat(first.qty_rcv) || 1,
                    satuan: first.master_satuan || 'PCS',
                    harga_satuan: parseFloat(first.harga_satuan) || 0,
                    alasan_retur: 'CACAT_PRODUKSI',
                    keterangan_kerusakan: '',
                    foto_base64: '',
                    foto_name: ''
                });
            }

            renderItemsTable();
        }
    } catch (e) {
        showToast('Gagal memuat detail penerimaan: ' + e.message, 'danger');
    }
}

function resetRcvDisplay() {
    currentRcvData = null;
    returItems = [];
    document.getElementById('displayNoPo').value = '-';
    document.getElementById('inputPoId').value = '';
    document.getElementById('displayNoSj').value = '-';
    document.getElementById('displaySite').value = '-';
    document.getElementById('inputSiteId').value = '';
    document.getElementById('displayVendor').value = '-';
    document.getElementById('inputVendorId').value = '';
    document.getElementById('displayTeleponVendor').value = '-';
    document.getElementById('inputPicVendor').value = '';
    ratePajak = 0;
    renderItemsTable();
}

function formatBiayaReturInput(input) {
    let raw = input.value.replace(/\D/g, '');
    if (raw === '') {
        input.value = '0';
        return;
    }
    const num = parseInt(raw, 10);
    input.value = new Intl.NumberFormat('id-ID').format(num);
}

function selectPengirimanCard(val) {
    const hidden = document.getElementById('selectedPengiriman');
    if (hidden) hidden.value = val;

    const radios = document.querySelectorAll('input[name="pengiriman_retur"]');
    radios.forEach(r => {
        const isMatch = (r.value === val);
        r.checked = isMatch;
        const card = r.closest('.pengiriman-card');
        if (card) {
            if (isMatch) {
                card.classList.add('border-primary', 'bg-primary-subtle');
                card.classList.remove('bg-light');
            } else {
                card.classList.remove('border-primary', 'bg-primary-subtle');
                card.classList.add('bg-light');
            }
        }
    });
}

function getReasonText(code) {
    switch (code) {
        case 'RUSAK_FISIK': return 'Rusak Fisik / Pengiriman';
        case 'CACAT_PRODUKSI': return 'Cacat Pabrik / Vendor';
        case 'SALAH_SPESIFIKASI': return 'Salah Spesifikasi';
        case 'KURANG_PENGIRIMAN': return 'Kurang Kuantitas';
        case 'KADALUARSA_EXP': return 'Kadaluarsa / Expired';
        default: return code || 'Rusak Fisik';
    }
}

function renderItemsTable() {
    const tbody = document.getElementById('itemsBody');
    document.getElementById('badgeItemCount').textContent = returItems.length;

    if (!returItems || returItems.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="bi bi-box-arrow-in-left fs-3 d-block mb-2 text-secondary"></i>
                    Belum ada barang yang dipilih untuk diretur.
                </td>
            </tr>`;
        updateSummary();
        return;
    }

    let html = '';
    returItems.forEach((item, idx) => {
        const subtotal = item.qty_retur * item.harga_satuan;
        const hasPhotoDot = item.foto_base64 
            ? '<span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:0.7rem;"><i class="bi bi-image"></i> Foto</span>' 
            : '';

        html += `
        <tr>
            <td class="text-center text-muted fw-semibold">${idx + 1}</td>
            <td>
                <div class="fw-bold text-dark">${escapeHtml(item.nama_barang)}</div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="font-monospace text-muted small">${escapeHtml(item.kode_barang || '-')}</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:0.72rem;">${getReasonText(item.alasan_retur)}</span>
                    ${hasPhotoDot}
                </div>
            </td>
            <td>
                <input type="number" step="any" min="0" max="${item.qty_max}" class="form-control form-control-sm text-center fw-bold font-monospace" value="${item.qty_retur}" onchange="updateItemQty(${idx}, this.value)">
                <div class="text-muted text-center" style="font-size: 0.72rem;">Maks: ${item.qty_max}</div>
            </td>
            <td class="text-center fw-semibold text-muted small">${escapeHtml(item.satuan)}</td>
            <td class="text-end font-monospace">${formatRupiah(item.harga_satuan)}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(subtotal)}</td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                    <button type="button" class="btn btn-outline-primary btn-sm p-1 px-2" onclick="openClaimModal(${idx})" title="Alasan Kerusakan, Keterangan & Bukti Foto">
                        <i class="bi bi-chat-left-text"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeItem(${idx})" title="Hapus Baris">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
    updateSummary();
}

// -------------------------------------------------------------
// LOGIKA MODAL DETAIL KERUSAKAN & BUKTI FOTO PER ITEM
// -------------------------------------------------------------
let activeModalItemIdx = null;
let tempModalPhotoBase64 = '';
let tempModalPhotoName = '';

function openClaimModal(idx) {
    if (!returItems[idx]) return;
    activeModalItemIdx = idx;
    const item = returItems[idx];

    document.getElementById('modalItemIndex').value = idx;
    document.getElementById('modalItemNamaHeader').textContent = item.nama_barang || 'Informasi Detail';
    document.getElementById('modalItemKodeHeader').textContent = `${item.kode_barang || '-'} • Qty: ${item.qty_retur} ${item.satuan}`;
    document.getElementById('modalItemAlasan').value = item.alasan_retur || 'RUSAK_FISIK';
    document.getElementById('modalItemKet').value = item.keterangan_kerusakan || '';

    // Kembalikan tab ke Tab 1 (Rincian Kerusakan) saat modal dibuka
    const firstTabBtn = document.getElementById('tab-modal-info-btn');
    if (firstTabBtn) {
        bootstrap.Tab.getOrCreateInstance(firstTabBtn).show();
    }

    // Foto Preview
    tempModalPhotoBase64 = item.foto_base64 || '';
    tempModalPhotoName = item.foto_name || '';

    const previewWrapper = document.getElementById('modalPhotoPreviewWrapper');
    const emptyState = document.getElementById('modalPhotoEmptyState');
    const previewImg = document.getElementById('modalPhotoPreviewImg');
    const photoNameBadge = document.getElementById('modalPhotoName');
    const fotoBadge = document.getElementById('modalFotoBadge');
    const fileInput = document.getElementById('modalItemPhotoInput');
    fileInput.value = '';

    if (tempModalPhotoBase64) {
        previewImg.src = tempModalPhotoBase64;
        photoNameBadge.textContent = tempModalPhotoName || 'foto.jpg';
        previewWrapper.style.display = 'block';
        emptyState.style.display = 'none';
        fotoBadge.style.display = 'inline-block';
    } else {
        previewImg.src = '';
        photoNameBadge.textContent = '';
        previewWrapper.style.display = 'none';
        emptyState.style.display = 'block';
        fotoBadge.style.display = 'none';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalClaimDetail'));
    modal.show();
}

function previewModalPhoto(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validasi format JPG / JPEG
        const validTypes = ['image/jpeg', 'image/jpg'];
        const fileName = file.name.toLowerCase();
        const isJpg = validTypes.includes(file.type) || fileName.endsWith('.jpg') || fileName.endsWith('.jpeg');
        
        if (!isJpg) {
            showToast('Hanya file foto berekstensi JPG / JPEG yang diperbolehkan.', 'warning');
            input.value = '';
            return;
        }

        // Validasi ukuran maks 2MB
        if (file.size > 2 * 1024 * 1024) {
            showToast('Ukuran foto melebihi batas maksimal (Maks 2MB).', 'warning');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            tempModalPhotoBase64 = e.target.result;
            tempModalPhotoName = file.name;

            document.getElementById('modalPhotoPreviewImg').src = tempModalPhotoBase64;
            document.getElementById('modalPhotoName').textContent = file.name;
            document.getElementById('modalPhotoPreviewWrapper').style.display = 'block';
            document.getElementById('modalPhotoEmptyState').style.display = 'none';
            document.getElementById('modalFotoBadge').style.display = 'inline-block';
        };
        reader.readAsDataURL(file);
    }
}

function removeModalPhoto() {
    tempModalPhotoBase64 = '';
    tempModalPhotoName = '';
    document.getElementById('modalItemPhotoInput').value = '';
    document.getElementById('modalPhotoPreviewImg').src = '';
    document.getElementById('modalPhotoName').textContent = '';
    document.getElementById('modalPhotoPreviewWrapper').style.display = 'none';
    document.getElementById('modalPhotoEmptyState').style.display = 'block';
    document.getElementById('modalFotoBadge').style.display = 'none';
}

function saveClaimModal() {
    if (activeModalItemIdx === null || !returItems[activeModalItemIdx]) return;

    returItems[activeModalItemIdx].alasan_retur = document.getElementById('modalItemAlasan').value;
    returItems[activeModalItemIdx].keterangan_kerusakan = document.getElementById('modalItemKet').value.trim();
    returItems[activeModalItemIdx].foto_base64 = tempModalPhotoBase64;
    returItems[activeModalItemIdx].foto_name = tempModalPhotoName;

    const modalEl = document.getElementById('modalClaimDetail');
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (modalInstance) modalInstance.hide();

    renderItemsTable();
    showToast('Informasi detail & foto berhasil disimpan.', 'success');
}

function updateItemQty(idx, val) {
    const num = parseFloat(val) || 0;
    if (num > returItems[idx].qty_max) {
        showToast(`Qty retur tidak boleh melebihi kuantitas penerimaan (${returItems[idx].qty_max})`, 'warning');
        returItems[idx].qty_retur = returItems[idx].qty_max;
    } else {
        returItems[idx].qty_retur = Math.max(0, num);
    }
    renderItemsTable();
}

function removeItem(idx) {
    returItems.splice(idx, 1);
    renderItemsTable();
}

function openAddItemModal() {
    if (!currentRcvData || !currentRcvData.items) {
        showToast('Pilih dokumen RCV pada Tab 1 terlebih dahulu.', 'warning');
        return;
    }

    const list = document.getElementById('rcvAvailableItemsList');
    list.innerHTML = '';

    // Hanya ambil item yang tercatat rusak / cacat fisik saat penerimaan (status_qc == 0)
    const damagedList = currentRcvData.items.filter(it => (it.status_qc == 0 || it.is_damaged));

    if (damagedList.length === 0) {
        list.innerHTML = `<div class="text-center py-4 text-muted small"><i class="bi bi-check2-circle fs-2 text-success d-block mb-1"></i>Tidak ada barang cacat/rusak lainnya pada dokumen penerimaan ini.</div>`;
    } else {
        damagedList.forEach(it => {
            const alreadyInTable = returItems.some(r => r.id_barang == it.id_barang);
            const disabled = alreadyInTable ? 'disabled opacity-50' : '';
            const badge = alreadyInTable ? '<span class="badge bg-secondary ms-2">Sudah Masuk</span>' : '';

            list.innerHTML += `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${disabled}" onclick="addOtherItem(${it.id_barang})">
                    <div>
                        <div class="fw-semibold text-dark">${it.nama_barang}</div>
                        <div class="small text-muted font-monospace">${it.kode_barang} &bull; Rusak: ${it.qty_rcv} ${it.master_satuan}</div>
                    </div>
                    <div>${badge}</div>
                </button>`;
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('modalAddOtherItem'));
    modal.show();
}

function addOtherItem(idBarang) {
    const itemToAdd = currentRcvData.items.find(i => i.id_barang == idBarang);
    if (itemToAdd && !returItems.some(r => r.id_barang == idBarang)) {
        returItems.push({
            id_barang: itemToAdd.id_barang,
            kode_barang: itemToAdd.kode_barang,
            nama_barang: itemToAdd.nama_barang,
            qty_max: parseFloat(itemToAdd.qty_rcv) || 1,
            qty_retur: parseFloat(itemToAdd.qty_rcv) || 1,
            satuan: itemToAdd.master_satuan || 'PCS',
            harga_satuan: parseFloat(itemToAdd.harga_satuan) || 0,
            alasan_retur: itemToAdd.status_qc == 0 ? 'RUSAK_FISIK' : 'CACAT_PRODUKSI',
            keterangan_kerusakan: itemToAdd.ket_rcv || '',
            foto_base64: '',
            foto_name: ''
        });
        renderItemsTable();
    }
    bootstrap.Modal.getInstance(document.getElementById('modalAddOtherItem')).hide();
}

function updateSummary() {
    let subtotal = 0;
    returItems.forEach(i => {
        subtotal += (i.qty_retur * i.harga_satuan);
    });

    const nominalPajak = subtotal * (ratePajak / 100);
    const grandTotal = subtotal + nominalPajak;

    document.getElementById('summarySubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('summaryNominalPajak').textContent = formatRupiah(nominalPajak);
    document.getElementById('summaryGrandTotal').textContent = formatRupiah(grandTotal);
}

function goToTab(tabBtnId) {
    const rcvEl = document.getElementById('selectRcv');
    const headerData = (currentRcvData && currentRcvData.header) ? currentRcvData.header : {};
    const rcvId = parseInt(rcvEl ? rcvEl.value : 0) || parseInt(headerData.id_rcv || 0) || parseInt(selectedRcvId || 0);

    // Validasi saat berpindah dari Tab 1
    if (tabBtnId !== 'tab-info-btn') {
        if (!rcvId || rcvId <= 0) {
            showToast('Harap pilih Dokumen Penerimaan (RCV) pada Tab 1 terlebih dahulu.', 'warning');
            const infoBtn = document.getElementById('tab-info-btn');
            if (infoBtn) bootstrap.Tab.getOrCreateInstance(infoBtn).show();
            return;
        }
    }

    // Validasi saat melewati Tab 2 (Vendor)
    if (tabBtnId === 'tab-pengiriman-btn' || tabBtnId === 'tab-items-btn' || tabBtnId === 'tab-persetujuan-btn') {
        const pic = (document.getElementById('inputPicVendor').value || headerData.pic_vendor || '').trim();
        if (!pic) {
            showToast('Harap lengkapi PIC Vendor pada Tab 2.', 'warning');
            const vendorBtn = document.getElementById('tab-vendor-btn');
            if (vendorBtn) bootstrap.Tab.getOrCreateInstance(vendorBtn).show();
            document.getElementById('inputPicVendor').focus();
            return;
        }
    }

    const targetBtn = document.getElementById(tabBtnId);
    if (targetBtn) {
        bootstrap.Tab.getOrCreateInstance(targetBtn).show();
    }
}

async function submitReturForm(targetStatus) {
    const rcvEl = document.getElementById('selectRcv');
    const headerData = (currentRcvData && currentRcvData.header) ? currentRcvData.header : {};
    
    const idRcv = parseInt(rcvEl ? rcvEl.value : 0) || parseInt(headerData.id_rcv || 0) || parseInt(selectedRcvId || 0);
    const idPo = parseInt(document.getElementById('inputPoId').value || 0) || parseInt(headerData.id_po || 0);
    const idVendor = parseInt(document.getElementById('inputVendorId').value || 0) || parseInt(headerData.id_vendor || 0);
    const idSite = parseInt(document.getElementById('inputSiteId').value || 0) || parseInt(headerData.id_site || 0);
    const picVendor = (document.getElementById('inputPicVendor').value || headerData.pic_vendor || '').trim();
    const kompensasiEl = document.querySelector('input[name="kompensasi"]:checked');
    const kompensasi = kompensasiEl ? parseInt(kompensasiEl.value) : 1;
    const pengirimanHidden = document.getElementById('selectedPengiriman');
    const pengirimanRadio = document.querySelector('input[name="pengiriman_retur"]:checked');
    const pengirimanRetur = (pengirimanHidden && pengirimanHidden.value) ? pengirimanHidden.value : (pengirimanRadio ? pengirimanRadio.value : 'Vendor');
    const biayaReturInput = document.getElementById('inputBiayaRetur');
    const biayaRetur = biayaReturInput ? (parseFloat(biayaReturInput.value.replace(/\D/g, '')) || 0) : 0;
    const approverEl = document.getElementById('selectKaryawanApproved');
    let idKaryawanApproved = approverEl ? parseInt(approverEl.value) : 0;
    const tanggalRetur = document.getElementById('inputTanggalRetur').value;
    const noSjRetur = document.getElementById('inputNoSjRetur').value.trim();
    const noNotaPajak = document.getElementById('inputNoNotaPajak').value.trim();
    const keterangan = document.getElementById('inputKeterangan').value.trim();

    if (!idRcv || idRcv <= 0) {
        showToast('Dokumen Penerimaan (RCV) wajib dipilih pada Tab 1.', 'warning');
        goToTab('tab-info-btn');
        return;
    }

    if (!picVendor) {
        showToast('PIC Vendor wajib diisi pada Tab 2.', 'warning');
        goToTab('tab-vendor-btn');
        return;
    }

    if (!returItems || returItems.length === 0) {
        showToast('Harap tambahkan minimal 1 item barang yang akan diretur pada Tab 4.', 'warning');
        goToTab('tab-items-btn');
        return;
    }

    if (!idKaryawanApproved || idKaryawanApproved <= 0) {
        showToast('Harap pilih Pejabat Penyetuju pada Tab 5.', 'warning');
        goToTab('tab-persetujuan-btn');
        return;
    }

    const nomorRetur = (document.getElementById('inputNomorRetur') ? document.getElementById('inputNomorRetur').value : '').trim();
    const payload = {
        nomor_po_retur: nomorRetur,
        id_po: idPo,
        id_rcv: idRcv,
        id_vendor: idVendor,
        id_site: idSite,
        pic_vendor: picVendor,
        kompensasi: kompensasi,
        pengiriman_retur: pengirimanRetur,
        biaya_retur: biayaRetur,
        id_karyawan_approved: idKaryawanApproved,
        tanggal_po_retur: tanggalRetur,
        rate_pajak: ratePajak,
        nomor_sj_retur: noSjRetur,
        nomor_nota_retur_pajak: noNotaPajak,
        keterangan: keterangan,
        status: targetStatus,
        items: returItems
    };

    try {
        const res = await apiRequest('/api/retur_po/index.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        if (res && res.success) {
            showToast(res.message || 'Dokumen Retur PO berhasil dibuat!', 'success');
            setTimeout(() => {
                window.location.href = `<?= BASE_URL ?>/admin/pages/retur_po/detail.php?id=${res.data.id_po_retur}`;
            }, 1200);
        } else {
            showToast(res.message || 'Gagal membuat dokumen Retur PO.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan sistem: ' + e.message, 'danger');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatRupiah(num) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}
</script>

<style>
.rcv-custom-select {
    min-height: 40px;
    transition: all 0.2s ease;
}
.rcv-custom-select:hover {
    border-color: #86b7fe;
    background-color: #fafbfc !important;
}
.rcv-item-card {
    background-color: #fafbfc;
    transition: all 0.15s ease;
}
.rcv-item-card:hover {
    background-color: #f0f7ff;
    border-color: #86b7fe !important;
    transform: translateY(-1px);
}
.rcv-item-card.active-rcv {
    background-color: #e7f1ff;
    border-color: #0d6efd !important;
}
.transition-chevron {
    transition: transform 0.2s ease;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
