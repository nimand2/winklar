# Winklar Schiessanlass Webapplikation

Webapplikation zur Verwaltung von Schiessanlaessen mit Anlaessen, Schuetzen, Standblaettern, Kasse, Ranglisten und SIUS-Anbindung.

Das Projekt besteht aus einer PHP-Webapplikation, einer MySQL/MariaDB-Datenbank und einem separaten C#-Windows-Client fuer den Datenaustausch mit SIUS.

## Funktionen

- Benutzer-Login fuer den geschuetzten Bereich
- Anlaesse erstellen, bearbeiten und konfigurieren
- Schuetzen und Adressen verwalten
- Standblaetter loesen, bearbeiten, abrechnen und drucken
- Stiche, Gaben und Auszeichnungslimiten konfigurieren
- Kassenuebersicht und Ranglisten
- REST-API fuer den SIUS-Client
- Import von SIUS-Schussdaten

## Projektstruktur

```text
app/
  Controllers/     HTTP-Controller fuer Weboberflaeche und API
  Core/            Router, Response, View, Database und Basisklassen
  Models/          Datenbankzugriff
  Services/        Geschaeftslogik
  Views/           PHP-Views
client/
  Form1.cs         C# Windows Forms SIUS-Client
public/
  index.php        Einstiegspunkt der Webapplikation
  assets/          CSS und JavaScript
schema.sql         Datenbankschema
beispieldaten.sql  Beispieldaten
config.php         Lokale Konfiguration
```

## Voraussetzungen

- PHP 8.x mit PDO-MySQL
- MySQL oder MariaDB
- Webserver mit Document Root auf `public/`
- Fuer den SIUS-Client: Windows mit .NET/Windows Forms Entwicklungsumgebung

## Installation

1. Repository klonen.
2. Datenbank erstellen:

   ```bash
   mysql -u root -p < schema.sql
   ```

3. Optional Beispieldaten importieren:

   ```bash
   mysql -u root -p winklar < beispieldaten.sql
   ```

4. `config.php` an die lokale Umgebung anpassen:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'winklar');
   define('DB_USER', '...');
   define('DB_PASS', '...');
   ```

5. Webserver so konfigurieren, dass `public/` der Document Root ist.

## Lokale Entwicklung

Mit dem eingebauten PHP-Server kann die Anwendung lokal gestartet werden:

```bash
php -S localhost:8000 -t public
```

Danach ist die Anwendung unter folgender Adresse erreichbar:

```text
http://localhost:8000
```

## Wichtige Routen

- `/login` - Login
- `/dashboard` - Dashboard
- `/anlass` - Anlassuebersicht
- `/anlass/neu` - neuen Anlass erstellen
- `/anlass/{id}` - Anlassdetail
- `/anlass/{id}/konfiguration` - Anlass konfigurieren
- `/anlass/{id}/schuetzen` - Schuetzenverwaltung
- `/anlass/{id}/loesen` - Standblatt auswaehlen
- `/anlass/{id}/loesen/neu` - Standblatt loesen
- `/anlass/{id}/kasse` - Kassenuebersicht
- `/anlass/{id}/abschliessen` - Rangliste

## SIUS-API

Der C#-Client kommuniziert ueber JSON mit der Webapplikation. Die wichtigsten Endpunkte sind:

- `POST /api/login`
- `GET /api/anlaesse`
- `GET /api/anlaesse/{id}/shooters`
- `GET /api/anlaesse/{id}/shooters/new?sinceId=...`
- `POST /api/anlaesse/{id}/shots/import`

Geschuetzte Endpunkte verwenden einen Bearer Token:

```http
Authorization: Bearer <token>
Accept: application/json
```

Die vollstaendige Schnittstellendokumentation steht in [`client/doku_schnitstelle.md`](client/doku_schnitstelle.md).

## Weitere Dokumentation

- [`zusammenfassung.md`](zusammenfassung.md) - fachliche und technische Zusammenfassung
- [`DESIGN_GUIDE.md`](DESIGN_GUIDE.md) - Designgrundlagen
- [`schema.sql`](schema.sql) - Datenbankschema
- [`client/doku_schnitstelle.md`](client/doku_schnitstelle.md) - SIUS-Client API

## Sicherheit

`config.php` enthaelt lokale Zugangsdaten und sollte fuer produktive Installationen passend abgesichert werden. In einer oeffentlichen GitHub-Ablage sollten echte Passwoerter, produktive Datenbankzugriffe und andere Secrets nicht versioniert werden.

