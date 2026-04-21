<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Grundkonfiguration
|--------------------------------------------------------------------------
| Passe diese Werte an deine Umgebung an.
*/

define('APP_BASE_PATH', '');

define('DB_HOST', 'localhost');
define('DB_NAME', 'winklar');
define('DB_USER', 'login_app');
define('DB_PASS', '6dd81fb1c872b85a6159653473ef0b2d');
define('DB_CHARSET', 'utf8mb4');

define('REMEMBER_ME_COOKIE', 'remember_me');
define('REMEMBER_ME_LIFETIME', 60 * 60 * 24 * 30); // 30 Tage
define('REMEMBER_ME_SECURE_COOKIE', false); // In Produktion mit HTTPS auf true setzen
