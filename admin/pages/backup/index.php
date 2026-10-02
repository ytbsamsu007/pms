<?php
/**
 * Halaman Manajemen Backup & Restore Database
 * Path: admin/pages/backup/index.php
 * Akses: Khusus ROLE_ADMIN
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Hanya Administrator yang boleh mengakses
$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Backup & Restore Database';
$pageHeading = 'Cadangan & Pemulihan Database';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
/* Konsistensi Form Control Filter Bar Sesuai Standard Proyek */
.backup-filter-bar .form-control,
.backup-filter-bar .form-select,
.backup-filter-bar .input-group-text,
.backup-filter-bar .btn {
    height: 38px !important;
    font-size: 0.85rem !important;
}
.backup-filter-bar .input-group-text {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 10px;
    padding-right: 10px;
}
.backup-filter-bar .form-select {
    padding-top: 0.22rem !important;
    padding-bottom: 0.28rem !important;
    padding-right: 2rem !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    line-height: 1.5 !important;
}
.backup-filter-bar .form-control {
    padding-top: 0.22rem !important;
    padding-bottom: 0.28rem !important;
    line-height: 1.5 !important;
}
.backup-filter-bar input[type="date"] {
    -moz-appearance: textfield !important;
    appearance: none !important;
    padding-top: 0.22rem !important;
    padding-bottom: 0.28rem !important;
}
.backup-filter-bar input[type="date"]::-webkit-calendar-picker-indicator {
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

/* Modal UI Tabs & Styling Seragam */
.table-tables-container {
    max-height: 290px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 0 0 8px 8px;
    background: #ffffff;
}
.table-item-card {
    transition: all 0.15s ease-in-out;
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
    cursor: pointer;
}
.table-item-card:hover {
    background-color: #f8fafc;
    border-color: #cbd5e1;
}
.table-item-card.is-selected {
    background-color: #eff6ff;
    border-color: #93c5fd;
}
.table-search-box {
    border-radius: 8px 8px 0 0;
    border-bottom: 0;
}
.otp-input-box {
    font-family: 'Courier New', Courier, monospace;
    font-size: 20px;
    letter-spacing: 5px;
    text-align: center;
    font-weight: bold;
}
</style>

<div class="container-fluid px-0">
    <!-- Header Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Backup &amp; Restore Database</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 fw-semibold shadow-sm" onclick="loadBackupData(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" onclick="openBackupModal()">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Buat Backup
            </button>
        </div>
    </div>

    <!-- Filter & Tabel Data Backup -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 backup-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-4 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari nama berkas, email, atau keterangan..." autocomplete="off" oninput="debounceSearch()">
                    </div>
                </div>

                <!-- Scope Filter (Auto ... jika panjang) -->
                <div class="col-6 col-md-3" style="min-width: 190px;">
                    <select class="form-select form-select-sm text-truncate" id="filterScope" onchange="loadBackupData(1)" title="Pilih Cakupan Scope">
                        <option value="">Semua Cakupan Scope</option>
                        <option value="ALL">Full Backup (Semua Tabel)</option>
                        <option value="PARTIAL">Partial Backup (Pilihan Tabel)</option>
                    </select>
                </div>

                <!-- Range Tanggal: Dari Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 150px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Dari Tanggal" onclick="const el=document.getElementById('filterStartDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterStartDate" placeholder="mm / dd / yyyy" title="Dari Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="loadBackupData(1)">
                    </div>
                </div>

                <!-- Range Tanggal: Sampai Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 150px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Sampai Tanggal" onclick="const el=document.getElementById('filterEndDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-check"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterEndDate" placeholder="mm / dd / yyyy" title="Sampai Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="loadBackupData(1)">
                    </div>
                </div>

                <!-- Tombol Reset (Rata Kanan) -->
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetFilter()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL DATA ARSIP BACKUP -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableBackup">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th style="min-width: 220px;">Nama File</th>
                            <th style="min-width: 130px;">Scope</th>
                            <th style="min-width: 150px;">Tanggal Backup</th>
                            <th style="min-width: 150px;">Tanggal Restore</th>
                            <th class="text-center" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBackup">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Memuat data riwayat backup...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FOOTER PAGINATION -->
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2" id="paginationContainer">
                <div class="text-muted small" id="paginationInfo">Menampilkan 0 data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationNav"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 1: BUAT BACKUP DATABASE (DENGAN 4 TABS TERPADU & SERAGAM)-->
<!-- ============================================================== -->
<div class="modal fade" id="modalBackup" tabindex="-1" aria-labelledby="modalBackupLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 820px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="formBackup" onsubmit="event.preventDefault(); submitBackupProcess();">
                <!-- MODAL HEADER DENGAN 4 NAV TABS TERPADU -->
                <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="modalBackupLabel">
                            Backup Database
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- 4 Nav Tabs Modal Form Sesuai Fungsi -->
                    <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="backupModalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark small py-2 px-3" id="btab-scope" data-bs-toggle="tab" data-bs-target="#bpane-scope" type="button" role="tab">
                                <i class="bi bi-diagram-3 me-1 text-primary"></i> 1. Cakupan Scope
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="btab-email" data-bs-toggle="tab" data-bs-target="#bpane-email" type="button" role="tab">
                                <i class="bi bi-envelope-at me-1 text-primary"></i> 2. Email Tujuan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="btab-catatan" data-bs-toggle="tab" data-bs-target="#bpane-catatan" type="button" role="tab">
                                <i class="bi bi-card-text me-1 text-primary"></i> 3. Catatan Arsip
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="btab-security" data-bs-toggle="tab" data-bs-target="#bpane-security" type="button" role="tab">
                                <i class="bi bi-shield-lock me-1 text-primary"></i> 4. Password &amp; OTP
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="backupTabContent">
                        
                        <!-- TAB 1: CAKUPAN SCOPE TABEL -->
                        <div class="tab-pane fade show active" id="bpane-scope" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-bold small text-dark mb-0">Metode Pemilihan Tabel Database:</label>
                                <span class="badge bg-primary font-monospace" id="labelTotalSelectedTables">0 Tabel Dipilih</span>
                            </div>

                            <div class="card bg-light border-0 p-3 mb-3">
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="scope_mode" id="scopeAll" value="ALL" checked onchange="handleScopeModeChange()">
                                        <label class="form-check-label fw-semibold small text-dark" for="scopeAll">
                                            <i class="bi bi-check-all text-primary me-1"></i> Semua Tabel (Full Database Backup)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="scope_mode" id="scopeCustom" value="CUSTOM" onchange="handleScopeModeChange()">
                                        <label class="form-check-label fw-semibold small text-dark" for="scopeCustom">
                                            <i class="bi bi-list-check text-success me-1"></i> Pilih Tabel Tertentu (Partial Scope)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- List Tabel Checkbox yang Dirapikan -->
                            <div id="tableSelectorWrapper" style="display: none;">
                                <div class="p-2 bg-light border table-search-box d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="checkSelectAllTables" onchange="toggleSelectAllTables(this.checked)">
                                        <label class="form-check-label fw-bold text-dark small" for="checkSelectAllTables">Pilih Semua (Select All)</label>
                                    </div>
                                    <div class="input-group input-group-sm" style="width: 220px;">
                                        <span class="input-group-text bg-white py-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" class="form-control py-1" id="filterTableSearch" placeholder="Cari nama tabel..." oninput="filterTableCheckboxList()">
                                    </div>
                                </div>
                                <div class="table-tables-container p-2" id="tablesCheckboxContainer">
                                    <div class="text-center py-3 text-muted small">
                                        <div class="spinner-border spinner-border-sm text-primary me-1"></div> Mengambil skema tabel...
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" onclick="goToTab('btab-email')">
                                    Lanjut ke Email Tujuan <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- TAB 2: EMAIL TUJUAN -->
                        <div class="tab-pane fade" id="bpane-email" role="tabpanel">
                            

                            <div class="mb-3">
                                <label for="backupEmail" class="form-label small fw-bold text-dark">
                                    Alamat Email Tujuan Pengiriman Berkas (.SQL) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-envelope-at text-muted"></i></span>
                                    <input type="email" class="form-control" id="backupEmail" required placeholder="nama@perusahaan.com">
                                </div>
                                
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('btab-scope')">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" onclick="goToTab('btab-catatan')">
                                    Lanjut ke Catatan Arsip <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- TAB 3: CATATAN / KETERANGAN ARSIP (TAB SENDIRI) -->
                        <div class="tab-pane fade" id="bpane-catatan" role="tabpanel">
                           

                            <div class="mb-3">
                                <label for="backupKeterangan" class="form-label small fw-bold text-dark">Catatan / Keterangan Arsip</label>
                                <textarea class="form-control" id="backupKeterangan" rows="4" placeholder="Contoh: Pencadangan berkala database sebelum update modul transaksi..."></textarea>
                                
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('btab-email')">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold" onclick="goToTab('btab-security')">
                                    Lanjut ke Otorisasi Keamanan <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- TAB 4: OTORISASI PASSWORD & OTP -->
                        <div class="tab-pane fade" id="bpane-security" role="tabpanel">
                           

                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-6">
                                    <label for="backupPassword" class="form-label small fw-bold text-dark">
                                        Password Akun Anda <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white"><i class="bi bi-lock-fill text-muted"></i></span>
                                        <input type="password" class="form-control" id="backupPassword" placeholder="Masukkan password login Anda" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-primary w-100 fw-semibold" id="btnRequestOtpBackup" onclick="requestOtpBackup()">
                                        <i class="bi bi-send-fill me-1"></i> Minta Kode OTP ke Email
                                    </button>
                                </div>
                            </div>

                            <div class="row g-3" id="otpBackupSection" style="display: none;">
                                <div class="col-md-6">
                                    <label for="backupOtp" class="form-label small fw-bold text-dark">
                                        Masukkan 6 Digit Kode OTP <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control form-control-sm otp-input-box text-primary" id="backupOtp" maxlength="6" placeholder="------" autocomplete="off">
                                    <div class="form-text small text-muted" id="otpBackupHelp">Kode OTP berlaku selama 15 menit.</div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-2 px-3 small w-100 text-center" id="otpBackupCountdown" style="display: none;">
                                        <i class="bi bi-hourglass-split me-1"></i> Tunggu <span id="otpBackupTimer">60</span> detik untuk kirim ulang
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('btab-catatan')">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                </button>
                                <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" id="btnSubmitBackup" onclick="submitBackupProcess()">
                                    <i class="bi bi-cloud-arrow-up-fill me-1"></i> Eksekusi &amp; Simpan Backup
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: QUICK RESTORE DATABASE (SERAGAM DENGAN STANDARD)      -->
<!-- ============================================================== -->
<div class="modal fade" id="modalRestore" tabindex="-1" aria-labelledby="modalRestoreLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered" style="max-width: 580px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0" id="modalRestoreLabel">
                    Konfirmasi Pemulihan Database
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRestore" onsubmit="event.preventDefault(); submitRestoreProcess();">
                <div class="modal-body px-4 py-3">
                    <div class="alert alert-danger border-0 d-flex gap-2 align-items-start py-2 px-3 mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="d-block text-danger small">Peringatan Kritis:</strong>
                            <span class="small" style="font-size: 0.8rem;">Proses ini akan menimpa data pada tabel terpilih dengan berkas cadangan. Pastikan data terkini telah diamankan.</span>
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="row g-2 small">
                            <div class="col-4 text-muted">Berkas SQL:</div>
                            <div class="col-8 fw-bold font-monospace text-primary text-truncate" id="restoreModalFileName">-</div>
                            <div class="col-4 text-muted">Cakupan Scope:</div>
                            <div class="col-8" id="restoreModalScope">-</div>
                            <div class="col-4 text-muted">Waktu Backup:</div>
                            <div class="col-8" id="restoreModalTanggal">-</div>
                        </div>
                    </div>

                    <input type="hidden" id="restoreBackupId" value="0">

                    <!-- Otorisasi Keamanan: Password & Tombol Minta OTP Berdampingan (Sama Tinggi) -->
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-md-6">
                            <label for="restorePassword" class="form-label small fw-bold text-dark">
                                Password Akun Anda <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-lock-fill text-muted"></i></span>
                                <input type="password" class="form-control" id="restorePassword" placeholder="Masukkan password login" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-outline-danger w-100 fw-semibold" id="btnRequestOtpRestore" onclick="requestOtpRestore()">
                                <i class="bi bi-send-fill me-1"></i> Minta Kode OTP
                            </button>
                        </div>
                    </div>

                    <!-- Input 6 Digit OTP -->
                    <div class="row g-3" id="otpRestoreSection" style="display: none;">
                        <div class="col-12">
                            <label for="restoreOtp" class="form-label small fw-bold text-dark">
                                Masukkan 6 Digit Kode OTP <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm otp-input-box text-danger" id="restoreOtp" maxlength="6" placeholder="------" autocomplete="off">
                            <div class="form-text small text-muted">Masukkan 6 digit kode OTP yang dikirimkan ke email login Anda (berlaku 15 menit).</div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Bawah Terpadu -->
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-semibold" id="btnSubmitRestore">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Eksekusi Pemulihan Database
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 3: RINCIAN TABEL                                         -->
<!-- ============================================================== -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailLabel">
                    Rincian Arsip Backup
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <table class="table table-sm table-borderless small mb-3">
                    <tr><td class="text-muted" style="width: 35%;">Nama Berkas:</td><td class="fw-bold font-monospace text-primary" id="detailFileName">-</td></tr>
                    <tr><td class="text-muted">Ukuran Berkas:</td><td class="fw-bold" id="detailFileSize">-</td></tr>
                    <tr><td class="text-muted">Waktu Backup:</td><td id="detailTanggalBackup">-</td></tr>
                    <tr><td class="text-muted">Dibuat Oleh:</td><td id="detailPelaksanaBackup">-</td></tr>
                    <tr><td class="text-muted">Email Pengiriman:</td><td id="detailEmailBackup">-</td></tr>
                    <tr><td class="text-muted">Keterangan:</td><td id="detailKeterangan">-</td></tr>
                    <tr><td class="text-muted">Status Restore:</td><td id="detailStatusRestore">-</td></tr>
                </table>

                <div>
                    <label class="form-label fw-bold small text-dark mb-1">Daftar Tabel Terarsip (Scope):</label>
                    <div class="p-2 border rounded bg-light" style="max-height: 140px; overflow-y: auto;" id="detailScopeList">
                        -
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Frontend Logic: Backup & Restore Manager
 * Terintegrasi penuh dengan REST API Backend.
 */

let currentPage = 1;
let totalPages = 1;
let searchTimeout = null;
let currentTablesData = [];
let defaultSmtpEmail = '';
let selectedDetailBackupId = 0;
let otpCooldownInterval = null;

let modalBackupInstance = null;
let modalRestoreInstance = null;
let modalDetailInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    modalBackupInstance = new bootstrap.Modal(document.getElementById('modalBackup'));
    modalRestoreInstance = new bootstrap.Modal(document.getElementById('modalRestore'));
    modalDetailInstance = new bootstrap.Modal(document.getElementById('modalDetail'));

    loadBackupData(1);
    fetchDatabaseTables();
});

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadBackupData(1);
    }, 300);
}

