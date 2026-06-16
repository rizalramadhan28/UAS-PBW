<?php
session_start();

include '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}



$base = BASE_URL;
$error = '';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . $base . '/detail_kunjungan/data_detail.php');
    exit();
}

// Ambil data resep beserta info kunjungan & pasien
$stmt = mysqli_prepare(
    $conn,
    "SELECT d.*, k.nomor_antrian, k.tanggal, p.nama_pasien, po.nama_poli
     FROM detail_kunjungan d
     JOIN kunjungan k ON k.id_kunjungan = d.id_kunjungan
     JOIN pasien p    ON p.id_pasien    = k.id_pasien
     JOIN poli po     ON po.id_poli     = k.id_poli
     WHERE d.id_detail = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    header('Location: ' . $base . '/detail_kunjungan/data_detail.php');
    exit();
}

// Daftar obat untuk dropdown (semua, tidak filter stok karena obat lama mungkin sekarang stoknya 0)
$obat_list = mysqli_query($conn, "SELECT id_obat, nama_obat, satuan, stok FROM obat ORDER BY nama_obat");

if (isset($_POST['update'])) {
    $id_obat_baru     = (int) ($_POST['id_obat'] ?? 0);
    $jumlah_baru      = max(1, (int) ($_POST['jumlah_obat'] ?? 0));
    $aturan           = trim($_POST['aturan_pakai'] ?? '');

    $id_obat_lama     = (int) $row['id_obat'];
    $jumlah_lama      = (int) $row['jumlah_obat'];

    if ($id_obat_baru <= 0 || $jumlah_baru <= 0) {
        $error = 'Obat dan jumlah wajib diisi.';
    } else {
        // Cek stok cukup untuk perubahan
        // Logika: kembalikan stok lama dulu, lalu kurangi stok baru.
        // Kalau obat sama: cek (stok + jumlah_lama) >= jumlah_baru
        // Kalau obat beda: cek stok_obat_baru >= jumlah_baru
        if ($id_obat_baru === $id_obat_lama) {
            $stmt = mysqli_prepare($conn, "SELECT stok FROM obat WHERE id_obat = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id_obat_baru);
            mysqli_stmt_execute($stmt);
            $stok_skrg = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['stok'];
            $stok_tersedia = $stok_skrg + $jumlah_lama; // setelah dikembalikan
        } else {
            $stmt = mysqli_prepare($conn, "SELECT stok FROM obat WHERE id_obat = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id_obat_baru);
            mysqli_stmt_execute($stmt);
            $cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            $stok_tersedia = $cek ? (int) $cek['stok'] : 0;
        }

        if ($stok_tersedia < $jumlah_baru) {
            $error = 'Stok obat tidak cukup (tersedia ' . $stok_tersedia . ').';
        } else {
            mysqli_begin_transaction($conn);
            try {
                // 1. Kembalikan stok obat lama
                $stmt = mysqli_prepare($conn, "UPDATE obat SET stok = stok + ? WHERE id_obat = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $jumlah_lama, $id_obat_lama);
                mysqli_stmt_execute($stmt);

                // 2. Kurangi stok obat baru
                $stmt = mysqli_prepare($conn, "UPDATE obat SET stok = stok - ? WHERE id_obat = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $jumlah_baru, $id_obat_baru);
                mysqli_stmt_execute($stmt);

                // 3. Update record resep
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE detail_kunjungan
                     SET id_obat = ?, jumlah_obat = ?, aturan_pakai = ?
                     WHERE id_detail = ?"
                );
                mysqli_stmt_bind_param($stmt, 'iisi', $id_obat_baru, $jumlah_baru, $aturan, $id);
                mysqli_stmt_execute($stmt);

                mysqli_commit($conn);
                $_SESSION['flash_success'] = 'Resep berhasil diperbarui. Stok obat telah disesuaikan.';
                header('Location: ' . $base . '/detail_kunjungan/data_detail.php');
                exit();
            } catch (Throwable $e) {
                mysqli_rollback($conn);
                $error = 'Gagal memperbarui: ' . $e->getMessage();
            }
        }

        // Refresh nilai form jika error
        $row['id_obat']      = $id_obat_baru;
        $row['jumlah_obat']  = $jumlah_baru;
        $row['aturan_pakai'] = $aturan;
    }
}

function fmt_tgl_short($d) {
    if (!$d) return '-';
    return date('d/m/Y', strtotime($d));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Resep — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Edit Resep Obat</h2>
            <a href="<?= $base ?>/detail_kunjungan/data_detail.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div style="background:var(--bg);padding:14px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;">
            <div><strong>Antrian:</strong> <?= htmlspecialchars($row['nomor_antrian'] ?? '-') ?></div>
            <div><strong>Pasien:</strong> <?= htmlspecialchars($row['nama_pasien']) ?></div>
            <div><strong>Poli:</strong> <?= htmlspecialchars($row['nama_poli']) ?></div>
            <div><strong>Tanggal:</strong> <?= fmt_tgl_short($row['tanggal']) ?></div>
        </div>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Obat</label>
                    <select name="id_obat" required>
                        <option value="">— Pilih obat —</option>
                        <?php while ($o = mysqli_fetch_assoc($obat_list)): ?>
                            <option value="<?= (int)$o['id_obat'] ?>"
                                <?= (int)$row['id_obat'] === (int)$o['id_obat'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['nama_obat']) ?>
                                (sisa <?= (int)$o['stok'] ?> <?= htmlspecialchars($o['satuan'] ?? '') ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah</label>
                    <div class="stepper">
                        <button type="button" onclick="stepJumlah(-1)" aria-label="Kurangi">−</button>
                        <input type="number" id="jumlah" name="jumlah_obat" min="1"
                               value="<?= (int)$row['jumlah_obat'] ?>" required>
                        <button type="button" onclick="stepJumlah(1)" aria-label="Tambah">+</button>
                    </div>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Aturan Pakai</label>
                    <input type="text" name="aturan_pakai"
                           placeholder="Contoh: 3x1 sehari setelah makan"
                           value="<?= htmlspecialchars($row['aturan_pakai'] ?? '') ?>">
                </div>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
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
