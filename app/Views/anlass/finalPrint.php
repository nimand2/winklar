<?php

declare(strict_types=1);
use App\Core\Url;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final-Standblätter <?= htmlspecialchars((string) ($final['kategorie_label'] ?? '')) ?></title>
    <?php \App\Core\View::partial('loesen/standblattPrintStyles', ['gridGroups' => 1, 'gabenColumns' => 1]); ?>
    <style>
        @media print {
            .sheet + .sheet { break-before: page; page-break-before: always; }
        }
    </style>
</head>
<body>
    <div class="screen-actions">
        <?php if ($blaetter !== []): ?><button type="button" onclick="window.print()">Alle <?= count($blaetter) ?> Standblätter <?= htmlspecialchars((string) ($final['kategorie_label'] ?? '')) ?> drucken</button><?php endif; ?>
        <a href="<?= htmlspecialchars(Url::app('/anlass/' . (int) $anlass['id'] . '/final')) ?>">Zurück zur Finalauswertung</a>
    </div>
    <?php if ($blaetter === []): ?>
        <main class="sheet">Keine qualifizierten Schützen mit bestätigtem Teilnahmewunsch.</main>
    <?php else: ?>
        <?php foreach ($blaetter as $blatt): ?>
            <?php \App\Core\View::partial('loesen/standblattPrint', $blatt); ?>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
