<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;

$query = mysqli_query($conn, "SELECT * FROM dokter WHERE aktif = 1 ORDER BY id_dokter DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dokter — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Manajemen Dokter</h2>
            <a href="<?= $base ?>/dokter/tambah_dokter.php" class="btn btn-primary">+ Tambah Dokter</a>
        </div>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Dokter</th>
                        <th>NIP</th>
                        <th>SIP</th>
                        <th>Poli (Spesialis)</th>
                        <th>No. HP</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; while ($data = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($data['nama_dokter']) ?></strong></td>
                        <td><?= htmlspecialchars($data['nip'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($data['sip'] ?? '-') ?></td>
                        <td><span class="badge"><?= htmlspecialchars($data['spesialis']) ?></span></td>
                        <td><?= htmlspecialchars($data['no_hp'] ?? '-') ?></td>
                        <td style="text-align:right; white-space:nowrap;">
                            <a href="<?= $base ?>/dokter/edit_dokter.php?id=<?= (int)$data['id_dokter'] ?>"
                               class="btn btn-outline" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <a href="<?= $base ?>/dokter/hapus_dokter.php?id=<?= (int)$data['id_dokter'] ?>"
                               class="btn btn-danger" style="padding:6px 12px;font-size:12px;"
                               onclick="return confirm('Yakin ingin menghapus dokter ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($query) === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--text-secondary);padding:30px;">
                            Belum ada data dokter.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
