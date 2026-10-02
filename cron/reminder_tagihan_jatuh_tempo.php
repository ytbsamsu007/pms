<?php
/**
 * Cron Job / CLI Script: Peringatan Otomatis Jatuh Tempo Tagihan Vendor (H-3)
 * Path: cron/reminder_tagihan_jatuh_tempo.php
 * 
 * Penggunaan CLI:
 *   C:\xampp\php\php.exe cron/reminder_tagihan_jatuh_tempo.php
 * 
 * Penggunaan HTTP / Webhook:
 *   GET /cron/reminder_tagihan_jatuh_tempo.php?key=JT_CRON_2026
 */

// Tentukan apakah dieksekusi via CLI atau Web Browser
$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/mailer.php';

// Parameter H-Days (default: H-3)
$hDays = isset($_GET['h_days']) && is_numeric($_GET['h_days']) ? (int)$_GET['h_days'] : 3;
if ($isCli && isset($argv[1]) && is_numeric($argv[1])) {
    $hDays = (int)$argv[1];
}

$startTime = microtime(true);
$timestamp = date('Y-m-d H:i:s');

if ($isCli) {
    echo "===============================================================\n";
    echo " PT JAYA TEKNIS - CRON REMINDER TAGIHAN JATUH TEMPO (H-{$hDays})\n";
    echo " Waktu Eksekusi: {$timestamp}\n";
    echo "===============================================================\n";
}

try {
    $result = sendDueBillsReminderNotification($conn, $hDays);
    $execDuration = round(microtime(true) - $startTime, 3);

    if ($isCli) {
        if (!empty($result['success'])) {
            echo "[SUCCESS] {$result['message']}\n";
            echo " - Total Faktur             : " . ($result['due_count'] ?? 0) . " faktur\n";
            echo "   * Lewat Jatuh Tempo (Overdue) : " . ($result['overdue_count'] ?? 0) . " faktur\n";
            echo "   * Mendekati Tempo (H-{$hDays})     : " . ($result['approaching_count'] ?? 0) . " faktur\n";
            echo " - Total Sisa Kewajiban     : Rp " . number_format($result['total_sisa_tagihan'] ?? 0, 0, ',', '.') . "\n";
            echo " - Email Terkirim           : " . ($result['sent_count'] ?? 0) . " penerima\n";
            if (!empty($result['recipients'])) {
                echo " - Penerima                 : " . implode(', ', $result['recipients']) . "\n";
            }
        } else {
            echo "[WARNING/FAILED] {$result['message']}\n";
        }
        echo "Durasi Eksekusi: {$execDuration} detik\n";
        echo "===============================================================\n";
    } else {
        http_response_code($result['success'] ? 200 : 500);
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => [
                'timestamp' => $timestamp,
                'h_days' => $hDays,
                'due_count' => $result['due_count'] ?? 0,
                'overdue_count' => $result['overdue_count'] ?? 0,
                'approaching_count' => $result['approaching_count'] ?? 0,
                'sent_count' => $result['sent_count'] ?? 0,
                'total_sisa_tagihan' => $result['total_sisa_tagihan'] ?? 0,
                'recipients' => $result['recipients'] ?? [],
                'duration_seconds' => $execDuration
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (\Throwable $e) {
    $execDuration = round(microtime(true) - $startTime, 3);
    if ($isCli) {
        echo "[ERROR] Exception: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            'duration_seconds' => $execDuration
        ]);
    }
}
