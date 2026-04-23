<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$old = $old ?? [];
$errors = $errors ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adressverwaltung</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
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
                                <h1 class="h2 mb-2">Schütz auswählen</h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars((string) $anlass['name_anlass']) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                    Zurück zum Anlass
                                </a>
                            </div>
                        </div>

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars((string) $errors[0]) ?>
                            </div>
                        <?php endif; ?>

                        <div class="row g-4">
                            <div class="col-12 col-lg-7">
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
                                                        href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/neu?adresse_id=' . (int) $adresse['id'])) ?>"
                                                        class="btn btn-primary"
                                                    >
                                                        Öffnen
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($adressen === []): ?>
                                    <div class="alert alert-light border mb-0">
                                        Es sind noch keine Adressen vorhanden.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 col-lg-5">
                                <div class="list-group-item p-4 bg-white rounded-4">
                                    <h2 class="h5 mb-3">Neue Adresse erstellen</h2>
                                    <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/schuetzen/neu')) ?>">
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
                                                <label for="firmen_anrede" class="form-label">Firma/Verein</label>
                                                <input id="firmen_anrede" name="firmen_anrede" class="form-control" value="<?= htmlspecialchars((string) ($old['firmen_anrede'] ?? '')) ?>">
                                            </div>
                                            <div class="col-12">
                                                <label for="strasse" class="form-label">Strasse</label>
                                                <input id="strasse" name="strasse" class="form-control" value="<?= htmlspecialchars((string) ($old['strasse'] ?? '')) ?>">
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
                                                <button type="submit" class="btn btn-primary w-100">
                                                    Neue Adresse erstellen
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
