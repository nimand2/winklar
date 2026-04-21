<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Core\Response;
use App\Core\Router;

$authController = new AuthController(app_auth());
$dashboardController = new DashboardController(app_auth());
$homeController = new HomeController(app_auth());
$router = new Router();

$router->get('/', [$homeController, 'index']);

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->get('/logout', [$authController, 'logout']);
$router->get('/dashboard', [$dashboardController, 'index']);

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
