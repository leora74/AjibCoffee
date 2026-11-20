# Petunjuk Pembayaran QRIS (Midtrans)

## 1. Pasang Server Key (wajib)
1. Salin `PHP/config_midtrans.example.php` menjadi `PHP/config_midtrans.php`.
2. Isi `server_key` dengan Server Key **Sandbox** dari
   dashboard.sandbox.midtrans.com > Settings > Access Keys.
3. Jangan bagikan `config_midtrans.php` (sudah masuk `.gitignore`).

Pastikan channel QRIS aktif pada akun Midtrans Anda dan ekstensi `curl`
PHP aktif (bawaan Laragon).

## 2. Alur halaman
`form_pemesanan.php` -> (pesanan dibuat) -> `metode_pembayaran.html?id=` ->
`pembayaran_qris.html?id=` -> (setelah dibayar) -> `struk.html?id=`

- Pesanan dibuat saat tombol "Pilih Metode Pembayaran" ditekan dengan status
  `menunggu_pembayaran`, dan stok langsung dikurangi.
- Jika QRIS kedaluwarsa/dibatalkan, stok dikembalikan otomatis.
- Pesanan yang tidak pernah lanjut ke QRIS dibatalkan otomatis setelah 30 menit.

## 3. Status pembayaran
- Halaman QRIS memeriksa status ke Midtrans tiap 7 detik, dan tombol
  "Lihat Status Pembayaran" memeriksa seketika. Ini cukup untuk Laragon lokal.
- Webhook (opsional): daftarkan URL `PHP/midtrans_notifikasi.php` di Midtrans
  Dashboard > Settings > Payment > Notification URL. Di komputer lokal URL
  ini hanya bisa dijangkau Midtrans lewat tunnel (mis. ngrok).

## 4. Mencoba di Sandbox
Buat pesanan sampai muncul QRIS, lalu bayar memakai simulator pembayaran
Midtrans Sandbox (menu QRIS). Setelah dibayar, halaman otomatis pindah ke struk.

## 5. Aset opsional (belum ada di project)
- `ASSETS/img/qris-logo.png` : logo QRIS. Jika tidak ada, tampil teks "QRIS".
- `ASSETS/img/qr-ulasan.png` : QR ulasan pelanggan. Jika tidak ada, bagian
  "Ulasan Pelanggan Scan QR code" di struk tidak ditampilkan.

## 6. Batasan yang perlu diketahui
- Tabel `detail_pesanan` tidak punya kolom varian. Hot/Ice pada struk dicocokkan
  dari harga satuan terhadap `harga_hot`/`harga_ice` menu. Jika harga menu diubah
  setelah pesanan dibuat, label varian pada struk lama bisa tidak akurat.
