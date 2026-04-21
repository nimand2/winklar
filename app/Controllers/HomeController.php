<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

final class HomeController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function index(): void
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->redirect('/login');
    }
}
