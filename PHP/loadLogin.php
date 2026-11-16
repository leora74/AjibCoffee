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

// Ambil input
$login = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';


// Validasi input
if ($login === '' || $password === '') {
    kembaliLogin('error');
}

if (strlen($password) < 8) {
    kembaliLogin('short');
}


try {

    // Cari akun berdasarkan email atau username
    $stmt = $connection->prepare(
        'SELECT idPelanggan, email, username, password
         FROM Pelanggan
         WHERE email = ? OR username = ?
         LIMIT 1'
    );

    if (!$stmt) {
        throw new Exception('Gagal menyiapkan pencarian akun.');
    }

    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();

    // Ambil data akun
    $result = $stmt->get_result();
    $pelanggan = $result->fetch_assoc();

    $stmt->close();

    // Periksa akun dan password
    if (
        !$pelanggan ||
        !password_verify($password, $pelanggan['password'])
    ) {
        kembaliLogin('error');
    }

    // Buat session login pelanggan
    session_regenerate_id(true);

    $_SESSION['idPelanggan'] = $pelanggan['idPelanggan'];
    $_SESSION['email'] = $pelanggan['email'];
    $_SESSION['username'] = $pelanggan['username'];

    // Login berhasil
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
