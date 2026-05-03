<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $anlass */
$anlassId = (int) $anlass['id'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => (string) $anlass['name_anlass']]); ?>
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
                            </div>

                            <div class="d-flex flex-wrap gap-2">                              
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration')) ?>" class="btn btn-outline-secondary">
                                Anlass konfigurieren
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
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/adresse')) ?>" class="btn btn-primary">
                                Standblatt lösen
                            </a>
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen')) ?>" class="btn btn-outline-primary">
                                Auswahl Standblatt
                            </a>
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/schuetzen')) ?>" class="btn btn-outline-secondary">
                                Adressverwaltung öffnen
                            </a>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Kurzname</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['shortname_anlass'] ?: 'Kein Kurzname hinterlegt')) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Anlass-ID</div>
                                    <div class="fw-semibold">#<?= htmlspecialchars((string) $anlass['id']) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Start</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['start_anlass'] ?: 'Kein Startdatum hinterlegt')) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Ende</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($anlass['end_anlass'] ?: 'Kein Enddatum hinterlegt')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/kasse')) ?>" class="btn btn-outline-secondary">
                                Kassen-Abrechnung
                            </a>
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/abschliessen')) ?>" class="btn btn-outline-secondary">
                                Rangliste anzeigen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
