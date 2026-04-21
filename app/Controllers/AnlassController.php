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
}
