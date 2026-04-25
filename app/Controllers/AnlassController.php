<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Auszeichnungslimitten;
use App\Models\Gaben;
use App\Models\Schussdaten;
use App\Models\Standblatt;
use App\Models\Stich;
use App\Services\AuthService;
use App\Services\AnlassService;

final class AnlassController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Gaben $gabenModel,
        private readonly Stich $stichModel,
        private readonly Auszeichnungslimitten $auszeichnungslimittenModel,
        private readonly Standblatt $standblattModel,
        private readonly Schussdaten $schussdatenModel,
    )
    {
    }

    public function index(): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->anlassService->getAnlass();

        $this->render('anlass/index', [
            'user' => $user,
            'anlass' => $anlass,
        ]);
    }

    public function show(array $params): void
    {
        $user = $this->authService->requireUser();
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(404);
            echo 'Anlass nicht gefunden';
            return;
        }

        $anlass = $this->anlassService->getAnlassById($id);

        if ($anlass === null) {
            http_response_code(404);
            echo 'Anlass nicht gefunden';
            return;
        }

        $this->render('anlass/show', [
            'user' => $user,
            'anlass' => $anlass,
        ]);
    }

    public function create(): void
    {
        $user = $this->authService->requireUser();

        $this->render('anlass/form', [
            'user' => $user,
            'mode' => 'create',
            'anlass' => [],
            'old' => [],
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $user = $this->authService->requireUser();
        $data = $this->sanitizeAnlassData($_POST, (int) $user['id']);
        $errors = $this->validateAnlassData($data);

        if ($errors !== []) {
            $this->render('anlass/form', [
                'user' => $user,
                'mode' => 'create',
                'anlass' => [],
                'old' => $_POST,
                'errors' => $errors,
            ]);
            return;
        }

        $id = $this->anlassService->createAnlass($data);

        Response::redirect('/anlass/' . $id . '/konfiguration');
    }

    public function edit(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));

        $this->render('anlass/form', [
            'user' => $user,
            'mode' => 'edit',
            'anlass' => $anlass,
            'old' => $anlass,
            'errors' => [],
        ]);
    }

    public function update(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $data = $this->sanitizeAnlassData($_POST, (int) $user['id'], $anlass);
        $errors = $this->validateAnlassData($data);

        if ($errors !== []) {
            $this->render('anlass/form', [
                'user' => $user,
                'mode' => 'edit',
                'anlass' => $anlass,
                'old' => $_POST,
                'errors' => $errors,
            ]);
            return;
        }

        $this->anlassService->updateAnlass((int) $anlass['id'], $data);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function konfiguration(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));

        $this->render('anlass/konfiguration', [
            'user' => $user,
            'anlass' => $anlass,
            'stiche' => $this->stichModel->findByAnlassId((int) $anlass['id']),
            'gaben' => $this->gabenModel->getAll(),
            'regeln' => $this->auszeichnungslimittenModel->findByAnlassId((int) $anlass['id']),
            'errors' => [],
        ]);
    }

    public function abschliessen(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));

        $this->render('anlass/rangliste', [
            'user' => $user,
            'anlass' => $anlass,
            'ranglisten' => $this->buildRanglisten((int) $anlass['id']),
        ]);
    }

    public function kasse(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));

        $this->render('anlass/kasse', [
            'user' => $user,
            'anlass' => $anlass,
            'abrechnung' => $this->buildKassenAbrechnung((int) $anlass['id']),
        ]);
    }

    public function storeGabe(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name !== '') {
            $this->gabenModel->create([
                'name' => $name,
                'punktwert' => $this->nullableDecimal($_POST['punktwert'] ?? null) ?? 0,
                'preis' => $this->nullableDecimal($_POST['preis'] ?? null) ?? 0,
                'anzahl' => max(0, (int) ($_POST['anzahl'] ?? 0)),
                'created_by_user_id' => (int) $user['id'],
                'updated_by_user_id' => (int) $user['id'],
            ]);
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function updateGabe(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $gabe = $this->gabenModel->findById((int) ($params['gabeId'] ?? 0));

        if ($gabe !== null) {
            $this->gabenModel->update((int) $gabe['id'], [
                'name' => trim((string) ($_POST['name'] ?? $gabe['name'])),
                'punktwert' => $this->nullableDecimal($_POST['punktwert'] ?? null) ?? 0,
                'preis' => $this->nullableDecimal($_POST['preis'] ?? null) ?? 0,
                'anzahl' => max(0, (int) ($_POST['anzahl'] ?? 0)),
                'created_by_user_id' => $gabe['created_by_user_id'] ?? null,
                'updated_by_user_id' => (int) $user['id'],
            ]);
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function deleteGabe(array $params): void
    {
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $this->authService->requireUser();
        $this->gabenModel->delete((int) ($params['gabeId'] ?? 0));

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function storeRegel(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $stich = $this->stichModel->findById((int) ($_POST['stich_id'] ?? 0));

        if ($stich !== null && (int) $stich['id_anlass'] === (int) $anlass['id']) {
            $this->auszeichnungslimittenModel->create([
                'stich_id' => (int) $stich['id'],
                'gaben_id' => (int) ($_POST['gaben_id'] ?? 0) ?: null,
                'min_wert' => $this->nullableDecimal($_POST['min_wert'] ?? null),
                'max_wert' => $this->nullableDecimal($_POST['max_wert'] ?? null),
                'min_alter' => $this->nullableInt($_POST['min_alter'] ?? null),
                'max_alter' => $this->nullableInt($_POST['max_alter'] ?? null),
                'created_by_user_id' => (int) $user['id'],
                'updated_by_user_id' => (int) $user['id'],
            ]);
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function deleteRegel(array $params): void
    {
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $this->authService->requireUser();
        $this->auszeichnungslimittenModel->delete((int) ($params['regelId'] ?? 0));

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
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

    private function sanitizeAnlassData(array $source, int $userId, array $existing = []): array
    {
        return [
            'fk_adress_id_creator' => $existing['fk_adress_id_creator'] ?? null,
            'fk_adress_id_modifier' => $existing['fk_adress_id_modifier'] ?? null,
            'name_anlass' => trim((string) ($source['name_anlass'] ?? '')),
            'shortname_anlass' => $this->nullableString($source['shortname_anlass'] ?? null),
            'start_anlass' => $this->nullableString($source['start_anlass'] ?? null),
            'end_anlass' => $this->nullableString($source['end_anlass'] ?? null),
            'created_by_user_id' => $existing['created_by_user_id'] ?? $userId,
            'updated_by_user_id' => $userId,
        ];
    }

    private function validateAnlassData(array $data): array
    {
        $errors = [];

        if ($data['name_anlass'] === '') {
            $errors[] = 'Bitte gib einen Namen fuer den Anlass ein.';
        }

        if (
            $data['start_anlass'] !== null
            && $data['end_anlass'] !== null
            && $data['end_anlass'] < $data['start_anlass']
        ) {
            $errors[] = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
        }

        return $errors;
    }

    private function buildRanglisten(int $anlassId): array
    {
        $stiche = $this->stichModel->findByAnlassId($anlassId);
        $standblaetter = $this->standblattModel->findForAnlassWithAdresse($anlassId);
        $schuesse = $this->schussdatenModel->findByAnlassId($anlassId);
        $schuesseByStandblattAndStich = [];

        foreach ($schuesse as $schuss) {
            if ((int) ($schuss['ins_del'] ?? 0) !== 0) {
                continue;
            }

            $standblattId = (int) ($schuss['start_nr'] ?? 0);
            $stichIndex = (int) ($schuss['stich_index'] ?? 0);

            if ($standblattId <= 0 || $stichIndex <= 0) {
                continue;
            }

            $schuesseByStandblattAndStich[$standblattId][$stichIndex][] = $schuss;
        }

        $ranglisten = [];

        foreach ($stiche as $position => $stich) {
            $stichIndex = $this->stichIndex($stich, $position);
            $teilnehmer = [];

            foreach ($standblaetter as $standblatt) {
                $standblattId = (int) $standblatt['id'];
                $stichSchuesse = $schuesseByStandblattAndStich[$standblattId][$stichIndex] ?? [];

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

    private function buildKassenAbrechnung(int $anlassId): array
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

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        return $value === '' ? null : (float) str_replace(',', '.', $value);
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : max(0, (int) $value);
    }
}
