<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Gaben
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, preis, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM gaben
             ORDER BY name ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO gaben (name, preis, created_by_user_id, updated_by_user_id)
             VALUES (:name, :preis, :created_by_user_id, :updated_by_user_id)'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, preis, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM gaben
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $gabe = $statement->fetch();

        return $gabe ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE gaben
             SET name = :name,
                 preis = :preis,
                 created_by_user_id = :created_by_user_id,
                 updated_by_user_id = :updated_by_user_id
             WHERE id = :id'
        );

        $payload = $this->buildPayload($data);
        $payload['id'] = $id;

        return $statement->execute($payload);
    }

    public function delete(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM gaben
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    private function buildPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'preis' => $data['preis'] ?? 0.00,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
