<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;
$error = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $base . '/pasien/data_pasien.php');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM pasien WHERE id_pasien = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    header('Location: ' . $base . '/pasien/data_pasien.php');
    exit();
}

/**
 * Cek apakah nilai sudah dipakai pasien lain (selain record yang diedit).
 */
function pasien_field_exists($conn, $field, $value, $exceptId = 0) {
    $col = $field === 'nik' ? 'nik' : 'no_bpjs';
    $stmt = mysqli_prepare($conn, "SELECT id_pasien FROM pasien WHERE $col = ? AND id_pasien <> ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'si', $value, $exceptId);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) !== null;
}

if (isset($_POST['update'])) {
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
    } elseif ($nik !== '' && pasien_field_exists($conn, 'nik', $nik, $id)) {
        $error = 'NIK "' . htmlspecialchars($nik) . '" sudah terdaftar untuk pasien lain.';
    } elseif ($bpjs !== '' && pasien_field_exists($conn, 'no_bpjs', $bpjs, $id)) {
        $error = 'No. BPJS "' . htmlspecialchars($bpjs) . '" sudah terdaftar untuk pasien lain.';
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pasien
             SET nama_pasien=?, nik=?, no_bpjs=?, jenis_kelamin=?, alamat=?, no_hp=?
             WHERE id_pasien=?"
        );
        mysqli_stmt_bind_param($stmt, 'ssssssi', $nama, $nik, $bpjs, $jk, $alamat, $hp, $id);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/pasien/data_pasien.php');
            exit();
        }
        $error = 'Gagal memperbarui: ' . mysqli_error($conn);
    }

    // refresh value form jika error
    $row['nama_pasien']   = $nama;
    $row['nik']           = $nik;
    $row['no_bpjs']       = $bpjs;
    $row['jenis_kelamin'] = $jk;
    $row['alamat']        = $alamat;
    $row['no_hp']         = $hp;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pasien — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Edit Data Pasien</h2>
            <a href="<?= $base ?>/pasien/data_pasien.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Pasien</label>
                    <input type="text" name="nama_pasien" required
                           value="<?= htmlspecialchars($row['nama_pasien']) ?>">
                </div>

                <div class="form-group">
                    <label>NIK</label>
                    <input type="text" name="nik" inputmode="numeric" maxlength="20"
                           value="<?= htmlspecialchars($row['nik'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>No. BPJS</label>
                    <input type="text" name="no_bpjs" inputmode="numeric" maxlength="20"
                           value="<?= htmlspecialchars($row['no_bpjs'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jenis Kelamin</label>
                    <select name="jenis_kelamin" required>
                        <option value="">— Pilih —</option>
                        <option value="Laki-laki" <?= $row['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="Perempuan" <?= $row['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="text" name="no_hp" inputmode="tel" placeholder="08xxxxxxxxxx"
                           value="<?= htmlspecialchars($row['no_hp'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="3"><?= htmlspecialchars($row['alamat'] ?? '') ?></textarea>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
