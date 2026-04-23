<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class RememberToken
{
    public function getAll(): array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, user_id, selector, validator_hash, expires_at, created_by_user_id, created_at,
                    updated_by_user_id, updated_at
             FROM user_remember_tokens
             ORDER BY expires_at DESC, id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create(array|int $data, ?string $selector = null, ?string $validatorHash = null, ?string $expiresAt = null): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO user_remember_tokens (
                user_id, selector, validator_hash, expires_at, created_by_user_id, updated_by_user_id
             ) VALUES (
                :user_id, :selector, :validator_hash, :expires_at, :created_by_user_id, :updated_by_user_id
             )'
        );
        $statement->execute($this->buildPayload($data, $selector, $validatorHash, $expiresAt));

        return (int) Database::connection()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, user_id, selector, validator_hash, expires_at, created_by_user_id, created_at,
                    updated_by_user_id, updated_at
             FROM user_remember_tokens
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $token = $statement->fetch();

        return $token ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $statement = Database::connection()->prepare(
            'UPDATE user_remember_tokens
             SET user_id = :user_id,
                 selector = :selector,
                 validator_hash = :validator_hash,
                 expires_at = :expires_at,
                 created_by_user_id = :created_by_user_id,
                 updated_by_user_id = :updated_by_user_id
             WHERE id = :id'
        );

        $payload = $this->buildPayload($data);
        $payload['id'] = $id;

        return $statement->execute($payload);
    }

    public function delete(int $id): bool
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM user_remember_tokens
             WHERE id = :id'
        );

        return $statement->execute(['id' => $id]);
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

    private function buildPayload(array|int $data, ?string $selector = null, ?string $validatorHash = null, ?string $expiresAt = null): array
    {
        if (is_array($data)) {
            return [
                'user_id' => $data['user_id'],
                'selector' => $data['selector'],
                'validator_hash' => $data['validator_hash'],
                'expires_at' => $data['expires_at'],
                'created_by_user_id' => $data['created_by_user_id'] ?? null,
                'updated_by_user_id' => $data['updated_by_user_id'] ?? null,
            ];
        }

        return [
            'user_id' => $data,
            'selector' => $selector,
            'validator_hash' => $validatorHash,
            'expires_at' => $expiresAt,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
        ];
    }
}
