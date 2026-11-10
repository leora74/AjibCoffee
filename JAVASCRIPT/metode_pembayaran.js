/**
 * metode_pembayaran.js
 * Halaman Metode Pembayaran: menampilkan total dan membuat pembayaran QRIS.
 */
document.addEventListener("DOMContentLoaded", async () => {
    const id = AjibBayar.idPesanan();
    const totalEl = document.getElementById("totalBayar");
    const kodeEl = document.getElementById("kodeOrder");
    const tombolQris = document.getElementById("pilihQris");
    const caption = document.getElementById("captionQris");

    if (!id) {
        AjibBayar.pesan("Pesanan tidak ditemukan. Silakan ulangi dari keranjang.");
        tombolQris.disabled = true;
        return;
    }

    try {
        const data = await AjibBayar.ambilJson(
            `../PHP/data_pembayaran.php?id=${encodeURIComponent(id)}`
        );

        totalEl.textContent = AjibBayar.rupiah(data.total);
        kodeEl.textContent = "#" + data.kode;

        if (data.status_pesanan === "dibatalkan") {
            AjibBayar.pesan("Pesanan ini sudah dibatalkan atau kedaluwarsa. Silakan buat pesanan baru.");
            tombolQris.disabled = true;
            return;
        }
        if (data.status_pesanan !== "menunggu_pembayaran") {
            window.location.href = `struk.html?id=${encodeURIComponent(id)}`; // sudah dibayar
            return;
        }
        if (data.ada_pembayaran && data.status_bayar === "pending") {
            caption.textContent = "Lanjutkan pembayaran QRIS";
        }
    } catch (error) {
        AjibBayar.pesan(error.message);
        tombolQris.disabled = true;
        return;
    }

    tombolQris.addEventListener("click", async () => {
        tombolQris.disabled = true;
        AjibBayar.sembunyikanPesan();
        caption.textContent = "Membuat kode QRIS...";

        try {
            const hasil = await AjibBayar.ambilJson("../PHP/buat_pembayaran_qris.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id_pesanan: Number(id) })
            });
            window.location.href = hasil.redirect;
        } catch (error) {
            AjibBayar.pesan(error.message);
            caption.textContent = "Scan dengan e-wallet atau m-banking";
            tombolQris.disabled = false;
        }
    });
});
