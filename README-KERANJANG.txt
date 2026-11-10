AJIB COFFEE - INTEGRASI KERANJANG

Pemisahan file:
- HTML/keranjang.html: frontend HTML murni, tanpa kode PHP.
- CSS/keranjang.css: seluruh styling halaman keranjang.
- JAVASCRIPT/keranjang.js: mengambil data API, merender produk, checkbox, total terpilih, dan interaksi.
- PHP/keranjang.php: backend API JSON untuk membaca isi session dan mengambil detail menu dari database.
- PHP/ambil_menu.php: API detail menu dan opsi varian untuk modal pada halaman utama.
- PHP/tambah_keranjang.php: memvalidasi dan menambahkan menu ke session.
- PHP/proses_keranjang.php: memperbarui kuantitas atau varian.
- PHP/hapus_keranjang.php: menghapus produk dari session.
- PHP/jumlah_keranjang.php: menghitung item untuk badge keranjang di halaman utama.
- HTML/main.html dan JAVASCRIPT/main.js: tombol Buy membuka modal pilihan varian/jumlah dan mengirim produk ke keranjang.

CARA MENJALANKAN
1. Ekstrak folder AjibCoffee-master ke C:\laragon\www\
2. Jalankan Apache dan MySQL dari Laragon.
3. Pastikan database ajibCoffee sudah diimpor.
4. Periksa PHP/conn.php. Konfigurasi default:
   host=localhost, user=root, password kosong, database=ajibCoffee.
5. Buka:
   http://localhost/AjibCoffee-master/HTML/main.html
6. Klik Buy pada menu. Pilih Hot/Cold bila tersedia, atur jumlah, lalu Tambah ke Keranjang.
7. Buka ikon keranjang atau:
   http://localhost/AjibCoffee-master/HTML/keranjang.html

CATATAN PENTING
- Database tidak diubah.
- Tabel detail_pesanan pada dump yang diberikan belum memiliki kolom varian. Pilihan Hot/Ice tersimpan di session keranjang, tetapi belum bisa disimpan permanen ke detail transaksi.
- Halaman checkout dan pembayaran belum diintegrasikan. Tombol checkout saat ini menghitung produk terpilih, tetapi belum membuat pesanan atau memanggil payment gateway.
- Arsip proyek hanya memiliki beberapa foto produk umum. Gambar individual sesuai nama pada kolom menu.gambar belum tersedia. Jika file gambar tidak ditemukan, keranjang menggunakan cart-placeholder.jpg yang dibuat dari aset minuman Ajib yang sudah ada.
- Ikon profil menggunakan Bootstrap Icon karena file foto/avatar pelanggan tidak tersedia dalam arsip proyek.
- Pemeriksaan sintaks PHP dilakukan dengan php -l dan JavaScript dengan node --check. Pengujian end-to-end tetap perlu dilakukan di Laragon dengan database pengguna.


UPDATE HALAMAN DETAIL PRODUK
- Tombol Buy pada main.html membuka HTML/detail_produk.php?id_menu=...
- Detail produk mengambil nama, deskripsi, gambar, stok, dan harga dari tabel menu.
- Pilihan Hot/Cold hanya tampil sesuai harga yang tersedia di database.
- CSS dan JavaScript detail dipisah di CSS/detail_produk.css dan JAVASCRIPT/detail_produk.js.
- Ikon keranjang tidak menampilkan badge jumlah produk.
- Checkbox keranjang menggunakan warna hijau Ajib Coffee.
