# Design Guide

Diese Datei beschreibt den aktuellen visuellen und strukturellen Stil der Webapplikation. Neue Seiten sollen sich daran orientieren, damit Login, Dashboard, Anlassverwaltung, Schützenverwaltung, Standblätter, Abrechnung und Ranglisten wie ein zusammenhängendes System wirken.

## Ziel des Designs

Die Anwendung ist eine Arbeitsoberfläche für einen Schiessanlass. Das Design soll deshalb ruhig, klar und effizient sein:

- schnelle Orientierung für Personen im Büro oder an der Kasse
- gute Lesbarkeit von Namen, Startnummern, Preisen, Daten und Resultaten
- klare Primäraktionen wie "Neuer Anlass", "Neuer Schütz", "Abrechnen" oder "Drucken"
- einheitliche Seitenstruktur mit Bootstrap 5
- möglichst wenig eigenes CSS, aber genug Projektcharakter

Die Webapp ist keine Marketing-Seite. Neue Ansichten sollen direkt die eigentliche Arbeit ermöglichen.

## Technische Basis

Für neue Seiten immer diese Grundlagen verwenden:

- Bootstrap CSS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css`
- Projekt-CSS: [public/assets/css/app.css](/var/www/html/public/assets/css/app.css), im Browser unter `/assets/css/app.css`
- Bootstrap JS: `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js`
- Gemeinsamer Head: [app/Views/partials/head.php](/var/www/html/app/Views/partials/head.php)
- Gemeinsames Bootstrap-Script: [app/Views/partials/bootstrap-script.php](/var/www/html/app/Views/partials/bootstrap-script.php)

Neue PHP-Views sollen den vorhandenen MVC-Stil nutzen und Links über `App\Core\Url::app()` bzw. Assets über `App\Core\Url::asset()` erzeugen.

## Grundlayout

Jede normale Seite nutzt:

- `body class="app-shell"`
- `main class="container py-5"`
- Bootstrap Grid mit `row justify-content-center`
- eine klare maximale Inhaltsbreite

Empfohlene Spalten:

- Login und schmale Formulare: `col-12 col-md-8 col-lg-5`
- Standardseiten: `col-12 col-lg-8`
- Übersichten mit mehreren Spalten oder vielen Aktionen: `col-12 col-xl-10`

Beispiel:

```php
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Seitentitel']); ?>
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card dashboard-card">
                    <div class="card-body">
                        ...
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
```

## Seitenaufbau

Eine Seite besteht typischerweise aus:

1. Kopfbereich mit `brand-badge`, Titel und kurzem Beschreibungstext
2. Aktionsbereich mit den wichtigsten Buttons
3. Inhalt: Tabelle, Liste, Formular, Detaildaten oder Druckansicht
4. Statusmeldungen mit Bootstrap Alerts

Der Kopfbereich soll auf Desktop horizontal funktionieren und auf Mobile sauber untereinander umbrechen:

```html
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
    <div>
        <div class="brand-badge mb-3">Anlass</div>
        <h1 class="h2 mb-2">Anlasstitel</h1>
        <p class="muted-copy mb-0">Kurze Einordnung der Ansicht.</p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="#" class="btn btn-primary">Primäraktion</a>
        <a href="#" class="btn btn-outline-secondary">Zurück</a>
    </div>
