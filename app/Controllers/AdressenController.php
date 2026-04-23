<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
use App\Services\AnlassService;
use App\Services\AuthService;

final class AdressenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Adressen $adressenModel,
    ) {
    }

    public function index(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));

        $this->render('clients/indes', [
            'user' => $user,
            'anlass' => $anlass,
            'adressen' => $this->adressenModel->getAll(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $data = $this->addressDataFromRequest($user);
        $errors = $this->validateAddressData($data);

        if ($errors !== []) {
            $this->render('clients/indes', [
                'user' => $user,
                'anlass' => $anlass,
                'adressen' => $this->adressenModel->getAll(),
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $adresseId = $this->adressenModel->create($data);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/neu?adresse_id=' . $adresseId);
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

    private function addressDataFromRequest(array $user): array
    {
        return [
            'anrede' => trim((string) ($_POST['anrede'] ?? '')),
            'firmen_anrede' => trim((string) ($_POST['firmen_anrede'] ?? '')),
            'nachname' => trim((string) ($_POST['nachname'] ?? '')),
            'vorname' => trim((string) ($_POST['vorname'] ?? '')),
            'zusatz' => trim((string) ($_POST['zusatz'] ?? '')),
            'strasse' => trim((string) ($_POST['strasse'] ?? '')),
            'postfach' => trim((string) ($_POST['postfach'] ?? '')),
            'nation' => trim((string) ($_POST['nation'] ?? '')),
            'telefon' => trim((string) ($_POST['telefon'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'notiz' => trim((string) ($_POST['notiz'] ?? '')),
            'geburtsdatum' => $this->nullableString($_POST['geburtsdatum'] ?? null),
            'lizenz' => trim((string) ($_POST['lizenz'] ?? '')),
            'created_by_user_id' => (int) $user['id'],
            'updated_by_user_id' => (int) $user['id'],
        ];
    }

    private function validateAddressData(array $data): array
    {
        if ((string) $data['nachname'] === '') {
            return ['Bitte gib mindestens einen Nachnamen ein.'];
        }

        return [];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
