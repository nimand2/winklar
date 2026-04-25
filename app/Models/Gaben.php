<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Gaben
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, punktwert, preis, anzahl, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM gaben
             ORDER BY name ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO gaben (name, punktwert, preis, anzahl, created_by_user_id, updated_by_user_id)
             VALUES (:name, :punktwert, :preis, :anzahl, :created_by_user_id, :updated_by_user_id)'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, punktwert, preis, anzahl, created_by_user_id, created_at, updated_by_user_id, updated_at
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
                 punktwert = :punktwert,
                 preis = :preis,
                 anzahl = :anzahl,
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

    public function findRegelnForStiche(array $stichIds): array
    {
        $stichIds = array_values(array_unique(array_filter(array_map('intval', $stichIds))));

        if ($stichIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($stichIds), '?'));
        $statement = Database::connection()->prepare(
            'SELECT al.id AS regel_id, al.stich_id, al.gaben_id, al.min_wert, al.max_wert,
                    g.name, g.punktwert, g.preis, g.anzahl
             FROM auszeichnungslimiten al
             INNER JOIN gaben g ON g.id = al.gaben_id
             WHERE al.stich_id IN (' . $placeholders . ')
             ORDER BY al.stich_id ASC, COALESCE(al.min_wert, g.punktwert) ASC, g.name ASC'
        );
        $statement->execute($stichIds);

        return $statement->fetchAll();
    }

    public function findAbgabenForStandblatt(int $standblattId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ga.id, ga.gaben_id, ga.standblatt_id, ga.stich_id, g.name, g.punktwert, g.preis, g.anzahl
             FROM gaben_abgaben ga
             INNER JOIN gaben g ON g.id = ga.gaben_id
             WHERE ga.standblatt_id = :standblatt_id
             ORDER BY ga.stich_id ASC, g.name ASC'
        );
        $statement->execute(['standblatt_id' => $standblattId]);

        return $statement->fetchAll();
    }

    public function findAbgabenForAnlass(int $anlassId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ga.id, ga.gaben_id, ga.standblatt_id, ga.stich_id,
                    g.name, g.punktwert, g.preis, g.anzahl,
                    st.name AS stich_name,
                    s.gaben_geprueft,
                    a.vorname, a.nachname, a.firmen_anrede, a.zusatz
             FROM gaben_abgaben ga
             INNER JOIN gaben g ON g.id = ga.gaben_id
             INNER JOIN standblatt s ON s.id = ga.standblatt_id
             INNER JOIN adressen a ON a.id = s.id_adresse
             LEFT JOIN stich st ON st.id = ga.stich_id
             WHERE s.id_anlass = :anlass_id
             ORDER BY g.name ASC, st.name ASC, a.nachname ASC, a.vorname ASC, ga.id ASC'
        );
        $statement->execute(['anlass_id' => $anlassId]);

        return $statement->fetchAll();
    }

    public function areAbgabenGeprueft(int $standblattId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT gaben_geprueft
             FROM standblatt
             WHERE id = :standblatt_id
             LIMIT 1'
        );
        $statement->execute(['standblatt_id' => $standblattId]);

        return (int) ($statement->fetchColumn() ?: 0) === 1;
    }

    public function replaceAbgabenForStandblatt(int $standblattId, array $items, int $userId): void
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $deleteStatement = $connection->prepare(
                'DELETE FROM gaben_abgaben WHERE standblatt_id = :standblatt_id'
            );
            $deleteStatement->execute(['standblatt_id' => $standblattId]);

            $insertStatement = $connection->prepare(
                'INSERT INTO gaben_abgaben (
                    gaben_id, standblatt_id, stich_id, created_by_user_id, updated_by_user_id
                 ) VALUES (
                    :gaben_id, :standblatt_id, :stich_id, :created_by_user_id, :updated_by_user_id
                 )'
            );

            foreach ($items as $item) {
                $insertStatement->execute([
                    'gaben_id' => (int) $item['gaben_id'],
                    'standblatt_id' => $standblattId,
                    'stich_id' => (int) $item['stich_id'],
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                ]);
            }

            $checkedStatement = $connection->prepare(
                'UPDATE standblatt
                 SET gaben_geprueft = 1,
                     updated_by_user_id = :updated_by_user_id
                 WHERE id = :standblatt_id'
            );
            $checkedStatement->execute([
                'standblatt_id' => $standblattId,
                'updated_by_user_id' => $userId,
            ]);

            $connection->commit();
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }
    }

    private function buildPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'punktwert' => $data['punktwert'] ?? 0.00,
            'preis' => $data['preis'] ?? 0.00,
            'anzahl' => $data['anzahl'] ?? 0,
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
