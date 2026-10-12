<?php

declare(strict_types=1);

define('APP_BASE_PATH', rtrim(getenv('APP_BASE_PATH') ?: '', '/'));
define('REMEMBER_ME_COOKIE', 'remember_me');
define('REMEMBER_ME_LIFETIME', 60 * 60 * 24 * 30); // 30 Tage
$secureCookies = getenv('APP_SECURE_COOKIES');
define('REMEMBER_ME_SECURE_COOKIE', $secureCookies !== false
    ? filter_var($secureCookies, FILTER_VALIDATE_BOOLEAN)
    : (getenv('APP_ENV') !== 'development'));
unset($secureCookies);
define('API_TOKEN_SECRET', getenv('API_TOKEN_SECRET') ?: '');
