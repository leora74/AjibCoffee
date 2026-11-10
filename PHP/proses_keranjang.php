<?php
session_start();
require_once __DIR__ . '/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/keranjang.html');
    exit;
}

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
function cartResponse($success, $message = '', $data = []) {
    global $isAjax;
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!$success && session_status() === PHP_SESSION_ACTIVE) $_SESSION['cart_error'] = $message;
    header('Location: ../HTML/keranjang.html');
    exit;
}

$key = (string)($_POST['key'] ?? '');
$aksi = $_POST['aksi'] ?? 'update';

if (!isset($_SESSION['keranjang'][$key])) cartResponse(false, 'Produk tidak ditemukan di keranjang.');

$item = $_SESSION['keranjang'][$key];
$id_menu = (int)$item['id_menu'];

$stmt = $connection->prepare(
    'SELECT harga, harga_hot, harga_ice, stok FROM menu WHERE id_menu = ? LIMIT 1'
);
$stmt->bind_param('i', $id_menu);
$stmt->execute();
$menu = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$menu) {
    unset($_SESSION['keranjang'][$key]);
    cartResponse(false, 'Menu tidak ditemukan.');
}

$jumlah_lama = (int)$item['jumlah'];
$jumlah_baru = $jumlah_lama;

if ($aksi === 'plus') $jumlah_baru++;
elseif ($aksi === 'minus') $jumlah_baru = max(1, $jumlah_baru - 1);
elseif ($aksi === 'update') {
    $jumlah_baru = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT) ?: 1;
    $varian_baru = trim($_POST['varian'] ?? $item['varian']);
} else {
    $varian_baru = $item['varian'];
}

if ($aksi !== 'update') $varian_baru = $item['varian'];

if ($jumlah_baru < 1) $jumlah_baru = 1;

if ($jumlah_baru > (int)$menu['stok']) {
    cartResponse(false, 'Jumlah melebihi stok yang tersedia.');
}

if ($menu['harga'] !== null) {
    $varian_baru = '';
    $harga = (int)$menu['harga'];
} elseif ($varian_baru === 'Hot' && $menu['harga_hot'] !== null) {
    $harga = (int)$menu['harga_hot'];
} elseif ($varian_baru === 'Ice' && $menu['harga_ice'] !== null) {
    $harga = (int)$menu['harga_ice'];
} else {
    cartResponse(false, 'Varian tidak tersedia untuk menu ini.');
}

$new_key = $id_menu . '_' . ($varian_baru !== '' ? strtolower($varian_baru) : 'none');
$existing = ($new_key === $key) ? 0 : (int)($_SESSION['keranjang'][$new_key]['jumlah'] ?? 0);
if ($new_key !== $key && $existing + $jumlah_baru > (int)$menu['stok']) {
    cartResponse(false, 'Total jumlah varian tersebut melebihi stok.');
}

$item_baru = [
    'id_menu' => $id_menu,
    'varian' => $varian_baru,
    'jumlah' => $existing + $jumlah_baru,
    'harga_satuan' => $harga
];

// Perbarui item TANPA memindahkan urutannya di keranjang.
// (unset lalu isi ulang membuat item pindah ke posisi paling bawah.)
if ($new_key === $key) {
    // Hanya jumlah yang berubah: timpa di posisi yang sama.
    $_SESSION['keranjang'][$key] = $item_baru;
} elseif (isset($_SESSION['keranjang'][$new_key])) {
    // Varian baru sudah ada di keranjang: gabung ke item tersebut
    // (posisinya dipertahankan), lalu buang item lama.
    $_SESSION['keranjang'][$new_key] = $item_baru;
    unset($_SESSION['keranjang'][$key]);
} else {
    // Varian berganti: ganti kunci lama dengan kunci baru di tempat yang sama.
    $susunan_baru = [];
    foreach ($_SESSION['keranjang'] as $k => $v) {
        if ((string)$k === $key) {
            $susunan_baru[$new_key] = $item_baru;
        } else {
            $susunan_baru[$k] = $v;
        }
    }
    $_SESSION['keranjang'] = $susunan_baru;
}

cartResponse(true, 'Jumlah keranjang diperbarui.', [
    'key' => $new_key,
    'jumlah' => $existing + $jumlah_baru,
    'harga' => $harga,
    'subtotal' => $harga * ($existing + $jumlah_baru),
    'stok' => (int)$menu['stok']
]);
