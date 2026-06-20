<?php
session_start();

// Hanya admin yang boleh akses
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/users/login.php');
    exit();
}

include '../config/koneksi.php';

$base = BASE_URL;
$error = '';

// Ambil daftar poli untuk dropdown spesialis
$list_poli = mysqli_query($conn, "SELECT nama_poli FROM poli ORDER BY nama_poli ASC");

if (isset($_POST['simpan'])) {
    $nama      = trim($_POST['nama_dokter'] ?? '');
    $nip       = trim($_POST['nip'] ?? '');
    $sip       = trim($_POST['sip'] ?? '');
    $spesialis = trim($_POST['spesialis'] ?? '');
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    // Validasi: cek field wajib
    if ($nama === '' || $nip === '' || $sip === '' || $spesialis === '') {
        $error = 'Nama, NIP, SIP, dan Poli wajib diisi.';
    } else {
        // Validasi: cek NIP duplikat (hanya di antara dokter yang masih aktif)
        $cek = mysqli_prepare($conn, "SELECT id_dokter FROM dokter WHERE nip = ? AND aktif = 1");
        mysqli_stmt_bind_param($cek, 's', $nip);
        mysqli_stmt_execute($cek);
        $ada_nip = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

        // Validasi: cek SIP duplikat (hanya di antara dokter yang masih aktif)
        $cek = mysqli_prepare($conn, "SELECT id_dokter FROM dokter WHERE sip = ? AND aktif = 1");
        mysqli_stmt_bind_param($cek, 's', $sip);
        mysqli_stmt_execute($cek);
        $ada_sip = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));

        if ($ada_nip) {
            $error = 'NIP "' . htmlspecialchars($nip) . '" sudah dipakai dokter lain.';
        } elseif ($ada_sip) {
            $error = 'SIP "' . htmlspecialchars($sip) . '" sudah dipakai dokter lain.';
        } else {
            // Simpan ke database
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO dokter (nama_dokter, nip, sip, spesialis, no_hp, alamat)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'ssssss', $nama, $nip, $sip, $spesialis, $no_hp, $alamat);

            if (mysqli_stmt_execute($stmt)) {
                header('Location: ' . $base . '/dokter/data_dokter.php');
                exit();
            } else {
                $error = 'Gagal menyimpan: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Dokter — Klinik App</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/admin.css">
</head>
<body class="admin-page">

<?php include '../users/navbar_admin.php'; ?>

<div class="container" style="max-width:800px;">
    <div class="card">
        <div class="card-header">
            <h2>Tambah Data Dokter</h2>
            <a href="<?= $base ?>/dokter/data_dokter.php" class="btn btn-outline">Kembali</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Lengkap (beserta gelar)</label>
                    <input type="text" name="nama_dokter" required
                           placeholder="Contoh: dr. Andi, Sp.PD"
                           value="<?= htmlspecialchars($_POST['nama_dokter'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Poli (Spesialis)</label>
                    <select name="spesialis" required>
                        <option value="">— Pilih poli —</option>
                        <?php while ($p = mysqli_fetch_assoc($list_poli)): ?>
                            <option value="<?= htmlspecialchars($p['nama_poli']) ?>"
                                <?= ($_POST['spesialis'] ?? '') === $p['nama_poli'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nama_poli']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>NIP (harus unik)</label>
                    <input type="text" name="nip" required
                           placeholder="Nomor Induk Pegawai"
                           value="<?= htmlspecialchars($_POST['nip'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>SIP (harus unik)</label>
                    <input type="text" name="sip" required
                           placeholder="Nomor Surat Izin Praktik"
                           value="<?= htmlspecialchars($_POST['sip'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Nomor HP</label>
                    <input type="text" name="no_hp" placeholder="08xxxxxxxxxx"
                           value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Sub Spesialis</label>
                    <input type="text" name="sub_spesialis" placeholder="Sub Spesialis"
                           value="<?= htmlspecialchars($_POST['sub_spesialis'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Alamat</label>
                <textarea name="alamat" rows="3"
                          placeholder="Alamat lengkap dokter"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
            </div>

            <div style="margin-top:24px; display:flex; gap:10px;">
                <button type="submit" name="simpan" class="btn btn-primary">Simpan Data</button>
                <button type="reset" class="btn btn-outline">Reset</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
