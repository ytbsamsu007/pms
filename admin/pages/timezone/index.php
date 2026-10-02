<?php
/**
 * Pengaturan Timezone Sistem (Master Data) - PT Jaya Teknis
 * Path: admin/pages/timezone/index.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Atur Timezone';
$pageHeading = 'Pengaturan Zona Waktu Sistem';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h6 class="fw-bold text-dark mb-0">
                <i class="bi bi-gear me-2 text-primary"></i>Konfigurasi Zona Waktu
            </h6>
        </div>

        <form id="timezoneForm" onsubmit="handleSaveTimezone(event)">
            <div class="card-body p-4">
                <div class="mb-4">
                    <label class="form-label small fw-bold">Default Timezone <span class="text-danger">*</span></label>
                    <select class="form-select pe-4" id="appTimezone" onchange="updateLiveClockPreview()">
                        <option value="Asia/Makassar">Asia/Makassar (WITA, UTC+08:00)</option>
                        <option value="Asia/Jakarta">Asia/Jakarta (WIB, UTC+07:00)</option>
                        <option value="Asia/Jayapura">Asia/Jayapura (WIT, UTC+09:00)</option>
                        <option value="Asia/Singapore">Asia/Singapore (SGT, UTC+08:00)</option>
                        <option value="UTC">UTC (UTC+00:00)</option>
                    </select>
                </div>

                <div class="card border border-primary-subtle bg-light rounded-3 p-4">
                    <div class="d-flex align-items-center mb-2">
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle me-3">
                            <i class="bi bi-clock fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Simulasi Waktu Sistem Saat Ini</div>
                            <div class="text-muted small" id="previewTzLabel">Asia/Makassar (WITA, UTC+08:00)</div>
                        </div>
                    </div>
                    <div class="text-center py-3">
                        <div class="display-5 fw-bold font-monospace text-primary mb-1" id="previewClockTime">--:--:--</div>
                        <div class="text-muted fw-semibold" id="previewClockDate">Memuat tanggal...</div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-light p-3 d-flex justify-content-end border-top">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSaveTimezone">
                    <i class="bi bi-save me-1"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let clockInterval = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadTimezoneData();
    startLiveClock();
});

async function loadTimezoneData() {
    const res = await apiRequest('/api/master/timezone.php');
    if (res && res.success && res.data) {
        document.getElementById('appTimezone').value = res.data.timezone || 'Asia/Makassar';
        updateLiveClockPreview();
    }
}

function startLiveClock() {
    if (clockInterval) clearInterval(clockInterval);
    updateLiveClockPreview();
    clockInterval = setInterval(updateLiveClockPreview, 1000);
}

function updateLiveClockPreview() {
    const tzSelect = document.getElementById('appTimezone');
    const tz = tzSelect ? tzSelect.value : 'Asia/Makassar';
    const labelEl = document.getElementById('previewTzLabel');
    const timeEl = document.getElementById('previewClockTime');
    const dateEl = document.getElementById('previewClockDate');

    if (!timeEl || !dateEl) return;

    try {
        const now = new Date();
        const formatterTime = new Intl.DateTimeFormat('en-GB', {
            timeZone: tz,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        });
        const formatterDate = new Intl.DateTimeFormat('id-ID', {
            timeZone: tz,
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Format menggunakan titik dua (:) untuk jam:menit:detik
        timeEl.textContent = formatterTime.format(now).replace(/\./g, ':');
        dateEl.textContent = formatterDate.format(now);
        if (labelEl && tzSelect) {
            const selectedOption = tzSelect.options[tzSelect.selectedIndex];
            labelEl.textContent = selectedOption ? selectedOption.text : tz;
        }
    } catch (e) {
        timeEl.textContent = new Date().toLocaleTimeString('en-GB');
    }
}

async function handleSaveTimezone(e) {
    e.preventDefault();

    const payload = {
        timezone: document.getElementById('appTimezone').value.trim() || 'Asia/Makassar'
    };

    const btn = document.getElementById('btnSaveTimezone');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    }

    const res = await apiRequest('/api/master/timezone.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Pengaturan';
    }

    if (res && res.success) {
        showToast('Pengaturan zona waktu berhasil disimpan.', 'success');
        updateLiveClockPreview();
    } else {
        showToast(res ? res.message : 'Gagal menyimpan pengaturan timezone.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
