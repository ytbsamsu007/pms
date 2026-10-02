<?php
/**
 * Komponen Footer Admin - PT Jaya Teknik
 */
$companyProfile = getCompanyProfile();
$companyName = !empty($companyProfile['nama']) ? $companyProfile['nama'] : 'PT Jaya Teknik';
$companyAddress = !empty($companyProfile['alamat']) ? $companyProfile['alamat'] : '';
$companyCity = !empty($companyProfile['kota']) ? $companyProfile['kota'] : '';
$fullLocation = trim($companyAddress . ($companyCity ? ', ' . $companyCity : ''));
?>
    </main> <!-- End .content-body -->

    <footer class="py-3 px-4 bg-white border-top text-muted small text-center text-md-start d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div>
            &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($companyName) ?></strong> &bull; Purchase Management System
        </div>
        <div class="text-muted">
            <i class="bi bi-geo-alt me-1 text-primary"></i><?= htmlspecialchars($fullLocation ?: 'Sistem Internal Galangan & Bengkel Kapal') ?>
        </div>
    </footer>
</div> <!-- End #main-content -->
</div> <!-- End #app-wrapper -->

<!-- Toast Notification Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="appToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                <i class="bi bi-info-circle-fill fs-5" id="toastIcon"></i>
                <span id="toastText">Notifikasi</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Modal Pop-Up Notifikasi Berhasil Disimpan dengan Nomor Transaksi Resmi -->
<div class="modal fade" id="modalSuccessTransaction" tabindex="-1" aria-labelledby="modalSuccessTransactionLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bg-white text-dark py-3 px-4 border-bottom">
                <h5 class="modal-title fs-6 fw-bold mb-0" id="modalSuccessTransactionLabel">
                    Transaksi Berhasil
                </h5>
                <button type="button" class="btn-close" id="btnModalSuccessCloseX" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center bg-white">
                <h6 class="fw-bold text-dark mb-3" id="modalSuccessTxTitle">Dokumen Berhasil Disimpan!</h6>
                
                <div class="p-3 bg-light border border-secondary-subtle rounded-3">
                    <span class="text-muted small d-block mb-1 font-monospace" style="font-size: 0.75rem;">NOMOR TRANSAKSI:</span>
                    <span class="fs-5 fw-bold font-monospace text-primary tracking-wide" id="modalSuccessTxNumber">TRX-XXXX-XXXX</span>
                </div>
                <p class="text-muted small mb-0 mt-2" style="font-size: 0.8rem;" id="modalSuccessTxNote"></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Logout & Peringatan Tab Workspace Masih Terbuka -->
<div class="modal fade" id="modalConfirmLogout" tabindex="-1" aria-labelledby="modalConfirmLogoutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="modalConfirmLogoutLabel">
                    <i class="bi bi-box-arrow-right me-2"></i> Konfirmasi Keluar Sistem
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="logoutWarningTabsArea" class="d-none mb-3">
                    <div class="alert alert-warning border-0 d-flex gap-2 align-items-start py-2 px-3 mb-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="d-block text-dark small">Peringatan: Tab Pekerjaan Masih Terbuka!</strong>
                            <span class="small text-muted" style="font-size: 0.8rem;">Terdapat tab halaman yang belum Anda tutup di sesi login ini:</span>
                        </div>
                    </div>
                    <div class="border rounded-3 p-2 bg-light mb-2 overflow-auto" id="logoutOpenTabsList" style="max-height: 180px;">
                        <!-- Open tabs rendered here dynamically -->
                    </div>
                </div>

                <p class="text-secondary small mb-0" id="logoutConfirmMessageText">
                    Apakah Anda yakin ingin mengakhiri sesi dan keluar dari sistem?
                </p>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm px-3 fw-semibold shadow-sm" onclick="executeLogoutNow()" id="btnConfirmLogoutExecute">
                    <i class="bi bi-box-arrow-right me-1"></i> Tutup Tab &amp; Logout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Upload Lampiran Dokumen Universal (Desain Tab Standar) -->
