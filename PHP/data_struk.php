<?php
/**
 * data_struk.php
 * Mengambil struk pesanan yang pembayarannya sudah berhasil.
 */
require_once __DIR__ . '/midtrans.php';

$idPelanggan = require_customer();

try {
    $id = (int) ($_GET['id'] ?? 0);

    $p = ambil_pesanan_milik($connection, $id, $idPelanggan);

    if (!$p) {
        json_out([
            'success' => false,
            'message' => 'Transaksi tidak ditemukan.'
        ], 404);
    }

    // Perbarui status terbaru dari Midtrans
    sinkron_status_pembayaran($connection, $id);

    // Ambil ulang data setelah sinkronisasi
    $p = ambil_pesanan_milik($connection, $id, $idPelanggan);
    $b = ambil_pembayaran($connection, $id);

    $batal = $p['status_pesanan'] === STATUS_BATAL;

    $sudahDibayar = (
        $b &&
        $b['status_pembayaran'] === 'settlement'
    );

    // Struk hanya tersedia setelah pembayaran berhasil
    if ($batal || !$sudahDibayar ||
        $p['status_pesanan'] === STATUS_MENUNGGU) {

        json_out([
            'success' => false,
            'belum_dibayar' => !$batal,
            'id_pesanan' => $id,
            'message' => $batal
                ? 'Pesanan ini dibatalkan, sehingga tidak ada struk.'
                : 'Struk tersedia setelah pembayaran berhasil.'
        ], 422);
    }

    // Ambil rincian produk
    $items = ambil_item_pesanan($connection, $id);

    $subtotal = 0;

    foreach ($items as $it) {
        $subtotal += (int) $it['subtotal'];
    }

    json_out([
        'success' => true,
        'struk' => [
            'kode' => kode_order($id),
            'nama' => $p['nama_penerima'],
            'tanggal' => date(
                'Y-m-d',
                strtotime($p['tanggal_pesanan'])
            ),
            'jenis' => in_array(
                $p['jenis_layanan'],
                ['Pickup', 'Pick Up'],
                true
            ) ? 'Pick Up' : 'Delivery',

            'items' => $items,
            'subtotal' => $subtotal,
            'total' => (int) $p['total_harga'],

            'qr_ulasan' => aset_gambar([
                'qr-ulasan.png',
                'qr-ulasan.jpg',
                'qr-ulasan.jpeg'
            ])
        ]
    ]);

} catch (Throwable $e) {
    tangani_error($e);
}