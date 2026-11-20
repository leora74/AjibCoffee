<?php
session_start();
require_once __DIR__ . '/../PHP/payment_helper.php';
$data = ajib_payment_data($connection);
$method = $_SESSION['metode_pembayaran'] ?? 'QRIS';
$provider = $_SESSION['provider_ewallet'] ?? 'DANA';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pilih Pembayaran | Ajib Coffee</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../CSS/payment.css">
</head>
<body class="payment-choice-body">
<main class="figma-payment-page">
    <header class="figma-green-header"><img src="../ASSETS/img/ajib_logo2.png" alt="Ajib Coffee"><span class="profile-avatar"><i class="bi bi-person-fill"></i></span></header>
    <section class="payment-total-card">
        <span>Total</span><strong><?= ajib_rupiah($data['total']) ?></strong>
        <small>Order ID #<?= ajib_e('AJ' . date('ymd') . str_pad((string)$data['customer_id'], 4, '0', STR_PAD_LEFT)) ?></small>
    </section>
    <section class="figma-payment-content">
        <form action="../PHP/proses_pembayaran.php" method="post" id="paymentForm">
            <p class="payment-section-label">Metode Pembayaran</p>
            <div class="method-group">
                <label class="method-option figma-method">
                    <input type="radio" name="metode_pembayaran" value="QRIS" <?= $method==='QRIS'?'checked':'' ?>>
                    <span class="figma-method-icon qris-icon"><i class="bi bi-qr-code"></i></span>
                    <span class="method-copy"><strong>QRIS</strong><small>Scan QR menggunakan aplikasi pembayaran.</small></span>
                    <i class="bi bi-chevron-right method-chevron"></i>
                </label>
                <label class="method-option figma-method">
                    <input type="radio" name="metode_pembayaran" value="E-Wallet" <?= $method==='E-Wallet'?'checked':'' ?>>
                    <span class="figma-method-icon wallet-icon"><i class="bi bi-wallet2"></i></span>
                    <span class="method-copy"><strong>E-Wallet</strong><small>Pilih dompet digital yang ingin digunakan.</small></span>
                    <i class="bi bi-chevron-right method-chevron"></i>
                </label>
                <div class="wallet-options figma-wallet-options" id="walletOptions">
                    <p>Pilih E-Wallet</p>
                    <label><input type="radio" name="provider_ewallet" value="DANA" <?= $provider==='DANA'?'checked':'' ?>><span class="wallet-logo dana">DANA</span></label>
                    <label><input type="radio" name="provider_ewallet" value="GoPay" <?= $provider==='GoPay'?'checked':'' ?>><span class="wallet-logo gopay">GoPay</span></label>
                    <label><input type="radio" name="provider_ewallet" value="OVO" <?= $provider==='OVO'?'checked':'' ?>><span class="wallet-logo ovo">OVO</span></label>
                    <label><input type="radio" name="provider_ewallet" value="ShopeePay" <?= $provider==='ShopeePay'?'checked':'' ?>><span class="wallet-logo shopee">ShopeePay</span></label>
                </div>
            </div>
            <button class="figma-continue-btn" type="submit">Lanjutkan <i class="bi bi-arrow-right"></i></button>
        </form>
    </section>
</main>
<script src="../JAVASCRIPT/payment.js"></script>
</body></html>
