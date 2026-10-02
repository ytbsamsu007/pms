<?php
/**
 * API Master: Merk Barang CRUD Endpoint - PT Jaya Teknis
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

// GET: List / Search Merk
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (nama_merk LIKE ? OR kode_merk LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $types .= "ss";
    }

    $countSql = "SELECT COUNT(*) as total FROM merk_barang" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $sql = "SELECT id_merk, kode_merk, nama_merk, aktif FROM merk_barang"
        . $whereSql . " ORDER BY id_merk DESC LIMIT ? OFFSET ?";

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
            'id_merk' => (int)$row['id_merk'],
            'kode_merk' => $row['kode_merk'] ?? '',
            'nama_merk' => $row['nama_merk'] ?? '',
            'aktif' => (int)($row['aktif'] ?? 1)
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data merk barang berhasil diambil.', [
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

// Hanya Role ADMIN yang dapat mengubah
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data merk.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// POST: Tambah Merk Baru
if ($method === 'POST') {
    $namaMerk = trim($input['nama_merk'] ?? '');
    $kodeMerk = trim($input['kode_merk'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaMerk)) {
        jsonResponse(false, 'Nama merk wajib diisi.', null, 422);
    }

    if (empty($kodeMerk)) {
        $resCount = $conn->query("SELECT MAX(id_merk) as max_id FROM merk_barang");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeMerk = 'MRK' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO merk_barang (kode_merk, nama_merk, aktif) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $kodeMerk, $namaMerk, $aktif);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Merk barang berhasil ditambahkan.', ['id_merk' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan merk barang.', null, 500);
    }
}

// PUT: Update Merk
if ($method === 'PUT') {
    $idMerk = isset($input['id_merk']) ? (int)$input['id_merk'] : 0;
    $namaMerk = trim($input['nama_merk'] ?? '');
    $kodeMerk = trim($input['kode_merk'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if ($idMerk <= 0 || empty($namaMerk)) {
        jsonResponse(false, 'ID dan Nama merk wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE merk_barang SET kode_merk = ?, nama_merk = ?, aktif = ? WHERE id_merk = ?");
    $stmt->bind_param("ssii", $kodeMerk, $namaMerk, $aktif, $idMerk);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data merk berhasil diperbarui.', ['id_merk' => $idMerk], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui merk.', null, 500);
    }
}

// DELETE: Hapus Merk
if ($method === 'DELETE') {
    $idMerk = isset($input['id_merk']) ? (int)$input['id_merk'] : (int)($_GET['id'] ?? 0);

    if ($idMerk <= 0) {
        jsonResponse(false, 'ID merk tidak valid.', null, 422);
    }

    // --- Pengecekan Relasi Database ---
    $q = "SELECT COUNT(*) as count FROM barang WHERE id_merk = ?";
    $stmtRel = $conn->prepare($q);
    $inUse = false;
    $count = 0;
    if ($stmtRel) {
        $stmtRel->bind_param("i", $idMerk);
        $stmtRel->execute();
        $count = $stmtRel->get_result()->fetch_assoc()['count'] ?? 0;
        $stmtRel->close();
        
        if ($count > 0) {
            $inUse = true;
        }
    }
    
    if ($inUse) {
        $msg = "Gagal menghapus! Merk ini sedang digunakan pada $count Master Barang.";
        jsonResponse(false, $msg, null, 409);
    }
    // -----------------------------------

    $stmt = $conn->prepare("DELETE FROM merk_barang WHERE id_merk = ?");
    $stmt->bind_param("i", $idMerk);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Merk berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus merk. Terjadi kesalahan server.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
