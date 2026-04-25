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

    public function buildViewData(int $anlassId, int $standblattId): array
    {
        $stiche = $this->standblattModel->findSticheForStandblatt($standblattId);
        $schuesse = $this->schussdatenModel->findByStartNrAndIdAnlass($standblattId, $anlassId);
        $auswertung = $this->buildAuswertung($stiche, $schuesse);
        $regeln = $this->gabenModel->findRegelnForStiche($this->stichIdsFromAuswertung($auswertung));
        $savedAbgaben = $this->gabenModel->findAbgabenForStandblatt($standblattId);
        $defaultSelected = !$this->gabenModel->areAbgabenGeprueft($standblattId);
        $gaben = $this->buildGabenVergleich($auswertung['rows'], $regeln, $savedAbgaben, $defaultSelected);

        return [
            'stiche' => $stiche,
            'schuesse' => $schuesse,
            'auswertung' => $auswertung,
            'gabenVergleich' => $gaben,
        ];
    }

    public function itemsFromPostedGaben(int $anlassId, int $standblattId, array $postedGaben): array
    {
        $viewData = $this->buildViewData($anlassId, $standblattId);
        $regeln = $this->gabenModel->findRegelnForStiche($this->stichIdsFromAuswertung($viewData['auswertung']));
        $selectable = $this->buildSelectableAbgaben($viewData['auswertung']['rows'], $regeln);
        $items = [];

        foreach ($postedGaben as $stichId => $gabenIds) {
            foreach ((array) $gabenIds as $gabenId) {
                $key = (int) $stichId . ':' . (int) $gabenId;

                if (!isset($selectable[$key])) {
                    continue;
                }

                $items[$key] = [
                    'stich_id' => (int) $stichId,
                    'gaben_id' => (int) $gabenId,
                ];
            }
        }

        return array_values($items);
    }

    private function buildAuswertung(array $stiche, array $schuesse): array
    {
        usort($schuesse, static function (array $left, array $right): int {
            return ((int) ($left['match_index'] ?? 0) <=> (int) ($right['match_index'] ?? 0))
                ?: ((int) ($left['stich_index'] ?? 0) <=> (int) ($right['stich_index'] ?? 0))
                ?: strcmp((string) ($left['schuss_zeit'] ?? ''), (string) ($right['schuss_zeit'] ?? ''))
                ?: ((int) $left['id'] <=> (int) $right['id']);
        });

        $schuesseByStich = [];
        foreach ($schuesse as $schuss) {
            if ((int) ($schuss['ins_del'] ?? 0) !== 0) {
                continue;
            }

            $index = (int) ($schuss['stich_index'] ?? 0);
            $schuesseByStich[$index][] = $schuss;
        }

        $rows = [];
        $usedIndexes = [];
        $total = 0.0;

        foreach ($stiche as $position => $stich) {
            $index = $this->stichIndex($stich, $position);
            $usedIndexes[] = $index;
            $row = $this->buildStichRow($stich, $schuesseByStich[$index] ?? []);
            $rows[] = $row;
            $total += $row['total'];
        }

        foreach ($schuesseByStich as $index => $stichSchuesse) {
            if (in_array((int) $index, $usedIndexes, true)) {
                continue;
            }

            $row = $this->buildStichRow([
                'name' => 'Nicht zugeordneter Stich ' . (int) $index,
                'short_name' => '',
                'anzahl_schuss' => count($stichSchuesse),
                'anzahl_stiche' => 1,
            ], $stichSchuesse);
            $rows[] = $row;
            $total += $row['total'];
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'schussCount' => array_sum(array_map(static fn (array $row): int => count($row['schuesse']), $rows)),
        ];
    }

    private function buildStichRow(array $stich, array $schuesse): array
    {
        $werte = [];
        $total = 0.0;

        foreach ($schuesse as $schuss) {
            $wert = $this->numericValue($schuss['primaerwertung'] ?? null);
            $werte[] = [
                'primaerwertung' => $wert,
                'sekundaerwertung' => $schuss['sekundaerwertung'] ?? null,
                'mouche' => (int) ($schuss['mouche'] ?? 0) === 1,
                'zeit' => $schuss['schuss_zeit'] ?? null,
            ];
            $total += $wert;
        }

        $anzahlSchuss = (int) ($stich['anzahl_schuss'] ?? 0);
        $anzahlSchuss = $anzahlSchuss > 0 ? $anzahlSchuss : count($werte);

        return [
            'stich_id' => (int) ($stich['id'] ?? 0),
            'bezeichnung' => (string) ($stich['name'] ?? 'Stich'),
            'kurzname' => (string) ($stich['short_name'] ?? ''),
            'preis' => (float) ($stich['preis'] ?? 0),
            'anzahl_schuss' => max(1, $anzahlSchuss),
            'anzahl_stiche' => max(1, (int) ($stich['anzahl_stiche'] ?? 1)),
            'erwartet' => max(0, (int) ($stich['anzahl_schuss'] ?? 0) * max(1, (int) ($stich['anzahl_stiche'] ?? 1))),
            'total' => $total,
            'schuesse' => $werte,
        ];
    }

    private function buildGabenVergleich(array $rows, array $regeln, array $savedAbgaben, bool $defaultSelected = false): array
    {
        $saved = [];
        foreach ($savedAbgaben as $abgabe) {
            $saved[(int) $abgabe['stich_id'] . ':' . (int) $abgabe['gaben_id']] = true;
        }

        $regelnByStich = [];
        foreach ($regeln as $regel) {
            $regelnByStich[(int) $regel['stich_id']][] = $regel;
        }

        $gruppen = [];
        foreach ($rows as $row) {
            $stichId = (int) ($row['stich_id'] ?? 0);
            $stichTotal = (float) ($row['total'] ?? 0);
            $gaben = [];

            foreach ($regelnByStich[$stichId] ?? [] as $regel) {
                $minWert = $regel['min_wert'] !== null ? (float) $regel['min_wert'] : (float) ($regel['punktwert'] ?? 0);
                $maxWert = $regel['max_wert'] !== null ? (float) $regel['max_wert'] : null;
                $erreicht = $stichTotal >= $minWert && ($maxWert === null || $stichTotal <= $maxWert);
                $key = $stichId . ':' . (int) $regel['gaben_id'];
                $gaben[] = [
                    'stich_id' => $stichId,
                    'gaben_id' => (int) $regel['gaben_id'],
                    'name' => (string) ($regel['name'] ?? ''),
                    'anzahl' => (int) ($regel['anzahl'] ?? 0),
                    'limit' => $minWert,
                    'max_wert' => $maxWert,
                    'erreicht' => $erreicht,
                    'selected' => isset($saved[$key]) || ($defaultSelected && $erreicht),
                    'differenz' => $stichTotal - $minWert,
                ];
            }

            $gruppen[] = [
                'stich_id' => $stichId,
                'bezeichnung' => (string) ($row['bezeichnung'] ?? 'Stich'),
                'kurzname' => (string) ($row['kurzname'] ?? ''),
                'total' => $stichTotal,
                'gaben' => $gaben,
            ];
        }

        return $gruppen;
    }

    private function buildSelectableAbgaben(array $rows, array $regeln): array
    {
        $gruppen = $this->buildGabenVergleich($rows, $regeln, []);
        $selectable = [];

        foreach ($gruppen as $gruppe) {
            foreach ($gruppe['gaben'] as $gabe) {
                if (empty($gabe['erreicht'])) {
                    continue;
                }

                $selectable[(int) $gabe['stich_id'] . ':' . (int) $gabe['gaben_id']] = true;
            }
        }

        return $selectable;
    }

    private function stichIdsFromAuswertung(array $auswertung): array
    {
        return array_values(array_unique(array_map(
            static fn (array $row): int => (int) ($row['stich_id'] ?? 0),
            (array) ($auswertung['rows'] ?? [])
        )));
    }

    private function stichIndex(array $stich, int $position): int
    {
        $anzeigeId = (int) ($stich['anzeige_id'] ?? 0);

        return $anzeigeId > 0 ? $anzeigeId : $position + 1;
    }

    private function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
