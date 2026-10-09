<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $user */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Übersicht']); ?>
</head>
<body class="app-shell">
    <?php \App\Core\View::partial('partials/navigation', ['navigation' => $navigation ?? []]); ?>
    <main id="main-content" tabindex="-1" class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Arbeitsplatz</div>
                                <h1 class="h2 mb-2">Übersicht</h1>
                                <p class="muted-copy mb-0">Öffne einen Anlass oder verwalte die Stammdaten deiner Schützen.</p>
                            </div>
                            <div class="small text-body-secondary pt-md-2">Angemeldet als <strong class="text-body"><?= htmlspecialchars((string) ($user['username'] ?? '')) ?></strong></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <a class="app-workflow-link" href="<?= htmlspecialchars(Url::app('/anlass')) ?>">
                                    <strong>Anlässe öffnen <span class="app-workflow-arrow" aria-hidden="true">→</span></strong>
                                    <span>Standblätter erstellen und abrechnen, Ranglisten anzeigen und die Finale vorbereiten.</span>
                                </a>
                            </div>
                            <div class="col-12 col-md-6">
                                <a class="app-workflow-link" href="<?= htmlspecialchars(Url::app('/schuetzen')) ?>">
                                    <strong>Schützen verwalten <span class="app-workflow-arrow" aria-hidden="true">→</span></strong>
                                    <span>Schützen suchen, neu erfassen und ihre Adress- und Vereinsangaben pflegen.</span>
                                </a>
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
