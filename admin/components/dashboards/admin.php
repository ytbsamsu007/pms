<!-- =============================================================
     DASHBOARD KHUSUS ROLE ADMIN (MANAJEMEN SISTEM)
     ============================================================= -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            Ringkasan Master Data &amp; Inventaris
        </h5>
    </div>
    
    <div class="row g-3">
        <!-- 1. Jumlah Karyawan Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/user/index.php" class="stat-card text-decoration-none text-dark h-100 d-flex align-items-center bg-white p-3 rounded shadow-sm border border-primary-subtle hover-opacity">
                <div class="stat-icon flex-shrink-0 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Jumlah Karyawan Aktif</div>
                    <div class="stat-value text-dark fs-4 fw-bold font-monospace text-truncate" id="statKaryawanAktif">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. Jumlah Barang Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card text-decoration-none text-dark h-100 d-flex align-items-center bg-white p-3 rounded shadow-sm border border-success-subtle hover-opacity">
                <div class="stat-icon flex-shrink-0 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-box-seam-fill fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Jumlah Barang Aktif</div>
                    <div class="stat-value text-success fs-4 fw-bold font-monospace text-truncate" id="statBarangAktif">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. Jumlah Barang Tidak Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card text-decoration-none text-dark h-100 d-flex align-items-center bg-white p-3 rounded shadow-sm border border-secondary-subtle hover-opacity">
                <div class="stat-icon flex-shrink-0 bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-archive-fill fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Barang Tidak Aktif</div>
                    <div class="stat-value text-muted fs-4 fw-bold font-monospace text-truncate" id="statBarangNonAktif">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </a>
        </div>

        <!-- 4. Total Stok -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card text-decoration-none text-dark h-100 d-flex align-items-center bg-white p-3 rounded shadow-sm border border-info-subtle hover-opacity">
                <div class="stat-icon flex-shrink-0 bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-boxes fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Total Stok Material</div>
                    <div class="stat-value text-info fs-4 fw-bold font-monospace text-truncate" id="statTotalStok">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </a>
        </div>

        <!-- 5. Jumlah Vendor Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/vendor/index.php" class="stat-card text-decoration-none text-dark h-100 d-flex align-items-center bg-white p-3 rounded shadow-sm border border-warning-subtle hover-opacity">
                <div class="stat-icon flex-shrink-0 bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-truck fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Jumlah Vendor Aktif</div>
                    <div class="stat-value text-warning fs-4 fw-bold font-monospace text-truncate" id="statVendorAktif">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </a>
        </div>

        <!-- 6. Jumlah Site Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/site/index.php" class="stat-card text-decoration-none text-dark h-100 d-flex align-items-center bg-white p-3 rounded shadow-sm border border-primary-subtle hover-opacity">
                <div class="stat-icon flex-shrink-0 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-geo-alt-fill fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Jumlah Site Aktif</div>
                    <div class="stat-value text-primary fs-4 fw-bold font-monospace text-truncate" id="statSiteAktif">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await apiRequest('/api/dashboard/stats.php');
        if (res && res.success && res.data && res.data.master_admin) {
            const ma = res.data.master_admin;
            const updateEl = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val.toLocaleString('id-ID');
            };

            updateEl('statKaryawanAktif', ma.karyawan_aktif);
            updateEl('statBarangAktif', ma.barang_aktif);
            updateEl('statBarangNonAktif', ma.barang_nonaktif);
            updateEl('statTotalStok', ma.total_stok);
            updateEl('statVendorAktif', ma.vendor_aktif);
            updateEl('statSiteAktif', ma.site_aktif);
        }
    } catch (err) {
        console.error('Error loading admin dashboard stats:', err);
    }
});
</script>
