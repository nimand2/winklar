<?php

declare(strict_types=1);

use App\Core\Url;

$pageTitle = (string) ($pageTitle ?? '');
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= htmlspecialchars(Url::asset('css/app.css')) ?>">
