<?php
/**
 * Komponen Universal Attachment Dokumen (PT Jaya Teknis)
 * Dapat di-include di Form Create/Edit atau Detail Modal Transaksi manapun.
 * 
 * Penggunaan:
 * <?php 
 *   $attachmentModule = 'PURCHASE'; // 'REQUEST','PURCHASE','RECEIVING','RETUR PO','FAKTUR PO','PAYMENT PO','MUTASI BARANG','ADJUSTMENT STOK'
 *   $attachmentModuleClean = 'PURCHASE';
 *   include __DIR__ . '/../../components/attachment_card.php';
 * ?>
 */
$moduleKey = $attachmentModuleClean ?? 'DEFAULT';
?>

<!-- Universal Attachment Manager Section -->
<div class="attachment-manager-wrapper" id="attachmentManagerWrapper_<?= $moduleKey ?>">
    <!-- Card Container -->
    <div class="card border rounded-3 shadow-xs bg-white mb-3">
        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-paperclip text-primary fs-5"></i>
                <span class="fw-bold text-dark small text-uppercase">Lampiran Berkas / File Dokumen</span>
                <span class="badge bg-primary rounded-pill px-2" id="badgeTotalLampiran_<?= $moduleKey ?>">0</span>
            </div>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-xs" onclick="openModalUploadDokumen('<?= $attachmentModule ?? '' ?>', '<?= $moduleKey ?>')">
                <i class="bi bi-plus-circle me-1"></i> Tambah Lampiran
            </button>
        </div>
        <div class="card-body p-3">
            <!-- Table / List Lampiran -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0" id="tableDokumenList_<?= $moduleKey ?>" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th style="min-width: 200px;">Nama / Label Dokumen</th>
                            <th style="width: 140px;">Tipe Berkas</th>
                            <th style="width: 110px;">Tanggal</th>
                            <th style="width: 150px;">Diupload Oleh</th>
                            <th class="text-center" style="width: 80px;">Unduh</th>
                            <th class="text-center" style="width: 110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDokumenList_<?= $moduleKey ?>">
                        <tr>
                            <td colspan="7" class="text-center py-3 text-muted small">
                                <i class="bi bi-folder2-open d-block fs-3 mb-1 text-secondary opacity-50"></i>
                                Belum ada berkas lampiran.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
