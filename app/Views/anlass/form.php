<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $anlass */
/** @var array<string, mixed> $old */
$anlass = $anlass ?? [];
$mode = (string) ($mode ?? 'create');
$isEdit = $mode === 'edit';
$old = $old ?? [];
$errors = $errors ?? [];
$anlassId = (int) ($anlass['id'] ?? 0);
$title = $isEdit ? 'Anlass bearbeiten' : 'Anlass erstellen';
$action = $isEdit ? Url::app('/anlass/' . $anlassId . '/bearbeiten') : Url::app('/anlass/neu');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => $title]); ?>
</head>
<body class="app-shell">
    <?php \App\Core\View::partial('partials/navigation', ['navigation' => $navigation ?? []]); ?>
    <main id="main-content" tabindex="-1" class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Planung</div>
                                <h1 class="h2 mb-2"><?= htmlspecialchars($title) ?></h1>
                                <p class="muted-copy mb-0">
                                    Grunddaten für den Anlass erfassen und danach Gaben, Stiche und Regeln konfigurieren.
                                </p>
                            </div>


                        </div>

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars((string) $errors[0]) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= htmlspecialchars($action) ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="name_anlass" class="form-label">Name</label>
                                    <input
                                        id="name_anlass"
                                        name="name_anlass"
                                        class="form-control"
                                        required
                                        value="<?= htmlspecialchars((string) ($old['name_anlass'] ?? '')) ?>"
                                    >
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="shortname_anlass" class="form-label">Kurzname</label>
                                    <input
                                        id="shortname_anlass"
                                        name="shortname_anlass"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($old['shortname_anlass'] ?? '')) ?>"
                                    >
                                </div>

                                <div class="col-12 col-md-3">
                                    <label for="start_anlass" class="form-label">Start</label>
                                    <input
                                        id="start_anlass"
                                        name="start_anlass"
                                        type="date"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($old['start_anlass'] ?? '')) ?>"
                                    >
                                </div>

                                <div class="col-12 col-md-3">
                                    <label for="end_anlass" class="form-label">Ende</label>
                                    <input
                                        id="end_anlass"
                                        name="end_anlass"
                                        type="date"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) ($old['end_anlass'] ?? '')) ?>"
                                    >
                                </div>

                                <div class="col-12 d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <?= $isEdit ? 'Anlass speichern' : 'Anlass erstellen' ?>
                                    </button>
                                    <a href="<?= htmlspecialchars(Url::app($isEdit ? '/anlass/' . $anlassId . '/konfiguration' : '/anlass')) ?>" class="btn btn-outline-secondary">Abbrechen</a>
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
