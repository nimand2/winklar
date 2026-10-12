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
Die Ordnerumstellung selbst ändert keine Sussiport/API-Endpunkte oder Datenformate.

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
anwenden. Die Strukturänderung benötigt selbst keine Datenbankmigration.

Neue Dateien zusammen ausrollen: `public/index.php`, `routes/`, `config/`,
`app/Validation/` und umbenannte Views gehören zur selben Version.
Bei einer vorbereiteten Neuinstallation bleiben keine alten View- oder Servicekopien zurück.
