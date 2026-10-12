# Codeprüfung vom 12. Oktober 2026

## Stand nach der Behebung

Alle 18 Befunde und die fünf zusätzlichen Code-/Dokumentationshinweise sind
umgesetzt. Die nachfolgenden Befundbeschreibungen dokumentieren den
**Ausgangsstand**; ihre Zeilenangaben sind historische Fundstellen.

| Befund | Umsetzung |
| --- | --- |
| 1 | Installationsschema ohne Datenbanklöschung; ausdrücklich geschützter separater Entwicklungsreset. |
| 2 | Name, Preis, Geld-/Sachgabenart und Regelwerte werden bei einer Abgabe eingefroren. |
| 3 | Verwendete Gaben und Stiche sind durch Modellprüfung und RESTRICT-Fremdschlüssel geschützt; Abgaben und Rücknahmen erhalten eine dauerhafte Historie. |
| 4 | Gemeinsamer Serienservice für Rangliste und Abrechnung; nur vollständige gelöste Serien zählen. Final benötigt mindestens zwei. |
| 5 | Altersgrenzen der Gabenregeln werden anhand des Jahrgangs im Anlassjahr angewendet. |
| 6 | Überlappende Regeln ergeben je Gabe und Serie höchstens einen Anspruch. |
| 7 | Neue Importe, Mengen-/Regel-/Resultatänderungen setzen die Gabenprüfung innerhalb derselben Transaktion zurück; Wiederholungsimporte bleiben ohne Nebenwirkung. |
| 8 | Schussformulare erhalten und validieren Sekunden. |
| 9 | Offene Ansprüche und bestätigte Abgaben/Barauszahlungen werden getrennt ausgewiesen und berechnet. |
| 10 | Einnahmen verwenden gespeicherte Buchungspreise und centgenaue Positionskosten. |
| 11 | Serverseitige Prüfungen für Typ, Datum, Länge, Zahlenformat, Wertebereich und Mengen; Formularfehler behalten gültige Eingaben. |
| 12 | SIUS-Anzeige-IDs sind je Anlass eindeutig; Migration prüft Mehrdeutigkeiten vorab. |
| 13 | Beispieldaten passen zum Schema, zu gelösten Serien und zu Preis-/Abgabensnapshots. |
| 14 | Basispfad wird nur bei eingehenden Requests und an einer Pfadgrenze entfernt. |
| 15 | Apache-Assets und Routing funktionieren im Projektroot, unter public/ und mit Unterpfad. |
| 16 | Regellöschung prüft den zugehörigen Stich und Anlass; fremder Kontext ergibt 404. |
| 17 | JSON-Objekt, Pflichtfelder, Startnummernzuordnung, widersprüchliche Felder und ganze Importblöcke werden geprüft; fehlerhafte Blöcke bleiben ohne Schreibwirkung. |
| 18 | SIUS 0/1 zählen, 2 wird ignoriert; lokales Löschen verwendet ein separates Feld und bleibt bei Wiederholungsimporten erhalten. |

Zusätzlich: sichere Cookies als Produktivstandard, gemeinsame Web-/API-
Loginbegrenzung mit 429, dezimales `TimeSinceChange`, korrigierte exakte
Ortsnamensuche und richtige Dokumentation des Final-Nachrückens.

Fachliche Entscheidungen des Betreibers:

- Rangliste/Gaben: vollständige gelöste Serien; Final: mindestens zwei.
- Historische Gesamtkosten proportional und centgenau auf Stiche verteilen;
  bestehende Gabenpreise als Ausgangswerte sichern und Herkunft kennzeichnen.
- Es gab keine manuellen Schusslöschungen. Alle bestehenden `InsDel=1` zählen
  wieder. [SiusData.pdf, Seite 39](../reference/SiusData.pdf) bestätigt 1 als
  eingefügt und 2 als ignoriert.

Die bestehende Datenbank wurde am 12. Oktober 2026 migriert. Die vollständige
Sicherung vor dem ersten Schritt liegt privat unter
`storage/backups/review-20261012-202926-22c5ba.sql`; beim wiederaufgenommenen
Abschluss wurde eine weitere Sicherung erstellt. Eine MariaDB-Namenskollision
beim atomaren Fremdschlüsselaustausch wurde behoben und mit echten, separaten
Testtabellen geprüft. Die Abschlussprüfung bestätigte unveränderte Anzahlen,
Gesamtkosten und rohe Schussdaten, erhaltene bestätigte Abgaben, centgenaue
Kostenaufteilung und aktive Löschsperren. 19 vorhandene 1-Einträge zählen wieder.
Standblätter benötigen nach dem Update eine erneute Gabenprüfung.

