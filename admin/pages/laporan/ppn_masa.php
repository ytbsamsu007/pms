<?php
/**
 * Halaman Laporan PPN Masa (PPN Keluaran vs PPN Masukan)
 * Path: admin/pages/laporan/ppn_masa.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection Khusus Finance, Admin, Manager
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$pageTitle = 'Laporan PPN Masa';
$pageHeading = 'Laporan PPN Masa';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan PPN Masa</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadPpnMasaReport()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
            <button type="button" class="btn btn-primary filter-btn px-3 shadow-sm fw-semibold" onclick="printReport()">
                <i class="bi bi-printer-fill me-1"></i> Cetak
            </button>
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Filter Tahun -->
                <div style="min-width: 140px;">
                    <select class="form-select filter-select font-monospace fw-semibold" id="filterTahun" onchange="loadPpnMasaReport()">
                        <?php
                        $curYr = (int)date('Y');
                        for ($y = $curYr - 5; $y <= $curYr + 10; $y++) {
                            $sel = ($y === $curYr) ? 'selected' : '';
                            echo "<option value=\"{$y}\" {$sel}>{$y}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Filter Bulan -->
                <div style="min-width: 170px;">
                    <select class="form-select filter-select" id="filterBulan" onchange="loadPpnMasaReport()">
                        <option value="0">Semua Bulan</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <div>
                    <button type="button" class="btn btn-outline-secondary filter-btn" title="Reset Filter" onclick="resetFilters()" style="width: 38px; height: 38px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablePpnMasa">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-2 align-middle text-center" style="width: 50px;">No</th>
                            <th class="py-2 align-middle" style="min-width: 160px;">Masa Pajak</th>
                            <th class="py-2 align-middle text-end" style="min-width: 170px;">PPN Masukan</th>
                            <th class="py-2 align-middle text-end" style="min-width: 170px;">PPN Keluaran</th>
                            <th class="py-2 align-middle text-end" style="min-width: 180px;">Selisih PPN</th>
                            <th class="pe-3 py-2 align-middle text-center" style="min-width: 160px;">Status PPN</th>
                        </tr>
                    </thead>
                    <tbody id="ppnMasaTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data laporan PPN masa...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold" id="ppnMasaTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="2" class="ps-3 py-2 text-end text-uppercase">Grand Total:</td>
                            <td class="py-2 text-end text-primary font-monospace fs-6" id="footGrandMasukan">0</td>
                            <td class="py-2 text-end text-info-emphasis font-monospace fs-6" id="footGrandKeluaran">0</td>
                            <td class="py-2 text-end font-monospace fs-6" id="footGrandSelisih">0</td>
                            <td class="pe-3 py-2 text-center" id="footGrandStatus">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RINCIAN DETAIL PPN MASA PER BULAN -->
<div class="modal fade" id="modalDetailMasa" tabindex="-1" aria-labelledby="modalDetailMasaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailMasaLabel">
                        <i class="bi bi-calculator-fill text-primary me-2"></i> Rincian PPN Masa - <span id="detailModalPeriodeTitle">-</span>
                    </h5>
                    <div class="text-muted small" id="detailModalStatusText">Status: -</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalDetailBody">
                <!-- Rangkuman Kotak -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-3 p-3 text-center">
                            <span class="text-muted small d-block mb-1">Total PPN Masukan (Beli)</span>
                            <h5 class="fw-bold text-primary font-monospace mb-0" id="modalSummaryMasukan">0</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-3 p-3 text-center">
                            <span class="text-muted small d-block mb-1">Total PPN Keluaran (Jual)</span>
                            <h5 class="fw-bold text-info-emphasis font-monospace mb-0" id="modalSummaryKeluaran">0</h5>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-3 p-3 text-center" id="modalSummarySelisihCard">
                            <span class="text-muted small d-block mb-1">Selisih &amp; Status</span>
                            <h5 class="fw-bold font-monospace mb-0" id="modalSummarySelisih">0</h5>
                            <span class="badge px-2 py-1 mt-1 align-self-center" id="modalSummaryBadge">-</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs: PPN Masukan & PPN Keluaran -->
                <ul class="nav nav-tabs mb-3" id="tabDetailMasa" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="tab-masukan-btn" data-bs-toggle="tab" data-bs-target="#tab-masukan-pane" type="button" role="tab">
                            <i class="bi bi-box-arrow-in-down me-1 text-primary"></i> Rincian PPN Masukan (<span id="countMasukanTab">0</span> Faktur)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="tab-keluaran-btn" data-bs-toggle="tab" data-bs-target="#tab-keluaran-pane" type="button" role="tab">
                            <i class="bi bi-box-arrow-up me-1 text-info"></i> Rincian PPN Keluaran (<span id="countKeluaranTab">0</span> Data)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="tabDetailMasaContent">
                    <!-- Tab PPN Masukan -->
                    <div class="tab-pane fade show active" id="tab-masukan-pane" role="tabpanel">
                        <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">No</th>
                                        <th>Tgl Faktur Pajak</th>
                                        <th>No. Seri Faktur Pajak</th>
                                        <th>No. PO / Inv Vendor</th>
                                        <th>Vendor</th>
                                        <th class="text-end">DPP</th>
                                        <th class="text-end">PPN Masukan</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModalMasukan">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab PPN Keluaran -->
                    <div class="tab-pane fade" id="tab-keluaran-pane" role="tabpanel">
                        <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">No</th>
                                        <th>Keterangan</th>
                                        <th>Diinput Oleh</th>
                                        <th>Waktu Input</th>
                                        <th class="text-end">Nilai PPN Keluaran</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModalKeluaran">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light py-2 px-4 border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let cachedDataRows = [];

document.addEventListener('DOMContentLoaded', () => {
    loadPpnMasaReport();
});

function resetFilters() {
    const today = new Date();
    document.getElementById('filterTahun').value = today.getFullYear();
    document.getElementById('filterBulan').value = '0';
    loadPpnMasaReport();
}

function formatRupiah(num) {
    if (num === null || num === undefined || isNaN(num)) return '0';
    return Number(num).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

async function loadPpnMasaReport() {
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;

    const tbody = document.getElementById('ppnMasaTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div> Memuat data laporan PPN masa...
            </td>
        </tr>
    `;

    try {
        const url = `<?= BASE_URL ?>/api/laporan/ppn_masa.php?tahun=${tahun}&bulan=${bulan}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> ${escapeHtml(json.message || 'Gagal memuat data.')}
                    </td>
                </tr>
            `;
            return;
        }

        const data = json.data;
        cachedDataRows = data.rows || [];
        const summary = data.summary || {};

        // Update Table Foot
        document.getElementById('footGrandMasukan').innerText = formatRupiah(summary.grand_total_masukan || 0);
        document.getElementById('footGrandKeluaran').innerText = formatRupiah(summary.grand_total_keluaran || 0);
        
        const grandSelisih = parseFloat(summary.grand_total_selisih) || 0;
        const footSelisihEl = document.getElementById('footGrandSelisih');
        footSelisihEl.innerText = formatRupiah(Math.abs(grandSelisih));
        
        const footStatusEl = document.getElementById('footGrandStatus');
        if (grandSelisih > 0) {
            footSelisihEl.className = 'py-2 text-end text-danger font-monospace fs-6';
            footStatusEl.innerHTML = `<span class="badge bg-danger text-white px-2 py-1">PPN Kurang Bayar</span>`;
        } else if (grandSelisih < 0) {
            footSelisihEl.className = 'py-2 text-end text-success font-monospace fs-6';
            footStatusEl.innerHTML = `<span class="badge bg-success text-white px-2 py-1">PPN Lebih Bayar</span>`;
        } else {
            footSelisihEl.className = 'py-2 text-end text-secondary font-monospace fs-6';
            footStatusEl.innerHTML = `<span class="badge bg-secondary text-white px-2 py-1">Nihil</span>`;
        }
        document.getElementById('ppnMasaTableFoot').style.display = cachedDataRows.length > 0 ? '' : 'none';

        // Render Table Rows
        if (cachedDataRows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                        Tidak ada data PPN masa untuk periode yang dipilih.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        cachedDataRows.forEach((row, idx) => {
            const no = idx + 1;
            const masukan = formatRupiah(row.ppn_masukan);
            const keluaran = formatRupiah(row.ppn_keluaran);
            const selisih = formatRupiah(row.selisih_abs);

            let statusHtml = '';
            let selisihColorClass = 'text-secondary';
            if (row.selisih > 0) {
                selisihColorClass = 'text-danger';
                statusHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-semibold"><i class="bi bi-arrow-up-circle me-1"></i>PPN Kurang Bayar</span>`;
            } else if (row.selisih < 0) {
                selisihColorClass = 'text-success';
                statusHtml = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-semibold"><i class="bi bi-arrow-down-circle me-1"></i>PPN Lebih Bayar</span>`;
            } else {
                statusHtml = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 fw-semibold">Nihil</span>`;
            }

            html += `
                <tr class="align-middle">
                    <td class="ps-3 py-2 text-center text-muted fw-semibold">${no}</td>
                    <td class="py-2">
                        <div class="fw-bold text-dark">${escapeHtml(row.periode_formatted)}</div>
                    </td>
                    <td class="py-2 text-end fw-bold text-primary font-monospace">
                        ${masukan}
                    </td>
                    <td class="py-2 text-end fw-bold text-info-emphasis font-monospace">
                        ${keluaran}
                    </td>
                    <td class="py-2 text-end fw-bold font-monospace fs-6 ${selisihColorClass}">
                        ${selisih}
                    </td>
                    <td class="pe-3 py-2 text-center">
                        ${statusHtml}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

    } catch (e) {
        console.error('Error fetching PPN masa:', e);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Terjadi kesalahan saat memuat data.
                </td>
            </tr>
        `;
    }
}

async function showDetailMasa(tahun, bulan) {
    try {
        const url = `<?= BASE_URL ?>/api/laporan/ppn_masa.php?action=detail&tahun=${tahun}&bulan=${bulan}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success || !json.data) {
            alert(json.message || 'Gagal memuat rincian masa pajak.');
            return;
        }

        const data = json.data;
        document.getElementById('detailModalPeriodeTitle').innerText = data.periode_formatted;
        document.getElementById('detailModalStatusText').innerText = `Status: ${data.status_pajak}`;

        document.getElementById('modalSummaryMasukan').innerText = 'Rp ' + formatRupiah(data.total_ppn_masukan);
        document.getElementById('modalSummaryKeluaran').innerText = 'Rp ' + formatRupiah(data.total_ppn_keluaran);
        document.getElementById('modalSummarySelisih').innerText = 'Rp ' + formatRupiah(Math.abs(data.selisih));
        
        const badgeEl = document.getElementById('modalSummaryBadge');
        badgeEl.className = `badge bg-${data.status_badge} text-white px-2 py-1 mt-1`;
        badgeEl.innerText = data.status_pajak;

        // Render Tab PPN Masukan
        const listM = data.rincian_masukan || [];
        document.getElementById('countMasukanTab').innerText = listM.length;
        const tbodyM = document.getElementById('tbodyModalMasukan');
        if (listM.length === 0) {
            tbodyM.innerHTML = `<tr><td colspan="7" class="text-center py-3 text-muted">Tidak ada faktur pajak masukan pada bulan ini.</td></tr>`;
        } else {
            let htmlM = '';
            listM.forEach((item, idx) => {
                htmlM += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td class="font-monospace">${escapeHtml(item.tanggal_faktur_pajak || '-')}</td>
                        <td class="font-monospace fw-bold text-dark">${escapeHtml(item.nomor_faktur_pajak || '-')}</td>
                        <td>
                            <div class="font-monospace fw-semibold">${escapeHtml(item.nomor_po || '-')}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">Inv: ${escapeHtml(item.nomor_faktur_vendor || '-')}</div>
                        </td>
                        <td>${escapeHtml(item.nama_vendor || '-')}</td>
                        <td class="text-end font-monospace">${formatRupiah(item.dpp)}</td>
                        <td class="text-end font-monospace fw-bold text-primary">${formatRupiah(item.ppn_masukan)}</td>
                    </tr>
                `;
            });
            tbodyM.innerHTML = htmlM;
        }

        // Render Tab PPN Keluaran
        const listK = data.rincian_keluaran || [];
        document.getElementById('countKeluaranTab').innerText = listK.length;
        const tbodyK = document.getElementById('tbodyModalKeluaran');
        if (listK.length === 0) {
            tbodyK.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada catatan pajak keluaran pada bulan ini.</td></tr>`;
        } else {
            let htmlK = '';
            listK.forEach((item, idx) => {
                htmlK += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td>${escapeHtml(item.keterangan || '-')}</td>
                        <td>
                            <span class="fw-semibold">${escapeHtml(item.nama_karyawan || 'Finance')}</span>
                            <span class="text-muted small d-block" style="font-size: 0.75rem;">${escapeHtml(item.nama_jabatan || '')}</span>
                        </td>
                        <td class="font-monospace text-muted small">${escapeHtml(item.created_at || '-')}</td>
                        <td class="text-end font-monospace fw-bold text-info-emphasis">${formatRupiah(item.ppn_keluaran)}</td>
                    </tr>
                `;
            });
            tbodyK.innerHTML = htmlK;
        }

        const modal = new bootstrap.Modal(document.getElementById('modalDetailMasa'));
        modal.show();

    } catch (e) {
        alert('Gagal mengambil rincian data.');
    }
}

function printReport() {
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;

    const url = `<?= BASE_URL ?>/admin/pages/laporan/print_ppn_masa.php?tahun=${tahun}&bulan=${bulan}&kop=1`;
    window.open(url, '_blank');
}
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
