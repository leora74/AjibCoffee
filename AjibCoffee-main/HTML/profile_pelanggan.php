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
$avatarMessage = $_SESSION['avatarMessage'] ?? '';
unset($_SESSION['avatarMessage']);
$phoneMessage = '';
$phoneEditOpen = false;
$phoneInputValue = '';

if (empty($_SESSION['avatarCsrfToken'])) {
    $_SESSION['avatarCsrfToken'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_phone'])) {
    $phone = trim($_POST['phone'] ?? '');
    $phoneInputValue = $phone;

    if (
        !isset($_POST['avatar_csrf_token']) ||
        !hash_equals($_SESSION['avatarCsrfToken'], (string) $_POST['avatar_csrf_token'])
    ) {
        $phoneMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
        $phoneEditOpen = true;
    } elseif (!preg_match('/^\+?[0-9]{8,12}$/', $phone)) {
        $phoneMessage = 'Nomor telepon harus 8–13 karakter, hanya angka, dan boleh diawali +.';
        $phoneEditOpen = true;
    } else {
        $updatePhone = $conn->prepare('UPDATE pelanggan SET phone = ? WHERE idPelanggan = ?');
        if (!$updatePhone) {
            error_log('Gagal menyiapkan update nomor telepon: ' . $conn->error);
            $phoneMessage = 'Nomor telepon gagal disimpan. Silakan coba lagi.';
            $phoneEditOpen = true;
        } else {
            $updatePhone->bind_param('si', $phone, $idPelanggan);
            if (!$updatePhone->execute()) {
                error_log('Gagal menyimpan nomor telepon: ' . $updatePhone->error);
                $phoneMessage = 'Nomor telepon gagal disimpan. Silakan coba lagi.';
                $phoneEditOpen = true;
            } else {
                $_SESSION['phoneMessage'] = 'Nomor telepon berhasil diperbarui.';
                $updatePhone->close();
                header('Location: profile_pelanggan.php');
                exit;
            }
            $updatePhone->close();
        }
    }
}

$phoneMessage = $_SESSION['phoneMessage'] ?? $phoneMessage;
unset($_SESSION['phoneMessage']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    $errorMessage = '';
    $upload = $_FILES['avatar'] ?? null;

    if (
        !isset($_POST['avatar_csrf_token']) ||
        !hash_equals($_SESSION['avatarCsrfToken'], (string) $_POST['avatar_csrf_token'])
    ) {
        $errorMessage = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (!$upload || !isset($upload['error']) || $upload['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
            $errorMessage = 'Ukuran foto terlalu besar. Maksimal 5 MB.';
        } elseif ($uploadError === UPLOAD_ERR_NO_FILE) {
            $errorMessage = 'Pilih foto dari perangkat terlebih dahulu.';
        } else {
            $errorMessage = 'Foto gagal diunggah. Silakan coba lagi.';
        }
    } elseif ($upload['size'] > 5 * 1024 * 1024) {
        $errorMessage = 'Ukuran foto terlalu besar. Maksimal 5 MB.';
    } else {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo ? finfo_file($fileInfo, $upload['tmp_name']) : false;
        if ($fileInfo) {
            finfo_close($fileInfo);
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (!$mimeType || !isset($allowedTypes[$mimeType])) {
            $errorMessage = 'Format foto harus JPG, PNG, atau WEBP.';
        } else {
            $uploadDirectory = dirname(__DIR__) . '/ASSETS/img/profile-uploads';
            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                $errorMessage = 'Folder penyimpanan foto tidak dapat dibuat.';
            } else {
                $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                $destination = $uploadDirectory . '/' . $fileName;
                $avatarPath = '../ASSETS/img/profile-uploads/' . $fileName;

                if (!move_uploaded_file($upload['tmp_name'], $destination)) {
                    $errorMessage = 'Foto gagal disimpan. Silakan coba lagi.';
                } else {
                    $update = $conn->prepare('UPDATE pelanggan SET avatar = ? WHERE idPelanggan = ?');
                    if (!$update) {
                        unlink($destination);
                        $errorMessage = 'Foto gagal disimpan ke profil. Silakan coba lagi.';
                    } else {
                        $update->bind_param('si', $avatarPath, $idPelanggan);
                        if (!$update->execute() || $update->affected_rows < 1) {
                            unlink($destination);
                            $errorMessage = 'Foto gagal disimpan ke profil. Silakan coba lagi.';
                        } else {
                            $_SESSION['avatarMessage'] = 'Foto profil berhasil diperbarui.';
                        }
                        $update->close();
                    }
                }
            }
        }
    }

    if ($errorMessage !== '') {
        $_SESSION['avatarMessage'] = $errorMessage;
    }

    header('Location: profile_pelanggan.php');
    exit;
}

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
        'avatar'   => ''
    ];
}

