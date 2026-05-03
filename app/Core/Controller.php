<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Rendert eine View mit den vom Controller vorbereiteten Daten.
     */
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Leitet auf einen Anwendungspfad weiter und beendet die aktuelle Anfrage.
     */
    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }
}
