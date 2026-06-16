<?php
session_start();

include dirname(__DIR__) . '/config/koneksi.php';

$base = BASE_URL;
$error = '';

// Proteksi: hanya pasien yang sudah login
if (!isset($_SESSION['login']) || ($_SESSION['role'] ?? '') !== 'pasien') {
    header('Location: ' . $base . '/users/login.php');
    exit();
}

// 1. Ambil data pasien yang sedang login saat ini
$id_user = (int) ($_SESSION['id_user'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM pasien WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$pasien = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$pasien) {
    header('Location: ' . $base . '/pasien/tambah_pasien.php');
    exit();
}

// 2. Cek apakah user sudah memilih Poli (untuk memicu list dokter)
$id_poli_terpilih = $_POST['id_poli'] ?? '';

// 3. Proses Simpan Kunjungan saat tombol "Daftar Kunjungan" ditekan
if (isset($_POST['daftar'])) {
    $id_pasien = (int) $pasien['id_pasien'];
    $id_poli   = (int) ($_POST['id_poli'] ?? 0);
    $id_dokter = (int) ($_POST['id_dokter'] ?? 0);
    $tanggal   = $_POST['tanggal_kunjungan'] ?? '';
    $metode    = $_POST['metode_pembayaran'] ?? '';
    $keluhan   = trim($_POST['keluhan'] ?? '');

    if ($id_poli <= 0 || $id_dokter <= 0 || $tanggal === '' || $metode === '') {
        $error = 'Semua form wajib diisi kecuali keluhan.';
    } elseif (!in_array($metode, ['BPJS', 'Umum', 'Asuransi'], true)) {
        $error = 'Metode pembayaran tidak valid.';
    } else {
        // Generate nomor antrian (konsisten dengan halaman admin)
        $nomor_antrian = 'A' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO kunjungan (id_pasien, id_poli, id_dokter, tanggal, metode_pembayaran, keluhan, nomor_antrian, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'Menunggu')"
        );
        mysqli_stmt_bind_param(
            $stmt, 'iiissss',
            $id_pasien, $id_poli, $id_dokter, $tanggal, $metode, $keluhan, $nomor_antrian
        );
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/users/dashboard_pasien.php');
            exit();
        }
        $error = 'Gagal mendaftar kunjungan: ' . mysqli_error($conn);
    }
}

// 4. Ambil semua data poli untuk dropdown pertama
$list_poli = mysqli_query($conn, "SELECT id_poli, nama_poli FROM poli ORDER BY nama_poli ASC");

// 5. Ambil dokter berdasarkan poli yang dipilih.
//    Tabel `dokter` tidak punya kolom id_poli → kita JOIN lewat nama poli (kolom `spesialis`).
$list_dokter = [];
if ($id_poli_terpilih !== '') {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT d.id_dokter, d.nama_dokter
         FROM dokter d
         JOIN poli p ON p.nama_poli = d.spesialis
         WHERE p.id_poli = ? AND d.aktif = 1
         ORDER BY d.nama_dokter ASC"
    );
    $idp = (int) $id_poli_terpilih;
    mysqli_stmt_bind_param($stmt, 'i', $idp);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $list_dokter[] = $row;
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

<?php include '../users/navbar_pasien.php'; ?>

<div class="container" style="max-width:800px; margin-top:30px;">
    <div class="card">
        <div class="card-header">
            <h2>Pendaftaran Kunjungan</h2>
            <a href="<?= $base ?>/users/dashboard_pasien.php" class="btn btn-outline">Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="formKunjungan" style="margin-top:20px;">
            <p style="margin-bottom:15px; color:#555;">
                Mendaftar atas nama: <strong><?= htmlspecialchars($pasien['nama_pasien']) ?></strong>
            </p>

            <div class="form-grid">
                <div class="form-group">
                    <label>Poli Tujuan</label>
                    <select name="id_poli" onchange="this.form.submit()" required>
                        <option value="">— Pilih poli —</option>
                        <?php while ($p = mysqli_fetch_assoc($list_poli)): ?>
                            <option value="<?= (int) $p['id_poli'] ?>"
                                <?= (string) $id_poli_terpilih === (string) $p['id_poli'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama_poli']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Dokter</label>
                    <select name="id_dokter" required <?= $id_poli_terpilih === '' ? 'disabled' : '' ?>>
                        <?php if ($id_poli_terpilih === ''): ?>
                            <option value="">— Pilih poli terlebih dahulu —</option>
                        <?php else: ?>
                            <option value="">— Pilih dokter —</option>
                            <?php if (count($list_dokter) > 0): ?>
                                <?php foreach ($list_dokter as $dr): ?>
                                    <option value="<?= (int) $dr['id_dokter'] ?>"
                                        <?= (isset($_POST['id_dokter']) && (string) $_POST['id_dokter'] === (string) $dr['id_dokter']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dr['nama_dokter']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada dokter di poli ini</option>
                            <?php endif; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tanggal Kunjungan</label>
                    <input type="date" name="tanggal_kunjungan" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['tanggal_kunjungan'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="metode_pembayaran" required>
                        <option value="">— Pilih metode —</option>
                        <option value="Umum"     <?= (($_POST['metode_pembayaran'] ?? '') === 'Umum')     ? 'selected' : '' ?>>Umum / Mandiri</option>
                        <option value="BPJS"     <?= (($_POST['metode_pembayaran'] ?? '') === 'BPJS')     ? 'selected' : '' ?>>BPJS Kesehatan</option>
                        <option value="Asuransi" <?= (($_POST['metode_pembayaran'] ?? '') === 'Asuransi') ? 'selected' : '' ?>>Asuransi</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Keluhan (Opsional)</label>
                <textarea name="keluhan" rows="3"
                          placeholder="Tuliskan keluhan atau gejala yang Anda rasakan..."><?= htmlspecialchars($_POST['keluhan'] ?? '') ?></textarea>
            </div>

            <div style="margin-top:24px; display:flex; gap:10px;">
                <button type="submit" name="daftar" class="btn btn-primary">Daftar Kunjungan</button>
                <a href="<?= $base ?>/kunjungan/tambah_kunjungan.php" class="btn btn-outline"
                   style="text-decoration:none; text-align:center;">Reset</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
