<?php

declare(strict_types=1);

use App\Core\Url;

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kassen-Abrechnung</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
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
                                <div class="brand-badge mb-3">Kasse</div>
                                <h1 class="h2 mb-2">Kassen-Abrechnung <?= htmlspecialchars((string) $anlass['name_anlass']) ?></h1>
                                <p class="muted-copy mb-0">
                                    Einnahmen aus geloesten Standblaettern und Warenwert der abgegebenen Gaben.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2 no-print">
                                <button type="button" class="btn btn-primary" onclick="window.print()">Drucken</button>
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                    Zurueck zum Anlass
                                </a>
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
                                    <div class="small text-body-secondary mb-1">Gabenwert</div>
                                    <div class="fw-semibold fs-5"><?= htmlspecialchars($money($gabenTotal)) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Netto</div>
                                    <div class="fw-semibold fs-5"><?= htmlspecialchars($money($netto)) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Offene Gabenpruefung</div>
                                    <div class="fw-semibold fs-5"><?= (int) ($abrechnung['offene_gaben_pruefungen'] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-lg-6">
                                <section class="list-group-item p-4 bg-white rounded-4 h-100">
                                    <h2 class="h5 mb-3">Einnahmen nach Stich</h2>
                                    <?php if ($stichEinnahmen === []): ?>
                                        <div class="alert alert-light border mb-0">Noch keine Stiche geloest.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Stich</th>
                                                        <th class="text-end">Anzahl</th>
                                                        <th class="text-end">Preis</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stichEinnahmen as $row): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string) $row['name']) ?></td>
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
                                    <h2 class="h5 mb-3">Abgegebene Gaben</h2>
                                    <?php if ($gaben === []): ?>
                                        <div class="alert alert-light border mb-0">Noch keine Gaben abgegeben.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Gabe</th>
                                                        <th class="text-end">Anzahl</th>
                                                        <th class="text-end">Wert</th>
                                                        <th class="text-end">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($gaben as $gabe): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars((string) $gabe['name']) ?></td>
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
                            <h2 class="h5 mb-3">Standblaetter</h2>
                            <?php if ($standblaetter === []): ?>
                                <div class="alert alert-light border mb-0">Noch keine Standblaetter vorhanden.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Standblatt</th>
                                                <th>Schuetze</th>
                                                <th>Verein</th>
                                                <th>Datum</th>
                                                <th class="text-end">Kosten</th>
                                                <th>Gaben</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($standblaetter as $row): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . (int) $row['id'])) ?>" class="text-decoration-none">
                                                            #<?= (int) $row['id'] ?>
                                                        </a>
                                                    </td>
                                                    <td><?= htmlspecialchars((string) ($row['name'] ?: 'Unbekannt')) ?></td>
                                                    <td><?= htmlspecialchars((string) ($row['verein'] ?: '-')) ?></td>
                                                    <td><?= htmlspecialchars((string) ($row['datum'] ?: '-')) ?></td>
                                                    <td class="text-end fw-semibold"><?= htmlspecialchars($money((float) $row['kosten'])) ?></td>
                                                    <td><?= $row['gaben_geprueft'] ? 'geprueft' : 'offen' ?></td>
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
                                    <table class="table align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Gabe</th>
                                                <th>Stich</th>
                                                <th>Schuetze</th>
                                                <th class="text-end">Wert</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($abgaben as $abgabe): ?>
                                                <?php $name = trim((string) (($abgabe['vorname'] ?? '') . ' ' . ($abgabe['nachname'] ?? ''))); ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) $abgabe['name']) ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
