<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;

function hitung($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    return $r ? (int) (mysqli_fetch_assoc($r)['jml'] ?? 0) : 0;
}

$total_pasien    = hitung($conn, "SELECT COUNT(*) jml FROM pasien");
$total_dokter    = hitung($conn, "SELECT COUNT(*) jml FROM dokter WHERE aktif = 1");
$total_poli      = hitung($conn, "SELECT COUNT(*) jml FROM poli");
$total_obat      = hitung($conn, "SELECT COUNT(*) jml FROM obat");
$total_kunjungan = hitung($conn, "SELECT COUNT(*) jml FROM kunjungan");
$kunjungan_hari  = hitung($conn, "SELECT COUNT(*) jml FROM kunjungan WHERE tanggal = CURRENT_DATE");
$obat_menipis    = hitung($conn, "SELECT COUNT(*) jml FROM obat WHERE stok <= 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include 'navbar_admin.php'; ?>

<div class="container">
    <h1 class="page-title">Dashboard Admin Klinik</h1>
    <p class="page-subtitle">Ringkasan data dan akses cepat ke semua modul.</p>

    <!-- STATISTIK -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-label">Total Pasien</div>
            <div class="stat-value"><?= $total_pasien ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🩺</div>
            <div class="stat-label">Total Dokter</div>
            <div class="stat-value"><?= $total_dokter ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏥</div>
            <div class="stat-label">Total Poli</div>
            <div class="stat-value"><?= $total_poli ?></div>
        </div>
        <div class="stat-card accent">
            <div class="stat-icon">💊</div>
            <div class="stat-label">Total Obat</div>
            <div class="stat-value"><?= $total_obat ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">📋</div>
            <div class="stat-label">Total Kunjungan</div>
            <div class="stat-value"><?= $total_kunjungan ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">📆</div>
            <div class="stat-label">Kunjungan Hari Ini</div>
            <div class="stat-value"><?= $kunjungan_hari ?></div>
        </div>
        <?php if ($obat_menipis > 0): ?>
            <div class="stat-card danger">
                <div class="stat-icon">⚠</div>
                <div class="stat-label">Stok Obat Menipis</div>
                <div class="stat-value"><?= $obat_menipis ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- AKSI CEPAT -->
    <div class="card">
        <div class="card-header"><h2>Modul Manajemen</h2></div>
        <div class="action-grid">
            <a href="<?= $base ?>/pasien/data_pasien.php" class="action-card">
                <div class="action-icon">👥</div>
                <h3>Pasien</h3>
                <p>Kelola data pasien klinik</p>
            </a>
            <a href="<?= $base ?>/dokter/data_dokter.php" class="action-card">
                <div class="action-icon">🩺</div>
                <h3>Dokter</h3>
                <p>Kelola data dokter & spesialis</p>
            </a>
            <a href="<?= $base ?>/poli/data_poli.php" class="action-card">
                <div class="action-icon">🏥</div>
                <h3>Poli</h3>
                <p>Kelola daftar poli</p>
            </a>
            <a href="<?= $base ?>/obat/data_obat.php" class="action-card">
                <div class="action-icon">💊</div>
                <h3>Obat</h3>
                <p>Kelola stok dan harga obat</p>
            </a>
            <a href="<?= $base ?>/kunjungan/data_kunjungan.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>Kunjungan</h3>
                <p>Monitoring kunjungan pasien</p>
            </a>
            <a href="<?= $base ?>/detail_kunjungan/data_detail.php" class="action-card">
                <div class="action-icon">📝</div>
                <h3>Resep Obat</h3>
                <p>Catat dan lihat resep</p>
            </a>
            <a href="<?= $base ?>/laporan/laporan.php" class="action-card">
                <div class="action-icon">📊</div>
                <h3>Laporan</h3>
                <p>Rekap dan statistik klinik</p>
            </a>
        </div>
    </div>
</div>

</body>
</html>
