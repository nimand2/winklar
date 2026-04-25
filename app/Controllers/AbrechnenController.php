<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
use App\Models\Gaben;
use App\Models\Standblatt;
use App\Services\AbrechnungsService;
use App\Services\AnlassService;
use App\Services\AuthService;

final class AbrechnenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Adressen $adressenModel,
        private readonly Standblatt $standblattModel,
        private readonly Gaben $gabenModel,
        private readonly AbrechnungsService $abrechnungsService,
    ) {
    }

    public function show(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse']);
        $abrechnung = $this->abrechnungsService->buildViewData((int) $anlass['id'], (int) $standblatt['id']);

        $this->render('abrechnen/abrechnen', array_merge([
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
        ], $abrechnung));
    }

    public function speichern(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $items = $this->abrechnungsService->itemsFromPostedGaben(
            (int) $anlass['id'],
            (int) $standblatt['id'],
            (array) ($_POST['gaben'] ?? [])
        );

        $this->gabenModel->replaceAbgabenForStandblatt((int) $standblatt['id'], $items, (int) $user['id']);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    public function druck(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $adresse = $this->findAdresseOrFail((int) $standblatt['id_adresse']);
        $abrechnung = $this->abrechnungsService->buildViewData((int) $anlass['id'], (int) $standblatt['id']);

        $this->render('abrechnen/abrechnenPrint', array_merge([
            'user' => $user,
            'anlass' => $anlass,
            'adresse' => $adresse,
            'standblatt' => $standblatt,
        ], $abrechnung));
    }

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
}
