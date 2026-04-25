<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Standblatt auswaehlen']); ?>
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Lösen</div>
                                <h1 class="h2 mb-2">Standblatt auswählen</h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars((string) $anlass['name_anlass']) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/schuetzen/neu')) ?>" class="btn btn-primary">
                                    Neuer Schütz
                                </a>
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                    Zurück zum Anlass
                                </a>
                            </div>
                        </div>

                        <?php if ($standblaetter === []): ?>
                            <div class="alert alert-light border mb-0">
                                Für diesen Anlass wurde noch kein Standblatt erstellt.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($standblaetter as $standblatt): ?>
                                    <?php
                                    $standblattId = (int) $standblatt['id'];
                                    $name = trim((string) (($standblatt['vorname'] ?? '') . ' ' . ($standblatt['nachname'] ?? '')));
                                    $verein = (string) (($standblatt['zusatz'] ?? '') ?: ($standblatt['firmen_anrede'] ?? ''));
                                    ?>
                                    <a
                                        href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId)) ?>"
                                        class="list-group-item list-group-item-action p-3"
                                    >
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
                                    </a>
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
