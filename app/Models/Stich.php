<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Stich
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, id_disziplin, name, short_name, anzeige_id, scheibe, wertigkeit,
                    anzahl_schuss, anzahl_passen, preis, verbindung, created_by_user_id, created_at,
                    updated_by_user_id, updated_at
             FROM stich
             ORDER BY id_anlass ASC, name ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO stich (
                id_anlass, id_disziplin, name, short_name, anzeige_id, scheibe, wertigkeit,
                anzahl_schuss, anzahl_passen, preis, verbindung, created_by_user_id, updated_by_user_id
             ) VALUES (
                :id_anlass, :id_disziplin, :name, :short_name, :anzeige_id, :scheibe, :wertigkeit,
                :anzahl_schuss, :anzahl_passen, :preis, :verbindung, :created_by_user_id, :updated_by_user_id
             )'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, id_disziplin, name, short_name, anzeige_id, scheibe, wertigkeit,
                    anzahl_schuss, anzahl_passen, preis, verbindung, created_by_user_id, created_at,
                    updated_by_user_id, updated_at
             FROM stich
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $stich = $statement->fetch();

        return $stich ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE stich
             SET id_anlass = :id_anlass,
                 id_disziplin = :id_disziplin,
                 name = :name,
                 short_name = :short_name,
                 anzeige_id = :anzeige_id,
                 scheibe = :scheibe,
                 wertigkeit = :wertigkeit,
                 anzahl_schuss = :anzahl_schuss,
                 anzahl_passen = :anzahl_passen,
                 preis = :preis,
                 verbindung = :verbindung,
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
            'DELETE FROM stich
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    private function buildPayload(array $data): array
    {
        return [
            'id_anlass' => $data['id_anlass'],
            'id_disziplin' => $data['id_disziplin'] ?? null,
            'name' => $data['name'],
            'short_name' => $data['short_name'] ?? null,
            'anzeige_id' => $data['anzeige_id'] ?? null,
            'scheibe' => $data['scheibe'] ?? null,
            'wertigkeit' => $data['wertigkeit'] ?? null,
            'anzahl_schuss' => $data['anzahl_schuss'] ?? null,
            'anzahl_passen' => $data['anzahl_passen'] ?? null,
            'preis' => $data['preis'] ?? null,
            'verbindung' => $data['verbindung'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