if (!$phoneEditOpen) {
    $phoneInputValue = $pelanggan['phone'] ?? '';
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
        <a class="nav-link" href="about_us.html">Tentang Kami</a>
      </li>
      <li class="nav-item">
        <a class="nav-link cart-link" href="keranjang.html" aria-label="Keranjang belanja">
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
          <?php if (!empty($pelanggan['avatar'])): ?>
            <img src="<?php echo htmlspecialchars($pelanggan['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto Profil">
          <?php else: ?>
            <i class="bi bi-person-fill avatar-placeholder" aria-hidden="true"></i>
          <?php endif; ?>
          <form id="avatar-upload-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="avatar_csrf_token" value="<?php echo htmlspecialchars($_SESSION['avatarCsrfToken'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="upload_avatar" value="1">
            <input id="avatar-upload" class="avatar-file-input" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" aria-label="Pilih foto profil">
            <label class="edit-avatar-btn" for="avatar-upload" title="Ubah Foto" aria-label="Pilih foto profil">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            </label>
          </form>
        </div>
        <?php if ($avatarMessage !== ''): ?>
          <p class="avatar-message" role="status"><?php echo htmlspecialchars($avatarMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

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
          <div class="field-input-box" id="phone-display-row"<?php echo $phoneEditOpen ? ' hidden' : ''; ?>>
            <span><?php echo htmlspecialchars($pelanggan['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?: 'Belum diisi'; ?></span>
            <button type="button" id="edit-phone-btn" title="Edit Nomor Telepon" aria-label="Edit nomor telepon" aria-expanded="<?php echo $phoneEditOpen ? 'true' : 'false'; ?>">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
              </svg>
            </button>
          </div>
          <form id="phone-edit-form" class="phone-edit-form" method="post"<?php echo $phoneEditOpen ? '' : ' hidden'; ?>>
            <input type="hidden" name="avatar_csrf_token" value="<?php echo htmlspecialchars($_SESSION['avatarCsrfToken'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="update_phone" value="1">
            <input
              type="tel"
              name="phone"
              id="phone-input"
              value="<?php echo htmlspecialchars($phoneInputValue, ENT_QUOTES, 'UTF-8'); ?>"
              placeholder="Contoh: +62812345678"
              inputmode="tel"
              maxlength="13"
              pattern="\+?[0-9]{8,12}"
              aria-label="Nomor telepon"
              required
            >
            <button type="submit" class="phone-save-btn">Simpan</button>
            <button type="button" class="phone-cancel-btn" id="cancel-phone-btn">Batal</button>
          </form>
          <?php if ($phoneMessage !== ''): ?>
            <p class="phone-message" role="status"><?php echo htmlspecialchars($phoneMessage, ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
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

  <script>
    const phoneDisplayRow = document.getElementById('phone-display-row');
    const phoneEditForm = document.getElementById('phone-edit-form');
    const editPhoneButton = document.getElementById('edit-phone-btn');

    editPhoneButton.addEventListener('click', function () {
      phoneDisplayRow.hidden = true;
      phoneEditForm.hidden = false;
      editPhoneButton.setAttribute('aria-expanded', 'true');
      document.getElementById('phone-input').focus();
    });

    document.getElementById('cancel-phone-btn').addEventListener('click', function () {
      phoneEditForm.hidden = true;
      phoneDisplayRow.hidden = false;
      editPhoneButton.setAttribute('aria-expanded', 'false');
    });

    document.getElementById('avatar-upload').addEventListener('change', function () {
      if (this.files.length > 0) {
        document.getElementById('avatar-upload-form').submit();
      }
    });
  </script>
</body>
</html>