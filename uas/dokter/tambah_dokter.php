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
    $nama      = trim($_POST['nama_dokter'] ?? '');
    $nip       = trim($_POST['nip'] ?? '');
    $sip       = trim($_POST['sip'] ?? '');
    $spesialis = trim($_POST['spesialis'] ?? '');
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    $nama_foto_baru = '';
    if (!empty($_FILES['foto']['name'])) {
        $tmp_foto = $_FILES['foto']['tmp_name'];
        $ext      = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $error = 'Format foto harus JPG, PNG, GIF, atau WEBP.';
        } else {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $nama_foto_baru = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['foto']['name']);
            if (!move_uploaded_file($tmp_foto, $upload_dir . $nama_foto_baru)) {
                $error = 'Gagal mengunggah foto.';
                $nama_foto_baru = '';
            }
        }
    }

    if (!$error) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO dokter (nama_dokter, nip, sip, spesialis, no_hp, alamat, foto)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'sssssss', $nama, $nip, $sip, $spesialis, $no_hp, $alamat, $nama_foto_baru);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/dokter/data_dokter.php');
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
    <title>Tambah Dokter — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Tambah Data Dokter</h2>
            <a href="<?= $base ?>/dokter/data_dokter.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap (beserta gelar)</label>
                    <input type="text" name="nama_dokter" required placeholder="Contoh: dr. Andi Saputra, Sp.PD">
                </div>
                <div class="form-group">
                    <label>Spesialis / Poli</label>
                    <input type="text" name="spesialis" required placeholder="Contoh: Poli Umum">
                </div>
                <div class="form-group">
                    <label>Nomor Induk Pegawai (NIP)</label>
                    <input type="text" name="nip" placeholder="Masukkan NIP">
                </div>
                <div class="form-group">
                    <label>Surat Izin Praktik (SIP)</label>
                    <input type="text" name="sip" placeholder="Masukkan Nomor SIP">
                </div>
                <div class="form-group">
                    <label>Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" placeholder="Contoh: 08123456789">
                </div>
                <div class="form-group">
                    <label>Foto Profil</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="3" placeholder="Masukkan alamat lengkap dokter"></textarea>
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
