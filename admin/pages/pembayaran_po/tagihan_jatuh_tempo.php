<?php
/**
 * Halaman Monitoring Tagihan Jatuh Tempo (Accounts Payable)
 * Path: admin/pages/pembayaran_po/tagihan_jatuh_tempo.php
 * Khusus Role: FINANCE, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Daftar Tagihan';
$pageHeading = 'Daftar Tagihan';

// Ambil list tahun unik dari tanggal_jatuh_tempo untuk dropdown filter tahun
$tahunList = [];
$qTahun = $conn->query("SELECT DISTINCT YEAR(tanggal_jatuh_tempo) AS thn FROM faktur_po WHERE tanggal_jatuh_tempo IS NOT NULL AND sisa_tagihan > 0 ORDER BY thn DESC");
if ($qTahun) {
    while ($rowTh = $qTahun->fetch_assoc()) {
        if (!empty($rowTh['thn'])) {
            $tahunList[] = (int)$rowTh['thn'];
        }
    }
}
$currentYear = (int)date('Y');
if (!in_array($currentYear, $tahunList)) {
    array_unshift($tahunList, $currentYear);
}

// Daftar Bank Standar (sama dengan tab 4 Catat Pembayaran PO) & Bank yang ada di faktur
$bankList = ['BCA', 'Bank Mandiri', 'BRI', 'BNI', 'CIMB Niaga', 'BSI', 'Bank Danamon', 'Bank Permata', 'CASH', 'QRIS'];
$qBank = $conn->query("SELECT DISTINCT nama_bank FROM faktur_po WHERE nama_bank IS NOT NULL AND nama_bank != '' ORDER BY nama_bank ASC");
if ($qBank) {
    while ($rB = $qBank->fetch_assoc()) {
        $bName = trim($rB['nama_bank']);
        if (!empty($bName) && !in_array($bName, $bankList)) {
            $bankList[] = $bName;
        }
    }
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
/* Kontrol Form & Filter Standar */
.form-control,
.form-select,
.input-group > .form-control,
.input-group > .btn,
.input-group > .input-group-text {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 0.875rem !important;
}

.table-hover tbody tr:hover {
    background-color: #f8fafc;
}

.cursor-pointer {
    cursor: pointer;
}

