<?php
/**
 * API Master: Barang Stok Endpoint - PT Jaya Teknik
 * Mengelola relasi stok barang per lokasi site / workshop
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
// GET: Mengambil Data Stok Barang per Site
// -------------------------------------------------------------
if ($method === 'GET') {
    $idBarang = isset($_GET['id_barang']) && is_numeric($_GET['id_barang']) ? (int)$_GET['id_barang'] : null;
    $idSite = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : null;

    if ($idBarang) {
        // Ambil stok barang hanya di site yang mengizinkan penyimpanan stok (penyimpanan_stok = 1)
        $sql = "SELECT s.id_site, s.nama_site, s.kode_site, s.jenis_site,
                       COALESCE(bs.id_stok, 0) AS id_stok,
                       COALESCE(bs.stok, 0) AS stok
                FROM site s
                LEFT JOIN barang_stok bs ON s.id_site = bs.id_site AND bs.id_barang = ?
                WHERE s.penyimpanan_stok = 1
                ORDER BY s.id_site ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idBarang);
        $stmt->execute();
        $res = $stmt->get_result();

        $items = [];
        $totalStok = 0;
        while ($row = $res->fetch_assoc()) {
            $stokVal = (int)$row['stok'];
            $totalStok += $stokVal;
            $items[] = [
                'id_stok' => (int)$row['id_stok'],
                'id_barang' => $idBarang,
                'id_site' => (int)$row['id_site'],
                'nama_site' => $row['nama_site'],
                'kode_site' => $row['kode_site'],
                'jenis_site' => $row['jenis_site'],
                'stok' => $stokVal
            ];
        }
        $stmt->close();

        jsonResponse(true, 'Data stok per site berhasil diambil.', [
            'id_barang' => $idBarang,
            'total_stok' => $totalStok,
            'items' => $items
        ], 200);
    }

    if ($idSite) {
        // Ambil stok seluruh barang di satu site tertentu
        $sql = "SELECT b.id_barang, b.kode_barang, b.nama_barang, b.satuan,
                       COALESCE(bs.id_stok, 0) AS id_stok,
                       COALESCE(bs.stok, 0) AS stok
                FROM barang b
                LEFT JOIN barang_stok bs ON b.id_barang = bs.id_barang AND bs.id_site = ?
                WHERE b.aktif = 1
                ORDER BY b.nama_barang ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idSite);
        $stmt->execute();
        $res = $stmt->get_result();

        $items = [];
        while ($row = $res->fetch_assoc()) {
            $items[] = [
                'id_stok' => (int)$row['id_stok'],
                'id_barang' => (int)$row['id_barang'],
                'kode_barang' => $row['kode_barang'],
                'nama_barang' => $row['nama_barang'],
                'satuan' => $row['satuan'],
                'id_site' => $idSite,
                'stok' => (int)$row['stok']
            ];
        }
        $stmt->close();

        jsonResponse(true, 'Data inventaris stok site berhasil diambil.', [
            'id_site' => $idSite,
            'items' => $items
        ], 200);
    }

    jsonResponse(false, 'Parameter id_barang atau id_site wajib disertakan.', null, 422);
}

// -------------------------------------------------------------
// Hanya Role ADMIN yang diizinkan mengupdate stok manual
// -------------------------------------------------------------
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola stok barang.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST / PUT: Update Stok Barang per Site (Single atau Bulk)
// -------------------------------------------------------------
if ($method === 'POST' || $method === 'PUT') {
    $idBarang = isset($input['id_barang']) ? (int)$input['id_barang'] : 0;

    if ($idBarang <= 0) {
        jsonResponse(false, 'ID barang tidak valid.', null, 422);
    }

    // Kasus 1: Bulk array of sites [{ id_site: 1, stok: 30 }, { id_site: 2, stok: 70 }]
    if (isset($input['stok_sites']) && is_array($input['stok_sites'])) {
        foreach ($input['stok_sites'] as $siteItem) {
            $siteId = isset($siteItem['id_site']) ? (int)$siteItem['id_site'] : 0;
            $stokVal = isset($siteItem['stok']) ? max(0, (int)$siteItem['stok']) : 0;

            if ($siteId > 0) {
                // Upsert stok
                $check = $conn->prepare("SELECT id_stok FROM barang_stok WHERE id_barang = ? AND id_site = ?");
                $check->bind_param("ii", $idBarang, $siteId);
                $check->execute();
                $resC = $check->get_result();

                if ($rowC = $resC->fetch_assoc()) {
                    $idStok = $rowC['id_stok'];
                    $up = $conn->prepare("UPDATE barang_stok SET stok = ? WHERE id_stok = ?");
                    $up->bind_param("ii", $stokVal, $idStok);
                    $up->execute();
                    $up->close();
                } else {
                    $ins = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");
                    $ins->bind_param("iii", $idBarang, $siteId, $stokVal);
                    $ins->execute();
                    $ins->close();
                }
                $check->close();
            }
        }

        jsonResponse(true, 'Stok barang per site berhasil diperbarui.', ['id_barang' => $idBarang], 200);
    }

    // Kasus 2: Single site update
    $idSite = isset($input['id_site']) ? (int)$input['id_site'] : 0;
    $stokVal = isset($input['stok']) ? max(0, (int)$input['stok']) : 0;

    if ($idSite <= 0) {
        jsonResponse(false, 'ID site tidak valid.', null, 422);
    }

    $check = $conn->prepare("SELECT id_stok FROM barang_stok WHERE id_barang = ? AND id_site = ?");
    $check->bind_param("ii", $idBarang, $idSite);
    $check->execute();
    $resC = $check->get_result();

    if ($rowC = $resC->fetch_assoc()) {
        $idStok = $rowC['id_stok'];
        $up = $conn->prepare("UPDATE barang_stok SET stok = ? WHERE id_stok = ?");
        $up->bind_param("ii", $stokVal, $idStok);
        $up->execute();
        $up->close();
    } else {
        $ins = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");
        $ins->bind_param("iii", $idBarang, $idSite, $stokVal);
        $ins->execute();
        $ins->close();
    }
    $check->close();

    jsonResponse(true, 'Stok barang pada site berhasil diperbarui.', [
        'id_barang' => $idBarang,
        'id_site' => $idSite,
        'stok' => $stokVal
    ], 200);
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
