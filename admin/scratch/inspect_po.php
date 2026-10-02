<?php
require_once __DIR__ . '/../../config/koneksi.php';

echo "=== PURCHASE_ORDER COLUMNS ===\n";
$res = $conn->query("SHOW COLUMNS FROM purchase_order");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}

echo "\n=== PURCHASE_ORDER_DETAIL COLUMNS ===\n";
$res = $conn->query("SHOW COLUMNS FROM purchase_order_detail");
while ($r = $res->fetch_assoc()) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
