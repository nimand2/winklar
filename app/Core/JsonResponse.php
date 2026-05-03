<?php

declare(strict_types=1);

namespace App\Core;

final class JsonResponse
{
    /**
     * Sendet eine JSON-Antwort mit HTTP-Statuscode.
     */
    public static function send(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Sendet eine standardisierte JSON-Fehlerantwort fuer API-Endpunkte.
     */
    public static function error(string $message, int $statusCode, string $errorCode = 'ERROR'): void
    {
        self::send([
            'success' => false,
            'message' => $message,
            'errorCode' => $errorCode,
        ], $statusCode);
    }
}
