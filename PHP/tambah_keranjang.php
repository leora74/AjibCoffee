<?php
session_start();
require_once __DIR__ . '/conn.php';

function kembali($status, $message = '') {
    $id = isset($_POST['id_menu']) ? (int)$_POST['id_menu'] : 0;
    if ($status === 'added') {
        $url = '../HTML/keranjang.html?cart_status=added';
    } else {
        $url = '../HTML/detail_produk.php?id_menu=' . $id . '&status=' . urlencode($status);
    }
    if ($message !== '') $url .= '&message=' . urlencode($message);
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') kembali('invalid');

$id_menu = filter_input(INPUT_POST, 'id_menu', FILTER_VALIDATE_INT);
$jumlah = filter_input(INPUT_POST, 'jumlah', FILTER_VALIDATE_INT);
$varian = trim($_POST['varian'] ?? '');

if (!$id_menu || !$jumlah || $jumlah < 1) kembali('invalid');

$stmt = $connection->prepare(
    'SELECT id_menu, harga, harga_hot, harga_ice, stok
     FROM menu WHERE id_menu = ? LIMIT 1'
);
$stmt->bind_param('i', $id_menu);
$stmt->execute();
$menu = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$menu) kembali('not_found');
if ($jumlah > (int)$menu['stok']) kembali('stock');

if ($menu['harga'] !== null) {
    if ($varian !== '') kembali('variant');
    $harga = (int)$menu['harga'];
    $varian = '';
} elseif ($varian === 'Hot' && $menu['harga_hot'] !== null) {
    $harga = (int)$menu['harga_hot'];
} elseif ($varian === 'Ice' && $menu['harga_ice'] !== null) {
    $harga = (int)$menu['harga_ice'];
} else {
    kembali('variant');
}

if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];
$key = $id_menu . '_' . ($varian !== '' ? strtolower($varian) : 'none');
$jumlah_lama = (int)($_SESSION['keranjang'][$key]['jumlah'] ?? 0);
if ($jumlah_lama + $jumlah > (int)$menu['stok']) kembali('stock');

$itemBaru = [
    'id_menu' => (int)$id_menu,
    'varian' => $varian,
    'jumlah' => $jumlah_lama + $jumlah,
    'harga_satuan' => $harga
];
// Produk yang baru ditambahkan selalu dipindahkan ke posisi paling atas.
unset($_SESSION['keranjang'][$key]);
$_SESSION['keranjang'] = [$key => $itemBaru] + $_SESSION['keranjang'];

kembali('added');
