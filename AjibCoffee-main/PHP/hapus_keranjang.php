<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = (string)($_POST['key'] ?? '');
    unset($_SESSION['keranjang'][$key]);
}
header('Location: ../HTML/keranjang.html');
exit;
