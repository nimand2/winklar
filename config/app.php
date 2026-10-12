<?php

declare(strict_types=1);

define('APP_BASE_PATH', '');
define('REMEMBER_ME_COOKIE', 'remember_me');
define('REMEMBER_ME_LIFETIME', 60 * 60 * 24 * 30); // 30 Tage
define('REMEMBER_ME_SECURE_COOKIE', false); // In Produktion mit HTTPS auf true setzen
define('API_TOKEN_SECRET', getenv('API_TOKEN_SECRET') ?: '');
