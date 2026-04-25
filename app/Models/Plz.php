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

    public function getActiveOptions(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, plz4, ortschaftsname, kantonskuerzel
             FROM plz
             WHERE ist_eintrag_aktiv = 1
             ORDER BY plz4 ASC, ortschaftsname ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
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

    public function findByLookup(string $lookup): ?array
    {
        $lookup = trim($lookup);

        if ($lookup === '') {
            return null;
        }

        preg_match('/^(\d{4})\s*(.*)$/', $lookup, $matches);
        $plz4 = $matches[1] ?? null;
        $ortschaftsname = trim((string) ($matches[2] ?? ''));

        if ($plz4 === null) {
            return null;
        }

        if ($ortschaftsname !== '') {
            $statement = Database::connection()->prepare(
                'SELECT id, plz4, ortschaftsname
                 FROM plz
                 WHERE plz4 = :plz4
                   AND ortschaftsname = :ortschaftsname
                   AND ist_eintrag_aktiv = 1
                 ORDER BY id ASC
                 LIMIT 1'
            );
            $statement->execute([
                'plz4' => $plz4,
                'ortschaftsname' => $ortschaftsname,
            ]);

            $plz = $statement->fetch();

            if ($plz) {
                return $plz;
            }
        }

        $statement = Database::connection()->prepare(
            'SELECT id, plz4, ortschaftsname
             FROM plz
             WHERE plz4 = :plz4
               AND ist_eintrag_aktiv = 1
             ORDER BY adressenanteil DESC, ortschaftsname ASC, id ASC
             LIMIT 1'
        );
        $statement->execute(['plz4' => $plz4]);

        $plz = $statement->fetch();

        return $plz ?: null;
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
