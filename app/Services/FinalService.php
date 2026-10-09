<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Standblatt;

final class FinalService
{
    public function __construct(private readonly RanglistenService $ranglistenService, private readonly Standblatt $standblattModel)
    {
    }

    public function buildForAnlass(array $anlass): array
    {
        $kategorien = [];
        $stichId = (int) ($anlass['final_stich_id'] ?? 0);
        $stich = null;
        if ($stichId <= 0) {
            return ['stich' => null, 'kategorien' => [], 'teilnehmer' => []];
        }
        $standblaetter = [];
        foreach ($this->standblattModel->findForAnlassWithAdresse((int) $anlass['id']) as $row) {
            $standblaetter[(int) $row['id']] = $row;
        }
        $datum = (string) (($anlass['start_anlass'] ?? '') ?: ($anlass['end_anlass'] ?? ''));
        foreach ($this->ranglistenService->buildForAnlass((int) $anlass['id'], $datum) as $rangliste) {
            if ((int) $rangliste['stich']['id'] !== $stichId) {
                continue;
            }
            $stich = $rangliste['stich'];
            foreach ($rangliste['kategorien'] as $key => $kategorie) {
                $plaetze = (int) ($anlass['final_anzahl_' . $key] ?? 6);
                $qualifizierte = [];
                foreach ($kategorie['teilnehmer'] as $row) {
                    if ((int) $row['rang'] > $plaetze) {
                        continue;
                    }
                    $standblatt = $standblaetter[(int) $row['standblatt_id']] ?? null;
                    if ($standblatt === null) {
                        continue;
                    }
                    $id = (int) $row['standblatt_id'];
                    $barcodeBase = (10000000 + $id) * 100;
                    $qualifizierte[] = array_merge($row, [
                        'kategorie' => $key,
                        'kategorie_label' => $kategorie['label'],
                        'nimmt_teil' => (int) ($standblatt['final_teilnahme'] ?? 0) === 1,
                        'barcode' => $id <= 999998 ? (string) ($barcodeBase + (97 - ($barcodeBase % 97))) : null,
                        'standblatt' => $standblatt,
                    ]);
                }
                $kategorien[$key] = ['label' => $kategorie['label'], 'plaetze' => $plaetze, 'qualifizierte' => $qualifizierte];
            }
            break;
        }
        $teilnehmer = [];
        foreach ($kategorien as $kategorie) {
            foreach ($kategorie['qualifizierte'] as $row) {
                if ($row['nimmt_teil']) {
                    $teilnehmer[] = $row;
                }
            }
        }
        return ['stich' => $stich, 'kategorien' => $kategorien, 'teilnehmer' => $teilnehmer];
    }

    public function forCategory(array $final, string $kategorie): array
    {
        if (!in_array($kategorie, ['u18', 'ue18'], true)) {
            throw new \InvalidArgumentException('Ungültige Finalkategorie');
        }

        $final['teilnehmer'] = array_values(array_filter(
            $final['teilnehmer'],
            static fn (array $row): bool => $row['kategorie'] === $kategorie
        ));
        $final['kategorien'] = isset($final['kategorien'][$kategorie]) ? [$kategorie => $final['kategorien'][$kategorie]] : [];
        $final['kategorie'] = $kategorie;
        $final['kategorie_label'] = $kategorie === 'u18' ? 'U18' : 'Ü18';

        return $final;
    }

    public function exportData(array $anlass, array $final, string $kategorie): array
    {
        $final = $this->forCategory($final, $kategorie);
        $teilnehmer = [];
        foreach ($final['teilnehmer'] as $row) {
            $s = $row['standblatt'];
            $teilnehmer[] = [
                'standblatt_nummer' => (int) $row['standblatt_id'],
                'start_nummer' => (int) $row['standblatt_id'],
                'barcode' => $row['barcode'],
                'barcode_typ' => 'Interleaved 2 of 5',
                'adresse_id' => (int) $s['id_adresse'],
                'vorname' => (string) ($s['vorname'] ?? ''),
                'nachname' => (string) ($s['nachname'] ?? ''),
                'geburtsdatum' => $s['geburtsdatum'] ?? null,
                'jahrgang' => empty($s['geburtsdatum']) ? null : (int) substr((string) $s['geburtsdatum'], 0, 4),
                'kategorie' => $row['kategorie'],
                'kategorie_label' => $row['kategorie_label'],
                'verein' => $row['verein'],
                'lizenz' => $s['lizenz'] ?? null,
                'strasse' => $s['strasse'] ?? null,
                'postfach' => $s['postfach'] ?? null,
                'plz' => $s['plz4'] ?? null,
                'ort' => $s['ortschaftsname'] ?? null,
                'nation' => $s['nation'] ?? null,
                'email' => $s['email'] ?? null,
                'telefon' => $s['telefon'] ?? null,
                'qualifiziert' => true,
                'final_teilnahme' => true,
                'qualifikationsrang' => (int) $row['rang'],
                'qualifikationssumme' => (float) $row['summe_beste_zwei'],
                'bestes_resultat' => (float) $row['total'],
                'qualifikationsresultate' => $row['resultate'],
                'qualifikationsschuesse' => (int) $row['schuss_count'],
                'standblatt_datum' => $s['datum'] ?? null,
                'standblatt_kosten' => isset($s['kosten']) ? (float) $s['kosten'] : null,
            ];
        }
        return [
            'schema_version' => 1,
            'export_typ' => 'final_teilnehmer',
            'final' => [
                'id' => (int) $anlass['id'] . '-' . $kategorie,
                'kategorie' => $kategorie,
                'name' => 'Final ' . $final['kategorie_label'],
                'plaetze' => (int) ($anlass['final_anzahl_' . $kategorie] ?? 6),
            ],
            'erstellt_am' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zurich')))->format(DATE_ATOM),
            'anlass' => ['id' => (int) $anlass['id'], 'name' => $anlass['name_anlass'], 'kurzname' => $anlass['shortname_anlass'] ?? null, 'start' => $anlass['start_anlass'] ?? null, 'ende' => $anlass['end_anlass'] ?? null],
            'final_regeln' => ['stich_id' => (int) ($anlass['final_stich_id'] ?? 0), 'plaetze_u18' => (int) ($anlass['final_anzahl_u18'] ?? 6), 'plaetze_ue18' => (int) ($anlass['final_anzahl_ue18'] ?? 6), 'wertung' => 'Summe der besten zwei Resultate', 'altersregel' => 'Anlassjahr minus Geburtsjahr; U18 unter 18, Ü18 ab 18', 'nachruecken' => false],
            'qualifikationsstich' => $final['stich'],
            'teilnehmer_anzahl' => count($teilnehmer),
            'teilnehmer' => $teilnehmer,
        ];
    }
}
