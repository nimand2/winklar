<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
use App\Models\Standblatt;
use App\Services\AnlassService;
use App\Services\AuthService;

final class LoesenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Adressen $adressenModel,
        private readonly Standblatt $standblattModel,
    ) {
    }

    public function create(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $adresse = $this->findAdresseOrFail((int) ($_GET['adresse_id'] ?? 0), (int) $anlass['id']);

        $this->render('loesen/loesenNew', [
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'errors' => [],
            'old' => [
                'datum' => date('Y-m-d'),
                'kosten' => '',
            ],
        ]);
    }

    public function store(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $adresse = $this->findAdresseOrFail((int) ($_POST['adresse_id'] ?? 0), (int) $anlass['id']);
        $data = [
            'id_anlass' => (int) $anlass['id'],
            'id_adresse' => (int) $adresse['id'],
            'datum' => $this->nullableString($_POST['datum'] ?? null),
            'kosten' => $this->nullableString($_POST['kosten'] ?? null),
            'created_by_user_id' => (int) $user['id'],
            'updated_by_user_id' => (int) $user['id'],
        ];

        $this->standblattModel->create($data);

        Response::redirect('/anlass/' . (int) $anlass['id']);
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
}
