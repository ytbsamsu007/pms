<?php
/**
 * API Upload Image - PT Jaya Teknis
 * Path: api/master/upload_image.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan POST.', null, 405);
}

// Deteksi berkas dari field 'logo', 'image', 'foto', atau 'file'
$file = $_FILES['logo'] ?? $_FILES['image'] ?? $_FILES['foto'] ?? $_FILES['file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'Tidak ada berkas gambar yang diunggah atau terjadi kesalahan saat upload.', null, 400);
}

$type = trim($_POST['type'] ?? $_POST['subfolder'] ?? 'barang'); // 'barang' | 'profile' | 'company'
$isCompanyLogo = ($type === 'profile' || $type === 'company' || isset($_FILES['logo']));

// Direktori target
$subDir = $isCompanyLogo ? 'company/' : 'barang/';
$baseUploadDir = __DIR__ . '/../../images/uploads/' . $subDir;

if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0777, true);
}

// Validasi Ekstensi & MIME
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// Aturan Khusus Logo Perusahaan
if ($isCompanyLogo) {
    // 1. Format Wajib .PNG
    if ($ext !== 'png' || $mimeType !== 'image/png') {
        jsonResponse(false, 'Format berkas logo tidak valid. Harap upload gambar berekstensi .PNG.', null, 422);
    }

    // 2. Ukuran Maksimal 500 KB
    $maxSizeLogo = 500 * 1024;
    if ($file['size'] > $maxSizeLogo) {
        $sizeKb = round($file['size'] / 1024, 1);
        jsonResponse(false, "Ukuran berkas logo ({$sizeKb} KB) melebihi batas maksimal 500 KB.", null, 422);
    }

    // 3. Rasio Aspek Wajib 1:1 (Persegi)
    $imgInfo = @getimagesize($file['tmp_name']);
    if (!$imgInfo) {
        jsonResponse(false, 'Berkas gambar rusak atau tidak dapat dibaca.', null, 422);
    }
    $w = $imgInfo[0];
    $h = $imgInfo[1];
    $ratio = $w / max(1, $h);
    if ($ratio < 0.95 || $ratio > 1.05) {
        jsonResponse(false, "Logo perusahaan harus memiliki rasio 1:1 (persegi). Dimensi berkas Anda: {$w}×{$h} px.", null, 422);
    }
} else {
    // Validasi Umum Barang / Gambar Lainnya
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        jsonResponse(false, 'Ukuran gambar maksimal adalah 10MB.', null, 422);
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
    if (!in_array($mimeType, $allowedMimes)) {
        jsonResponse(false, 'Format berkas tidak valid. Harap upload gambar berekstensi JPG, PNG, atau WEBP.', null, 422);
    }
}

$prefix = $isCompanyLogo ? 'company_logo_' : 'brg_';
$filename = $prefix . time() . '_' . bin2hex(random_bytes(3)) . '.' . ($ext ?: 'png');
$targetPath = $baseUploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativeUrl = 'images/uploads/' . $subDir . $filename;
    jsonResponse(true, 'Gambar berhasil diunggah.', [
        'filename' => $filename,
        'url' => $relativeUrl,
        'file_path' => $relativeUrl
    ], 200);
} else {
    jsonResponse(false, 'Gagal memindahkan berkas yang diunggah ke folder target.', null, 500);
}
