<?php

declare(strict_types=1);

use App\Core\Url;

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Planung</div>
                                <h1 class="h2 mb-2"><?= htmlspecialchars($title) ?></h1>
                                <p class="muted-copy mb-0">
                                    Grunddaten fuer den Anlass erfassen und danach Gaben, Stiche und Regeln konfigurieren.
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($isEdit): ?>
                                    <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration')) ?>" class="btn btn-outline-secondary">
                                        Zurueck zur Konfiguration
                                    </a>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars(Url::app('/anlass')) ?>" class="btn btn-outline-secondary">
                                        Zurueck zur Auswahl
                                    </a>
                                <?php endif; ?>
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
                                    <?php if ($isEdit): ?>
                                        <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                            Zum Anlass
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
