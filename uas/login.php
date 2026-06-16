<?php
session_start();

// Jika sudah login, redirect ke halaman admin
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}

$error = "";

// Data user simulasi (dalam aplikasi nyata, ambil dari database)
$users = [
    "admin" => password_hash("admin123", PASSWORD_DEFAULT),
    "user1" => password_hash("password1", PASSWORD_DEFAULT),
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    // Validasi input tidak kosong
    if (empty($username) || empty($password)) {
        $error = "Username dan password tidak boleh kosong!";
    } elseif (!isset($users[$username])) {
        $error = "Username tidak ditemukan!";
    } elseif (!password_verify($password, $users[$username])) {
        $error = "Password salah!";
    } else {
        // Login berhasil — simpan session
        $_SESSION['user_logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        header("Location: admin.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Autentikasi</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0e0f13;
            --surface: #16181f;
            --border: #2a2d38;
            --accent: #e8c46a;
            --accent-dim: #c9a84c;
            --text: #ecedf0;
            --muted: #7a7e8e;
            --error-bg: #2a1a1a;
            --error: #f07070;
            --success: #6ad4a0;
            --input-bg: #1c1f2a;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        /* Decorative background circles */
        body::before {
            content: '';
            position: fixed;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(232,196,106,0.08) 0%, transparent 70%);
            top: -150px; right: -150px;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(106,212,160,0.05) 0%, transparent 70%);
            bottom: -100px; left: -100px;
            pointer-events: none;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 420px;
            position: relative;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dim));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }

        .logo-text {
            font-family: 'DM Serif Display', serif;
            font-size: 1.25rem;
            color: var(--text);
            letter-spacing: 0.01em;
        }

        h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.9rem;
            font-weight: 400;
            color: var(--text);
            margin-bottom: 0.4rem;
        }

        .subtitle {
            color: var(--muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .error-box {
            background: var(--error-bg);
            border: 1px solid rgba(240,112,112,0.3);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            color: var(--error);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%      { transform: translateX(-6px); }
            75%      { transform: translateX(6px); }
        }

        .field {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.825rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(232,196,106,0.12);
        }

        .password-wrapper {
            position: relative;
        }

        .toggle-pw {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: var(--text); }

        .btn-login {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--accent), var(--accent-dim));
            color: #0e0f13;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 0.75rem;
            letter-spacing: 0.02em;
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-login:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        .hint {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px dashed var(--border);
            border-radius: 10px;
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.7;
        }
        .hint strong { color: var(--success); }
    </style>
</head>
<body>

<div class="card">
    <div class="logo">
        <div class="logo-icon">🔐</div>
        <span class="logo-text">AuthSystem</span>
    </div>

    <h1>Selamat Datang</h1>
    <p class="subtitle">Silakan login untuk mengakses panel admin</p>

    <?php if (!empty($error)): ?>
        <div class="error-box">
            <span>⚠</span>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
        <div class="field">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Masukkan username..."
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                required
                autofocus
            >
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password..."
                    required
                >
                <button type="button" class="toggle-pw" onclick="togglePassword()" title="Tampilkan/Sembunyikan Password">
                    👁
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">Masuk →</button>
    </form>

    <div class="hint">
        <strong>Demo akun:</strong><br>
        Username: <strong>admin</strong> / Password: <strong>admin123</strong><br>
        Username: <strong>user1</strong> / Password: <strong>password1</strong>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById('password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>