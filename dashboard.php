<?php

declare(strict_types=1);

require_once __DIR__ . '/auth/require_login.php';

$user = current_user();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            margin: 0;
            padding: 40px 24px;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #1f2937;
        }

        .wrapper {
            max-width: 720px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
        }

        a {
            color: #0f766e;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <main class="wrapper">
        <h1>Dashboard</h1>
        <p>Willkommen, <?= htmlspecialchars((string) ($user['username'] ?? '')) ?>.</p>
        <p>Deine E-Mail: <?= htmlspecialchars((string) ($user['email'] ?? '')) ?></p>
        <p>Diese Seite ist geschuetzt und nur nach erfolgreichem Login erreichbar.</p>
        <p><a href="<?= htmlspecialchars(app_url('/logout.php')) ?>">Logout</a></p>
    </main>
</body>
</html>

