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

    /**
     * Berechnet Einnahmen, Gabenwerte und Detailzeilen fuer die Kassenansicht.
     */
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

        $gabenTotal = 0;
        $gabenOffen = 0;
        $geldAbgegeben = 0;
        $geldOffen = 0;
        $gabenById = [];

        foreach ($abgaben as $abgabe) {
            $gabenId = (int) $abgabe['gaben_id'];
            $preis = $this->numericValue($abgabe['preis'] ?? null);
            $cents = (int) round($preis * 100);
            $delivered = !empty($abgabe['abgegeben']);
            if ($delivered) $gabenTotal += $cents; else $gabenOffen += $cents;
            if (!empty($abgabe['ist_geld'])) {
                if ($delivered) $geldAbgegeben += $cents; else $geldOffen += $cents;
            }
            $groupKey = $gabenId . ':' . $preis . ':' . (int) $delivered;
            if (!isset($gabenById[$groupKey])) {
                $gabenById[$groupKey] = [
                    'gaben_id' => $gabenId,
                    'name' => (string) ($abgabe['name'] ?? ''),
                    'preis' => $preis,
                    'anzahl' => 0,
                    'abgegeben' => $delivered,
                    'total' => 0.0,
                ];
            }

            $gabenById[$groupKey]['anzahl']++;
            $gabenById[$groupKey]['total'] += $preis;
        }

        return [
            'einnahmen_total' => $einnahmenTotal,
            'gaben_total' => $gabenTotal / 100.0,
            'gaben_offen_total' => $gabenOffen / 100.0,
            'geld_abgegeben' => $geldAbgegeben / 100.0,
            'geld_offen' => $geldOffen / 100.0,
            'kassenbestand' => $einnahmenTotal - $geldAbgegeben / 100.0,
            'netto' => $einnahmenTotal - $gabenTotal / 100.0,
            'standblatt_count' => count($standblaetter),
            'offene_gaben_pruefungen' => $offeneGabenPruefungen,
            'standblaetter' => $standblattRows,
            'stich_einnahmen' => $stichEinnahmen,
            'gaben' => array_values($gabenById),
            'abgaben' => $abgaben,
        ];
    }

    /**
     * Normalisiert optionale Zahlenwerte aus Datenbankfeldern.
     */
    private function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
