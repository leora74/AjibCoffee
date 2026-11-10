
<?php
session_start();
require_once __DIR__ . '/conn.php';

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Pastikan pelanggan sudah login
$idPelanggan = (int) ($_SESSION['idPelanggan'] ?? 0);

if ($idPelanggan <= 0) {
    header('Location: ../HTML/login.html');
    exit;
}

// Pastikan request berasal dari form
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/keranjang.html');
    exit;
}

// Pastikan ada produk yang dipilih untuk checkout
$checkoutKeys = $_SESSION['checkout_keys'] ?? [];
$keranjang = $_SESSION['keranjang'] ?? [];

if (!is_array($checkoutKeys) || !$checkoutKeys) {
    header('Location: ../HTML/keranjang.html?checkout=empty');
    exit;
}

// Ambil data penerima
$jenisInput = trim($_POST['jenis_layanan'] ?? 'Delivery');
$jenis = in_array($jenisInput, ['Pick Up', 'Pickup'], true)
    ? 'Pickup'
    : 'Delivery';

$nama = trim($_POST['nama_penerima'] ?? '');
$telepon = trim($_POST['no_telepon_penerima'] ?? '');
$lokasi = trim($_POST['lokasi_pengantaran'] ?? '');
$detail = trim($_POST['detail_lokasi'] ?? '');
$catatan = trim($_POST['catatan_pesanan'] ?? '');

if (
    $nama === '' ||
    $telepon === '' ||
    strlen($telepon) > 13 ||
    ($jenis === 'Delivery' && ($lokasi === '' || $detail === ''))
) {
    $_SESSION['form_error'] = 'Periksa kembali data pemesanan Anda.';
    header('Location: ../HTML/form_pemesanan.php?status=invalid');
    exit;
}

// Ambil hanya produk yang dipilih ketika checkout
$itemCheckout = [];

foreach ($checkoutKeys as $key) {
    if (!isset($keranjang[$key])) {
        continue;
    }

    $item = $keranjang[$key];
    $idMenu = (int) ($item['id_menu'] ?? 0);
    $jumlah = (int) ($item['jumlah'] ?? 0);
    $varian = trim($item['varian'] ?? '');

    if ($idMenu <= 0 || $jumlah <= 0) {
        continue;
    }

    $itemCheckout[] = [
        'key' => (string) $key,
        'id_menu' => $idMenu,
        'jumlah' => $jumlah,
        'varian' => $varian
    ];
}

if (!$itemCheckout) {
    $_SESSION['form_error'] = 'Produk checkout tidak ditemukan.';
    header('Location: ../HTML/keranjang.html');
    exit;
}

$transaksiAktif = false;

