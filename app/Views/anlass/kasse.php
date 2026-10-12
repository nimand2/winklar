<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $anlass */
/** @var array<string, mixed> $abrechnung */
$anlassId = (int) $anlass['id'];
$abrechnung = $abrechnung ?? [];

$money = static fn (float $value): string => 'CHF ' . number_format($value, 2, '.', "'");
$einnahmenTotal = (float) ($abrechnung['einnahmen_total'] ?? 0);
$gabenTotal = (float) ($abrechnung['gaben_total'] ?? 0);
$netto = (float) ($abrechnung['netto'] ?? 0);
$standblaetter = $abrechnung['standblaetter'] ?? [];
$stichEinnahmen = $abrechnung['stich_einnahmen'] ?? [];
$gaben = $abrechnung['gaben'] ?? [];
$abgaben = $abrechnung['abgaben'] ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Kassenübersicht']); ?>
    <style>
        .table-sort-button {
            border: 0;
            padding: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            font-weight: inherit;
            cursor: pointer;
            text-align: inherit;
        }
        .table-sort-arrow { margin-left: 0.3em; }
        @media print {
            .table-sort-arrow { display: none; }
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
    <?php \App\Core\View::partial('partials/navigation', ['navigation' => $navigation ?? []]); ?>
    <main id="main-content" tabindex="-1" class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Kasse</div>
                                <h1 class="h2 mb-2">Kassenübersicht <?= htmlspecialchars((string) $anlass['name_anlass']) ?></h1>
                                <p class="muted-copy mb-0">
                                    Einnahmen aus gelösten Standblättern und Warenwert der abgegebenen Gaben.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2 no-print">
                                <button type="button" class="btn btn-primary" onclick="window.print()">Drucken</button>

                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Einnahmen</div>
                                    <div class="fw-semibold fs-5"><?= htmlspecialchars($money($einnahmenTotal)) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Gabenwert abgegeben</div>
                                    <div class="fw-semibold fs-5"><?= htmlspecialchars($money($gabenTotal)) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Ergebnis nach abgegebenen Gaben</div>
                                    <div class="fw-semibold fs-5"><?= htmlspecialchars($money($netto)) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Offene Gabenprüfung</div>
                                    <div class="fw-semibold fs-5"><?= (int) ($abrechnung['offene_gaben_pruefungen'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <?php foreach (['gaben_offen_total' => 'Gabenwert noch offen', 'geld_abgegeben' => 'Barauszahlungen bestätigt', 'geld_offen' => 'Barauszahlungen offen', 'kassenbestand' => 'Einnahmen minus bestätigte Barauszahlungen'] as $key => $label): ?>
                                <div class="col-12 col-md-3"><div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1"><?= htmlspecialchars($label) ?></div>
                                    <div class="fw-semibold fs-5"><?= htmlspecialchars($money((float) ($abrechnung[$key] ?? 0))) ?></div>
                                </div></div>
                            <?php endforeach; ?>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <section class="list-group-item p-4 bg-white rounded-4 h-100">
                                    <h2 class="h5 mb-3">Einnahmen nach Stich</h2>
                                    <?php if ($stichEinnahmen === []): ?>
                                        <div class="alert alert-light border mb-0">Noch keine Stiche gelöst.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0" data-sortable-table>
                                                <thead>
                                                    <tr>
                                                        <th data-sort-type="text">Stich</th>
                                                        <th class="text-end" data-sort-type="number">Anzahl</th>
                                                        <th class="text-end" data-sort-type="number">Preis</th>
                                                        <th class="text-end" data-sort-type="number">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stichEinnahmen as $row): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string) $row['name']) ?><?php if (!empty($row['preis_uebernommen'])): ?><div class="small text-body-secondary">Preis aus bestehenden Gesamtkosten übernommen</div><?php endif; ?></td>
                                                            <td class="text-end"><?= (int) $row['anzahl_stiche'] ?></td>
                                                            <td class="text-end"><?= htmlspecialchars($money((float) $row['preis'])) ?></td>
                                                            <td class="text-end fw-semibold"><?= htmlspecialchars($money((float) $row['total'])) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </section>
                            </div>

                            <div class="col-12 col-lg-6">
                                <section class="list-group-item p-4 bg-white rounded-4 h-100">
                                    <h2 class="h5 mb-3">Gaben nach Abgabestatus</h2>
                                    <?php if ($gaben === []): ?>
                                        <div class="alert alert-light border mb-0">Noch keine Gaben abgegeben.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0" data-sortable-table>
                                                <thead>
                                                    <tr>
                                                        <th data-sort-type="text">Gabe</th>
                                                        <th class="text-end" data-sort-type="number">Anzahl</th>
                                                        <th class="text-end" data-sort-type="number">Wert</th>
                                                        <th class="text-end" data-sort-type="number">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($gaben as $gabe): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string) $gabe['name']) ?><div class="small text-body-secondary"><?= !empty($gabe['abgegeben']) ? 'Abgegeben' : 'Offen' ?></div></td>
                                                            <td class="text-end"><?= (int) $gabe['anzahl'] ?></td>
                                                            <td class="text-end"><?= htmlspecialchars($money((float) $gabe['preis'])) ?></td>
                                                            <td class="text-end fw-semibold"><?= htmlspecialchars($money((float) $gabe['total'])) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </section>
                            </div>
                        </div>

                        <section class="list-group-item p-4 bg-white rounded-4 mt-4">
                            <h2 class="h5 mb-3">Standblätter</h2>
                            <?php if ($standblaetter === []): ?>
                                <div class="alert alert-light border mb-0">Noch keine Standblätter vorhanden.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0" data-sortable-table>
                                        <thead>
                                            <tr>
                                                <th data-sort-type="number">Standblatt</th>
                                                <th data-sort-type="text">Schütze</th>
                                                <th data-sort-type="text">Verein</th>
                                                <th data-sort-type="number">Datum</th>
                                                <th class="text-end" data-sort-type="number">Kosten</th>
                                                <th data-sort-type="text">Gaben</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($standblaetter as $row): ?>
                                                <tr>
                                                    <td data-sort-value="<?= (int) $row['id'] ?>">
                                                        <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . (int) $row['id'])) ?>" class="text-decoration-none">
                                                            #<?= (int) $row['id'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= htmlspecialchars((string) ($row['name'] ?: 'Unbekannt')) ?></td>
                                                    <td><?= htmlspecialchars((string) ($row['verein'] ?: '-')) ?></td>
                                                    <td data-sort-value="<?= htmlspecialchars((string) (strtotime((string) ($row['datum'] ?? '')) ?: 0)) ?>"><?= htmlspecialchars((string) ($row['datum'] ?: '-')) ?></td>
                                                    <td class="text-end fw-semibold"><?= htmlspecialchars($money((float) $row['kosten'])) ?></td>
                                                    <td><?= $row['gaben_geprueft'] ? 'geprüft' : 'offen' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>

                        <section class="list-group-item p-4 bg-white rounded-4 mt-4">
                            <h2 class="h5 mb-3">Gaben-Details</h2>
                            <?php if ($abgaben === []): ?>
                                <div class="alert alert-light border mb-0">Noch keine Gaben-Details vorhanden.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0" data-sortable-table>
                                        <thead>
                                            <tr>
                                                <th data-sort-type="text">Gabe</th>
                                                <th data-sort-type="text">Stich</th>
                                                <th data-sort-type="text">Schütze</th>
                                                <th class="text-end" data-sort-type="number">Wert</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($abgaben as $abgabe): ?>
                                                <?php $name = trim((string) (($abgabe['vorname'] ?? '') . ' ' . ($abgabe['nachname'] ?? ''))); ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) $abgabe['name']) ?><div class="small text-body-secondary"><?= !empty($abgabe['abgegeben']) ? 'Abgegeben' : 'Offen' ?><?= ($abgabe['preis_quelle'] ?? '') === 'uebernommen' ? ' · Preis übernommen' : '' ?></div></td>
                                                    <td><?= htmlspecialchars((string) ($abgabe['stich_name'] ?? '-')) ?></td>
                                                    <td><?= htmlspecialchars($name !== '' ? $name : 'Unbekannt') ?></td>
                                                    <td class="text-end"><?= htmlspecialchars($money((float) $abgabe['preis'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
    <script src="<?= htmlspecialchars(Url::asset('js/table-sort.js')) ?>" defer></script>
</body>
</html>
