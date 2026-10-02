<?php
/**
 * Dynamic Sidebar Component - PT Jaya Teknis
 */
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? '';
$userName = $currentUser['nama'] ?? 'User';
$currentUri = ($_SERVER['SCRIPT_NAME'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? '');
$companyProfile = getCompanyProfile();
$companyName = !empty($companyProfile['nama']) ? $companyProfile['nama'] : 'PT Jaya Teknik';
$companyPicture = !empty($companyProfile['picture']) ? $companyProfile['picture'] : '';

// Dapatkan id_jabatan user yang aktif
$userJabatanId = isset($currentUser['id_jabatan']) ? (int)$currentUser['id_jabatan'] : 0;
if ($userJabatanId <= 0) {
    $jabQuery = $conn->query("SELECT id_jabatan FROM jabatan ORDER BY level ASC, id_jabatan ASC LIMIT 1");
    if ($jabQuery && $rowJ = $jabQuery->fetch_assoc()) {
        $userJabatanId = (int)$rowJ['id_jabatan'];
    } else {
        $userJabatanId = 1;
    }
}

// Query Menu Level Berdasarkan Hak Akses Jabatan
$menuQuery = $conn->query("SELECT * FROM menu_level 
                          WHERE id_jabatan = $userJabatanId AND akses = 1 AND terlihat = 1 
                          ORDER BY FIELD(kategori_menu, 'MENU UTAMA', 'OPERASIONAL', 'MASTER DATA', 'LAPORAN'),
                                   urutan ASC, id_levelmenu ASC");

$groupedMenus = [];
$childrenByParent = [];

if ($menuQuery && $menuQuery->num_rows > 0) {
    while ($m = $menuQuery->fetch_assoc()) {
        if (!empty($m['id_parent'])) {
            $childrenByParent[(int)$m['id_parent']][] = $m;
        } else {
            $kat = $m['kategori_menu'] ?: 'OPERASIONAL';
            $groupedMenus[$kat][] = $m;
        }
    }
}
?>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar-wrapper app-sidebar" id="sidebarWrapper">
    <!-- Brand / Logo Header Dinamis dari Tabel Profile -->
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="brand-link">
            <div class="brand-icon">
                <?php if ($companyPicture): ?>
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyPicture) ?>" alt="Logo" class="rounded" style="width: 100%; height: 100%; object-fit: contain;">
                <?php else: ?>
                    <i class="bi bi-shield-check text-info"></i>
                <?php endif; ?>
            </div>
            <div class="brand-text">
                <span class="brand-title"><?= htmlspecialchars($companyName) ?></span>
                <span class="brand-subtitle">Web-Based Purchase System</span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu Dinamis Mengikuti Jabatan -->
    <nav class="sidebar-nav">
        <?php if (!empty($groupedMenus)): ?>
            <?php foreach ($groupedMenus as $kategori => $menus): ?>
                <div class="nav-section-title <?= ($kategori === 'MENU UTAMA') ? '' : 'mt-2' ?>">
                    <?= htmlspecialchars($kategori) ?>
                </div>

                <?php foreach ($menus as $menu): ?>
                    <?php 
                    $menuId = (int)$menu['id_levelmenu'];
                    $isParent = (int)$menu['is_parent'] === 1;
                    $children = $childrenByParent[$menuId] ?? [];
                    $iconClass = !empty($menu['icon']) ? htmlspecialchars($menu['icon']) : 'bi-circle';
                    $menuName = htmlspecialchars($menu['nama_menu']);
                    $menuLink = !empty($menu['link']) ? $menu['link'] : '#';
                    
                    // Format full URL
                    $fullUrl = (strpos($menuLink, 'http') === 0 || $menuLink === '#') ? $menuLink : (BASE_URL . '/' . ltrim($menuLink, '/'));
                    ?>

                    <?php if ($isParent && !empty($children)): ?>
                        <?php 
                        // Periksa apakah salah satu child aktif
                        $isAnyChildActive = false;
                        foreach ($children as $c) {
                            $cLink = ltrim($c['link'] ?? '', '/');
                            if (!empty($cLink) && strpos($currentUri, $cLink) !== false) {
                                $isAnyChildActive = true;
                                break;
                            }
                        }
                        $collapseId = 'collapseMenu_' . $menuId;
                        ?>
                        <a class="sidebar-link <?= $isAnyChildActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="javascript:void(0)" data-bs-target="#<?= $collapseId ?>" role="button" aria-expanded="<?= $isAnyChildActive ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>" data-menu-title="<?= $menuName ?>">
                            <div class="d-flex align-items-center">
                                <i class="bi <?= $iconClass ?>"></i>
                                <span><?= $menuName ?></span>
                            </div>
                            <i class="bi bi-chevron-down collapse-arrow"></i>
                        </a>
                        <div class="collapse <?= $isAnyChildActive ? 'show' : '' ?>" id="<?= $collapseId ?>">
                            <div class="sidebar-submenu">
                                <?php foreach ($children as $child): ?>
                                    <?php 
                                    $childLink = $child['link'] ?? '#';
                                    $childFullUrl = (strpos($childLink, 'http') === 0 || $childLink === '#') ? $childLink : (BASE_URL . '/' . ltrim($childLink, '/'));
                                    $childActive = (!empty($childLink) && $childLink !== '#' && strpos($currentUri, ltrim($childLink, '/')) !== false);
                                    $childIcon = !empty($child['icon']) ? htmlspecialchars($child['icon']) : 'bi-circle';
                                    ?>
                                    <a href="<?= $childFullUrl ?>" class="sidebar-sublink <?= $childActive ? 'active' : '' ?>" data-menu-title="<?= htmlspecialchars($child['nama_menu']) ?>">
                                        <i class="bi <?= $childIcon ?>"></i>
                                        <span><?= htmlspecialchars($child['nama_menu']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <?php 
                        $menuActive = (!empty($menuLink) && $menuLink !== '#' && strpos($currentUri, ltrim($menuLink, '/')) !== false);
                        ?>
                        <a href="<?= $fullUrl ?>" class="sidebar-link <?= $menuActive ? 'active' : '' ?>" data-menu-title="<?= $menuName ?>">
                            <i class="bi <?= $iconClass ?>"></i>
                            <span><?= $menuName ?></span>
                        </a>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endforeach; ?>

        <?php else: ?>
            <!-- Fallback Jika Belum Ada Menu Dikonfigurasi -->
            <div class="nav-section-title">Menu Utama</div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link active" data-menu-title="Dashboard">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <div class="nav-section-title mt-2">Master Data</div>
            <a href="<?= BASE_URL ?>/admin/pages/menu/index.php" class="sidebar-link" data-menu-title="Manajemen Menu">
                <i class="bi bi-list-check"></i>
                <span>Manajemen Menu</span>
            </a>
        <?php endif; ?>
    </nav>
</aside>
