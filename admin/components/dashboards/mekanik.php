<!-- =============================================================
     DASHBOARD KHUSUS ROLE MEKANIK / PEMOHON
     ============================================================= -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            Status Request Order
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/create.php" class="btn btn-sm btn-primary fw-semibold shadow-sm">
            <i class="bi bi-plus-lg me-1"></i>Buat RO Baru
        </a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-secondary-subtle d-flex align-items-center h-100">
                <div class="stat-icon flex-shrink-0 bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-file-earmark-text fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Draft Saya</div>
                    <div class="stat-value text-dark fs-4 fw-bold font-monospace" id="statDraft">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-warning-subtle d-flex align-items-center h-100">
                <div class="stat-icon flex-shrink-0 bg-warning-subtle text-warning-emphasis rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-clock-history fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Menunggu Persetujuan</div>
                    <div class="stat-value text-warning-emphasis fs-4 fw-bold font-monospace" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card bg-white p-3 rounded shadow-sm border border-success-subtle d-flex align-items-center h-100">
                <div class="stat-icon flex-shrink-0 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                </div>
                <div class="stat-details overflow-hidden">
                    <div class="stat-label text-truncate text-muted small fw-semibold">Selesai (Diterima)</div>
                    <div class="stat-value text-success fs-4 fw-bold font-monospace" id="statApproved">0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            Request Order Aktif (In Progress)
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-sm btn-outline-primary fw-semibold">
            Lihat Semua RO &rarr;
        </a>
    </div>

    <!-- Grid Card RO Khusus Mekanik -->
    <div class="row g-3" id="pendingRoGrid">
        <div class="col-12 text-center py-5 text-muted bg-white rounded-3 border">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat daftar Request Order...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const resStats = await apiRequest('/api/dashboard/stats.php');
        if (resStats && resStats.success && resStats.data && resStats.data.request_order) {
            const ro = resStats.data.request_order;
            document.getElementById('statDraft').textContent = ro.draft.toLocaleString('id-ID');
            document.getElementById('statSubmitted').textContent = (ro.submitted + ro.processing).toLocaleString('id-ID'); // Menunggu + Proses
            document.getElementById('statApproved').textContent = ro.approved.toLocaleString('id-ID');
        }

        const grid = document.getElementById('pendingRoGrid');
        const resPending = await apiRequest('/api/dashboard/pending_ro.php');
        if (!resPending || !resPending.success || !Array.isArray(resPending.data) || resPending.data.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted bg-white rounded-3 border">
                    
                    <h6 class="fw-bold text-dark mb-1">Semua Request Order Telah Selesai Diterima!</h6>
                    <p class="small text-muted mb-0">Tidak ada Request Order aktif yang belum diproses atau belum diterima.</p>
                </div>
            `;
            return;
        }

        let html = '';
        resPending.data.forEach(ro => {
            const noRo = escapeHtml(ro.nomor || '-');
            const tgl = ro.tanggal_ro ? ro.tanggal_ro.split(' ')[0] : '-';
            const peminta = escapeHtml(ro.nama_karyawan || 'Peminta');
            const site = escapeHtml(ro.nama_site || '-');
            const totalItem = parseInt(ro.total_item) || 0;
            const totalQty = parseFloat(ro.total_qty) || 0;
            const status = ro.status || 'DRAFT';
            const isUrgent = (ro.prioritas === 'URGENT' || ro.prioritas === 'TINGGI');

            let badgeHtml = '';
            if (status === 'DRAFT') {
                badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-pencil me-1"></i>Draft</span>';
            } else if (status === 'TERKIRIM') {
                badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Menunggu Logistik</span>';
            } else if (status === 'DISETUJUI LOGISTIK') {
                badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-cart-plus me-1"></i>Menunggu Purchasing</span>';
            } else if (status === 'DISETUJUI PURCHASING') {
                badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>PO Terbit</span>';
            } else {
                badgeHtml = `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
            }

            html += `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-3 hover-opacity" style="border-top: 3px solid #1e5288 !important;">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="font-monospace fw-bold text-primary fs-6">${noRo}</span>
                                    <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                                        <i class="bi bi-calendar-event me-1"></i>${tgl}
                                    </div>
                                </div>
                                <div>
                                    ${badgeHtml}
                                </div>
                            </div>
                            <div class="bg-light rounded-3 p-2 my-2 small">
                                <div class="d-flex align-items-center mb-1 text-truncate">
                                    <i class="bi bi-person-fill text-muted me-2"></i>
                                    <strong class="text-dark me-1">${peminta}</strong>
                                </div>
                                <div class="d-flex align-items-center text-truncate">
                                    <i class="bi bi-geo-alt-fill text-muted me-2"></i>
                                    <span class="text-secondary">${site}</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-muted mb-3 mt-1">
                                <div>
                                    <i class="bi bi-boxes me-1 text-primary"></i>
                                    <span class="fw-semibold text-dark">${totalItem}</span> Item (${totalQty} qty)
                                </div>
                                ${isUrgent ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>` : ''}
                                ${ro.nomor_po ? `<span class="badge bg-light text-primary border font-monospace" style="font-size: 0.7rem;">PO: ${escapeHtml(ro.nomor_po)}</span>` : ''}
                            </div>
                            <div class="mt-auto pt-2 border-top d-flex justify-content-end">
                                <a href="<?= BASE_URL ?>/admin/pages/request_order/edit.php?id=${ro.id_request}" class="btn btn-outline-primary btn-sm px-3 py-1 fw-semibold w-100" style="font-size: 0.8rem;">
                                    Lihat Rincian RO &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        grid.innerHTML = html;

    } catch (err) {
        console.error('Error loading mekanik dashboard stats:', err);
    }
});
</script>
