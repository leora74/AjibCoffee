<?php
session_start();

// Hapus variabel session khusus admin
unset($_SESSION['idPelanggan']);
unset($_SESSION['username']);
unset($_SESSION['role']);

// Jika ingin menghapus seluruh session admin
session_unset();
session_destroy();

// Redirect ke halaman login admin
header("Location: ../index.html"); 
exit();
?>