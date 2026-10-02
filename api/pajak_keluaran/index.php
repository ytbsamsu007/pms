<?php
/**
 * REST API: CRUD Pajak Keluaran (PPN Keluaran Penjualan / Hasil)
 * Endpoint: /api/pajak_keluaran/index.php
 * Path: api/pajak_keluaran/index.php
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
require_once __DIR__ . '/../../config/activity_logger.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);
$method = $_SERVER['REQUEST_METHOD'];

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper Nama Bulan Indonesia
$namaBulanIndo = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April',   '05' => 'Mei',      '06' => 'Juni',
    '07' => 'Juli',    '08' => 'Agustus',  '09' => 'September',
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// =========================================================================
// 1. GET: Ambil Detail Tunggal / List dengan Filter & Ringkasan / Pagination
// =========================================================================
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_pajak_keluaran']) ? intval($_GET['id_pajak_keluaran']) : 0);

    // Detail Tunggal
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT pk.*, k.nama_karyawan, k.kode_karyawan, j.nama_jabatan
                                FROM pajak_keluaran pk
                                LEFT JOIN karyawan k ON pk.id_karyawan = k.id_karyawan
                                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                                WHERE pk.id_pajak_keluaran = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        $stmt->close();

        if (!$data) {
            sendJson(false, 'Data pajak keluaran tidak ditemukan.', null, 404);
        }

        $bulanStr = str_pad($data['bulan'], 2, '0', STR_PAD_LEFT);
        $data['bulan_formatted'] = $bulanStr;
        $data['nama_bulan'] = $namaBulanIndo[$bulanStr] ?? $bulanStr;
        $data['periode_formatted'] = ($namaBulanIndo[$bulanStr] ?? $bulanStr) . ' ' . $data['tahun'];

        sendJson(true, 'Data pajak keluaran ditemukan.', $data);
    }

    $action = trim($_GET['action'] ?? '');

    // MODE REKAPITULASI TAHUNAN PER BULAN (12 BULAN)
    if ($action === 'rekapitulasi') {
        $tahun = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? trim($_GET['tahun']) : date('Y');

        $stmtRekap = $conn->prepare("SELECT 
                                        pk.tahun,
                                        pk.bulan,
                                        COUNT(pk.id_pajak_keluaran) as jumlah_record,
                                        SUM(pk.ppn_keluaran) as total_ppn_keluaran,
                                        GROUP_CONCAT(pk.keterangan SEPARATOR '; ') as keterangan_gabung
                                     FROM pajak_keluaran pk
                                     WHERE pk.tahun = ?
                                     GROUP BY pk.tahun, pk.bulan
                                     ORDER BY CAST(pk.bulan AS UNSIGNED) ASC");
        $stmtRekap->bind_param("s", $tahun);
        $stmtRekap->execute();
        $resRekap = $stmtRekap->get_result();

        $rekapMap = [];
        while ($r = $resRekap->fetch_assoc()) {
            $bInt = (int)$r['bulan'];
            $rekapMap[$bInt] = [
                'jumlah_record' => (int)$r['jumlah_record'],
                'total_ppn_keluaran' => (float)$r['total_ppn_keluaran'],
                'keterangan' => $r['keterangan_gabung']
            ];
        }
        $stmtRekap->close();

        $rows = [];
        $grandTotalPpn = 0;
        $grandTotalRecord = 0;

        for ($m = 1; $m <= 12; $m++) {
            $mPad = str_pad((string)$m, 2, '0', STR_PAD_LEFT);
            $namaBln = $namaBulanIndo[$mPad] ?? "Bulan $m";
            $dataBln = $rekapMap[$m] ?? ['jumlah_record' => 0, 'total_ppn_keluaran' => 0, 'keterangan' => ''];

            $grandTotalPpn += $dataBln['total_ppn_keluaran'];
            $grandTotalRecord += $dataBln['jumlah_record'];

            $rows[] = [
                'tahun' => (int)$tahun,
                'bulan' => $m,
                'bulan_formatted' => $mPad,
                'nama_bulan' => $namaBln,
                'jumlah_record' => $dataBln['jumlah_record'],
                'total_ppn_keluaran' => $dataBln['total_ppn_keluaran'],
                'keterangan' => $dataBln['keterangan']
            ];
        }

        sendJson(true, "Rekapitulasi Pajak Keluaran Tahun {$tahun} berhasil dimuat.", [
            'tahun' => (int)$tahun,
            'rows' => $rows,
            'grand_totals' => [
                'total_record' => $grandTotalRecord,
                'grand_total_ppn_keluaran' => $grandTotalPpn
            ]
        ]);
    }

    // List & Summary
    $tahun = trim($_GET['tahun'] ?? '');
    $bulan = trim($_GET['bulan'] ?? '');
    $q = trim($_GET['q'] ?? '');
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(5, min(100, intval($_GET['limit'] ?? 15)));
    $isAll = isset($_GET['all']) && (int)$_GET['all'] === 1;
    $offset = ($page - 1) * $limit;

    $where = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($tahun) && $tahun !== '0') {
        $where[] = "pk.tahun = ?";
        $params[] = $tahun;
        $types .= "s";
    }

    if (!empty($bulan) && $bulan !== '0') {
        $bulanPad = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $where[] = "pk.bulan = ?";
        $params[] = $bulanPad;
        $types .= "s";
    }

    if (!empty($q)) {
        $where[] = "(pk.keterangan LIKE ? OR k.nama_karyawan LIKE ? OR pk.tahun LIKE ?)";
        $likeQ = "%$q%";
        $params[] = $likeQ;
        $params[] = $likeQ;
        $params[] = $likeQ;
        $types .= "sss";
    }

    $whereClause = implode(" AND ", $where);

    // Hitung Metrik Ringkasan (Total PPN Keluaran Tahun/Filter & Total Record)
    $sqlSummary = "SELECT COUNT(*) as total_records, 
                          COALESCE(SUM(pk.ppn_keluaran), 0) as total_ppn_keluaran,
                          COALESCE(AVG(pk.ppn_keluaran), 0) as rata_ppn_keluaran,
                          COALESCE(MAX(pk.ppn_keluaran), 0) as max_ppn_keluaran
                   FROM pajak_keluaran pk
                   LEFT JOIN karyawan k ON pk.id_karyawan = k.id_karyawan
                   WHERE $whereClause";
    $stmtSummary = $conn->prepare($sqlSummary);
    if (!empty($params)) {
        $stmtSummary->bind_param($types, ...$params);
    }
    $stmtSummary->execute();
    $summary = $stmtSummary->get_result()->fetch_assoc();
    $stmtSummary->close();

    $totalRecords = (int)($summary['total_records'] ?? 0);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;

    // Fetch Rows
    $sqlRows = "SELECT pk.*, k.nama_karyawan, k.kode_karyawan, j.nama_jabatan
                FROM pajak_keluaran pk
                LEFT JOIN karyawan k ON pk.id_karyawan = k.id_karyawan
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                WHERE $whereClause
                ORDER BY pk.tahun DESC, CAST(pk.bulan AS UNSIGNED) DESC, pk.id_pajak_keluaran DESC";
    
    $fetchParams = $params;
    $fetchTypes = $types;

    if (!$isAll) {
        $sqlRows .= " LIMIT ? OFFSET ?";
        $fetchParams[] = $limit;
        $fetchParams[] = $offset;
        $fetchTypes .= "ii";
    }

    $stmtRows = $conn->prepare($sqlRows);
    if (!empty($fetchParams)) {
        $stmtRows->bind_param($fetchTypes, ...$fetchParams);
    }
    $stmtRows->execute();
    $resRows = $stmtRows->get_result();

    $items = [];
    while ($row = $resRows->fetch_assoc()) {
        $bStr = str_pad($row['bulan'], 2, '0', STR_PAD_LEFT);
        $row['bulan_formatted'] = $bStr;
        $row['nama_bulan'] = $namaBulanIndo[$bStr] ?? $bStr;
        $row['periode_formatted'] = ($namaBulanIndo[$bStr] ?? $bStr) . ' ' . $row['tahun'];
        $row['ppn_keluaran'] = (float)$row['ppn_keluaran'];
        $items[] = $row;
    }
    $stmtRows->close();

    sendJson(true, 'Data pajak keluaran berhasil dimuat.', [
        'items' => $items,
        'summary' => [
            'total_records' => $totalRecords,
            'total_ppn_keluaran' => (float)$summary['total_ppn_keluaran'],
            'rata_ppn_keluaran' => (float)$summary['rata_ppn_keluaran'],
            'max_ppn_keluaran' => (float)$summary['max_ppn_keluaran']
        ],
        'pagination' => [
            'total_records' => $totalRecords,
            'total_pages' => (int)$totalPages,
            'current_page' => (int)$page,
            'limit' => (int)$limit
        ]
    ]);
}

// =========================================================================
// 2. POST: Tambah Data Pajak Keluaran Baru
// =========================================================================
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) $input = $_POST;

    $tahun = trim($input['tahun'] ?? date('Y'));
    $bulan = trim($input['bulan'] ?? date('m'));
    $ppnKeluaran = floatval($input['ppn_keluaran'] ?? 0);
    $keterangan = trim($input['keterangan'] ?? '');
    $idKaryawan = !empty($user['id_karyawan']) ? intval($user['id_karyawan']) : null;

    if (empty($tahun) || strlen($tahun) !== 4 || !is_numeric($tahun)) {
        sendJson(false, 'Format tahun tidak valid (harus 4 digit angka, contoh: 2026).', null, 422);
    }

    $bulanInt = intval($bulan);
    if ($bulanInt < 1 || $bulanInt > 12) {
        sendJson(false, 'Bulan tidak valid (harus 1 sampai 12).', null, 422);
    }
    $bulanPad = str_pad((string)$bulanInt, 2, '0', STR_PAD_LEFT);

    if ($ppnKeluaran < 0) {
        sendJson(false, 'Nilai PPN Keluaran tidak boleh bernilai negatif.', null, 422);
    }

    // Cek duplikasi periode tahun & bulan
    $cek = $conn->prepare("SELECT id_pajak_keluaran FROM pajak_keluaran WHERE tahun = ? AND bulan = ? LIMIT 1");
    $cek->bind_param("ss", $tahun, $bulanPad);
    $cek->execute();
    $existing = $cek->get_result()->fetch_assoc();
    $cek->close();

    if ($existing) {
        $namaBulan = $namaBulanIndo[$bulanPad] ?? $bulanPad;
        sendJson(false, "Data pajak keluaran untuk periode {$namaBulan} {$tahun} sudah ada. Silakan edit data yang sudah ada.", null, 409);
    }

    $stmt = $conn->prepare("INSERT INTO pajak_keluaran (id_karyawan, tahun, bulan, ppn_keluaran, keterangan, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("issds", $idKaryawan, $tahun, $bulanPad, $ppnKeluaran, $keterangan);

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        $stmt->close();

        // Log Aktivitas
        logActivity($conn, [
            'modul' => 'PAJAK_KELUARAN',
            'aksi' => 'CREATE',
            'id_referensi' => $newId,
            'nomor_referensi' => "{$bulanPad}/{$tahun}",
            'deskripsi' => "Menambahkan data pajak keluaran periode {$bulanPad}/{$tahun} senilai Rp " . number_format($ppnKeluaran, 0, ',', '.'),
            'id_karyawan' => $idKaryawan
        ]);

        sendJson(true, 'Data pajak keluaran berhasil disimpan.', [
            'id_pajak_keluaran' => $newId,
            'tahun' => $tahun,
            'bulan' => $bulanPad,
            'ppn_keluaran' => $ppnKeluaran
        ], 201);
    } else {
        $err = $stmt->error;
        $stmt->close();
        sendJson(false, 'Gagal menyimpan data pajak keluaran: ' . $err, null, 500);
    }
}

// =========================================================================
// 3. PUT: Edit / Update Data Pajak Keluaran
// =========================================================================
if ($method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        sendJson(false, 'Payload data tidak valid.', null, 400);
    }

    $id = intval($input['id_pajak_keluaran'] ?? $input['id'] ?? 0);
    if ($id <= 0) {
        sendJson(false, 'ID Pajak Keluaran tidak valid.', null, 422);
    }

    // Cek keberadaan data
    $stmtCek = $conn->prepare("SELECT * FROM pajak_keluaran WHERE id_pajak_keluaran = ? LIMIT 1");
    $stmtCek->bind_param("i", $id);
    $stmtCek->execute();
    $current = $stmtCek->get_result()->fetch_assoc();
    $stmtCek->close();

    if (!$current) {
        sendJson(false, 'Data pajak keluaran tidak ditemukan.', null, 404);
    }

    $tahun = trim($input['tahun'] ?? $current['tahun']);
    $bulan = trim($input['bulan'] ?? $current['bulan']);
    $ppnKeluaran = isset($input['ppn_keluaran']) ? floatval($input['ppn_keluaran']) : floatval($current['ppn_keluaran']);
    $keterangan = isset($input['keterangan']) ? trim($input['keterangan']) : $current['keterangan'];

    if (empty($tahun) || strlen($tahun) !== 4 || !is_numeric($tahun)) {
        sendJson(false, 'Format tahun tidak valid (harus 4 digit angka, contoh: 2026).', null, 422);
    }

    $bulanInt = intval($bulan);
    if ($bulanInt < 1 || $bulanInt > 12) {
        sendJson(false, 'Bulan tidak valid (harus 1 sampai 12).', null, 422);
    }
    $bulanPad = str_pad((string)$bulanInt, 2, '0', STR_PAD_LEFT);

    if ($ppnKeluaran < 0) {
        sendJson(false, 'Nilai PPN Keluaran tidak boleh bernilai negatif.', null, 422);
    }

    // Cek duplikasi periode dengan ID lain
    $cekDup = $conn->prepare("SELECT id_pajak_keluaran FROM pajak_keluaran WHERE tahun = ? AND bulan = ? AND id_pajak_keluaran != ? LIMIT 1");
    $cekDup->bind_param("ssi", $tahun, $bulanPad, $id);
    $cekDup->execute();
    $dup = $cekDup->get_result()->fetch_assoc();
    $cekDup->close();

    if ($dup) {
        $namaBulan = $namaBulanIndo[$bulanPad] ?? $bulanPad;
        sendJson(false, "Data pajak keluaran untuk periode {$namaBulan} {$tahun} sudah ada di entri lain.", null, 409);
    }

    $stmt = $conn->prepare("UPDATE pajak_keluaran 
                           SET tahun = ?, bulan = ?, ppn_keluaran = ?, keterangan = ?, updated_at = NOW() 
                           WHERE id_pajak_keluaran = ?");
    $stmt->bind_param("ssdsi", $tahun, $bulanPad, $ppnKeluaran, $keterangan, $id);

    if ($stmt->execute()) {
        $stmt->close();

        // Log Aktivitas
        logActivity($conn, [
            'modul' => 'PAJAK_KELUARAN',
            'aksi' => 'UPDATE',
            'id_referensi' => $id,
            'nomor_referensi' => "{$bulanPad}/{$tahun}",
            'deskripsi' => "Memperbarui data pajak keluaran ID #{$id} (Periode {$bulanPad}/{$tahun} senilai Rp " . number_format($ppnKeluaran, 0, ',', '.') . ")",
            'id_karyawan' => $user['id_karyawan'] ?? null
        ]);

        sendJson(true, 'Data pajak keluaran berhasil diperbarui.', [
            'id_pajak_keluaran' => $id,
            'tahun' => $tahun,
            'bulan' => $bulanPad,
            'ppn_keluaran' => $ppnKeluaran
        ]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        sendJson(false, 'Gagal memperbarui data pajak keluaran: ' . $err, null, 500);
    }
}

// =========================================================================
// 4. DELETE: Hapus Data Pajak Keluaran
// =========================================================================
if ($method === 'DELETE' || ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!is_array($input)) $input = $_POST;

    $id = intval($input['id_pajak_keluaran'] ?? $input['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        sendJson(false, 'ID Pajak Keluaran tidak valid.', null, 422);
    }

    $stmtCek = $conn->prepare("SELECT * FROM pajak_keluaran WHERE id_pajak_keluaran = ? LIMIT 1");
    $stmtCek->bind_param("i", $id);
    $stmtCek->execute();
    $current = $stmtCek->get_result()->fetch_assoc();
    $stmtCek->close();

    if (!$current) {
        sendJson(false, 'Data pajak keluaran tidak ditemukan.', null, 404);
    }

    $stmtDel = $conn->prepare("DELETE FROM pajak_keluaran WHERE id_pajak_keluaran = ?");
    $stmtDel->bind_param("i", $id);

    if ($stmtDel->execute()) {
        $stmtDel->close();

        // Log Aktivitas
        logActivity($conn, [
            'modul' => 'PAJAK_KELUARAN',
            'aksi' => 'DELETE',
            'id_referensi' => $id,
            'nomor_referensi' => "{$current['bulan']}/{$current['tahun']}",
            'deskripsi' => "Menghapus data pajak keluaran ID #{$id} (Periode {$current['bulan']}/{$current['tahun']})",
            'id_karyawan' => $user['id_karyawan'] ?? null
        ]);

        sendJson(true, 'Data pajak keluaran berhasil dihapus.');
    } else {
        $err = $stmtDel->error;
        $stmtDel->close();
        sendJson(false, 'Gagal menghapus data: ' . $err, null, 500);
    }
}

sendJson(false, 'Metode HTTP tidak didukung.', null, 405);
