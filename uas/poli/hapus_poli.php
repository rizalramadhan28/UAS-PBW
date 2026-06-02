<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM poli WHERE id_poli = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    @mysqli_stmt_execute($stmt);
}

header('Location: ' . $base . '/poli/data_poli.php');
exit();
