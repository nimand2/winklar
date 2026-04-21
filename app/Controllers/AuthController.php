<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function showLogin(): void
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [
            'flash' => Session::pullFlash(),
        ]);
    }

    public function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/login');
        }

        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $rememberMe = isset($_POST['remember_me']);

        if ($login === '' || $password === '') {
            Session::putFlash('error', 'Bitte Benutzername/E-Mail und Passwort ausfuellen.');
            $this->redirect('/login');
        }

        if (!$this->authService->attemptLogin($login, $password, $rememberMe)) {
            Session::putFlash('error', 'Die Login-Daten sind ungueltig.');
            $this->redirect('/login');
        }

        Session::putFlash('success', 'Login erfolgreich.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $this->authService->logout();
        Session::putFlash('success', 'Du wurdest erfolgreich ausgeloggt.');
        $this->redirect('/login');
    }
}
