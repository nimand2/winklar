<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        self::requireView($view, $data);
    }

    public static function partial(string $view, array $data = []): void
    {
        self::requireView($view, $data);
    }

    private static function requireView(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException(sprintf('View "%s" wurde nicht gefunden.', $view));
        }

        extract($data, EXTR_SKIP);
        require $viewFile;
    }
}
