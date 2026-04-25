<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$adresseId = (int) $adresse['id'];
$old = $old ?? [];
$errors = $errors ?? [];
$selectedStichIds = array_map('intval', (array) ($old['stich_ids'] ?? []));
$stichCounts = (array) ($old['stich_counts'] ?? []);
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

                        <?php if ($errors !== []): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars((string) $errors[0]) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/neu')) ?>">
                            <input type="hidden" name="adresse_id" value="<?= htmlspecialchars((string) $adresseId) ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="datum" class="form-label">Datum</label>
                                    <input id="datum" name="datum" type="date" class="form-control" value="<?= htmlspecialchars((string) ($old['datum'] ?? '')) ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="kosten" class="form-label">Kosten</label>
                                    <input id="kosten" name="kosten" type="number" step="0.01" min="0" class="form-control" value="<?= htmlspecialchars((string) ($old['kosten'] ?? '')) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <div class="list-group-item p-3 bg-white rounded-4">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                            <div>
                                                <h2 class="h5 mb-1">Stiche auswählen</h2>
                                                <div class="small text-body-secondary">
                                                    Wähle die Stiche, die auf dieses Standblatt gehören.
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($stiche === []): ?>
                                            <div class="alert alert-light border mb-0">
                                                Für diesen Anlass sind noch keine Stiche hinterlegt.
                                            </div>
                                        <?php else: ?>
                                            <div class="row g-2">
                                                <?php foreach ($stiche as $stich): ?>
                                                    <?php $stichId = (int) $stich['id']; ?>
                                                    <?php $anzahlStiche = max(1, (int) ($stichCounts[$stichId] ?? 1)); ?>
                                                    <div class="col-12 col-md-6">
                                                        <label class="list-group-item h-100 p-3">
                                                            <div class="form-check">
                                                                <input
                                                                    class="form-check-input"
                                                                    type="checkbox"
                                                                    name="stich_ids[]"
                                                                    value="<?= htmlspecialchars((string) $stichId) ?>"
                                                                    id="stich-<?= htmlspecialchars((string) $stichId) ?>"
                                                                    data-stich-price="<?= htmlspecialchars((string) ($stich['preis'] ?? 0)) ?>"
                                                                    <?= in_array($stichId, $selectedStichIds, true) ? 'checked' : '' ?>
                                                                >
                                                                <span class="form-check-label fw-semibold">
                                                                    <?= htmlspecialchars((string) $stich['name']) ?>
                                                                </span>
                                                            </div>
                                                            <div class="mt-3">
                                                                <label for="stich-count-<?= htmlspecialchars((string) $stichId) ?>" class="form-label small text-body-secondary">
                                                                    Anzahl Stiche
                                                                </label>
                                                                <input
                                                                    id="stich-count-<?= htmlspecialchars((string) $stichId) ?>"
                                                                    name="stich_counts[<?= htmlspecialchars((string) $stichId) ?>]"
                                                                    type="number"
                                                                    min="1"
                                                                    step="1"
                                                                    class="form-control"
                                                                    value="<?= htmlspecialchars((string) $anzahlStiche) ?>"
                                                                >
                                                            </div>
                                                            <div class="small text-body-secondary mt-2">
                                                                <?php if (!empty($stich['short_name'])): ?>
                                                                    <?= htmlspecialchars((string) $stich['short_name']) ?> ·
                                                                <?php endif; ?>
                                                                <?= htmlspecialchars((string) ($stich['anzahl_schuss'] ?: '')) ?> Schuss
                                                                <?php if ($stich['preis'] !== null): ?>
                                                                    · CHF <?= htmlspecialchars((string) $stich['preis']) ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
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
    <script>
        (() => {
            const kostenInput = document.querySelector('#kosten');
            const stichInputs = Array.from(document.querySelectorAll('input[name="stich_ids[]"]'));

            if (!kostenInput || stichInputs.length === 0) {
                return;
            }

            const calculateKosten = () => {
                const total = stichInputs.reduce((sum, checkbox) => {
                    if (!checkbox.checked) {
                        return sum;
                    }

                    const countInput = document.querySelector(`[name="stich_counts[${checkbox.value}]"]`);
                    const count = Math.max(1, parseInt(countInput?.value || '1', 10));
                    const price = parseFloat((checkbox.dataset.stichPrice || '0').replace(',', '.'));

                    return sum + (count * price);
                }, 0);

                kostenInput.value = total.toFixed(2);
            };

            stichInputs.forEach((checkbox) => {
                checkbox.addEventListener('change', calculateKosten);
                document.querySelector(`[name="stich_counts[${checkbox.value}]"]`)?.addEventListener('input', calculateKosten);
            });
            calculateKosten();
        })();
    </script>
</body>
</html>
