<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conn.php';

function kategoriMenu($id) {
    if ($id >= 46 && $id <= 54) return 'Non Coffee';
    if ($id >= 55 && $id <= 59) return 'Frappe';
    if ($id >= 60 && $id <= 69) return 'Foods';
    if ($id >= 70 && $id <= 79) return 'Coffee';
    if ($id >= 80 && $id <= 84) return 'Americano Series';
    return 'Indomie Series';
}

$items = [];
$total = 0;

foreach (($_SESSION['keranjang'] ?? []) as $key => $cartItem) {
    $idMenu = (int)($cartItem['id_menu'] ?? 0);
    $stmt = $connection->prepare(
        'SELECT id_menu, nama_menu, harga, harga_hot, harga_ice, stok, gambar
         FROM menu WHERE id_menu = ? LIMIT 1'
    );
    $stmt->bind_param('i', $idMenu);
    $stmt->execute();
    $menu = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$menu) {
        unset($_SESSION['keranjang'][$key]);
        continue;
    }

    $varian = (string)($cartItem['varian'] ?? '');
    if ($menu['harga'] !== null) {
        $varian = '';
        $harga = (int)$menu['harga'];
    } elseif ($varian === 'Hot' && $menu['harga_hot'] !== null) {
        $harga = (int)$menu['harga_hot'];
    } elseif ($varian === 'Ice' && $menu['harga_ice'] !== null) {
        $harga = (int)$menu['harga_ice'];
    } else {
        unset($_SESSION['keranjang'][$key]);
        continue;
    }

    $jumlah = max(1, (int)($cartItem['jumlah'] ?? 1));
    $subtotal = $harga * $jumlah;
    $total += $subtotal;

    $gambar = basename((string)$menu['gambar']);
    $imageFile = __DIR__ . '/../ASSETS/img/' . $gambar;
    $gambarUrl = is_file($imageFile)
        ? '../ASSETS/img/' . rawurlencode($gambar)
        : '../ASSETS/img/cart-placeholder.jpg';

    $items[] = [
        'key' => (string)$key,
        'id_menu' => $idMenu,
        'nama' => $menu['nama_menu'],
        'kategori' => kategoriMenu($idMenu),
        'gambar' => $gambarUrl,
        'varian' => $varian,
        'harga' => $harga,
        'jumlah' => $jumlah,
        'subtotal' => $subtotal,
        'stok' => (int)$menu['stok'],
        'harga_hot' => $menu['harga_hot'] === null ? null : (int)$menu['harga_hot'],
        'harga_ice' => $menu['harga_ice'] === null ? null : (int)$menu['harga_ice'],
        'harga_menu' => $menu['harga'] === null ? null : (int)$menu['harga']
    ];
}

echo json_encode([
    'success' => true,
    'items' => $items,
    'total' => $total,
    'count' => count($items)
], JSON_UNESCAPED_UNICODE);
