<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\AdressenController;
use App\Controllers\AbrechnenController;
use App\Controllers\AnlassController;
use App\Controllers\ClientApiController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\LoesenController;
use App\Core\Response;
use App\Core\Router;

$authController = new AuthController(app_auth());
$clientApiController = new ClientApiController(
    app_auth(),
    app_api_token_service(),
    app_client_api_service(),
    new App\Models\User()
);
$anlassController = new AnlassController(app_auth(), app_anlass_service(), new App\Models\Gaben(), new App\Models\Stich(), new App\Models\Auszeichnungslimitten(), new App\Models\Standblatt(), new App\Models\Schussdaten());
$adressenController = new AdressenController(app_auth(), app_anlass_service(), new App\Models\Adressen(), new App\Models\Plz());
$loesenController = new LoesenController(app_auth(), app_anlass_service(), new App\Models\Adressen(), new App\Models\Standblatt(), new App\Models\Stich(), new App\Models\Gaben());
$abrechnenController = new AbrechnenController(app_auth(), app_anlass_service(), new App\Models\Adressen(), new App\Models\Standblatt(), new App\Models\Schussdaten(), new App\Models\Gaben());
$dashboardController = new DashboardController(app_auth());
$homeController = new HomeController(app_auth());
$router = new Router();

$router->get('/', [$homeController, 'index']);

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/logout', [$authController, 'logout']);
$router->get('/dashboard', [$dashboardController, 'index']);
$router->get('/anlass', [$anlassController, 'index']);
$router->get('/anlass/neu', [$anlassController, 'create']);
$router->post('/anlass/neu', [$anlassController, 'store']);
$router->get('/anlass/{id}/bearbeiten', [$anlassController, 'edit']);
$router->post('/anlass/{id}/bearbeiten', [$anlassController, 'update']);
$router->get('/anlass/{id}/konfiguration', [$anlassController, 'konfiguration']);
$router->post('/anlass/{id}/konfiguration/gaben', [$anlassController, 'storeGabe']);
$router->post('/anlass/{id}/konfiguration/gaben/{gabeId}', [$anlassController, 'updateGabe']);
$router->post('/anlass/{id}/konfiguration/gaben/{gabeId}/loeschen', [$anlassController, 'deleteGabe']);
$router->post('/anlass/{id}/konfiguration/gaben-regeln', [$anlassController, 'storeRegel']);
$router->post('/anlass/{id}/konfiguration/gaben-regeln/{regelId}/loeschen', [$anlassController, 'deleteRegel']);
$router->get('/anlass/{id}/abschliessen', [$anlassController, 'abschliessen']);
$router->get('/anlass/{id}/kasse', [$anlassController, 'kasse']);
$router->get('/anlass/{id}/schuetzen', [$adressenController, 'index']);
$router->get('/anlass/{id}/schuetzen/neu', [$adressenController, 'index']);
$router->post('/anlass/{id}/schuetzen/neu', [$adressenController, 'store']);
$router->get('/anlass/{id}/loesen', [$loesenController, 'open']);
$router->get('/anlass/{id}/loesen/neu', [$loesenController, 'create']);
$router->post('/anlass/{id}/loesen/neu', [$loesenController, 'store']);
$router->get('/anlass/{id}/loesen/{standblattId}/abrechnen', [$abrechnenController, 'show']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen', [$abrechnenController, 'speichern']);
$router->get('/anlass/{id}/loesen/{standblattId}/abrechnen/druck', [$abrechnenController, 'druck']);
$router->get('/anlass/{id}/loesen/{standblattId}/druck', [$loesenController, 'druck']);
$router->get('/anlass/{id}/loesen/{standblattId}', [$loesenController, 'show']);
$router->post('/anlass/{id}/loesen/{standblattId}', [$loesenController, 'update']);
$router->get('/anlass/{id}', [$anlassController, 'show']);

$router->post('/api/login', [$clientApiController, 'login']);
$router->get('/api/anlaesse', [$clientApiController, 'anlaesse']);
$router->get('/api/anlaesse/{id}/shooters', [$clientApiController, 'shooters']);
$router->get('/api/anlaesse/{id}/shooters/new', [$clientApiController, 'newShooters']);
$router->post('/api/anlaesse/{id}/shots/import', [$clientApiController, 'importShots']);

$router->get('/index.php', static function (): never {
    Response::redirect('/');
});

$router->get('/login.php', static function (): never {
    Response::redirect('/login');
});

$router->post('/auth/login_process.php', [$authController, 'login']);
$router->get('/auth/login_process.php', static function (): never {
    Response::redirect('/login');
});

$router->get('/dashboard.php', static function (): never {
    Response::redirect('/dashboard');
});

$router->get('/logout.php', static function (): never {
    Response::redirect('/logout');
});

$router->get('/auth/require_login.php', static function (): never {
    Response::redirect('/dashboard');
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
