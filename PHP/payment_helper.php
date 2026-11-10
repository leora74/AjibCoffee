<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/conn.php';

function ajib_e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function ajib_rupiah($value): string {
    return 'Rp ' . number_format((int)$value, 0, ',', '.');
}
function ajib_payment_data(mysqli $connection): array {
    $keys = $_SESSION['checkout_keys'] ?? [];
    $cart = $_SESSION['keranjang'] ?? [];
    $customer = $_SESSION['idPelanggan'] ?? null;
    $order = $_SESSION['data_pemesanan'] ?? null;

    if (!$customer || !$keys || !is_array($keys) || !is_array($order)) {
        header('Location: ../HTML/keranjang.html?checkout=required');
        exit;
    }

    $items = [];
    $subtotal = 0;
    $stmt = $connection->prepare(
        'SELECT id_menu, nama_menu, harga, harga_hot, harga_ice, stok, gambar
         FROM menu WHERE id_menu = ? LIMIT 1'
    );
    foreach ($keys as $key) {
        if (!isset($cart[$key])) continue;
        $cartItem = $cart[$key];
        $id = (int)($cartItem['id_menu'] ?? 0);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $menu = $stmt->get_result()->fetch_assoc();
        if (!$menu) continue;

        $variant = (string)($cartItem['varian'] ?? '');
        if ($menu['harga'] !== null) {
            $variant = '';
            $price = (int)$menu['harga'];
        } elseif ($variant === 'Hot' && $menu['harga_hot'] !== null) {
            $price = (int)$menu['harga_hot'];
        } elseif ($variant === 'Ice' && $menu['harga_ice'] !== null) {
            $price = (int)$menu['harga_ice'];
        } else {
            continue;
        }
        $qty = max(1, (int)($cartItem['jumlah'] ?? 1));
        $line = $price * $qty;
        $subtotal += $line;
        $filename = basename((string)$menu['gambar']);
        $imgPath = __DIR__ . '/../ASSETS/img/' . $filename;
        $image = ($filename && is_file($imgPath))
            ? '../ASSETS/img/' . rawurlencode($filename)
            : '../ASSETS/img/cart-placeholder.jpg';
        $items[] = [
            'id_menu' => (int)$menu['id_menu'], 'nama' => $menu['nama_menu'],
            'varian' => $variant, 'jumlah' => $qty, 'harga' => $price,
            'subtotal' => $line, 'gambar' => $image
        ];
    }
    $stmt->close();
    if (!$items) {
        header('Location: ../HTML/keranjang.html?checkout=empty');
        exit;
    }
    return [
        'items' => $items, 'subtotal' => $subtotal, 'total' => $subtotal,
        'order' => $order, 'customer_id' => (int)$customer
    ];
}
