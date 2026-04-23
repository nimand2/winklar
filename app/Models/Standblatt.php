<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Standblatt
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, id_adresse, datum, kosten, created_by_user_id, created_at,
                    updated_by_user_id, updated_at
             FROM standblatt
             ORDER BY datum DESC, id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function findShootersForAnlass(int $anlassId, int $sinceId = 0): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.id,
                    s.id AS startnummer,
                    a.nachname AS name,
                    a.vorname,
                    COALESCE(a.zusatz, a.firmen_anrede, "") AS verein
             FROM standblatt s
             INNER JOIN adressen a ON a.id = s.id_adresse
             WHERE s.id_anlass = :anlass_id
               AND s.id > :since_id
             ORDER BY s.id ASC'
        );
        $statement->execute([
            'anlass_id' => $anlassId,
            'since_id' => $sinceId,
        ]);

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO standblatt (
                id_anlass, id_adresse, datum, kosten, created_by_user_id, updated_by_user_id
             ) VALUES (
                :id_anlass, :id_adresse, :datum, :kosten, :created_by_user_id, :updated_by_user_id
             )'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, id_adresse, datum, kosten, created_by_user_id, created_at,
                    updated_by_user_id, updated_at
             FROM standblatt
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $standblatt = $statement->fetch();

        return $standblatt ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE standblatt
             SET id_anlass = :id_anlass,
                 id_adresse = :id_adresse,
                 datum = :datum,
                 kosten = :kosten,
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
            'DELETE FROM standblatt
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    private function buildPayload(array $data): array
    {
        return [
            'id_anlass' => $data['id_anlass'],
            'id_adresse' => $data['id_adresse'],
            'datum' => $data['datum'] ?? null,
            'kosten' => $data['kosten'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
