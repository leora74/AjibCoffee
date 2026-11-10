<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$total = 0;
foreach (($_SESSION['keranjang'] ?? []) as $item) {
    $total += max(0, (int)($item['jumlah'] ?? 0));
}
echo json_encode(['count' => $total]);
