<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (!is_logged_in() || current_user() === null) {
    logout_user();
    set_flash_message('error', 'Bitte logge dich zuerst ein.');
    redirect('/login.php');
}
