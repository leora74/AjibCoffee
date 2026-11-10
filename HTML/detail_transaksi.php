<?php
session_start();
require_once __DIR__ . '/../PHP/payment_helper.php';
$data = ajib_payment_data($connection);
$result = $_SESSION['hasil_transaksi_demo'] ?? null;
if (!$result) { header('Location: pembayaran.php'); exit; }
$order = $data['order'];
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Detail Transaksi | Ajib Coffee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet"><link rel="stylesheet" href="../CSS/payment.css"></head>
<body class="receipt-body"><div class="receipt-site">
<nav class="receipt-navbar"><a href="main.html" class="receipt-logo"><img src="../ASSETS/img/ajib_logo2.png" alt="Ajib Coffee"></a><div class="receipt-nav-links"><a href="main.html">Beranda</a><a href="about_us.html">Tentang Kami</a><a href="keranjang.html"><i class="bi bi-cart3"></i></a><span class="profile-avatar"><i class="bi bi-person-fill"></i></span></div></nav>
<main class="receipt-background"><article class="figma-receipt">
<header class="receipt-brand"><h1>AJIB</h1><h2>Coffe &amp; Caffe</h2></header>
<section class="receipt-info">
<div><span>Nama Pelanggan:</span><b><?= ajib_e($order['nama_penerima'] ?? '-') ?></b></div>
<div><span>Nomor Pesanan:</span><b><?= ajib_e($result['kode_pesanan']) ?></b></div>
<div><span>Tanggal:</span><b><?= ajib_e($result['tanggal']) ?></b></div>
<div><span>Jenis Layanan:</span><b><?= ajib_e($order['jenis_layanan'] ?? '-') ?></b></div>
</section>
<div class="receipt-rule"></div><h3>Rincian Produk</h3>
<section class="receipt-products"><?php foreach($data['items'] as $item): ?><div><span><?= (int)$item['jumlah'] ?> <?= ajib_e($item['nama']) ?> <?= $item['varian'] ? ajib_e(['Hot' => 'Panas', 'Ice' => 'Dingin'][$item['varian']] ?? $item['varian']) : '' ?></span><b><?= ajib_rupiah($item['subtotal']) ?></b></div><?php endforeach; ?></section>
<div class="receipt-rule"></div>
<section class="receipt-prices"><div><span>Subtotal:</span><b><?= ajib_rupiah($data['subtotal']) ?></b></div><div><span>Total:</span><b><?= ajib_rupiah($data['total']) ?></b></div></section>
<div class="receipt-rule"></div>
<footer class="receipt-footer">Terima kasih atas kunjunganmu.<br>Untuk kritik dan saran, hubungi:<br>+62 858-7108-9280</footer>
<div class="review-label">Pindai kode QR untuk memberikan ulasan:</div><div class="review-qr-placeholder" aria-label="QR ulasan demo"><div class="qr-pattern"></div></div>
<div class="receipt-bottom-actions"><a href="main.html">Kembali ke Beranda</a><button type="button" onclick="window.print()">Cetak Struk</button></div>
</article></main></div></body></html>
