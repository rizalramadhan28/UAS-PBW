<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;
$error = '';

// Ambil id dokter dari URL
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $base . '/dokter/data_dokter.php');
    exit();
}

// Ambil data dokter yang akan diedit
$stmt = mysqli_prepare($conn, "SELECT * FROM dokter WHERE id_dokter = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    header('Location: ' . $base . '/dokter/data_dokter.php');
    exit();
}

// Ambil daftar poli untuk dropdown spesialis
$list_poli = mysqli_query($conn, "SELECT nama_poli FROM poli ORDER BY nama_poli ASC");

if (isset($_POST['update'])) {
    $nama      = trim($_POST['nama_dokter'] ?? '');
    $nip       = trim($_POST['nip'] ?? '');
    $sip       = trim($_POST['sip'] ?? '');
    $spesialis = trim($_POST['spesialis'] ?? '');
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    if ($nama === '' || $nip === '' || $sip === '' || $spesialis === '') {
        $error = 'Nama, NIP, SIP, dan Poli wajib diisi.';
    } else {
        // Cek NIP duplikat di dokter aktif LAIN (selain yang sedang diedit)
        $cek = mysqli_prepare($conn, "SELECT id_dokter FROM dokter WHERE nip = ? AND aktif = 1 AND id_dokter <> ?");
        mysqli_stmt_bind_param($cek, 'si', $nip, $id);
        mysqli_stmt_execute($cek);
        $ada_nip = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

        // Cek SIP duplikat di dokter aktif LAIN
        $cek = mysqli_prepare($conn, "SELECT id_dokter FROM dokter WHERE sip = ? AND aktif = 1 AND id_dokter <> ?");
        mysqli_stmt_bind_param($cek, 'si', $sip, $id);
        mysqli_stmt_execute($cek);
        $ada_sip = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

        if ($ada_nip) {
            $error = 'NIP "' . htmlspecialchars($nip) . '" sudah dipakai dokter lain.';
        } elseif ($ada_sip) {
            $error = 'SIP "' . htmlspecialchars($sip) . '" sudah dipakai dokter lain.';
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE dokter
                 SET nama_dokter = ?, nip = ?, sip = ?, spesialis = ?, no_hp = ?, alamat = ?
                 WHERE id_dokter = ?"
            );
            mysqli_stmt_bind_param($stmt, 'ssssssi', $nama, $nip, $sip, $spesialis, $no_hp, $alamat, $id);

            if (mysqli_stmt_execute($stmt)) {
                header('Location: ' . $base . '/dokter/data_dokter.php');
                exit();
            } else {
                $error = 'Gagal memperbarui: ' . mysqli_error($conn);
            }
        }

        // Kalau error, isi form dengan data yang baru diketik user
        $row['nama_dokter'] = $nama;
        $row['nip']         = $nip;
        $row['sip']         = $sip;
        $row['spesialis']   = $spesialis;
        $row['no_hp']       = $no_hp;
        $row['alamat']      = $alamat;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Dokter — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Edit Data Dokter</h2>
            <a href="<?= $base ?>/dokter/data_dokter.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap (beserta gelar)</label>
                    <input type="text" name="nama_dokter" required
                           value="<?= htmlspecialchars($row['nama_dokter']) ?>">
                </div>

                <div class="form-group">
                    <label>Poli (Spesialis)</label>
                    <select name="spesialis" required>
                        <option value="">— Pilih poli —</option>
                        <?php while ($p = mysqli_fetch_assoc($list_poli)): ?>
                            <option value="<?= htmlspecialchars($p['nama_poli']) ?>"
                                <?= $row['spesialis'] === $p['nama_poli'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama_poli']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>NIP (harus unik)</label>
                    <input type="text" name="nip" required
                           value="<?= htmlspecialchars($row['nip'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>SIP (harus unik)</label>
                    <input type="text" name="sip" required
                           value="<?= htmlspecialchars($row['sip'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="text" name="no_hp"
                           value="<?= htmlspecialchars($row['no_hp'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Alamat</label>
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
