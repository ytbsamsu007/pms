<?php
/**
 * API Request Order: Generate Next Number Endpoint - PT Jaya Teknis
 * Menggunakan format dinamis dari tabel `penomoran` (Tipe: REQUEST)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/penomoran_helper.php';
require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan GET.', null, 405);
}

// Ambil parameter tanggal (default sekarang)
$dateParam = trim($_GET['date'] ?? '');
if (empty($dateParam) || !strtotime($dateParam)) {
    $dateParam = date('Y-m-d');
}

$gen = generateNomorTransaksi($conn, 'REQUEST', $dateParam);

if ($gen['success']) {
    jsonResponse(true, 'Nomor RO berikutnya berhasil di-generate.', [
        'nomor_ro' => $gen['nomor'],
        'format' => $gen['format'],
        'counter' => $gen['counter'],
        'digit_counter' => $gen['digit_counter'],
        'tipe_reset' => $gen['tipe_reset']
    ], 200);
} else {
    jsonResponse(false, $gen['message'] ?? 'Gagal generate nomor RO.', null, 500);
}
