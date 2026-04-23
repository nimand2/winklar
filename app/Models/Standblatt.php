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

    public function findForAnlassWithAdresse(int $anlassId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.id, s.id_anlass, s.id_adresse, s.datum, s.kosten, s.created_by_user_id,
                    s.created_at, s.updated_by_user_id, s.updated_at,
                    a.vorname, a.nachname, a.firmen_anrede, a.zusatz, a.email, a.telefon
             FROM standblatt s
             INNER JOIN adressen a ON a.id = s.id_adresse
             WHERE s.id_anlass = :anlass_id
             ORDER BY s.datum DESC, s.id DESC'
        );
        $statement->execute(['anlass_id' => $anlassId]);

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

    public function createWithStiche(array $data, array $stichCounts, int $userId): int
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $standblattId = $this->create($data);
            $statement = $connection->prepare(
                'INSERT INTO standblatt_stich (
                    id_standblatt, id_stich, anzahl_stiche, created_by_user_id, updated_by_user_id
                 ) VALUES (
                    :id_standblatt, :id_stich, :anzahl_stiche, :created_by_user_id, :updated_by_user_id
                 )'
            );

            foreach ($stichCounts as $stichId => $anzahlStiche) {
                $statement->execute([
                    'id_standblatt' => $standblattId,
                    'id_stich' => (int) $stichId,
                    'anzahl_stiche' => (int) $anzahlStiche,
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                ]);
            }

            $connection->commit();

            return $standblattId;
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }
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

    public function findSticheForStandblatt(int $standblattId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT st.id, st.id_anlass, ss.anzahl_stiche, st.name, st.short_name, st.anzeige_id, st.scheibe,
                    st.wertigkeit, st.anzahl_schuss, st.anzahl_passen, st.preis, st.verbindung
             FROM standblatt_stich ss
             INNER JOIN stich st ON st.id = ss.id_stich
             WHERE ss.id_standblatt = :id_standblatt
             ORDER BY st.name ASC, st.id ASC'
        );
        $statement->execute(['id_standblatt' => $standblattId]);

        return $statement->fetchAll();
    }

    public function updateWithStiche(int $id, array $data, array $stichCounts, int $userId): bool
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $this->update($id, $data);

            $deleteStatement = $connection->prepare(
                'DELETE FROM standblatt_stich
                 WHERE id_standblatt = :id_standblatt'
            );
            $deleteStatement->execute(['id_standblatt' => $id]);

            $insertStatement = $connection->prepare(
                'INSERT INTO standblatt_stich (
                    id_standblatt, id_stich, anzahl_stiche, created_by_user_id, updated_by_user_id
                 ) VALUES (
                    :id_standblatt, :id_stich, :anzahl_stiche, :created_by_user_id, :updated_by_user_id
                 )'
            );

            foreach ($stichCounts as $stichId => $anzahlStiche) {
                $insertStatement->execute([
                    'id_standblatt' => $id,
                    'id_stich' => (int) $stichId,
                    'anzahl_stiche' => (int) $anzahlStiche,
                    'created_by_user_id' => $userId,
                    'updated_by_user_id' => $userId,
                ]);
            }

            $connection->commit();

            return true;
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE standblatt
             SET id_anlass = :id_anlass,
                 id_adresse = :id_adresse,
                 datum = :datum,
                 kosten = :kosten,
                 updated_by_user_id = :updated_by_user_id
             WHERE id = :id'
        );

        $payload = $this->buildPayload($data);
        unset($payload['created_by_user_id']);
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
