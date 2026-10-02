<?php
/**
 * Halaman Cetak Berita Acara Penyesuaian Stok (Stock Adjustment)
 * Path: admin/pages/adjustment_stok/print.php
 * Format: Terintegrasi External CSS (styles/print_document.css) sesuai template standar sistem
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MEKANIK, ROLE_MANAGER]);
$id = isset($_GET['id']) ? decodeId($_GET['id']) : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($id <= 0) {
    die('ID Adjustment tidak valid.');
}

// Ambil Data Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'logistik@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
$logoWebPath = '';
$hasLogo = false;

if (!empty($companyLogo)) {
    $cleanPath = ltrim($companyLogo, '/\\');
    if (file_exists(__DIR__ . '/../../../' . $cleanPath)) {
        $logoWebPath = BASE_URL . '/' . $cleanPath;
        $hasLogo = true;
    } elseif (file_exists(__DIR__ . '/../../../uploads/profile/' . $cleanPath)) {
        $logoWebPath = BASE_URL . '/uploads/profile/' . $cleanPath;
        $hasLogo = true;
    }
}

// Query Header
$stmt = $conn->prepare("
    SELECT 
        a.id_adjustment,
        a.nomor_adjustment,
        a.tanggal_adjustment,
        a.jenis_adjustment,
        a.alasan,
        a.keterangan,
        a.status,
        a.tanggal_approved,
        s.nama_site,
        s.kode_site AS inisial_site,
        k_created.nama_karyawan AS pembuat_nama,
        k_created.kode_karyawan AS pembuat_kode,
        j_created.nama_jabatan AS pembuat_jabatan,
        k_app.nama_karyawan AS approver_nama,
        k_app.kode_karyawan AS approver_kode,
        j_app.nama_jabatan AS approver_jabatan
    FROM adjustment_stok a
    LEFT JOIN site s ON a.id_site = s.id_site
    LEFT JOIN karyawan k_created ON a.id_karyawan = k_created.id_karyawan
    LEFT JOIN jabatan j_created ON k_created.id_jabatan = j_created.id_jabatan
    LEFT JOIN karyawan k_app ON a.id_karyawan_approved = k_app.id_karyawan
    LEFT JOIN jabatan j_app ON k_app.id_jabatan = j_app.id_jabatan
    WHERE a.id_adjustment = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$header = $res->fetch_assoc();
$stmt->close();

if (!$header) {
    die('Data Adjustment tidak ditemukan.');
}

// Validasi Status: Hanya boleh dicetak jika sudah APPROVED (disetujui)
if ($header['status'] !== 'APPROVED') {
    http_response_code(403);
    
    $statusTitle = 'Dokumen Belum Disetujui';
    $statusDesc = 'Berita Acara Penyesuaian Stok <strong>' . htmlspecialchars($header['nomor_adjustment']) . '</strong> saat ini berstatus <span class="badge bg-secondary">' . htmlspecialchars($header['status']) . '</span> dan belum dapat dicetak sampai disetujui (APPROVED).';
    $statusIcon = 'bi-exclamation-triangle text-warning';

    if ($header['status'] === 'REJECTED') {
        $statusTitle = 'Dokumen Ditolak (REJECTED)';
        $statusDesc = 'Berita Acara Penyesuaian Stok <strong>' . htmlspecialchars($header['nomor_adjustment']) . '</strong> telah <strong>DITOLAK</strong> sehingga tidak dapat dicetak.';
        $statusIcon = 'bi-x-octagon text-danger';
    } elseif ($header['status'] === 'BATAL') {
        $statusTitle = 'Dokumen Dibatalkan (BATAL)';
        $statusDesc = 'Berita Acara Penyesuaian Stok <strong>' . htmlspecialchars($header['nomor_adjustment']) . '</strong> telah <strong>DIBATALKAN</strong> sehingga tidak dapat dicetak.';
        $statusIcon = 'bi-dash-circle text-danger';
    }

    echo '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($statusTitle) . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="card shadow-sm border-0 text-center p-4" style="max-width: 480px;">
            <div class="card-body">
                <i class="bi ' . $statusIcon . ' display-4 d-block mb-3"></i>
                <h5 class="fw-bold text-dark mb-2">' . htmlspecialchars($statusTitle) . '</h5>
                <p class="text-muted small mb-4">' . $statusDesc . '</p>
                <div class="d-flex justify-content-center gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" onclick="window.close()">Tutup Halaman</button>
                    <a href="' . BASE_URL . '/admin/pages/adjustment_stok/index.php" class="btn btn-primary btn-sm px-4">Kembali ke Daftar</a>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Query Detail
$stmtDtl = $conn->prepare("
    SELECT 
        d.id_adjustment_detail,
        d.id_barang,
        d.qty_sistem,
        d.qty_fisik,
        d.qty_adjustment,
        d.qty_akhir,
        d.keterangan,
        d.harga_satuan,
        d.subtotal_adjustment,
        b.kode_barang,
        b.nama_barang,
        b.satuan,
        kb.nama_kategori,
        mb.nama_merk
    FROM adjustment_stok_detail d
    JOIN barang b ON d.id_barang = b.id_barang
    LEFT JOIN kategori_barang kb ON b.id_kategori = kb.id_kategori
    LEFT JOIN merk_barang mb ON b.id_merk = mb.id_merk
    WHERE d.id_adjustment = ?
    ORDER BY d.id_adjustment_detail ASC
");
$stmtDtl->bind_param("i", $id);
$stmtDtl->execute();
$resDtl = $stmtDtl->get_result();
$items = [];
$totalKtsPenyesuaian = 0;
$totalNilaiSelisih = 0;

while ($r = $resDtl->fetch_assoc()) {
    $items[] = $r;
    $totalKtsPenyesuaian += abs((float)$r['qty_adjustment']);
    $totalNilaiSelisih += (float)$r['subtotal_adjustment'];
}
$stmtDtl->close();

$kolomKtsTitle = ($header['jenis_adjustment'] === 'PENGURANGAN') ? 'Kts Kurang' : 'Kts Tambah';
$isPengurangan = ($header['jenis_adjustment'] === 'PENGURANGAN');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Stock Adjustment - <?= htmlspecialchars($header['nomor_adjustment']) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet Standard -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Berita Acara Stock Adjustment
            </span>
            <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($header['nomor_adjustment']) ?></span>
            <span class="badge <?= $header['status'] === 'APPROVED' ? 'bg-success' : ($header['status'] === 'PENDING' ? 'bg-warning text-dark' : 'bg-secondary') ?>"><?= $header['status'] ?></span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group">
                <a href="?id=<?= encodeId($id) ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= encodeId($id) ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/adjustment_stok/index.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- CONTAINER UTAMA DOKUMEN -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>" id="printContainer">
    
    <?php if ($useKop): ?>
    <!-- KOP SURAT (SESUAI PROFIL PERUSAHAAN) -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if ($hasLogo): ?>
                <img src="<?= htmlspecialchars($logoWebPath) ?>" alt="Logo" style="width: 54px; height: 54px; object-fit: contain; flex-shrink: 0;">
            <?php else: ?>
                <svg width="54" height="54" viewBox="0 0 100 100" style="flex-shrink: 0;">
                    <polygon points="50,4 92,27 92,73 50,96 8,73 8,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <polyline points="8,27 50,50 92,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <line x1="50" y1="50" x2="50" y2="96" stroke="#000" stroke-width="8"/>
                    <polygon points="50,22 74,35 50,48 26,35" fill="#000"/>
                    <polygon points="26,41 46,51 46,76 26,65" fill="#000"/>
                    <polygon points="74,41 54,51 54,76 74,65" fill="#000"/>
                </svg>
            <?php endif; ?>

            <div>
                <div class="company-title"><?= htmlspecialchars($companyName) ?></div>
                <div class="company-addr"><?= htmlspecialchars($companyAddr) ?><?= $companyCity ? ' - ' . htmlspecialchars($companyCity) : '' ?></div>
                <div class="company-contacts">
                    <?php if (!empty($companyPhone)): ?>
                        <span><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($companyPhone) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyWa)): ?>
                        <span><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($companyWa) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyEmail)): ?>
                        <span><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($companyEmail) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tagline-container">
            <div class="tagline-divider"></div>
            <div class="tagline-text">
                SOLUSI<br>LOGISTIK<br>UNTUK<br>INDUSTRI
            </div>
        </div>
    </div>
    
    <div class="header-divider-line"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>: Bagian atas dikosongkan untuk dicetak pada kertas berkop resmi perusahaan.
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & KOTAK NOMOR DOKUMEN -->
    <div class="title-box-row">
        <div class="title-area">
            <div class="doc-title-main">BERITA ACARA PENYESUAIAN STOK</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">STOCK &nbsp; ADJUSTMENT &nbsp; REPORT</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Dokumen</div>
            <div class="box-row-val font-monospace"><?= htmlspecialchars($header['nomor_adjustment']) ?></div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal</div>
            <div class="box-row-val"><?= date('d/m/Y', strtotime($header['tanggal_adjustment'])) ?></div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM -->
    <div class="info-grid">
        <div class="info-col-left">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Lokasi Site / Gudang</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold"><?= htmlspecialchars($header['nama_site']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Jenis Penyesuaian</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold"><?= htmlspecialchars($header['jenis_adjustment']) ?></td>
                </tr>
            </table>
        </div>

        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Status Dokumen</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold"><?= htmlspecialchars($header['status']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Alasan Penyesuaian</td>
                    <td class="colon">:</td>
                    <td class="val"><?= htmlspecialchars($header['alasan']) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL BARANG UTAMA -->
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th>Nama Barang</th>
                <th style="width: 65px;">Satuan</th>
                <th style="width: 80px;">Kts Sistem</th>
                <th style="width: 90px;"><?= $kolomKtsTitle ?></th>
                <th style="width: 80px;">Kts Akhir</th>
                <th style="width: 105px;">Harga</th>
                <th style="width: 140px;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 15px;">Tidak ada rincian barang.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $item): 
                    $qtyAdj = (float)$item['qty_adjustment'];
                    $displayAdj = ($isPengurangan ? '-' : '+') . number_format(abs($qtyAdj), 0, ',', '.');
                ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($item['nama_barang']) ?></strong>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($item['satuan']) ?></td>
                    <td class="text-center font-monospace"><?= number_format($item['qty_sistem'], 0, ',', '.') ?></td>
                    <td class="text-center font-monospace fw-bold"><?= $displayAdj ?></td>
                    <td class="text-center font-monospace fw-bold"><?= number_format($item['qty_akhir'], 0, ',', '.') ?></td>
                    <td class="text-end font-monospace"><?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                    <td style="color: #444;"><?= htmlspecialchars($item['keterangan'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- KOTAK KETERANGAN & CATATAN -->
    <?php if (!empty($header['keterangan'])): ?>
    <div class="catatan-penerimaan-section">
        <div class="notes-title">Keterangan Kronologi / Catatan Tambahan:</div>
        <div class="catatan-box">
            <?= nl2br(htmlspecialchars($header['keterangan'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- TANDA TANGAN (2 KOLOM RESMI) -->
    <div class="sig-section">
        <div class="row g-0 justify-content-between">
            <div class="col-5 sig-col">
                <div class="sig-header-main">Dibuat Oleh,</div>
                <div class="sig-header-sub">Petugas Pembuat</div>
                <div class="sig-line-box">
                    <span class="sig-person-name"><?= htmlspecialchars($header['pembuat_nama'] ?: 'Petugas') ?></span>
                </div>
                <div class="sig-footer-note"><?= htmlspecialchars($header['pembuat_jabatan'] ?: 'Staff Mekanik') ?></div>
            </div>

            <div class="col-5 sig-col">
                <div class="sig-header-main">Disetujui Oleh,</div>
                <div class="sig-header-sub">Kepala Site / Logistik</div>
                <div class="sig-line-box">
                    <span class="sig-person-name"><?= htmlspecialchars($header['approver_nama'] ?: '( Belum Disetujui )') ?></span>
                </div>
                <div class="sig-footer-note">
                    <?= htmlspecialchars($header['approver_jabatan'] ?: 'Kepala Site') ?>
                    <?php if ($header['tanggal_approved']): ?>
                        <br><small><?= date('d/m/Y H:i', strtotime($header['tanggal_approved'])) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH -->
    <div class="footer-line-container">
        <div class="footer-right">
            <div class="fw-bold">Halaman 1 dari 1</div>
            <div>Dicetak: <?= date('d/m/Y H:i') ?></div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

</body>
</html>
