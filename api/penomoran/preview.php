<?php
/**
 * REST API: Generate Mock / Next Preview Number
 * Endpoint: /api/penomoran/preview.php
 * Path: api/penomoran/preview.php
 * Khusus Role: ADMIN
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_ADMIN]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function getRomanMonth($m) {
    $map = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];
    return $map[(int)$m] ?? 'I';
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $format = trim($input['format'] ?? '');
    $digits = max(1, min(10, intval($input['digit_counter'] ?? 5)));
    $mockCounter = intval($input['counter_value'] ?? 123);

    $now = new DateTime();
    $year = $now->format('Y');
    $shortYear = $now->format('y');
    $month = $now->format('m');
    $romanMonth = getRomanMonth((int)$month);
    $day = $now->format('d');

    $counterStr = str_pad((string)$mockCounter, $digits, '0', STR_PAD_LEFT);

    $result = $format;
    $result = str_replace('[YEAR]', $year, $result);
    $result = str_replace('[SHORT_YEAR]', $shortYear, $result);
    $result = str_replace('[MONTH]', $month, $result);
    $result = str_replace('[ROMAN_MONTH]', $romanMonth, $result);
    $result = str_replace('[DAY]', $day, $result);
    $result = str_replace('[COUNTER]', $counterStr, $result);

    sendJson(true, 'Preview generated', [
        'preview' => $result,
        'raw_format' => $format,
        'tokens' => [
            'YEAR' => $year,
            'SHORT_YEAR' => $shortYear,
            'MONTH' => $month,
            'ROMAN_MONTH' => $romanMonth,
            'DAY' => $day,
            'COUNTER' => $counterStr
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Gagal generate preview: ' . $e->getMessage(), null, 500);
}
