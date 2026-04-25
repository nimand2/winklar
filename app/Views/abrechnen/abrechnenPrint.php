<?php

declare(strict_types=1);

use App\Core\Url;

$anlassId = (int) $anlass['id'];
$standblattId = (int) $standblatt['id'];
$name = trim((string) (($adresse['nachname'] ?? '') . ' ' . ($adresse['vorname'] ?? '')));
$vorname = trim((string) ($adresse['vorname'] ?? ''));
$nachname = trim((string) ($adresse['nachname'] ?? ''));
$ort = trim((string) (($adresse['plz4'] ?? '') . ' ' . ($adresse['ortschaftsname'] ?? '')));
$geburtsjahr = '';

if (!empty($adresse['geburtsdatum'])) {
    $geburtsjahr = substr((string) $adresse['geburtsdatum'], 0, 4);
}

$kosten = (float) ($standblatt['kosten'] ?? 0);
$rows = (array) ($auswertung['rows'] ?? []);
$gabenVergleich = (array) ($gabenVergleich ?? []);
$formatMoney = static fn (float $value): string => number_format($value, 2, '.', "'");
$formatNumber = static function (float $value): string {
    $rounded = round($value, 2);

    return floor($rounded) === $rounded ? (string) (int) $rounded : number_format($rounded, 2, '.', "'");
};
$printGroups = [];

foreach ($rows as $row) {
    $anzahlSchuss = max(1, (int) ($row['anzahl_schuss'] ?? count((array) ($row['schuesse'] ?? [])) ?: 1));
    $anzahlStiche = max(1, (int) ($row['anzahl_stiche'] ?? 1));
    $columns = max(1, min(4, (int) ceil($anzahlSchuss / 5)));
    $schuesse = array_values((array) ($row['schuesse'] ?? []));

    for ($groupIndex = 0; $groupIndex < $anzahlStiche; $groupIndex++) {
        $groupSchuesse = array_slice($schuesse, $groupIndex * $anzahlSchuss, $anzahlSchuss);
        $groupTotal = array_sum(array_map(static fn (array $schuss): float => (float) ($schuss['primaerwertung'] ?? 0), $groupSchuesse));
        $printGroups[] = [
            'name' => (string) ($row['bezeichnung'] ?? 'Stich'),
            'short_name' => (string) ($row['kurzname'] ?? ''),
            'preis' => (float) ($row['preis'] ?? 0),
            'schuss' => $anzahlSchuss,
            'columns' => $columns,
            'werte' => $groupSchuesse,
            'total' => $groupTotal,
        ];
    }
}

