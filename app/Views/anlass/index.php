<?php

declare(strict_types=1);

use App\Core\Url;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anlass auswaehlen</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Planung</div>
                                <h1 class="h2 mb-2">Anlass auswaehlen</h1>
                                <p class="muted-copy mb-0">
                                    Waehle die passende Kategorie als Ausgangspunkt fuer Aufbau, Inhalte und naechste Schritte.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/dashboard')) ?>" class="btn btn-outline-secondary">
                                    Zurueck zum Dashboard
                                </a>
                                <a href="<?= htmlspecialchars(Url::app('/logout')) ?>" class="btn btn-outline-danger">
                                    Logout
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <?php foreach ($anlass as $eintrag): ?>
                                <div class="col-12 col-md-6 col-xl-4">
                                    <a
                                        href="<?= htmlspecialchars(Url::app('/anlass/' . (int) $eintrag['id'])) ?>"
                                        class="anlass-card list-group-item h-100 p-4 bg-white rounded-4 text-decoration-none text-body"
                                    >
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <div class="small text-body-secondary mb-1">Kategorie</div>
                                                <div class="fw-semibold fs-5">
                                                    <?= htmlspecialchars((string) $eintrag['name_anlass']) ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($eintrag['start_anlass'])): ?>
                                                <span class="badge text-bg-primary">
                                                    <?= htmlspecialchars((string) $eintrag['start_anlass']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="muted-copy mb-0">
                                            <?php if (!empty($eintrag['end_anlass'])): ?>
                                                Bis <?= htmlspecialchars((string) $eintrag['end_anlass']) ?>
                                            <?php else: ?>
                                                Kein Enddatum hinterlegt.
                                            <?php endif; ?>
                                        </p>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($anlass === []): ?>
                            <div class="alert alert-light border mb-4">
                                Es sind aktuell noch keine Anlaesse in der Datenbank vorhanden.
                            </div>
                        <?php endif; ?>

                        <div class="dashboard-meta">
                            <div class="list-group">
                                <div class="list-group-item p-3">
                                    <div class="small text-body-secondary mb-1">Empfehlung</div>
                                    <div class="fw-semibold">Starte mit der Kategorie, die deinem Anlass am naechsten kommt.</div>
                                </div>
                                <div class="list-group-item p-3 mt-3">
                                    <div class="small text-body-secondary mb-1">Hinweis</div>
                                    <div class="fw-semibold">Die genaue Ausgestaltung kann im naechsten Schritt weiterhin individuell angepasst werden.</div>
                                </div>
                                <div class="list-group-item p-3 mt-3">
                                    <div class="small text-body-secondary mb-1">Status</div>
                                    <div class="fw-semibold">Die Seite ist als Auswahluebersicht vorbereitet und kann jetzt an deinen weiteren Flow angebunden werden.</div>
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