<div class="modal fade" id="modalUploadDokumen" tabindex="-1" aria-labelledby="modalUploadDokumenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 650px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- MODAL HEADER DENGAN NAV TABS -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalUploadDokumenLabel">
                        Unggah Lampiran Dokumen
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Nav Tabs Sesuai Fungsi -->
                <ul class="nav nav-tabs border-bottom-0" id="uploadDokumenTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="doc-tab-info" data-bs-toggle="tab" data-bs-target="#doc-pane-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Info Transaksi &amp; Dokumen
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="doc-tab-file" data-bs-toggle="tab" data-bs-target="#doc-pane-file" type="button" role="tab">
                            <i class="bi bi-cloud-arrow-up me-1 text-primary"></i> 2. Berkas &amp; Proteksi
                        </button>
                    </li>
                </ul>
            </div>

            <form id="formUploadDokumen" onsubmit="submitUploadDokumen(event)">
                <input type="hidden" id="uploadDokumenCallerKey" value="">

                <!-- MODAL BODY DENGAN TAB CONTENT -->
                <div class="modal-body p-4">
                    <div class="tab-content" id="uploadDokumenTabContent">
                        
                        <!-- TAB 1: INFORMASI TRANSAKSI & DOKUMEN -->
                        <div class="tab-pane fade show active" id="doc-pane-info" role="tabpanel">
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="row g-3">
                                    <div class="col-md-6 col-12">
                                        <label class="form-label small fw-bold text-dark d-block">Tipe Transaksi <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="uploadDokumenTipe" name="tipe_dokumen" required onchange="fetchRecentTransaksiDokumen()">
                                            <option value="REQUEST">REQUEST ORDER</option>
                                            <option value="PURCHASE" selected>PURCHASE ORDER</option>
                                            <option value="RECEIVING">RECEIVING (PENERIMAAN)</option>
                                            <option value="RETUR PO">RETUR PO</option>
                                            <option value="FAKTUR PO">FAKTUR PO</option>
                                            <option value="PAYMENT PO">PAYMENT PO (PEMBAYARAN)</option>
                                            <option value="MUTASI BARANG">MUTASI BARANG</option>
                                            <option value="ADJUSTMENT STOK">ADJUSTMENT STOK</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 col-12 position-relative">
                                        <label class="form-label small fw-bold text-dark d-block">Nomor Transaksi <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control form-control-sm" id="uploadDokumenNomor" name="nomor_dokumen" placeholder="Pilih Nomor Transaksi" autocomplete="off" required onfocus="showRecentDokumenDropdown()" oninput="filterRecentDokumenDropdown(this.value)">
                                            <button class="btn btn-outline-secondary d-flex align-items-center justify-content-center px-2" type="button" onclick="toggleRecentDokumenDropdown(event)" title="Pilih Transaksi Terakhir">
                                                <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
                                            </button>
                                        </div>
                                        <!-- Dropdown Menu Searchable -->
                                        <div id="dropdownRecentTransaksiList" class="position-absolute start-0 end-0 bg-white border rounded-3 shadow-lg p-1 mt-1 d-none" style="z-index: 1060; max-height: 220px; overflow-y: auto; margin-left: 12px; margin-right: 12px;">
                                            <!-- Items dynamically injected -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded-3 border">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark">Nama / Label Dokumen <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="uploadDokumenNama" name="nama_dokumen" placeholder="Contoh: Surat Jalan Vendor, Invoice Asli, Bukti QC, Nota Pembelian" required>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small fw-bold text-dark">Tanggal Dokumen</label>
                                    <input type="date" class="form-control form-control-sm" id="uploadDokumenTanggal" name="tanggal_dokumen" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: SUMBER BERKAS & PROTEKSI PASSWORD -->
                        <div class="tab-pane fade" id="doc-pane-file" role="tabpanel">
                            <!-- Tab Pilihan Sumber File: Upload Fisik vs Google Drive / Cloud URL -->
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="btn-group w-100 btn-group-sm mb-3" role="group">
                                    <input type="radio" class="btn-check" name="source_type" id="sourceUploadFile" value="FILE" checked onchange="toggleAttachmentSourceType()">
                                    <label class="btn btn-outline-primary fw-semibold" for="sourceUploadFile">
                                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Upload File Server (Internal)
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="source_type" id="sourceExternalUrl" value="URL" onchange="toggleAttachmentSourceType()">
                                    <label class="btn btn-outline-primary fw-semibold" for="sourceExternalUrl">
                                        <i class="bi bi-google me-1"></i> Link Google Drive / Cloud
                                    </label>
                                </div>

                                <!-- Panel File Fisik -->
                                <div id="panelSourceFile">
                                    <label class="form-label small fw-bold text-dark">Pilih File dari Komputer / HP</label>
                                    <input type="file" class="form-control form-control-sm" id="uploadDokumenFileInput" name="file" accept=".pdf,.jpg,.jpeg">
                                    <div class="form-text small text-muted" style="font-size: 0.75rem;">
                                        Format didukung: PDF dan JPG (Maks. 5MB).
                                    </div>
                                </div>

                                <!-- Panel Link Eksternal -->
                                <div id="panelSourceUrl" class="d-none">
                                    <label class="form-label small fw-bold text-dark">Tautan Google Drive / Cloud Storage</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white"><i class="bi bi-link-45deg"></i></span>
                                        <input type="url" class="form-control" id="uploadDokumenUrlInput" name="external_url" placeholder="https://drive.google.com/file/d/...">
                                    </div>
                                    
                                </div>
                            </div>

                            <!-- Password Open (Opsional untuk Dokumen Rahasia) -->
                            <div class="p-3 bg-light rounded-3 border">
                                <label class="form-label small fw-bold text-dark">Password Buka File (Opsional)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-shield-lock"></i></span>
                                    <input type="password" class="form-control font-monospace" id="uploadDokumenPassword" name="password_open" autocomplete="new-password" placeholder="Kosongkan jika dokumen bersifat publik/umum...">
                                </div>
                                
                            </div>
                        </div>

                    </div>
                </div>

                <!-- MODAL FOOTER DENGAN TOMBOL SIMPAN -->
                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitUploadDokumen">
                        <i class="bi bi-cloud-upload me-1"></i> Simpan Lampiran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Verifikasi Password Dokumen Terproteksi (Sederhana) -->
<div class="modal fade" id="modalVerifyDokumenPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border-0 shadow rounded-3">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title fw-bold text-dark mb-0">
                     Dokumen Terproteksi
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formVerifyDokumenPassword" onsubmit="submitVerifyDokumenPassword(event)">
                <input type="hidden" id="verifyDokumenId" value="">
                <input type="hidden" id="verifyDokumenCallerKey" value="DEFAULT">
                <div class="modal-body p-3">
                    <label class="form-label small text-muted mb-2">Masukkan Password / PIN untuk membuka:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control border-start-0 border-end-0 font-monospace text-center fw-bold" id="verifyDokumenPasswordInput" placeholder="Password Dokumen" required autocomplete="off" autofocus>
                        
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-0 d-flex justify-content-end gap-2">
                    
                    <button type="submit" class="btn btn-primary btn-sm fw-semibold" id="btnSubmitVerifyPassword">
                        Buka File
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Data (Relasional) -->
<div class="modal fade" id="modalGlobalDelete" tabindex="-1" aria-labelledby="modalGlobalDeleteLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="modalGlobalDeleteLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Penghapusan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 text-dark" id="modalGlobalDeleteMessage">Apakah Anda yakin ingin menghapus data ini?</p>
                <div class="alert alert-warning border-warning-subtle small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i> Data yang dihapus tidak dapat dikembalikan.
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold text-dark mb-1">
                        Ketik <strong class="text-danger font-monospace" id="modalGlobalDeleteExpectedText"></strong> untuk melanjutkan:
                    </label>
                    <input type="text" class="form-control text-center font-monospace" id="modalGlobalDeleteInput" autocomplete="off" placeholder="Ketik di sini...">
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm px-4 fw-semibold" id="btnGlobalDeleteConfirm" disabled>
                    <i class="bi bi-trash-fill me-1"></i> Hapus Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Global Application Script -->
<script>
let globalDeleteCallback = null;
let globalDeleteExpectedCode = '';

function showDeleteConfirm(message, expectedCode, onConfirm) {
    globalDeleteExpectedCode = expectedCode.trim();
    globalDeleteCallback = onConfirm;

    document.getElementById('modalGlobalDeleteMessage').innerHTML = message;
    document.getElementById('modalGlobalDeleteExpectedText').textContent = expectedCode;
    
    const input = document.getElementById('modalGlobalDeleteInput');
    const btn = document.getElementById('btnGlobalDeleteConfirm');
    
    input.value = '';
    btn.disabled = true;

    input.oninput = function() {
        if (this.value.trim() === globalDeleteExpectedCode) {
            btn.disabled = false;
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-danger', 'shadow');
        } else {
            btn.disabled = true;
            btn.classList.remove('shadow');
        }
    };

    btn.onclick = function() {
        if (typeof globalDeleteCallback === 'function') {
            globalDeleteCallback();
        }
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalGlobalDelete'));
        if (modal) modal.hide();
    };

    let modal = bootstrap.Modal.getInstance(document.getElementById('modalGlobalDelete'));
    if (!modal) {
        modal = new bootstrap.Modal(document.getElementById('modalGlobalDelete'), { backdrop: 'static' });
    }
    modal.show();

    setTimeout(() => input.focus(), 500);
}

// Global Utility: Escape HTML Safe
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Mobile Sidebar Toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarWrapper') || document.querySelector('.app-sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar && backdrop) {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
    }
}

