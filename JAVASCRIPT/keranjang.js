document.addEventListener("DOMContentLoaded", async () => {
    const list = document.getElementById("cartList");
    const checkoutBar = document.getElementById("checkoutBar");
    const selectAll = document.getElementById("selectAll");
    const totalElement = document.getElementById("selectedTotal");
    const countElement = document.getElementById("selectedCount");
    const checkoutButton = document.getElementById("checkoutButton");

    const pageParams = new URLSearchParams(window.location.search);
    if (pageParams.get("cart_status") === "added") {
        alert("Produk berhasil ditambahkan ke keranjang.");
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Label varian untuk tampilan (nilai di database tetap "Hot" / "Ice")
    const labelVarian = (value) => ({ Hot: "Panas", Ice: "Dingin" })[value] || value || "";

    const rupiah = (value) => "Rp " + Number(value).toLocaleString("id-ID");
    const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    })[char]);

    function renderEmpty() {
        checkoutBar.hidden = true;
        list.innerHTML = `
            <section class="empty-cart">
                <i class="bi bi-cart-x"></i>
                <h1>Keranjang masih kosong</h1>
                <p>Pilih menu favoritmu dari Ajib Coffee.</p>
                <a href="main.html#daftar" class="btn-ajib">Lihat Menu</a>
            </section>`;
    }

    function renderItems(items, checkedKeys = null) {
        checkoutBar.hidden = false;
        list.innerHTML = items.map((item, index) => {
            const key = escapeHtml(item.key);
            const variantLabel = labelVarian(item.varian);
            const variantTag = item.varian
                ? `<span class="variant-tag">${escapeHtml(variantLabel)}</span>` : "";

            let variantSelect = "";
            if (item.harga_menu === null && (item.harga_hot !== null || item.harga_ice !== null)) {
                const options = [];
                if (item.harga_hot !== null) {
                    options.push(`<option value="Hot" ${item.varian === "Hot" ? "selected" : ""}>Panas</option>`);
                }
                if (item.harga_ice !== null) {
                    options.push(`<option value="Ice" ${item.varian === "Ice" ? "selected" : ""}>Dingin</option>`);
                }
                variantSelect = `
                    <form class="variant-form" action="../PHP/proses_keranjang.php" method="post">
                        <input type="hidden" name="key" value="${key}">
                        <input type="hidden" name="aksi" value="update">
                        <input type="hidden" name="jumlah" value="${Number(item.jumlah)}">
                        <select class="form-select form-select-sm variant-select" name="varian" aria-label="Pilih varian ${escapeHtml(item.nama)}">
                            ${options.join("")}
                        </select>
                    </form>`;
            }

            return `
                <article class="cart-item" data-subtotal="${Number(item.subtotal)}" data-key="${key}" data-stok="${Number(item.stok)}">
                    <label class="item-check">
                        <input class="form-check-input item-checkbox" type="checkbox" ${checkedKeys === null || checkedKeys.has(item.key) ? "checked" : ""} aria-label="Pilih ${escapeHtml(item.nama)}">
                    </label>
                    <div class="item-image">
                        <img src="${escapeHtml(item.gambar)}" alt="${escapeHtml(item.nama)}">
                    </div>
                    <div class="item-info">
                        <h2>${escapeHtml(item.nama)}</h2>
                        <div class="item-tags">
                            ${variantTag}
                            <span class="category-tag">${escapeHtml(item.kategori)}</span>
                        </div>
                        <p class="item-price">${rupiah(item.harga)}</p>
                        ${variantSelect}
                    </div>
                    <div class="item-actions">
                        <div class="quantity-control" aria-label="Jumlah produk">
                            <form action="../PHP/proses_keranjang.php" method="post">
                                <input type="hidden" name="key" value="${key}">
                                <input type="hidden" name="aksi" value="minus">
                                <button type="button" class="quantity-btn quantity-minus" data-key="${key}" aria-label="Kurangi jumlah" ${Number(item.jumlah) <= 1 ? 'disabled' : ''}><i class="bi bi-dash"></i></button>
                            </form>
                            <span>${Number(item.jumlah)}</span>
                            <form action="../PHP/proses_keranjang.php" method="post">
                                <input type="hidden" name="key" value="${key}">
                                <input type="hidden" name="aksi" value="plus">
                                <button type="button" class="quantity-btn quantity-plus" data-key="${key}" aria-label="Tambah jumlah" ${Number(item.jumlah) >= Number(item.stok) ? 'disabled' : ''}><i class="bi bi-plus"></i></button>
                            </form>
                        </div>
                        <form action="../PHP/hapus_keranjang.php" method="post" class="delete-form">
                            <input type="hidden" name="key" value="${key}">
                            <button type="submit" class="delete-btn" aria-label="Hapus ${escapeHtml(item.nama)}"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </article>`;
        }).join("");

        // Ganti varian lewat AJAX supaya halaman tidak reload dan posisi item tetap.
        list.querySelectorAll(".variant-select").forEach((select) => {
            select.dataset.current = select.value;
            select.addEventListener("change", () => changeVariant(select));
        });
        list.querySelectorAll(".delete-form").forEach((form) => {
            form.addEventListener("submit", (event) => {
                if (!confirm("Hapus menu ini dari keranjang?")) event.preventDefault();
            });
        });
        list.querySelectorAll('.quantity-minus, .quantity-plus').forEach((button) => {
            button.addEventListener('click', async () => {
                if (button.disabled) return;
                const card = button.closest('.cart-item');
                const keyValue = button.dataset.key;
                const action = button.classList.contains('quantity-minus') ? 'minus' : 'plus';
                const formData = new FormData();
                formData.append('key', keyValue);
                formData.append('aksi', action);
                button.disabled = true;
                try {
                    const response = await fetch('../PHP/proses_keranjang.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Gagal memperbarui jumlah.');
                    card.dataset.subtotal = result.subtotal;
                    const quantityText = card.querySelector('.quantity-control > span');
                    quantityText.textContent = result.jumlah;
                    const minusButton = card.querySelector('.quantity-minus');
                    const plusButton = card.querySelector('.quantity-plus');
                    minusButton.disabled = result.jumlah <= 1;
                    plusButton.disabled = result.jumlah >= result.stok;
                    const priceElement = card.querySelector('.item-price');
                    // Subtotal terbaru dipakai untuk ringkasan; harga satuan tetap ditampilkan.
                    card.dataset.subtotal = result.subtotal;
                    updateSummary();
                } catch (error) {
                    alert(error.message);
                } finally {
                    const currentQty = Number(card.querySelector('.quantity-control > span').textContent);
                    button.disabled = button.classList.contains('quantity-minus') ? currentQty <= 1 : currentQty >= Number(card.dataset.stok || 999999);
                }
            });
        });
        list.querySelectorAll(".item-checkbox").forEach((checkbox) => {
            checkbox.addEventListener("change", updateSummary);
        });
        updateSummary();
    }

    // Perbarui satu kartu produk di tempat setelah server menyimpan perubahan.
    function applyUpdatedItem(card, result, selectedVariant) {
        card.dataset.key = result.key;
        card.dataset.subtotal = result.subtotal;
        card.dataset.stok = result.stok;

        // Semua form di kartu ini memakai kunci lama; ganti ke kunci baru.
        card.querySelectorAll('input[name="key"]').forEach((input) => {
            input.value = result.key;
        });
        card.querySelectorAll(".quantity-btn").forEach((button) => {
            button.dataset.key = result.key;
        });

        const variantForm = card.querySelector('.variant-form input[name="jumlah"]');
        if (variantForm) variantForm.value = result.jumlah;

        card.querySelector(".item-price").textContent = rupiah(result.harga);

        const tag = card.querySelector(".variant-tag");
        if (tag) tag.textContent = labelVarian(selectedVariant);

        card.querySelector(".quantity-control > span").textContent = result.jumlah;
        card.querySelector(".quantity-minus").disabled = result.jumlah <= 1;
        card.querySelector(".quantity-plus").disabled = result.jumlah >= result.stok;

        updateSummary();
    }

    async function changeVariant(select) {
        const card = select.closest(".cart-item");
        const oldKey = card.dataset.key;
        const previousValue = select.dataset.current;
        const quantity = Number(card.querySelector(".quantity-control > span").textContent);

        const formData = new FormData();
        formData.append("key", oldKey);
        formData.append("aksi", "update");
        formData.append("varian", select.value);
        formData.append("jumlah", quantity);

        select.disabled = true;
        try {
            const response = await fetch("../PHP/proses_keranjang.php", {
                method: "POST",
                body: formData,
                headers: { "X-Requested-With": "XMLHttpRequest", "Accept": "application/json" }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || "Gagal mengganti varian.");
            }

            // Varian baru sudah ada sebagai kartu lain: server menggabungkannya,
            // jadi daftar dimuat ulang (tanpa reload halaman) dengan centang tetap.
            const mergedWithExisting = result.key !== oldKey
                && list.querySelector(`.cart-item[data-key="${CSS.escape(result.key)}"]`);

            if (mergedWithExisting) {
                const checkedKeys = new Set(
                    [...list.querySelectorAll(".cart-item")]
                        .filter((item) => item.querySelector(".item-checkbox").checked)
                        .map((item) => (item.dataset.key === oldKey ? result.key : item.dataset.key))
                );
                await loadCart(checkedKeys);
                return;
            }

            applyUpdatedItem(card, result, select.value);
            select.dataset.current = select.value;
        } catch (error) {
            select.value = previousValue; // kembalikan pilihan jika gagal
            alert(error.message);
        } finally {
            select.disabled = false;
        }
    }

    function updateSummary() {
        const cards = [...list.querySelectorAll(".cart-item")];
        let total = 0;
        let count = 0;

        cards.forEach((card) => {
            if (card.querySelector(".item-checkbox").checked) {
                total += Number(card.dataset.subtotal || 0);
                count++;
            }
        });

        totalElement.textContent = rupiah(total);
        countElement.textContent = count;
        checkoutButton.disabled = count === 0;

        const selected = cards.filter((card) => card.querySelector(".item-checkbox").checked).length;
        selectAll.checked = selected === cards.length;
        selectAll.indeterminate = selected > 0 && selected < cards.length;
    }

    selectAll.addEventListener("change", () => {
        list.querySelectorAll(".item-checkbox").forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateSummary();
    });

    checkoutButton.addEventListener("click", () => {
        const selected = [...list.querySelectorAll(".cart-item")]
            .filter((card) => card.querySelector(".item-checkbox").checked)
            .map((card) => card.dataset.key);
        if (!selected.length) return;

        // Kirim hanya produk yang dicentang ke halaman form pemesanan.
        const form = document.createElement("form");
        form.method = "POST";
        form.action = "../PHP/pilih_checkout.php";

        selected.forEach((key) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "selected_keys[]";
            input.value = key;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });

    async function loadCart(checkedKeys = null) {
        try {
            const response = await fetch("../PHP/keranjang.php", { headers: { "Accept": "application/json" } });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || "Gagal memuat keranjang.");
            if (!data.items.length) renderEmpty();
            else renderItems(data.items, checkedKeys);
        } catch (error) {
            checkoutBar.hidden = true;
            list.innerHTML = `
                <section class="empty-cart">
                    <i class="bi bi-exclamation-circle"></i>
                    <h1>Keranjang tidak dapat dimuat</h1>
                    <p>${escapeHtml(error.message)} Pastikan Laragon dan koneksi database aktif.</p>
                    <a href="main.html#daftar" class="btn-ajib">Kembali ke Menu</a>
                </section>`;
        }
    }

    loadCart();
});