function goToTab(tabButtonId) {
    const tabEl = document.getElementById(tabButtonId);
    if (tabEl) {
        bootstrap.Tab.getInstance(tabEl)?.show() || new bootstrap.Tab(tabEl).show();
    }
}

/**
 * 1. Memuat Riwayat Backup dari API
 */
async function loadBackupData(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('tbodyBackup');
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Memuat data riwayat backup...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const scope = document.getElementById('filterScope').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        page: page,
        limit: 10,
        search: search,
        scope: scope,
        start_date: startDate,
        end_date: endDate
    });

    try {
        const result = await apiRequest(`/api/backup/index.php?${params.toString()}`);

        if (!result || !result.success) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger py-4">${result ? result.message : 'Gagal memuat data'}</td></tr>`;
            return;
        }

        const data = result.data.data;
        const pagination = result.data.pagination;

        // Render Table Body
        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                        Belum ada riwayat pencadangan database yang sesuai kriteria.
                    </td>
                </tr>
            `;
        } else {
            let html = '';
            data.forEach((item, index) => {
                const no = (pagination.current_page - 1) * pagination.limit + (index + 1);
                
                // Badge Scope
                const scopeBadge = item.is_all_scope 
                    ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-check-all me-1"></i>Seluruh Tabel</span>`
                    : `<span class="badge bg-teal-subtle text-dark border px-2 py-1"><i class="bi bi-list-check me-1"></i>${item.scope_count} Tabel</span>`;

                // Tanggal Restore Badge / Text
                const tanggalRestoreText = item.is_restored
                    ? `<div class="small fw-semibold text-success">${item.tanggal_restore_format}</div>`
                    : `<span class="badge bg-light text-muted border px-2 py-1">Belum Pernah</span>`;

                html += `
                    <tr>
                        <td class="text-center text-muted fw-bold">${no}</td>
                        <td>
                            <div class="fw-bold text-dark font-monospace small text-truncate" style="max-width: 280px;" title="${escapeHtml(item.nama_file)}">
                                ${escapeHtml(item.nama_file)}
                            </div>
                        </td>
                        <td>${scopeBadge}</td>
                        <td>
                            <div class="small fw-semibold text-dark">${item.tanggal_backup_format}</div>
                        </td>
                        <td>
                            ${tanggalRestoreText}
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" title="Lihat Rincian" onclick='openDetailModal(${JSON.stringify(item)})'>
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                ${item.file_exists ? `
                                <a href="<?= BASE_URL ?>/api/backup/download.php?id=${item.id_backup}" class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-xs" title="Unduh Berkas SQL">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" title="Pulihkan Database (Restore)" onclick='openRestoreModal(${JSON.stringify(item)})'>
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                ` : ''}
                                <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" title="Hapus Riwayat" onclick="deleteBackup(${item.id_backup}, '${escapeHtml(item.nama_file)}')">
                                    <i class="bi bi-x-octagon-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // Render Pagination
        renderPagination(pagination);

    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Gagal terhubung ke API: ${err.message}</td></tr>`;
    }
}

