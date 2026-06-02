<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';
$error = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $base . '/poli/data_poli.php');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM poli WHERE id_poli = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    header('Location: ' . $base . '/poli/data_poli.php');
    exit();
}

if (isset($_POST['update'])) {
    $nama = trim($_POST['nama_poli'] ?? '');
    if ($nama === '') {
        $error = 'Nama poli tidak boleh kosong.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE poli SET nama_poli = ? WHERE id_poli = ?");
        mysqli_stmt_bind_param($stmt, 'si', $nama, $id);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/poli/data_poli.php');
            exit();
        }
        $error = 'Gagal memperbarui: ' . mysqli_error($conn);
    }
    $row['nama_poli'] = $nama;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Poli — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:600px;">
    <div class="card">
        <div class="card-header">
            <h2>Edit Data Poli</h2>
            <a href="<?= $base ?>/poli/data_poli.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Poli</label>
                <input type="text" name="nama_poli" required
                       value="<?= htmlspecialchars($row['nama_poli']) ?>">
            </div>

            <div style="margin-top:24px;">
                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
