/**
 * bayar_umum.js
 * Fungsi bersama halaman Metode Pembayaran, QRIS, dan Struk.
 */
const AjibBayar = (() => {
    const rupiah = (angka) =>
        "Rp " + new Intl.NumberFormat("id-ID").format(Number(angka) || 0);

    const esc = (teks) =>
        String(teks ?? "").replace(/[&<>"']/g, (c) => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        }[c]));

    const idPesanan = () => new URLSearchParams(location.search).get("id");

    // Panggil endpoint JSON. Belum login -> arahkan ke halaman login.
    async function ambilJson(url, opsi = {}) {
        let res;
        try {
            res = await fetch(url, { cache: "no-store", credentials: "same-origin", ...opsi });
        } catch (e) {
            throw new Error("Tidak dapat terhubung ke server. Pastikan Laragon aktif.");
        }

        let data;
        try {
            data = await res.json();
        } catch (e) {
            throw new Error("Respons server tidak valid.");
        }

        if (res.status === 401 && data.login_required) {
            window.location.href = "login.html";
            return new Promise(() => {}); // hentikan proses selama pindah halaman
        }
        if (!res.ok || !data.success) {
            const galat = new Error(data.message || "Terjadi kesalahan.");
            galat.data = data;
            throw galat;
        }
        return data;
    }

    // Tampilkan pesan pada #pesanHalaman: jenis = info | error | sukses
    function pesan(teks, jenis = "error") {
        const el = document.getElementById("pesanHalaman");
        if (!el) return;
        el.className = `pesan-halaman pesan-${jenis}`;
        el.textContent = teks;
        el.hidden = false;
    }

    function sembunyikanPesan() {
        const el = document.getElementById("pesanHalaman");
        if (el) el.hidden = true;
    }

    return { rupiah, esc, idPesanan, ambilJson, pesan, sembunyikanPesan };
})();
