<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
use App\Models\Gaben;
use App\Models\Standblatt;
use App\Models\Stich;
use App\Services\AnlassService;
use App\Services\AuthService;

final class LoesenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Adressen $adressenModel,
        private readonly Standblatt $standblattModel,
        private readonly Stich $stichModel,
        private readonly Gaben $gabenModel,
    ) {
    }

    /**
     * Zeigt das Formular zum Loesen eines neuen Standblatts fuer eine Adresse.
     */
    public function create(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $adresse = $this->findAdresseOrFail((int) ($_GET['adresse_id'] ?? 0), (int) $anlass['id']);
        $stiche = $this->stichModel->findByAnlassId((int) $anlass['id']);

        $this->render('loesen/create', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'stiche' => $stiche,
            'errors' => [],
            'old' => [
                'datum' => date('Y-m-d'),
                'kosten' => '',
                'stich_ids' => [],
                'stich_counts' => [],
            ],
        ]);
    }

    /**
     * Zeigt die vorhandenen Standblaetter eines Anlasses zur Auswahl.
     */
    public function open(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $nummer = trim((string) ($_GET['nummer'] ?? ''));

        $this->render('loesen/index', [
            'user' => $user,
            'anlass' => $anlass,
            'standblaetter' => $this->standblattModel->findForAnlassWithAdresse((int) $anlass['id'], $nummer),
            'nummer' => $nummer,
        ]);
    }

    /**
     * Zeigt die Adressauswahl vor dem Loesen eines neuen Standblatts.
     */
    public function selectAdresse(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $query = trim((string) ($_GET['q'] ?? ''));

        $this->render('loesen/adresseSelect', [
            'user' => $user,
            'anlass' => $anlass,
            'adressen' => $this->adressenModel->search($query),
            'query' => $query,
        ]);
    }

    /**
     * Erstellt ein Standblatt mit den ausgewaehlten Stichen.
     */
    public function store(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $adresse = $this->findAdresseOrFail((int) ($_POST['adresse_id'] ?? 0), (int) $anlass['id']);
        $stiche = $this->stichModel->findByAnlassId((int) $anlass['id']);
        $form = \App\Validation\StandblattFormService::parse($_POST, $stiche);
        if ($form['errors'] !== []) {
            http_response_code(422);
            $this->render(isset($standblatt) ? 'loesen/edit' : 'loesen/create', [
                'user' => $user, 'anlass' => $anlass, 'adresse' => $adresse, 'stiche' => $stiche,
                'standblatt' => $standblatt ?? [], 'old' => ['datum' => is_string($_POST['datum'] ?? null) ? $_POST['datum'] : '', 'stich_ids' => array_keys($form['counts']), 'stich_counts' => $form['counts']],
                'errors' => $form['errors'], 'selectedStichCounts' => $form['counts'],
            ]);
            return;
        }
        $selectedStichCounts = $form['counts'];
        $data = [
            'id_anlass' => (int) $anlass['id'],
            'id_adresse' => (int) $adresse['id'],
            'datum' => $form['datum'],
            'kosten' => $this->calculateKosten($stiche, $selectedStichCounts),
            'created_by_user_id' => (int) $user['id'],
            'updated_by_user_id' => (int) $user['id'],
        ];

        $standblattId = $this->standblattModel->createWithStiche($data, $selectedStichCounts, (int) $user['id']);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . $standblattId);
    }

    /**
     * Zeigt ein bestehendes Standblatt zur Bearbeitung.
     */
    public function show(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse'], (int) $anlass['id']);
        $selectedStiche = $this->standblattModel->findSticheForStandblatt((int) $standblatt['id']);

        $available = $this->stichModel->findByAnlassId((int) $anlass['id']);
        foreach ($available as &$row) foreach ($selectedStiche as $saved) if ((int) $row['id'] === (int) $saved['id']) $row = array_merge($row, $saved);
        unset($row);
        $this->render('loesen/edit', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
            'stiche' => $available,
            'selectedStichCounts' => $this->stichCountsById($selectedStiche),
            'errors' => [],
            'old' => [
                'datum' => (string) ($standblatt['datum'] ?? ''),
                'kosten' => (string) ($standblatt['kosten'] ?? ''),
            ],
        ]);
    }

    /**
     * Rendert die Druckansicht fuer ein Standblatt.
     */
    public function druck(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse'], (int) $anlass['id']);
        $selectedStiche = $this->standblattModel->findSticheForStandblatt((int) $standblatt['id']);
        $gaben = $this->gabenModel->getAll();
        usort($gaben, static fn (array $left, array $right): int => (float) $left['punktwert'] <=> (float) $right['punktwert']);

        $this->render('loesen/standblattPrint', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
            'stiche' => $selectedStiche,
            'gaben' => $gaben,
        ]);
    }

    /**
     * Aktualisiert Datum und Stichmengen eines Standblatts.
     */
    public function update(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse'], (int) $anlass['id']);
        $stiche = $this->stichModel->findByAnlassId((int) $anlass['id']);
        $savedStiche = $this->standblattModel->findSticheForStandblatt((int) $standblatt['id']);
        foreach ($stiche as &$row) foreach ($savedStiche as $saved) if ((int) $row['id'] === (int) $saved['id']) $row = array_merge($row, $saved);
        unset($row);
        $form = \App\Validation\StandblattFormService::parse($_POST, $stiche);
        if ($form['errors'] !== []) {
            http_response_code(422);
            $this->render(isset($standblatt) ? 'loesen/edit' : 'loesen/create', [
                'user' => $user, 'anlass' => $anlass, 'adresse' => $adresse, 'stiche' => $stiche,
                'standblatt' => $standblatt ?? [], 'old' => ['datum' => is_string($_POST['datum'] ?? null) ? $_POST['datum'] : '', 'stich_ids' => array_keys($form['counts']), 'stich_counts' => $form['counts']],
                'errors' => $form['errors'], 'selectedStichCounts' => $form['counts'],
            ]);
            return;
        }
        $selectedStichCounts = $form['counts'];

        $data = [
            'id_anlass' => (int) $anlass['id'],
            'id_adresse' => (int) $adresse['id'],
            'datum' => $form['datum'],
            'kosten' => $this->calculateKosten($stiche, $selectedStichCounts),
            'updated_by_user_id' => (int) $user['id'],
        ];

        $this->standblattModel->updateWithStiche((int) $standblatt['id'], $data, $selectedStichCounts, (int) $user['id']);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id']);
    }

    /**
     * Sucht ein Standblatt innerhalb eines Anlasses oder bricht mit 404 ab.
     */
    private function findStandblattOrFail(int $id, int $anlassId): array
    {
        if ($id <= 0) {
            Response::notFound('Standblatt nicht gefunden');
        }

        $standblatt = $this->standblattModel->findById($id);

        if ($standblatt === null || (int) $standblatt['id_anlass'] !== $anlassId) {
            Response::notFound('Standblatt nicht gefunden');
        }

        return $standblatt;
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
     * Sucht eine Adresse oder leitet zur Adressauswahl weiter.
     */
    private function findAdresseOrFail(int $id, int $anlassId): array
    {
        if ($id <= 0) {
            Response::redirect('/anlass/' . $anlassId . '/loesen/adresse');
        }

        $adresse = $this->adressenModel->findById($id);

        if ($adresse === null) {
            Response::notFound('Adresse nicht gefunden');
        }

        return $adresse;
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
     * Berechnet die Kosten anhand der gewaehlten Stiche und Mengen.
     */
    private function calculateKosten(array $availableStiche, array $selectedStichCounts): string
    {
        $total = 0.0;

        foreach ($availableStiche as $stich) {
            $stichId = (int) $stich['id'];

            if (!array_key_exists($stichId, $selectedStichCounts)) {
                continue;
            }

            $total += (float) ($stich['preis'] ?? 0) * max(1, (int) $selectedStichCounts[$stichId]);
        }

        return number_format($total, 2, '.', '');
    }

    /**
     * Wandelt gespeicherte Standblatt-Stiche in eine ID-zu-Menge-Map.
     */
    private function stichCountsById(array $stiche): array
    {
        $counts = [];

        foreach ($stiche as $stich) {
            $counts[(int) $stich['id']] = max(1, (int) ($stich['anzahl_stiche'] ?? 1));
        }

        return $counts;
    }
}