.filter-bar {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
</style>

<div class="container-fluid px-0">

    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">Daftar Tagihan</h4>
            
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-warning btn-sm shadow-sm fw-semibold" id="btnSendH3Reminder" onclick="triggerH3ReminderEmail()" title="Kirim Email Peringatan Tagihan Mendekati Jatuh Tempo (H-3) & Lewat Tempo ke Tim Finance">
                <i class="bi bi-envelope-exclamation me-1"></i> Kirim Peringatan (H-3 &amp; Lewat Tempo)
            </button>
            <button type="button" class="btn btn-light border btn-sm shadow-sm" onclick="loadTagihanData()" title="Refresh">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- FILTER BAR: SEARCH VENDOR, NAMA BANK, BULAN & TAHUN -->
    <div class="filter-bar">
        <div class="row g-2 align-items-center">
            <!-- 1. Search Nama Vendor (Tanpa Icon) -->
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" id="filterVendor" 
                           placeholder="Ketik nama perusahaan vendor..." oninput="handleSearchInput()">
                    <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" onclick="clearVendorSearch()" style="display: none;" title="Hapus Pencarian">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <!-- 2. Filter Bank (Sesuai Bank Asal / Kas Pengirim) -->
            <div class="col-6 col-md-3">
                <select class="form-select" id="filterBank" onchange="loadTagihanData()">
                    <option value="">Semua Bank</option>
                    <?php foreach ($bankList as $bk): ?>
                        <option value="<?= htmlspecialchars($bk) ?>"><?= htmlspecialchars($bk === 'CASH' ? 'CASH / TUNAI' : $bk) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 3. Filter Bulan -->
            <div class="col-6 col-md-2">
                <select class="form-select" id="filterBulan" onchange="loadTagihanData()">
                    <option value="">Semua Bulan</option>
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

            <!-- 4. Filter Tahun -->
            <div class="col-6 col-md-2">
                <select class="form-select" id="filterTahun" onchange="loadTagihanData()">
                    <option value="">Semua Tahun</option>
                    <?php foreach ($tahunList as $th): ?>
                        <option value="<?= $th ?>" <?= ($th == $currentYear) ? 'selected' : '' ?>><?= $th ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 5. Tombol Reset (Hanya Icon) -->
            <div class="col-6 col-md-1">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center" onclick="resetFilter()" title="Reset Filter" style="height: 38px;">
                    <i class="bi bi-arrow-counterclockwise fs-6"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- TABEL DATA TAGIHAN JATUH TEMPO -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tableTagihan">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="min-width: 170px;">Nomor Faktur</th>
                        <th style="width: 135px;" class="text-center">Jatuh Tempo</th>
                        <th style="width: 130px;" class="text-center">Sisa Hari</th>
                        <th style="min-width: 220px;">Vendor</th>
                        <th style="min-width: 200px;">Rekening Bank</th>
                        <th style="width: 160px;" class="text-end">Sisa Tagihan</th>
                        <th style="width: 130px;" class="text-center">Keterangan</th>
                        <th style="width: 90px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tagihanTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Memuat data tagihan jatuh tempo...
                        </td>
                    </tr>
                </tbody>

                <!-- TFOOT: GRAND TOTAL SISA TAGIHAN DIBAWAH TABEL DENGAN TOMBOL CETAK -->
                <tfoot class="table-light border-top-2">
                    <tr class="fw-bold align-middle">
                        <td colspan="5" class="py-2.5 ps-3 text-start">
                            <button type="button" class="btn btn-outline-primary btn-sm px-3 py-1.5 fw-semibold d-inline-flex align-items-center shadow-xs rounded-2" onclick="openPrintDaftarTagihan()" title="Cetak Lampiran Transfer Bank Daftar Tagihan">
                                <i class="bi bi-printer me-2"></i>Cetak
                            </button>
                        </td>
                        <td class="text-end text-uppercase text-secondary small py-2.5" style="white-space: nowrap;">
                            Total Sisa Tagihan:
                        </td>
                        <td class="text-end font-monospace text-primary fs-6 py-2.5" id="tfootGrandTotal">
                            Rp 0
                        </td>
                        <td colspan="2" class="py-2.5 text-center small text-muted font-monospace" id="tfootCountText">
                            0 Faktur
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<script>
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
    loadTagihanData();
});

function handleSearchInput() {
    const val = document.getElementById('filterVendor').value.trim();
    const btnClear = document.getElementById('btnClearSearch');
    if (btnClear) {
        btnClear.style.display = val ? 'block' : 'none';
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadTagihanData();
    }, 350);
}

function clearVendorSearch() {
    document.getElementById('filterVendor').value = '';
    document.getElementById('btnClearSearch').style.display = 'none';
    loadTagihanData();
}

function resetFilter() {
    document.getElementById('filterVendor').value = '';
    document.getElementById('btnClearSearch').style.display = 'none';
    document.getElementById('filterBank').value = '';
    document.getElementById('filterBulan').value = '';
    document.getElementById('filterTahun').value = '<?= $currentYear ?>';
    loadTagihanData();
}

