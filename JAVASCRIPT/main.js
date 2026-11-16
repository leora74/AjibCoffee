document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".nav-link");
    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            navLinks.forEach((item) => item.classList.remove("active"));
            link.classList.add("active");
        });
    });

        // ===== Fungsi Searchbar =====

    const menuSearch = document.getElementById("menuSearch");
    const menuItems = document.querySelectorAll(".menu-group li");

    if (menuSearch && menuItems.length) {
        const menuGroups = document.querySelectorAll(".menu-group");

        menuSearch.addEventListener("input", () => {
            const keyword = menuSearch.value.trim().toLocaleLowerCase("id");

            menuItems.forEach((item) => {
                const namaMenu = item.querySelector("span")?.textContent
                    .trim()
                    .toLocaleLowerCase("id") || "";

                item.hidden = !namaMenu.includes(keyword);
            });

            // Sembunyikan kategori tanpa hasil.
            menuGroups.forEach((group) => {
                const adaHasil = group.querySelector("li:not([hidden])");
                group.hidden = !adaHasil;
            });
        });

        // Tombol hapus pencarian.
        document.getElementById("clearMenuSearch")?.addEventListener("click", () => {
            menuSearch.value = "";
            menuSearch.dispatchEvent(new Event("input"));
            menuSearch.focus();
        });
    }

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
