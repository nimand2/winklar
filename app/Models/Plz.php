<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Plz
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, ortschaftsname, plz4, zusatzziffer, zip_id, gemeindename, bfs_nr,
                    kantonskuerzel, adressenanteil, e, n, ist_eintrag_aktiv, sprache,
                    validity_from, validity_to
             FROM plz
             ORDER BY plz4 ASC, ortschaftsname ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO plz (
                ortschaftsname, plz4, zusatzziffer, zip_id, gemeindename, bfs_nr,
                kantonskuerzel, adressenanteil, e, n, ist_eintrag_aktiv, sprache,
                validity_from, validity_to
             ) VALUES (
                :ortschaftsname, :plz4, :zusatzziffer, :zip_id, :gemeindename, :bfs_nr,
                :kantonskuerzel, :adressenanteil, :e, :n, :ist_eintrag_aktiv, :sprache,
                :validity_from, :validity_to
             )'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, ortschaftsname, plz4, zusatzziffer, zip_id, gemeindename, bfs_nr,
                    kantonskuerzel, adressenanteil, e, n, ist_eintrag_aktiv, sprache,
                    validity_from, validity_to
             FROM plz
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $plz = $statement->fetch();

        return $plz ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE plz
             SET ortschaftsname = :ortschaftsname,
                 plz4 = :plz4,
                 zusatzziffer = :zusatzziffer,
                 zip_id = :zip_id,
                 gemeindename = :gemeindename,
                 bfs_nr = :bfs_nr,
                 kantonskuerzel = :kantonskuerzel,
                 adressenanteil = :adressenanteil,
                 e = :e,
                 n = :n,
                 ist_eintrag_aktiv = :ist_eintrag_aktiv,
                 sprache = :sprache,
                 validity_from = :validity_from,
                 validity_to = :validity_to
             WHERE id = :id'
        );

        $payload = $this->buildPayload($data);
        $payload['id'] = $id;

        return $statement->execute($payload);
    }

    public function delete(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM plz
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

    public function search_by_plz(string $query): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, plz4, ortschaftsname
             FROM plz
             WHERE plz4 LIKE :query'
        );
        $statement->execute(['query' => "%$query%"]);

        return $statement->fetchAll();
    }
    public function find_by_ortschaftsname(string $ortschaftsname): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, plz4, ortschaftsname
             FROM plz
             WHERE ortschaftsname = :ortschaftsname'
        );
        $statement->execute(['ortschaftsname' => "%$ortschaftsname%"]);

        $plz = $statement->fetch();

        return $plz ?: null;
    }

    private function buildPayload(array $data): array
    {
        return [
            'ortschaftsname' => $data['ortschaftsname'],
            'plz4' => $data['plz4'],
            'zusatzziffer' => $data['zusatzziffer'] ?? null,
            'zip_id' => $data['zip_id'] ?? null,
            'gemeindename' => $data['gemeindename'] ?? null,
            'bfs_nr' => $data['bfs_nr'] ?? null,
            'kantonskuerzel' => $data['kantonskuerzel'] ?? null,
            'adressenanteil' => $data['adressenanteil'] ?? null,
            'e' => $data['e'] ?? null,
            'n' => $data['n'] ?? null,
            'ist_eintrag_aktiv' => $data['ist_eintrag_aktiv'] ?? 1,
            'sprache' => $data['sprache'] ?? null,
            'validity_from' => $data['validity_from'] ?? null,
            'validity_to' => $data['validity_to'] ?? null,
        ];
    }
}
