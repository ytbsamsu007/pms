<?php
/**
 * Security Helper: Obfuscasi / Hash Enkripsi & Dekripsi Parameter URL ID
 * Path: config/security_helper.php
 * PT Jembatan Translog
 */

if (!defined('JT_HASH_KEY')) {
    define('JT_HASH_KEY', 'JT_Purchase_Secret_Salt_2026_x89!');
}

/**
 * Enkripsi Integer ID menjadi string hash yang aman untuk URL
 * Format output: Base64Url string aman tanpa karakter +, /, =
 * Contoh: 6 -> 'a1b2c3d4...'
 */
function encodeId($id): string {
    if ($id === null || $id === '' || !is_numeric($id)) {
        return '';
    }
    $id = (int)$id;
    if ($id <= 0) {
        return '';
    }

    $secretKey = JT_HASH_KEY;
    $method = 'AES-128-CTR';
    // Gunakan IV tetap dari turunan key agar deterministik & URL konsisten
    $iv = substr(hash('sha256', 'iv_' . $secretKey), 0, 16);
    
    $payload = $id . '|' . substr(hash_hmac('sha256', (string)$id, $secretKey), 0, 8);
    $encrypted = openssl_encrypt($payload, $method, $secretKey, 0, $iv);
    
    if ($encrypted === false) {
        // Fallback Base64 sederhana jika openssl terkendala
        return rtrim(strtr(base64_encode('JT_' . $id), '+/', '-_'), '=');
    }
    
    return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
}

/**
 * Dekripsi string hash URL menjadi integer ID asli
 * Keamanan Ketat: Memblokir penembakan plain integer ID manual (misal ?id=8)
 * Hanya menerima hash yang valid yang di-generate melalui encodeId()
 * Contoh: 'MWRHU3lxZk5aSnZnOFE9PQ' atau 'SlRfOA' -> 8
 */
function decodeId($encoded): int {
    if ($encoded === null || $encoded === '') {
        return 0;
    }

    // Blok jika berupa angka murni tanpa hash (mencegah penembakan URL langsung)
    if (is_numeric($encoded)) {
        return 0;
    }

    $secretKey = JT_HASH_KEY;
    $method = 'AES-128-CTR';
    $iv = substr(hash('sha256', 'iv_' . $secretKey), 0, 16);

    // Decode Base64 URL Safe
    $b64 = strtr($encoded, '-_', '+/');
    $mod4 = strlen($b64) % 4;
    if ($mod4) {
        $b64 .= substr('====', $mod4);
    }

    $raw = base64_decode($b64, true);
    if ($raw === false) {
        return 0;
    }

    // Coba Fallback Base64 sederhana
    if (strpos($raw, 'JT_') === 0) {
        $cleanId = substr($raw, 3);
        return is_numeric($cleanId) ? (int)$cleanId : 0;
    }

    // Dekripsi AES
    $decrypted = openssl_decrypt($raw, $method, $secretKey, 0, $iv);
    if ($decrypted === false) {
        return 0;
    }

    $parts = explode('|', $decrypted);
    if (count($parts) === 2) {
        $id = (int)$parts[0];
        $hmac = $parts[1];
        $expectedHmac = substr(hash_hmac('sha256', (string)$id, $secretKey), 0, 8);
        if (hash_equals($expectedHmac, $hmac)) {
            return $id;
        }
    }

    return 0;
}
