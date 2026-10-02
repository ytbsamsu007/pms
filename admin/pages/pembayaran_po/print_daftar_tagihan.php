<?php
/**
 * Halaman Cetak / Download Lampiran Daftar Tagihan Vendor (Transfer Bank)
 * Path: admin/pages/pembayaran_po/print_daftar_tagihan.php
 * Format: Grouped by Bank dengan Kolom Kosong untuk Eksekusi Transfer
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

$searchVendor = trim($_GET['vendor'] ?? ($_GET['search'] ?? ''));
$namaBank = trim($_GET['bank'] ?? ($_GET['nama_bank'] ?? ''));
$bulan = isset($_GET['bulan']) ? intval($_GET['bulan']) : 0;
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

$whereClause = [
    "faktur_po.sisa_tagihan > 0",
    "faktur_po.status != 'BATAL'"
];
$params = [];
$types = "";

if (!empty($searchVendor)) {
    $whereClause[] = "(vendor.nama_perusahaan LIKE ? OR faktur_po.nomor_faktur LIKE ? OR faktur_po.nomor_faktur_vendor LIKE ?)";
    $like = "%{$searchVendor}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($namaBank)) {
    $whereClause[] = "faktur_po.nama_bank = ?";
    $params[] = $namaBank;
    $types .= "s";
}

if ($bulan >= 1 && $bulan <= 12) {
    $whereClause[] = "MONTH(faktur_po.tanggal_jatuh_tempo) = ?";
    $params[] = $bulan;
    $types .= "i";
}

if ($tahun > 0) {
    $whereClause[] = "YEAR(faktur_po.tanggal_jatuh_tempo) = ?";
    $params[] = $tahun;
    $types .= "i";
}

$whereSql = implode(" AND ", $whereClause);

$sql = "SELECT
            faktur_po.id_faktur,
            faktur_po.nomor_faktur,
            faktur_po.nomor_faktur_vendor,
            faktur_po.tanggal_jatuh_tempo,
            COALESCE(NULLIF(faktur_po.nama_bank, ''), 'LAIN-LAIN / BELUM ADA BANK') AS bank_group,
            faktur_po.nomor_rekening,
            faktur_po.atas_nama_rekening,
            faktur_po.total_tagihan,
            faktur_po.terbayar,
            faktur_po.sisa_tagihan,
            vendor.nama_perusahaan
        FROM faktur_po
        JOIN vendor ON faktur_po.id_vendor = vendor.id_vendor
        WHERE {$whereSql}
        ORDER BY bank_group ASC, faktur_po.tanggal_jatuh_tempo ASC, vendor.nama_perusahaan ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group items by bank_group
$groupedData = [];
$grandTotalTagihan = 0;
$totalFakturCount = count($items);

foreach ($items as $it) {
    $bg = trim($it['bank_group']);
    if (!isset($groupedData[$bg])) {
        $groupedData[$bg] = [
            'bank_name' => $bg,
            'items'     => [],
            'subtotal'  => 0
        ];
    }
    $groupedData[$bg]['items'][] = $it;
    $groupedData[$bg]['subtotal'] += (float)$it['sisa_tagihan'];
    $grandTotalTagihan += (float)$it['sisa_tagihan'];
}

// Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'finance@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';

$bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$periodeText = ($bulan > 0 ? $bulanNames[$bulan] . ' ' : '') . ($tahun > 0 ? $tahun : 'Semua Periode');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lampiran Daftar Tagihan Vendor - <?= htmlspecialchars($periodeText) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">

    <style>
    @page {
        size: A4 landscape;
        margin: 8mm 10mm 8mm 10mm;
    }

    body.print-mode {
        background-color: #f0f2f5;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11.5px;
        color: #000000;
    }

    .print-wrapper {
        max-width: 1060px !important;
        margin: 15px auto;
        background: #ffffff;
        padding: 24px 30px;
        box-shadow: 0 4px 22px rgba(0,0,0,0.1);
        border-radius: 4px;
        box-sizing: border-box;
        color: #000000;
    }

    .doc-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        font-size: 11px;
        color: #000000;
        background-color: transparent;
    }

    .doc-table th {
        background-color: transparent !important;
        color: #000000 !important;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.4px;
        border: 1px solid #000000 !important;
        padding: 6px 8px;
        vertical-align: middle;
    }

    .doc-table td {
        background-color: transparent !important;
        border: 1px solid #000000 !important;
        padding: 6px 8px;
        vertical-align: middle;
        color: #000000 !important;
    }

    .bank-group-title {
        background-color: transparent !important;
        font-weight: bold;
        font-size: 11.5px;
        color: #000000 !important;
        border: 1px solid #000000 !important;
        padding: 6px 10px;
        text-transform: uppercase;
    }

    .bank-subtotal-row td {
        background-color: transparent !important;
        color: #000000 !important;
        font-weight: bold;
        border-top: 1px solid #000000 !important;
        border-bottom: 1.5px solid #000000 !important;
    }

    .grand-total-row td {
        background-color: transparent !important;
        color: #000000 !important;
        font-weight: bold;
        font-size: 11.5px;
        border-top: 2px solid #000000 !important;
        border-bottom: 2px solid #000000 !important;
    }

    .blank-cell {
        background-color: transparent !important;
        min-width: 90px;
    }

    .meta-badge {
        display: inline-block;
        border: 1px solid #000000;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 11px;
        background: transparent;
        color: #000000;
    }

    .signature-container {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        page-break-inside: avoid;
        color: #000000;
    }

    .sig-box {
        text-align: center;
        width: 28%;
    }

    .sig-line {
        border-bottom: 1px solid #000000;
        margin-top: 50px;
        margin-bottom: 4px;
    }

    @media print {
        .no-print {
            display: none !important;
        }
        body.print-mode {
            background-color: #ffffff;
            color: #000000;
        }
        .print-wrapper {
            max-width: 100% !important;
            margin: 0;
            padding: 0;
            box-shadow: none;
            border-radius: 0;
            background: #ffffff !important;
        }
        .doc-table, .doc-table th, .doc-table td, .bank-group-title, .bank-subtotal-row td, .grand-total-row td {
            background-color: transparent !important;
            color: #000000 !important;
            border-color: #000000 !important;
        }
    }
    </style>
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap gap-2 px-3">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Lampiran Daftar Tagihan Transfer
            </span>
            <span class="badge bg-secondary font-monospace"><?= $totalFakturCount ?> Faktur</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?vendor=<?= urlencode($searchVendor) ?>&bank=<?= urlencode($namaBank) ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?vendor=<?= urlencode($searchVendor) ?>&bank=<?= urlencode($namaBank) ?>&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php" class="btn btn-outline-light btn-sm px-3">
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
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/../../../' . $companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyLogo) ?>" alt="Logo" style="width: 50px; height: 50px; object-fit: contain; flex-shrink: 0;">
            <?php else: ?>
                <svg width="50" height="50" viewBox="0 0 100 100" style="flex-shrink: 0;">
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
                LAMPIRAN<br>DAFTAR TAGIHAN<br>TRANSFER VENDOR
            </div>
        </div>
    </div>
    
    <div class="header-divider-line"></div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & INFO FILTER -->
    <div class="text-center my-3">
        <h5 class="fw-bold text-dark text-uppercase mb-1" style="letter-spacing: 0.5px;">DAFTAR TAGIHAN PEMBAYARAN VENDOR</h5>
        <div class="text-muted small">Lampiran Rekapitulasi Rencana Transfer Pembayaran Kas / Bank Kepada Vendor</div>
    </div>

    <!-- META FILTER INFO -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <span class="meta-badge"><strong>Periode:</strong> <?= htmlspecialchars($periodeText) ?></span>
            <?php if (!empty($namaBank)): ?>
                <span class="meta-badge"><strong>Filter Bank:</strong> <?= htmlspecialchars($namaBank) ?></span>
            <?php endif; ?>
            <?php if (!empty($searchVendor)): ?>
                <span class="meta-badge"><strong>Cari Vendor:</strong> <?= htmlspecialchars($searchVendor) ?></span>
            <?php endif; ?>
        </div>
        <div class="text-end small text-muted">
            Tanggal Cetak: <strong><?= date('d/m/Y H:i') ?></strong> | Dicetak Oleh: <strong><?= htmlspecialchars($user['nama_karyawan'] ?? 'Finance') ?></strong>
        </div>
    </div>

    <!-- TABEL DATA TAGIHAN GROUPED BY BANK -->
    <?php if (empty($groupedData)): ?>
        <div class="alert alert-warning text-center my-4 py-3">
            <i class="bi bi-exclamation-triangle me-1"></i> Tidak ditemukan tagihan jatuh tempo yang sesuai dengan filter.
        </div>
    <?php else: ?>
        <table class="doc-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">No</th>
                    <th style="width: 220px;">Vendor</th>
                    <th style="width: 140px;">Nomor Rekening</th>
                    <th style="width: 170px;">Atas Nama</th>
                    <th style="width: 130px;" class="text-end">Nominal Tagihan</th>
                    <th style="width: 130px;" class="text-center">Nominal Dibayar</th>
                    <th style="width: 140px;" class="text-center">No Rek Pembayaran</th>
                    <th style="width: 65px;" class="text-center">Paraf</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $globalIndex = 1;
                foreach ($groupedData as $bankNameKey => $group): 
                ?>
                    <!-- HEADER GRUP NAMA BANK -->
                    <tr>
                        <td colspan="8" class="bank-group-title">
                            NAMA BANK: <?= htmlspecialchars($bankNameKey) ?> (<?= count($group['items']) ?> Faktur)
                        </td>
                    </tr>

                    <?php foreach ($group['items'] as $it): ?>
                        <tr>
                            <td class="text-center font-monospace"><?= $globalIndex++ ?></td>
                            <td>
                                <strong><?= htmlspecialchars($it['nama_perusahaan']) ?></strong>
                                <div class="text-muted" style="font-size: 9.5px;"><?= htmlspecialchars($it['nomor_faktur']) ?></div>
                            </td>
                            <td class="font-monospace fw-semibold"><?= htmlspecialchars($it['nomor_rekening'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($it['atas_nama_rekening'] ?: $it['nama_perusahaan']) ?></td>
                            <td class="text-end font-monospace fw-bold">
                                <?= number_format($it['sisa_tagihan'], 0, ',', '.') ?>
                            </td>
                            <!-- Kolom Kosong untuk Pelaksanaan Transfer -->
                            <td class="blank-cell"></td>
                            <td class="blank-cell"></td>
                            <td class="blank-cell text-center"></td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- SUBTOTAL PER BANK -->
                    <tr class="bank-subtotal-row">
                        <td colspan="4" class="text-end text-uppercase" style="font-size: 10.5px;">
                            Subtotal Tagihan (<?= htmlspecialchars($bankNameKey) ?>):
                        </td>
                        <td class="text-end font-monospace fw-bold">
                            <?= number_format($group['subtotal'], 0, ',', '.') ?>
                        </td>
                        <td colspan="3"></td>
                    </tr>
                <?php endforeach; ?>

                <!-- GRAND TOTAL KESELURUHAN -->
                <tr class="grand-total-row">
                    <td colspan="4" class="text-end text-uppercase py-2" style="font-size: 11.5px;">
                        GRAND TOTAL KESELURUHAN (<?= $totalFakturCount ?> FAKTUR):
                    </td>
                    <td class="text-end font-monospace fw-bold py-2" style="font-size: 12.5px;">
                        <?= number_format($grandTotalTagihan, 0, ',', '.') ?>
                    </td>
                    <td colspan="3" class="py-2"></td>
                </tr>
            </tbody>
        </table>

        <!-- AREA TANDA TANGAN -->
        <div class="signature-container">
            <div class="sig-box">
                <div>Dibuat Oleh,</div>
                <div class="text-muted" style="font-size: 10px;">Staff Keuangan / AP</div>
                <div class="sig-line"></div>
                <div class="fw-bold"><?= htmlspecialchars($user['nama_karyawan'] ?? '(........................)') ?></div>
            </div>

            <div class="sig-box">
                <div>Disetujui Oleh,</div>
                <div class="text-muted" style="font-size: 10px;">Manajer Keuangan / Direktur</div>
                <div class="sig-line"></div>
                <div class="fw-bold">(....................................)</div>
            </div>

            <div class="sig-box">
                <div>Dilaksanakan Oleh,</div>
                <div class="text-muted" style="font-size: 10px;">Kasir / Pelaksana Transfer</div>
                <div class="sig-line"></div>
                <div class="fw-bold">(....................................)</div>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
