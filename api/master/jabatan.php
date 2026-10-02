<?php
/**
 * API Master: Jabatan CRUD Endpoint - PT Jaya Teknik
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST with _method override for PUT/DELETE
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// -------------------------------------------------------------
// GET: Mengambil Data Jabatan (List / Detail)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $divisiId = isset($_GET['divisi_id']) && is_numeric($_GET['divisi_id']) ? (int)$_GET['divisi_id'] : null;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 50;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (j.nama_jabatan LIKE ? OR j.kode_jabatan LIKE ? OR d.nama_divisi LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 3; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    if ($divisiId !== null) {
        $whereSql .= " AND j.id_divisi = ?";
        $params[] = $divisiId;
        $types .= "i";
    }

    // 1. Total records
    $countSql = "SELECT COUNT(*) as total FROM jabatan j LEFT JOIN divisi d ON j.id_divisi = d.id_divisi" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    // 2. Query Data (Urutkan dari level 1 tertinggi ke bawah)
    $sql = "SELECT j.id_jabatan, j.kode_jabatan, j.nama_jabatan, j.id_divisi, j.level,
                   d.nama_divisi, d.kode_divisi
            FROM jabatan j
            LEFT JOIN divisi d ON j.id_divisi = d.id_divisi"
            . $whereSql . " ORDER BY j.level ASC, j.id_jabatan ASC LIMIT ? OFFSET ?";

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
            'id_jabatan' => (int)$row['id_jabatan'],
            'kode_jabatan' => $row['kode_jabatan'] ?? '',
            'nama_jabatan' => $row['nama_jabatan'] ?? '',
            'id_divisi' => $row['id_divisi'] ? (int)$row['id_divisi'] : null,
            'nama_divisi' => $row['nama_divisi'] ?? 'Semua Divisi / Umum',
            'kode_divisi' => $row['kode_divisi'] ?? '-',
            'level' => isset($row['level']) ? (int)$row['level'] : 3
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data master jabatan berhasil diambil.', [
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
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data jabatan.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Jabatan Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaJabatan = trim($input['nama_jabatan'] ?? '');
    $kodeJabatan = trim($input['kode_jabatan'] ?? '');
    $idDivisi = !empty($input['id_divisi']) ? (int)$input['id_divisi'] : null;
    $level = isset($input['level']) && is_numeric($input['level']) ? (int)$input['level'] : 3;

    if (empty($namaJabatan)) {
        jsonResponse(false, 'Nama jabatan wajib diisi.', null, 422);
    }

    // Auto-generate kode_jabatan format JB-001 jika kosong
    if (empty($kodeJabatan)) {
        $resCount = $conn->query("SELECT MAX(id_jabatan) as max_id FROM jabatan");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeJabatan = 'JB-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO jabatan (kode_jabatan, nama_jabatan, id_divisi, level) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $kodeJabatan, $namaJabatan, $idDivisi, $level);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Jabatan baru berhasil ditambahkan.', ['id_jabatan' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan jabatan.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Jabatan
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idJabatan = isset($input['id_jabatan']) ? (int)$input['id_jabatan'] : 0;
    $namaJabatan = trim($input['nama_jabatan'] ?? '');
    $kodeJabatan = trim($input['kode_jabatan'] ?? '');
    $idDivisi = !empty($input['id_divisi']) ? (int)$input['id_divisi'] : null;
    $level = isset($input['level']) && is_numeric($input['level']) ? (int)$input['level'] : 3;

    if ($idJabatan <= 0 || empty($namaJabatan)) {
        jsonResponse(false, 'ID jabatan dan nama jabatan wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE jabatan SET kode_jabatan = ?, nama_jabatan = ?, id_divisi = ?, level = ? WHERE id_jabatan = ?");
    $stmt->bind_param("ssiii", $kodeJabatan, $namaJabatan, $idDivisi, $level, $idJabatan);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data jabatan berhasil diperbarui.', ['id_jabatan' => $idJabatan], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui data jabatan.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Jabatan
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idJabatan = isset($input['id_jabatan']) ? (int)$input['id_jabatan'] : (int)($_GET['id'] ?? 0);

    if ($idJabatan <= 0) {
        jsonResponse(false, 'ID jabatan tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("DELETE FROM jabatan WHERE id_jabatan = ?");
    $stmt->bind_param("i", $idJabatan);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Jabatan berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus jabatan. Data mungkin terkait dengan entitas lain.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
