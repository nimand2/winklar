<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Auszeichnungslimitten
{
    public function getById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, stich_id, gaben_id, min_wert, max_wert, min_alter, max_alter,
                    created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM auszeichnungslimiten
             WHERE id = :id'
        );
        $statement->execute([':id' => $id]);

        $regel = $statement->fetch();

        return $regel ?: null;
    }

    public function findByAnlassId(int $anlassId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT al.id, al.stich_id, al.gaben_id, al.min_wert, al.max_wert, al.min_alter, al.max_alter,
                    al.created_by_user_id, al.created_at, al.updated_by_user_id, al.updated_at,
                    s.name AS stich_name,
                    g.name AS gaben_name
             FROM auszeichnungslimiten al
             INNER JOIN stich s ON s.id = al.stich_id
             LEFT JOIN gaben g ON g.id = al.gaben_id
             WHERE s.id_anlass = :anlass_id
             ORDER BY s.name ASC, COALESCE(al.min_wert, 0) ASC, g.name ASC'
        );
        $statement->execute(['anlass_id' => $anlassId]);

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
