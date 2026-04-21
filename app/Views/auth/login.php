<?php

declare(strict_types=1);

use App\Core\Url;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
</head>
<body class="app-shell">
    <main class="container auth-wrapper d-flex align-items-center justify-content-center py-5">
        <div class="row justify-content-center w-100">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card auth-card">
                    <div class="card-body">
                        <div class="brand-badge mb-3">Login-Modul</div>
                        <h1 class="h2 mb-2">Anmelden</h1>
                        <p class="muted-copy mb-4">Melde dich mit Benutzername oder E-Mail an.</p>

                        <?php if (!empty($flash)): ?>
                            <?php $alertClass = ($flash['type'] ?? '') === 'success' ? 'alert-success' : 'alert-danger'; ?>
                            <div class="alert <?= htmlspecialchars($alertClass) ?>" role="alert">
                                <?= htmlspecialchars((string) ($flash['message'] ?? '')) ?>
                            </div>
                        <?php endif; ?>

                        <form id="login-form" action="<?= htmlspecialchars(Url::app('/login')) ?>" method="post" class="vstack gap-3">
                            <div>
                                <label for="login" class="form-label">Benutzername oder E-Mail</label>
                                <input
                                    type="text"
                                    id="login"
                                    name="login"
                                    class="form-control form-control-lg"
                                    autocomplete="username"
                                    required
                                >
                            </div>

                            <div>
                                <label for="password" class="form-label">Passwort</label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control form-control-lg"
                                    autocomplete="current-password"
                                    required
                                >
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me" value="1">
                                <label class="form-check-label" for="remember_me">
                                    Eingeloggt bleiben
                                </label>
                            </div>

                            <button type="submit" id="login-button" class="btn btn-primary btn-lg w-100">
                                Einloggen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= htmlspecialchars(Url::asset('js/login.js')) ?>"></script>
</body>
</html>
