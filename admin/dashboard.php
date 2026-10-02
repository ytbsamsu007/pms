<?php
/**
 * Dashboard Utama - PT Jaya Teknis
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

$user = requireAuth();
$pageTitle = 'Dashboard';
$pageHeading = 'Dashboard ' . ucfirst(strtolower($user['role']));

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
        <div class="card text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%) !important;">
            <div class="card-body p-4">
                <h2 class="fs-4 fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($nama) ?>!</h2>
                <p class="mb-0 text-white-50 small">Sistem Pengadaan &amp; Logistik Terintegrasi</p>
            </div>
        </div>
    </div>
</div>

<?php
// Router Dashboard Berdasarkan Role
$dashboardFile = __DIR__ . '/components/dashboards/' . strtolower($role) . '.php';

if (file_exists($dashboardFile)) {
    require_once $dashboardFile;
} else {
    echo '<div class="alert alert-info border-info-subtle bg-info-subtle text-info-emphasis shadow-sm">
            <i class="bi bi-info-circle-fill me-2"></i>
            Modul Dashboard khusus untuk role Anda (<strong>' . htmlspecialchars($role) . '</strong>) belum tersedia.
          </div>';
}
?>

<!-- Helper Function JS Global untuk Dashboard -->
<script>
function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
const USER_ROLE = '<?= $role ?>';
</script>

<?php
require_once __DIR__ . '/components/footer.php';
?>
