<?php
/**
 * Halaman Utama: Daftar Penerimaan Barang (Receiving / SPB Vendor)
 * Path: admin/pages/receiving/index.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 * Database: receiving_order & receiving_order_detail
 * PERHATIAN: TIDAK MENAMPILKAN INFO HARGA / SUBTOTAL / PAJAK
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Khusus Logistik, Manager, dan Admin
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);

$pageTitle = 'Penerimaan Barang';
$pageHeading = 'Daftar Penerimaan Barang';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Title & Action Buttons (Tanpa Ikon Kubus Sesuai Permintaan) -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Penerimaan Barang</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/receiving/create.php" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold">
                <i class="bi bi-plus-circle-fill me-1"></i> Terima Barang
            </a>
        </div>
    </div>

    <!-- FILTER BAR SEDERHANA -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input Bebas -->
                <div class="col-md-7 col-12">
                    <div class="input-group" style="height: 38px;">
                        <span class="input-group-text bg-white border-end-0 text-muted" style="height: 38px;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control bg-white border-start-0 ps-0" id="filterSearch" 
                               placeholder="Cari No Receiving, SPB, No PO, Vendor..." autocomplete="off" style="height: 38px;">
                    </div>
                </div>

                <!-- Dropdown Filter Site -->
                <div class="col-md-4 col-10">
                    <select class="form-select text-truncate" id="filterSite" style="height: 38px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" title="Filter Site">
                        <option value="">Semua Site / Lokasi</option>
                    </select>
                </div>

                <!-- Tombol Reset Filter -->
                <div class="col-md-1 col-2 text-end">
                    <button type="button" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center" style="height: 38px;" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL DAFTAR PENERIMAAN BARANG (RINGKAS & BERSIH) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableReceivingList">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 150px;">No. Receiving</th>
                            <th style="width: 110px;">Tgl Terima</th>
                            <th style="width: 130px;">Nomor PO</th>
                            <th style="min-width: 180px;">Vendor</th>
                            <th style="width: 130px;" class="text-center">Item</th>
                            <th style="width: 110px;" class="text-center">Status Cetak</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="receivingTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data penerimaan barang...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINATION BAR (PAGING LENGKAP & JELAS) -->
        <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted" id="paginationInfo">
                Menampilkan 0 data
            </div>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0" id="paginationList">
                    <!-- Dynamic Page Buttons -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL DETAIL PENERIMAAN BARANG -->
<div class="modal fade" id="modalDetailReceiving" tabindex="-1" aria-labelledby="modalDetailReceivingLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- MODAL HEADER DENGAN NAV TABS -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailReceivingLabel">
                        Rincian Penerimaan Barang
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Nav Tabs Modal Sesuai Fungsi -->
                <ul class="nav nav-tabs border-bottom-0" id="modalDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="modal-tab-dokumen" data-bs-toggle="tab" data-bs-target="#modal-pane-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Dokumen &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-rincian" data-bs-toggle="tab" data-bs-target="#modal-pane-rincian" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> 2. Rincian Barang (QC)
                            <span class="badge bg-primary text-white ms-1" id="modalItemCountBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN TAB CONTENT -->
            <div class="modal-body p-4">
                <div class="tab-content" id="modalDetailTabContent">
                    
                    <!-- TAB 1: DOKUMEN & VENDOR -->
                    <div class="tab-pane fade show active" id="modal-pane-dokumen" role="tabpanel">
                        <div class="row g-3">
                            <!-- Kolom Kiri: Identitas Dokumen & PO -->
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Identitas Penerimaan</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nomor Receiving (RCV):</span>
                                        <strong class="text-primary font-monospace fs-6" id="detailNomorReceiving">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">No. SPB / Surat Jalan Vendor:</span>
                                        <strong class="text-dark font-monospace fs-6" id="detailNomorSpb">-</strong>
                                        <div id="detailFileSjContainer" class="mt-1 d-none">
                                            <a href="#" id="detailFileSjLink" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                                Buka Dokumen Surat Jalan
                                            </a>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Referensi Purchase Order (PO):</span>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" id="detailNomorPo">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Tanggal Diterima:</span>
                                        <span class="text-dark font-monospace" id="detailTanggalReceiving">-</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Petugas Logistik Penerima:</span>
                                        <strong class="text-dark" id="detailPetugas">-</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Pengirim & Lokasi Penerimaan -->
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Pengirim &amp; Lokasi Penerimaan</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Vendor Pengirim:</span>
                                        <strong class="text-dark fs-6" id="detailVendor">-</strong>
                                        <div class="text-muted small" id="detailTeleponVendor">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Site / Gudang Tujuan:</span>
                                        <strong class="text-dark" id="detailSite">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Alamat Site:</span>
                                        <span class="text-secondary small" id="detailAlamatSite">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan Penerimaan -->
                            <div class="col-12">
                                <div class="bg-light p-3 rounded-3 border-0">
                                    <span class="text-muted small d-block mb-1 fw-bold">Catatan Penerimaan:</span>
                                    <p class="mb-0 small text-dark" id="detailCatatan">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: RINCIAN BARANG & QC -->
                    <div class="tab-pane fade" id="modal-pane-rincian" role="tabpanel">
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th style="width: 40px;" class="text-center align-middle">No</th>
                                        <th class="align-middle">Nama Barang &amp; Kode</th>
                                        <th style="width: 90px;" class="text-center align-middle">Qty PO</th>
                                        <th style="width: 110px;" class="text-center align-middle text-success">Qty Diterima</th>
                                        <th style="width: 80px;" class="text-center align-middle">Satuan</th>
                                        <th style="width: 110px;" class="text-center align-middle">Kondisi QC</th>
                                        <th class="align-middle">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="detailReceivingItemsBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Memuat rincian barang...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PILIHAN CETAK SPB (DENGAN KOP / TANPA KOP) -->
<div class="modal fade" id="modalPrintOption" tabindex="-1" aria-labelledby="modalPrintOptionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <h6 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="modalPrintOptionLabel">
                    <i class="bi bi-printer-fill text-primary"></i> Pilihan Cetak Dokumen
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="small text-muted mb-1">Surat Penerimaan Barang (SPB)</div>
                    <div class="fw-bold fs-6 font-monospace text-primary" id="printOptionDocNum">RCV-XXXX-XXXX</div>
                </div>
                <p class="text-muted small text-center mb-4">Silakan tentukan apakah Anda ingin menyertakan <strong>Kop Surat</strong> resmi perusahaan:</p>
                
                <div class="d-grid gap-3">
                    <!-- Opsi 1: Dengan Kop Surat -->
                    <button type="button" class="btn btn-outline-primary p-3 text-start d-flex align-items-center justify-content-between rounded-3 border-2" onclick="executePrint(1)">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="bi bi-file-earmark-richtext-fill fs-4"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Pakai Kop Surat</div>
                                <div class="small text-muted" style="font-size: 0.78rem;">Sertakan logo, identitas PT Jaya Teknis, & dokumen info</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>

                    <!-- Opsi 2: Tanpa Kop Surat -->
                    <button type="button" class="btn btn-outline-secondary p-3 text-start d-flex align-items-center justify-content-between rounded-3 border-2" onclick="executePrint(0)">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-secondary-subtle text-secondary p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="bi bi-file-earmark-text fs-4"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Tanpa Kop Surat</div>
                                <div class="small text-muted" style="font-size: 0.78rem;">Format kosong atas untuk cetak di kertas berkop resmi</div>
                            </div>
                        </div>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<script>
let currentPage = 1;
let currentLimit = 10;
let modalDetailInstance = null;
let modalPrintOptionInstance = null;
let currentPrintId = 0;
let currentPrintNomor = '';

document.addEventListener('DOMContentLoaded', async () => {
    modalDetailInstance = new bootstrap.Modal(document.getElementById('modalDetailReceiving'));
    modalPrintOptionInstance = new bootstrap.Modal(document.getElementById('modalPrintOption'));

    await loadSiteOptions();

    // Event Listener Filter
    document.getElementById('filterSearch').addEventListener('input', debounce(() => loadReceivingList(1), 350));
    document.getElementById('filterSite').addEventListener('change', () => loadReceivingList(1));

    loadReceivingList(1);
});

// -------------------------------------------------------------
// LOAD SITE OPTIONS
// -------------------------------------------------------------
async function loadSiteOptions() {
    try {
        const res = await apiRequest('/api/master/site.php?limit=100');
        if (res && res.success && res.data) {
            const select = document.getElementById('filterSite');
            const items = res.data.items || res.data;
            let options = '<option value="">Semua Site / Lokasi</option>';
            if (Array.isArray(items)) {
                items.forEach(site => {
                    options += `<option value="${site.id_site}">${escapeHtml(site.nama_site)} (${escapeHtml(site.kode_site || 'SITE')})</option>`;
                });
            }
            select.innerHTML = options;
        }
    } catch (err) {
        console.error('Gagal memuat list site:', err);
    }
}

// -------------------------------------------------------------
// LOAD RECEIVING LIST DENGAN FILTER & PAGINATION
// -------------------------------------------------------------
async function loadReceivingList(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('receivingTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data penerimaan barang...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const siteId = document.getElementById('filterSite').value;

    let url = `/api/receiving/index.php?page=${page}&limit=${currentLimit}`;
    if (search) url += `&q=${encodeURIComponent(search)}`;
    if (siteId) url += `&site_id=${encodeURIComponent(siteId)}`;

    const res = await apiRequest(url);

    if (!res || !res.success || !res.data) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat data penerimaan barang.
                </td>
            </tr>
        `;
        return;
    }

    const items = res.data.items || [];
    const pagination = res.data.pagination || { total_records: 0, total_pages: 1, current_page: 1, limit: currentLimit };

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    Belum ada data Penerimaan Barang (Receiving).
                </td>
            </tr>
        `;
        renderPagination(pagination);
        return;
    }

    let rowsHtml = '';
    const startIndex = (pagination.current_page - 1) * pagination.limit;

    items.forEach((item, idx) => {
        const no = startIndex + idx + 1;
        const noRcv = item.nomor_rcv ? escapeHtml(item.nomor_rcv) : '-';
        const noPo = item.nomor_po ? escapeHtml(item.nomor_po) : '-';
        const tgl = item.tanggal_diterima ? item.tanggal_diterima.split(' ')[0] : (item.tanggal_rcv ? item.tanggal_rcv.split(' ')[0] : '-');
        const vendor = item.nama_vendor ? escapeHtml(item.nama_vendor) : '-';
        const totalItems = parseInt(item.total_item) || 0;
        const totalQty = parseFloat(item.total_qty_diterima) || 0;
        const isPrinted = (parseInt(item.print) === 1);

        // Status Cetak Badge
        const printBadge = isPrinted 
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Tercetak</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-hourglass-split me-1"></i>Belum</span>';

        // Action: Unduh File SJ Vendor
        const downloadSjBtn = item.file_sj 
            ? `<a href="<?= BASE_URL ?>/uploads/surat_jalan/${encodeURIComponent(item.file_sj)}" target="_blank" class="btn btn-outline-info btn-sm px-2 py-1 shadow-xs" title="Unduh Surat Jalan Vendor (.pdf/.jpg)"><i class="bi bi-paperclip"></i></a>`
            : `<button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-xs disabled" title="Tidak ada lampiran surat jalan"><i class="bi bi-paperclip"></i></button>`;

        const encRcvId = encodeId(item.id_rcv);
        // Action: Edit Button (Terkunci jika sudah print)
        const editBtn = isPrinted
            ? `<button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-xs disabled" title="Terkunci (Sudah Dicetak)"><i class="bi bi-lock-fill text-muted"></i></button>`
            : `<a href="<?= BASE_URL ?>/admin/pages/receiving/edit.php?id=${encRcvId}" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs" title="Edit Penerimaan"><i class="bi bi-pencil"></i></a>`;

        rowsHtml += `
            <tr>
                <td class="text-center font-monospace text-muted small">${no}</td>
                <td>
                    <div class="fw-bold font-monospace text-primary">${noRcv}</div>
                </td>
                <td class="small text-muted font-monospace">${tgl}</td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace">${noPo}</span>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${vendor}</div>
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary-subtle text-secondary font-monospace">${totalItems} item (${totalQty} qty)</span>
                </td>
                <td class="text-center">
                    ${printBadge}
                </td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        <!-- Lihat Detail -->
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="openDetailModal(${item.id_rcv})" title="Lihat Rincian">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        <!-- Cetak Surat Penerimaan Barang -->
                        <button type="button" class="btn btn-outline-dark btn-sm px-2 py-1 shadow-xs" onclick="openPrintModal(${item.id_rcv}, '${noRcv}')" title="Cetak Surat Penerimaan Barang">
                            <i class="bi bi-printer-fill"></i>
                        </button>
                        <!-- Unduh Surat Jalan Vendor -->
                        ${downloadSjBtn}
                        <!-- Edit Penerimaan -->
                        ${editBtn}
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    renderPagination(pagination);
}

// -------------------------------------------------------------
// PAGINATION RENDERING (PAGING KONTROL LENGKAP)
// -------------------------------------------------------------
function renderPagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('paginationList');

    const totalRecords = pagination.total_records || 0;
    const totalPages = pagination.total_pages || 1;
    const current = pagination.current_page || 1;

    info.innerHTML = `Menampilkan <strong>${totalRecords}</strong> data Penerimaan Barang (Halaman <strong>${current}</strong> dari <strong>${totalPages}</strong>)`;

    if (totalPages <= 1) {
        list.innerHTML = '';
        return;
    }

    let paginationHtml = '';
    
    // Tombol Sebelumnya
    paginationHtml += `
        <li class="page-item ${current === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="loadReceivingList(${current - 1})" title="Halaman Sebelumnya">&laquo; Prev</button>
        </li>
    `;

    // Tombol Angka Halaman
    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= current - 2 && p <= current + 2)) {
            paginationHtml += `
                <li class="page-item ${p === current ? 'active' : ''}">
                    <button class="page-link" onclick="loadReceivingList(${p})">${p}</button>
                </li>
            `;
        } else if (p === current - 3 || p === current + 3) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">&hellip;</span></li>`;
        }
    }

    // Tombol Selanjutnya
    paginationHtml += `
        <li class="page-item ${current === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="loadReceivingList(${current + 1})" title="Halaman Selanjutnya">Next &raquo;</button>
        </li>
    `;

    list.innerHTML = paginationHtml;
}

// -------------------------------------------------------------
// BUKA MODAL DETAIL RECEIVING (TANPA HARGA)
// -------------------------------------------------------------
async function openDetailModal(idRcv) {
    modalDetailInstance.show();

    // Reset ke tab pertama (Dokumen & Vendor)
    const tabTrigger = document.querySelector('#modal-tab-dokumen');
    if (tabTrigger) {
        const tab = bootstrap.Tab.getInstance(tabTrigger) || new bootstrap.Tab(tabTrigger);
        tab.show();
    }

    document.getElementById('detailNomorReceiving').textContent = 'Memuat...';
    document.getElementById('detailNomorSpb').textContent = '-';
    document.getElementById('detailNomorPo').textContent = '-';
    document.getElementById('detailTanggalReceiving').textContent = '-';
    document.getElementById('detailPetugas').textContent = '-';
    document.getElementById('detailVendor').textContent = '-';
    document.getElementById('detailTeleponVendor').textContent = '-';
    document.getElementById('detailSite').textContent = '-';
    document.getElementById('detailAlamatSite').textContent = '-';
    document.getElementById('detailCatatan').textContent = '-';
    document.getElementById('modalItemCountBadge').textContent = '0';
    const btnPrintEl = document.getElementById('modalBtnPrint');
    if (btnPrintEl) btnPrintEl.onclick = () => openPrintModal(idRcv, 'RCV');

    document.getElementById('detailReceivingItemsBody').innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat rincian barang...
            </td>
        </tr>
    `;

    const res = await apiRequest(`/api/receiving/index.php?id=${idRcv}`);
    if (!res || !res.success || !res.data) {
        showToast(res ? res.message : 'Gagal memuat rincian penerimaan.', 'danger');
        modalDetailInstance.hide();
        return;
    }

    const rcv = res.data;

    document.getElementById('detailNomorReceiving').textContent = rcv.nomor_rcv || '-';
    document.getElementById('detailNomorSpb').textContent = rcv.nomor_sj || '-';
    if (btnPrintEl) btnPrintEl.onclick = () => openPrintModal(idRcv, rcv.nomor_rcv || 'RCV');
    
    // Set Surat Jalan File Link
    const sjContainer = document.getElementById('detailFileSjContainer');
    const sjLink = document.getElementById('detailFileSjLink');
    if (rcv.file_sj) {
        sjContainer.classList.remove('d-none');
        sjLink.href = `<?= BASE_URL ?>/uploads/surat_jalan/${encodeURIComponent(rcv.file_sj)}`;
    } else {
        sjContainer.classList.add('d-none');
        sjLink.href = '#';
    }

    document.getElementById('detailNomorPo').textContent = rcv.nomor_po ? `PO: ${rcv.nomor_po}` : '-';
    document.getElementById('detailTanggalReceiving').textContent = rcv.tanggal_diterima ? rcv.tanggal_diterima.split(' ')[0] : (rcv.tanggal_rcv ? rcv.tanggal_rcv.split(' ')[0] : '-');
    document.getElementById('detailPetugas').textContent = rcv.nama_penerima || 'Petugas Logistik';

    document.getElementById('detailVendor').textContent = rcv.nama_vendor || '-';
    document.getElementById('detailTeleponVendor').textContent = rcv.telepon_vendor ? `Telp: ${rcv.telepon_vendor}` : '';
    document.getElementById('detailSite').textContent = rcv.nama_site ? `${rcv.nama_site} (${rcv.kode_site || '-'})` : '-';
    document.getElementById('detailAlamatSite').textContent = rcv.alamat_site || 'Alamat Site';
    document.getElementById('detailCatatan').textContent = rcv.catatan_rcv ? rcv.catatan_rcv : 'Tidak ada catatan khusus.';

    const items = rcv.items || [];
    document.getElementById('modalItemCountBadge').textContent = items.length;
    if (items.length === 0) {
        document.getElementById('detailReceivingItemsBody').innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-3 text-muted">Tidak ada rincian barang.</td>
            </tr>
        `;
        return;
    }

    let itemsHtml = '';
    items.forEach((item, idx) => {
        const qtyPo = parseFloat(item.qty_po) || 0;
        const qtyRcv = parseFloat(item.qty_diterima) || 0;
        const statusQcText = (parseInt(item.status_qc) === 1) ? 'Baik (Passed)' : 'Kurang / Cacat';
        const statusQcBadge = (parseInt(item.status_qc) === 1) ? 'bg-success-subtle text-success border-success-subtle' : 'bg-warning-subtle text-warning border-warning-subtle';

        itemsHtml += `
            <tr>
                <td class="text-center font-monospace text-muted small">${idx + 1}</td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(item.nama_barang || '')}</div>
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                        ${item.nama_kategori ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-tag me-1"></i>${escapeHtml(item.nama_kategori)}</span>` : ''}
                        ${item.nama_merk ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-bookmark me-1"></i>${escapeHtml(item.nama_merk)}</span>` : ''}
                    </div>
                </td>
                <td class="text-center font-monospace text-muted">${qtyPo}</td>
                <td class="text-center font-monospace fw-bold text-success fs-6">${qtyRcv}</td>
                <td class="text-center font-monospace small text-muted">${escapeHtml(item.satuan || 'PCS')}</td>
                <td class="text-center">
                    <span class="badge ${statusQcBadge} border">${statusQcText}</span>
                </td>
                <td class="small text-secondary">${item.keterangan_item ? escapeHtml(item.keterangan_item) : '-'}</td>
            </tr>
        `;
    });

    document.getElementById('detailReceivingItemsBody').innerHTML = itemsHtml;
}

// -------------------------------------------------------------
// PILIHAN CETAK SPB (DENGAN KOP / TANPA KOP)
// -------------------------------------------------------------
function openPrintModal(idRcv, nomorRcv = '') {
    currentPrintId = idRcv;
    currentPrintNomor = nomorRcv;
    document.getElementById('printOptionDocNum').textContent = nomorRcv || 'RCV-XXXX';
    modalPrintOptionInstance.show();
}

function executePrint(kop = 1) {
    if (!currentPrintId) return;
    modalPrintOptionInstance.hide();
    const encId = encodeId(currentPrintId);
    const url = `<?= BASE_URL ?>/admin/pages/receiving/print.php?id=${encId}&kop=${kop}`;
    window.open(url, '_blank');
}

// -------------------------------------------------------------
// RESET FILTERS & UTILITIES
// -------------------------------------------------------------
function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '';
    loadReceivingList(1);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
