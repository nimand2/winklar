<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) $anlass['name_anlass']) ?></title>
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
                                <div class="brand-badge mb-3">Anlass</div>
                                <h1 class="h2 mb-2"><?= htmlspecialchars((string) $anlass['name_anlass']) ?></h1>
                                <p class="muted-copy mb-0">
                                    Detailansicht zum ausgewaehlten Anlass.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass')) ?>" class="btn btn-outline-secondary">
                                    Zurueck zur Auswahl
                                </a>
                                <a href="<?= htmlspecialchars(Url::app('/dashboard')) ?>" class="btn btn-outline-secondary">
                                    Dashboard
                                </a>
                                <a href="<?= htmlspecialchars(Url::app('/logout')) ?>" class="btn btn-outline-danger">
                                    Logout
                                </a>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/schuetzen/neu')) ?>" class="btn btn-primary">
                                Neuer Schütz
                            </a>
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen')) ?>" class="btn btn-outline-primary">
                                Auswahl Schütz
                            </a>
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/schuetzen')) ?>" class="btn btn-outline-secondary">
                                Adressverwaltung öffnen
                            </a>
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/abschliessen')) ?>" class="btn btn-outline-danger">
                                Anlass Abschliessen
                            </a>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Kurzname</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['shortname_anlass'] ?: 'Kein Kurzname hinterlegt')) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Anlass-ID</div>
                                    <div class="fw-semibold">#<?= htmlspecialchars((string) $anlass['id']) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Start</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['start_anlass'] ?: 'Kein Startdatum hinterlegt')) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Ende</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['end_anlass'] ?: 'Kein Enddatum hinterlegt')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-meta mt-4">
                            <div class="list-group">
                                <div class="list-group-item p-3">
                                    <div class="small text-body-secondary mb-1">Erstellt am</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['created_at'] ?: 'Nicht hinterlegt')) ?>
                                    </div>
                                </div>
                                <div class="list-group-item p-3 mt-3">
                                    <div class="small text-body-secondary mb-1">Zuletzt aktualisiert</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['updated_at'] ?: 'Nicht hinterlegt')) ?>
                                    </div>
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
