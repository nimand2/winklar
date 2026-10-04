<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $anlass */
/** @var array<string, mixed> $standblatt */
/** @var array<string, mixed> $adresse */
/** @var array{rows?: array<int, array<string, mixed>>, total?: int|float|string, schussCount?: int|string} $auswertung */
/** @var array<int, array<string, mixed>> $gabenVergleich */
$anlassId = (int) $anlass['id'];
$standblattId = (int) $standblatt['id'];
$name = trim((string) (($adresse['vorname'] ?? '') . ' ' . ($adresse['nachname'] ?? '')));
$auswertung = $auswertung ?? ['rows' => [], 'total' => 0, 'schussCount' => 0];
$gabenVergleich = $gabenVergleich ?? [];
$rows = (array) ($auswertung['rows'] ?? []);
$maxSchuesse = 0;
$stiche = (array) ($stiche ?? []);
$scheiben = [];

foreach ($rows as $row) {
    $maxSchuesse = max($maxSchuesse, count((array) ($row['schuesse'] ?? [])), (int) ($row['erwartet'] ?? 0));
}

foreach ($stiche as $stich) {
    $externeNummer = trim((string) ($stich['anzeige_id'] ?? ''));

    if ($externeNummer === '') {
        continue;
    }

    $scheiben[$externeNummer] = trim((string) ($stich['name'] ?? 'Stich')) . ' (' . $externeNummer . ')';
}

$maxSchuesse = min(max($maxSchuesse, 1), 20);
$resultColspan = $maxSchuesse + 3;

$formatNumber = static function (float $value): string {
    $rounded = round($value, 2);

    return floor($rounded) === $rounded ? (string) (int) $rounded : number_format($rounded, 2, '.', "'");
};

