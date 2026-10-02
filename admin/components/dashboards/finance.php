<!-- 1. 4 KARTU METRIK UTAMA (ALIGNED & CONSISTENT) -->
<div class="row g-3 mb-4">
    <!-- 1. Total Hutang Usaha (Outstanding AP) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon primary flex-shrink-0">
                <i class="bi bi-receipt"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-truncate">Hutang Usaha</div>
                <div class="stat-value text-primary fs-5 fw-bold font-monospace text-truncate" id="statFinanceFakturNominal">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-muted text-truncate" id="statFinanceFakturCount" style="font-size: 0.75rem;">
                    - Faktur Aktif
                </div>
            </div>
        </a>
    </div>

    <!-- 2. Tagihan Mendesak / Jatuh Tempo -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon danger flex-shrink-0">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-danger text-truncate">Jatuh Tempo (&le; 7 Hari)</div>
                <div class="stat-value text-danger fs-5 fw-bold font-monospace text-truncate" id="statFinanceDueNominal">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-danger text-truncate" id="statFinanceDueCount" style="font-size: 0.75rem;">
                    - Perlu Dibayar
                </div>
            </div>
        </a>
    </div>

    <!-- 3. Realisasi Kas Keluar Bulan Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon success flex-shrink-0">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-truncate">Kas Keluar (Bulan Ini)</div>
                <div class="stat-value text-success fs-5 fw-bold font-monospace text-truncate" id="statFinanceCashOutNominal">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-muted text-truncate" id="statFinanceCashOutCount" style="font-size: 0.75rem;">
                    - Transaksi Bayar
                </div>
            </div>
        </a>
    </div>

    <!-- 4. PO Disetujui (Pipeline Tagihan Masuk) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= BASE_URL ?>/admin/pages/purchase_order/index.php" class="stat-card text-decoration-none h-100 d-flex align-items-center">
            <div class="stat-icon info flex-shrink-0">
                <i class="bi bi-file-earmark-check-fill"></i>
            </div>
            <div class="stat-details overflow-hidden">
                <div class="stat-label text-truncate">PO Menunggu Faktur</div>
                <div class="stat-value text-info fs-5 fw-bold font-monospace text-truncate" id="statFinancePoWaiting">
                    <span class="spinner-border spinner-border-sm text-muted"></span>
                </div>
                <div class="small text-muted text-truncate" style="font-size: 0.75rem;">
                    PO Disetujui
                </div>
            </div>
        </a>
    </div>
</div>

<!-- 2. DUA KONTEN UTAMA (CLEAN, MINIMALIST & TANPA ICON BERLEBIH) -->
<div class="row g-3 mb-4">
    <!-- KIRI (65%): TAB PRIORITAS TAGIHAN & HUTANG VENDOR -->
    <div class="col-12 col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <!-- Nav Tabs Minimalis (Clean Text Tanpa Icon) -->
                <ul class="nav nav-pills card-header-pills small" id="financeTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1.5 px-3 fw-semibold rounded-pill" id="tab-urgent-tab" data-bs-toggle="pill" data-bs-target="#tab-urgent" type="button" role="tab" aria-controls="tab-urgent" aria-selected="true">
                            Tagihan Jatuh Tempo
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1.5 px-3 fw-semibold rounded-pill" id="tab-vendor-tab" data-bs-toggle="pill" data-bs-target="#tab-vendor" type="button" role="tab" aria-controls="tab-vendor" aria-selected="false">
                            Top 5 Hutang Vendor
                        </button>
                    </li>
                </ul>

                <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/tagihan_jatuh_tempo.php" class="btn btn-outline-primary btn-sm px-2.5 py-1 rounded-2 shadow-xs fw-semibold" style="font-size: 0.78rem;">
                    Jadwal Lengkap &rarr;
                </a>
            </div>

            <div class="card-body p-0">
                <div class="tab-content" id="financeTabContent">
                    <!-- TAB 1: TAGIHAN JATUH TEMPO -->
                    <div class="tab-pane fade show active" id="tab-urgent" role="tabpanel" aria-labelledby="tab-urgent-tab">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th>No. Faktur</th>
                                        <th>Vendor</th>
                                        <th class="text-center">Jatuh Tempo</th>
                                        <th class="text-end">Sisa Tagihan</th>
                                        <th class="text-center" style="width: 75px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="financeUrgentTagihanBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat tagihan jatuh tempo...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: TOP 5 VENDOR -->
                    <div class="tab-pane fade" id="tab-vendor" role="tabpanel" aria-labelledby="tab-vendor-tab">
                        <div class="table-responsive">
                            <table class="table table-hover table-custom align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th>Nama Vendor</th>
                                        <th class="text-center" style="width: 100px;">Jml Faktur</th>
                                        <th class="text-end">Total Tagihan</th>
                                        <th class="text-end">Sisa Hutang</th>
                                    </tr>
                                </thead>
                                <tbody id="financeTopVendorsBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data vendor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

