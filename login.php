<?php

declare(strict_types=1);

require_once __DIR__ . '/auth/auth.php';

if (is_logged_in()) {
    redirect('/dashboard.php');
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --primary: #0f766e;
            --primary-hover: #115e59;
            --danger-bg: #fee2e2;
            --danger-text: #991b1b;
            --success-bg: #dcfce7;
            --success-text: #166534;
            --border: #dbe2ea;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, #d9f99d 0, transparent 25%),
                radial-gradient(circle at bottom right, #99f6e4 0, transparent 25%),
                var(--bg);
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 28px;
        }

        p {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--muted);
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
        }

        .alert.error {
            background: var(--danger-bg);
            color: var(--danger-text);
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 15px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            color: #ffffff;
            background: var(--primary);
            transition: background 0.2s ease, opacity 0.2s ease;
        }

        button:hover {
            background: var(--primary-hover);
        }

        button:disabled {
            cursor: wait;
            opacity: 0.75;
        }
    </style>
</head>
<body>
    <main class="card">
        <h1>Login</h1>
        <p>Melde dich mit Benutzername oder E-Mail an.</p>

        <?php if ($flash): ?>
            <div class="alert <?= htmlspecialchars((string) $flash['type']) ?>">
                <?= htmlspecialchars((string) $flash['message']) ?>
            </div>
        <?php endif; ?>

        <form id="login-form" action="<?= htmlspecialchars(app_url('/auth/login_process.php')) ?>" method="post">
            <label for="login">Benutzername oder E-Mail</label>
            <input
                type="text"
                id="login"
                name="login"
                autocomplete="username"
                required
            >

            <label for="password">Passwort</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <label class="remember" for="remember_me">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                Eingeloggt bleiben
            </label>

            <button type="submit" id="login-button">Einloggen</button>
        </form>
    </main>

    <script>
        const loginForm = document.getElementById('login-form');
        const loginButton = document.getElementById('login-button');

        loginForm.addEventListener('submit', function () {
            loginButton.disabled = true;
            loginButton.textContent = 'Pruefe Login...';
        });
    </script>
</body>
</html>

