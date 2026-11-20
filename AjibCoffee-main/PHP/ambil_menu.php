<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conn.php';

$id_menu = filter_input(INPUT_GET, 'id_menu', FILTER_VALIDATE_INT);
if (!$id_menu) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Menu tidak valid.']);
    exit;
}

$stmt = $connection->prepare(
    'SELECT id_menu, nama_menu, harga, harga_hot, harga_ice, stok
     FROM menu WHERE id_menu = ? LIMIT 1'
);
$stmt->bind_param('i', $id_menu);
$stmt->execute();
$menu = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$menu) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Menu tidak ditemukan.']);
    exit;
}

$variants = [];
if ($menu['harga_hot'] !== null) $variants[] = ['value' => 'Hot', 'price' => (int)$menu['harga_hot']];
if ($menu['harga_ice'] !== null) $variants[] = ['value' => 'Ice', 'price' => (int)$menu['harga_ice']];
if ($menu['harga'] !== null) $variants[] = ['value' => '', 'price' => (int)$menu['harga']];

echo json_encode([
    'success' => true,
    'menu' => [
        'id_menu' => (int)$menu['id_menu'],
        'nama_menu' => $menu['nama_menu'],
        'stok' => (int)$menu['stok'],
        'variants' => $variants
    ]
]);
