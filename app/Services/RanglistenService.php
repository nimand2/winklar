<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Schussdaten;
use App\Models\Standblatt;
use App\Models\Stich;

final class RanglistenService
{
    public function __construct(
        private readonly Stich $stichModel,
        private readonly Standblatt $standblattModel,
        private readonly Schussdaten $schussdatenModel,
    ) {
    }

    public function buildForAnlass(int $anlassId): array
    {
        $stiche = $this->stichModel->findByAnlassId($anlassId);
        $standblaetter = $this->standblattModel->findForAnlassWithAdresse($anlassId);
        $schuesse = $this->schussdatenModel->findByAnlassId($anlassId);
        $schuesseByStandblattAndExterneNummer = [];

        foreach ($schuesse as $schuss) {
            if ((int) ($schuss['ins_del'] ?? 0) !== 0) {
                continue;
            }

            $standblattId = (int) ($schuss['start_nr'] ?? 0);
            $externeNummer = $this->externalNumber($schuss['externe_nummer'] ?? null);

            if ($standblattId <= 0 || $externeNummer === '') {
                continue;
            }

            $schuesseByStandblattAndExterneNummer[$standblattId][$externeNummer][] = $schuss;
        }

        $ranglisten = [];

        foreach ($stiche as $stich) {
            $externeNummer = $this->externalNumber($stich['anzeige_id'] ?? null);
            $teilnehmer = [];

            if ($externeNummer === '') {
                $ranglisten[] = [
                    'stich' => $stich,
                    'teilnehmer' => [],
                ];
                continue;
            }

            foreach ($standblaetter as $standblatt) {
                $standblattId = (int) $standblatt['id'];
                $stichSchuesse = $schuesseByStandblattAndExterneNummer[$standblattId][$externeNummer] ?? [];

                if ($stichSchuesse === []) {
                    continue;
                }

                $total = array_reduce(
                    $stichSchuesse,
                    fn (float $sum, array $schuss): float => $sum + $this->numericValue($schuss['primaerwertung'] ?? null),
                    0.0
                );

                $teilnehmer[] = [
                    'standblatt_id' => $standblattId,
                    'name' => trim((string) (($standblatt['vorname'] ?? '') . ' ' . ($standblatt['nachname'] ?? ''))),
                    'verein' => (string) (($standblatt['zusatz'] ?? '') ?: ($standblatt['firmen_anrede'] ?? '')),
                    'geburtsdatum' => $standblatt['geburtsdatum'] ?? null,
                    'total' => $total,
                    'schuss_count' => count($stichSchuesse),
                ];
            }

            usort($teilnehmer, static function (array $left, array $right): int {
                $totalCompare = (float) $right['total'] <=> (float) $left['total'];

                if ($totalCompare !== 0) {
                    return $totalCompare;
                }

                $leftBirthdate = (string) ($left['geburtsdatum'] ?? '');
                $rightBirthdate = (string) ($right['geburtsdatum'] ?? '');

                return strcmp($rightBirthdate, $leftBirthdate)
                    ?: strcmp((string) $left['name'], (string) $right['name'])
                    ?: ((int) $left['standblatt_id'] <=> (int) $right['standblatt_id']);
            });

            foreach ($teilnehmer as $rang => $teilnehmerRow) {
                $teilnehmer[$rang]['rang'] = $rang + 1;
            }

            $ranglisten[] = [
                'stich' => $stich,
                'teilnehmer' => $teilnehmer,
            ];
        }

        return $ranglisten;
    }

    private function externalNumber(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function numericValue(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }
}
