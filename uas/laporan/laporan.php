<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] != "admin") {
    header("Location: ../users/login.php");
    exit;
}

include '../users/navbar_admin.php';
include '../config/koneksi.php';

// Filter tanggal (opsional)
$tgl_awal  = isset($_GET['tgl_awal'])  && $_GET['tgl_awal']  != '' ? $_GET['tgl_awal']  : null;
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] != '' ? $_GET['tgl_akhir'] : null;

$where_kunjungan = "";
if ($tgl_awal && $tgl_akhir) {
    $a = mysqli_real_escape_string($conn, $tgl_awal);
    $b = mysqli_real_escape_string($conn, $tgl_akhir);
    $where_kunjungan = " WHERE k.tanggal BETWEEN '$a' AND '$b' ";
}

// Hitung ringkasan
function hitung($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($r);
    return $row['jml'] ?? 0;
}

$total_pasien    = hitung($conn, "SELECT COUNT(*) jml FROM pasien");
$total_dokter    = hitung($conn, "SELECT COUNT(*) jml FROM dokter WHERE aktif = 1");
$total_poli      = hitung($conn, "SELECT COUNT(*) jml FROM poli");
$total_obat      = hitung($conn, "SELECT COUNT(*) jml FROM obat");
$total_kunjungan = hitung($conn, "SELECT COUNT(*) jml FROM kunjungan k $where_kunjungan");

