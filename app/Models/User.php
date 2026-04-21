<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, username, email
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByLogin(string $login): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, username, email, password_hash
             FROM users
             WHERE email = :email_login OR username = :username_login
             LIMIT 1'
        );
        $statement->execute([
            'email_login' => $login,
            'username_login' => $login,
        ]);

        $user = $statement->fetch();

        return $user ?: null;
    }
}
