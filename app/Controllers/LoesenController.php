<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
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
    ) {
    }

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

    public function druck(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse'], (int) $anlass['id']);
        $selectedStiche = $this->standblattModel->findSticheForStandblatt((int) $standblatt['id']);

        $this->render('loesen/standblattPrint', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
            'stiche' => $selectedStiche,
        ]);
    }

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

    private function findAdresseOrFail(int $id, int $anlassId): array
    {
        if ($id <= 0) {
            Response::redirect('/anlass/' . $anlassId . '/schuetzen/neu');
        }

        $adresse = $this->adressenModel->findById($id);

        if ($adresse === null) {
            http_response_code(404);
            echo 'Adresse nicht gefunden';
            exit;
        }

        return $adresse;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

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

    private function stichCountsById(array $stiche): array
    {
        $counts = [];

        foreach ($stiche as $stich) {
            $counts[(int) $stich['id']] = max(1, (int) ($stich['anzahl_stiche'] ?? 1));
        }

        return $counts;
    }
}
