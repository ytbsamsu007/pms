<?php
/**
 * Komponen Navbar / Topbar Admin - PT Jaya Teknik
 */
$pageHeading = $pageHeading ?? 'Dashboard';
$userName = $_SESSION['nama'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'MEKANIK';
?>
<div id="main-content">
    <header class="app-topbar">
        <div class="d-flex align-items-center gap-2">
            <!-- Mobile Toggle -->
            <button class="btn btn-outline-secondary d-lg-none p-1 px-2 border-0" type="button" onclick="toggleSidebar()" aria-label="Toggle Mobile Navigation">
                <i class="bi bi-list fs-4"></i>
            </button>
            
            <!-- Desktop Collapse/Expand Sidebar Toggle Elegan -->
            <button class="btn-sidebar-toggle d-none d-lg-inline-flex" type="button" onclick="toggleSidebarCollapse()" title="Minimize / Expand Sidebar" id="btnSidebarCollapse">
                <i class="bi bi-text-indent-left"></i>
            </button>

            <h1 class="topbar-title mb-0 fs-5 ms-1"><?= htmlspecialchars($pageHeading) ?></h1>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle text-primary"></i>
                    <span class="d-none d-sm-inline fw-semibold"><?= htmlspecialchars($userName) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">Login sebagai: <?= htmlspecialchars($userRole) ?></h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/pages/user/profile.php">
                            <i class="bi bi-person-gear text-primary"></i> Edit Profil
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="javascript:void(0)" onclick="handleLogout()">
                            <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Top App Multi-Tab Bar (ERP Style) -->
    <div class="app-tabs-bar" id="appTabsBar">
        <div class="app-tabs-scroller" id="appTabsScroller">
            <div class="app-tabs-container" id="appTabsContainer">
                <!-- Rendered Dynamically by JS Engine -->
            </div>
        </div>
        <div class="app-tabs-actions">
            <div class="dropdown">
                <button class="btn btn-sm btn-light border-0 py-1 px-2 text-muted" type="button" data-bs-toggle="dropdown" title="Opsi Tab Workspace">
                    <i class="bi bi-chevron-down"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item small" href="javascript:void(0)" onclick="AppTabs.closeOtherTabs()"><i class="bi bi-x-circle me-1 text-primary"></i> Tutup Tab Lain</a></li>
                    <li><a class="dropdown-item small text-danger" href="javascript:void(0)" onclick="AppTabs.closeAllTabs()"><i class="bi bi-x-octagon me-1"></i> Tutup Semua Tab</a></li>
                </ul>
            </div>
        </div>
    </div>

    <main class="content-body">
