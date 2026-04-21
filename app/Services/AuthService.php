<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Response;
use App\Core\Session;
use App\Models\RememberToken;
use App\Models\User;

final class AuthService
{
    private ?array $currentUser = null;

    public function __construct(
        private readonly User $userModel,
        private readonly RememberToken $rememberTokenModel
    ) {
    }

    public function boot(): void
    {
        Session::start();
        $this->attemptRememberMeLogin();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
    }

    public function currentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        $userId = (int) $_SESSION['user_id'];
        $this->currentUser = $this->userModel->findById($userId);

        return $this->currentUser;
    }

    public function requireUser(): array
    {
        $user = $this->currentUser();

        if ($user !== null) {
            return $user;
        }

        $this->logout();
        Session::putFlash('error', 'Bitte logge dich zuerst ein.');
        Response::redirect('/login');
    }

    public function findUserByLogin(string $login): ?array
    {
        return $this->userModel->findByLogin($login);
    }

    public function attemptLogin(string $login, string $password, bool $rememberMe = false): bool
    {
        $user = $this->findUserByLogin($login);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        $this->loginUser($user, $rememberMe);

        return true;
    }

    public function loginUser(array $user, bool $rememberMe = false): void
    {
        $this->finalizeLogin($user);

        if ($rememberMe) {
            $this->createRememberMeToken((int) $user['id']);
            return;
        }

        $selector = $this->rememberMeSelectorFromCookie();

        if ($selector !== null) {
            $this->rememberTokenModel->deleteBySelector($selector);
        }

        $this->clearRememberMeCookie();
    }

    public function logout(): void
    {
        $selector = $this->rememberMeSelectorFromCookie();

        if ($selector !== null) {
            $this->rememberTokenModel->deleteBySelector($selector);
        }

        $this->clearRememberMeCookie();
        $this->currentUser = null;
        Session::destroy();
    }

    public function attemptRememberMeLogin(): void
    {
        if ($this->isLoggedIn() || empty($_COOKIE[REMEMBER_ME_COOKIE])) {
            return;
        }

        [$selector, $validator] = array_pad(
            explode(':', (string) $_COOKIE[REMEMBER_ME_COOKIE], 2),
            2,
            ''
        );

        if ($selector === '' || $validator === '') {
            $this->clearRememberMeCookie();
            return;
        }

        $token = $this->rememberTokenModel->findBySelectorWithUser($selector);

        if ($token === null) {
            $this->clearRememberMeCookie();
            return;
        }

        if (strtotime((string) $token['expires_at']) < time()) {
            $this->rememberTokenModel->deleteBySelector($selector);
            $this->clearRememberMeCookie();
            return;
        }

        $currentValidatorHash = hash('sha256', $validator);

        if (!hash_equals((string) $token['validator_hash'], $currentValidatorHash)) {
            $this->rememberTokenModel->deleteBySelector($selector);
            $this->clearRememberMeCookie();
            return;
        }

        $this->rememberTokenModel->deleteBySelector($selector);

        $this->finalizeLogin([
            'id' => (int) $token['user_id_real'],
            'username' => (string) $token['username'],
            'email' => (string) $token['email'],
        ]);

        $this->createRememberMeToken((int) $token['user_id_real']);
    }

    private function finalizeLogin(array $user): void
    {
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];
        $this->currentUser = [
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'email' => (string) ($user['email'] ?? ''),
        ];
    }

    private function createRememberMeToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $validatorHash = hash('sha256', $validator);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + REMEMBER_ME_LIFETIME);

        $this->rememberTokenModel->create($userId, $selector, $validatorHash, $expiresAt);
        $this->setRememberMeCookie($selector, $validator);
    }

    private function rememberMeSelectorFromCookie(): ?string
    {
        if (empty($_COOKIE[REMEMBER_ME_COOKIE])) {
            return null;
        }

        [$selector] = explode(':', (string) $_COOKIE[REMEMBER_ME_COOKIE], 2);

        return $selector !== '' ? $selector : null;
    }

    private function setRememberMeCookie(string $selector, string $validator): void
    {
        setcookie(
            REMEMBER_ME_COOKIE,
            $selector . ':' . $validator,
            [
                'expires' => time() + REMEMBER_ME_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => REMEMBER_ME_SECURE_COOKIE,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    private function clearRememberMeCookie(): void
    {
        setcookie(
            REMEMBER_ME_COOKIE,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => REMEMBER_ME_SECURE_COOKIE,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        unset($_COOKIE[REMEMBER_ME_COOKIE]);
    }
}
