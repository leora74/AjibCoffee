<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['data_pemesanan']) || empty($_SESSION['metode_pembayaran'])) {
    header('Location: ../HTML/keranjang.html'); exit;
}
$_SESSION['hasil_transaksi_demo'] = [
    'kode_pesanan' => $_SESSION['kode_pesanan_demo'] ?? ('AJ' . date('ymdHis')),
    'metode' => $_SESSION['metode_pembayaran'],
    'provider' => $_SESSION['provider_ewallet'] ?? '',
    'status' => 'Berhasil (Simulasi)',
    'tanggal' => date('d M Y H:i')
];
header('Location: ../HTML/detail_transaksi.php'); exit;
