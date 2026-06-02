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
    header('Location: ' . $base . '/dokter/data_dokter.php');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM dokter WHERE id_dokter = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header('Location: ' . $base . '/dokter/data_dokter.php');
    exit();
}

if (isset($_POST['update'])) {
    $nama      = trim($_POST['nama_dokter'] ?? '');
    $nip       = trim($_POST['nip'] ?? '');
    $sip       = trim($_POST['sip'] ?? '');
    $spesialis = trim($_POST['spesialis'] ?? '');
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    $nama_foto_baru = $data['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $error = 'Format foto harus JPG, PNG, GIF, atau WEBP.';
        } else {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $nama_foto_baru = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['foto']['name']);
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $nama_foto_baru)) {
                if (!empty($data['foto']) && file_exists($upload_dir . $data['foto'])) {
                    @unlink($upload_dir . $data['foto']);
                }
            } else {
                $error = 'Gagal mengunggah foto.';
                $nama_foto_baru = $data['foto'];
            }
        }
    }

    if (!$error) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE dokter
             SET nama_dokter=?, nip=?, sip=?, spesialis=?, no_hp=?, alamat=?, foto=?
             WHERE id_dokter=?"
        );
        mysqli_stmt_bind_param($stmt, 'sssssssi', $nama, $nip, $sip, $spesialis, $no_hp, $alamat, $nama_foto_baru, $id);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/dokter/data_dokter.php');
            exit();
        }
        $error = 'Gagal memperbarui data: ' . mysqli_error($conn);
    }

    // refresh data on error
    $data['nama_dokter'] = $nama;
    $data['nip']         = $nip;
    $data['sip']         = $sip;
    $data['spesialis']   = $spesialis;
    $data['no_hp']       = $no_hp;
    $data['alamat']      = $alamat;
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

        <?php if (!empty($data['foto']) && file_exists(__DIR__ . '/../uploads/' . $data['foto'])): ?>
            <div style="margin-bottom:16px;">
                <label style="font-size:13px;font-weight:600;color:var(--text-secondary);">Foto saat ini:</label><br>
                <img src="<?= $base ?>/uploads/<?= rawurlencode($data['foto']) ?>"
                     class="foto-profil" style="width:80px;height:80px;margin-top:6px;" alt="foto">
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap (beserta gelar)</label>
                    <input type="text" name="nama_dokter" value="<?= htmlspecialchars($data['nama_dokter']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Spesialis / Poli</label>
                    <input type="text" name="spesialis" value="<?= htmlspecialchars($data['spesialis']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Nomor Induk Pegawai (NIP)</label>
                    <input type="text" name="nip" value="<?= htmlspecialchars($data['nip'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Surat Izin Praktik (SIP)</label>
                    <input type="text" name="sip" value="<?= htmlspecialchars($data['sip'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Nomor HP / WhatsApp</label>
                    <input type="text" name="no_hp" value="<?= htmlspecialchars($data['no_hp'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Foto Baru (abaikan jika tidak ingin ganti)</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Alamat Lengkap</label>
                <textarea name="alamat" rows="3"><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
