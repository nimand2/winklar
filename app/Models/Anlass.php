<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Anlass
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name_anlass, start_anlass, end_anlass
             FROM anlass
             ORDER BY start_anlass DESC, id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }
    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO anlass (name_anlass, start_anlass, end_anlass) VALUES (:name_anlass, :start_anlass, :end_anlass)'
        );
        $statement->execute($data);

        return (int)Database::connection()->lastInsertId();
    }
    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name_anlass, start_anlass, end_anlass
             FROM anlass
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $anlass = $statement->fetch();

        return $anlass ?: null;
    }
    public function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE anlass
             SET name_anlass = :name_anlass, start_anlass = :start_anlass, end_anlass = :end_anlass
             WHERE id = :id'
        );
        $statement->execute(array_merge($data, ['id' => $id]));
    }
    public function delete(int $id): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM anlass
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }
    
}
