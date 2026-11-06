<?php

session_start();

require_once __DIR__ . '/conn.php';

// Fungsi untuk kembali ke halaman registrasi
function kembaliRegister($status)
{
    header('Location: ../HTML/register.html?status=' . $status);
    exit;
}

// Pastikan data dikirim melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/index.html');
    exit;
}

// Ambil input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validasi email dan password
if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($password) < 8 ||
    $password !== $confirmPassword
) {
    kembaliRegister('error');
}

try {
    // Periksa apakah email sudah digunakan
    $cekEmail = $connection->prepare(
        'SELECT idPelanggan FROM Pelanggan WHERE email = ?'
    );

    if (!$cekEmail) {
        throw new Exception('Gagal menyiapkan pemeriksaan email.');
    }

    $cekEmail->bind_param('s', $email);
    $cekEmail->execute();
    $cekEmail->store_result();

    if ($cekEmail->num_rows > 0) {
        $cekEmail->close();
        kembaliRegister('email_exists');
    }

    $cekEmail->close();

    // Simpan data sementara ke session
    $_SESSION['register_email'] = $email;
    $_SESSION['register_password'] = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Perbarui ID session
    session_regenerate_id(true);

    // Arahkan ke halaman buat username
    header('Location: ../HTML/buat_username.html');
    exit;

} catch (Throwable $e) {
    error_log('Register error: ' . $e->getMessage());

    kembaliRegister('error');
}