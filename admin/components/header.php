<?php
/**
 * Komponen Header HTML Admin - PT Jaya Teknis
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../../config/session.php';
}

$pageTitle = $pageTitle ?? 'Purchasing Management';
$currentUser = getCurrentUser() ?? [];
$companyProfile = getCompanyProfile();
$companyName = $companyProfile['nama'] ?? 'PT Jaya Teknis';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Design Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/app.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/responsive.css">

    <script>
        // Global App Config untuk JavaScript Frontend
        const BASE_URL = '<?= BASE_URL ?>';
        const API_TOKEN = '<?= $_SESSION['api_token'] ?? '' ?>';
        const CURRENT_USER = <?= json_encode($currentUser) ?>;

        // URL Parameter Obfuscation / Hash Helper (JS Equivalent)
        function encodeId(id) {
            if (id === null || id === undefined || id === '' || isNaN(id)) return '';
            const num = parseInt(id, 10);
            if (num <= 0) return '';
            // Obfuscate ID dengan payload b64 safe
            try {
                const raw = 'JT_' + num;
                return btoa(raw).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
            } catch(e) {
                return String(num);
            }
        }

        function decodeId(str) {
            if (!str) return 0;
            // Blok jika berupa angka murni tanpa hash (mencegah penembakan URL langsung)
            if (!isNaN(str) && /^\d+$/.test(str.trim())) return 0;
            try {
                let b64 = str.replace(/-/g, '+').replace(/_/g, '/');
                while (b64.length % 4) b64 += '=';
                const decoded = atob(b64);
                if (decoded.startsWith('JT_')) {
                    const val = parseInt(decoded.substring(3), 10);
                    return isNaN(val) ? 0 : val;
                }
            } catch(e) {}
            return 0;
        }
    </script>
<body>
<div id="app-wrapper">
<script>
    // Terapkan status sidebar-collapsed seketika secara sinkron sebelum render sidebar agar tidak ada flicker/animasi jump
    try {
        if (localStorage.getItem('jt_sidebar_collapsed') === '1') {
            document.getElementById('app-wrapper').className = 'sidebar-collapsed';
        }
    } catch (e) {}
</script>
