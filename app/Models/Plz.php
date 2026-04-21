<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Anlass
{
    public function search_by_plz(string $query): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, plz4, ortschaftsname
             FROM plz
             WHERE plz4 LIKE :query
             ORDER BY plz4 ASC
             LIMIT 10'
        );
        $statement->execute(['query' => "%$query%"]);

        return $statement->fetchAll();
    }
    public function find_by_ortschaftsname(string $ortschaftsname): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name, description, date
             FROM anlass
             WHERE ortschaftsname = :ortschaftsname
             LIMIT 1'
        );
        $statement->execute(['ortschaftsname' => $ortschaftsname]);

        $anlass = $statement->fetch();

        return $anlass ?: null;
    }
}