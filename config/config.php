<?php
/**
 * Konfigurasi Aplikasi & Helper Terpusat - PT Jaya Teknis
 */

// Set Default Timezone Aplikasi (WITA / UTC+8 - PT Jaya Teknis)
date_default_timezone_set('Asia/Makassar');

// Base URL definition (Kompatibel dengan Localhost & Cloudflare Tunnel HTTPS)
if (!defined('BASE_URL')) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    );
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Cari root direktori proyek jika berada dalam subfolder
    $basePath = preg_replace('#/(admin|api|config|pages|styles|components).*#', '', $scriptDir);
    $basePath = rtrim($basePath, '/');
    define('BASE_URL', $protocol . $host . $basePath);
}

// Role Constants
define('ROLE_ADMIN', 'ADMIN');
define('ROLE_MEKANIK', 'MEKANIK');
define('ROLE_LOGISTIK', 'LOGISTIK');
define('ROLE_PURCHASING', 'PURCHASING');
define('ROLE_FINANCE', 'FINANCE');
define('ROLE_MANAGER', 'MANAGER');

// Status Request Order Constants (sesuai PRD dan kompatibel dengan DB enum)
define('STATUS_DRAFT', 'DRAFT');
define('STATUS_SUBMITTED', 'TERKIRIM'); // DB ENUM 'TERKIRIM' / display: Submitted
define('STATUS_APPROVED', 'DISETUJUI');   // DB ENUM 'DISETUJUI' / display: Ready for PO
define('STATUS_REJECTED', 'TIDAK DISETUJUI'); // DB ENUM 'TIDAK DISETUJUI' / display: Ditolak
define('STATUS_CANCELLED', 'BATAL');     // DB ENUM 'BATAL' / display: Dibatalkan

/**
 * Helper: Mengambil identitas profile perusahaan dari database
 */
function getCompanyProfile(?mysqli $dbConn = null): array {
    global $conn;
    if (!$dbConn && !$conn) {
        @require_once __DIR__ . '/koneksi.php';
    }
    $db = $dbConn ?? $conn ?? null;
    
    $default = [
        'id_perusahaan' => 1,
        'nama' => 'PT Jaya Teknik',
        'telepon1' => '',
        'whatsapp' => '',
        'email' => '',
        'alamat' => '',
        'kota' => '',
        'provinsi' => '',
        'npwp' => '',
        'KLU' => '',
        'NITKU' => '',
        'timezone' => 'Asia/Makassar',
        'pajak12' => 1,
        'picture' => ''
    ];
    
    if (!$db) return $default;
    
    try {
        $res = $db->query("SELECT * FROM profile ORDER BY id_perusahaan ASC LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            foreach ($row as $k => $v) {
                if ($v !== null) {
                    $default[$k] = $v;
                }
            }
            return $default;
        }
    } catch (Exception $e) {
        // Fallback
    }
    
    return $default;
}

/**
 * Helper: Mengirim response JSON standar dan menghentikan eksekusi
 */
function jsonResponse(bool $success, string $message, $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    $payload = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $payload['data'] = $data;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Helper: Sanitasi string input
 */
function sanitizeInput(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper: Mendapatkan Bearer Token dari Header HTTP Request
 */
function getBearerToken(): ?string {
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    
    if (!empty($headers) && preg_match('/Bearer\s(\S+)/i', $headers, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Helper: Badge status CSS class & format text
 */
function getStatusBadge(string $status): array {
    switch (strtoupper($status)) {
        case 'DRAFT':
            return ['class' => 'bg-secondary', 'label' => 'Draft'];
        case 'TERKIRIM':
        case 'SUBMITTED':
            return ['class' => 'bg-info text-dark', 'label' => 'Submitted / Menunggu Logistik'];
        case 'PROCESSING_LOGISTIC':
            return ['class' => 'bg-primary', 'label' => 'Diproses Logistik'];
        case 'DISETUJUI':
        case 'READY_FOR_PO':
            return ['class' => 'bg-success', 'label' => 'Ready for PO'];
        case 'CONVERTED_TO_PO':
            return ['class' => 'bg-dark', 'label' => 'PO Terbit'];
        case 'TIDAK DISETUJUI':
        case 'REJECTED':
            return ['class' => 'bg-danger', 'label' => 'Ditolak'];
        case 'BATAL':
        case 'CANCELLED':
            return ['class' => 'bg-secondary', 'label' => 'Dibatalkan'];
        default:
            return ['class' => 'bg-secondary', 'label' => $status];
    }
}

// Security Helper: Obfuscasi / Hash URL Parameters
require_once __DIR__ . '/security_helper.php';
