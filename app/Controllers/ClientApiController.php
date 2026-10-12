<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\JsonResponse;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Services\AuthService;
use App\Services\ClientApiService;
use Throwable;

final class ClientApiController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ApiTokenService $tokenService,
        private readonly ClientApiService $clientApiService,
        private readonly User $userModel,
    ) {
    }

    /**
     * Authentifiziert externe Clients und liefert einen Bearer-Token.
     */
    public function login(): void
    {
        $payload = $this->jsonPayload();
        $login = $payload['Username'] ?? $payload['username'] ?? null;
        $secret = $payload['Password'] ?? $payload['password'] ?? null;
        $username = is_string($login) ? trim($login) : '';
        $password = is_string($secret) ? $secret : '';

        if ($username === '' || $password === '' || mb_strlen($username, 'UTF-8') > 150 || strlen($password) > 4096) {
            JsonResponse::error('Benutzername und Passwort sind erforderlich.', 400, 'INVALID_INPUT');
            return;
        }

        try {
            $user = $this->authService->validateCredentials($username, $password);
        } catch (\App\Core\LoginLimitExceeded $exception) {
            header('Retry-After: 900');
            JsonResponse::error($exception->getMessage(), 429, 'TOO_MANY_ATTEMPTS');
            return;
        }
        if ($user === null) {
            JsonResponse::error('Ungültige Anmeldedaten.', 401, 'INVALID_CREDENTIALS');
            return;
        }

        JsonResponse::send([
            'token' => $this->tokenService->createToken((int) $user['id']),
            'expiresIn' => $this->tokenService->lifetimeSeconds(),
        ]);
    }

    /**
     * Liefert alle Anlaesse fuer authentifizierte API-Clients.
     */
    public function anlaesse(): void
    {
        if (!$this->requireApiUser()) {
            return;
        }

        JsonResponse::send($this->clientApiService->listAnlaesse());
    }

    /**
     * Liefert alle Schuetzen beziehungsweise Standblaetter eines Anlasses.
     */
    public function shooters(array $params): void
    {
        if (!$this->requireApiUser()) {
            return;
        }

        $anlassId = (int) ($params['id'] ?? 0);
        if ($anlassId <= 0) {
            JsonResponse::error('Ungültiger Anlass.', 400, 'INVALID_ANLASS');
            return;
        }

        JsonResponse::send($this->clientApiService->listShooters($anlassId));
    }

    /**
     * Liefert nur seit einer Start-ID neu hinzugekommene Schuetzen.
     */
    public function newShooters(array $params): void
    {
        if (!$this->requireApiUser()) {
            return;
        }

        $anlassId = (int) ($params['id'] ?? 0);
        $input = new \App\Validation\FormInput($_GET);
        $sinceId = $input->integer('sinceId', 0, PHP_INT_MAX) ?? 0;
        if ($input->errors !== []) throw new \InvalidArgumentException(implode(' ', $input->errors));

        if ($anlassId <= 0) {
            JsonResponse::error('Ungültiger Anlass.', 400, 'INVALID_ANLASS');
            return;
        }

        JsonResponse::send($this->clientApiService->listShooters($anlassId, $sinceId));
    }

    /**
     * Importiert Schussdaten eines Clients fuer einen Anlass.
     */
    public function importShots(array $params): void
    {
        $user = $this->requireApiUser();
        if ($user === null) {
            return;
        }

        $anlassId = (int) ($params['id'] ?? 0);
        $payload = $this->jsonPayload();
        $input = new \App\Validation\FormInput(['AnlassId' => $payload['AnlassId'] ?? $payload['anlassId'] ?? null]);
        $payloadAnlassId = $input->integer('AnlassId', 1, PHP_INT_MAX);
        if ($input->errors !== []) throw new \InvalidArgumentException(implode(' ', $input->errors));
        $shots = $payload['Shots'] ?? $payload['shots'] ?? null;

        if ($anlassId <= 0 || ($payloadAnlassId > 0 && $payloadAnlassId !== $anlassId)) {
            JsonResponse::error('Ungültiger Anlass.', 400, 'INVALID_ANLASS');
            return;
        }

        if (!is_array($shots)) {
            JsonResponse::error('Shots muss ein Array sein.', 400, 'INVALID_INPUT');
            return;
        }

        try {
            $created = $this->clientApiService->importShots($anlassId, $shots, (int) $user['id']);
        } catch (\InvalidArgumentException $exception) {
            JsonResponse::error($exception->getMessage(), 422, 'INVALID_INPUT');
            return;
        } catch (Throwable $exception) {
            JsonResponse::error('Schussdaten konnten nicht importiert werden.', 500, 'IMPORT_FAILED');
            return;
        }

        JsonResponse::send([
            'success' => true,
            'imported' => $created,
            // createMany commits all normalised rows in one transaction.
            // Existing import hashes count as accepted, not newly imported.
            'accepted' => count($shots),
            'duplicates' => count($shots) - $created,
        ], 201);
    }

    /**
     * Validiert den Bearer-Token und liefert den zugehoerigen Benutzer.
     */
    private function requireApiUser(): ?array
    {
        $userId = $this->tokenService->userIdFromAuthorizationHeader($this->authorizationHeader());
        if ($userId === null) {
            JsonResponse::error('Authentifizierung erforderlich.', 401, 'UNAUTHORIZED');
            return null;
        }

        $user = $this->userModel->findById($userId);
        if ($user === null) {
            JsonResponse::error('Authentifizierung erforderlich.', 401, 'UNAUTHORIZED');
            return null;
        }

        return $user;
    }

    /**
     * Liest den JSON-Requestbody als Array.
     */
    private function jsonPayload(): array
    {
        return \App\Core\JsonRequest::decode(file_get_contents('php://input') ?: '');
    }

    /**
     * Ermittelt den Authorization-Header auch hinter Redirect/FastCGI Setups.
     */
    private function authorizationHeader(): ?string
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return null;
    }
}
