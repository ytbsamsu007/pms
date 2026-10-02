<?php
/**
 * API Purchase Order: Proses Verifikasi Request Order (RO) ke Purchase Order (PO)
 * Path: api/purchase_order/process_ro.php
 * Akses: PURCHASING, MANAGER, ADMIN
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

// Auth Protection: Purchasing, Manager, Admin
$currentUser = apiAuth();
$userRole = strtoupper($currentUser['nama_jabatan'] ?? $currentUser['level'] ?? '');
$allowedRoles = ['ADMIN', 'ADMINISTRATOR', 'PURCHASING', 'STAFF PURCHASING', 'MANAGER', 'MANAGER CABANG'];
if (!in_array($userRole, $allowedRoles)) {
    jsonResponse(false, 'Akses ditolak. Fitur ini hanya dapat diakses oleh tim Purchasing atau Manajemen.', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan POST.', null, 405);
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;

$action = trim($input['action'] ?? '');
$idRequest = isset($input['id_request']) && is_numeric($input['id_request']) ? (int)$input['id_request'] : null;

if (!$idRequest) {
    jsonResponse(false, 'ID Request Order wajib disertakan.', null, 422);
}

// 1. Ambil & Kunci Data Request Order
$stmtRo = $conn->prepare("SELECT r.*, s.nama_site, s.alamat as alamat_site, k.nama_karyawan 
                         FROM request_order r
                         LEFT JOIN site s ON r.id_site = s.id_site
                         LEFT JOIN karyawan k ON r.id_karyawan = k.id_karyawan
                         WHERE r.id_request = ? LIMIT 1");
$stmtRo->bind_param("i", $idRequest);
$stmtRo->execute();
$ro = $stmtRo->get_result()->fetch_assoc();
$stmtRo->close();

if (!$ro) {
    jsonResponse(false, 'Dokumen Request Order tidak ditemukan.', null, 404);
}

// Cek apakah RO sudah pernah diproses ke PO
if (!empty($ro['id_po']) && (int)$ro['id_po'] > 0) {
    jsonResponse(false, "Request Order ini sudah pernah diproses ke PO (ID PO: {$ro['id_po']}).", null, 400);
}

// =========================================================================
// AKSI 1: TOLAK / TIDAK DISETUJUI
// =========================================================================
if ($action === 'reject') {
    $alasan = trim($input['alasan'] ?? $input['keterangan'] ?? '');
    if (empty($alasan)) {
        jsonResponse(false, 'Alasan penolakan Request Order wajib diisi.', null, 422);
    }

    $ketBaru = !empty($ro['keterangan']) ? ($ro['keterangan'] . " | Catatan Purchasing (Ditolak): " . $alasan) : ("Catatan Purchasing (Ditolak): " . $alasan);

    $up = $conn->prepare("UPDATE request_order SET status = 'TIDAK DISETUJUI PURCHASING', keterangan = ?, tanggal_status = NOW() WHERE id_request = ?");
    $up->bind_param("si", $ketBaru, $idRequest);
    
    if ($up->execute()) {
        $up->close();

        // Kirim Notifikasi Email ke Pemohon RO
        $emailSent = false;
        try {
            require_once __DIR__ . '/../../config/mailer.php';
            if (function_exists('sendNotificationEvent')) {
                $approverName = $currentUser['nama_karyawan'] ?? ($currentUser['nama_users'] ?? ($currentUser['username'] ?? 'Staff Purchasing'));
                $mailRes = sendNotificationEvent($conn, 'ro_status_update', [
                    'id_request' => $idRequest,
                    'status' => 'TIDAK DISETUJUI PURCHASING',
                    'actor_name' => $approverName,
                    'keterangan' => $alasan
                ]);
                $emailSent = !empty($mailRes['success']);
            }
        } catch (Throwable $t) {
            error_log("Gagal mengirim notifikasi tolak Purchasing RO {$ro['nomor']}: " . $t->getMessage());
        }

        jsonResponse(true, "Request Order {$ro['nomor']} telah ditolak (Tidak Disetujui Purchasing).", [
            'id_request' => $idRequest,
            'status' => 'TIDAK DISETUJUI PURCHASING',
            'email_sent' => $emailSent
        ]);
    } else {
        $err = $up->error;
        $up->close();
        jsonResponse(false, 'Gagal memperbarui status: ' . $err, null, 500);
    }
}

// =========================================================================
// AKSI 2: BATAL
// =========================================================================
if ($action === 'cancel') {
    $alasan = trim($input['alasan'] ?? $input['keterangan'] ?? '');
    if (empty($alasan)) {
        jsonResponse(false, 'Alasan pembatalan Request Order wajib diisi.', null, 422);
    }

    $ketBaru = !empty($ro['keterangan']) ? ($ro['keterangan'] . " | Catatan Purchasing (Batal): " . $alasan) : ("Catatan Purchasing (Batal): " . $alasan);

    $up = $conn->prepare("UPDATE request_order SET status = 'BATAL', keterangan = ?, tanggal_status = NOW() WHERE id_request = ?");
    $up->bind_param("si", $ketBaru, $idRequest);
    
    if ($up->execute()) {
        $up->close();
        jsonResponse(true, "Request Order {$ro['nomor']} berhasil dibatalkan.", [
            'id_request' => $idRequest,
            'status' => 'BATAL'
        ]);
    } else {
        $err = $up->error;
        $up->close();
        jsonResponse(false, 'Gagal membatalkan Request Order: ' . $err, null, 500);
    }
}

// =========================================================================
// AKSI 3: DISETUJUI & PROSES KE PURCHASE ORDER (PO) ATAU SIMPAN SEBAGAI DRAFT
// =========================================================================
if ($action === 'approve' || $action === 'draft') {
    $isDraft = ($action === 'draft');

    // Validasi Password Konfirmasi Khusus Penerbitan PO Resmi (Approve)
    if (!$isDraft) {
        $confirmPassword = trim($input['confirm_password'] ?? $input['password'] ?? '');
        if (empty($confirmPassword)) {
            jsonResponse(false, 'Password konfirmasi wajib diisi untuk otorisasi penerbitan Purchase Order.', null, 422);
        }

        $storedPassword = '';
        if (!empty($currentUser['id_karyawan'])) {
            $stmtPw = $conn->prepare("SELECT password FROM karyawan WHERE id_karyawan = ? LIMIT 1");
            $stmtPw->bind_param("i", $currentUser['id_karyawan']);
            $stmtPw->execute();
            $resPw = $stmtPw->get_result()->fetch_assoc();
            $storedPassword = $resPw['password'] ?? '';
            $stmtPw->close();
        } elseif (!empty($currentUser['id_users']) || !empty($currentUser['user_id'])) {
            $uId = (int)($currentUser['id_users'] ?? $currentUser['user_id']);
            $stmtPw = $conn->prepare("SELECT password FROM users WHERE id_users = ? LIMIT 1");
            $stmtPw->bind_param("i", $uId);
            $stmtPw->execute();
            $resPw = $stmtPw->get_result()->fetch_assoc();
            $storedPassword = $resPw['password'] ?? '';
            $stmtPw->close();
        }

        $isPwValid = false;
        if (!empty($storedPassword)) {
            if (password_verify($confirmPassword, $storedPassword) || $confirmPassword === $storedPassword) {
                $isPwValid = true;
            }
        }

        if (!$isPwValid) {
            jsonResponse(false, 'Password konfirmasi salah. Otorisasi penerbitan Purchase Order ditolak.', null, 400);
        }
    }

    $idVendor = isset($input['id_vendor']) && is_numeric($input['id_vendor']) ? (int)$input['id_vendor'] : null;
    if (!$idVendor) {
        jsonResponse(false, 'Vendor belum ditentukan pada Request Order.', null, 422);
    }

    // Tanggal PO
    $tanggalPo = trim($input['tanggal_po'] ?? date('Y-m-d'));
    if (!strtotime($tanggalPo)) {
        $tanggalPo = date('Y-m-d');
    }

    $idKaryawan = $currentUser['id_karyawan'] ?? $ro['id_karyawan'];
    $idSite = (int)$ro['id_site'];
    $prioritas = in_array(strtoupper($input['prioritas'] ?? $ro['prioritas']), ['NORMAL', 'URGENT']) ? strtoupper($input['prioritas'] ?? $ro['prioritas']) : 'NORMAL';
    $termOfPayment = isset($input['term_of_payment']) ? (int)$input['term_of_payment'] : 0;
    
    $pengiriman = in_array($input['pengiriman'] ?? '', ['Vendor', 'Expedisi', 'Internal']) ? $input['pengiriman'] : 'Vendor';
    $tanggalPengiriman = !empty($input['tanggal_pengiriman']) ? date('Y-m-d H:i:s', strtotime($input['tanggal_pengiriman'])) : null;
    $alamatPengiriman = trim($input['alamat'] ?? $ro['alamat_site'] ?? '');
    
    $pajak = isset($input['pajak']) ? (int)$input['pajak'] : 0; // PPN %
    $totalTermasukPajak = isset($input['total_termasuk_pajak']) ? (int)$input['total_termasuk_pajak'] : 0;
    $diskonGlobal = isset($input['diskon']) ? (float)$input['diskon'] : 0.0;
    $keteranganPo = trim($input['keterangan'] ?? '');

    // Ambil Data Barang yang diajukan dari RO Detail (Pencegahan manipulasi / penghapusan item oleh Purchasing)
    $stmtItems = $conn->prepare("SELECT rd.*, b.nama_barang as nama_master, b.PPnBM, b.rate_PPnBM 
                                 FROM request_order_detail rd
                                 LEFT JOIN barang b ON rd.id_barang = b.id_barang
                                 WHERE rd.id_request = ?");
    $stmtItems->bind_param("i", $idRequest);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();
    
    $roItems = [];
    while ($item = $resItems->fetch_assoc()) {
        $roItems[] = $item;
    }
    $stmtItems->close();

    if (empty($roItems)) {
        jsonResponse(false, 'Request Order ini tidak memiliki rincian barang untuk diproses.', null, 422);
    }

    $pajakPpnbm = isset($input['pajak_PPnBM']) ? (int)$input['pajak_PPnBM'] : 0;
    $totalTermasukPpnbm = isset($input['total_termasuk_PPnBM']) ? (int)$input['total_termasuk_PPnBM'] : 0;

    // Input data harga & diskon per item yang dikirim dari form UI
    $itemsInputMap = [];
    if (isset($input['items']) && is_array($input['items'])) {
        foreach ($input['items'] as $it) {
            $bId = (int)($it['id_barang'] ?? 0);
            if ($bId > 0) {
                $itemsInputMap[$bId] = $it;
            }
        }
    }

    // MULAI TRANSAKSI DATABASE
    $conn->begin_transaction();

    try {
        // Generate Nomor PO Resmi Terkunci (Atomik untuk Multi-User)
        require_once __DIR__ . '/../../config/penomoran_helper.php';
        $gen = generateNomorTransaksiLocked($conn, 'PURCHASE', $tanggalPo);
        if ($gen['success']) {
            $nomorPo = $isDraft ? ('DRF/' . $gen['nomor']) : $gen['nomor'];
        } else {
            $nomorPo = ($isDraft ? 'DRF/' : '') . 'PO-' . date('ym', strtotime($tanggalPo)) . '-0001';
        }

        // 1. Insert ke purchase_order
        $statusPo = $isDraft ? 'DRAFT' : 'DISETUJUI INTERNAL';
        $stmtPo = $conn->prepare("INSERT INTO purchase_order 
            (nomor_po, tanggal_po, id_karyawan, id_site, id_vendor, status, prioritas, tanggal_status, keterangan, alamat, pengiriman, tanggal_pengiriman, term_of_payment, id_karyawan_approved, pajak, total_termasuk_pajak, diskon, pajak_PPnBM, total_termasuk_PPnBM) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $approvedBy = $isDraft ? null : $idKaryawan;
        $stmtPo->bind_param(
            "ssiiissssssiiiidii",
            $nomorPo,
            $tanggalPo,
            $idKaryawan,
            $idSite,
            $idVendor,
            $statusPo,
            $prioritas,
            $keteranganPo,
            $alamatPengiriman,
            $pengiriman,
            $tanggalPengiriman,
            $termOfPayment,
            $approvedBy,
            $pajak,
            $totalTermasukPajak,
            $diskonGlobal,
            $pajakPpnbm,
            $totalTermasukPpnbm
        );

        if (!$stmtPo->execute()) {
            throw new Exception("Gagal menyimpan header Purchase Order: " . $stmtPo->error);
        }

        $newIdPo = $stmtPo->insert_id;
        $stmtPo->close();

        // 2. Insert ke purchase_order_detail (Semua barang asli dari RO dipindahkan)
        $stmtDetail = $conn->prepare("INSERT INTO purchase_order_detail 
            (id_po, id_barang, qty, harga, diskon, subtotal, kena_pajak, keterangan, kena_pajak_ppnbm) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($roItems as $roItem) {
            $idBarang = (int)$roItem['id_barang'];
            $qty = (float)$roItem['qty'];
            
            // Ambil penyesuaian harga dari form purchasing (jika ada), atau gunakan default
            $overrideItem = $itemsInputMap[$idBarang] ?? null;
            $harga = $overrideItem && isset($overrideItem['harga']) ? (float)$overrideItem['harga'] : ((float)$roItem['harga'] > 0 ? (float)$roItem['harga'] : 0.0);
            $diskonItem = $overrideItem && isset($overrideItem['diskon']) ? (float)$overrideItem['diskon'] : 0.0;
            $kenaPajak = $overrideItem && isset($overrideItem['kena_pajak']) ? (float)$overrideItem['kena_pajak'] : 1.0;
            $kenaPajakPpnbm = (int)($roItem['PPnBM'] ?? 0) === 1 ? 1.0 : 0.0;
            $keteranganItem = $overrideItem && isset($overrideItem['keterangan']) ? trim($overrideItem['keterangan']) : '';

            $subtotal = ($qty * $harga) - $diskonItem;
            if ($subtotal < 0) $subtotal = 0;

            $stmtDetail->bind_param("iidddddsd", $newIdPo, $idBarang, $qty, $harga, $diskonItem, $subtotal, $kenaPajak, $keteranganItem, $kenaPajakPpnbm);
            
            if (!$stmtDetail->execute()) {
                throw new Exception("Gagal menyimpan item barang PO: " . $stmtDetail->error);
            }
        }
        $stmtDetail->close();

        // 3. Update status Request Order:
        // Jika Approve: DISETUJUI PURCHASING, id_po, id_vendor
        // Jika Draft: catat id_po dan id_vendor, status RO tetap status sebelumnya
        $newRoStatus = $isDraft ? $ro['status'] : 'DISETUJUI PURCHASING';
        $stmtUpRo = $conn->prepare("UPDATE request_order 
            SET status = ?, id_po = ?, id_vendor = ?, tanggal_status = NOW() 
            WHERE id_request = ?");
        $stmtUpRo->bind_param("siii", $newRoStatus, $newIdPo, $idVendor, $idRequest);

        if (!$stmtUpRo->execute()) {
            throw new Exception("Gagal memperbarui status Request Order: " . $stmtUpRo->error);
        }
        $stmtUpRo->close();

        // Commit transaksi
        $conn->commit();

        // Kirim Notifikasi Email ke Pemohon RO saat PO Resmi Terbit (DISETUJUI PURCHASING)
        $emailSent = false;
        if (!$isDraft) {
            try {
                require_once __DIR__ . '/../../config/mailer.php';
                if (function_exists('sendNotificationEvent')) {
                    // Dapatkan nama vendor untuk rincian email
                    $namaVendor = '';
                    $stmtV = $conn->prepare("SELECT nama_perusahaan FROM vendor WHERE id_vendor = ? LIMIT 1");
                    $stmtV->bind_param("i", $idVendor);
                    $stmtV->execute();
                    $resV = $stmtV->get_result();
                    if ($resV && $rowV = $resV->fetch_assoc()) {
                        $namaVendor = $rowV['nama_perusahaan'];
                    }
                    $stmtV->close();

                    $approverName = $currentUser['nama_karyawan'] ?? ($currentUser['nama_users'] ?? ($currentUser['username'] ?? 'Staff Purchasing'));
                    $mailRes = sendNotificationEvent($conn, 'ro_status_update', [
                        'id_request' => $idRequest,
                        'status' => 'DISETUJUI PURCHASING',
                        'actor_name' => $approverName,
                        'keterangan' => $keteranganPo,
                        'extra_data' => [
                            'nomor_po' => $nomorPo,
                            'id_po' => $newIdPo,
                            'nama_vendor' => $namaVendor
                        ]
                    ]);
                    $emailSent = !empty($mailRes['success']);
                }
            } catch (Throwable $t) {
                error_log("Gagal mengirim notifikasi PO Terbit untuk RO {$ro['nomor']}: " . $t->getMessage());
            }
        }

        // Catat Log Aktivitas Pengguna
        require_once __DIR__ . '/../../config/activity_logger.php';
        logActivity($conn, [
            'modul'           => 'PURCHASE_ORDER',
            'aksi'            => $isDraft ? 'CREATE_DRAFT' : 'CREATE',
            'id_referensi'    => $newIdPo,
            'nomor_referensi' => $nomorPo,
            'deskripsi'       => "Menerbitkan dokumen Purchase Order {$nomorPo} (Status: {$statusPo}) dari Request Order {$ro['nomor']}.",
            'data_sesudahnya' => [
                'id_po'        => $newIdPo,
                'nomor_po'     => $nomorPo,
                'id_request'   => $idRequest,
                'nomor_ro'     => $ro['nomor'],
                'id_vendor'    => $idVendor,
                'status'       => $statusPo,
                'pajak'        => $pajak,
                'diskon'       => $diskonGlobal,
                'total_items'  => count($roItems)
            ]
        ]);

        $msg = $isDraft 
            ? "Purchase Order {$nomorPo} berhasil disimpan sebagai Draft."
            : "Purchase Order {$nomorPo} berhasil disetujui & diterbitkan dari Request Order {$ro['nomor']}.";

        jsonResponse(true, $msg, [
            'id_po' => $newIdPo,
            'nomor_po' => $nomorPo,
            'id_request' => $idRequest,
            'nomor_ro' => $ro['nomor'],
            'is_draft' => $isDraft,
            'status' => $statusPo,
            'email_sent' => $emailSent
        ], 201);

    } catch (Exception $e) {
        $conn->rollback();
        jsonResponse(false, $e->getMessage(), null, 500);
    }
}

jsonResponse(false, 'Aksi tidak dikenali. Gunakan action: approve, draft, reject, atau cancel.', null, 422);
