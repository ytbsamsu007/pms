<?php
/**
 * API Master: Barang Harga Vendor Endpoint - PT Jaya Teknik
 * Mengelola relasi histori & daftar harga barang per vendor
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
// GET: Mengambil Data Harga Barang per Vendor
// -------------------------------------------------------------
if ($method === 'GET') {
    $idBarang = isset($_GET['id_barang']) && is_numeric($_GET['id_barang']) ? (int)$_GET['id_barang'] : null;
    $idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : null;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if ($idBarang) {
        $whereSql .= " AND bh.id_barang = ?";
        $params[] = $idBarang;
        $types .= "i";
    }

    if ($idVendor) {
        $whereSql .= " AND bh.id_vendor = ?";
        $params[] = $idVendor;
        $types .= "i";
    }

    $sql = "SELECT bh.id_harga, bh.id_barang, bh.id_vendor, bh.harga_set, bh.berlaku,
                   v.nama_perusahaan, v.kode_vendor, v.kota, v.no_telepon,
                   b.nama_barang, b.kode_barang, b.satuan
            FROM barang_hargavendor bh
            LEFT JOIN vendor v ON bh.id_vendor = v.id_vendor
            LEFT JOIN barang b ON bh.id_barang = b.id_barang"
            . $whereSql . " ORDER BY bh.berlaku DESC, bh.id_harga DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'id_harga' => (int)$row['id_harga'],
            'id_barang' => (int)$row['id_barang'],
            'nama_barang' => $row['nama_barang'] ?? '',
            'kode_barang' => $row['kode_barang'] ?? '',
            'satuan' => $row['satuan'] ?? 'PCS',
            'id_vendor' => (int)$row['id_vendor'],
            'nama_perusahaan' => $row['nama_perusahaan'] ?? 'Vendor Tidak Dikenal',
            'kode_vendor' => $row['kode_vendor'] ?? '-',
            'kota' => $row['kota'] ?? '-',
            'no_telepon' => $row['no_telepon'] ?? '-',
            'harga_set' => (float)($row['harga_set'] ?? 0),
            'harga_formatted' => 'Rp ' . number_format((float)($row['harga_set'] ?? 0), 0, ',', '.'),
            'berlaku' => $row['berlaku'] ?? '',
            'berlaku_formatted' => $row['berlaku'] ? date('d-m-Y', strtotime($row['berlaku'])) : '-'
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data harga vendor berhasil diambil.', [
        'items' => $items,
        'total' => count($items)
    ], 200);
}

// -------------------------------------------------------------
// Hanya Role ADMIN yang diizinkan mengelola harga vendor
// -------------------------------------------------------------
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola harga barang vendor.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Harga Vendor Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $idBarang = isset($input['id_barang']) ? (int)$input['id_barang'] : 0;
    $idVendor = isset($input['id_vendor']) ? (int)$input['id_vendor'] : 0;
    $hargaSet = isset($input['harga_set']) ? (float)$input['harga_set'] : 0;
    $berlaku = !empty($input['berlaku']) ? $input['berlaku'] : date('Y-m-d');

    if ($idBarang <= 0 || $idVendor <= 0) {
        jsonResponse(false, 'Barang dan Vendor wajib dipilih.', null, 422);
    }

    if ($hargaSet <= 0) {
        jsonResponse(false, 'Harga yang diset harus lebih besar dari 0.', null, 422);
    }

    $stmt = $conn->prepare("INSERT INTO barang_hargavendor (id_barang, id_vendor, harga_set, berlaku) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iids", $idBarang, $idVendor, $hargaSet, $berlaku);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Harga vendor berhasil ditambahkan.', ['id_harga' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan harga vendor.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Harga Vendor
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idHarga = isset($input['id_harga']) ? (int)$input['id_harga'] : 0;
    $idVendor = isset($input['id_vendor']) ? (int)$input['id_vendor'] : 0;
    $hargaSet = isset($input['harga_set']) ? (float)$input['harga_set'] : 0;
    $berlaku = !empty($input['berlaku']) ? $input['berlaku'] : date('Y-m-d');

    if ($idHarga <= 0) {
        jsonResponse(false, 'ID harga tidak valid.', null, 422);
    }

    if ($hargaSet <= 0) {
        jsonResponse(false, 'Harga harus lebih besar dari 0.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE barang_hargavendor SET id_vendor = ?, harga_set = ?, berlaku = ? WHERE id_harga = ?");
    $stmt->bind_param("idsi", $idVendor, $hargaSet, $berlaku, $idHarga);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Harga vendor berhasil diperbarui.', ['id_harga' => $idHarga], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui harga vendor.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Harga Vendor
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idHarga = isset($input['id_harga']) ? (int)$input['id_harga'] : (int)($_GET['id'] ?? 0);

    if ($idHarga <= 0) {
        jsonResponse(false, 'ID harga tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("DELETE FROM barang_hargavendor WHERE id_harga = ?");
    $stmt->bind_param("i", $idHarga);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data harga vendor berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus data harga vendor.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
