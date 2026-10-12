<?php

declare(strict_types=1);

// Package current sources only; local credentials, database backups and old artifacts are excluded.
$root = dirname(__DIR__);
$output = $argv[1] ?? $root . '/build/Winklar-Update.zip';
if (file_exists($output)) {
    throw new RuntimeException('Zieldatei existiert bereits: ' . $output);
}
if (!is_dir(dirname($output)) && !mkdir(dirname($output), 0775, true)) {
    throw new RuntimeException('Zielverzeichnis kann nicht angelegt werden.');
}
$files = ['.htaccess', 'config.php', 'README.md'];
foreach (['app', 'config', 'routes', 'database', 'public', 'tests', 'docs', 'scripts'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->isLink()) continue;
        $relative = substr($file->getPathname(), strlen($root) + 1);
        if ($relative === 'config/database.local.php'
            || str_starts_with($relative, 'database/reference/')
            || str_starts_with($relative, 'docs/reference/')) continue;
        $files[] = $relative;
    }
}
sort($files, SORT_STRING);
$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    throw new RuntimeException('ZIP-Datei kann nicht angelegt werden.');
}
foreach ($files as $relative) {
    if (!$zip->addFile($root . '/' . $relative, $relative)
        || !$zip->setMtimeName($relative, 946684800)) {
        throw new RuntimeException('Datei kann nicht verpackt werden: ' . $relative);
    }
    $zip->setExternalAttributesName($relative, ZipArchive::OPSYS_UNIX, 0100644 << 16);
}
if (!$zip->close()) throw new RuntimeException('ZIP-Datei kann nicht abgeschlossen werden.');
echo $output . PHP_EOL;
