<?php
/**
 * API Auth: Me / Profil Endpoint - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

// Memastikan request terautentikasi via API Middleware
$currentUser = apiAuth();

jsonResponse(true, 'Data profil user berhasil diambil.', [
    'user' => $currentUser
], 200);
