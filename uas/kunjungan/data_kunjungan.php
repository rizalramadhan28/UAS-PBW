<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;

// ==== Update status (POST) ====
if (isset($_POST['ubah_status']) && isset($_POST['id_kunjungan'])) {
    $idK = (int)$_POST['id_kunjungan'];
    $st  = $_POST['status'] ?? '';

    // Ambil status saat ini dulu untuk validasi alur
    $stmt = mysqli_prepare($conn, "SELECT status FROM kunjungan WHERE id_kunjungan = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idK);
    mysqli_stmt_execute($stmt);
    $cur = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $statusSekarang = $cur['status'] ?? '';

    // Aturan alur: Menunggu → Diproses → Selesai (harus maju selangkah, tidak boleh mundur, tidak bisa ubah lagi kalau sudah Selesai)
    $alur = ['Menunggu' => 0, 'Diproses' => 1, 'Selesai' => 2];
    $bolehUbah = (
        $idK > 0
        && in_array($st, ['Menunggu', 'Diproses', 'Selesai'], true)
        && $statusSekarang !== 'Selesai'                          // sudah selesai → kunci
        && isset($alur[$statusSekarang], $alur[$st])
        && $alur[$st] === $alur[$statusSekarang] + 1              // harus maju TEPAT 1 langkah
    );

    if ($bolehUbah) {
        $stmt = mysqli_prepare($conn, "UPDATE kunjungan SET status = ? WHERE id_kunjungan = ?");
        mysqli_stmt_bind_param($stmt, 'si', $st, $idK);
        mysqli_stmt_execute($stmt);
    }
    header('Location: ' . $base . '/kunjungan/data_kunjungan.php');
    exit();
}

// ==== Pagination & search ====
$batas   = 10;
$halaman = isset($_GET['hal']) ? max(1, (int)$_GET['hal']) : 1;
$awal    = ($halaman - 1) * $batas;
$cari    = isset($_GET['cari']) ? trim($_GET['cari']) : '';
$status  = isset($_GET['status']) ? trim($_GET['status']) : '';

$where = [];
$types = '';
$params = [];

if ($cari !== '') {
    $where[] = "(p.nama_pasien LIKE ? OR d.nama_dokter LIKE ? OR po.nama_poli LIKE ? OR k.nomor_antrian LIKE ?)";
    $like = '%' . $cari . '%';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($status, ['Menunggu', 'Diproses', 'Selesai'], true)) {
    $where[] = "k.status = ?";
    $types .= 's';
    $params[] = $status;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT k.*, p.nama_pasien, d.nama_dokter, po.nama_poli
        FROM kunjungan k
        JOIN pasien p ON p.id_pasien = k.id_pasien
        JOIN dokter d ON d.id_dokter = k.id_dokter
        JOIN poli po  ON po.id_poli  = k.id_poli
        $whereSql
        ORDER BY k.tanggal DESC, k.id_kunjungan DESC
        LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $sql);
$typesAll = $types . 'ii';
$paramsAll = array_merge($params, [$awal, $batas]);
mysqli_stmt_bind_param($stmt, $typesAll, ...$paramsAll);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Count
$sqlCount = "SELECT COUNT(*) AS total
             FROM kunjungan k
             JOIN pasien p ON p.id_pasien = k.id_pasien
             JOIN dokter d ON d.id_dokter = k.id_dokter
             JOIN poli po  ON po.id_poli  = k.id_poli
             $whereSql";
$stmtC = mysqli_prepare($conn, $sqlCount);
if ($types !== '') {
    mysqli_stmt_bind_param($stmtC, $types, ...$params);
}
mysqli_stmt_execute($stmtC);
$total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtC))['total'];
$total_hal = max(1, (int) ceil($total / $batas));

function fmt_tgl($d) {
    if (!$d || $d === '0000-00-00') return '-';
    $bln = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($d);
    return date('d', $t) . ' ' . $bln[(int)date('n', $t)] . ' ' . date('Y', $t);
}
function status_badge($st) {
    if ($st === 'Selesai')  return 'badge-success';
    if ($st === 'Diproses') return 'badge-warning';
    return ''; // Menunggu = badge default
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kunjungan — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Manajemen Kunjungan</h2>
        </div>

        <form method="GET" class="search-bar">
            <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>"
                   placeholder="Cari pasien, dokter, poli, atau no. antrian...">
            <select name="status" style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;background:#fff;">
                <option value="">Semua Status</option>
                <option value="Menunggu" <?= $status === 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                <option value="Diproses" <?= $status === 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                <option value="Selesai"  <?= $status === 'Selesai'  ? 'selected' : '' ?>>Selesai</option>
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
            <?php if ($cari !== '' || $status !== ''): ?>
                <a href="<?= $base ?>/kunjungan/data_kunjungan.php" class="btn btn-outline">Reset</a>
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
                        <th>Poli</th>
                        <th>Dokter</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = $awal + 1; while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row['nomor_antrian'] ?? '-') ?></strong></td>
                        <td><?= fmt_tgl($row['tanggal']) ?></td>
                        <td><?= htmlspecialchars($row['nama_pasien']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($row['nama_poli']) ?></span></td>
                        <td><?= htmlspecialchars($row['nama_dokter']) ?></td>
                        <td><?= htmlspecialchars($row['metode_pembayaran'] ?? '-') ?></td>
                        <td>
                            <?php if ($row['status'] === 'Selesai'): ?>
                                <span class="badge badge-success">Selesai</span>
                            <?php else:
                                $alur = ['Menunggu' => 0, 'Diproses' => 1, 'Selesai' => 2];
                                $levelSkrg = $alur[$row['status']] ?? 0;
                            ?>
                                <form method="POST" style="display:inline-flex;gap:6px;align-items:center;margin:0;">
                                    <input type="hidden" name="id_kunjungan" value="<?= (int)$row['id_kunjungan'] ?>">
                                    <select name="status" style="padding:5px 8px;font-size:12px;border:1px solid var(--border);border-radius:6px;">
                                        <?php foreach ($alur as $opt => $level): ?>
                                            <?php
                                                // Tampilkan status sekarang (selected) dan satu status berikutnya saja.
                                                // Status sebelumnya tidak ditampilkan (tidak bisa mundur).
                                                if ($level < $levelSkrg) continue;
                                                if ($level > $levelSkrg + 1) continue;
                                            ?>
                                            <option value="<?= $opt ?>" <?= $opt === $row['status'] ? 'selected' : '' ?>>
                                                <?= $opt ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="ubah_status" class="btn btn-outline"
                                            style="padding:5px 10px;font-size:12px;">Ubah</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($total === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;color:var(--text-secondary);padding:30px;">
                            <?= ($cari !== '' || $status !== '') ? 'Tidak ada kunjungan yang cocok dengan filter.' : 'Belum ada data kunjungan.' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_hal > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_hal; $i++):
                    $qs = http_build_query(array_filter(['hal' => $i, 'cari' => $cari, 'status' => $status]));
                ?>
                    <?php if ($i === $halaman): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= $qs ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
