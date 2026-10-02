<?php
/**
 * Root Index - PT Jaya Teknis
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/admin/login.php');
}
exit;
