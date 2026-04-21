<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    public static function app(string $path = ''): string
    {
        $basePath = rtrim(APP_BASE_PATH, '/');
        $path = '/' . ltrim($path, '/');

        return $basePath . $path;
    }

    public static function asset(string $path): string
    {
        return self::app('/assets/' . ltrim($path, '/'));
    }
}
