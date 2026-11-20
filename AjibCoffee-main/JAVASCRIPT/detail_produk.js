document.addEventListener("DOMContentLoaded", () => {
    const quantity = document.getElementById("productQuantity");
    const minus = document.getElementById("minusQuantity");
    const plus = document.getElementById("plusQuantity");
    const price = document.getElementById("productPrice");
    const radios = document.querySelectorAll('input[name="varian"][data-price]');
    const form = document.getElementById("detailOrderForm");

    const formatRupiah = (value) => "Rp " + Number(value).toLocaleString("id-ID");
    function updatePrice() {
        const selected = document.querySelector('input[name="varian"][data-price]:checked');
        if (selected && price) price.textContent = formatRupiah(selected.dataset.price);
    }
    radios.forEach((radio) => radio.addEventListener("change", updatePrice));
    if (minus && quantity) minus.addEventListener("click", () => { quantity.value = Math.max(1, Number(quantity.value || 1) - 1); });
    if (plus && quantity) plus.addEventListener("click", () => { quantity.value = Math.min(Number(quantity.max || 999), Number(quantity.value || 1) + 1); });
    if (quantity) quantity.addEventListener("change", () => { quantity.value = Math.min(Number(quantity.max || 999), Math.max(1, Number(quantity.value || 1))); });
    if (form) form.addEventListener("submit", (event) => {
        if (!form.reportValidity()) { event.preventDefault(); return; }
        const submit = form.querySelector('button[type="submit"]');
        if (submit) { submit.disabled = true; submit.innerHTML = 'Menambahkan...'; }
    });
});
