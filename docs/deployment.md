# Einrichtung und Updates

Der Webserver verwendet `public/` als Document Root. Bei bestehenden Installationen
mit Projektroot unterstützt die angepasste `.htaccess` weiterhin das Routing und
sperrt den Zugriff auf interne Verzeichnisse, einschließlich `config/` und `database/`.

## Konfiguration

Datenbankwerte kommen aus `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` und `DB_CHARSET`.
Alternativ kann `config/database.local.example.php` nach
`config/database.local.php` kopiert und lokal ausgefüllt werden.
Umgebungsvariablen haben Vorrang; die lokale Datei wird weder versioniert noch verpackt.
Die bisherige lokale Datenbankkonfiguration wurde bei der Umstellung übernommen.
Der PHP/Webserver-Benutzer muss die lokale Datei lesen können. Für Apache unter
Ubuntu mit Gruppe `www-data` eignen sich:

```bash
chgrp www-data config/database.local.php
chmod 640 config/database.local.php
```

Die Datei gehört damit weiterhin dem administrativen Benutzer; die Webservergruppe
bekommt nur Leserechte. `600` funktioniert nur, wenn PHP als Dateieigentümer läuft.
Nach Konfigurationsänderungen auch `/login` über den tatsächlichen Webserver prüfen.
`config.php` bleibt als kompatibler Ladepunkt bestehen.

Allgemeine Einstellungen stehen unter `config/app.php`. `API_TOKEN_SECRET` muss
in der Serverumgebung gesetzt sein; `openssl rand -hex 32` erzeugt einen geeigneten
Wert. Ein Wechsel dieses Geheimnisses erfordert eine erneute Anmeldung der API-Clients.
Die Ordnerumstellung selbst ändert keine SiusClientV2/API-Endpunkte oder Datenformate.

## Updatepaket erstellen

```bash
php scripts/test.php
php scripts/test.php integration
php scripts/build-update.php
```

Das letzte Kommando erzeugt `build/Winklar-Update.zip`. Ein abweichender Zielpfad
kann als Argument übergeben werden. Bestehende ZIP-Dateien werden nicht überschrieben.
Das Paket enthält die aktuellen Anwendungsdateien, Routen, Konfigurationsvorlagen,
Datenbankschema/Migrationen, Tests, Skripte und Dokumentation.
Lokale Zugangsdaten, Datenbankbackups, PLZ-Rohdaten, externe SIUS-Referenzen
und frühere Updatepakete sind ausgeschlossen.
Dateireihenfolge und ZIP-Zeitstempel sind fest, damit gleiche Quellen mit gleicher
ZIP-Implementierung reproduzierbare Pakete ergeben.

Das frühere Verzeichnis `server-update/` wird nicht mehr als zweite Quelle gepflegt.

## Bestehende Installation aktualisieren

Dateien und Datenbank sichern. Das Paket in eine vorbereitete neue Installation
entpacken, lokale Konfiguration übernehmen und die PHP-Tests ausführen.
`config/database.local.php` muss außerhalb des öffentlichen Document Roots liegen.
Falls bisher Zugangsdaten direkt in `config.php` standen, vor dem Ersetzen in die
neue lokale Datei oder Serverumgebung übertragen.

Bei einem Wechsel im bestehenden Projektroot auch die aktuelle `.htaccess` aus
dem Repository übernehmen. Updatepakete enthalten diese Root-Datei ebenfalls.
Die bereits angewendeten Migrationen unter `database/migrations/` nicht erneut
anwenden. Für die Review-Korrekturen vom 12. Oktober 2026 ist die unten beschriebene
additive Migration erforderlich.

Neue Dateien zusammen ausrollen: `public/index.php`, `routes/`, `config/`,
`app/Validation/` und umbenannte Views gehören zur selben Version.
Bei einer vorbereiteten Neuinstallation bleiben keine alten View- oder Servicekopien zurück.


## Review-Migration vom 12. Oktober 2026

```bash
php scripts/migrate-review.php
php scripts/migrate-review.php --apply
```

Das erste Kommando prüft doppelte SIUS-Anzeige-IDs, negative Preise, Abgaben
mit fehlenden Stammdaten und Kosten ohne Stichzuordnung. Es ändert keine Daten.
`--apply` sichert zuerst Schema und Daten der konfigurierten Datenbank in einer
SQL-Datei unter `storage/backups/` (Dateirechte 600). Schlägt das Backup fehl,
startet die Migration nicht. Während der Migration dürfen keine anderen
Prozesse Anwendungsdaten ändern; Anmeldung und SIUS-Import vorübergehend pausieren.
MySQL-DDL kann nicht als Ganzes zurückgerollt werden. Die Migration ist
wiederholbar und speichert ihren Abschluss in `app_migrations`.

