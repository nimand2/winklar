<?php

declare(strict_types=1);
use App\Core\Url;
$navigation = $navigation ?? [];
if ($navigation === []) {
    return;
}
?>
<a class="app-skip-link visually-hidden-focusable" href="#main-content">Zum Inhalt springen</a>
<header class="app-navigation no-print">
    <div class="app-nav-container container-fluid px-3 px-xl-4">
        <nav class="app-global-nav d-flex flex-wrap align-items-center" aria-label="Hauptnavigation">
            <a class="app-brand d-inline-flex align-items-center gap-2 text-decoration-none" href="<?= htmlspecialchars(Url::app('/dashboard')) ?>">
                <span class="app-brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M12 1v5m0 12v5M1 12h5m12 0h5"/></svg></span>
                <span>Schiessverwaltung</span>
            </a>
            <div class="app-nav-links d-flex flex-wrap gap-1">
                <?php foreach ($navigation['links'] as $link): ?>
                    <a class="app-nav-link btn <?= $navigation['global'] === $link['key'] ? 'is-active btn-primary' : 'btn-light' ?>" href="<?= htmlspecialchars(Url::app($link['path'])) ?>" <?= $navigation['global'] === $link['key'] ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($link['label']) ?></a>
                <?php endforeach; ?>
            </div>
            <a class="app-logout btn btn-outline-secondary" href="<?= htmlspecialchars(Url::app('/logout')) ?>">Abmelden</a>
        </nav>
        <?php if ($navigation['event'] !== null): ?>
            <div class="app-event-name"><span>Aktueller Anlass</span> <?= htmlspecialchars((string) $navigation['event']['name_anlass']) ?></div>
            <nav class="app-event-nav d-flex flex-wrap gap-1" aria-label="Navigation im Anlass">
                <?php foreach ($navigation['tabs'] as $link): ?>
                    <a class="app-nav-link btn <?= $link['active'] ? 'is-active btn-primary' : 'btn-light' ?>" href="<?= htmlspecialchars(Url::app($link['path'])) ?>" <?= $link['active'] ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($link['label']) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</header>
<div class="app-location app-nav-container container-fluid px-3 px-xl-4 no-print">
    <nav aria-label="Seitenpfad"><ol class="breadcrumb mb-0">
        <?php foreach ($navigation['crumbs'] as $crumb): ?>
            <li class="breadcrumb-item <?= $crumb['path'] === null ? 'active' : '' ?>" <?= $crumb['path'] === null ? 'aria-current="page"' : '' ?>>
                <?php if ($crumb['path'] === null): ?><?= htmlspecialchars($crumb['label']) ?><?php else: ?><a href="<?= htmlspecialchars(Url::app($crumb['path'])) ?>"><?= htmlspecialchars($crumb['label']) ?></a><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol></nav>
    <?php if ($navigation['back'] !== null): ?><a class="app-back-link" href="<?= htmlspecialchars(Url::app($navigation['back']['path'])) ?>"><span aria-hidden="true">←</span> <?= htmlspecialchars($navigation['back']['label']) ?></a><?php endif; ?>
    <?php if ($navigation['standblattLinks'] !== []): ?>
        <nav class="app-sheet-nav d-flex flex-wrap gap-1" aria-label="Aktionen für dieses Standblatt">
            <?php foreach ($navigation['standblattLinks'] as $link): ?><a class="app-nav-link btn <?= $link['active'] ? 'is-active btn-primary' : 'btn-light' ?>" href="<?= htmlspecialchars(Url::app($link['path'])) ?>" <?= $link['active'] ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($link['label']) ?></a><?php endforeach; ?>
        </nav>
    <?php endif; ?>
</div>
