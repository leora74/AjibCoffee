<?php

session_start();

require_once __DIR__ . '/conn.php';

// Fungsi untuk mengarahkan kembali ke halaman register
function kembaliRegister($status)
{
    header('Location: ../HTML/register.html?status=' . $status);
    exit;
}

// Pastikan data dikirim melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/register.html');
    exit;
}

// Ambil dan bersihkan input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validasi di sisi server
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

    // Hash password sebelum disimpan
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Simpan akun baru
    $stmt = $connection->prepare(
        'INSERT INTO Pelanggan (email, password) VALUES (?, ?)'
    );

    if (!$stmt) {
        throw new Exception('Gagal menyiapkan penyimpanan akun.');
    }

    $stmt->bind_param('ss', $email, $passwordHash);

    if ($stmt->execute()) {
        $stmt->close();
        kembaliRegister('success');
    }

    $stmt->close();
    kembaliRegister('error');

} catch (Throwable $e) {
    error_log(
        'Register error: ' .
        $e->getMessage() .
        ' | File: ' . $e->getFile() .
        ' | Line: ' . $e->getLine()
    );

    kembaliRegister('error');
}
