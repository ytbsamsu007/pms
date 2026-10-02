<?php
/**
 * Halaman Edit Profil Karyawan / Pengguna yang Sedang Login
 * Path: admin/pages/user/profile.php
 * Akses: Seluruh Role yang Login (Admin, Mekanik, Logistik, Purchasing, Manager)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth: Dapat diakses oleh seluruh pengguna yang sedang login
$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Edit Profil Saya';
$pageHeading = 'Pengaturan Profil & Akun Karyawan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Halaman -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">
            Edit Profil Saya
        </h4>
        
    </div>

    <!-- Loading Skeleton Card -->
    <div id="profileLoadingSkeleton" class="card border-0 shadow-sm rounded-3 p-4 mb-4">
        <div class="row g-4">
            <div class="col-lg-4 text-center">
                <div class="skeleton-shimmer rounded-circle mb-3" style="width: 100px; height: 100px;"></div>
                <div class="skeleton-shimmer mb-2" style="width: 160px; height: 20px;"></div>
                <div class="skeleton-shimmer" style="width: 100px; height: 24px; border-radius: 12px;"></div>
            </div>
            <div class="col-lg-8">
                <div class="skeleton-shimmer mb-3" style="width: 100%; height: 38px;"></div>
                <div class="skeleton-shimmer mb-3" style="width: 100%; height: 38px;"></div>
                <div class="skeleton-shimmer mb-3" style="width: 100%; height: 38px;"></div>
            </div>
        </div>
    </div>

    <!-- Main Profile Card Content -->
    <div id="profileContentArea" class="row g-4 d-none">
        <!-- Kolom Kiri: Kartu Identitas & Jabatan Karyawan -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden text-center">
                <div class="bg-primary bg-gradient py-4 px-3 text-white">
                    <div class="d-inline-flex align-items-center justify-content-center bg-white text-primary rounded-circle shadow fw-bold fs-2 mb-2" 
                         style="width: 84px; height: 84px;" id="avatarInitialDisplay">
                        U
                    </div>
                    <h5 class="fw-bold mb-1" id="displayNamaUser">Memuat...</h5>
                    <span class="badge bg-light text-primary fw-semibold px-3 py-1" id="displayRoleBadge">ROLE</span>
                </div>

                <div class="card-body p-3 text-start">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 small text-uppercase">
                        Informasi Penugasan
                    </h6>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Kode Karyawan / NIK:</span>
                        <strong class="text-dark font-monospace" id="infoKodeKaryawan">-</strong>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Jabatan:</span>
                        <span class="fw-semibold text-dark" id="infoJabatan">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Divisi / Departemen:</span>
                        <span class="fw-semibold text-dark" id="infoDivisi">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Site / Penempatan:</span>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace" id="infoSite">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Jenis Kelamin:</span>
                        <span class="fw-semibold text-dark" id="infoJenisKelamin">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Tempat, Tanggal Lahir:</span>
                        <span class="fw-semibold text-dark" id="infoTtl">-</span>
                    </div>

                    <div>
                        <span class="text-muted small d-block">Status Karyawan:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" id="infoStatusKaryawan">Tetap</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Formulir Edit Profil & Password -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                         Perbarui Informasi Pribadi &amp; Kata Sandi
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form id="formEditProfile" onsubmit="handleSaveProfile(event)">
                        <!-- Data Pribadi & Kontak -->
                        

                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="inputNama" required placeholder="Nama Lengkap Anda">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Tempat Lahir</label>
                                <input type="text" class="form-control form-control-sm" id="inputTempatLahir" placeholder="Contoh: Surabaya">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Tanggal Lahir</label>
                                <input type="date" class="form-control form-control-sm" id="inputTanggalLahir">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Jenis Kelamin</label>
                                <select class="form-select form-select-sm" id="inputJenisKelamin">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="1">Laki-laki (Pria)</option>
                                    <option value="2">Perempuan (Wanita)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-sm" id="inputEmail" required placeholder="nama@jayateknis.com" oninput="updateTargetEmailDisplay()">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">No. Handphone / WhatsApp</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="inputNoHp" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>

                        <!-- Keamanan Akun / Ganti Password -->
                        <h6 class="fw-bold text-dark border-top pt-4 mb-3 small text-uppercase">
                             Ganti Kata Sandi <span class="text-muted fw-normal text-lowercase">(kosongkan jika tidak ingin mengubah password)</span>
                        </h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Password Saat Ini</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" id="inputPasswordLama" placeholder="Masukkan kata sandi saat ini untuk konfirmasi">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('inputPasswordLama')" title="Tampilkan/Sembunyikan Password">
                                        <i class="bi bi-eye" id="iconToggleinputPasswordLama"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Password Baru</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" id="inputPasswordBaru" placeholder="Minimal 5 karakter" oninput="handlePasswordBaruChange()">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('inputPasswordBaru')" title="Tampilkan/Sembunyikan Password">
                                        <i class="bi bi-eye" id="iconToggleinputPasswordBaru"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Ulangi Password Baru</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" id="inputKonfirmasiPassword" placeholder="Ketik ulang kata sandi baru" oninput="handlePasswordBaruChange()">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('inputKonfirmasiPassword')" title="Tampilkan/Sembunyikan Password">
                                        <i class="bi bi-eye" id="iconToggleinputKonfirmasiPassword"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- CAPTCHA VERIFIKASI KEAMANAN SEDERHANA -->
                        <div id="captchaSectionWrapper" class="d-none border rounded-3 p-3 bg-white mb-3 shadow-xs">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-shield-check text-primary me-1"></i> Verifikasi Keamanan (Captcha Aritmatika) <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="d-inline-flex align-items-center bg-light border rounded px-3 py-1 font-monospace fw-bold fs-6 text-primary user-select-none" id="captchaQuestionDisplay">
                                    ...
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generateSimpleCaptcha()" title="Ganti Angka Captcha">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <div style="max-width: 140px;">
                                    <input type="number" class="form-control form-control-sm font-monospace text-center fw-bold" id="inputCaptchaAnswer" placeholder="Jawaban...">
                                </div>
                                <span class="text-muted small">Hitung hasil penjumlahan untuk verifikasi</span>
                            </div>
                        </div>

                        <!-- KOTAK VERIFIKASI OTP EMAIL (MUNCUL OTOMATIS JIKA GANTI PASSWORD) -->
                        <div id="otpSectionWrapper" class="d-none border rounded-3 p-3 bg-light mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <h6 class="fw-bold text-primary mb-0 small">
                                    <i class="bi bi-shield-check me-1"></i> Verifikasi OTP Email (Wajib untuk Ganti Password)
                                </h6>
                                <span class="badge bg-primary-subtle text-primary font-monospace">Berlaku 1 Jam</span>
                            </div>
                            <p class="text-muted small mb-3" style="font-size: 0.825rem;">
                                Untuk keamanan, kode 6 digit OTP akan dikirimkan ke email Anda (<strong id="otpEmailTargetDisplay">...</strong>).
                                <span class="text-danger d-block mt-1 fw-semibold">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Batas Kesalahan: Maksimal 10x salah input. Melebihi 10x akan otomatis menonaktifkan akun karyawan.
                                </span>
                            </p>

                            <div class="row g-2 align-items-center">
                                <div class="col-sm-6 col-md-5">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white font-monospace fw-bold text-primary"><i class="bi bi-key-fill"></i></span>
                                        <input type="text" class="form-control form-control-sm font-monospace text-center fw-bold fs-6" id="inputOtpCode" maxlength="6" placeholder="6 DIGIT OTP" autocomplete="off" style="letter-spacing: 4px;">
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-7">
                                    <button type="button" class="btn btn-outline-primary btn-sm fw-semibold w-100" id="btnRequestOtp" onclick="handleRequestOtp()">
                                        <i class="bi bi-send me-1"></i> <span id="btnRequestOtpText">Kirim Kode OTP ke Email</span>
                                    </button>
                                </div>
                            </div>
                            <div id="otpAlertBox" class="mt-2 d-none"></div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitProfile">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let otpCooldownInterval = null;
let otpCooldownSeconds = 0;
let captchaNum1 = 0;
let captchaNum2 = 0;
let captchaExpectedAnswer = 0;

document.addEventListener('DOMContentLoaded', async () => {
    generateSimpleCaptcha();
    await loadUserProfileData();
});

// Helper Format Tanggal Indonesia
function formatTglIndo(tglStr) {
    if (!tglStr) return '';
    const date = new Date(tglStr);
    if (isNaN(date.getTime())) return tglStr;
    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${date.getDate()} ${bulan[date.getMonth()]} ${date.getFullYear()}`;
}

// -------------------------------------------------------------
// HELPER: CAPTCHA SEDERHANA & HIDE/SHOW PASSWORD
// -------------------------------------------------------------
function generateSimpleCaptcha() {
    captchaNum1 = Math.floor(Math.random() * 9) + 1; // 1-9
    captchaNum2 = Math.floor(Math.random() * 9) + 1; // 1-9
    captchaExpectedAnswer = captchaNum1 + captchaNum2;
    const el = document.getElementById('captchaQuestionDisplay');
    if (el) {
        el.textContent = `${captchaNum1} + ${captchaNum2} = ?`;
    }
    const input = document.getElementById('inputCaptchaAnswer');
    if (input) input.value = '';
}

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById('iconToggle' + inputId);
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

function updateTargetEmailDisplay() {
    const email = document.getElementById('inputEmail').value.trim();
    document.getElementById('otpEmailTargetDisplay').textContent = email || 'email Anda';
}

function handlePasswordBaruChange() {
    const passBaru = document.getElementById('inputPasswordBaru').value.trim();
    const passKonf = document.getElementById('inputKonfirmasiPassword').value.trim();
    const passLama = document.getElementById('inputPasswordLama').value.trim();
    const otpSection = document.getElementById('otpSectionWrapper');
    const captchaSection = document.getElementById('captchaSectionWrapper');

    if (passBaru.length > 0 || passKonf.length > 0 || passLama.length > 0) {
        otpSection.classList.remove('d-none');
        captchaSection.classList.remove('d-none');
        if (captchaExpectedAnswer === 0) {
            generateSimpleCaptcha();
        }
        updateTargetEmailDisplay();
    } else {
        otpSection.classList.add('d-none');
        captchaSection.classList.add('d-none');
        document.getElementById('inputOtpCode').value = '';
        document.getElementById('otpAlertBox').classList.add('d-none');
    }
}

// -------------------------------------------------------------
// 1. LOAD DATA PROFIL DARI API
// -------------------------------------------------------------
async function loadUserProfileData() {
    const res = await apiRequest('/api/auth/profile.php');
    if (!res || !res.success) {
        showToast(res ? res.message : 'Gagal memuat profil.', 'danger');
        return;
    }

    const data = res.data;

    // Tampilkan Data ke Kartu Identitas
    const nama = data.nama_karyawan || data.nama || 'Pengguna';
    document.getElementById('displayNamaUser').textContent = nama;
    document.getElementById('avatarInitialDisplay').textContent = nama.charAt(0).toUpperCase();
    document.getElementById('displayRoleBadge').textContent = (data.role || 'USER').toUpperCase();

    document.getElementById('infoKodeKaryawan').textContent = data.kode_karyawan || '-';
    document.getElementById('infoJabatan').textContent = data.nama_jabatan || '-';
    document.getElementById('infoDivisi').textContent = data.nama_divisi || '-';
    document.getElementById('infoSite').textContent = data.nama_site || '-';
    document.getElementById('infoStatusKaryawan').textContent = data.status_karyawan_label || 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)';

    // Jenis Kelamin & TTL
    const jk = parseInt(data.jenis_kelamin);
    document.getElementById('infoJenisKelamin').textContent = jk === 1 ? 'Laki-laki (Pria)' : (jk === 2 ? 'Perempuan (Wanita)' : '-');

    const tempat = data.tempat_lahir || '';
    const tgl = data.tanggal_lahir ? formatTglIndo(data.tanggal_lahir) : '';
    let ttl = '-';
    if (tempat && tgl) ttl = `${tempat}, ${tgl}`;
    else if (tempat) ttl = tempat;
    else if (tgl) ttl = tgl;
    document.getElementById('infoTtl').textContent = ttl;

    // Tampilkan Data ke Form Input
    document.getElementById('inputNama').value = nama;
    document.getElementById('inputTempatLahir').value = data.tempat_lahir || '';
    document.getElementById('inputTanggalLahir').value = data.tanggal_lahir || '';
    document.getElementById('inputJenisKelamin').value = data.jenis_kelamin || '';
    document.getElementById('inputEmail').value = data.email || '';
    document.getElementById('inputNoHp').value = data.no_handphone || '';
    updateTargetEmailDisplay();

    // Sembunyikan Skeleton & Tampilkan Konten
    document.getElementById('profileLoadingSkeleton').classList.add('d-none');
    document.getElementById('profileContentArea').classList.remove('d-none');
}

// -------------------------------------------------------------
// 2. KIRIM KODE OTP (COOLDOWN 90 DETIK DENGAN CAPTCHA)
// -------------------------------------------------------------
async function handleRequestOtp() {
    if (otpCooldownSeconds > 0) return;

    const email = document.getElementById('inputEmail').value.trim();
    if (!email) {
        showToast('Isi alamat email Anda terlebih dahulu!', 'warning');
        return;
    }

    // Validasi Captcha Sederhana
    const captchaAns = parseInt(document.getElementById('inputCaptchaAnswer').value);
    if (isNaN(captchaAns) || captchaAns !== captchaExpectedAnswer) {
        showToast('Selesaikan perhitungan Captcha verifikasi keamanan terlebih dahulu sebelum meminta OTP!', 'warning');
        generateSimpleCaptcha();
        document.getElementById('inputCaptchaAnswer').focus();
        return;
    }

    const btn = document.getElementById('btnRequestOtp');
    const textSpan = document.getElementById('btnRequestOtpText');
    const originalText = textSpan.textContent;

    btn.disabled = true;
    textSpan.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim OTP...';

    const res = await apiRequest('/api/auth/profile.php?action=request_password_otp', {
        method: 'POST'
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        const alertBox = document.getElementById('otpAlertBox');
        alertBox.className = 'alert alert-success py-2 px-3 small mt-2';
        alertBox.innerHTML = `<i class="bi bi-check-circle-fill me-1"></i> ${res.message}`;
        alertBox.classList.remove('d-none');

        // Mulai Countdown 90 Detik
        startOtpCooldown(res.data.cooldown_seconds || 90);
    } else {
        btn.disabled = false;
        textSpan.textContent = originalText;
        showToast(res ? res.message : 'Gagal mengirim kode OTP.', 'danger');

        if (res && res.data && res.data.cooldown_seconds) {
            startOtpCooldown(res.data.cooldown_seconds);
        }
    }
}

function startOtpCooldown(seconds) {
    otpCooldownSeconds = seconds;
    const btn = document.getElementById('btnRequestOtp');
    const textSpan = document.getElementById('btnRequestOtpText');
    btn.disabled = true;

    if (otpCooldownInterval) clearInterval(otpCooldownInterval);

    otpCooldownInterval = setInterval(() => {
        otpCooldownSeconds--;
        if (otpCooldownSeconds <= 0) {
            clearInterval(otpCooldownInterval);
            otpCooldownSeconds = 0;
            btn.disabled = false;
            textSpan.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang OTP';
        } else {
            textSpan.textContent = `Kirim Ulang OTP (${otpCooldownSeconds}s)`;
        }
    }, 1000);
}

// -------------------------------------------------------------
// 3. SIMPAN PERUBAHAN PROFIL & PASSWORD
// -------------------------------------------------------------
async function handleSaveProfile(e) {
    e.preventDefault();

    const nama = document.getElementById('inputNama').value.trim();
    const tempatLahir = document.getElementById('inputTempatLahir').value.trim();
    const tanggalLahir = document.getElementById('inputTanggalLahir').value;
    const jenisKelamin = document.getElementById('inputJenisKelamin').value;
    const email = document.getElementById('inputEmail').value.trim();
    const noHp = document.getElementById('inputNoHp').value.trim();
    const passLama = document.getElementById('inputPasswordLama').value;
    const passBaru = document.getElementById('inputPasswordBaru').value;
    const passKonf = document.getElementById('inputKonfirmasiPassword').value;
    const otpCode = document.getElementById('inputOtpCode').value.trim();

    if (!nama) {
        showToast('Nama lengkap tidak boleh kosong!', 'warning');
        return;
    }
    if (!email) {
        showToast('Alamat email tidak boleh kosong!', 'warning');
        return;
    }

    if (passBaru) {
        if (passBaru.length < 5) {
            showToast('Password baru minimal 5 karakter!', 'warning');
            return;
        }
        if (passBaru !== passKonf) {
            showToast('Konfirmasi kata sandi baru tidak cocok!', 'warning');
            return;
        }

        // Validasi Captcha
        const captchaAns = parseInt(document.getElementById('inputCaptchaAnswer').value);
        if (isNaN(captchaAns) || captchaAns !== captchaExpectedAnswer) {
            showToast('Jawaban Captcha verifikasi keamanan salah! Silakan hitung kembali.', 'warning');
            generateSimpleCaptcha();
            document.getElementById('inputCaptchaAnswer').focus();
            return;
        }

        if (!otpCode || otpCode.length !== 6) {
            showToast('Masukkan 6 digit kode OTP yang dikirimkan ke email Anda!', 'warning');
            document.getElementById('inputOtpCode').focus();
            return;
        }
    }

    const payload = {
        nama_karyawan: nama,
        tempat_lahir: tempatLahir,
        tanggal_lahir: tanggalLahir,
        jenis_kelamin: jenisKelamin,
        email: email,
        no_handphone: noHp,
        password_lama: passLama,
        password_baru: passBaru,
        konfirmasi_password: passKonf,
        otp_code: otpCode
    };

    const btnSubmit = document.getElementById('btnSubmitProfile');
    const originalText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const res = await apiRequest('/api/auth/profile.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btnSubmit.disabled = false;
    btnSubmit.innerHTML = originalText;

    if (res && res.success) {
        showToast(res.message, 'success');
        document.getElementById('inputPasswordLama').value = '';
        document.getElementById('inputPasswordBaru').value = '';
        document.getElementById('inputKonfirmasiPassword').value = '';
        document.getElementById('inputOtpCode').value = '';
        document.getElementById('inputCaptchaAnswer').value = '';
        document.getElementById('otpSectionWrapper').classList.add('d-none');
        document.getElementById('captchaSectionWrapper').classList.add('d-none');
        document.getElementById('otpAlertBox').classList.add('d-none');
        generateSimpleCaptcha();

        // Segarkan informasi di kartu identitas kiri
        document.getElementById('displayNamaUser').textContent = nama;
        document.getElementById('avatarInitialDisplay').textContent = nama.charAt(0).toUpperCase();
        
        const jkNum = parseInt(jenisKelamin);
        document.getElementById('infoJenisKelamin').textContent = jkNum === 1 ? 'Laki-laki (Pria)' : (jkNum === 2 ? 'Perempuan (Wanita)' : '-');

        const tglIndo = tanggalLahir ? formatTglIndo(tanggalLahir) : '';
        let ttl = '-';
        if (tempatLahir && tglIndo) ttl = `${tempatLahir}, ${tglIndo}`;
        else if (tempatLahir) ttl = tempatLahir;
        else if (tglIndo) ttl = tglIndo;
        document.getElementById('infoTtl').textContent = ttl;
    } else {
        showToast(res ? res.message : 'Gagal memperbarui profil.', 'danger');

        // Jika akun terkunci otomatis karena >10x salah
        if (res && res.data && res.data.account_locked) {
            const alertBox = document.getElementById('otpAlertBox');
            alertBox.className = 'alert alert-danger py-2 px-3 small mt-2';
            alertBox.innerHTML = `<i class="bi bi-x-octagon-fill me-1"></i> <strong>Akun Dinonaktifkan:</strong> ${res.message}`;
            alertBox.classList.remove('d-none');
            setTimeout(() => {
                window.location.href = `${BASE_URL}/admin/logout.php`;
            }, 3000);
            return;
        }

        // Tampilkan sisa kesempatan
        if (res && res.data && res.data.remaining_attempts !== undefined) {
            const alertBox = document.getElementById('otpAlertBox');
            alertBox.className = 'alert alert-warning py-2 px-3 small mt-2';
            alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${res.message}`;
            alertBox.classList.remove('d-none');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
