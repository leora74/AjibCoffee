<?php
/**
 * buat_pembayaran_qris.php
 * Membuat transaksi QRIS di Midtrans untuk satu pesanan lalu menyimpannya
 * ke tabel pembayaran. POST {id_pesanan}. Aman dipanggil berulang: bila
 * QRIS sudah dibuat, pelanggan hanya diarahkan ke halaman QRIS yang sama.
 */
require_once __DIR__ . '/midtrans.php';

$idPelanggan = require_customer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'message' => 'Metode tidak diizinkan.'], 405);
}

try {
    $id = (int) (body_json()['id_pesanan'] ?? 0);
    $tujuan = 'qris.php?id=' . $id;

    bersihkan_pesanan_kedaluwarsa($connection);

    $connection->begin_transaction();

    try {
        // Kunci baris pesanan agar klik ganda tidak membuat dua QRIS
        $stmt = $connection->prepare(
            'SELECT * FROM pesanan WHERE id_pesanan = ? AND idPelanggan = ? FOR UPDATE'
        );
        $stmt->bind_param('ii', $id, $idPelanggan);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$p) {
            throw new AppException('Pesanan tidak ditemukan.');
        }

        $bayar = ambil_pembayaran($connection, $id);
        if ($bayar) {
            if ($bayar['status_pembayaran'] === 'pending') {
                $connection->commit();
                json_out(['success' => true, 'redirect' => $tujuan]);
            }
            throw new AppException(
                'Pembayaran untuk pesanan ini sudah kedaluwarsa atau dibatalkan.'
            );
        }

        if ($p['status_pesanan'] !== STATUS_MENUNGGU) {
            throw new AppException('Pesanan ini tidak dapat dibayar.');
        }

        $items = [];
        $jumlahItem = 0;
        foreach (ambil_item_pesanan($connection, $id) as $it) {
            $nama = $it['nama'] . ($it['varian'] !== '' ? ' (' . $it['varian'] . ')' : '');
            $items[] = [
                'id'       => (string) $it['id_menu'],
                'price'    => $it['harga'],
                'quantity' => $it['jumlah'],
                'name'     => mb_substr($nama, 0, 50),
            ];
            $jumlahItem += $it['harga'] * $it['jumlah'];
        }

        $total = (int) $p['total_harga'];
        if (!$items || $jumlahItem !== $total) {
            throw new AppException('Rincian pesanan tidak sesuai. Silakan buat pesanan ulang.');
        }

        // Order ID Midtrans harus unik; tampilan ke pelanggan tetap memakai kode_order()
        $orderIdMidtrans = kode_order($id) . '-' . time();
        $qris = midtrans_charge_qris(
            $orderIdMidtrans,
            $total,
            $items,
            $p['nama_penerima'],
            $p['no_telepon_penerima']
        );

        $metode = 'QRIS';
        $statusBayar = 'pending';
        $sekarang = date('Y-m-d H:i:s');

        $stmt = $connection->prepare(
            'INSERT INTO pembayaran
             (id_pesanan, id_transaksi, metode_pembayaran,
              status_pembayaran, tanggal_pembayaran, order_id,
              gross_amount, expiry_time, qr_code_url, qr_string,
              transaction_time)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'isssss' . 'i' . 'ssss',
            $id,
            $qris['id_transaksi'],
            $metode,
            $statusBayar,
            $sekarang,
            $orderIdMidtrans,
            $total,
            $qris['expiry_time'],
            $qris['qr_code_url'],
            $qris['qr_string'],
            $qris['transaction_time']
        );
        $stmt->execute();
        $stmt->close();

        $connection->commit();
    } catch (Throwable $e) {
        $connection->rollback();
        throw $e;
    }

    json_out(['success' => true, 'redirect' => $tujuan]);
} catch (Throwable $e) {
    tangani_error($e);
}
