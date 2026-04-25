<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
use App\Models\Gaben;
use App\Models\Schussdaten;
use App\Models\Standblatt;
use App\Services\AnlassService;
use App\Services\AuthService;

final class AbrechnenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Adressen $adressenModel,
        private readonly Standblatt $standblattModel,
        private readonly Schussdaten $schussdatenModel,
        private readonly Gaben $gabenModel,
    ) {
    }

    public function show(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse']);
        $stiche = $this->standblattModel->findSticheForStandblatt((int) $standblatt['id']);
        $schuesse = $this->schussdatenModel->findByStartNrAndIdAnlass((int) $standblatt['id'], (int) $anlass['id']);
        $auswertung = $this->buildAuswertung($stiche, $schuesse);
        $regeln = $this->gabenModel->findRegelnForStiche($this->stichIdsFromAuswertung($auswertung));
        $savedAbgaben = $this->gabenModel->findAbgabenForStandblatt((int) $standblatt['id']);
        $defaultSelected = !$this->gabenModel->areAbgabenGeprueft((int) $standblatt['id']);
        $gaben = $this->buildGabenVergleich($auswertung['rows'], $regeln, $savedAbgaben, $defaultSelected);

        $this->render('abrechnen/abrechnen', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
            'stiche' => $stiche,
            'schuesse' => $schuesse,
            'auswertung' => $auswertung,
            'gabenVergleich' => $gaben,
        ]);
    }

    public function speichern(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $stiche = $this->standblattModel->findSticheForStandblatt((int) $standblatt['id']);
        $schuesse = $this->schussdatenModel->findByStartNrAndIdAnlass((int) $standblatt['id'], (int) $anlass['id']);
        $auswertung = $this->buildAuswertung($stiche, $schuesse);
        $regeln = $this->gabenModel->findRegelnForStiche($this->stichIdsFromAuswertung($auswertung));
        $selectable = $this->buildSelectableAbgaben($auswertung['rows'], $regeln);
        $posted = (array) ($_POST['gaben'] ?? []);
        $items = [];

        foreach ($posted as $stichId => $gabenIds) {
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

        $this->gabenModel->replaceAbgabenForStandblatt((int) $standblatt['id'], array_values($items), (int) $user['id']);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    public function druck(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse']);
        $stiche = $this->standblattModel->findSticheForStandblatt((int) $standblatt['id']);
        $schuesse = $this->schussdatenModel->findByStartNrAndIdAnlass((int) $standblatt['id'], (int) $anlass['id']);
        $auswertung = $this->buildAuswertung($stiche, $schuesse);
        $regeln = $this->gabenModel->findRegelnForStiche($this->stichIdsFromAuswertung($auswertung));
        $savedAbgaben = $this->gabenModel->findAbgabenForStandblatt((int) $standblatt['id']);
        $defaultSelected = !$this->gabenModel->areAbgabenGeprueft((int) $standblatt['id']);
        $gaben = $this->buildGabenVergleich($auswertung['rows'], $regeln, $savedAbgaben, $defaultSelected);

        $this->render('abrechnen/abrechnenPrint', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
            'stiche' => $stiche,
            'schuesse' => $schuesse,
            'auswertung' => $auswertung,
            'gabenVergleich' => $gaben,
        ]);
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

    private function findStandblattOrFail(int $id, int $anlassId): array
    {
        if ($id <= 0) {
            http_response_code(404);
            echo 'Standblatt nicht gefunden';
            exit;
        }

        $standblatt = $this->standblattModel->findById($id);

        if ($standblatt === null || (int) $standblatt['id_anlass'] !== $anlassId) {
            http_response_code(404);
            echo 'Standblatt nicht gefunden';
            exit;
        }

        return $standblatt;
    }

    private function findAnlassOrFail(int $id): array
    {
        if ($id <= 0) {
            http_response_code(404);
            echo 'Anlass nicht gefunden';
            exit;
        }

        $anlass = $this->anlassService->getAnlassById($id);

        if ($anlass === null) {
            http_response_code(404);
            echo 'Anlass nicht gefunden';
            exit;
        }

        return $anlass;
    }

    private function findAdresseOrFail(int $id): array
    {
        if ($id <= 0) {
            http_response_code(404);
            echo 'Adresse nicht gefunden';
            exit;
        }

        $adresse = $this->adressenModel->findById($id);

        if ($adresse === null) {
            http_response_code(404);
            echo 'Adresse nicht gefunden';
            exit;
        }

        return $adresse;
    }
}
