<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\AdressenController;
use App\Controllers\AbrechnenController;
use App\Controllers\AnlassController;
use App\Controllers\FinalController;
use App\Controllers\ClientApiController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\LoesenController;
use App\Core\Router;

$authController = new AuthController(app_auth());
$clientApiController = new ClientApiController(
    app_auth(),
    app_api_token_service(),
    app_client_api_service(),
    new App\Models\User()
);
$anlassController = new AnlassController(app_auth(), app_anlass_service(), new App\Models\Gaben(), new App\Models\Stich(), new App\Models\Auszeichnungslimitten(), app_ranglisten_service(), app_kassen_service());
$adressenController = new AdressenController(app_auth(), app_anlass_service(), new App\Models\Adressen(), new App\Models\Plz());
$loesenController = new LoesenController(app_auth(), app_anlass_service(), new App\Models\Adressen(), new App\Models\Standblatt(), new App\Models\Stich(), new App\Models\Gaben());
$abrechnenController = new AbrechnenController(app_auth(), app_anlass_service(), new App\Models\Adressen(), new App\Models\Standblatt(), new App\Models\Schussdaten(), new App\Models\Gaben(), app_abrechnungs_service(), app_ranglisten_service());
$dashboardController = new DashboardController(app_auth());
$homeController = new HomeController(app_auth());
$finalController = new FinalController(app_auth(), app_anlass_service(), new App\Services\FinalService(app_ranglisten_service(), new App\Models\Standblatt()), new App\Models\Standblatt(), new App\Models\Gaben());
$router = new Router();

require dirname(__DIR__) . '/routes/web.php';
require dirname(__DIR__) . '/routes/api.php';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
