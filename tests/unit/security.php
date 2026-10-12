<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
session_save_path(sys_get_temp_dir());
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$check(!App\Core\Csrf::valid('invented'), 'Token must exist in session');
$token = App\Core\Csrf::token();
$check(strlen($token) === 64 && App\Core\Csrf::valid($token), 'Session token accepted');
$check(!App\Core\Csrf::valid(null) && !App\Core\Csrf::valid([]) && !App\Core\Csrf::valid('wrong'), 'Invalid tokens rejected');
$router = new App\Core\Router();
$called = false;
$router->post('/form', static function () use (&$called): void { $called = true; });
$_POST = [];
ob_start(); $router->dispatch('POST', '/form'); ob_end_clean();
$check(!$called && http_response_code() === 403, 'Reject before controller');
$_POST = ['csrf_token' => $token];
$router->dispatch('POST', '/form');
$check($called, 'Valid form reaches controller');
$called = false;
$_POST = [];
$router->post('/api/login', static function () use (&$called): void { $called = true; });
$router->dispatch('POST', '/api/login');
$check($called, 'Bearer API does not require session CSRF');
$service = new App\Services\ApiTokenService();
if (strlen(trim(API_TOKEN_SECRET)) < 32) {
    $failed = false;
    try { $service->createToken(1); } catch (RuntimeException $e) { $failed = str_contains($e->getMessage(), 'API_TOKEN_SECRET'); }
    $check($failed, 'Missing or short secret must fail explicitly');
} else {
    $token = $service->createToken(7);
    $check($service->userIdFromAuthorizationHeader('Bearer ' . $token) === 7, 'Signed token round trip');
    $check($service->userIdFromAuthorizationHeader('Bearer ' . $token . 'x') === null, 'Tampered signature rejected');
}
App\Core\Session::destroy();
echo "Security tests passed.\n";