Prüfungen nach der Behebung:

- `php scripts/test.php`: acht Unit-Dateien und zwei View-Dateien bestehen.
- `php scripts/test.php integration`: drei Datenbanktestdateien bestehen;
  sämtliche Anwendungstabellen werden temporär überschattet.
- `python3 tests/http/apache.py`: HTTP-Prüfung von drei Document-Root-/Pfad-
  Varianten, Assets, Dateisperren, Secure-Cookies, CSRF, Login, authentifizierten
  Seiten, Formularfehlern, manuellen Korrekturen, Bearer-API und Loginbegrenzung.
- `php tests/http/migration-foreignkeys.php`: echte Fremdschlüsselsperren und
  MariaDB-Austausch in zwei zufällig benannten, anschliessend entfernten
  Testtabellen; keine Änderung von Anwendungstabellen.
- PHP-Syntaxprüfung, Apache-Konfigurationsprüfung und Updatepaketprüfung.

Der reale Windows-/SIUS-Anlagenbetrieb und Browserdruck bleiben ausserhalb
dieser Umgebung. Die Änderungen betreffen den Server; der externe V2-Client
wird weiterhin in seinem eigenen Repository gepflegt.

## Ausgangsprüfung

Geprüfter Ausgangsstand: Git-Commit `c847e93`. Die Prüfung umfasst PHP-Kern,
Controller, Modelle, Services, Validierung, Views, JavaScript/CSS, Routen,
Datenbankschema, Migrationen, Beispieldaten, Buildskripte und vorhandene Tests.
Als Client wird laut Nutzer jetzt [SiusClientV2](https://github.com/nimand2/SiusClientV2)
verwendet. Der ergänzende Abgleich bezieht sich auf Commit
[`c613ed2`](https://github.com/nimand2/SiusClientV2/tree/c613ed20d863085a2bf458ec5bc3a9a2e3b6b899).
Die fünf ursprünglich dokumentierten Befunde C1–C5 betrafen ausschließlich den
ausgemusterten C#-Client und zählen nicht zu den aktuellen offenen Befunden.

Bei der ursprünglichen Prüfung wurden Anwendungscode und persistente Anwendungsdaten nicht geändert. Die
zusätzlichen Datenbankprüfungen verwendeten ausschließlich verbindungslokale
temporäre Tabellen. Der hierfür vorübergehend angelegte Test wurde entfernt.

Prioritäten: **P1** = hohes Risiko für Datenverlust oder falsche Ergebnisse;
**P2** = funktionaler Fehler unter einem konkreten Auslöser. Dynamische
Reproduktionen sind ausdrücklich gekennzeichnet; Konfigurations- und
Clientbefunde beruhen teilweise auf Quellcodeprüfung.

## Befunde im geprüften Ausgangsstand (behoben)

### 1. P1 — Installationsschema löscht eine bestehende Datenbank

Fundstelle: [database/schema.sql:1](/var/www/html/database/schema.sql:1).

Die erste Anweisung ist `DROP DATABASE IF EXISTS winklar`. Der in der README
empfohlene Schemaimport entfernt somit eine bereits vorhandene Datenbank samt
allen Daten, bevor das Schema neu angelegt wird. Das betrifft auch einen
versehentlich wiederholten Installationslauf. Die Löschung wurde nicht ausgeführt.

Korrektur: das Installationsschema ohne `DROP DATABASE` ausliefern und einen
bewusst destruktiven Entwicklungsreset getrennt bereitstellen.

### 2. P1 — Preisänderungen verändern bereits ausgegebene Gaben rückwirkend

Fundstellen: [Gaben.php:124](/var/www/html/app/Models/Gaben.php:124),
[Gaben.php:141](/var/www/html/app/Models/Gaben.php:141).

Gespeicherte Abgaben enthalten keinen Preis zum Ausgabezeitpunkt. Beide
Leseabfragen holen den aktuellen Preis aus dem globalen Gabenstamm. Wird eine
Gabe später geändert, ändern sich bereits bestätigte Abgaben und frühere
Kassenabrechnungen sämtlicher betroffener Anlässe.

**Dynamisch reproduziert:** eine bestätigte Abgabe wurde zunächst mit CHF 10.00
gelesen. Nach einer Preisänderung im temporären Gabenstamm lieferte dieselbe
gespeicherte Abgabe CHF 20.00.

Korrektur: Ausgabepreis und relevante Eigenschaften der Gabe in der Abgabe
speichern; historische Ausgaben anhand dieser gespeicherten Werte auswerten.

### 3. P1 — Löschen von Gaben oder Stichen entfernt Ausgabehistorie

Fundstellen: [AnlassController.php:335](/var/www/html/app/Controllers/AnlassController.php:335),
[database/schema.sql:233](/var/www/html/database/schema.sql:233),
[database/schema.sql:239](/var/www/html/database/schema.sql:239).

Die Konfiguration erlaubt die unmittelbare Löschung bereits verwendeter Gaben
und Stiche. Die Fremdschlüssel in `gaben_abgaben` verwenden `ON DELETE CASCADE`.
Damit verschwinden auch bestätigte Ausgaben beziehungsweise Auszahlungen.
Beim Löschen eines Stichs verschwinden außerdem die gelösten Stichzuordnungen.
Die Kassensummen ändern sich, ohne dass eine Rücknahme gebucht wurde.

Korrektur: verwendete Stammdaten archivieren oder deren Löschung sperren;
bestätigte Ausgaben dauerhaft erhalten. Die Kaskadenwirkung wurde anhand des
Schemas geprüft, nicht an persistenten Datensätzen ausgeführt.

### 4. P1 — Rangliste berücksichtigt nicht gelöste Serien

Fundstellen: [RanglistenService.php:67](/var/www/html/app/Services/RanglistenService.php:67),
[RanglistenService.php:207](/var/www/html/app/Services/RanglistenService.php:207),
[AbrechnungsService.php:118](/var/www/html/app/Services/AbrechnungsService.php:118).

Die Rangliste nimmt alle Schüsse mit passender externer Nummer und teilt sie
vollständig in Serien auf. Sie kontrolliert weder die gelöste Stichzuordnung
noch deren `anzahl_stiche`. Die Abrechnung begrenzt dagegen die zugeordneten
Serien auf die gelöste Menge. Zusätzliche Schüsse können daher die Rangliste
und die davon abhängige Finalqualifikation verbessern.

**Dynamisch reproduziert:** ein gelöster Fünfschussstich mit erster Serie
`1,1,1,1,1` und zusätzlicher Serie `10,10,10,10,10` ergibt in der Rangliste
`[50,5]`. Die Abrechnung ordnet dem gelösten Stich lediglich 5 Punkte zu und
führt die zusätzlichen 50 Punkte separat als nicht zugeordnet auf.

Korrektur: Rangliste und Abrechnung mit derselben Zuordnung und derselben
gelösten Serienmenge berechnen.

Zusätzlich werden unvollständige Serien bereits klassiert: ein einzelner
Schuss eines Fünfschussstichs genügt. Das wurde dynamisch bestätigt. Ob eine
vorläufige Klassierung erwünscht ist, muss fachlich festgelegt werden;
für endgültige Finalqualifikation sollte die Vollständigkeit explizit geprüft sein.

### 5. P2 — Altersgrenzen der Gabenregeln bleiben wirkungslos

Fundstellen: [Gaben.php:106](/var/www/html/app/Models/Gaben.php:106),
[AbrechnungsService.php:224](/var/www/html/app/Services/AbrechnungsService.php:224).

Die Oberfläche speichert `min_alter` und `max_alter`, aber die für die
Abrechnung verwendete Regelabfrage lädt diese Felder nicht. Der Vergleich
berücksichtigt nur Punkte; Geburtsdatum und Anlassjahr werden nicht übergeben.
Eine auf Minderjährige begrenzte Gabe wird somit auch Erwachsenen angeboten.

**Dynamisch reproduziert:** eine Regel mit `max_alter=17` wird unabhängig vom
Geburtsdatum berücksichtigt.

Korrektur: Altersgrenzen laden und vor der Vergabe gegen das nach einer
einheitlichen Fachregel berechnete Alter prüfen.

### 6. P2 — Überlappende Regeln zählen dieselbe Gabe mehrfach und verhindern das Speichern

Fundstellen: [AbrechnungsService.php:232](/var/www/html/app/Services/AbrechnungsService.php:232),
[Gaben.php:197](/var/www/html/app/Models/Gaben.php:197),
[database/schema.sql:248](/var/www/html/database/schema.sql:248).

Jede passende Regel erzeugt einen eigenen Gabeneintrag. Zwei passende Regeln
für denselben Stich und dieselbe Gabe werden nicht zusammengeführt. Die
Oberfläche und die Auszahlungssumme enthalten die Gabe doppelt. Beim Speichern
verletzt der zweite Eintrag den eindeutigen Schlüssel aus Gabe, Standblatt,
Stich und Serie; die komplette Transaktion wird zurückgerollt.

**Dynamisch reproduziert:** zwei Regeln für eine Gabe zu CHF 10 ergeben CHF 20.
Zwei entsprechende Abgabeeinträge verursachen MySQL-Fehler 1062.

Korrektur: passende Regeln pro Gabe/Stich/Serie zusammenführen oder
unzulässige Überschneidungen beim Konfigurieren ablehnen.

### 7. P2 — Neue Importdaten lassen den Gabenprüfstatus veraltet

Fundstellen: [ClientApiService.php:100](/var/www/html/app/Services/ClientApiService.php:100),
[Gaben.php:231](/var/www/html/app/Models/Gaben.php:231),
[Standblatt.php:205](/var/www/html/app/Models/Standblatt.php:205).

Manuelle Schusskorrekturen setzen die Gabenprüfung zurück. Neue SIUS-Schüsse
werden dagegen gespeichert, ohne `gaben_geprueft` zurückzusetzen oder offene
Gaben neu abzugleichen. Auch das Bearbeiten der gelösten Stichmengen führt
keinen solchen Abgleich aus. Ein Standblatt kann weiter als vollständig
abgegeben erscheinen, obwohl sich sein Anspruch geändert hat.

**Dynamisch reproduziert:** nach Import eines neuen Schusses blieben
`gaben_geprueft=1` und der berechnete Status `gaben_abgegeben=1` erhalten.

Korrektur: betroffene Standblätter innerhalb derselben Transaktion erneut zur
Prüfung markieren; bestätigte historische Abgaben dabei erhalten.

### 8. P2 — Speichern von Schüssen verliert die Sekunden des Zeitstempels

Fundstellen: [abrechnen.php:49](/var/www/html/app/Views/abrechnen/abrechnen.php:49),
[AbrechnenController.php:194](/var/www/html/app/Controllers/AbrechnenController.php:194),
[Schussreihenfolge.php:22](/var/www/html/app/Services/Schussreihenfolge.php:22).

Die Formularansicht formatiert vorhandene Zeitstempel nur als `Y-m-d\TH:i`.
Beim gemeinsamen Speichern wird dieser Wert wieder in alle Schüsse geschrieben,
auch wenn lediglich ein Punktwert geändert wurde. Die ursprünglichen Sekunden
werden zu `00`. Da die Serienaufteilung zuerst nach Zeit sortiert, können
später nachgelieferte Schüsse dadurch falsch eingeordnet werden.

**Dynamisch reproduziert:** `2026-10-12 10:00:59` wird im Formular zu
`2026-10-12T10:00` und beim Normalisieren zu `10:00:00`.

Korrektur: Sekunden erhalten, im Eingabefeld `step=1` verwenden oder unveränderte
Zeitfelder nicht erneut schreiben.

### 9. P2 — Kasse zieht auch noch nicht ausgegebene Gaben ab

Fundstelle: [KassenService.php:51](/var/www/html/app/Services/KassenService.php:51).

Die Schleife summiert jede gespeicherte Abgabe, ohne `abgegeben` auszuwerten.
Die Abrechnung speichert aber auch offene Ansprüche mit `abgegeben=0`.
Eine noch nicht bestätigte Barauszahlung reduziert daher bereits `netto`.
Die Kassenansicht unterscheidet offene Verpflichtungen und tatsächliche
Ausgaben nicht, obwohl die Standblattansicht diese Unterscheidung kennt.

Korrektur: offene und bestätigte Beträge getrennt ausweisen und eindeutig
festlegen, welcher Betrag in den tatsächlichen Kassenbestand eingeht.

### 10. P2 — Einnahmen nach Stich verwenden spätere Preise

Fundstelle: [Standblatt.php:93](/var/www/html/app/Models/Standblatt.php:93).

Die Einnahmen je Stich werden mit dem aktuellen `stich.preis` berechnet,
während die gesamte Einnahme aus den beim Lösen gespeicherten Standblattkosten
kommt. Nach einer Preisänderung widersprechen sich die beiden Kassenangaben.
Beim erneuten Speichern des Standblatts werden zudem alle Mengen mit aktuellen
Preisen neu berechnet.

**Dynamisch reproduziert:** ein Standblatt zu CHF 5 behält CHF 5 Gesamtpreis;
nach Änderung des Stichpreises zeigt die Einnahmenaufteilung CHF 15.

Korrektur: den tatsächlich berechneten Einzelpreis in `standblatt_stich`
speichern und bei Preisänderungen bestehende Buchungen erhalten.

### 11. P2 — Formularvalidierung akzeptiert ungültige Zahlen und Daten

Fundstellen: [AnlassFormService.php:26](/var/www/html/app/Validation/AnlassFormService.php:26),
[AnlassFormService.php:70](/var/www/html/app/Validation/AnlassFormService.php:70),
[AnlassFormService.php:94](/var/www/html/app/Validation/AnlassFormService.php:94),
[LoesenController.php:274](/var/www/html/app/Controllers/LoesenController.php:274).

Die Stichvalidierung prüft nur den Namen. Dezimalwerte werden per Cast
umgewandelt, negative Preise bleiben erlaubt und nichtnumerische Schusszahlen
werden zu 0. Anlassdaten werden lediglich lexikografisch miteinander verglichen;
ungültige Kalenderdaten werden nicht erkannt. Stichmengen haben keine obere
Grenze. Browserattribute wie `min=0` schützen die POST-Endpunkte nicht.

**Dynamisch reproduziert:** `preis=-10`, `anzahl_schuss=abc` und
`start_anlass=2026-99-99` erzeugen keine Validierungsfehler. Folgen sind falsche
Kosten, eine Einzelschusswertung durch den Fallback oder Datenbankfehler statt
verständlicher Rückmeldungen.

Korrektur: Zahlen, fachliche Grenzen, Feldlängen und echte Kalenderdaten vor
dem Speichern prüfen; Fehler mit erhaltenen Eingaben anzeigen.

### 12. P2 — Mehrere Stiche dürfen dieselbe SIUS-Anzeige-ID besitzen

Fundstellen: [AnlassFormService.php:70](/var/www/html/app/Validation/AnlassFormService.php:70),
[AbrechnungsService.php:111](/var/www/html/app/Services/AbrechnungsService.php:111),
[database/schema.sql:118](/var/www/html/database/schema.sql:118).

Weder Validierung noch Schema erzwingen eine eindeutige `anzeige_id` innerhalb
eines Anlasses. Die Auswertung identifiziert Schüsse ausschließlich anhand
dieser Nummer. Haben zwei gelöste Stiche dieselbe ID, werden dieselben Schüsse
für beide Stiche gewertet und können zweimal Gaben auslösen.

Korrektur: nichtleere Anzeige-IDs innerhalb eines Anlasses eindeutig halten
oder eine fachlich eindeutige zusätzliche Zuordnung implementieren.

### 13. P2 — Beispieldaten sind mit dem ausgelieferten Schema inkompatibel

Fundstellen: [beispieldaten.sql:90](/var/www/html/database/seeds/beispieldaten.sql:90),
[database/schema.sql:226](/var/www/html/database/schema.sql:226).

Die INSERTs für `gaben_abgaben` lassen die verpflichtende Spalte `stich_id`
weg. Die empfohlene Beispieldateninstallation bricht deshalb ab, nachdem
bereits andere Tabellen befüllt wurden. Außerdem fehlt `anzahl_stiche` bei
den Stichzuordnungen, wodurch deren Menge den Schema-Default 0 erhält. Die
Schussstartnummern und externen Nummern passen nicht zu den angelegten
Standblättern/Stichen.

**Dynamisch reproduziert gegen temporäre Tabellen aus `database/schema.sql`:**
MySQL-Fehler 1364, `Field 'stich_id' doesn't have a default value`.

Korrektur: vollständige, fachlich zusammenpassende Beispieldaten pflegen und
den gesamten Seedimport gegen das ausgelieferte Schema testen.

### 14. P2 — Ein konfigurierter Basispfad erzeugt URLs, die der Router nicht erkennt

Fundstellen: [Url.php:16](/var/www/html/app/Core/Url.php:16),
[Router.php:59](/var/www/html/app/Core/Router.php:59).

`Url::app()` ergänzt `APP_BASE_PATH`, aber der Router entfernt diesen Präfix
bei eingehenden Anfragen nicht. Die vorhandenen Tests kontrollieren lediglich
die erzeugten Links mit Basispfad, nicht deren tatsächliche Zustellung.

**Dynamisch reproduziert:** `APP_BASE_PATH=/test-app` erzeugt
`/test-app/dashboard`; eine Anfrage an diesen Pfad liefert 404 und erreicht
den für `/dashboard` registrierten Controller nicht.

Korrektur: den Basispfad bei eingehenden Requests auf einer Pfadgrenze
entfernen und die Kombination aus URL-Erzeugung und Dispatch testen.

### 15. P2 — Projektroot-Deployment kann die erzeugten Asset-URLs nicht auflösen

Fundstellen: [.htaccess:13](/var/www/html/.htaccess:13),
[Url.php:25](/var/www/html/app/Core/Url.php:25).

Die Dokumentation verspricht Unterstützung eines Document Roots auf dem
Projektverzeichnis. Die Views laden `/assets/...`; die Dateien liegen aber
unter `public/assets/...`. Die Root-`.htaccess` enthält keine entsprechende
Assetabbildung. Ohne eine zusätzliche Server-Alias-/Rewritekonfiguration werden
die Assetanfragen an den Frontcontroller geleitet und enden als 404.

Korrektur: im unterstützten Root-Deployment Assets explizit auf `public/assets`
abbilden oder ausschließlich `public/` als Document Root unterstützen und
dies vollständig dokumentieren. Dies ist eine Prüfung der ausgelieferten
Konfiguration; ein zusätzlicher Alias im tatsächlichen Webserver wurde nicht geprüft.

### 16. P2 — Löschen einer Gabenregel kontrolliert den Anlass nicht

Fundstelle: [AnlassController.php:372](/var/www/html/app/Controllers/AnlassController.php:372).

`deleteRegel()` lädt den Anlass aus der URL, löscht anschließend aber jede
übergebene Regel-ID ohne Prüfung ihres Stichs und dessen Anlass. Eine gültige
POST-Anfrage für Anlass A kann dadurch eine Regel von Anlass B entfernen.
Die vergleichbare Stichlöschung überprüft diese Zugehörigkeit bereits.

Korrektur: Regel laden, zugehörigen Stich und Anlass prüfen; bei einem
abweichenden Kontext mit 404 abbrechen. Dies ist ein Fehler der Kontextprüfung;
ein separates Rollen-/Berechtigungsmodell ist im Projekt nicht vorhanden.

### 17. P2 — Schussimport bestätigt unbrauchbare Eingaben als Erfolg

Fundstellen: [ClientApiController.php:110](/var/www/html/app/Controllers/ClientApiController.php:110),
[ClientApiController.php:172](/var/www/html/app/Controllers/ClientApiController.php:172),
[ClientApiService.php:57](/var/www/html/app/Services/ClientApiService.php:57).

Ungültiges JSON wird in ein leeres Array umgewandelt; fehlende `Shots` werden
ebenfalls als leere Liste behandelt. Der authentifizierte Import antwortet
dann mit `201` und `success=true`. Einzelne nicht als Array vorliegende
Schüsse werden still übersprungen; leere Schussobjekte besitzen keine
Pflichtfeldprüfung und können als Datensatz ohne Startnummer/Wertung gespeichert
werden. Startnummern werden nicht gegen Standblätter des Anlasses geprüft.

Korrektur: JSON-Fehler und ungültige Einzelobjekte mit 400/422 zurückmelden;
verbindliche Identitäts-/Wertungsfelder und deren Zuordnung prüfen. Das
Erfolgsergebnis muss eindeutig die tatsächlich akzeptierten Eingaben beschreiben.

### 18. P1 — V2-Kennzeichen `I` wird importiert, aber aus der Wertung ausgeschlossen

Fundstellen: [V2 core.py:132](https://github.com/nimand2/SiusClientV2/blob/c613ed20d863085a2bf458ec5bc3a9a2e3b6b899/sius_client/core.py#L132),
[ClientApiService.php:84](/var/www/html/app/Services/ClientApiService.php:84),
[RanglistenService.php:38](/var/www/html/app/Services/RanglistenService.php:38),
[AbrechnungsService.php:95](/var/www/html/app/Services/AbrechnungsService.php:95).

V2 wandelt das Kennzeichen `I` im 17. CSV-Feld in `InsDel=1` um. Der Server
speichert dies unverändert und behandelt jeden Wert ungleich 0 als ausgeschlossen.
Auch die manuelle Schusslöschung nutzt diesen Wert. Eine erfolgreiche
Importbestätigung bedeutet deshalb für diese Datensätze keine Teilnahme an
Rangliste oder Abrechnung.

**Dynamisch reproduziert:** fünf synthetische Zeilen wurden mit dem echten
V2-Parser eingelesen und durch die echten PHP-Import-/Auswertungsservices mit
isolierten Modell-Fixtures geführt. Mit `I` wurden fünf Schüsse angenommen,
aber null Schüsse ausgewertet und null Teilnehmer klassiert. Dieselben Zeilen
mit Kennzeichen `0` ergaben fünf ausgewertete Schüsse, 50 Punkte und einen
klassierten Teilnehmer. Es wurde kein produktiver Server angesprochen.

Damit ist das Verhalten der Kombination bestätigt. Welche fachliche Bedeutung
`I` in den tatsächlich verwendeten SIUS-Dateien besitzt, wurde nicht anhand
einer verbindlichen Formatspezifikation verifiziert. Insbesondere darf nicht
pauschal jeder Importwert auf 0 gesetzt werden: Manuelle Löschungen müssen
weiter erhalten bleiben. Importkennzeichen und lokaler Löschstatus sollten
nach geklärter SIUS-Semantik eindeutig abgebildet und gemeinsam getestet werden.

## Abgleich mit SiusClientV2

Der externe Client verwendet Python/PyQt6. Geprüft wurden seine Übertragungslogik,
das SQLite-Journal, die API-Aufrufe und die Steuerung von Live-Start/Pause im
Quellcode. Die fünf alten C#-Befunde wurden aus der aktiven Liste entfernt.

- V2 sichert Schüsse vor dem Versand im Journal und quittiert sie nach gültiger
  Serverantwort. Fehlerwiederholung, verlorene Antworten und Neustart werden
  getestet; die alte vorzeitige Importmarkierung liegt hier nicht vor.
- Das Journal trennt Server und Anlass. Ein Anlasswechsel ist während des
  laufenden Live-Tasks gesperrt; Pausieren wartet auf dessen Abschluss.
- V2 wartet bei einer unvollständigen letzten CSV-Zeile auf den Zeilenumbruch
  und prüft Dateiaustausch sowie periodischen Wiederabgleich.
- Vollständiger Schützenexport und Anhängen verwenden dieselbe Latin-1-Codierung.
  Der Export ist kommagetrennt und hat elf Felder ohne Überschrift, mit Dummy-ID 0.
- Die fünf verwendeten API-Endpunkte, PascalCase-Schussfelder, Bearer-Anmeldung
  und die Prüfung von `success`, `imported` und optional `accepted` passen zum
  aktuellen Serververtrag. Auch ein Wiederabgleich mit `imported=0` ist zulässig.
- Die in V2 vorbereitete zeitliche Serversortierung und die Bestätigungsfelder
  `accepted`/`duplicates` sind hier bereits vorhanden. Das alte Serverpaket aus
  dem Client-Repository wurde nicht über den aktuellen Servercode kopiert.

**Hier ausgeführt:** 25 Transfer-Tests und drei API-Tests bestehen unter Python
3.13.3. Die vier Qt-UI-Tests wurden nicht ausgeführt, weil PyQt6 hier nicht
installiert ist. Der gemeinsame Kennzeichen-Test aus Befund 18 ergänzt die
getrennten Tests von Client und Server. Der Abgleich ist keine vollständige
Prüfung sämtlicher Dateien des externen Repositories und kein realer
Windows-/Anlagenbetrieb.

## Hinweise und Prüfgrenzen der ursprünglichen Prüfung

- `config/app.php:8` setzt Secure-Cookies standardmäßig auf `false`; diese
  Einstellung gilt sowohl für die Session als auch für Remember-Me. Für einen
  HTTPS-Betrieb ist eine sichere, umgebungsabhängige Konfiguration erforderlich.
  Der tatsächlich aktive HTTPS-/Proxybetrieb wurde nicht geprüft.
- In Web- und API-Login ist keine Begrenzung fehlgeschlagener Versuche vorhanden.
  Eine mögliche vorgelagerte Begrenzung im Webserver/Proxy wurde nicht geprüft.
- `ClientApiService.php:85` verarbeitet `TimeSinceChange` als Integer, obwohl
  Client und Schnittstellendokumentation einen Dezimalwert vorsehen. `2.5` wird
  zu `2`; das Schema verwendet ebenfalls `INT`.
- `Plz.php:155` verwendet bei `find_by_ortschaftsname()` einen Vergleich mit `=`,
  bindet aber `%Ortsname%`. Die Methode findet einen normalen Ortsnamen dadurch
  nicht. Sie wird in den aktuellen Controllern nicht verwendet.
- `docs/final-export.md` behauptet, Absagen lösten kein Nachrücken aus;
  Implementierung, Tests und Finaloberfläche rücken bestätigte Teilnehmer nach.
- README und Schnittstellendokumentation wurden auf den externen SiusClientV2
  und dessen aktuelle Export-/Übertragungsformate aktualisiert.
- Browserdruck, reale SIUS-Dateien, Clientbetrieb unter Windows und Belastungs-
  beziehungsweise Nebenläufigkeitstests wurden nicht ausgeführt. Node.js und
  PyQt6 stehen in dieser Umgebung nicht zur Verfügung.
- Keine Prüfung der Zugangsdatenwerte oder produktiven Personendaten war für
  diese Befunde erforderlich; solche Daten wurden nicht in den Bericht übernommen.

## Prüfungen des Ausgangsstands

- PHP 8.4.5: Syntaxprüfung aller 84 versionierten PHP-Dateien erfolgreich.
- `php scripts/test.php`: alle sechs Unit-Dateien und beide View-Testdateien
  erfolgreich.
- `php scripts/test.php integration`: vorhandener Importtest erfolgreich,
  einschließlich Dubletten, vollständigem Rollback und Wiederverwendung der Verbindung.
- Sicherheitstest zusätzlich mit einem ausschließlich für den Test gesetzten
  API-Geheimnis ausgeführt: signierte Tokens und manipulierte Signaturen geprüft.
- Login über `public/index.php` gerendert: HTML und Formular-CSRF-Feld vorhanden.
- Zusätzliche isolierte PHP-Prüfungen für Serienabweichung, unvollständige
  Serie, Altersregel, doppelte Gaben, Sekundenverlust, Eingabevalidierung und
  Routing mit Basispfad durchgeführt; Ergebnisse stehen bei den Befunden.
- Zusätzliche Datenbankprüfungen für historische Gabenpreise, Einnahmenabweichung,
  Importprüfstatus, doppelte Abgaben und Seedimport durchgeführt. Sämtliche
  Schreibzugriffe fanden in temporären Tabellen statt; diese wurden entfernt.
- Zwei Updatepakete unter `/tmp` erzeugt: identische SHA-256-Prüfsummen;
  lokale Datenbankkonfiguration und Backups waren ausgeschlossen.

Die bestehenden Tests bestehen trotz der aufgeführten Fehler. Besonders fehlen
serverseitige Tests für Überschneidungen von Regeln, Altersgrenzen,
Preisänderungen, zusätzliche/nicht gelöste Serien, Import nach Gabenprüfung,
Seedinstallation und den vollständigen Requestweg mit Basispfad.

Für die Behebung zuerst Datenbankreset und historische Buchungsdaten absichern,
dann Ranglisten-/Abrechnungszuordnung vereinheitlichen und die Gabenlogik
korrigieren. Für die Kombination mit SiusClientV2 zusätzlich die Kennzeichen-
semantik aus Befund 18 abstimmen und gemeinsam absichern.
