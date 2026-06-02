<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: /uas/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = '/uas';
$error = '';

// Daftar kunjungan dengan info pasien dan poli
$kunjungan_list = mysqli_query($conn, "
    SELECT k.id_kunjungan, k.nomor_antrian, k.tanggal,
           p.nama_pasien, po.nama_poli
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    JOIN poli po  ON po.id_poli  = k.id_poli
    ORDER BY k.tanggal DESC, k.id_kunjungan DESC
");

$obat_list = mysqli_query($conn, "SELECT id_obat, nama_obat, satuan, stok FROM obat WHERE stok > 0 ORDER BY nama_obat");

if (isset($_POST['simpan'])) {
    $id_kunjungan = (int)($_POST['id_kunjungan'] ?? 0);
    $id_obat      = (int)($_POST['id_obat'] ?? 0);
    $jumlah       = max(1, (int)($_POST['jumlah_obat'] ?? 0));
    $aturan       = trim($_POST['aturan_pakai'] ?? '');

    if ($id_kunjungan <= 0 || $id_obat <= 0 || $jumlah <= 0) {
        $error = 'Semua field wajib diisi.';
    } else {
        // Cek stok cukup
        $stmt = mysqli_prepare($conn, "SELECT stok FROM obat WHERE id_obat = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id_obat);
        mysqli_stmt_execute($stmt);
        $obat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$obat) {
            $error = 'Obat tidak ditemukan.';
        } elseif ((int)$obat['stok'] < $jumlah) {
            $error = 'Stok obat tidak mencukupi (sisa ' . (int)$obat['stok'] . ').';
        } else {
            mysqli_begin_transaction($conn);
            try {
                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO detail_kunjungan (id_kunjungan, id_obat, jumlah_obat, aturan_pakai)
                     VALUES (?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, 'iiis', $id_kunjungan, $id_obat, $jumlah, $aturan);
                mysqli_stmt_execute($stmt);

                $stmt = mysqli_prepare($conn, "UPDATE obat SET stok = stok - ? WHERE id_obat = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $jumlah, $id_obat);
                mysqli_stmt_execute($stmt);

                mysqli_commit($conn);
                header('Location: ' . $base . '/detail_kunjungan/data_detail.php');
                exit();
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}

function fmt_tgl_short($d) {
    if (!$d) return '';
    $t = strtotime($d);
    return date('d/m/Y', $t);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Resep — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Tambah Resep Obat</h2>
            <a href="<?= $base ?>/detail_kunjungan/data_detail.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Kunjungan</label>
                    <select name="id_kunjungan" required>
                        <option value="">— Pilih kunjungan —</option>
                        <?php while ($k = mysqli_fetch_assoc($kunjungan_list)): ?>
                            <option value="<?= (int)$k['id_kunjungan'] ?>">
                                <?= htmlspecialchars($k['nomor_antrian'] ?? '-') ?>
                                — <?= htmlspecialchars($k['nama_pasien']) ?>
                                (<?= htmlspecialchars($k['nama_poli']) ?>, <?= fmt_tgl_short($k['tanggal']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Obat (stok &gt; 0)</label>
                    <select name="id_obat" required>
                        <option value="">— Pilih obat —</option>
                        <?php while ($o = mysqli_fetch_assoc($obat_list)): ?>
                            <option value="<?= (int)$o['id_obat'] ?>">
                                <?= htmlspecialchars($o['nama_obat']) ?>
                                (sisa <?= (int)$o['stok'] ?> <?= htmlspecialchars($o['satuan'] ?? '') ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah</label>
                    <div class="stepper">
                        <button type="button" onclick="stepJumlah(-1)" aria-label="Kurangi jumlah">−</button>
                        <input type="number" id="jumlah" name="jumlah_obat" min="1" value="<?= max(1, (int)($_POST['jumlah_obat'] ?? 1)) ?>" required>
                        <button type="button" onclick="stepJumlah(1)" aria-label="Tambah jumlah">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Aturan Pakai</label>
                    <input type="text" name="aturan_pakai" placeholder="Contoh: 3x1 sehari setelah makan"
                           value="<?= htmlspecialchars($_POST['aturan_pakai'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top:24px;display:flex;gap:10px;">
                <button type="submit" name="simpan" class="btn btn-primary">Simpan Resep</button>
                <button type="reset" class="btn btn-outline">Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
function stepJumlah(delta) {
    const el = document.getElementById('jumlah');
    const v = parseInt(el.value || '1', 10) + delta;
    el.value = Math.max(1, v);
}
</script>

</body>
</html>
