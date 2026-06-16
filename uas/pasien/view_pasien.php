<?php
session_start();

include dirname(__DIR__) . '/config/koneksi.php';

$base = BASE_URL;

// Proteksi ketat: yang bisa akses halaman ini HANYA user dengan role pasien
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pasien') {
    header('Location: ' . $base . '/users/login.php');
    exit();
}

// Ambil data spesifik milik pasien yang sedang login saat ini
$stmt = mysqli_prepare($conn, "SELECT * FROM pasien WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id_user']);
mysqli_stmt_execute($stmt);
$pasien = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Jika ternyata database kosong / belum pernah isi data, arahkan ke form tambah
if (!$pasien) {
    header('Location: ' . $base . '/pasien/tambah_pasien.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets\css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_pasien.php'; ?>

<div class="container" style="max-width:700px; margin-top: 40px;">
    <div class="card">
        <div class="card-header">
            <h2>Data Profil Saya</h2>
            <a href="<?= $base ?>/users/dashboard_pasien.php" class="btn btn-outline">Kembali</a>
        </div>

        <div style="margin-top:20px;">
            <table class="table" style="width:100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 14px; font-weight:600; width:30%;">Nama Lengkap</td>
                    <td style="padding: 14px;">: <?= htmlspecialchars($pasien['nama_pasien']) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 14px; font-weight:600;">NIK (No. KTP)</td>
                    <td style="padding: 14px;">: <?= htmlspecialchars($pasien['nik'] ?: '—') ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 14px; font-weight:600;">No. BPJS</td>
                    <td style="padding: 14px;">: <?= htmlspecialchars($pasien['no_bpjs'] ?: '—') ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 14px; font-weight:600;">Jenis Kelamin</td>
                    <td style="padding: 14px;">: <?= htmlspecialchars($pasien['jenis_kelamin']) ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 14px; font-weight:600;">No. Handphone</td>
                    <td style="padding: 14px;">: <?= htmlspecialchars($pasien['no_hp'] ?: '—') ?></td>
                </tr>
                <tr>
                    <td style="padding: 14px; font-weight:600; vertical-align: top;">Alamat Tempat Tinggal</td>
                    <td style="padding: 14px; line-height: 1.6;">: <?= nl2br(htmlspecialchars($pasien['alamat'] ?: '—')) ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

</body>
</html>