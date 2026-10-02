<?php
/**
 * Halaman Login 2 Kolom (Pengumuman Sistem & Form Login) - PT Jaya Teknik
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

// Jika sudah login, langsung arahkan ke Dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$companyProfile = getCompanyProfile();
$companyName = $companyProfile['nama'] ?? 'PT Jaya Teknis';
$companyAddress = $companyProfile['alamat'] ?? 'Bengkel Las & Bubut Kapal';
$companyCity = $companyProfile['kota'] ?? 'Surabaya';


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Theme Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/app.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/login.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-bg-shapes">
        <div class="login-shape-1"></div>
        <div class="login-shape-2"></div>
    </div>

    <!-- 2-Column Split Container -->
    <div class="login-card-container">
        <div class="row g-0">
            
            <!-- =========================================================
                 KOLOM KIRI: PENGUMUMAN & INFORMASI SISTEM
                 ========================================================= -->
            <div class="col-lg-6 login-left-pane">
                
                <!-- Branding Header -->
                <div>
                    <div class="brand-header-box">
                        <?php if (!empty($companyProfile['picture'])): ?>
                            <div class="brand-icon-bubble" style="background: white; border: none; overflow: hidden; padding: 4px;">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyProfile['picture']) ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                            </div>
                        <?php else: ?>
                            <div class="brand-icon-bubble">
                                <i class="bi bi-shield-check"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="fw-bold mb-0 text-white tracking-tight"><?= htmlspecialchars($companyName) ?></h4>
                            <span class="text-white-50 small fw-medium">Purchase Management System</span>
                        </div>
                    </div>

                    <!-- Header Papan Pengumuman -->
                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom border-white border-opacity-10">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-indicator"></span>
                            <span class="small fw-bold text-uppercase tracking-wider text-info" style="font-size: 0.78rem;">
                                Pengumuman &amp; Informasi
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Feed Pengumuman List (Scrollable) -->
                <div class="announcement-feed-wrapper">
                    <div class="announcement-scroll-area" id="announcementListContainer">
                        <div class="text-center py-4 text-white-50 small">
                            <span class="spinner-border spinner-border-sm me-2 text-info"></span>Memuat pengumuman terbaru...
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-top border-white border-opacity-10 mt-3 d-flex justify-content-between align-items-center text-white-50 small" style="font-size: 0.75rem;">
                    <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?></span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalFiturSistem" style="font-size: 0.7rem;">
                            <i class="bi bi-card-list me-1"></i> Fitur Sistem
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalFlowchart" style="font-size: 0.7rem;">
                            <i class="bi bi-diagram-3 me-1"></i> Lihat Alur Sistem
                        </button>
                    </div>
                </div>
            </div>

            <!-- =========================================================
                 KOLOM KANAN: FORM LOGIN KE SISTEM
                 ========================================================= -->
            <div class="col-lg-6 login-right-pane">
                
                <div class="mb-4">
                    <h3 class="fw-bold text-dark mb-1">Masuk ke Akun</h3>
                    <p class="text-muted small mb-0">Silakan masukkan username/email dan password Anda.</p>
                </div>

                <!-- Alert Box Error -->
                <div id="loginAlert" class="alert alert-danger d-none py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="loginAlertText"></span>
                </div>

                <form id="loginForm" onsubmit="handleLoginSubmit(event)">
                    <!-- Email / Username Input -->
                    <div class="form-group-custom">
                        <label class="form-label small fw-semibold text-secondary mb-1">Email atau Username</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" class="form-control" id="identity" name="identity" placeholder="nama / email user" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <!-- Password Input with Lupa Password Link -->
                    <div class="form-group-custom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-secondary mb-0">Password</label>
                            <!-- Link Lupa Password -->
                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modalResetPassword" class="small text-primary text-decoration-none fw-semibold" style="font-size: 0.8rem;">
                                Lupa Password?
                            </a>
                        </div>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility()" title="Lihat password">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mt-4">
                        <button type="submit" id="btnSubmit" class="btn btn-login-submit d-flex align-items-center justify-content-center">
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                            <span id="btnText"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem</span>
                        </button>
                    </div>
                </form>

                <!-- Quick Demo Account Pills (Dinamis dari Database) -->
                <div class="demo-roles-container mb-4">
                    <div class="text-center text-muted small mb-2 fw-bold" style="font-size: 0.72rem;">
                        UJI COBA ROLE CEPAT:
                    </div>
                    <div class="d-flex flex-wrap gap-1 justify-content-center" id="demoRolesContainer">
                        <span class="spinner-border spinner-border-sm text-muted" role="status" aria-hidden="true"></span>
                    </div>
                </div>

                <div class="text-center text-black-50 fst-italic w-100" style="font-size: 0.65rem;">
                    <i class="bi bi-display"></i> Best view on PC / Tablet (16:9)
                </div>

            </div>

        </div>
    </div>
</div>

<!-- =============================================================
     MODAL BACA DETAIL PENGUMUMAN DI HALAMAN LOGIN
     ============================================================= -->
<div class="modal fade" id="modalLoginAnnouncementDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-white border-bottom py-3 px-4 d-flex align-items-center">
                <div>
                    <h5 class="modal-title fs-5 fw-bold mb-0 text-dark">
                        Pusat Informasi
                    </h5>
                    
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" id="loginAnnouncementModalBody">
                <!-- Rendered dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL FLOWCHART SISTEM
     ============================================================= -->
<div class="modal fade" id="modalFlowchart" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark text-white border-0 shadow-lg">
            <div class="modal-header border-bottom border-secondary py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-diagram-3-fill me-2 text-info"></i>Alur Sistem (Flowchart)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 position-relative overflow-hidden bg-black" id="flowchartContainer" style="height: 75vh; cursor: grab;">
                <!-- Toolbar Zoom -->
                <div class="position-absolute top-0 end-0 m-3 z-3 bg-dark rounded p-1 bg-opacity-75 shadow">
                    <button class="btn btn-sm btn-outline-light border-0" onclick="zoomFlowchart(0.2)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
                    <button class="btn btn-sm btn-outline-light border-0" onclick="zoomFlowchart(-0.2)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
                    <button class="btn btn-sm btn-outline-light border-0" onclick="resetZoomFlowchart()" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
                <!-- Image -->
                <img src="<?= BASE_URL ?>/images/uploads/company/Flowchart.png" id="flowchartImage" class="img-fluid" style="transform-origin: center; transition: transform 0.1s ease; pointer-events: none; width: 100%; height: 100%; object-fit: contain;">
            </div>
            <div class="modal-footer bg-dark border-top border-secondary py-1 px-3 d-flex justify-content-center">
                <span class="small text-muted"><i class="bi bi-info-circle me-1"></i> Scroll untuk zoom, klik & tahan untuk menggeser.</span>
            </div>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL FITUR SISTEM (CAROUSEL)
     ============================================================= -->
<div class="modal fade" id="modalFiturSistem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg bg-light">
            <div class="modal-header bg-white py-3 border-bottom d-flex align-items-center">
                <div>
                    <h5 class="modal-title fs-5 fw-bold mb-0 text-dark">Fitur Sistem Terintegrasi</h5>
                    <small class="text-muted">Terakhir diperbarui: 21 September 2026</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div id="featuresCarousel" class="carousel slide" data-bs-ride="false" data-bs-wrap="false">
                <div class="carousel-inner p-4 pb-2">
                    
                    <!-- Modul 1 -->
                    <div class="carousel-item active">
                        <div class="text-center mb-4">
                            <span class="badge bg-primary mb-2 px-3 py-2 rounded-pill shadow-sm">Modul 01 / 10</span>
                            <h4 class="fw-bold text-dark">Keamanan & Akses</h4>
                            <p class="text-muted small">Pemisahan hak akses berbasis peran kerja.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-person-badge text-primary me-2"></i>Multi-Role Login</h6><p class="small text-muted mb-0">Hak akses berjenjang (Admin s/d Manager).</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-shield-lock text-primary me-2"></i>Session Management</h6><p class="small text-muted mb-0">Autentikasi aman dan proteksi sesi ketat.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-lightning text-primary me-2"></i>Quick Demo Login</h6><p class="small text-muted mb-0">Simulasi peran cepat dengan satu klik.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-speedometer2 text-primary me-2"></i>Role Dashboard</h6><p class="small text-muted mb-0">Tampilan otomatis menyesuaikan tugas.</p></div></div></div>
                        </div>
                    </div>

                    <!-- Modul 2 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge mb-2 px-3 py-2 rounded-pill shadow-sm" style="background-color: #6610f2; color: white;">Modul 02 / 10</span>
                            <h4 class="fw-bold text-dark">Pengaturan Perusahaan</h4>
                            <p class="text-muted small">Konfigurasi entitas, divisi, dan jabatan struktural.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-building text-indigo me-2" style="color: #6610f2;"></i>Profil Identitas</h6><p class="small text-muted mb-0">Pengaturan legalitas, NPWP & Zona Waktu.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-diagram-2 text-indigo me-2" style="color: #6610f2;"></i>Divisi & Jabatan</h6><p class="small text-muted mb-0">Hierarki kuat penentu wewenang approval.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-geo-alt text-indigo me-2" style="color: #6610f2;"></i>Master Site</h6><p class="small text-muted mb-0">Pemetaan lokasi proyek & gudang pusat.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-person-lines-fill text-indigo me-2" style="color: #6610f2;"></i>Akun Karyawan</h6><p class="small text-muted mb-0">Pengelolaan akses web tiap individu.</p></div></div></div>
                        </div>
                    </div>
                    
                    <!-- Modul 3 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge bg-info mb-2 px-3 py-2 rounded-pill shadow-sm text-dark">Modul 03 / 10</span>
                            <h4 class="fw-bold text-dark">Master Data Inventory</h4>
                            <p class="text-muted small">Sentralisasi katalog barang dan rekap pemasok.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-tags text-info me-2"></i>Kategori & Merk</h6><p class="small text-muted mb-0">Klasifikasi spesifik jenis material suku cadang.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-box-seam text-info me-2"></i>Master Barang</h6><p class="small text-muted mb-0">Database inventory & limit stok minimum gudang.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-truck text-info me-2"></i>Database Vendor</h6><p class="small text-muted mb-0">Profil lengkap supplier & rekening bank.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-bank text-info me-2"></i>Rekening Internal</h6><p class="small text-muted mb-0">Sumber dana pembayaran kas keluar.</p></div></div></div>
                        </div>
                    </div>
                    
                    <!-- Modul 4 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge bg-warning text-dark mb-2 px-3 py-2 rounded-pill shadow-sm">Modul 04 / 10</span>
                            <h4 class="fw-bold text-dark">Request Order (RO)</h4>
                            <p class="text-muted small">Alur permintaan material / kebutuhan dari lapangan.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-pen text-warning me-2"></i>Pembuatan RO</h6><p class="small text-muted mb-0">Inisiasi permintaan pengadaan dari mekanik.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-file-earmark-text text-warning me-2"></i>Sistem Draft</h6><p class="small text-muted mb-0">Simpan revisi sebelum finalisasi/pengajuan.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-search text-warning me-2"></i>Cek Logistik</h6><p class="small text-muted mb-0">Pemeriksaan silang stok fisik vs beli baru.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-check-circle text-warning me-2"></i>Approval Manager</h6><p class="small text-muted mb-0">Gatekeeper persetujuan RO oleh pimpinan.</p></div></div></div>
                        </div>
                    </div>

                    <!-- Modul 5 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge bg-success mb-2 px-3 py-2 rounded-pill shadow-sm">Modul 05 / 10</span>
                            <h4 class="fw-bold text-dark">Purchase Order (PO)</h4>
                            <p class="text-muted small">Pembuatan pesanan komersial ke pihak ketiga (Vendor).</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-link-45deg text-success me-2"></i>Integrasi RO ke PO</h6><p class="small text-muted mb-0">Auto-pull data berdasar RO Approved.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-calculator text-success me-2"></i>Kalkulasi Instan</h6><p class="small text-muted mb-0">Perhitungan pajak, diskon & ongkir otomatis.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-ui-checks text-success me-2"></i>Validasi Bertingkat</h6><p class="small text-muted mb-0">Persetujuan ganda (Purchasing & Finance).</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-file-pdf text-success me-2"></i>Cetak & Ekspor</h6><p class="small text-muted mb-0">Surat Pesanan PDF siap kirim ke supplier.</p></div></div></div>
                        </div>
                    </div>

                    <!-- Modul 6 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge bg-danger mb-2 px-3 py-2 rounded-pill shadow-sm">Modul 06 / 10</span>
                            <h4 class="fw-bold text-dark">Penerimaan & Retur</h4>
                            <p class="text-muted small">Logistik fisik dan Quality Control penerimaan gudang.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-box-arrow-in-down text-danger me-2"></i>Terima Barang (Receiving)</h6><p class="small text-muted mb-0">Kroscek fisik dengan Surat Jalan Vendor.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-graph-up-arrow text-danger me-2"></i>Auto Update Stok</h6><p class="small text-muted mb-0">Stok bertambah otomatis secara real-time.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-pie-chart text-danger me-2"></i>Penerimaan Parsial</h6><p class="small text-muted mb-0">Mendukung barang datang bertahap/dicicil.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-arrow-counterclockwise text-danger me-2"></i>Sistem Retur</h6><p class="small text-muted mb-0">Pengembalian barang rusak & koreksi hutang.</p></div></div></div>
                        </div>
                    </div>

                    <!-- Modul 7 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge bg-secondary mb-2 px-3 py-2 rounded-pill shadow-sm">Modul 07 / 10</span>
                            <h4 class="fw-bold text-dark">Keuangan (Finance)</h4>
                            <p class="text-muted small">Pencatatan akuntansi dan kontrol kas pembayaran.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-journal-text text-secondary me-2"></i>Accrual Basis</h6><p class="small text-muted mb-0">Hutang timbul akurat saat barang tiba.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-wallet2 text-secondary me-2"></i>Eksekusi Pembayaran</h6><p class="small text-muted mb-0">Tentukan sumber rekening transfer.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-cash-stack text-secondary me-2"></i>Cicilan Parsial</h6><p class="small text-muted mb-0">Mendukung pelunasan hutang secara bertahap.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-check2-all text-secondary me-2"></i>Validasi Transfer</h6><p class="small text-muted mb-0">Bukti bayar terekam & approval Finance.</p></div></div></div>
                        </div>
                    </div>
                    
                    <!-- Modul 8 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge bg-dark mb-2 px-3 py-2 rounded-pill shadow-sm">Modul 08 / 10</span>
                            <h4 class="fw-bold text-dark">Laporan & Analitik</h4>
                            <p class="text-muted small">Rekapitulasi data pendukung keputusan manajemen.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-bar-chart text-dark me-2"></i>Inventory Report</h6><p class="small text-muted mb-0">Posisi stok & mutasi barang in/out.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-graph-up text-dark me-2"></i>Purchasing Report</h6><p class="small text-muted mb-0">Histori belanja & statistik PO vendor.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-cash text-dark me-2"></i>Finance Report</h6><p class="small text-muted mb-0">Daftar saldo hutang (AP) & kas keluar.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-file-earmark-excel text-dark me-2"></i>Data Export</h6><p class="small text-muted mb-0">Ekspor cepat ke format Excel / PDF.</p></div></div></div>
                        </div>
                    </div>

                    <!-- Modul 9 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge mb-2 px-3 py-2 rounded-pill shadow-sm text-dark" style="background-color: #0dcaf0;">Modul 09 / 10</span>
                            <h4 class="fw-bold text-dark">Informasi Publik</h4>
                            <p class="text-muted small">Papan pengumuman & broadcast internal perusahaan.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-megaphone text-info me-2"></i>Pengumuman Publik</h6><p class="small text-muted mb-0">Informasi global tampil di halaman awal login.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-bullseye text-info me-2"></i>Target Spesifik</h6><p class="small text-muted mb-0">Pengumuman dashboard khusus per-divisi.</p></div></div></div>
                        </div>
                    </div>

                    <!-- Modul 10 -->
                    <div class="carousel-item">
                        <div class="text-center mb-4">
                            <span class="badge bg-danger text-white mb-2 px-3 py-2 rounded-pill shadow-sm">Modul 10 / 10</span>
                            <h4 class="fw-bold text-dark">Proteksi Tambahan</h4>
                            <p class="text-muted small">Keamanan data tingkat lanjut & fail-safe preventions.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-lock-fill text-danger me-2"></i>Anti-Hapus Relasi</h6><p class="small text-muted mb-0">Data transaksi aktif terproteksi dari Delete.</p></div></div></div>
                            <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body"><h6 class="fw-bold"><i class="bi bi-key text-danger me-2"></i>Validasi Kode Unik</h6><p class="small text-muted mb-0">Hapus master data butuh konfirmasi ketik.</p></div></div></div>
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="modal-footer bg-white py-3 px-4 d-flex justify-content-between align-items-center border-top">
                <button class="btn btn-outline-secondary btn-sm px-4 rounded-pill" type="button" data-bs-target="#featuresCarousel" data-bs-slide="prev">
                    <i class="bi bi-chevron-left me-1"></i> Sebelumnya
                </button>
                
                <div class="carousel-indicators position-static m-0" style="gap: 6px;">
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="0" class="active bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;" aria-current="true"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="1" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="2" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="3" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="4" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="5" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="6" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="7" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="8" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                    <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="9" class="bg-secondary" style="width: 8px; height: 8px; border-radius: 50%;"></button>
                </div>
                
                <button class="btn btn-primary btn-sm px-4 rounded-pill shadow-sm" type="button" data-bs-target="#featuresCarousel" data-bs-slide="next">
                    Selanjutnya <i class="bi bi-chevron-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let publicAnnouncementsCache = [];
let loginAnnouncementModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    loginAnnouncementModalInstance = new bootstrap.Modal(document.getElementById('modalLoginAnnouncementDetail'));
    loadPublicAnnouncements();
    loadDemoRoles();
});

// -------------------------------------------------------------
// LOAD DEMO ROLES (API)
// -------------------------------------------------------------
async function loadDemoRoles() {
    const container = document.getElementById('demoRolesContainer');
    try {
        const response = await fetch(BASE_URL + '/api/auth/demo-roles.php');
        const res = await response.json();
        
        if (res && res.success && res.data) {
            let html = '';
            res.data.forEach(demo => {
                html += `
                    <button type="button" 
                            class="role-btn-chip" 
                            title="${escapeHtml(demo.name)} (${escapeHtml(demo.email)})"
                            onclick="setDemoAccount('${escapeHtml(demo.email)}', '${escapeHtml(demo.password)}')">
                        ${escapeHtml(demo.label)}
                    </button>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = '<span class="text-muted small">Gagal memuat roles</span>';
        }
    } catch (e) {
        container.innerHTML = '<span class="text-muted small">Gagal memuat roles</span>';
    }
}

// -------------------------------------------------------------
// LOAD PUBLIC ANNOUNCEMENTS (KOLOM KIRI)
// -------------------------------------------------------------
async function loadPublicAnnouncements() {
    const container = document.getElementById('announcementListContainer');

    try {
        const response = await fetch(BASE_URL + '/api/master/info.php?public=1&limit=10');
        const res = await response.json();
        
        if (res && res.success && res.data && res.data.items && res.data.items.length > 0) {
            publicAnnouncementsCache = res.data.items;

            let html = '';
            publicAnnouncementsCache.forEach((item, idx) => {
                html += `
                    <div class="announcement-card-item" onclick="showLoginAnnouncementDetail(${idx})">
                        <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                            <span class="text-white fw-bold small text-truncate flex-grow-1" style="font-size: 0.86rem; min-width: 0;">
                                ${escapeHtml(item.judul)}
                            </span>
                            <span class="text-white-50 font-monospace flex-shrink-0" style="font-size: 0.68rem;">${item.tanggal_format ? item.tanggal_format.split(',')[0] : ''}</span>
                        </div>
                        <p class="text-white-50 mb-2 small text-truncate" style="font-size: 0.78rem; line-height: 1.4; min-width: 0;">
                            ${escapeHtml(item.isi)}
                        </p>
                        <div class="d-flex justify-content-end align-items-center">
                            <span class="text-info small fw-semibold flex-shrink-0" style="font-size: 0.74rem;">
                                Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="text-center py-5 text-white-50">
                    <i class="bi bi-chat-square-dots fs-2 d-block mb-2 text-info opacity-50"></i>
                    Belum ada pengumuman baru yang diterbitkan.
                </div>
            `;
        }
    } catch (e) {
        container.innerHTML = `
            <div class="text-center py-4 text-white-50 small">
                Gagal memuat feed pengumuman.
            </div>
        `;
    }
}

function showLoginAnnouncementDetail(idx) {
    const item = publicAnnouncementsCache[idx];
    if (!item) return;

    const isImg = item.file && /\.(jpg|jpeg|png|webp|gif)$/i.test(item.file);
    const body = document.getElementById('loginAnnouncementModalBody');

    let imageHtml = '';
    if (item.file_url && isImg) {
        imageHtml = `
            <div class="rounded-3 overflow-hidden mb-3 border bg-light text-center">
                <img src="${item.file_url}" alt="Attachment" class="img-fluid" style="max-height: 240px; object-fit: contain;">
            </div>
        `;
    }

    let fileDownloadHtml = '';
    if (item.file_url && !isImg) {
        fileDownloadHtml = `
            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                <span class="small text-muted"><i class="bi bi-paperclip me-1"></i>Lampiran Dokumen</span>
                <a href="${item.file_url}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="bi bi-download me-1"></i>Unduh Lampiran
                </a>
            </div>
        `;
    }

    const targetDivisi = item.is_all_divisi ? 'Semua Divisi' : (item.divisi_names ? escapeHtml(item.divisi_names.join(', ')) : 'Umum');

    body.innerHTML = `
        <div class="border-bottom pb-3 mb-3">
            <h5 class="fw-bold text-dark mb-3 lh-base">${escapeHtml(item.judul)}</h5>
            <div class="w-100">
                <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                    <div class="fw-bold text-dark text-truncate" style="font-size: 0.95rem;">${escapeHtml(item.pembuat)}</div>
                    <div class="small text-muted text-end flex-shrink-0" style="font-size: 0.75rem;">${item.tanggal_format}</div>
                </div>
                <div class="d-flex align-items-start gap-2" style="font-size: 0.8rem;">
                    <span class="text-muted flex-shrink-0">Kepada:</span> 
                    <span class="text-dark fw-medium text-wrap text-start lh-base">${targetDivisi}</span>
                </div>
            </div>
        </div>
        
        <div class="text-dark mb-2" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.95rem;">${escapeHtml(item.isi)}</div>
        
        ${imageHtml}
        ${fileDownloadHtml}
    `;

    loginAnnouncementModalInstance.show();
}

// -------------------------------------------------------------
// LOGIN FORM HANDLERS
// -------------------------------------------------------------
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

function setDemoAccount(username, pass) {
    document.getElementById('identity').value = username;
    document.getElementById('password').value = pass;
    document.getElementById('loginAlert').classList.add('d-none');
}

function handleResetPasswordClick() {
    // Digantikan dengan Bootstrap Modal (data-bs-toggle)
}

// -------------------------------------------------------------
// FLOWCHART PAN & ZOOM LOGIC
// -------------------------------------------------------------
let flowchartScale = 1;
let isDraggingFlowchart = false;
let startX, startY;
let translateX = 0, translateY = 0;

const flowchartContainer = document.getElementById('flowchartContainer');
const flowchartImage = document.getElementById('flowchartImage');

function updateFlowchartTransform() {
    if (!flowchartImage) return;
    flowchartImage.style.transform = `translate(${translateX}px, ${translateY}px) scale(${flowchartScale})`;
}

function zoomFlowchart(delta) {
    flowchartScale += delta;
    if (flowchartScale < 0.2) flowchartScale = 0.2;
    if (flowchartScale > 5) flowchartScale = 5;
    updateFlowchartTransform();
}

function resetZoomFlowchart() {
    flowchartScale = 1;
    translateX = 0;
    translateY = 0;
    updateFlowchartTransform();
}

if (flowchartContainer) {
    flowchartContainer.addEventListener('wheel', (e) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        zoomFlowchart(delta);
    });

    flowchartContainer.addEventListener('mousedown', (e) => {
        isDraggingFlowchart = true;
        flowchartContainer.style.cursor = 'grabbing';
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
    });

    flowchartContainer.addEventListener('mousemove', (e) => {
        if (!isDraggingFlowchart) return;
        e.preventDefault();
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateFlowchartTransform();
    });

    flowchartContainer.addEventListener('mouseup', () => {
        isDraggingFlowchart = false;
        flowchartContainer.style.cursor = 'grab';
    });

    flowchartContainer.addEventListener('mouseleave', () => {
        isDraggingFlowchart = false;
        flowchartContainer.style.cursor = 'grab';
    });
    
    // Reset saat modal ditutup
    const modalFlowchart = document.getElementById('modalFlowchart');
    if (modalFlowchart) {
        modalFlowchart.addEventListener('hidden.bs.modal', () => {
            resetZoomFlowchart();
        });
    }
}

async function handleLoginSubmit(event) {
    event.preventDefault();
    
    const identity = document.getElementById('identity').value.trim();
    const password = document.getElementById('password').value.trim();
    const alertBox = document.getElementById('loginAlert');
    const alertText = document.getElementById('loginAlertText');
    const btnSubmit = document.getElementById('btnSubmit');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    
    alertBox.classList.add('d-none');
    
    if (!identity || !password) {
        alertText.textContent = 'Harap isi email/username dan password.';
        alertBox.classList.remove('d-none');
        return;
    }
    
    // Loading UI state
    btnSubmit.disabled = true;
    btnSpinner.classList.remove('d-none');
    btnText.textContent = 'Memverifikasi...';
    
    try {
        const response = await fetch(BASE_URL + '/api/auth/login.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: identity,
                password: password
            })
        });
        
        const res = await response.json();
        
        if (res.success) {
            btnText.textContent = 'Berhasil! Mengalihkan...';
            // Bersihkan sisa tab workspace dari user sebelumnya
            sessionStorage.removeItem('jt_workspace_tabs');
            localStorage.removeItem('jt_workspace_tabs');
            sessionStorage.removeItem('jt_sidebar_scroll_top');
            window.location.href = res.data.redirect_url || (BASE_URL + '/admin/dashboard.php');
        } else {
            alertText.textContent = res.message || 'Login gagal. Periksa kembali kredensial Anda.';
            alertBox.classList.remove('d-none');
            btnSubmit.disabled = false;
            btnSpinner.classList.add('d-none');
            btnText.textContent = 'Masuk ke Sistem';
        }
    } catch (error) {
        alertText.textContent = 'Terjadi kesalahan koneksi saat menghubungi server.';
        alertBox.classList.remove('d-none');
        btnSubmit.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.textContent = 'Masuk ke Sistem';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1" aria-labelledby="modalResetPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formResetPassword" onsubmit="handleResetPasswordSubmit(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalResetPasswordLabel">Reset Password Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Silakan masukkan Email terdaftar dan Tanggal Lahir Anda. Kami akan mengirimkan password sementara ke email tersebut.</p>
                    <div class="mb-3">
                        <label for="resetEmail" class="form-label small fw-semibold">Email Karyawan <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="resetEmail" name="email" required placeholder="Masukkan email terdaftar">
                    </div>
                    <div class="mb-3">
                        <label for="resetTanggalLahir" class="form-label small fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="resetTanggalLahir" name="tanggal_lahir" required>
                    </div>
                    <div class="alert alert-danger d-none py-2 small" id="resetAlertBox">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="resetAlertText"></span>
                    </div>
                    <div class="alert alert-success d-none py-2 small" id="resetSuccessBox">
                        <i class="bi bi-check-circle-fill me-1"></i> <span id="resetSuccessText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnResetSubmit">
                        <span id="btnResetSpinner" class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                        <span id="btnResetText">Kirim Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
async function handleResetPasswordSubmit(event) {
    event.preventDefault();
    
    const email = document.getElementById('resetEmail').value.trim();
    const tglLahir = document.getElementById('resetTanggalLahir').value.trim();
    
    const alertBox = document.getElementById('resetAlertBox');
    const alertText = document.getElementById('resetAlertText');
    const successBox = document.getElementById('resetSuccessBox');
    const successText = document.getElementById('resetSuccessText');
    
    const btnSubmit = document.getElementById('btnResetSubmit');
    const btnSpinner = document.getElementById('btnResetSpinner');
    const btnText = document.getElementById('btnResetText');
    
    alertBox.classList.add('d-none');
    successBox.classList.add('d-none');
    
    if (!email || !tglLahir) {
        alertText.textContent = 'Harap lengkapi semua isian.';
        alertBox.classList.remove('d-none');
        return;
    }
    
    btnSubmit.disabled = true;
    btnSpinner.classList.remove('d-none');
    btnText.textContent = 'Memproses...';
    
    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('tanggal_lahir', tglLahir);
        
        const response = await fetch(BASE_URL + '/api/auth/reset_password.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            successText.textContent = result.message || 'Password berhasil dikirim ke email Anda.';
            successBox.classList.remove('d-none');
            // Bersihkan form
            document.getElementById('formResetPassword').reset();
            // Optional: tutup modal setelah beberapa detik
            setTimeout(() => {
                const modalEl = document.getElementById('modalResetPassword');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                successBox.classList.add('d-none');
            }, 3500);
        } else {
            alertText.textContent = result.message || 'Gagal mengirim password.';
            alertBox.classList.remove('d-none');
        }
    } catch (error) {
        alertText.textContent = 'Terjadi kesalahan koneksi jaringan.';
        alertBox.classList.remove('d-none');
    } finally {
        btnSubmit.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.textContent = 'Kirim Password';
    }
}
</script>

</body>
</html>
