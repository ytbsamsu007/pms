<?php
/**
 * API Mutasi Barang: Generate Nomor Mutasi Otomatis
 * Path: api/mutasi_order/get_next_number.php
 * Menggunakan format dinamis dari tabel `penomoran` (Tipe: MUTASI BARANG)
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

apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_FINANCE, ROLE_MANAGER]);

$tanggal = trim($_GET['tanggal'] ?? $_GET['date'] ?? '');
if (empty($tanggal) || !strtotime($tanggal)) {
    $tanggal = date('Y-m-d');
}

$gen = generateNomorTransaksi($conn, 'MUTASI BARANG', $tanggal);

if ($gen['success']) {
    jsonResponse(true, 'Nomor Mutasi Barang berhasil digenerate.', [
        'kode_mutasi' => $gen['nomor'],
        'nomor' => $gen['nomor'],
        'format' => $gen['format'],
        'counter' => $gen['counter'],
        'digit_counter' => $gen['digit_counter'],
        'tipe_reset' => $gen['tipe_reset']
    ], 200);
} else {
    jsonResponse(false, $gen['message'] ?? 'Gagal generate nomor Mutasi Barang.', null, 500);
}
