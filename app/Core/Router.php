<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /**
     * @var array<string, array<int, array{path: string, pattern: string, handler: callable}>>
     */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->map('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->map('DELETE', $path, $handler);
    }

    public function options(string $path, callable $handler): void
    {
        $this->map('OPTIONS', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath($uri);

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

            $route['handler']($params);
            return;
        }

        http_response_code(404);
        echo '404 Not Found';
    }

    private function map(string $method, string $path, callable $handler): void
    {
        $normalizedPath = $this->normalizePath($path);
        $quotedPath = preg_quote($normalizedPath, '#');
        $pattern = preg_replace_callback(
            '/\\\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\\\}/',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $quotedPath
        );

        $this->routes[$method][] = [
            'path' => $normalizedPath,
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }
}
