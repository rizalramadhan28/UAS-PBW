<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;

$batas   = 10;
$halaman = isset($_GET['hal']) ? max(1, (int)$_GET['hal']) : 1;
$awal    = ($halaman - 1) * $batas;
$cari    = isset($_GET['cari']) ? trim($_GET['cari']) : '';

if ($cari !== '') {
    $like = '%' . $cari . '%';
    $stmt = mysqli_prepare(
        $conn,
        "SELECT d.*, o.nama_obat, o.satuan, k.nomor_antrian, k.tanggal,
                p.nama_pasien
         FROM detail_kunjungan d
         JOIN obat o      ON o.id_obat      = d.id_obat
         JOIN kunjungan k ON k.id_kunjungan = d.id_kunjungan
         JOIN pasien p    ON p.id_pasien    = k.id_pasien
         WHERE o.nama_obat LIKE ? OR p.nama_pasien LIKE ? OR k.nomor_antrian LIKE ?
         ORDER BY d.id_detail DESC LIMIT ?, ?"
    );
    mysqli_stmt_bind_param($stmt, 'sssii', $like, $like, $like, $awal, $batas);

    $stmtC = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total
         FROM detail_kunjungan d
         JOIN obat o      ON o.id_obat      = d.id_obat
         JOIN kunjungan k ON k.id_kunjungan = d.id_kunjungan
         JOIN pasien p    ON p.id_pasien    = k.id_pasien
         WHERE o.nama_obat LIKE ? OR p.nama_pasien LIKE ? OR k.nomor_antrian LIKE ?"
    );
    mysqli_stmt_bind_param($stmtC, 'sss', $like, $like, $like);
} else {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT d.*, o.nama_obat, o.satuan, k.nomor_antrian, k.tanggal,
                p.nama_pasien
         FROM detail_kunjungan d
         JOIN obat o      ON o.id_obat      = d.id_obat
         JOIN kunjungan k ON k.id_kunjungan = d.id_kunjungan
         JOIN pasien p    ON p.id_pasien    = k.id_pasien
         ORDER BY d.id_detail DESC LIMIT ?, ?"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $awal, $batas);
    $stmtC = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM detail_kunjungan");
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

mysqli_stmt_execute($stmtC);
$total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtC))['total'];
$total_hal = max(1, (int) ceil($total / $batas));

function fmt_tgl($d) {
    if (!$d || $d === '0000-00-00') return '-';
    $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($d);
    return date('d', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y', $t);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resep Obat — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Resep Obat</h2>
            <a href="<?= $base ?>/detail_kunjungan/tambah_detail.php" class="btn btn-primary">+ Tambah Resep</a>
        </div>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <form method="GET" class="search-bar">
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari obat, pasien, atau no. antrian...">
            <button type="submit" class="btn btn-outline">Cari</button>
            <?php if ($cari !== ''): ?>
                <a href="<?= $base ?>/detail_kunjungan/data_detail.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Antrian</th>
                        <th>Tanggal</th>
                        <th>Pasien</th>
                        <th>Obat</th>
                        <th>Jumlah</th>
                        <th>Aturan Pakai</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = $awal + 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row['nomor_antrian'] ?? '-') ?></strong></td>
                        <td><?= fmt_tgl($row['tanggal']) ?></td>
                        <td><?= htmlspecialchars($row['nama_pasien']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_obat']) ?></strong>
                            <?php if (!empty($row['satuan'])): ?>
                                <br><span style="font-size:12px;color:var(--text-secondary)"><?= htmlspecialchars($row['satuan']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge"><?= (int)$row['jumlah_obat'] ?></span></td>
                        <td><?= htmlspecialchars($row['aturan_pakai'] ?? '-') ?></td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a href="<?= $base ?>/detail_kunjungan/edit_detail.php?id=<?= (int)$row['id_detail'] ?>"
                               class="btn btn-outline" style="padding:6px 12px;font-size:12px;">Edit</a>
                            <a href="<?= $base ?>/detail_kunjungan/hapus_detail.php?id=<?= (int)$row['id_detail'] ?>"
                               class="btn btn-danger" style="padding:6px 12px;font-size:12px;"
                               onclick="return confirm('Yakin ingin menghapus resep ini? Stok obat akan dikembalikan.')">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;color:var(--text-secondary);padding:30px;">
                            <?= $cari !== '' ? 'Tidak ada resep yang cocok dengan pencarian.' : 'Belum ada data resep.' ?>
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
