<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $base . '/dokter/data_dokter.php');
    exit();
}

// Ambil foto untuk dihapus dari disk
$stmt = mysqli_prepare($conn, "SELECT foto FROM dokter WHERE id_dokter = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if ($data && !empty($data['foto'])) {
    $foto_fs = __DIR__ . '/../uploads/' . $data['foto'];
    if (file_exists($foto_fs)) {
        @unlink($foto_fs);
    }
}

$stmt = mysqli_prepare($conn, "DELETE FROM dokter WHERE id_dokter = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);

header('Location: ' . $base . '/dokter/data_dokter.php');
exit();
