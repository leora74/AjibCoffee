<?php
/**
 * midtrans_notifikasi.php
 * Webhook (HTTP Notification) dari Midtrans. Daftarkan URL file ini di
 * Midtrans Dashboard > Settings > Payment > Notification URL.
 * Di Laragon lokal URL ini tidak bisa dijangkau Midtrans kecuali memakai
 * tunnel (mis. ngrok). Tanpa itu, status tetap diperbarui lewat polling
 * di halaman QRIS dan tombol "Lihat Status Pembayaran".
 */
require_once __DIR__ . '/midtrans.php';

try {
    $data = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        exit('Bad request');
    }

    $config = midtrans_config();
    $orderId = (string) ($data['order_id'] ?? '');
    $statusCode = (string) ($data['status_code'] ?? '');
    $gross = (string) ($data['gross_amount'] ?? '');
    $signature = (string) ($data['signature_key'] ?? '');

    // Tanda tangan memastikan notifikasi benar-benar dari Midtrans
    $harapan = hash('sha512', $orderId . $statusCode . $gross . $config['server_key']);
    if (!hash_equals($harapan, $signature)) {
        http_response_code(403);
        exit('Invalid signature');
    }

    terapkan_status_pembayaran(
        $connection,
        $orderId,
        (string) ($data['transaction_status'] ?? '')
    );

    http_response_code(200);
    echo 'OK';
} catch (Throwable $e) {
    error_log('Webhook Midtrans error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error';
}
