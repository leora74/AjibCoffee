<?php
// Halaman pembayaran QRIS Ajib Coffee
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>QRIS | Ajib Coffee</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/payment.css">
</head>

<body class="qris-body">

<main class="figma-qris-page">

    <header class="figma-green-header">
        <img src="../ASSETS/img/ajib_logo2.png" alt="Ajib Coffee">

        <span class="profile-avatar">
            <i class="bi bi-person-fill"></i>
        </span>
    </header>

    <section class="qris-total-card">

        <div>
            <span>Total</span>
            <strong id="totalBayar">Rp -</strong>
            <small>Order ID <span id="kodeOrder">#-</span></small>
        </div>

        <div class="qris-deadline">
            Bayar dalam
            <strong id="hitungMundur">00.00.00</strong>
        </div>

    </section>

    <div id="pesanHalaman" role="alert" hidden></div>

    <section class="qris-content">

        <div class="qris-merchant">
            <img src="../ASSETS/img/ajib_logo2.png" alt="Ajib Coffee">
        </div>

        <div class="qris-code-placeholder" id="blokQr">
            <p>Memuat kode QRIS...</p>
        </div>

        <p class="qris-nmid" id="nmid" hidden></p>

        <form class="qris-status-form">
            <button type="button" id="btnStatus">
                Lihat Status Pembayaran
            </button>
        </form>

        <p class="qris-demo-note" id="infoStatus" role="status">
            Menghubungkan ke Midtrans...
        </p>

        <a class="qris-change-method" href="main.html">
            Kembali ke menu
        </a>

    </section>

</main>

<script src="../JAVASCRIPT/bayar_umum.js"></script>
<script src="../JAVASCRIPT/pembayaran_qris.js"></script>

</body>
</html>