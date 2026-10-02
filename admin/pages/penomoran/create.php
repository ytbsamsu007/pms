<?php
/**
 * Halaman Tambah Format Penomoran Transaksi
 * Path: admin/pages/penomoran/create.php
 * Khusus Role: ADMIN
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection Khusus ADMIN
$user = requireAuth([ROLE_ADMIN]);

$pageTitle = 'Tambah Format Penomoran';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid py-3">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Tambah Format</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/penomoran/index.php" class="btn btn-outline-secondary btn-sm px-3">
                Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- FORM UTAMA (2 KOLOM) -->
    <form id="formCreatePenomoran" onsubmit="handleSavePenomoran(event)">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-4">
                <div class="row g-4">
                    <!-- KOLOM KIRI: Informasi & Konfigurasi -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                Parameter Format
                            </h6>

                            <!-- Field: Nama -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Nama Penomoran <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="namaPenomoran" placeholder="Contoh: Format Faktur Pembelian" required>
                            </div>

                            <!-- Field: Tipe Transaksi -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Tipe Transaksi <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="tipeTransaksi" required>
                                    <option value="">-- Pilih Tipe Transaksi --</option>
                                    <option value="FAKTUR PO">Faktur Pembelian</option>
                                    <option value="REQUEST">Request Order</option>
                                    <option value="PURCHASE">Purchase Order</option>
                                    <option value="RECEIVING">Penerimaan Barang</option>
                                    <option value="RETUR PO">Retur PO</option>
                                    <option value="PAYMENT PO">Pembayaran PO</option>
                                    <option value="MUTASI BARANG">Mutasi Barang</option>
                                    <option value="ADJUSTMENT STOK">Stock Adjustment</option>
                                </select>
                            </div>

                            <!-- Field: Tipe Penomoran -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-dark">Tipe Penomoran (Reset)</label>
                                <select class="form-select form-select-sm" id="tipePenomoran">
                                    <option value="0">Tidak reset</option>
                                    <option value="1">Reset setiap hari</option>
                                    <option value="2" selected>Reset setiap bulan</option>
                                    <option value="3">Reset setiap tahun</option>
                                </select>
                            </div>

                            <!-- Field: Jumlah Digit Counter -->
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-dark">Jumlah Digit Counter <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm" id="digitCounter" value="5" min="1" max="10" required oninput="renderChipsAndPreview()">
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: Komponen Penomoran & Live Preview -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                    Komponen &amp; Pola Format
                                </h6>

                                <!-- Field: Komponen Penomoran & Builder -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark">Tambah Komponen</label>
                                    <div class="d-flex gap-2">
                                        <select class="form-select form-select-sm" id="selectKomponen">
                                            <option value="DAY">Hari</option>
                                            <option value="YEAR">Tahun</option>
                                            <option value="SHORT_YEAR">Tahun [Singkat]</option>
                                            <option value="MONTH">Bulan</option>
                                            <option value="ROMAN_MONTH">Bulan [Romawi]</option>
                                            <option value="COUNTER">Counter</option>
                                            <option value="CUSTOM">Teks Pemisah</option>
                                        </select>
                                        <button type="button" class="btn btn-success btn-sm px-3 fw-bold shadow-sm" onclick="addSelectedComponent()" title="Tambah Komponen">+</button>
                                    </div>
                                </div>

                                <!-- Dynamic Chips Container -->
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-dark">Susunan Pola Format</label>
                                    <div class="p-2 border rounded bg-white d-flex flex-wrap align-items-center gap-1" id="chipsContainer" style="min-height: 48px;">
                                        <!-- Chips will be rendered here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Field: Contoh Hasil Penomoran -->
                            <div class="mt-3 p-3 bg-white rounded border border-primary-subtle">
                                <div class="text-muted small fw-bold mb-1">CONTOH HASIL PENOMORAN:</div>
                                <div class="fs-4 fw-bold text-primary font-monospace" id="previewResultDisplay">
                                    -
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Bawah -->
                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSubmit">
                        <i class="bi bi-save me-2"></i> Simpan Format
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- MODAL INPUT TEKS KUSTOM / PEMISAH -->
<div class="modal fade" id="modalCustomText" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 px-3 bg-light">
                <h6 class="modal-title fw-bold text-dark small mb-0">Input Teks / Pemisah</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <label class="form-label small fw-semibold text-muted">Ketik Teks (misal: FP, -, /, dsb):</label>
                <input type="text" class="form-control form-control-sm font-monospace" id="customTextInput" placeholder="Contoh: FP atau -">
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="confirmAddCustomText()">Tambahkan</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Style Chip Tag Sesuai Gambar 2 */
.num-chip {
    display: inline-flex;
    align-items: center;
    background-color: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    border-radius: 4px;
    padding: 2px 8px;
    font-size: 0.875rem;
    font-weight: 600;
    font-family: monospace;
    user-select: none;
}
.num-chip-close {
    cursor: pointer;
    margin-left: 6px;
    font-weight: bold;
    color: #1e3a8a;
    transition: color 0.15s ease-in-out;
}
.num-chip-close:hover {
    color: #dc2626;
}
</style>

<script>
// Array komponen token diawali kosong saat create
let componentsList = [];
let existingTipeTransaksi = [];

document.addEventListener('DOMContentLoaded', () => {
    loadExistingTypes();
    renderChipsAndPreview();
});

