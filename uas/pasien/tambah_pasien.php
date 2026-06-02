<?php
session_start();

include '../config/koneksi.php';

$base = '/uas';
$error = '';

// Halaman ini bisa diakses oleh admin (mendaftarkan pasien baru) maupun
// oleh user role pasien (mengisi profilnya sendiri dari dashboard)
$is_admin   = isset($_SESSION['login']) && ($_SESSION['role'] ?? '') === 'admin';
$is_pasien  = isset($_SESSION['login']) && ($_SESSION['role'] ?? '') === 'pasien';

if (!$is_admin && !$is_pasien) {
    header('Location: ' . $base . '/users/login.php');
    exit();
}

if (isset($_POST['simpan'])) {
    $nama   = trim($_POST['nama_pasien'] ?? '');
    $nik    = trim($_POST['nik'] ?? '');
    $bpjs   = trim($_POST['no_bpjs'] ?? '');
    $jk     = $_POST['jenis_kelamin'] ?? '';
    $alamat = trim($_POST['alamat'] ?? '');
    $hp     = trim($_POST['no_hp'] ?? '');

    if ($nama === '') {
        $error = 'Nama pasien tidak boleh kosong.';
    } elseif (!in_array($jk, ['Laki-laki', 'Perempuan'], true)) {
        $error = 'Jenis kelamin harus dipilih.';
    } else {
        // id_user: kalau yg login pasien, pakai sessionnya; admin biarkan NULL
        $id_user = $is_pasien ? (int)$_SESSION['id_user'] : null;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO pasien (id_user, nama_pasien, nik, no_bpjs, jenis_kelamin, alamat, no_hp)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'issssss', $id_user, $nama, $nik, $bpjs, $jk, $alamat, $hp);
        if (mysqli_stmt_execute($stmt)) {
            if ($is_pasien) {
                header('Location: ' . $base . '/users/dashboard_pasien.php');
            } else {
                header('Location: ' . $base . '/pasien/data_pasien.php');
            }
            exit();
        }
        $error = 'Gagal menyimpan data: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Pasien — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include $is_admin ? '../users/navbar_admin.php' : '../users/navbar_pasien.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2><?= $is_admin ? 'Tambah Data Pasien' : 'Lengkapi Data Pasien' ?></h2>
            <?php if ($is_admin): ?>
                <a href="<?= $base ?>/pasien/data_pasien.php" class="btn btn-outline">Kembali</a>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Pasien</label>
                    <input type="text" name="nama_pasien" required
                           value="<?= htmlspecialchars($_POST['nama_pasien'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>NIK</label>
                    <input type="text" name="nik" inputmode="numeric" maxlength="20"
                           value="<?= htmlspecialchars($_POST['nik'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>No. BPJS (opsional)</label>
                    <input type="text" name="no_bpjs" inputmode="numeric" maxlength="20"
                           value="<?= htmlspecialchars($_POST['no_bpjs'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" required>
                        <option value="">— Pilih jenis kelamin —</option>
                        <option value="Laki-laki" <?= ($_POST['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= ($_POST['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="text" name="no_hp" inputmode="tel" placeholder="08xxxxxxxxxx"
                           value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="3" placeholder="Masukkan alamat lengkap pasien"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
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
