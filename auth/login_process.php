<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

$login = trim((string) ($_POST['login'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$rememberMe = isset($_POST['remember_me']);

if ($login === '' || $password === '') {
    set_flash_message('error', 'Bitte Benutzername/E-Mail und Passwort ausfuellen.');
    redirect('/login.php');
}

$user = find_user_by_login($login);

if (!$user || !password_verify($password, (string) $user['password_hash'])) {
    set_flash_message('error', 'Die Login-Daten sind ungueltig.');
    redirect('/login.php');
}

login_user($user, $rememberMe);
set_flash_message('success', 'Login erfolgreich.');
redirect('/dashboard.php');

