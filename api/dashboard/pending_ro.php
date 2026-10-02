<?php
/**
 * API Dashboard: Request Order yang Belum Diterima (Belum Diterima Full / Belum Diterima Sebagian)
 * Path: api/dashboard/pending_ro.php
 * Mengambil RO dengan status aktif yang belum selesai diterima (DRAFT, TERKIRIM, DISETUJUI LOGISTIK, DISETUJUI PURCHASING)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Metode HTTP tidak diizinkan. Gunakan GET.', null, 405);
}

// Filter RO yang BELUM DITERIMA (bukan DITERIMA FULL, bukan DITERIMA SEBAGIAN, bukan BATAL, bukan DITOLAK)
$where = ["ro.status IN ('DRAFT', 'TERKIRIM', 'DISETUJUI LOGISTIK', 'DISETUJUI PURCHASING')"];
$params = [];
$types = "";

if ($currentUser['role'] === ROLE_MEKANIK && !empty($currentUser['id_karyawan'])) {
    $where[] = "ro.id_karyawan = ?";
    $params[] = (int)$currentUser['id_karyawan'];
    $types .= "i";
}

$whereClause = implode(" AND ", $where);

$sql = "SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.status, ro.prioritas, ro.keterangan,
               ro.id_po, po.nomor_po,
               k.nama_karyawan, j.nama_jabatan, d.nama_divisi,
               s.id_site, s.nama_site, s.kode_site,
               v.id_vendor, v.nama_perusahaan as nama_vendor,
               (SELECT COUNT(*) FROM request_order_detail rod WHERE rod.id_request = ro.id_request) as total_item,
               (SELECT COALESCE(SUM(rod.qty), 0) FROM request_order_detail rod WHERE rod.id_request = ro.id_request) as total_qty
        FROM request_order ro
        LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
        LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
        LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
        LEFT JOIN site s ON ro.id_site = s.id_site
        LEFT JOIN vendor v ON ro.id_vendor = v.id_vendor
        LEFT JOIN purchase_order po ON ro.id_po = po.id_po
        WHERE {$whereClause}
        ORDER BY ro.id_request DESC
        LIMIT 12";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

jsonResponse(true, 'Daftar Request Order belum diterima berhasil dimuat.', $list);
