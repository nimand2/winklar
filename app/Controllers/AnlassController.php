<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Validation\AnlassFormService;
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
        $data = \App\Validation\AnlassFormService::sanitizeAnlassData($_POST, (int) $user['id']);
        $errors = \App\Validation\AnlassFormService::validateAnlassData($data);

        if ($errors !== []) {
            http_response_code(422);
            $this->render('anlass/form', [
                'user' => $user,
                'mode' => 'create',
                'anlass' => [],
                'old' => array_filter($_POST, 'is_scalar'),
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
        $data = \App\Validation\AnlassFormService::sanitizeAnlassData($_POST, (int) $user['id'], $anlass);
        $errors = \App\Validation\AnlassFormService::validateAnlassData($data);

        if ($errors !== []) {
            http_response_code(422);
            $this->render('anlass/form', [
                'user' => $user,
                'mode' => 'edit',
                'anlass' => $anlass,
                'old' => array_filter($_POST, 'is_scalar'),
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
     * Speichert den Finalstich und die Anzahl Finalplaetze je Kategorie.
     */
    public function updateFinalRegeln(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $stiche = $this->stichModel->findByAnlassId((int) $anlass['id']);
        $stichId = filter_var($_POST['final_stich_id'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $u18 = filter_var($_POST['final_anzahl_u18'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);
        $ue18 = filter_var($_POST['final_anzahl_ue18'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);
        $validIds = array_map(static fn (array $stich): int => (int) $stich['id'], $stiche);

        if ($stichId === false || $u18 === false || $ue18 === false || ($stichId !== 0 && !in_array($stichId, $validIds, true))) {
            http_response_code(422);
            $this->render('anlass/konfiguration', [
                'user' => $user,
                'anlass' => $anlass,
                'stiche' => $stiche,
                'gaben' => $this->gabenModel->getAll(),
                'regeln' => $this->auszeichnungslimittenModel->findByAnlassId((int) $anlass['id']),
                'errors' => ['Bitte einen Stich dieses Anlasses und je Kategorie eine Anzahl von 0 bis 1000 wählen.'],
                'oldSection' => 'final',
                'old' => array_filter($_POST, 'is_scalar'),
            ]);
            return;
        }

        $this->anlassService->updateFinalRegeln((int) $anlass['id'], $stichId ?: null, $u18, $ue18, (int) $user['id']);
        Response::redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
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
        $data = AnlassFormService::sanitizeStichData($_POST, (int) $anlass['id'], (int) $user['id']);
        $errors = AnlassFormService::validateStichData($data);
        if ($this->stichModel->hasAnzeigeId((int) $anlass['id'], $data['anzeige_id'])) {
            $errors[] = 'Diese Anzeige-ID wird in diesem Anlass bereits verwendet.';
        }
        if ($errors !== []) {
            $this->configurationError($anlass, $user, $errors, 'stich');
            return;
        }
        $this->stichModel->create($data);
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function updateStich(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $stich = $this->stichModel->findById((int) ($params['stichId'] ?? 0));
        if ($stich === null || (int) $stich['id_anlass'] !== (int) $anlass['id']) Response::notFound('Stich nicht gefunden');
        $data = AnlassFormService::sanitizeStichData($_POST, (int) $anlass['id'], (int) $user['id'], $stich);
        $errors = AnlassFormService::validateStichData($data);
        if ($this->stichModel->hasAnzeigeId((int) $anlass['id'], $data['anzeige_id'], (int) $stich['id'])) {
            $errors[] = 'Diese Anzeige-ID wird in diesem Anlass bereits verwendet.';
        }
        if ($errors !== []) {
            $this->configurationError($anlass, $user, $errors, 'stich', (int) $stich['id']);
            return;
        }
        $this->stichModel->update((int) $stich['id'], $data);
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function deleteStich(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $stich = $this->stichModel->findById((int) ($params['stichId'] ?? 0));
        if ($stich === null || (int) $stich['id_anlass'] !== (int) $anlass['id']) Response::notFound('Stich nicht gefunden');
        if (!$this->stichModel->delete((int) $stich['id'])) {
            $this->configurationError($anlass, $user, ['Dieser Stich gehört zu einem Standblatt oder einer Abgabe und kann nicht gelöscht werden.']);
            return;
        }
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function storeGabe(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $data = AnlassFormService::gabe($_POST, (int) $user['id']);
        if ($data['_errors'] !== []) {
            $this->configurationError($anlass, $user, $data['_errors'], 'gabe');
            return;
        }
        $this->gabenModel->create($data);
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function updateGabe(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $gabe = $this->gabenModel->findById((int) ($params['gabeId'] ?? 0));
        if ($gabe === null) Response::notFound('Gabe nicht gefunden');
        $data = AnlassFormService::gabe($_POST, (int) $user['id'], $gabe);
        if ($data['_errors'] !== []) {
            $this->configurationError($anlass, $user, $data['_errors'], 'gabe', (int) $gabe['id']);
            return;
        }
        $this->gabenModel->update((int) $gabe['id'], $data);
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function deleteGabe(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        if (!$this->gabenModel->delete((int) ($params['gabeId'] ?? 0))) {
            $this->configurationError($anlass, $user, ['Diese Gabe wird in Regeln oder Abgaben verwendet und kann nicht gelöscht werden.']);
            return;
        }
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function storeRegel(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $data = AnlassFormService::regel($_POST, (int) $user['id']);
        $stich = $this->stichModel->findById((int) ($data['stich_id'] ?? 0));
        if ($stich === null || (int) $stich['id_anlass'] !== (int) $anlass['id']) $data['_errors'][] = 'Bitte einen Stich dieses Anlasses wählen.';
        if ($this->gabenModel->findById((int) ($data['gaben_id'] ?? 0)) === null) $data['_errors'][] = 'Bitte eine vorhandene Gabe wählen.';
        if ($data['_errors'] !== []) {
            $this->configurationError($anlass, $user, $data['_errors'], 'regel');
            return;
        }
        $this->auszeichnungslimittenModel->create($data);
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    public function deleteRegel(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $regel = $this->auszeichnungslimittenModel->getById((int) ($params['regelId'] ?? 0));
        $stich = $regel === null ? null : $this->stichModel->findById((int) $regel['stich_id']);
        if ($stich === null || (int) $stich['id_anlass'] !== (int) $anlass['id']) Response::notFound('Regel nicht gefunden');
        $this->auszeichnungslimittenModel->delete((int) $regel['id']);
        $this->redirect('/anlass/' . (int) $anlass['id'] . '/konfiguration');
    }

    private function configurationError(array $anlass, array $user, array $errors, string $section = '', int $id = 0): void
    {
        http_response_code(422);
        $stiche = $this->stichModel->findByAnlassId((int) $anlass['id']);
        $gaben = $this->gabenModel->getAll();
        // Keep scalar submitted values so users can correct them without retyping.
        $old = array_filter($_POST, static fn ($value): bool => is_string($value));
        if ($id > 0) {
            if ($section === 'stich') foreach ($stiche as &$row) { if ((int) $row['id'] === $id) $row = array_merge($row, $old); }
            if ($section === 'gabe') foreach ($gaben as &$row) { if ((int) $row['id'] === $id) $row = array_merge($row, $old); }
            unset($row);
        }
        $this->render('anlass/konfiguration', [
            'user' => $user, 'anlass' => $anlass, 'stiche' => $stiche, 'gaben' => $gaben,
            'regeln' => $this->auszeichnungslimittenModel->findByAnlassId((int) $anlass['id']),
            'errors' => $errors, 'old' => $id === 0 ? $old : [], 'oldSection' => $section,
        ]);
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

}
