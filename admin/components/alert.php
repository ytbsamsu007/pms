<?php
/**
 * Komponen Helper Alert / Notifikasi Flash - PT Jaya Teknis
 */
function renderFlashAlert(): void {
    if (!empty($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        $icon = 'bi-info-circle-fill';
        if ($type === 'success') $icon = 'bi-check-circle-fill';
        if ($type === 'danger' || $type === 'error') {
            $type = 'danger';
            $icon = 'bi-exclamation-triangle-fill';
        }
        if ($type === 'warning') $icon = 'bi-exclamation-circle-fill';
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm' role='alert'>
                <i class='bi {$icon} fs-5'></i>
                <div>" . htmlspecialchars($msg) . "</div>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}
