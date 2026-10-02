<?php
/**
 * Pengaturan Profil Perusahaan (Master Data) - PT Jaya Teknis
 * Path: admin/pages/profile/index.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Profil Perusahaan';
$pageHeading = 'Pengaturan Profil & Identitas Perusahaan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">Profil Perusahaan</h4>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="companyProfileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold text-dark py-3 px-4" id="tab-umum-btn" data-bs-toggle="tab" data-bs-target="#panel-umum" type="button" role="tab">
                        <i class="bi bi-buildings me-2 text-primary"></i>1. Identitas &amp; Kontak
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-dark py-3 px-4" id="tab-pajak-btn" data-bs-toggle="tab" data-bs-target="#panel-pajak" type="button" role="tab">
                        <i class="bi bi-receipt-cutoff me-2 text-primary"></i>2. Legalitas &amp; Pajak
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-dark py-3 px-4" id="tab-lokasi-btn" data-bs-toggle="tab" data-bs-target="#panel-lokasi" type="button" role="tab">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>3. Alamat &amp; Lokasi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-dark py-3 px-4" id="tab-logo-btn" data-bs-toggle="tab" data-bs-target="#panel-logo" type="button" role="tab">
                        <i class="bi bi-image me-2 text-primary"></i>4. Logo Perusahaan
                    </button>
                </li>
            </ul>
        </div>

        <form id="companyProfileForm" onsubmit="handleSaveCompanyProfile(event)">
            <div class="card-body p-4">
                <div class="tab-content" id="companyProfileTabContent">
                    
                    <!-- TAB 1: IDENTITAS & KONTAK -->
                    <div class="tab-pane fade show active" id="panel-umum" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3">Data Utama &amp; Komunikasi</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="profNama" required placeholder="PT Jaya Teknis">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Resmi</label>
                                <input type="email" class="form-control form-control-sm" id="profEmail" placeholder="info@jayateknis.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor Telepon Kantor</label>
                                <input type="text" class="form-control form-control-sm" id="profTelepon" placeholder="031-889900">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor WhatsApp Operasional</label>
                                <input type="text" class="form-control form-control-sm" id="profWhatsapp" placeholder="081234567890">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: LEGALITAS & PAJAK -->
                    <div class="tab-pane fade" id="panel-pajak" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3">Identitas Pajak &amp; Legalitas</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NPWP Perusahaan</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="profNpwp" placeholder="00.000.000.0-000.000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">KLU (Klasifikasi Lapangan Usaha)</label>
                                <input type="text" class="form-control form-control-sm" id="profKlu" placeholder="Kode KLU Pajak">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NITKU</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="profNitku" placeholder="Nomor Identitas Tempat Kegiatan Usaha">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tarif PPN Standar (%)</label>
                                <select class="form-select form-select-sm" id="profPajak12">
                                    <option value="1">PPN 12% (Berlaku)</option>
                                    <option value="0">Non-PPN / PPN 11%</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: ALAMAT & LOKASI -->
                    <div class="tab-pane fade" id="panel-lokasi" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3">Alamat Kantor &amp; Workshop Pusat</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Alamat Lengkap Kantor</label>
                                <textarea class="form-control form-control-sm" id="profAlamat" rows="2" placeholder="Jl. Raya Pelabuhan No. 123..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Kota / Kabupaten</label>
                                <input type="text" class="form-control form-control-sm" id="profKota" placeholder="Surabaya">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Provinsi</label>
                                <input type="text" class="form-control form-control-sm" id="profProvinsi" placeholder="Jawa Timur">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Koordinat GPS / Link Google Maps</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="profAlamatGps" placeholder="-7.250445, 112.768845">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: LOGO PERUSAHAAN -->
                    <div class="tab-pane fade" id="panel-logo" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3">Logo Resmi Perusahaan</h6>
                        
                        <div class="row g-4 align-items-start">
                            <!-- Preview Box -->
                            <div class="col-md-auto text-center">
                                <div class="p-2 border rounded-3 bg-light d-inline-block shadow-xs">
                                    <div id="logoPreviewContainer" style="width: 250px; height: 250px; display: flex; align-items: center; justify-content: center; background: repeating-conic-gradient(#f8f9fa 0% 25%, #ffffff 0% 50%) 50% / 20px 20px; border: 1px dashed #ced4da; border-radius: 6px; overflow: hidden; position: relative;">
                                        <img id="imgLogoPreview" src="" alt="Logo Perusahaan" class="d-none" style="width: 250px; height: 250px; object-fit: contain;">
                                        <div id="logoPlaceholder" class="text-muted text-center p-3">
                                            <i class="bi bi-image fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                            <span class="small d-block fw-semibold">Belum Ada Logo</span>
                                            <span style="font-size: 0.72rem;">250 × 250 px (.png)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ketentuan & Upload Controls -->
                            <div class="col-md">
                                <div class="card bg-light border-0 rounded-3 p-3 mb-3">
                                    <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-info-circle text-primary me-1"></i> Ketentuan Berkas Logo:</h6>
                                    <ul class="small text-muted mb-0 ps-3">
                                        <li class="mb-1">Format file harus <strong>.PNG</strong> (disarankan latar belakang transparan).</li>
                                        <li class="mb-1">Dimensi dan rasio gambar harus <strong>1:1 (Persegi)</strong>, ukuran ideal <strong>250 × 250 px</strong>.</li>
                                        <li>Ukuran file maksimal <strong>500 KB</strong>.</li>
                                    </ul>
                                </div>

                                <!-- Input File Hidden -->
                                <input type="file" id="inputLogoFile" accept=".png,image/png" class="d-none" onchange="onLogoFileSelected(event)">

                                <!-- Feedback Status -->
                                <div id="logoValidationAlert" class="alert alert-danger py-2 px-3 small d-none mb-3"></div>

                                <?php if ($user['role'] === ROLE_ADMIN): ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-xs" onclick="document.getElementById('inputLogoFile').click()">
                                        <i class="bi bi-folder2-open me-1"></i> Pilih Berkas PNG
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-xs d-none" id="btnUploadLogo" onclick="handleUploadLogo()">
                                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload &amp; Simpan Logo
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold shadow-xs d-none" id="btnDeleteLogo" onclick="handleDeleteLogo()">
                                        <i class="bi bi-trash-fill me-1"></i> Hapus Logo
                                    </button>
                                </div>
                                <div class="small text-muted mt-2" id="selectedFileName"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php if ($user['role'] === ROLE_ADMIN): ?>
            <div class="card-footer bg-light p-3 d-flex justify-content-end border-top" id="formMainFooter">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSaveCompanyProfile">
                    <i class="bi bi-save me-1"></i> Simpan Profil Perusahaan
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
let currentSelectedLogoFile = null;
let currentCompanyLogoUrl = '';

document.addEventListener('DOMContentLoaded', async () => {
    await loadCompanyProfileData();

    // Sembunyikan tombol simpan profil utama jika sedang di tab logo
    const tabs = document.querySelectorAll('#companyProfileTabs button[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
            const footer = document.getElementById('formMainFooter');
            if (footer) {
                if (e.target.id === 'tab-logo-btn') {
                    footer.classList.add('d-none');
                } else {
                    footer.classList.remove('d-none');
                }
            }
        });
    });
});

async function loadCompanyProfileData() {
    const res = await apiRequest('/api/master/profile.php');
    if (res && res.success && res.data && res.data.profile) {
        const p = res.data.profile;
        document.getElementById('profNama').value = p.nama || '';
        document.getElementById('profEmail').value = p.email || '';
        document.getElementById('profTelepon').value = p.telepon1 || '';
        document.getElementById('profWhatsapp').value = p.whatsapp || '';
        document.getElementById('profNpwp').value = p.npwp || '';
        document.getElementById('profKlu').value = p.KLU || '';
        document.getElementById('profNitku').value = p.NITKU || '';
        document.getElementById('profPajak12').value = (p.pajak12 !== undefined) ? p.pajak12 : 1;
        document.getElementById('profAlamat').value = p.alamat || '';
        document.getElementById('profKota').value = p.kota || '';
        document.getElementById('profProvinsi').value = p.provinsi || '';
        document.getElementById('profAlamatGps').value = p.alamat_gps || '';

        // Render Logo Profile
        renderLogoDisplay(p.picture);
    }
}

function renderLogoDisplay(picturePath) {
    const imgEl = document.getElementById('imgLogoPreview');
    const placeholder = document.getElementById('logoPlaceholder');
    const btnDelete = document.getElementById('btnDeleteLogo');

    if (picturePath && picturePath.trim() !== '') {
        currentCompanyLogoUrl = '<?= BASE_URL ?>/' + picturePath.trim().replace(/^\//, '');
        imgEl.src = currentCompanyLogoUrl;
        imgEl.classList.remove('d-none');
        placeholder.classList.add('d-none');
        if (btnDelete) btnDelete.classList.remove('d-none');
    } else {
        currentCompanyLogoUrl = '';
        imgEl.src = '';
        imgEl.classList.add('d-none');
        placeholder.classList.remove('d-none');
        if (btnDelete) btnDelete.classList.add('d-none');
    }
}

function onLogoFileSelected(event) {
    const file = event.target.files[0];
    const alertEl = document.getElementById('logoValidationAlert');
    const btnUpload = document.getElementById('btnUploadLogo');
    const fileNameEl = document.getElementById('selectedFileName');

    alertEl.classList.add('d-none');
    alertEl.textContent = '';
    currentSelectedLogoFile = null;
    if (btnUpload) btnUpload.classList.add('d-none');
    if (fileNameEl) fileNameEl.textContent = '';

    if (!file) return;

    // 1. Validasi Ekstensi (.png)
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext !== 'png' || file.type !== 'image/png') {
        alertEl.textContent = 'Format berkas tidak valid! Hanya berkas dengan ekstensi .PNG yang diizinkan.';
        alertEl.classList.remove('d-none');
        event.target.value = '';
        return;
    }

    // 2. Validasi Ukuran File (Maks 500 KB)
    const maxBytes = 500 * 1024;
    if (file.size > maxBytes) {
        const sizeKb = (file.size / 1024).toFixed(1);
        alertEl.textContent = `Ukuran berkas (${sizeKb} KB) melebihi batas maksimal 500 KB!`;
        alertEl.classList.remove('d-none');
        event.target.value = '';
        return;
    }

    // 3. Validasi Dimensi & Rasio 1:1 Menggunakan Image Object
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const width = img.naturalWidth;
            const height = img.naturalHeight;
            const ratio = width / Math.max(1, height);

            if (ratio < 0.95 || ratio > 1.05) {
                alertEl.textContent = `Gambar harus memiliki rasio 1:1 (persegi). Dimensi berkas Anda: ${width} × ${height} px.`;
                alertEl.classList.remove('d-none');
                event.target.value = '';
                return;
            }

            // Validasi Berhasil
            currentSelectedLogoFile = file;
            document.getElementById('imgLogoPreview').src = e.target.result;
            document.getElementById('imgLogoPreview').classList.remove('d-none');
            document.getElementById('logoPlaceholder').classList.add('d-none');

            if (btnUpload) btnUpload.classList.remove('d-none');
            if (fileNameEl) {
                fileNameEl.innerHTML = `<i class="bi bi-file-earmark-check text-success me-1"></i> <strong>${escapeHtml(file.name)}</strong> (${(file.size / 1024).toFixed(1)} KB, ${width}×${height} px)`;
            }
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

async function handleUploadLogo() {
    if (!currentSelectedLogoFile) {
        showToast('Pilih berkas logo PNG terlebih dahulu.', 'warning');
        return;
    }

    const btn = document.getElementById('btnUploadLogo');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengunggah...';
    }

    const formData = new FormData();
    formData.append('image', currentSelectedLogoFile);
    formData.append('type', 'company');

    try {
        // 1. Upload file gambar ke endpoint upload API
        const uploadRes = await fetch('<?= BASE_URL ?>/api/master/upload_image.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const uploadResult = await uploadRes.json();

        if (!uploadResult || !uploadResult.success || !uploadResult.data) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload &amp; Simpan Logo';
            }
            showToast(uploadResult ? uploadResult.message : 'Gagal mengunggah berkas logo.', 'danger');
            return;
        }

        const relativeLogoPath = uploadResult.data.url || uploadResult.data.file_path;

        // 2. Simpan path logo baru ke data profil perusahaan via Profile API
        const profilePayload = {
            nama: document.getElementById('profNama').value.trim() || 'PT Jaya Teknis',
            picture: relativeLogoPath
        };

        const saveRes = await apiRequest('/api/master/profile.php', {
            method: 'POST',
            body: JSON.stringify(profilePayload)
        });

        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload &amp; Simpan Logo';
        }

        if (saveRes && saveRes.success) {
            showToast('Logo perusahaan berhasil diperbarui.', 'success');
            currentSelectedLogoFile = null;
            document.getElementById('inputLogoFile').value = '';
            if (btn) btn.classList.add('d-none');
            document.getElementById('selectedFileName').textContent = '';
            renderLogoDisplay(relativeLogoPath);
        } else {
            showToast(saveRes ? saveRes.message : 'Gagal menyimpan perubahan logo pada profil.', 'danger');
        }
    } catch (err) {
        console.error('Error uploading logo:', err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload &amp; Simpan Logo';
        }
        showToast('Terjadi kesalahan jaringan saat mengunggah logo.', 'danger');
    }
}

async function handleDeleteLogo() {
    if (!confirm('Apakah Anda yakin ingin menghapus logo perusahaan saat ini?')) {
        return;
    }

    const btn = document.getElementById('btnDeleteLogo');
    if (btn) {
        btn.disabled = true;
    }

    try {
        const response = await fetch('<?= BASE_URL ?>/api/master/profile.php?action=delete_logo', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (btn) btn.disabled = false;

        if (result && result.success) {
            showToast('Logo perusahaan berhasil dihapus.', 'success');
            renderLogoDisplay('');
        } else {
            showToast(result ? result.message : 'Gagal menghapus logo.', 'danger');
        }
    } catch (err) {
        console.error('Error deleting logo:', err);
        if (btn) btn.disabled = false;
        showToast('Terjadi kesalahan jaringan saat menghapus logo.', 'danger');
    }
}

async function handleSaveCompanyProfile(e) {
    e.preventDefault();

    const payload = {
        nama: document.getElementById('profNama').value.trim(),
        email: document.getElementById('profEmail').value.trim(),
        telepon1: document.getElementById('profTelepon').value.trim(),
        whatsapp: document.getElementById('profWhatsapp').value.trim(),
        npwp: document.getElementById('profNpwp').value.trim(),
        KLU: document.getElementById('profKlu').value.trim(),
        NITKU: document.getElementById('profNitku').value.trim(),
        pajak12: parseInt(document.getElementById('profPajak12').value) || 0,
        alamat: document.getElementById('profAlamat').value.trim(),
        kota: document.getElementById('profKota').value.trim(),
        provinsi: document.getElementById('profProvinsi').value.trim(),
        alamat_gps: document.getElementById('profAlamatGps').value.trim()
    };

    const btn = document.getElementById('btnSaveCompanyProfile');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    }

    const res = await apiRequest('/api/master/profile.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Profil Perusahaan';
    }

    if (res && res.success) {
        showToast('Profil perusahaan berhasil disimpan.', 'success');
    } else {
        showToast(res ? res.message : 'Gagal menyimpan profil perusahaan.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
