<?php

declare(strict_types=1);

use App\Core\Url;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Dashboard']); ?>
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

                            <a href="<?= htmlspecialchars(Url::app('/logout')) ?>" class="btn btn-outline-danger">
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
                            <div>
                                <a href="<?= htmlspecialchars(Url::app('/anlass')) ?>" class="btn btn-primary">Anlass auswählen</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