$gridGroups = max(1, count($printGroups));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrechnung drucken #<?= htmlspecialchars((string) $standblattId) ?></title>
    <style>
        @page {
            size: A5 landscape;
            margin: 7mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #e9eef4;
            color: #050505;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
        }

        .screen-actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 12px;
        }

        .screen-actions a,
        .screen-actions button {
            border: 1px solid #6b7280;
            border-radius: 6px;
            background: #fff;
            color: #111827;
            cursor: pointer;
            font: inherit;
            padding: 7px 12px;
            text-decoration: none;
        }

        .sheet {
            width: 210mm;
            min-height: 148mm;
            margin: 0 auto 16px;
            padding: 7mm;
            background: #fff;
            border: 1px solid #222;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        }

        .header {
            display: grid;
            grid-template-columns: 1fr 42mm;
            gap: 6mm;
            border-bottom: 1px solid #111;
            padding-bottom: 2mm;
        }

        .club {
            font-size: 9pt;
            margin-bottom: 1mm;
        }

        .title {
            font-size: 19pt;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 2mm;
        }

        .person-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 26mm;
            gap: 1mm 5mm;
        }

        .meta {
            display: grid;
            gap: 1mm;
            text-align: right;
        }

        .label {
            font-weight: 700;
        }

        .shoot-grid {
            display: grid;
            grid-template-columns: repeat(<?= htmlspecialchars((string) $gridGroups) ?>, minmax(0, 1fr));
            gap: 1mm;
            margin-top: 6mm;
        }

        .shot-group {
            display: grid;
            gap: 1mm;
            align-content: start;
        }

        .shot-group-title {
            display: grid;
            gap: 0.6mm;
            grid-column: 1 / -1;
            min-height: 13mm;
            font-size: 7.5pt;
        }

        .shot-group-title strong {
            font-size: 8pt;
        }

        .shot-cell,
        .total-cell {
            border: 1px solid #222;
            min-height: 7.8mm;
            padding: 0.6mm 0.8mm;
            font-size: 7.5pt;
        }

        .shot-value,
        .total-value {
            display: block;
            font-size: 12pt;
            font-weight: 800;
            line-height: 1;
            text-align: center;
        }

        .total-cell {
            min-height: 8.5mm;
            text-align: center;
            font-weight: 700;
        }

        .footer {
            border-top: 1px solid #111;
            margin-top: 5mm;
            padding-top: 2mm;
        }

        .awards {
            display: grid;
            grid-template-columns: repeat(<?= htmlspecialchars((string) max(1, count($gabenVergleich))) ?>, minmax(0, 1fr));
            gap: 2mm;
            font-size: 7.5pt;
        }

        .award {
            text-align: center;
        }

        .award-checkbox {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3mm;
            height: 3mm;
            border: 1px solid #222;
            margin-right: 1mm;
            vertical-align: middle;
        }

        .award-checkbox.checked::after {
            content: "✓";
            font-size: 7pt;
            font-weight: 800;
            line-height: 1;
        }

        .print-note {
            display: flex;
            justify-content: space-between;
            margin-top: 2mm;
            font-size: 7.5pt;
        }

        @media print {
            body {
                background: #fff;
            }

            .screen-actions {
                display: none;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="screen-actions">
        <button type="button" onclick="window.print()">Drucken</button>
        <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId . '/abrechnen')) ?>">Zurück zur Abrechnung</a>
    </div>

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
                    <div><span class="label">Jahrgang:</span> <?= htmlspecialchars($geburtsjahr) ?></div>
                    <div></div>
                </div>
            </div>

            <div class="meta">
                <div><span class="label">Datum:</span> <?= htmlspecialchars((string) ($standblatt['datum'] ?: date('d.m.Y'))) ?></div>
                <div><span class="label">Standblatt:</span> <?= htmlspecialchars((string) $standblattId) ?></div>
                <div><span class="label">Munition:</span></div>
                <div><span class="label">Kosten:</span> Fr. <?= htmlspecialchars($formatMoney($kosten)) ?></div>
            </div>
        </header>

        <section class="shoot-grid">
            <?php if ($printGroups === []): ?>
                <div class="shot-group" style="grid-template-columns: 1fr;">
                    <div class="shot-cell">Keine Schussdaten</div>
                </div>
            <?php else: ?>
                <?php foreach ($printGroups as $group): ?>
                    <div
                        class="shot-group"
                        style="grid-template-columns: repeat(<?= htmlspecialchars((string) $group['columns']) ?>, minmax(0, 1fr));"
                    >
                        <div class="shot-group-title">
                            <strong><?= htmlspecialchars((string) $group['name']) ?></strong>
                            <span>à <?= htmlspecialchars($formatMoney((float) ($group['preis'] ?? 0))) ?></span>
                            <span><?= htmlspecialchars((string) $group['short_name']) ?> · <?= htmlspecialchars((string) $group['schuss']) ?> Schuss</span>
                        </div>
                        <?php for ($shot = 1; $shot <= (int) $group['schuss']; $shot++): ?>
                            <?php $schuss = $group['werte'][$shot - 1] ?? null; ?>
                            <div class="shot-cell">
                                <?= htmlspecialchars((string) $shot) ?>
                                <?php if ($schuss !== null): ?>
                                    <span class="shot-value"><?= htmlspecialchars($formatNumber((float) $schuss['primaerwertung'])) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                        <div class="total-cell" style="grid-column: <?= htmlspecialchars((string) $group['columns']) ?>;">
                            Total
                            <?php if ((float) $group['total'] > 0): ?>
                                <span class="total-value"><?= htmlspecialchars($formatNumber((float) $group['total'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <footer class="footer">
            <div class="awards">
                <?php if ($gabenVergleich === []): ?>
                    <div class="award">Keine Gaben hinterlegt</div>
                <?php else: ?>
                    <?php foreach ($gabenVergleich as $gruppe): ?>
                        <div class="award">
                            <strong><?= htmlspecialchars((string) $gruppe['bezeichnung']) ?></strong><br>
                            <?php $selectedGaben = array_values(array_filter((array) ($gruppe['gaben'] ?? []), static fn (array $gabe): bool => !empty($gabe['selected']))); ?>
                            <?php if ($selectedGaben === []): ?>
                                Keine Gabe ausgewählt
                            <?php else: ?>
                                <?php foreach ($selectedGaben as $gabe): ?>
                                    <span class="award-checkbox checked"></span>
                                    <?= htmlspecialchars((string) $gabe['anzahl']) ?> <?= htmlspecialchars((string) $gabe['name']) ?><br>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
</body>
</html>
