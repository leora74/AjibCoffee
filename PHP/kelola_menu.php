<?php
session_start();

// Koneksi ke Database
$host     = "localhost";
$user_db  = "root";
$pass_db  = "";
$name_db  = "ajibcoffee"; // Sesuaikan nama database Anda

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = mysqli_connect($host, $user_db, $pass_db, $name_db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Session Admin Default jika belum login
$id_admin = $_SESSION['id_admin'] ?? 1;

// ==========================================
// PROSES CRUD (CREATE, UPDATE, DELETE)
// ==========================================

// 1. TAMBAH MENU (CREATE)
if (isset($_POST['tambah_menu'])) {
    $nama_menu = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $varian    = mysqli_real_escape_string($koneksi, $_POST['varian']);
    $harga     = $_POST['harga'] ?: 0;
    $harga_hot = $_POST['harga_hot'] ?: 0;
    $harga_ice = $_POST['harga_ice'] ?: 0;
    $stok      = $_POST['stok'] ?: 0;
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Upload Gambar
    $gambar_nama = $_FILES['gambar']['name'];
    $gambar_tmp  = $_FILES['gambar']['tmp_name'];
    $nama_gambar_baru = "default.png";

    if (!empty($gambar_nama)) {
        $ext = pathinfo($gambar_nama, PATHINFO_EXTENSION);
        $nama_gambar_baru = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($gambar_tmp, "../ASSETS/img/" . $nama_gambar_baru);
    }

    $query = "INSERT INTO menu (nama_menu, deskripsi, harga, stok, gambar, varian, id_admin, harga_hot, harga_ice) 
              VALUES ('$nama_menu', '$deskripsi', '$harga', '$stok', '$nama_gambar_baru', '$varian', '$id_admin', '$harga_hot', '$harga_ice')";
    
    if (mysqli_query($koneksi, $query)) {
        header("Location: kelola_menu.php?status=success_add");
        exit();
    }
}

// 2. EDIT MENU (UPDATE)
if (isset($_POST['edit_menu'])) {
    $id_menu   = $_POST['id_menu'];
    $nama_menu = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $varian    = mysqli_real_escape_string($koneksi, $_POST['varian']);
    $harga     = $_POST['harga'] ?: 0;
    $harga_hot = $_POST['harga_hot'] ?: 0;
    $harga_ice = $_POST['harga_ice'] ?: 0;
    $stok      = $_POST['stok'] ?: 0;
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Cek jika gambar diubah
    $gambar_nama = $_FILES['gambar']['name'];
    $gambar_tmp  = $_FILES['gambar']['tmp_name'];

    if (!empty($gambar_nama)) {
        $ext = pathinfo($gambar_nama, PATHINFO_EXTENSION);
        $nama_gambar_baru = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($gambar_tmp, "../ASSETS/img/" . $nama_gambar_baru);
        $sql_gambar = ", gambar='$nama_gambar_baru'";
    } else {
        $sql_gambar = "";
    }

    $query = "UPDATE menu SET 
                nama_menu='$nama_menu', 
                deskripsi='$deskripsi', 
                harga='$harga', 
                stok='$stok', 
                varian='$varian', 
                harga_hot='$harga_hot', 
                harga_ice='$harga_ice' 
                $sql_gambar 
              WHERE id_menu='$id_menu'";

    if (mysqli_query($koneksi, $query)) {
        header("Location: kelola_menu.php?status=success_edit");
        exit();
    }
}

// 3. HAPUS MENU (DELETE)
if (isset($_GET['hapus'])) {
    $id_menu = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM menu WHERE id_menu='$id_menu'");
    header("Location: kelola_menu.php?status=success_delete");
    exit();
}

// READ (Ambil Data Menu)
$result_menu = mysqli_query($koneksi, "SELECT * FROM menu ORDER BY id_menu DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengelolaan Menu | AJIB Coffee</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../CSS/dashboard_admin.css">

    <style>
        .page-title {
            color: #285d49;
            font-weight: 700;
            font-size: 24px;
            font-family: 'Bricolage Grotesque', sans-serif;
        }
        .menu-list-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .menu-img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 8px;
        }
        .badge-variant {
            background-color: #e9ecef;
            color: #6c757d;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .status-tersedia { color: #28a745; font-size: 13px; font-weight: 500; }
        .status-habis { color: #dc3545; font-size: 13px; font-weight: 500; }
        .price-text {
            color: #c89357;
            font-size: 18px;
            font-weight: 600;
        }
        .btn-add-custom {
            background-color: #285d49;
            color: #fff;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-add-custom:hover { background-color: #1e4b3b; color: #fff; }
    </style>
</head>

<body>
    <div class="dashboard-page">

        <!-- NAVBAR -->
        <nav class="navbar navbar-expand-md navbar-light custom-navbar">
            <div class="container-fluid px-4 px-lg-5">
                <a class="navbar-brand brand-logo" href="../HTML/dashboard_admin.html">
                    <img src="../ASSETS/img/ajib_logo2.png" alt="Logo AJIB Coffee">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAjib">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarAjib">
                    <ul class="navbar-nav ms-auto align-items-md-center nav-menu">
                        <li class="nav-item">
                            <a class="btn btn-register-nav" href="../HTML/dashboard.html">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link icon-nav-button" type="button" title="Notifikasi">
                                <i class="bi bi-bell"></i>
                                <span class="notification-dot"></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <a href="../HTML/profile_admin.php" class="nav-link icon-nav-button" title="Profil admin">
                                <i class="bi bi-person-circle"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="dashboard-content">

            <!-- HEADER TITLE & TOMBOL TAMBAH -->
            <div class="text-center my-3">
                <h2 class="page-title">Pengelolaan Menu</h2>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-semibold text-secondary mb-0">Daftar Menu</h5>
                <button class="btn btn-add-custom" data-bs-toggle="modal" data-bs-target="#modalTambah">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Menu
                </button>
            </div>

            <!-- DAFTAR MENU LIST (READ) -->
            <div class="menu-container">
                <?php if (mysqli_num_rows($result_menu) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result_menu)): ?>
                        <div class="menu-list-card">
                            <div class="d-flex align-items-center gap-3">
                                <img src="../ASSETS/img/<?php echo !empty($row['gambar']) ? $row['gambar'] : 'default.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($row['nama_menu']); ?>" 
                                     class="menu-img">
                                <div>
                                    <h5 class="fw-bold mb-1" style="color: #285d49;">
                                        <?php echo htmlspecialchars($row['nama_menu']); ?>
                                    </h5>
                                    <span class="badge-variant">
                                        <?php echo htmlspecialchars($row['varian'] ?: 'Umum'); ?>
                                    </span>
                                    <div class="mt-1">
                                        <?php if ($row['stok'] > 0): ?>
                                            <span class="status-tersedia">Tersedia (Stok: <?php echo $row['stok']; ?>)</span>
                                        <?php else: ?>
                                            <span class="status-habis">Habis</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-4">
                                <span class="price-text">
                                    Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                                </span>

                                <div class="d-flex gap-2">
                                    <!-- Tombol Edit Modal -->
                                    <button class="btn btn-sm btn-outline-secondary border-0 fs-5" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEdit<?php echo $row['id_menu']; ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <!-- Tombol Hapus -->
                                    <a href="kelola_menu.php?hapus=<?php echo $row['id_menu']; ?>" 
                                       class="btn btn-sm btn-outline-danger border-0 fs-5"
                                       onclick="return confirm('Yakin ingin menghapus menu ini?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL EDIT MENU -->
                        <div class="modal fade" id="modalEdit<?php echo $row['id_menu']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="kelola_menu.php" method="POST" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold" style="color: #285d49;">Edit Menu</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id_menu" value="<?php echo $row['id_menu']; ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Nama Menu</label>
                                                <input type="text" name="nama_menu" class="form-control" value="<?php echo htmlspecialchars($row['nama_menu']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Varian/Kategori (Contoh: Americano Series, Non Coffe, Signature)</label>
                                                <input type="text" name="varian" class="form-control" value="<?php echo htmlspecialchars($row['varian']); ?>">
                                            </div>
                                            <div class="row">
                                                <div class="col-6 mb-3">
                                                    <label class="form-label">Harga Utama (Rp)</label>
                                                    <input type="number" name="harga" class="form-control" value="<?php echo $row['harga']; ?>" required>
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <label class="form-label">Stok</label>
                                                    <input type="number" name="stok" class="form-control" value="<?php echo $row['stok']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-6 mb-3">
                                                    <label class="form-label">Harga Hot (Optional)</label>
                                                    <input type="number" name="harga_hot" class="form-control" value="<?php echo $row['harga_hot']; ?>">
                                                </div>
                                                <div class="col-6 mb-3">
                                                    <label class="form-label">Harga Ice (Optional)</label>
                                                    <input type="number" name="harga_ice" class="form-control" value="<?php echo $row['harga_ice']; ?>">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Deskripsi</label>
                                                <textarea name="deskripsi" class="form-control" rows="2"><?php echo htmlspecialchars($row['deskripsi']); ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ganti Gambar (Biarkan kosong jika tidak ingin diubah)</label>
                                                <input type="file" name="gambar" class="form-control" accept="image/*">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" name="edit_menu" class="btn btn-add-custom">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-muted my-5">Belum ada menu yang ditambahkan.</div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- MODAL TAMBAH MENU -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="kelola_menu.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" style="color: #285d49;">Tambah Menu Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Menu</label>
                            <input type="text" name="nama_menu" class="form-control" placeholder="Contoh: Montblanc" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Varian/Kategori</label>
                            <input type="text" name="varian" class="form-control" placeholder="Contoh: Americano Series / Signature / Non Coffe">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Harga Utama (Rp)</label>
                                <input type="number" name="harga" class="form-control" placeholder="18000" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" name="stok" class="form-control" value="10" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Harga Hot (Opsional)</label>
                                <input type="number" name="harga_hot" class="form-control" placeholder="0">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Harga Ice (Opsional)</label>
                                <input type="number" name="harga_ice" class="form-control" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi rasa atau bahan menu..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Gambar Menu</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_menu" class="btn btn-add-custom">Simpan Menu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>