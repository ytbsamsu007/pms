<?php
/**
 * API Purchase Order: Generate Next Number Endpoint - PT Jaya Teknis
 * Menggunakan format dinamis dari tabel `penomoran` (Tipe: PURCHASE)
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

// Cek apakah mode draft
$isDraft = isset($_GET['draft']) && ($_GET['draft'] == '1' || strtolower($_GET['draft']) === 'true' || strtolower($_GET['type'] ?? '') === 'draft');

// Ambil parameter tanggal (default sekarang)
$dateParam = trim($_GET['date'] ?? $_GET['tanggal'] ?? '');
if (empty($dateParam) || !strtotime($dateParam)) {
    $dateParam = date('Y-m-d');
}

$gen = generateNomorTransaksi($conn, 'PURCHASE', $dateParam);

if ($gen['success']) {
    $nomorPo = $gen['nomor'];
    if ($isDraft) {
        $nomorPo = 'DRF/' . $nomorPo;
    }

    jsonResponse(true, 'Nomor PO berikutnya berhasil di-generate.', [
        'nomor_po' => $nomorPo,
        'format' => $gen['format'],
        'counter' => $gen['counter'],
        'digit_counter' => $gen['digit_counter'],
        'tipe_reset' => $gen['tipe_reset'],
        'is_draft' => $isDraft
    ], 200);
} else {
    jsonResponse(false, $gen['message'] ?? 'Gagal generate nomor PO.', null, 500);
}
