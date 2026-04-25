<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Gaben;
use App\Models\Standblatt;

final class KassenService
{
    public function __construct(
        private readonly Standblatt $standblattModel,
        private readonly Gaben $gabenModel,
    ) {
    }

    public function buildAbrechnung(int $anlassId): array
    {
        $standblaetter = $this->standblattModel->findForAnlassWithAdresse($anlassId);
        $stichEinnahmen = $this->standblattModel->findEinnahmenByStichForAnlass($anlassId);
        $abgaben = $this->gabenModel->findAbgabenForAnlass($anlassId);
        $einnahmenTotal = 0.0;
        $offeneGabenPruefungen = 0;
        $standblattRows = [];

        foreach ($standblaetter as $standblatt) {
            $kosten = $this->numericValue($standblatt['kosten'] ?? null);
            $einnahmenTotal += $kosten;

            if ((int) ($standblatt['gaben_geprueft'] ?? 0) !== 1) {
                $offeneGabenPruefungen++;
            }

            $standblattRows[] = [
                'id' => (int) $standblatt['id'],
                'name' => trim((string) (($standblatt['vorname'] ?? '') . ' ' . ($standblatt['nachname'] ?? ''))),
                'verein' => (string) (($standblatt['zusatz'] ?? '') ?: ($standblatt['firmen_anrede'] ?? '')),
                'datum' => $standblatt['datum'] ?? null,
                'kosten' => $kosten,
                'gaben_geprueft' => (int) ($standblatt['gaben_geprueft'] ?? 0) === 1,
            ];
        }

        $gabenTotal = 0.0;
        $gabenById = [];

        foreach ($abgaben as $abgabe) {
            $gabenId = (int) $abgabe['gaben_id'];
            $preis = $this->numericValue($abgabe['preis'] ?? null);
            $gabenTotal += $preis;

            if (!isset($gabenById[$gabenId])) {
                $gabenById[$gabenId] = [
                    'gaben_id' => $gabenId,
                    'name' => (string) ($abgabe['name'] ?? ''),
                    'preis' => $preis,
                    'anzahl' => 0,
                    'total' => 0.0,
                ];
            }

            $gabenById[$gabenId]['anzahl']++;
            $gabenById[$gabenId]['total'] += $preis;
        }

        return [
            'einnahmen_total' => $einnahmenTotal,
            'gaben_total' => $gabenTotal,
            'netto' => $einnahmenTotal - $gabenTotal,
            'standblatt_count' => count($standblaetter),
            'offene_gaben_pruefungen' => $offeneGabenPruefungen,
            'standblaetter' => $standblattRows,
            'stich_einnahmen' => $stichEinnahmen,
            'gaben' => array_values($gabenById),
            'abgaben' => $abgaben,
        ];
    }

    private function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
