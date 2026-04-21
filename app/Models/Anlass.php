<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Anlass
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, name_anlass, start_anlass, end_anlass
             FROM anlass
             ORDER BY start_anlass DESC, id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }
}
