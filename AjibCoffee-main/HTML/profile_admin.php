<?php
session_start();

// 1. Cek apakah admin sudah login (opsional, sesuaikan dengan variabel session login Anda)
if (!isset($_SESSION['id_admin'])) {
    // Jika belum login, redirect ke halaman login
    // header("Location: login.php");
    // exit();

    // TEMPORARY / CONTOH: Jika session belum ada, kita set manual id_admin = 1 untuk pengetesan
    $_SESSION['id_admin'] = 1;
}

// 2. Koneksi ke Database (Sesuaikan nama database, host, username, & password)
$host     = "localhost";
$user_db  = "root";
$pass_db  = "";
$name_db  = "ajibcoffee"; // Ubah sesuai nama database Anda

$koneksi = mysqli_connect($host, $user_db, $pass_db, $name_db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 3. Ambil data admin dari database berdasarkan session id_admin
$id_admin = $_SESSION['id_admin'];
$query    = "SELECT * FROM admin WHERE id_admin = '$id_admin'"; // Sesuaikan nama tabel jika bukan 'admin'
$result   = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $data_admin = mysqli_fetch_assoc($result);
    $username   = $data_admin['username_admin'];
    $email      = $data_admin['email_admin'];
} else {
    $username   = "Admin";
    $email      = "Email tidak ditemukan";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Admin | AJIB Coffee</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../CSS/profile_admin.css">

    <!-- Custom JavaScript -->
    <script src="../JAVASCRIPT/profile_admin.js" defer></script>
</head>

<body>
    <div class="dashboard-page">

        <!-- NAVBAR DASHBOARD -->
        <nav class="navbar navbar-expand-md navbar-light custom-navbar">
            <div class="container-fluid px-4 px-lg-5">

                <!-- LOGO -->
                <a class="navbar-brand brand-logo" href="dashboard_admin.php" aria-label="AJIB Coffee">
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

                        <!-- DASHBOARD -->
                        <li class="nav-item">
                            <a class="btn btn-register-nav" href="dashboard.html">
                                Dashboard
                            </a>
                        </li>

                        <!-- NOTIFIKASI -->
                        <li class="nav-item">
                            <button 
                                class="nav-link icon-nav-button" 
                                type="button" 
                                id="notificationButton" 
                                aria-label="Notifikasi" 
                                title="Notifikasi"
                            >
                                <i class="bi bi-bell"></i>
                                <span class="notification-dot"></span>
                            </button>
                        </li>

                        <!-- PROFIL ADMIN (AKTIF) -->
                        <li class="nav-item">
                            <a href="profile_admin.php" class="nav-link icon-nav-button active" title="Profil admin">
                                <i class="bi bi-person-circle"></i>
                            </a>
                        </li>

                    </ul>
                </div>

            </div>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="main-container">
            
            <!-- Profile Card Left -->
            <div class="profile-card">
                <div class="avatar-box">
                    <i class="bi bi-person-circle"></i>
                </div>
                
                <h2 class="profile-title">Admin</h2>

                <div class="form-group">
                    <label>Username</label>
                    <div class="field-box"><?php echo htmlspecialchars($username); ?></div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <div class="field-box"><?php echo htmlspecialchars($email); ?></div>
                </div>

                <a href="../PHP/logout_admin.php" class="btn-logout" style="text-decoration: none;">
                    <i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>

            <!-- Right Content Section -->
            <div class="profile-content">
                <h1 class="welcome-title">
                    Selamat Datang Admin!<br>
                </h1>

                <div class="divider"></div>

                <a href="../PHP/kelola_menu.php" style="text-decoration: none; width: 100%; max-width: 450px;">
                    <div class="menu-card">
                        <span>Kelola Menu</span>
                        <i class="bi bi-card-checklist"></i>
                    </div>
                </a>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>