<?php
session_start();
require_once __DIR__ . '/../PHP/conn.php';

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function rupiah($value) { return 'Rp ' . number_format((int)$value, 0, ',', '.'); }

$keys = $_SESSION['checkout_keys'] ?? [];
$cart = $_SESSION['keranjang'] ?? [];
if (!$keys || !is_array($keys)) {
    header('Location: keranjang.html');
    exit;
}

$items = [];
$subtotal = 0;
foreach ($keys as $key) {
    if (!isset($cart[$key])) continue;
    $cartItem = $cart[$key];
    $id = (int)($cartItem['id_menu'] ?? 0);
    $stmt = $connection->prepare('SELECT id_menu, nama_menu, harga, harga_hot, harga_ice, stok, gambar FROM menu WHERE id_menu = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $menu = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$menu) continue;

    $varian = (string)($cartItem['varian'] ?? '');
    if ($menu['harga'] !== null) {
        $varian = '';
        $harga = (int)$menu['harga'];
    } elseif ($varian === 'Hot' && $menu['harga_hot'] !== null) {
        $harga = (int)$menu['harga_hot'];
    } elseif ($varian === 'Ice' && $menu['harga_ice'] !== null) {
        $harga = (int)$menu['harga_ice'];
    } else {
        continue;
    }

    $jumlah = max(1, (int)($cartItem['jumlah'] ?? 1));
    $lineTotal = $harga * $jumlah;
    $subtotal += $lineTotal;
    $gambar = basename((string)$menu['gambar']);
    $imagePath = __DIR__ . '/../ASSETS/img/' . $gambar;
    $imageUrl = ($gambar && is_file($imagePath)) ? '../ASSETS/img/' . rawurlencode($gambar) : '../ASSETS/img/cart-placeholder.jpg';

    $items[] = [
        'nama' => $menu['nama_menu'],
        'varian' => $varian,
        'jumlah' => $jumlah,
        'harga' => $harga,
        'subtotal' => $lineTotal,
        'gambar' => $imageUrl
    ];
}

if (!$items) {
    unset($_SESSION['checkout_keys']);
    header('Location: keranjang.html');
    exit;
}

$discount = 0;
$total = max(0, $subtotal - $discount);
$status = $_GET['status'] ?? '';
$old = $_SESSION['data_pemesanan'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pemesanan | Ajib Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/form_pemesanan.css">
</head>
<body>
<div class="order-page">
    <nav class="navbar navbar-expand-md order-navbar">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand brand-logo" href="main.html"><img src="../ASSETS/img/ajib_logo2.png" alt="Ajib Coffee"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAjib" aria-controls="navbarAjib" aria-expanded="false" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarAjib">
                <ul class="navbar-nav ms-auto align-items-md-center nav-menu">
                    <li class="nav-item"><a class="nav-link" href="main.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about_us.html">About us</a></li>
                    <li class="nav-item"><a class="nav-link cart-link" href="keranjang.html" aria-label="Keranjang"><i class="bi bi-cart3"></i></a></li>
                    <li class="nav-item"><a class="nav-link profile-link" href="profile.html" aria-label="Profil"><i class="bi bi-person-circle"></i></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="order-main">
        <form class="order-form" action="../PHP/simpan_form_pemesanan.php" method="post" id="orderForm">
            <section class="customer-section">
                <h1><i class="bi bi-info-circle"></i> Informasi Pengiriman</h1>
                <div class="service-switch" role="group" aria-label="Jenis layanan">
                    <label class="service-option">
                        <input type="radio" name="jenis_layanan" value="Delivery" <?= (($old['jenis_layanan'] ?? 'Delivery') === 'Delivery') ? 'checked' : '' ?>>
                        <span><i class="bi bi-truck"></i> Delivery</span>
                    </label>
                    <label class="service-option">
                        <input type="radio" name="jenis_layanan" value="Pick Up" <?= (($old['jenis_layanan'] ?? '') === 'Pick Up') ? 'checked' : '' ?>>
                        <span><i class="bi bi-shop"></i> Pick Up</span>
                    </label>
                </div>

                <?php if ($status === 'saved'): ?>
                    <div class="form-notice">Informasi pemesanan tersimpan sementara. Integrasi pembayaran akan dibuat pada tahap berikutnya.</div>
                <?php elseif ($status === 'invalid'): ?>
                    <div class="form-error">Periksa kembali nama dan nomor telepon pemesan.</div>
                <?php endif; ?>

                <div class="field-group">
                    <label for="nama_penerima">Nama Pemesan <span>*</span></label>
                    <input id="nama_penerima" name="nama_penerima" type="text" maxlength="100" value="<?= e($old['nama_penerima'] ?? '') ?>" placeholder="Masukkan nama pemesan" required>
                </div>
                <div class="field-group">
                    <label for="no_telepon_penerima">Nomor Telepon <span>*</span></label>
                    <input id="no_telepon_penerima" name="no_telepon_penerima" type="tel" inputmode="numeric" maxlength="20" value="<?= e($old['no_telepon_penerima'] ?? '') ?>" placeholder="Masukkan nomor telepon" required>
                </div>

                <div id="deliveryFields">
                    <div class="field-group">
                        <label for="lokasi_pengantaran">Tempat Pengiriman <span>*</span></label>
                        <input id="lokasi_pengantaran" name="lokasi_pengantaran" type="text" maxlength="150" value="<?= e($old['lokasi_pengantaran'] ?? '') ?>" placeholder="Contoh: GSG">
                    </div>
                    <div class="field-group">
                        <label for="detail_lokasi">Detail Tempat Pengiriman <span>*</span></label>
                        <input id="detail_lokasi" name="detail_lokasi" type="text" maxlength="255" value="<?= e($old['detail_lokasi'] ?? '') ?>" placeholder="Contoh: Blok C lantai 5, ruang 501">
                    </div>
                </div>

                <div class="field-group">
                    <label for="catatan_pesanan">Catatan (Opsional)</label>
                    <textarea id="catatan_pesanan" name="catatan_pesanan" rows="2" maxlength="500" placeholder="Tambahkan catatan untuk pesanan"><?= e($old['catatan_pesanan'] ?? '') ?></textarea>
                </div>
            </section>

            <aside class="order-summary">
                <div class="summary-items">
                    <?php foreach ($items as $item): ?>
                        <article class="summary-item">
                            <img src="<?= e($item['gambar']) ?>" alt="<?= e($item['nama']) ?>">
                            <div class="summary-product">
                                <strong><?= e($item['nama']) ?></strong>
                                <span class="summary-variant"><?= e(['Hot' => 'Panas', 'Ice' => 'Dingin'][$item['varian']] ?? ($item['varian'] ?: 'Reguler')) ?> · <?= (int)$item['jumlah'] ?>x</span>
                            </div>
                            <span class="summary-price"><?= rupiah($item['subtotal']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="summary-lines">
                    <div><span>Subtotal</span><span><?= rupiah($subtotal) ?></span></div>
                    <div><span>Diskon</span><span><?= rupiah($discount) ?></span></div>
                </div>
                <div class="summary-total"><span>Total</span><strong><?= rupiah($total) ?></strong></div>
                <button class="payment-button" type="submit">Pilih Metode Pembayaran</button>
                <a class="back-cart" href="keranjang.html">Kembali ke Keranjang</a>
            </aside>
        </form>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../JAVASCRIPT/form_pemesanan.js"></script>
</body>
</html>
