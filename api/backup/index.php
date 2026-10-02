<?php
/**
 * API Backup: List History & Metrics
 * Mengembalikan riwayat backup database dengan filter, pagination, dan summary metrics.
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

// Auth Protection
$currentUser = apiAuth();

try {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 15)));
    $offset = ($page - 1) * $limit;
    $search = trim($_GET['search'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $scopeFilter = trim($_GET['scope'] ?? '');

    $whereClauses = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereClauses[] = "(b.nama_file LIKE ? OR b.email_backup LIKE ? OR b.keterangan LIKE ? OR b.id_karyawan LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ssss";
    }

    if (!empty($startDate)) {
        $whereClauses[] = "DATE(b.tanggal_backup) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $whereClauses[] = "DATE(b.tanggal_backup) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    if (!empty($scopeFilter)) {
        if ($scopeFilter === 'ALL') {
            $whereClauses[] = "b.scope = 'ALL'";
        } else {
            $whereClauses[] = "b.scope != 'ALL'";
        }
    }

    $whereSql = implode(" AND ", $whereClauses);

    // Hitung total data
    $countSql = "SELECT COUNT(*) as total FROM backup b WHERE {$whereSql}";
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalRows = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    // Query data backup dengan left join ke karyawan untuk informasi nama pelaksana
    $sql = "SELECT 
                b.id_backup,
                b.email_backup,
                b.tanggal_backup,
                b.id_karyawan,
                b.scope,
                b.keterangan,
                b.lokasi,
                b.nama_file,
                b.id_karyawan_restore,
                b.tanggal_restore,
                k_back.nama_karyawan AS nama_karyawan_backup,
                k_back.kode_karyawan AS kode_karyawan_backup,
                k_rest.nama_karyawan AS nama_karyawan_restore,
                k_rest.kode_karyawan AS kode_karyawan_restore
            FROM backup b
            LEFT JOIN karyawan k_back ON (b.id_karyawan = k_back.id_karyawan OR b.id_karyawan = k_back.kode_karyawan)
            LEFT JOIN karyawan k_rest ON (b.id_karyawan_restore = k_rest.id_karyawan)
            WHERE {$whereSql}
            ORDER BY b.id_backup DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $bindParams = $params;
    $bindParams[] = $limit;
    $bindParams[] = $offset;
    $bindTypes = $types . "ii";

    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $res = $stmt->get_result();

    $backupList = [];
    $backupDir = dirname(__DIR__, 2) . '/backups/';

    while ($row = $res->fetch_assoc()) {
        $filePath = $backupDir . $row['nama_file'];
        $fileExists = !empty($row['nama_file']) && file_exists($filePath);
        $fileSizeBytes = $fileExists ? filesize($filePath) : 0;
        
        $fileSizeFormatted = $fileSizeBytes >= 1048576 
            ? round($fileSizeBytes / 1048576, 2) . ' MB' 
            : ($fileSizeBytes >= 1024 ? round($fileSizeBytes / 1024, 2) . ' KB' : $fileSizeBytes . ' B');

        // Scope parsing
        $scopeRaw = $row['scope'] ?? 'ALL';
        $scopeCount = 0;
        $scopeList = [];
        if (strtoupper($scopeRaw) === 'ALL') {
            $isAll = true;
            $scopeText = 'Seluruh Tabel (ALL)';
        } else {
            $isAll = false;
            $tables = array_filter(array_map('trim', explode(',', $scopeRaw)));
            $scopeCount = count($tables);
            $scopeList = $tables;
            $scopeText = $scopeCount . ' Tabel Terpilih';
        }

        // Periksa nama operator backup
        $pelaksanaBackup = $row['nama_karyawan_backup'] ?: $row['id_karyawan'];
        $pelaksanaRestore = $row['nama_karyawan_restore'] ?: ($row['id_karyawan_restore'] ? 'User #' . $row['id_karyawan_restore'] : null);

        $backupList[] = [
            'id_backup'              => (int)$row['id_backup'],
            'email_backup'           => $row['email_backup'],
            'tanggal_backup'         => $row['tanggal_backup'],
            'tanggal_backup_format'  => date('d/m/Y H:i', strtotime($row['tanggal_backup'])),
            'id_karyawan'            => $row['id_karyawan'],
            'pelaksana_backup'       => $pelaksanaBackup,
            'scope'                  => $scopeRaw,
            'is_all_scope'           => $isAll,
            'scope_count'            => $scopeCount,
            'scope_tables'           => $scopeList,
            'scope_formatted'        => $scopeText,
            'keterangan'             => $row['keterangan'] ?: '-',
            'lokasi'                 => $row['lokasi'],
            'nama_file'              => $row['nama_file'],
            'file_exists'            => $fileExists,
            'file_size_bytes'        => $fileSizeBytes,
            'file_size_formatted'    => $fileSizeFormatted,
            'id_karyawan_restore'    => $row['id_karyawan_restore'] ? (int)$row['id_karyawan_restore'] : null,
            'pelaksana_restore'      => $pelaksanaRestore,
            'tanggal_restore'        => $row['tanggal_restore'],
            'tanggal_restore_format' => $row['tanggal_restore'] ? date('d/m/Y H:i:s', strtotime($row['tanggal_restore'])) : null,
            'is_restored'            => !empty($row['tanggal_restore'])
        ];
    }
    $stmt->close();

    // Hitung Metrics / Summary
    $metrics = [
        'total_backups'     => 0,
        'total_full_backup' => 0,
        'total_partial'     => 0,
        'total_restored'    => 0,
        'last_backup_date'  => null,
        'last_restore_date' => null
    ];

    $mRes = $conn->query("SELECT 
                            COUNT(*) AS total_backups,
                            SUM(CASE WHEN scope = 'ALL' THEN 1 ELSE 0 END) AS total_full,
                            SUM(CASE WHEN scope != 'ALL' THEN 1 ELSE 0 END) AS total_partial,
                            SUM(CASE WHEN tanggal_restore IS NOT NULL THEN 1 ELSE 0 END) AS total_restored,
                            MAX(tanggal_backup) AS last_backup,
                            MAX(tanggal_restore) AS last_restore
                          FROM backup");
    if ($mRes && $mRow = $mRes->fetch_assoc()) {
        $metrics['total_backups']     = (int)$mRow['total_backups'];
        $metrics['total_full_backup'] = (int)$mRow['total_full'];
        $metrics['total_partial']     = (int)$mRow['total_partial'];
        $metrics['total_restored']    = (int)$mRow['total_restored'];
        $metrics['last_backup_date']  = $mRow['last_backup'] ? date('d/m/Y H:i', strtotime($mRow['last_backup'])) : '-';
        $metrics['last_restore_date'] = $mRow['last_restore'] ? date('d/m/Y H:i', strtotime($mRow['last_restore'])) : '-';
    }

    $totalPages = ceil($totalRows / $limit);

    jsonResponse(true, 'Data riwayat backup berhasil dimuat.', [
        'data'       => $backupList,
        'metrics'    => $metrics,
        'pagination' => [
            'current_page' => $page,
            'limit'        => $limit,
            'total_rows'   => $totalRows,
            'total_pages'  => $totalPages
        ]
    ]);
} catch (Throwable $e) {
    jsonResponse(false, 'Gagal memuat data backup: ' . $e->getMessage(), null, 500);
}
