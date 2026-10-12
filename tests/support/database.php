<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

/** Every application table is shadowed: no test may read/write real application rows. */
function review_test_database(): PDO
{
    $pdo = App\Core\Database::connection();
    $schema = file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');
    preg_match_all('/CREATE TABLE (\w+) \([\s\S]*?\n\);/', $schema, $tables, PREG_SET_ORDER);
    foreach ($tables as $table) {
        $sql = preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $table[0]);
        $sql = preg_replace('/,\s*CONSTRAINT\s+\w+\s+FOREIGN KEY\s*\([^)]*\)\s+REFERENCES\s+\w+\s*\([^)]*\)\s+ON DELETE (?:SET NULL|CASCADE|RESTRICT)/', '', $sql);
        $sql = preg_replace('/\);$/', ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;', $sql);
        $pdo->exec($sql);
    }
    return $pdo;
}

function review_test_seed(PDO $pdo): void
{
    $sql = file_get_contents(dirname(__DIR__, 2) . '/database/seeds/beispieldaten.sql');
    $sql = preg_replace('/^USE winklar;\s*/', '', $sql);
    foreach (preg_split('/;\s*(?:\n|$)/', $sql) as $statement) if (trim($statement) !== '') $pdo->exec($statement);
}
