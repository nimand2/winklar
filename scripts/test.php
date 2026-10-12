<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$groups = array_slice($argv, 1) ?: ['unit', 'views'];
foreach ($groups as $group) {
    if (!in_array($group, ['unit', 'views', 'integration'], true)) {
        fwrite(STDERR, "Erlaubte Testgruppen: unit, views, integration\n");
        exit(1);
    }
    $files = glob($root . '/tests/' . $group . '/*.php');
    sort($files);
    foreach ($files as $file) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file), $status);
        if ($status !== 0) exit($status);
    }
}
