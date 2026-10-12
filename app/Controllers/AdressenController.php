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

    /**
     * Zeigt die globale oder anlassbezogene Adressverwaltung mit Suche.
     */
    public function index(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $query = trim((string) ($_GET['q'] ?? ''));

        $this->render('adressen/index', [
            'anlass' => $anlass,
            'adressen' => $this->adressenModel->search($query),
            'query' => $query,
            'errors' => [],
        ]);
    }

    /**
     * Zeigt das Formular fuer eine neue Adresse.
     */
    public function create(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);

        $this->render('adressen/form', [
            'anlass' => $anlass,
            'adresse' => null,
            'plzOptions' => $this->plzModel->getActiveOptions(),
            'errors' => [],
            'old' => [],
        ]);
    }

    /**
     * Speichert eine neue Adresse und leitet je nach Kontext weiter.
     */
    public function store(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $data = $this->addressDataFromRequest($user);
        $plz = $this->plzModel->findByLookup($data['plz_lookup']);
        $data['plz_id'] = $plz['id'] ?? null;
        $errors = $this->validateAddressData($data);

        if ($errors !== []) {
            http_response_code(422);
            $this->render('adressen/form', [
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

    /**
     * Zeigt das Bearbeitungsformular fuer eine bestehende Adresse.
     */
    public function edit(array $params): void
    {
        $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $adresse = $this->findAdresseOrFail((int) ($params['adresseId'] ?? 0));

        $this->render('adressen/form', [
            'anlass' => $anlass,
            'adresse' => $adresse,
            'plzOptions' => $this->plzModel->getActiveOptions(),
            'errors' => [],
            'old' => $this->addressDataForForm($adresse),
        ]);
    }

    /**
     * Aktualisiert eine bestehende Adresse.
     */
    public function update(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findOptionalAnlass($params);
        $adresse = $this->findAdresseOrFail((int) ($params['adresseId'] ?? 0));
        $data = $this->addressDataFromRequest($user);
        $plz = $this->plzModel->findByLookup($data['plz_lookup']);
        $data['plz_id'] = $plz['id'] ?? null;
        $data['creator_adress_id'] = $adresse['creator_adress_id'] ?? null;
        $data['modifier_adress_id'] = $adresse['modifier_adress_id'] ?? null;
        $data['passwort'] = $adresse['passwort'] ?? null;
        $data['created_by_user_id'] = $adresse['created_by_user_id'] ?? null;
        $data['updated_by_user_id'] = (int) $user['id'];
        $errors = $this->validateAddressData($data);

        if ($errors !== []) {
            http_response_code(422);
            $this->render('adressen/form', [
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

    /**
     * Liefert den optionalen Anlasskontext aus Routenparametern.
     */
    private function findOptionalAnlass(array $params): ?array
    {
        if (!isset($params['id'])) {
            return null;
        }

        return $this->findAnlassOrFail((int) $params['id']);
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
     * Sucht eine Adresse oder bricht mit 404 ab.
     */
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

    /**
     * Normalisiert Adressdaten aus dem Formularrequest.
     */
    private function addressDataFromRequest(array $user): array
    {
        $input = new \App\Validation\FormInput($_POST);
        $data = [];
        foreach (['anrede'=>50, 'firmen_anrede'=>100, 'nachname'=>150, 'vorname'=>150, 'zusatz'=>150, 'strasse'=>190, 'postfach'=>100, 'nation'=>100, 'plz_lookup'=>200, 'telefon'=>50, 'email'=>190, 'notiz'=>10000, 'lizenz'=>100] as $field=>$length) {
            $data[$field] = $input->text($field, $length, $field === 'nachname') ?? '';
        }
        $data['geburtsdatum'] = $input->date('geburtsdatum');
        if ($data['geburtsdatum'] !== null && $data['geburtsdatum'] > date('Y-m-d')) $input->errors[] = 'Geburtsdatum darf nicht in der Zukunft liegen.';
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $input->errors[] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        $data['created_by_user_id'] = (int) $user['id'];
        $data['updated_by_user_id'] = (int) $user['id'];
        $data['_errors'] = $input->errors;
        return $data;
    }

    /**
     * Prueft Pflichtfelder und die ausgewaehlte PLZ.
     */
    private function validateAddressData(array $data): array
    {
        if (($data['_errors'] ?? []) !== []) return $data['_errors'];
        if ((string) $data['nachname'] === '') {
            return ['Bitte gib mindestens einen Nachnamen ein.'];
        }

        if ((string) ($data['plz_lookup'] ?? '') !== '' && empty($data['plz_id'])) {
            return ['Bitte wähle eine gültige PLZ aus der Liste aus.'];
        }

        return [];
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
     * Bereitet eine vorhandene Adresse fuer das Formular vor.
     */
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
