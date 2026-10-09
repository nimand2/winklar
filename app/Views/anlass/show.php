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
    <?php \App\Core\View::partial('partials/navigation', ['navigation' => $navigation ?? []]); ?>
    <main id="main-content" tabindex="-1" class="container py-5">
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


                            </div>
                        </div>

                        <p class="muted-copy mb-4">Wähle den nächsten Arbeitsschritt. Alle Bereiche bleiben oben im Anlassmenü erreichbar.</p>
                        <div class="row g-3 mb-4">
                            <?php foreach ([
                                ['/loesen/adresse', 'Standblatt erstellen', 'Schützen auswählen und Stiche lösen.'],
                                ['/loesen', 'Standblätter öffnen', 'Standblatt suchen, bearbeiten, drucken oder abrechnen.'],
                                ['/schuetzen', 'Schützen verwalten', 'Personen suchen und Stammdaten bearbeiten.'],
                                ['/abschliessen', 'Rangliste anzeigen', 'Resultate nach Stich und Alterskategorie vergleichen.'],
                                ['/final', 'Final auswerten', 'U18- und Ü18-Final, Standblätter und JSON-Export.'],
                                ['/kasse', 'Kasse öffnen', 'Einnahmen und Gabenabgaben kontrollieren.'],
                            ] as [$path, $label, $description]): ?>
                                <div class="col-12 col-md-6 col-xl-4">
                                    <a class="app-workflow-link" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . $path)) ?>"><strong><?= htmlspecialchars($label) ?></strong><span><?= htmlspecialchars($description) ?></span></a>
                                </div>
                            <?php endforeach; ?>
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

                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
