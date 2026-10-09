<?php

declare(strict_types=1);

use App\Core\Url;

/** @var array<string, mixed> $anlass */
/** @var array<string, mixed> $standblatt */
/** @var array<string, mixed> $adresse */
/** @var array<int, array<string, mixed>> $stiche */
/** @var array<int, array<string, mixed>> $gaben */
$anlassId = (int) $anlass['id'];
$standblattId = (int) $standblatt['id'];
$stiche = $stiche ?? [];
$name = trim((string) (($adresse['nachname'] ?? '') . ' ' . ($adresse['vorname'] ?? '')));
$vorname = trim((string) ($adresse['vorname'] ?? ''));
$nachname = trim((string) ($adresse['nachname'] ?? ''));
$ort = trim((string) (($adresse['plz4'] ?? '') . ' ' . ($adresse['ortschaftsname'] ?? '')));
$geburtsjahr = '';

if (!empty($adresse['geburtsdatum'])) {
    $geburtsjahr = substr((string) $adresse['geburtsdatum'], 0, 4);
}

$alterskategorie = 'Ü18';
$anlassDatum = trim((string) (($anlass['start_anlass'] ?? '') ?: ($anlass['end_anlass'] ?? '')));
if ($geburtsjahr !== '' && $anlassDatum !== '') {
    try {
        $anlassJahr = (int) (new \DateTimeImmutable($anlassDatum))->format('Y');
        $alterskategorie = $anlassJahr - (int) $geburtsjahr < 18 ? 'U18' : 'Ü18';
    } catch (\Throwable) {
        // Wie in der Rangliste bleiben fehlende oder ungueltige Anlassdaten in Ü18.
    }
}

$kosten = (float) ($standblatt['kosten'] ?? 0);
$formatMoney = static fn (float $value): string => number_format($value, 2, '.', "'");
$visibleStiche = array_slice($stiche, 0, 3);
$printGroups = [];

foreach ($stiche as $stich) {
    $anzahlStiche = max(1, (int) ($stich['anzahl_stiche'] ?? 1));
    $anzahlSchuss = max(1, (int) ($stich['anzahl_schuss'] ?? 1));
    $columns = max(1, min(4, (int) ceil($anzahlSchuss / 5)));

    for ($stichNumber = 1; $stichNumber <= $anzahlStiche; $stichNumber++) {
        $printGroups[] = [
            'name' => (string) ($stich['name'] ?? 'Stich'),
            'short_name' => (string) ($stich['short_name'] ?? ''),
            'preis' => (float) ($stich['preis'] ?? 0),
            'schuss' => $anzahlSchuss,
            'columns' => $columns,
        ];
    }
}

$gridGroups = max(1, count($printGroups));
$gaben = (array) ($gaben ?? []);
$gabenColumns = max(1, count($gaben));
$barcodeError = $standblattId > 999998
    ? 'Fehler: Standblattnummer grösser 999998. SIUS-Barcode kann nicht erzeugt werden.'
    : '';
$barcodeNumber = '';

if ($barcodeError === '') {
    $barcodeBase = (10000000 + $standblattId) * 100;
    $barcodeNumber = (string) ($barcodeBase + (97 - ($barcodeBase % 97)));
}

$barcodeSvg = static function (string $digits): string {
    if (strlen($digits) % 2 !== 0) {
        $digits = '0' . $digits;
    }

    $patterns = [
        '0' => 'nnwwn',
        '1' => 'wnnnw',
        '2' => 'nwnnw',
        '3' => 'wwnnn',
        '4' => 'nnwnw',
        '5' => 'wnwnn',
        '6' => 'nwwnn',
        '7' => 'nnnww',
        '8' => 'wnnwn',
        '9' => 'nwnwn',
    ];
    $bars = [];
    $x = 8;
    $narrow = 1;
    $wide = 3;
    $height = 42;
    $addBar = static function (array &$bars, int &$x, int $width, bool $black) use ($height): void {
        if ($black) {
            $bars[] = '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '"/>';
        }

        $x += $width;
    };

    foreach ([true, false, true, false] as $black) {
        $addBar($bars, $x, $narrow, $black);
    }

    for ($index = 0; $index < strlen($digits); $index += 2) {
        $barPattern = $patterns[$digits[$index]];
        $spacePattern = $patterns[$digits[$index + 1]];

        for ($position = 0; $position < 5; $position++) {
            $addBar($bars, $x, $barPattern[$position] === 'w' ? $wide : $narrow, true);
            $addBar($bars, $x, $spacePattern[$position] === 'w' ? $wide : $narrow, false);
        }
    }

    $addBar($bars, $x, $wide, true);
    $addBar($bars, $x, $narrow, false);
    $addBar($bars, $x, $narrow, true);
    $width = $x + 8;

    return '<svg class="barcode-svg" viewBox="0 0 ' . $width . ' 58" role="img" aria-label="Standblatt Barcode ' . htmlspecialchars($digits) . '">'
        . '<g fill="#000">' . implode('', $bars) . '</g>'
        . '<text x="' . ($width / 2) . '" y="55" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="9">'
        . htmlspecialchars($digits)
        . '</text></svg>';
};
?>
<?php if (empty($batchPrint)): ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standblatt drucken #<?= htmlspecialchars((string) $standblattId) ?></title>
    <?php \App\Core\View::partial('loesen/standblattPrintStyles', ['gridGroups' => $gridGroups, 'gabenColumns' => $gabenColumns]); ?>
