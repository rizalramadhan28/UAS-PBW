<?php
session_start();

// Proteksi halaman: harus login sebagai admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';

$query = mysqli_query($conn, "SELECT * FROM dokter ORDER BY id_dokter DESC");
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

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Profil</th>
                        <th>Nama &amp; NIP</th>
                        <th>SIP &amp; Spesialis</th>
                        <th>Kontak</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($data = mysqli_fetch_assoc($query)): ?>
                    <?php
                    $foto_url = null;
                    if (!empty($data['foto'])) {
                        $foto_fs = __DIR__ . '/../uploads/' . $data['foto'];
                        if (file_exists($foto_fs)) {
                            $foto_url = $base . '/uploads/' . rawurlencode($data['foto']);
                        }
                    }
                    if (!$foto_url) {
                        $nama_clean = preg_replace('/(dr\.|drg\.|Sp\.[A-Z]+|,)/', '', $data['nama_dokter']);
                        $foto_url = 'https://ui-avatars.com/api/?name=' . urlencode(trim($nama_clean)) . '&background=0d7a54&color=fff&bold=true';
                    }
                    ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($foto_url) ?>" class="foto-profil" alt="foto">
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($data['nama_dokter']) ?></strong><br>
                            <span style="font-size:12px;color:var(--text-secondary)">
                                NIP: <?= htmlspecialchars($data['nip'] ?? '-') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge"><?= htmlspecialchars($data['spesialis']) ?></span><br>
                            <span style="font-size:12px;color:var(--text-secondary);margin-top:4px;display:inline-block">
                                SIP: <?= htmlspecialchars($data['sip'] ?? '-') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($data['no_hp'] ?? '-') ?></td>
                        <td style="text-align: right;">
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
                        <td colspan="5" style="text-align:center;color:var(--text-secondary);padding:30px;">
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
