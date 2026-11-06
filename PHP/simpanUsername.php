
<?php

session_start();

require_once __DIR__ . '/conn.php';

// Fungsi untuk mengarahkan kembali ke halaman buat username
function kembaliUsername($status)
{
    header('Location: ../HTML/buat_username.html?status=' . $status);
    exit;
}

// Pastikan request menggunakan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../HTML/register.html');
    exit;
}

// Pastikan data registrasi sebelumnya tersedia di session
if (
    empty($_SESSION['register_email']) ||
    empty($_SESSION['register_password'])
) {
    header('Location: ../HTML/register.html?status=session_expired');
    exit;
}

// Ambil username
$username = trim($_POST['username'] ?? '');

$email = $_SESSION['register_email'];
$passwordHash = $_SESSION['register_password'];

// Validasi username
if (
    strlen($username) < 3 ||
    strlen($username) > 30 ||
    !preg_match('/^[a-zA-Z0-9_]+$/', $username)
) {
    kembaliUsername('invalid_username');
}

try {

    // Periksa apakah username sudah digunakan
    $cekUsername = $connection->prepare(
        'SELECT idPelanggan FROM Pelanggan WHERE username = ?'
    );

    if (!$cekUsername) {
        throw new Exception('Gagal menyiapkan pemeriksaan username.');
    }

    $cekUsername->bind_param('s', $username);
    $cekUsername->execute();
    $cekUsername->store_result();

    if ($cekUsername->num_rows > 0) {
        $cekUsername->close();
        kembaliUsername('username_exists');
    }

    $cekUsername->close();

    // Simpan data pelanggan ke database
    $stmt = $connection->prepare(
        'INSERT INTO Pelanggan (email, password, username)
         VALUES (?, ?, ?)'
    );

    if (!$stmt) {
        throw new Exception('Gagal menyiapkan penyimpanan pelanggan.');
    }

    $stmt->bind_param(
        'sss',
        $email,
        $passwordHash,
        $username
    );

    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan data pelanggan.');
    }

    $stmt->close();

    // Hapus data registrasi sementara dari session
    unset(
        $_SESSION['register_email'],
        $_SESSION['register_password']
    );

    // Arahkan pelanggan ke halaman login
    header('Location: ../HTML/login.html?status=register_success');
    exit;

} catch (Throwable $e) {

    error_log(
        'Simpan pelanggan error: ' . $e->getMessage()
    );

    kembaliUsername('error');
}
