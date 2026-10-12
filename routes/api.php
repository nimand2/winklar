<?php

declare(strict_types=1);

$router->post('/api/login', [$clientApiController, 'login']);
$router->get('/api/anlaesse', [$clientApiController, 'anlaesse']);
$router->get('/api/anlaesse/{id}/shooters', [$clientApiController, 'shooters']);
$router->get('/api/anlaesse/{id}/shooters/new', [$clientApiController, 'newShooters']);
$router->post('/api/anlaesse/{id}/shots/import', [$clientApiController, 'importShots']);

