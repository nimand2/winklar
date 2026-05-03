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
use App\Services\KassenService;
use App\Services\RanglistenService;

final class AnlassController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Gaben $gabenModel,
        private readonly Stich $stichModel,
        private readonly Auszeichnungslimitten $auszeichnungslimittenModel,
        private readonly RanglistenService $ranglistenService,
        private readonly KassenService $kassenService,
    )
    {
    }

    /**
     * Zeigt die Anlassauswahl.
     */
    public function index(): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->anlassService->getAnlass();

        $this->render('anlass/index', [
            'user' => $user,
            'anlass' => $anlass,
        ]);
    }

    /**
     * Zeigt Detail- und Aktionsseite eines Anlasses.
     */
    public function show(array $params): void
    {
        $user = $this->authService->requireUser();
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            Response::notFound('Anlass nicht gefunden');
        }

        $anlass = $this->anlassService->getAnlassById($id);

        if ($anlass === null) {
            Response::notFound('Anlass nicht gefunden');
        }

        $this->render('anlass/show', [
            'user' => $user,
            'anlass' => $anlass,
        ]);
    }

    /**
     * Zeigt das Formular fuer einen neuen Anlass.
     */
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

    /**
     * Validiert und erstellt einen neuen Anlass.
     */
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

    /**
     * Zeigt das Bearbeitungsformular fuer Anlassgrunddaten.
     */
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

    /**
     * Aktualisiert Anlassgrunddaten.
     */
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

    /**
     * Zeigt die Konfiguration von Stichen, Gaben und Gabenregeln.
     */
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

    /**
     * Zeigt die Rangliste fuer einen Anlass.
     */
    public function abschliessen(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));

        $this->render('anlass/rangliste', [
            'user' => $user,
            'anlass' => $anlass,
            'ranglisten' => $this->ranglistenService->buildForAnlass(
                (int) $anlass['id'],
                (string) (($anlass['start_anlass'] ?? '') ?: ($anlass['end_anlass'] ?? ''))
            ),
        ]);
    }

    /**
     * Zeigt die Kassenabrechnung fuer einen Anlass.
     */
    public function kasse(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));

        $this->render('anlass/kasse', [
            'user' => $user,
            'anlass' => $anlass,
            'abrechnung' => $this->kassenService->buildAbrechnung((int) $anlass['id']),
        ]);
    }

    /**
     * Erstellt einen neuen Stich fuer den Anlass.
     */
    public function storeStich(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $data = $this->sanitizeStichData($_POST, (int) $anlass['id'], (int) $user['id']);

        if ($this->validateStichData($data) === []) {
            $this->stichModel->create($data);
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    /**
     * Aktualisiert einen Stich des Anlasses.
     */
    public function updateStich(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $stich = $this->stichModel->findById((int) ($params['stichId'] ?? 0));

        if ($stich !== null && (int) $stich['id_anlass'] === (int) $anlass['id']) {
            $data = $this->sanitizeStichData($_POST, (int) $anlass['id'], (int) $user['id'], $stich);

            if ($this->validateStichData($data) === []) {
                $this->stichModel->update((int) $stich['id'], $data);
            }
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    /**
     * Loescht einen Stich des Anlasses.
     */
    public function deleteStich(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $stich = $this->stichModel->findById((int) ($params['stichId'] ?? 0));

        if ($stich !== null && (int) $stich['id_anlass'] === (int) $anlass['id']) {
            $this->stichModel->delete((int) $stich['id']);
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    /**
     * Erstellt eine neue Gabe.
     */
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

    /**
     * Aktualisiert eine bestehende Gabe.
     */
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

    /**
     * Loescht eine Gabe.
     */
    public function deleteGabe(array $params): void
    {
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $this->authService->requireUser();
        $this->gabenModel->delete((int) ($params['gabeId'] ?? 0));

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    /**
     * Erstellt eine neue Regel zur Gabenvergabe.
     */
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

    /**
     * Loescht eine Regel zur Gabenvergabe.
     */
    public function deleteRegel(array $params): void
    {
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $this->authService->requireUser();
        $this->auszeichnungslimittenModel->delete((int) ($params['regelId'] ?? 0));

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    /**
     * Sucht einen Anlass oder bricht mit 404 ab.
     */
    private function findAnlassOrFail(int $id): array
    {
        if ($id <= 0) {
            Response::notFound('Anlass nicht gefunden');
        }

        $anlass = $this->anlassService->getAnlassById($id);

        if ($anlass === null) {
            Response::notFound('Anlass nicht gefunden');
        }

        return $anlass;
    }

    /**
     * Normalisiert Anlassdaten aus Formularwerten.
     */
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

    /**
     * Validiert Pflichtfelder und Datumslogik eines Anlasses.
     */
    private function validateAnlassData(array $data): array
    {
        $errors = [];

        if ($data['name_anlass'] === '') {
            $errors[] = 'Bitte gib einen Namen für den Anlass ein.';
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

    /**
     * Normalisiert Stichdaten aus Formularwerten.
     */
    private function sanitizeStichData(array $source, int $anlassId, int $userId, array $existing = []): array
    {
        return [
            'id_anlass' => $anlassId,
            'id_disziplin' => $this->nullableInt($source['id_disziplin'] ?? null),
            'name' => trim((string) ($source['name'] ?? '')),
            'short_name' => $this->nullableString($source['short_name'] ?? null),
            'anzeige_id' => $this->nullableString($source['anzeige_id'] ?? null),
            'scheibe' => $this->nullableString($source['scheibe'] ?? null),
            'wertigkeit' => $this->nullableDecimal($source['wertigkeit'] ?? null),
            'anzahl_schuss' => $this->nullableInt($source['anzahl_schuss'] ?? null),
            'anzahl_passen' => $this->nullableInt($source['anzahl_passen'] ?? null),
            'preis' => $this->nullableDecimal($source['preis'] ?? null),
            'verbindung' => $this->nullableString($source['verbindung'] ?? null),
            'created_by_user_id' => $existing['created_by_user_id'] ?? $userId,
            'updated_by_user_id' => $userId,
        ];
    }

    /**
     * Validiert Pflichtfelder eines Stiches.
     */
    private function validateStichData(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Bitte gib einen Namen für den Stich ein.';
        }

        return $errors;
    }

    /**
     * Wandelt leere Formularwerte in null um.
     */
    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Wandelt optionale Dezimalwerte aus Formularen um.
     */
    private function nullableDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        return $value === '' ? null : (float) str_replace(',', '.', $value);
    }

    /**
     * Wandelt optionale Ganzzahlen aus Formularen um.
     */
    private function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : max(0, (int) $value);
    }
}
