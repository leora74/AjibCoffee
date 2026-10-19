<?php

session_start();

require_once __DIR__ . '/conn.php';

// Fungsi untuk mengarahkan kembali ke halaman login
function kembaliLogin($status)
{
    header('Location: ../HTML/login.html?status=' . $status);
    exit;
}

// Pastikan data dikirim melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/login.html');
    exit;
}

// Ambil dan bersihkan input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $password === ''
) {
    kembaliLogin('error');
}

try {
    // Cari akun berdasarkan email
    $stmt = $connection->prepare(
        'SELECT idPelanggan, email, password
         FROM Pelanggan
         WHERE email = ?'
    );

    if (!$stmt) {
        throw new Exception('Gagal menyiapkan pencarian akun.');
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();

    // Ambil data akun
    $result = $stmt->get_result();
    $pelanggan = $result->fetch_assoc();

    $stmt->close();

    // Periksa email dan password
    if (
        !$pelanggan ||
        !password_verify($password, $pelanggan['password'])
    ) {
        kembaliLogin('invalid');
    }

    // Buat session login pelanggan
    session_regenerate_id(true);

    $_SESSION['idPelanggan'] = $pelanggan['idPelanggan'];
    $_SESSION['email'] = $pelanggan['email'];

    // Login berhasil, masuk ke halaman utama
    header('Location: ../HTML/main.html?status=success');
    exit;


} catch (Throwable $e) {
    error_log(
        'Login error: ' .
        $e->getMessage() .
        ' | File: ' . $e->getFile() .
        ' | Line: ' . $e->getLine()
    );

    kembaliLogin('error');
}
