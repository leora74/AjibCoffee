
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
        const menuSection = document.getElementById("daftar");
        const noResults = document.createElement("p");
        noResults.className = "menu-search-empty";
        noResults.textContent = "Menu tidak ditemukan.";
        noResults.hidden = true;
        menuSection?.querySelector(".menu-columns")?.before(noResults);

        const normalize = (value) => value
            .trim()
            .toLocaleLowerCase("id")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");

        menuSearch.addEventListener("input", () => {
            const keyword = normalize(menuSearch.value);
            const wasEmpty = !menuSearch.dataset.previousQuery;
            let visibleItems = 0;
            const mainContent = document.querySelector("body > .page-shell main");

            mainContent?.classList.toggle("searching-menu", keyword !== "");

            menuItems.forEach((item) => {
                const namaMenu = normalize(item.querySelector("span")?.textContent || "");

                item.hidden = !namaMenu.includes(keyword);
                if (!item.hidden) visibleItems++;
            });

            // Sembunyikan kategori tanpa hasil.
            menuGroups.forEach((group) => {
                group.hidden = !group.querySelector("li:not([hidden])");
            });

            noResults.hidden = keyword === "" || visibleItems > 0;
            menuSearch.dataset.previousQuery = keyword;
            if (keyword && wasEmpty) {
                menuSection?.scrollIntoView({ behavior: "smooth", block: "start" });
            }

            const clearButton = document.getElementById("clearMenuSearch");
            if (clearButton) clearButton.hidden = keyword === "";
        });

        // Tombol hapus pencarian.
        document.getElementById("clearMenuSearch")?.addEventListener("click", () => {
            menuSearch.value = "";
            delete menuSearch.dataset.previousQuery;
            menuSearch.dispatchEvent(new Event("input"));
            menuSearch.focus();
        });
    }

    // ===== Notifikasi Login =====

    const params = new URLSearchParams(window.location.search);

    if (params.get("status") === "success") {
        alert("Login berhasil! Selamat datang.");
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // ===== Tombol Buy =====

    document.querySelectorAll(".buy-button[data-menu-id]").forEach((button) => {
        button.addEventListener("click", () => {
            const idMenu = button.dataset.menuId;
            window.location.href = `detail_produk.php?id_menu=${encodeURIComponent(idMenu)}`;
        });
    });
});
