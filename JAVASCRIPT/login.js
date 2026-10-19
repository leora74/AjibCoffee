// ================================
// TOGGLE PASSWORD (IKON MATA)
// ================================

document.querySelectorAll(".toggle-password").forEach((button) => {
    button.addEventListener("click", () => {
        const targetId = button.dataset.target;
        const input = document.getElementById(targetId);
        const icon = button.querySelector("i");

        if (!input || !icon) return;

        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace("bi-eye", "bi-eye-slash");
            button.setAttribute("aria-label", "Sembunyikan password");
        } else {
            input.type = "password";
            icon.classList.replace("bi-eye-slash", "bi-eye");
            button.setAttribute("aria-label", "Tampilkan password");
        }
    });
});


// ================================
// VALIDASI FORM LOGIN
// ================================

const loginForm = document.getElementById("loginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const emailError = document.getElementById("emailError");
const passwordError = document.getElementById("passwordError");

function clearFieldError(input, errorElement) {
    input.classList.remove("is-invalid");
    errorElement.textContent = "";
}

function showFieldError(input, errorElement, message) {
    input.classList.add("is-invalid");
    errorElement.textContent = message;
}

loginForm.addEventListener("submit", (event) => {
    let valid = true;

    clearFieldError(emailInput, emailError);
    clearFieldError(passwordInput, passwordError);

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    if (!email) {
        showFieldError(
            emailInput,
            emailError,
            "Email wajib diisi."
        );
        valid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showFieldError(
            emailInput,
            emailError,
            "Format email tidak valid."
        );
        valid = false;
    }

    if (!password.trim()) {
        showFieldError(
            passwordInput,
            passwordError,
            "Password wajib diisi."
        );
        valid = false;
    }

    if (!valid) {
        event.preventDefault();
    }
});

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

    console.log("URL saat ini:", window.location.href);
    console.log("Status login:", params.get("status"));

    if (params.get("status") === "success") {
        alert("Login berhasil! Selamat datang.");

        window.history.replaceState(
            {},
            document.title,
            window.location.pathname
        );
    }
});