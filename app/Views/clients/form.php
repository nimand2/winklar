<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed>|null $anlass */
/** @var array<string, mixed>|null $adresse */
/** @var array<string, mixed> $old */
/** @var array<int, array<string, mixed>> $plzOptions */
$anlass = $anlass ?? null;
$adresse = $adresse ?? null;
$isEdit = is_array($adresse);
$old = $old ?? [];
$errors = $errors ?? [];
$plzOptions = $plzOptions ?? [];
$title = $isEdit ? 'Schütz bearbeiten' : 'Neue Adresse erstellen';

if (is_array($anlass)) {
    $anlassId = (int) $anlass['id'];
    $basePath = '/anlass/' . $anlassId . '/schuetzen';
    $contextLabel = (string) $anlass['name_anlass'];
} else {
    $basePath = '/schuetzen';
    $contextLabel = 'Globale Personenverwaltung';
}

if (is_array($adresse)) {
    $action = Url::app($basePath . '/' . (int) $adresse['id'] . '/bearbeiten');
} else {
    $action = Url::app($basePath . '/neu');
}

$submitLabel = $isEdit ? 'Änderungen speichern' : 'Adresse erstellen';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => $title]); ?>
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Adressverwaltung</div>
                                <h1 class="h2 mb-2"><?= htmlspecialchars($title) ?></h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars($contextLabel) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app($basePath)) ?>" class="btn btn-outline-secondary">
                                    Zurück zur Verwaltung
                                </a>
                            </div>
                        </div>

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars((string) $errors[0]) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= htmlspecialchars($action) ?>">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="anrede" class="form-label">Anrede</label>
                                    <input id="anrede" name="anrede" class="form-control" value="<?= htmlspecialchars((string) ($old['anrede'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="vorname" class="form-label">Vorname</label>
                                    <input id="vorname" name="vorname" class="form-control" value="<?= htmlspecialchars((string) ($old['vorname'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="nachname" class="form-label">Nachname</label>
                                    <input id="nachname" name="nachname" class="form-control" required value="<?= htmlspecialchars((string) ($old['nachname'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label for="firmen_anrede" class="form-label">Firma</label>
                                    <input id="firmen_anrede" name="firmen_anrede" class="form-control" value="<?= htmlspecialchars((string) ($old['firmen_anrede'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label for="zusatz" class="form-label">Zusatz</label>
                                    <input id="zusatz" name="zusatz" class="form-control" value="<?= htmlspecialchars((string) ($old['zusatz'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label for="strasse" class="form-label">Strasse</label>
                                    <input id="strasse" name="strasse" class="form-control" value="<?= htmlspecialchars((string) ($old['strasse'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="postfach" class="form-label">Postfach</label>
                                    <input id="postfach" name="postfach" class="form-control" value="<?= htmlspecialchars((string) ($old['postfach'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="nation" class="form-label">Nation</label>
                                    <input id="nation" name="nation" class="form-control" value="<?= htmlspecialchars((string) ($old['nation'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label for="plz_lookup" class="form-label">PLZ / Ort</label>
                                    <input
                                        id="plz_lookup"
                                        name="plz_lookup"
                                        class="form-control"
                                        list="plz-options"
                                        autocomplete="off"
                                        placeholder="z.B. 8000 Zürich"
                                        value="<?= htmlspecialchars((string) ($old['plz_lookup'] ?? '')) ?>"
                                    >
                                    <datalist id="plz-options">
                                        <?php foreach ($plzOptions as $plz): ?>
                                            <option value="<?= htmlspecialchars(trim((string) (($plz['plz4'] ?? '') . ' ' . ($plz['ortschaftsname'] ?? '')))) ?>">
                                                <?= htmlspecialchars((string) (($plz['kantonskuerzel'] ?? '') ?: '')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="telefon" class="form-label">Telefon</label>
                                    <input id="telefon" name="telefon" class="form-control" value="<?= htmlspecialchars((string) ($old['telefon'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="email" class="form-label">E-Mail</label>
                                    <input id="email" name="email" type="email" class="form-control" value="<?= htmlspecialchars((string) ($old['email'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="geburtsdatum" class="form-label">Geburtsdatum</label>
                                    <input id="geburtsdatum" name="geburtsdatum" type="date" class="form-control" value="<?= htmlspecialchars((string) ($old['geburtsdatum'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="lizenz" class="form-label">Lizenz</label>
                                    <input id="lizenz" name="lizenz" class="form-control" value="<?= htmlspecialchars((string) ($old['lizenz'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <label for="notiz" class="form-label">Notiz</label>
                                    <textarea id="notiz" name="notiz" class="form-control" rows="3"><?= htmlspecialchars((string) ($old['notiz'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <?= htmlspecialchars($submitLabel) ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