async function loadTagihanData() {
    const tbody = document.getElementById('tagihanTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Memuat data tagihan...
            </td>
        </tr>`;

    const vendor = document.getElementById('filterVendor').value.trim();
    const bank = document.getElementById('filterBank').value;
    const bulan = document.getElementById('filterBulan').value;
    const tahun = document.getElementById('filterTahun').value;

    const params = new URLSearchParams();
    if (vendor) params.append('vendor', vendor);
    if (bank) params.append('bank', bank);
    if (bulan) params.append('bulan', bulan);
    if (tahun) params.append('tahun', tahun);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/pembayaran_po/tagihan_jatuh_tempo.php?${params.toString()}`);
        const result = await res.json();

        if (!result.success) {
            throw new Error(result.message || 'Gagal memuat data.');
        }

        const data = result.data || {};
        const items = data.items || [];
        const grandTotal = parseFloat(data.grand_total_sisa_tagihan) || 0;
        const totalFaktur = parseInt(data.total_faktur) || 0;

        // Update Grand Total Footer
        document.getElementById('tfootGrandTotal').textContent = formatRupiah(grandTotal);
        document.getElementById('tfootCountText').textContent = `${totalFaktur} Faktur`;

        if (items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard-check fs-2 d-block mb-2 text-secondary"></i>
                        Tidak ada tagihan jatuh tempo yang cocok dengan filter saat ini.
                    </td>
                </tr>`;
            return;
        }

        let html = '';
        items.forEach((it, idx) => {
            const sisaHari = parseInt(it.sisa_hari);
            let badgeSisaHari = '';

            if (sisaHari < 0) {
                badgeSisaHari = `<span class="badge bg-danger text-white font-monospace px-2 py-1" title="Lewat ${Math.abs(sisaHari)} Hari">
                                    <i class="bi bi-exclamation-octagon me-1"></i>${sisaHari}
                                 </span>`;
            } else if (sisaHari === 0) {
                badgeSisaHari = `<span class="badge bg-danger text-white font-monospace px-2 py-1" title="Jatuh Tempo Hari Ini">
                                    <i class="bi bi-exclamation-triangle me-1"></i>H-0 (Hari Ini)
                                 </span>`;
            } else if (sisaHari <= 3) {
                badgeSisaHari = `<span class="badge bg-warning text-dark border border-warning font-monospace px-2 py-1" title="Mendekati Jatuh Tempo (H-${sisaHari})">
                                    <i class="bi bi-hourglass-split me-1"></i>H-${sisaHari} (${sisaHari} Hari)
                                 </span>`;
            } else if (sisaHari <= 7) {
                badgeSisaHari = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2 py-1">${sisaHari} Hari</span>`;
            } else {
                badgeSisaHari = `<span class="badge bg-success-subtle text-success border border-success-subtle font-monospace px-2 py-1">${sisaHari} Hari</span>`;
            }

            // Keterangan Status Pembayaran
            let badgeKeterangan = '';
            if (it.keterangan === 'BELUM LUNAS') {
                badgeKeterangan = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                      <i class="bi bi-pie-chart me-1"></i> BELUM LUNAS
                                   </span>`;
            } else {
                badgeKeterangan = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                      <i class="bi bi-hourglass-top me-1"></i> BELUM BAYAR
                                   </span>`;
            }

            // Info Rekening Bank
            let rekeningInfo = '';
            if (it.nomor_rekening && it.nomor_rekening !== '-') {
                rekeningInfo = `
                    <div class="fw-semibold text-dark small">${escapeHtml(it.nama_bank)} &bull; <span class="font-monospace">${escapeHtml(it.nomor_rekening)}</span></div>
                    <div class="text-muted small" style="font-size: 0.76rem;">a/n ${escapeHtml(it.atas_nama_rekening)}</div>
                `;
            } else {
                rekeningInfo = `<span class="text-muted small font-monospace">${escapeHtml(it.nama_bank || '-')}</span>`;
            }

            // Tanggal Jatuh Tempo format Indo
            const tglTempo = formatIndoDate(it.tanggal_jatuh_tempo);

            html += `
            <tr>
                <td class="text-center font-monospace text-muted small">${idx + 1}</td>
                <td>
                    <div class="fw-bold font-monospace text-primary">${escapeHtml(it.nomor_faktur)}</div>
                </td>
                <td class="text-center font-monospace small">${tglTempo}</td>
                <td class="text-center">${badgeSisaHari}</td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(it.nama_perusahaan)}</div>
                </td>
                <td>${rekeningInfo}</td>
                <td class="text-end font-monospace fw-bold text-dark">
                    ${formatRupiah(it.sisa_tagihan)}
                </td>
                <td class="text-center">${badgeKeterangan}</td>
                <td class="text-center">
                    <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/create.php?id_faktur=${it.id_faktur}" 
                       class="btn btn-sm btn-primary py-1 px-2 d-inline-flex align-items-center justify-content-center" 
                       title="Catat Pembayaran Faktur ${escapeHtml(it.nomor_faktur)}">
                        <i class="bi bi-cash-stack"></i>
                    </a>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;

    } catch (err) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle fs-3 d-block mb-1"></i>
                    Gagal memuat data tagihan: ${escapeHtml(err.message)}
                </td>
            </tr>`;
    }
}

