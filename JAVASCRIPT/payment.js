document.addEventListener('DOMContentLoaded', () => {
    const walletOptions = document.getElementById('walletOptions');
    const methods = document.querySelectorAll('input[name="metode_pembayaran"]');
    function updateWalletOptions() {
        const selected = document.querySelector('input[name="metode_pembayaran"]:checked')?.value;
        walletOptions.hidden = selected !== 'E-Wallet';
    }
    methods.forEach((input) => input.addEventListener('change', updateWalletOptions));
    updateWalletOptions();
});
