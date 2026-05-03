<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Schussdaten
{
    /**
     * Laedt alle importierten Schussdaten.
     */
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, start_nr, primaerwertung, schussart, bahn_nr, sekundaerwertung,
                    teiler, schuss_zeit, mouche, x_koordinate, y_koordinate, in_time,
                    time_since_change, sweep_direction, demonstration, match_index, stich_index,
                    ins_del, total_art, gruppe, feuerart, log_event, log_typ,
                    zeit_seit_jahresanfang, abloesung, waffe, position, target_id,
                    externe_nummer, import_hash, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM schussdaten
             ORDER BY schuss_zeit DESC, id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * Speichert einen Schussdatensatz, wenn sein Import-Hash noch nicht existiert.
     */
    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO schussdaten (
                id_anlass, start_nr, primaerwertung, schussart, bahn_nr, sekundaerwertung,
                teiler, schuss_zeit, mouche, x_koordinate, y_koordinate, in_time,
                time_since_change, sweep_direction, demonstration, match_index, stich_index,
                ins_del, total_art, gruppe, feuerart, log_event, log_typ,
                zeit_seit_jahresanfang, abloesung, waffe, position, target_id,
                externe_nummer, import_hash, created_by_user_id, updated_by_user_id
             ) VALUES (
                :id_anlass, :start_nr, :primaerwertung, :schussart, :bahn_nr, :sekundaerwertung,
                :teiler, :schuss_zeit, :mouche, :x_koordinate, :y_koordinate, :in_time,
                :time_since_change, :sweep_direction, :demonstration, :match_index, :stich_index,
                :ins_del, :total_art, :gruppe, :feuerart, :log_event, :log_typ,
                :zeit_seit_jahresanfang, :abloesung, :waffe, :position, :target_id,
                :externe_nummer, :import_hash, :created_by_user_id, :updated_by_user_id
             )
             ON DUPLICATE KEY UPDATE id = id'
        );
        $statement->execute($this->buildPayload($data));

        return $statement->rowCount() > 0 ? (int) Database::connection()->lastInsertId() : 0;
    }

    /**
     * Importiert mehrere Schussdatensaetze in einer Transaktion.
     */
    public function createMany(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $connection = Database::connection();
        $connection->beginTransaction();
        $created = 0;

        try {
            foreach ($rows as $row) {
                if ($this->create($row) > 0) {
                    $created++;
                }
            }

            $connection->commit();
        } catch (\Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }

        return $created;
    }

    /**
     * Findet einen Schussdatensatz anhand seiner ID.
     */
    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, start_nr, primaerwertung, schussart, bahn_nr, sekundaerwertung,
                    teiler, schuss_zeit, mouche, x_koordinate, y_koordinate, in_time,
                    time_since_change, sweep_direction, demonstration, match_index, stich_index,
                    ins_del, total_art, gruppe, feuerart, log_event, log_typ,
                    zeit_seit_jahresanfang, abloesung, waffe, position, target_id,
                    externe_nummer, import_hash, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM schussdaten
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $schussdaten = $statement->fetch();

        return $schussdaten ?: null;
    }

    /**
     * Laedt Schussdaten fuer Startnummer und Anlass.
     */
    public function findByStartNrAndIdAnlass(int $startNr, int $idAnlass): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, start_nr, primaerwertung, schussart, bahn_nr, sekundaerwertung,
                    teiler, schuss_zeit, mouche, x_koordinate, y_koordinate, in_time,
                    time_since_change, sweep_direction, demonstration, match_index, stich_index,
                    ins_del, total_art, gruppe, feuerart, log_event, log_typ,
                    zeit_seit_jahresanfang, abloesung, waffe, position, target_id,
                    externe_nummer, import_hash, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM schussdaten
             WHERE start_nr = :start_nr AND id_anlass = :id_anlass  
             ORDER BY schuss_zeit DESC'
        );
        $statement->execute(['start_nr' => $startNr, 'id_anlass' => $idAnlass]);

        return $statement->fetchAll();
    }

    /**
     * Laedt alle Schussdaten eines Anlasses in Auswertungsreihenfolge.
     */
    public function findByAnlassId(int $idAnlass): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, id_anlass, start_nr, primaerwertung, schussart, bahn_nr, sekundaerwertung,
                    teiler, schuss_zeit, mouche, x_koordinate, y_koordinate, in_time,
                    time_since_change, sweep_direction, demonstration, match_index, stich_index,
                    ins_del, total_art, gruppe, feuerart, log_event, log_typ,
                    zeit_seit_jahresanfang, abloesung, waffe, position, target_id,
                    externe_nummer, import_hash, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM schussdaten
             WHERE id_anlass = :id_anlass
             ORDER BY start_nr ASC, match_index ASC, stich_index ASC, schuss_zeit ASC, id ASC'
        );
        $statement->execute(['id_anlass' => $idAnlass]);

        return $statement->fetchAll();
    }

    /**
     * Normalisiert importierte Schusswerte auf die Datenbankspalten.
     */
    private function buildPayload(array $data): array
    {
        return [
            'id_anlass' => $data['id_anlass'],
            'start_nr' => $data['start_nr'] ?? null,
            'primaerwertung' => $data['primaerwertung'] ?? null,
            'schussart' => $data['schussart'] ?? null,
            'bahn_nr' => $data['bahn_nr'] ?? null,
            'sekundaerwertung' => $data['sekundaerwertung'] ?? null,
            'teiler' => $data['teiler'] ?? null,
            'schuss_zeit' => $data['schuss_zeit'] ?? null,
            'mouche' => $data['mouche'] ?? 0,
            'x_koordinate' => $data['x_koordinate'] ?? null,
            'y_koordinate' => $data['y_koordinate'] ?? null,
            'in_time' => $data['in_time'] ?? 1,
            'time_since_change' => $data['time_since_change'] ?? null,
            'sweep_direction' => $data['sweep_direction'] ?? null,
            'demonstration' => $data['demonstration'] ?? 0,
            'match_index' => $data['match_index'] ?? null,
            'stich_index' => $data['stich_index'] ?? null,
            'ins_del' => $data['ins_del'] ?? 0,
            'total_art' => $data['total_art'] ?? null,
            'gruppe' => $data['gruppe'] ?? null,
            'feuerart' => $data['feuerart'] ?? null,
            'log_event' => $data['log_event'] ?? null,
            'log_typ' => $data['log_typ'] ?? null,
            'zeit_seit_jahresanfang' => $data['zeit_seit_jahresanfang'] ?? null,
            'abloesung' => $data['abloesung'] ?? null,
            'waffe' => $data['waffe'] ?? null,
            'position' => $data['position'] ?? null,
            'target_id' => $data['target_id'] ?? null,
            'externe_nummer' => $data['externe_nummer'] ?? null,
            'import_hash' => $data['import_hash'],
            'created_by_user_id' => $data['created_by_user_id'] ?? null,
            'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
        ];
    }
}
