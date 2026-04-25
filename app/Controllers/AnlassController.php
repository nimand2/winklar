<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Auszeichnungslimitten;
use App\Models\Gaben;
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
