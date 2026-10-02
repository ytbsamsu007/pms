<?php
/**
 * Master Data Vendor - PT Jaya Teknik
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Master Vendor';
$pageHeading = 'Master Data Vendor & Supplier';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Daftar Vendor</h2>
    </div>
    <!-- Search di kiri, Tombol Tambah di paling kanan -->
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari vendor / kota / kontak..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahVendorModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Vendor
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode</th>
                    <th>Nama Perusahaan</th>
                    <th class="text-center" style="width: 90px;">C.Person</th>
                    <th>Kota</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="vendorTableBody">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data vendor...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Pagination Footer (50 Baku) -->
    <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top">
        <div class="text-muted small" id="paginationInfo">
            Menampilkan data...
        </div>
        <nav aria-label="Navigasi Halaman">
            <ul class="pagination pagination-sm mb-0" id="paginationControls">
            </ul>
        </nav>
    </div>
</div>

<!-- Modal Form Tambah / Edit Vendor dengan 4 Tab Terfokus (2-Kolom) -->
<div class="modal fade" id="vendorFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="vendorForm" onsubmit="handleSaveVendor(event)">
                <input type="hidden" id="formIdVendor" name="id_vendor">
                
                <!-- MODAL HEADER DENGAN NAV TABS TERPADU (SERAGAM DENGAN RO / MODUL LAIN) -->
                <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="vendorFormModalTitle">
                            <i class="bi bi-buildings-fill text-primary"></i> Tambah Vendor Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <!-- Nav Tabs Modal Form (4 Tab Rapi & Seragam) -->
                    <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="vendorFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark small py-2 px-3" id="vform-tab-1" data-bs-toggle="tab" data-bs-target="#vform-pane-1" type="button" role="tab">
                                <i class="bi bi-building me-1 text-primary"></i> 1. Utama
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="vform-tab-2" data-bs-toggle="tab" data-bs-target="#vform-pane-2" type="button" role="tab">
                                <i class="bi bi-telephone me-1 text-primary"></i> 2. Kontak
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="vform-tab-3" data-bs-toggle="tab" data-bs-target="#vform-pane-3" type="button" role="tab">
                                <i class="bi bi-geo-alt me-1 text-primary"></i> 3. Lokasi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="vform-tab-4" data-bs-toggle="tab" data-bs-target="#vform-pane-4" type="button" role="tab">
                                <i class="bi bi-cash-coin me-1 text-primary"></i> 4. Keuangan
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="vendorFormTabContent">
                        
                        <!-- TAB 1: UTAMA (2 KOLOM) -->
                        <div class="tab-pane fade show active" id="vform-pane-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kode Vendor</label>
                                    <input type="text" class="form-control" id="formKodeVendor" placeholder="Otomatis jika kosong">
                                    <div class="form-text small">Biarkan kosong untuk penomoran otomatis format <code>VND001</code>.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Perusahaan / Supplier <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formNamaPerusahaan" required placeholder="Contoh: PT Baja Maritim Nusantara">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jenis Vendor</label>
                                    <input type="text" class="form-control" id="formJenisVendor" placeholder="Contoh: Supplier Plat Baja / Kawat Las">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Status Keaktifan</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="vendorAktifYes" value="1" checked>
                                        <label class="btn btn-outline-success btn-sm" for="vendorAktifYes">Aktif</label>
                                        
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="vendorAktifNo" value="0">
                                        <label class="btn btn-outline-danger btn-sm" for="vendorAktifNo">Non-aktif</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: KONTAK (2 KOLOM) -->
                        <div class="tab-pane fade" id="vform-pane-2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor Telepon Kantor</label>
                                    <input type="text" class="form-control" id="formNoTelepon" placeholder="Contoh: 031-778811">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Alamat Email Resmi</label>
                                    <input type="email" class="form-control" id="formEmail" placeholder="sales@vendor.com">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Website Perusahaan</label>
                                    <input type="text" class="form-control" id="formWebsite" placeholder="https://www.vendor.com">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Contact Person (PIC)</label>
                                    <input type="text" class="form-control" id="formPerson" placeholder="Nama marketing / sales">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor HP / Jabatan PIC</label>
                                    <input type="text" class="form-control" id="formKontakPerson" placeholder="08123456789 (Sales Manager)">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: LOKASI (2 KOLOM) -->
                        <div class="tab-pane fade" id="vform-pane-3" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Kota Domisili</label>
                                    <input type="text" class="form-control" id="formKota" placeholder="Contoh: Surabaya / Samarinda">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Alamat Fisik Lengkap</label>
                                    <textarea class="form-control" id="formAlamat" rows="4" placeholder="Jalan, nomor gedung, kawasan industri"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Koordinat GPS / Link Google Maps</label>
                                    <textarea class="form-control" id="formGpsAlamat" rows="4" placeholder="-7.2000, 112.7300 atau link Google Maps"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: KEUANGAN (2 KOLOM) -->
                        <div class="tab-pane fade" id="vform-pane-4" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Bank Pembayaran</label>
                                    <input type="text" class="form-control" id="formNamaBank" placeholder="Contoh: Bank BCA / Mandiri / BRI">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor Rekening</label>
                                    <input type="text" class="form-control" id="formNomorRekening" placeholder="Nomor rekening transfer">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Term of Payment (TOP / Tempo Hari)</label>
                                    <input type="number" class="form-control" id="formTop" value="30" min="0" placeholder="Jumlah hari (contoh: 30)">
                                    <div class="form-text small">Jatuh tempo pembayaran dalam satuan hari (0 = Tunai/COD).</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Keterangan Tambahan</label>
                                    <textarea class="form-control" id="formKeterangan" rows="2" placeholder="Catatan syarat vendor, diskon, dll"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveVendor" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Vendor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lengkap Vendor dengan 4 Tab Terfokus (2-Kolom) -->
<div class="modal fade" id="vendorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- MODAL HEADER DENGAN NAV TABS TERPADU (SERAGAM DENGAN RO / MODUL LAIN) -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-building-check text-primary fs-5"></i>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="vendorDetailModalTitle">
                            Rincian Data Vendor
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Nav Tabs Modal Detail (4 Tab Rapi & Seragam) -->
                <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="vendorDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="vdetail-tab-1" data-bs-toggle="tab" data-bs-target="#vdetail-pane-1" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 1. Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="vdetail-tab-2" data-bs-toggle="tab" data-bs-target="#vdetail-pane-2" type="button" role="tab">
                            <i class="bi bi-telephone me-1 text-primary"></i> 2. Kontak
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="vdetail-tab-3" data-bs-toggle="tab" data-bs-target="#vdetail-pane-3" type="button" role="tab">
                            <i class="bi bi-geo-alt me-1 text-primary"></i> 3. Lokasi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="vdetail-tab-4" data-bs-toggle="tab" data-bs-target="#vdetail-pane-4" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1 text-primary"></i> 4. Keuangan
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="vendorDetailTabContent">
                    
                    <!-- TAB 1: UTAMA (2 KOLOM) -->
                    <div class="tab-pane fade show active" id="vdetail-pane-1" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kode Vendor</label>
                                <div class="fw-semibold font-monospace text-primary fs-6" id="modalKodeVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Status Keaktifan</label>
                                <div id="modalAktif">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nama Perusahaan</label>
                                <div class="fw-bold text-dark fs-5" id="modalNamaPerusahaan">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jenis Vendor</label>
                                <div id="modalJenisVendor">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: KONTAK (2 KOLOM) -->
                    <div class="tab-pane fade" id="vdetail-pane-2" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nomor Telepon Kantor</label>
                                <div class="text-dark fw-semibold" id="modalNoTelepon">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Email Resmi</label>
                                <div class="text-dark" id="modalEmail">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Website Perusahaan</label>
                                <div id="modalWebsite">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Contact Person (PIC)</label>
                                <div class="fw-bold text-dark" id="modalPerson">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kontak / Jabatan PIC</label>
                                <div class="text-muted" id="modalKontakPerson">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: LOKASI (2 KOLOM) -->
                    <div class="tab-pane fade" id="vdetail-pane-3" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kota Domisili</label>
                                <div class="fw-semibold text-dark fs-6" id="modalKota">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Alamat Fisik Lengkap</label>
                                <div class="p-3 bg-light rounded border small" id="modalAlamat">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Koordinat GPS / Map</label>
                                <div class="p-3 bg-light rounded border font-monospace small text-muted" id="modalGpsAlamat">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: KEUANGAN (2 KOLOM) -->
                    <div class="tab-pane fade" id="vdetail-pane-4" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nama Bank Pembayaran</label>
                                <div class="fw-bold text-dark" id="modalNamaBank">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nomor Rekening</label>
                                <div class="font-monospace text-primary fw-bold fs-6" id="modalNomorRekening">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Term of Payment (Tempo Pembayaran)</label>
                                <div id="modalTop">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Saldo Hutang Terakhir</label>
                                <div class="fw-bold text-danger" id="modalSaldoHutang">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Keterangan / Catatan Tambahan</label>
                                <div class="p-2 bg-light rounded border small text-muted" id="modalKeterangan">-</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const fixedLimit = 50;
let searchTimeout = null;
let vendorDataStore = [];

function formatWaNumber(raw) {
    if (!raw) return '';
    let digits = String(raw).replace(/\D/g, '');
    if (!digits) return '';
    if (digits.startsWith('08')) {
        digits = '628' + digits.substring(2);
    } else if (digits.startsWith('0')) {
        digits = '62' + digits.substring(1);
    } else if (digits.startsWith('8')) {
        digits = '628' + digits.substring(1);
    }
    return digits;
}

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadVendor();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadVendor();
}

async function loadVendor() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('vendorTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data vendor...</td></tr>`;
    
    const url = `/api/master/vendor.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        vendorDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (vendorDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data vendor ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        vendorDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            const rawContact = item.kontak_person || item.no_telepon || '';
            const waNum = formatWaNumber(rawContact);
            const contactTooltip = (item.person ? `${item.person} - ` : '') + (item.kontak_person || item.no_telepon || 'WhatsApp');

            let waButtonHtml = '<span class="text-muted small">-</span>';
            if (waNum && waNum.length >= 8) {
                waButtonHtml = `
                    <a href="https://wa.me/${waNum}" target="_blank" rel="noopener noreferrer" 
                       class="btn btn-sm btn-light text-success border border-success-subtle shadow-sm rounded-circle d-inline-flex align-items-center justify-content-center" 
                       style="width: 32px; height: 32px;" 
                       title="Hubungi WhatsApp: ${contactTooltip.replace(/"/g, '&quot;')}">
                        <i class="bi bi-whatsapp fs-6"></i>
                    </a>
                `;
            }

            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_vendor || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_perusahaan}</td>
                    <td class="text-center">${waButtonHtml}</td>
                    <td><span class="badge bg-secondary-subtle text-secondary">${item.kota || '-'}</span></td>
                    <td><span class="badge bg-info-subtle text-info">${item.jenis_vendor || 'Umum'}</span></td>
                    <td>
                        <span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${item.aktif ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="showVendorDetail(${idx})" title="Lihat Rincian">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditVendorModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteVendor(${item.id_vendor}, '${item.kode_vendor}', '${item.nama_perusahaan.replace(/'/g, "\\'")}')" title="Hapus">
                                <i class="bi bi-x-octagon-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        paginationInfo.textContent = `Menampilkan ${pag.from} - ${pag.to} dari ${pag.total_records} data (Total: ${pag.total_pages} Halaman)`;
        renderPagination(pag);
    } else {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Gagal memuat data vendor.</td></tr>`;
    }
}

function renderPagination(pag) {
    const controls = document.getElementById('paginationControls');
    let html = '';
    
    html += `
        <li class="page-item ${pag.page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page - 1})">&laquo; Prev</a>
        </li>
    `;
    
    const startPage = Math.max(1, pag.page - 2);
    const endPage = Math.min(pag.total_pages, pag.page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === pag.page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${p})">${p}</a>
            </li>
        `;
    }
    
    if (endPage < pag.total_pages) {
        if (endPage < pag.total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.total_pages})">${pag.total_pages}</a></li>`;
    }
    
    html += `
        <li class="page-item ${pag.page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page + 1})">Next &raquo;</a>
        </li>
    `;
    
    controls.innerHTML = html;
}

function openTambahVendorModal() {
    document.getElementById('vendorForm').reset();
    document.getElementById('formIdVendor').value = '';
    document.getElementById('vendorAktifYes').checked = true;
    document.getElementById('formTop').value = '30';
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('vform-tab-1')).show();
    document.getElementById('vendorFormModalTitle').innerHTML = '<i class="bi bi-buildings-fill me-2"></i>Tambah Vendor Baru';
    const modal = new bootstrap.Modal(document.getElementById('vendorFormModal'));
    modal.show();
}

function openEditVendorModal(idx) {
    const item = vendorDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdVendor').value = item.id_vendor;
    document.getElementById('formKodeVendor').value = item.kode_vendor;
    document.getElementById('formNamaPerusahaan').value = item.nama_perusahaan;
    document.getElementById('formJenisVendor').value = item.jenis_vendor !== '-' ? item.jenis_vendor : '';
    
    if (item.aktif === 1) {
        document.getElementById('vendorAktifYes').checked = true;
    } else {
        document.getElementById('vendorAktifNo').checked = true;
    }

    document.getElementById('formNoTelepon').value = item.no_telepon !== '-' ? item.no_telepon : '';
    document.getElementById('formEmail').value = item.email !== '-' ? item.email : '';
    document.getElementById('formWebsite').value = item.website !== '-' ? item.website : '';
    document.getElementById('formPerson').value = item.person !== '-' ? item.person : '';
    document.getElementById('formKontakPerson').value = item.kontak_person !== '-' ? item.kontak_person : '';

    document.getElementById('formKota').value = item.kota !== '-' ? item.kota : '';
    document.getElementById('formAlamat').value = item.alamat !== '-' ? item.alamat : '';
    document.getElementById('formGpsAlamat').value = item.gps_alamat !== '-' ? item.gps_alamat : '';

    document.getElementById('formNamaBank').value = item.nama_bank !== '-' ? item.nama_bank : '';
    document.getElementById('formNomorRekening').value = item.nomor_rekening !== '-' ? item.nomor_rekening : '';
    document.getElementById('formTop').value = item.term_of_payment || 30;
    document.getElementById('formKeterangan').value = item.keterangan !== '-' ? item.keterangan : '';

    bootstrap.Tab.getOrCreateInstance(document.getElementById('vform-tab-1')).show();
    document.getElementById('vendorFormModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Data Vendor';
    const modal = new bootstrap.Modal(document.getElementById('vendorFormModal'));
    modal.show();
}

async function handleSaveVendor(e) {
    e.preventDefault();
    const id = document.getElementById('formIdVendor').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveVendor');
    
    const namaPerusahaan = document.getElementById('formNamaPerusahaan').value.trim();
    if (!namaPerusahaan) {
        showToast('Nama perusahaan vendor wajib diisi.', 'error');
        return;
    }

    const aktifVal = document.querySelector('input[name="formAktifRadio"]:checked')?.value || '1';

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const payload = {
        id_vendor: id,
        kode_vendor: document.getElementById('formKodeVendor').value.trim(),
        nama_perusahaan: namaPerusahaan,
        jenis_vendor: document.getElementById('formJenisVendor').value.trim(),
        aktif: parseInt(aktifVal),
        no_telepon: document.getElementById('formNoTelepon').value.trim(),
        email: document.getElementById('formEmail').value.trim(),
        website: document.getElementById('formWebsite').value.trim(),
        person: document.getElementById('formPerson').value.trim(),
        kontak_person: document.getElementById('formKontakPerson').value.trim(),
        kota: document.getElementById('formKota').value.trim(),
        alamat: document.getElementById('formAlamat').value.trim(),
        gps_alamat: document.getElementById('formGpsAlamat').value.trim(),
        nama_bank: document.getElementById('formNamaBank').value.trim(),
        nomor_rekening: document.getElementById('formNomorRekening').value.trim(),
        term_of_payment: parseInt(document.getElementById('formTop').value || 30),
        keterangan: document.getElementById('formKeterangan').value.trim(),
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/vendor.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data Vendor';
    
    if (res && res.success) {
        showToast(res.message || 'Data vendor berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('vendorFormModal')).hide();
        loadVendor();
    } else {
        showToast(res.message || 'Gagal menyimpan data vendor.', 'error');
    }
}

async function deleteVendor(id, kode, name) {
    showDeleteConfirm(
        `Apakah Anda yakin ingin menghapus vendor <strong>${escapeHtml(kode)} - ${escapeHtml(name)}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.`,
        kode,
        async function() {
            const res = await apiRequest('/api/master/vendor.php', {
                method: 'POST',
                body: JSON.stringify({ id_vendor: id, _method: 'DELETE' })
            });
            
            if (res && res.success) {
                showToast('Vendor berhasil dihapus.', 'success');
                loadVendor();
            } else {
                showToast(res.message || 'Gagal menghapus vendor.', 'error');
            }
        }
    );
}

function showVendorDetail(idx) {
    const item = vendorDataStore[idx];
    if (!item) return;
    
    // Tab 1: Utama
    document.getElementById('modalKodeVendor').textContent = item.kode_vendor || '-';
    document.getElementById('modalNamaPerusahaan').textContent = item.nama_perusahaan;
    document.getElementById('modalJenisVendor').innerHTML = `<span class="badge bg-info-subtle text-info">${item.jenis_vendor}</span>`;
    document.getElementById('modalAktif').innerHTML = item.aktif ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Non-aktif</span>';
    
    // Tab 2: Kontak
    document.getElementById('modalNoTelepon').textContent = item.no_telepon || '-';
    document.getElementById('modalEmail').textContent = item.email || '-';
    document.getElementById('modalWebsite').innerHTML = item.website && item.website !== '-' ? `<a href="${item.website}" target="_blank" class="text-primary text-decoration-none">${item.website} <i class="bi bi-box-arrow-up-right small"></i></a>` : '-';
    document.getElementById('modalPerson').textContent = item.person || '-';
    
    const kpWa = formatWaNumber(item.kontak_person || item.no_telepon);
    if (kpWa && kpWa.length >= 8) {
        document.getElementById('modalKontakPerson').innerHTML = `
            <span>${item.kontak_person || item.no_telepon || '-'}</span>
            <a href="https://wa.me/${kpWa}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success text-white py-0 px-2 ms-2 rounded-2 small d-inline-flex align-items-center gap-1">
                <i class="bi bi-whatsapp"></i> Chat WA
            </a>
        `;
    } else {
        document.getElementById('modalKontakPerson').textContent = item.kontak_person || '-';
    }

    // Tab 3: Lokasi
    document.getElementById('modalKota').textContent = item.kota || '-';
    document.getElementById('modalAlamat').textContent = item.alamat || '-';
    document.getElementById('modalGpsAlamat').textContent = item.gps_alamat || '-';

    // Tab 4: Keuangan
    document.getElementById('modalNamaBank').textContent = item.nama_bank || '-';
    document.getElementById('modalNomorRekening').textContent = item.nomor_rekening || '-';
    document.getElementById('modalTop').innerHTML = `<span class="badge bg-primary-subtle text-primary">${item.term_of_payment} Hari Tempo</span>`;
    document.getElementById('modalSaldoHutang').textContent = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(item.saldo_hutang_terakhir || 0);
    document.getElementById('modalKeterangan').textContent = item.keterangan || '-';

    bootstrap.Tab.getOrCreateInstance(document.getElementById('vdetail-tab-1')).show();
    const modal = new bootstrap.Modal(document.getElementById('vendorDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadVendor();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
