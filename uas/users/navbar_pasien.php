<?php
if (!isset($_SESSION)) {
    session_start();
}

// Base URL project (folder root di htdocs/laragon)
$base = '/uas';
?>

<style>
.navbar {
    background: #27ae60;
    padding: 15px;
}
.navbar a {
    color: white;
    text-decoration: none;
    margin-right: 20px;
    font-weight: bold;
}
.navbar a:hover {
    color: yellow;
}
</style>

<div class="navbar">
    <a href="<?= $base ?>/users/dashboard_pasien.php">Dashboard</a>
    <a href="<?= $base ?>/pasien/tambah_pasien.php">Profil Saya</a>
    <a href="<?= $base ?>/kunjungan/tambah_kunjungan.php">Daftar Kunjungan</a>
    <a href="<?= $base ?>/users/logout.php">Logout</a>
</div>
