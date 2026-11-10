document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".nav-link");
    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            navLinks.forEach((item) => item.classList.remove("active"));
            link.classList.add("active");
        });
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get("status") === "success") {
        alert("Login berhasil! Selamat datang.");
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Tombol Buy selalu menuju halaman detail berdasarkan ID menu.
    document.querySelectorAll(".buy-button[data-menu-id]").forEach((button) => {
        button.addEventListener("click", () => {
            const idMenu = button.dataset.menuId;
            window.location.href = `detail_produk.php?id_menu=${encodeURIComponent(idMenu)}`;
        });
    });
});
