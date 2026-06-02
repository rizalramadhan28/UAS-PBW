<?php
if (!isset($_SESSION)) {
    session_start();
}

// Base URL project (folder root di htdocs/laragon)
$base = '/uas';
?>

<style>
.navbar {
    background: #0d7a54;
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
    <a href="<?= $base ?>/users/dashboard_admin.php">Dashboard</a>
    <a href="<?= $base ?>/pasien/data_pasien.php">Pasien</a>
    <a href="<?= $base ?>/poli/data_poli.php">Poli</a>
    <a href="<?= $base ?>/dokter/data_dokter.php">Dokter</a>
    <a href="<?= $base ?>/obat/data_obat.php">Obat</a>
    <a href="<?= $base ?>/kunjungan/data_kunjungan.php">Kunjungan</a>
    <a href="<?= $base ?>/detail_kunjungan/data_detail.php">Resep</a>
    <a href="<?= $base ?>/laporan/laporan.php">Laporan</a>
    <a href="<?= $base ?>/users/logout.php">Logout</a>
</div>
