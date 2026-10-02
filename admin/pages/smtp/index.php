<?php
/**
 * Halaman Manajemen Server SMTP Mailer
 * Path: admin/pages/smtp/index.php
 * Akses: Khusus ROLE_ADMIN
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Hanya Administrator yang boleh mengakses
$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Server SMTP Mailer';
$pageHeading = 'Manajemen Server SMTP';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Halaman & Tombol Tambah -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">
                <i class="bi bi-envelope-at-fill text-primary me-2"></i>Manajemen Server SMTP
            </h4>
            <span class="text-muted small">Konfigurasi akun & gateway pengiriman email notifikasi sistem</span>
        </div>
        <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" onclick="openAddSmtpModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Server SMTP
        </button>
    </div>

    <!-- Ringkasan Statistik SMTP -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon primary p-2 rounded-3 fs-4 text-primary bg-primary-subtle">
                        <i class="bi bi-hdd-network"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Server</span>
                        <strong class="fs-5 text-dark" id="statTotalServer">0</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon success p-2 rounded-3 fs-4 text-success bg-success-subtle">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Server Aktif</span>
                        <strong class="fs-5 text-success" id="statTotalAktif">0</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon info p-2 rounded-3 fs-4 text-info bg-info-subtle">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Total Kuota Harian</span>
                        <strong class="fs-5 text-dark" id="statTotalKuota">0</strong>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon warning p-2 rounded-3 fs-4 text-warning bg-warning-subtle">
                        <i class="bi bi-envelope-paper"></i>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Sisa Kuota Hari Ini</span>
                        <strong class="fs-5 text-dark" id="statTotalSisa">0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control" id="filterSearch" placeholder="Cari nama provider, host SMTP, username..." oninput="debounceSmtpSearch()">
                    </div>
                </div>
                <div class="col-md-4 col-lg-3">
                    <select class="form-select form-select-sm" id="filterStatus" onchange="loadSmtpServers()">
                        <option value="">Semua Status</option>
                        <option value="1">Hanya Aktif</option>
                        <option value="0">Hanya Non-Aktif</option>
                    </select>
                </div>
                <div class="col-md-2 col-lg-4 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetSmtpFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data SMTP Server -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="smtpTable">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="min-width: 180px;">Nama Provider</th>
                        <th style="min-width: 220px;">Host Server &amp; Port</th>
                        <th style="min-width: 200px;">User Login</th>
                        <th style="width: 180px;">Kuota Harian</th>
                        <th style="width: 120px;" class="text-center">Status</th>
                        <th style="width: 140px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="smtpTableBody">
                    <!-- Dynamic Rows via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL FORM TAMBAH / EDIT SERVER SMTP                    -->
<!-- ======================================================== -->
<div class="modal fade" id="smtpFormModal" tabindex="-1" aria-labelledby="smtpFormModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 720px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white px-4 py-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2" id="smtpFormModalTitle">
                    <i class="bi bi-envelope-plus text-primary"></i> Tambah Server SMTP
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formSmtp" onsubmit="handleSaveSmtp(event)">
                <input type="hidden" id="formSmtpId" value="">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Provider & Link -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Nama Provider <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="formNamaProvider" required placeholder="Contoh: Gmail, SendGrid, Mailjet">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Link Provider / Dashboard URL</label>
                            <input type="url" class="form-control form-control-sm" id="formLinkProvider" placeholder="https://mail.google.com">
                        </div>

                        <!-- Host Server & Port -->
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-dark">SMTP Server Host <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="formStmpServer" required placeholder="smtp.gmail.com atau mail.domain.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-dark">Port SMTP <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="formPort" required placeholder="587 / 465 / 25">
                        </div>

                        <!-- User & Password -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Username / User Login <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="formUserLogin" required placeholder="noreply@jayateknis.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">
                                Password / App Password <span class="text-danger" id="reqPassIndicator">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control" id="formPassword" placeholder="Masukkan password SMTP">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('formPassword')">
                                    <i class="bi bi-eye" id="iconToggleformPassword"></i>
                                </button>
                            </div>
                            <span class="text-muted small d-block mt-1" id="helpPassText" style="font-size: 0.75rem;">Untuk Gmail gunakan <em>App Password 16 digit</em>.</span>
                        </div>

                        <!-- Kuota Harian & Sisa -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Limit Kuota Harian (Email/Hari)</label>
                            <input type="number" class="form-control form-control-sm" id="formLimitHarian" value="300" min="1" placeholder="300">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-dark">Sisa Kuota Hari Ini</label>
                            <input type="number" class="form-control form-control-sm" id="formSisaHarian" value="300" min="0" placeholder="300">
                        </div>

                        <!-- Status Aktif -->
                        <div class="col-12">
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="formAktif" checked>
                                <label class="form-check-label small fw-bold text-dark" for="formAktif">
                                    Aktifkan Server SMTP ini untuk antrean pengiriman email
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-info btn-sm fw-semibold" onclick="testSmtpConnectionFromModal()" id="btnModalTestConn">
                        <i class="bi bi-lightning-charge-fill me-1"></i> Test Koneksi Host
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold" id="btnSubmitSmtp">
                            <i class="bi bi-save me-1"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let smtpModal = null;
let searchTimeout = null;

document.addEventListener('DOMContentLoaded', () => {
    smtpModal = new bootstrap.Modal(document.getElementById('smtpFormModal'));
    loadSmtpServers();
});

function debounceSmtpSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadSmtpServers();
    }, 300);
}

function resetSmtpFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterStatus').value = '';
    loadSmtpServers();
}

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById('iconToggle' + inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// -------------------------------------------------------------
// 1. LOAD DAFTAR SMTP SERVER DARI API
// -------------------------------------------------------------
async function loadSmtpServers() {
    const tbody = document.getElementById('smtpTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data SMTP Server...
            </td>
        </tr>
    `;

    const q = document.getElementById('filterSearch').value.trim();
    const status = document.getElementById('filterStatus').value;

    let url = `/api/master/smtp.php?t=${Date.now()}`;
    if (q) url += `&q=${encodeURIComponent(q)}`;
    if (status !== '') url += `&aktif=${encodeURIComponent(status)}`;

    const res = await apiRequest(url);

    if (res && res.success) {
        const items = res.data.items || [];
        const stats = res.data.stats || {};

        // Update Stat Cards
        document.getElementById('statTotalServer').textContent = stats.total_server || 0;
        document.getElementById('statTotalAktif').textContent = stats.total_aktif || 0;
        document.getElementById('statTotalKuota').textContent = (stats.total_kuota || 0).toLocaleString('id-ID');
        document.getElementById('statTotalSisa').textContent = (stats.total_sisa || 0).toLocaleString('id-ID');

        renderSmtpTableRows(items);
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat data server SMTP.
                </td>
            </tr>
        `;
    }
}

function renderSmtpTableRows(items) {
    const tbody = document.getElementById('smtpTableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-3 d-block mb-1"></i> Belum ada data Server SMTP yang dikonfigurasi.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    items.forEach((item, index) => {
        const isAktif = parseInt(item.aktif) === 1;
        const sisa = parseInt(item.sisa_harian) || 0;
        const limit = parseInt(item.limit_harian) || 300;
        const persentase = limit > 0 ? Math.min(100, Math.round((sisa / limit) * 100)) : 0;

        let progressColor = 'bg-success';
        if (persentase < 20) progressColor = 'bg-danger';
        else if (persentase < 50) progressColor = 'bg-warning';

        html += `
            <tr>
                <td class="text-center text-muted small">${index + 1}</td>
                <td>
                    <div class="fw-bold text-dark">${item.nama_provider || '-'}</div>
                    ${item.link_provider ? `<a href="${item.link_provider}" target="_blank" class="text-muted small text-decoration-none"><i class="bi bi-box-arrow-up-right me-1"></i>Kunjungi Panel</a>` : ''}
                </td>
                <td>
                    <div class="font-monospace fw-semibold text-primary"><i class="bi bi-server me-1"></i>${item.stmp_server || '-'}</div>
                    <span class="badge bg-light text-secondary border font-monospace">Port: ${item.port || '-'}</span>
                </td>
                <td>
                    <div class="text-dark small"><i class="bi bi-person me-1 text-muted"></i>${item.user_login || '-'}</div>
                </td>
                <td>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Sisa: <strong>${sisa}</strong></span>
                        <span>Limit: ${limit}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar ${progressColor}" role="progressbar" style="width: ${persentase}%"></div>
                    </div>
                </td>
                <td class="text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="checkbox" role="switch" ${isAktif ? 'checked' : ''} onchange="toggleSmtpStatus(${item.id_stmp}, this.checked)" title="${isAktif ? 'Nonaktifkan' : 'Aktifkan'}">
                    </div>
                </td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm px-2 py-1 shadow-xs" onclick="testSmtpConnectionDirect(${item.id_stmp}, '${item.stmp_server}', '${item.port}')" title="Test Koneksi &amp; Autentikasi Host">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" onclick="openEditSmtpModal(${item.id_stmp})" title="Edit SMTP">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1 shadow-xs" onclick="deleteSmtpServer(${item.id_stmp}, '${item.nama_provider}')" title="Hapus SMTP">
                            <i class="bi bi-x-octagon-fill"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// -------------------------------------------------------------
// 2. MODAL CREATE & EDIT SMTP
// -------------------------------------------------------------
function openAddSmtpModal() {
    document.getElementById('smtpFormModalTitle').innerHTML = '<i class="bi bi-envelope-plus text-primary"></i> Tambah Server SMTP';
    document.getElementById('formSmtpId').value = '';
    document.getElementById('formNamaProvider').value = '';
    document.getElementById('formLinkProvider').value = '';
    document.getElementById('formStmpServer').value = '';
    document.getElementById('formPort').value = '587';
    document.getElementById('formUserLogin').value = '';
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').required = true;
    document.getElementById('reqPassIndicator').classList.remove('d-none');
    document.getElementById('helpPassText').textContent = 'Untuk Gmail gunakan App Password 16 digit.';
    document.getElementById('formLimitHarian').value = 300;
    document.getElementById('formSisaHarian').value = 300;
    document.getElementById('formAktif').checked = true;

    smtpModal.show();
}

async function openEditSmtpModal(id) {
    const res = await apiRequest(`/api/master/smtp.php?id=${id}`);
    if (!res || !res.success) {
        showToast('Gagal memuat rincian SMTP.', 'danger');
        return;
    }

    const d = res.data;
    document.getElementById('smtpFormModalTitle').innerHTML = '<i class="bi bi-pencil-square text-primary"></i> Edit Server SMTP';
    document.getElementById('formSmtpId').value = d.id_stmp;
    document.getElementById('formNamaProvider').value = d.nama_provider || '';
    document.getElementById('formLinkProvider').value = d.link_provider || '';
    document.getElementById('formStmpServer').value = d.stmp_server || '';
    document.getElementById('formPort').value = d.port || '587';
    document.getElementById('formUserLogin').value = d.user_login || '';
    document.getElementById('formPassword').value = '';
    document.getElementById('formPassword').required = false;
    document.getElementById('reqPassIndicator').classList.add('d-none');
    document.getElementById('helpPassText').textContent = 'Kosongkan jika tidak ingin mengubah password yang tersimpan.';
    document.getElementById('formLimitHarian').value = d.limit_harian || 300;
    document.getElementById('formSisaHarian').value = d.sisa_harian !== undefined ? d.sisa_harian : (d.limit_harian || 300);
    document.getElementById('formAktif').checked = parseInt(d.aktif) === 1;

    smtpModal.show();
}

// -------------------------------------------------------------
// 3. SIMPAN (CREATE / UPDATE)
// -------------------------------------------------------------
async function handleSaveSmtp(e) {
    e.preventDefault();

    const id = document.getElementById('formSmtpId').value;
    const payload = {
        id_stmp: id ? parseInt(id) : null,
        nama_provider: document.getElementById('formNamaProvider').value.trim(),
        link_provider: document.getElementById('formLinkProvider').value.trim(),
        stmp_server: document.getElementById('formStmpServer').value.trim(),
        port: document.getElementById('formPort').value.trim(),
        user_login: document.getElementById('formUserLogin').value.trim(),
        password: document.getElementById('formPassword').value,
        limit_harian: parseInt(document.getElementById('formLimitHarian').value) || 300,
        sisa_harian: parseInt(document.getElementById('formSisaHarian').value) || 300,
        aktif: document.getElementById('formAktif').checked ? 1 : 0
    };

    const btnSubmit = document.getElementById('btnSubmitSmtp');
    const originalText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const method = id ? 'PUT' : 'POST';
    const res = await apiRequest('/api/master/smtp.php', {
        method: method,
        body: JSON.stringify(payload)
    });

    btnSubmit.disabled = false;
    btnSubmit.innerHTML = originalText;

    if (res && res.success) {
        showToast(res.message, 'success');
        smtpModal.hide();
        loadSmtpServers();
    } else {
        showToast(res ? res.message : 'Gagal menyimpan data SMTP.', 'danger');
    }
}

// -------------------------------------------------------------
// 4. TOGGLE STATUS & HAPUS
// -------------------------------------------------------------
async function toggleSmtpStatus(id, isChecked) {
    const res = await apiRequest('/api/master/smtp.php', {
        method: 'PUT',
        body: JSON.stringify({
            id_stmp: id,
            toggle_aktif: isChecked ? 1 : 0
        })
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        loadSmtpServers();
    } else {
        showToast('Gagal mengubah status aktif.', 'danger');
        loadSmtpServers();
    }
}

async function deleteSmtpServer(id, nama) {
    if (!confirm(`Apakah Anda yakin ingin menghapus konfigurasi SMTP "${nama}"?`)) return;

    const res = await apiRequest(`/api/master/smtp.php?id=${id}`, {
        method: 'DELETE'
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        loadSmtpServers();
    } else {
        showToast(res ? res.message : 'Gagal menghapus server SMTP.', 'danger');
    }
}

// -------------------------------------------------------------
// 5. TEST KONEKSI & AUTENTIKASI SOCKET SMTP
// -------------------------------------------------------------
async function testSmtpConnectionDirect(id, host, port) {
    showToast(`Menguji koneksi & autentikasi ke ${host}:${port}...`, 'info');
    const res = await apiRequest('/api/master/smtp.php?action=test', {
        method: 'POST',
        body: JSON.stringify({ id_stmp: id, stmp_server: host, port: port })
    });

    if (res && res.success) {
        showToast(res.message, 'success');
    } else {
        showToast(res ? res.message : 'Koneksi / Autentikasi gagal.', 'danger');
    }
}

async function testSmtpConnectionFromModal() {
    const id = document.getElementById('formSmtpId').value;
    const host = document.getElementById('formStmpServer').value.trim();
    const port = document.getElementById('formPort').value.trim();
    const user = document.getElementById('formUserLogin').value.trim();
    const pass = document.getElementById('formPassword').value;

    if (!host) {
        showToast('Isi Host SMTP Server terlebih dahulu!', 'warning');
        return;
    }

    const btn = document.getElementById('btnModalTestConn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menguji Autentikasi...';

    const res = await apiRequest('/api/master/smtp.php?action=test', {
        method: 'POST',
        body: JSON.stringify({
            id_stmp: id ? parseInt(id) : null,
            stmp_server: host,
            port: port || '587',
            user_login: user,
            password: pass
        })
    });

    btn.disabled = false;
    btn.innerHTML = originalText;

    if (res && res.success) {
        showToast(res.message, 'success');
    } else {
        showToast(res ? res.message : 'Pengujian gagal.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
