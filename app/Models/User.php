<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class User
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, username, email, password_hash, created_at
             FROM users
             ORDER BY username ASC, id ASC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO users (username, email, password_hash)
             VALUES (:username, :email, :password_hash)'
        );
        $statement->execute($this->buildPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE users
             SET username = :username,
                 email = :email,
                 password_hash = :password_hash
             WHERE id = :id'
        );

        $payload = $this->buildPayload($data);
        $payload['id'] = $id;

        return $statement->execute($payload);
    }

    public function delete(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM users
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
    }

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

    private function buildPayload(array $data): array
    {
        return [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
        ];
    }
}
