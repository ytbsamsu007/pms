<?php
/**
 * REST API: Laporan Pembatalan Transaksi (Membaca langsung dari PO & Faktur yang berstatus BATAL)
 * Path: api/laporan/pembatalan.php
 * Khusus Role: ADMIN, FINANCE, MANAGER, PURCHASING, LOGISTIK
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak didukung. Gunakan GET.']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

$type = strtoupper(trim($_GET['type'] ?? ''));
$idRef = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

// JIKA MENGAMBIL SINGLE DETAIL DOKUMEN BATAL (untuk cetak BAP)
if (!empty($type) && $idRef > 0) {
    if ($type === 'PO') {
        $stmt = $conn->prepare("
            SELECT po.id_po as id_referensi, 'PO' as jenis_dokumen, po.nomor_po as nomor_referensi,
                   po.status, po.keterangan as alasan_detail, po.tanggal_status as tanggal_batal,
                   v.id_vendor, v.nama_perusahaan as nama_vendor, v.kode_vendor, v.alamat as alamat_vendor, v.no_telepon as telp_vendor,
                   k.nama_karyawan as nama_karyawan_batal,
                   (SELECT COALESCE(SUM(subtotal), 0) FROM purchase_order_detail WHERE id_po = po.id_po) as nilai_transaksi,
                   p.nama as nama_profil, p.alamat as alamat_profil, p.kota as kota_profil, p.provinsi as prov_profil, 
                   p.telepon1 as telp_profil, p.email as email_profil, p.picture as logo_profil
            FROM purchase_order po
            LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
            LEFT JOIN karyawan k ON po.id_karyawan = k.id_karyawan
            LEFT JOIN profile p ON 1=1
            WHERE po.id_po = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $idRef);
        $stmt->execute();
        $res = $stmt->get_result();
        $doc = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($doc) {
            $stmtItm = $conn->prepare("
                SELECT pod.*, b.kode_barang, b.nama_barang, b.satuan 
                FROM purchase_order_detail pod 
                LEFT JOIN barang b ON pod.id_barang = b.id_barang 
                WHERE pod.id_po = ?
            ");
            $stmtItm->bind_param("i", $idRef);
            $stmtItm->execute();
            $resItm = $stmtItm->get_result();
            $items = [];
            while ($r = $resItm->fetch_assoc()) $items[] = $r;
            $stmtItm->close();
            $doc['items'] = $items;
        }
    } else {
        $stmt = $conn->prepare("
            SELECT fp.id_faktur as id_referensi, 'FAKTUR' as jenis_dokumen, fp.nomor_faktur as nomor_referensi,
                   fp.status, fp.keterangan as alasan_detail, COALESCE(fp.updated_at, fp.created_at) as tanggal_batal,
                   v.id_vendor, v.nama_perusahaan as nama_vendor, v.kode_vendor, v.alamat as alamat_vendor, v.no_telepon as telp_vendor,
                   k.nama_karyawan as nama_karyawan_batal,
                   fp.total_tagihan as nilai_transaksi,
                   p.nama as nama_profil, p.alamat as alamat_profil, p.kota as kota_profil, p.provinsi as prov_profil, 
                   p.telepon1 as telp_profil, p.email as email_profil, p.picture as logo_profil
            FROM faktur_po fp
            LEFT JOIN vendor v ON fp.id_vendor = v.id_vendor
            LEFT JOIN karyawan k ON fp.id_karyawan = k.id_karyawan
            LEFT JOIN profile p ON 1=1
            WHERE fp.id_faktur = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $idRef);
        $stmt->execute();
        $res = $stmt->get_result();
        $doc = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($doc) {
            $stmtItm = $conn->prepare("
                SELECT fpd.*, b.kode_barang, b.nama_barang, b.satuan 
                FROM faktur_po_detail fpd 
                LEFT JOIN barang b ON fpd.id_barang = b.id_barang 
                WHERE fpd.id_faktur = ?
            ");
            $stmtItm->bind_param("i", $idRef);
            $stmtItm->execute();
            $resItm = $stmtItm->get_result();
            $items = [];
            while ($r = $resItm->fetch_assoc()) $items[] = $r;
            $stmtItm->close();
            $doc['items'] = $items;
        }
    }

    if (!$doc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
        exit;
    }

    // Parse kategori alasan dari keterangan jika ada format [BATAL: ...]
    $kategoriAlasan = 'Pembatalan Transaksi';
    $rawKet = $doc['alasan_detail'] ?? '';
    if (preg_match('/\[BATAL:\s*([^\]]+)\]/', $rawKet, $m)) {
        $kategoriAlasan = trim($m[1]);
    }
    $doc['kategori_alasan'] = $kategoriAlasan;
    $refNum = $doc['nomor_referensi'] ?? '';
    if (strpos($refNum, $doc['jenis_dokumen'] . '-') === 0 || strpos($refNum, 'PO-') === 0 || strpos($refNum, 'FAK-') === 0 || strpos($refNum, 'INV-') === 0) {
        $doc['nomor_bap'] = 'BAP-' . $refNum;
    } else {
        $doc['nomor_bap'] = 'BAP-' . $doc['jenis_dokumen'] . '-' . $refNum;
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $doc]);
    exit;
}

// =========================================================================
// QUERY LIST LAPORAN PEMBATALAN (UNION PO & FAKTUR)
// =========================================================================
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$jenisDokumen = strtoupper(trim($_GET['jenis_dokumen'] ?? ''));
$idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
$search = trim($_GET['search'] ?? '');

$unionSql = "
    SELECT 
        'PO' as jenis_dokumen,
        po.id_po as id_referensi,
        po.nomor_po as nomor_referensi,
        'PO - BATAL' as state_tahapan,
        po.id_vendor,
        COALESCE(v.nama_perusahaan, 'Vendor Umum') as nama_vendor,
        v.kode_vendor,
        (SELECT COALESCE(SUM(subtotal), 0) FROM purchase_order_detail pod WHERE pod.id_po = po.id_po) as nilai_transaksi,
        po.keterangan as alasan_detail,
        COALESCE(po.tanggal_status, po.tanggal_po) as tanggal_batal,
        COALESCE(k.nama_karyawan, 'Petugas') as nama_karyawan_batal
    FROM purchase_order po
    LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
    LEFT JOIN karyawan k ON po.id_karyawan = k.id_karyawan
    WHERE po.status = 'BATAL'

    UNION ALL

    SELECT 
        'FAKTUR' as jenis_dokumen,
        fp.id_faktur as id_referensi,
        fp.nomor_faktur as nomor_referensi,
        'FAKTUR - BATAL' as state_tahapan,
        fp.id_vendor,
        COALESCE(v.nama_perusahaan, 'Vendor Umum') as nama_vendor,
        v.kode_vendor,
        fp.total_tagihan as nilai_transaksi,
        fp.keterangan as alasan_detail,
        COALESCE(fp.updated_at, fp.created_at) as tanggal_batal,
        COALESCE(k.nama_karyawan, 'Petugas') as nama_karyawan_batal
    FROM faktur_po fp
    LEFT JOIN vendor v ON fp.id_vendor = v.id_vendor
    LEFT JOIN karyawan k ON fp.id_karyawan = k.id_karyawan
    WHERE fp.status = 'BATAL'
";

$outerWhere = ["1=1"];
if (!empty($jenisDokumen) && in_array($jenisDokumen, ['PO', 'FAKTUR'])) {
    $outerWhere[] = "t.jenis_dokumen = '" . $conn->real_escape_string($jenisDokumen) . "'";
}
if ($idVendor > 0) {
    $outerWhere[] = "t.id_vendor = " . (int)$idVendor;
}
if (!empty($startDate)) {
    $outerWhere[] = "DATE(t.tanggal_batal) >= '" . $conn->real_escape_string($startDate) . "'";
}
if (!empty($endDate)) {
    $outerWhere[] = "DATE(t.tanggal_batal) <= '" . $conn->real_escape_string($endDate) . "'";
}
if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $outerWhere[] = "(t.nomor_referensi LIKE '%$s%' OR t.nama_vendor LIKE '%$s%' OR t.alasan_detail LIKE '%$s%' OR t.nama_karyawan_batal LIKE '%$s%')";
}

$whereStr = implode(" AND ", $outerWhere);

try {
    $finalSql = "SELECT t.* FROM ($unionSql) t WHERE $whereStr ORDER BY t.tanggal_batal DESC";
    $res = $conn->query($finalSql);
    $items = [];
    $totalNilai = 0;
    $poCount = 0;
    $fakturCount = 0;

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            // Format BAP Number on the fly
            $refNum = $row['nomor_referensi'] ?? '';
            if (strpos($refNum, $row['jenis_dokumen'] . '-') === 0 || strpos($refNum, 'PO-') === 0 || strpos($refNum, 'FAK-') === 0 || strpos($refNum, 'INV-') === 0) {
                $row['nomor_bap'] = 'BAP-' . $refNum;
            } else {
                $row['nomor_bap'] = 'BAP-' . $row['jenis_dokumen'] . '-' . $refNum;
            }
            
            // Extract kategori alasan
            $kat = 'Pembatalan';
            if (preg_match('/\[BATAL:\s*([^\]]+)\]/', $row['alasan_detail'] ?? '', $m)) {
                $kat = trim($m[1]);
            }
            $row['kategori_alasan'] = $kat;
            $row['email_notif_sent'] = 1; // default sent via SMTP

            $totalNilai += (float)($row['nilai_transaksi'] ?? 0);
            if ($row['jenis_dokumen'] === 'PO') $poCount++;
            else $fakturCount++;

            $items[] = $row;
        }
    }

    $metrics = [
        'total_batal' => count($items),
        'total_po_batal' => $poCount,
        'total_faktur_batal' => $fakturCount,
        'total_nilai_batal' => $totalNilai
    ];

    // Vendors list for dropdown
    $vendorsList = [];
    $resV = $conn->query("SELECT id_vendor, kode_vendor, nama_perusahaan FROM vendor ORDER BY nama_perusahaan ASC");
    if ($resV) {
        while ($v = $resV->fetch_assoc()) {
            $vendorsList[] = $v;
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Data Laporan Pembatalan Transaksi berhasil dimuat.',
        'data' => [
            'metrics' => $metrics,
            'items' => $items,
            'vendors_list' => $vendorsList
        ]
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
