<?php
declare(strict_types=1);
// Optional DDL test: creates only dedicated random tables, never application rows.
if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$pdo = App\Core\Database::connection();
$prefix = 'review_fk_test_' . bin2hex(random_bytes(8));
$parent = $prefix . '_parent';
$child = $prefix . '_child';
$constraint = $prefix . '_fk';
try {
    $pdo->exec('CREATE TABLE `' . $parent . '` (id INT PRIMARY KEY) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE `' . $child . '` (id INT PRIMARY KEY, parent_id INT NOT NULL, CONSTRAINT `' . $constraint . '` FOREIGN KEY(parent_id) REFERENCES `' . $parent . '`(id) ON DELETE CASCADE) ENGINE=InnoDB');
    $pdo->exec('INSERT INTO `' . $parent . '` VALUES(1)');
    $pdo->exec('INSERT INTO `' . $child . '` VALUES(1,1)');
    $migration = new App\Core\ReviewMigration($pdo);
    $migration->protectReference($child, 'parent_id', $parent, $constraint);
    $query = $pdo->prepare('SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $query->execute([$child]);
    if (!in_array($query->fetchColumn(), ['RESTRICT','NO ACTION'], true)) throw new RuntimeException('Foreign key protection missing.');
    try { $pdo->exec('DELETE FROM `' . $parent . '` WHERE id=1'); throw new RuntimeException('Protected record deleted.'); }
    catch (PDOException $error) { if (($error->errorInfo[1] ?? 0) !== 1451) throw $error; }
    // Retry after a simulated interrupted finalisation, with the already-renamed FK.
    $migration->protectReference($child, 'parent_id', $parent, $constraint);
    if ((int)$pdo->query('SELECT COUNT(*) FROM `' . $child . '`')->fetchColumn() !== 1) throw new RuntimeException('Test child changed.');
    echo "Persistent DDL checks passed: MariaDB constraint replacement, enforced delete protection and interrupted migration retry.\n";
} finally {
    $pdo->exec('DROP TABLE IF EXISTS `' . $child . '`');
    $pdo->exec('DROP TABLE IF EXISTS `' . $parent . '`');
}
