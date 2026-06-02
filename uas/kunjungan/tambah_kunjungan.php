<?php
session_start();

include '../config/koneksi.php';

$base = '/uas';
$error = '';
$sukses = false;
$nomor_antrian = '';

$is_admin  = isset($_SESSION['login']) && ($_SESSION['role'] ?? '') === 'admin';
$is_pasien = isset($_SESSION['login']) && ($_SESSION['role'] ?? '') === 'pasien';

if (!$is_admin && !$is_pasien) {
    header('Location: ' . $base . '/users/login.php');
    exit();
}

// Untuk role pasien: ambil id_pasien dari session id_user
$pasien_session = null;
if ($is_pasien) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM pasien WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['id_user']);
    mysqli_stmt_execute($stmt);
    $pasien_session = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$pasien_session) {
        // Pasien belum lengkapi profil → arahkan ke form profil
        header('Location: ' . $base . '/pasien/tambah_pasien.php');
        exit();
    }
}

// Data dropdown
$poli_list   = mysqli_query($conn, "SELECT * FROM poli ORDER BY nama_poli");
$dokter_list = mysqli_query($conn, "SELECT * FROM dokter ORDER BY nama_dokter");
$pasien_list = $is_admin ? mysqli_query($conn, "SELECT id_pasien, nama_pasien FROM pasien ORDER BY nama_pasien") : null;

if (isset($_POST['simpan'])) {
    $id_pasien = $is_pasien
        ? (int) $pasien_session['id_pasien']
        : (int) ($_POST['id_pasien'] ?? 0);
    $id_dokter = (int) ($_POST['id_dokter'] ?? 0);
    $id_poli   = (int) ($_POST['id_poli'] ?? 0);
    $tanggal   = trim($_POST['tanggal'] ?? '');
    $keluhan   = trim($_POST['keluhan'] ?? '');
    $bayar     = $_POST['metode_pembayaran'] ?? '';

    if ($id_pasien <= 0 || $id_dokter <= 0 || $id_poli <= 0 || $tanggal === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!in_array($bayar, ['BPJS', 'Umum', 'Asuransi'], true)) {
        $error = 'Metode pembayaran tidak valid.';
    } else {
        $nomor_antrian = 'A' . str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO kunjungan (id_pasien, id_dokter, id_poli, tanggal, keluhan, metode_pembayaran, nomor_antrian, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu')"
        );
        mysqli_stmt_bind_param($stmt, 'iiissss', $id_pasien, $id_dokter, $id_poli, $tanggal, $keluhan, $bayar, $nomor_antrian);
        if (mysqli_stmt_execute($stmt)) {
            $sukses = true;
        } else {
            $error = 'Gagal menyimpan: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran Kunjungan — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include $is_admin ? '../users/navbar_admin.php' : '../users/navbar_pasien.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Pendaftaran Kunjungan</h2>
            <?php if ($is_admin): ?>
                <a href="<?= $base ?>/kunjungan/data_kunjungan.php" class="btn btn-outline">Lihat Daftar</a>
            <?php else: ?>
                <a href="<?= $base ?>/users/dashboard_pasien.php" class="btn btn-outline">Dashboard</a>
            <?php endif; ?>
        </div>

        <?php if ($sukses): ?>
            <div class="alert" style="background:rgba(46,204,113,0.12);border:1px solid #2ecc71;color:#1e8449;text-align:center;padding:24px;">
                <h3 style="margin:0 0 6px 0;color:#1e8449;">Pendaftaran Berhasil</h3>
                <p style="margin:0;font-size:13px;">Nomor antrian Anda:</p>
                <div style="font-size:36px;font-weight:700;letter-spacing:0.1em;color:var(--primary);margin-top:10px;">
                    <?= htmlspecialchars($nomor_antrian) ?>
                </div>
                <a href="<?= $base ?>/kunjungan/tambah_kunjungan.php" class="btn btn-primary" style="margin-top:14px;">Daftar Lagi</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($is_pasien && $pasien_session): ?>
                <p style="color:var(--text-secondary);margin-bottom:16px;">
                    Mendaftar atas nama: <strong><?= htmlspecialchars($pasien_session['nama_pasien']) ?></strong>
                </p>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <?php if ($is_admin): ?>
                        <div class="form-group">
                            <label>Pasien</label>
                            <select name="id_pasien" required>
                                <option value="">— Pilih pasien —</option>
                                <?php while ($p = mysqli_fetch_assoc($pasien_list)): ?>
                                    <option value="<?= (int)$p['id_pasien'] ?>"><?= htmlspecialchars($p['nama_pasien']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Poli</label>
                        <select name="id_poli" required>
                            <option value="">— Pilih poli —</option>
                            <?php while ($r = mysqli_fetch_assoc($poli_list)): ?>
                                <option value="<?= (int)$r['id_poli'] ?>"><?= htmlspecialchars($r['nama_poli']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Dokter</label>
                        <select name="id_dokter" required>
                            <option value="">— Pilih dokter —</option>
                            <?php while ($r = mysqli_fetch_assoc($dokter_list)): ?>
                                <option value="<?= (int)$r['id_dokter'] ?>"><?= htmlspecialchars($r['nama_dokter']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Kunjungan</label>
                        <input type="date" name="tanggal" required min="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($_POST['tanggal'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode_pembayaran" required>
                            <option value="">— Pilih metode —</option>
                            <option value="BPJS"     <?= ($_POST['metode_pembayaran'] ?? '') === 'BPJS'     ? 'selected' : '' ?>>BPJS</option>
                            <option value="Umum"     <?= ($_POST['metode_pembayaran'] ?? '') === 'Umum'     ? 'selected' : '' ?>>Umum</option>
                            <option value="Asuransi" <?= ($_POST['metode_pembayaran'] ?? '') === 'Asuransi' ? 'selected' : '' ?>>Asuransi</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label>Keluhan</label>
                    <textarea name="keluhan" rows="3" placeholder="Tuliskan keluhan Anda..."><?= htmlspecialchars($_POST['keluhan'] ?? '') ?></textarea>
                </div>

                <div style="margin-top:24px;display:flex;gap:10px;">
                    <button type="submit" name="simpan" class="btn btn-primary">Daftar Kunjungan</button>
                    <button type="reset" class="btn btn-outline">Reset</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
