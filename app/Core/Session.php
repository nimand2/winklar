<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    /**
     * Startet die Session mit den zentralen Cookie-Einstellungen.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => REMEMBER_ME_SECURE_COOKIE,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    /**
     * Erneuert die Session-ID nach sicherheitsrelevanten Statuswechseln.
     */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /**
     * Speichert eine einmalige Statusmeldung fuer den naechsten Request.
     */
    public static function putFlash(string $type, string $message): void
    {
        self::start();
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * Liest und entfernt die gespeicherte Flash-Meldung.
     */
    public static function pullFlash(): ?array
    {
        self::start();

        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return is_array($flash) ? $flash : null;
    }

    /**
     * Loescht Sessiondaten und entfernt das Session-Cookie.
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 3600,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }
}
