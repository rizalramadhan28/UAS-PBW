<?php
session_start();

// Proteksi: harus login admin
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';

// ==== Pagination & search ====
$batas   = 8;
$halaman = isset($_GET['hal']) ? max(1, (int)$_GET['hal']) : 1;
$awal    = ($halaman - 1) * $batas;
$cari    = isset($_GET['cari']) ? trim($_GET['cari']) : '';

if ($cari !== '') {
    $like = '%' . $cari . '%';
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM pasien
         WHERE nama_pasien LIKE ? OR nik LIKE ? OR no_bpjs LIKE ?
         ORDER BY id_pasien DESC LIMIT ?, ?"
    );
    mysqli_stmt_bind_param($stmt, 'sssii', $like, $like, $like, $awal, $batas);

    $stmtCount = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM pasien
         WHERE nama_pasien LIKE ? OR nik LIKE ? OR no_bpjs LIKE ?"
    );
    mysqli_stmt_bind_param($stmtCount, 'sss', $like, $like, $like);
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM pasien ORDER BY id_pasien DESC LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt, 'ii', $awal, $batas);
    $stmtCount = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM pasien");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

mysqli_stmt_execute($stmtCount);
$total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];
$total_hal = max(1, (int) ceil($total / $batas));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Manajemen Pasien</h2>
            <a href="<?= $base ?>/pasien/tambah_pasien.php" class="btn btn-primary">+ Tambah Pasien</a>
        </div>

        <form method="GET" class="search-bar">
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari nama, NIK, atau No. BPJS...">
            <button type="submit" class="btn btn-outline">Cari</button>
            <?php if ($cari !== ''): ?>
                <a href="<?= $base ?>/pasien/data_pasien.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Profil</th>
                        <th>Nama &amp; NIK</th>
                        <th>No. BPJS</th>
                        <th>Jenis Kelamin</th>
                        <th>Kontak</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)):
                    // Avatar fallback
                    $foto_url = 'https://ui-avatars.com/api/?name=' . urlencode($row['nama_pasien'])
                              . '&background=0d7a54&color=fff&bold=true';
                    // Badge gender
                    $jk = $row['jenis_kelamin'] ?? '';
                    if ($jk === 'Laki-laki')      { $jkBadge = 'badge'; }
                    elseif ($jk === 'Perempuan')  { $jkBadge = 'badge-warning badge'; }
                    else                          { $jkBadge = 'badge'; $jk = '-'; }
                ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($foto_url) ?>" class="foto-profil" alt="avatar"></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_pasien']) ?></strong><br>
                            <span style="font-size:12px;color:var(--text-secondary)">
                                NIK: <?= htmlspecialchars($row['nik'] ?? '-') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['no_bpjs'] ?: '-') ?></td>
                        <td><span class="<?= $jkBadge ?>"><?= htmlspecialchars($jk) ?></span></td>
                        <td><?= htmlspecialchars($row['no_hp'] ?: '-') ?></td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a href="<?= $base ?>/pasien/edit_pasien.php?id=<?= (int)$row['id_pasien'] ?>"
                               class="btn btn-outline" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <a href="<?= $base ?>/pasien/hapus_pasien.php?id=<?= (int)$row['id_pasien'] ?>"
                               class="btn btn-danger" style="padding:6px 12px;font-size:12px;"
                               onclick="return confirm('Yakin ingin menghapus data pasien ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-secondary);padding:30px;">
                            <?= $cari !== '' ? 'Tidak ada pasien yang cocok dengan pencarian.' : 'Belum ada data pasien.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_hal > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_hal; $i++): ?>
                    <?php if ($i === $halaman): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?hal=<?= $i ?><?= $cari !== '' ? '&cari=' . urlencode($cari) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