// Desktop Collapsible Sidebar (Expand / Collapse)
function toggleSidebarCollapse() {
    const wrapper = document.getElementById('app-wrapper');
    if (!wrapper) return;
    wrapper.classList.toggle('sidebar-collapsed');
    const isCollapsed = wrapper.classList.contains('sidebar-collapsed');
    localStorage.setItem('jt_sidebar_collapsed', isCollapsed ? '1' : '0');
}

// Inisialisasi awal status collapsed sidebar
(function() {
    if (localStorage.getItem('jt_sidebar_collapsed') === '1') {
        document.getElementById('app-wrapper')?.classList.add('sidebar-collapsed');
    }
})();

// Multi-Tab Workspace Manager (ERP Style)
const AppTabs = {
    storageKey: 'jt_workspace_tabs',
    
    getKnownTabs() {
        return [
            { id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false },
            { id: 'request_order', title: 'Daftar Request Order', url: BASE_URL + '/admin/pages/request_order/index.php', icon: 'bi-file-earmark-text-fill', closable: true },
            { id: 'ro_edit', title: 'Detail RO', url: BASE_URL + '/admin/pages/request_order/edit.php', icon: 'bi-file-earmark-text-fill', closable: true },
            { id: 'purchase_order', title: 'Purchase Order', url: BASE_URL + '/admin/pages/purchase_order/index.php', icon: 'bi-cart-check', closable: true },
            { id: 'po_outstanding', title: 'PO Outstanding', url: BASE_URL + '/admin/pages/purchase_order/outstanding.php', icon: 'bi-hourglass-split', closable: true },
            { id: 'receiving', title: 'Penerimaan Barang', url: BASE_URL + '/admin/pages/receiving/index.php', icon: 'bi-box-seam', closable: true },
            { id: 'receiving_create', title: 'Terima Barang Baru', url: BASE_URL + '/admin/pages/receiving/create.php', icon: 'bi-box-arrow-in-down', closable: true },
            { id: 'receiving_edit', title: 'Edit Penerimaan', url: BASE_URL + '/admin/pages/receiving/edit.php', icon: 'bi-pencil-square', closable: true },
            { id: 'retur_po', title: 'Retur PO', url: BASE_URL + '/admin/pages/retur_po/index.php', icon: 'bi-arrow-return-left', closable: true },
            { id: 'retur_create', title: 'Buat Retur PO', url: BASE_URL + '/admin/pages/retur_po/create.php', icon: 'bi-plus-circle-fill', closable: true },
            { id: 'retur_detail', title: 'Detail Retur PO', url: BASE_URL + '/admin/pages/retur_po/detail.php', icon: 'bi-file-earmark-diff-fill', closable: true },
            { id: 'faktur_po', title: 'Faktur PO', url: BASE_URL + '/admin/pages/faktur_po/index.php', icon: 'bi-receipt-cutoff', closable: true },
            { id: 'faktur_create', title: 'Buat Faktur PO', url: BASE_URL + '/admin/pages/faktur_po/create.php', icon: 'bi-file-earmark-plus', closable: true },
            { id: 'tagihan_jatuh_tempo', title: 'Daftar Tagihan', url: BASE_URL + '/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php', icon: 'bi-clock-history', closable: true },
            { id: 'pembayaran_po', title: 'Pembayaran Faktur PO', url: BASE_URL + '/admin/pages/pembayaran_po/index.php', icon: 'bi-cash-coin', closable: true },
            { id: 'pembayaran_create', title: 'Catat Pembayaran PO', url: BASE_URL + '/admin/pages/pembayaran_po/create.php', icon: 'bi-plus-circle-fill', closable: true },
            { id: 'pembayaran_edit', title: 'Edit Pembayaran PO', url: BASE_URL + '/admin/pages/pembayaran_po/edit.php', icon: 'bi-pencil-square', closable: true },
            { id: 'ro_create', title: 'Buat RO Baru', url: BASE_URL + '/admin/pages/request_order/create.php', icon: 'bi-plus-circle', closable: true },
            { id: 'proses_po', title: 'Proses PO', url: BASE_URL + '/admin/pages/request_order/proses_po.php', icon: 'bi-cart-check-fill', closable: true },
            { id: 'site', title: 'Master Site', url: BASE_URL + '/admin/pages/site/index.php', icon: 'bi-geo-alt-fill', closable: true },
            { id: 'jabatan', title: 'Master Jabatan', url: BASE_URL + '/admin/pages/jabatan/index.php', icon: 'bi-briefcase-fill', closable: true },
            { id: 'divisi', title: 'Master Divisi', url: BASE_URL + '/admin/pages/divisi/index.php', icon: 'bi-diagram-3-fill', closable: true },
            { id: 'karyawan', title: 'Master Karyawan', url: BASE_URL + '/admin/pages/user/index.php', icon: 'bi-people-fill', closable: true },
            { id: 'vendor', title: 'Master Vendor', url: BASE_URL + '/admin/pages/vendor/index.php', icon: 'bi-truck', closable: true },
            { id: 'kategori', title: 'Kategori Barang', url: BASE_URL + '/admin/pages/kategori/index.php', icon: 'bi-tags', closable: true },
            { id: 'merk', title: 'Merk Barang', url: BASE_URL + '/admin/pages/merk/index.php', icon: 'bi-bookmark-star', closable: true },
            { id: 'barang', title: 'Katalog Barang', url: BASE_URL + '/admin/pages/barang/index.php', icon: 'bi-box-seam', closable: true },
            { id: 'rekening_bank', title: 'Rekening Bank', url: BASE_URL + '/admin/pages/rekening_bank/index.php', icon: 'bi-credit-card', closable: true },
            { id: 'profile', title: 'Profil Perusahaan', url: BASE_URL + '/admin/pages/profile/index.php', icon: 'bi-buildings', closable: true },
            { id: 'timezone', title: 'Atur Timezone', url: BASE_URL + '/admin/pages/timezone/index.php', icon: 'bi-clock-history', closable: true },
            { id: 'smtp', title: 'Server SMTP', url: BASE_URL + '/admin/pages/smtp/index.php', icon: 'bi-envelope-at-fill', closable: true },
            { id: 'info', title: 'Informasi & Pengumuman', url: BASE_URL + '/admin/pages/info/index.php', icon: 'bi-megaphone-fill', closable: true },
            { id: 'menu', title: 'Manajemen Menu', url: BASE_URL + '/admin/pages/menu/index.php', icon: 'bi-list-check', closable: true },
            { id: 'activity_log', title: 'Log Aktivitas', url: BASE_URL + '/admin/pages/activity_log/index.php', icon: 'bi-clock-history', closable: true },
            { id: 'laporan_pengeluaran_bank', title: 'Pengeluaran Bank', url: BASE_URL + '/admin/pages/laporan/pengeluaran_bank.php', icon: 'bi-bank', closable: true },
            { id: 'laporan_pembelian_vendor', title: 'Pembelian per Vendor', url: BASE_URL + '/admin/pages/laporan/pembelian_vendor.php', icon: 'bi-shop', closable: true },
            { id: 'laporan_realisasi_kuantitas', title: 'Realisasi Kuantitas', url: BASE_URL + '/admin/pages/laporan/realisasi_kuantitas.php', icon: 'bi-bar-chart-fill', closable: true },
            { id: 'laporan_hutang_vendor', title: 'Hutang Vendor', url: BASE_URL + '/admin/pages/laporan/hutang_vendor.php', icon: 'bi-journal-text', closable: true },
            { id: 'laporan_purchase_aging', title: 'Purchase Aging', url: BASE_URL + '/admin/pages/laporan/purchase_aging.php', icon: 'bi-hourglass-split', closable: true }
        ];
    },

    getCurrentTabInfo() {
        const path = window.location.pathname.replace(/\/+$/, '');
        const known = this.getKnownTabs();

        // 1. Ambil icon dinamis dari menu link aktif di sidebar jika ada
        let activeSidebarIcon = null;
        const activeLink = document.querySelector('.sidebar-wrapper a.sidebar-link.active, .sidebar-wrapper a.sidebar-sublink.active');
        if (activeLink) {
            const iconI = activeLink.querySelector('i.bi');
            if (iconI) {
                const biClass = Array.from(iconI.classList).find(c => c.startsWith('bi-') && c !== 'bi');
                if (biClass) activeSidebarIcon = biClass;
            }
        }

        if (path.includes('/request_order/edit.php')) {
            const urlParams = new URLSearchParams(window.location.search);
            const roId = urlParams.get('id');
            let roTitle = (typeof CURRENT_RO_NOMOR !== 'undefined' && CURRENT_RO_NOMOR) ? CURRENT_RO_NOMOR : '';
            if (!roTitle) {
                const docTitle = document.title.split('-')[0].trim();
                if (docTitle && !docTitle.toLowerCase().includes('edit request') && !docTitle.toLowerCase().includes('request order:')) {
                    roTitle = docTitle;
                }
            }
            if (!roTitle) {
                roTitle = roId ? 'RO #' + roId : 'Detail RO';
            }
            return {
                id: 'ro_edit' + (roId ? '_' + roId : ''),
                title: roTitle,
                url: window.location.href,
                icon: activeSidebarIcon || 'bi-file-earmark-text-fill',
                closable: true
            };
        }
        
        for (const t of known) {
            const tPath = new URL(t.url, window.location.origin).pathname.replace(/\/+$/, '');
            if (
                path === tPath || 
                (t.id === 'user_profile' && path.includes('/user/profile.php')) ||
                (t.id === 'karyawan' && path.includes('/user/index.php')) || 
                (t.id === 'profile' && path.includes('/profile/index.php')) ||
                (t.id === 'barang' && path.includes('/barang/')) || 
                (t.id === 'smtp' && path.includes('/smtp/')) || 
                (t.id === 'info' && path.includes('/info/')) || 
                (t.id === 'proses_po' && path.includes('/request_order/proses_po.php')) || 
                (t.id === 'receiving_create' && path.includes('/receiving/create.php')) ||
                (t.id === 'receiving_edit' && path.includes('/receiving/edit.php')) ||
                (t.id === 'receiving' && path.includes('/receiving/')) ||
                (t.id === 'retur_create' && path.includes('/retur_po/create.php')) ||
                (t.id === 'retur_detail' && path.includes('/retur_po/detail.php')) ||
                (t.id === 'retur_po' && path.includes('/retur_po/')) ||
                (t.id === 'faktur_create' && path.includes('/faktur_po/create.php')) ||
                (t.id === 'faktur_po' && path.includes('/faktur_po/')) ||
                (t.id === 'tagihan_jatuh_tempo' && (path.includes('/pembayaran_po/tagihan_jatuh_tempo.php') || path.includes('/pembayaran_po/jatuh_tempo.php'))) ||
                (t.id === 'pembayaran_create' && path.includes('/pembayaran_po/create.php')) ||
                (t.id === 'pembayaran_edit' && path.includes('/pembayaran_po/edit.php')) ||
                (t.id === 'pembayaran_po' && (path.includes('/pembayaran_po/index.php') || (path.includes('/pembayaran_po/') && !path.includes('tagihan_jatuh_tempo') && !path.includes('jatuh_tempo')))) ||
                (t.id === 'po_outstanding' && path.includes('/purchase_order/outstanding.php')) ||
                (t.id === 'purchase_order' && (path.includes('/purchase_order/index.php') || path.includes('/purchase_order/edit.php'))) || 
                (t.id === 'request_order' && path.includes('/request_order/index.php')) || 
                (t.id === 'ro_create' && path.includes('/request_order/create.php')) ||
                (t.id === 'laporan_pengeluaran_bank' && path.includes('/laporan/pengeluaran_bank.php')) ||
                (t.id === 'laporan_pembelian_vendor' && path.includes('/laporan/pembelian_vendor.php')) ||
                (t.id === 'laporan_realisasi_kuantitas' && path.includes('/laporan/realisasi_kuantitas.php')) ||
                (t.id === 'laporan_hutang_vendor' && path.includes('/laporan/hutang_vendor.php')) ||
                (t.id === 'laporan_purchase_aging' && path.includes('/laporan/purchase_aging.php')) ||
                (t.id === 'activity_log' && path.includes('/activity_log/')) ||
                (t.id === 'rekening_bank' && path.includes('/rekening_bank/')) ||
                (t.id === 'timezone' && path.includes('/timezone/'))
            ) {
                return {
                    ...t,
                    icon: activeSidebarIcon || t.icon,
                    url: window.location.href
                };
            }
        }
        
        return {
            id: 'tab_' + Math.abs(path.split('').reduce((a,b)=>{a=((a<<5)-a)+b.charCodeAt(0);return a&a},0)),
            title: document.title.split('-')[0].trim() || 'Halaman',
            url: window.location.href,
            icon: activeSidebarIcon || 'bi-window-sidebar',
            closable: true
        };
    },

    getOpenedTabs() {
        try {
            const raw = sessionStorage.getItem(this.storageKey);
            let tabs = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(tabs) || tabs.length === 0) {
                tabs = [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
            } else {
                // Refresh icon tab jika ada pembaruan di getKnownTabs
                const known = this.getKnownTabs();
                tabs = tabs.map(tab => {
                    const match = known.find(k => k.id === tab.id || k.url === tab.url);
                    if (match && match.icon) {
                        tab.icon = match.icon;
                    }
                    return tab;
                });
            }
            return tabs;
        } catch (e) {
            return [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
        }
    },

    saveOpenedTabs(tabs) {
        sessionStorage.setItem(this.storageKey, JSON.stringify(tabs));
    },

    init() {
        const container = document.getElementById('appTabsContainer');
        if (!container) return;

        const currentTab = this.getCurrentTabInfo();
        let openedTabs = this.getOpenedTabs();

        // Dashboard selalu ada di awal
        if (!openedTabs.some(t => t.id === 'dashboard')) {
            openedTabs.unshift({ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false });
        }

        // Tambahkan tab aktif saat ini jika belum terdaftar
        const existingIdx = openedTabs.findIndex(t => t.id === currentTab.id);
        if (existingIdx === -1) {
            openedTabs.push(currentTab);
        } else {
            // Update icon & title tab aktif saat ini
            openedTabs[existingIdx].icon = currentTab.icon;
            openedTabs[existingIdx].title = currentTab.title;
            openedTabs[existingIdx].url = currentTab.url;
        }
        this.saveOpenedTabs(openedTabs);

        // Render Bar Tabs
        let html = '';
        openedTabs.forEach(tab => {
            const isActive = tab.id === currentTab.id;
            html += `
                <a href="${tab.url}" class="app-tab-item ${isActive ? 'active' : ''}" data-tab-id="${tab.id}" title="${tab.title}">
                    <i class="bi ${tab.icon || 'bi-file-earmark'} tab-icon"></i>
                    <span class="app-tab-title">${tab.title}</span>
                    ${tab.closable ? `<span class="app-tab-close" onclick="AppTabs.closeTab('${tab.id}', event)" title="Tutup Tab"><i class="bi bi-x"></i></span>` : ''}
                </a>
            `;
        });
        container.innerHTML = html;

        // Auto Scroll ke tab aktif
        const activeEl = container.querySelector('.app-tab-item.active');
        if (activeEl) {
            activeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
        }
    },

    closeTab(tabId, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        let openedTabs = this.getOpenedTabs();
        const currentTab = this.getCurrentTabInfo();
        const targetIdx = openedTabs.findIndex(t => t.id === tabId);
        
        if (targetIdx === -1) return;

        const isClosingActive = (tabId === currentTab.id);
        openedTabs.splice(targetIdx, 1);
        this.saveOpenedTabs(openedTabs);

        if (isClosingActive) {
            // Pindah ke tab tetangga atau Dashboard
            const nextTab = openedTabs[targetIdx] || openedTabs[targetIdx - 1] || openedTabs[0];
            if (nextTab) {
                window.location.href = nextTab.url;
            } else {
                window.location.href = BASE_URL + '/admin/dashboard.php';
            }
        } else {
            this.init();
        }
    },

    updateCurrentTabTitle(newTitle) {
        if (!newTitle) return;
        const currentTab = this.getCurrentTabInfo();
        let openedTabs = this.getOpenedTabs();
        const existingIdx = openedTabs.findIndex(t => t.id === currentTab.id);
        if (existingIdx !== -1) {
            openedTabs[existingIdx].title = newTitle;
            this.saveOpenedTabs(openedTabs);
            const el = document.querySelector(`.app-tab-item[data-tab-id="${currentTab.id}"] .app-tab-title`);
            if (el) el.textContent = newTitle;
            const itemEl = document.querySelector(`.app-tab-item[data-tab-id="${currentTab.id}"]`);
            if (itemEl) itemEl.setAttribute('title', newTitle);
        }
    },

    closeOtherTabs() {
        const currentTab = this.getCurrentTabInfo();
        let openedTabs = [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
        if (currentTab.id !== 'dashboard') {
            openedTabs.push(currentTab);
        }
        this.saveOpenedTabs(openedTabs);
        this.init();
    },

    closeAllTabs() {
        const openedTabs = [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
        this.saveOpenedTabs(openedTabs);
        window.location.href = BASE_URL + '/admin/dashboard.php';
    }
};

// Global Toast Notification Helper
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('appToast');
    const toastText = document.getElementById('toastText');
    const toastIcon = document.getElementById('toastIcon');
    
    if (!toastEl || !toastText) return;
    
    toastText.textContent = message;
    
    toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary');
    toastIcon.className = 'fs-5 bi ';
    
    if (type === 'success') {
        toastEl.classList.add('bg-success');
        toastIcon.classList.add('bi-check-circle-fill');
    } else if (type === 'error' || type === 'danger') {
        toastEl.classList.add('bg-danger');
        toastIcon.classList.add('bi-exclamation-triangle-fill');
    } else if (type === 'warning') {
        toastEl.classList.add('bg-warning', 'text-dark');
        toastIcon.classList.add('bi-exclamation-circle-fill');
    } else {
        toastEl.classList.add('bg-primary');
        toastIcon.classList.add('bi-info-circle-fill');
    }
    
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
}

// Global Pop-up Modal Berhasil Disimpan dengan Nomor Transaksi
function showSuccessTransactionModal(options = {}) {
    const title = options.title || 'Transaksi Berhasil Disimpan!';
    const subtitle = options.subtitle || 'Berhasil disimpan resmi ke dalam database sistem dengan nomor:';
    const nomor = options.nomor || '-';
    const redirectUrl = options.redirectUrl || '';
    const delay = options.delay !== undefined ? options.delay : 0; // Default 0: Tutup manual

    const modalEl = document.getElementById('modalSuccessTransaction');
    if (!modalEl) {
        showToast(`Berhasil Disimpan Dengan Nomor : ${nomor}`, 'success');
        if (redirectUrl) {
            setTimeout(() => { window.location.href = redirectUrl; }, 1200);
        }
        return;
    }

    const titleEl = document.getElementById('modalSuccessTxTitle');
    const numEl = document.getElementById('modalSuccessTxNumber');
    const noteEl = document.getElementById('modalSuccessTxNote');
    const closeXBtn = document.getElementById('btnModalSuccessCloseX');

    if (titleEl) titleEl.innerText = title;
    if (numEl) numEl.innerText = nomor;
    if (noteEl) {
        noteEl.innerText = (redirectUrl && delay > 0) ? 'Halaman akan dialihkan dalam beberapa detik...' : '';
    }

    let modalInstance = bootstrap.Modal.getInstance(modalEl);
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
    }
    modalInstance.show();

    let redirected = false;
    const doRedirect = () => {
        if (!redirected && redirectUrl) {
            redirected = true;
            window.location.href = redirectUrl;
        }
    };

    if (closeXBtn) {
        closeXBtn.onclick = () => {
            modalInstance.hide();
            doRedirect();
        };
    }

    if (redirectUrl && delay > 0) {
        setTimeout(doRedirect, delay);
    }
}

// Global Logout Handler dengan Peringatan Tab Masih Terbuka
let logoutModalInstance = null;

function handleLogout() {
    const openedTabs = AppTabs.getOpenedTabs ? AppTabs.getOpenedTabs() : [];
    // Filter tab workspace aktif selain Dashboard
    const activeWorkspaceTabs = openedTabs.filter(t => t.id !== 'dashboard');

    const modalEl = document.getElementById('modalConfirmLogout');
    if (!modalEl) {
        if (!confirm('Apakah Anda yakin ingin keluar dari sistem?')) return;
        executeLogoutNow();
        return;
    }

    if (!logoutModalInstance) {
        logoutModalInstance = new bootstrap.Modal(modalEl);
    }

    const warningArea = document.getElementById('logoutWarningTabsArea');
    const tabsList = document.getElementById('logoutOpenTabsList');
    const msgText = document.getElementById('logoutConfirmMessageText');

    if (activeWorkspaceTabs.length > 0) {
        warningArea.classList.remove('d-none');
        let html = '<div class="d-flex flex-column gap-1">';
        activeWorkspaceTabs.forEach(t => {
            const icon = t.icon || 'bi-window-sidebar';
            html += `
                <div class="d-flex align-items-center justify-content-between bg-white border rounded px-2 py-1 small shadow-xs">
                    <span class="text-dark fw-semibold"><i class="bi ${icon} text-primary me-2"></i>${t.title || 'Tab'}</span>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace" style="font-size: 0.7rem;">Tab Terbuka</span>
                </div>
            `;
        });
        html += '</div>';
        tabsList.innerHTML = html;
        msgText.innerHTML = `Keluar sekarang akan <strong>menutup otomatis ${activeWorkspaceTabs.length} tab pekerjaan di atas</strong> dan membersihkan sesi Anda.`;
    } else {
        warningArea.classList.add('d-none');
        msgText.textContent = 'Apakah Anda yakin ingin mengakhiri sesi dan keluar dari sistem?';
    }

    logoutModalInstance.show();
}

async function executeLogoutNow() {
    const btn = document.getElementById('btnConfirmLogoutExecute');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengeluarkan...';
    }

    // Bersihkan seluruh tab workspace di sessionStorage & localStorage agar akun berikutnya fresh
    sessionStorage.removeItem('jt_workspace_tabs');
    localStorage.removeItem('jt_workspace_tabs');
    sessionStorage.removeItem('jt_sidebar_scroll_top');

    try {
        const res = await fetch(BASE_URL + '/api/auth/logout.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + API_TOKEN,
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        window.location.href = (data && data.data && data.data.redirect_url) ? data.data.redirect_url : (BASE_URL + '/admin/login.php');
    } catch (err) {
        window.location.href = BASE_URL + '/admin/login.php';
    }
}

// Centralized Fetch API Wrapper
async function apiRequest(endpoint, options = {}, payload = null) {
    if (typeof options === 'string') {
        const method = options.toUpperCase();
        if (payload !== null && payload !== undefined) {
            options = {
                method: method,
                body: (payload instanceof FormData) ? payload : (typeof payload === 'string' ? payload : JSON.stringify(payload))
            };
        } else {
            options = { method: method };
        }
    }
    const defaultHeaders = {
        'Authorization': 'Bearer ' + API_TOKEN,
        'Accept': 'application/json'
    };
    
    // Jangan set Content-Type jika body adalah FormData agar browser otomatis menyertakan boundary multipart
    if (!(options.body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
    }
    
    options.headers = Object.assign({}, defaultHeaders, options.headers || {});
    if (options.body instanceof FormData && options.headers['Content-Type']) {
        delete options.headers['Content-Type'];
    }
    options.credentials = options.credentials || 'include';
    
    try {
        let fetchUrl = endpoint;
        if (!fetchUrl.startsWith('http://') && !fetchUrl.startsWith('https://')) {
            if (fetchUrl.startsWith(BASE_URL)) {
                // already starts with BASE_URL
            } else {
                fetchUrl = BASE_URL + (fetchUrl.startsWith('/') ? '' : '/') + fetchUrl;
            }
        }
        const response = await fetch(fetchUrl, options);
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('API Response Non-JSON:', text);
            showToast('Respon server tidak valid: ' + (text.replace(/<[^>]*>/g, '').trim().substring(0, 80) || 'Format salah'), 'error');
            return { success: false, message: 'Respon server tidak valid.', raw: text };
        }
        
        if (response.status === 401) {
            showToast('Sesi Anda telah berakhir. Mengalihkan ke login...', 'warning');
            setTimeout(() => { window.location.href = BASE_URL + '/admin/login.php'; }, 1500);
            return null;
        }
        
        return data;
    } catch (error) {
        console.error('API Request Error:', error);
        showToast('Terjadi kesalahan koneksi ke server.', 'error');
        return { success: false, message: 'Kesalahan koneksi ke server.' };
    }
}

// =============================================================
// UNIVERSAL DOKUMEN / ATTACHMENT HANDLERS (SEMUA ROLE & MODUL)
// =============================================================
let currentLoadedDokumenParams = {};

function toggleAttachmentSourceType() {
    const isFile = document.getElementById('sourceUploadFile').checked;
    const panelFile = document.getElementById('panelSourceFile');
    const panelUrl = document.getElementById('panelSourceUrl');
    const fileInput = document.getElementById('uploadDokumenFileInput');
    const urlInput = document.getElementById('uploadDokumenUrlInput');

    if (isFile) {
        panelFile.classList.remove('d-none');
        panelUrl.classList.add('d-none');
        fileInput.required = true;
        urlInput.required = false;
        urlInput.value = '';
    } else {
        panelFile.classList.add('d-none');
        panelUrl.classList.remove('d-none');
        fileInput.required = false;
        urlInput.required = true;
        fileInput.value = '';
    }
}

function openModalUploadDokumen(tipeDokumen, callerKey = 'DEFAULT') {
    let nomorDokumen = '';
    
    // Auto-detect nomor dokumen dari halaman/modal aktif jika ada
    const possibleIds = [
        'nomorPo', 'inputNomorPo', 'detailNomorPo',
        'nomorRo', 'inputNomorRo', 'detailNomorRo',
        'nomorRcv', 'inputNomorRcv', 'detailNomorRcv',
        'nomorFaktur', 'inputNomorFaktur', 'detailNomorFaktur',
        'nomorPembayaran', 'inputNomorPembayaran', 'detailNomorPembayaran',
        'nomorMutasi', 'inputNomorMutasi', 'detailNomorMutasi',
        'nomorAdjustment', 'inputNomorAdjustment', 'headerNomorAdj',
        'nomorRetur', 'inputNomorRetur', 'detailNomorRetur'
    ];

    for (const id of possibleIds) {
        const el = document.getElementById(id);
        if (el) {
            const val = (el.value !== undefined ? el.value : el.innerText || '').trim();
            if (val && val !== '-' && val !== '[Otomatis]' && val !== 'PO-XXXX-XXXX' && val !== 'RO-XXXX-XXXX' && val !== 'RCV-XXXX-XXXX') {
                nomorDokumen = val;
                break;
            }
        }
    }

    // Reset Form
    document.getElementById('formUploadDokumen').reset();
    document.getElementById('uploadDokumenTipe').value = tipeDokumen || 'PURCHASE';
    document.getElementById('uploadDokumenNomor').value = nomorDokumen || '';
    document.getElementById('uploadDokumenCallerKey').value = callerKey;

    // Reset to Tab 1
    const firstTabBtn = document.getElementById('doc-tab-info');
    if (firstTabBtn) {
        bootstrap.Tab.getInstance(firstTabBtn)?.show() || new bootstrap.Tab(firstTabBtn).show();
    }

    // Load 10 transaksi terakhir
    fetchRecentTransaksiDokumen(nomorDokumen);

    const modalEl = document.getElementById('modalUploadDokumen');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

let cachedRecentTransaksiList = [];

async function fetchRecentTransaksiDokumen(selectedNomor = '') {
    const tipeSelect = document.getElementById('uploadDokumenTipe');
    const inputNomor = document.getElementById('uploadDokumenNomor');
    if (!tipeSelect) return;

    const tipe = tipeSelect.value || 'PURCHASE';
    cachedRecentTransaksiList = [];

    try {
        const res = await fetch(`${BASE_URL}/api/dokumen/index.php?action=get_recent_transactions&tipe=${encodeURIComponent(tipe)}`);
        const json = await res.json();
        
        if (json.success && json.data && json.data.items) {
            cachedRecentTransaksiList = json.data.items.map(item => item.nomor).filter(Boolean);
        }

        renderRecentDokumenDropdown(cachedRecentTransaksiList);

        if (selectedNomor) {
            inputNomor.value = selectedNomor;
        }
    } catch (e) {
        console.error('Error fetching recent transactions:', e);
    }
}

function renderRecentDokumenDropdown(list) {
    const dropdown = document.getElementById('dropdownRecentTransaksiList');
    if (!dropdown) return;

    if (!list || list.length === 0) {
        dropdown.innerHTML = '<div class="text-muted small p-2 text-center fst-italic">Tidak ada riwayat transaksi ditemukan</div>';
        return;
    }

    let html = '';
    list.forEach(nomor => {
        html += `<a href="javascript:void(0)" class="dropdown-item py-1 px-2 rounded-2 small text-dark d-flex justify-content-between align-items-center" onclick="selectRecentDokumenNomor('${escapeHtml(nomor)}')">
                    <span>${escapeHtml(nomor)}</span>
                    <i class="bi bi-arrow-return-left text-muted" style="font-size: 0.75rem;"></i>
                 </a>`;
    });
    dropdown.innerHTML = html;
}

function showRecentDokumenDropdown() {
    const dropdown = document.getElementById('dropdownRecentTransaksiList');
    const inputVal = (document.getElementById('uploadDokumenNomor')?.value || '').trim();
    if (!dropdown) return;
    
    filterRecentDokumenDropdown(inputVal);
    dropdown.classList.remove('d-none');
}

function hideRecentDokumenDropdown() {
    const dropdown = document.getElementById('dropdownRecentTransaksiList');
    if (dropdown) {
        dropdown.classList.add('d-none');
    }
}

function toggleRecentDokumenDropdown(e) {
    if (e) e.stopPropagation();
    const dropdown = document.getElementById('dropdownRecentTransaksiList');
    const input = document.getElementById('uploadDokumenNomor');
    if (!dropdown) return;

    if (dropdown.classList.contains('d-none')) {
        showRecentDokumenDropdown();
        if (input) input.focus();
    } else {
        hideRecentDokumenDropdown();
    }
}

function filterRecentDokumenDropdown(keyword) {
    const kw = (keyword || '').toLowerCase().trim();
    if (!kw) {
        renderRecentDokumenDropdown(cachedRecentTransaksiList);
    } else {
        const filtered = cachedRecentTransaksiList.filter(nomor => nomor.toLowerCase().includes(kw));
        renderRecentDokumenDropdown(filtered);
    }
    const dropdown = document.getElementById('dropdownRecentTransaksiList');
    if (dropdown && dropdown.classList.contains('d-none')) {
        dropdown.classList.remove('d-none');
    }
}

function selectRecentDokumenNomor(nomor) {
    const input = document.getElementById('uploadDokumenNomor');
    if (input) {
        input.value = nomor;
    }
    hideRecentDokumenDropdown();
}

// Tutup dropdown jika klik di luar area
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdownRecentTransaksiList');
    const input = document.getElementById('uploadDokumenNomor');
    const btn = e.target.closest('button[onclick*="toggleRecentDokumenDropdown"]');
    if (dropdown && !dropdown.contains(e.target) && e.target !== input && !btn) {
        hideRecentDokumenDropdown();
    }
});

