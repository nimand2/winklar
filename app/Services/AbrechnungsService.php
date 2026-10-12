<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Gaben;
use App\Models\Schussdaten;
use App\Models\Standblatt;

final class AbrechnungsService
{
    public function __construct(
        private readonly Standblatt $standblattModel,
        private readonly Schussdaten $schussdatenModel,
        private readonly Gaben $gabenModel,
    ) {
    }

    /**
     * Baut alle Daten fuer die Abrechnungsansicht eines Standblatts auf.
     */
    public function buildViewData(int $anlassId, int $standblattId): array
    {
        $stiche = $this->standblattModel->findSticheForStandblatt($standblattId);
        $schuesse = $this->schussdatenModel->findByStartNrAndIdAnlass($standblattId, $anlassId);
        $auswertung = $this->buildAuswertung($stiche, $schuesse);
        $regeln = $this->gabenModel->findRegelnForStiche($this->stichIdsFromAuswertung($auswertung));
        $savedAbgaben = $this->gabenModel->findAbgabenForStandblatt($standblattId);
        $context = $this->standblattModel->findById($standblattId);
        $age = Altersregel::alter($context['geburtsdatum'] ?? null, ($context['start_anlass'] ?? null) ?: ($context['end_anlass'] ?? null));
        $gaben = $this->buildGabenVergleich($auswertung['rows'], $regeln, $savedAbgaben, $age);

        return [
            'stiche' => $stiche,
            'schuesse' => $schuesse,
            'auswertung' => $auswertung,
            'gabenVergleich' => $gaben,
            'gabenSummen' => $this->gabenSummen($gaben),
        ];
    }

    /**
     * Filtert gepostete Gaben auf tatsaechlich erreichbare und waehbare Abgaben.
     */
    public function itemsFromPostedGaben(int $anlassId, int $standblattId, array $postedGaben, array $postedAbgegeben = [], array $postedRuecknahmen = []): array
    {
        $viewData = $this->buildViewData($anlassId, $standblattId);
        $items = [];
        foreach ($viewData['gabenVergleich'] as $gruppe) {
            foreach ($gruppe['gaben'] as $gabe) {
                $ruecknahme = !empty($gabe['abgegeben']) && !empty($postedRuecknahmen[$gabe['stich_id']][$gabe['serie_nummer']][$gabe['gaben_id']]);
                $status = $postedAbgegeben[$gabe['stich_id']][$gabe['serie_nummer']] ?? [];
                $items[] = [
                    'stich_id' => $gabe['stich_id'],
                    'serie_nummer' => $gabe['serie_nummer'],
                    'gaben_id' => $gabe['gaben_id'],
                    'ruecknahme' => $ruecknahme,
                    'snapshot' => ['name' => $gabe['name'], 'preis' => $gabe['preis'], 'punktwert' => $gabe['limit'], 'anzahl' => $gabe['anzahl'], 'ist_geld' => !empty($gabe['ist_geld']), 'stich_name' => $gruppe['bezeichnung']],
                    'abgegeben' => !$ruecknahme && (!empty($gabe['abgegeben']) || (is_array($status) ? !empty($status[$gabe['gaben_id']]) : !empty($status))),
                ];
            }
        }
        return $items;
    }

    /** Summiert Gabenwerte und Geldzahlungen getrennt nach Abgabestatus. */
    public function gabenSummen(array $gruppen): array
    {
        $summen = ['gesamt' => 0, 'abgegeben' => 0, 'offen' => 0, 'geld_gesamt' => 0, 'geld_abgegeben' => 0, 'geld_offen' => 0];
        foreach ($gruppen as $gruppe) {
            foreach ($gruppe['gaben'] as $gabe) {
                $cents = (int) round((float) $gabe['preis'] * 100);
                $status = !empty($gabe['abgegeben']) ? 'abgegeben' : 'offen';
                $summen['gesamt'] += $cents;
                $summen[$status] += $cents;
                if (!empty($gabe['ist_geld'])) {
                    $summen['geld_gesamt'] += $cents;
                    $summen['geld_' . $status] += $cents;
                }
            }
        }
        return array_map(static fn (int $cents): float => $cents / 100, $summen);
    }

    /**
     * Gruppiert Schussdaten nach Stich und berechnet Totalwerte fuer die Abrechnung.
     */
    private function buildAuswertung(array $stiche, array $schuesse): array
    {
        return SerienService::build($stiche, $schuesse);
    }

