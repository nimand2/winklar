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
$finalStatus = $finalStatus ?? ['qualifiziert' => false, 'rang' => null, 'kategorie' => null, 'stich' => null];
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
    <?php \App\Core\View::partial('partials/navigation', ['navigation' => $navigation ?? []]); ?>
    <main id="main-content" tabindex="-1" class="container-fluid px-3 px-xl-4 px-xxl-5 py-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Abrechnen</div>
                                <h1 class="h2 mb-2">Standblatt abrechnen #<?= htmlspecialchars((string) $standblattId) ?></h1>
                                <?php if (!empty($standblatt['gaben_abgegeben'])): ?><span class="badge text-bg-success">Gaben Abgegeben</span><?php endif; ?>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars($name) ?> · <?= htmlspecialchars((string) $anlass['name_anlass']) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">


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
                                                            $batchFormId = 'schuesse-save-' . $rowIndex;
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
                                                                    Schüsse bearbeiten
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr class="collapse" id="<?= htmlspecialchars($collapseId) ?>">
                                                            <td colspan="<?= htmlspecialchars((string) $resultColspan) ?>" class="bg-light">
                                                                <div class="py-2">
                                                                    <div class="small fw-semibold mb-2">Schüsse bearbeiten</div>
                                                                    <form id="<?= htmlspecialchars($batchFormId) ?>" method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/schuesse')) ?>"></form>
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
                                                                                        $formId = $batchFormId;
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
                                                                                            <input type="hidden" name="schuesse[<?= (int) $editIndex ?>][id]" value="<?= (int) $schussId ?>" form="<?= htmlspecialchars($formId) ?>">
                                                                                                <input
                                                                                                    name="schuesse[<?= (int) $editIndex ?>][primaerwertung]"
                                                                                                    data-shot-value
                                                                                                    form="<?= htmlspecialchars($formId) ?>"
                                                                                                    type="number"
                                                                                                    step="0.01"
                                                                                                    min="0"
                                                                                                    class="form-control form-control-sm text-end"
                                                                                                    value="<?= $schuss !== null ? htmlspecialchars($formatNumber((float) ($schuss['primaerwertung'] ?? 0))) : '' ?>"
                                                                                                    <?= $isExistingSchuss ? 'required' : '' ?>
                                                                                                >
                                                                                        </td>
                                                                                        <td style="min-width: 12rem;">
                                                                                            <select name="schuesse[<?= (int) $editIndex ?>][externe_nummer]" class="form-select form-select-sm" form="<?= htmlspecialchars($formId) ?>" required>
                                                                                                <?php if ($externeNummer !== '' && !isset($scheiben[$externeNummer])): ?>
                                                                                                    <option value="<?= htmlspecialchars($externeNummer) ?>" selected>
                                                                                                        <?= htmlspecialchars($externeNummer) ?>
                                                                                                    </option>
                                                                                                <?php endif; ?>
                                                                                                <?php foreach ($scheiben as $nummer => $label): ?>
                                                                                                    <option value="<?= htmlspecialchars((string) $nummer) ?>" <?= (string) $nummer === $externeNummer ? 'selected' : '' ?>>
                                                                                                        <?= htmlspecialchars($label) ?>
                                                                                                    </option>
                                                                                                <?php endforeach; ?>
                                                                                            </select>
                                                                                        </td>
                                                                                        <td style="min-width: 12rem;">
                                                                                            <input
                                                                                                name="schuesse[<?= (int) $editIndex ?>][schuss_zeit]"
                                                                                                type="datetime-local"
                                                                                                class="form-control form-control-sm"
                                                                                                value="<?= $schuss !== null ? htmlspecialchars($formatDateTimeLocal($schuss['zeit'] ?? null)) : '' ?>"
                                                                                                form="<?= htmlspecialchars($formId) ?>"
                                                                                            >
                                                                                        </td>
                                                                                        <td>
                                                                                            <input
                                                                                                name="schuesse[<?= (int) $editIndex ?>][mouche]"
                                                                                                type="checkbox"
                                                                                                class="form-check-input"
                                                                                                value="1"
                                                                                                form="<?= htmlspecialchars($formId) ?>"
                                                                                                <?= !empty($schuss['mouche']) ? 'checked' : '' ?>
                                                                                            >
                                                                                        </td>
                                                                                        <td class="text-end">
                                                                                            <div class="d-flex justify-content-end gap-2">
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
                                                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                                                        <button type="submit" class="btn btn-primary btn-sm" form="<?= htmlspecialchars($batchFormId) ?>">Alle Schüsse speichern</button>
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
                                <div class="list-group-item p-3 bg-white rounded-4 mb-4">
                                    <h2 class="h5 mb-3">Final</h2>
                                    <div class="alert <?= !empty($finalStatus['qualifiziert']) ? 'alert-success' : 'alert-light border' ?> mb-2">
                                        <strong><?= !empty($finalStatus['qualifiziert']) ? 'Für den Final qualifiziert' : 'Aktuell nicht für den Final qualifiziert' ?></strong>
                                        <?php if ($finalStatus['rang'] !== null): ?>
                                            <div class="small mt-1"><?= htmlspecialchars((string) $finalStatus['stich']) ?> · <?= htmlspecialchars((string) $finalStatus['kategorie']) ?> · Rang <?= (int) $finalStatus['rang'] ?></div>
                                        <?php else: ?>
                                            <div class="small mt-1"><?= $finalStatus['stich'] !== null ? 'Noch kein klassiertes Resultat im Finalstich vorhanden.' : 'Für diesen Anlass ist kein Finalstich eingestellt.' ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="small text-body-secondary">Finalplätze: U18 <?= (int) ($anlass['final_anzahl_u18'] ?? 6) ?> · Ü18 <?= (int) ($anlass['final_anzahl_ue18'] ?? 6) ?>. Der Status richtet sich nach der aktuellen Rangliste und den Anlassregeln.</p>
                                    <a class="small d-block mb-3" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration')) ?>">Finalregeln einstellen</a>
                                    <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/final')) ?>">
                                        <label class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="final_teilnahme" value="1" <?= !empty($standblatt['final_teilnahme']) ? 'checked' : '' ?>>
                                            <span class="form-check-label">Ich möchte am Final teilnehmen</span>
                                        </label>
                                        <button type="submit" class="btn btn-outline-primary w-100">Finalteilnahme speichern</button>
                                    </form>
                                </div>
                                <div class="list-group-item p-3 bg-white rounded-4">
                                    <h2 class="h5 mb-3">Gaben und Auszahlung</h2>
                                    <?php if ($gabenVergleich === []): ?>
                                        <div class="alert alert-light border mb-0">Keine Gaben erreicht.</div>
                                    <?php else: ?>
                                        <?php $summen = $gabenSummen; $money = static fn ($value): string => number_format((float) $value, 2, '.', "'"); ?>
                                        <div class="alert alert-success">
                                            <div>Gabenwert gesamt: CHF <?= $money($summen['gesamt']) ?></div>
                                            <div>Bereits abgegeben: CHF <?= $money($summen['abgegeben']) ?></div>
                                            <div class="fw-semibold">Noch abzugeben: CHF <?= $money($summen['offen']) ?></div>
                                            <hr>
                                            <div>Barauszahlung gesamt: CHF <?= $money($summen['geld_gesamt']) ?></div>
                                            <div>Bereits ausgezahlt: CHF <?= $money($summen['geld_abgegeben']) ?></div>
                                            <div class="fw-bold">Jetzt auszuzahlen: CHF <?= $money($summen['geld_offen']) ?></div>
                                        </div>
                                        <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen')) ?>">
                                            <div class="list-group">
                                                <?php foreach ($gabenVergleich as $gruppe): ?>
                                                    <div class="list-group-item">
                                                        <div class="fw-semibold"><?= htmlspecialchars((string) $gruppe['bezeichnung']) ?> · <?= htmlspecialchars($formatNumber((float) $gruppe['total'])) ?> Punkte</div>
                                                        <?php foreach ($gruppe['gaben'] as $gabe): ?>
                                                            <div class="border-top mt-2 pt-2">
                                                                <input type="hidden" name="gaben[<?= (int) $gabe['stich_id'] ?>][<?= (int) $gabe['serie_nummer'] ?>][]" value="<?= (int) $gabe['gaben_id'] ?>">
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span><?= htmlspecialchars((string) $gabe['name']) ?></span>
                                                                    <strong>CHF <?= $money($gabe['preis']) ?></strong>
                                                                </div>
                                                                <?php if (!empty($gabe['abgegeben'])): ?>
                                                                    <input type="hidden" name="abgegeben[<?= (int) $gabe['stich_id'] ?>][<?= (int) $gabe['serie_nummer'] ?>][<?= (int) $gabe['gaben_id'] ?>]" value="1">
                                                                    <span class="badge text-bg-success"><?= !empty($gabe['ist_geld']) ? 'Bereits ausgezahlt' : 'Bereits abgegeben' ?></span>
                                                                    <label class="form-check mt-2 mb-0">
                                                                        <input class="form-check-input" type="checkbox" name="ruecknahme[<?= (int) $gabe['stich_id'] ?>][<?= (int) $gabe['serie_nummer'] ?>][<?= (int) $gabe['gaben_id'] ?>]" value="1">
                                                                        <span class="form-check-label"><?= !empty($gabe['ist_geld']) ? 'Rücknahme der Auszahlung bestätigen' : 'Rücknahme der Gabe bestätigen' ?></span>
                                                                    </label>
                                                                <?php else: ?>
                                                                    <label class="form-check mt-2 mb-0">
                                                                        <input class="form-check-input" type="checkbox" name="abgegeben[<?= (int) $gabe['stich_id'] ?>][<?= (int) $gabe['serie_nummer'] ?>][<?= (int) $gabe['gaben_id'] ?>]" value="1">
                                                                        <span class="form-check-label"><?= !empty($gabe['ist_geld']) ? 'Auszahlung bestätigen' : 'Abgabe bestätigen' ?></span>
                                                                    </label>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100 mt-3">Abgaben und Auszahlungen speichern</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                            <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen/druck')) ?>">Abrechnung Drucken</a>
                            <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-primary">
                                Zur Anlassübersicht
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
    <script>
        document.addEventListener('keydown', event => {
            if (event.key !== 'Enter' || event.isComposing || event.ctrlKey || event.altKey || event.metaKey) return;
            const input = event.target;
            if (!(input instanceof HTMLInputElement) || !input.matches('[data-shot-value]')) return;
            event.preventDefault();
            const form = input.form;
            if (!form) return;
            const values = Array.from(form.elements).filter(field =>
                field instanceof HTMLInputElement && field.matches('[data-shot-value]') && !field.disabled
            );
            const next = values[values.indexOf(input) + (event.shiftKey ? -1 : 1)];
            if (next) {
                next.focus();
                next.select();
            } else if (!event.shiftKey) {
                const button = Array.from(form.elements).find(field =>
                    field instanceof HTMLButtonElement && field.type === 'submit' && !field.disabled
                );
                button?.focus();
            }
        });
    </script>
</body>
</html>
