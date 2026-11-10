<?php
session_start();

// Hapus variabel session khusus admin
unset($_SESSION['id_admin']);
unset($_SESSION['username_admin']);
unset($_SESSION['role']);

// Jika ingin menghapus seluruh session admin
session_unset();
session_destroy();

// Redirect ke halaman login admin
header("Location: ../HTML/login_admin.html"); 
exit();
?>