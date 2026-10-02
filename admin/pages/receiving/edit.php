<?php
/**
 * Halaman Edit Penerimaan Barang (Receiving)
 * Path: admin/pages/receiving/edit.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 * Aturan: Hanya dapat diedit jika print != 1
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);
$idRcv = isset($_GET['id']) ? decodeId($_GET['id']) : (isset($_GET['id_rcv']) ? decodeId($_GET['id_rcv']) : 0);

if ($idRcv <= 0) {
    header("Location: " . BASE_URL . "/admin/pages/receiving/index.php");
    exit;
}

$pageTitle = 'Edit Penerimaan Barang';
$pageHeading = 'Edit Data Penerimaan Barang (SPB Vendor)';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Title -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">
                <i class="bi bi-pencil-square text-primary me-2"></i>Edit Penerimaan Barang
            </h4>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/pages/receiving/index.php" class="btn btn-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Penerimaan
            </a>
        </div>
    </div>

    <!-- Alert jika sudah diprint -->
    <div id="alertPrintedLock" class="alert alert-warning border-0 rounded-3 p-3 d-none align-items-center gap-3 mb-4 shadow-sm">
        <i class="bi bi-lock-fill fs-3 text-warning"></i>
        <div>
            <strong class="d-block text-dark">Dokumen Terkunci Permanen:</strong>
            <span class="small text-muted">Dokumen Penerimaan ini sudah dicetak (print). Seluruh data dan kuantitas stok telah termigrasi dan tidak dapat diubah kembali.</span>
        </div>
    </div>

    <!-- FORM EDIT DENGAN TAB NAVIGASI -->
    <form id="formEditReceiving" onsubmit="handleUpdateReceiving(event)">
        <input type="hidden" id="rcvId" value="<?= $idRcv ?>">
        
        <div class="card border-0 shadow-sm rounded-3">
            <!-- Nav Tabs Header -->
            <div class="card-header bg-white pt-3 pb-0 px-4 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="rcvFormTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="tab-info-utama" data-bs-toggle="tab" data-bs-target="#pane-info-utama" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-2 text-primary"></i>1. Dokumen &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark" id="tab-verifikasi-material" data-bs-toggle="tab" data-bs-target="#pane-verifikasi-material" type="button" role="tab">
                            <i class="bi bi-boxes me-2 text-primary"></i>2. Verifikasi Fisik Material &amp; QC
                            <span class="badge bg-primary text-white ms-2" id="tabItemCountBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="rcvFormTabContent">
                    
                    <!-- TAB 1: INFORMASI DOKUMEN & VENDOR (LAYOUT 2 KOLOM) -->
                    <div class="tab-pane fade show active" id="pane-info-utama" role="tabpanel">
                        <div class="row g-4">
                            
                            <!-- KOLOM KIRI: IDENTITAS DOKUMEN & PO -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        <i class="bi bi-card-heading text-primary me-2"></i>Identitas Penerimaan &amp; PO
                                    </h6>
                                    
                                    <!-- Nomor RCV -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Nomor Receiving (RCV)</label>
                                        <input type="text" class="form-control font-monospace fw-bold text-primary bg-white" id="rcvNomor" readonly>
                                    </div>

                                    <!-- Referensi PO -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Referensi Purchase Order (PO)</label>
                                        <input type="text" class="form-control font-monospace bg-white" id="displayPo" readonly>
                                    </div>

                                    <!-- Tanggal Penerimaan Fisik -->
                                    <div class="mb-3">
                                        <label for="rcvTanggalDiterima" class="form-label small fw-bold text-dark">Tanggal Diterima Fisik <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-calendar-check"></i></span>
                                            <input type="date" class="form-control" id="rcvTanggalDiterima" required>
                                        </div>
                                    </div>

                                    <!-- Site Tujuan (Readonly) -->
                                    <div class="mb-2">
                                        <label class="form-label small text-muted">Site / Gudang Tujuan</label>
                                        <input type="text" class="form-control form-control-sm bg-white" id="displaySite" readonly value="-">
                                    </div>
                                </div>
                            </div>

                            <!-- KOLOM KANAN: SURAT JALAN & PETUGAS LOGISTIK -->
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark mb-3">
                                        <i class="bi bi-truck text-primary me-2"></i>Surat Jalan Vendor &amp; Petugas
                                    </h6>

                                    <!-- Nomor SPB / Surat Jalan Vendor -->
                                    <div class="mb-3">
                                        <label for="rcvNomorSj" class="form-label small fw-bold text-dark">No. Surat Pengantar Barang (SPB) / Surat Jalan Vendor <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white"><i class="bi bi-file-earmark-text"></i></span>
                                            <input type="text" class="form-control font-monospace fw-bold" id="rcvNomorSj" required>
                                        </div>
                                    </div>

                                    <!-- Vendor Pengirim (Readonly) -->
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Vendor Pengirim</label>
                                        <input type="text" class="form-control form-control-sm bg-white" id="displayVendor" readonly value="-">
                                    </div>

                                    <!-- Petugas Logistik Penerima -->
                                    <div class="mb-3">
                                        <label class="form-label small text-muted">Petugas Penerima (Logistik)</label>
                                        <input type="text" class="form-control form-control-sm bg-white fw-semibold" id="displayPetugas" readonly value="-">
                                    </div>

                                    <!-- Catatan Penerimaan -->
                                    <div class="mb-2">
                                        <label for="rcvKeterangan" class="form-label small fw-bold text-dark">Catatan / Keterangan Penerimaan</label>
                                        <textarea class="form-control form-control-sm" id="rcvKeterangan" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- TAB 2: VERIFIKASI FISIK MATERIAL & QC -->
                    <div class="tab-pane fade" id="pane-verifikasi-material" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">
                                Rincian Barang
                            </h6>
                            <span class="badge bg-secondary-subtle text-secondary" id="countItemDetailBadge">0 Item</span>
                        </div>

                        <div class="table-responsive border rounded-3 mb-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th style="width: 45px;" class="text-center align-middle">No</th>
                                        <th style="min-width: 220px;" class="align-middle">Nama Barang &amp; Kode</th>
                                        <th style="width: 100px;" class="text-center align-middle">Qty PO</th>
                                        <th style="width: 130px;" class="text-center text-success align-middle">Qty Baik (Passed) <span class="text-danger">*</span></th>
                                        <th style="width: 130px;" class="text-center text-danger align-middle">Qty Cacat / Rusak</th>
                                        <th style="width: 80px;" class="text-center align-middle">Satuan</th>
                                        <th style="width: 90px;" class="text-center align-middle">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody id="receivingItemTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER ACTION BUTTONS DENGAN TOMBOL UPLOAD SURAT JALAN -->
            <div class="card-footer bg-light p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <!-- Upload Surat Jalan Vendor (.pdf / .jpg, max 2MB) -->
                <div class="d-flex align-items-center gap-2">
                    <input type="file" id="inputUploadSj" accept=".pdf,.jpg,.jpeg,.png" style="display: none;" onchange="handleFileSjChange(event)">
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-xs" onclick="document.getElementById('inputUploadSj').click()" id="btnUploadSj">
                        <i class="bi bi-paperclip me-1"></i> Upload / Ganti Surat Jalan
                    </button>
                    <div id="fileSjPreviewBadge" class="d-none align-items-center gap-1 bg-white border rounded px-2 py-1 small">
                        <i class="bi bi-file-earmark-check text-success"></i>
                        <span class="font-monospace text-dark fw-semibold" id="fileSjName" style="font-size: 0.78rem;">file.pdf</span>
                        <span class="text-muted small" id="fileSjSize" style="font-size: 0.72rem;"></span>
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 ms-1" onclick="clearFileSj()" title="Hapus File"><i class="bi bi-x-circle-fill"></i></button>
                    </div>
                </div>

                <!-- Action Simpan -->
                <div>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSubmitReceiving">
                        <i class="bi bi-check2-circle me-1"></i> Perbarui Data Penerimaan
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL POP-UP CATATAN ITEM QC -->
<div class="modal fade" id="modalItemNote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-chat-left-text text-primary"></i>
                    <span>Catatan Kondisi / QC Material</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small text-muted">Nama Barang:</label>
                    <div class="fw-bold text-dark fs-6" id="modalNoteBarangNama">-</div>
                </div>
                <div class="mb-3">
                    <label for="modalNoteText" class="form-label small fw-bold text-dark">Keterangan / Catatan Fisik Barang:</label>
                    <textarea class="form-control" id="modalNoteText" rows="3" placeholder="Keterangan kondisi fisik..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" onclick="saveItemNoteFromModal()">
                    <i class="bi bi-check2 me-1"></i> Simpan Catatan
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<script>
const ID_RCV = <?= $idRcv ?>;
let modalItemNoteInstance = null;
let currentEditingItemIdx = null;
let selectedFileSj = null;
let isLocked = false;

document.addEventListener('DOMContentLoaded', async () => {
    modalItemNoteInstance = new bootstrap.Modal(document.getElementById('modalItemNote'));
    await loadReceivingData();
});

async function loadReceivingData() {
    const res = await apiRequest(`/api/receiving/index.php?id=${ID_RCV}`);
    if (!res || !res.success || !res.data) {
        showToast(res ? res.message : 'Gagal memuat data.', 'danger');
        return;
    }

    const rcv = res.data;
    document.getElementById('rcvNomor').value = rcv.nomor_rcv || '';
    document.getElementById('displayPo').value = rcv.nomor_po ? `[${rcv.nomor_po}] - [${rcv.nama_vendor || 'Vendor'}]` : '-';
    document.getElementById('rcvTanggalDiterima').value = rcv.tanggal_diterima ? rcv.tanggal_diterima.split(' ')[0] : (rcv.tanggal_rcv ? rcv.tanggal_rcv.split(' ')[0] : '');
    document.getElementById('displaySite').value = rcv.nama_site ? `${rcv.nama_site} (${rcv.kode_site || '-'})` : '-';
    document.getElementById('rcvNomorSj').value = rcv.nomor_sj || '';
    document.getElementById('displayVendor').value = rcv.nama_vendor || '-';
    document.getElementById('displayPetugas').value = rcv.nama_penerima || 'Petugas Logistik';
    document.getElementById('rcvKeterangan').value = rcv.catatan_rcv || '';

    if (rcv.file_sj) {
        document.getElementById('fileSjName').textContent = rcv.file_sj;
        document.getElementById('fileSjPreviewBadge').classList.remove('d-none');
        document.getElementById('fileSjPreviewBadge').classList.add('d-flex');
    }

    // Cek Kunci Status Print
    if (parseInt(rcv.print) === 1) {
        isLocked = true;
        document.getElementById('alertPrintedLock').classList.remove('d-none');
        document.getElementById('alertPrintedLock').classList.add('d-flex');
        document.getElementById('btnSubmitReceiving').disabled = true;
        document.getElementById('btnUploadSj').disabled = true;
        document.getElementById('rcvTanggalDiterima').readOnly = true;
        document.getElementById('rcvNomorSj').readOnly = true;
        document.getElementById('rcvKeterangan').readOnly = true;
    }

    const items = rcv.items || [];
    document.getElementById('tabItemCountBadge').textContent = items.length;
    document.getElementById('countItemDetailBadge').textContent = `${items.length} Item Barang`;

    const tbody = document.getElementById('receivingItemTableBody');
    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada item barang.</td></tr>';
        return;
    }

    let html = '';
    items.forEach((item, idx) => {
        const qtyPo = parseFloat(item.qty_po) || 0;
        const qtyRcv = parseFloat(item.qty_diterima) || 0;
        const statusQc = (item.status_qc !== undefined && item.status_qc !== null && item.status_qc !== '') ? parseInt(item.status_qc) : 1;
        const qtyBaik = (statusQc === 1) ? qtyRcv : 0;
        const qtyRusak = (statusQc === 0) ? qtyRcv : 0;
        const note = item.keterangan_item || '';
        const btnNoteClass = note ? 'btn-primary text-white' : 'btn-outline-secondary';
        const btnNoteIcon = note ? 'bi-chat-left-text-fill' : 'bi-chat-left-text';

        html += `
            <tr data-id-barang="${item.id_barang}" data-item-idx="${idx}">
                <td class="text-center font-monospace text-muted small">${idx + 1}</td>
                <td>
                    <div class="fw-bold text-dark item-nama-text">${escapeHtml(item.nama_barang || '')}</div>
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                        ${item.nama_kategori ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-tag me-1"></i>${escapeHtml(item.nama_kategori)}</span>` : ''}
                        ${item.nama_merk ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-bookmark me-1"></i>${escapeHtml(item.nama_merk)}</span>` : ''}
                    </div>
                </td>
                <td class="text-center font-monospace text-muted fw-bold">
                    <span class="item-qty-po">${qtyPo}</span>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-center font-monospace fw-bold item-qty-baik text-success" 
                           min="0" max="${qtyPo}" step="1" value="${qtyBaik}" ${isLocked ? 'readonly' : 'required'} oninput="validateQtyRow(this)">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-center font-monospace fw-bold item-qty-rusak text-danger" 
                           min="0" max="${qtyPo}" step="1" value="${qtyRusak}" ${isLocked ? 'readonly' : 'required'} oninput="validateQtyRow(this)">
                </td>
                <td class="text-center font-monospace small text-muted">
                    <span class="item-satuan">${escapeHtml(item.satuan || 'PCS')}</span>
                </td>
                <td class="text-center">
                    <input type="hidden" class="item-catatan-val" value="${escapeHtml(note)}">
                    <button type="button" class="btn ${btnNoteClass} btn-sm px-2 py-1 item-btn-note shadow-xs" onclick="openItemNoteModal(${idx})" title="Catatan QC">
                        <i class="bi ${btnNoteIcon}"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function handleFileSjChange(e) {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
        showToast('Ukuran file melebihi batas maksimal 2 MB.', 'danger');
        clearFileSj();
        return;
    }

    selectedFileSj = file;
    document.getElementById('fileSjName').textContent = file.name;
    document.getElementById('fileSjPreviewBadge').classList.remove('d-none');
    document.getElementById('fileSjPreviewBadge').classList.add('d-flex');
}

function clearFileSj() {
    selectedFileSj = null;
    document.getElementById('inputUploadSj').value = '';
    document.getElementById('fileSjPreviewBadge').classList.add('d-none');
    document.getElementById('fileSjPreviewBadge').classList.remove('d-flex');
}

function validateQtyRow(inputEl) {
    const tr = inputEl.closest('tr');
    const qtyPo = parseInt(tr.querySelector('.item-qty-po').textContent) || 0;
    const inpBaik = tr.querySelector('.item-qty-baik');
    const inpRusak = tr.querySelector('.item-qty-rusak');

    let vBaik = parseInt(inpBaik.value) || 0;
    let vRusak = parseInt(inpRusak.value) || 0;

    if (vBaik < 0) { vBaik = 0; inpBaik.value = 0; }
    if (vRusak < 0) { vRusak = 0; inpRusak.value = 0; }

    if (vBaik + vRusak > qtyPo) {
        showToast(`Total Qty (${vBaik + vRusak}) tidak boleh melebihi Qty PO (${qtyPo}).`, 'warning');
        if (inputEl === inpBaik) {
            inpBaik.value = Math.max(0, qtyPo - vRusak);
        } else {
            inpRusak.value = Math.max(0, qtyPo - vBaik);
        }
    }
}

function openItemNoteModal(idx) {
    currentEditingItemIdx = idx;
    const tr = document.querySelector(`tr[data-item-idx="${idx}"]`);
    if (!tr) return;

    const namaBarang = tr.querySelector('.item-nama-text').textContent;
    const existingNote = tr.querySelector('.item-catatan-val').value;

    document.getElementById('modalNoteBarangNama').textContent = namaBarang;
    document.getElementById('modalNoteText').value = existingNote;

    if (isLocked) {
        document.getElementById('modalNoteText').readOnly = true;
    }

    modalItemNoteInstance.show();
}

function saveItemNoteFromModal() {
    if (isLocked || currentEditingItemIdx === null) return;
    const tr = document.querySelector(`tr[data-item-idx="${currentEditingItemIdx}"]`);
    if (!tr) return;

    const note = document.getElementById('modalNoteText').value.trim();
    tr.querySelector('.item-catatan-val').value = note;

    const btn = tr.querySelector('.item-btn-note');
    if (note) {
        btn.className = 'btn btn-primary btn-sm px-2 py-1 item-btn-note shadow-xs text-white';
        btn.innerHTML = '<i class="bi bi-chat-left-text-fill"></i>';
    } else {
        btn.className = 'btn btn-outline-secondary btn-sm px-2 py-1 item-btn-note shadow-xs';
        btn.innerHTML = '<i class="bi bi-chat-left-text"></i>';
    }

    modalItemNoteInstance.hide();
    showToast('Catatan berhasil disimpan.', 'success');
}

async function handleUpdateReceiving(e) {
    e.preventDefault();
    if (isLocked) return;

    const nomorSj = document.getElementById('rcvNomorSj').value.trim();
    const tanggalDiterima = document.getElementById('rcvTanggalDiterima').value;
    const keterangan = document.getElementById('rcvKeterangan').value.trim();

    const trItems = document.querySelectorAll('#receivingItemTableBody tr[data-id-barang]');
    const itemsPayload = [];

    trItems.forEach(tr => {
        const idBarang = tr.getAttribute('data-id-barang');
        const qtyPo = parseInt(tr.querySelector('.item-qty-po').textContent) || 0;
        const qtyBaik = parseInt(tr.querySelector('.item-qty-baik').value) || 0;
        const qtyRusak = parseInt(tr.querySelector('.item-qty-rusak').value) || 0;
        const catatan = tr.querySelector('.item-catatan-val').value.trim();

        itemsPayload.push({
            id_barang: idBarang,
            qty_po: qtyPo,
            qty_baik: qtyBaik,
            qty_rusak: qtyRusak,
            catatan: catatan
        });
    });

    const formData = new FormData();
    formData.append('id_rcv', ID_RCV);
    formData.append('nomor_sj', nomorSj);
    formData.append('tanggal_diterima', tanggalDiterima);
    formData.append('keterangan', keterangan);
    formData.append('items', JSON.stringify(itemsPayload));

    if (selectedFileSj) {
        formData.append('file_sj', selectedFileSj);
    }

    const btnSubmit = document.getElementById('btnSubmitReceiving');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan Perubahan...';

    const res = await apiRequest('/api/receiving/update.php', {
        method: 'POST',
        body: formData
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = '<?= BASE_URL ?>/admin/pages/receiving/index.php';
        }, 1200);
    } else {
        showToast(res ? res.message : 'Gagal memperbarui penerimaan barang.', 'danger');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Perbarui Data Penerimaan';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
