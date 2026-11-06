
// ================================
// ELEMENT FORM LOGIN
// ================================

const loginForm = document.getElementById("loginForm");

const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");

const emailError = document.getElementById("emailError");
const passwordError = document.getElementById("passwordError");


// ================================
// TOGGLE PASSWORD
// ================================

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


// ================================
// FUNGSI VALIDASI
// ================================

// Menghapus error
function clearFieldError(input, errorElement) {
    input.classList.remove("is-invalid");
    errorElement.textContent = "";
    errorElement.hidden = true;
}

// Menampilkan error
function showFieldError(input, errorElement, message) {
    input.classList.add("is-invalid");
    errorElement.textContent = message;
    errorElement.hidden = false;
}


// ================================
// VALIDASI FORM LOGIN
// ================================

loginForm.addEventListener("submit", (event) => {
    event.preventDefault();

    clearFieldError(emailInput, emailError);
    clearFieldError(passwordInput, passwordError);

    const login = emailInput.value.trim();
    const password = passwordInput.value;

    let isValid = true;

    // Validasi email atau username
    if (login === "") {
        showFieldError(
            emailInput,
            emailError,
            "Email atau username wajib diisi."
        );

        isValid = false;
    }

    // Validasi password kosong
    if (password.trim() === "") {
        showFieldError(
            passwordInput,
            passwordError,
            "Password wajib diisi."
        );

        isValid = false;
    }

    // Validasi panjang password
    else if (password.length < 8) {
        showFieldError(
            passwordInput,
            passwordError,
            "Password minimal 8 karakter."
        );

        isValid = false;
    }

    // Jika validasi gagal
    if (!isValid) {
        const firstInvalidInput =
            loginForm.querySelector(".is-invalid");

        if (firstInvalidInput) {
            firstInvalidInput.focus();
        }

        return;
    }

    // Jika valid, kirim form ke PHP
    HTMLFormElement.prototype.submit.call(loginForm);
});


// ================================
// HAPUS ERROR SAAT MENGETIK
// ================================

emailInput.addEventListener("input", () => {
    clearFieldError(emailInput, emailError);
});

passwordInput.addEventListener("input", () => {
    clearFieldError(passwordInput, passwordError);
});


// ================================
// ALERT DARI PHP
// ================================

document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    const status = params.get("status");

    if (status === "error") {
        alert("Email/username atau password salah.");

    } else if (status === "short") {
        alert("Password minimal 8 karakter.");
    }

    // Bersihkan status dari URL
    if (status) {
        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );
    }
});
