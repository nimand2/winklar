<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Services\AnlassService;

final class AnlassController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly AnlassService $anlassService,
    )
    {
    }

    public function index(): void
    {
        $user = $this->authService->requireUser();
        $anlass = $this->anlassService->getAnlass();

        $this->render('anlass/index', [
            'user' => $user,
            'anlass' => $anlass,
        ]);
    }

    public function show(array $params): void
    {
        $user = $this->authService->requireUser();
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(404);
            echo 'Anlass nicht gefunden';
            return;
        }

        $anlass = $this->anlassService->getAnlassById($id);

        if ($anlass === null) {
            http_response_code(404);
            echo 'Anlass nicht gefunden';
            return;
        }

        $this->render('anlass/show', [
            'user' => $user,
            'anlass' => $anlass,
        ]);
    }
}
