<?php
/**
 * API Authentication Middleware - PT Jaya Teknis
 * Memvalidasi autentikasi API via PHP Session atau Bearer Token.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

/**
 * Memastikan request API terautentikasi dan mengembalikan data user aktif.
 * Menghentikan eksekusi dengan HTTP 401 jika tidak valid.
 *
 * @param array $allowedRoles Daftar role yang diizinkan (kosong = semua role terautentikasi)
 * @return array Data user yang terotentikasi
 */
function apiAuth(array $allowedRoles = []): array {
    $user = null;
    
    // 1. Cek dari PHP Session aktif
    if (isLoggedIn()) {
        $user = getCurrentUser();
    } 
    // 2. Cek dari Authorization Header (Bearer Token)
    else {
        $bearerToken = getBearerToken();
        if ($bearerToken) {
            // Verifikasi token jika disimpan di session server
            if (!empty($_SESSION['api_token']) && hash_equals($_SESSION['api_token'], $bearerToken)) {
                $user = getCurrentUser();
            }
        }
    }

    // Jika autentikasi gagal
    if (!$user) {
        jsonResponse(false, 'Unauthorized. Silakan login terlebih dahulu.', null, 401);
    }

    // Validasi hak akses role jika ditentukan (Case-insensitive)
    if (!empty($allowedRoles)) {
        $upperAllowed = array_map('strtoupper', $allowedRoles);
        $userRoleUpper = strtoupper((string)($user['role'] ?? ''));
        if (!in_array($userRoleUpper, $upperAllowed)) {
            jsonResponse(false, 'Forbidden. Anda tidak memiliki hak akses untuk tindakan ini.', [
                'user_role' => $user['role'],
                'allowed_roles' => $allowedRoles
            ], 403);
        }
    }

    return $user;
}
