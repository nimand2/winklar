# Winklar Schiessanlass Webapplikation

Webapplikation zur Verwaltung von Schiessanlaessen mit Anlaessen, Schuetzen, Standblaettern, Kasse, Ranglisten und SIUS-Anbindung.

Das Projekt besteht aus einer PHP-Webapplikation, einer MySQL/MariaDB-Datenbank und dem separaten Python-Windows-Client [SiusClientV2](https://github.com/nimand2/SiusClientV2) fuer den Datenaustausch mit SIUS. Der Client wird in seinem eigenen Repository gepflegt.

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
  Core/            Router, Response, View, Database und Session
  Models/          Datenbankzugriff
  Services/        Geschaeftslogik
  Validation/      Formularpruefung und Normalisierung
  Views/           PHP-Views nach Fachbereich
config/            App- und Datenbankkonfiguration
routes/            Web- und API-Routen
database/
  schema.sql       Datenbankschema
  migrations/      Schemaaenderungen
  seeds/           Beispieldaten
  reference/       PLZ-Rohdaten, nicht im Updatepaket
client/            Schnittstellendokumentation fuer den externen SiusClientV2
public/            Einstiegspunkt, CSS und JavaScript
tests/
  unit/            Geschaeftslogik und Sicherheit
  integration/     Echte Datenbanktests
  views/           Navigation und Formulare
scripts/           Testausfuehrung und Updatepakete
docs/              Architektur, Entwicklung, Design und Deployment
  reference/       Externe SIUS-Referenzen, nicht im Updatepaket
storage/backups/   Lokale Datenbankbackups, nicht versioniert
config.php         Kompatibler Einstieg zur neuen Konfiguration
```

## Voraussetzungen

- PHP 8.1 oder neuer mit PDO-MySQL und mbstring
- MySQL oder MariaDB
- Webserver mit Document Root auf `public/`
- Fuer SiusClientV2: Windows; Einrichtung und Python-/EXE-Start sind im [Client-Repository](https://github.com/nimand2/SiusClientV2) beschrieben

## Installation

1. Repository klonen.
2. Datenbank erstellen:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

3. Optional Beispieldaten importieren:

   ```bash
   mysql -u root -p winklar < database/seeds/beispieldaten.sql
   ```

4. Datenbankzugang über `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` und
   `DB_CHARSET` in der Serverumgebung setzen. Alternativ
   `config/database.local.example.php` nach `config/database.local.php`
   kopieren und dort die Zugangsdaten eintragen. Umgebungsvariablen haben Vorrang.
   Die lokale Datei wird von Git und Updatepaketen ausgeschlossen.
   Allgemeine Einstellungen stehen in `config/app.php`.
   `API_TOKEN_SECRET` als zufälliges Geheimnis in der Serverumgebung setzen.

5. Webserver so konfigurieren, dass `public/` der Document Root ist.

## Lokale Entwicklung

Mit dem eingebauten PHP-Server kann die Anwendung lokal gestartet werden:

```bash
APP_ENV=development php -S localhost:8000 -t public public/index.php
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

SiusClientV2 kommuniziert ueber JSON mit der Webapplikation. Die wichtigsten Endpunkte sind:

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

- [`docs/deployment.md`](docs/deployment.md) - Einrichtung und Updatepakete
- [`DESIGN_GUIDE.md`](docs/design/DESIGN_GUIDE.md) - Designgrundlagen
- [`database/schema.sql`](database/schema.sql) - Datenbankschema
- [`client/doku_schnitstelle.md`](client/doku_schnitstelle.md) - SIUS-Client API

## Sicherheit

`config/database.local.php` enthält bei lokaler Konfiguration Zugangsdaten und ist vom Versionsverlauf ausgeschlossen. In einer oeffentlichen GitHub-Ablage sollten echte Passwoerter, produktive Datenbankzugriffe und andere Secrets nicht versioniert werden.


### API-Geheimnis und Sicherheit

`API_TOKEN_SECRET` muss im Serverprozess als unabhängiges, zufälliges Geheimnis
mit mindestens 32 Zeichen gesetzt sein. Zum Erzeugen eignet sich
`openssl rand -hex 32`. Den Wert sicher in der Serverkonfiguration hinterlegen,
nicht im Repository. Ohne gültige Konfiguration schlägt die Token-Erstellung
und Signaturprüfung ausdrücklich fehl. Nach dem Wechsel des Geheimnisses müssen
sich SiusClientV2/API-Clients neu anmelden. Endpunkte und JSON-Formate bleiben gleich.

Webformulare validieren einen Session-CSRF-Token; Logout erfolgt per POST.
Die mit Bearer-Tokens authentifizierte API benötigt keinen Formular-CSRF-Token.
Secure-Cookies sind im Produktivbetrieb standardmässig aktiviert; lokales HTTP
verwendet `APP_ENV=development`. `APP_BASE_PATH=/winklar` konfiguriert einen
Unterpfad. Web- und API-Login begrenzen Versuche gemeinsam pro IP und Login.
`LOGIN_LIMIT_DIR` kann dafür ein privates, für PHP schreibbares Verzeichnis setzen.

Die Datenbanktests legen alle Anwendungstabellen als verbindungslokale temporäre
Tabellen an. Sie benötigen eine erreichbare MySQL/MariaDB-Datenbank und das Recht,
temporäre Tabellen anzulegen; vorhandene Anwendungsdaten bleiben unverändert.

Bestehende Installationen benötigen die additive Review-Migration:
`php scripts/migrate-review.php` prüft vorab, `php scripts/migrate-review.php --apply`
erstellt zuerst ein privates Backup und migriert anschliessend. Details und
Herkunftskennzeichnung stehen in [docs/deployment.md](docs/deployment.md).

## Tests und Updatepakete

`php scripts/test.php` führt Unit- und View-Tests aus.
`php scripts/test.php integration` startet zusätzlich separat die Datenbanktests.
`php scripts/build-update.php` erstellt `build/Winklar-Update.zip` aus aktuellen
Quellen. Details stehen in [docs/deployment.md](docs/deployment.md).

## Dokumentation und Referenzmaterial

- [Architekturmodell](docs/architecture/ARCHITEKTURMODELL.md)
- [Klassendiagramm](docs/architecture/KLASSENDIAGRAMM.md)
- [Entwicklungsübersicht](docs/development/CHEATSHEET.md)
- [Designgrundlagen](docs/design/DESIGN_GUIDE.md)
- [PLZ-Referenzdaten](database/reference/README.md)
- [SIUS-Referenzmaterial](docs/reference/README.md)

Das bestehende SQL-Backup liegt lokal unter `storage/backups/winklar.sql`.
Es wird nicht versioniert oder in Updatepakete aufgenommen.
