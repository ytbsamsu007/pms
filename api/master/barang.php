<?php
/**
 * API Master: Barang CRUD Endpoint - PT Jaya Teknik
 * Terintegrasi dengan Stok per Site (barang_stok) & Harga per Vendor (barang_hargavendor)
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
// GET: Mengambil Data Barang (List / Detail)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $kategoriId = isset($_GET['kategori_id']) && is_numeric($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : null;
    $merkId = isset($_GET['merk_id']) && is_numeric($_GET['merk_id']) ? (int)$_GET['merk_id'] : null;
    $vendorId = isset($_GET['vendor_id']) && is_numeric($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 50;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (b.nama_barang LIKE ? OR b.kode_barang LIKE ? OR b.serial_number LIKE ? OR b.deskripsi LIKE ? OR k.nama_kategori LIKE ? OR m.nama_merk LIKE ? OR v.nama_perusahaan LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 7; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    if ($kategoriId !== null) {
        $whereSql .= " AND b.id_kategori = ?";
        $params[] = $kategoriId;
        $types .= "i";
    }

    if ($merkId !== null) {
        $whereSql .= " AND b.id_merk = ?";
        $params[] = $merkId;
        $types .= "i";
    }

    if ($vendorId !== null) {
        $whereSql .= " AND b.default_id_vendor = ?";
        $params[] = $vendorId;
        $types .= "i";
    }

    // 1. Total records
    $countSql = "SELECT COUNT(*) as total FROM barang b 
                 LEFT JOIN vendor v ON b.default_id_vendor = v.id_vendor
                 LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori
                 LEFT JOIN merk_barang m ON b.id_merk = m.id_merk" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $orderSql = " ORDER BY b.id_barang DESC";
    if (!empty($search)) {
        $searchEscaped = $conn->real_escape_string($search);
        $orderSql = " ORDER BY 
            CASE 
                WHEN b.nama_barang LIKE '{$searchEscaped}%' THEN 1 
                WHEN b.kode_barang LIKE '{$searchEscaped}%' THEN 2 
                WHEN b.nama_barang LIKE '%{$searchEscaped}%' THEN 3 
                ELSE 4 
            END, b.nama_barang ASC";
    }

    $sql = "SELECT b.id_barang, b.kode_barang, b.nama_barang, b.id_merk, b.id_kategori, b.default_id_vendor,
                   b.jenis, b.satuan, b.asset, b.PPnBM, b.rate_PPnBM, b.serial_number, b.foto1, b.foto2, b.deskripsi, b.created_at, b.id_karyawan, b.aktif,
                   v.nama_perusahaan AS nama_vendor, v.kode_vendor,
                   k.nama_kategori, m.nama_merk, kry.nama_karyawan AS pembuat_barang,
                   COALESCE((SELECT SUM(bs_sub.stok) FROM barang_stok bs_sub JOIN site s_sub ON bs_sub.id_site = s_sub.id_site WHERE bs_sub.id_barang = b.id_barang AND s_sub.penyimpanan_stok = 1), 0) AS total_stok
            FROM barang b
            LEFT JOIN vendor v ON b.default_id_vendor = v.id_vendor
            LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori
            LEFT JOIN merk_barang m ON b.id_merk = m.id_merk
            LEFT JOIN karyawan kry ON b.id_karyawan = kry.id_karyawan"
            . $whereSql . $orderSql . " LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    $barangIds = [];
    while ($row = $res->fetch_assoc()) {
        $bId = (int)$row['id_barang'];
        $barangIds[] = $bId;
        $items[$bId] = [
            'id_barang' => $bId,
            'kode_barang' => $row['kode_barang'] ?? '',
            'nama_barang' => $row['nama_barang'] ?? '',
            'id_merk' => (int)($row['id_merk'] ?? 1),
            'nama_merk' => $row['nama_merk'] ?? 'Umum',
            'id_kategori' => (int)($row['id_kategori'] ?? 1),
            'nama_kategori' => $row['nama_kategori'] ?? 'Umum',
            'default_id_vendor' => $row['default_id_vendor'] ? (int)$row['default_id_vendor'] : null,
            'nama_vendor' => $row['nama_vendor'] ?? '',
            'kode_vendor' => $row['kode_vendor'] ?? '',
            'jenis' => (int)($row['jenis'] ?? 1),
            'jenis_label' => (int)($row['jenis'] ?? 1) === 1 ? 'Persediaan' : 'Jasa',
            'satuan' => $row['satuan'] ?? 'PCS',
            'asset' => (int)($row['asset'] ?? 0),
            'asset_label' => (int)($row['asset'] ?? 0) === 1 ? 'Asset' : 'Bukan Asset',
            'ppnbm' => (int)($row['PPnBM'] ?? 0),
            'PPnBM' => (int)($row['PPnBM'] ?? 0),
            'ppnbm_label' => (int)($row['PPnBM'] ?? 0) === 1 ? 'PPnBM' : 'Non PPnBM',
            'rate_ppnbm' => (float)($row['rate_PPnBM'] ?? 0),
            'rate_PPnBM' => (float)($row['rate_PPnBM'] ?? 0),
            'serial_number' => $row['serial_number'] ?? '',
            'foto1' => $row['foto1'] ?? '',
            'foto2' => $row['foto2'] ?? '',
            'deskripsi' => $row['deskripsi'] ?? '',
            'total_stok' => (int)$row['total_stok'],
            'stok_per_site' => [],
            'harga_vendors' => [],
            'created_at' => $row['created_at'] ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-',
            'pembuat_barang' => $row['pembuat_barang'] ?? '-',
            'aktif' => isset($row['aktif']) ? (int)$row['aktif'] : 1,
            'aktif_label' => (isset($row['aktif']) && (int)$row['aktif'] === 0) ? 'Non-aktif' : 'Aktif'
        ];
    }
    $stmt->close();

    // Query rincian stok per site dan harga vendor jika ada items
    if (!empty($barangIds)) {
        $idListStr = implode(',', $barangIds);

        // A. Stok per Site (Hanya site yang diizinkan menyimpan stok)
        $sqlStok = "SELECT bs.id_stok, bs.id_barang, bs.id_site, bs.stok, s.nama_site, s.kode_site
                    FROM barang_stok bs
                    JOIN site s ON bs.id_site = s.id_site
                    WHERE s.penyimpanan_stok = 1 AND bs.id_barang IN ($idListStr)
                    ORDER BY s.id_site ASC";
        $resStok = $conn->query($sqlStok);
        if ($resStok) {
            while ($sRow = $resStok->fetch_assoc()) {
                $bId = (int)$sRow['id_barang'];
                if (isset($items[$bId])) {
                    $items[$bId]['stok_per_site'][] = [
                        'id_stok' => (int)$sRow['id_stok'],
                        'id_site' => (int)$sRow['id_site'],
                        'nama_site' => $sRow['nama_site'],
                        'kode_site' => $sRow['kode_site'],
                        'stok' => (int)$sRow['stok']
                    ];
                }
            }
        }

        // B. Harga Vendor
        $sqlHarga = "SELECT bh.id_harga, bh.id_barang, bh.id_vendor, bh.harga_set, bh.berlaku,
                            v.nama_perusahaan, v.kode_vendor
                     FROM barang_hargavendor bh
                     JOIN vendor v ON bh.id_vendor = v.id_vendor
                     WHERE bh.id_barang IN ($idListStr)
                     ORDER BY bh.berlaku DESC";
        $resHarga = $conn->query($sqlHarga);
        if ($resHarga) {
            while ($hRow = $resHarga->fetch_assoc()) {
                $bId = (int)$hRow['id_barang'];
                if (isset($items[$bId])) {
                    $items[$bId]['harga_vendors'][] = [
                        'id_harga' => (int)$hRow['id_harga'],
                        'id_vendor' => (int)$hRow['id_vendor'],
                        'nama_vendor' => $hRow['nama_perusahaan'],
                        'kode_vendor' => $hRow['kode_vendor'],
                        'harga_set' => (float)$hRow['harga_set'],
                        'harga_formatted' => 'Rp ' . number_format((float)$hRow['harga_set'], 0, ',', '.'),
                        'berlaku' => $hRow['berlaku'],
                        'berlaku_formatted' => $hRow['berlaku'] ? date('d-m-Y', strtotime($hRow['berlaku'])) : '-'
                    ];
                }
            }
        }
    }

    jsonResponse(true, 'Data master barang berhasil diambil.', [
        'items' => array_values($items),
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
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data master.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
/**
 * Helper: Hapus berkas fisik gambar di images/uploads/ jika ada
 */
