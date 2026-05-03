<?php

declare(strict_types=1);

namespace App\Core;

final class Url
{
    /**
     * Erzeugt einen URL-Pfad relativ zum konfigurierten App-Basispfad.
     */
    public static function app(string $path = ''): string
    {
        $basePath = rtrim(APP_BASE_PATH, '/');
        $path = '/' . ltrim($path, '/');

        return $basePath . $path;
    }

    /**
     * Erzeugt einen URL-Pfad fuer statische Assets unter public/assets.
     */
    public static function asset(string $path): string
    {
        return self::app('/assets/' . ltrim($path, '/'));
    }
}
