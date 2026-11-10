<?php
/**
 * midtrans.php
 * Integrasi Midtrans Core API (QRIS) dan sinkronisasi status pembayaran.
 */

require_once __DIR__ . '/fungsi_pesanan.php';

function midtrans_config(): array
{
    $file = __DIR__ . '/config_midtrans.php';
    if (!is_file($file)) {
        throw new AppException(
            'Pembayaran belum dikonfigurasi. Hubungi admin Ajib Coffee.'
        );
    }

    $config = require $file;
    if (empty($config['server_key'])) {
        throw new AppException('Server Key Midtrans belum diisi.');
    }
    return $config;
}

/* Kirim request ke API Midtrans, hasilnya berupa array */
function midtrans_request(string $method, string $path, ?array $body = null): array
{
    $config = midtrans_config();
    $base = !empty($config['is_production'])
        ? 'https://api.midtrans.com'
        : 'https://api.sandbox.midtrans.com';

    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($config['server_key'] . ':'),
        ],
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno !== 0 || $response === false) {
        throw new AppException(
            'Tidak dapat terhubung ke Midtrans. Coba beberapa saat lagi.'
        );
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        throw new AppException('Respons Midtrans tidak valid.');
    }
    return $data;
}

/*
 * Buat transaksi QRIS.
 * $items: [['id' => '..', 'price' => int, 'quantity' => int, 'name' => '..']]
 * Total semua item HARUS sama dengan $total, jika tidak Midtrans menolak.
 */
function midtrans_charge_qris(
    string $orderId,
    int $total,
    array $items,
    string $nama,
    string $telepon
): array {
    $data = midtrans_request('POST', '/v2/charge', [
        'payment_type'        => 'qris',
        'transaction_details' => [
            'order_id'     => $orderId,
            'gross_amount' => $total,
        ],
        'qris'             => ['acquirer' => 'gopay'],
        'item_details'     => $items,
        'customer_details' => [
            'first_name' => mb_substr($nama, 0, 50),
            'phone'      => $telepon,
        ],
        'custom_expiry' => [
            'expiry_duration' => 10,
            'unit'            => 'minute',
        ],
    ]);

    $kode = (string) ($data['status_code'] ?? '');
    if ($kode !== '201' && $kode !== '200') {
        error_log('Midtrans charge gagal: ' . json_encode($data));
        throw new AppException(
            'Pembayaran QRIS gagal dibuat: ' .
            ($data['status_message'] ?? 'kesalahan tidak diketahui')
        );
    }

    $qrUrl = '';
    foreach (($data['actions'] ?? []) as $aksi) {
        if (($aksi['name'] ?? '') === 'generate-qr-code') {
            $qrUrl = (string) $aksi['url'];
        }
    }

    return [
        'id_transaksi'     => (string) ($data['transaction_id'] ?? ''),
        'transaction_time' => $data['transaction_time'] ?? date('Y-m-d H:i:s'),
        'expiry_time'      => $data['expiry_time'] ?? null,
        'qr_code_url'      => $qrUrl,
        'qr_string'        => (string) ($data['qr_string'] ?? ''),
    ];
}

/*
 * Terapkan status dari Midtrans ke tabel pembayaran dan pesanan.
 * Dipakai oleh webhook dan oleh pengecekan manual (idempoten).
 */
function terapkan_status_pembayaran(
    mysqli $c,
    string $orderIdMidtrans,
    string $statusMidtrans
): void {
    $berhasil = in_array($statusMidtrans, ['settlement', 'capture'], true);
    $gagal = in_array(
        $statusMidtrans,
        ['expire', 'cancel', 'deny', 'failure'],
        true
    );
    if (!$berhasil && !$gagal) {
        return; // masih pending
    }

    $stmt = $c->prepare(
        'SELECT id_pembayaran, id_pesanan, status_pembayaran
         FROM pembayaran WHERE order_id = ? LIMIT 1'
    );
    $stmt->bind_param('s', $orderIdMidtrans);
    $stmt->execute();
    $bayar = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bayar || $bayar['status_pembayaran'] !== 'pending') {
        return; // tidak ada, atau sudah final
    }

    $idPesanan = (int) $bayar['id_pesanan'];
    $idBayar = (int) $bayar['id_pembayaran'];

    $c->begin_transaction();
    try {
        $statusBayar = $berhasil ? 'settlement' : $statusMidtrans;
        $sekarang = date('Y-m-d H:i:s');
        $pending = 'pending';

        // Hanya satu proses yang berhasil mengubah dari "pending"
        $up = $c->prepare(
            'UPDATE pembayaran
             SET status_pembayaran = ?, tanggal_pembayaran = ?
             WHERE id_pembayaran = ? AND status_pembayaran = ?'
        );
        $up->bind_param('ssis', $statusBayar, $sekarang, $idBayar, $pending);
        $up->execute();
        $diubah = $up->affected_rows > 0;
        $up->close();

        if ($diubah) {
            if ($berhasil) {
                $baru = STATUS_DIPROSES;
                $lama = STATUS_MENUNGGU;
                $up = $c->prepare(
                    'UPDATE pesanan SET status_pesanan = ?
                     WHERE id_pesanan = ? AND status_pesanan = ?'
                );
                $up->bind_param('sis', $baru, $idPesanan, $lama);
                $up->execute();
                $up->close();
            } else {
                batalkan_pesanan_menunggu($c, $idPesanan);
            }
        }

        $c->commit();
    } catch (Throwable $e) {
        $c->rollback();
        throw $e;
    }
}

/* Tanyakan status terbaru ke Midtrans bila pembayaran masih pending */
function sinkron_status_pembayaran(mysqli $c, int $idPesanan): void
{
    $bayar = ambil_pembayaran($c, $idPesanan);
    if (!$bayar || $bayar['status_pembayaran'] !== 'pending') {
        return;
    }

    try {
        $data = midtrans_request(
            'GET',
            '/v2/' . rawurlencode($bayar['order_id']) . '/status'
        );
        if (!empty($data['transaction_status'])) {
            terapkan_status_pembayaran(
                $c,
                $bayar['order_id'],
                (string) $data['transaction_status']
            );
        }
    } catch (AppException $e) {
        // Midtrans tidak terjangkau: tampilkan status terakhir di database
        error_log('Sinkron status gagal: ' . $e->getMessage());
    }
}