/**
 * 2. Mengambil Daftar Tabel untuk Scope Selection
 */
async function fetchDatabaseTables() {
    try {
        const result = await apiRequest(`/api/backup/tables.php`);

        if (result && result.success) {
            currentTablesData = result.data.tables || [];
            defaultSmtpEmail = result.data.default_email || '';
            renderTablesCheckboxList();
        }
    } catch (e) {
        console.error('Gagal mengambil daftar tabel database', e);
    }
}

function renderTablesCheckboxList(filterKeyword = '') {
    const container = document.getElementById('tablesCheckboxContainer');

    if (!currentTablesData || currentTablesData.length === 0) {
        container.innerHTML = '<div class="text-center py-3 text-muted small">Tidak ada tabel ditemukan dalam database.</div>';
        return;
    }

    const keyword = filterKeyword.toLowerCase().trim();
    const filteredTables = currentTablesData.filter(t => t.name.toLowerCase().includes(keyword));

    if (filteredTables.length === 0) {
        container.innerHTML = '<div class="text-center py-3 text-muted small">Tidak ada tabel yang cocok dengan kata kunci pencarian.</div>';
        return;
    }

    let html = '<div class="row g-2">';
    filteredTables.forEach((t) => {
        html += `
            <div class="col-md-6 table-item-col" data-table-name="${escapeHtml(t.name)}">
                <label class="p-2 rounded-2 table-item-card d-flex align-items-center justify-content-between mb-0" for="tbl_${escapeHtml(t.name)}">
                    <div class="form-check mb-0 d-flex align-items-center gap-2">
                        <input class="form-check-input table-checkbox-item mt-0" type="checkbox" value="${escapeHtml(t.name)}" id="tbl_${escapeHtml(t.name)}" onchange="updateSelectedTableCount(this)">
                        <span class="font-monospace small fw-bold text-dark">${escapeHtml(t.name)}</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 10px;">${t.rows} baris</span>
                        <span class="badge bg-secondary-subtle text-secondary border font-monospace" style="font-size: 10px;">${t.size_formatted}</span>
                    </div>
                </label>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function filterTableCheckboxList() {
    const keyword = document.getElementById('filterTableSearch').value;
    renderTablesCheckboxList(keyword);
    // Sinkronkan state checkbox yang sudah terpilih
    updateSelectedTableCount();
}

function handleScopeModeChange() {
    const isCustom = document.getElementById('scopeCustom').checked;
    const wrapper = document.getElementById('tableSelectorWrapper');
    wrapper.style.display = isCustom ? 'block' : 'none';
    updateSelectedTableCount();
}

function toggleSelectAllTables(checked) {
    const checkboxes = document.querySelectorAll('.table-checkbox-item');
    checkboxes.forEach(cb => {
        cb.checked = checked;
        const card = cb.closest('.table-item-card');
        if (card) {
            if (checked) card.classList.add('is-selected');
            else card.classList.remove('is-selected');
        }
    });
    updateSelectedTableCount();
}

function updateSelectedTableCount(inputElem = null) {
    if (inputElem) {
        const card = inputElem.closest('.table-item-card');
        if (card) {
            if (inputElem.checked) card.classList.add('is-selected');
            else card.classList.remove('is-selected');
        }
    }

    const isAll = document.getElementById('scopeAll').checked;
    const badge = document.getElementById('labelTotalSelectedTables');

    if (isAll) {
        badge.textContent = `Semua Tabel (${currentTablesData.length})`;
        badge.className = 'badge bg-primary font-monospace';
    } else {
        const checked = document.querySelectorAll('.table-checkbox-item:checked');
        badge.textContent = `${checked.length} dari ${currentTablesData.length} Tabel`;
        badge.className = checked.length > 0 ? 'badge bg-success font-monospace' : 'badge bg-danger font-monospace';
    }
}

// Helper: Freeze Persistence (LocalStorage)
function getRemainingFreezeSeconds() {
    try {
        const freezeUntil = parseInt(localStorage.getItem('backup_freeze_until') || '0', 10);
        if (freezeUntil > Date.now()) {
            return Math.ceil((freezeUntil - Date.now()) / 1000);
        }
    } catch (e) {}
    return 0;
}

function setFreezeUntil(seconds) {
    try {
        const freezeUntil = Date.now() + (seconds * 1000);
        localStorage.setItem('backup_freeze_until', freezeUntil.toString());
    } catch (e) {}
}

function clearFreeze() {
    try {
        localStorage.removeItem('backup_freeze_until');
    } catch (e) {}
}

/**
 * 3. Modal Backup Trigger & Otp Request
 */
function openBackupModal() {
    document.getElementById('formBackup').reset();
    document.getElementById('scopeAll').checked = true;
    handleScopeModeChange();
    
    document.getElementById('backupEmail').value = defaultSmtpEmail;
    document.getElementById('otpBackupSection').style.display = 'none';

    const btnRequest = document.getElementById('btnRequestOtpBackup');
    const btnSubmit = document.getElementById('btnSubmitBackup');
    const otpInput = document.getElementById('backupOtp');
    const helpEl = document.getElementById('otpBackupHelp');

    if (otpInput) {
        otpInput.disabled = false;
        otpInput.value = '';
    }
    if (helpEl) {
        helpEl.innerHTML = 'Kode OTP berlaku selama 15 menit.';
    }

    const remainingFreeze = getRemainingFreezeSeconds();
    if (remainingFreeze > 0) {
        if (btnSubmit) btnSubmit.disabled = true;
        startFreezeCooldown('otpBackupCountdown', 'otpBackupTimer', btnRequest, 'backupOtp', 'btnSubmitBackup', remainingFreeze, false);
    } else {
        if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Eksekusi &amp; Simpan Backup';
        }
        if (btnRequest) {
            btnRequest.disabled = false;
            btnRequest.className = 'btn btn-sm btn-outline-primary w-100 fw-semibold';
            btnRequest.innerHTML = '<i class="bi bi-send-fill me-1"></i> Minta Kode OTP ke Email';
        }
    }

    // Reset tab to tab 1
    goToTab('btab-scope');

    modalBackupInstance.show();
}

async function requestOtpBackup() {
    const password = document.getElementById('backupPassword').value.trim();
    const email = document.getElementById('backupEmail').value.trim();
    const btn = document.getElementById('btnRequestOtpBackup');

    if (!password) {
        showToast('Silakan masukkan Password akun Anda terlebih dahulu.', 'warning');
        return;
    }
    if (!email) {
        showToast('Silakan tentukan Email tujuan pada Tab 2 terlebih dahulu.', 'warning');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim OTP...';

    try {
        const result = await apiRequest(`/api/backup/request_otp.php`, 'POST', {
            password: password,
            email: email,
            action_type: 'BACKUP_DATABASE'
        });

        if (result && result.success) {
            clearFreeze();
            showToast(result.message || 'Kode OTP telah berhasil dikirim ke email.', 'success');

            document.getElementById('otpBackupSection').style.display = 'flex';
            const otpInput = document.getElementById('backupOtp');
            otpInput.disabled = false;
            otpInput.focus();

            const helpEl = document.getElementById('otpBackupHelp');
            if (helpEl) {
                helpEl.innerHTML = 'Kode OTP berlaku selama 15 menit (Sisa kesempatan: 3x).';
            }

            startOtpCooldown('otpBackupCountdown', 'otpBackupTimer', btn, result.data?.cooldown_seconds || 60);

        } else {
            showToast(result ? result.message : 'Gagal meminta kode OTP', 'error');
            if (result && result.data && result.data.frozen) {
                startFreezeCooldown('otpBackupCountdown', 'otpBackupTimer', btn, 'backupOtp', 'btnSubmitBackup', result.data.freeze_seconds || 300, false);
            } else if (result && result.data && result.data.cooldown_seconds) {
                startOtpCooldown('otpBackupCountdown', 'otpBackupTimer', btn, result.data.cooldown_seconds);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Minta Kode OTP ke Email';
            }
        }
    } catch (e) {
        showToast('Terjadi kesalahan komunikasi API: ' + e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Minta Kode OTP ke Email';
    }
}

async function submitBackupProcess() {
    const password = document.getElementById('backupPassword').value.trim();
    const otp = document.getElementById('backupOtp').value.trim();
    const email = document.getElementById('backupEmail').value.trim();
    const keterangan = document.getElementById('backupKeterangan').value.trim();
    const isAll = document.getElementById('scopeAll').checked;

    if (!email) {
        goToTab('btab-email');
        showToast('Alamat email tujuan wajib diisi pada Tab 2.', 'warning');
        return;
    }
    if (!password) {
        goToTab('btab-security');
        showToast('Password akun wajib diisi pada Tab 4.', 'warning');
        return;
    }
    if (!otp) {
        goToTab('btab-security');
        showToast('Kode OTP 6-digit wajib diisi pada Tab 4.', 'warning');
        return;
    }

    let scopePayload = 'ALL';
    if (!isAll) {
        const checkedNodes = document.querySelectorAll('.table-checkbox-item:checked');
        const selectedTables = Array.from(checkedNodes).map(cb => cb.value);
        if (selectedTables.length === 0) {
            goToTab('btab-scope');
            showToast('Anda memilih mode Partial Scope, silakan centang minimal 1 tabel.', 'warning');
            return;
        }
        scopePayload = selectedTables;
    }

    const btnSubmit = document.getElementById('btnSubmitBackup');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengenerate & Mengirim Backup...';

    try {
        const result = await apiRequest(`/api/backup/create.php`, 'POST', {
            password: password,
            otp_code: otp,
            email_backup: email,
            scope: scopePayload,
            keterangan: keterangan
        });

        if (result && result.success) {
            clearFreeze();
            modalBackupInstance.hide();
            showToast(result.message || 'Backup database berhasil digenerate dan disimpan!', 'success');
            loadBackupData(1);
        } else {
            showToast(result ? result.message : 'Terjadi kesalahan sistem saat membuat backup', 'error');

            if (result && result.data) {
                if (result.data.frozen) {
                    const btnRequest = document.getElementById('btnRequestOtpBackup');
                    startFreezeCooldown('otpBackupCountdown', 'otpBackupTimer', btnRequest, 'backupOtp', 'btnSubmitBackup', result.data.freeze_seconds || 300, true);
                    const helpEl = document.getElementById('otpBackupHelp');
                    if (helpEl) {
                        helpEl.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-shield-x me-1"></i>Batas 3x percobaan terlampaui. Dibekukan selama 5 menit.</span>`;
                    }
                } else if (result.data.remaining_attempts !== undefined) {
                    const helpEl = document.getElementById('otpBackupHelp');
                    if (helpEl) {
                        helpEl.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i>Sisa kesempatan memasukkan OTP: ${result.data.remaining_attempts}x lagi</span>`;
                    }
                    const otpInput = document.getElementById('backupOtp');
                    if (otpInput) {
                        otpInput.focus();
                        otpInput.select();
                    }
                }
            }
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    } finally {
        if (!document.getElementById('backupOtp').disabled) {
            btnSubmit.disabled = false;
        }
        btnSubmit.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Eksekusi &amp; Simpan Backup';
    }
}

/**
 * 4. Modal Restore Trigger & Otp Request
 */
function openRestoreModal(item) {
    document.getElementById('formRestore').reset();
    document.getElementById('restoreBackupId').value = item.id_backup;
    document.getElementById('restoreModalFileName').textContent = item.nama_file;
    document.getElementById('restoreModalScope').textContent = item.scope_formatted;
    document.getElementById('restoreModalTanggal').textContent = item.tanggal_backup_format;
    document.getElementById('otpRestoreSection').style.display = 'none';

    const remainingFreeze = getRemainingFreezeSeconds();
    if (remainingFreeze > 0) {
        const btnOtp = document.getElementById('btnRequestOtpRestore');
        const otpInput = document.getElementById('restoreOtp');
        const btnSubmit = document.getElementById('btnSubmitRestore');
        if (otpInput) otpInput.disabled = true;
        if (btnSubmit) btnSubmit.disabled = true;
        if (btnOtp) {
            btnOtp.disabled = true;
            btnOtp.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Dibekukan: ${formatMinutesSeconds(remainingFreeze)}`;
        }
    } else {
        const otpInput = document.getElementById('restoreOtp');
        if (otpInput) {
            otpInput.disabled = false;
            otpInput.value = '';
        }
        const btnSubmit = document.getElementById('btnSubmitRestore');
        if (btnSubmit) {
            btnSubmit.disabled = false;
        }
        const btnOtp = document.getElementById('btnRequestOtpRestore');
        btnOtp.disabled = false;
        btnOtp.innerHTML = '<i class="bi bi-send-fill me-1"></i> Minta Kode OTP';
    }

    modalRestoreInstance.show();
}

