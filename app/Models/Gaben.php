<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Gaben
{
    public function invalidateStich(int $stichId, ?int $userId): void
    {
        Database::transaction(function ($pdo) use ($stichId, $userId): void {
            $statement = $pdo->prepare('SELECT id_standblatt FROM standblatt_stich WHERE id_stich = ?');
            $statement->execute([$stichId]);
            foreach ($statement->fetchAll(\PDO::FETCH_COLUMN) as $id) $this->resetAbgabenForStandblatt((int) $id, $userId);
        });
    }

    public function invalidateAnlass(int $anlassId, ?int $userId): void
    {
        Database::transaction(function ($pdo) use ($anlassId, $userId): void {
            $ids = $pdo->prepare('SELECT id FROM standblatt WHERE id_anlass = ?');
            $ids->execute([$anlassId]);
            foreach ($ids->fetchAll(\PDO::FETCH_COLUMN) as $id) $this->resetAbgabenForStandblatt((int) $id, $userId);
        });
    }
    /**
     * Laedt alle Gaben alphabetisch sortiert.
     */
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

    /**
     * Erstellt eine Gabe und gibt die neue ID zurueck.
     */
    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO gaben (name, punktwert, preis, anzahl, created_by_user_id, updated_by_user_id)
             VALUES (:name, :punktwert, :preis, :anzahl, :created_by_user_id, :updated_by_user_id)'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Findet eine Gabe anhand ihrer ID.
     */
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

    /**
     * Aktualisiert eine Gabe.
     */
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

        return Database::transaction(function ($pdo) use ($statement, $payload, $id, $data): bool {
            $result = $statement->execute($payload);
            $stiche = $pdo->prepare('SELECT DISTINCT stich_id FROM auszeichnungslimiten WHERE gaben_id = ?');
            $stiche->execute([$id]);
            foreach ($stiche->fetchAll(\PDO::FETCH_COLUMN) as $stichId) $this->invalidateStich((int) $stichId, $data['updated_by_user_id'] ?? null);
            return $result;
        });
    }

    /**
     * Loescht eine Gabe.
     */
    public function delete(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM gaben WHERE id = :id
             AND NOT EXISTS (SELECT 1 FROM auszeichnungslimiten WHERE gaben_id = gaben.id)
             AND NOT EXISTS (SELECT 1 FROM gaben_abgaben WHERE gaben_id = gaben.id)
             AND NOT EXISTS (SELECT 1 FROM gaben_bewegungen WHERE gaben_id = gaben.id)'
        );
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }

    /**
     * Laedt die passenden Gabenregeln fuer mehrere Stiche.
     */
    public function findRegelnForStiche(array $stichIds): array
    {
        $stichIds = array_values(array_unique(array_filter(array_map('intval', $stichIds))));

        if ($stichIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($stichIds), '?'));
        $statement = Database::connection()->prepare(
            'SELECT al.id AS regel_id, al.stich_id, al.gaben_id, al.min_wert, al.max_wert, al.min_alter, al.max_alter,
                    g.name, g.punktwert, g.preis, g.anzahl
             FROM auszeichnungslimiten al
             INNER JOIN gaben g ON g.id = al.gaben_id
             WHERE al.stich_id IN (' . $placeholders . ')
             ORDER BY al.stich_id ASC, COALESCE(al.min_wert, g.punktwert) ASC, g.name ASC'
        );
        $statement->execute($stichIds);

        return $statement->fetchAll();
    }

    /**
     * Laedt die gespeicherten Gabenabgaben eines Standblatts.
     */
    public function findAbgabenForStandblatt(int $standblattId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ga.id, ga.gaben_id, ga.standblatt_id, ga.stich_id, ga.serie_nummer, ga.abgegeben, ga.name_snapshot AS name, ga.punktwert_snapshot AS punktwert, ga.preis_snapshot AS preis, ga.anzahl_snapshot AS anzahl, ga.ist_geld_snapshot AS ist_geld, ga.stich_name_snapshot AS stich_name, ga.preis_quelle
             FROM gaben_abgaben ga
             INNER JOIN gaben g ON g.id = ga.gaben_id
             WHERE ga.standblatt_id = :standblatt_id
             ORDER BY ga.stich_id ASC, g.name ASC'
        );
        $statement->execute(['standblatt_id' => $standblattId]);

        return $statement->fetchAll();
    }

    /**
     * Laedt alle Gabenabgaben eines Anlasses fuer die Kontrolle.
     */
    public function findAbgabenForAnlass(int $anlassId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ga.id, ga.gaben_id, ga.standblatt_id, ga.stich_id, ga.serie_nummer, ga.abgegeben,
                    ga.name_snapshot AS name, ga.punktwert_snapshot AS punktwert, ga.preis_snapshot AS preis, ga.anzahl_snapshot AS anzahl, ga.ist_geld_snapshot AS ist_geld, ga.preis_quelle,
                    ga.stich_name_snapshot AS stich_name,
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

    /**
     * Prueft, ob die Gaben eines Standblatts bereits kontrolliert wurden.
     */
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

    /**
     * Ersetzt die Gabenabgaben eines Standblatts in einer Transaktion.
     */
    public function replaceAbgabenForStandblatt(int $standblattId, array $items, int $userId): void
    {
        Database::transaction(function ($connection) use ($standblattId, $items, $userId): void {
            $lock = $connection->prepare('SELECT id FROM standblatt WHERE id = ? FOR UPDATE');
            $lock->execute([$standblattId]);
            $old = [];
            foreach ($this->findAbgabenForStandblatt($standblattId) as $row) {
                $old[$row['stich_id'] . ':' . $row['serie_nummer'] . ':' . $row['gaben_id']] = $row;
            }
            $delete = $connection->prepare('DELETE FROM gaben_abgaben WHERE standblatt_id = ? AND abgegeben = 0');
            $delete->execute([$standblattId]);
            $insert = $connection->prepare(
                'INSERT INTO gaben_abgaben (gaben_id, standblatt_id, stich_id, serie_nummer, abgegeben,
                    name_snapshot, preis_snapshot, punktwert_snapshot, anzahl_snapshot, ist_geld_snapshot, stich_name_snapshot,
                    preis_quelle, created_by_user_id, updated_by_user_id)
                 VALUES (:gabe, :blatt, :stich, :serie, :abgegeben, :name, :preis, :punktwert, :anzahl, :ist_geld, :stich_name, :quelle, :creator, :modifier)
                 ON DUPLICATE KEY UPDATE abgegeben = VALUES(abgegeben), updated_by_user_id = VALUES(updated_by_user_id)'
            );
            $history = $connection->prepare(
                'INSERT INTO gaben_bewegungen (gaben_id, standblatt_id, stich_id, serie_nummer, richtung,
                    name_snapshot, preis_snapshot, ist_geld_snapshot, preis_quelle, created_by_user_id)
                 VALUES (:gabe, :blatt, :stich, :serie, :richtung, :name, :preis, :ist_geld, :quelle, :user)'
            );
            $seen = [];
            foreach ($items as $item) {
                $key = $item['stich_id'] . ':' . ($item['serie_nummer'] ?? 1) . ':' . $item['gaben_id'];
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $previous = $old[$key] ?? null;
                $wasDelivered = !empty($previous['abgegeben']);
                $delivered = empty($item['ruecknahme']) && ($wasDelivered || !empty($item['abgegeben']));
                $snapshot = $wasDelivered ? $previous : $item['snapshot'];
                $values = [
                    'gabe' => (int) $item['gaben_id'], 'blatt' => $standblattId, 'stich' => (int) $item['stich_id'],
                    'serie' => (int) ($item['serie_nummer'] ?? 1), 'abgegeben' => (int) $delivered,
                    'name' => $snapshot['name'], 'preis' => $snapshot['preis'], 'punktwert' => $snapshot['punktwert'],
                    'anzahl' => $snapshot['anzahl'], 'ist_geld' => (int) !empty($snapshot['ist_geld']),
                    'stich_name' => $snapshot['stich_name'], 'quelle' => $wasDelivered ? ($previous['preis_quelle'] ?? 'erfasst') : 'erfasst',
                    'creator' => $previous['created_by_user_id'] ?? $userId, 'modifier' => $userId,
                ];
                $insert->execute($values);
                if ($wasDelivered !== $delivered) {
                    $history->execute([
                        'gabe' => $values['gabe'], 'blatt' => $standblattId, 'stich' => $values['stich'], 'serie' => $values['serie'],
                        'richtung' => $delivered ? 1 : -1, 'name' => $values['name'], 'preis' => $values['preis'],
                        'ist_geld' => $values['ist_geld'], 'quelle' => $values['quelle'], 'user' => $userId,
                    ]);
                }
            }
            $checked = $connection->prepare('UPDATE standblatt SET gaben_geprueft = 1, updated_by_user_id = ? WHERE id = ?');
            $checked->execute([$userId, $standblattId]);
        });
    }

    /**
     * Setzt gespeicherte Gaben zurueck, wenn Schussdaten nachtraeglich korrigiert wurden.
     */
    public function resetAbgabenForStandblatt(int $standblattId, ?int $userId): void
    {
        $connection = Database::connection();
        $ownsTransaction = !$connection->inTransaction();
        if ($ownsTransaction) {
            $connection->beginTransaction();
        }

        try {
            $deleteStatement = $connection->prepare(
                'DELETE FROM gaben_abgaben WHERE standblatt_id = :standblatt_id AND abgegeben = 0'
            );
            $deleteStatement->execute(['standblatt_id' => $standblattId]);

            $checkedStatement = $connection->prepare(
                'UPDATE standblatt
                 SET gaben_geprueft = 0,
                     updated_by_user_id = :updated_by_user_id
                 WHERE id = :standblatt_id'
            );
            $checkedStatement->execute([
                'standblatt_id' => $standblattId,
                'updated_by_user_id' => $userId,
            ]);

            if ($ownsTransaction) {
                $connection->commit();
            }
        } catch (\Throwable $throwable) {
            if ($ownsTransaction) {
                $connection->rollBack();
            }
            throw $throwable;
        }
    }

    /**
     * Normalisiert Gabendaten fuer INSERT und UPDATE.
     */
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
