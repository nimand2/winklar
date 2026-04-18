<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/db.php';

/**
 * Baut absolute Anwendungs-URLs auf.
 */
function app_url(string $path = ''): string
{
    $basePath = rtrim(APP_BASE_PATH, '/');
    $path = '/' . ltrim($path, '/');

    return $basePath . $path;
}

/**
 * Baut Asset-URLs innerhalb des Projekts auf.
 */
function asset_url(string $path): string
{
    return app_url('/assets/' . ltrim($path, '/'));
}

/**
 * Startet die Session mit sinnvollen Cookie-Optionen.
 */
function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => REMEMBER_ME_SECURE_COOKIE,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Kurze Helper-Funktion für Redirects.
 */
function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

/**
 * Flash-Nachricht für genau einen Request speichern.
 */
function set_flash_message(string $type, string $message): void
{
    ensure_session_started();
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Flash-Nachricht abrufen und direkt entfernen.
 */
function get_flash_message(): ?array
{
    ensure_session_started();

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Prüft, ob aktuell ein Benutzer eingeloggt ist.
 */
function is_logged_in(): bool
{
    ensure_session_started();
    return isset($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

/**
 * Lädt den aktuell eingeloggten Benutzer.
 */
function current_user(): ?array
{
    if (!is_logged_in()) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, username, email
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $_SESSION['user_id']]);

    $user = $statement->fetch();
    return $user ?: null;
}

/**
 * Sucht einen Benutzer anhand von E-Mail oder Benutzername.
 */
function find_user_by_login(string $login): ?array
{
    $statement = db()->prepare(
        'SELECT id, username, email, password_hash
         FROM users
         WHERE email = :email_login OR username = :username_login
         LIMIT 1'
    );
    $statement->execute([
        'email_login' => $login,
        'username_login' => $login,
    ]);

    $user = $statement->fetch();
    return $user ?: null;
}

/**
 * Legt den Session-Zustand für einen Benutzer an.
 */
function finalize_login(array $user): void
{
    ensure_session_started();

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
}

/**
 * Setzt das Remember-Me-Cookie.
 */
function set_remember_me_cookie(string $selector, string $validator): void
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

/**
 * Entfernt das Remember-Me-Cookie im Browser.
 */
function clear_remember_me_cookie(): void
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

/**
 * Speichert einen neuen Remember-Me-Token für einen Benutzer.
 */
function create_remember_me_token(int $userId): void
{
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expiresAt = gmdate('Y-m-d H:i:s', time() + REMEMBER_ME_LIFETIME);

    $statement = db()->prepare(
        'INSERT INTO user_remember_tokens (user_id, selector, validator_hash, expires_at)
         VALUES (:user_id, :selector, :validator_hash, :expires_at)'
    );
    $statement->execute([
        'user_id' => $userId,
        'selector' => $selector,
        'validator_hash' => $validatorHash,
        'expires_at' => $expiresAt,
    ]);

    set_remember_me_cookie($selector, $validator);
}

/**
 * Entfernt einen Remember-Me-Token anhand seines Selectors.
 */
function delete_remember_me_token_by_selector(string $selector): void
{
    $statement = db()->prepare(
        'DELETE FROM user_remember_tokens
         WHERE selector = :selector'
    );
    $statement->execute(['selector' => $selector]);
}

/**
 * Führt den eigentlichen Login durch.
 */
function login_user(array $user, bool $rememberMe = false): void
{
    finalize_login($user);

    if ($rememberMe) {
        create_remember_me_token((int) $user['id']);
        return;
    }

    if (!empty($_COOKIE[REMEMBER_ME_COOKIE])) {
        [$selector] = explode(':', (string) $_COOKIE[REMEMBER_ME_COOKIE], 2);
        if ($selector !== '') {
            delete_remember_me_token_by_selector($selector);
        }
    }

    clear_remember_me_cookie();
}

/**
 * Loggt den Benutzer aus und zerstört die Session vollständig.
 */
function logout_user(): void
{
    ensure_session_started();

    if (!empty($_COOKIE[REMEMBER_ME_COOKIE])) {
        [$selector] = explode(':', (string) $_COOKIE[REMEMBER_ME_COOKIE], 2);
        if ($selector !== '') {
            delete_remember_me_token_by_selector($selector);
        }
    }

    clear_remember_me_cookie();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

/**
 * Versucht automatisch per Remember-Me-Cookie einzuloggen.
 * Der Token wird bei erfolgreicher Verwendung rotiert.
 */
function attempt_remember_me_login(): void
{
    if (is_logged_in() || empty($_COOKIE[REMEMBER_ME_COOKIE])) {
        return;
    }

    [$selector, $validator] = array_pad(
        explode(':', (string) $_COOKIE[REMEMBER_ME_COOKIE], 2),
        2,
        ''
    );

    if ($selector === '' || $validator === '') {
        clear_remember_me_cookie();
        return;
    }

    $statement = db()->prepare(
        'SELECT t.id, t.user_id, t.validator_hash, t.expires_at, u.id AS user_id_real, u.username, u.email
         FROM user_remember_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.selector = :selector
         LIMIT 1'
    );
    $statement->execute(['selector' => $selector]);

    $token = $statement->fetch();

    if (!$token) {
        clear_remember_me_cookie();
        return;
    }

    if (strtotime((string) $token['expires_at']) < time()) {
        delete_remember_me_token_by_selector($selector);
        clear_remember_me_cookie();
        return;
    }

    $currentValidatorHash = hash('sha256', $validator);

    if (!hash_equals((string) $token['validator_hash'], $currentValidatorHash)) {
        delete_remember_me_token_by_selector($selector);
        clear_remember_me_cookie();
        return;
    }

    delete_remember_me_token_by_selector($selector);

    finalize_login([
        'id' => (int) $token['user_id_real'],
        'username' => (string) $token['username'],
        'email' => (string) $token['email'],
    ]);

    create_remember_me_token((int) $token['user_id_real']);
}

ensure_session_started();
attempt_remember_me_login();
