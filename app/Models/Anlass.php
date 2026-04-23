<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Anlass
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, fk_adress_id_creator, fk_adress_id_modifier, name_anlass, shortname_anlass,
                    start_anlass, end_anlass, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM anlass
             ORDER BY start_anlass DESC, id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }
    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO anlass (
                fk_adress_id_creator,
                fk_adress_id_modifier,
                name_anlass,
                shortname_anlass,
                start_anlass,
                end_anlass,
                created_by_user_id,
                updated_by_user_id
             ) VALUES (
                :fk_adress_id_creator,
                :fk_adress_id_modifier,
                :name_anlass,
                :shortname_anlass,
                :start_anlass,
                :end_anlass,
                :created_by_user_id,
                :updated_by_user_id
             )'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, fk_adress_id_creator, fk_adress_id_modifier, name_anlass, shortname_anlass,
                    start_anlass, end_anlass, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM anlass
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $anlass = $statement->fetch();

        return $anlass ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE anlass
             SET fk_adress_id_creator = :fk_adress_id_creator,
                 fk_adress_id_modifier = :fk_adress_id_modifier,
                 name_anlass = :name_anlass,
                 shortname_anlass = :shortname_anlass,
                 start_anlass = :start_anlass,
                 end_anlass = :end_anlass,
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
            'DELETE FROM anlass
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    private function buildPayload(array $data): array
    {
        return [
            'fk_adress_id_creator' => $data['fk_adress_id_creator'] ?? null,
            'fk_adress_id_modifier' => $data['fk_adress_id_modifier'] ?? null,
            'name_anlass' => $data['name_anlass'],
            'shortname_anlass' => $data['shortname_anlass'] ?? null,
            'start_anlass' => $data['start_anlass'] ?? null,
            'end_anlass' => $data['end_anlass'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
