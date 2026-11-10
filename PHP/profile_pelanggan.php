<?php
session_start();

// 1. Cek Login Pelanggan (Jika belum login, redirect ke login.php)
if (!isset($_SESSION['idPelanggan'])) {
    // TEMPORARY: Jika session belum di-set saat testing, default ke ID 1
    $_SESSION['idPelanggan'] = 1;
    
    // Aktifkan baris di bawah ini jika halaman login sudah siap:
    // header("Location: login.php");
    // exit();
}

// 2. Koneksi ke Database
$host     = "localhost";
$user_db  = "root";
$pass_db  = "";
$name_db  = "ajib_coffee"; // Sesuaikan nama database Anda

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = mysqli_connect($host, $user_db, $pass_db, $name_db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 3. Ambil Data Pelanggan berdasarkan Session idPelanggan
$id_pelanggan = $_SESSION['idPelanggan'];
// Sesuaikan nama tabel 'pelanggan' jika di database Anda berbeda
$query  = "SELECT * FROM pelanggan WHERE idPelanggan = '$id_pelanggan'";
$result = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $data     = mysqli_fetch_assoc($result);
    $username = $data['username'];
    $email    = $data['email'];
} else {
    $username = "Pengguna";
    $email    = "Email tidak ditemukan";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pelanggan | AJIB Coffee</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../CSS/profile_pelanggan.css">
</head>

<body>
    <div class="dashboard-page">

        <!-- NAVBAR -->
        <nav class="navbar navbar-expand-md navbar-light custom-navbar">
            <div class="container-fluid px-4 px-lg-5">

                <!-- LOGO -->
                <a class="navbar-brand brand-logo" href="beranda.php" aria-label="AJIB Coffee">
                    <img src="../ASSETS/img/ajib_logo2.png" alt="Logo AJIB Coffee">
                </a>

                <!-- TOGGLE MOBILE -->
                <button 
                    class="navbar-toggler" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#navbarAjib" 
                    aria-controls="navbarAjib" 
                    aria-expanded="false" 
                    aria-label="Buka navigasi"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- MENU NAVBAR -->
                <div class="collapse navbar-collapse" id="navbarAjib">
                    <ul class="navbar-nav ms-auto align-items-md-center nav-menu">

                        <!-- BERANDA / MENU -->
                        <li class="nav-item">
                            <a class="btn btn-register-nav" href="beranda.php">
                                Menu
                            </a>
                        </li>

                        <!-- KERANJANG -->
                        <li class="nav-item">
                            <a href="keranjang.php" class="nav-link icon-nav-button" title="Keranjang Belanja">
                                <i class="bi bi-cart3"></i>
                            </a>
                        </li>

                        <!-- PROFIL PELANGGAN (AKTIF) -->
                        <li class="nav-item">
                            <a href="profile_pelanggan.php" class="nav-link icon-nav-button active" title="Profil Pelanggan">
                                <i class="bi bi-person-circle"></i>
                            </a>
                        </li>

                    </ul>
                </div>

            </div>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="main-container">
            
            <!-- PROFILE CARD (LEFT) -->
            <div class="profile-card">
                <div class="avatar-box">
                    <i class="bi bi-person-circle"></i>
                </div>
                
                <h2 class="profile-title"><?php echo htmlspecialchars($username); ?></h2>

                <div class="form-group">
                    <label>Username</label>
                    <div class="field-box"><?php echo htmlspecialchars($username); ?></div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <div class="field-box"><?php echo htmlspecialchars($email); ?></div>
                </div>

                <a href="logout_pelanggan.php" class="btn-logout" style="text-decoration: none;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>

            <!-- MENU QUICK ACCESS (RIGHT) -->
            <div class="profile-content">
                <h1 class="welcome-title">
                    Selamat Datang<br><?php echo htmlspecialchars($username); ?>
                </h1>

                <div class="divider"></div>

                <!-- RIWAYAT PESANAN -->
                <a href="riwayat_pesanan.php" class="menu-card-link">
                    <div class="menu-card">
                        <span>Riwayat Pesanan</span>
                        <i class="bi bi-clock-history"></i>
                    </div>
                </a>

                <!-- EDIT PROFIL / GANTI PASSWORD -->
                <a href="edit_profile.php" class="menu-card-link mt-3">
                    <div class="menu-card">
                        <span>Pengaturan Akun</span>
                        <i class="bi bi-gear"></i>
                    </div>
                </a>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>