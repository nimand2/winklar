<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Auszeichnungslimitten
{
    public function getById(int $id): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, anlass_id, limit_auszeichnung, created_by_user_id, created_at, updated_by_user_id, updated_at
             FROM auszeichnungslimitten
             WHERE id = :id'
        );
        $statement->execute([':id' => $id]);

        return $statement->fetch();
    }
}    