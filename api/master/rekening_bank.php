<?php
/**
 * API Master: Rekening Bank CRUD Endpoint - PT Jaya Teknis
 * Khusus Role: FINANCE, ADMIN, MANAGER
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
// GET: Mengambil Data Rekening Bank (List / Search / Dropdown)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $isAll = isset($_GET['all']) && $_GET['all'] == '1';
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (rb.nama_bank LIKE ? OR rb.atasnama_rekening LIKE ? OR rb.no_rekening LIKE ? OR k.nama_karyawan LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $types .= "ssss";
    }

    $countSql = "SELECT COUNT(*) as total FROM rekening_bank rb LEFT JOIN karyawan k ON rb.id_karyawan = k.id_karyawan" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    if ($isAll) {
        $sql = "SELECT rb.id_bank, rb.nama_bank, rb.atasnama_rekening, rb.no_rekening, rb.created_at, rb.id_karyawan,
                       k.nama_karyawan, k.kode_karyawan
                FROM rekening_bank rb
                LEFT JOIN karyawan k ON rb.id_karyawan = k.id_karyawan"
                . $whereSql . " ORDER BY rb.id_bank ASC";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    } else {
        $sql = "SELECT rb.id_bank, rb.nama_bank, rb.atasnama_rekening, rb.no_rekening, rb.created_at, rb.id_karyawan,
                       k.nama_karyawan, k.kode_karyawan
                FROM rekening_bank rb
                LEFT JOIN karyawan k ON rb.id_karyawan = k.id_karyawan"
                . $whereSql . " ORDER BY rb.id_bank DESC LIMIT ? OFFSET ?";
        $paramsWithLimit = $params;
        $typesWithLimit = $types . "ii";
        $paramsWithLimit[] = $limit;
        $paramsWithLimit[] = $offset;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'id_bank' => (int)$row['id_bank'],
            'nama_bank' => $row['nama_bank'] ?? '',
            'atasnama_rekening' => $row['atasnama_rekening'] ?? '',
            'no_rekening' => $row['no_rekening'] ?? '',
            'created_at' => $row['created_at'] ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-',
            'created_at_raw' => $row['created_at'] ?? '',
            'id_karyawan' => $row['id_karyawan'] ? (int)$row['id_karyawan'] : null,
            'nama_karyawan' => $row['nama_karyawan'] ?? '-',
            'kode_karyawan' => $row['kode_karyawan'] ?? '-'
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data rekening bank berhasil diambil.', [
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
// Role yang diizinkan mengelola data rekening bank (CREATE, UPDATE, DELETE):
// FINANCE, ADMIN, MANAGER
// -------------------------------------------------------------
$allowedRoles = [ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER];
if (!in_array($currentUser['role'], $allowedRoles, true)) {
    jsonResponse(false, 'Forbidden. Anda tidak memiliki hak akses untuk mengelola data rekening bank.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Rekening Bank Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaBank = trim($input['nama_bank'] ?? '');
    $atasnamaRekening = trim($input['atasnama_rekening'] ?? '');
    $noRekening = trim($input['no_rekening'] ?? '');
    
    // Auto karyawan dari input atau session aktif
    $idKaryawan = !empty($input['id_karyawan']) ? (int)$input['id_karyawan'] : (!empty($currentUser['id_karyawan']) ? (int)$currentUser['id_karyawan'] : null);

    if (empty($namaBank)) {
        jsonResponse(false, 'Nama bank wajib diisi.', null, 422);
    }
    if (empty($noRekening)) {
        jsonResponse(false, 'Nomor rekening wajib diisi.', null, 422);
    }
    if (empty($atasnamaRekening)) {
        jsonResponse(false, 'Atas nama rekening wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("INSERT INTO rekening_bank (nama_bank, atasnama_rekening, no_rekening, id_karyawan) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $namaBank, $atasnamaRekening, $noRekening, $idKaryawan);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Rekening bank berhasil ditambahkan.', ['id_bank' => $newId], 201);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan rekening bank: ' . $err, null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Rekening Bank
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idBank = isset($input['id_bank']) ? (int)$input['id_bank'] : 0;
    $namaBank = trim($input['nama_bank'] ?? '');
    $atasnamaRekening = trim($input['atasnama_rekening'] ?? '');
    $noRekening = trim($input['no_rekening'] ?? '');
    $idKaryawan = !empty($input['id_karyawan']) ? (int)$input['id_karyawan'] : null;

    if ($idBank <= 0) {
        jsonResponse(false, 'ID rekening bank tidak valid.', null, 422);
    }
    if (empty($namaBank)) {
        jsonResponse(false, 'Nama bank wajib diisi.', null, 422);
    }
    if (empty($noRekening)) {
        jsonResponse(false, 'Nomor rekening wajib diisi.', null, 422);
    }
    if (empty($atasnamaRekening)) {
        jsonResponse(false, 'Atas nama rekening wajib diisi.', null, 422);
    }

    if ($idKaryawan !== null) {
        $stmt = $conn->prepare("UPDATE rekening_bank SET nama_bank = ?, atasnama_rekening = ?, no_rekening = ?, id_karyawan = ? WHERE id_bank = ?");
        $stmt->bind_param("sssii", $namaBank, $atasnamaRekening, $noRekening, $idKaryawan, $idBank);
    } else {
        $stmt = $conn->prepare("UPDATE rekening_bank SET nama_bank = ?, atasnama_rekening = ?, no_rekening = ? WHERE id_bank = ?");
        $stmt->bind_param("sssi", $namaBank, $atasnamaRekening, $noRekening, $idBank);
    }

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data rekening bank berhasil diperbarui.', ['id_bank' => $idBank], 200);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui rekening bank: ' . $err, null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Rekening Bank
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idBank = isset($input['id_bank']) ? (int)$input['id_bank'] : (int)($_GET['id'] ?? 0);

    if ($idBank <= 0) {
        jsonResponse(false, 'ID rekening bank tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("DELETE FROM rekening_bank WHERE id_bank = ?");
    $stmt->bind_param("i", $idBank);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Rekening bank berhasil dihapus.', null, 200);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus rekening bank: ' . $err, null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
