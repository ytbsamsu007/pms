<?php
/**
 * REST API: Manajemen Mutasi Barang (Transfer Antar Site)
 * Path: api/mutasi_order/index.php
 * Khusus Role: ADMIN, LOGISTIK, MANAGER
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
require_once __DIR__ . '/../../config/penomoran_helper.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);

$method = $_SERVER['REQUEST_METHOD'];

// Helper function: Generate Next Kode Mutasi sesuai tabel penomoran
function generateNextKodeMutasi($conn, $tanggal = null, bool $forUpdate = false) {
    if (empty($tanggal) || !strtotime($tanggal)) {
        $tanggal = date('Y-m-d');
    }
    $gen = generateNomorTransaksi($conn, 'MUTASI BARANG', $tanggal, $forUpdate);
    if ($gen && !empty($gen['success']) && !empty($gen['nomor'])) {
        return $gen['nomor'];
    }
    $prefix = 'DI-' . date('ym', strtotime($tanggal)) . '-';
    $sql = "SELECT kode_mutasi FROM mutasi_order WHERE kode_mutasi LIKE ? ORDER BY kode_mutasi DESC LIMIT 1" . ($forUpdate ? " FOR UPDATE" : "");
    $stmt = $conn->prepare($sql);
    $likeParam = $prefix . '%';
    $stmt->bind_param('s', $likeParam);
    $stmt->execute();
    $res = $stmt->get_result();
    $nextSeq = 1;
    if ($row = $res->fetch_assoc()) {
        $lastCode = $row['kode_mutasi'];
        $lastSeq = (int)substr($lastCode, strlen($prefix));
        $nextSeq = $lastSeq + 1;
    }
    $stmt->close();
    return $prefix . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
}

try {
    // ----------------------------------------------------
    // 1. GET METHOD (Listing, Detail, Helper Data)
    // ----------------------------------------------------
    if ($method === 'GET') {
        $action = trim($_GET['action'] ?? '');

        // 1.1 Helper: Generate Next Kode Mutasi
        if ($action === 'next_code') {
            $tgl = trim($_GET['tanggal'] ?? $_GET['date'] ?? '');
            $nextCode = generateNextKodeMutasi($conn, $tgl);
            echo json_encode([
                'success' => true,
                'data' => ['next_code' => $nextCode, 'kode_mutasi' => $nextCode]
            ]);
            exit;
        }

        // 1.2 Helper: Ambil Data Karyawan (Request & Approval Level 1)
        if ($action === 'karyawan_options') {
            // Pemohon (semua karyawan aktif)
            $reqKaryawan = [];
            $resReq = $conn->query("
                SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, j.nama_jabatan, j.level
                FROM karyawan k
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                WHERE k.aktif = 1
                ORDER BY k.nama_karyawan ASC
            ");
            while ($r = $resReq->fetch_assoc()) {
                $reqKaryawan[] = $r;
            }

            // Approved (Hanya Level 1: Manager / Administrator)
            $appKaryawan = [];
            $resApp = $conn->query("
                SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, j.nama_jabatan, j.level
                FROM karyawan k
                INNER JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                WHERE k.aktif = 1 AND j.level = 1
                ORDER BY k.nama_karyawan ASC
            ");
            while ($a = $resApp->fetch_assoc()) {
                $appKaryawan[] = $a;
            }

            // Sites
            $sites = [];
            $resSites = $conn->query("SELECT id_site, kode_site, nama_site, jenis_site FROM site ORDER BY nama_site ASC");
            while ($s = $resSites->fetch_assoc()) {
                $sites[] = $s;
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'karyawan_request' => $reqKaryawan,
                    'karyawan_approved' => $appKaryawan,
                    'sites' => $sites
                ]
            ]);
            exit;
        }

        // 1.3 Helper: Ambil Daftar Barang beserta Stok di Site Asal
        if ($action === 'barang_by_site') {
            $idSiteAsal = isset($_GET['id_site_asal']) ? (int)$_GET['id_site_asal'] : 0;
            if ($idSiteAsal <= 0) {
                echo json_encode(['success' => true, 'data' => []]);
                exit;
            }

            $sql = "
                SELECT 
                    b.id_barang, 
                    b.kode_barang, 
                    b.nama_barang, 
                    b.satuan, 
                    b.serial_number,
                    bs.stok AS stok_site
                FROM barang b
                INNER JOIN barang_stok bs ON b.id_barang = bs.id_barang AND bs.id_site = ?
                WHERE b.aktif = 1 AND bs.stok > 0
                ORDER BY b.nama_barang ASC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $idSiteAsal);
            $stmt->execute();
            $res = $stmt->get_result();
            $barangList = [];
            while ($b = $res->fetch_assoc()) {
                $b['stok_site'] = (float)$b['stok_site'];
                $barangList[] = $b;
            }
            $stmt->close();

            echo json_encode(['success' => true, 'data' => $barangList]);
            exit;
        }

        // 1.4 Single Detail Data Mutasi
        if (isset($_GET['id_mutasi']) && is_numeric($_GET['id_mutasi'])) {
            $idMutasi = (int)$_GET['id_mutasi'];

            $sql = "
                SELECT 
                    mo.*,
                    sa.nama_site AS nama_site_asal,
                    sa.kode_site AS kode_site_asal,
                    sa.jenis_site AS jenis_site_asal,
                    st.nama_site AS nama_site_tujuan,
                    st.kode_site AS kode_site_tujuan,
                    st.jenis_site AS jenis_site_tujuan,
                    kp.nama_karyawan AS nama_pembuat,
                    kr.nama_karyawan AS nama_pemohon,
                    jr.nama_jabatan AS jabatan_pemohon,
                    ka.nama_karyawan AS nama_penyetuju,
                    ja.nama_jabatan AS jabatan_penyetuju
                FROM mutasi_order mo
                LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
                LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
                LEFT JOIN karyawan kp ON mo.id_karyawan = kp.id_karyawan
                LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
                LEFT JOIN jabatan jr ON kr.id_jabatan = jr.id_jabatan
                LEFT JOIN karyawan ka ON mo.id_karyawan_approved = ka.id_karyawan
                LEFT JOIN jabatan ja ON ka.id_jabatan = ja.id_jabatan
                WHERE mo.id_mutasi = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $idMutasi);
            $stmt->execute();
            $res = $stmt->get_result();
            $header = $res->fetch_assoc();
            $stmt->close();

            if (!$header) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Data mutasi tidak ditemukan.']);
                exit;
            }

            // Fetch Detail Items
            $sqlDet = "
                SELECT 
                    modt.*,
                    b.kode_barang,
                    b.nama_barang,
                    b.satuan,
                    b.serial_number,
                    b.deskripsi,
                    COALESCE(bs_asal.stok, 0) AS stok_site_asal
                FROM mutasi_order_detail modt
                LEFT JOIN barang b ON modt.id_barang = b.id_barang
                LEFT JOIN barang_stok bs_asal ON b.id_barang = bs_asal.id_barang AND bs_asal.id_site = ?
                WHERE modt.id_mutasi = ?
                ORDER BY modt.id_mutasi_detail ASC
            ";
            $stmtDet = $conn->prepare($sqlDet);
            $idAsal = (int)$header['id_site_asal'];
            $stmtDet->bind_param('ii', $idAsal, $idMutasi);
            $stmtDet->execute();
            $resDet = $stmtDet->get_result();
            $items = [];
            $totalQty = 0;
            while ($it = $resDet->fetch_assoc()) {
                $it['qty'] = (float)$it['qty'];
                $it['stok_site_asal'] = (float)$it['stok_site_asal'];
                $totalQty += $it['qty'];
                $items[] = $it;
            }
            $stmtDet->close();

            $header['biaya_operasional'] = (float)$header['biaya_operasional'];
            $header['biaya_operasional_formatted'] = 'Rp ' . number_format($header['biaya_operasional'], 0, ',', '.');
            $header['tanggal_mutasi_formatted'] = $header['tanggal_mutasi'] ? date('d-m-Y H:i', strtotime($header['tanggal_mutasi'])) : '-';
            $header['total_qty'] = $totalQty;
            $header['total_items'] = count($items);
            $header['items'] = $items;

            echo json_encode(['success' => true, 'data' => $header]);
            exit;
        }

        // 1.5 Main Listing with Search, Filters & Pagination
        $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
        $idSiteAsal = isset($_GET['id_site_asal']) && is_numeric($_GET['id_site_asal']) ? (int)$_GET['id_site_asal'] : 0;
        $idSiteTujuan = isset($_GET['id_site_tujuan']) && is_numeric($_GET['id_site_tujuan']) ? (int)$_GET['id_site_tujuan'] : 0;
        $status = trim($_GET['status'] ?? '');
        $startDate = trim($_GET['start_date'] ?? '');
        $endDate = trim($_GET['end_date'] ?? '');
        
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 25;
        if ($limit < 0) $limit = 25;

        $where = ["1=1"];
        $params = [];
        $types = "";

        if ($idSiteAsal > 0) {
            $where[] = "mo.id_site_asal = ?";
            $params[] = $idSiteAsal;
            $types .= "i";
        }

        if ($idSiteTujuan > 0) {
            $where[] = "mo.id_site_tujuan = ?";
            $params[] = $idSiteTujuan;
            $types .= "i";
        }

        if (!empty($status)) {
            $where[] = "mo.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($startDate)) {
            $where[] = "DATE(mo.tanggal_mutasi) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }

        if (!empty($endDate)) {
            $where[] = "DATE(mo.tanggal_mutasi) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        if (!empty($search)) {
            $where[] = "(
                mo.kode_mutasi LIKE ? OR 
                mo.nomor_surat_mutasi LIKE ? OR 
                kr.nama_karyawan LIKE ? OR 
                sa.nama_site LIKE ? OR 
                st.nama_site LIKE ? OR
                mo.keterangan LIKE ?
            )";
            $wildcard = "%$search%";
            for ($i = 0; $i < 6; $i++) {
                $params[] = $wildcard;
                $types .= "s";
            }
        }

        $whereSql = implode(" AND ", $where);

        // Count Total Records
        $countSql = "
            SELECT COUNT(*) AS total
            FROM mutasi_order mo
            LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
            LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
            LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
            WHERE $whereSql
        ";
        $stmtCount = $conn->prepare($countSql);
        if (!empty($params)) {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $totalRecords = (int)$stmtCount->get_result()->fetch_assoc()['total'];
        $stmtCount->close();

        $totalPages = ($limit > 0) ? (int)ceil($totalRecords / $limit) : 1;
        if ($totalPages < 1) $totalPages = 1;
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($limit > 0) ? ($page - 1) * $limit : 0;

        // Fetch Data
        $dataSql = "
            SELECT 
                mo.id_mutasi,
                mo.kode_mutasi,
                mo.nomor_surat_mutasi,
                mo.tanggal_mutasi,
                mo.status,
                mo.biaya_operasional,
                mo.keterangan,
                mo.created_at,
                sa.id_site AS id_site_asal,
                sa.nama_site AS nama_site_asal,
                sa.kode_site AS kode_site_asal,
                st.id_site AS id_site_tujuan,
                st.nama_site AS nama_site_tujuan,
                st.kode_site AS kode_site_tujuan,
                kp.nama_karyawan AS nama_pembuat,
                kr.nama_karyawan AS nama_pemohon,
                ka.nama_karyawan AS nama_penyetuju,
                (SELECT COUNT(*) FROM mutasi_order_detail WHERE id_mutasi = mo.id_mutasi) AS total_items,
                (SELECT COALESCE(SUM(qty), 0) FROM mutasi_order_detail WHERE id_mutasi = mo.id_mutasi) AS total_qty
            FROM mutasi_order mo
            LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
            LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
            LEFT JOIN karyawan kp ON mo.id_karyawan = kp.id_karyawan
            LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
            LEFT JOIN karyawan ka ON mo.id_karyawan_approved = ka.id_karyawan
            WHERE $whereSql
            ORDER BY mo.tanggal_mutasi DESC, mo.id_mutasi DESC
        ";

        if ($limit > 0) {
            $dataSql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

        $stmtData = $conn->prepare($dataSql);
        if (!empty($params)) {
            $stmtData->bind_param($types, ...$params);
        }
        $stmtData->execute();
        $resData = $stmtData->get_result();
        
        $items = [];
        while ($row = $resData->fetch_assoc()) {
            $row['biaya_operasional'] = (float)$row['biaya_operasional'];
            $row['biaya_operasional_formatted'] = 'Rp ' . number_format($row['biaya_operasional'], 0, ',', '.');
            $row['tanggal_mutasi_formatted'] = $row['tanggal_mutasi'] ? date('d-m-Y', strtotime($row['tanggal_mutasi'])) : '-';
            $row['waktu_mutasi_formatted'] = $row['tanggal_mutasi'] ? date('H:i', strtotime($row['tanggal_mutasi'])) : '-';
            $row['total_qty'] = (float)$row['total_qty'];
            $row['total_items'] = (int)$row['total_items'];
            $items[] = $row;
        }
        $stmtData->close();

        $from = $totalRecords > 0 ? ($offset + 1) : 0;
        $to = ($limit > 0) ? min($offset + $limit, $totalRecords) : $totalRecords;

        // Ambil data Sites untuk Filter
        $sites = [];
        $resSites = $conn->query("SELECT id_site, kode_site, nama_site FROM site ORDER BY nama_site ASC");
        while ($s = $resSites->fetch_assoc()) {
            $sites[] = $s;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Data mutasi order berhasil dimuat.',
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total_records' => $totalRecords,
                    'total_pages' => $totalPages,
                    'from' => $from,
                    'to' => $to
                ],
                'sites' => $sites
            ]
        ]);
        exit;
    }

    // ----------------------------------------------------
    // 2. POST METHOD (Create New Mutasi)
    // ----------------------------------------------------
    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $body = json_decode($rawInput, true);
        if (!$body && !empty($_POST)) {
            $body = $_POST;
        }

        $isPut = isset($body['_method']) && strtoupper($body['_method']) === 'PUT';
        $isDelete = isset($body['_method']) && strtoupper($body['_method']) === 'DELETE';

        if ($isDelete) {
            // Handle Delete
            $idMutasi = isset($body['id_mutasi']) ? (int)$body['id_mutasi'] : 0;
            if ($idMutasi <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID mutasi tidak valid.']);
                exit;
            }

            // Cek status
            $chk = $conn->query("SELECT status FROM mutasi_order WHERE id_mutasi = $idMutasi")->fetch_assoc();
            if (!$chk) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Data mutasi tidak ditemukan.']);
                exit;
            }

            if (!in_array($chk['status'], ['DRAFT', 'BATAL'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Hanya transaksi berstatus DRAFT atau BATAL yang dapat dihapus.']);
                exit;
            }

            $conn->begin_transaction();
            try {
                $conn->query("DELETE FROM mutasi_order_detail WHERE id_mutasi = $idMutasi");
                $conn->query("DELETE FROM mutasi_order WHERE id_mutasi = $idMutasi");
                $conn->commit();

                echo json_encode(['success' => true, 'message' => 'Data mutasi berhasil dihapus.']);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                throw $e;
            }
        }

        if ($isPut) {
            // Handle Update / Status Change
            $idMutasi = isset($body['id_mutasi']) ? (int)$body['id_mutasi'] : 0;
            if ($idMutasi <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID mutasi tidak valid.']);
                exit;
            }

            $action = trim($body['action'] ?? 'update_data');

            // 2.1 Update Status Saja
            if ($action === 'update_status') {
                $newStatus = trim($body['status'] ?? '');
                $validStatuses = ['DRAFT', 'MENUNGGU PERSETUJUAN', 'DISETUJUI', 'DIKIRIM SITE ASAL', 'DITERIMA SITE TUJUAN', 'BATAL'];
                if (!in_array($newStatus, $validStatuses)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
                    exit;
                }

                $cur = $conn->query("SELECT * FROM mutasi_order WHERE id_mutasi = $idMutasi")->fetch_assoc();
                if (!$cur) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Data mutasi tidak ditemukan.']);
                    exit;
                }

                $oldStatus = $cur['status'];
                if ($oldStatus === $newStatus) {
                    echo json_encode(['success' => true, 'message' => 'Status mutasi tidak berubah.']);
                    exit;
                }

                if ($oldStatus === 'DITERIMA SITE TUJUAN') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Mutasi dengan status DITERIMA SITE TUJUAN telah selesai (final) dan tidak dapat diubah lagi.']);
                    exit;
                }

                if ($oldStatus === 'BATAL') {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Mutasi dengan status BATAL telah ditutup dan tidak dapat diubah lagi.']);
                    exit;
                }

                // Proses Mutasi Stok jika status berubah ke DIKIRIM SITE ASAL atau DITERIMA SITE TUJUAN
                $conn->begin_transaction();
                try {
                    // Update status mutasi_order
                    $stmtUp = $conn->prepare("UPDATE mutasi_order SET status = ?, updated_at = NOW() WHERE id_mutasi = ?");
                    $stmtUp->bind_param('si', $newStatus, $idMutasi);
                    $stmtUp->execute();
                    $stmtUp->close();

                    // Ambil detail item mutasi
                    $itemsRes = $conn->query("SELECT id_barang, qty FROM mutasi_order_detail WHERE id_mutasi = $idMutasi");
                    $items = [];
                    while ($it = $itemsRes->fetch_assoc()) {
                        $items[] = $it;
                    }

                    $idAsal = (int)$cur['id_site_asal'];
                    $idTujuan = (int)$cur['id_site_tujuan'];

                    // ATURAN MUTASI STOK:
                    // Hanya saat status berubah menjadi 'DITERIMA SITE TUJUAN', stok antar site diperbarui:
                    // Stok Site Asal berkurang (-qty) dan Stok Site Tujuan bertambah (+qty)
                    if ($newStatus === 'DITERIMA SITE TUJUAN' && $oldStatus !== 'DITERIMA SITE TUJUAN') {
                        foreach ($items as $it) {
                            $bId = (int)$it['id_barang'];
                            $qty = (float)$it['qty'];

                            // 1. Kurangi di Site Asal
                            $conn->query("
                                INSERT INTO barang_stok (id_barang, id_site, stok) 
                                VALUES ($bId, $idAsal, 0)
                                ON DUPLICATE KEY UPDATE stok = GREATEST(0, stok - $qty)
                            ");

                            // 2. Tambah di Site Tujuan
                            $conn->query("
                                INSERT INTO barang_stok (id_barang, id_site, stok) 
                                VALUES ($bId, $idTujuan, $qty)
                                ON DUPLICATE KEY UPDATE stok = stok + $qty
                            ");
                        }
                    }

                    // Kasus Rollback: Jika dibatalkan setelah sebelumnya pernah DITERIMA SITE TUJUAN
                    if ($newStatus === 'BATAL' && $oldStatus === 'DITERIMA SITE TUJUAN') {
                        foreach ($items as $it) {
                            $bId = (int)$it['id_barang'];
                            $qty = (float)$it['qty'];
                            // Kembalikan ke asal
                            $conn->query("
                                INSERT INTO barang_stok (id_barang, id_site, stok) 
                                VALUES ($bId, $idAsal, $qty)
                                ON DUPLICATE KEY UPDATE stok = stok + $qty
                            ");
                            // Kurangi dari tujuan
                            $conn->query("
                                UPDATE barang_stok SET stok = GREATEST(0, stok - $qty) WHERE id_barang = $bId AND id_site = $idTujuan
                            ");
                        }
                    }

                    $conn->commit();

                    // ----------------------------------------------------
                    // PENGIRIMAN NOTIFIKASI EMAIL KETIKA DITERIMA SITE TUJUAN
                    // ----------------------------------------------------
                    $emailSent = false;
                    $emailMsg = '';
                    if ($newStatus === 'DITERIMA SITE TUJUAN') {
                        try {
                            require_once __DIR__ . '/../../config/mailer.php';

                            // Ambil data detail untuk isi email
                            $mailDataSql = "
                                SELECT 
                                    mo.*,
                                    sa.nama_site AS nama_site_asal,
                                    st.nama_site AS nama_site_tujuan,
                                    kr.nama_karyawan AS nama_pemohon,
                                    ka.nama_karyawan AS nama_penyetuju,
                                    ka.email AS email_penyetuju
                                FROM mutasi_order mo
                                LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
                                LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
                                LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
                                LEFT JOIN karyawan ka ON mo.id_karyawan_approved = ka.id_karyawan
                                WHERE mo.id_mutasi = $idMutasi
                            ";
                            $mData = $conn->query($mailDataSql)->fetch_assoc();

                            if ($mData && !empty($mData['email_penyetuju'])) {
                                $toEmail = $mData['email_penyetuju'];
                                $toName = $mData['nama_penyetuju'];
                                $subject = "[MUTASI SELESAI] Barang Telah Diterima di {$mData['nama_site_tujuan']} - {$mData['kode_mutasi']}";

                                $itemsTableHtml = '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; font-family:sans-serif; font-size:13px;">
                                    <tr style="background:#f0f0f0;">
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Nama Barang</th>
                                        <th>Qty</th>
                                    </tr>';
                                $itQ = $conn->query("
                                    SELECT modt.qty, b.kode_barang, b.nama_barang, b.satuan 
                                    FROM mutasi_order_detail modt 
                                    JOIN barang b ON modt.id_barang = b.id_barang 
                                    WHERE modt.id_mutasi = $idMutasi
                                ");
                                $no = 1;
                                while ($itRow = $itQ->fetch_assoc()) {
                                    $itemsTableHtml .= "<tr>
                                        <td align='center'>{$no}</td>
                                        <td>{$itRow['kode_barang']}</td>
                                        <td>{$itRow['nama_barang']}</td>
                                        <td align='right'><strong>{$itRow['qty']} {$itRow['satuan']}</strong></td>
                                    </tr>";
                                    $no++;
                                }
                                $itemsTableHtml .= '</table>';

                                $htmlBody = "
                                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                                        <h3 style='color: #0d6efd; margin-bottom: 5px;'>Pemberitahuan Penerimaan Mutasi Barang</h3>
                                        <p>Yth. <strong>{$toName}</strong> (Pejabat Penyetuju / Level 1),</p>
                                        <p>Transaksi transfer/mutasi material antar-site berikut telah <strong>DITERIMA DENGAN SUKSES</strong> di site tujuan dan stok persediaan telah resmi diperbarui:</p>
                                        
                                        <div style='background: #f8f9fa; border: 1px solid #e9ecef; padding: 12px 15px; border-radius: 6px; margin-bottom: 15px;'>
                                            <table style='width: 100%; font-size: 13px;'>
                                                <tr><td style='width: 140px;'><strong>Kode Mutasi</strong></td><td>: <span style='color: #0d6efd; font-family: monospace; font-weight: bold;'>{$mData['kode_mutasi']}</span></td></tr>
                                                <tr><td><strong>Site Asal (Pengirim)</strong></td><td>: {$mData['nama_site_asal']}</td></tr>
                                                <tr><td><strong>Site Tujuan (Penerima)</strong></td><td>: {$mData['nama_site_tujuan']}</td></tr>
                                                <tr><td><strong>Pemohon Mutasi</strong></td><td>: {$mData['nama_pemohon']}</td></tr>
                                                <tr><td><strong>Status</strong></td><td>: <span style='background: #198754; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px;'>DITERIMA SITE TUJUAN</span></td></tr>
                                                <tr><td><strong>Tanggal Transaksi</strong></td><td>: " . date('d-m-Y H:i', strtotime($mData['tanggal_mutasi'])) . " WITA</td></tr>
                                            </table>
                                        </div>

                                        <h4 style='margin-bottom: 8px;'>Rincian Barang Dimutasi:</h4>
                                        {$itemsTableHtml}

                                        <p style='margin-top: 20px; font-size: 12px; color: #6c757d;'>Email ini dikirim secara otomatis oleh Sistem Pembelian & Logistik PT Jaya Teknik.</p>
                                    </div>
                                ";

                                $mRes = sendSmtpEmail($conn, $toEmail, $toName, $subject, $htmlBody);
                                $emailSent = !empty($mRes['success']);
                            }
                        } catch (Throwable $eMail) {
                            error_log("Gagal mengirim email notifikasi mutasi diterima: " . $eMail->getMessage());
                        }
                    }

                    echo json_encode([
                        'success' => true,
                        'message' => "Status mutasi berhasil diubah menjadi: $newStatus",
                        'email_sent' => $emailSent
                    ]);
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();
                    throw $e;
                }
            }

            // 2.2 Update Penuh Form Mutasi (Status DRAFT)
            $kodeMutasi = trim($body['kode_mutasi'] ?? '');
            $nomorSurat = trim($body['nomor_surat_mutasi'] ?? '');
            $tglMutasi = trim($body['tanggal_mutasi'] ?? date('Y-m-d H:i:s'));
            $idSiteAsal = (int)($body['id_site_asal'] ?? 0);
            $idSiteTujuan = (int)($body['id_site_tujuan'] ?? 0);
            $idReq = (int)($body['id_karyawan_request'] ?? 0);
            $idApp = !empty($body['id_karyawan_approved']) ? (int)$body['id_karyawan_approved'] : null;
            $biaya = (float)($body['biaya_operasional'] ?? 0);
            $ket = trim($body['keterangan'] ?? '');
            $items = $body['items'] ?? [];

            if ($idSiteAsal <= 0 || $idSiteTujuan <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Site Asal dan Site Tujuan wajib dipilih.']);
                exit;
            }
            if ($idSiteAsal === $idSiteTujuan) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Site Tujuan tidak boleh sama dengan Site Asal.']);
                exit;
            }
            if ($idReq <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Karyawan pemohon transfer wajib dipilih.']);
                exit;
            }
            if (empty($items) || !is_array($items)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Minimal 1 item barang harus ditambahkan.']);
                exit;
            }

            $conn->begin_transaction();
            try {
                $sqlUp = "
                    UPDATE mutasi_order SET 
                        nomor_surat_mutasi = ?,
                        tanggal_mutasi = ?,
                        id_site_asal = ?,
                        id_site_tujuan = ?,
                        id_karyawan_request = ?,
                        id_karyawan_approved = ?,
                        biaya_operasional = ?,
                        keterangan = ?,
                        updated_at = NOW()
                    WHERE id_mutasi = ?
                ";
                $stmtUp = $conn->prepare($sqlUp);
                $stmtUp->bind_param('ssiiisdsi', $nomorSurat, $tglMutasi, $idSiteAsal, $idSiteTujuan, $idReq, $idApp, $biaya, $ket, $idMutasi);
                $stmtUp->execute();
                $stmtUp->close();

                // Replace items
                $conn->query("DELETE FROM mutasi_order_detail WHERE id_mutasi = $idMutasi");

                // Cari max id_mutasi_detail untuk primary key manual jika tidak auto increment
                $maxIdRes = $conn->query("SELECT COALESCE(MAX(id_mutasi_detail), 0) AS max_id FROM mutasi_order_detail");
                $currDetId = (int)$maxIdRes->fetch_assoc()['max_id'];

                $stmtIt = $conn->prepare("INSERT INTO mutasi_order_detail (id_mutasi_detail, id_mutasi, id_barang, qty) VALUES (?, ?, ?, ?)");
                foreach ($items as $it) {
                    $currDetId++;
                    $bId = (int)($it['id_barang'] ?? 0);
                    $qty = (float)($it['qty'] ?? 0);
                    if ($bId > 0 && $qty > 0) {
                        $stmtIt->bind_param('iiid', $currDetId, $idMutasi, $bId, $qty);
                        $stmtIt->execute();
                    }
                }
                $stmtIt->close();

                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Data mutasi order berhasil diperbarui.',
                    'data' => ['id_mutasi' => $idMutasi]
                ]);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                throw $e;
            }
        }

        $tglMutasi = trim($body['tanggal_mutasi'] ?? date('Y-m-d H:i:s'));
        $nomorSurat = trim($body['nomor_surat_mutasi'] ?? '');
        $idSiteAsal = (int)($body['id_site_asal'] ?? 0);
        $idSiteTujuan = (int)($body['id_site_tujuan'] ?? 0);
        $idCreator = (int)($user['id_karyawan'] ?? 1);
        $idReq = (int)($body['id_karyawan_request'] ?? 0);
        $idApp = !empty($body['id_karyawan_approved']) ? (int)$body['id_karyawan_approved'] : null;
        $status = trim($body['status'] ?? 'DRAFT');
        $biaya = (float)($body['biaya_operasional'] ?? 0);
        $ket = trim($body['keterangan'] ?? '');
        $items = $body['items'] ?? [];

        if ($idSiteAsal <= 0 || $idSiteTujuan <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Site Asal dan Site Tujuan wajib dipilih.']);
            exit;
        }
        if ($idSiteAsal === $idSiteTujuan) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Site Tujuan tidak boleh sama dengan Site Asal.']);
            exit;
        }
        if ($idReq <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Karyawan pemohon transfer wajib dipilih.']);
            exit;
        }
        if (empty($items) || !is_array($items)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Minimal 1 item barang harus ditambahkan.']);
            exit;
        }

        $conn->begin_transaction();
        try {
            $kodeMutasi = trim($body['kode_mutasi'] ?? '');
            if (empty($kodeMutasi)) {
                $kodeMutasi = generateNextKodeMutasi($conn, $tglMutasi, true);
            }

            $sqlIns = "
                INSERT INTO mutasi_order (
                    kode_mutasi,
                    nomor_surat_mutasi,
                    tanggal_mutasi,
                    id_karyawan,
                    id_karyawan_request,
                    id_karyawan_approved,
                    id_site_asal,
                    id_site_tujuan,
                    status,
                    biaya_operasional,
                    keterangan,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
            $stmtIns = $conn->prepare($sqlIns);
            $stmtIns->bind_param('sssiiiiisds', $kodeMutasi, $nomorSurat, $tglMutasi, $idCreator, $idReq, $idApp, $idSiteAsal, $idSiteTujuan, $status, $biaya, $ket);
            $stmtIns->execute();
            $newIdMutasi = $stmtIns->insert_id;
            $stmtIns->close();

            // Insert Detail Items
            $maxIdRes = $conn->query("SELECT COALESCE(MAX(id_mutasi_detail), 0) AS max_id FROM mutasi_order_detail");
            $currDetId = (int)$maxIdRes->fetch_assoc()['max_id'];

            $stmtIt = $conn->prepare("INSERT INTO mutasi_order_detail (id_mutasi_detail, id_mutasi, id_barang, qty) VALUES (?, ?, ?, ?)");
            foreach ($items as $it) {
                $currDetId++;
                $bId = (int)($it['id_barang'] ?? 0);
                $qty = (float)($it['qty'] ?? 0);
                if ($bId > 0 && $qty > 0) {
                    $stmtIt->bind_param('iiid', $currDetId, $newIdMutasi, $bId, $qty);
                    $stmtIt->execute();
                }
            }
            $stmtIt->close();

            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Data transaksi mutasi barang berhasil dibuat.',
                'data' => [
                    'id_mutasi' => $newIdMutasi,
                    'kode_mutasi' => $kodeMutasi
                ]
            ]);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
    ]);
}
