<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string, array<int, array{path: string, pattern: string, handler: callable}>>
     */
    private array $routes = [];

    /**
     * Registriert eine GET-Route.
     */
    public function get(string $path, callable $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    /**
     * Registriert eine POST-Route.
     */
    public function post(string $path, callable $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    /**
     * Registriert eine PUT-Route.
     */
    public function put(string $path, callable $handler): void
    {
        $this->map('PUT', $path, $handler);
    }

    /**
     * Registriert eine DELETE-Route.
     */
    public function delete(string $path, callable $handler): void
    {
        $this->map('DELETE', $path, $handler);
    }

    /**
     * Registriert eine OPTIONS-Route.
     */
    public function options(string $path, callable $handler): void
    {
        $this->map('OPTIONS', $path, $handler);
    }

    /**
     * Sucht die passende Route zur Anfrage und ruft den Handler mit Pfadparametern auf.
     */
    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath($uri);
        $base = rtrim(defined('APP_BASE_PATH') ? APP_BASE_PATH : '', '/');
        if ($base !== '') {
            if ($path !== $base && !str_starts_with($path, $base . '/')) {
                Response::notFound();
            }
            $path = $this->normalizePath(substr($path, strlen($base)) ?: '/');
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            // API routes authenticate independently with Bearer tokens.
            if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)
                && !str_starts_with($route['path'], '/api/')
                && !Csrf::valid($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                echo 'Ungültiges Formular-Token. Bitte Seite neu laden.';
                return;
            }

            try {
                $route['handler']($params);
            } catch (\InvalidArgumentException $exception) {
                if (str_starts_with($route['path'], '/api/')) {
                    JsonResponse::error($exception->getMessage(), 422, 'INVALID_INPUT');
                } else {
                    http_response_code(422);
                    echo htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                }
            }
            return;
        }

        Response::notFound();
    }

    /**
     * Wandelt eine Route mit Platzhaltern in ein Regex-Muster um.
     */
    private function map(string $method, string $path, callable $handler): void
    {
        $normalizedPath = $this->normalizePath($path);
        $quotedPath = preg_quote($normalizedPath, '#');
        $pattern = preg_replace_callback(
            '/\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}/',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>'
                . (($matches[1] === 'id' || str_ends_with($matches[1], 'Id')) ? '[0-9]+' : '[^/]+') . ')',
            $quotedPath
        );

        $this->routes[$method][] = [
            'path' => $normalizedPath,
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    /**
     * Normalisiert Pfade fuer Routing-Vergleiche ohne Querystring und trailing Slash.
     */
    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }
}