async function requestOtpRestore() {
    const password = document.getElementById('restorePassword').value.trim();
    const btn = document.getElementById('btnRequestOtpRestore');

    if (!password) {
        showToast('Silakan masukkan Password akun Anda terlebih dahulu.', 'warning');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...';

    try {
        const result = await apiRequest(`/api/backup/request_otp.php`, 'POST', {
            password: password,
            action_type: 'RESTORE_DATABASE'
        });

        if (result && result.success) {
            clearFreeze();
            showToast(result.message || 'Kode OTP verifikasi restore berhasil dikirim ke email.', 'success');

            document.getElementById('otpRestoreSection').style.display = 'block';
            const otpInput = document.getElementById('restoreOtp');
            otpInput.disabled = false;
            otpInput.focus();
        } else {
            showToast(result ? result.message : 'Gagal meminta kode OTP', 'error');
            if (result && result.data && result.data.frozen) {
                setFreezeUntil(result.data.freeze_seconds || 300);
                btn.disabled = true;
                btn.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Dibekukan: ${formatMinutesSeconds(result.data.freeze_seconds || 300)}`;
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Minta Kode OTP';
            }
        }
    } catch (e) {
        showToast('Terjadi kesalahan komunikasi API: ' + e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-1"></i> Minta Kode OTP';
    }
}

async function submitRestoreProcess() {
    const idBackup = document.getElementById('restoreBackupId').value;
    const password = document.getElementById('restorePassword').value.trim();
    const otp = document.getElementById('restoreOtp').value.trim();

    if (!password) {
        showToast('Password konfirmasi wajib diisi.', 'warning');
        return;
    }
    if (!otp) {
        showToast('Kode OTP 6-digit wajib diisi.', 'warning');
        return;
    }

    if (!confirm('PERINGATAN: Apakah Anda benar-benar yakin ingin menimpa data database dengan berkas backup ini? Seluruh data yang ditimpa tidak dapat dibatalkan.')) {
        return;
    }

    const btnSubmit = document.getElementById('btnSubmitRestore');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sedang Memulihkan Database...';

    try {
        const result = await apiRequest(`/api/backup/restore.php`, 'POST', {
            id_backup: idBackup,
            password: password,
            otp_code: otp
        });

        if (result && result.success) {
            clearFreeze();
            modalRestoreInstance.hide();
            showToast(result.message || 'Pemulihan (Restore) database berhasil diselesaikan!', 'success');
            loadBackupData(currentPage);
        } else {
            showToast(result ? result.message : 'Terjadi kesalahan sistem saat memulihkan database', 'error');

            if (result && result.data) {
                if (result.data.frozen) {
                    setFreezeUntil(result.data.freeze_seconds || 300);
                    const otpInput = document.getElementById('restoreOtp');
                    if (otpInput) otpInput.disabled = true;
                    btnSubmit.disabled = true;
                    const btnOtp = document.getElementById('btnRequestOtpRestore');
                    if (btnOtp) {
                        btnOtp.disabled = true;
                        btnOtp.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Dibekukan: ${formatMinutesSeconds(result.data.freeze_seconds || 300)}`;
                    }
                } else if (result.data.remaining_attempts !== undefined) {
                    const otpInput = document.getElementById('restoreOtp');
                    if (otpInput) {
                        otpInput.focus();
                        otpInput.select();
                    }
                }
            }
        }
    } catch (err) {
        showToast('Terjadi kesalahan eksekusi: ' + err.message, 'error');
    } finally {
        if (!document.getElementById('restoreOtp').disabled) {
            btnSubmit.disabled = false;
        }
        btnSubmit.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Eksekusi Pemulihan Database';
    }
}