</head>
<body>
    <div class="screen-actions">
        <button type="button" onclick="window.print()">Drucken</button>
        <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId)) ?>">Zurück zum Standblatt</a>
    </div>

<?php endif; ?>
    <main class="sheet">
        <header class="header">
            <div>
                <div class="club"><?= htmlspecialchars((string) (($anlass['shortname_anlass'] ?? '') ?: 'Sportschützen')) ?></div>
                <div class="title"><?= htmlspecialchars((string) $anlass['name_anlass']) ?></div>
                <div class="person-grid">
                    <div><span class="label">Name:</span> <?= htmlspecialchars($nachname) ?></div>
                    <div><span class="label">Anschrift:</span> <?= htmlspecialchars((string) ($adresse['strasse'] ?? '')) ?></div>
                    <div><?= htmlspecialchars($ort) ?></div>
                    <div><span class="label">Vorname:</span> <?= htmlspecialchars($vorname) ?></div>
                    <div><span class="label">Jahrgang:</span> <?= htmlspecialchars($geburtsjahr) ?> (<?= htmlspecialchars($alterskategorie) ?>)</div>
                    <div></div>
                </div>
            </div>

            <div class="barcode">
                <?php if ($barcodeError !== ''): ?>
                    <div class="barcode-error"><?= htmlspecialchars($barcodeError) ?></div>
                <?php else: ?>
                    <?= $barcodeSvg($barcodeNumber) ?>
                <?php endif; ?>
            </div>

            <div class="meta">
                <div><span class="label">Datum:</span> <?= htmlspecialchars((string) ($standblatt['datum'] ?: date('d.m.Y'))) ?></div>
                <div><span class="label">Standblatt:</span> <?= htmlspecialchars((string) $standblattId) ?></div>
                <div><span class="label">Munition:</span></div>
                <div><span class="label">Kosten:</span> Fr. <?= htmlspecialchars($formatMoney($kosten)) ?></div>
            </div>
        </header>

        <section class="shoot-grid" style="grid-template-columns: repeat(<?= (int) $gridGroups ?>, minmax(0, 1fr));">
            <?php if ($printGroups === []): ?>
                <div class="shot-group" style="grid-template-columns: 1fr;">
                    <div class="shot-cell">Keine Stiche</div>
                </div>
            <?php else: ?>
                <?php foreach ($printGroups as $group): ?>
                    <div
                        class="shot-group"
                        style="grid-template-columns: repeat(<?= htmlspecialchars((string) $group['columns']) ?>, minmax(0, 1fr));"
                        title="<?= htmlspecialchars(trim((string) $group['name'] . ' ' . $group['short_name'])) ?>"
                    >
                        <div class="shot-group-title">
                            <strong><?= htmlspecialchars((string) $group['name']) ?></strong>
                            <span>à <?= htmlspecialchars($formatMoney((float) ($group['preis'] ?? 0))) ?></span>
                            <span><?= htmlspecialchars((string) $group['short_name']) ?> · <?= htmlspecialchars((string) $group['schuss']) ?> Schuss</span>
                        </div>
                        <?php for ($shot = 1; $shot <= (int) $group['schuss']; $shot++): ?>
                            <div class="shot-cell"><?= htmlspecialchars((string) $shot) ?></div>
                        <?php endfor; ?>
                        <div class="total-cell" style="grid-column: <?= htmlspecialchars((string) $group['columns']) ?>;">Total</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <footer class="footer">
            <div class="awards" style="grid-template-columns: repeat(<?= (int) $gabenColumns ?>, minmax(0, 1fr));">
                <?php if ($gaben === []): ?>
                    <div>Keine Gaben hinterlegt</div>
                <?php else: ?>
                    <?php foreach ($gaben as $gabe): ?>
                        <div>
                            <?= htmlspecialchars((string) ($gabe['anzahl'] ?? 0)) ?> <?= htmlspecialchars((string) $gabe['name']) ?><br>
                            ab <?= htmlspecialchars($formatMoney((float) ($gabe['punktwert'] ?? 0))) ?> Pkt.
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="print-note">
                <div><?= htmlspecialchars($name) ?> · Standblatt <?= htmlspecialchars((string) $standblattId) ?></div>
                <div>Gedruckt: <?= htmlspecialchars(date('d.m.Y H:i')) ?></div>
            </div>
        </footer>
    </main>
<?php if (empty($batchPrint)): ?>
</body>
</html>
<?php endif; ?>
