<?php
declare(strict_types=1);

// Deliberately separate from installation. Never run by deployment or tests.
if (PHP_SAPI !== 'cli' || getenv('APP_ENV') !== 'development'
    || ($argv[1] ?? '') !== '--delete-development-database') {
    fwrite(STDERR, "Nur für Entwicklung: APP_ENV=development php scripts/reset-development.php --delete-development-database\n");
    exit(1);
}
require dirname(__DIR__) . '/app/bootstrap.php';
$pdo = App\Core\Database::connection();
$name = '`' . str_replace('`', '``', DB_NAME) . '`';
$pdo->exec('DROP DATABASE ' . $name);
echo "Entwicklungsdatenbank gelöscht. Für Neuinstallation database/schema.sql verwenden.\n";
