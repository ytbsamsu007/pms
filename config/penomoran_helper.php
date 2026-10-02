<?php
/**
 * Helper Fungsi Generator Nomor Transaksi Dinamis Terpusat
 * Menggunakan Konfigurasi Format dari Tabel `penomoran`
 * Path: config/penomoran_helper.php
 * PT Jaya Teknik
 */

if (!function_exists('getRomanMonthNumber')) {
    function getRomanMonthNumber(int $month): string {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }
}

if (!function_exists('generateNomorTransaksi')) {
    /**
     * Generate nomor transaksi berikutnya sesuai format di tabel penomoran
     *
     * @param mysqli $conn Koneksi Database MySQLi
     * @param string $tipeTransaksi ENUM ('REQUEST','PURCHASE','RECEIVING','RETUR PO','FAKTUR PO','PAYMENT PO','MUTASI BARANG','ADJUSTMENT STOK')
     * @param string|null $dateStr Tanggal transaksi (YYYY-MM-DD atau datetime). Default: sekarang
     * @return array [
     *    'success' => bool,
     *    'nomor' => string,
     *    'format' => string,
     *    'counter' => int,
     *    'message' => string
     * ]
     */
    function generateNomorTransaksi($conn, string $tipeTransaksi, ?string $dateStr = null, bool $forUpdate = false): array {
        if (!$conn || !($conn instanceof mysqli)) {
            return [
                'success' => false,
                'nomor' => '',
                'format' => '',
                'counter' => 1,
                'message' => 'Koneksi database tidak valid'
            ];
        }

        $timestamp = !empty($dateStr) && strtotime($dateStr) ? strtotime($dateStr) : time();
        $yearFull = date('Y', $timestamp);
        $yearShort = date('y', $timestamp);
        $month = date('m', $timestamp);
        $romanMonth = getRomanMonthNumber((int)$month);
        $day = date('d', $timestamp);

        // 1. Ambil Pengaturan dari Tabel Penomoran
        $lockPenomoran = $forUpdate ? " FOR UPDATE" : "";
        $stmt = $conn->prepare("SELECT * FROM penomoran WHERE tipe_transaksi = ? LIMIT 1 {$lockPenomoran}");
        $stmt->bind_param("s", $tipeTransaksi);
        $stmt->execute();
        $res = $stmt->get_result();
        $config = $res->fetch_assoc();
        $stmt->close();

        // Mapping fallback jika tipe transaksi belum disetting di tabel penomoran
        $defaultConfigMap = [
            'REQUEST'         => ['format' => 'RO-[SHORT_YEAR][MONTH]-[COUNTER]', 'tipe_penomoran' => 2, 'digit_counter' => 5],
            'PURCHASE'        => ['format' => 'PO-[SHORT_YEAR][MONTH]-[COUNTER]', 'tipe_penomoran' => 2, 'digit_counter' => 4],
            'RECEIVING'       => ['format' => 'PN/[SHORT_YEAR][MONTH][DAY]/[COUNTER]', 'tipe_penomoran' => 1, 'digit_counter' => 4],
            'RETUR PO'        => ['format' => 'RT/[COUNTER]/[SHORT_YEAR][MONTH][DAY]', 'tipe_penomoran' => 1, 'digit_counter' => 4],
            'FAKTUR PO'       => ['format' => 'FP[SHORT_YEAR]/[MONTH][DAY]/[COUNTER]', 'tipe_penomoran' => 2, 'digit_counter' => 3],
            'PAYMENT PO'      => ['format' => 'PF[SHORT_YEAR]/[MONTH][DAY]/[COUNTER]', 'tipe_penomoran' => 2, 'digit_counter' => 3],
            'MUTASI BARANG'   => ['format' => 'DI-[SHORT_YEAR][MONTH]-[COUNTER]', 'tipe_penomoran' => 2, 'digit_counter' => 5],
            'ADJUSTMENT STOK' => ['format' => 'ADJ-[SHORT_YEAR][MONTH]-[COUNTER]', 'tipe_penomoran' => 2, 'digit_counter' => 5]
        ];

        $formatPattern = $config['format'] ?? ($defaultConfigMap[$tipeTransaksi]['format'] ?? 'TRX-[SHORT_YEAR][MONTH]-[COUNTER]');
        $tipeReset = isset($config['tipe_penomoran']) ? (int)$config['tipe_penomoran'] : ($defaultConfigMap[$tipeTransaksi]['tipe_penomoran'] ?? 2);
        $digitCounter = isset($config['digit_counter']) ? (int)$config['digit_counter'] : ($defaultConfigMap[$tipeTransaksi]['digit_counter'] ?? 5);

        // 2. Tentukan Tabel & Kolom Target
        $tableMapping = [
            'REQUEST'         => ['table' => 'request_order', 'column' => 'nomor', 'pk' => 'id_request', 'date_col' => 'tanggal_ro'],
            'PURCHASE'        => ['table' => 'purchase_order', 'column' => 'nomor_po', 'pk' => 'id_po', 'date_col' => 'tanggal_po'],
            'RECEIVING'       => ['table' => 'receiving_order', 'column' => 'nomor_rcv', 'pk' => 'id_rcv', 'date_col' => 'tanggal_rcv'],
            'RETUR PO'        => ['table' => 'retur_po', 'column' => 'nomor_po_retur', 'pk' => 'id_po_retur', 'date_col' => 'tanggal_po_retur'],
            'FAKTUR PO'       => ['table' => 'faktur_po', 'column' => 'nomor_faktur', 'pk' => 'id_faktur', 'date_col' => 'created_at'],
            'PAYMENT PO'      => ['table' => 'payment_purchase_detail', 'column' => 'kode_pembayaran', 'pk' => 'id_pembayaran_detail', 'date_col' => 'tanggal_bayar'],
            'MUTASI BARANG'   => ['table' => 'mutasi_order', 'column' => 'kode_mutasi', 'pk' => 'id_mutasi', 'date_col' => 'tanggal_mutasi'],
            'ADJUSTMENT STOK' => ['table' => 'adjustment_stok', 'column' => 'nomor_adjustment', 'pk' => 'id_adjustment', 'date_col' => 'tanggal_adjustment']
        ];

        $target = $tableMapping[$tipeTransaksi] ?? null;
        if (!$target) {
            return [
                'success' => false,
                'nomor' => '',
                'format' => $formatPattern,
                'counter' => 1,
                'message' => "Tipe transaksi '{$tipeTransaksi}' tidak dikenali"
            ];
        }

        // 3. Bangun Prefix/Pola Pencarian Berdasarkan Format & Tipe Reset
        // Format tokens: [YEAR], [SHORT_YEAR], [MONTH], [ROMAN_MONTH], [DAY], [COUNTER]
        $renderedWithoutCounter = str_replace(
            ['[YEAR]', '[SHORT_YEAR]', '[MONTH]', '[ROMAN_MONTH]', '[DAY]'],
            [$yearFull, $yearShort, $month, $romanMonth, $day],
            $formatPattern
        );

        // Pecah template berdasarkan [COUNTER] untuk regex dan query LIKE
        $parts = explode('[COUNTER]', $renderedWithoutCounter);
        $prefixPart = $parts[0] ?? '';
        $suffixPart = $parts[1] ?? '';

        // Query LIKE filter
        $likePattern = $prefixPart . '%' . ($suffixPart !== '' ? $suffixPart : '');

        // Bangun Regex untuk ekstraksi digit counter: ^prefix(\d+)suffix$
        $regexPattern = '/^' . preg_quote($prefixPart, '/') . '(\d+)' . preg_quote($suffixPart, '/') . '$/i';

        // Filter tambahan berdasarkan tipe reset:
        // 0: Tidak reset (sepanjang waktu)
        // 1: Reset harian (tanggal_transaksi = YYYY-MM-DD)
        // 2: Reset bulanan (YEAR = YYYY AND MONTH = MM)
        // 3: Reset tahunan (YEAR = YYYY)
        $dateFilterSql = "";
        $dateCol = $target['date_col'];

        if ($tipeReset === 1) { // Harian
            $dateFilterSql = " AND DATE({$dateCol}) = '" . date('Y-m-d', $timestamp) . "' ";
        } elseif ($tipeReset === 2) { // Bulanan
            $dateFilterSql = " AND YEAR({$dateCol}) = '{$yearFull}' AND MONTH({$dateCol}) = '{$month}' ";
        } elseif ($tipeReset === 3) { // Tahunan
            $dateFilterSql = " AND YEAR({$dateCol}) = '{$yearFull}' ";
        }

        $tableName = $target['table'];
        $columnName = $target['column'];
        $pkName = $target['pk'];

        // Kunci baris penomoran jika berada dalam database transaction ($forUpdate = true)
        $lockSql = $forUpdate ? " FOR UPDATE" : "";

        // Query sequence tertinggi yang sudah terdaftar di database
        $sql = "SELECT {$columnName} FROM {$tableName} WHERE {$columnName} LIKE ? {$dateFilterSql} ORDER BY {$pkName} DESC LIMIT 200 {$lockSql}";
        $stmtSeq = $conn->prepare($sql);
        $stmtSeq->bind_param("s", $likePattern);
        $stmtSeq->execute();
        $resSeq = $stmtSeq->get_result();

        $maxSequence = 0;
        while ($row = $resSeq->fetch_assoc()) {
            $numStr = $row[$columnName] ?? '';
            if (preg_match($regexPattern, $numStr, $matches)) {
                $seq = (int)$matches[1];
                if ($seq > $maxSequence) {
                    $maxSequence = $seq;
                }
            }
        }
        $stmtSeq->close();

        // 4. Hitung Sequence Berikutnya
        $nextSequence = $maxSequence + 1;
        $counterStr = str_pad((string)$nextSequence, $digitCounter, '0', STR_PAD_LEFT);

        // Gabungkan nomor final
        $finalNomor = str_replace('[COUNTER]', $counterStr, $renderedWithoutCounter);

        return [
            'success' => true,
            'nomor' => $finalNomor,
            'format' => $formatPattern,
            'counter' => $nextSequence,
            'digit_counter' => $digitCounter,
            'tipe_reset' => $tipeReset,
            'message' => 'Nomor transaksi berhasil di-generate'
        ];
    }
}

if (!function_exists('generateNomorTransaksiLocked')) {
    /**
     * Khusus dipanggil saat proses SIMPAN transaksi (wajib di dalam $conn->begin_transaction())
     * Mengunci baris penomoran dan baris transaksi untuk mencegah tabrakan / duplicate key multi-user
     */
    function generateNomorTransaksiLocked($conn, string $tipeTransaksi, ?string $dateStr = null): array {
        return generateNomorTransaksi($conn, $tipeTransaksi, $dateStr, true);
    }
}
