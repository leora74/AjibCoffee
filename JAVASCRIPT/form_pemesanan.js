document.addEventListener('DOMContentLoaded', () => {
    const deliveryFields = document.getElementById('deliveryFields');
    const locationInput = document.getElementById('lokasi_pengantaran');
    const detailInput = document.getElementById('detail_lokasi');
    const serviceInputs = document.querySelectorAll('input[name="jenis_layanan"]');

    function updateService() {
        const selected = document.querySelector('input[name="jenis_layanan"]:checked')?.value || 'Delivery';
        const isDelivery = selected === 'Delivery';
        deliveryFields.hidden = !isDelivery;
        locationInput.required = isDelivery;
        detailInput.required = isDelivery;
        [locationInput, detailInput].forEach((field) => {
            field.disabled = !isDelivery;
        });
    }

    serviceInputs.forEach((input) => input.addEventListener('change', updateService));
    updateService();

    const phone = document.getElementById('no_telepon_penerima');
    phone.addEventListener('input', () => {
        phone.value = phone.value.replace(/[^0-9+]/g, '');
    });
});
