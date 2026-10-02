<?php
/**
 * API Master: Site CRUD Endpoint - PT Jaya Teknik
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// -------------------------------------------------------------
// GET: Mengambil Data Site (List / Detail)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $penyimpananStok = isset($_GET['penyimpanan_stok']) && is_numeric($_GET['penyimpanan_stok']) ? (int)$_GET['penyimpanan_stok'] : null;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 50;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (s.nama_site LIKE ? OR s.kode_site LIKE ? OR s.jenis_site LIKE ? OR s.alamat LIKE ? OR kry.nama_karyawan LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 5; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    if ($penyimpananStok !== null) {
        $whereSql .= " AND s.penyimpanan_stok = ?";
        $params[] = $penyimpananStok;
        $types .= "i";
    }

    $countSql = "SELECT COUNT(*) as total FROM site s LEFT JOIN karyawan kry ON s.id_karyawan_headof = kry.id_karyawan" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $sql = "SELECT s.id_site, s.kode_site, s.nama_site, s.jenis_site, s.alamat, s.alamat_gps, s.no_hp, s.id_karyawan_headof, s.penyimpanan_stok,
                   kry.nama_karyawan AS kepala_site, kry.email AS email_kepala_site, kry.no_handphone AS hp_kepala_site
            FROM site s
            LEFT JOIN karyawan kry ON s.id_karyawan_headof = kry.id_karyawan"
            . $whereSql . " ORDER BY s.id_site DESC LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'id_site' => (int)$row['id_site'],
            'kode_site' => $row['kode_site'] ?? '',
            'nama_site' => $row['nama_site'] ?? '',
            'jenis_site' => $row['jenis_site'] ?? 'Lain-lain',
            'alamat' => $row['alamat'] ?? '-',
            'alamat_gps' => $row['alamat_gps'] ?? '-',
            'no_hp' => $row['no_hp'] ?? '-',
            'id_karyawan_headof' => $row['id_karyawan_headof'] ? (int)$row['id_karyawan_headof'] : null,
            'penyimpanan_stok' => (int)($row['penyimpanan_stok'] ?? 0),
            'kepala_site' => $row['kepala_site'] ?? '-',
            'email_kepala_site' => $row['email_kepala_site'] ?? '-',
            'hp_kepala_site' => $row['hp_kepala_site'] ?? '-'
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data site berhasil diambil.', [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ], 200);
}

// -------------------------------------------------------------
// Hanya Role ADMIN yang diizinkan melakukan CREATE, UPDATE, DELETE
// -------------------------------------------------------------
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data site.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Site Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaSite = trim($input['nama_site'] ?? '');
    $kodeSite = trim($input['kode_site'] ?? '');
    $jenisSite = trim($input['jenis_site'] ?? 'Bengkel');
    $alamat = trim($input['alamat'] ?? '');
    $alamatGps = trim($input['alamat_gps'] ?? '');
    $noHp = trim($input['no_hp'] ?? '');
    $idHeadOf = !empty($input['id_karyawan_headof']) ? (int)$input['id_karyawan_headof'] : null;
    $penyimpananStok = isset($input['penyimpanan_stok']) ? (int)$input['penyimpanan_stok'] : 0;

    if (empty($namaSite)) {
        jsonResponse(false, 'Nama site / galangan wajib diisi.', null, 422);
    }

    if (empty($kodeSite)) {
        $resCount = $conn->query("SELECT MAX(id_site) as max_id FROM site");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeSite = 'SIT' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO site (kode_site, nama_site, jenis_site, alamat, alamat_gps, no_hp, id_karyawan_headof, penyimpanan_stok) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssii", $kodeSite, $namaSite, $jenisSite, $alamat, $alamatGps, $noHp, $idHeadOf, $penyimpananStok);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Site berhasil ditambahkan.', ['id_site' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan site.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Site
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idSite = isset($input['id_site']) ? (int)$input['id_site'] : 0;
    $namaSite = trim($input['nama_site'] ?? '');
    $kodeSite = trim($input['kode_site'] ?? '');
    $jenisSite = trim($input['jenis_site'] ?? 'Bengkel');
    $alamat = trim($input['alamat'] ?? '');
    $alamatGps = trim($input['alamat_gps'] ?? '');
    $noHp = trim($input['no_hp'] ?? '');
    $idHeadOf = !empty($input['id_karyawan_headof']) ? (int)$input['id_karyawan_headof'] : null;
    $penyimpananStok = isset($input['penyimpanan_stok']) ? (int)$input['penyimpanan_stok'] : 0;

    if ($idSite <= 0 || empty($namaSite)) {
        jsonResponse(false, 'ID site dan nama site wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE site SET kode_site = ?, nama_site = ?, jenis_site = ?, alamat = ?, alamat_gps = ?, no_hp = ?, id_karyawan_headof = ?, penyimpanan_stok = ? 
                            WHERE id_site = ?");
    $stmt->bind_param("ssssssiii", $kodeSite, $namaSite, $jenisSite, $alamat, $alamatGps, $noHp, $idHeadOf, $penyimpananStok, $idSite);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data site berhasil diperbarui.', ['id_site' => $idSite], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui data site.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Site
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idSite = isset($input['id_site']) ? (int)$input['id_site'] : (int)($_GET['id'] ?? 0);

    if ($idSite <= 0) {
        jsonResponse(false, 'ID site tidak valid.', null, 422);
    }

    // --- Pengecekan Relasi Database ---
    $relations = [
        ['table' => 'request_order', 'col' => 'id_site', 'label' => 'Request Order'],
        ['table' => 'karyawan', 'col' => 'id_site', 'label' => 'Master Karyawan'],
        ['table' => 'mutasi_order', 'col' => 'id_site_asal', 'label' => 'Mutasi Order (Asal)'],
        ['table' => 'mutasi_order', 'col' => 'id_site_tujuan', 'label' => 'Mutasi Order (Tujuan)']
    ];
    
    $inUse = [];
    foreach ($relations as $rel) {
        $q = "SELECT COUNT(*) as count FROM {$rel['table']} WHERE {$rel['col']} = ?";
        $stmtRel = $conn->prepare($q);
        if ($stmtRel) {
            $stmtRel->bind_param("i", $idSite);
            $stmtRel->execute();
            $count = $stmtRel->get_result()->fetch_assoc()['count'] ?? 0;
            $stmtRel->close();
            
            if ($count > 0) {
                $inUse[] = $count . " data " . $rel['label'];
            }
        }
    }
    
    if (count($inUse) > 0) {
        $msg = "Gagal menghapus! Site ini sedang digunakan pada: " . implode(", ", $inUse) . ".";
        jsonResponse(false, $msg, null, 409); // 409 Conflict
    }
    // -----------------------------------

    $stmt = $conn->prepare("DELETE FROM site WHERE id_site = ?");
    $stmt->bind_param("i", $idSite);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Site berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus site. Terjadi kesalahan pada server.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
