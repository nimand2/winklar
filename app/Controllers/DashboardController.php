<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

final class DashboardController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function index(): void
    {
        $user = $this->authService->requireUser();

        $this->render('dashboard/index', [
            'user' => $user,
        ]);
    }
}
