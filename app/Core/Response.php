<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function notFound(string $message = '404 Not Found'): never
    {
        http_response_code(404);
        echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        exit;
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . Url::app($path));
        exit;
    }
}
