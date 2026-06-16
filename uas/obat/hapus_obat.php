<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM obat WHERE id_obat = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header('Location: ' . $base . '/obat/data_obat.php');
exit();
