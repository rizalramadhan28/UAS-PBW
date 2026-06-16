<?php
if (!isset($_SESSION)) {
    session_start();
}

$base = BASE_URL;

$cur = $_SERVER['PHP_SELF'] ?? '';
function nav_active_p($cur, $needle) {
    return strpos($cur, $needle) !== false ? 'active' : '';
}
$userName = htmlspecialchars($_SESSION['username'] ?? 'Pasien');
?>

<style>
:root {
    --sb-w: 248px;
    --sb-bg: #0c2f23;
    --sb-bg2: #0f3d2d;
    --sb-accent: #2bb673;
    --sb-text: #cfe6db;
    --sb-muted: #7fa493;
}

body { padding-left: var(--sb-w); transition: padding .2s; }

.sidebar {
    position: fixed; top: 0; left: 0;
    width: var(--sb-w); height: 100vh;
    background: linear-gradient(180deg, var(--sb-bg) 0%, var(--sb-bg2) 100%);
    display: flex; flex-direction: column;
    z-index: 1000;
    box-shadow: 2px 0 14px rgba(0,0,0,.18);
    font-family: 'Segoe UI', Arial, sans-serif;
}
.sidebar-brand {
    display: flex; align-items: center; gap: 12px;
    padding: 22px 20px; border-bottom: 1px solid rgba(255,255,255,.08);
}
.sidebar-brand .logo {
    width: 42px; height: 42px; border-radius: 12px;
    background: linear-gradient(135deg, var(--sb-accent), #1e8f57);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.sidebar-brand .brand-text b { color: #fff; font-size: 1.05rem; display: block; line-height: 1.2; }
.sidebar-brand .brand-text span { color: var(--sb-muted); font-size: 11px; text-transform: uppercase; letter-spacing: .08em; }

.sidebar-user {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,.08);
}
.sidebar-user .avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--sb-accent); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; flex-shrink: 0;
}
.sidebar-user .info b { color: #fff; font-size: .9rem; display: block; }
.sidebar-user .info span { color: var(--sb-muted); font-size: 11px; }

.sidebar-nav { flex: 1; overflow-y: auto; padding: 14px 12px; display: flex; flex-direction: column; gap: 4px; }
.sidebar-nav .nav-label { color: var(--sb-muted); font-size: 10px; text-transform: uppercase; letter-spacing: .1em; padding: 10px 12px 4px; }
.sidebar-nav a {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 14px; color: var(--sb-text); text-decoration: none;
    border-radius: 9px; font-size: .92rem; font-weight: 500; position: relative;
    transition: background .15s, color .15s;
}
.sidebar-nav a .icon { font-size: 17px; width: 20px; text-align: center; }
.sidebar-nav a:hover { background: rgba(255,255,255,.07); color: #fff; }
.sidebar-nav a.active { background: rgba(43,182,115,.18); color: #fff; }
.sidebar-nav a.active::before {
    content: ''; position: absolute; left: 0; top: 8px; bottom: 8px;
    width: 3px; border-radius: 0 3px 3px 0; background: var(--sb-accent);
}

.sidebar-footer { padding: 12px; border-top: 1px solid rgba(255,255,255,.08); }
.sidebar-footer a {
    display: flex; align-items: center; gap: 10px; justify-content: center;
    padding: 11px; background: rgba(240,112,112,.12);
    border: 1px solid rgba(240,112,112,.28); color: #f3a3a3;
    border-radius: 9px; text-decoration: none; font-size: .9rem; font-weight: 600;
    transition: background .15s;
}
.sidebar-footer a:hover { background: rgba(240,112,112,.22); color: #fff; }

.sidebar-toggle {
    display: none; position: fixed; top: 14px; left: 14px; z-index: 1100;
    width: 44px; height: 44px; border: none; border-radius: 10px;
    background: var(--sb-bg); color: #fff; font-size: 20px; cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
}

@media (max-width: 900px) {
    body { padding-left: 0; }
    .sidebar { transform: translateX(-100%); transition: transform .25s; }
    .sidebar.open { transform: translateX(0); }
    .sidebar-toggle { display: block; }
}
@media print {
    .sidebar, .sidebar-toggle { display: none !important; }
    body { padding-left: 0 !important; }
}
</style>

<button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="logo">🏥</div>
        <div class="brand-text">
            <b>Klinik Kemala</b>
            <span>Portal Pasien</span>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
        <div class="info">
            <b><?= $userName ?></b>
            <span>Pasien</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Menu</div>
        <a href="<?= $base ?>/users/dashboard_pasien.php" class="<?= nav_active_p($cur, 'dashboard_pasien') ?>">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="<?= $base ?>/pasien/view_pasien.php" class="<?= nav_active_p($cur, '/pasien/') ?>">
            <span class="icon">👤</span> Profil Saya
        </a>
        <a href="<?= $base ?>/kunjungan/tambah_kunjungan.php" class="<?= nav_active_p($cur, '/kunjungan/') ?>">
            <span class="icon">📝</span> Daftar Kunjungan
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $base ?>/users/logout.php">⏻ Logout</a>
    </div>
</aside>
