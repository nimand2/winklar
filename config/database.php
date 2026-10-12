<?php

declare(strict_types=1);

$localFile = __DIR__ . '/database.local.php';
$local = is_file($localFile) ? require $localFile : [];
$defaults = ['DB_HOST' => 'localhost', 'DB_NAME' => 'winklar', 'DB_USER' => '', 'DB_PASS' => '', 'DB_CHARSET' => 'utf8mb4'];
foreach ($defaults as $name => $default) {
    $value = getenv($name);
    define($name, $value !== false ? $value : ($local[$name] ?? $default));
}
unset($localFile, $local, $defaults, $name, $default, $value);
