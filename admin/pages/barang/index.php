<?php
/**
 * Master Data Barang - PT Jaya Teknik
 * Terintegrasi dengan Stok per Site (barang_stok) & Harga per Vendor (barang_hargavendor)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Barang';
$pageHeading = 'Master Data Barang & Jasa';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-0">Katalog Master Barang &amp; Jasa</h2>
    </div>
    <!-- Action Controls: Search di kiri, Tombol Tambah di paling kanan -->
    <div class="d-flex gap-2 align-items-stretch flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Cari nama / kode / serial..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm d-inline-flex align-items-center" onclick="openTambahBarangModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Barang
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nama Barang</th>
                    <th>Total KTS</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="barangTableBody">
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data barang...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Pagination Footer -->
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

<!-- Modal Form Tambah / Edit Barang dengan Tab Rapi (2 Kolom) -->
<div class="modal fade" id="barangFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <form id="barangForm" onsubmit="handleSaveBarang(event)">
                <input type="hidden" id="formIdBarang" name="id_barang">
                <input type="hidden" id="formFoto1Val" name="foto1">
                <input type="hidden" id="formFoto2Val" name="foto2">
                <input type="hidden" id="formKategoriIdVal" name="id_kategori">
                <input type="hidden" id="formMerkIdVal" name="id_merk">
                <input type="hidden" id="formVendorIdVal" name="default_id_vendor">
                
                <!-- Modal Header dengan Nav Tabs Terpadu -->
                <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="barangFormModalTitle">
                             Tambah Barang Baru
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="barangFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-dark small py-2 px-3" id="bform-tab-utama" data-bs-toggle="tab" data-bs-target="#bform-pane-utama" type="button" role="tab">
                                <i class="bi bi-tag-fill me-1 text-primary"></i> 1. Utama
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="bform-tab-tambahan" data-bs-toggle="tab" data-bs-target="#bform-pane-tambahan" type="button" role="tab">
                                <i class="bi bi-sliders me-1 text-primary"></i> 2. Spesifikasi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="bform-tab-stok" data-bs-toggle="tab" data-bs-target="#bform-pane-stok" type="button" role="tab">
                                <i class="bi bi-boxes me-1 text-primary"></i> 3. Stok
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="bform-tab-harga" data-bs-toggle="tab" data-bs-target="#bform-pane-harga" type="button" role="tab">
                                <i class="bi bi-cash-coin me-1 text-primary"></i> 4. Vendor
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-dark small py-2 px-3" id="bform-tab-foto" data-bs-toggle="tab" data-bs-target="#bform-pane-foto" type="button" role="tab">
                                <i class="bi bi-images me-1 text-primary"></i> 5. Foto
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="barangFormTabContent">
                        
                        <!-- TAB 1: UTAMA (2 KOLOM) -->
                        <div class="tab-pane fade show active" id="bform-pane-utama" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kode Barang</label>
                                    <input type="text" class="form-control" id="formKodeBarang" placeholder="Otomatis jika kosong">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Barang / Jasa <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formNamaBarang" required placeholder="Contoh: Plat Baja Marine AH36">
                                </div>
                                
                                <!-- Searchable Kategori -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kategori Barang <span class="text-danger">*</span></label>
                                    <div class="searchable-select-wrapper" id="kategoriSearchWrapper">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="kategoriSearchInput" required placeholder="Pilih / cari kategori..." autocomplete="off" onfocus="openKategoriDropdown()" oninput="debounceKategoriSearch()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearKategoriSelection()" title="Hapus">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="searchable-select-menu d-none" id="kategoriDropdownMenu">
                                            <div id="kategoriDropdownList">
                                                <div class="p-2 text-center text-muted small">Memuat kategori...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Searchable Merk -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Merk / Brand</label>
                                    <div class="searchable-select-wrapper" id="merkSearchWrapper">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="merkSearchInput" placeholder="Pilih / cari merk..." autocomplete="off" onfocus="openMerkDropdown()" oninput="debounceMerkSearch()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearMerkSelection()" title="Hapus">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="searchable-select-menu d-none" id="merkDropdownMenu">
                                            <div id="merkDropdownList">
                                                <div class="p-2 text-center text-muted small">Memuat merk...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Satuan <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formSatuanRadio" id="satuanPCS" value="PCS" checked onchange="updateStokSatuanLabels()">
                                        <label class="btn btn-outline-secondary btn-sm" for="satuanPCS">PCS</label>
                                        
                                        <input type="radio" class="btn-check" name="formSatuanRadio" id="satuanUNIT" value="UNIT" onchange="updateStokSatuanLabels()">
                                        <label class="btn btn-outline-secondary btn-sm" for="satuanUNIT">UNIT</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Jenis Barang <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formJenisRadio" id="jenisPersediaan" value="1" checked>
                                        <label class="btn btn-outline-primary btn-sm" for="jenisPersediaan">Persediaan</label>
                                        
                                        <input type="radio" class="btn-check" name="formJenisRadio" id="jenisJasa" value="0">
                                        <label class="btn btn-outline-primary btn-sm" for="jenisJasa">Jasa</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: VENDOR (2 KOLOM) -->
                        <div class="tab-pane fade" id="bform-pane-vendor" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Default Vendor</label>
                                    <div class="searchable-select-wrapper" id="vendorSearchWrapper">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control" id="vendorSearchInput" placeholder="Ketik nama / kode vendor..." autocomplete="off" onfocus="openVendorDropdown()" oninput="debounceVendorSearch()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearVendorSelection()" title="Hapus Pilihan">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="searchable-select-menu d-none" id="vendorDropdownMenu">
                                            <div id="vendorDropdownList">
                                                <div class="p-2 text-center text-muted small">Memuat vendor...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: STOK PER SITE -->
                        <div class="tab-pane fade" id="bform-pane-stok" role="tabpanel">
                            <div class="mb-3">
                                <span class="fw-bold small text-dark">Alokasi Stok Awal per Site</span>
                            </div>
                            <div class="table-responsive border rounded bg-white">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 45px;" class="text-center">No</th>
                                            <th>Site</th>
                                            <th>Jenis</th>
                                            <th style="width: 180px;">Stok (<span class="stokSatuanLabel">PCS</span>)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stokRowsContainer">
                                        <tr><td colspan="4" class="text-center py-3 text-muted small">Memuat daftar site...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 4: HARGA VENDOR -->
                        <div class="tab-pane fade" id="bform-pane-harga" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="fw-bold small text-dark">Daftar Harga per Vendor</span>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" onclick="addHargaRow()">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Harga
                                </button>
                            </div>
                            
                            <!-- Placeholder jika belum ada data harga -->
                            <div id="emptyHargaNotice" class="text-center py-4 border rounded bg-light text-muted small">
                                <i class="bi bi-cash-stack fs-4 d-block mb-1 text-secondary"></i>
                                Belum ada daftar harga vendor. Klik tombol <strong>"Tambah Harga"</strong> di atas untuk menambahkan.
                            </div>

                            <!-- Table Wrapper (sembunyi jika kosong) -->
                            <div class="table-responsive border rounded bg-white d-none" id="tableHargaWrapper" style="overflow: visible;">
                                <table class="table table-sm table-hover align-middle mb-0" id="tableHargaForm">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 45px;" class="text-center">No</th>
                                            <th style="min-width: 250px;">Nama Vendor</th>
                                            <th style="width: 170px;">Berlaku</th>
                                            <th style="width: 210px;">Harga Satuan (Rp)</th>
                                            <th class="text-center" style="width: 50px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hargaRowsContainer">
                                        <!-- Dynamic Harga Rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 5: FOTO (2 KOLOM) -->
                        <div class="tab-pane fade" id="bform-pane-foto" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Foto Produk 1 (Utama)</label>
                                    <input type="file" class="form-control form-control-sm mb-2" id="formFile1" accept="image/*" onchange="handleLocalFileSelect(this, 1)">
                                    <div class="border rounded bg-light p-2 text-center position-relative" style="height: 140px; display: flex; align-items: center; justify-content: center;">
                                        <img id="previewFoto1" src="" class="img-fluid rounded d-none" style="max-height: 120px; object-fit: contain;">
                                        <div id="placeholderFoto1" class="text-muted small"><i class="bi bi-image me-1"></i>Belum ada Foto 1</div>
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-1 d-none" id="btnRemoveFoto1" onclick="removeProductImage(1)" title="Hapus Foto 1">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Foto Produk 2 (Detail)</label>
                                    <input type="file" class="form-control form-control-sm mb-2" id="formFile2" accept="image/*" onchange="handleLocalFileSelect(this, 2)">
                                    <div class="border rounded bg-light p-2 text-center position-relative" style="height: 140px; display: flex; align-items: center; justify-content: center;">
                                        <img id="previewFoto2" src="" class="img-fluid rounded d-none" style="max-height: 120px; object-fit: contain;">
                                        <div id="placeholderFoto2" class="text-muted small"><i class="bi bi-image me-1"></i>Belum ada Foto 2</div>
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-1 d-none" id="btnRemoveFoto2" onclick="removeProductImage(2)" title="Hapus Foto 2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 6: TAMBAHAN (2 KOLOM) -->
                        <div class="tab-pane fade" id="bform-pane-tambahan" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Klasifikasi Asset</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAssetRadio" id="assetNon" value="0" checked>
                                        <label class="btn btn-outline-secondary btn-sm" for="assetNon">Bukan Asset</label>
                                        
                                        <input type="radio" class="btn-check" name="formAssetRadio" id="assetYes" value="1">
                                        <label class="btn btn-outline-primary btn-sm" for="assetYes">Asset</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Status Keaktifan</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="barangAktifYes" value="1" checked>
                                        <label class="btn btn-outline-success btn-sm" for="barangAktifYes">Aktif</label>
                                        
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="barangAktifNo" value="0">
                                        <label class="btn btn-outline-danger btn-sm" for="barangAktifNo">Non-aktif</label>
                                    </div>
                                </div>

                                <!-- Klasifikasi Pajak PPnBM & Rate -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Klasifikasi PPnBM</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formPpnbmRadio" id="ppnbmNo" value="0" checked onchange="togglePpnbmRateInput()">
                                        <label class="btn btn-outline-secondary btn-sm" for="ppnbmNo">Non PPnBM</label>
                                        
                                        <input type="radio" class="btn-check" name="formPpnbmRadio" id="ppnbmYes" value="1" onchange="togglePpnbmRateInput()">
                                        <label class="btn btn-outline-warning btn-sm" for="ppnbmYes">Kena PPnBM</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tarif / Rate PPnBM (%)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0" max="100" class="form-control" id="formRatePpnbm" placeholder="0" value="0">
                                        <span class="input-group-text bg-light">%</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Serial Number / Part Number</label>
                                    <input type="text" class="form-control" id="formSerialNumber" placeholder="Contoh: SN-8890283-A">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Pembuatan Data</label>
                                    <input type="text" class="form-control bg-light" id="formCreatedAtDisplay" readonly value="<?= date('d-m-Y H:i') ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold">Deskripsi &amp; Spesifikasi Teknis</label>
                                    <textarea class="form-control" id="formDeskripsi" rows="3" placeholder="Rincian dimensi, grade standar, sertifikasi, dll"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    
                    <button type="submit" id="btnSaveBarang" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lengkap Barang (5 Tab Terfokus) -->
<!-- Modal Detail Barang dengan Gaya Pop-Up Modern Sama Seperti Request Order -->
<div class="modal fade" id="barangDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- Header Modal Modern dengan Status & Close Button di Kanan Atas -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam-fill text-primary fs-5"></i>
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalHeaderKodeBarang">
                            BRG-XXXX
                        </h5>
                    </div>
                    <!-- Status & Jenis di sebelah tombol X -->
                    <div class="d-flex align-items-center gap-2">
                        <span id="modalHeaderStatusBadge">-</span>
                        <span id="modalHeaderJenisBadge">-</span>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Nav Tabs Modal Detail -->
                <ul class="nav nav-tabs border-bottom-0" id="barangDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="bdetail-tab-utama" data-bs-toggle="tab" data-bs-target="#bdetail-pane-utama" type="button" role="tab">
                            <i class="bi bi-tag-fill me-1 text-primary"></i> Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="bdetail-tab-tambahan" data-bs-toggle="tab" data-bs-target="#bdetail-pane-tambahan" type="button" role="tab">
                            <i class="bi bi-sliders me-1 text-primary"></i> Speksifikasi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="bdetail-tab-stok" data-bs-toggle="tab" data-bs-target="#bdetail-pane-stok" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> Stok
                            <span class="badge bg-primary text-white ms-1" id="modalTabStokBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="bdetail-tab-harga" data-bs-toggle="tab" data-bs-target="#bdetail-pane-harga" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1 text-primary"></i> Vendor
                            <span class="badge bg-secondary text-white ms-1" id="modalTabHargaBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="bdetail-tab-foto" data-bs-toggle="tab" data-bs-target="#bdetail-pane-foto" type="button" role="tab">
                            <i class="bi bi-images me-1 text-primary"></i> Foto
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="barangDetailTabContent">
                    
                    <!-- TAB 1: INFORMASI UTAMA -->
                    <div class="tab-pane fade show active" id="bdetail-pane-utama" role="tabpanel">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Kode Barang:</span>
                                        <strong class="text-primary font-monospace fs-6" id="modalKodeBarang">-</strong>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Nama Barang / Material:</span>
                                        <strong class="text-dark fs-6 d-block" id="modalNamaBarang">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Satuan Pengadaan:</span>
                                        <span id="modalSatuanBarang">-</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Kategori:</span>
                                        <strong class="text-dark" id="modalKategoriBarang">-</strong>
                                    </div>
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Merk / Brand:</span>
                                        <strong class="text-dark" id="modalMerkBarang">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Default Vendor:</span>
                                        <strong class="text-dark" id="modalDefaultVendor">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: STOK SITE DETAIL -->
                    <div class="tab-pane fade" id="bdetail-pane-stok" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small">Rincian Persediaan Stok per Site</span>
                            <span class="badge bg-primary" id="modalTotalStokBadge">Total: 0 PCS</span>
                        </div>
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th>Lokasi Site / Workshop</th>
                                        <th>Jenis Site</th>
                                        <th class="text-end">Jumlah Stok</th>
                                    </tr>
                                </thead>
                                <tbody id="modalStokSiteList">
                                    <!-- Dynamic Stok List -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: HARGA VENDOR DETAIL -->
                    <div class="tab-pane fade" id="bdetail-pane-harga" role="tabpanel">
                        <div class="mb-2">
                            <span class="fw-bold text-dark small">Daftar Referensi Harga per Vendor</span>
                        </div>
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th>Vendor</th>
                                        <th>Harga Satuan (Rp)</th>
                                        <th>Tanggal Berlaku</th>
                                    </tr>
                                </thead>
                                <tbody id="modalHargaVendorList">
                                    <!-- Dynamic Vendor Price List -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 4: FOTO -->
                    <div class="tab-pane fade" id="bdetail-pane-foto" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border rounded-3 p-3 bg-light text-center h-100">
                                    <span class="text-muted small fw-bold text-uppercase d-block mb-2">Foto 1 (Utama)</span>
                                    <div class="d-flex align-items-center justify-content-center" style="min-height: 180px;" id="modalFoto1Container">
                                        <span class="text-muted small">Tidak ada foto</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border rounded-3 p-3 bg-light text-center h-100">
                                    <span class="text-muted small fw-bold text-uppercase d-block mb-2">Foto 2 (Detail)</span>
                                    <div class="d-flex align-items-center justify-content-center" style="min-height: 180px;" id="modalFoto2Container">
                                        <span class="text-muted small">Tidak ada foto</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: SPESIFIKASI & TAMBAHAN -->
                    <div class="tab-pane fade" id="bdetail-pane-tambahan" role="tabpanel">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Klasifikasi Asset:</span>
                                    <div id="modalAssetBarang">-</div>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Klasifikasi Pajak (PPnBM):</span>
                                    <div id="modalPpnbmBarang">-</div>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Serial Number / Part No.:</span>
                                    <strong class="font-monospace text-dark" id="modalSerialBarang">-</strong>
                                </div>
                                <div class="col-md-6">
                                    <span class="text-muted small d-block">Tanggal Dibuat:</span>
                                    <span class="text-dark small font-monospace" id="modalCreatedAt">-</span>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted small d-block">Deskripsi &amp; Spesifikasi:</span>
                                    <div class="p-3 bg-white rounded border text-dark small mt-1" id="modalDeskripsiBarang">-</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const fixedLimit = 50; // Baku 50 per halaman
let searchTimeout = null;
let vendorSearchTimeout = null;
let kategoriSearchTimeout = null;
let merkSearchTimeout = null;

let barangDataStore = [];
let vendorListCache = [];
let kategoriListCache = [];
let merkListCache = [];
let siteListCache = [];

// Local file storage for deferred upload on form submit
let pendingFile1 = null;
let pendingFile2 = null;

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadBarang();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadBarang();
}

async function loadDependencies() {
    // 1. Kategori
    const resK = await apiRequest('/api/master/kategori.php?limit=100');
    if (resK && resK.success) kategoriListCache = resK.data.items || [];

    // 2. Merk
    const resM = await apiRequest('/api/master/merk.php?limit=100');
    if (resM && resM.success) merkListCache = resM.data.items || [];

    // 3. Vendor
    const resV = await apiRequest('/api/master/vendor.php?limit=100');
    if (resV && resV.success) vendorListCache = resV.data.items || [];

    // 4. Site (Hanya site yang berstatus tempat penyimpanan stok)
    const resS = await apiRequest('/api/master/site.php?penyimpanan_stok=1&limit=100');
    if (resS && resS.success) {
        siteListCache = resS.data.items || [];
        renderStokFormRows();
    }
}

function getSelectedSatuan() {
    return document.querySelector('input[name="formSatuanRadio"]:checked')?.value || 'PCS';
}

function updateStokSatuanLabels() {
    const currentSatuan = getSelectedSatuan();
    document.querySelectorAll('.stokSatuanLabel').forEach(el => el.textContent = currentSatuan);
    document.querySelectorAll('.stokSatuanSuffix').forEach(el => el.textContent = currentSatuan);
}

function renderStokFormRows(existingStokMap = {}) {
    const container = document.getElementById('stokRowsContainer');
    if (!container) return;
    if (siteListCache.length === 0) {
        container.innerHTML = '<tr><td colspan="4" class="text-center py-2 text-muted small">Tidak ada data site tersedia.</td></tr>';
        return;
    }

    const currentSatuan = getSelectedSatuan();
    document.querySelectorAll('.stokSatuanLabel').forEach(el => el.textContent = currentSatuan);

    let html = '';
    siteListCache.forEach((s, idx) => {
        const qty = existingStokMap[s.id_site] !== undefined ? existingStokMap[s.id_site] : 0;
        html += `
            <tr>
                <td class="text-center text-muted fw-bold">${idx + 1}</td>
                <td class="fw-semibold text-dark">
                    ${s.nama_site} 
                    <span class="badge bg-light text-muted border font-monospace ms-1">${s.kode_site || ''}</span>
                </td>
                <td><span class="badge bg-secondary-subtle text-secondary">${s.jenis_site || 'Site'}</span></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control site-stok-input" data-site-id="${s.id_site}" value="${qty}" min="0" placeholder="0">
                        <span class="input-group-text bg-light stokSatuanSuffix">${currentSatuan}</span>
                    </div>
                </td>
            </tr>
        `;
    });
    container.innerHTML = html;
}

function updateTableHargaVisibility() {
    const container = document.getElementById('hargaRowsContainer');
    const tableWrapper = document.getElementById('tableHargaWrapper');
    const emptyNotice = document.getElementById('emptyHargaNotice');
    
    if (container && tableWrapper && emptyNotice) {
        if (container.children.length > 0) {
            tableWrapper.classList.remove('d-none');
            emptyNotice.classList.add('d-none');
            reindexHargaRows();
        } else {
            tableWrapper.classList.add('d-none');
            emptyNotice.classList.remove('d-none');
        }
    }
}

function reindexHargaRows() {
    const container = document.getElementById('hargaRowsContainer');
    if (!container) return;
    const rows = container.querySelectorAll('.harga-vendor-row');
    rows.forEach((row, idx) => {
        const noCell = row.querySelector('.harga-row-num');
        if (noCell) noCell.textContent = idx + 1;
    });
}

function addHargaRow(idVendor = '', hargaSet = '', berlaku = '') {
    const container = document.getElementById('hargaRowsContainer');
    const rowId = 'hrow_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const today = new Date().toISOString().split('T')[0];

    let selectedVendorText = '-- Pilih Vendor --';
    if (idVendor) {
        const found = vendorListCache.find(v => String(v.id_vendor) === String(idVendor));
        if (found) {
            selectedVendorText = (found.kode_vendor ? `[${found.kode_vendor}] ` : '') + (found.nama_perusahaan || '');
        }
    }

    const initialPriceVal = hargaSet ? parseFloat(hargaSet) : '';
    const initialPriceDisplay = initialPriceVal ? formatRupiahDisplay(initialPriceVal) : '';

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'harga-vendor-row';
    tr.innerHTML = `
        <td class="text-center text-muted fw-bold harga-row-num" style="height: 38px; vertical-align: middle;">1</td>
        <td style="vertical-align: middle;">
            <div class="position-relative" id="wrapper_vendor_${rowId}">
                <input type="hidden" class="harga-vendor-id-val" value="${idVendor || ''}">
                <div class="form-control d-flex align-items-center justify-content-between bg-white cursor-pointer px-3" 
                     id="btn_vendor_${rowId}" 
                     onclick="toggleRowVendorDropdown('${rowId}', event)" 
                     style="height: 38px; cursor: pointer; user-select: none; border-radius: 6px;">
                    <span id="label_vendor_${rowId}" class="text-truncate text-dark small" style="max-width: calc(100% - 20px);" title="${escapeHtml(selectedVendorText)}">
                        ${escapeHtml(selectedVendorText)}
                    </span>
                    <i class="bi bi-chevron-down text-muted small ms-1" id="icon_vendor_${rowId}"></i>
                </div>
                <div class="dropdown-menu shadow border p-2 w-100" 
                     id="menu_vendor_${rowId}" 
                     style="display: none; position: absolute; top: 100%; left: 0; z-index: 1060; margin-top: 4px; max-height: 250px;">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="search_vendor_${rowId}" placeholder="Cari nama / kode vendor..." onkeyup="filterRowVendorList('${rowId}', this.value)" autocomplete="off">
                    </div>
                    <div class="overflow-auto" id="list_vendor_${rowId}" style="max-height: 180px;">
                        <!-- Vendor list populated by JS -->
                    </div>
                </div>
            </div>
        </td>
        <td style="vertical-align: middle;">
            <input type="date" class="form-control harga-vendor-date" value="${berlaku || today}" style="height: 38px;" required>
        </td>
        <td style="vertical-align: middle;">
            <div class="input-group" style="height: 38px;">
                <span class="input-group-text bg-light text-muted small" style="height: 38px;">Rp</span>
                <input type="text" class="form-control harga-vendor-price-display" value="${initialPriceDisplay}" placeholder="0" oninput="handlePriceDisplayInput(this)" style="height: 38px;" required>
                <input type="hidden" class="harga-vendor-price" value="${initialPriceVal}">
            </div>
        </td>
        <td class="text-center" style="vertical-align: middle;">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeHargaRow('${rowId}')" title="Hapus Baris" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    container.appendChild(tr);
    updateTableHargaVisibility();
}

function formatRupiahDisplay(val) {
    if (val === '' || isNaN(val)) return '';
    return new Intl.NumberFormat('id-ID').format(val);
}

function handlePriceDisplayInput(input) {
    // Ambil hanya angka
    let raw = input.value.replace(/\D/g, '');
    if (raw === '') {
        input.value = '';
        input.closest('td').querySelector('.harga-vendor-price').value = '';
        return;
    }
    const num = parseInt(raw, 10);
    input.value = formatRupiahDisplay(num);
    input.closest('td').querySelector('.harga-vendor-price').value = num;
}

function toggleRowVendorDropdown(rowId, e) {
    e.stopPropagation();
    const menu = document.getElementById(`menu_vendor_${rowId}`);
    const isOpen = menu.style.display === 'block';
    
    // Close any other open dropdowns first
    document.querySelectorAll('[id^="menu_vendor_"]').forEach(m => m.style.display = 'none');
    document.querySelectorAll('[id^="btn_vendor_"]').forEach(b => b.classList.remove('border-primary', 'shadow-sm'));

    if (!isOpen) {
        menu.style.display = 'block';
        document.getElementById(`btn_vendor_${rowId}`).classList.add('border-primary', 'shadow-sm');
        const sInput = document.getElementById(`search_vendor_${rowId}`);
        sInput.value = '';
        filterRowVendorList(rowId, '');
        setTimeout(() => sInput.focus(), 50);
    }
}

function filterRowVendorList(rowId, query) {
    const q = (query || '').toLowerCase().trim();
    const listContainer = document.getElementById(`list_vendor_${rowId}`);
    const currentVal = document.querySelector(`#wrapper_vendor_${rowId} .harga-vendor-id-val`).value;

    let html = `
        <div class="dropdown-item py-2 px-2 rounded-2 text-truncate cursor-pointer ${currentVal === '' ? 'active fw-bold' : ''}" 
             onclick="selectRowVendor('${rowId}', '', '-- Pilih Vendor --')" 
             style="cursor: pointer; font-size: 0.85rem;">
            -- Pilih Vendor --
        </div>
    `;

    let matchCount = 0;
    vendorListCache.forEach(v => {
        const text = (v.kode_vendor ? `[${v.kode_vendor}] ` : '') + (v.nama_perusahaan || '');
        if (!q || text.toLowerCase().includes(q)) {
            matchCount++;
            const isSel = String(v.id_vendor) === String(currentVal);
            html += `
                <div class="dropdown-item py-2 px-2 rounded-2 text-truncate cursor-pointer ${isSel ? 'active fw-bold' : ''}" 
                     onclick="selectRowVendor('${rowId}', '${v.id_vendor}', '${escapeHtml(text)}')" 
                     style="cursor: pointer; font-size: 0.85rem;" title="${escapeHtml(text)}">
                    ${escapeHtml(text)}
                </div>
            `;
        }
    });

    if (matchCount === 0 && q !== '') {
        html += `<div class="p-2 text-muted text-center small">Tidak ada vendor cocok.</div>`;
    }

    listContainer.innerHTML = html;
}

function selectRowVendor(rowId, id, label) {
    const wrapper = document.getElementById(`wrapper_vendor_${rowId}`);
    wrapper.querySelector('.harga-vendor-id-val').value = id;
    const labelEl = document.getElementById(`label_vendor_${rowId}`);
    labelEl.textContent = label;
    labelEl.title = label;
    document.getElementById(`menu_vendor_${rowId}`).style.display = 'none';
    document.getElementById(`btn_vendor_${rowId}`).classList.remove('border-primary', 'shadow-sm');
}

// Global click listener to close table row vendor dropdowns when clicking outside
document.addEventListener('click', function(e) {
    document.querySelectorAll('[id^="wrapper_vendor_"]').forEach(wrapper => {
        const menu = wrapper.querySelector('[id^="menu_vendor_"]');
        const btn = wrapper.querySelector('[id^="btn_vendor_"]');
        if (menu && menu.style.display === 'block') {
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.style.display = 'none';
                btn.classList.remove('border-primary', 'shadow-sm');
            }
        }
    });
});

function removeHargaRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) row.remove();
    updateTableHargaVisibility();
}

async function loadBarang() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('barangTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data katalog barang...</td></tr>`;
    
    const url = `/api/master/barang.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        barangDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (barangDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data barang yang cocok.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        barangDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-center text-muted">${rowNumber}</td>
                    <td>
                        <div class="fw-bold text-dark">${item.nama_barang}</div>
                        <div class="d-flex align-items-center flex-wrap gap-1 mt-1">
                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">${item.kode_barang || '-'}</span>
                            ${item.nama_kategori ? `<span class="badge bg-primary-subtle text-primary" style="font-size: 0.7rem;">${item.nama_kategori}</span>` : ''}
                            ${item.nama_merk ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">${item.nama_merk}</span>` : ''}
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary font-monospace fw-bold">
                            ${item.total_stok || 0} ${item.satuan}
                        </span>
                    </td>
                    <td><span class="badge ${item.jenis === 1 ? 'bg-info-subtle text-dark' : 'bg-warning-subtle text-dark'}">${item.jenis_label}</span></td>
                    <td>
                        <span class="badge ${item.aktif === 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${item.aktif === 1 ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="showBarangDetail(${idx})" title="Lihat Rincian">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <button class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditBarangModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteBarang(${item.id_barang}, '${item.kode_barang}', '${item.nama_barang.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat data barang.</td></tr>`;
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

// -------------------------------------------------------------
// SEARCHABLE SELECT: KATEGORI
// -------------------------------------------------------------
function openKategoriDropdown() {
    const query = document.getElementById('kategoriSearchInput').value.trim();
    renderKategoriList(kategoriListCache, query);
    document.getElementById('kategoriDropdownMenu').classList.remove('d-none');
}

function debounceKategoriSearch() {
    clearTimeout(kategoriSearchTimeout);
    kategoriSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('kategoriSearchInput').value.trim();
        const res = await apiRequest(`/api/master/kategori.php?limit=100&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderKategoriList(res.data.items || [], query);
        }
    }, 250);
}

function renderKategoriList(items, searchQuery = '') {
    const list = document.getElementById('kategoriDropdownList');
    const cleanQuery = searchQuery.trim();
    let html = '';

    const hasExactMatch = items.some(k => k.nama_kategori.toLowerCase() === cleanQuery.toLowerCase());

    if (items.length === 0 && !cleanQuery) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada kategori ditemukan.</div>`;
        return;
    }

    if (items.length > 0) {
        items.forEach(k => {
            html += `<div class="searchable-select-item" onclick="selectKategori(${k.id_kategori}, '${k.nama_kategori.replace(/'/g, "\\'")}')">
                        <span class="fw-bold text-dark">${k.nama_kategori}</span>
                     </div>`;
        });
    } else {
        html += `<div class="p-2 text-center text-muted small">Kategori <em>"${cleanQuery}"</em> belum terdaftar.</div>`;
    }

    // Tombol Tambah Baru jika tidak ada di list
    if (cleanQuery && !hasExactMatch) {
        const escapedQuery = cleanQuery.replace(/'/g, "\\'");
        html += `
            <div class="searchable-select-item text-primary bg-primary-subtle border-top border-primary-subtle py-2 d-flex align-items-center justify-content-between" onclick="quickCreateKategori('${escapedQuery}')">
                <span><i class="bi bi-plus-circle-fill me-1"></i> Tambah Baru: <strong>"${cleanQuery}"</strong></span>
                <span class="badge bg-primary text-white" style="font-size: 0.7rem;">Simpan Otomatis</span>
            </div>
        `;
    }

    list.innerHTML = html;
}

async function quickCreateKategori(name) {
    const list = document.getElementById('kategoriDropdownList');
    list.innerHTML = `<div class="p-2 text-center text-muted small"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Menyimpan kategori baru "${name}"...</div>`;

    const res = await apiRequest('/api/master/kategori.php', {
        method: 'POST',
        body: JSON.stringify({ nama_kategori: name, aktif: 1 })
    });

    if (res && res.success) {
        const newId = res.data?.id_kategori || (res.data?.id || 0);
        // Refresh cache
        const resK = await apiRequest('/api/master/kategori.php?limit=100');
        if (resK && resK.success) kategoriListCache = resK.data.items || [];
        
        selectKategori(newId, name);
        showToast(`Kategori "${name}" berhasil ditambahkan dan dipilih!`, 'success');
    } else {
        showToast(res.message || 'Gagal menambahkan kategori baru', 'error');
        renderKategoriList(kategoriListCache, name);
    }
}

function selectKategori(id, name) {
    document.getElementById('formKategoriIdVal').value = id;
    document.getElementById('kategoriSearchInput').value = name;
    document.getElementById('kategoriDropdownMenu').classList.add('d-none');
}

function clearKategoriSelection() {
    document.getElementById('formKategoriIdVal').value = '';
    document.getElementById('kategoriSearchInput').value = '';
}

// -------------------------------------------------------------
// SEARCHABLE SELECT: MERK
// -------------------------------------------------------------
function openMerkDropdown() {
    const query = document.getElementById('merkSearchInput').value.trim();
    renderMerkList(merkListCache, query);
    document.getElementById('merkDropdownMenu').classList.remove('d-none');
}

function debounceMerkSearch() {
    clearTimeout(merkSearchTimeout);
    merkSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('merkSearchInput').value.trim();
        const res = await apiRequest(`/api/master/merk.php?limit=100&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderMerkList(res.data.items || [], query);
        }
    }, 250);
}

function renderMerkList(items, searchQuery = '') {
    const list = document.getElementById('merkDropdownList');
    const cleanQuery = searchQuery.trim();
    let html = '';

    const hasExactMatch = items.some(m => m.nama_merk.toLowerCase() === cleanQuery.toLowerCase());

    if (items.length === 0 && !cleanQuery) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada merk ditemukan.</div>`;
        return;
    }

    if (items.length > 0) {
        items.forEach(m => {
            html += `<div class="searchable-select-item" onclick="selectMerk(${m.id_merk}, '${m.nama_merk.replace(/'/g, "\\'")}')">
                        <span class="fw-bold text-dark">${m.nama_merk}</span>
                     </div>`;
        });
    } else {
        html += `<div class="p-2 text-center text-muted small">Merk <em>"${cleanQuery}"</em> belum terdaftar.</div>`;
    }

    // Tombol Tambah Baru jika tidak ada di list
    if (cleanQuery && !hasExactMatch) {
        const escapedQuery = cleanQuery.replace(/'/g, "\\'");
        html += `
            <div class="searchable-select-item text-primary bg-primary-subtle border-top border-primary-subtle py-2 d-flex align-items-center justify-content-between" onclick="quickCreateMerk('${escapedQuery}')">
                <span><i class="bi bi-plus-circle-fill me-1"></i> Tambah Baru: <strong>"${cleanQuery}"</strong></span>
                <span class="badge bg-primary text-white" style="font-size: 0.7rem;">Simpan Otomatis</span>
            </div>
        `;
    }

    list.innerHTML = html;
}

async function quickCreateMerk(name) {
    const list = document.getElementById('merkDropdownList');
    list.innerHTML = `<div class="p-2 text-center text-muted small"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Menyimpan merk baru "${name}"...</div>`;

    const res = await apiRequest('/api/master/merk.php', {
        method: 'POST',
        body: JSON.stringify({ nama_merk: name, aktif: 1 })
    });

    if (res && res.success) {
        const newId = res.data?.id_merk || (res.data?.id || 0);
        // Refresh cache
        const resM = await apiRequest('/api/master/merk.php?limit=100');
        if (resM && resM.success) merkListCache = resM.data.items || [];
        
        selectMerk(newId, name);
        showToast(`Merk "${name}" berhasil ditambahkan dan dipilih!`, 'success');
    } else {
        showToast(res.message || 'Gagal menambahkan merk baru', 'error');
        renderMerkList(merkListCache, name);
    }
}

function selectMerk(id, name) {
    document.getElementById('formMerkIdVal').value = id;
    document.getElementById('merkSearchInput').value = name;
    document.getElementById('merkDropdownMenu').classList.add('d-none');
}

function clearMerkSelection() {
    document.getElementById('formMerkIdVal').value = '';
    document.getElementById('merkSearchInput').value = '';
}

// -------------------------------------------------------------
// SEARCHABLE SELECT: VENDOR
// -------------------------------------------------------------
function openVendorDropdown() {
    renderVendorList(vendorListCache);
    document.getElementById('vendorDropdownMenu').classList.remove('d-none');
}

function debounceVendorSearch() {
    clearTimeout(vendorSearchTimeout);
    vendorSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('vendorSearchInput').value.trim();
        const res = await apiRequest(`/api/master/vendor.php?limit=100&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderVendorList(res.data.items || []);
        }
    }, 250);
}

function renderVendorList(items) {
    const list = document.getElementById('vendorDropdownList');
    if (items.length === 0) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada vendor ditemukan.</div>`;
        return;
    }
    let html = '';
    items.forEach(v => {
        html += `<div class="searchable-select-item" onclick="selectVendor(${v.id_vendor}, '${v.nama_perusahaan.replace(/'/g, "\\'")}', '${v.kode_vendor || ''}')">
                    <div class="fw-bold text-dark">${v.nama_perusahaan}</div>
                    <div class="text-muted small" style="font-size: 0.75rem;">${v.kode_vendor || '-'} &bull; ${v.kota || '-'}</div>
                 </div>`;
    });
    list.innerHTML = html;
}

function selectVendor(id, name, code) {
    document.getElementById('formVendorIdVal').value = id;
    document.getElementById('vendorSearchInput').value = `${name} (${code || 'VND'})`;
    document.getElementById('vendorDropdownMenu').classList.add('d-none');
}

function clearVendorSelection() {
    document.getElementById('formVendorIdVal').value = '';
    document.getElementById('vendorSearchInput').value = '';
}

// Klik di luar dropdown untuk menutup
document.addEventListener('click', (e) => {
    if (!e.target.closest('#kategoriSearchWrapper')) {
        document.getElementById('kategoriDropdownMenu')?.classList.add('d-none');
    }
    if (!e.target.closest('#merkSearchWrapper')) {
        document.getElementById('merkDropdownMenu')?.classList.add('d-none');
    }
    if (!e.target.closest('#vendorSearchWrapper')) {
        document.getElementById('vendorDropdownMenu')?.classList.add('d-none');
    }
});

// -------------------------------------------------------------
// LOCAL FILE PREVIEW
// -------------------------------------------------------------
function handleLocalFileSelect(input, slot) {
    const file = input.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        showToast('Ukuran gambar maksimal 5 MB.', 'error');
        input.value = '';
        return;
    }

    if (slot === 1) pendingFile1 = file;
    if (slot === 2) pendingFile2 = file;

    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById(`previewFoto${slot}`);
        const placeholder = document.getElementById(`placeholderFoto${slot}`);
        const btnRemove = document.getElementById(`btnRemoveFoto${slot}`);
        
        preview.src = e.target.result;
        preview.classList.remove('d-none');
        placeholder.classList.add('d-none');
        btnRemove.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

function removeProductImage(slot) {
    if (slot === 1) {
        pendingFile1 = null;
        document.getElementById('formFile1').value = '';
        document.getElementById('formFoto1Val').value = '';
        document.getElementById('previewFoto1').src = '';
        document.getElementById('previewFoto1').classList.add('d-none');
        document.getElementById('placeholderFoto1').classList.remove('d-none');
        document.getElementById('btnRemoveFoto1').classList.add('d-none');
    } else {
        pendingFile2 = null;
        document.getElementById('formFile2').value = '';
        document.getElementById('formFoto2Val').value = '';
        document.getElementById('previewFoto2').src = '';
        document.getElementById('previewFoto2').classList.add('d-none');
        document.getElementById('placeholderFoto2').classList.remove('d-none');
        document.getElementById('btnRemoveFoto2').classList.add('d-none');
    }
}

function togglePpnbmRateInput() {
    const isPpnbm = document.getElementById('ppnbmYes').checked;
    const rateInput = document.getElementById('formRatePpnbm');
    if (isPpnbm) {
        rateInput.disabled = false;
        if (parseFloat(rateInput.value) <= 0) {
            rateInput.value = '10'; // default nilai tarif acuan
        }
        rateInput.focus();
    } else {
        rateInput.disabled = true;
        rateInput.value = '0';
    }
}

// -------------------------------------------------------------
// MODAL CRUD
// -------------------------------------------------------------
function openTambahBarangModal() {
    document.getElementById('barangForm').reset();
    document.getElementById('formIdBarang').value = '';
    
    clearKategoriSelection();
    clearMerkSelection();
    clearVendorSelection();
    removeProductImage(1);
    removeProductImage(2);

    document.getElementById('satuanPCS').checked = true;
    document.getElementById('jenisPersediaan').checked = true;
    document.getElementById('assetNon').checked = true;
    document.getElementById('barangAktifYes').checked = true;

    // PPnBM & Rate
    document.getElementById('ppnbmNo').checked = true;
    togglePpnbmRateInput();
    
    // Clear & Init Harga & Stok Form
    document.getElementById('hargaRowsContainer').innerHTML = '';
    updateTableHargaVisibility();
    renderStokFormRows({});
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('bform-tab-utama')).show();
    document.getElementById('barangFormModalTitle').innerHTML = 'Tambah Barang Baru';
    const modal = new bootstrap.Modal(document.getElementById('barangFormModal'));
    modal.show();
}

function openEditBarangModal(idx) {
    const item = barangDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdBarang').value = item.id_barang;
    document.getElementById('formKodeBarang').value = item.kode_barang;
    document.getElementById('formNamaBarang').value = item.nama_barang;
    document.getElementById('formSerialNumber').value = item.serial_number;
    document.getElementById('formDeskripsi').value = item.deskripsi;
    document.getElementById('formCreatedAtDisplay').value = item.created_at || '-';

    // Kategori
    if (item.id_kategori && item.nama_kategori) {
        selectKategori(item.id_kategori, item.nama_kategori);
    } else {
        clearKategoriSelection();
    }

    // Merk
    if (item.id_merk && item.nama_merk) {
        selectMerk(item.id_merk, item.nama_merk);
    } else {
        clearMerkSelection();
    }

    // Vendor
    if (item.default_id_vendor && item.nama_vendor) {
        selectVendor(item.default_id_vendor, item.nama_vendor, item.kode_vendor);
    } else {
        clearVendorSelection();
    }

    // Satuan
    if (item.satuan === 'UNIT') {
        document.getElementById('satuanUNIT').checked = true;
    } else {
        document.getElementById('satuanPCS').checked = true;
    }
    updateStokSatuanLabels();

    // Jenis
    if (item.jenis === 0) {
        document.getElementById('jenisJasa').checked = true;
    } else {
        document.getElementById('jenisPersediaan').checked = true;
    }

    // Asset
    if (item.asset === 1) {
        document.getElementById('assetYes').checked = true;
    } else {
        document.getElementById('assetNon').checked = true;
    }

    // Aktif
    if (item.aktif === 1) {
        document.getElementById('barangAktifYes').checked = true;
    } else {
        document.getElementById('barangAktifNo').checked = true;
    }

    // PPnBM & Rate
    if (item.ppnbm === 1) {
        document.getElementById('ppnbmYes').checked = true;
        document.getElementById('formRatePpnbm').disabled = false;
        document.getElementById('formRatePpnbm').value = item.rate_ppnbm || 0;
    } else {
        document.getElementById('ppnbmNo').checked = true;
        document.getElementById('formRatePpnbm').disabled = true;
        document.getElementById('formRatePpnbm').value = 0;
    }

    // Foto 1
    removeProductImage(1);
    if (item.foto1) {
        document.getElementById('formFoto1Val').value = item.foto1;
        const p1 = document.getElementById('previewFoto1');
        p1.src = `${BASE_URL}/${item.foto1}`;
        p1.classList.remove('d-none');
        document.getElementById('placeholderFoto1').classList.add('d-none');
        document.getElementById('btnRemoveFoto1').classList.remove('d-none');
    }

    // Foto 2
    removeProductImage(2);
    if (item.foto2) {
        document.getElementById('formFoto2Val').value = item.foto2;
        const p2 = document.getElementById('previewFoto2');
        p2.src = `${BASE_URL}/${item.foto2}`;
        p2.classList.remove('d-none');
        document.getElementById('placeholderFoto2').classList.add('d-none');
        document.getElementById('btnRemoveFoto2').classList.remove('d-none');
    }

    // Load Stok Map
    const stokMap = {};
    if (item.stok_per_site && Array.isArray(item.stok_per_site)) {
        item.stok_per_site.forEach(stk => {
            stokMap[stk.id_site] = stk.stok;
        });
    }
    renderStokFormRows(stokMap);

    // Load Harga Vendor Rows
    const hargaContainer = document.getElementById('hargaRowsContainer');
    hargaContainer.innerHTML = '';
    if (item.harga_vendors && Array.isArray(item.harga_vendors) && item.harga_vendors.length > 0) {
        item.harga_vendors.forEach(h => {
            addHargaRow(h.id_vendor, h.harga_set, h.berlaku);
        });
    } else {
        updateTableHargaVisibility();
    }

    bootstrap.Tab.getOrCreateInstance(document.getElementById('bform-tab-utama')).show();
    document.getElementById('barangFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Data Barang';
    const modal = new bootstrap.Modal(document.getElementById('barangFormModal'));
    modal.show();
}

async function handleSaveBarang(e) {
    e.preventDefault();
    const id = document.getElementById('formIdBarang').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveBarang');

    const namaBarang = document.getElementById('formNamaBarang').value.trim();
    const idKategori = document.getElementById('formKategoriIdVal').value;

    if (!namaBarang) {
        showToast('Nama barang wajib diisi.', 'error');
        return;
    }
    if (!idKategori) {
        showToast('Kategori barang wajib dipilih.', 'error');
        return;
    }

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengunggah & Menyimpan...';

    // 1. Upload Pending Foto 1 jika ada
    let foto1Final = document.getElementById('formFoto1Val').value;
    if (pendingFile1) {
        const formData1 = new FormData();
        formData1.append('image', pendingFile1);
        formData1.append('foto', pendingFile1);
        formData1.append('type', 'barang');
        try {
            const resUp1 = await fetch(BASE_URL + '/api/master/upload_image.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Authorization': 'Bearer ' + API_TOKEN
                },
                body: formData1
            });
            const dataUp1 = await resUp1.json();
            if (dataUp1 && dataUp1.success && dataUp1.data) {
                foto1Final = dataUp1.data.url || dataUp1.data.file_path || dataUp1.data.filename;
            } else {
                console.error('Upload foto1 gagal:', dataUp1?.message);
            }
        } catch (err) {
            console.error('Upload foto1 network error:', err);
        }
    }

    // 2. Upload Pending Foto 2 jika ada
    let foto2Final = document.getElementById('formFoto2Val').value;
    if (pendingFile2) {
        const formData2 = new FormData();
        formData2.append('image', pendingFile2);
        formData2.append('foto', pendingFile2);
        formData2.append('type', 'barang');
        try {
            const resUp2 = await fetch(BASE_URL + '/api/master/upload_image.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Authorization': 'Bearer ' + API_TOKEN
                },
                body: formData2
            });
            const dataUp2 = await resUp2.json();
            if (dataUp2 && dataUp2.success && dataUp2.data) {
                foto2Final = dataUp2.data.url || dataUp2.data.file_path || dataUp2.data.filename;
            } else {
                console.error('Upload foto2 gagal:', dataUp2?.message);
            }
        } catch (err) {
            console.error('Upload foto2 network error:', err);
        }
    }

    const satuanVal = document.querySelector('input[name="formSatuanRadio"]:checked')?.value || 'PCS';
    const jenisVal = document.querySelector('input[name="formJenisRadio"]:checked')?.value || '1';
    const assetVal = document.querySelector('input[name="formAssetRadio"]:checked')?.value || '0';
    const aktifVal = document.querySelector('input[name="formAktifRadio"]:checked')?.value || '1';
    const ppnbmVal = document.querySelector('input[name="formPpnbmRadio"]:checked')?.value || '0';
    const ratePpnbmVal = parseFloat(document.getElementById('formRatePpnbm')?.value) || 0;

    // Kumpulkan Alokasi Stok per Site (Selalu simpan ke database meskipun 0 atau null)
    const stokSites = [];
    document.querySelectorAll('.site-stok-input').forEach(inp => {
        const siteId = parseInt(inp.dataset.siteId, 10);
        const val = inp.value.trim();
        const qty = (val !== '' && !isNaN(val)) ? Math.max(0, parseInt(val, 10)) : 0;
        if (siteId > 0) {
            stokSites.push({ id_site: siteId, stok: qty });
        }
    });

    // Kumpulkan Daftar Harga per Vendor
    const hargaVendors = [];
    document.querySelectorAll('.harga-vendor-row').forEach(row => {
        const inpV = row.querySelector('.harga-vendor-id-val');
        const inpP = row.querySelector('.harga-vendor-price');
        const inpD = row.querySelector('.harga-vendor-date');
        const vId = inpV ? parseInt(inpV.value, 10) : 0;
        const price = inpP ? parseFloat(inpP.value) : 0;
        const bDate = inpD ? inpD.value : '';
        if (vId > 0 && price > 0) {
            hargaVendors.push({ id_vendor: vId, harga_set: price, berlaku: bDate });
        }
    });

    const payload = {
        id_barang: id,
        kode_barang: document.getElementById('formKodeBarang').value.trim(),
        nama_barang: namaBarang,
        id_kategori: idKategori,
        id_merk: document.getElementById('formMerkIdVal').value || 1,
        default_id_vendor: document.getElementById('formVendorIdVal').value || null,
        satuan: satuanVal,
        jenis: parseInt(jenisVal),
        asset: parseInt(assetVal),
        aktif: parseInt(aktifVal),
        PPnBM: parseInt(ppnbmVal),
        rate_PPnBM: ratePpnbmVal,
        serial_number: document.getElementById('formSerialNumber').value.trim(),
        deskripsi: document.getElementById('formDeskripsi').value.trim(),
        foto1: foto1Final,
        foto2: foto2Final,
        stok_sites: stokSites,
        harga_vendors: hargaVendors,
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/barang.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan';
    
    if (res && res.success) {
        showToast(res.message || 'Data barang berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('barangFormModal')).hide();
        loadBarang();
    } else {
        showToast(res.message || 'Gagal menyimpan data barang.', 'error');
    }
}

async function deleteBarang(id, kode, name) {
    showDeleteConfirm(
        `Apakah Anda yakin ingin menghapus barang <strong>${escapeHtml(kode)} - ${escapeHtml(name)}</strong>?<br>Data yang dihapus tidak dapat dikembalikan.`,
        kode,
        async function() {
            const res = await apiRequest('/api/master/barang.php', {
                method: 'POST',
                body: JSON.stringify({ id_barang: id, _method: 'DELETE' })
            });
            
            if (res && res.success) {
                showToast('Barang berhasil dihapus.', 'success');
                loadBarang();
            } else {
                showToast(res.message || 'Gagal menghapus barang.', 'error');
            }
        }
    );
}

function showBarangDetail(idx) {
    const item = barangDataStore[idx];
    if (!item) return;
    
    // Header Modal
    document.getElementById('modalHeaderKodeBarang').textContent = item.kode_barang || 'BRG-DETAIL';
    document.getElementById('modalHeaderStatusBadge').innerHTML = `<span class="badge ${item.aktif === 1 ? 'bg-success text-white border border-success' : 'bg-danger text-white border border-danger'} px-2 py-1">${item.aktif === 1 ? 'Aktif' : 'Non-aktif'}</span>`;
    document.getElementById('modalHeaderJenisBadge').innerHTML = `<span class="badge ${item.jenis === 1 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'} px-2 py-1">${item.jenis_label}</span>`;

    // Tab 1: Utama
    document.getElementById('modalKodeBarang').textContent = item.kode_barang || '-';
    document.getElementById('modalNamaBarang').textContent = item.nama_barang;
    document.getElementById('modalSatuanBarang').innerHTML = `<span class="badge bg-secondary-subtle text-secondary font-monospace border">${item.satuan}</span>`;
    document.getElementById('modalKategoriBarang').textContent = item.nama_kategori || 'Umum';
    document.getElementById('modalMerkBarang').textContent = item.nama_merk || 'Umum';
    document.getElementById('modalDefaultVendor').textContent = item.nama_vendor ? `${item.nama_vendor} (${item.kode_vendor || 'VND'})` : '-';
    
    // Tab 2: Stok Site
    const stokList = document.getElementById('modalStokSiteList');
    const stokPerSite = item.stok_per_site || [];
    document.getElementById('modalTabStokBadge').textContent = `${item.total_stok || 0} ${item.satuan}`;
    document.getElementById('modalTotalStokBadge').textContent = `Total Stok: ${item.total_stok || 0} ${item.satuan}`;
    
    if (stokPerSite.length > 0) {
        let sHtml = '';
        stokPerSite.forEach(s => {
            sHtml += `
                <tr>
                    <td class="fw-semibold"><i class="bi bi-geo-alt me-1 text-primary"></i>${s.nama_site}</td>
                    <td><span class="badge bg-light text-muted border font-monospace">${s.kode_site || '-'}</span></td>
                    <td class="text-end font-monospace fw-bold ${s.stok > 0 ? 'text-primary' : 'text-muted'}">${s.stok} ${item.satuan}</td>
                </tr>
            `;
        });
        stokList.innerHTML = sHtml;
    } else {
        stokList.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-muted small">Belum ada alokasi stok di site manapun.</td></tr>`;
    }

    // Tab 3: Harga Vendor
    const hargaList = document.getElementById('modalHargaVendorList');
    const hargaVendors = item.harga_vendors || [];
    document.getElementById('modalTabHargaBadge').textContent = `${hargaVendors.length}`;
    
    if (hargaVendors.length > 0) {
        let hHtml = '';
        hargaVendors.forEach(h => {
            hHtml += `
                <tr>
                    <td class="fw-semibold text-dark">${h.nama_vendor}</td>
                    <td class="font-monospace text-success fw-bold">${h.harga_formatted}</td>
                    <td class="text-muted small">${h.berlaku_formatted}</td>
                </tr>
            `;
        });
        hargaList.innerHTML = hHtml;
    } else {
        hargaList.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-muted small">Belum ada data referensi harga vendor.</td></tr>`;
    }

    // Tab 4: Foto
    const f1Cont = document.getElementById('modalFoto1Container');
    if (item.foto1) {
        f1Cont.innerHTML = `<img src="${BASE_URL}/${item.foto1}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">`;
    } else {
        f1Cont.innerHTML = `<span class="text-muted small"><i class="bi bi-image me-1"></i>Tidak ada Foto 1</span>`;
    }

    const f2Cont = document.getElementById('modalFoto2Container');
    if (item.foto2) {
        f2Cont.innerHTML = `<img src="${BASE_URL}/${item.foto2}" class="img-fluid rounded" style="max-height: 180px; object-fit: contain;">`;
    } else {
        f2Cont.innerHTML = `<span class="text-muted small"><i class="bi bi-image me-1"></i>Tidak ada Foto 2</span>`;
    }

    // Tab 5: Tambahan
    document.getElementById('modalAssetBarang').innerHTML = `<span class="badge ${item.asset === 1 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border'}">${item.asset_label}</span>`;
    
    // Visual Klasifikasi Pajak PPnBM & Rate
    const ppnbmBadge = item.ppnbm === 1 
        ? `<span class="badge bg-warning text-dark border border-warning-subtle"><i class="bi bi-percent me-1"></i>Kena PPnBM (${item.rate_ppnbm || 0}%)</span>`
        : `<span class="badge bg-light text-muted border">Non PPnBM (0%)</span>`;
    document.getElementById('modalPpnbmBarang').innerHTML = ppnbmBadge;

    document.getElementById('modalSerialBarang').textContent = item.serial_number || '-';
    document.getElementById('modalCreatedAt').textContent = item.created_at || '-';
    document.getElementById('modalDeskripsiBarang').textContent = item.deskripsi || 'Tidak ada deskripsi khusus.';
    
    // Reset ke tab pertama (Utama)
    const tabTrigger = document.querySelector('#bdetail-tab-utama');
    if (tabTrigger) {
        const tab = bootstrap.Tab.getInstance(tabTrigger) || new bootstrap.Tab(tabTrigger);
        tab.show();
    }

    const modal = bootstrap.Modal.getInstance(document.getElementById('barangDetailModal')) || new bootstrap.Modal(document.getElementById('barangDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadDependencies();
    loadBarang();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
