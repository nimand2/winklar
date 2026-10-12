<?php

declare(strict_types=1);

use App\Core\Response;

$router->get('/', [$homeController, 'index']);

$router->get('/login', [$authController, 'showLogin']);
$router->post('/login', [$authController, 'login']);
$router->post('/logout', [$authController, 'logout']);
$router->get('/dashboard', [$dashboardController, 'index']);
$router->get('/schuetzen', [$adressenController, 'index']);
$router->get('/schuetzen/neu', [$adressenController, 'create']);
$router->post('/schuetzen/neu', [$adressenController, 'store']);
$router->get('/schuetzen/{adresseId}/bearbeiten', [$adressenController, 'edit']);
$router->post('/schuetzen/{adresseId}/bearbeiten', [$adressenController, 'update']);
$router->get('/anlass', [$anlassController, 'index']);
$router->get('/anlass/neu', [$anlassController, 'create']);
$router->post('/anlass/neu', [$anlassController, 'store']);
$router->get('/anlass/{id}/bearbeiten', [$anlassController, 'edit']);
$router->post('/anlass/{id}/bearbeiten', [$anlassController, 'update']);
$router->get('/anlass/{id}/konfiguration', [$anlassController, 'konfiguration']);
$router->post('/anlass/{id}/konfiguration/final', [$anlassController, 'updateFinalRegeln']);
$router->post('/anlass/{id}/konfiguration/stiche', [$anlassController, 'storeStich']);
$router->post('/anlass/{id}/konfiguration/stiche/{stichId}', [$anlassController, 'updateStich']);
$router->post('/anlass/{id}/konfiguration/stiche/{stichId}/loeschen', [$anlassController, 'deleteStich']);
$router->post('/anlass/{id}/konfiguration/gaben', [$anlassController, 'storeGabe']);
$router->post('/anlass/{id}/konfiguration/gaben/{gabeId}', [$anlassController, 'updateGabe']);
$router->post('/anlass/{id}/konfiguration/gaben/{gabeId}/loeschen', [$anlassController, 'deleteGabe']);
$router->post('/anlass/{id}/konfiguration/gaben-regeln', [$anlassController, 'storeRegel']);
$router->post('/anlass/{id}/konfiguration/gaben-regeln/{regelId}/loeschen', [$anlassController, 'deleteRegel']);
$router->get('/anlass/{id}/abschliessen', [$anlassController, 'abschliessen']);
$router->get('/anlass/{id}/final', [$finalController, 'show']);
$router->get('/anlass/{id}/final/{kategorie}/druck', [$finalController, 'druck']);
$router->get('/anlass/{id}/final/{kategorie}/export', [$finalController, 'export']);
$router->get('/anlass/{id}/kasse', [$anlassController, 'kasse']);
$router->get('/anlass/{id}/schuetzen', [$adressenController, 'index']);
$router->get('/anlass/{id}/schuetzen/neu', [$adressenController, 'create']);
$router->post('/anlass/{id}/schuetzen/neu', [$adressenController, 'store']);
$router->get('/anlass/{id}/schuetzen/{adresseId}/bearbeiten', [$adressenController, 'edit']);
$router->post('/anlass/{id}/schuetzen/{adresseId}/bearbeiten', [$adressenController, 'update']);
$router->get('/anlass/{id}/loesen', [$loesenController, 'open']);
$router->get('/anlass/{id}/loesen/adresse', [$loesenController, 'selectAdresse']);
$router->get('/anlass/{id}/loesen/neu', [$loesenController, 'create']);
$router->post('/anlass/{id}/loesen/neu', [$loesenController, 'store']);
$router->get('/anlass/{id}/loesen/{standblattId}/abrechnen', [$abrechnenController, 'show']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen', [$abrechnenController, 'speichern']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen/final', [$abrechnenController, 'updateFinalTeilnahme']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen/datum', [$abrechnenController, 'updateDatum']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen/schuesse', [$abrechnenController, 'saveSchuesse']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen/schuss', [$abrechnenController, 'storeSchuss']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen/schuss/{schussId}', [$abrechnenController, 'updateSchuss']);
$router->post('/anlass/{id}/loesen/{standblattId}/abrechnen/schuss/{schussId}/loeschen', [$abrechnenController, 'deleteSchuss']);
$router->get('/anlass/{id}/loesen/{standblattId}/abrechnen/druck', [$abrechnenController, 'druck']);
$router->get('/anlass/{id}/loesen/{standblattId}/druck', [$loesenController, 'druck']);
$router->get('/anlass/{id}/loesen/{standblattId}', [$loesenController, 'show']);
$router->post('/anlass/{id}/loesen/{standblattId}', [$loesenController, 'update']);
$router->get('/anlass/{id}', [$anlassController, 'show']);

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

$router->post('/logout.php', [$authController, 'logout']);
$router->get('/logout.php', static function (): never {
    Response::redirect('/');
});

$router->get('/auth/require_login.php', static function (): never {
    Response::redirect('/dashboard');
});

