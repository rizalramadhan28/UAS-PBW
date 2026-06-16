<?php
session_start();

// ==========================================
// PROTEKSI HALAMAN: cek apakah sudah login
// ==========================================
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Belum login → redirect ke halaman login
    header("Location: login.php");
    exit();
}

$username    = htmlspecialchars($_SESSION['username']);
$login_time  = date("d M Y, H:i:s", $_SESSION['login_time']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Sistem Autentikasi</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0e0f13;
            --surface: #16181f;
            --surface2: #1c1f2a;
            --border: #2a2d38;
            --accent: #e8c46a;
            --accent-dim: #c9a84c;
            --text: #ecedf0;
            --muted: #7a7e8e;
            --success: #6ad4a0;
            --danger: #f07070;
            --info: #6aafe8;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dim));
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
        }

        .brand-name {
            font-family: 'DM Serif Display', serif;
            font-size: 1.15rem;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.4rem 0.9rem;
            font-size: 0.85rem;
            color: var(--muted);
        }

        .user-badge strong { color: var(--accent); }

        .btn-logout {
            background: rgba(240,112,112,0.12);
            border: 1px solid rgba(240,112,112,0.3);
            color: var(--danger);
            padding: 0.45rem 1rem;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-logout:hover { background: rgba(240,112,112,0.22); }

        /* ── MAIN ── */
        .main {
            padding: 2.5rem 2rem;
            max-width: 900px;
            margin: 0 auto;
            animation: fadeIn 0.4s ease both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .page-title {
            font-family: 'DM Serif Display', serif;
            font-size: 2rem;
            font-weight: 400;
            margin-bottom: 0.4rem;
        }

        .page-subtitle {
            color: var(--muted);
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        /* ── STATS ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.green::before  { background: var(--success); }
        .stat-card.yellow::before { background: var(--accent); }
        .stat-card.blue::before   { background: var(--info); }

        .stat-icon { font-size: 1.8rem; margin-bottom: 0.75rem; }

        .stat-value {
            font-family: 'DM Serif Display', serif;
            font-size: 1.7rem;
            margin-bottom: 0.2rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* ── SESSION INFO ── */
        .section-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .info-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.7rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .info-row:last-child { border-bottom: none; }

        .info-key { color: var(--muted); }
        .info-val { color: var(--text); font-weight: 500; }
        .info-val.ok {
            color: var(--success);
            background: rgba(106,212,160,0.1);
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        /* ── CHECKLIST ── */
        .checklist {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .checklist li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            padding: 0.65rem 1rem;
            background: var(--surface2);
            border-radius: 9px;
            border: 1px solid var(--border);
        }

        .check {
            width: 20px; height: 20px;
            border-radius: 50%;
            background: rgba(106,212,160,0.15);
            border: 1.5px solid var(--success);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem;
            color: var(--success);
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon">🔐</div>
        <span class="brand-name">AuthSystem</span>
    </div>
    <div class="topbar-user">
        <div class="user-badge">
            👤 Halo, <strong><?= $username ?></strong>
        </div>
        <a href="logout.php" class="btn-logout">⏻ Logout</a>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="main">
    <h1 class="page-title">Panel Admin</h1>
    <p class="page-subtitle">Halaman ini hanya bisa diakses setelah login berhasil.</p>

    <!-- STATS -->
    <div class="stats">
        <div class="stat-card green">
            <div class="stat-icon">✅</div>
            <div class="stat-value">Aktif</div>
            <div class="stat-label">Status Sesi</div>
        </div>
        <div class="stat-card yellow">
            <div class="stat-icon">🔒</div>
            <div class="stat-value">Aman</div>
            <div class="stat-label">Status Autentikasi</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon">🕐</div>
            <div class="stat-value"><?= date("H:i") ?></div>
            <div class="stat-label">Waktu Login</div>
        </div>
    </div>

    <!-- SESSION INFO -->
    <p class="section-title">Informasi Sesi</p>
    <div class="info-box">
        <div class="info-row">
            <span class="info-key">Username</span>
            <span class="info-val"><?= $username ?></span>
        </div>
        <div class="info-row">
            <span class="info-key">Waktu Login</span>
            <span class="info-val"><?= $login_time ?></span>
        </div>
        <div class="info-row">
            <span class="info-key">Session ID</span>
            <span class="info-val" style="font-size:0.8rem;font-family:monospace;color:var(--muted)"><?= session_id() ?></span>
        </div>
        <div class="info-row">
            <span class="info-key">Status Proteksi</span>
            <span class="info-val ok">✔ Terproteksi</span>
        </div>
    </div>

    <!-- CHECKLIST FITUR -->
    <p class="section-title">Fitur Autentikasi yang Diimplementasi</p>
    <div class="info-box">
        <ul class="checklist">
            <li><span class="check">✓</span> Halaman login dengan validasi username &amp; password</li>
            <li><span class="check">✓</span> Password di-hash menggunakan <code>password_hash()</code> dengan algoritma PASSWORD_DEFAULT</li>
            <li><span class="check">✓</span> PHP Session untuk menjaga status login</li>
            <li><span class="check">✓</span> Fungsi logout yang menghapus session secara benar</li>
            <li><span class="check">✓</span> Proteksi halaman: halaman admin tidak dapat diakses tanpa login</li>
        </ul>
    </div>
</main>

</body>
</html>