/**
 * 5. Modal Detail & Kirim Ulang Email
 */
function openDetailModal(item) {
    selectedDetailBackupId = item.id_backup;
    document.getElementById('detailFileName').textContent = item.nama_file;
    document.getElementById('detailFileSize').textContent = item.file_size_formatted;
    document.getElementById('detailTanggalBackup').textContent = item.tanggal_backup_format;
    document.getElementById('detailPelaksanaBackup').textContent = item.pelaksana_backup || '-';
    document.getElementById('detailEmailBackup').textContent = item.email_backup || '-';
    document.getElementById('detailKeterangan').textContent = item.keterangan || '-';
    document.getElementById('detailStatusRestore').innerHTML = item.is_restored 
        ? `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Di-restore pada ${item.tanggal_restore_format} oleh ${item.pelaksana_restore || 'Admin'}</span>` 
        : '<span class="text-muted">Belum pernah di-restore</span>';

    const scopeContainer = document.getElementById('detailScopeList');
    if (item.is_all_scope) {
        scopeContainer.innerHTML = '<span class="badge bg-primary">Seluruh Tabel Database (Full Backup)</span>';
    } else {
        let tags = item.scope_tables.map(t => `<span class="badge bg-secondary me-1 mb-1 font-monospace">${escapeHtml(t)}</span>`).join('');
        scopeContainer.innerHTML = tags || '-';
    }

    modalDetailInstance.show();
}

