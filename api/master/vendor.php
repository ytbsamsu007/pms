<?php
/**
 * API Master: Vendor CRUD Endpoint - PT Jaya Teknis
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
// GET: Mengambil Data Vendor (List / Detail)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (nama_perusahaan LIKE ? OR kode_vendor LIKE ? OR kota LIKE ? OR person LIKE ? OR kontak_person LIKE ? OR email LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 6; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    $statusParam = $_GET['status'] ?? $_GET['aktif'] ?? null;
    if ($statusParam !== null && $statusParam !== '') {
        $statusUpper = strtoupper(trim((string)$statusParam));
        if ($statusUpper === 'AKTIF' || $statusUpper === '1') {
            $whereSql .= " AND aktif = 1";
        } elseif ($statusUpper === 'NONAKTIF' || $statusUpper === '0') {
            $whereSql .= " AND aktif = 0";
        }
    }

    $countSql = "SELECT COUNT(*) as total FROM vendor" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $sql = "SELECT id_vendor, kode_vendor, nama_perusahaan, alamat, gps_alamat, no_telepon, kota,
                   person, kontak_person, website, email, created_at, update_at, jenis_vendor, keterangan,
                   aktif, nomor_rekening, nama_bank, term_of_payment, saldo_hutang_terakhir
            FROM vendor"
            . $whereSql . " ORDER BY id_vendor DESC LIMIT ? OFFSET ?";

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
            'id_vendor' => (int)$row['id_vendor'],
            'kode_vendor' => $row['kode_vendor'] ?? '',
            'nama_perusahaan' => $row['nama_perusahaan'] ?? '',
            'alamat' => $row['alamat'] ?? '-',
            'gps_alamat' => $row['gps_alamat'] ?? '-',
            'no_telepon' => $row['no_telepon'] ?? '-',
            'kota' => $row['kota'] ?? '-',
            'person' => $row['person'] ?? '-',
            'kontak_person' => $row['kontak_person'] ?? '-',
            'website' => $row['website'] ?? '-',
            'email' => $row['email'] ?? '-',
            'jenis_vendor' => $row['jenis_vendor'] ?? '-',
            'keterangan' => $row['keterangan'] ?? '-',
            'nomor_rekening' => $row['nomor_rekening'] ?? '-',
            'nama_bank' => $row['nama_bank'] ?? '-',
            'term_of_payment' => $row['term_of_payment'] ? (int)$row['term_of_payment'] : 0,
            'saldo_hutang_terakhir' => (float)($row['saldo_hutang_terakhir'] ?? 0),
            'created_at' => $row['created_at'] ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-',
            'aktif' => (int)($row['aktif'] ?? 1)
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data vendor berhasil diambil.', [
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
// Role yang diizinkan mengelola data vendor (CREATE, UPDATE, DELETE):
// ADMIN, LOGISTIK, PURCHASING, MANAGER
// -------------------------------------------------------------
$allowedRoles = [ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER];
if (!in_array($currentUser['role'], $allowedRoles, true)) {
    jsonResponse(false, 'Forbidden. Anda tidak memiliki hak akses untuk mengelola data vendor.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Vendor Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaPerusahaan = trim($input['nama_perusahaan'] ?? '');
    $kodeVendor = trim($input['kode_vendor'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $gpsAlamat = trim($input['gps_alamat'] ?? '');
    $noTelepon = trim($input['no_telepon'] ?? '');
    $kota = trim($input['kota'] ?? '');
    $person = trim($input['person'] ?? '');
    $kontakPerson = trim($input['kontak_person'] ?? '');
    $website = trim($input['website'] ?? '');
    $email = trim($input['email'] ?? '');
    $jenisVendor = trim($input['jenis_vendor'] ?? '');
    $keterangan = trim($input['keterangan'] ?? '');
    $nomorRekening = trim($input['nomor_rekening'] ?? '');
    $namaBank = trim($input['nama_bank'] ?? '');
    $termOfPayment = isset($input['term_of_payment']) ? (int)$input['term_of_payment'] : 30;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaPerusahaan)) {
        jsonResponse(false, 'Nama perusahaan / vendor wajib diisi.', null, 422);
    }

    if (empty($kodeVendor)) {
        $resCount = $conn->query("SELECT MAX(id_vendor) as max_id FROM vendor");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeVendor = 'VND' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO vendor (kode_vendor, nama_perusahaan, alamat, gps_alamat, no_telepon, kota, person, kontak_person, website, email, jenis_vendor, keterangan, aktif, nomor_rekening, nama_bank, term_of_payment) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssssissi", $kodeVendor, $namaPerusahaan, $alamat, $gpsAlamat, $noTelepon, $kota, $person, $kontakPerson, $website, $email, $jenisVendor, $keterangan, $aktif, $nomorRekening, $namaBank, $termOfPayment);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Vendor berhasil ditambahkan.', ['id_vendor' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan vendor.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Vendor
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idVendor = isset($input['id_vendor']) ? (int)$input['id_vendor'] : 0;
    $namaPerusahaan = trim($input['nama_perusahaan'] ?? '');
    $kodeVendor = trim($input['kode_vendor'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $gpsAlamat = trim($input['gps_alamat'] ?? '');
    $noTelepon = trim($input['no_telepon'] ?? '');
    $kota = trim($input['kota'] ?? '');
    $person = trim($input['person'] ?? '');
    $kontakPerson = trim($input['kontak_person'] ?? '');
    $website = trim($input['website'] ?? '');
    $email = trim($input['email'] ?? '');
    $jenisVendor = trim($input['jenis_vendor'] ?? '');
    $keterangan = trim($input['keterangan'] ?? '');
    $nomorRekening = trim($input['nomor_rekening'] ?? '');
    $namaBank = trim($input['nama_bank'] ?? '');
    $termOfPayment = isset($input['term_of_payment']) ? (int)$input['term_of_payment'] : 30;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if ($idVendor <= 0 || empty($namaPerusahaan)) {
        jsonResponse(false, 'ID vendor dan nama perusahaan wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE vendor SET kode_vendor = ?, nama_perusahaan = ?, alamat = ?, gps_alamat = ?, no_telepon = ?, 
                            kota = ?, person = ?, kontak_person = ?, website = ?, email = ?, jenis_vendor = ?, keterangan = ?, 
                            aktif = ?, nomor_rekening = ?, nama_bank = ?, term_of_payment = ? 
                            WHERE id_vendor = ?");
    $stmt->bind_param("ssssssssssssissii", $kodeVendor, $namaPerusahaan, $alamat, $gpsAlamat, $noTelepon, $kota, $person, $kontakPerson, $website, $email, $jenisVendor, $keterangan, $aktif, $nomorRekening, $namaBank, $termOfPayment, $idVendor);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data vendor berhasil diperbarui.', ['id_vendor' => $idVendor], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui data vendor.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Vendor
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idVendor = isset($input['id_vendor']) ? (int)$input['id_vendor'] : (int)($_GET['id'] ?? 0);

    if ($idVendor <= 0) {
        jsonResponse(false, 'ID vendor tidak valid.', null, 422);
    }

    // --- Pengecekan Relasi Database ---
    $relations = [
        ['table' => 'request_order', 'col' => 'id_vendor', 'label' => 'Request Order'],
        ['table' => 'purchase_order', 'col' => 'id_vendor', 'label' => 'Purchase Order'],
        ['table' => 'faktur_po', 'col' => 'id_vendor', 'label' => 'Faktur PO'],
        ['table' => 'retur_po', 'col' => 'id_vendor', 'label' => 'Retur PO'],
        ['table' => 'barang_hargavendor', 'col' => 'id_vendor', 'label' => 'Master Harga Barang Vendor']
    ];
    
    $inUse = [];
    foreach ($relations as $rel) {
        $q = "SELECT COUNT(*) as count FROM {$rel['table']} WHERE {$rel['col']} = ?";
        $stmtRel = $conn->prepare($q);
        if ($stmtRel) {
            $stmtRel->bind_param("i", $idVendor);
            $stmtRel->execute();
            $count = $stmtRel->get_result()->fetch_assoc()['count'] ?? 0;
            $stmtRel->close();
            
            if ($count > 0) {
                $inUse[] = $count . " data " . $rel['label'];
            }
        }
    }
    
    if (count($inUse) > 0) {
        $msg = "Gagal menghapus! Vendor ini sedang digunakan pada: " . implode(", ", $inUse) . ".";
        jsonResponse(false, $msg, null, 409); // 409 Conflict
    }
    // -----------------------------------

    $stmt = $conn->prepare("DELETE FROM vendor WHERE id_vendor = ?");
    $stmt->bind_param("i", $idVendor);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Vendor berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus vendor. Terjadi kesalahan pada server.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
