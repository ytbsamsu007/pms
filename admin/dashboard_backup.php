<?php
/**
 * Dashboard Utama - PT Jaya Teknis
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

$user = requireAuth();
$pageTitle = 'Dashboard';
$pageHeading = 'Dashboard Purchasing';

$role = $user['role'];
$nama = $user['nama'];

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/sidebar.php';
require_once __DIR__ . '/components/navbar.php';
?>

<!-- Styling Hover Efek Status -->
<style>
.hover-opacity {
    transition: transform 0.15s ease, filter 0.15s ease;
    cursor: pointer;
}
.hover-opacity:hover {
    transform: translateY(-1px);
    filter: brightness(0.92);
}
</style>

<!-- Banner Welcome (Bersih Tanpa Label & Tombol) -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-primary text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%) !important;">
            <div class="card-body p-4">
                <h2 class="fs-4 fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($nama) ?>!</h2>
                <p class="mb-0 text-white-50 small">Purchase Management System</p>
            </div>
        </div>
    </div>
</div>

<?php if ($role === ROLE_FINANCE): ?>
<!-- =============================================================
     DASHBOARD KHUSUS ROLE FINANCE (KEUANGAN & ARUS KAS)
     ============================================================= -->

<!-- 1. 4 KARTU METRIK UTAMA (ALIGNED & CONSISTENT) -->
<div class="row g-3 mb-4">
    <!-- 1. Total Hutang Usaha (Outstanding AP) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon primary flex-shrink-0">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-truncate">Hutang Usaha</div>
                <div class="stat-value text-primary fs-5 fw-bold font-monospace text-truncate" id="statFinanceFakturNominal">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-muted text-truncate" id="statFinanceFakturCount" style="font-size: 0.75rem;">
                    - Faktur Aktif
                </div>
            </div>
        </a>
    </div>

    <!-- 2. Tagihan Mendesak / Jatuh Tempo -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon danger flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-danger text-truncate">Jatuh Tempo (&le; 7 Hari)</div>
                <div class="stat-value text-danger fs-5 fw-bold font-monospace text-truncate" id="statFinanceDueNominal">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-danger text-truncate" id="statFinanceDueCount" style="font-size: 0.75rem;">
                    - Perlu Dibayar
                </div>
            </div>
        </a>
    </div>

    <!-- 3. Realisasi Kas Keluar Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon success flex-shrink-0">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-truncate">Kas Keluar (Bulan Ini)</div>
                <div class="stat-value text-success fs-5 fw-bold font-monospace text-truncate" id="statFinanceCashOutNominal">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-muted text-truncate" id="statFinanceCashOutCount" style="font-size: 0.75rem;">
                    - Transaksi Bayar
                </div>
            </div>
        </a>
    </div>

    <!-- 4. PO Disetujui (Pipeline Tagihan Masuk) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/purchase_order/index.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon info flex-shrink-0">
                <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-truncate">PO Menunggu Faktur</div>
                <div class="stat-value text-info fs-5 fw-bold font-monospace text-truncate" id="statFinancePoWaiting">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-muted text-truncate" style="font-size: 0.75rem;">
                    PO Disetujui
                </div>
            </div>
        </a>
    </div>
</div>

<!-- 2. DUA KONTEN UTAMA (CLEAN, MINIMALIST & TANPA ICON BERLEBIH) -->
<div class="row g-3 mb-4">
    <!-- KIRI (65%): TAB PRIORITAS TAGIHAN & HUTANG VENDOR -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <!-- Nav Tabs Minimalis (Clean Text Tanpa Icon) -->
                <ul class="nav nav-pills card-header-pills small" id="financeTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1.5 px-3 fw-semibold rounded-pill" id="tab-urgent-tab" data-bs-toggle="pill" data-bs-target="#tab-urgent" type="button" role="tab" aria-controls="tab-urgent" aria-selected="true">
                            Tagihan Jatuh Tempo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1.5 px-3 fw-semibold rounded-pill" id="tab-vendor-tab" data-bs-toggle="pill" data-bs-target="#tab-vendor" type="button" role="tab" aria-controls="tab-vendor" aria-selected="false">
                            Top 5 Hutang Vendor
                        </button>
                    </li>
                </ul>

                <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php" class="btn btn-outline-primary btn-sm px-2.5 py-1 rounded-2 shadow-xs fw-semibold" style="font-size: 0.78rem;">
                    Jadwal Lengkap &rarr;
                </a>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="financeTabContent">
                    <!-- TAB 1: TAGIHAN JATUH TEMPO -->
                    <div class="tab-pane fade show active" id="tab-urgent" role="tabpanel" aria-labelledby="tab-urgent-tab">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th>No. Faktur</th>
                                        <th>Vendor</th>
                                        <th class="text-center">Jatuh Tempo</th>
                                        <th class="text-end">Sisa Tagihan</th>
                                        <th class="text-center" style="width: 75px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="financeUrgentTagihanBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat tagihan jatuh tempo...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: TOP 5 VENDOR -->
                    <div class="tab-pane fade" id="tab-vendor" role="tabpanel" aria-labelledby="tab-vendor-tab">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th>Nama Vendor</th>
                                        <th class="text-center" style="width: 100px;">Jml Faktur</th>
                                        <th class="text-end">Total Tagihan</th>
                                        <th class="text-end">Sisa Hutang</th>
                                    </tr>
                                </thead>
                                <tbody id="financeTopVendorsBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data vendor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KANAN (35%): RINGKASAN ARUS KAS & PEMBAYARAN TERBARU -->
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 h-100 d-flex flex-column">
            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark fs-6">
                    Arus Kas Keluar
                </span>
                <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/create.php" class="btn btn-primary btn-sm px-2.5 py-1 rounded-2 shadow-xs fw-semibold" style="font-size: 0.78rem;">
                    Bayar
                </a>
            </div>

            <div class="card-body p-3 d-flex flex-column gap-3">
                <!-- Section 1: Distribusi per Bank -->
                <div>
                    <div class="small fw-semibold text-secondary text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        Distribusi Sumber Dana
                    </div>
                    <div id="financeCashOutBankContainer">
                        <div class="text-center py-3 text-muted small">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data bank...
                        </div>
                    </div>
                </div>

                <hr class="my-0 text-muted opacity-25">

                <!-- Section 2: Pembayaran Terakhir -->
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small fw-semibold text-secondary text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            Pembayaran Terakhir
                        </div>
                        <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="text-decoration-none small text-primary fw-medium" style="font-size: 0.75rem;">
                            Lihat Semua &rarr;
                        </a>
                    </div>
                    <div class="list-group list-group-flush rounded-2 border" id="financeRecentPaymentsList">
                        <div class="text-center py-3 text-muted small">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat riwayat pembayaran...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- =============================================================
     DASHBOARD STANDARD: ROLE ADMIN, LOGISTIK, PURCHASING, MEKANIK, MANAGER
     ============================================================= -->

<?php if ($role === ROLE_ADMIN): ?>
<!-- STATISTIK MASTER DATA & INVENTARIS (KHUSUS LOGIN ADMIN) -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            Ringkasan Master Data &amp; Inventaris
        </h5>
    </div>
    
    <div class="row g-3">
        <!-- 1. Jumlah Karyawan Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/user/index.php" class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Karyawan Aktif</div>
                    <div class="stat-value" id="statKaryawanAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 2. Jumlah Barang Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card">
                <div class="stat-icon success"><i class="bi bi-box-seam-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Barang Aktif</div>
                    <div class="stat-value text-success" id="statBarangAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 3. Jumlah Barang Tidak Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card">
                <div class="stat-icon secondary"><i class="bi bi-archive-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Barang Tidak Aktif</div>
                    <div class="stat-value text-muted" id="statBarangNonAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 4. Total Stok -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card">
                <div class="stat-icon info"><i class="bi bi-boxes"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Total Stok Material (Semua Site)</div>
                    <div class="stat-value text-info" id="statTotalStok"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 5. Jumlah Vendor Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/vendor/index.php" class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-truck"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Vendor Aktif</div>
                    <div class="stat-value text-warning" id="statVendorAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 6. Jumlah Site Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/site/index.php" class="stat-card">
                <div class="stat-icon teal"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Site Aktif</div>
                    <div class="stat-value text-teal" id="statSiteAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- STATISTIK OPERASIONAL REQUEST ORDER (RO) -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            Status Permintaan Request Order
        </h5>
    </div>

    <div class="row g-3" id="statsRoRow">
        <?php if ($role === ROLE_MEKANIK): ?>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-file-earmark-text"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Draft Saya</div>
                    <div class="stat-value" id="statDraft">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-clock-history"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Menunggu Logistik</div>
                    <div class="stat-value" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Selesai Diproses (Diterima)</div>
                    <div class="stat-value text-success" id="statApproved">0</div>
                </div>
            </div>
        </div>

        <?php elseif ($role === ROLE_LOGISTIK): ?>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-inbox-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Request Masuk</div>
                    <div class="stat-value" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-arrow-repeat"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Sedang Diproses</div>
                    <div class="stat-value" id="statProcessing">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-patch-check-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Selesai (Diterima)</div>
                    <div class="stat-value text-success" id="statApproved">0</div>
                </div>
            </div>
        </div>

        <?php else: // Admin / Purchasing / Manager ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-files"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Total Draft</div>
                    <div class="stat-value" id="statDraft">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-inbox"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Menunggu Logistik</div>
                    <div class="stat-value" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon info"><i class="bi bi-gear-wide-connected"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Sedang Diproses</div>
                    <div class="stat-value text-info" id="statProcessing">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-check-all"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Selesai (Diterima)</div>
                    <div class="stat-value text-success" id="statApproved">0</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($role === ROLE_MEKANIK): ?>
<!-- KHUSUS MEKANIK: DAFTAR RO BELUM DITERIMA (CARD GRID) -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            <i class="bi bi-hourglass-split me-2 text-primary"></i>Request Order (In Progress)
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-sm btn-outline-primary fw-semibold">
            Lihat Semua RO &rarr;
        </a>
    </div>

    <!-- Grid Card RO Khusus Mekanik -->
    <div class="row g-3" id="pendingRoGrid">
        <div class="col-12 text-center py-5 text-muted bg-white rounded-3 border">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat daftar Request Order...
        </div>
    </div>
</div>

<?php else: ?>
<!-- TABEL MONITORING REQUEST ORDER TERBARU -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fs-6 fw-bold text-dark">
             Request Order Terbaru
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-sm btn-outline-primary fw-semibold">
            Lihat Semua RO &rarr;
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-custom mb-0">
                <thead class="table-light small text-muted text-uppercase">
                    <tr>
                        <th style="width: 140px;">No. RO</th>
                        <th style="width: 110px;">Tanggal</th>
                        <th>Peminta</th>
                        <th>Site / Workshop</th>
                        <th>Vendor Referensi</th>
                        <th style="width: 180px;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="recentRoBody">
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Request Order...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; // End of $role !== ROLE_FINANCE ?>

<script>
const USER_ROLE = '<?= $role ?>';

async function loadDashboardStats() {
    try {
        const res = await apiRequest('/api/dashboard/stats.php');
        if (res && res.success) {
            const d = res.data;
            
            // Render Master Data Stats (Khusus Admin)
            if (d.master_admin) {
                const ma = d.master_admin;
                const elKaryawan = document.getElementById('statKaryawanAktif');
                const elBarangAktif = document.getElementById('statBarangAktif');
                const elBarangNonAktif = document.getElementById('statBarangNonAktif');
                const elTotalStok = document.getElementById('statTotalStok');
                const elVendorAktif = document.getElementById('statVendorAktif');
                const elSiteAktif = document.getElementById('statSiteAktif');

                if (elKaryawan) elKaryawan.textContent = ma.karyawan_aktif.toLocaleString('id-ID');
                if (elBarangAktif) elBarangAktif.textContent = ma.barang_aktif.toLocaleString('id-ID');
                if (elBarangNonAktif) elBarangNonAktif.textContent = ma.barang_nonaktif.toLocaleString('id-ID');
                if (elTotalStok) elTotalStok.textContent = ma.total_stok.toLocaleString('id-ID');
                if (elVendorAktif) elVendorAktif.textContent = ma.vendor_aktif.toLocaleString('id-ID');
                if (elSiteAktif) elSiteAktif.textContent = ma.site_aktif.toLocaleString('id-ID');
            }

            // Render Request Order Stats
            if (d.request_order) {
                const ro = d.request_order;
                const elDraft = document.getElementById('statDraft');
                const elSub = document.getElementById('statSubmitted');
                const elProc = document.getElementById('statProcessing');
                const elApp = document.getElementById('statApproved');

                if (elDraft) elDraft.textContent = ro.draft.toLocaleString('id-ID');
                if (elSub) elSub.textContent = ro.submitted.toLocaleString('id-ID');
                if (elProc) elProc.textContent = ro.processing.toLocaleString('id-ID');
                if (elApp) elApp.textContent = ro.approved.toLocaleString('id-ID');
            }
        }
    } catch (err) {
        console.error('Error loading dashboard stats:', err);
    }
}

// Khusus Mekanik: Load Card Grid
async function loadPendingRoCards() {
    const grid = document.getElementById('pendingRoGrid');
    if (!grid) return;

    try {
        const res = await apiRequest('/api/dashboard/pending_ro.php');
        if (!res || !res.success || !Array.isArray(res.data) || res.data.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted bg-white rounded-3 border">
                    
                    <h6 class="fw-bold text-dark mb-1">Semua Request Order Telah Selesai Diterima!</h6>
                    <p class="small text-muted mb-0">Tidak ada Request Order aktif yang belum diproses atau belum diterima.</p>
                </div>
            `;
            return;
        }

        let html = '';
        res.data.forEach(ro => {
            const noRo = escapeHtml(ro.nomor || '-');
            const tgl = ro.tanggal_ro ? ro.tanggal_ro.split(' ')[0] : '-';
            const peminta = escapeHtml(ro.nama_karyawan || 'Peminta');
            const jabatan = escapeHtml(ro.nama_jabatan || (ro.nama_divisi || ''));
            const site = escapeHtml(ro.nama_site || '-');
            const totalItem = parseInt(ro.total_item) || 0;
            const totalQty = parseFloat(ro.total_qty) || 0;
            const status = ro.status || 'DRAFT';
            const isUrgent = (ro.prioritas === 'URGENT' || ro.prioritas === 'TINGGI');

            // Status Badge
            let badgeHtml = '';
            if (status === 'DRAFT') {
                badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-pencil me-1"></i>Draft</span>';
            } else if (status === 'TERKIRIM') {
                badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Menunggu Logistik</span>';
            } else if (status === 'DISETUJUI LOGISTIK') {
                badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-cart-plus me-1"></i>Menunggu Purchasing</span>';
            } else if (status === 'DISETUJUI PURCHASING') {
                badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>PO Terbit</span>';
            } else {
                badgeHtml = `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
            }

            html += `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-3 transition-hover" style="border-top: 3px solid #1e5288 !important;">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="font-monospace fw-bold text-primary fs-6">${noRo}</span>
                                    <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                                        <i class="bi bi-calendar-event me-1"></i>${tgl}
                                    </div>
                                </div>
                                <div>
                                    ${badgeHtml}
                                </div>
                            </div>

                            <div class="bg-light rounded-3 p-2 my-2 small">
                                <div class="d-flex align-items-center mb-1 text-truncate">
                                    <i class="bi bi-person-fill text-muted me-2"></i>
                                    <strong class="text-dark me-1">${peminta}</strong>
                                    ${jabatan ? `<span class="text-muted" style="font-size: 0.72rem;">(${jabatan})</span>` : ''}
                                </div>
                                <div class="d-flex align-items-center text-truncate">
                                    <i class="bi bi-geo-alt-fill text-muted me-2"></i>
                                    <span class="text-secondary">${site}</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small text-muted mb-3 mt-1">
                                <div>
                                    <i class="bi bi-boxes me-1 text-primary"></i>
                                    <span class="fw-semibold text-dark">${totalItem}</span> Item (${totalQty} qty)
                                </div>
                                ${isUrgent ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>` : ''}
                                ${ro.nomor_po ? `<span class="badge bg-light text-primary border font-monospace" style="font-size: 0.7rem;">PO: ${escapeHtml(ro.nomor_po)}</span>` : ''}
                            </div>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-end">
                                <a href="<?= BASE_URL ?>/admin/pages/request_order/edit.php?id=${ro.id_request}" class="btn btn-outline-primary btn-sm px-3 py-1 fw-semibold w-100" style="font-size: 0.8rem;">
                                    Lihat Rincian RO &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        grid.innerHTML = html;
    } catch (err) {
        console.error('Error loading pending RO cards:', err);
        grid.innerHTML = '<div class="col-12 text-center py-4 text-danger bg-white rounded-3 border">Gagal memuat data Request Order.</div>';
    }
}

// Khusus Non-Mekanik: Load Tabel RO Terbaru
async function loadRecentRoTable() {
    const tbody = document.getElementById('recentRoBody');
    if (!tbody) return;

    try {
        const res = await apiRequest('/api/request_order/index.php?limit=8');
        if (!res || !res.success || !res.data || !Array.isArray(res.data.items) || res.data.items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Belum ada data Request Order.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        res.data.items.forEach(ro => {
            const noRo = escapeHtml(ro.nomor || '-');
            const tgl = ro.tanggal_ro ? ro.tanggal_ro.split(' ')[0] : '-';
            const peminta = escapeHtml(ro.nama_karyawan || '-');
            const site = escapeHtml(ro.nama_site || '-');
            const vendor = escapeHtml(ro.nama_vendor || '-');
            const status = ro.status || 'DRAFT';
            const detailUrl = `<?= BASE_URL ?>/admin/pages/request_order/edit.php?id=${ro.id_request}`;

            let badgeHtml = '';
            if (status === 'DRAFT') {
                badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Draft</span>';
            } else if (status === 'TERKIRIM') {
                badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Menunggu Logistik</span>';
            } else if (status === 'DISETUJUI LOGISTIK') {
                badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">Menunggu Purchasing</span>';
            } else if (status === 'DISETUJUI PURCHASING') {
                badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">PO Terbit</span>';
            } else if (status === 'DITERIMA FULL') {
                badgeHtml = '<span class="badge bg-success text-white border px-2 py-1">Diterima Full</span>';
            } else if (status === 'DITERIMA SEBAGIAN') {
                badgeHtml = '<span class="badge bg-warning text-dark border px-2 py-1">Diterima Sebagian</span>';
            } else {
                badgeHtml = `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
            }

            html += `
                <tr>
                    <td>
                        <a href="${detailUrl}" class="font-monospace fw-bold text-primary text-decoration-none" title="Buka Detail ${noRo}">
                            ${noRo}
                        </a>
                    </td>
                    <td class="small text-muted font-monospace">${tgl}</td>
                    <td class="fw-semibold text-dark">${peminta}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${site}</span></td>
                    <td class="small text-muted">${vendor}</td>
                    <td class="text-center">
                        <a href="${detailUrl}" class="text-decoration-none d-inline-block hover-opacity" title="Buka Detail ${noRo}">
                            ${badgeHtml}
                        </a>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-danger">Gagal memuat data Request Order.</td></tr>';
    }
}

// Khusus Finance: Load Finance Dashboard Analytics & KPIs
async function loadFinanceDashboard() {
    try {
        const res = await apiRequest('/api/dashboard/finance_stats.php');
        if (!res || !res.success || !res.data) return;

        const { kpi, cash_out_by_bank, top_vendors, urgent_tagihan, recent_payments } = res.data;

        // 1. KPI Cards
        const elPoWaiting = document.getElementById('statFinancePoWaiting');
        const elFakturCount = document.getElementById('statFinanceFakturCount');
        const elFakturNom = document.getElementById('statFinanceFakturNominal');
        const elDueCount = document.getElementById('statFinanceDueCount');
        const elDueNom = document.getElementById('statFinanceDueNominal');
        const elCashOutNom = document.getElementById('statFinanceCashOutNominal');
        const elCashOutCount = document.getElementById('statFinanceCashOutCount');

        if (elPoWaiting) elPoWaiting.textContent = kpi.po_waiting_invoice.toLocaleString('id-ID') + ' PO';
        if (elFakturCount) elFakturCount.textContent = kpi.faktur_belum_lunas_count.toLocaleString('id-ID') + ' Faktur Aktif';
        if (elFakturNom) elFakturNom.textContent = 'Rp ' + Number(kpi.faktur_belum_lunas_nominal).toLocaleString('id-ID');
        if (elDueCount) elDueCount.textContent = kpi.jatuh_tempo_count.toLocaleString('id-ID') + ' Faktur Perlu Dibayar';
        if (elDueNom) elDueNom.textContent = 'Rp ' + Number(kpi.jatuh_tempo_nominal).toLocaleString('id-ID');
        if (elCashOutNom) elCashOutNom.textContent = 'Rp ' + Number(kpi.cash_out_month_nominal).toLocaleString('id-ID');
        if (elCashOutCount) elCashOutCount.textContent = kpi.cash_out_month_count.toLocaleString('id-ID') + ' Transaksi Bayar';

        // 2. Urgent Tagihan Jatuh Tempo Terdekat
        const urgentTbody = document.getElementById('financeUrgentTagihanBody');
        if (urgentTbody) {
            if (!urgent_tagihan || urgent_tagihan.length === 0) {
                urgentTbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted small">
                            Tidak ada tagihan yang mendekati jatuh tempo.
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                urgent_tagihan.forEach(it => {
                    const sisaHari = parseInt(it.sisa_hari);
                    let badgeHari = '';
                    if (sisaHari < 0) {
                        badgeHari = `<span class="badge bg-danger text-white">Lewat ${Math.abs(sisaHari)} hr</span>`;
                    } else if (sisaHari === 0) {
                        badgeHari = `<span class="badge bg-danger text-white">Hari Ini</span>`;
                    } else if (sisaHari <= 3) {
                        badgeHari = `<span class="badge bg-warning text-dark">${sisaHari} hr lagi</span>`;
                    } else {
                        badgeHari = `<span class="badge bg-light text-secondary border">${sisaHari} hr lagi</span>`;
                    }

                    const tglDue = it.tanggal_jatuh_tempo ? it.tanggal_jatuh_tempo.split('-').reverse().join('/') : '-';
                    const bayarUrl = `<?= BASE_URL ?>/admin/pages/pembayaran_po/create.php?id_faktur=${it.id_faktur}`;

                    html += `
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/pages/faktur_po/edit.php?id=${it.id_faktur}" class="fw-bold text-primary text-decoration-none font-monospace">
                                    ${escapeHtml(it.nomor_faktur)}
                                </a>
                                ${it.nomor_faktur_vendor ? `<div class="text-muted small" style="font-size: 0.72rem;">Ref: ${escapeHtml(it.nomor_faktur_vendor)}</div>` : ''}
                            </td>
                            <td>
                                <strong class="text-dark">${escapeHtml(it.nama_vendor)}</strong>
                            </td>
                            <td class="text-center font-monospace">
                                <div>${tglDue}</div>
                                <div class="mt-0.5">${badgeHari}</div>
                            </td>
                            <td class="text-end font-monospace fw-bold text-danger">
                                Rp ${Number(it.sisa_tagihan).toLocaleString('id-ID')}
                            </td>
                            <td class="text-center">
                                <a href="${bayarUrl}" class="btn btn-outline-primary btn-sm px-2.5 py-1 rounded-2 shadow-xs fw-semibold" title="Bayar Faktur Ini" style="font-size: 0.75rem;">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    `;
                });
                urgentTbody.innerHTML = html;
            }
        }

        // 3. Top 5 Vendor Tagihan Terbesar
        const topVendorsTbody = document.getElementById('financeTopVendorsBody');
        if (topVendorsTbody) {
            if (!top_vendors || top_vendors.length === 0) {
                topVendorsTbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small">
                            Tidak ada hutang usaha aktif.
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                top_vendors.forEach(v => {
                    html += `
                        <tr>
                            <td>
                                <strong class="text-dark">${escapeHtml(v.nama_vendor)}</strong>
                            </td>
                            <td class="text-center font-monospace">${Number(v.total_faktur).toLocaleString('id-ID')} Faktur</td>
                            <td class="text-end font-monospace text-muted">Rp ${Number(v.total_tagihan).toLocaleString('id-ID')}</td>
                            <td class="text-end font-monospace fw-bold text-primary">Rp ${Number(v.total_sisa_tagihan).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                });
                topVendorsTbody.innerHTML = html;
            }
        }

        // 4. Pengeluaran per Bank (Bulan Ini)
        const bankContainer = document.getElementById('financeCashOutBankContainer');
        if (bankContainer) {
            if (!cash_out_by_bank || cash_out_by_bank.length === 0) {
                bankContainer.innerHTML = `
                    <div class="text-center py-2 text-muted small">
                        Belum ada pengeluaran kas/bank bulan ini.
                    </div>
                `;
            } else {
                let html = '<div class="d-flex flex-column gap-2">';
                cash_out_by_bank.forEach(b => {
                    html += `
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <div>
                                    <strong class="text-dark">${escapeHtml(b.nama_bank)}</strong>
                                    <span class="text-muted ms-1" style="font-size: 0.7rem;">(${b.total_transaksi}x)</span>
                                </div>
                                <div class="font-monospace fw-bold text-dark" style="font-size: 0.8rem;">
                                    Rp ${Number(b.total_nominal).toLocaleString('id-ID')}
                                    <span class="badge bg-light text-secondary border ms-1">${b.persentase}%</span>
                                </div>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: ${b.persentase}%;" aria-valuenow="${b.persentase}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                bankContainer.innerHTML = html;
            }
        }

        // 5. Pembayaran Terakhir (Maks 4 items)
        const recentList = document.getElementById('financeRecentPaymentsList');
        if (recentList) {
            if (!recent_payments || recent_payments.length === 0) {
                recentList.innerHTML = `
                    <div class="text-center py-3 text-muted small">
                        Belum ada riwayat transaksi pembayaran.
                    </div>
                `;
            } else {
                let html = '';
                recent_payments.slice(0, 4).forEach(p => {
                    const tglP = p.tanggal_bayar ? p.tanggal_bayar.split(' ')[0].split('-').reverse().join('/') : '-';
                    const detailUrl = `<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php`;

                    html += `
                        <a href="${detailUrl}" class="list-group-item list-group-item-action p-2 d-flex justify-content-between align-items-center gap-2">
                            <div class="text-truncate">
                                <div class="fw-bold text-primary font-monospace" style="font-size: 0.78rem;">
                                    ${escapeHtml(p.kode_pembayaran || '-')}
                                </div>
                                <div class="text-dark small text-truncate" style="font-size: 0.75rem;">
                                    ${escapeHtml(p.nama_vendor)}
                                </div>
                                <div class="text-muted" style="font-size: 0.68rem;">
                                    <span>${tglP}</span> &bull; <span>${escapeHtml(p.bank_pengirim || 'Kas')}</span>
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="font-monospace fw-bold text-success" style="font-size: 0.82rem;">
                                    Rp ${Number(p.nominal_bayar).toLocaleString('id-ID')}
                                </div>
                            </div>
                        </a>
                    `;
                });
                recentList.innerHTML = html;
            }
        }

    } catch (err) {
        console.error('Error loading finance dashboard:', err);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

document.addEventListener('DOMContentLoaded', () => {
    if (USER_ROLE === 'FINANCE') {
        loadFinanceDashboard();
    } else {
        loadDashboardStats();
        if (USER_ROLE === 'MEKANIK') {
            loadPendingRoCards();
        } else {
            loadRecentRoTable();
        }
    }
});
</script>

<?php
require_once __DIR__ . '/components/footer.php';
?>
