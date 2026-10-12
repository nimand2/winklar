<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$router = new App\Core\Router();
$controller = new class {
    public array $calls = [];
    public function __call(string $name, array $arguments): void { $this->calls[] = [$name, $arguments]; }
};
$authController = $anlassController = $adressenController = $loesenController = $abrechnenController = $dashboardController = $homeController = $finalController = $clientApiController = $controller;
require dirname(__DIR__, 2) . '/routes/web.php';
require dirname(__DIR__, 2) . '/routes/api.php';
foreach ([
    ['POST', '/api/login', 'login', []],
    ['GET', '/api/anlaesse', 'anlaesse', []],
    ['GET', '/api/anlaesse/9/shooters', 'shooters', ['id' => '9']],
    ['GET', '/api/anlaesse/9/shooters/new?sinceId=10', 'newShooters', ['id' => '9']],
    ['POST', '/api/anlaesse/9/shots/import', 'importShots', ['id' => '9']],
    ['GET', '/anlass/9/loesen/16', 'show', ['id' => '9', 'standblattId' => '16']],
] as [$method, $path, $handler, $params]) {
    $router->dispatch($method, $path);
    if (array_pop($controller->calls) !== [$handler, [$params]]) {
        throw new RuntimeException('Unexpected route handler: ' . $path);
    }
}
echo "Route tests passed: API paths, handlers and parameters preserved.\n";
