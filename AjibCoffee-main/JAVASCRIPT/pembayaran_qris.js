/**
 * pembayaran_qris.js
 * Halaman QRIS: tampilkan kode QR Midtrans, hitung mundur, dan pantau status.
 */
document.addEventListener("DOMContentLoaded", () => {
    const id = AjibBayar.idPesanan();
    const hitungEl = document.getElementById("hitungMundur");
    const blokQr = document.getElementById("blokQr");
    const btnStatus = document.getElementById("btnStatus");
    const infoStatus = document.getElementById("infoStatus");
    const nmidEl = document.getElementById("nmid");
    const logoWadah = document.getElementById("logoQris");

    let sisa = 0;
    let timerHitung = null;
    let timerCek = null;
    let sedangMemuat = false;
    let qrSudahTampil = false;

    const dua = (n) => String(n).padStart(2, "0");

    function tampilHitung() {
        const j = Math.floor(sisa / 3600);
        const m = Math.floor((sisa % 3600) / 60);
        hitungEl.textContent = `${dua(j)}.${dua(m)}.${dua(sisa % 60)}`;
    }

    function berhenti() {
        clearInterval(timerHitung);
        clearInterval(timerCek);
    }

    // Membaca NMID dari qr_string (format EMVCo: tag 2 digit + panjang 2 digit + nilai)
    function bacaTlv(teks) {
        const hasil = {};
        let i = 0;
        while (i + 4 <= teks.length) {
            const tag = teks.slice(i, i + 2);
            const panjang = parseInt(teks.slice(i + 2, i + 4), 10);
            if (Number.isNaN(panjang)) break;
            hasil[tag] = teks.slice(i + 4, i + 4 + panjang);
            i += 4 + panjang;
        }
        return hasil;
    }

    function cariNmid(qrString) {
        if (!qrString) return null;
        try {
            const utama = bacaTlv(qrString);
            for (let tag = 26; tag <= 51; tag++) {
                const nilai = utama[String(tag)];
                if (!nilai) continue;
                const sub = bacaTlv(nilai);
                if (sub["02"] && /^ID/i.test(sub["02"])) return sub["02"];
            }
        } catch (e) { /* NMID tidak wajib tampil */ }
        return null;
    }

    function tampilkanLogoQris(url) {
    if (!logoWadah || !url) return;
}

    function tampilkanQr(data) {
        if (qrSudahTampil || !data.qr_code_url) return;
        qrSudahTampil = true;

        blokQr.innerHTML =
            `<img class="qr-gambar" src="${AjibBayar.esc(data.qr_code_url)}" alt="Kode QRIS untuk pembayaran">`;

        const nmid = cariNmid(data.qr_string);
        nmidEl.textContent = nmid ? `NMID: ${nmid}` : "";
        nmidEl.hidden = !nmid;
    }

    function pembayaranGagal(data) {
        berhenti();
        hitungEl.textContent = "00.00.00";
        hitungEl.classList.add("habis");
        const alasan = data.status_bayar === "expire" ? "kedaluwarsa" : "dibatalkan";
        blokQr.innerHTML = `<p class="qr-gagal">Pembayaran ${alasan}.<br>
            <a href="main.html">Kembali ke menu</a> untuk membuat pesanan baru.</p>`;
        nmidEl.hidden = true;
        btnStatus.hidden = true;
        infoStatus.textContent = "";
    }

    function terapkan(data) {
        document.getElementById("totalBayar").textContent = AjibBayar.rupiah(data.total);
        document.getElementById("kodeOrder").textContent = "#" + data.kode;
        tampilkanLogoQris(data.logo_qris);

        if (!data.ada_pembayaran) {
            window.location.href = `metode_pembayaran.html?id=${encodeURIComponent(id)}`;
            return;
        }
        if (data.status_bayar === "settlement") {
            berhenti();
            window.location.href = `struk.html?id=${encodeURIComponent(id)}`;
            return;
        }
        if (data.status_bayar !== "pending") {
            pembayaranGagal(data);
            return;
        }

        tampilkanQr(data);
        sisa = data.sisa_detik;
        tampilHitung();
    }

    async function muat(manual = false) {
        if (sedangMemuat) return;
        sedangMemuat = true;
        if (manual) {
            btnStatus.disabled = true;
            infoStatus.textContent = "Memeriksa pembayaran...";
        }

        try {
            const data = await AjibBayar.ambilJson(
                `../PHP/data_pembayaran.php?id=${encodeURIComponent(id)}`
            );
            AjibBayar.sembunyikanPesan();
            terapkan(data);
            if (manual && data.status_bayar === "pending") {
                infoStatus.textContent = "Pembayaran belum diterima. Selesaikan pembayaran lalu periksa lagi.";
            } else if (!manual && data.status_bayar === "pending") {
                infoStatus.textContent = "Menunggu pembayaran...";
            }
        } catch (error) {
            AjibBayar.pesan(error.message);
            infoStatus.textContent = "";
        } finally {
            sedangMemuat = false;
            btnStatus.disabled = false;
        }
    }

    if (!id) {
        AjibBayar.pesan("Pesanan tidak ditemukan.");
        btnStatus.hidden = true;
        return;
    }

    btnStatus.addEventListener("click", () => muat(true));

    muat().then(() => {
        timerHitung = setInterval(() => {
            if (sisa > 0) {
                sisa--;
                tampilHitung();
                if (sisa === 0) muat(); // waktu habis: minta status terbaru
            }
        }, 1000);
        timerCek = setInterval(() => muat(), 7000); // cek otomatis tiap 7 detik
    });
});
