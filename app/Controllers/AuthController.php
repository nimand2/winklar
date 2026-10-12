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

    /**
     * Zeigt das Loginformular oder leitet angemeldete Benutzer zum Dashboard.
     */
    public function showLogin(): void
    {
        if ($this->authService->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->render('auth/login', [
            'flash' => Session::pullFlash(),
        ]);
    }

    /**
     * Verarbeitet den Login-POST und setzt passende Flash-Meldungen.
     */
    public function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/login');
        }

        $login = is_string($_POST['login'] ?? null) ? trim($_POST['login']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $rememberMe = isset($_POST['remember_me']);

        if ($login === '' || $password === '' || mb_strlen($login, 'UTF-8') > 150 || strlen($password) > 4096) {
            Session::putFlash('error', 'Bitte Benutzername/E-Mail und Passwort ausfüllen.');
            $this->redirect('/login');
        }

        try {
            $loggedIn = $this->authService->attemptLogin($login, $password, $rememberMe);
        } catch (\App\Core\LoginLimitExceeded $exception) {
            http_response_code(429);
            header('Retry-After: 900');
            $this->render('auth/login', ['flash' => ['type' => 'error', 'message' => $exception->getMessage()]]);
            return;
        }
        if (!$loggedIn) {
            Session::putFlash('error', 'Die Login-Daten sind ungültig.');
            $this->redirect('/login');
        }

        Session::putFlash('success', 'Login erfolgreich.');
        $this->redirect('/dashboard');
    }

    /**
     * Meldet den Benutzer ab und fuehrt zur Login-Seite zurueck.
     */
    public function logout(): void
    {
        $this->authService->logout();
        Session::putFlash('success', 'Du wurdest erfolgreich ausgeloggt.');
        $this->redirect('/login');
    }
}
