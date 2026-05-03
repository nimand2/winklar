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

        $this->render('loesen/loesenNew', [
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

        $this->render('loesen/loesenOpen', [
            'user' => $user,
            'anlass' => $anlass,
            'standblaetter' => $this->standblattModel->findForAnlassWithAdresse((int) $anlass['id']),
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
        $selectedStichCounts = $this->selectedStichCountsFromRequest($stiche);
        $data = [
            'id_anlass' => (int) $anlass['id'],
            'id_adresse' => (int) $adresse['id'],
            'datum' => $this->nullableString($_POST['datum'] ?? null),
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

        $this->render('loesen/loesenEdit', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
            'stiche' => $this->stichModel->findByAnlassId((int) $anlass['id']),
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
        $selectedStichCounts = $this->selectedStichCountsFromRequest($stiche);

        $data = [
            'id_anlass' => (int) $anlass['id'],
            'id_adresse' => (int) $adresse['id'],
            'datum' => $this->nullableString($_POST['datum'] ?? null),
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
     * Extrahiert und validiert Stichmengen aus dem Formularrequest.
     */
    private function selectedStichCountsFromRequest(array $availableStiche): array
    {
        $availableIds = array_map(
            static fn (array $stich): int => (int) $stich['id'],
            $availableStiche
        );
        $selectedIds = array_map('intval', (array) ($_POST['stich_ids'] ?? []));
        $selectedIds = array_values(array_unique($selectedIds));
        $selectedIds = array_values(array_intersect($selectedIds, $availableIds));
        $postedCounts = (array) ($_POST['stich_counts'] ?? []);
        $selectedStichCounts = [];

        foreach ($selectedIds as $stichId) {
            $count = (int) ($postedCounts[$stichId] ?? 1);
            $selectedStichCounts[$stichId] = max(1, $count);
        }

        return $selectedStichCounts;
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
