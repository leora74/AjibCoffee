<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/keranjang.html');
    exit;
}

$selected = $_POST['selected_keys'] ?? [];
if (!is_array($selected)) $selected = [];

$cart = $_SESSION['keranjang'] ?? [];
$valid = [];
foreach ($selected as $key) {
    $key = (string)$key;
    if (isset($cart[$key])) $valid[] = $key;
}

$valid = array_values(array_unique($valid));
if (!$valid) {
    header('Location: ../HTML/keranjang.html?checkout=empty');
    exit;
}

$_SESSION['checkout_keys'] = $valid;
unset($_SESSION['data_pemesanan']);
header('Location: ../HTML/form_pemesanan.php');
exit;
