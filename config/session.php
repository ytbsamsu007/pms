<?php
/**
 * Session Handler & Auth Utilities - PT Jaya Teknis
 */

require_once __DIR__ . '/config.php';

// Konfigurasi session yang aman & kompatibel dengan Cloudflare Tunnel & Mobile Browser (Android Chrome, iOS Safari)
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    );

    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 hari
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => $isHttps ? 'None' : 'Lax'
    ]);
    
    session_start();
}

/**
 * Cek apakah session aktif dan terautentikasi
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Mendapatkan data user yang sedang login
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nama' => $_SESSION['nama'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'id_karyawan' => $_SESSION['id_karyawan'] ?? null,
        'kode_karyawan' => $_SESSION['kode_karyawan'] ?? '',
        'id_jabatan' => $_SESSION['id_jabatan'] ?? null,
        'nama_jabatan' => $_SESSION['nama_jabatan'] ?? '',
        'level_jabatan' => $_SESSION['level_jabatan'] ?? null,
        'id_divisi' => $_SESSION['id_divisi'] ?? null,
        'nama_divisi' => $_SESSION['nama_divisi'] ?? '',
        'id_site' => $_SESSION['id_site'] ?? null,
        'nama_site' => $_SESSION['nama_site'] ?? '',
        'api_token' => $_SESSION['api_token'] ?? ''
    ];
}

/**
 * Guard untuk halaman Admin UI (Redirect ke login jika belum login)
 * Terintegrasi penuh secara dinamis dengan Hak Akses Tabel `menu_level` di Database.
 */
function requireAuth(array $allowedRoles = []): array {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    
    $user = getCurrentUser();
    
    // 1. Super Admin (ROLE_ADMIN) selalu memiliki izin akses penuh ke seluruh halaman
    if ($user['role'] === ROLE_ADMIN) {
        return $user;
    }

    // 2. Cek Izin Dinamis dari Database (Tabel menu_level) berdasarkan id_jabatan user
    global $conn;
    if (!isset($conn) || !$conn) {
        require_once __DIR__ . '/koneksi.php';
    }

    if (isset($conn) && !empty($user['id_jabatan'])) {
        $userJabId = (int)$user['id_jabatan'];
        
        // Dapatkan path halaman yang sedang diakses (misal: /admin/pages/vendor/index.php)
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
        $cleanPath = '/' . ltrim(str_replace('\\', '/', $scriptPath), '/');
        
        $posAdmin = strpos($cleanPath, '/admin/');
        $pagePath = ($posAdmin !== false) ? substr($cleanPath, $posAdmin) : $cleanPath;

        // Query status izin menu untuk jabatan ini
        $stmtM = $conn->prepare("SELECT akses, terlihat FROM menu_level 
                                WHERE id_jabatan = ? 
                                  AND link IS NOT NULL 
                                  AND link != '#' 
                                  AND link != '' 
                                  AND (link = ? OR ? LIKE CONCAT('%', link)) 
                                LIMIT 1");
        if ($stmtM) {
            $stmtM->bind_param("iss", $userJabId, $pagePath, $pagePath);
            $stmtM->execute();
            $resM = $stmtM->get_result();
            if ($resM && $resM->num_rows > 0) {
                $rowM = $resM->fetch_assoc();
                $stmtM->close();
                if ((int)$rowM['akses'] === 1) {
                    return $user; // Izin DIBERIKAN secara dinamis dari Manajemen Menu!
                } else {
                    http_response_code(403);
                    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                            <h2>403 - Akses Ditolak</h2>
                            <p>Hak akses ke halaman ini untuk jabatan Anda (<strong>" . htmlspecialchars($user['nama_jabatan'] ?: $user['role']) . "</strong>) telah dinonaktifkan oleh Administrator.</p>
                            <a href='" . BASE_URL . "/admin/dashboard.php' style='color:#0d6efd;'>Kembali ke Dashboard</a>
                          </div>";
                    exit;
                }
            }
            $stmtM->close();
        }
    }
    
    // 3. Fallback jika halaman belum terdaftar di menu_level
    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h2>403 - Akses Ditolak</h2>
                <p>Role Anda (<strong>" . htmlspecialchars($user['role']) . "</strong>) tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href='" . BASE_URL . "/admin/dashboard.php' style='color:#0d6efd;'>Kembali ke Dashboard</a>
              </div>";
        exit;
    }
    
    return $user;
}
