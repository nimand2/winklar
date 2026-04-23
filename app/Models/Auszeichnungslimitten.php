<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Auszeichnungslimitten
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, stich_id, gaben_id, min_wert, max_wert, min_alter, max_alter,
                    created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM auszeichnungslimiten
             ORDER BY stich_id ASC, min_wert ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO auszeichnungslimiten (
                stich_id, gaben_id, min_wert, max_wert, min_alter, max_alter,
                created_by_user_id, updated_by_user_id
             ) VALUES (
                :stich_id, :gaben_id, :min_wert, :max_wert, :min_alter, :max_alter,
                :created_by_user_id, :updated_by_user_id
             )'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, stich_id, gaben_id, min_wert, max_wert, min_alter, max_alter,
                    created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM auszeichnungslimiten
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $limit = $statement->fetch();

        return $limit ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE auszeichnungslimiten
             SET stich_id = :stich_id,
                 gaben_id = :gaben_id,
                 min_wert = :min_wert,
                 max_wert = :max_wert,
                 min_alter = :min_alter,
                 max_alter = :max_alter,
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
            'DELETE FROM auszeichnungslimiten
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    private function buildPayload(array $data): array
    {
        return [
            'stich_id' => $data['stich_id'],
            'gaben_id' => $data['gaben_id'] ?? null,
            'min_wert' => $data['min_wert'] ?? null,
            'max_wert' => $data['max_wert'] ?? null,
            'min_alter' => $data['min_alter'] ?? null,
            'max_alter' => $data['max_alter'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
