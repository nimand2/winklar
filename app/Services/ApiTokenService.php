<?php

declare(strict_types=1);

namespace App\Services;

final class ApiTokenService
{
    private const LIFETIME_SECONDS = 3600;

    public function createToken(int $userId): string
    {
        $payload = [
            'userId' => $userId,
            'expiresAt' => time() + self::LIFETIME_SECONDS,
        ];

        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        $payloadPart = $this->base64UrlEncode($payloadJson);
        $signature = hash_hmac('sha256', $payloadPart, $this->secret());

        return $payloadPart . '.' . $signature;
    }

    public function lifetimeSeconds(): int
    {
        return self::LIFETIME_SECONDS;
    }

    public function userIdFromAuthorizationHeader(?string $authorizationHeader): ?int
    {
        if ($authorizationHeader === null || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authorizationHeader, 7));
        [$payloadPart, $signature] = array_pad(explode('.', $token, 2), 2, '');

        if ($payloadPart === '' || $signature === '') {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $payloadPart, $this->secret());
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($payloadPart);
        if ($payloadJson === null) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        $expiresAt = (int) ($payload['expiresAt'] ?? 0);
        $userId = (int) ($payload['userId'] ?? 0);

        if ($expiresAt < time() || $userId <= 0) {
            return null;
        }

        return $userId;
    }

    private function secret(): string
    {
        if (defined('API_TOKEN_SECRET')) {
            $secret = (string) constant('API_TOKEN_SECRET');

            if ($secret !== '') {
                return $secret;
            }
        }

        return hash('sha256', DB_PASS . '|' . DB_NAME);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