$formatDateTimeLocal = static function (mixed $value): string {
    $timestamp = strtotime((string) $value);

    return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
};
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Standblatt abrechnen #' . $standblattId]); ?>
</head>
<body class="app-shell">
    <main class="container-fluid px-3 px-xl-4 px-xxl-5 py-5">
        <div class="row justify-content-center">
            <div class="col-12">
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
                                <a
                                    href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/druck')) ?>"
                                    class="btn btn-primary"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Abrechnung drucken
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
                                    <form
                                        method="post"
                                        action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/datum')) ?>"
                                        class="d-flex gap-2 align-items-center"
                                    >
                                        <input
                                            name="datum"
                                            type="date"
                                            class="form-control"
                                            value="<?= htmlspecialchars((string) ($standblatt['datum'] ?? '')) ?>"
                                            aria-label="Datum"
                                        >
                                        <button type="submit" class="btn btn-outline-primary">
                                            Speichern
                                        </button>
                                    </form>
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
                                                        <th class="text-end">Aktion</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($rows as $rowIndex => $row): ?>
                                                        <?php
                                                            $schuesseRow = (array) ($row['schuesse'] ?? []);
                                                            $collapseId = 'stich-edit-' . $rowIndex;
                                                            $rowExterneNummer = (string) ($row['externe_nummer'] ?? '');
                                                            $expectedSlots = max(1, (int) ($row['anzahl_schuss'] ?? 0), count($schuesseRow));
                                                        ?>
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
                                                            <td class="text-end">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-primary btn-sm"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#<?= htmlspecialchars($collapseId) ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?= htmlspecialchars($collapseId) ?>"
                                                                >
                                                                    Edit
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr class="collapse" id="<?= htmlspecialchars($collapseId) ?>">
                                                            <td colspan="<?= htmlspecialchars((string) $resultColspan) ?>" class="bg-light">
                                                                <div class="py-2">
                                                                    <div class="small fw-semibold mb-2">Schüsse bearbeiten</div>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm align-middle mb-0">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Schuss</th>
                                                                                    <th class="text-end">Wert</th>
                                                                                    <th>Scheibe</th>
                                                                                    <th>Zeit</th>
                                                                                    <th>Mouche</th>
                                                                                    <th class="text-end">Aktion</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php for ($editIndex = 0; $editIndex < $expectedSlots; $editIndex++): ?>
                                                                                    <?php
                                                                                        $schuss = $schuesseRow[$editIndex] ?? null;
                                                                                        $schussId = (int) ($schuss['id'] ?? 0);
                                                                                        $isExistingSchuss = $schussId > 0;
                                                                                        $formId = ($isExistingSchuss ? 'schuss-update-' . $schussId : 'schuss-insert-' . $rowIndex . '-' . $editIndex);
                                                                                        $formAction = $isExistingSchuss
                                                                                            ? Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/schuss/' . $schussId)
                                                                                            : Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/schuss');
                                                                                        $externeNummer = (string) ($schuss['externe_nummer'] ?? $rowExterneNummer);
                                                                                    ?>
                                                                                    <tr>
                                                                                        <td>
                                                                                            <div class="fw-semibold"><?= htmlspecialchars((string) ($editIndex + 1)) ?></div>
                                                                                            <div class="small text-body-secondary">
                                                                                                <?= $isExistingSchuss ? '#' . htmlspecialchars((string) $schussId) : 'leer' ?>
                                                                                            </div>
                                                                                        </td>
                                                                                        <td class="text-end" style="min-width: 7rem;">
                                                                                            <form
                                                                                                id="<?= htmlspecialchars($formId) ?>"
                                                                                                method="post"
                                                                                                action="<?= htmlspecialchars($formAction) ?>"
                                                                                            >
                                                                                                <input
                                                                                                    name="primaerwertung"
                                                                                                    type="number"
                                                                                                    step="0.01"
                                                                                                    min="0"
                                                                                                    class="form-control form-control-sm text-end"
                                                                                                    value="<?= $schuss !== null ? htmlspecialchars($formatNumber((float) ($schuss['primaerwertung'] ?? 0))) : '' ?>"
                                                                                                    required
                                                                                                >
                                                                                            </form>
                                                                                        </td>
                                                                                        <td style="min-width: 12rem;">
                                                                                            <select name="externe_nummer" class="form-select form-select-sm" form="<?= htmlspecialchars($formId) ?>" required>
                                                                                                <?php if ($externeNummer !== '' && !isset($scheiben[$externeNummer])): ?>
                                                                                                    <option value="<?= htmlspecialchars($externeNummer) ?>" selected>
                                                                                                        <?= htmlspecialchars($externeNummer) ?>
                                                                                                    </option>
                                                                                                <?php endif; ?>
                                                                                                <?php foreach ($scheiben as $nummer => $label): ?>
                                                                                                    <option value="<?= htmlspecialchars((string) $nummer) ?>" <?= $nummer === $externeNummer ? 'selected' : '' ?>>
                                                                                                        <?= htmlspecialchars($label) ?>
                                                                                                    </option>
                                                                                                <?php endforeach; ?>
                                                                                            </select>
                                                                                        </td>
                                                                                        <td style="min-width: 12rem;">
                                                                                            <input
                                                                                                name="schuss_zeit"
                                                                                                type="datetime-local"
                                                                                                class="form-control form-control-sm"
                                                                                                value="<?= $schuss !== null ? htmlspecialchars($formatDateTimeLocal($schuss['zeit'] ?? null)) : '' ?>"
                                                                                                form="<?= htmlspecialchars($formId) ?>"
                                                                                            >
                                                                                        </td>
                                                                                        <td>
                                                                                            <input
                                                                                                name="mouche"
                                                                                                type="checkbox"
                                                                                                class="form-check-input"
                                                                                                value="1"
                                                                                                form="<?= htmlspecialchars($formId) ?>"
                                                                                                <?= !empty($schuss['mouche']) ? 'checked' : '' ?>
                                                                                            >
                                                                                        </td>
                                                                                        <td class="text-end">
                                                                                            <div class="d-flex justify-content-end gap-2">
                                                                                                <button type="submit" class="btn btn-outline-primary btn-sm" form="<?= htmlspecialchars($formId) ?>">
                                                                                                    <?= $isExistingSchuss ? 'Speichern' : 'Einfügen' ?>
                                                                                                </button>
                                                                                                <?php if ($isExistingSchuss): ?>
                                                                                                    <form
                                                                                                        method="post"
                                                                                                        action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/schuss/' . $schussId . '/loeschen')) ?>"
                                                                                                    >
                                                                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                                                            Löschen
                                                                                                        </button>
                                                                                                    </form>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                                <?php endfor; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </td>
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
                                            Für die gelösten Stiche sind noch keine Gabenregeln hinterlegt.
                                        </div>
                                    <?php else: ?>
                                        <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen')) ?>">
                                            <div class="list-group">
                                                <?php foreach ($gabenVergleich as $gruppe): ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                                            <div>
                                                                <div class="fw-semibold"><?= htmlspecialchars((string) $gruppe['bezeichnung']) ?></div>
                                                                <div class="small text-body-secondary">
                                                                    Total <?= htmlspecialchars($formatNumber((float) $gruppe['total'])) ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <?php if (($gruppe['gaben'] ?? []) === []): ?>
                                                            <div class="small text-body-secondary">Keine Gaben für diesen Stich hinterlegt.</div>
                                                        <?php else: ?>
                                                            <div class="d-grid gap-2">
                                                                <?php foreach ((array) $gruppe['gaben'] as $gabe): ?>
                                                                    <?php $inputId = 'gabe-' . (int) $gabe['stich_id'] . '-' . (int) $gabe['gaben_id']; ?>
                                                                    <label class="form-check d-flex align-items-start gap-2 mb-0">
                                                                        <input
                                                                            id="<?= htmlspecialchars($inputId) ?>"
                                                                            class="form-check-input mt-1"
                                                                            type="checkbox"
                                                                            name="gaben[<?= htmlspecialchars((string) $gabe['stich_id']) ?>][]"
                                                                            value="<?= htmlspecialchars((string) $gabe['gaben_id']) ?>"
                                                                            <?= !empty($gabe['selected']) ? 'checked' : '' ?>
                                                                            <?= empty($gabe['erreicht']) ? 'disabled' : '' ?>
                                                                        >
                                                                        <span class="form-check-label flex-grow-1">
                                                                            <span class="d-flex justify-content-between gap-2">
                                                                                <span class="fw-semibold"><?= htmlspecialchars((string) $gabe['name']) ?></span>
                                                                                <span class="badge <?= !empty($gabe['erreicht']) ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                                                    <?= !empty($gabe['erreicht']) ? 'erreicht' : htmlspecialchars($formatNumber(abs((float) $gabe['differenz']))) . ' fehlt' ?>
                                                                                </span>
                                                                            </span>
                                                                            <span class="small text-body-secondary d-block">
                                                                                Anzahl <?= htmlspecialchars((string) $gabe['anzahl']) ?> · ab <?= htmlspecialchars($formatNumber((float) $gabe['limit'])) ?> Pkt.
                                                                            </span>
                                                                        </span>
                                                                    </label>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100 mt-3">
                                                Gaben speichern
                                            </button>
                                        </form>
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

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
