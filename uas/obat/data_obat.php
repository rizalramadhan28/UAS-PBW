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
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM obat WHERE nama_obat LIKE ? OR jenis_obat LIKE ?
         ORDER BY id_obat DESC LIMIT ?, ?"
    );
    $like = '%' . $cari . '%';
    mysqli_stmt_bind_param($stmt, 'ssii', $like, $like, $awal, $batas);

    $stmtCount = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total FROM obat WHERE nama_obat LIKE ? OR jenis_obat LIKE ?"
    );
    mysqli_stmt_bind_param($stmtCount, 'ss', $like, $like);
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM obat ORDER BY id_obat DESC LIMIT ?, ?");
    mysqli_stmt_bind_param($stmt, 'ii', $awal, $batas);

    $stmtCount = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM obat");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

mysqli_stmt_execute($stmtCount);
$total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCount))['total'];
$total_hal = max(1, (int) ceil($total / $batas));

// helper
function fmt_rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function fmt_tanggal($d) {
    if (!$d || $d === '0000-00-00') return '-';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($d);
    return date('d', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Obat — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Manajemen Obat</h2>
            <a href="<?= $base ?>/obat/tambah_obat.php" class="btn btn-primary">+ Tambah Obat</a>
        </div>

        <form method="GET" class="search-bar">
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari nama atau jenis obat...">
            <button type="submit" class="btn btn-outline">Cari</button>
            <?php if ($cari !== ''): ?>
                <a href="<?= $base ?>/obat/data_obat.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Obat</th>
                        <th>Jenis</th>
                        <th>Stok</th>
                        <th>Harga</th>
                        <th>Tanggal Expired</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = $awal + 1;
                while ($row = mysqli_fetch_assoc($result)):
                    // Status stok
                    $stok = (int)$row['stok'];
                    if ($stok === 0)       { $stokBadge = 'badge-danger';  $stokLabel = 'Habis'; }
                    elseif ($stok <= 10)   { $stokBadge = 'badge-warning'; $stokLabel = 'Menipis'; }
                    else                   { $stokBadge = 'badge-success'; $stokLabel = 'Tersedia'; }

                    // Status expired
                    $expBadge = '';
                    $expLabel = '';
                    if (!empty($row['tanggal_expired']) && $row['tanggal_expired'] !== '0000-00-00') {
                        $diffDays = (strtotime($row['tanggal_expired']) - time()) / 86400;
                        if ($diffDays < 0)        { $expBadge = 'badge-danger';  $expLabel = 'Kadaluarsa'; }
                        elseif ($diffDays <= 30)  { $expBadge = 'badge-warning'; $expLabel = 'Segera Expired'; }
                    }
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_obat']) ?></strong>
                            <?php if (!empty($row['satuan'])): ?>
                                <br><span style="font-size:12px;color:var(--text-secondary)">per <?= htmlspecialchars($row['satuan']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge"><?= htmlspecialchars($row['jenis_obat'] ?? '-') ?></span></td>
                        <td>
                            <strong><?= $stok ?></strong>
                            <span class="badge <?= $stokBadge ?>" style="margin-left:6px;"><?= $stokLabel ?></span>
                        </td>
                        <td><strong><?= fmt_rupiah($row['harga']) ?></strong></td>
                        <td>
                            <?= fmt_tanggal($row['tanggal_expired']) ?>
                            <?php if ($expLabel): ?>
                                <br><span class="badge <?= $expBadge ?>" style="margin-top:4px;"><?= $expLabel ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a href="<?= $base ?>/obat/edit_obat.php?id=<?= (int)$row['id_obat'] ?>"
                               class="btn btn-outline" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <a href="<?= $base ?>/obat/hapus_obat.php?id=<?= (int)$row['id_obat'] ?>"
                               class="btn btn-danger" style="padding:6px 12px;font-size:12px;"
                               onclick="return confirm('Yakin ingin menghapus obat ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total === 0): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--text-secondary);padding:30px;">
                            <?= $cari !== '' ? 'Tidak ada obat yang cocok dengan pencarian.' : 'Belum ada data obat.' ?>
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