function formatRupiah(number) {
    const val = parseFloat(number) || 0;
    return 'Rp ' + val.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

function formatIndoDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const dd = String(d.getDate()).padStart(2, '0');
    return `${dd} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function getFilterQueryParams() {
    const vendor = document.getElementById('filterVendor').value.trim();
    const bank = document.getElementById('filterBank').value;
    const bulan = document.getElementById('filterBulan').value;
    const tahun = document.getElementById('filterTahun').value;

    const params = new URLSearchParams();
    if (vendor) params.append('vendor', vendor);
    if (bank) params.append('bank', bank);
    if (bulan) params.append('bulan', bulan);
    if (tahun) params.append('tahun', tahun);

    return params.toString();
}

function openPrintDaftarTagihan() {
    const qs = getFilterQueryParams();
    const url = `<?= BASE_URL ?>/admin/pages/pembayaran_po/print_daftar_tagihan.php?${qs}`;
    window.open(url, '_blank');
}

/**
 * Memicu pengiriman email peringatan H-3 tagihan jatuh tempo ke Tim Finance
 */
async function triggerH3ReminderEmail() {
    const btn = document.getElementById('btnSendH3Reminder');
    
    // Konfirmasi via SweetAlert2 jika ada, atau confirm default
    if (typeof Swal !== 'undefined') {
        const confirmRes = await Swal.fire({
            title: 'Kirim Peringatan Email ke Finance?',
            text: 'Sistem akan memeriksa seluruh tagihan vendor yang mendekati jatuh tempo (H-3 atau kurang) serta tagihan yang telah lewat jatuh tempo (Overdue), lalu mengirimkan notifikasi email kompilasi ke Tim Finance.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0284c7',
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="bi bi-send me-1"></i> Ya, Kirim Email',
            cancelButtonText: 'Batal'
        });
        if (!confirmRes.isConfirmed) return;
    } else {
        if (!confirm('Kirim email peringatan tagihan mendekati jatuh tempo (H-3) & lewat jatuh tempo ke Tim Finance?')) return;
    }

    const originalBtnHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Mengirim...';
    }

    try {
        const response = await fetch('<?= BASE_URL ?>/api/notification/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'due_bills_reminder',
                h_days: 3
            })
        });

        const result = await response.json();

        if (result.success) {
            const data = result.data || {};
            const dueCount = data.due_count || 0;
            const overdueCount = data.overdue_count || 0;
            const approachingCount = data.approaching_count || 0;
            const sentCount = data.sent_count || 0;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Berhasil Terkirim!',
                    text: result.message || `Peringatan berhasil dikirim ke ${sentCount} personil Finance (${approachingCount} mendekati tempo, ${overdueCount} lewat tempo).`,
                    icon: 'success',
                    confirmButtonColor: '#0284c7'
                });
            } else {
                alert(result.message || 'Peringatan berhasil dikirim.');
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Informasi / Perhatian',
                    text: result.message || 'Gagal mengirim email pengingat.',
                    icon: 'warning',
                    confirmButtonColor: '#0284c7'
                });
            } else {
                alert(result.message || 'Gagal mengirim email pengingat.');
            }
        }
    } catch (err) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Kesalahan Sistem',
                text: 'Terjadi kegagalan jaringan: ' + err.message,
                icon: 'error',
                confirmButtonColor: '#0284c7'
            });
        } else {
            alert('Terjadi kegagalan jaringan: ' + err.message);
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalBtnHtml;
        }
    }
}
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