async function executeResendEmail() {
    const email = document.getElementById('resendTargetEmail').value.trim();
    if (!email) {
        showToast('Silakan masukkan email tujuan.', 'warning');
        return;
    }

    const btn = document.getElementById('btnResendEmail');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...';

    try {
        const result = await apiRequest(`/api/backup/resend_email.php`, 'POST', {
            id_backup: selectedDetailBackupId,
            email: email
        });

        if (result && result.success) {
            showToast(result.message || 'Berkas backup berhasil dikirim ulang ke email.', 'success');
            loadBackupData(currentPage);
        } else {
            showToast(result ? result.message : 'Gagal mengirim email', 'error');
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Kirim Ulang';
    }
}

/**
 * 6. Hapus Record & File Backup
 */
async function deleteBackup(idBackup, fileName) {
    if (!confirm(`Apakah Anda yakin ingin menghapus arsip backup "${fileName}"? Berkas fisik .sql juga akan dihapus dari server.`)) {
        return;
    }

    try {
        const result = await apiRequest(`/api/backup/delete.php`, 'POST', { id_backup: idBackup });

        if (result && result.success) {
            showToast(result.message || 'Arsip backup berhasil dihapus.', 'success');
            loadBackupData(currentPage);
        } else {
            showToast(result ? result.message : 'Gagal menghapus data', 'error');
        }
    } catch (e) {
        showToast('Gagal memproses penghapusan: ' + e.message, 'error');
    }
}

/**
 * Helper: Countdown Timer OTP (Standar 60 Detik)
 */
function startOtpCooldown(countdownBadgeId, timerSpanId, requestButton, seconds) {
    let timeLeft = seconds;
    const badge = document.getElementById(countdownBadgeId);
    const span = document.getElementById(timerSpanId);

    if (badge) {
        badge.className = 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-2 px-3 small w-100 text-center';
        badge.innerHTML = `<i class="bi bi-hourglass-split me-1"></i> Tunggu <span id="${timerSpanId}">${timeLeft}</span> detik untuk kirim ulang`;
        badge.style.display = 'block';
    }

    if (otpCooldownInterval) clearInterval(otpCooldownInterval);

    otpCooldownInterval = setInterval(() => {
        timeLeft--;
        const currentSpan = document.getElementById(timerSpanId);
        if (currentSpan) currentSpan.textContent = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(otpCooldownInterval);
            if (badge) badge.style.display = 'none';
            if (requestButton) {
                requestButton.disabled = false;
                requestButton.className = 'btn btn-sm btn-outline-primary w-100 fw-semibold';
                requestButton.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Kirim Ulang Kode OTP';
            }
        }
    }, 1000);
}