<script>
async function loadFinanceDashboard() {
    try {
        const res = await apiRequest('/api/dashboard/finance_stats.php');
        if (!res || !res.success || !res.data) return;

        const { kpi, cash_out_by_bank, top_vendors, urgent_tagihan, recent_payments } = res.data;

        // 1. KPI Cards
        const elPoWaiting = document.getElementById('statFinancePoWaiting');
        const elFakturCount = document.getElementById('statFinanceFakturCount');
        const elFakturNom = document.getElementById('statFinanceFakturNominal');
        const elDueCount = document.getElementById('statFinanceDueCount');
        const elDueNom = document.getElementById('statFinanceDueNominal');
        const elCashOutNom = document.getElementById('statFinanceCashOutNominal');
        const elCashOutCount = document.getElementById('statFinanceCashOutCount');

        if (elPoWaiting) elPoWaiting.textContent = kpi.po_waiting_invoice.toLocaleString('id-ID') + ' PO';
        if (elFakturCount) elFakturCount.textContent = kpi.faktur_belum_lunas_count.toLocaleString('id-ID') + ' Faktur Aktif';
        if (elFakturNom) elFakturNom.textContent = 'Rp ' + Number(kpi.faktur_belum_lunas_nominal).toLocaleString('id-ID');
        if (elDueCount) elDueCount.textContent = kpi.jatuh_tempo_count.toLocaleString('id-ID') + ' Faktur Perlu Dibayar';
        if (elDueNom) elDueNom.textContent = 'Rp ' + Number(kpi.jatuh_tempo_nominal).toLocaleString('id-ID');
        if (elCashOutNom) elCashOutNom.textContent = 'Rp ' + Number(kpi.cash_out_month_nominal).toLocaleString('id-ID');
        if (elCashOutCount) elCashOutCount.textContent = kpi.cash_out_month_count.toLocaleString('id-ID') + ' Transaksi Bayar';

        // 2. Urgent Tagihan Jatuh Tempo Terdekat
        const urgentTbody = document.getElementById('financeUrgentTagihanBody');
        if (urgentTbody) {
            if (!urgent_tagihan || urgent_tagihan.length === 0) {
                urgentTbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted small">
                            Tidak ada tagihan yang mendekati jatuh tempo.
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                urgent_tagihan.forEach(it => {
                    const sisaHari = parseInt(it.sisa_hari);
                    let badgeHari = '';
                    if (sisaHari < 0) {
                        badgeHari = `<span class="badge bg-danger text-white">Lewat ${Math.abs(sisaHari)} hr</span>`;
                    } else if (sisaHari === 0) {
                        badgeHari = `<span class="badge bg-danger text-white">Hari Ini</span>`;
                    } else if (sisaHari <= 3) {
                        badgeHari = `<span class="badge bg-warning text-dark">${sisaHari} hr lagi</span>`;
                    } else {
                        badgeHari = `<span class="badge bg-light text-secondary border">${sisaHari} hr lagi</span>`;
                    }

                    const tglDue = it.tanggal_jatuh_tempo ? it.tanggal_jatuh_tempo.split('-').reverse().join('/') : '-';
                    const bayarUrl = `<?= BASE_URL ?>/admin/pages/pembayaran_po/create.php?id_faktur=${it.id_faktur}`;

                    html += `
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/pages/faktur_po/edit.php?id=${it.id_faktur}" class="fw-bold text-primary text-decoration-none font-monospace">
                                    ${escapeHtml(it.nomor_faktur)}
                                </a>
                                ${it.nomor_faktur_vendor ? `<div class="text-muted small" style="font-size: 0.72rem;">Ref: ${escapeHtml(it.nomor_faktur_vendor)}</div>` : ''}
                            </td>
                            <td>
                                <strong class="text-dark">${escapeHtml(it.nama_vendor)}</strong>
                            </td>
                            <td class="text-center font-monospace">
                                <div>${tglDue}</div>
                                <div class="mt-0.5">${badgeHari}</div>
                            </td>
                            <td class="text-end font-monospace fw-bold text-danger">
                                Rp ${Number(it.sisa_tagihan).toLocaleString('id-ID')}
                            </td>
                            <td class="text-center">
                                <a href="${bayarUrl}" class="btn btn-outline-primary btn-sm px-2.5 py-1 rounded-2 shadow-xs fw-semibold" title="Bayar Faktur Ini" style="font-size: 0.75rem;">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    `;
                });
                urgentTbody.innerHTML = html;
            }
        }

        // 3. Top 5 Vendor Tagihan Terbesar
        const topVendorsTbody = document.getElementById('financeTopVendorsBody');
        if (topVendorsTbody) {
            if (!top_vendors || top_vendors.length === 0) {
                topVendorsTbody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted small">
                            Tidak ada hutang usaha aktif.
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                top_vendors.forEach(v => {
                    html += `
                        <tr>
                            <td>
                                <strong class="text-dark">${escapeHtml(v.nama_vendor)}</strong>
                            </td>
                            <td class="text-center font-monospace">${Number(v.total_faktur).toLocaleString('id-ID')} Faktur</td>
                            <td class="text-end font-monospace text-muted">Rp ${Number(v.total_tagihan).toLocaleString('id-ID')}</td>
                            <td class="text-end font-monospace fw-bold text-primary">Rp ${Number(v.total_sisa_tagihan).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                });
                topVendorsTbody.innerHTML = html;
            }
        }
    } catch (err) {
        console.error('Error loading finance dashboard:', err);
    }
}

document.addEventListener('DOMContentLoaded', loadFinanceDashboard);
</script>