<?php
/**
 * API Backup: List Tables
 * Mengembalikan daftar tabel dalam database beserta estimasi jumlah baris dan ukuran data.
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

require_once __DIR__ . '/../middleware/auth.php';

// Auth Protection menggunakan standar middleware aplikasi
$currentUser = apiAuth();

try {
    $dbName = DB_NAME;
    $sql = "SELECT 
                TABLE_NAME AS table_name,
                TABLE_ROWS AS approx_rows,
                DATA_LENGTH AS data_bytes,
                INDEX_LENGTH AS index_bytes,
                (DATA_LENGTH + INDEX_LENGTH) AS total_bytes,
                TABLE_COMMENT AS comment,
                CREATE_TIME AS created_at
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_NAME ASC";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dbName);
    $stmt->execute();
    $res = $stmt->get_result();

    $tables = [];
    $totalSize = 0;
    $totalTables = 0;

    while ($row = $res->fetch_assoc()) {
        $bytes = (int)($row['total_bytes'] ?? 0);
        $totalSize += $bytes;
        $totalTables++;

        $sizeFormatted = $bytes >= 1048576 
            ? round($bytes / 1048576, 2) . ' MB' 
            : ($bytes >= 1024 ? round($bytes / 1024, 2) . ' KB' : $bytes . ' B');

        $tables[] = [
            'name'           => $row['table_name'],
            'rows'           => (int)$row['approx_rows'],
            'bytes'          => $bytes,
            'size_formatted' => $sizeFormatted,
            'comment'        => $row['comment'] ?: '-'
        ];
    }
    $stmt->close();

    $totalSizeFormatted = $totalSize >= 1048576 
        ? round($totalSize / 1048576, 2) . ' MB' 
        : ($totalSize >= 1024 ? round($totalSize / 1024, 2) . ' KB' : $totalSize . ' B');

    // Ambil default email SMTP untuk rekomendasi input backup
    $defaultSmtpEmail = '';
    $smtpRes = $conn->query("SELECT user_login FROM smtp_server WHERE aktif = 1 ORDER BY id_stmp ASC LIMIT 1");
    if ($smtpRes && $smtpRow = $smtpRes->fetch_assoc()) {
        $defaultSmtpEmail = $smtpRow['user_login'];
    }
    if (empty($defaultSmtpEmail)) {
        $defaultSmtpEmail = $currentUser['email'] ?? '';
    }

    jsonResponse(true, 'Daftar tabel berhasil dimuat.', [
        'database'             => $dbName,
        'total_tables'         => $totalTables,
        'total_size'           => $totalSize,
        'total_size_formatted' => $totalSizeFormatted,
        'default_email'        => $defaultSmtpEmail,
        'tables'               => $tables
    ]);
} catch (Throwable $e) {
    jsonResponse(false, 'Gagal memuat daftar tabel: ' . $e->getMessage(), null, 500);
}
