<?php
/**
 * data_pembayaran.php?id=12
 * Data untuk halaman Metode Pembayaran dan halaman QRIS.
 * Status pembayaran yang masih pending ditanyakan ke Midtrans.
 */
require_once __DIR__ . '/midtrans.php';

$idPelanggan = require_customer();

try {
    $id = (int) ($_GET['id'] ?? 0);

    if (!ambil_pesanan_milik($connection, $id, $idPelanggan)) {
        json_out(['success' => false, 'message' => 'Pesanan tidak ditemukan.'], 404);
    }

    bersihkan_pesanan_kedaluwarsa($connection);
    sinkron_status_pembayaran($connection, $id);

    // Ambil ulang karena status bisa berubah setelah sinkronisasi
    $p = ambil_pesanan_milik($connection, $id, $idPelanggan);
    $b = ambil_pembayaran($connection, $id);

    $sisa = 0;
    if ($b && $b['expiry_time']) {
        $sisa = max(0, strtotime($b['expiry_time']) - time());
    }

    json_out([
        'success'        => true,
        'id_pesanan'     => $id,
        'kode'           => kode_order($id),
        'total'          => (int) $p['total_harga'],
        'status_pesanan' => $p['status_pesanan'],
        'ada_pembayaran' => (bool) $b,
        'status_bayar'   => $b['status_pembayaran'] ?? '',
        'sisa_detik'     => $sisa,
        'qr_code_url'    => $b['qr_code_url'] ?? '',
        'qr_string'      => $b['qr_string'] ?? '',
        'logo_qris'      => aset_gambar(['qris-logo.png', 'qris-logo.jpg', 'qris-logo.jpeg', 'qris-logo.svg']),
    ]);
} catch (Throwable $e) {
    tangani_error($e);
}
