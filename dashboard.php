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
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/app.css')) ?>">
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Geschuetzter Bereich</div>
                                <h1 class="h2 mb-2">Dashboard</h1>
                                <p class="muted-copy mb-0">Diese Seite ist nur nach erfolgreichem Login erreichbar.</p>
                            </div>

                            <a href="<?= htmlspecialchars(app_url('/logout.php')) ?>" class="btn btn-outline-danger">
                                Logout
                            </a>
                        </div>

                        <div class="dashboard-meta">
                            <div class="list-group">
                                <div class="list-group-item p-3">
                                    <div class="small text-body-secondary mb-1">Benutzername</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($user['username'] ?? '')) ?></div>
                                </div>
                                <div class="list-group-item p-3 mt-3">
                                    <div class="small text-body-secondary mb-1">E-Mail</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
