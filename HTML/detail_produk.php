<?php
session_start();
require_once __DIR__ . '/../PHP/conn.php';

$id_menu = filter_input(INPUT_GET, 'id_menu', FILTER_VALIDATE_INT);
if (!$id_menu) {
    http_response_code(400);
    $menu = null;
} else {
    $stmt = $connection->prepare('SELECT id_menu, nama_menu, deskripsi, harga, harga_hot, harga_ice, stok, gambar FROM menu WHERE id_menu = ? LIMIT 1');
    $stmt->bind_param('i', $id_menu);
    $stmt->execute();
    $menu = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$menu) http_response_code(404);
}

function rupiah($angka) { return 'Rp ' . number_format((int)$angka, 0, ',', '.'); }
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$gambar = $menu ? basename((string)$menu['gambar']) : '';
$gambarPath = __DIR__ . '/../ASSETS/img/' . $gambar;
$gambarUrl = ($gambar && is_file($gambarPath)) ? '../ASSETS/img/' . rawurlencode($gambar) : '../ASSETS/img/cart-placeholder.jpg';
$variants = [];
if ($menu) {
    if ($menu['harga_hot'] !== null) $variants[] = ['value' => 'Hot', 'label' => 'Panas', 'price' => (int)$menu['harga_hot']];
    if ($menu['harga_ice'] !== null) $variants[] = ['value' => 'Ice', 'label' => 'Dingin', 'price' => (int)$menu['harga_ice']];
    if ($menu['harga'] !== null) $variants[] = ['value' => '', 'label' => 'Reguler', 'price' => (int)$menu['harga']];
}
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $menu ? e($menu['nama_menu']) : 'Produk tidak ditemukan' ?> | Ajib Coffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/main.css">
    <link rel="stylesheet" href="../CSS/detail_produk.css">
</head>
<body>
<div class="page-shell container-xxl px-0">
    <nav class="navbar navbar-expand-md navbar-light custom-navbar">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand brand-logo" href="main.html" aria-label="Ajib Coffee - beranda"><img src="../ASSETS/img/ajib_logo2.png" alt="Logo Ajib Coffee"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAjib" aria-controls="navbarAjib" aria-expanded="false" aria-label="Buka navigasi"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarAjib"><ul class="navbar-nav ms-auto align-items-md-center nav-menu">
                <li class="nav-item"><a class="nav-link" href="main.html">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="about_us.html">Tentang kami</a></li>
                <li class="nav-item"><a class="nav-link cart-link" href="keranjang.html" aria-label="Keranjang"><i class="bi bi-cart3"></i></a></li>
                <li class="nav-item"><a class="nav-link profile-link" href="profile.html" aria-label="Profil akun"><i class="bi bi-person-circle"></i></a></li>
            </ul></div>
        </div>
    </nav>
    <main class="product-detail-wrap">
        <?php if (!$menu): ?>
            <section class="product-not-found"><i class="bi bi-exclamation-circle"></i><h1>Produk tidak ditemukan</h1><p>Menu yang lu cari tidak tersedia.</p><a class="detail-back" href="main.html#daftar">Kembali ke Menu</a></section>
        <?php else: ?>
            <?php if ($status !== ''): ?>
                <div class="detail-alert" role="alert">
                    <?php
                    $messages = ['stock' => 'Jumlah melebihi stok yang tersedia.', 'invalid' => 'Periksa kembali jumlah dan pilihan produk.', 'variant' => 'Pilih jenis produk yang tersedia.', 'not_found' => 'Menu tidak ditemukan.'];
                    echo e($messages[$status] ?? 'Produk belum dapat ditambahkan ke keranjang.');
                    ?>
                </div>
            <?php endif; ?>
            <section class="product-detail-card">
                <div class="product-detail-image"><img src="<?= e($gambarUrl) ?>" alt="<?= e($menu['nama_menu']) ?>"></div>
                <div class="product-detail-info">
                    <span class="product-series">Ajib Coffee Menu</span>
                    <h1><?= e($menu['nama_menu']) ?></h1>
                    <p class="product-price" id="productPrice"><?= $variants ? rupiah($variants[0]['price']) : 'Harga belum tersedia' ?></p>
                    <p class="product-description"><?= e($menu['deskripsi'] ?: 'Nikmati menu pilihan dari Ajib Coffee.') ?></p>
                    <form action="../PHP/tambah_keranjang.php" method="post" id="detailOrderForm">
                        <input type="hidden" name="id_menu" value="<?= (int)$menu['id_menu'] ?>">
                        <div class="detail-controls">
                            <?php if (count($variants) > 1 || ($variants && $variants[0]['value'] !== '')): ?>
                                <fieldset class="variant-picker"><legend>Pilih jenis</legend><div class="variant-options">
                                    <?php foreach ($variants as $index => $variant): ?>
                                        <label class="variant-choice"><input type="radio" name="varian" value="<?= e($variant['value']) ?>" data-price="<?= (int)$variant['price'] ?>" <?= $index === 0 ? 'checked' : '' ?> required><span><?= e($variant['label']) ?></span></label>
                                    <?php endforeach; ?>
                                </div></fieldset>
                            <?php else: ?>
                                <input type="hidden" name="varian" value="">
                            <?php endif; ?>
                            <div class="detail-quantity"><label for="productQuantity">Jumlah</label><div class="quantity-stepper"><button type="button" id="minusQuantity" aria-label="Kurangi jumlah">−</button><input type="number" id="productQuantity" name="jumlah" min="1" max="<?= max(1, (int)$menu['stok']) ?>" value="1" required><button type="button" id="plusQuantity" aria-label="Tambah jumlah">+</button></div><small>Stok tersedia: <?= (int)$menu['stok'] ?></small></div>
                        </div>
                        <button class="detail-add-button" type="submit" <?= (int)$menu['stok'] < 1 || !$variants ? 'disabled' : '' ?>>Tambah ke Keranjang <i class="bi bi-cart-plus"></i></button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../JAVASCRIPT/detail_produk.js"></script>
</body>
</html>
