<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';
$error = '';

// Daftar jenis obat
$jenis_list = ['Tablet', 'Kapsul', 'Sirup', 'Salep', 'Injeksi', 'Tetes', 'Suppositoria', 'Inhaler', 'Cairan', 'Serbuk'];
$satuan_list = ['Tablet', 'Kapsul', 'Botol', 'Tube', 'Ampul', 'Sachet', 'Strip', 'Box'];

if (isset($_POST['simpan'])) {
    $nama    = trim($_POST['nama_obat'] ?? '');
    $jenis   = trim($_POST['jenis_obat'] ?? '');
    $stok    = max(0, (int)($_POST['stok'] ?? 0));
    $satuan  = trim($_POST['satuan'] ?? '');
    $harga   = max(0, (float) preg_replace('/[^\d.]/', '', $_POST['harga'] ?? '0'));
    $expired = trim($_POST['tanggal_expired'] ?? '');

    if ($nama === '') {
        $error = 'Nama obat tidak boleh kosong.';
    } elseif ($jenis === '') {
        $error = 'Jenis obat harus dipilih.';
    } else {
        $expired_db = $expired !== '' ? $expired : null;
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO obat (nama_obat, jenis_obat, stok, satuan, harga, tanggal_expired)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, 'ssisds', $nama, $jenis, $stok, $satuan, $harga, $expired_db);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/obat/data_obat.php');
            exit();
        }
        $error = 'Gagal menyimpan: ' . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Obat — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Tambah Data Obat</h2>
            <a href="<?= $base ?>/obat/data_obat.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Obat</label>
                    <input type="text" name="nama_obat" required placeholder="Contoh: Paracetamol 500mg"
                           value="<?= htmlspecialchars($_POST['nama_obat'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Jenis Obat</label>
                    <select name="jenis_obat" required>
                        <option value="">— Pilih jenis obat —</option>
                        <?php foreach ($jenis_list as $j): ?>
                            <option value="<?= $j ?>" <?= ($_POST['jenis_obat'] ?? '') === $j ? 'selected' : '' ?>>
                                <?= $j ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Stok</label>
                    <div class="stepper">
                        <button type="button" onclick="stepStok(-1)" aria-label="Kurangi stok">−</button>
                        <input type="number" id="stok" name="stok" min="0" value="<?= (int)($_POST['stok'] ?? 0) ?>" required>
                        <button type="button" onclick="stepStok(1)" aria-label="Tambah stok">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Satuan</label>
                    <select name="satuan">
                        <option value="">— Pilih satuan —</option>
                        <?php foreach ($satuan_list as $s): ?>
                            <option value="<?= $s ?>" <?= ($_POST['satuan'] ?? '') === $s ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Harga</label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input type="number" name="harga" min="0" step="100"
                               value="<?= htmlspecialchars($_POST['harga'] ?? '0') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tanggal Expired</label>
                    <input type="date" name="tanggal_expired"
                           value="<?= htmlspecialchars($_POST['tanggal_expired'] ?? '') ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div style="margin-top:24px;display:flex;gap:10px;">
                <button type="submit" name="simpan" class="btn btn-primary">Simpan Data</button>
                <button type="reset" class="btn btn-outline">Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
function stepStok(delta) {
    const el = document.getElementById('stok');
    const v = parseInt(el.value || '0', 10) + delta;
    el.value = Math.max(0, v);
}
</script>

</body>
</html>
