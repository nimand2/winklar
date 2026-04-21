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
use App\Models\User;
use App\Services\AuthService;

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
