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
    // Cek kunjungan yang masih aktif (belum Selesai) milik dokter ini.
    // Selama kunjungan masih berjalan, dokter tidak boleh dinonaktifkan.
    $cek = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM kunjungan WHERE id_dokter = ? AND status <> 'Selesai'"
    );
    mysqli_stmt_bind_param($cek, 'i', $id);
    mysqli_stmt_execute($cek);
    $aktif = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($cek))['total'];

    if ($aktif > 0) {
        $_SESSION['flash_error'] = 'Dokter tidak bisa dihapus karena masih memiliki '
            . $aktif . ' kunjungan yang belum selesai.';
    } else {
        // SOFT DELETE: cuma set aktif=0.
        // Data dokter tetap ada di DB, kunjungan & resep tetap utuh untuk laporan.
        // Dokter yang aktif=0 tidak akan muncul di daftar dokter dan dropdown pendaftaran.
        $stmt = mysqli_prepare($conn, "UPDATE dokter SET aktif = 0 WHERE id_dokter = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['flash_success'] = 'Data dokter berhasil dihapus. Riwayat kunjungan tetap tersimpan untuk laporan.';
        } else {
            $_SESSION['flash_error'] = 'Gagal menghapus dokter: ' . mysqli_error($conn);
        }
    }
}

header('Location: ' . $base . '/dokter/data_dokter.php');
exit();
