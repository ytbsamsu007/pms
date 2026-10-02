<?php
/**
 * API Master: Kategori Barang CRUD Endpoint - PT Jaya Teknis
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

// GET: List / Search Kategori
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (nama_kategori LIKE ? OR kode_kategori LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $types .= "ss";
    }

    // Total records
    $countSql = "SELECT COUNT(*) as total FROM kategori_barang" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $sql = "SELECT id_kategori, kode_kategori, nama_kategori, aktif FROM kategori_barang"
        . $whereSql . " ORDER BY id_kategori DESC LIMIT ? OFFSET ?";

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
            'id_kategori' => (int)$row['id_kategori'],
            'kode_kategori' => $row['kode_kategori'] ?? '',
            'nama_kategori' => $row['nama_kategori'] ?? '',
            'aktif' => (int)($row['aktif'] ?? 1)
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data kategori barang berhasil diambil.', [
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
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data kategori.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// POST: Tambah Kategori Baru
if ($method === 'POST') {
    $namaKategori = trim($input['nama_kategori'] ?? '');
    $kodeKategori = trim($input['kode_kategori'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaKategori)) {
        jsonResponse(false, 'Nama kategori wajib diisi.', null, 422);
    }

    if (empty($kodeKategori)) {
        $resCount = $conn->query("SELECT MAX(id_kategori) as max_id FROM kategori_barang");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeKategori = 'KAT' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO kategori_barang (kode_kategori, nama_kategori, aktif) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $kodeKategori, $namaKategori, $aktif);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Kategori barang berhasil ditambahkan.', ['id_kategori' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan kategori barang.', null, 500);
    }
}

// PUT: Update Kategori
if ($method === 'PUT') {
    $idKategori = isset($input['id_kategori']) ? (int)$input['id_kategori'] : 0;
    $namaKategori = trim($input['nama_kategori'] ?? '');
    $kodeKategori = trim($input['kode_kategori'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if ($idKategori <= 0 || empty($namaKategori)) {
        jsonResponse(false, 'ID dan Nama kategori wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE kategori_barang SET kode_kategori = ?, nama_kategori = ?, aktif = ? WHERE id_kategori = ?");
    $stmt->bind_param("ssii", $kodeKategori, $namaKategori, $aktif, $idKategori);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data kategori berhasil diperbarui.', ['id_kategori' => $idKategori], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui kategori.', null, 500);
    }
}

// DELETE: Hapus Kategori
if ($method === 'DELETE') {
    $idKategori = isset($input['id_kategori']) ? (int)$input['id_kategori'] : (int)($_GET['id'] ?? 0);

    if ($idKategori <= 0) {
        jsonResponse(false, 'ID kategori tidak valid.', null, 422);
    }

    // --- Pengecekan Relasi Database ---
    $q = "SELECT COUNT(*) as count FROM barang WHERE id_kategori = ?";
    $stmtRel = $conn->prepare($q);
    $inUse = false;
    $count = 0;
    if ($stmtRel) {
        $stmtRel->bind_param("i", $idKategori);
        $stmtRel->execute();
        $count = $stmtRel->get_result()->fetch_assoc()['count'] ?? 0;
        $stmtRel->close();
        
        if ($count > 0) {
            $inUse = true;
        }
    }
    
    if ($inUse) {
        $msg = "Gagal menghapus! Kategori ini sedang digunakan pada $count Master Barang.";
        jsonResponse(false, $msg, null, 409);
    }
    // -----------------------------------

    $stmt = $conn->prepare("DELETE FROM kategori_barang WHERE id_kategori = ?");
    $stmt->bind_param("i", $idKategori);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Kategori berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus kategori. Terjadi kesalahan server.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
