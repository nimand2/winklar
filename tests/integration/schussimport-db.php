<?php

declare(strict_types=1);

require dirname(__DIR__) . '/support/database.php';
$pdo = review_test_database();
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
try {
    $model = new App\Models\Schussdaten();
    $row = ['id_anlass' => 1, 'start_nr' => 42, 'import_hash' => hash('sha256', 'test-shot')];
    $check($model->createMany([]) === 0, 'Empty batch');
    $check($model->createMany([$row, $row]) === 1, 'Duplicate in same batch');
    $check($model->createMany([$row]) === 0, 'Repeated import');
    $check($model->create($row) === 0, 'Duplicate single insert');
    $new = array_replace($row, ['import_hash' => hash('sha256', 'rollback-shot')]);
    $invalid = array_replace($row, ['import_hash' => null]);
    $failed = false;
    try {
        $model->createMany([$new, $invalid]);
    } catch (PDOException) {
        $failed = true;
    }
    $check($failed, 'Invalid row must fail');
    $check(!$pdo->inTransaction(), 'Transaction ended after rollback');
    $check((int) $pdo->query('SELECT COUNT(*) FROM schussdaten')->fetchColumn() === 1, 'Entire failed batch rolled back');
    $check($model->createMany([$new]) === 1, 'Connection usable after rollback');
    echo "Database import tests passed: duplicates, rollback, recovery.\n";
} finally {
    $pdo->exec('DROP TEMPORARY TABLE schussdaten');
}
