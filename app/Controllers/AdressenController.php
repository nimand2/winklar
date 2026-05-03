<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
use App\Models\Plz;
use App\Services\AnlassService;
use App\Services\AuthService;

final class AdressenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Adressen $adressenModel,
        private readonly Plz $plzModel,
    ) {
    }

    public function index(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $query = trim((string) ($_GET['q'] ?? ''));

        $this->render('clients/indes', [
            'anlass' => $anlass,
            'adressen' => $this->adressenModel->search($query),
            'query' => $query,
            'errors' => [],
        ]);
    }

    public function create(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);

        $this->render('clients/form', [
            'anlass' => $anlass,
            'adresse' => null,
            'plzOptions' => $this->plzModel->getActiveOptions(),
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $data = $this->addressDataFromRequest($user);
        $plz = $this->plzModel->findByLookup((string) ($_POST['plz_lookup'] ?? ''));
        $data['plz_id'] = $plz['id'] ?? null;
        $errors = $this->validateAddressData($data);

        if ($errors !== []) {
            $this->render('clients/form', [
                'anlass' => $anlass,
                'adresse' => null,
                'plzOptions' => $this->plzModel->getActiveOptions(),
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $adresseId = $this->adressenModel->create($data);

        Response::redirect($anlass === null ? '/schuetzen' : '/anlass/' . (int) $anlass['id'] . '/loesen/neu?adresse_id=' . $adresseId);
    }

    public function edit(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $adresse = $this->findAdresseOrFail((int) ($params['adresseId'] ?? 0));

        $this->render('clients/form', [
            'anlass' => $anlass,
            'adresse' => $adresse,
            'plzOptions' => $this->plzModel->getActiveOptions(),
            'errors' => [],
            'old' => $this->addressDataForForm($adresse),
        ]);
    }

    public function update(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $adresse = $this->findAdresseOrFail((int) ($params['adresseId'] ?? 0));
        $data = $this->addressDataFromRequest($user);
        $plz = $this->plzModel->findByLookup((string) ($_POST['plz_lookup'] ?? ''));
        $data['plz_id'] = $plz['id'] ?? null;
        $data['creator_adress_id'] = $adresse['creator_adress_id'] ?? null;
        $data['modifier_adress_id'] = $adresse['modifier_adress_id'] ?? null;
        $data['passwort'] = $adresse['passwort'] ?? null;
        $data['created_by_user_id'] = $adresse['created_by_user_id'] ?? null;
        $data['updated_by_user_id'] = (int) $user['id'];
        $errors = $this->validateAddressData($data);

        if ($errors !== []) {
            $this->render('clients/form', [
                'anlass' => $anlass,
                'adresse' => $adresse,
                'plzOptions' => $this->plzModel->getActiveOptions(),
                'errors' => $errors,
                'old' => $data,
            ]);
            return;
        }

        $this->adressenModel->update((int) $adresse['id'], $data);

        Response::redirect($anlass === null ? '/schuetzen' : '/anlass/' . (int) $anlass['id'] . '/schuetzen');
    }

    private function findOptionalAnlass(array $params): ?array
    {
        if (!isset($params['id'])) {
            return null;
        }

        return $this->findAnlassOrFail((int) $params['id']);
    }

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

    private function findAdresseOrFail(int $id): array
    {
        if ($id <= 0) {
            Response::notFound('Adresse nicht gefunden');
        }

        $adresse = $this->adressenModel->findById($id);

        if ($adresse === null) {
            Response::notFound('Adresse nicht gefunden');
        }

        return $adresse;
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
            'plz_lookup' => trim((string) ($_POST['plz_lookup'] ?? '')),
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

        if ((string) ($data['plz_lookup'] ?? '') !== '' && empty($data['plz_id'])) {
            return ['Bitte wähle eine gültige PLZ aus der Liste aus.'];
        }

        return [];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function addressDataForForm(array $adresse): array
    {
        $plzLookup = trim((string) (($adresse['plz4'] ?? '') . ' ' . ($adresse['ortschaftsname'] ?? '')));

        return [
            'anrede' => $adresse['anrede'] ?? '',
            'firmen_anrede' => $adresse['firmen_anrede'] ?? '',
            'nachname' => $adresse['nachname'] ?? '',
            'vorname' => $adresse['vorname'] ?? '',
            'zusatz' => $adresse['zusatz'] ?? '',
            'strasse' => $adresse['strasse'] ?? '',
            'postfach' => $adresse['postfach'] ?? '',
            'nation' => $adresse['nation'] ?? '',
            'plz_lookup' => $plzLookup,
            'telefon' => $adresse['telefon'] ?? '',
            'email' => $adresse['email'] ?? '',
            'notiz' => $adresse['notiz'] ?? '',
            'geburtsdatum' => $adresse['geburtsdatum'] ?? null,
            'lizenz' => $adresse['lizenz'] ?? '',
        ];
    }
}
