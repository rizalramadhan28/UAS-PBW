<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'pasien') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';

// Data pasien
$stmt = mysqli_prepare($conn, "SELECT * FROM pasien WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id_user']);
mysqli_stmt_execute($stmt);
$pasien = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$total_kunjungan = 0;
$kunjungan_terakhir = null;
if ($pasien) {
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS jml FROM kunjungan WHERE id_pasien = ?");
    mysqli_stmt_bind_param($stmt, 'i', $pasien['id_pasien']);
    mysqli_stmt_execute($stmt);
    $total_kunjungan = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['jml'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT k.*, d.nama_dokter, po.nama_poli
         FROM kunjungan k
         JOIN dokter d ON d.id_dokter = k.id_dokter
         JOIN poli po  ON po.id_poli  = k.id_poli
         WHERE k.id_pasien = ?
         ORDER BY k.tanggal DESC LIMIT 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $pasien['id_pasien']);
    mysqli_stmt_execute($stmt);
    $kunjungan_terakhir = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

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
    <title>Dashboard Pasien — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include 'navbar_pasien.php'; ?>

<div class="container">
    <h1 class="page-title">
        Selamat datang<?= $pasien ? ', ' . htmlspecialchars($pasien['nama_pasien']) : '' ?>
    </h1>
    <p class="page-subtitle">
        <?= $pasien ? 'Berikut ringkasan akun dan layanan Anda.' : 'Lengkapi profil Anda terlebih dahulu untuk dapat mendaftar kunjungan.' ?>
    </p>

    <?php if (!$pasien): ?>
        <div class="alert alert-error">
            Profil pasien Anda belum lengkap. Silakan
            <a href="<?= $base ?>/pasien/tambah_pasien.php" style="color:var(--primary);font-weight:600;">isi data diri</a>
            terlebih dahulu.
        </div>
    <?php else: ?>
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-label">Total Kunjungan</div>
                <div class="stat-value"><?= $total_kunjungan ?></div>
            </div>
            <div class="stat-card accent">
                <div class="stat-icon">🆔</div>
                <div class="stat-label">No. BPJS</div>
                <div class="stat-value" style="font-size:1.1rem;">
                    <?= htmlspecialchars($pasien['no_bpjs'] ?: '—') ?>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">📆</div>
                <div class="stat-label">Kunjungan Terakhir</div>
                <div class="stat-value" style="font-size:1.1rem;">
                    <?= $kunjungan_terakhir ? fmt_tgl($kunjungan_terakhir['tanggal']) : '—' ?>
                </div>
            </div>
        </div>

        <?php if ($kunjungan_terakhir): ?>
            <div class="card">
                <div class="card-header"><h2>Kunjungan Terakhir</h2></div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
                    <div>
                        <div style="font-size:12px;color:var(--text-secondary);text-transform:uppercase;font-weight:600;">No. Antrian</div>
                        <div style="font-size:1.25rem;font-weight:700;color:var(--primary);">
                            <?= htmlspecialchars($kunjungan_terakhir['nomor_antrian'] ?? '-') ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:var(--text-secondary);text-transform:uppercase;font-weight:600;">Poli</div>
                        <div style="font-size:1rem;"><?= htmlspecialchars($kunjungan_terakhir['nama_poli']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:var(--text-secondary);text-transform:uppercase;font-weight:600;">Dokter</div>
                        <div style="font-size:1rem;"><?= htmlspecialchars($kunjungan_terakhir['nama_dokter']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:var(--text-secondary);text-transform:uppercase;font-weight:600;">Status</div>
                        <div>
                            <?php
                                $st = $kunjungan_terakhir['status'] ?? 'Menunggu';
                                $cls = $st === 'Selesai' ? 'badge-success' : ($st === 'Diproses' ? 'badge-warning' : 'badge');
                            ?>
                            <span class="badge <?= $cls ?>"><?= htmlspecialchars($st) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- AKSI CEPAT -->
    <div class="card">
        <div class="card-header"><h2>Layanan</h2></div>
        <div class="action-grid">
            <a href="<?= $base ?>/pasien/tambah_pasien.php" class="action-card">
                <div class="action-icon">👤</div>
                <h3>Profil Saya</h3>
                <p>Lengkapi atau perbarui data diri</p>
            </a>
            <a href="<?= $base ?>/kunjungan/tambah_kunjungan.php" class="action-card">
                <div class="action-icon">📝</div>
                <h3>Daftar Kunjungan</h3>
                <p>Daftar konsultasi dengan dokter</p>
            </a>
            <a href="<?= $base ?>/users/logout.php" class="action-card">
                <div class="action-icon">⏻</div>
                <h3>Logout</h3>
                <p>Keluar dari akun</p>
            </a>
        </div>
    </div>
</div>

</body>
</html>
