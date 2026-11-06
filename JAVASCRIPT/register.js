
const registerForm = document.getElementById("registerForm");

const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const confirmPasswordInput = document.getElementById("confirmPassword");

const emailError = document.getElementById("emailError");
const passwordError = document.getElementById("passwordError");
const confirmPasswordError = document.getElementById("confirmPasswordError");

const formAlert = document.getElementById("formAlert");
const formSuccess = document.getElementById("formSuccess");

// Toggle tampil/sembunyikan password
document.querySelectorAll("button.toggle-password").forEach((button) => {
    button.addEventListener("click", () => {
        const input = document.getElementById(button.dataset.target);
        const icon = button.querySelector("i");

        if (!input || !icon) return;

        const isPassword = input.type === "password";

        input.type = isPassword ? "text" : "password";

        icon.classList.toggle("bi-eye", !isPassword);
        icon.classList.toggle("bi-eye-slash", isPassword);

        button.setAttribute(
            "aria-label",
            isPassword ? "Sembunyikan password" : "Tampilkan password"
        );
    });
});

// Menghapus error pada satu field
function clearFieldError(input, errorElement) {
    input.classList.remove("is-invalid");
    errorElement.textContent = "";
    errorElement.hidden = true;
}

// Menampilkan error pada satu field
function showFieldError(input, errorElement, message) {
    input.classList.add("is-invalid");
    errorElement.textContent = message;
    errorElement.hidden = false;
}

// Menghapus pesan form
function clearFormMessages() {
    formAlert.textContent = "";
    formAlert.hidden = true;

    formSuccess.textContent = "";
    formSuccess.hidden = true;
}

// Validasi sebelum dikirim ke PHP
registerForm.addEventListener("submit", (event) => {
    event.preventDefault();

    clearFormMessages();

    clearFieldError(emailInput, emailError);
    clearFieldError(passwordInput, passwordError);
    clearFieldError(confirmPasswordInput, confirmPasswordError);

    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    let isValid = true;

    // Validasi email
    if (email === "" || !emailInput.validity.valid) {
        showFieldError(
            emailInput,
            emailError,
            "Masukkan alamat email yang valid."
        );
        isValid = false;
    }

    // Validasi panjang password
    if (password.length < 8) {
        showFieldError(
            passwordInput,
            passwordError,
            "Password minimal 8 karakter."
        );
        isValid = false;
    }

    // Validasi konfirmasi password
    if (password !== confirmPassword) {
        showFieldError(
            confirmPasswordInput,
            confirmPasswordError,
            "Konfirmasi password tidak cocok."
        );
        isValid = false;
    }

    // Jika gagal, hentikan pengiriman
    if (!isValid) {
        const firstInvalidInput =
            registerForm.querySelector(".is-invalid");

        if (firstInvalidInput) {
            firstInvalidInput.focus();
        }

        return;
    }

    // Jika valid, kirim form ke loadRegis.php
    // Gunakan submit() native agar listener ini tidak dipanggil ulang.
    HTMLFormElement.prototype.submit.call(registerForm);
});

// Bersihkan error saat pengguna mengetik
emailInput.addEventListener("input", () => {
    clearFieldError(emailInput, emailError);
});

passwordInput.addEventListener("input", () => {
    clearFieldError(passwordInput, passwordError);
    clearFieldError(confirmPasswordInput, confirmPasswordError);
});

confirmPasswordInput.addEventListener("input", () => {
    clearFieldError(confirmPasswordInput, confirmPasswordError);
});

// Tampilkan pesan berdasarkan respons PHP

/* Tampilkan alert berdasarkan respons PHP */
const params = new URLSearchParams(window.location.search);
const status = params.get("status");

if (status === "success") {
    alert("Pendaftaran berhasil! Silahkan login.");

    // Pindah ke halaman login setelah tombol OK ditekan
    window.location.replace("../index.html");

} else if (status === "email_exists") {
    alert("Email sudah terdaftar. Gunakan email lain.");

} else if (status === "error") {
    alert("Pendaftaran gagal. Periksa data lu dan coba lagi.");
}

/* Bersihkan status dari URL jika tidak pindah halaman */
if (status && status !== "success") {
    window.history.replaceState(
        {},
        document.title,
        window.location.pathname
    );
}

/* Bersihkan status dari URL jika tidak pindah halaman */
if (status && status !== "success") {
    window.history.replaceState(
        {},
        document.title,
        window.location.pathname
    );
}

