<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $anlass */
/** @var array<int, array<string, mixed>> $adressen */
$anlassId = (int) $anlass['id'];
$adressen = $adressen ?? [];
$query = trim((string) ($query ?? ''));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Standblatt erstellen']); ?>
</head>
<body class="app-shell">
    <?php \App\Core\View::partial('partials/navigation', ['navigation' => $navigation ?? []]); ?>
    <main id="main-content" tabindex="-1" class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Standblatt</div>
                                <h1 class="h2 mb-2">Standblatt erstellen</h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars((string) $anlass['name_anlass']) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/schuetzen/neu')) ?>" class="btn btn-primary">
                                    Schützen erfassen
                                </a>

                            </div>
                        </div>

                        <p class="small text-body-secondary">Schritt 1 von 2: Vorhandene Schützen auswählen oder neue Schützen erfassen. Danach die Stiche für das Standblatt wählen.</p>

                        <form method="get" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/adresse')) ?>" class="mb-3">
                            <label for="adresssuche" class="form-label">Schützen suchen</label>
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
                                    <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/adresse')) ?>" class="btn btn-outline-secondary">
                                        Zurücksetzen
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <?php if ($adressen === []): ?>
                            <div class="alert alert-light border mb-0">
                                <?php if ($query !== ''): ?>
                                    Keine Adresse zu "<?= htmlspecialchars($query) ?>" gefunden.
                                <?php else: ?>
                                    Es sind noch keine Adressen vorhanden.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($adressen as $adresse): ?>
                                    <?php
                                    $adresseId = (int) $adresse['id'];
                                    $name = trim((string) (($adresse['vorname'] ?? '') . ' ' . ($adresse['nachname'] ?? '')));
                                    $verein = (string) (($adresse['zusatz'] ?? '') ?: ($adresse['firmen_anrede'] ?? ''));
                                    ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($name !== '' ? $name : 'Adresse #' . $adresseId) ?>
                                                </div>
                                                <div class="small text-body-secondary">
                                                    <?= htmlspecialchars($verein !== '' ? $verein : 'Kein Verein hinterlegt') ?>
                                                </div>
                                                <div class="small text-body-secondary">
                                                    <?= htmlspecialchars((string) ($adresse['email'] ?: 'Keine E-Mail')) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start">
                                                <a
                                                    href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/neu?adresse_id=' . $adresseId)) ?>"
                                                    class="btn btn-primary"
                                                >
                                                    Auswählen
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
