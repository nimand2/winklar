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
$formatMoney = static fn (float $value): string => number_format($value, 2, '.', "'");
$visibleStiche = array_slice($stiche, 0, 3);
$gridGroups = max(1, min(8, array_sum(array_map(static fn (array $stich): int => max(1, (int) ($stich['anzahl_stiche'] ?? 1)), $stiche))));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standblatt drucken #<?= htmlspecialchars((string) $standblattId) ?></title>
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

        .section-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6mm;
            min-height: 14mm;
            padding: 2mm 0;
        }

        .stich-box {
            display: grid;
            gap: 1mm;
        }

        .shoot-grid {
            display: grid;
            grid-template-columns: repeat(<?= htmlspecialchars((string) $gridGroups) ?>, 1fr);
            gap: 1mm;
            margin-top: 6mm;
        }

        .shot-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1mm;
        }

        .shot-cell,
        .total-cell {
            height: 10.5mm;
            border: 1px solid #222;
            padding: 0.8mm 1mm;
            font-size: 7.5pt;
        }

        .total-cell {
            grid-column: 2;
            height: 11mm;
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
            grid-template-columns: repeat(6, 1fr);
            gap: 2mm;
            text-align: center;
            font-size: 7.5pt;
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
        <a href="<?= htmlspecialchars(Url::app('/anlass/' . $anlassId . '/loesen/' . $standblattId)) ?>">Zurück zum Standblatt</a>
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
                    <div><span class="label">Lizenz:</span> <?= htmlspecialchars((string) ($adresse['lizenz'] ?? '')) ?></div>
                    <div><span class="label">Gruppe:</span> <?= htmlspecialchars((string) (($adresse['zusatz'] ?? '') ?: ($adresse['firmen_anrede'] ?? ''))) ?></div>
                    <div><span class="label">Kat.:</span></div>
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

        <section class="section-row">
            <?php foreach ($visibleStiche as $stich): ?>
                <div class="stich-box">
                    <div><?= htmlspecialchars((string) $stich['name']) ?></div>
                    <div>à <?= htmlspecialchars($formatMoney((float) ($stich['preis'] ?? 0))) ?></div>
                    <div><?= htmlspecialchars((string) ($stich['short_name'] ?? '')) ?> · <?= htmlspecialchars((string) ($stich['anzahl_schuss'] ?? '')) ?> Schuss</div>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="shoot-grid">
            <?php for ($group = 0; $group < $gridGroups; $group++): ?>
                <div class="shot-group">
                    <?php for ($shot = 1; $shot <= 10; $shot++): ?>
                        <div class="shot-cell"><?= htmlspecialchars((string) $shot) ?></div>
                    <?php endfor; ?>
                    <div class="total-cell">Total</div>
                </div>
            <?php endfor; ?>
        </section>

        <footer class="footer">
            <div class="awards">
                <div>Kranzk. à Fr. 4.-<br>Kranzabzeichen</div>
                <div>Kranzk. à Fr. 5.-<br>BRONZE</div>
                <div>Kranzk. à Fr. 6.-<br>SILBER</div>
                <div>Kranzk. à Fr. 8.-<br></div>
                <div>Kranzk. à Fr. 10.-<br>GOLD</div>
                <div>Kranzk. à Fr. 12.-<br></div>
            </div>
            <div class="print-note">
                <div><?= htmlspecialchars($name) ?> · Standblatt <?= htmlspecialchars((string) $standblattId) ?></div>
                <div>Gedruckt: <?= htmlspecialchars(date('d.m.Y H:i')) ?></div>
            </div>
        </footer>
    </main>
</body>
</html>
