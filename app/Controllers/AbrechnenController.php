<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Adressen;
use App\Models\Gaben;
use App\Models\Schussdaten;
use App\Models\Standblatt;
use App\Services\AbrechnungsService;
use App\Services\AnlassService;
use App\Services\AuthService;
use App\Services\RanglistenService;

final class AbrechnenController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
        private readonly Adressen $adressenModel,
        private readonly Standblatt $standblattModel,
        private readonly Schussdaten $schussdatenModel,
        private readonly Gaben $gabenModel,
        private readonly AbrechnungsService $abrechnungsService,
        private readonly RanglistenService $ranglistenService,
    ) {
    }

    /**
     * Zeigt die Abrechnung eines Standblatts mit Resultaten und Gabenvergleich.
     */
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
            'finalStatus' => $this->ranglistenService->finalStatusForStandblatt(
                (int) $anlass['id'],
                (int) $standblatt['id'],
                $anlass
            ),
        ], $abrechnung));
    }

    /**
     * Speichert die ausgewaehlten Gaben fuer ein Standblatt.
     */
    public function speichern(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $items = $this->abrechnungsService->itemsFromPostedGaben(
            (int) $anlass['id'],
            (int) $standblatt['id'],
            (array) ($_POST['gaben'] ?? []),
            (array) ($_POST['abgegeben'] ?? [])
        );

        $this->gabenModel->replaceAbgabenForStandblatt((int) $standblatt['id'], $items, (int) $user['id']);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    /**
     * Speichert den Teilnahmewunsch unabhaengig von der aktuellen Qualifikation.
     */
    public function updateFinalTeilnahme(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);

        $this->standblattModel->updateFinalTeilnahme(
            (int) $standblatt['id'],
            ($_POST['final_teilnahme'] ?? '') === '1',
            (int) $user['id']
        );

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    /**
     * Aktualisiert das Datum eines Standblatts aus der Abrechnung.
     */
    public function updateDatum(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);

        $this->standblattModel->update((int) $standblatt['id'], [
            'id_anlass' => (int) $anlass['id'],
            'id_adresse' => (int) $standblatt['id_adresse'],
            'datum' => $this->nullableString($_POST['datum'] ?? null),
            'kosten' => $standblatt['kosten'] ?? null,
            'updated_by_user_id' => (int) $user['id'],
        ]);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    /**
     * Fuegt einen manuell korrigierten Schuss hinzu.
     */
    public function storeSchuss(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $primaerwertung = $this->nullableDecimal($_POST['primaerwertung'] ?? null);
        $externeNummer = $this->nullableString($_POST['externe_nummer'] ?? null);

        if ($primaerwertung !== null && $externeNummer !== null) {
            $matchIndex = $this->schussdatenModel->nextMatchIndex((int) $standblatt['id'], (int) $anlass['id']);
            $schussZeit = $this->datetimeValue($_POST['schuss_zeit'] ?? null) ?? date('Y-m-d H:i:s');

            $this->schussdatenModel->create([
                'id_anlass' => (int) $anlass['id'],
                'start_nr' => (string) $standblatt['id'],
                'primaerwertung' => $primaerwertung,
                'sekundaerwertung' => $this->nullableDecimal($_POST['sekundaerwertung'] ?? null),
                'schussart' => 'Korrektur',
                'schuss_zeit' => $schussZeit,
                'mouche' => isset($_POST['mouche']) ? 1 : 0,
                'match_index' => $matchIndex,
                'stich_index' => $matchIndex,
                'ins_del' => 0,
                'log_event' => 'MANUAL_INSERT',
                'log_typ' => 'INFO',
                'externe_nummer' => $externeNummer,
                'import_hash' => hash('sha256', implode('|', [
                    'manual',
                    (int) $anlass['id'],
                    (int) $standblatt['id'],
                    $externeNummer,
                    $primaerwertung,
                    $schussZeit,
                    microtime(true),
                ])),
                'created_by_user_id' => (int) $user['id'],
                'updated_by_user_id' => (int) $user['id'],
            ]);

            $this->gabenModel->resetAbgabenForStandblatt((int) $standblatt['id'], (int) $user['id']);
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    /**
     * Korrigiert einen vorhandenen Schuss.
     */
    public function updateSchuss(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $schuss = $this->findSchussOrFail((int) ($params['schussId'] ?? 0), (int) $anlass['id'], (int) $standblatt['id']);
        $primaerwertung = $this->nullableDecimal($_POST['primaerwertung'] ?? null);
        $externeNummer = $this->nullableString($_POST['externe_nummer'] ?? null);

        if ($primaerwertung !== null && $externeNummer !== null) {
            $this->schussdatenModel->updateManualCorrection((int) $schuss['id'], [
                'primaerwertung' => $primaerwertung,
                'sekundaerwertung' => array_key_exists('sekundaerwertung', $_POST)
                    ? $this->nullableDecimal($_POST['sekundaerwertung'])
                    : $schuss['sekundaerwertung'],
                'externe_nummer' => $externeNummer,
                'schuss_zeit' => $this->datetimeValue($_POST['schuss_zeit'] ?? null) ?? $schuss['schuss_zeit'],
                'mouche' => isset($_POST['mouche']) ? 1 : 0,
                'updated_by_user_id' => (int) $user['id'],
            ]);

            $this->gabenModel->resetAbgabenForStandblatt((int) $standblatt['id'], (int) $user['id']);
        }

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    /**
     * Entfernt einen Schuss aus der Auswertung.
     */
    public function deleteSchuss(array $params): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->findAnlassOrFail((int) ($params['id'] ?? 0));
        $standblatt = $this->findStandblattOrFail((int) ($params['standblattId'] ?? 0), (int) $anlass['id']);
        $schuss = $this->findSchussOrFail((int) ($params['schussId'] ?? 0), (int) $anlass['id'], (int) $standblatt['id']);

        $this->schussdatenModel->markDeleted((int) $schuss['id'], (int) $user['id']);
        $this->gabenModel->resetAbgabenForStandblatt((int) $standblatt['id'], (int) $user['id']);

        Response::redirect('/anlass/' . (int) $anlass['id'] . '/loesen/' . (int) $standblatt['id'] . '/abrechnen');
    }

    /**
     * Rendert die druckoptimierte Abrechnung eines Standblatts.
     */
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
     * Sucht einen Schuss innerhalb des aktuellen Standblatts.
     */
    private function findSchussOrFail(int $id, int $anlassId, int $standblattId): array
    {
        if ($id <= 0) {
            Response::notFound('Schuss nicht gefunden');
        }

        $schuss = $this->schussdatenModel->findById($id);

        if (
            $schuss === null
            || (int) $schuss['id_anlass'] !== $anlassId
            || (int) $schuss['start_nr'] !== $standblattId
        ) {
            Response::notFound('Schuss nicht gefunden');
        }

        return $schuss;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $value = trim(str_replace(',', '.', (string) ($value ?? '')));

        return is_numeric($value) ? $value : null;
    }

    private function datetimeValue(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }
}
