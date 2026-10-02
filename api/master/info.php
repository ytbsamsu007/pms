<?php
/**
 * API Master: Pengumuman & Informasi (Tabel `info`) - PT Jaya Teknis
 * Path: api/master/info.php
 * 
 * Fitur:
 * - Public Feed (untuk ditampilkan di Login Page tanpa perlu login)
 * - Authenticated Feed (untuk Dashboard Karyawan sesuai divisi)
 * - Full CRUD & Toggle Aktif (Khusus Administrator)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle method spoofing untuk form-data multipart POST
if ($method === 'POST') {
    $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($rawInput['_method'])) {
        $method = strtoupper($rawInput['_method']);
    }
}

// =========================================================================
// 1. GET: Ambil Feed Pengumuman (Public Login Page atau Admin Management)
// =========================================================================
if ($method === 'GET') {
    $isPublic = isset($_GET['public']) && (int)$_GET['public'] === 1;
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    // Single Detail Info
    if ($id) {
        $stmt = $conn->prepare("SELECT i.id_info, i.judul, i.isi, i.file, i.aktif, i.tampil_login, i.id_karyawan, i.created_at, i.divisi_array,
                                       k.nama_karyawan as pembuat_karyawan,
                                       k.kode_karyawan
                                FROM info i 
                                LEFT JOIN karyawan k ON i.id_karyawan = k.id_karyawan
                                WHERE i.id_info = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $stmt->close();
            $row['file_url'] = !empty($row['file']) ? (BASE_URL . '/' . ltrim($row['file'], '/')) : null;
            $row['divisi_ids'] = !empty($row['divisi_array']) ? array_map('intval', explode(',', $row['divisi_array'])) : [];
            $row['tampil_login'] = (int)($row['tampil_login'] ?? 0);
            jsonResponse(true, 'Detail informasi berhasil diambil.', $row);
        }
        $stmt->close();
        jsonResponse(false, 'Informasi tidak ditemukan.', null, 404);
    }

    // List Info / Feed
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;
    
    // Filter aktif
    $aktifFilter = isset($_GET['aktif']) && $_GET['aktif'] !== '' ? (int)$_GET['aktif'] : ($isPublic ? 1 : null);
    
    // Filter tampil_login
    $tampilLoginFilter = isset($_GET['tampil_login']) && $_GET['tampil_login'] !== '' ? (int)$_GET['tampil_login'] : ($isPublic ? 1 : null);
    
    // Filter divisi spesifik (opsional)
    $divisiFilter = isset($_GET['id_divisi']) && is_numeric($_GET['id_divisi']) ? (int)$_GET['id_divisi'] : null;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    // Jika public atau filter aktif tertentu
    if ($aktifFilter !== null) {
        $whereSql .= " AND i.aktif = ?";
        $params[] = $aktifFilter;
        $types .= "i";
    }

    // Jika filter tampil_login (Khususnya untuk halaman login)
    if ($tampilLoginFilter !== null) {
        $whereSql .= " AND i.tampil_login = ?";
        $params[] = $tampilLoginFilter;
        $types .= "i";
    }

    if (!empty($search)) {
        $whereSql .= " AND (i.judul LIKE ? OR i.isi LIKE ?)";
        $wildcard = "%" . $search . "%";
        $params[] = $wildcard;
        $params[] = $wildcard;
        $types .= "ss";
    }

    if ($divisiFilter !== null) {
        $whereSql .= " AND (i.divisi_array IS NULL OR i.divisi_array = '' OR FIND_IN_SET(?, i.divisi_array))";
        $params[] = $divisiFilter;
        $types .= "i";
    }

    // Hitung Total Data
    $countSql = "SELECT COUNT(*) as total FROM info i" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    // Ambil Data
    $sql = "SELECT i.id_info, i.judul, i.isi, i.file, i.aktif, i.tampil_login, i.id_karyawan, i.created_at, i.divisi_array,
                   COALESCE(k.nama_karyawan, 'Administrator Sistem') as pembuat,
                   k.kode_karyawan
            FROM info i 
            LEFT JOIN karyawan k ON i.id_karyawan = k.id_karyawan
            $whereSql 
            ORDER BY i.created_at DESC, i.id_info DESC 
            LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $res = $stmt->get_result();

    // Cache daftar divisi untuk label visual
    $divisiMap = [];
    $divRes = $conn->query("SELECT id_divisi, nama_divisi FROM divisi");
    if ($divRes) {
        while($d = $divRes->fetch_assoc()) {
            $divisiMap[(int)$d['id_divisi']] = $d['nama_divisi'];
        }
    }

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $row['id_info'] = (int)$row['id_info'];
        $row['aktif'] = (int)$row['aktif'];
        $row['tampil_login'] = (int)($row['tampil_login'] ?? 0);
        $row['id_karyawan'] = $row['id_karyawan'] ? (int)$row['id_karyawan'] : null;
        $row['file_url'] = !empty($row['file']) ? (BASE_URL . '/' . ltrim($row['file'], '/')) : null;
        
        // Parse nama-nama divisi target
        $targetDivisiNames = [];
        if (!empty($row['divisi_array'])) {
            $arr = explode(',', $row['divisi_array']);
            foreach ($arr as $dId) {
                $dId = (int)trim($dId);
                if (isset($divisiMap[$dId])) {
                    $targetDivisiNames[] = $divisiMap[$dId];
                }
            }
        }
        $row['divisi_names'] = $targetDivisiNames;
        $row['is_all_divisi'] = empty($row['divisi_array']);
        $row['tanggal_format'] = date('d M Y, H:i', strtotime($row['created_at']));
        
        $items[] = $row;
    }
    $stmt->close();

    // Hitung ringkasan statistik (Aktif, Non-Aktif, Tampil Login, Total)
    $statRes = $conn->query("SELECT 
        COUNT(*) as total_info,
        SUM(CASE WHEN aktif = 1 THEN 1 ELSE 0 END) as info_aktif,
        SUM(CASE WHEN aktif = 0 THEN 1 ELSE 0 END) as info_nonaktif,
        SUM(CASE WHEN tampil_login = 1 THEN 1 ELSE 0 END) as info_tampil_login
    FROM info");
    $stats = $statRes ? $statRes->fetch_assoc() : [
        'total_info' => 0, 'info_aktif' => 0, 'info_nonaktif' => 0, 'info_tampil_login' => 0
    ];

    jsonResponse(true, 'Data informasi berhasil dimuat.', [
        'items' => $items,
        'pagination' => [
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        ],
        'stats' => $stats
    ]);
}

// =========================================================================
// UNTUK POST, PUT, DELETE: Wajib Terautentikasi
// =========================================================================
$currentUser = apiAuth();

// Helper: Handle upload file attachment info (Gambar atau Dokumen)
function handleInfoFileUpload() {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES['file'];
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        jsonResponse(false, 'Format file tidak didukung. Gunakan file gambar (JPG, PNG, WEBP) atau dokumen (PDF, Word, Excel).', null, 422);
    }

    // Maksimal 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(false, 'Ukuran file attachment maksimal 5MB.', null, 422);
    }

    $uploadDir = __DIR__ . '/../../images/info/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = 'info_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return 'images/info/' . $fileName;
    }

    return null;
}

// =========================================================================
// 2. POST: Tambah Informasi Baru
// =========================================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $judul = trim($input['judul'] ?? '');
    $isi = trim($input['isi'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;
    $tampilLogin = isset($input['tampil_login']) ? (int)$input['tampil_login'] : 1;
    
    // Divisi array format: bisa string '1,2,3' atau array [1,2,3]
    $divisiInput = $input['divisi_array'] ?? '';
    if (is_array($divisiInput)) {
        $divisiArray = implode(',', array_filter(array_map('intval', $divisiInput)));
    } else {
        $divisiArray = trim((string)$divisiInput);
    }

    if (empty($judul)) {
        jsonResponse(false, 'Judul informasi wajib diisi (maksimal 60 karakter).', null, 422);
    }

    if (mb_strlen($judul) > 60) {
        jsonResponse(false, 'Judul informasi terlalu panjang (maksimal 60 karakter).', null, 422);
    }

    // Cek file upload
    $filePath = handleInfoFileUpload();
    if (!$filePath && isset($input['file']) && is_string($input['file'])) {
        $filePath = trim($input['file']);
    }

    $idKaryawan = $currentUser['id_karyawan'] ?? null;

    $stmt = $conn->prepare("INSERT INTO info (judul, isi, file, aktif, id_karyawan, created_at, divisi_array, tampil_login) 
                            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
    $stmt->bind_param("sssiisi", $judul, $isi, $filePath, $aktif, $idKaryawan, $divisiArray, $tampilLogin);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();
        jsonResponse(true, 'Informasi berhasil diterbitkan.', [
            'id_info' => $newId,
            'judul' => $judul
        ], 201);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal menyimpan informasi: ' . $err, null, 500);
    }
}

// =========================================================================
// 3. PUT: Update Informasi / Toggle Status
// =========================================================================
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $id = isset($input['id_info']) && is_numeric($input['id_info']) ? (int)$input['id_info'] : null;
    if (!$id) {
        jsonResponse(false, 'ID Informasi wajib disertakan untuk pembaruan.', null, 422);
    }

    // Cek keberadaan data & id_karyawan pembuat
    $stmtCheck = $conn->prepare("SELECT id_info, file, id_karyawan FROM info WHERE id_info = ? LIMIT 1");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $curr = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if (!$curr) {
        jsonResponse(false, 'Informasi tidak ditemukan.', null, 404);
    }

    // Validasi Hak Akses: Hanya Admin atau Karyawan Pembuat yang boleh mengubah
    $isAdmin = ($currentUser['role'] === ROLE_ADMIN);
    $userIdKaryawan = $currentUser['id_karyawan'] ?? null;
    $isOwner = ($userIdKaryawan !== null && $curr['id_karyawan'] !== null && (int)$userIdKaryawan === (int)$curr['id_karyawan']);

    if (!$isAdmin && !$isOwner) {
        jsonResponse(false, 'Akses ditolak. Anda hanya dapat mengubah pengumuman yang dibuat oleh akun/karyawan Anda sendiri.', null, 403);
    }

    // Aksi Cepat: Toggle Status Aktif (1 / 0)
    if (isset($input['action']) && $input['action'] === 'toggle_aktif') {
        $newStatus = isset($input['aktif']) ? (int)$input['aktif'] : 1;
        $up = $conn->prepare("UPDATE info SET aktif = ? WHERE id_info = ?");
        $up->bind_param("ii", $newStatus, $id);
        $up->execute();
        $up->close();

        $statusText = $newStatus === 1 ? 'diaktifkan' : 'dinonaktifkan';
        jsonResponse(true, "Status informasi berhasil {$statusText}.", ['aktif' => $newStatus]);
    }

    // Aksi Cepat: Toggle Status Tampil Login (1 / 0)
    if (isset($input['action']) && $input['action'] === 'toggle_tampil_login') {
        $newStatus = isset($input['tampil_login']) ? (int)$input['tampil_login'] : 1;
        $up = $conn->prepare("UPDATE info SET tampil_login = ? WHERE id_info = ?");
        $up->bind_param("ii", $newStatus, $id);
        $up->execute();
        $up->close();

        $statusText = $newStatus === 1 ? 'akan ditampilkan di' : 'disembunyikan dari';
        jsonResponse(true, "Pengumuman {$statusText} halaman login.", ['tampil_login' => $newStatus]);
    }

    // Update Lengkap
    $judul = trim($input['judul'] ?? '');
    $isi = trim($input['isi'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;
    $tampilLogin = isset($input['tampil_login']) ? (int)$input['tampil_login'] : 1;

    $divisiInput = $input['divisi_array'] ?? '';
    if (is_array($divisiInput)) {
        $divisiArray = implode(',', array_filter(array_map('intval', $divisiInput)));
    } else {
        $divisiArray = trim((string)$divisiInput);
    }

    if (empty($judul)) {
        jsonResponse(false, 'Judul informasi wajib diisi (maksimal 60 karakter).', null, 422);
    }

    if (mb_strlen($judul) > 60) {
        jsonResponse(false, 'Judul informasi terlalu panjang (maksimal 60 karakter).', null, 422);
    }

    // Cek file upload baru
    $filePath = handleInfoFileUpload();
    if (!$filePath) {
        // Jika tidak upload baru, gunakan file lama atau string input
        $filePath = isset($input['file']) ? trim($input['file']) : $curr['file'];
    }

    $stmt = $conn->prepare("UPDATE info SET judul = ?, isi = ?, file = ?, aktif = ?, divisi_array = ?, tampil_login = ? WHERE id_info = ?");
    $stmt->bind_param("sssisii", $judul, $isi, $filePath, $aktif, $divisiArray, $tampilLogin, $id);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Informasi berhasil diperbarui.', [
            'id_info' => $id,
            'judul' => $judul,
            'tampil_login' => $tampilLogin
        ]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui informasi: ' . $err, null, 500);
    }
}

// =========================================================================
// 4. DELETE: Hapus Informasi
// =========================================================================
if ($method === 'DELETE') {
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
    if (!$id) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id_info']) && is_numeric($input['id_info']) ? (int)$input['id_info'] : null;
    }

    if (!$id) {
        jsonResponse(false, 'ID Informasi wajib disertakan untuk penghapusan.', null, 422);
    }

    // Cek dan ambil path file serta id_karyawan untuk dibersihkan
    $stmtCheck = $conn->prepare("SELECT id_info, judul, file, id_karyawan FROM info WHERE id_info = ? LIMIT 1");
    $stmtCheck->bind_param("i", $id);
    $stmtCheck->execute();
    $curr = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if (!$curr) {
        jsonResponse(false, 'Informasi tidak ditemukan.', null, 404);
    }

    // Validasi Hak Akses: Hanya Admin atau Karyawan Pembuat yang boleh menghapus
    $isAdmin = ($currentUser['role'] === ROLE_ADMIN);
    $userIdKaryawan = $currentUser['id_karyawan'] ?? null;
    $isOwner = ($userIdKaryawan !== null && $curr['id_karyawan'] !== null && (int)$userIdKaryawan === (int)$curr['id_karyawan']);

    if (!$isAdmin && !$isOwner) {
        jsonResponse(false, 'Akses ditolak. Anda hanya dapat menghapus pengumuman yang dibuat oleh akun/karyawan Anda sendiri.', null, 403);
    }

    // Hapus dari database
    $stmtDel = $conn->prepare("DELETE FROM info WHERE id_info = ?");
    $stmtDel->bind_param("i", $id);

    if ($stmtDel->execute()) {
        $stmtDel->close();

        // Hapus file fisik attachment jika ada di folder images/info/
        if (!empty($curr['file'])) {
            $absPath = __DIR__ . '/../../' . ltrim($curr['file'], '/');
            if (file_exists($absPath) && is_file($absPath)) {
                @unlink($absPath);
            }
        }

        jsonResponse(true, "Informasi \"{$curr['judul']}\" berhasil dihapus.");
    } else {
        $err = $stmtDel->error;
        $stmtDel->close();
        jsonResponse(false, 'Gagal menghapus informasi: ' . $err, null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
