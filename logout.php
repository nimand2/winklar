<?php

declare(strict_types=1);

require_once __DIR__ . '/auth/auth.php';

logout_user();
set_flash_message('success', 'Du wurdest erfolgreich ausgeloggt.');
redirect('/login.php');

