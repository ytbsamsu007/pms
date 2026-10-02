<?php
/**
 * RESTful API Manajemen Dynamic Menu Level Jabatan - PT Jaya Teknis
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// GET: Mengambil Data Menu Dinamis per Jabatan
// -------------------------------------------------------------
if ($method === 'GET') {
    $idJabatan = isset($_GET['id_jabatan']) ? (int)$_GET['id_jabatan'] : 0;
    
    // Jika tidak spesifik jabatan, gunakan jabatan user yang login jika bukan ADMIN
    if ($idJabatan <= 0 && $currentUser['role'] !== ROLE_ADMIN) {
        $idJabatan = isset($currentUser['id_jabatan']) ? (int)$currentUser['id_jabatan'] : 1;
    }

    $whereClause = "1=1";
    if ($idJabatan > 0) {
        $whereClause .= " AND m.id_jabatan = $idJabatan";
    }

    // Filter akses & terlihat jika diminta untuk rendering sidebar saja
    if (isset($_GET['sidebar_only']) && $_GET['sidebar_only'] == '1') {
        $whereClause .= " AND m.akses = 1 AND m.terlihat = 1";
    }

    // Search query
    if (!empty($_GET['q'])) {
        $q = $conn->real_escape_string(trim($_GET['q']));
        $whereClause .= " AND (m.nama_menu LIKE '%$q%' OR m.kategori_menu LIKE '%$q%' OR m.link LIKE '%$q%')";
    }

    $query = "SELECT m.*, j.nama_jabatan, j.level as level_jabatan, d.nama_divisi 
              FROM menu_level m
              LEFT JOIN jabatan j ON m.id_jabatan = j.id_jabatan
              LEFT JOIN divisi d ON j.id_divisi = d.id_divisi
              WHERE $whereClause
              ORDER BY m.id_jabatan ASC, 
                       FIELD(m.kategori_menu, 'MENU UTAMA', 'OPERASIONAL', 'MASTER DATA', 'LAPORAN'),
                       m.urutan ASC, m.id_levelmenu ASC";

    $res = $conn->query($query);
    if (!$res) {
        jsonResponse(false, 'Gagal mengambil data menu: ' . $conn->error, null, 500);
    }

    $flatItems = [];
    $parentsMap = [];
    $childrenList = [];

    while ($row = $res->fetch_assoc()) {
        $item = [
            'id_levelmenu' => (int)$row['id_levelmenu'],
            'id_jabatan' => (int)$row['id_jabatan'],
            'nama_jabatan' => $row['nama_jabatan'] ?? '-',
            'level_jabatan' => isset($row['level_jabatan']) ? (int)$row['level_jabatan'] : null,
            'nama_divisi' => $row['nama_divisi'] ?? '-',
            'kategori_menu' => $row['kategori_menu'],
            'nama_menu' => $row['nama_menu'],
            'is_parent' => (int)$row['is_parent'],
            'id_parent' => !empty($row['id_parent']) ? (int)$row['id_parent'] : null,
            'link' => $row['link'],
            'icon' => $row['icon'] ?: 'bi-circle',
            'urutan' => (int)$row['urutan'],
            'akses' => (int)$row['akses'],
            'terlihat' => (int)$row['terlihat'],
            'created_at' => $row['created_at'],
            'children' => []
        ];

        $flatItems[] = $item;

        if (empty($item['id_parent'])) {
            $parentsMap[$item['id_levelmenu']] = $item;
        } else {
            $childrenList[] = $item;
        }
    }

    // Bangun Hierarki Menu (Parent -> Children)
    foreach ($childrenList as $child) {
        $pId = $child['id_parent'];
        if (isset($parentsMap[$pId])) {
            $parentsMap[$pId]['children'][] = $child;
        }
    }

    $structuredTree = array_values($parentsMap);

    jsonResponse(true, 'Data menu dinamis berhasil dimuat.', [
        'id_jabatan' => $idJabatan,
        'total_items' => count($flatItems),
        'items' => $flatItems,
        'tree' => $structuredTree
    ], 200);
}

// -------------------------------------------------------------
// Hanya Role ADMIN yang diizinkan melakukan CREATE, UPDATE, DELETE
// -------------------------------------------------------------
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola hak akses menu.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Menu Baru atau Salin Menu Antar Jabatan
// -------------------------------------------------------------
if ($method === 'POST') {
    $action = $input['action'] ?? 'create';

    // Aksi: Salin Template Menu dari Jabatan Lain
    if ($action === 'copy_from') {
        $fromJabatanId = isset($input['from_id_jabatan']) ? (int)$input['from_id_jabatan'] : 0;
        $targetJabatanId = isset($input['target_id_jabatan']) ? (int)$input['target_id_jabatan'] : 0;

        if ($fromJabatanId <= 0 || $targetJabatanId <= 0) {
            jsonResponse(false, 'Jabatan asal dan jabatan tujuan wajib dipilih.', null, 422);
        }

        if ($fromJabatanId === $targetJabatanId) {
            jsonResponse(false, 'Jabatan asal dan tujuan tidak boleh sama.', null, 422);
        }

        // Hapus menu lama pada jabatan target jika ada
        $conn->query("DELETE FROM menu_level WHERE id_jabatan = $targetJabatanId");

        // Ambil menu parents dari jabatan asal
        $parentsRes = $conn->query("SELECT * FROM menu_level WHERE id_jabatan = $fromJabatanId AND (id_parent IS NULL OR id_parent = 0) ORDER BY urutan ASC");
        $copiedCount = 0;

        while ($parent = $parentsRes->fetch_assoc()) {
            $oldParentId = (int)$parent['id_levelmenu'];
            $insP = $conn->prepare("INSERT INTO menu_level (id_jabatan, kategori_menu, nama_menu, is_parent, id_parent, link, icon, urutan, akses, terlihat) 
                                    VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?)");
            $insP->bind_param("ississiii", $targetJabatanId, $parent['kategori_menu'], $parent['nama_menu'], $parent['is_parent'], $parent['link'], $parent['icon'], $parent['urutan'], $parent['akses'], $parent['terlihat']);
            $insP->execute();
            $newParentId = $conn->insert_id;
            $insP->close();
            $copiedCount++;

            // Ambil dan salin children jika ada
            $childRes = $conn->query("SELECT * FROM menu_level WHERE id_jabatan = $fromJabatanId AND id_parent = $oldParentId ORDER BY urutan ASC");
            while ($child = $childRes->fetch_assoc()) {
                $insC = $conn->prepare("INSERT INTO menu_level (id_jabatan, kategori_menu, nama_menu, is_parent, id_parent, link, icon, urutan, akses, terlihat) 
                                        VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?)");
                $insC->bind_param("ississiii", $targetJabatanId, $child['kategori_menu'], $child['nama_menu'], $newParentId, $child['link'], $child['icon'], $child['urutan'], $child['akses'], $child['terlihat']);
                $insC->execute();
                $insC->close();
                $copiedCount++;
            }
        }

        jsonResponse(true, "Berhasil menyalin $copiedCount susunan menu ke jabatan target.", ['copied_count' => $copiedCount], 200);
    }

    // Aksi: Tambah Menu Tunggal
    $idJabatan = isset($input['id_jabatan']) ? (int)$input['id_jabatan'] : 0;
    $kategori = trim($input['kategori_menu'] ?? 'OPERASIONAL');
    $namaMenu = trim($input['nama_menu'] ?? '');
    $isParent = isset($input['is_parent']) ? (int)$input['is_parent'] : 0;
    $idParent = !empty($input['id_parent']) ? (int)$input['id_parent'] : null;
    $link = trim($input['link'] ?? '');
    $icon = trim($input['icon'] ?? 'bi-circle');
    $urutan = isset($input['urutan']) ? (int)$input['urutan'] : 1;
    $akses = isset($input['akses']) ? (int)$input['akses'] : 1;
    $terlihat = isset($input['terlihat']) ? (int)$input['terlihat'] : 1;

    if ($idJabatan <= 0 || empty($namaMenu)) {
        jsonResponse(false, 'Jabatan dan nama menu wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("INSERT INTO menu_level (id_jabatan, kategori_menu, nama_menu, is_parent, id_parent, link, icon, urutan, akses, terlihat) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississsiii", $idJabatan, $kategori, $namaMenu, $isParent, $idParent, $link, $icon, $urutan, $akses, $terlihat);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Menu berhasil ditambahkan.', ['id_levelmenu' => $newId], 201);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan menu: ' . $err, null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Menu atau Toggle Status
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idMenu = isset($input['id_levelmenu']) ? (int)$input['id_levelmenu'] : 0;

    if ($idMenu <= 0) {
        jsonResponse(false, 'ID menu tidak valid.', null, 422);
    }

    // Update Parsial / Toggle Cepat (misal toggle akses / terlihat / urutan)
    if (isset($input['toggle_field'])) {
        $field = $input['toggle_field'];
        if (in_array($field, ['akses', 'terlihat'])) {
            $val = (int)$input['toggle_value'];
            $conn->query("UPDATE menu_level SET $field = $val WHERE id_levelmenu = $idMenu");
            
            // Jika mematikan akses/terlihat pada parent, matikan juga pada anak-anaknya
            if ($val === 0) {
                $conn->query("UPDATE menu_level SET $field = 0 WHERE id_parent = $idMenu");
            }

            jsonResponse(true, "Status $field berhasil diperbarui.", null, 200);
        }
    }

    $kategori = trim($input['kategori_menu'] ?? 'OPERASIONAL');
    $namaMenu = trim($input['nama_menu'] ?? '');
    $isParent = isset($input['is_parent']) ? (int)$input['is_parent'] : 0;
    $idParent = !empty($input['id_parent']) ? (int)$input['id_parent'] : null;
    $link = trim($input['link'] ?? '');
    $icon = trim($input['icon'] ?? 'bi-circle');
    $urutan = isset($input['urutan']) ? (int)$input['urutan'] : 1;
    $akses = isset($input['akses']) ? (int)$input['akses'] : 1;
    $terlihat = isset($input['terlihat']) ? (int)$input['terlihat'] : 1;

    if (empty($namaMenu)) {
        jsonResponse(false, 'Nama menu wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE menu_level SET kategori_menu = ?, nama_menu = ?, is_parent = ?, id_parent = ?, link = ?, icon = ?, urutan = ?, akses = ?, terlihat = ? 
                            WHERE id_levelmenu = ?");
    $stmt->bind_param("ssisssiiii", $kategori, $namaMenu, $isParent, $idParent, $link, $icon, $urutan, $akses, $terlihat, $idMenu);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data menu berhasil diperbarui.', null, 200);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui menu: ' . $err, null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Menu
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idMenu = isset($_GET['id']) ? (int)$_GET['id'] : (isset($input['id_levelmenu']) ? (int)$input['id_levelmenu'] : 0);

    if ($idMenu <= 0) {
        jsonResponse(false, 'ID menu tidak valid.', null, 422);
    }

    // Hapus juga sub-menu jika parent dihapus
    $conn->query("DELETE FROM menu_level WHERE id_parent = $idMenu");
    
    $stmt = $conn->prepare("DELETE FROM menu_level WHERE id_levelmenu = ?");
    $stmt->bind_param("i", $idMenu);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Menu berhasil dihapus.', null, 200);
    } else {
        $err = $stmt->error;
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus menu: ' . $err, null, 500);
    }
}
