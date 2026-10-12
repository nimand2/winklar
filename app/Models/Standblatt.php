<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Standblatt
{
    /**
     * Laedt alle Standblaetter.
     */
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

    /**
     * Laedt Schuetzendaten fuer die Client-API eines Anlasses.
     */
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
             LEFT JOIN plz p ON p.id = a.plz_id
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

    /**
     * Laedt Standblaetter eines Anlasses mit Adressdaten.
     */
    public function findForAnlassWithAdresse(int $anlassId, string $nummer = ''): array
    {
        if ($nummer !== '' && !ctype_digit($nummer)) {
            return [];
        }

        $nummerFilter = $nummer !== '' ? ' AND s.id = :nummer' : '';
        $statement = Database::connection()->prepare(
            'SELECT s.id, s.id_anlass, s.id_adresse, s.datum, s.kosten, s.gaben_geprueft, s.final_teilnahme, s.created_by_user_id,
                    s.created_at, s.updated_by_user_id, s.updated_at,
                    s.gaben_geprueft = 1 AND NOT EXISTS (
                        SELECT 1 FROM gaben_abgaben ga
                        WHERE ga.standblatt_id = s.id AND ga.abgegeben = 0
                    ) AS gaben_abgegeben,
                    a.vorname, a.nachname, a.firmen_anrede, a.zusatz, a.email, a.telefon, a.geburtsdatum,
                    a.lizenz, a.strasse, a.postfach, a.nation, p.plz4, p.ortschaftsname
             FROM standblatt s
             INNER JOIN adressen a ON a.id = s.id_adresse
             LEFT JOIN plz p ON p.id = a.plz_id
             WHERE s.id_anlass = :anlass_id' . $nummerFilter . '
             ORDER BY s.datum DESC, s.id DESC'
        );
        $parameters = ['anlass_id' => $anlassId];
        if ($nummer !== '') {
            $parameters['nummer'] = $nummer;
        }
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    /**
     * Aggregiert Einnahmen pro Stich fuer die Kassenansicht.
     */
    public function findEinnahmenByStichForAnlass(int $anlassId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT st.id AS stich_id, st.name, st.short_name, ss.preis,
                    SUM(ss.anzahl_stiche) AS anzahl_stiche, SUM(ss.kosten) AS total,
                    MAX(ss.preis_quelle = "uebernommen") AS preis_uebernommen
             FROM standblatt_stich ss
             INNER JOIN standblatt s ON s.id = ss.id_standblatt
             INNER JOIN stich st ON st.id = ss.id_stich
             WHERE s.id_anlass = :anlass_id
             GROUP BY st.id, st.name, st.short_name, ss.preis
             ORDER BY st.name ASC, st.id ASC, ss.preis ASC'
        );
        $statement->execute(['anlass_id' => $anlassId]);
        return $statement->fetchAll();
    }

    /**
     * Erstellt ein Standblatt und gibt die neue ID zurueck.
     */
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

    /**
     * Erstellt ein Standblatt inklusive der geloesten Stiche.
     */
    public function createWithStiche(array $data, array $stichCounts, int $userId): int
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $id = $this->create(array_replace($data, ['kosten' => '0.00']));
            $this->saveStiche($id, (int) $data['id_anlass'], $stichCounts, $userId, []);
            $connection->commit();
            return $id;
        } catch (\Throwable $error) {
            $connection->rollBack();
            throw $error;
        }
    }

    /**
     * Findet ein Standblatt anhand seiner ID.
     */
    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT s.id, s.id_anlass, s.id_adresse, s.datum, s.kosten, s.gaben_geprueft, s.final_teilnahme, s.created_by_user_id, s.created_at,
                    a.geburtsdatum, an.start_anlass, an.end_anlass,
                    s.updated_by_user_id, s.updated_at, s.gaben_geprueft = 1 AND NOT EXISTS (
                        SELECT 1 FROM gaben_abgaben ga
                        WHERE ga.standblatt_id = s.id AND ga.abgegeben = 0
                    ) AS gaben_abgegeben
             FROM standblatt s
             INNER JOIN adressen a ON a.id = s.id_adresse
             INNER JOIN anlass an ON an.id = s.id_anlass
             WHERE s.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $standblatt = $statement->fetch();

        return $standblatt ?: null;
    }

    /**
     * Laedt die geloesten Stiche eines Standblatts.
     */
    private function stichColumns(): string
    {
        return 'st.id, st.id_anlass, ss.anzahl_stiche, st.name, st.short_name, st.anzeige_id, st.scheibe,
                st.wertigkeit, st.anzahl_schuss, st.anzahl_passen, ss.preis, ss.kosten, ss.preis_quelle, st.verbindung';
    }

    public function findSticheForStandblatt(int $standblattId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ' . $this->stichColumns() . '
             FROM standblatt_stich ss INNER JOIN stich st ON st.id = ss.id_stich
             WHERE ss.id_standblatt = :id ORDER BY st.name ASC, st.id ASC'
        );
        $statement->execute(['id' => $standblattId]);
        return $statement->fetchAll();
    }

    public function findSticheForAnlass(int $anlassId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ss.id_standblatt, ' . $this->stichColumns() . '
             FROM standblatt_stich ss INNER JOIN stich st ON st.id = ss.id_stich
             INNER JOIN standblatt s ON s.id = ss.id_standblatt
             WHERE s.id_anlass = :id ORDER BY st.name ASC, st.id ASC'
        );
        $statement->execute(['id' => $anlassId]);
        $result = [];
        foreach ($statement->fetchAll() as $row) $result[(int) $row['id_standblatt']][] = $row;
        return $result;
    }

    public function existingIdsForAnlass(int $anlassId, array $ids): array
    {
        $ids = array_values(array_unique($ids));
        if ($ids === []) return [];
        $statement = Database::connection()->prepare(
            'SELECT id FROM standblatt WHERE id_anlass = ? AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')'
        );
        $statement->execute(array_merge([$anlassId], $ids));
        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Aktualisiert ein Standblatt und ersetzt seine geloesten Stiche.
     */
    public function updateWithStiche(int $id, array $data, array $stichCounts, int $userId): bool
    {
        $connection = Database::connection();
        $connection->beginTransaction();
        try {
            $lock = $connection->prepare('SELECT id FROM standblatt WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            $old = [];
            foreach ($this->findSticheForStandblatt($id) as $row) $old[(int) $row['id']] = $row;
            $this->update($id, $data);
            $this->saveStiche($id, (int) $data['id_anlass'], $stichCounts, $userId, $old);
            $oldCounts = array_map(static fn (array $row): int => (int) $row['anzahl_stiche'], $old);
            ksort($oldCounts); ksort($stichCounts);
            if ($oldCounts !== $stichCounts) (new Gaben())->resetAbgabenForStandblatt($id, $userId);
            $connection->commit();
            return true;
        } catch (\Throwable $error) {
            $connection->rollBack();
            throw $error;
        }
    }

    private function saveStiche(int $id, int $anlassId, array $counts, int $userId, array $old): void
    {
        $connection = Database::connection();
        $rows = [];
        $total = 0;
        $lookup = $connection->prepare('SELECT id, preis FROM stich WHERE id = ? AND id_anlass = ? FOR UPDATE');
        foreach ($counts as $stichId => $count) {
            if (!is_int($count) || $count < 1 || $count > 1000) throw new \InvalidArgumentException('Ungültige Anzahl Stiche.');
            $lookup->execute([(int) $stichId, $anlassId]);
            $stich = $lookup->fetch();
            if (!$stich) throw new \InvalidArgumentException('Stich gehört nicht zu diesem Anlass.');
            $previous = $old[(int) $stichId] ?? null;
            $preis = $previous['preis'] ?? $stich['preis'] ?? '0';
            $kosten = $previous !== null && (int) $previous['anzahl_stiche'] === $count
                ? (int) round((float) $previous['kosten'] * 100) : (int) round((float) $preis * $count * 100);
            $total += $kosten;
            if ($total > 9999999999 || $kosten < 0) throw new \InvalidArgumentException('Standblattkosten überschreiten den zulässigen Bereich.');
            $rows[] = [(int) $stichId, $count, $preis, number_format($kosten / 100, 2, '.', ''), $previous['preis_quelle'] ?? 'erfasst'];
        }
        $delete = $connection->prepare('DELETE FROM standblatt_stich WHERE id_standblatt = ?');
        $delete->execute([$id]);
        $insert = $connection->prepare('INSERT INTO standblatt_stich (id_standblatt, id_stich, anzahl_stiche, preis, kosten, preis_quelle, created_by_user_id, updated_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($rows as $row) $insert->execute(array_merge([$id], $row, [$userId, $userId]));
        $update = $connection->prepare('UPDATE standblatt SET kosten = ? WHERE id = ?');
        $update->execute([number_format($total / 100, 2, '.', ''), $id]);
    }

    /**
     * Aktualisiert die Grunddaten eines Standblatts.
     */
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

    /**
     * Speichert den Wunsch zur Teilnahme am Final.
     */
    public function updateFinalTeilnahme(int $id, bool $teilnahme, int $userId): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE standblatt
             SET final_teilnahme = :final_teilnahme, updated_by_user_id = :updated_by_user_id
             WHERE id = :id'
        );

        return $statement->execute([
            'id' => $id,
            'final_teilnahme' => $teilnahme ? 1 : 0,
            'updated_by_user_id' => $userId,
        ]);
    }

    /**
     * Loescht ein Standblatt.
     */
    public function delete(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM standblatt
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    /**
     * Normalisiert Standblattdaten fuer INSERT und UPDATE.
     */
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
