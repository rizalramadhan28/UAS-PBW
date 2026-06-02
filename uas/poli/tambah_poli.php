<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';
$error = '';

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_poli'] ?? '');

    if ($nama === '') {
        $error = 'Nama poli tidak boleh kosong.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO poli (nama_poli) VALUES (?)");
        mysqli_stmt_bind_param($stmt, 's', $nama);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/poli/data_poli.php');
            exit();
        }
        $error = 'Gagal menyimpan: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Poli — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:600px;">
    <div class="card">
        <div class="card-header">
            <h2>Tambah Data Poli</h2>
            <a href="<?= $base ?>/poli/data_poli.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Poli</label>
                <input type="text" name="nama_poli" required placeholder="Contoh: Poli Umum"
                       value="<?= htmlspecialchars($_POST['nama_poli'] ?? '') ?>">
            </div>

            <div style="margin-top:24px;display:flex;gap:10px;">
                <button type="submit" name="simpan" class="btn btn-primary">Simpan Data</button>
                <button type="reset" class="btn btn-outline">Reset</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
