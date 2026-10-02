<?php
/**
 * API Master: Divisi CRUD Endpoint - PT Jaya Teknis
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

// GET: List / Search Divisi
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (d.nama_divisi LIKE ? OR d.kode_divisi LIKE ? OR kry.nama_karyawan LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $types .= "sss";
    }

    $countSql = "SELECT COUNT(*) as total FROM divisi d LEFT JOIN karyawan kry ON d.id_karyawan_headof = kry.id_karyawan" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $sql = "SELECT d.id_divisi, d.kode_divisi, d.nama_divisi, d.level, d.id_karyawan_headof,
                   kry.nama_karyawan AS nama_headof
            FROM divisi d
            LEFT JOIN karyawan kry ON d.id_karyawan_headof = kry.id_karyawan"
        . $whereSql . " ORDER BY d.id_divisi ASC LIMIT ? OFFSET ?";

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
            'id_divisi' => (int)$row['id_divisi'],
            'kode_divisi' => $row['kode_divisi'] ?? '',
            'nama_divisi' => $row['nama_divisi'] ?? '',
            'level' => (int)($row['level'] ?? 1),
            'id_karyawan_headof' => $row['id_karyawan_headof'] ? (int)$row['id_karyawan_headof'] : null,
            'nama_headof' => $row['nama_headof'] ?? '-'
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data divisi berhasil diambil.', [
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

// Hanya Role ADMIN yang dapat mengubah master divisi
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data divisi.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// POST: Tambah Divisi Baru
if ($method === 'POST') {
    $namaDivisi = trim($input['nama_divisi'] ?? '');
    $kodeDivisi = trim($input['kode_divisi'] ?? '');
    $level = isset($input['level']) ? (int)$input['level'] : 1;
    $idHeadOf = !empty($input['id_karyawan_headof']) ? (int)$input['id_karyawan_headof'] : null;

    if (empty($namaDivisi)) {
        jsonResponse(false, 'Nama divisi wajib diisi.', null, 422);
    }

    if (empty($kodeDivisi)) {
        $resCount = $conn->query("SELECT MAX(id_divisi) as max_id FROM divisi");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeDivisi = 'DIV' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO divisi (kode_divisi, nama_divisi, level, id_karyawan_headof) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $kodeDivisi, $namaDivisi, $level, $idHeadOf);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Divisi berhasil ditambahkan.', ['id_divisi' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan divisi.', null, 500);
    }
}

// PUT: Update Divisi
if ($method === 'PUT') {
    $idDivisi = isset($input['id_divisi']) ? (int)$input['id_divisi'] : 0;
    $namaDivisi = trim($input['nama_divisi'] ?? '');
    $kodeDivisi = trim($input['kode_divisi'] ?? '');
    $level = isset($input['level']) ? (int)$input['level'] : 1;
    $idHeadOf = !empty($input['id_karyawan_headof']) ? (int)$input['id_karyawan_headof'] : null;

    if ($idDivisi <= 0 || empty($namaDivisi)) {
        jsonResponse(false, 'ID dan Nama divisi wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE divisi SET kode_divisi = ?, nama_divisi = ?, level = ?, id_karyawan_headof = ? WHERE id_divisi = ?");
    $stmt->bind_param("ssiii", $kodeDivisi, $namaDivisi, $level, $idHeadOf, $idDivisi);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data divisi berhasil diperbarui.', ['id_divisi' => $idDivisi], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui divisi.', null, 500);
    }
}

// DELETE: Hapus Divisi
if ($method === 'DELETE') {
    $idDivisi = isset($input['id_divisi']) ? (int)$input['id_divisi'] : (int)($_GET['id'] ?? 0);

    if ($idDivisi <= 0) {
        jsonResponse(false, 'ID divisi tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("DELETE FROM divisi WHERE id_divisi = ?");
    $stmt->bind_param("i", $idDivisi);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Divisi berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus divisi. Data mungkin masih digunakan pada master karyawan.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
