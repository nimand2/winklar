<?php

declare(strict_types=1);

namespace App\Core;

final class Navigation
{
    public static function forView(string $view, array $data): array
    {
        $titles = [
            'dashboard/index' => 'Übersicht', 'anlass/index' => 'Anlässe',
            'anlass/show' => 'Anlassübersicht', 'anlass/konfiguration' => 'Konfiguration',
            'anlass/rangliste' => 'Rangliste', 'anlass/kasse' => 'Kasse', 'anlass/final' => 'Finalauswertung',
            'loesen/index' => 'Standblätter', 'loesen/adresseSelect' => 'Schützen auswählen',
            'loesen/create' => 'Standblatt erstellen', 'loesen/edit' => 'Standblatt bearbeiten',
            'abrechnen/abrechnen' => 'Standblatt abrechnen', 'adressen/index' => 'Schützen',
        ];
        $title = $titles[$view] ?? null;
        if ($view === 'anlass/form') {
            $title = ($data['mode'] ?? 'create') === 'edit' ? 'Anlass bearbeiten' : 'Anlass erstellen';
        } elseif ($view === 'adressen/form') {
            $title = empty($data['adresse']['id']) ? 'Schützen erfassen' : 'Schützen bearbeiten';
        }
        if ($title === null) {
            return [];
        }
        $anlass = $data['anlass'] ?? null;
        $event = is_array($anlass) && isset($anlass['id']) && (int) $anlass['id'] > 0 ? $anlass : null;
        $global = str_starts_with($view, 'adressen/') && $event === null ? 'schuetzen' : ($view === 'dashboard/index' ? 'dashboard' : 'anlass');
        $links = [
            ['key' => 'dashboard', 'label' => 'Übersicht', 'path' => '/dashboard'],
            ['key' => 'anlass', 'label' => 'Anlässe', 'path' => '/anlass'],
            ['key' => 'schuetzen', 'label' => 'Alle Schützen', 'path' => '/schuetzen'],
        ];
        $crumbs = [['label' => 'Übersicht', 'path' => '/dashboard']];
        $tabs = [];
        $standblattLinks = [];
        $back = null;
        if ($event !== null) {
            $base = '/anlass/' . (int) $event['id'];
            $section = match ($view) {
                'anlass/konfiguration', 'anlass/form' => 'konfiguration',
                'anlass/rangliste' => 'rangliste', 'anlass/kasse' => 'kasse', 'anlass/final' => 'final',
                'adressen/form', 'adressen/index' => 'schuetzen',
                'loesen/adresseSelect', 'loesen/create' => 'neu',
                'loesen/index', 'loesen/edit', 'abrechnen/abrechnen' => 'standblaetter',
                default => 'uebersicht',
            };
            foreach ([
                ['uebersicht', 'Anlassübersicht', ''], ['neu', 'Standblatt erstellen', '/loesen/adresse'],
                ['standblaetter', 'Standblätter', '/loesen'], ['schuetzen', 'Schützen', '/schuetzen'],
                ['rangliste', 'Rangliste', '/abschliessen'], ['final', 'Finalauswertung', '/final'],
                ['kasse', 'Kasse', '/kasse'], ['konfiguration', 'Konfiguration', '/konfiguration'],
            ] as [$key, $label, $suffix]) {
                $tabs[] = ['label' => $label, 'path' => $base . $suffix, 'active' => $key === $section];
            }
            $crumbs[] = ['label' => 'Anlässe', 'path' => '/anlass'];
            if ($view !== 'anlass/show') {
                $crumbs[] = ['label' => (string) $event['name_anlass'], 'path' => $base];
                $back = ['label' => 'Zurück zur Anlassübersicht', 'path' => $base];
            } else {
                $title = (string) $event['name_anlass'];
                $back = ['label' => 'Zurück zu den Anlässen', 'path' => '/anlass'];
            }
            if (in_array($view, ['loesen/edit', 'abrechnen/abrechnen'], true)) {
                $crumbs[] = ['label' => 'Standblätter', 'path' => $base . '/loesen'];
                $id = (int) ($data['standblatt']['id'] ?? 0);
                $sheet = $base . '/loesen/' . $id;
                $title = ($view === 'abrechnen/abrechnen' ? 'Abrechnen' : 'Bearbeiten') . ' · Standblatt #' . $id;
                $back = ['label' => 'Zurück zu den Standblättern', 'path' => $base . '/loesen'];
                $standblattLinks = [
                    ['label' => 'Standblatt bearbeiten', 'path' => $sheet, 'active' => $view === 'loesen/edit'],
                    ['label' => 'Standblatt abrechnen', 'path' => $sheet . '/abrechnen', 'active' => $view === 'abrechnen/abrechnen'],
                ];
            } elseif ($view === 'loesen/create') {
                $crumbs[] = ['label' => 'Schützen auswählen', 'path' => $base . '/loesen/adresse'];
                $back = ['label' => 'Zurück zur Schützenauswahl', 'path' => $base . '/loesen/adresse'];
            } elseif ($view === 'adressen/form') {
                $crumbs[] = ['label' => 'Schützen', 'path' => $base . '/schuetzen'];
                $back = ['label' => 'Zurück zu den Schützen', 'path' => $base . '/schuetzen'];
            } elseif ($view === 'anlass/form') {
                $crumbs[] = ['label' => 'Konfiguration', 'path' => $base . '/konfiguration'];
                $back = ['label' => 'Zurück zur Konfiguration', 'path' => $base . '/konfiguration'];
            }
        } elseif (str_starts_with($view, 'adressen/')) {
            if ($view === 'adressen/form') {
                $crumbs[] = ['label' => 'Schützen', 'path' => '/schuetzen'];
                $back = ['label' => 'Zurück zu den Schützen', 'path' => '/schuetzen'];
            } else {
                $back = ['label' => 'Zurück zur Übersicht', 'path' => '/dashboard'];
            }
        } elseif ($view === 'anlass/form') {
            $crumbs[] = ['label' => 'Anlässe', 'path' => '/anlass'];
            $back = ['label' => 'Zurück zu den Anlässen', 'path' => '/anlass'];
        } elseif ($view === 'anlass/index') {
            $back = ['label' => 'Zurück zur Übersicht', 'path' => '/dashboard'];
        }
        if ($view === 'dashboard/index') {
            $crumbs = [];
        }
        $crumbs[] = ['label' => $title, 'path' => null];
        return ['global' => $global, 'links' => $links, 'event' => $event, 'tabs' => $tabs, 'crumbs' => $crumbs, 'back' => $back, 'standblattLinks' => $standblattLinks];
    }
}
