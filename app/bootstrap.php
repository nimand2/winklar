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
use App\Models\Gaben;
use App\Services\AnlassService;
use App\Services\AbrechnungsService;
use App\Services\ApiTokenService;
use App\Services\AuthService;
use App\Services\ClientApiService;
use App\Services\KassenService;
use App\Services\RanglistenService;
use App\Models\Stich;

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

function app_ranglisten_service(): RanglistenService
{
    static $ranglistenService = null;

    if ($ranglistenService instanceof RanglistenService) {
        return $ranglistenService;
    }

    $ranglistenService = new RanglistenService(new Stich(), new Standblatt(), new Schussdaten());

    return $ranglistenService;
}

function app_kassen_service(): KassenService
{
    static $kassenService = null;

    if ($kassenService instanceof KassenService) {
        return $kassenService;
    }

    $kassenService = new KassenService(new Standblatt(), new Gaben());

    return $kassenService;
}

function app_abrechnungs_service(): AbrechnungsService
{
    static $abrechnungsService = null;

    if ($abrechnungsService instanceof AbrechnungsService) {
        return $abrechnungsService;
    }

    $abrechnungsService = new AbrechnungsService(new Standblatt(), new Schussdaten(), new Gaben());

    return $abrechnungsService;
}
