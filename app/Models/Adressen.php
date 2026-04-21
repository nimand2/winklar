<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Adressen
{
    public function getAll(): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, firmen_anrede, nachname, vorname, plz_id, geburtsdatum
             FROM adressen
             WHERE *'
        );
        $statement->execute([]);

        $adresse = $statement->fetch();

        return $adresse ?: null;
    }
}