// Rekap kunjungan per poli
$rekap_poli = mysqli_query($conn, "
    SELECT po.nama_poli, COUNT(k.id_kunjungan) AS jml
    FROM poli po
    LEFT JOIN kunjungan k ON k.id_poli = po.id_poli
    " . ($where_kunjungan ? str_replace('WHERE', 'AND', $where_kunjungan) . " " : "") . "
    GROUP BY po.id_poli, po.nama_poli
    ORDER BY jml DESC
");

// Rekap kunjungan per status
$rekap_status = mysqli_query($conn, "
    SELECT k.status, COUNT(*) AS jml
    FROM kunjungan k
    $where_kunjungan
    GROUP BY k.status
");

// Rekap metode pembayaran
$rekap_bayar = mysqli_query($conn, "
    SELECT k.metode_pembayaran, COUNT(*) AS jml
    FROM kunjungan k
    $where_kunjungan
    GROUP BY k.metode_pembayaran
");

// Obat paling banyak diresepkan
$rekap_obat = mysqli_query($conn, "
    SELECT o.nama_obat, SUM(d.jumlah_obat) AS total
    FROM detail_kunjungan d
    JOIN obat o ON o.id_obat = d.id_obat
    JOIN kunjungan k ON k.id_kunjungan = d.id_kunjungan
    $where_kunjungan
    GROUP BY o.id_obat, o.nama_obat
    ORDER BY total DESC
    LIMIT 10
");

// Detail kunjungan
$detail_kunjungan = mysqli_query($conn, "
    SELECT k.tanggal, k.nomor_antrian, p.nama_pasien,
           po.nama_poli, d.nama_dokter,
           k.metode_pembayaran, k.status
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    JOIN dokter d ON d.id_dokter = k.id_dokter
    JOIN poli   po ON po.id_poli  = k.id_poli
    $where_kunjungan
    ORDER BY k.tanggal DESC, k.id_kunjungan DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan / Rekap Klinik</title>
    <style>
        body { font-family: Arial; background: whitesmoke; margin: 0; }
        .wrap { width: 92%; margin: 20px auto; }
        h1, h2, h3 { color: #0d7a54; }
        h1 { text-align: center; }
        .filter {
            background: white; padding: 15px; border-radius: 8px;
            box-shadow: 0 0 8px #ccc; margin-bottom: 20px;
        }
        .filter input, .filter button {
            padding: 7px 10px; margin-right: 5px; font-size: 14px;
        }
        .filter button {
            background: #0d7a54; color: white; border: none; cursor: pointer;
            border-radius: 4px;
        }
        .cards { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        .card {
            background: white; flex: 1; min-width: 150px;
            padding: 18px; border-radius: 8px;
            box-shadow: 0 0 8px #ccc; text-align: center;
        }
        .card h3 { margin: 0 0 6px 0; font-size: 15px; color: #555; }
        .card .num { font-size: 28px; font-weight: bold; color: #0d7a54; }
        table {
            width: 100%; border-collapse: collapse;
            background: white; margin-bottom: 25px;
        }
        th, td { border: 1px solid #aaa; padding: 8px; font-size: 14px; }
        th { background: #0d7a54; color: white; }
        .section {
            background: white; padding: 15px; border-radius: 8px;
            box-shadow: 0 0 8px #ccc; margin-bottom: 20px;
        }
        .row { display: flex; gap: 20px; flex-wrap: wrap; }
        .row .section { flex: 1; min-width: 280px; }
        .btn-print {
            background: #0d7a54; color: white; border: none;
            padding: 8px 14px; border-radius: 4px; cursor: pointer;
        }
        @media print {
            .navbar, .filter, .btn-print { display: none; }
            body { background: white; }
            .section, .card { box-shadow: none; border: 1px solid #999; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Laporan &amp; Rekap Data Klinik</h1>

    <div class="filter">
        <form method="get">
            <strong>Filter Tanggal Kunjungan:</strong>
            Dari <input type="date" name="tgl_awal"  value="<?= htmlspecialchars($tgl_awal ?? '') ?>">
            s/d  <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tgl_akhir ?? '') ?>">
            <button type="submit">Tampilkan</button>
            <a href="laporan.php"><button type="button">Reset</button></a>
            <button type="button" class="btn-print" onclick="window.print()">Cetak</button>
        </form>
        <?php if ($tgl_awal && $tgl_akhir): ?>
            <p><em>Periode: <?= $tgl_awal ?> s/d <?= $tgl_akhir ?></em></p>
        <?php endif; ?>
    </div>

    <div class="cards">
        <div class="card"><h3>Total Pasien</h3>    <div class="num"><?= $total_pasien ?></div></div>
        <div class="card"><h3>Total Dokter</h3>    <div class="num"><?= $total_dokter ?></div></div>
        <div class="card"><h3>Total Poli</h3>      <div class="num"><?= $total_poli ?></div></div>
        <div class="card"><h3>Total Obat</h3>      <div class="num"><?= $total_obat ?></div></div>
        <div class="card"><h3>Total Kunjungan</h3> <div class="num"><?= $total_kunjungan ?></div></div>
    </div>

    <div class="row">
        <div class="section">
            <h2>Rekap Kunjungan per Poli</h2>
            <table>
                <tr><th>No</th><th>Poli</th><th>Jumlah</th></tr>
                <?php $no=1; while ($r = mysqli_fetch_assoc($rekap_poli)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($r['nama_poli']) ?></td>
                        <td><?= (int)$r['jml'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="section">
            <h2>Rekap Status Kunjungan</h2>
            <table>
                <tr><th>Status</th><th>Jumlah</th></tr>
                <?php while ($r = mysqli_fetch_assoc($rekap_status)): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['status'] ?? '-') ?></td>
                        <td><?= (int)$r['jml'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <h2>Rekap Metode Pembayaran</h2>
            <table>
                <tr><th>Metode</th><th>Jumlah</th></tr>
                <?php while ($r = mysqli_fetch_assoc($rekap_bayar)): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['metode_pembayaran'] ?? '-') ?></td>
                        <td><?= (int)$r['jml'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="section">
        <h2>10 Obat Paling Banyak Diresepkan</h2>
        <table>
            <tr><th>No</th><th>Nama Obat</th><th>Total Diresepkan</th></tr>
            <?php $no=1; while ($r = mysqli_fetch_assoc($rekap_obat)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($r['nama_obat']) ?></td>
                    <td><?= (int)$r['total'] ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if ($no === 1): ?>
                <tr><td colspan="3" align="center"><em>Belum ada data resep.</em></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="section">
        <h2>Detail Kunjungan</h2>
        <table>
            <tr>
                <th>No</th><th>Tanggal</th><th>Antrian</th>
                <th>Pasien</th><th>Poli</th><th>Dokter</th>
                <th>Pembayaran</th><th>Status</th>
            </tr>
            <?php $no=1; while ($r = mysqli_fetch_assoc($detail_kunjungan)): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($r['tanggal']) ?></td>
                    <td><?= htmlspecialchars($r['nomor_antrian'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['nama_pasien']) ?></td>
                    <td><?= htmlspecialchars($r['nama_poli']) ?></td>
                    <td><?= htmlspecialchars($r['nama_dokter']) ?></td>
                    <td><?= htmlspecialchars($r['metode_pembayaran'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($r['status'] ?? '-') ?></td>
                </tr>
            <?php endwhile; ?>
            <?php if ($no === 1): ?>
                <tr><td colspan="8" align="center"><em>Belum ada kunjungan pada periode ini.</em></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <p style="text-align:right; color:#555; font-size:12px;">
        Dicetak: <?= date('d-m-Y H:i') ?>
    </p>
</div>
</body>
</html>
