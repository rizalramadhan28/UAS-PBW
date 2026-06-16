<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    // Ambil data resep dulu (untuk kembalikan stok obat)
    $stmt = mysqli_prepare($conn, "SELECT id_obat, jumlah_obat FROM detail_kunjungan WHERE id_detail = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $resep = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($resep) {
        mysqli_begin_transaction($conn);
        try {
            // Hapus resep
            $stmt = mysqli_prepare($conn, "DELETE FROM detail_kunjungan WHERE id_detail = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);

            // Kembalikan stok obat
            $stmt = mysqli_prepare($conn, "UPDATE obat SET stok = stok + ? WHERE id_obat = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $resep['jumlah_obat'], $resep['id_obat']);
            mysqli_stmt_execute($stmt);

            mysqli_commit($conn);
            $_SESSION['flash_success'] = 'Resep berhasil dihapus. Stok obat telah dikembalikan.';
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $_SESSION['flash_error'] = 'Gagal menghapus resep: ' . $e->getMessage();
        }
    }
}

header('Location: ' . $base . '/detail_kunjungan/data_detail.php');
exit();
