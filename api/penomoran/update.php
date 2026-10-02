<?php
/**
 * REST API: Update Master Penomoran Transaksi
 * Endpoint: /api/penomoran/update.php
 * Path: api/penomoran/update.php
 * Khusus Role: ADMIN
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_ADMIN]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendJson(false, 'Metode HTTP tidak diizinkan.', null, 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $idNomor       = isset($input['id_nomor']) ? (int)$input['id_nomor'] : 0;
    $namaPenomoran = trim($input['nama_penomoran'] ?? '');
    $tipeTransaksi = trim($input['tipe_transaksi'] ?? '');
    $tipePenomoran = isset($input['tipe_penomoran']) ? (int)$input['tipe_penomoran'] : 0;
    $digitCounter  = isset($input['digit_counter']) ? (int)$input['digit_counter'] : 5;
    $format        = trim($input['format'] ?? '');
    $idKaryawan    = (int)($user['id_karyawan'] ?? 1);

    if ($idNomor <= 0) {
        sendJson(false, 'ID penomoran tidak valid', null, 400);
    }

    if (empty($namaPenomoran)) {
        sendJson(false, 'Nama penomoran wajib diisi', null, 422);
    }

    $validTipe = ['REQUEST','PURCHASE','RECEIVING','RETUR PO','FAKTUR PO','PAYMENT PO','MUTASI BARANG','ADJUSTMENT STOK'];
    if (!in_array($tipeTransaksi, $validTipe)) {
        sendJson(false, 'Tipe transaksi tidak valid', null, 422);
    }

    if ($digitCounter < 1 || $digitCounter > 10) {
        sendJson(false, 'Jumlah digit counter harus antara 1 sampai 10', null, 422);
    }

    if (empty($format)) {
        sendJson(false, 'Format penomoran tidak boleh kosong', null, 422);
    }

    // Cek duplikasi tipe transaksi di ID lain
    $stmtCek = $conn->prepare("SELECT id_nomor FROM penomoran WHERE tipe_transaksi = ? AND id_nomor != ? LIMIT 1");
    $stmtCek->bind_param("si", $tipeTransaksi, $idNomor);
    $stmtCek->execute();
    $resCek = $stmtCek->get_result();
    if ($resCek->num_rows > 0) {
        $stmtCek->close();
        sendJson(false, "Format penomoran untuk tipe transaksi {$tipeTransaksi} sudah digunakan oleh baris lain.", null, 422);
    }
    $stmtCek->close();

    $stmt = $conn->prepare("
        UPDATE penomoran 
        SET nama_penomoran = ?, 
            tipe_transaksi = ?, 
            tipe_penomoran = ?, 
            digit_counter = ?, 
            format = ?, 
            updated_at = NOW(), 
            id_karyawan = ?
        WHERE id_nomor = ?
    ");
    $stmt->bind_param("ssiisii", $namaPenomoran, $tipeTransaksi, $tipePenomoran, $digitCounter, $format, $idKaryawan, $idNomor);
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    sendJson(true, 'Format penomoran berhasil diperbarui', [
        'id_nomor' => $idNomor,
        'nama_penomoran' => $namaPenomoran,
        'tipe_transaksi' => $tipeTransaksi,
        'format' => $format
    ]);

} catch (Exception $e) {
    sendJson(false, 'Gagal memperbarui penomoran: ' . $e->getMessage(), null, 500);
}
