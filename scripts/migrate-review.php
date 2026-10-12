<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';
$pdo = App\Core\Database::connection();
$migration = new App\Core\ReviewMigration($pdo);
$migration->preflight();
if (($argv[1] ?? '') !== '--apply') {
    echo "Vorprüfung erfolgreich. Mit --apply wird zuerst ein Datenbankbackup angelegt, dann die additive Review-Migration ausgeführt.\n";
    exit;
}
$directory = dirname(__DIR__) . '/storage/backups';
if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Backupverzeichnis nicht verfügbar.');
$path = $directory . '/review-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sql';
$stream = fopen($path, 'x');
if ($stream === false) throw new RuntimeException('Backup konnte nicht angelegt werden.');
chmod($path, 0600);
$write = static function (string $text) use ($stream): void {
    if (fwrite($stream, $text) !== strlen($text)) throw new RuntimeException('Backup konnte nicht vollständig geschrieben werden.');
};
try {
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $pdo->beginTransaction();
    $write("-- Backup vor Review-Migration\nSET FOREIGN_KEY_CHECKS=0;\n");
    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as [$table]) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';
        $definition = $pdo->query('SHOW CREATE TABLE ' . $quoted)->fetch()['Create Table'];
        $write('DROP TABLE IF EXISTS ' . $quoted . ";\n" . $definition . ";\n");
        $rows = $pdo->query('SELECT * FROM ' . $quoted);
        while ($row = $rows->fetch()) {
            $columns = array_map(static fn (string $key): string => '`' . str_replace('`', '``', $key) . '`', array_keys($row));
            $values = array_map(static fn ($value): string => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row));
            $write('INSERT INTO ' . $quoted . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
        }
    }
    $write("SET FOREIGN_KEY_CHECKS=1;\n");
    if (!fflush($stream) || (function_exists('fsync') && !fsync($stream))) throw new RuntimeException('Backup konnte nicht gesichert werden.');
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
} finally { fclose($stream); }
echo 'Backup erstellt: ' . $path . PHP_EOL;
$migration->apply();
echo 'Review-Migration abgeschlossen. Backup: ' . $path . PHP_EOL;
