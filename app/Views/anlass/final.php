<?php

declare(strict_types=1);
use App\Core\Url;
$anlassId = (int) $anlass['id'];
?>
<!DOCTYPE html>
<html lang="de">
<head><?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Finalauswertung']); ?></head>
<body class="app-shell">
    <?php \App\Core\View::partial('partials/navigation', ['navigation' => $navigation ?? []]); ?>
<main id="main-content" tabindex="-1" class="container py-5">
    <div class="card dashboard-card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div><div class="brand-badge mb-3">Final</div><h1 class="h2">Finalauswertung</h1><p class="muted-copy mb-0"><?= htmlspecialchars((string) $anlass['name_anlass']) ?></p></div>
            <div class="d-flex flex-wrap align-items-start gap-2">

                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/konfiguration')) ?>">Finalregeln einstellen</a>
            </div>
        </div>
        <?php if ($final['stich'] === null): ?>
            <div class="alert alert-light border">Für diesen Anlass ist kein gültiger Finalstich eingestellt.</div>
        <?php else: ?>
            <p>Qualifikationsstich: <strong><?= htmlspecialchars((string) $final['stich']['name']) ?></strong>. <?= count($final['teilnehmer']) ?> Schützen sind qualifiziert und möchten teilnehmen.</p>
            <p class="small text-body-secondary">U18 und Ü18 führen separate Finale durch. Die Auswertung verwendet die aktuelle Rangliste und die gespeicherten Teilnahmewünsche. Nur qualifizierte Schützen mit Teilnahmewunsch werden gedruckt und exportiert. Absagen führen nicht automatisch zum Nachrücken.</p>
            <?php foreach ($final['kategorien'] as $key => $kategorie): ?>
                <section class="mb-4">
                    <h2 class="h5">Final <?= htmlspecialchars((string) $kategorie['label']) ?> · <?= (int) $kategorie['plaetze'] ?> Finalplätze</h2>
                    <?php $teilnehmerAnzahl = count(array_filter($kategorie['qualifizierte'], static fn (array $row): bool => $row['nimmt_teil'])); ?>
                    <p><?= $teilnehmerAnzahl ?> bestätigte Teilnehmer für diesen Final.</p>
                    <?php if ($teilnehmerAnzahl > 0): ?>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/final/' . $key . '/druck')) ?>">Standblätter <?= htmlspecialchars((string) $kategorie['label']) ?> drucken</a>
                            <a class="btn btn-outline-primary" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/final/' . $key . '/export')) ?>">JSON <?= htmlspecialchars((string) $kategorie['label']) ?> exportieren</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($kategorie['qualifizierte'] === []): ?>
                        <p class="text-body-secondary">Keine qualifizierten Schützen in dieser Kategorie.</p>
                    <?php else: ?>
                        <div class="table-responsive"><table class="table align-middle">
                            <thead><tr><th>Rang</th><th>Schütze</th><th>Verein</th><th>Standblatt</th><th>Summe beste 2</th><th>Teilnahme</th><th>Aktion</th></tr></thead>
                            <tbody><?php foreach ($kategorie['qualifizierte'] as $row): ?>
                                <tr>
                                    <td><?= (int) $row['rang'] ?></td><td><?= htmlspecialchars((string) $row['name']) ?></td><td><?= htmlspecialchars((string) ($row['verein'] ?: '-')) ?></td>
                                    <td>#<?= (int) $row['standblatt_id'] ?></td><td><?= htmlspecialchars((string) $row['summe_beste_zwei']) ?></td>
                                    <td><span class="badge <?= $row['nimmt_teil'] ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $row['nimmt_teil'] ? 'Möchte teilnehmen' : 'Nicht bestätigt' ?></span></td>
                                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . (int) $row['standblatt_id'] . '/abrechnen')) ?>">Teilnahme bearbeiten</a></td>
                                </tr>
                            <?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div></div>
</main>
<?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
