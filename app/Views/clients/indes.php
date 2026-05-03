<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed>|null $anlass */
/** @var array<int, array<string, mixed>> $adressen */
$anlass = $anlass ?? null;
$adressen = $adressen ?? [];
$errors = $errors ?? [];
$query = trim((string) ($query ?? ''));

if (is_array($anlass)) {
    $anlassId = (int) $anlass['id'];
    $basePath = '/anlass/' . $anlassId . '/schuetzen';
    $contextLabel = (string) $anlass['name_anlass'];
    $backPath = '/anlass/' . $anlassId;
    $backLabel = 'Zurück zum Anlass';
} else {
    $basePath = '/schuetzen';
    $contextLabel = 'Globale Personenverwaltung';
    $backPath = '/dashboard';
    $backLabel = 'Zurück zum Dashboard';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Adressverwaltung']); ?>
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Adressverwaltung</div>
                                <h1 class="h2 mb-2">Schützen verwalten</h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars($contextLabel) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app($basePath . '/neu')) ?>" class="btn btn-primary">
                                    Neuer Schütz
                                </a>
                                <a href="<?= htmlspecialchars(Url::app($backPath)) ?>" class="btn btn-outline-secondary">
                                    <?= htmlspecialchars($backLabel) ?>
                                </a>
                            </div>
                        </div>

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars((string) $errors[0]) ?>
                            </div>
                        <?php endif; ?>

                        <div class="row g-4">
                            <div class="col-12">
                                <form method="get" action="<?= htmlspecialchars(Url::app($basePath)) ?>" class="mb-3">
                                    <label for="adresssuche" class="form-label">Schütz suchen</label>
                                    <div class="input-group">
                                        <input
                                            id="adresssuche"
                                            name="q"
                                            class="form-control"
                                            value="<?= htmlspecialchars($query) ?>"
                                            placeholder="Name, Verein, Lizenz, E-Mail, Telefon oder Ort"
                                            autocomplete="off"
                                        >
                                        <button type="submit" class="btn btn-primary">Suchen</button>
                                        <?php if ($query !== ''): ?>
                                            <a href="<?= htmlspecialchars(Url::app($basePath)) ?>" class="btn btn-outline-secondary">
                                                Zurücksetzen
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>

                                <div class="list-group">
                                    <?php foreach ($adressen as $adresse): ?>
                                        <div class="list-group-item p-3">
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars(trim((string) (($adresse['vorname'] ?? '') . ' ' . ($adresse['nachname'] ?? '')))) ?>
                                                    </div>
                                                    <div class="small text-body-secondary">
                                                        <?= htmlspecialchars((string) ($adresse['strasse'] ?: 'Keine Strasse')) ?>,
                                                        <?= htmlspecialchars(trim((string) (($adresse['plz4'] ?? '') . ' ' . ($adresse['ortschaftsname'] ?? '')))) ?>
                                                    </div>
                                                    <div class="small text-body-secondary">
                                                        <?= htmlspecialchars((string) ($adresse['email'] ?: 'Keine E-Mail')) ?>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-start">
                                                    <a
                                                        href="<?= htmlspecialchars(Url::app($basePath . '/' . (int) $adresse['id'] . '/bearbeiten')) ?>"
                                                        class="btn btn-primary"
                                                    >
                                                        Bearbeiten
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($adressen === []): ?>
                                    <div class="alert alert-light border mb-0">
                                        <?php if ($query !== ''): ?>
                                            Keine Adresse zu "<?= htmlspecialchars($query) ?>" gefunden.
                                        <?php else: ?>
                                            Es sind noch keine Adressen vorhanden.
                                        <?php endif; ?>
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
