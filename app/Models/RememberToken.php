<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RememberToken
{
    public function create(int $userId, string $selector, string $validatorHash, string $expiresAt): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO user_remember_tokens (user_id, selector, validator_hash, expires_at)
             VALUES (:user_id, :selector, :validator_hash, :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'validator_hash' => $validatorHash,
            'expires_at' => $expiresAt,
        ]);
    }

    public function deleteBySelector(string $selector): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM user_remember_tokens
             WHERE selector = :selector'
        );
        $statement->execute(['selector' => $selector]);
    }

    public function findBySelectorWithUser(string $selector): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT t.id, t.user_id, t.validator_hash, t.expires_at, u.id AS user_id_real, u.username, u.email
             FROM user_remember_tokens t
             INNER JOIN users u ON u.id = t.user_id
             WHERE t.selector = :selector
             LIMIT 1'
        );
        $statement->execute(['selector' => $selector]);

        $token = $statement->fetch();

        return $token ?: null;
    }
}