Gespeicherte Standblatt-Gesamtkosten werden centgenau proportional auf ihre
gelösten Stiche verteilt; ohne positive Preisgewichte erfolgt die Verteilung
nach Stückzahlen. Die so berechneten Preise tragen `preis_quelle=uebernommen`.
Bei bestehenden Gabenabgaben werden die beim Update vorhandenen Stammdaten als
Ausgangswerte gesichert und ebenfalls gekennzeichnet. Diese Werte rekonstruieren
keine unbekannten ursprünglichen Einzelpreise. Bereits bestätigte Abgaben
bleiben erhalten und erhalten einen Eintrag in der Abgabehistorie. Offene
Gabenansprüche werden neu berechnet; alle Standblätter benötigen eine erneute
Gabenprüfung. Rücknahmen werden künftig separat protokolliert.

Nach der SIUS-Spezifikation zählt `InsDel=1` als eingefügt, `2` als ignoriert.
Der Betreiber hat bestätigt, dass keine bestehenden Schüsse manuell gelöscht
wurden. Deshalb zählen auch alle bisherigen `1`-Datensätze wieder. Künftige
manuelle Löschungen verwenden ausschliesslich `manuell_geloescht`.

Historische Buchungen sind gegen Katalogpreisänderungen und Löschung ihrer
Gaben/Stiche geschützt. Nur vollständige gelöste Serien zählen für Ranglisten
und Gaben; der Final benötigt mindestens zwei solche Serien im Finalstich.

Für eine neue Datenbank ausschliesslich `database/schema.sql` verwenden. Das
Schema löscht keine bestehende Datenbank. Beispieldaten nur in eine frische
Entwicklungsdatenbank importieren; die Benutzer enthalten Platzhalter-Hashes,
keine verwendbaren Standardpasswörter. Ein Entwicklungsreset ist separat mit
`APP_ENV=development php scripts/reset-development.php --delete-development-database`
und löscht bewusst die konfigurierte Entwicklungsdatenbank.

## Webserver und zusätzliche HTTP-Prüfung

Produktivbetrieb benötigt HTTPS; Session- und Remember-Me-Cookies sind ohne
weitere Einstellung `Secure`. Für lokales HTTP `APP_ENV=development` setzen.
`APP_SECURE_COOKIES` kann die Einstellung ausdrücklich überschreiben.
`APP_BASE_PATH=/winklar` muss einem tatsächlichen Webserver-Unterpfad entsprechen.
`LOGIN_LIMIT_DIR` kann auf ein privates, PHP-schreibbares Verzeichnis zeigen;
der Standard liegt unter dem System-Tempverzeichnis und ist installationsspezifisch.
Mehrere Serverinstanzen benötigen eine gemeinsam durchgesetzte Loginbegrenzung.

Mit Apache 2, mod_php 8.4, mod_rewrite, Python 3 und einer erreichbaren
Test-Datenbank kann zusätzlich `python3 tests/http/apache.py` ausgeführt werden.
Der Test startet eine eigene Apache-Instanz auf einem freien lokalen Port;
alle Anwendungstabellen werden durch temporäre Tabellen überschattet. Er prüft
`public/`, Projektroot und Unterpfad, Assets, gesperrte Dateien, sichere Cookies,
CSRF, Formulare, Bearer-API und fehlerhafte Requests. Die vorhandene
Apache-Konfiguration wird dabei nicht verändert.

`php tests/http/migration-foreignkeys.php` prüft zusätzlich echte Fremdschlüssel
mit zwei zufällig benannten Testtabellen in der konfigurierten Datenbank und
entfernt sie anschliessend. Dafür benötigt der Datenbankbenutzer CREATE-/ALTER-/
DROP-TABLE-Rechte. Der Test verändert keine Anwendungstabellen und prüft auch
die Wiederaufnahme nach einem unterbrochenen Migrationsabschluss. Beim atomaren
Fremdschlüsselaustausch erhält ein gleichnamiger alter Fremdschlüssel einen
neuen Namen mit `_review`, um eine MariaDB-Namenskollision zu vermeiden.
