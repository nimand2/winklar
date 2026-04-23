<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$adresseId = (int) $adresse['id'];
$old = $old ?? [];
$name = trim((string) (($adresse['vorname'] ?? '') . ' ' . ($adresse['nachname'] ?? '')));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schütz lösen</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Lösen</div>
                                <h1 class="h2 mb-2">Neuer Schütz</h1>
                                <p class="muted-copy mb-0">
                                    <?= htmlspecialchars((string) $anlass['name_anlass']) ?>
                                </p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/schuetzen/neu')) ?>" class="btn btn-outline-secondary">
                                    Adresse wechseln
                                </a>
                                <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId)) ?>" class="btn btn-outline-secondary">
                                    Zurück zum Anlass
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Adresse</div>
                                    <div class="fw-semibold"><?= htmlspecialchars($name) ?></div>
                                    <div class="small text-body-secondary">
                                        ID <?= htmlspecialchars((string) $adresseId) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="list-group-item h-100 p-3 bg-white rounded-4">
                                    <div class="small text-body-secondary mb-1">Kontakt</div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars((string) ($adresse['email'] ?: 'Keine E-Mail')) ?>
                                    </div>
                                    <div class="small text-body-secondary">
                                        <?= htmlspecialchars((string) ($adresse['telefon'] ?: 'Kein Telefon')) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/neu')) ?>">
                            <input type="hidden" name="adresse_id" value="<?= htmlspecialchars((string) $adresseId) ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="datum" class="form-label">Datum</label>
                                    <input id="datum" name="datum" type="date" class="form-control" value="<?= htmlspecialchars((string) ($old['datum'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="kosten" class="form-label">Kosten</label>
                                    <input id="kosten" name="kosten" type="number" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars((string) ($old['kosten'] ?? '')) ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        Schütz für Anlass lösen
                                    </button>
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
