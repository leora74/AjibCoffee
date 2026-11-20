<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['data_pemesanan'])) {
    header('Location: ../HTML/keranjang.html'); exit;
}
$method = $_POST['metode_pembayaran'] ?? '';
if (!in_array($method, ['QRIS', 'E-Wallet'], true)) {
    header('Location: ../HTML/pembayaran.php?error=method'); exit;
}
$provider = $_POST['provider_ewallet'] ?? 'DANA';
$allowed = ['DANA', 'GoPay', 'OVO', 'ShopeePay'];
if (!in_array($provider, $allowed, true)) $provider = 'DANA';
$_SESSION['metode_pembayaran'] = $method;
$_SESSION['provider_ewallet'] = $method === 'E-Wallet' ? $provider : '';
header('Location: ../HTML/qris.php'); exit;
