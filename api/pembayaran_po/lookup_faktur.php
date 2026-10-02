<?php
/**
 * REST API: Lookup Faktur PO Belum Lunas & Data Approver untuk Pembayaran
 * Endpoint: /api/pembayaran_po/lookup_faktur.php
 * Path: api/pembayaran_po/lookup_faktur.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$idFaktur = isset($_GET['id_faktur']) ? intval($_GET['id_faktur']) : 0;

// MODE 1: Ambil detail lengkap 1 faktur untuk form pembayaran
if ($idFaktur > 0) {
    $sqlF = "SELECT fp.*,
                    po.nomor_po, po.tanggal_po,
                    rcv.nomor_rcv, rcv.nomor_sj AS nomor_sj_rcv,
                    v.id_vendor, v.kode_vendor, v.nama_perusahaan AS nama_vendor,
                    v.nama_bank AS bank_vendor_master, v.nomor_rekening AS norek_vendor_master,
                    s.nama_site,
                    DATEDIFF(fp.tanggal_jatuh_tempo, CURRENT_DATE) AS sisa_hari_tempo
             FROM faktur_po fp
             JOIN purchase_order po ON fp.id_po = po.id_po
             JOIN receiving_order rcv ON fp.id_rcv = rcv.id_rcv
             JOIN vendor v ON fp.id_vendor = v.id_vendor
             JOIN site s ON fp.id_site = s.id_site
             WHERE fp.id_faktur = ? AND fp.status != 'BATAL'
             LIMIT 1";

    $stmtF = $conn->prepare($sqlF);
    $stmtF->bind_param("i", $idFaktur);
    $stmtF->execute();
    $faktur = $stmtF->get_result()->fetch_assoc();
    $stmtF->close();

    if (!$faktur) {
        sendJson(false, 'Dokumen Faktur PO tidak ditemukan atau telah dibatalkan.', null, 404);
    }

    // Ambil riwayat pembayaran sebelumnya (jika ada)
    $sqlHist = "SELECT ppd.*,
                       k.nama_karyawan AS nama_pembuat,
                       ka.nama_karyawan AS nama_approver
                FROM payment_purchase_detail ppd
                JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
                LEFT JOIN karyawan k ON ppd.id_karyawan = k.id_karyawan
                LEFT JOIN karyawan ka ON ppd.id_karyawan_approved = ka.id_karyawan
                WHERE pp.id_faktur = ?
                ORDER BY ppd.id_pembayaran_detail ASC";
    $stmtH = $conn->prepare($sqlHist);
    $stmtH->bind_param("i", $idFaktur);
    $stmtH->execute();
    $history = $stmtH->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtH->close();

    $faktur['history_pembayaran'] = $history;

    // Ambil daftar karyawan approver (Hanya Divisi Finance/Manajemen DAN Jabatan Level 1)
    $sqlAppr = "SELECT k.id_karyawan, k.nama_karyawan, j.nama_jabatan, d.nama_divisi
                FROM karyawan k
                JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                JOIN divisi d ON k.id_divisi = d.id_divisi
                WHERE k.aktif = 1
                  AND (k.id_divisi IN (1, 6) OR d.level IN (1, 6) OR d.nama_divisi LIKE '%Finance%' OR d.nama_divisi LIKE '%Manajemen%')
                  AND (j.level = 1 OR k.id_jabatan = 1)
                ORDER BY k.nama_karyawan ASC";
    $resAppr = $conn->query($sqlAppr);
    $approvers = $resAppr ? $resAppr->fetch_all(MYSQLI_ASSOC) : [];

    sendJson(true, 'Data faktur berhasil dimuat.', [
        'faktur' => $faktur,
        'approvers' => $approvers
    ]);
}

// MODE 2: Ambil daftar faktur yang belum lunas (sisa_tagihan > 0)
$search = trim($_GET['q'] ?? '');

$sqlList = "SELECT fp.id_faktur, fp.nomor_faktur, fp.nomor_faktur_vendor,
                   fp.tanggal_faktur_vendor, fp.tanggal_jatuh_tempo, fp.term_of_payment,
                   fp.total_tagihan, fp.terbayar, fp.sisa_tagihan, fp.status,
                   fp.nama_bank, fp.nomor_rekening, fp.atas_nama_rekening,
                   v.id_vendor, v.nama_perusahaan AS nama_vendor,
                   po.nomor_po, s.nama_site,
                   DATEDIFF(fp.tanggal_jatuh_tempo, CURRENT_DATE) AS sisa_hari_tempo
            FROM faktur_po fp
            JOIN purchase_order po ON fp.id_po = po.id_po
            JOIN vendor v ON fp.id_vendor = v.id_vendor
            JOIN site s ON fp.id_site = s.id_site
            WHERE fp.sisa_tagihan > 0 AND fp.status NOT IN ('LUNAS', 'BATAL', 'DRAFT') ";

$params = [];
$types = "";

if ($search !== '') {
    $sqlList .= " AND (fp.nomor_faktur LIKE ? OR fp.nomor_faktur_vendor LIKE ? OR v.nama_perusahaan LIKE ? OR po.nomor_po LIKE ?) ";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
    $types = "ssss";
}

$sqlList .= " ORDER BY fp.tanggal_jatuh_tempo ASC, fp.id_faktur ASC LIMIT 50";

$stmtList = $conn->prepare($sqlList);
if (!empty($params)) {
    $stmtList->bind_param($types, ...$params);
}
$stmtList->execute();
$listFaktur = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtList->close();

// Daftar approver untuk inisial dropdown (Hanya Divisi Finance/Manajemen DAN Jabatan Level 1)
$sqlAppr = "SELECT k.id_karyawan, k.nama_karyawan, j.nama_jabatan, d.nama_divisi
            FROM karyawan k
            JOIN jabatan j ON k.id_jabatan = j.id_jabatan
            JOIN divisi d ON k.id_divisi = d.id_divisi
            WHERE k.aktif = 1
              AND (k.id_divisi IN (1, 6) OR d.level IN (1, 6) OR d.nama_divisi LIKE '%Finance%' OR d.nama_divisi LIKE '%Manajemen%')
              AND (j.level = 1 OR k.id_jabatan = 1)
            ORDER BY k.nama_karyawan ASC";
$resAppr = $conn->query($sqlAppr);
$approvers = $resAppr ? $resAppr->fetch_all(MYSQLI_ASSOC) : [];

sendJson(true, 'Daftar faktur belum lunas berhasil dimuat.', [
    'faktur_list' => $listFaktur,
    'approvers' => $approvers
]);
