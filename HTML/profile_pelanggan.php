<?php
// Mulai session
session_start();

// Koneksi Database MySQL
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ajibcoffee";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil ID Pelanggan dari session
$idPelanggan = isset($_SESSION['idPelanggan']) ? $_SESSION['idPelanggan'] : 1;

$sql = "SELECT idPelanggan, username, email, phone, avatar FROM pelanggan WHERE idPelanggan = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idPelanggan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $pelanggan = $result->fetch_assoc();
} else {
    $pelanggan = [
        'username' => 'valent_124',
        'email'    => 'valentina@gmail.com',
        'phone'    => '+62 188-0882-078',
        'avatar'   => '../ASSETS/img/profile_default.png'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Pelanggan | AJIB Coffee</title>
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../CSS/profile_pelanggan.css">
</head>
<body>

  <!-- NAVBAR ATAS -->
  <header class="custom-navbar">
    <a href="home.php" class="brand-logo" aria-label="Ajib Coffee - Beranda">
      <img src="../ASSETS/img/ajib_logo2.png" alt="Logo Ajib Coffee">
    </a>
    
    <!-- KAPSUL MENU NAVIGASI -->
    <ul class="nav-menu">
      <li class="nav-item">
        <a class="nav-link" href="main.html">Beranda</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="about.php">Tentang</a>
      </li>
      <li class="nav-item">
        <a class="nav-link cart-link" href="keranjang." aria-label="Keranjang belanja">
          <i class="bi bi-cart3"></i>
        </a>
      </li>
      <li class="nav-item">
        <!-- Menu Profile Aktif -->
        <a class="nav-link profile-link active" href="profile_pelanggan.php" aria-label="Profil akun">
          <i class="bi bi-person-circle"></i>
        </a>
      </li>
    </ul>
  </header>

  <div class="profile-page-wrapper">

    <!-- KARTU PROFIL UTAMA -->
    <div class="profile-card-container">
      
      <!-- SISI KIRI: PANEL PUTIH LENGKUNG -->
      <div class="profile-left-panel">
        <div class="avatar-wrapper">
          <img src="<?php echo htmlspecialchars($pelanggan['avatar'] ?? '../ASSETS/img/profile_default.png'); ?>" alt="Foto Profile">
          <button class="edit-avatar-btn" title="Ubah Foto">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
        </div>

        <!-- Username -->
        <div class="profile-field-group">
          <label>Username</label>
          <div class="field-input-box">
            <span><?php echo htmlspecialchars($pelanggan['username']); ?></span>
            <button title="Edit Username">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Email -->
        <div class="profile-field-group">
          <label>Email:</label>
          <div class="field-input-box">
            <span><?php echo htmlspecialchars($pelanggan['email']); ?></span>
            <button title="Edit Email">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Phone -->
        <div class="profile-field-group">
          <label>Phone:</label>
          <div class="field-input-box">
            <span><?php echo htmlspecialchars($pelanggan['phone'] ?? '+62 188-0882-078'); ?></span>
            <button title="Edit Nomor Telepon">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Logout Button -->
        <div class="logout-btn-container">
          <a href="../PHP/loadLogout.php" class="logout-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
            Logout
          </a>
        </div>
      </div>

      <!-- SISI KANAN: PANEL HIJAU OPSI MENU -->
      <div class="profile-right-panel">
        <h1>Profile</h1>
        <div class="profile-divider"></div>

        <div class="profile-menu-options">
          <a href="pesanan_aktif.php" class="option-btn">
            <span>Lihat Pesanan Aktif</span>
            <div class="icon-box">
              <i class="bi bi-calendar2-check" style="font-size: 22px;"></i>
            </div>
          </a>

          <a href="riwayat_transaksi.php" class="option-btn">
            <span>Lihat Riwayat Transaksi</span>
            <div class="icon-box">
              <i class="bi bi-cart3" style="font-size: 22px;"></i>
            </div>
          </a>

          <a href="ewallet.php" class="option-btn">
            <span>Tautkan E-Wallet</span>
            <div class="icon-box">
              <i class="bi bi-credit-card-2-front" style="font-size: 22px;"></i>
            </div>
          </a>
        </div>
      </div>

    </div>

  </div>

</body>
</html>