    /**
     * Vergleicht erreichte Resultate mit Gabenregeln und markiert waehlbare Gaben.
     */
    private function buildGabenVergleich(array $rows, array $regeln, array $savedAbgaben, ?int $age = null): array
    {
        $saved = [];
        foreach ($savedAbgaben as $abgabe) {
            $saved[(int) $abgabe['stich_id'] . ':' . (int) ($abgabe['serie_nummer'] ?? 1) . ':' . (int) $abgabe['gaben_id']] = $abgabe;
        }

        $regelnByStich = [];
        foreach ($regeln as $regel) {
            $regelnByStich[(int) $regel['stich_id']][] = $regel;
        }

        $gruppen = [];
        foreach ($rows as $row) {
            $stichId = (int) ($row['stich_id'] ?? 0);
            $serieNummer = (int) ($row['serie_nummer'] ?? 1);
            $stichTotal = (float) ($row['total'] ?? 0);
            $abgegeben = false;
            $gaben = [];

            foreach ($regelnByStich[$stichId] ?? [] as $regel) {
                $minWert = $regel['min_wert'] !== null ? (float) $regel['min_wert'] : (float) ($regel['punktwert'] ?? 0);
                $maxWert = $regel['max_wert'] !== null ? (float) $regel['max_wert'] : null;
                $ageAllowed = (($regel['min_alter'] ?? null) === null && ($regel['max_alter'] ?? null) === null)
                    || ($age !== null && $age >= (int) ($regel['min_alter'] ?? 0) && $age <= (int) ($regel['max_alter'] ?? 150));
                $erreicht = !empty($row['vollstaendig']) && $ageAllowed
                    && $stichTotal >= $minWert && ($maxWert === null || $stichTotal <= $maxWert);
                $key = $stichId . ':' . $serieNummer . ':' . (int) $regel['gaben_id'];
                if (!$erreicht) {
                    continue;
                }
                $abgegeben = $abgegeben || !empty($saved[$key]['abgegeben']);
                $gaben[(int) $regel['gaben_id']] = [
                    'stich_id' => $stichId,
                    'serie_nummer' => $serieNummer,
                    'gaben_id' => (int) $regel['gaben_id'],
                    'name' => (string) (!empty($saved[$key]['abgegeben']) ? ($saved[$key]['name'] ?? $regel['name']) : ($regel['name'] ?? '')),
                    'anzahl' => (int) ($regel['anzahl'] ?? 0),
                    'limit' => $minWert,
                    'max_wert' => $maxWert,
                    'erreicht' => $erreicht,
                    'selected' => true,
                    'preis' => (float) (!empty($saved[$key]['abgegeben']) ? ($saved[$key]['preis'] ?? $regel['preis']) : ($regel['preis'] ?? 0)),
                    'ist_geld' => !empty($saved[$key]['abgegeben']) ? (bool) ($saved[$key]['ist_geld'] ?? (stripos((string) ($saved[$key]['name'] ?? $regel['name']), 'Barauszahlung') !== false)) : (stripos((string) ($regel['name'] ?? ''), 'Barauszahlung') !== false),
                    'abgegeben' => !empty($saved[$key]['abgegeben']),
                    'differenz' => $stichTotal - $minWert,
                ];
            }

            if ($gaben === []) {
                continue;
            }
            $gruppen[] = [
                'stich_id' => $stichId,
                'serie_nummer' => $serieNummer,
                'bezeichnung' => (string) ($row['bezeichnung'] ?? 'Stich'),
                'kurzname' => (string) ($row['kurzname'] ?? ''),
                'total' => $stichTotal,
                'gaben' => array_values($gaben),
                'abgegeben' => $abgegeben,
            ];
        }

        $visible = [];
        foreach ($gruppen as $gruppe) {
            foreach ($gruppe['gaben'] as $gabe) {
                $visible[$gabe['stich_id'] . ':' . $gabe['serie_nummer'] . ':' . $gabe['gaben_id']] = true;
            }
        }
        foreach ($saved as $key => $abgabe) {
            if (empty($abgabe['abgegeben']) || isset($visible[$key])) {
                continue;
            }
            $gruppen[] = [
                'stich_id' => (int) $abgabe['stich_id'],
                'serie_nummer' => (int) ($abgabe['serie_nummer'] ?? 1),
                'bezeichnung' => 'Bereits abgegebene Gabe (vor Resultatkorrektur)',
                'total' => 0,
                'abgegeben' => true,
                'gaben' => [[
                    'stich_id' => (int) $abgabe['stich_id'],
                    'serie_nummer' => (int) ($abgabe['serie_nummer'] ?? 1),
                    'gaben_id' => (int) $abgabe['gaben_id'],
                    'name' => (string) $abgabe['name'], 'preis' => (float) $abgabe['preis'],
                    'ist_geld' => (bool) ($abgabe['ist_geld'] ?? (stripos((string) $abgabe['name'], 'Barauszahlung') !== false)),
                    'anzahl' => (int) $abgabe['anzahl'], 'limit' => (float) $abgabe['punktwert'],
                    'selected' => true, 'erreicht' => false, 'abgegeben' => true,
                ]],
            ];
        }
        return $gruppen;
    }

    /**
     * Extrahiert eindeutige Stich-IDs aus der berechneten Auswertung.
     */
    private function stichIdsFromAuswertung(array $auswertung): array
    {
        return array_values(array_unique(array_map(
            static fn (array $row): int => (int) ($row['stich_id'] ?? 0),
            (array) ($auswertung['rows'] ?? [])
        )));
    }

}