function deleteImageFile(?string $relativePath): void {
    if (empty($relativePath)) return;
    $cleanPath = ltrim(str_replace('\\', '/', $relativePath), '/');
    if (strpos($cleanPath, 'images/uploads/') === 0) {
        $fullPath = __DIR__ . '/../../' . $cleanPath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}

// -------------------------------------------------------------
// POST: Tambah Barang Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaBarang = trim($input['nama_barang'] ?? '');
    $kodeBarang = trim($input['kode_barang'] ?? '');
    $satuan = !empty($input['satuan']) ? strtoupper(trim((string)$input['satuan'])) : 'PCS';
    if (strlen($satuan) > 20) { $satuan = substr($satuan, 0, 20); }
    $jenis = isset($input['jenis']) ? (int)$input['jenis'] : 1;
    $asset = isset($input['asset']) ? (int)$input['asset'] : 0;
    $ppnbm = isset($input['PPnBM']) ? (int)$input['PPnBM'] : (isset($input['ppnbm']) ? (int)$input['ppnbm'] : 0);
    $ratePpnbm = isset($input['rate_PPnBM']) ? (float)$input['rate_PPnBM'] : (isset($input['rate_ppnbm']) ? (float)$input['rate_ppnbm'] : 0.0);
    $idVendor = !empty($input['default_id_vendor']) ? (int)$input['default_id_vendor'] : null;
    $idKategori = !empty($input['id_kategori']) ? (int)$input['id_kategori'] : 1;
    $idMerk = !empty($input['id_merk']) ? (int)$input['id_merk'] : 1;
    $serialNumber = trim($input['serial_number'] ?? '');
    $deskripsi = trim($input['deskripsi'] ?? '');
    $idKaryawan = $currentUser['id_karyawan'] ?? 1;
    $foto1 = !empty(trim($input['foto1'] ?? '')) ? trim($input['foto1']) : null;
    $foto2 = !empty(trim($input['foto2'] ?? '')) ? trim($input['foto2']) : null;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaBarang)) {
        jsonResponse(false, 'Nama barang wajib diisi.', null, 422);
    }

    if (empty($kodeBarang)) {
        $resCount = $conn->query("SELECT MAX(id_barang) as max_id FROM barang");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeBarang = 'BRG' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO barang (kode_barang, id_merk, id_kategori, default_id_vendor, nama_barang, jenis, satuan, asset, PPnBM, rate_PPnBM, serial_number, foto1, foto2, deskripsi, id_karyawan, aktif) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiisisiddssssii", $kodeBarang, $idMerk, $idKategori, $idVendor, $namaBarang, $jenis, $satuan, $asset, $ppnbm, $ratePpnbm, $serialNumber, $foto1, $foto2, $deskripsi, $idKaryawan, $aktif);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();

        // 1. Simpan Stok per Site (Tetap simpan ke barang_stok meskipun bernilai 0 / null)
        if (isset($input['stok_sites']) && is_array($input['stok_sites'])) {
            foreach ($input['stok_sites'] as $sItem) {
                $siteId = isset($sItem['id_site']) ? (int)$sItem['id_site'] : 0;
                $stokQty = isset($sItem['stok']) && is_numeric($sItem['stok']) ? max(0, (int)$sItem['stok']) : 0;
                if ($siteId > 0) {
                    $insS = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, ?)");
                    $insS->bind_param("iii", $newId, $siteId, $stokQty);
                    $insS->execute();
                    $insS->close();
                }
            }
        } else {
            // Otomatis buat entri stok = 0 untuk seluruh site penyimpanan aktif
            $resSites = $conn->query("SELECT id_site FROM site WHERE penyimpanan_stok = 1");
            if ($resSites) {
                while ($stRow = $resSites->fetch_assoc()) {
                    $stId = (int)$stRow['id_site'];
                    $insDef = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, 0)");
                    $insDef->bind_param("ii", $newId, $stId);
                    $insDef->execute();
                    $insDef->close();
                }
            }
        }

        // 2. Simpan Harga Vendor jika ada
        if (isset($input['harga_vendors']) && is_array($input['harga_vendors'])) {
            foreach ($input['harga_vendors'] as $hItem) {
                $vId = isset($hItem['id_vendor']) ? (int)$hItem['id_vendor'] : 0;
                $hPrice = isset($hItem['harga_set']) ? (float)$hItem['harga_set'] : 0;
                $hDate = !empty($hItem['berlaku']) ? $hItem['berlaku'] : date('Y-m-d');
                if ($vId > 0 && $hPrice > 0) {
                    $insH = $conn->prepare("INSERT INTO barang_hargavendor (id_barang, id_vendor, harga_set, berlaku) VALUES (?, ?, ?, ?)");
                    $insH->bind_param("iids", $newId, $vId, $hPrice, $hDate);
                    $insH->execute();
                    $insH->close();
                }
            }
        }

        jsonResponse(true, 'Barang berhasil ditambahkan.', ['id_barang' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan barang.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Barang & Bersihkan Berkas Foto yang Dihapus
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idBarang = isset($input['id_barang']) ? (int)$input['id_barang'] : 0;
    $namaBarang = trim($input['nama_barang'] ?? '');
    $kodeBarang = trim($input['kode_barang'] ?? '');
    $satuan = !empty($input['satuan']) ? strtoupper(trim((string)$input['satuan'])) : 'PCS';
    if (strlen($satuan) > 20) { $satuan = substr($satuan, 0, 20); }
    $jenis = isset($input['jenis']) ? (int)$input['jenis'] : 1;
    $asset = isset($input['asset']) ? (int)$input['asset'] : 0;
    $ppnbm = isset($input['PPnBM']) ? (int)$input['PPnBM'] : (isset($input['ppnbm']) ? (int)$input['ppnbm'] : 0);
    $ratePpnbm = isset($input['rate_PPnBM']) ? (float)$input['rate_PPnBM'] : (isset($input['rate_ppnbm']) ? (float)$input['rate_ppnbm'] : 0.0);
    $idVendor = !empty($input['default_id_vendor']) ? (int)$input['default_id_vendor'] : null;
    $idKategori = !empty($input['id_kategori']) ? (int)$input['id_kategori'] : 1;
    $idMerk = !empty($input['id_merk']) ? (int)$input['id_merk'] : 1;
    $serialNumber = trim($input['serial_number'] ?? '');
    $deskripsi = trim($input['deskripsi'] ?? '');
    $foto1 = !empty(trim($input['foto1'] ?? '')) ? trim($input['foto1']) : null;
    $foto2 = !empty(trim($input['foto2'] ?? '')) ? trim($input['foto2']) : null;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if ($idBarang <= 0 || empty($namaBarang)) {
        jsonResponse(false, 'ID barang dan nama barang wajib diisi.', null, 422);
    }

    // Ambil data foto lama dari database untuk mengecek perubahan / penghapusan
    $oldFoto1 = null;
    $oldFoto2 = null;
    $chkOld = $conn->query("SELECT foto1, foto2 FROM barang WHERE id_barang = $idBarang");
    if ($chkOld && $rowOld = $chkOld->fetch_assoc()) {
        $oldFoto1 = $rowOld['foto1'];
        $oldFoto2 = $rowOld['foto2'];
    }

    // Jika foto1 diubah atau dihapus, hapus file lama dari folder uploads
    if (!empty($oldFoto1) && $oldFoto1 !== $foto1) {
        deleteImageFile($oldFoto1);
    }

    // Jika foto2 diubah atau dihapus, hapus file lama dari folder uploads
    if (!empty($oldFoto2) && $oldFoto2 !== $foto2) {
        deleteImageFile($oldFoto2);
    }

    $stmt = $conn->prepare("UPDATE barang SET kode_barang = ?, id_merk = ?, id_kategori = ?, default_id_vendor = ?, 
                            nama_barang = ?, jenis = ?, satuan = ?, asset = ?, PPnBM = ?, rate_PPnBM = ?, serial_number = ?, foto1 = ?, foto2 = ?, deskripsi = ?, aktif = ? 
                            WHERE id_barang = ?");
    $stmt->bind_param("siiisisiddssssii", $kodeBarang, $idMerk, $idKategori, $idVendor, $namaBarang, $jenis, $satuan, $asset, $ppnbm, $ratePpnbm, $serialNumber, $foto1, $foto2, $deskripsi, $aktif, $idBarang);
    
    if ($stmt->execute()) {
        $stmt->close();

        // 1. Simpan / Update Stok per Site (Tetap simpan / update meskipun bernilai 0 / null)
        if (isset($input['stok_sites']) && is_array($input['stok_sites'])) {
            foreach ($input['stok_sites'] as $sItem) {
                $siteId = isset($sItem['id_site']) ? (int)$sItem['id_site'] : 0;
                $stokQty = isset($sItem['stok']) && is_numeric($sItem['stok']) ? max(0, (int)$sItem['stok']) : 0;
                if ($siteId > 0) {
                    $chk = $conn->query("SELECT id_stok FROM barang_stok WHERE id_barang = $idBarang AND id_site = $siteId");
                    if ($chk && $chk->num_rows > 0) {
                        $conn->query("UPDATE barang_stok SET stok = $stokQty WHERE id_barang = $idBarang AND id_site = $siteId");
                    } else {
                        $conn->query("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES ($idBarang, $siteId, $stokQty)");
                    }
                }
            }
        }

        // 2. Simpan Harga Vendor jika ada
        if (isset($input['harga_vendors']) && is_array($input['harga_vendors'])) {
            $conn->query("DELETE FROM barang_hargavendor WHERE id_barang = $idBarang");
            foreach ($input['harga_vendors'] as $hItem) {
                $vId = isset($hItem['id_vendor']) ? (int)$hItem['id_vendor'] : 0;
                $hPrice = isset($hItem['harga_set']) ? (float)$hItem['harga_set'] : 0;
                $hDate = !empty($hItem['berlaku']) ? $hItem['berlaku'] : date('Y-m-d');
                if ($vId > 0 && $hPrice > 0) {
                    $insH = $conn->prepare("INSERT INTO barang_hargavendor (id_barang, id_vendor, harga_set, berlaku) VALUES (?, ?, ?, ?)");
                    $insH->bind_param("iids", $idBarang, $vId, $hPrice, $hDate);
                    $insH->execute();
                    $insH->close();
                }
            }
        }

        jsonResponse(true, 'Data barang berhasil diperbarui.', ['id_barang' => $idBarang], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui data barang.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Barang & Berkas Fisik Gambar
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idBarang = isset($input['id_barang']) ? (int)$input['id_barang'] : (int)($_GET['id'] ?? 0);

    if ($idBarang <= 0) {
        jsonResponse(false, 'ID barang tidak valid.', null, 422);
    }

    // --- Pengecekan Relasi Database ---
    $relations = [
        ['table' => 'request_order_detail', 'col' => 'id_barang', 'label' => 'Detail Request Order'],
        ['table' => 'purchase_order_detail', 'col' => 'id_barang', 'label' => 'Detail Purchase Order'],
        ['table' => 'receiving_order_detail', 'col' => 'id_barang', 'label' => 'Detail Penerimaan Barang'],
        ['table' => 'mutasi_order_detail', 'col' => 'id_barang', 'label' => 'Detail Mutasi Barang'],
        ['table' => 'adjustment_stok_detail', 'col' => 'id_barang', 'label' => 'Detail Adjustment Stok']
    ];
    
    $inUse = [];
    foreach ($relations as $rel) {
        $q = "SELECT COUNT(*) as count FROM {$rel['table']} WHERE {$rel['col']} = ?";
        $stmtRel = $conn->prepare($q);
        if ($stmtRel) {
            $stmtRel->bind_param("i", $idBarang);
            $stmtRel->execute();
            $count = $stmtRel->get_result()->fetch_assoc()['count'] ?? 0;
            $stmtRel->close();
            
            if ($count > 0) {
                $inUse[] = $count . " data " . $rel['label'];
            }
        }
    }
    
    if (count($inUse) > 0) {
        $msg = "Gagal menghapus! Barang ini sedang digunakan pada transaksi: " . implode(", ", $inUse) . ".";
        jsonResponse(false, $msg, null, 409); // 409 Conflict
    }
    // -----------------------------------

    // Ambil path foto untuk dihapus dari server
    $chkOld = $conn->query("SELECT foto1, foto2 FROM barang WHERE id_barang = $idBarang");
    if ($chkOld && $rowOld = $chkOld->fetch_assoc()) {
        deleteImageFile($rowOld['foto1']);
        deleteImageFile($rowOld['foto2']);
    }

    $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
    $stmt->bind_param("i", $idBarang);
    
    if ($stmt->execute()) {
        $stmt->close();
        // Hapus relasi stok dan harga
        $conn->query("DELETE FROM barang_stok WHERE id_barang = $idBarang");
        $conn->query("DELETE FROM barang_hargavendor WHERE id_barang = $idBarang");

        jsonResponse(true, 'Barang berhasil dihapus beserta berkas fotonya.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus barang. Data mungkin terkait dengan transaksi lain.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
