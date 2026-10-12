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

    /**
     * Erstellt Ranglisten pro Stich und Alterskategorie fuer einen Anlass.
     */
    public function buildForAnlass(int $anlassId, ?string $anlassDatum = null, ?int $onlyStichId = null, ?array $standblaetter = null): array
    {
        $stiche = $this->stichModel->findByAnlassId($anlassId);
        if ($onlyStichId !== null) {
            $stiche = array_values(array_filter($stiche, static fn (array $stich): bool => (int) $stich['id'] === $onlyStichId));
        }
        if ($stiche === []) {
            return [];
        }
        $standblaetter ??= $this->standblattModel->findForAnlassWithAdresse($anlassId);
        $schuesse = $this->schussdatenModel->findByAnlassId($anlassId);
        $bySheet = [];
        foreach ($schuesse as $schuss) $bySheet[(int) ($schuss['start_nr'] ?? 0)][] = $schuss;
        $purchases = $this->standblattModel->findSticheForAnlass($anlassId);
        $series = [];
        foreach ($standblaetter as $sheet) {
            $id = (int) $sheet['id'];
            $series[$id] = SerienService::build($purchases[$id] ?? [], $bySheet[$id] ?? [])['rows'];
        }
        $stichtag = $this->referenceDate($anlassDatum);

        $ranglisten = [];

        foreach ($stiche as $stich) {
            $externeNummer = $this->externalNumber($stich['anzeige_id'] ?? null);
            $kategorien = $this->emptyCategories();

            if ($externeNummer === '') {
                $ranglisten[] = [
                    'stich' => $stich,
                    'kategorien' => $kategorien,
                    'teilnehmer' => [],
                ];
                continue;
            }

            foreach ($standblaetter as $standblatt) {
                $standblattId = (int) $standblatt['id'];
                $rows = array_values(array_filter($series[$standblattId] ?? [], static fn (array $row): bool => (int) $row['stich_id'] === (int) $stich['id'] && !empty($row['vollstaendig'])));
                $resultate = array_column($rows, 'total');
                rsort($resultate, SORT_NUMERIC);
                if ($resultate === []) continue;

                $teilnehmer = [
                    'standblatt_id' => $standblattId,
                    'name' => trim((string) (($standblatt['vorname'] ?? '') . ' ' . ($standblatt['nachname'] ?? ''))),
                    'verein' => (string) (($standblatt['zusatz'] ?? '') ?: ($standblatt['firmen_anrede'] ?? '')),
                    'geburtsdatum' => $standblatt['geburtsdatum'] ?? null,
                    'total' => $resultate[0],
                    'summe_beste_zwei' => array_sum(array_slice($resultate, 0, 2)),
                    'resultate' => $resultate,
                    'schuss_count' => array_sum(array_map(static fn (array $row): int => count($row['schuesse']), $rows)),
                    'vollstaendige_serien' => count($rows),
                ];

                $kategorie = $this->categoryForBirthdate($standblatt['geburtsdatum'] ?? null, $stichtag);
                $kategorien[$kategorie]['teilnehmer'][] = $teilnehmer;
            }

            foreach ($kategorien as $key => $kategorie) {
                $teilnehmer = $kategorie['teilnehmer'];

                usort($teilnehmer, function (array $left, array $right): int {
                    $sumCompare = $right['summe_beste_zwei'] <=> $left['summe_beste_zwei'];

                    if ($sumCompare !== 0) {
                        return $sumCompare;
                    }

                    $resultCompare = $this->compareResultate($left['resultate'] ?? [], $right['resultate'] ?? []);

                    if ($resultCompare !== 0) {
                        return $resultCompare;
                    }

                    return strcmp((string) $left['name'], (string) $right['name'])
                        ?: ((int) $left['standblatt_id'] <=> (int) $right['standblatt_id']);
                });

                foreach ($teilnehmer as $rang => $teilnehmerRow) {
                    $teilnehmer[$rang]['rang'] = $rang + 1;
                }

                $kategorien[$key]['teilnehmer'] = $teilnehmer;
            }

            $ranglisten[] = [
                'stich' => $stich,
                'kategorien' => $kategorien,
                'teilnehmer' => array_merge(
                    $kategorien['u18']['teilnehmer'],
                    $kategorien['ue18']['teilnehmer']
                ),
            ];
        }

        return $ranglisten;
    }

    /**
     * Ermittelt die aktuelle Finalqualifikation nach den Anlassregeln.
     */
    public function finalStatusForStandblatt(int $anlassId, int $standblattId, array $anlass): array
    {
        $status = ['qualifiziert' => false, 'rang' => null, 'kategorie' => null, 'stich' => null];
        $stichId = (int) ($anlass['final_stich_id'] ?? 0);
        if ($stichId <= 0) {
            return $status;
        }

        $standblaetter = $this->standblattModel->findForAnlassWithAdresse($anlassId);
        $bestaetigte = [];
        foreach ($standblaetter as $blatt) {
            $bestaetigte[(int) $blatt['id']] = !empty($blatt['final_teilnahme']);
        }
        $datum = (string) (($anlass['start_anlass'] ?? '') ?: ($anlass['end_anlass'] ?? ''));
        foreach ($this->buildForAnlass($anlassId, $datum, $stichId, $standblaetter) as $rangliste) {
            if ((int) ($rangliste['stich']['id'] ?? 0) !== $stichId) {
                continue;
            }

            $status['stich'] = (string) $rangliste['stich']['name'];
            foreach ($rangliste['kategorien'] as $key => $kategorie) {
                $anzahl = max(0, (int) ($anlass['final_anzahl_' . $key] ?? 6));
                $belegt = 0;
                foreach ($kategorie['teilnehmer'] as $teilnehmer) {
                    if ((int) $teilnehmer['standblatt_id'] === $standblattId) {
                        return array_merge($status, [
                            'qualifiziert' => count($teilnehmer['resultate']) >= 2 && $belegt < $anzahl,
                            'rang' => (int) $teilnehmer['rang'],
                            'kategorie' => (string) $kategorie['label'],
                        ]);
                    }
                    if (count($teilnehmer['resultate']) >= 2 && !empty($bestaetigte[(int) $teilnehmer['standblatt_id']])) {
                        $belegt++;
                    }
                }
            }
        }

        return $status;
    }

    /**
     * Liefert die Standard-Kategorien fuer die Rangliste.
     */
    private function emptyCategories(): array
    {
        return [
            'u18' => [
                'label' => 'U18',
                'teilnehmer' => [],
            ],
            'ue18' => [
                'label' => 'Ü18',
                'teilnehmer' => [],
            ],
        ];
    }

    /**
     * Vergleicht Resultatserien fuer die Rangsortierung.
     */
    private function compareResultate(array $leftResultate, array $rightResultate): int
    {
        $maxCount = max(count($leftResultate), count($rightResultate));

        for ($index = 0; $index < $maxCount; $index++) {
            $left = (float) ($leftResultate[$index] ?? 0);
            $right = (float) ($rightResultate[$index] ?? 0);
            $compare = $right <=> $left;

            if ($compare !== 0) {
                return $compare;
            }
        }

        return 0;
    }

    /**
     * Ordnet Teilnehmende anhand des Jahrgangs im Anlassjahr einer Alterskategorie zu.
     */
    private function categoryForBirthdate(mixed $birthdate, ?\DateTimeImmutable $stichtag): string
    {
        $age = Altersregel::alter($birthdate, $stichtag?->format('Y-m-d'));
        return $age !== null && $age < 18 ? 'u18' : 'ue18';
    }

    /**
     * Erzeugt den Stichtag fuer Altersberechnungen.
     */
    private function referenceDate(?string $value): ?\DateTimeImmutable
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Normalisiert externe Schussnummern fuer die Anzeige.
     */
    private function externalNumber(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

}
