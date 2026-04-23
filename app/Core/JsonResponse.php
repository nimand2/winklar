<?php

declare(strict_types=1);

namespace App\Core;

final class JsonResponse
{
    public static function send(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $message, int $statusCode, string $errorCode = 'ERROR'): void
    {
        self::send([
            'success' => false,
            'message' => $message,
            'errorCode' => $errorCode,
        ], $statusCode);
    }
}
