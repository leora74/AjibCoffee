document.addEventListener('DOMContentLoaded', () => {
    const deliveryFields = document.getElementById('deliveryFields');
    const locationInput = document.getElementById('lokasi_pengantaran');
    const detailInput = document.getElementById('detail_lokasi');
    const serviceInputs = document.querySelectorAll('input[name="jenis_layanan"]');
    const deliveryOption = document.querySelector('input[name="jenis_layanan"][value="Delivery"]');
    const pickupOption = document.querySelector('input[name="jenis_layanan"][value="Pick Up"]');
    const deliveryTersedia = window.deliveryTersedia ?? false;

    if (!deliveryTersedia && deliveryOption) {
        deliveryOption.disabled = true;
        deliveryOption.checked = false;
    }

    function updateService() {
        const selected = document.querySelector('input[name="jenis_layanan"]:checked')?.value || (deliveryTersedia ? 'Delivery' : 'Pick Up');
        const isDelivery = deliveryTersedia && selected === 'Delivery';

        if (!deliveryTersedia && deliveryOption && deliveryOption.checked) {
            deliveryOption.checked = false;
            if (pickupOption) pickupOption.checked = true;
        }

        if (!deliveryTersedia && pickupOption) {
            pickupOption.checked = true;
        }

        if (deliveryFields) {
            deliveryFields.hidden = !isDelivery;
        }

        if (locationInput) {
            locationInput.required = isDelivery;
            locationInput.disabled = !isDelivery;
        }

        if (detailInput) {
            detailInput.required = isDelivery;
            detailInput.disabled = !isDelivery;
        }
    }

    serviceInputs.forEach((input) => input.addEventListener('change', updateService));

    if (!deliveryTersedia && pickupOption) {
        pickupOption.checked = true;
    }

    updateService();

    const phone = document.getElementById('no_telepon_penerima');
    if (phone) {
        phone.addEventListener('input', () => {
            phone.value = phone.value.replace(/[^0-9+]/g, '');
        });
    }
});
