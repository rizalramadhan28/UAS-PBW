<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;
$error = '';

$jenis_list  = ['Tablet', 'Kapsul', 'Sirup', 'Salep', 'Injeksi', 'Tetes', 'Suppositoria', 'Inhaler', 'Cairan', 'Serbuk'];
$satuan_list = ['Tablet', 'Kapsul', 'Botol', 'Tube', 'Ampul', 'Sachet', 'Strip', 'Box'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $base . '/obat/data_obat.php');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT * FROM obat WHERE id_obat = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    header('Location: ' . $base . '/obat/data_obat.php');
    exit();
}

if (isset($_POST['update'])) {
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
            "UPDATE obat
             SET nama_obat=?, jenis_obat=?, stok=?, satuan=?, harga=?, tanggal_expired=?
             WHERE id_obat=?"
        );
        mysqli_stmt_bind_param($stmt, 'ssisdsi', $nama, $jenis, $stok, $satuan, $harga, $expired_db, $id);
        if (mysqli_stmt_execute($stmt)) {
            header('Location: ' . $base . '/obat/data_obat.php');
            exit();
        }
        $error = 'Gagal memperbarui: ' . mysqli_error($conn);
    }

    // refresh dengan data form jika error
    $row['nama_obat']       = $nama;
    $row['jenis_obat']      = $jenis;
    $row['stok']            = $stok;
    $row['satuan']          = $satuan;
    $row['harga']           = $harga;
    $row['tanggal_expired'] = $expired;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Obat — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Edit Data Obat</h2>
            <a href="<?= $base ?>/obat/data_obat.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Obat</label>
                    <input type="text" name="nama_obat" required
                           value="<?= htmlspecialchars($row['nama_obat']) ?>">
                </div>

                <div class="form-group">
                    <label>Jenis Obat</label>
                    <select name="jenis_obat" required>
                        <option value="">— Pilih jenis obat —</option>
                        <?php foreach ($jenis_list as $j): ?>
                            <option value="<?= $j ?>" <?= $row['jenis_obat'] === $j ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                        <?php if ($row['jenis_obat'] && !in_array($row['jenis_obat'], $jenis_list, true)): ?>
                            <option value="<?= htmlspecialchars($row['jenis_obat']) ?>" selected>
                                <?= htmlspecialchars($row['jenis_obat']) ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Stok</label>
                    <div class="stepper">
                        <button type="button" onclick="stepStok(-1)" aria-label="Kurangi stok">−</button>
                        <input type="number" id="stok" name="stok" min="0" value="<?= (int)$row['stok'] ?>" required>
                        <button type="button" onclick="stepStok(1)" aria-label="Tambah stok">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Satuan</label>
                    <select name="satuan">
                        <option value="">— Pilih satuan —</option>
                        <?php foreach ($satuan_list as $s): ?>
                            <option value="<?= $s ?>" <?= $row['satuan'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                        <?php if ($row['satuan'] && !in_array($row['satuan'], $satuan_list, true)): ?>
                            <option value="<?= htmlspecialchars($row['satuan']) ?>" selected>
                                <?= htmlspecialchars($row['satuan']) ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Harga</label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input type="number" name="harga" min="0" step="100"
                               value="<?= number_format((float)$row['harga'], 0, '.', '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tanggal Expired</label>
                    <input type="date" name="tanggal_expired"
                           value="<?= htmlspecialchars($row['tanggal_expired'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
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