async function submitUploadDokumen(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitUploadDokumen');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah...';

    const form = document.getElementById('formUploadDokumen');
    const formData = new FormData(form);
    const callerKey = document.getElementById('uploadDokumenCallerKey').value || 'DEFAULT';
    const nomorDok = document.getElementById('uploadDokumenNomor').value;
    const tipeDok = document.getElementById('uploadDokumenTipe').value;

    try {
        const res = await fetch(`${BASE_URL}/api/dokumen/index.php?action=upload`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            showToast(json.message || 'Lampiran berhasil diunggah!', 'success');
            const modalEl = document.getElementById('modalUploadDokumen');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            // Refresh table lampiran modal / tab
            loadDokumenAttachments(nomorDok, tipeDok, callerKey);

            // Refresh halaman index dokumen jika sedang di halaman Arsip Dokumen
            if (typeof loadTableDokumen === 'function') {
                loadTableDokumen(typeof currentPage !== 'undefined' ? currentPage : 1);
            }
        } else {
            showToast(json.message || 'Gagal mengunggah lampiran.', 'danger');
        }
    } catch (err) {
        console.error('Error upload dokumen:', err);
        showToast('Terjadi kesalahan saat mengunggah dokumen.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function loadDokumenAttachments(nomorDokumen, tipeDokumen = '', callerKey = 'DEFAULT') {
    if (!nomorDokumen) return;

    currentLoadedDokumenParams[callerKey] = { nomor: nomorDokumen, tipe: tipeDokumen };

    const tbody = document.getElementById(`tbodyDokumenList_${callerKey}`);
    const badge = document.getElementById(`badgeTotalLampiran_${callerKey}`);
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted small"><span class="spinner-border spinner-border-sm me-1"></span> Memuat lampiran...</td></tr>`;

    try {
        let url = `${BASE_URL}/api/dokumen/index.php?action=list&nomor_dokumen=${encodeURIComponent(nomorDokumen)}`;
        if (tipeDokumen) url += `&tipe_dokumen=${encodeURIComponent(tipeDokumen)}`;

        const res = await fetch(url);
        const json = await res.json();

        if (json.success && json.data && json.data.items) {
            const items = json.data.items;
            if (badge) badge.innerText = items.length;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted small"><i class="bi bi-folder2-open d-block fs-4 mb-1 opacity-50"></i>Belum ada berkas lampiran untuk dokumen ini.</td></tr>`;
                return;
            }

            let html = '';
            items.forEach((item, idx) => {
                let badgeExt = '';
                let isExt = !item.file && item.external_url;
                if (isExt) {
                    badgeExt = `<span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-google me-1"></i>Google Drive</span>`;
                } else {
                    const ext = (item.file_ext || '').toUpperCase();
                    let color = 'secondary';
                    if (ext === 'PDF') color = 'danger';
                    else if (['JPG','JPEG','PNG','WEBP'].includes(ext)) color = 'primary';
                    else if (['XLS','XLSX'].includes(ext)) color = 'success';
                    else if (['DOC','DOCX'].includes(ext)) color = 'info';
                    else if (['ZIP','RAR'].includes(ext)) color = 'dark';
                    badgeExt = `<span class="badge bg-${color}-subtle text-${color} border font-monospace">${ext || 'FILE'}</span>`;
                }

                const lockBadge = item.is_protected ? `<span class="badge bg-danger-subtle text-danger ms-1" title="Dilindungi Password"><i class="bi bi-lock-fill"></i></span>` : '';

                html += `
                    <tr>
                        <td class="text-center">${idx + 1}</td>
                        <td>
                            <div class="fw-bold text-dark">${escapeHtml(item.nama_dokumen || '-')} ${lockBadge}</div>
                            <div class="small text-muted font-monospace" style="font-size: 0.72rem;">${escapeHtml(item.nomor_dokumen || '')}</div>
                        </td>
                        <td>${badgeExt}</td>
                        <td class="small font-monospace">${item.tanggal_dokumen || '-'}</td>
                        <td>
                            <div class="small fw-semibold text-dark">${escapeHtml(item.nama_karyawan || 'Internal / Sistem')}</div>
                            <div class="text-muted" style="font-size: 0.7rem;">${escapeHtml(item.nama_jabatan || '')}</div>
                        </td>
                        <td class="text-center font-monospace small">${item.unduh || 0}x</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="handleOpenDokumen(${item.id_dokumen}, ${item.is_protected ? 'true' : 'false'}, '${item.file_url ? item.file_url.replace(/'/g, "\\'") : ''}', '${item.external_url ? item.external_url.replace(/'/g, "\\'") : ''}', '${callerKey}')" title="Buka / Download">
                                    <i class="bi ${isExt ? 'bi-box-arrow-up-right' : 'bi-download'}"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteDokumenAttachment(${item.id_dokumen}, '${escapeHtml(item.nama_dokumen || '')}', '${callerKey}')" title="Hapus Lampiran">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-danger small">Gagal memuat lampiran.</td></tr>`;
        }
    } catch (e) {
        console.error('Error load dokumen:', e);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-danger small">Terjadi kesalahan saat memuat berkas.</td></tr>`;
    }
}

function handleOpenDokumen(idDokumen, isProtected, fileUrl, externalUrl, callerKey = 'DEFAULT') {
    if (isProtected) {
        document.getElementById('formVerifyDokumenPassword').reset();
        document.getElementById('verifyDokumenId').value = idDokumen;
        document.getElementById('verifyDokumenCallerKey').value = callerKey;
        
        const modalEl = document.getElementById('modalVerifyDokumenPassword');
        let modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
        setTimeout(() => {
            document.getElementById('verifyDokumenPasswordInput')?.focus();
        }, 300);
    } else {
        // Langsung buka atau unduh
        const targetUrl = fileUrl || externalUrl;
        if (targetUrl) {
            window.open(targetUrl, '_blank');
            // Trigger background increment
            fetch(`${BASE_URL}/api/dokumen/index.php?action=verify_download`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_dokumen: idDokumen, password: '' })
            }).then(() => {
                const params = currentLoadedDokumenParams[callerKey];
                if (params) loadDokumenAttachments(params.nomor, params.tipe, callerKey);
                if (typeof loadTableDokumen === 'function') {
                    loadTableDokumen(typeof currentPage !== 'undefined' ? currentPage : 1);
                }
            });
        }
    }
}

