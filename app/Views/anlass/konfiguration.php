<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$gaben = $gaben ?? [];
$stiche = $stiche ?? [];
$regeln = $regeln ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Anlass konfigurieren']); ?>
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Konfiguration</div>
                                <h1 class="h2 mb-2"><?= htmlspecialchars((string) $anlass['name_anlass']) ?></h1>
                                <p class="muted-copy mb-0">
                                    Anlassdaten, Stiche, Gaben und Auszeichnungsregeln zentral pflegen.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/bearbeiten')) ?>" class="btn btn-primary">
                                    Anlass bearbeiten
                                </a>
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                    Zurueck zum Anlass
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Kurzname</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($anlass['shortname_anlass'] ?: 'Kein Kurzname')) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Start</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($anlass['start_anlass'] ?: 'Nicht gesetzt')) ?></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Ende</div>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($anlass['end_anlass'] ?: 'Nicht gesetzt')) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-lg-5">
                                <div class="list-group-item p-4 bg-white rounded-4 mb-4">
                                    <h2 class="h5 mb-3">Neuer Stich</h2>
                                    <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/stiche')) ?>">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="stich_name" class="form-label">Name</label>
                                                <input id="stich_name" name="name" class="form-control" required>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label for="stich_short_name" class="form-label">Kurzname</label>
                                                <input id="stich_short_name" name="short_name" class="form-control">
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label for="stich_anzeige_id" class="form-label">Anzeige-ID</label>
                                                <input id="stich_anzeige_id" name="anzeige_id" class="form-control">
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label for="stich_scheibe" class="form-label">Scheibe</label>
                                                <input id="stich_scheibe" name="scheibe" class="form-control">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label for="stich_wertigkeit" class="form-label">Wertigkeit</label>
                                                <input id="stich_wertigkeit" name="wertigkeit" type="number" step="0.01" min="0" class="form-control">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label for="stich_anzahl_schuss" class="form-label">Schuss</label>
                                                <input id="stich_anzahl_schuss" name="anzahl_schuss" type="number" min="0" class="form-control">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label for="stich_anzahl_passen" class="form-label">Max. Passen</label>
                                                <input id="stich_anzahl_passen" name="anzahl_passen" type="number" min="0" class="form-control">
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label for="stich_preis" class="form-label">Preis</label>
                                                <input id="stich_preis" name="preis" type="number" step="0.01" min="0" class="form-control">
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label for="stich_id_disziplin" class="form-label">Disziplin-ID</label>
                                                <input id="stich_id_disziplin" name="id_disziplin" type="number" min="0" class="form-control">
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label for="stich_verbindung" class="form-label">Verbindung</label>
                                                <input id="stich_verbindung" name="verbindung" class="form-control">
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary w-100">Stich erstellen</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="list-group-item p-4 bg-white rounded-4 mb-4">
                                    <h2 class="h5 mb-3">Neue Gabe</h2>
                                    <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/gaben')) ?>">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="gabe_name" class="form-label">Name</label>
                                                <input id="gabe_name" name="name" class="form-control" required>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label for="gabe_punktwert" class="form-label">Punktwert</label>
                                                <input id="gabe_punktwert" name="punktwert" type="number" step="0.01" min="0" class="form-control" value="0">
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label for="gabe_preis" class="form-label">Preis</label>
                                                <input id="gabe_preis" name="preis" type="number" step="0.01" min="0" class="form-control" value="0">
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label for="gabe_anzahl" class="form-label">Anzahl</label>
                                                <input id="gabe_anzahl" name="anzahl" type="number" min="0" class="form-control" value="0">
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary w-100">Gabe erstellen</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="list-group-item p-4 bg-white rounded-4">
                                    <h2 class="h5 mb-3">Neue Gaben-Regel</h2>
                                    <?php if ($stiche === []): ?>
                                        <div class="alert alert-light border mb-0">
                                            Fuer diesen Anlass sind noch keine Stiche hinterlegt.
                                        </div>
                                    <?php elseif ($gaben === []): ?>
                                        <div class="alert alert-light border mb-0">
                                            Erstelle zuerst eine Gabe.
                                        </div>
                                    <?php else: ?>
                                        <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/gaben-regeln')) ?>">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="regel_stich" class="form-label">Stich</label>
                                                    <select id="regel_stich" name="stich_id" class="form-select" required>
                                                        <?php foreach ($stiche as $stich): ?>
                                                            <option value="<?= (int) $stich['id'] ?>"><?= htmlspecialchars((string) $stich['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label for="regel_gabe" class="form-label">Gabe</label>
                                                    <select id="regel_gabe" name="gaben_id" class="form-select" required>
                                                        <?php foreach ($gaben as $gabe): ?>
                                                            <option value="<?= (int) $gabe['id'] ?>"><?= htmlspecialchars((string) $gabe['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <label for="min_wert" class="form-label">Min. Wert</label>
                                                    <input id="min_wert" name="min_wert" type="number" step="0.01" class="form-control">
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <label for="max_wert" class="form-label">Max. Wert</label>
                                                    <input id="max_wert" name="max_wert" type="number" step="0.01" class="form-control">
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <label for="min_alter" class="form-label">Min. Alter</label>
                                                    <input id="min_alter" name="min_alter" type="number" min="0" class="form-control">
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <label for="max_alter" class="form-label">Max. Alter</label>
                                                    <input id="max_alter" name="max_alter" type="number" min="0" class="form-control">
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary w-100">Regel erstellen</button>
                                                </div>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-12 col-lg-7">
                                <h2 class="h5 mb-3">Stiche</h2>
                                <div class="list-group mb-4">
                                    <?php foreach ($stiche as $stich): ?>
                                        <div class="list-group-item p-3">
                                            <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/stiche/' . (int) $stich['id'])) ?>">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">Name</label>
                                                        <input name="name" class="form-control" required value="<?= htmlspecialchars((string) $stich['name']) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Kurzname</label>
                                                        <input name="short_name" class="form-control" value="<?= htmlspecialchars((string) ($stich['short_name'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Anzeige-ID</label>
                                                        <input name="anzeige_id" class="form-control" value="<?= htmlspecialchars((string) ($stich['anzeige_id'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Scheibe</label>
                                                        <input name="scheibe" class="form-control" value="<?= htmlspecialchars((string) ($stich['scheibe'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Preis</label>
                                                        <input name="preis" type="number" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars((string) ($stich['preis'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Wertigkeit</label>
                                                        <input name="wertigkeit" type="number" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars((string) ($stich['wertigkeit'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Schuss</label>
                                                        <input name="anzahl_schuss" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) ($stich['anzahl_schuss'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Max. Passen</label>
                                                        <input name="anzahl_passen" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) ($stich['anzahl_passen'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-6 col-md-2">
                                                        <label class="form-label">Disziplin-ID</label>
                                                        <input name="id_disziplin" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) ($stich['id_disziplin'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-12 col-md-2">
                                                        <label class="form-label">Verbindung</label>
                                                        <input name="verbindung" class="form-control" value="<?= htmlspecialchars((string) ($stich['verbindung'] ?? '')) ?>">
                                                    </div>
                                                    <div class="col-12 col-md-2 d-grid">
                                                        <button type="submit" class="btn btn-outline-primary">Speichern</button>
                                                    </div>
                                                </div>
                                            </form>
                                            <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/stiche/' . (int) $stich['id'] . '/loeschen')) ?>" class="mt-2">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Stich loeschen</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($stiche === []): ?>
                                    <div class="alert alert-light border">
                                        Es sind noch keine Stiche fuer diesen Anlass hinterlegt.
                                    </div>
                                <?php endif; ?>

                                <h2 class="h5 mb-3">Gaben</h2>
                                <div class="list-group mb-4">
                                    <?php foreach ($gaben as $gabe): ?>
                                        <div class="list-group-item p-3">
                                            <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/gaben/' . (int) $gabe['id'])) ?>">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">Name</label>
                                                        <input name="name" class="form-control" required value="<?= htmlspecialchars((string) $gabe['name']) ?>">
                                                    </div>
                                                    <div class="col-4 col-md-2">
                                                        <label class="form-label">Punktwert</label>
                                                        <input name="punktwert" type="number" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars((string) $gabe['punktwert']) ?>">
                                                    </div>
                                                    <div class="col-4 col-md-2">
                                                        <label class="form-label">Preis</label>
                                                        <input name="preis" type="number" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars((string) $gabe['preis']) ?>">
                                                    </div>
                                                    <div class="col-4 col-md-2">
                                                        <label class="form-label">Anzahl</label>
                                                        <input name="anzahl" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) $gabe['anzahl']) ?>">
                                                    </div>
                                                    <div class="col-12 col-md-2 d-grid">
                                                        <button type="submit" class="btn btn-outline-primary">Speichern</button>
                                                    </div>
                                                </div>
                                            </form>
                                            <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/gaben/' . (int) $gabe['id'] . '/loeschen')) ?>" class="mt-2">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Gabe loeschen</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($gaben === []): ?>
                                    <div class="alert alert-light border">
                                        Es sind noch keine Gaben vorhanden.
                                    </div>
                                <?php endif; ?>

                                <h2 class="h5 mb-3">Gaben-Regeln</h2>
                                <div class="list-group">
                                    <?php foreach ($regeln as $regel): ?>
                                        <div class="list-group-item p-3">
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars((string) $regel['stich_name']) ?> · <?= htmlspecialchars((string) ($regel['gaben_name'] ?? 'Keine Gabe')) ?>
                                                    </div>
                                                    <div class="small text-body-secondary">
                                                        Wert <?= htmlspecialchars((string) ($regel['min_wert'] ?? '-')) ?> bis <?= htmlspecialchars((string) ($regel['max_wert'] ?? '-')) ?>,
                                                        Alter <?= htmlspecialchars((string) ($regel['min_alter'] ?? '-')) ?> bis <?= htmlspecialchars((string) ($regel['max_alter'] ?? '-')) ?>
                                                    </div>
                                                </div>
                                                <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration/gaben-regeln/' . (int) $regel['id'] . '/loeschen')) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Regel loeschen</button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($regeln === []): ?>
                                    <div class="alert alert-light border mb-0">
                                        Es sind noch keine Gaben-Regeln fuer diesen Anlass hinterlegt.
                                    </div>
                                <?php endif; ?>
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
