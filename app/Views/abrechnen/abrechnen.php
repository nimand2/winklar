<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$standblattId = (int) $standblatt['id'];
$name = trim((string) (($adresse['vorname'] ?? '') . ' ' . ($adresse['nachname'] ?? '')));
$auswertung = $auswertung ?? ['rows' => [], 'total' => 0, 'schussCount' => 0];
$rows = (array) ($auswertung['rows'] ?? []);
$maxSchuesse = 0;

foreach ($rows as $row) {
    $maxSchuesse = max($maxSchuesse, count((array) ($row['schuesse'] ?? [])), (int) ($row['erwartet'] ?? 0));
}

$maxSchuesse = min(max($maxSchuesse, 1), 20);

$formatNumber = static function (float $value): string {
    $rounded = round($value, 2);

    return floor($rounded) === $rounded ? (string) (int) $rounded : number_format($rounded, 2, '.', "'");
};
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standblatt abrechnen #<?= htmlspecialchars((string) $standblattId) ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xxl-11">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Abrechnen</div>
                                <h1 class="h2 mb-2">Standblatt abschliessen #<?= htmlspecialchars((string) $standblattId) ?></h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars($name) ?> · <?= htmlspecialchars((string) $anlass['name_anlass']) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId)) ?>" class="btn btn-outline-secondary">
                                    Zurück zum Standblatt
                                </a>
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen')) ?>" class="btn btn-outline-secondary">
                                    Standblatt auswählen
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Total</div>
                                    <div class="display-6 fw-semibold"><?= htmlspecialchars($formatNumber((float) ($auswertung['total'] ?? 0))) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Schüsse</div>
                                    <div class="display-6 fw-semibold"><?= htmlspecialchars((string) ($auswertung['schussCount'] ?? 0)) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Datum</div>
                                    <div class="display-6 fw-semibold fs-4">
                                        <?= htmlspecialchars((string) ($standblatt['datum'] ?: 'Offen')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-xl-8">
                                <div class="list-group-item p-3 bg-white rounded-4">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                        <div>
                                            <h2 class="h5 mb-1">Schussresultate</h2>
                                            <div class="small text-body-secondary">
                                                Visualisierung der gelösten Stiche mit vorhandenen Schussdaten.
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($rows === []): ?>
                                        <div class="alert alert-light border mb-0">
                                            Für dieses Standblatt sind noch keine Stiche oder Schussdaten vorhanden.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Bezeichnung</th>
                                                        <th class="text-end">Total</th>
                                                        <?php for ($shotNumber = 1; $shotNumber <= $maxSchuesse; $shotNumber++): ?>
                                                            <th class="text-center">- <?= htmlspecialchars((string) $shotNumber) ?> -</th>
                                                        <?php endfor; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($rows as $row): ?>
                                                        <?php $schuesseRow = (array) ($row['schuesse'] ?? []); ?>
                                                        <tr>
                                                            <td>
                                                                <div class="fw-semibold"><?= htmlspecialchars((string) $row['bezeichnung']) ?></div>
                                                                <?php if ((string) ($row['kurzname'] ?? '') !== ''): ?>
                                                                    <div class="small text-body-secondary"><?= htmlspecialchars((string) $row['kurzname']) ?></div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-end fw-semibold">
                                                                <?= htmlspecialchars($formatNumber((float) ($row['total'] ?? 0))) ?>
                                                            </td>
                                                            <?php for ($shotIndex = 0; $shotIndex < $maxSchuesse; $shotIndex++): ?>
                                                                <?php $schuss = $schuesseRow[$shotIndex] ?? null; ?>
                                                                <td class="text-center">
                                                                    <?php if ($schuss !== null): ?>
                                                                        <span class="<?= !empty($schuss['mouche']) ? 'fw-bold text-success' : '' ?>">
                                                                            <?= htmlspecialchars($formatNumber((float) $schuss['primaerwertung'])) ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="text-body-tertiary">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            <?php endfor; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-12 col-xl-4">
                                <div class="list-group-item p-3 bg-white rounded-4">
                                    <h2 class="h5 mb-3">Gabenvergleich</h2>

                                    <?php if ($gabenVergleich === []): ?>
                                        <div class="alert alert-light border mb-0">
                                            Es sind noch keine Gaben hinterlegt.
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group">
                                            <?php foreach ($gabenVergleich as $gabe): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars((string) $gabe['name']) ?></div>
                                                        <div class="small text-body-secondary">
                                                            Vergleichswert <?= htmlspecialchars($formatNumber((float) $gabe['limit'])) ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($gabe['erreicht'])): ?>
                                                        <span class="badge text-bg-success">erreicht</span>
                                                    <?php else: ?>
                                                        <span class="badge text-bg-secondary">
                                                            <?= htmlspecialchars($formatNumber(abs((float) $gabe['differenz']))) ?> fehlt
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-primary">
                                Abschliessen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
