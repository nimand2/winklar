<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

spl_autoload_register(static function (string $className): void {
    $prefix = 'App\\';

    if (strncmp($className, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($className, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

use App\Models\RememberToken;
use App\Models\Schussdaten;
use App\Models\Standblatt;
use App\Models\User;
use App\Models\Anlass;
use App\Services\AnlassService;
use App\Services\ApiTokenService;
use App\Services\AuthService;
use App\Services\ClientApiService;

function app_auth(): AuthService
{
    static $authService = null;

    if ($authService instanceof AuthService) {
        return $authService;
    }

    $authService = new AuthService(new User(), new RememberToken());
    $authService->boot();

    return $authService;
}

function app_anlass_service(): AnlassService
{
    static $anlassService = null;

    if ($anlassService instanceof AnlassService) {
        return $anlassService;
    }

    $anlassService = new AnlassService(new Anlass());

    return $anlassService;
}

function app_api_token_service(): ApiTokenService
{
    static $apiTokenService = null;

    if ($apiTokenService instanceof ApiTokenService) {
        return $apiTokenService;
    }

    $apiTokenService = new ApiTokenService();

    return $apiTokenService;
}

function app_client_api_service(): ClientApiService
{
    static $clientApiService = null;

    if ($clientApiService instanceof ClientApiService) {
        return $clientApiService;
    }

    $clientApiService = new ClientApiService(new Anlass(), new Standblatt(), new Schussdaten());

    return $clientApiService;
}
