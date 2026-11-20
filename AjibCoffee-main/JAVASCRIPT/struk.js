/**
 * struk.js
 * Struk / detail transaksi pelanggan.
 */
document.addEventListener("DOMContentLoaded", async () => {
    const struk = document.getElementById("struk");
    const id = AjibBayar.idPesanan();

    const bulan = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sept", "Okt", "Nov", "Des"];

    // "2026-09-30" -> "30 Sept 2026"
    function tanggalSingkat(iso) {
        const [y, m, d] = String(iso).split("-").map(Number);
        if (!y || !m || !d) return iso;
        return `${d} ${bulan[m - 1]} ${y}`;
    }

    // Label varian untuk tampilan (nilai di database tetap "Hot" / "Ice")
    const labelVarian = (v) => ({ Hot: "Panas", Ice: "Dingin" })[v] || v || "";

    // Format struk: "Rp18.000" (tanpa spasi)
    const rp = (n) => "Rp" + new Intl.NumberFormat("id-ID").format(Number(n) || 0);

    function tampilkanPesan(teks, tautan = "") {
        struk.className = "struk struk-pesan";
        struk.innerHTML = `<p>${AjibBayar.esc(teks)}</p>${tautan}`;
    }

    if (!id) {
        tampilkanPesan("Pesanan tidak ditemukan.");
        return;
    }

    try {
        const { struk: s } = await AjibBayar.ambilJson(
            `../PHP/data_struk.php?id=${encodeURIComponent(id)}`
        );

        const produk = s.items.length
            ? s.items.map((it) => `
                <div class="struk-baris">
                    <span>${it.jumlah} ${AjibBayar.esc(it.nama)}${it.varian ? " " + AjibBayar.esc(labelVarian(it.varian).toUpperCase()) : ""}</span>
                    <span>${rp(it.subtotal)}</span>
                </div>`).join("")
            : '<div class="struk-baris"><span>Rincian produk tidak tersedia</span></div>';

        const qrUlasan = s.qr_ulasan
            ? `<div class="struk-ulasan">
                   <p>Ulasan Pelanggan, pindai kode QR:</p>
                   <img src="${AjibBayar.esc(s.qr_ulasan)}" alt="QR code ulasan pelanggan">
               </div>`
            : "";

        document.title = `Struk #${s.kode} | Ajib Coffee`;
        struk.innerHTML = `
            <h1>AJIB<span>Coffe &amp; Caffe</span></h1>
            <hr>
            <div class="struk-baris"><span>Nama Pelanggan:</span><span>${AjibBayar.esc(s.nama)}</span></div>
            <div class="struk-baris"><span>Order ID:</span><span>#${AjibBayar.esc(s.kode)}</span></div>
            <div class="struk-baris"><span>Tanggal:</span><span>${AjibBayar.esc(tanggalSingkat(s.tanggal))}</span></div>
            <div class="struk-baris"><span>Jenis Pesanan:</span><span>${AjibBayar.esc(s.jenis)}</span></div>
            <hr>
            <p class="struk-judul">Produk</p>
            ${produk}
            <hr>
            <div class="struk-baris"><span>Subtotal:</span><span>${rp(s.subtotal)}</span></div>
            <div class="struk-baris"><span>Total:</span><span>${rp(s.total)}</span></div>
            <hr>
            <p class="struk-penutup">Terima kasih atas kunjungannya.<br>
                Kritik dan saran, hubungi:<br>+62 858-7106-9280</p>
            ${qrUlasan}`;
    } catch (error) {
        const tautan = error.data && error.data.belum_dibayar
            ? `<a class="struk-link" href="metode_pembayaran.html?id=${encodeURIComponent(id)}">Lanjutkan Pembayaran</a>`
            : '<a class="struk-link" href="main.html">Kembali ke Beranda</a>';
        tampilkanPesan(error.message, tautan);
    }
});
