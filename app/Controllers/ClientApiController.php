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

    public function login(): void
    {
        $payload = $this->jsonPayload();
        $username = trim((string) ($payload['Username'] ?? $payload['username'] ?? ''));
        $password = (string) ($payload['Password'] ?? $payload['password'] ?? '');

        if ($username === '' || $password === '') {
            JsonResponse::error('Benutzername und Passwort sind erforderlich.', 400, 'INVALID_INPUT');
            return;
        }

        $user = $this->authService->findUserByLogin($username);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            JsonResponse::error('Ungueltige Anmeldedaten.', 401, 'INVALID_CREDENTIALS');
            return;
        }

        JsonResponse::send([
            'token' => $this->tokenService->createToken((int) $user['id']),
            'expiresIn' => $this->tokenService->lifetimeSeconds(),
        ]);
    }

    public function anlaesse(): void
    {
        if (!$this->requireApiUser()) {
            return;
        }

        JsonResponse::send($this->clientApiService->listAnlaesse());
    }

    public function shooters(array $params): void
    {
        if (!$this->requireApiUser()) {
            return;
        }

        $anlassId = (int) ($params['id'] ?? 0);
        if ($anlassId <= 0) {
            JsonResponse::error('Ungueltiger Anlass.', 400, 'INVALID_ANLASS');
            return;
        }

        JsonResponse::send($this->clientApiService->listShooters($anlassId));
    }

    public function newShooters(array $params): void
    {
        if (!$this->requireApiUser()) {
            return;
        }

        $anlassId = (int) ($params['id'] ?? 0);
        $sinceId = (int) ($_GET['sinceId'] ?? 0);

        if ($anlassId <= 0) {
            JsonResponse::error('Ungueltiger Anlass.', 400, 'INVALID_ANLASS');
            return;
        }

        JsonResponse::send($this->clientApiService->listShooters($anlassId, $sinceId));
    }

    public function importShots(array $params): void
    {
        $user = $this->requireApiUser();
        if ($user === null) {
            return;
        }

        $anlassId = (int) ($params['id'] ?? 0);
        $payload = $this->jsonPayload();
        $payloadAnlassId = (int) ($payload['AnlassId'] ?? $payload['anlassId'] ?? 0);
        $shots = $payload['Shots'] ?? $payload['shots'] ?? [];

        if ($anlassId <= 0 || ($payloadAnlassId > 0 && $payloadAnlassId !== $anlassId)) {
            JsonResponse::error('Ungueltiger Anlass.', 400, 'INVALID_ANLASS');
            return;
        }

        if (!is_array($shots)) {
            JsonResponse::error('Shots muss ein Array sein.', 400, 'INVALID_INPUT');
            return;
        }

        try {
            $created = $this->clientApiService->importShots($anlassId, $shots, (int) $user['id']);
        } catch (Throwable $exception) {
            JsonResponse::error('Schussdaten konnten nicht importiert werden.', 500, 'IMPORT_FAILED');
            return;
        }

        JsonResponse::send([
            'success' => true,
            'imported' => $created,
        ], 201);
    }

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

    private function jsonPayload(): array
    {
        $rawBody = file_get_contents('php://input');
        if ($rawBody === false || trim($rawBody) === '') {
            return [];
        }

        $payload = json_decode($rawBody, true);

        return is_array($payload) ? $payload : [];
    }

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