</div>
```

## Karten und Flächen

Der Hauptinhalt liegt aktuell in einer Bootstrap-Karte:

- `card auth-card` für Login
- `card dashboard-card` für geschützte Arbeitsseiten

Eigenschaften:

- kein harter Rahmen
- weicher Schatten
- grosszügiges Innenpadding
- helle, ruhige Fläche auf dem Hintergrund

Innerhalb einer Hauptkarte sollen wiederholte Inhalte als Listen, Tabellen oder einzelne `list-group-item`-Elemente dargestellt werden. Keine verschachtelten dekorativen Karten bauen, wenn eine Liste oder ein Grid reicht.

## Typografie

Die Anwendung nutzt die Standard-Typografie von Bootstrap.

Regeln:

- Seitentitel: `h1` mit Klasse `h2`
- Beschreibungstext: `p.muted-copy`
- Metadatenlabels: `small text-body-secondary`
- Werte: `fw-semibold`
- Keine sehr großen Hero-Schriften in Arbeitsansichten
- Texte kurz halten, besonders in Buttons und Badges

Beispiel:

```html
<div class="brand-badge mb-3">Kasse</div>
<h1 class="h2 mb-2">Kassen-Abrechnung</h1>
<p class="muted-copy mb-0">Übersicht der offenen und bezahlten Standblätter.</p>
```

## Farben

Die Farbwelt bleibt bewusst zurückhaltend:

- Anthrazit für Primäraktionen und aktive Elemente
- Signalblau sparsam für Fokus, Badges, Hover-Zustände und Hintergrundakzente
- Grün nur für positive Zustände wie Erfolgsmeldungen oder erreichte Gaben
- Rot nur für Logout, Löschen oder gefährliche Aktionen
- Grau für Metadaten, Hinweise und neutrale Navigation
- Weiss für Arbeitsflächen

Projektpalette:

- Primärfarbe Anthrazit: `#334155`
- Primärfarbe dunkel: `#1f2937`
- Akzentfarbe Signalblau: `#0ea5e9`
- Hintergrund hell: `#f8fafc`
- Hintergrund Verlauf: `#eef2f7`
- Metadaten/Hilfstext: `#64748b`

Buttons:

- Hauptaktion: `btn btn-primary`
- Zweite wichtige Aktion: `btn btn-outline-primary`
- Neutrale Navigation: `btn btn-outline-secondary`
- Logout/Löschen/Abbruch mit Risiko: `btn btn-outline-danger`

Alerts:

- Erfolg: `alert alert-success`
- Fehler: `alert alert-danger`
- Warnung: `alert alert-warning`
- Leerer Zustand: `alert alert-light border`

## Formulare

Formulare folgen Bootstrap 5.

Regeln:

- Inputs: `form-control`
- große Login-Inputs: `form-control form-control-lg`
- Labels: `form-label`
- Gruppenabstand: `vstack gap-3` oder Bootstrap Margins
- Checkboxen: `form-check`
- Hauptbutton bei Login und schmalen Formularen: `w-100`
- Pflichtfelder im Label oder Hilfetext klar machen
- Fehlermeldungen über Bootstrap Alerts anzeigen

Beispiel:

```html
<form class="vstack gap-3" method="post">
    <div>
        <label for="name_anlass" class="form-label">Name des Anlasses</label>
        <input type="text" id="name_anlass" name="name_anlass" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Speichern</button>
</form>
```

## Listen, Tabellen und Übersichten

Für Datensätze wie Anlässe, Schützen, Standblätter oder Abrechnungspositionen:

- bei wenigen Einträgen: `list-group`
- bei vielen vergleichbaren Spalten: Bootstrap `table`
- bei Auswahlkarten: `anlass-card list-group-item`
- auf Mobile sollen Zeilen umbrechen dürfen
- IDs, Startnummern, Datum und Kosten gut sichtbar machen

Leere Zustände immer ausdrücklich anzeigen:

```html
<div class="alert alert-light border mb-0">
    Für diesen Anlass wurde noch kein Standblatt erstellt.
</div>
```

## Navigation und Aktionen

Aktionsbuttons sollen inhaltlich gruppiert werden:

- oben rechts: Navigation, Zurück, Dashboard, Logout
- unter dem Seitentitel: fachliche Aktionen der aktuellen Seite
- in Listenzeilen: Aktionen, die genau diesen Eintrag betreffen

Wichtige vorhandene Arbeitsbereiche:

- `/login`: Anmeldung
- `/dashboard`: Einstieg nach Login
- `/anlass`: Anlassauswahl
- `/anlass/neu`: neuen Anlass erstellen
- `/anlass/{id}`: Anlassdetails und Navigation
- `/anlass/{id}/konfiguration`: Stiche, Gaben und Regeln konfigurieren
- `/anlass/{id}/schuetzen`: Adress- und Schützenverwaltung
- `/anlass/{id}/loesen`: Standblatt auswählen
- `/anlass/{id}/loesen/neu`: neues Standblatt lösen
- `/anlass/{id}/loesen/{standblattId}/abrechnen`: Standblatt abrechnen
- `/anlass/{id}/abschliessen`: Rangliste anzeigen
- `/anlass/{id}/kasse`: Kassenübersicht

