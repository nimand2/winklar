<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$ranglisten = $ranglisten ?? [];
$formatNumber = static function (float $value): string {
    $rounded = round($value, 2);

    return floor($rounded) === $rounded ? (string) (int) $rounded : number_format($rounded, 2, '.', "'");
};
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
                                    Auswertung pro Stich und Kategorie. Bei Punktgleichheit entscheidet das nächstbeste Resultat.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2 no-print">
                                <button type="button" class="btn btn-primary" onclick="window.print()">Drucken</button>
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                    Zurück zum Anlass
                                </a>
                            </div>
                        </div>

                        <?php if ($ranglisten === []): ?>
                            <div class="alert alert-light border mb-0">
                                Für diesen Anlass sind noch keine Stiche vorhanden.
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-column gap-4">
                            <?php foreach ($ranglisten as $rangliste): ?>
                                <?php
                                $stich = $rangliste['stich'] ?? [];
                                $kategorien = $rangliste['kategorien'] ?? [
                                    'offen' => [
                                        'label' => 'Rangliste',
                                        'teilnehmer' => $rangliste['teilnehmer'] ?? [],
                                    ],
                                ];
                                $klassiert = array_sum(array_map(
                                    static fn (array $kategorie): int => count((array) ($kategorie['teilnehmer'] ?? [])),
                                    (array) $kategorien
                                ));
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
                                            <?= (int) $klassiert ?> klassiert
                                        </div>
                                    </div>

                                    <?php if ($klassiert === 0): ?>
                                        <div class="alert alert-light border mb-0">
                                            Für diesen Stich sind noch keine Schussdaten vorhanden.
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex flex-column gap-4">
                                            <?php foreach ($kategorien as $kategorie): ?>
                                                <?php $teilnehmer = (array) ($kategorie['teilnehmer'] ?? []); ?>
                                                <div>
                                                    <div class="d-flex justify-content-between gap-2 mb-2">
                                                        <h3 class="h6 mb-0"><?= htmlspecialchars((string) ($kategorie['label'] ?? 'Kategorie')) ?></h3>
                                                        <div class="small text-body-secondary"><?= count($teilnehmer) ?> klassiert</div>
                                                    </div>

                                                    <?php if ($teilnehmer === []): ?>
                                                        <div class="alert alert-light border mb-0">
                                                            Keine klassierten Schützen in dieser Kategorie.
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="table-responsive">
                                                            <table class="table align-middle mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width: 80px;">Rang</th>
                                                                        <th>Schütze</th>
                                                                        <th>Verein</th>
                                                                        <th class="text-end">Bestes</th>
                                                                        <th>Resultate</th>
                                                                        <th class="text-end">Schüsse</th>
                                                                        <th>Geburtsdatum</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($teilnehmer as $row): ?>
                                                                        <?php
                                                                        $resultate = array_map(
                                                                            static fn ($value): string => $formatNumber((float) $value),
                                                                            (array) ($row['resultate'] ?? [])
                                                                        );
                                                                        ?>
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
                                                                            <td class="text-end fw-semibold"><?= htmlspecialchars($formatNumber((float) $row['total'])) ?></td>
                                                                            <td><?= htmlspecialchars($resultate === [] ? '-' : implode(' / ', $resultate)) ?></td>
                                                                            <td class="text-end"><?= (int) $row['schuss_count'] ?></td>
                                                                            <td><?= htmlspecialchars((string) ($row['geburtsdatum'] ?: '-')) ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
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