/**
 * Helper: Freeze Cooldown (5 Menit Cooldown ketika Salah 3x)
 */
function startFreezeCooldown(badgeId, timerSpanId, requestBtn, otpInputId, submitBtnId, seconds, showOtpSection = false) {
    setFreezeUntil(seconds);
    let timeLeft = seconds;
    const badge = document.getElementById(badgeId);
    const otpInput = document.getElementById(otpInputId);
    const submitBtn = document.getElementById(submitBtnId);

    if (otpInput) otpInput.disabled = true;
    if (submitBtn) submitBtn.disabled = true;
    
    if (requestBtn) {
        requestBtn.disabled = true;
        requestBtn.className = 'btn btn-sm btn-danger w-100 fw-semibold';
        requestBtn.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Dibekukan: ${formatMinutesSeconds(timeLeft)}`;
    }

    if (showOtpSection) {
        const otpSection = document.getElementById('otpBackupSection');
        if (otpSection) otpSection.style.display = 'flex';
        if (badge) {
            badge.className = 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle py-2 px-3 small w-100 text-center';
            badge.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Dibekukan: <span id="${timerSpanId}">${formatMinutesSeconds(timeLeft)}</span>`;
            badge.style.display = 'block';
        }
    } else {
        const otpSection = document.getElementById('otpBackupSection');
        if (otpSection) otpSection.style.display = 'none';
        if (badge) badge.style.display = 'none';
    }

    if (otpCooldownInterval) clearInterval(otpCooldownInterval);

    otpCooldownInterval = setInterval(() => {
        timeLeft--;
        if (requestBtn && requestBtn.disabled) {
            requestBtn.innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Dibekukan: ${formatMinutesSeconds(timeLeft)}`;
        }
        const currentSpan = document.getElementById(timerSpanId);
        if (currentSpan) currentSpan.textContent = formatMinutesSeconds(timeLeft);

        if (timeLeft <= 0) {
            clearInterval(otpCooldownInterval);
            clearFreeze();
            if (badge) badge.style.display = 'none';
            if (otpInput) {
                otpInput.disabled = false;
                otpInput.value = '';
            }
            if (submitBtn) submitBtn.disabled = false;
            if (requestBtn) {
                requestBtn.disabled = false;
                requestBtn.className = 'btn btn-sm btn-outline-primary w-100 fw-semibold';
                requestBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Minta Kode OTP Baru';
            }
            const helpEl = document.getElementById('otpBackupHelp');
            if (helpEl) {
                helpEl.innerHTML = 'Kode OTP berlaku selama 15 menit.';
            }
            showToast('Masa pembekuan 5 menit telah berakhir. Anda dapat mencoba kembali.', 'info');
        }
    }, 1000);
}

function formatMinutesSeconds(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

/**
 * Helper: Pagination Render
 */
function renderPagination(pagination) {
    const nav = document.getElementById('paginationNav');
    const info = document.getElementById('paginationInfo');
    
    totalPages = pagination.total_pages || 1;
    const totalRows = pagination.total_rows || 0;
    const current = pagination.current_page || 1;

    info.textContent = `Menampilkan ${totalRows > 0 ? (current - 1) * pagination.limit + 1 : 0} - ${Math.min(current * pagination.limit, totalRows)} dari total ${totalRows} arsip`;

    if (totalPages <= 1) {
        nav.innerHTML = '';
        return;
    }

    let html = '';
    html += `<li class="page-item ${current <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" onclick="event.preventDefault(); loadBackupData(${current - 1})">Prev</a></li>`;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= current - 2 && p <= current + 2)) {
            html += `<li class="page-item ${p === current ? 'active' : ''}"><a class="page-link" href="#" onclick="event.preventDefault(); loadBackupData(${p})">${p}</a></li>`;
        } else if (p === current - 3 || p === current + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${current >= totalPages ? 'disabled' : ''}"><a class="page-link" href="#" onclick="event.preventDefault(); loadBackupData(${current + 1})">Next</a></li>`;
    nav.innerHTML = html;
}

function resetFilter() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterScope').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    document.getElementById('filterStartDate').type = 'text';
    document.getElementById('filterEndDate').type = 'text';
    loadBackupData(1);
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