## Authentifizierung und geschützte Seiten

Geschuetzte Seiten müssen über den Controller die Authentifizierung erzwingen. In Views soll keine eigene Loginlogik entstehen.

UI-Regeln:

- Login-Seite schlicht und fokussiert
- Nach Login immer klare Rücknavigation anbieten
- Logout als `btn btn-outline-danger`
- Fehlermeldungen nicht technisch formulieren, sondern handlungsorientiert

## Druckansichten

Für Standblätter und Abrechnungen gibt es Druckansichten. Diese sollen:

- reduzierter gestaltet sein als Arbeitsseiten
- keine unnötigen Navigationselemente enthalten
- klare Tabellen und Summen zeigen
- auf A5 gut lesbar sein
- Bootstrap nur verwenden, wenn es den Druck nicht stört

## JavaScript

JavaScript bleibt minimal und unterstützt die Bedienung:

- Submit-Button beim Absenden deaktivieren
- Buttontext während Requests anpassen
- Bootstrap Modals oder Toasts nur bei echtem Nutzen
- keine komplexe Frontend-Architektur einführen, solange serverseitige PHP-Views reichen

Vorhandenes Beispiel:

- [public/assets/js/login.js](/var/www/html/public/assets/js/login.js)

## Responsives Verhalten

Alle Seiten müssen auf Mobile und Desktop nutzbar sein.

Regeln:

- Buttons dürfen umbrechen: `d-flex flex-wrap gap-2`
- Tabellen bei Bedarf mit `table-responsive` umschließen
- Formulare auf Mobile volle Breite
- Keine festen Breiten für Textbereiche
- Lange Namen, Vereine oder E-Mail-Adressen dürfen das Layout nicht sprengen

## Sprache und Begriffe

Die Anwendung verwendet deutschsprachige Fachbegriffe aus dem Schiessanlass:

- Anlass
- Schütz / Schützin oder Schützen
- Standblatt
- Stich
- Gabe
- Auszeichnungslimite
- Rangliste
- Kasse / Abrechnung
- Schussdaten

Wichtig: Sichtbare deutsche Texte sollen Umlaute verwenden. Technische Namen wie Routen, Feldnamen und Variablen bleiben stabil, auch wenn sie ASCII-Schreibweisen wie `schuetzen`, `loesen` oder `Anlaesse` enthalten.

## Standardstruktur für neue Arbeitsseiten

```php
<?php
declare(strict_types=1);

use App\Core\Url;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <?php \App\Core\View::partial('partials/head', ['pageTitle' => 'Neue Seite']); ?>
</head>
<body class="app-shell">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
                            <div>
                                <div class="brand-badge mb-3">Bereich</div>
                                <h1 class="h2 mb-2">Seitentitel</h1>
                                <p class="muted-copy mb-0">Kurze Beschreibung der Seite.</p>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= htmlspecialchars(Url::app('/dashboard')) ?>" class="btn btn-outline-secondary">
                                    Dashboard
                                </a>
                            </div>
                        </div>

                        <div class="list-group">
                            <div class="list-group-item p-3">
                                <div class="small text-body-secondary mb-1">Label</div>
                                <div class="fw-semibold">Wert</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php \App\Core\View::partial('partials/bootstrap-script'); ?>
</body>
</html>
```

## Kurzregel für neue Seiten

1. Gemeinsamen Head-Partial und Bootstrap verwenden.
2. `body class="app-shell"` setzen.
3. Inhalt in `container`, `row` und passende Bootstrap-Spalte legen.
4. Arbeitsinhalt in `card dashboard-card` oder Login in `card auth-card` darstellen.
5. Kopfbereich immer mit `brand-badge`, `h1.h2`, `muted-copy` und Aktionsgruppe aufbauen.
6. Formulare, Tabellen, Alerts und Buttons mit Bootstrap-Klassen lösen.
7. Eigenes CSS nur erweitern, wenn es mehreren Seiten hilft.
8. Mobile Umbrüche und lange Fachwerte immer mitdenken.
