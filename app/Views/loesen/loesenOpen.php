<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $anlass */
/** @var array<int, array<string, mixed>> $standblaetter */
$anlassId = (int) $anlass['id'];
$standblaetter = $standblaetter ?? [];
$nummer = $nummer ?? '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Standblätter']); ?>
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
                                <div class="brand-badge mb-3">Lösen</div>
                                <h1 class="h2 mb-2">Standblätter</h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars((string) $anlass['name_anlass']) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/adresse')) ?>" class="btn btn-primary">
                                    Standblatt erstellen
                                </a>

                            </div>
                        </div>

                        <form method="get" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen')) ?>" class="mb-3">
                            <label for="standblattnummer" class="form-label">Standblattnummer suchen</label>
                            <div class="input-group">
                                <input
                                    id="standblattnummer"
                                    name="nummer"
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]+"
                                    class="form-control"
                                    value="<?= htmlspecialchars($nummer) ?>"
                                    placeholder="Standblattnummer eingeben"
                                    autocomplete="off"
                                >
                                <button type="submit" class="btn btn-primary">Suchen</button>
                                <?php if ($nummer !== ''): ?>
                                    <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen')) ?>" class="btn btn-outline-secondary">Zurücksetzen</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <?php if ($standblaetter === []): ?>
                            <div class="alert alert-light border mb-0">
                                <?php if ($nummer !== ''): ?>
                                    Kein Standblatt mit der Nummer "<?= htmlspecialchars($nummer) ?>" für diesen Anlass gefunden.
                                <?php else: ?>
                                    Für diesen Anlass wurde noch kein Standblatt erstellt.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($standblaetter as $standblatt): ?>
                                    <?php
                                    $standblattId = (int) $standblatt['id'];
                                    $name = trim((string) (($standblatt['vorname'] ?? '') . ' ' . ($standblatt['nachname'] ?? '')));
                                    $verein = (string) (($standblatt['zusatz'] ?? '') ?: ($standblatt['firmen_anrede'] ?? ''));
                                    ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                            <div>
                                                <div class="fw-semibold">
                                                    Standblatt #<?= htmlspecialchars((string) $standblattId) ?>
                                                    <?= $name !== '' ? ' · ' . htmlspecialchars($name) : '' ?>
                                                </div>
                                                <div class="small text-body-secondary">
                                                    <?= htmlspecialchars($verein !== '' ? $verein : 'Kein Verein hinterlegt') ?>
                                                </div>
                                            </div>
                                            <div class="text-md-end small text-body-secondary">
                                                <div>Datum: <?= htmlspecialchars((string) ($standblatt['datum'] ?: 'Nicht hinterlegt')) ?></div>
                                                <div>Kosten: <?= htmlspecialchars((string) ($standblatt['kosten'] ?: 'Nicht hinterlegt')) ?></div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId)) ?>">Standblatt bearbeiten</a>
                                            <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen')) ?>">Standblatt abrechnen</a>
                                            <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/druck')) ?>">Standblatt drucken</a>
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
