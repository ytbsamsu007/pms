<?php
/**
 * Halaman Laporan Pengeluaran Kas & Bank
 * Path: admin/pages/laporan/pengeluaran_bank.php
 * Khusus Role: ADMIN, FINANCE, MANAGER, PURCHASING
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING]);

$pageTitle = 'Laporan Pengeluaran Bank';
$pageHeading = 'Laporan Pengeluaran Bank';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
.filter-select,
.filter-control,
.filter-btn {
    height: 38px !important;
    font-size: 0.875rem !important;
}
.filter-select {
    padding-top: 0.375rem !important;
    padding-bottom: 0.375rem !important;
}
.filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Sembunyikan icon kalender pada input date (Chrome, Edge, Safari, Firefox) */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="date"]::-webkit-inner-spin-button,
input[type="date"]::-webkit-clear-button {
    display: none !important;
    -webkit-appearance: none !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
    position: absolute !important;
    right: -9999px !important;
}
input[type="date"] {
    -moz-appearance: textfield !important;
    appearance: none !important;
}
#tableSummary th,
#tableSummary td {
    vertical-align: middle !important;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Pengeluaran Bank</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadBankReport()">
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
                <!-- Bank Filter -->
                <div style="width: 240px;">
                    <select class="form-select filter-select" id="filterBank" onchange="loadBankReport()">
                        <option value="">Semua Bank Pengirim</option>
                    </select>
                </div>

                <!-- Tanggal Mulai -->
                <div style="width: 180px;">
                    <input type="date" class="form-control filter-control" id="filterStartDate" title="Tanggal Mulai" onchange="loadBankReport()">
                </div>

                <!-- Tanggal Selesai -->
                <div style="width: 180px;">
                    <input type="date" class="form-control filter-control" id="filterEndDate" title="Tanggal Selesai" onchange="loadBankReport()">
                </div>

                <!-- Reset Button (Icon Only) -->
                <div>
                    <button type="button" class="btn btn-outline-secondary filter-btn" title="Reset Filter" onclick="resetFilters()" style="width: 38px; height: 38px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableSummary">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 align-middle" style="width: 50px;">No</th>
                            <th class="py-3 align-middle">Nama Bank</th>
                            <th class="py-3 align-middle" style="width: 180px;">No. Rekening</th>
                            <th class="text-end py-3 align-middle" style="width: 200px;">Nominal Transfer</th>
                            <th class="text-end py-3 align-middle" style="width: 170px;">Biaya Admin</th>
                            <th class="text-end pe-3 py-3 align-middle" style="width: 220px;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat rekapitulasi pengeluaran bank...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold align-middle" id="summaryTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="3" class="ps-3 py-3 text-uppercase align-middle">Grand Total</td>
                            <td class="text-end py-3 align-middle" id="footNominal">Rp 0</td>
                            <td class="text-end py-3 align-middle" id="footAdmin">Rp 0</td>
                            <td class="text-end pe-3 py-3 text-primary fs-6 align-middle" id="footTotal">Rp 0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let debounceTimer = null;
let currentReportData = null;

function resetFilters() {
    document.getElementById('filterBank').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    loadBankReport();
}

function printReport() {
    const bank = document.getElementById('filterBank').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
        bank_pengirim: bank
    });

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_pengeluaran_bank.php?${params.toString()}`, '_blank');
}

async function loadBankReport() {
    const sumTbody = document.getElementById('summaryTableBody');
    const sumTfoot = document.getElementById('summaryTableFoot');

    sumTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data rekapitulasi...</td></tr>`;
    sumTfoot.style.display = 'none';

    const bank = document.getElementById('filterBank').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate,
        bank_pengirim: bank
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/pengeluaran_bank.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            populateBankDropdown(res.data.banks_list, bank);
            renderSummaryTable(res.data.summary, res.data.grand_total);
        } else {
            sumTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">${res.message || 'Gagal memuat laporan.'}</td></tr>`;
        }
    } catch (err) {
        sumTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function populateBankDropdown(banks, currentSelected) {
    const sel = document.getElementById('filterBank');
    if (!banks || sel.options.length > 1) return;

    banks.forEach(b => {
        const opt = document.createElement('option');
        opt.value = b;
        opt.textContent = b;
        if (b === currentSelected) opt.selected = true;
        sel.appendChild(opt);
    });
}

function renderSummaryTable(summary, grand) {
    const tbody = document.getElementById('summaryTableBody');
    const tfoot = document.getElementById('summaryTableFoot');

    if (!summary || summary.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted align-middle">Tidak ada transaksi pengeluaran bank pada periode/filter ini.</td></tr>`;
        tfoot.style.display = 'none';
        return;
    }

    let html = '';
    summary.forEach((item, idx) => {
        html += `
        <tr>
            <td class="ps-3 text-muted align-middle">${idx + 1}</td>
            <td class="fw-semibold text-dark align-middle">
                ${escapeHtml(item.bank_pengirim || '-')}
            </td>
            <td class="font-monospace text-secondary align-middle">
                ${escapeHtml(item.norek_pengirim || '-')}
            </td>
            <td class="text-end font-monospace align-middle">${Number(item.total_nominal || 0).toLocaleString('id-ID')}</td>
            <td class="text-end font-monospace text-secondary align-middle">${Number(item.total_biaya_admin || 0).toLocaleString('id-ID')}</td>
            <td class="text-end pe-3 fw-bold font-monospace text-dark align-middle">${Number(item.total_pembayaran || 0).toLocaleString('id-ID')}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    if (grand) {
        document.getElementById('footNominal').textContent = 'Rp ' + Number(grand.total_nominal || 0).toLocaleString('id-ID');
        document.getElementById('footAdmin').textContent = 'Rp ' + Number(grand.total_biaya_admin || 0).toLocaleString('id-ID');
        document.getElementById('footTotal').textContent = 'Rp ' + Number(grand.total_pembayaran || 0).toLocaleString('id-ID');
        tfoot.style.display = 'table-footer-group';
    }
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    loadBankReport();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
