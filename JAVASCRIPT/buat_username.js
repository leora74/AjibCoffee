const params = new URLSearchParams(window.location.search);
const status = params.get('status');

if (status === 'username_exists') {
    alert('Username sudah digunakan! Silakan gunakan username lain.');
}

if (status === 'invalid_username') {
    alert('Username harus terdiri dari 3-30 karakter dan hanya boleh menggunakan huruf, angka, atau underscore.');
}

if (status === 'error') {
    alert('Terjadi kesalahan saat menyimpan username. Silakan coba lagi.');
}