async function loadExistingTypes() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/penomoran/index.php');
        const json = await res.json();
        if (json.success && json.data && json.data.items) {
            existingTipeTransaksi = json.data.items.map(item => item.tipe_transaksi);
            
            // Tandai / nonaktifkan opsi yang sudah terdaftar di dropdown
            const selectEl = document.getElementById('tipeTransaksi');
            Array.from(selectEl.options).forEach(opt => {
                if (opt.value && existingTipeTransaksi.includes(opt.value)) {
                    opt.text = opt.text + ' (Sudah Ada)';
                    opt.disabled = true;
                }
            });
        }
    } catch (e) {
        console.error('Error load existing types:', e);
    }
}

function getRomanMonth(m) {
    const map = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV', 5: 'V', 6: 'VI', 7: 'VII', 8: 'VIII', 9: 'IX', 10: 'X', 11: 'XI', 12: 'XII' };
    return map[parseInt(m)] || 'I';
}

function renderChipsAndPreview() {
    const container = document.getElementById('chipsContainer');
    const previewDisplay = document.getElementById('previewResultDisplay');
    const digits = parseInt(document.getElementById('digitCounter').value) || 5;

    let chipsHtml = '';
    let formatString = '';

    componentsList.forEach((comp, idx) => {
        let label = '';
        let rawToken = '';

        if (comp.type === 'tag') {
            rawToken = comp.tag;
            label = comp.tag;
        } else {
            rawToken = comp.val;
            label = comp.val;
        }

        formatString += rawToken;

        chipsHtml += `
            <div class="num-chip">
                <span>${escapeHtml(label)}</span>
                <span class="num-chip-close" onclick="removeComponent(${idx})" title="Hapus">✕</span>
            </div>
        `;
    });

    container.innerHTML = chipsHtml || '<span class="text-muted small">Belum ada komponen penomoran.</span>';

    // Generate Preview Realtime
    const now = new Date();
    const year = now.getFullYear().toString();
    const shortYear = year.slice(-2);
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const romanMonth = getRomanMonth(now.getMonth() + 1);
    const day = String(now.getDate()).padStart(2, '0');
    const mockCounter = '123'.padStart(digits, '0');

    let preview = formatString;
    preview = preview.replaceAll('[YEAR]', year);
    preview = preview.replaceAll('[SHORT_YEAR]', shortYear);
    preview = preview.replaceAll('[MONTH]', month);
    preview = preview.replaceAll('[ROMAN_MONTH]', romanMonth);
    preview = preview.replaceAll('[DAY]', day);
    preview = preview.replaceAll('[COUNTER]', mockCounter);

    previewDisplay.innerText = preview || '-';
}

function addSelectedComponent() {
    const sel = document.getElementById('selectKomponen').value;
    if (sel === 'CUSTOM') {
        document.getElementById('customTextInput').value = '';
        const modal = new bootstrap.Modal(document.getElementById('modalCustomText'));
        modal.show();
        setTimeout(() => document.getElementById('customTextInput').focus(), 400);
        return;
    }

    const tagMap = {
        'DAY': '[DAY]',
        'YEAR': '[YEAR]',
        'SHORT_YEAR': '[SHORT_YEAR]',
        'MONTH': '[MONTH]',
        'ROMAN_MONTH': '[ROMAN_MONTH]',
        'COUNTER': '[COUNTER]'
    };

    if (tagMap[sel]) {
        componentsList.push({ type: 'tag', tag: tagMap[sel] });
        renderChipsAndPreview();
    }
}

function confirmAddCustomText() {
    const val = document.getElementById('customTextInput').value;
    if (val !== '') {
        componentsList.push({ type: 'custom', val: val });
        renderChipsAndPreview();
    }
    bootstrap.Modal.getInstance(document.getElementById('modalCustomText')).hide();
}

function removeComponent(index) {
    componentsList.splice(index, 1);
    renderChipsAndPreview();
}

function getFormatString() {
    return componentsList.map(c => c.type === 'tag' ? c.tag : c.val).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

async function handleSavePenomoran(event) {
    event.preventDefault();

    const format = getFormatString();
    if (!format) {
        showToast('Format penomoran tidak boleh kosong. Silakan tambahkan komponen penomoran.', 'warning');
        return;
    }

    if (!format.includes('[COUNTER]')) {
        if (!confirm('Format penomoran belum menyertakan [COUNTER] urutan. Apakah Anda yakin ingin melanjutkan?')) {
            return;
        }
    }

    const tipeTransaksi = document.getElementById('tipeTransaksi').value;
    if (existingTipeTransaksi.includes(tipeTransaksi)) {
        showToast(`Format penomoran untuk tipe transaksi ${tipeTransaksi} sudah ada di sistem. Tidak dapat menduplikasi.`, 'danger');
        return;
    }

    const payload = {
        nama_penomoran: document.getElementById('namaPenomoran').value.trim(),
        tipe_transaksi: tipeTransaksi,
        tipe_penomoran: parseInt(document.getElementById('tipePenomoran').value),
        digit_counter: parseInt(document.getElementById('digitCounter').value) || 5,
        format: format
    };

    const btnSubmit = document.getElementById('btnSubmit');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    try {
        const res = await fetch('<?= BASE_URL ?>/api/penomoran/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.success) {
            sessionStorage.setItem('flashToast', JSON.stringify({
                message: 'Format penomoran berhasil disimpan!',
                type: 'success'
            }));
            window.location.href = '<?= BASE_URL ?>/admin/pages/penomoran/index.php';
        } else {
            showToast(json.message || 'Gagal menyimpan format penomoran.', 'danger');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Format';
        }
    } catch (err) {
        console.error(err);
        showToast('Terjadi kesalahan jaringan.', 'danger');
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Format';
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
