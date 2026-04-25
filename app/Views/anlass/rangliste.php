<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$ranglisten = $ranglisten ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Rangliste']); ?>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .card,
            .list-group-item {
                border: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Abschluss</div>
                                <h1 class="h2 mb-2">Rangliste <?= htmlspecialchars((string) $anlass['name_anlass']) ?></h1>
                                <p class="muted-copy mb-0">
                                    Auswertung pro Stich. Bei Punktgleichheit wird der juengere Schuetze hoeher rangiert.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2 no-print">
                                <button type="button" class="btn btn-primary" onclick="window.print()">Drucken</button>
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                    Zurueck zum Anlass
                                </a>
                            </div>
                        </div>

                        <?php if ($ranglisten === []): ?>
                            <div class="alert alert-light border mb-0">
                                Fuer diesen Anlass sind noch keine Stiche vorhanden.
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-column gap-4">
                            <?php foreach ($ranglisten as $rangliste): ?>
                                <?php
                                $stich = $rangliste['stich'] ?? [];
                                $teilnehmer = $rangliste['teilnehmer'] ?? [];
                                ?>
                                <section class="list-group-item p-4 bg-white rounded-4">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                        <div>
                                            <h2 class="h5 mb-1"><?= htmlspecialchars((string) ($stich['name'] ?? 'Stich')) ?></h2>
                                            <div class="small text-body-secondary">
                                                <?= htmlspecialchars((string) (($stich['short_name'] ?? '') ?: '')) ?>
                                            </div>
                                        </div>
                                        <div class="small text-body-secondary">
                                            <?= count($teilnehmer) ?> klassiert
                                        </div>
                                    </div>

                                    <?php if ($teilnehmer === []): ?>
                                        <div class="alert alert-light border mb-0">
                                            Fuer diesen Stich sind noch keine Schussdaten vorhanden.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 80px;">Rang</th>
                                                        <th>Schuetze</th>
                                                        <th>Verein</th>
                                                        <th class="text-end">Total</th>
                                                        <th class="text-end">Schuesse</th>
                                                        <th>Geburtsdatum</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($teilnehmer as $row): ?>
                                                        <tr>
                                                            <td class="fw-semibold"><?= (int) $row['rang'] ?></td>
                                                            <td>
                                                                <a
                                                                    href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . (int) $row['standblatt_id'])) ?>"
                                                                    class="text-decoration-none"
                                                                >
                                                                    <?= htmlspecialchars((string) ($row['name'] ?: 'Unbekannt')) ?>
                                                                </a>
                                                            </td>
                                                            <td><?= htmlspecialchars((string) ($row['verein'] ?: '-')) ?></td>
                                                            <td class="text-end fw-semibold"><?= htmlspecialchars(number_format((float) $row['total'], 2, '.', "'")) ?></td>
                                                            <td class="text-end"><?= (int) $row['schuss_count'] ?></td>
                                                            <td><?= htmlspecialchars((string) ($row['geburtsdatum'] ?: '-')) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
