# Design Guide

Diese Beschreibung definiert das aktuelle Design des Projekts, damit neue Seiten optisch konsistent aufgebaut werden.

## Ziel des Designs

Das Design soll:

- sauber und modern wirken
- leicht, freundlich und technisch ordentlich aussehen
- mit wenig eigenem CSS auskommen
- auf Bootstrap 5 als Basis setzen
- Inhalte in klaren Kartenflaechen praesentieren

## Grundprinzipien

- Bootstrap 5 ist das Basis-Framework fuer Layout, Abstaende, Buttons, Formulare und Grid.
- Eigenes CSS wird nur fuer den Projekt-Charakter verwendet.
- Jede Seite nutzt denselben Hintergrundstil, dieselbe Kartenlogik und dieselben Abstaende.
- Inhalte stehen nie lose auf dem Hintergrund, sondern fast immer in einer `card`.

## Dateien

Fuer neue Seiten sollen immer diese Dateien genutzt werden:

- Bootstrap CSS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css`
- Eigenes CSS: [assets/css/app.css](/var/www/html/assets/css/app.css)
- Optional Bootstrap JS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js`
- Optional eigenes JS: [assets/js/login.js](/var/www/html/assets/js/login.js) oder spaeter weitere JS-Dateien

## Layout-Stil

### Seitenhintergrund

Der Body verwendet die Klasse `app-shell`.

Eigenschaften:

- volle Hoehe ueber die gesamte Viewport-Hoehe
- heller Verlauf als Grundflaeche
- zwei weiche farbige Radialverlaeufe fuer mehr Tiefe
- keine dunklen oder harten Flaechen

Wirkung:

- freundlich
- modern
- luftig

### Inhaltsbreite

Neue Seiten sollen immer in einem Bootstrap-`container` liegen.

Empfohlene Struktur:

```html
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                ...
            </div>
        </div>
    </main>
</body>
```

Fuer schmale Formulare:

- `col-12 col-md-8 col-lg-5`

Fuer normale Inhaltsseiten:

- `col-12 col-lg-8`

## Kartenstil

Fast alle Inhalte sollen innerhalb einer Karte angezeigt werden.

Verwendete Klassen:

- `card auth-card`
- `card dashboard-card`

Gemeinsame Eigenschaften:

- kein harter Rahmen
- grosse Rundungen
- weicher Schatten
- grosszuegiges Innenpadding

Die Karte ist der visuelle Hauptcontainer einer Seite.

## Typografie

Es wird die Standard-Typografie von Bootstrap genutzt.

Regeln:

- Haupttitel mit `h1` und Klasse `h2`
- kurze Einleitung darunter
- Nebentexte mit `muted-copy`
- kleine Labels fuer Metadaten mit `small text-body-secondary`

Beispiel:

```html
<h1 class="h2 mb-2">Dashboard</h1>
<p class="muted-copy mb-0">Diese Seite ist nur nach erfolgreichem Login erreichbar.</p>
```

## Brand-Badge

Oben in der Karte steht eine kleine Kennzeichnung mit der Klasse `brand-badge`.

Beispiele:

- `Login-Modul`
- `Geschuetzter Bereich`
- `Profil`
- `Einstellungen`

Die Badge dient als kleine visuelle Ueberschrift vor dem eigentlichen Seitentitel.

## Farben

Das Design nutzt hauptsaechlich Bootstrap-Farben und nur wenig eigene Akzentgestaltung.

Primäre Richtung:

- Blau als Hauptfarbe
- Gruen nur als zweiter Hintergrundakzent
- neutrales Hellgrau fuer Flaechen und ruhige Texte

Wichtige Rollen:

- Primar-Buttons: `btn btn-primary`
- Sekundaere Aktionen: `btn btn-outline-secondary`
- Gefaehrliche Aktionen: `btn btn-outline-danger`
- Hinweise: Bootstrap `alert-success`, `alert-danger`, `alert-warning`

## Formulare

Formulare sollen immer im Bootstrap-Stil gebaut werden.

Regeln:

- Inputs mit `form-control`
- wichtige Inputs gross mit `form-control-lg`
- Labels immer mit `form-label`
- vertikale Abstaende ueber `vstack gap-3` oder Bootstrap-Margins
- Checkboxen mit `form-check`
- Hauptbutton immer volle Breite bei Login-Formularen

Beispiel:

```html
<form class="vstack gap-3">
    <div>
        <label for="email" class="form-label">E-Mail</label>
        <input type="email" id="email" class="form-control form-control-lg">
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100">Speichern</button>
</form>
```

## Buttons

Buttons sollen klar nach Bedeutung getrennt werden:

- Hauptaktion: `btn btn-primary`
- Abbrechen oder neutrale Aktion: `btn btn-outline-secondary`
- Logout oder Loeschen: `btn btn-outline-danger`

Keine eigenen wilden Button-Farben einfuehren, solange es keinen klaren Grund dafuer gibt.

## Inhaltsboxen und Listen

Fuer Daten oder Profilinformationen sollen innerhalb der Karte weitere strukturierte Bereiche genutzt werden.

Aktuell wird dafuer `list-group` verwendet.

Empfehlung:

- einzelne Datenpunkte in `list-group-item`
- jede Box mit etwas Padding
- Label klein und grau
- Wert etwas staerker gewichtet

Beispiel:

```html
<div class="list-group">
    <div class="list-group-item p-3">
        <div class="small text-body-secondary mb-1">E-Mail</div>
        <div class="fw-semibold">demo@example.com</div>
    </div>
</div>
```

## Responsives Verhalten

Das Design soll auf Mobilgeraeten und Desktop gleich sauber wirken.

Regeln:

- Inhalte immer im Grid zentrieren
- auf Mobile volle Breite
- auf Desktop begrenzte Spaltenbreite
- Kartenpadding wird auf kleinen Geraeten automatisch reduziert

Das ist bereits in [assets/css/app.css](/var/www/html/assets/css/app.css) vorgesehen.

## Verhalten von JavaScript

JavaScript bleibt minimal.

Es soll nur fuer kleine UX-Verbesserungen genutzt werden, zum Beispiel:

- Submit-Button beim Absenden deaktivieren
- Text eines Buttons waehrend eines Requests aendern
- spaeter einfache Toasts oder Modals mit Bootstrap

Kein unnötig komplexes Frontend-Framework verwenden.

## Standardstruktur fuer neue Seiten

Diese Struktur soll fuer neue Seiten als Vorlage dienen:

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/auth/auth.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Seite</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('css/app.css')) ?>">
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="brand-badge mb-3">Bereich</div>
                        <h1 class="h2 mb-2">Seitentitel</h1>
                        <p class="muted-copy mb-4">Kurze Beschreibung der Seite.</p>

                        <div class="list-group">
                            <div class="list-group-item p-3">
                                <div class="small text-body-secondary mb-1">Beispiel</div>
                                <div class="fw-semibold">Inhalt</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

## Kurzregel fuer neue Seiten

Wenn du neue Seiten baust, halte dich an diese 7 Punkte:

1. Immer Bootstrap 5 + `assets/css/app.css` einbinden.
2. Immer `body class="app-shell"` verwenden.
3. Inhalte in einem `container` und zentrierten `row` aufbauen.
4. Den Hauptinhalt immer in einer `card` darstellen.
5. Oben eine `brand-badge`, dann Titel und kurzen Beschreibungstext platzieren.
6. Formulare und Buttons nur mit Bootstrap-Klassen gestalten.
7. Eigenes CSS nur dann erweitern, wenn es wirklich dem gesamten Projekt hilft.