try {
    $connection->begin_transaction();
    $transaksiAktif = true;

    $menuCache = [];
    $stokTerpakai = [];
    $detailPesanan = [];
    $totalHarga = 0;

    // Validasi harga dan stok berdasarkan database
    foreach ($itemCheckout as $item) {
        $idMenu = $item['id_menu'];
        $jumlah = $item['jumlah'];
        $varian = $item['varian'];

        // Kunci data menu agar stok tidak berubah selama transaksi
        if (!isset($menuCache[$idMenu])) {
            $stmt = $connection->prepare(
                'SELECT harga, harga_hot, harga_ice, stok
                 FROM menu
                 WHERE id_menu = ?
                 FOR UPDATE'
            );

            $stmt->bind_param('i', $idMenu);
            $stmt->execute();

            $menuCache[$idMenu] = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$menuCache[$idMenu]) {
                throw new Exception('Salah satu menu tidak ditemukan.');
            }

            $stokTerpakai[$idMenu] = 0;
        }

        $menu = $menuCache[$idMenu];

        // Tentukan harga berdasarkan jenis menu
        if ($menu['harga'] !== null) {
            $varian = '';
            $harga = (int) $menu['harga'];
        } elseif (
            strtolower($varian) === 'hot' &&
            $menu['harga_hot'] !== null
        ) {
            $varian = 'Hot';
            $harga = (int) $menu['harga_hot'];
        } elseif (
            strtolower($varian) === 'ice' &&
            $menu['harga_ice'] !== null
        ) {
            $varian = 'Ice';
            $harga = (int) $menu['harga_ice'];
        } else {
            throw new Exception('Varian menu tidak tersedia.');
        }

        if ($harga <= 0) {
            throw new Exception('Harga menu tidak valid.');
        }

        $subtotal = $harga * $jumlah;
        $totalHarga += $subtotal;

        $stokTerpakai[$idMenu] += $jumlah;

        $detailPesanan[] = [
            'id_menu' => $idMenu,
            'jumlah' => $jumlah,
            'harga_satuan' => $harga,
            'subtotal' => $subtotal
        ];
    }

    // Validasi total stok setiap menu
    foreach ($stokTerpakai as $idMenu => $jumlahDipakai) {
        if ($jumlahDipakai > (int) $menuCache[$idMenu]['stok']) {
            throw new Exception('Stok menu tidak mencukupi.');
        }
    }

    if ($totalHarga <= 0) {
        throw new Exception('Total pesanan tidak valid.');
    }

    // id_admin = 1 mengikuti data pesanan yang sudah ada di database
    $idAdmin = 1;
    $tanggal = date('Y-m-d H:i:s');
    $status = 'menunggu_pembayaran';

    $lokasiDB = $jenis === 'Delivery' ? $lokasi : '';
    $detailDB = $jenis === 'Delivery' ? $detail : '';

    // Simpan pesanan utama
    $stmt = $connection->prepare(
        'INSERT INTO pesanan (
            idPelanggan,
            id_admin,
            tanggal_pesanan,
            jenis_layanan,
            total_harga,
            status_pesanan,
            nama_penerima,
            no_telepon_penerima,
            lokasi_pengantaran,
            detail_lokasi,
            catatan_pesanan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->bind_param(
        'iississssss',
        $idPelanggan,
        $idAdmin,
        $tanggal,
        $jenis,
        $totalHarga,
        $status,
        $nama,
        $telepon,
        $lokasiDB,
        $detailDB,
        $catatan
    );

    $stmt->execute();

    // Ambil ID pesanan yang baru dibuat
    $idPesanan = (int) $connection->insert_id;
    $stmt->close();

    // Simpan detail pesanan
    $stmtDetail = $connection->prepare(
        'INSERT INTO detail_pesanan (
            id_pesanan,
            id_menu,
            jumlah,
            harga_satuan,
            subtotal
        ) VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($detailPesanan as $detailItem) {
        $idMenu = $detailItem['id_menu'];
        $jumlah = $detailItem['jumlah'];
        $harga = $detailItem['harga_satuan'];
        $subtotal = $detailItem['subtotal'];

        $stmtDetail->bind_param(
            'iiiii',
            $idPesanan,
            $idMenu,
            $jumlah,
            $harga,
            $subtotal
        );

        $stmtDetail->execute();
    }

    $stmtDetail->close();

    // Kurangi stok sesuai jumlah yang dipesan
    $stmtStok = $connection->prepare(
        'UPDATE menu
         SET stok = stok - ?
         WHERE id_menu = ? AND stok >= ?'
    );

    foreach ($stokTerpakai as $idMenu => $jumlahDipakai) {
        $stmtStok->bind_param(
            'iii',
            $jumlahDipakai,
            $idMenu,
            $jumlahDipakai
        );

        $stmtStok->execute();

        if ($stmtStok->affected_rows !== 1) {
            throw new Exception('Gagal memperbarui stok menu.');
        }
    }

    $stmtStok->close();

    // Semua penyimpanan berhasil
    $connection->commit();
    $transaksiAktif = false;

    // Hapus hanya produk yang sudah berhasil di-checkout
    foreach ($checkoutKeys as $key) {
        unset($_SESSION['keranjang'][$key]);
    }

    if (empty($_SESSION['keranjang'])) {
        unset($_SESSION['keranjang']);
    }

    unset(
        $_SESSION['checkout_keys'],
        $_SESSION['data_pemesanan']
    );

    // Arahkan pelanggan dengan membawa ID database
    header(
        'Location: ../HTML/metode_pembayaran.html?id=' .
        $idPesanan
    );
    exit;

} catch (Throwable $e) {
    if ($transaksiAktif) {
        $connection->rollback();
    }

    error_log('Simpan pesanan gagal: ' . $e->getMessage());

    $_SESSION['form_error'] =
        'Pesanan gagal disimpan. Silakan periksa kembali data dan stok menu.';

    header('Location: ../HTML/form_pemesanan.php?status=error');
    exit;
}
