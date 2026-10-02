<!-- =============================================================
     DASHBOARD KHUSUS ROLE MANAGER (EKSEKUTIF)
     ============================================================= -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            Executive Overview
        </h5>
    </div>

    <!-- KPI Operasional RO -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-secondary-subtle d-flex align-items-center h-100 hover-opacity" onclick="window.location.href='<?= BASE_URL ?>/admin/pages/request_order/index.php'">
                <div class="stat-icon flex-shrink-0 bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-file-earmark-text fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Total RO Draft</div>
                    <div class="stat-value text-dark fs-4 fw-bold font-monospace" id="statDraft">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-warning-subtle d-flex align-items-center h-100 hover-opacity" onclick="window.location.href='<?= BASE_URL ?>/admin/pages/request_order/index.php'">
                <div class="stat-icon flex-shrink-0 bg-warning-subtle text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">RO Menunggu</div>
                    <div class="stat-value text-warning-emphasis fs-4 fw-bold font-monospace" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-info-subtle d-flex align-items-center h-100 hover-opacity" onclick="window.location.href='<?= BASE_URL ?>/admin/pages/request_order/index.php'">
                <div class="stat-icon flex-shrink-0 bg-info-subtle text-info-emphasis rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-gear-wide-connected fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">RO Sedang Diproses</div>
                    <div class="stat-value text-info-emphasis fs-4 fw-bold font-monospace" id="statProcessing">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-success-subtle d-flex align-items-center h-100 hover-opacity" onclick="window.location.href='<?= BASE_URL ?>/admin/pages/request_order/index.php'">
                <div class="stat-icon flex-shrink-0 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">RO Selesai</div>
                    <div class="stat-value text-success fs-4 fw-bold font-monospace" id="statApproved">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Keuangan Eksekutif -->
    <h5 class="fs-6 fw-bold text-dark mb-3 mt-4">
        Ringkasan Keuangan &amp; Tagihan
    </h5>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-danger-subtle d-flex align-items-center h-100">
                <div class="stat-icon flex-shrink-0 bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-receipt fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Total Hutang Usaha</div>
                    <div class="stat-value text-danger fs-5 fw-bold font-monospace text-truncate" id="statHutangUsaha">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-warning-subtle d-flex align-items-center h-100">
                <div class="stat-icon flex-shrink-0 bg-warning-subtle text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Jatuh Tempo Mendekat</div>
                    <div class="stat-value text-warning-emphasis fs-5 fw-bold font-monospace text-truncate" id="statJatuhTempo">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-success-subtle d-flex align-items-center h-100">
                <div class="stat-icon flex-shrink-0 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-cash-coin fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Kas Keluar (Bulan Ini)</div>
                    <div class="stat-value text-success fs-5 fw-bold font-monospace text-truncate" id="statKasKeluar">
                        <span class="spinner-border spinner-border-sm text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fs-6 fw-bold text-dark">
             Pemantauan Request Order Terbaru
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-sm btn-outline-primary fw-semibold">
            Lihat Semua RO &rarr;
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-custom mb-0 align-middle">
                <thead class="table-light small text-muted text-uppercase">
                    <tr>
                        <th style="width: 140px;">No. RO</th>
                        <th style="width: 110px;">Tanggal</th>
                        <th>Peminta</th>
                        <th>Site / Workshop</th>
                        <th>Vendor Referensi</th>
                        <th style="width: 180px;" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="recentRoBody">
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Request Order...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Load RO Stats
        const resStats = await apiRequest('/api/dashboard/stats.php');
        if (resStats && resStats.success && resStats.data && resStats.data.request_order) {
            const ro = resStats.data.request_order;
            document.getElementById('statDraft').textContent = ro.draft.toLocaleString('id-ID');
            document.getElementById('statSubmitted').textContent = ro.submitted.toLocaleString('id-ID');
            document.getElementById('statProcessing').textContent = ro.processing.toLocaleString('id-ID');
            document.getElementById('statApproved').textContent = ro.approved.toLocaleString('id-ID');
        }

        // Load Finance KPI
        const resFinance = await apiRequest('/api/dashboard/finance_stats.php');
        if (resFinance && resFinance.success && resFinance.data && resFinance.data.kpi) {
            const kpi = resFinance.data.kpi;
            document.getElementById('statHutangUsaha').textContent = 'Rp ' + Number(kpi.faktur_belum_lunas_nominal).toLocaleString('id-ID');
            document.getElementById('statJatuhTempo').textContent = 'Rp ' + Number(kpi.jatuh_tempo_nominal).toLocaleString('id-ID');
            document.getElementById('statKasKeluar').textContent = 'Rp ' + Number(kpi.cash_out_month_nominal).toLocaleString('id-ID');
        }

        // Load Recent RO
        const tbody = document.getElementById('recentRoBody');
        const resRecent = await apiRequest('/api/request_order/index.php?limit=8');
        
        if (!resRecent || !resRecent.success || !resRecent.data || !Array.isArray(resRecent.data.items) || resRecent.data.items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Belum ada data Request Order.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        resRecent.data.items.forEach(ro => {
            const noRo = escapeHtml(ro.nomor || '-');
            const tgl = ro.tanggal_ro ? ro.tanggal_ro.split(' ')[0] : '-';
            const peminta = escapeHtml(ro.nama_karyawan || '-');
            const site = escapeHtml(ro.nama_site || '-');
            const vendor = escapeHtml(ro.nama_vendor || '-');
            const status = ro.status || 'DRAFT';
            const detailUrl = `<?= BASE_URL ?>/admin/pages/request_order/edit.php?id=${ro.id_request}`;

            let badgeHtml = '';
            if (status === 'DRAFT') {
                badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Draft</span>';
            } else if (status === 'TERKIRIM') {
                badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Menunggu Logistik</span>';
            } else if (status === 'DISETUJUI LOGISTIK') {
                badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">Menunggu Purchasing</span>';
            } else if (status === 'DISETUJUI PURCHASING') {
                badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">PO Terbit</span>';
            } else if (status === 'DITERIMA FULL') {
                badgeHtml = '<span class="badge bg-success text-white border px-2 py-1">Diterima Full</span>';
            } else if (status === 'DITERIMA SEBAGIAN') {
                badgeHtml = '<span class="badge bg-warning text-dark border px-2 py-1">Diterima Sebagian</span>';
            } else {
                badgeHtml = `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
            }

            html += `
                <tr>
                    <td>
                        <a href="${detailUrl}" class="font-monospace fw-bold text-primary text-decoration-none" title="Buka Detail ${noRo}">
                            ${noRo}
                        </a>
                    </td>
                    <td class="small text-muted font-monospace">${tgl}</td>
                    <td class="fw-semibold text-dark">${peminta}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${site}</span></td>
                    <td class="small text-muted">${vendor}</td>
                    <td class="text-center">
                        <a href="${detailUrl}" class="text-decoration-none d-inline-block hover-opacity" title="Buka Detail ${noRo}">
                            ${badgeHtml}
                        </a>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

    } catch (err) {
        console.error('Error loading manager dashboard stats:', err);
    }
});
</script>