async function submitVerifyDokumenPassword(e) {
    e.preventDefault();
    const idDokumen = document.getElementById('verifyDokumenId').value;
    const callerKey = document.getElementById('verifyDokumenCallerKey').value || 'DEFAULT';
    const password = document.getElementById('verifyDokumenPasswordInput').value;
    const btn = document.getElementById('btnSubmitVerifyPassword');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memverifikasi...';

    try {
        const res = await fetch(`${BASE_URL}/api/dokumen/index.php?action=verify_download`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_dokumen: parseInt(idDokumen), password: password })
        });
        const json = await res.json();

        if (json.success && json.data && json.data.url) {
            const modalEl = document.getElementById('modalVerifyDokumenPassword');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            window.open(json.data.url, '_blank');

            // Refresh counter
            const params = currentLoadedDokumenParams[callerKey];
            if (params) loadDokumenAttachments(params.nomor, params.tipe, callerKey);
            if (typeof loadTableDokumen === 'function') {
                loadTableDokumen(typeof currentPage !== 'undefined' ? currentPage : 1);
            }
        } else {
            showToast(json.message || 'Password / PIN yang dimasukkan salah.', 'danger');
            document.getElementById('verifyDokumenPasswordInput')?.focus();
        }
    } catch (err) {
        showToast('Gagal memverifikasi password dokumen.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

async function deleteDokumenAttachment(idDokumen, namaDokumen, callerKey) {
    if (!confirm(`Apakah Anda yakin ingin menghapus berkas lampiran "${namaDokumen}"?`)) {
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}/api/dokumen/index.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_dokumen: parseInt(idDokumen) })
        });
        const json = await res.json();

        if (json.success) {
            showToast(json.message || 'Lampiran berhasil dihapus.', 'success');
            const params = currentLoadedDokumenParams[callerKey];
            if (params) {
                loadDokumenAttachments(params.nomor, params.tipe, callerKey);
            }
        } else {
            showToast(json.message || 'Gagal menghapus lampiran.', 'danger');
        }
    } catch (e) {
        console.error('Error delete dokumen:', e);
        showToast('Terjadi kesalahan saat menghapus lampiran.', 'danger');
    }
}

// Inisialisasi Event Listener
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi Workspace Multi-Tab
    AppTabs.init();

    // Pulihkan posisi scroll sidebar
    const sidebarNav = document.querySelector('.sidebar-nav');
    if (sidebarNav) {
        const savedScrollPos = sessionStorage.getItem('jt_sidebar_scroll_top');
        if (savedScrollPos !== null) {
            sidebarNav.scrollTop = parseInt(savedScrollPos, 10);
        } else {
            const activeItem = sidebarNav.querySelector('.sidebar-link.active, .sidebar-sublink.active');
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'nearest', behavior: 'auto' });
            }
        }

        let scrollTimer;
        sidebarNav.addEventListener('scroll', () => {
            sidebarNav.classList.add('is-scrolling');
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                sidebarNav.classList.remove('is-scrolling');
            }, 800);
            sessionStorage.setItem('jt_sidebar_scroll_top', sidebarNav.scrollTop);
        }, { passive: true });

        const sidebarLinks = sidebarNav.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                sessionStorage.setItem('jt_sidebar_scroll_top', sidebarNav.scrollTop);
            });
        });
    }
});
</script>
</body>
</html>
