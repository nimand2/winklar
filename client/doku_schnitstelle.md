# SIUS-Client Schnittstellendokumentation

Diese Datei beschreibt den Serververtrag und dessen Verwendung durch den externen Python-Client [SiusClientV2](https://github.com/nimand2/SiusClientV2). Der bisherige C#-Client wird nicht mehr verwendet.

Client-Abgleich vom 12. Oktober 2026: Commit [`c613ed2`](https://github.com/nimand2/SiusClientV2/tree/c613ed20d863085a2bf458ec5bc3a9a2e3b6b899). Installation und Bedienung werden im Client-Repository gepflegt.

Die Dokumentation ist bewusst auf die Endpunkte beschränkt, die der Client wirklich nutzt. Es gibt aktuell keine generische CRUD-API für Anlässe, Schützen oder Schussdaten.

## Überblick

- **Client**: SiusClientV2, Python-Desktop-Anwendung mit PyQt6 unter Windows.
- **Server**: PHP-Webapplikation mit JSON-API unter `/api/...`.
- **Transport**: HTTP oder HTTPS.
- **Datenformat**: JSON für API-Aufrufe, CSV für den lokalen SIUS-Dateiaustausch.
- **Authentifizierung**: Bearer Token im HTTP-Header `Authorization`.
- **Polling**: Schussimport etwa jede Sekunde, neue Schützen alle 3 Sekunden; bei Fehlern wächst die Wartezeit bis auf 30 Sekunden. Änderungen lösen eine vollständige Dateiprüfung aus, spätestens erfolgt sie nach 30 Sekunden.
- **Journal**: Schüsse werden vor dem Versand in SQLite gesichert, getrennt nach Server-Adresse und Anlass. Erst eine gültige Serverbestätigung entfernt sie aus der offenen Warteschlange.

## Ablauf im Client

1. Benutzer gibt Server-Adresse, Benutzername, Passwort, Importdatei und Exportdatei ein.
2. Client meldet sich mit `POST /api/login` am Server an.
3. Server liefert ein Bearer Token.
4. Client setzt `Authorization: Bearer <token>` für alle weiteren API-Aufrufe.
5. Client lädt alle Anlässe über `GET /api/anlaesse`.
6. Benutzer wählt Dateien, Schiesstag und Anlass und startet den Live-Betrieb.
7. Client exportiert die Schützen dieses Anlasses in die lokale Export-CSV; ein vorhandener Export wird erst nach Bestätigung ersetzt.
8. Hintergrundprozess startet:
   - vollständige SIUS-Schusszeilen aus der Import-CSV lesen und dauerhaft vormerken,
   - offene Schüsse in Blöcken von höchstens 500 an den Server senden und bestätigen,
   - neue Schützen vom Server holen und an die Export-CSV anhängen.

## Basis-URL

Die Server-Adresse wird im Client eingegeben, zum Beispiel:

```text
https://example.ch
http://localhost
```

Die Adresse muss mit `http://` oder `https://` beginnen. Der Client entfernt ein abschließendes `/` automatisch.

## Authentifizierung

### POST `/api/login`

Meldet den Client an und gibt ein API-Token zurück.

Dieser Endpunkt benötigt noch keinen Bearer Token.

**Request**

```json
{
  "Username": "admin",
  "Password": "secret"
}
```

Der Server akzeptiert auch kleingeschriebene Feldnamen:

```json
{
  "username": "admin",
  "password": "secret"
}
```

**Response `200 OK`**

```json
{
  "token": "base64urlPayload.hmacSignature",
  "expiresIn": 604800
}
```

`expiresIn` ist die Gültigkeit in Sekunden. Aktuell sind das 7 Tage.

**Fehler `400 Bad Request`**

```json
{
  "success": false,
  "message": "Benutzername und Passwort sind erforderlich.",
  "errorCode": "INVALID_INPUT"
}
```

**Fehler `401 Unauthorized`**

```json
{
  "success": false,
  "message": "Ungueltige Anmeldedaten.",
  "errorCode": "INVALID_CREDENTIALS"
}
```

## Header für geschützte Endpunkte

Alle folgenden Endpunkte benötigen:

```http
Authorization: Bearer <token>
Accept: application/json
```

JSON-Requests verwenden zusätzlich:

```http
Content-Type: application/json; charset=utf-8
```

Wenn der Token fehlt, ungültig oder abgelaufen ist, antwortet der Server mit:

```json
{
  "success": false,
  "message": "Authentifizierung erforderlich.",
  "errorCode": "UNAUTHORIZED"
}
```

## Anlässe

### GET `/api/anlaesse`

Lädt alle Anlässe für die Auswahl im Client.

**Response `200 OK`**

```json
[
  {
    "id": 1,
    "name": "Feldschiessen 2026"
  }
]
```

**Felder**

| Feld | Typ | Beschreibung |
| --- | --- | --- |
| `id` | int | ID des Anlasses aus der Datenbank. |
| `name` | string | Anzeigename des Anlasses. |

## Schützenexport

### GET `/api/anlaesse/{id}/shooters`

Lädt alle Schützen beziehungsweise Standblätter eines Anlasses. Der Client schreibt daraus die lokale Export-CSV für SIUS.

**Pfadparameter**

| Parameter | Typ | Beschreibung |
| --- | --- | --- |
| `id` | int | Anlass-ID. |

**Response `200 OK`**

```json
[
  {
    "id": 12,
    "startnummer": 12,
    "name": "Muster",
    "vorname": "Max",
    "verein": "Schützenverein Beispiel",
    "bahn": 0,
    "abloesung": 0,
    "aktiv": true
  }
]
```

**Felder**

| Feld | Typ | Beschreibung |
| --- | --- | --- |
| `id` | int | ID des Standblatts. |
| `startnummer` | int | Startnummer für SIUS. Aktuell identisch mit der Standblatt-ID. |
| `name` | string | Nachname. |
| `vorname` | string | Vorname. |
| `verein` | string | Verein/Zusatz/Firmenanrede aus der Adresse. |
| `bahn` | int | Aktuell immer `0`. |
| `abloesung` | int | Aktuell immer `0`. |
| `aktiv` | bool | Aktuell immer `true`. |

**Fehler `400 Bad Request`**

```json
{
  "success": false,
  "message": "Ungueltiger Anlass.",
  "errorCode": "INVALID_ANLASS"
}
```

## Neue Schützen

### GET `/api/anlaesse/{id}/shooters/new?sinceId={lastId}`

Lädt nur Schützen/Standblätter, deren ID größer als `sinceId` ist. Der Client nutzt diesen Endpunkt im Hintergrund und hängt neue Zeilen an die Export-CSV an.

**Pfadparameter**

| Parameter | Typ | Beschreibung |
| --- | --- | --- |
| `id` | int | Anlass-ID. |

**Queryparameter**

| Parameter | Typ | Beschreibung |
| --- | --- | --- |
| `sinceId` | int | Letzte bereits bekannte Standblatt-ID. Fehlt der Parameter, wird `0` verwendet. |

**Response `200 OK`**

```json
[
  {
    "id": 13,
    "startnummer": 13,
    "name": "Beispiel",
    "vorname": "Erika",
    "verein": "",
    "bahn": 0,
    "abloesung": 0,
    "aktiv": true
  }
]
```

Bei keinen neuen Schützen wird ein leeres Array zurückgegeben:

```json
[]
```

## Schussimport

### POST `/api/anlaesse/{id}/shots/import`

Importiert eine Liste von SIUS-Schussdatensätzen für einen Anlass.

V2 sendet offene Schüsse aus seinem lokalen Journal. Bei einem vollständigen Wiederabgleich sendet er auch zuvor bestätigte Schüsse erneut. Der Server verhindert doppelte Einträge über einen Import-Hash aus den relevanten SIUS-Schussfeldern. Dadurch bleiben Daten auch bei verlorenen Antworten, Neustart und erneutem Versand derselben Datei eindeutig.

**Pfadparameter**

| Parameter | Typ | Beschreibung |
| --- | --- | --- |
| `id` | int | Anlass-ID. |

**Request**

```json
{
  "AnlassId": 1,
  "Shots": [
    {
      "StartNr": 12,
      "Primaerwertung": 10.4,
      "Schussart": 0,
      "BahnNr": 3,
      "Sekundaerwertung": 10,
      "Teiler": 123,
      "Zeit": "2026-04-28 14:30:15",
      "Mouche": 1,
      "X": 0.12,
      "Y": -0.34,
      "InTime": 1,
      "TimeSinceChange": 2.5,
      "SweepDirection": 0,
      "Demonstration": 0,
      "Match": 1,
      "Stich": 1,
      "InsDel": 0,
      "TotalArt": 0,
      "Gruppe": 0,
      "Feuerart": 0,
      "LogEvent": 123456,
      "LogTyp": 1,
      "ZeitSeitJahresbeginn": 102030,
      "Abloesung": 0,
      "Waffe": 0,
      "Position": 0,
      "TargetId": 3,
      "ExterneNummer": 0
    }
  ]
}
```

Der Server akzeptiert für `AnlassId` und `Shots` auch `anlassId` und `shots`. Innerhalb eines Schusses akzeptiert der Server die gezeigten PascalCase-Feldnamen und zusätzlich die Variante mit kleinem Anfangsbuchstaben, zum Beispiel `startNr`.

`Shots` ist verpflichtend und muss eine Liste mit höchstens 500 Schüssen sein.
Eine ausdrücklich leere Liste ist zulässig. Jeder Schuss benötigt `StartNr`,
`Primaerwertung`, `Zeit` und `ExterneNummer`; die Startnummer muss zu einem
Standblatt dieses Anlasses gehören. Werte und Grenzen werden vor jedem
Schreibzugriff geprüft. Widersprüchliche PascalCase-/camelCase-Doppelfelder
werden zurückgewiesen. Zeitstempel bewahren Sekunden, `TimeSinceChange`
bewahrt bis zu vier Nachkommastellen.

**Response `201 Created`**

```json
{
  "success": true,
  "imported": 1,
  "accepted": 1,
  "duplicates": 0
}
```

`imported` enthält die Anzahl neu eingefügter Datensätze. Bereits vorhandene Schüsse werden übersprungen und nicht mitgezählt.

`accepted` zählt alle angenommenen Schüsse einschließlich vorhandener Datensätze; `duplicates` ist `accepted - imported`. V2 akzeptiert einen erfolgreichen Wiederabgleich mit `imported: 0`. Wenn `accepted` vorhanden ist, muss die Zahl exakt der Anzahl gesendeter Schüsse entsprechen, bevor V2 den Block lokal bestätigt.

**Fehler `400 Bad Request`: Anlass passt nicht**

```json
{
  "success": false,
  "message": "Ungueltiger Anlass.",
  "errorCode": "INVALID_ANLASS"
}
```

Dieser Fehler tritt auf, wenn die Anlass-ID im Pfad ungültig ist oder `AnlassId` im Body gesetzt ist und nicht zur Pfad-ID passt.

**Fehler `400 Bad Request`: Shots ist kein Array**

```json
{
  "success": false,
  "message": "Shots muss ein Array sein.",
  "errorCode": "INVALID_INPUT"
}
```

**Fehler `422 Unprocessable Content`**

Ungültiges JSON, falsche Pflichtfelder, Datums-/Zahlformate, unbekannte
Startnummern, widersprüchliche Felder oder zu grosse Listen liefern
`success: false` und `errorCode: "INVALID_INPUT"`. Der gesamte Block wird
abgelehnt. Fehlende oder nicht als Array gesendete `Shots` liefern `400`.
Import und erneute Gabenprüfung werden gemeinsam gespeichert; bei einem
Datenbankfehler wird der ganze Block zurückgerollt.

**Fehler `429 Too Many Requests` beim Login**

Nach zehn Versuchen je Kombination aus Login und IP oder fünfzig Versuchen je IP
innerhalb von 15 Minuten folgt `errorCode: "TOO_MANY_ATTEMPTS"` mit
`Retry-After: 900`. Web- und API-Login teilen diese Begrenzung.

**Fehler `500 Internal Server Error`**

```json
{
  "success": false,
  "message": "Schussdaten konnten nicht importiert werden.",
  "errorCode": "IMPORT_FAILED"
}
```

## SIUS-CSV Importdatei

Der Client liest die Importdatei mit `ISO-8859-1` und erwartet Semikolon als Trennzeichen.

V2 erwartet genau 28 Felder und verwendet einen CSV-Parser mit Unterstützung für Anführungszeichen. Eine unvollständige letzte Zeile bleibt bis zum abschließenden Zeilenumbruch ausstehend. Ungültige Zeilen werden im Journal und in einer Fehlerdatei neben der Quelle gesichert.

**Reihenfolge der Felder**

| Position | JSON-Feld | Typ |
| --- | --- | --- |
| 1 | `StartNr` | int |
| 2 | `Primaerwertung` | decimal |
| 3 | `Schussart` | int |
| 4 | `BahnNr` | int |
| 5 | `Sekundaerwertung` | decimal |
| 6 | `Teiler` | int |
| 7 | `Zeit` | string |
| 8 | `Mouche` | int |
| 9 | `X` | decimal |
| 10 | `Y` | decimal |
| 11 | `InTime` | int |
| 12 | `TimeSinceChange` | decimal |
| 13 | `SweepDirection` | int |
| 14 | `Demonstration` | int |
| 15 | `Match` | int |
| 16 | `Stich` | int |
| 17 | `InsDel` | int |
| 18 | `TotalArt` | int |
| 19 | `Gruppe` | int |
| 20 | `Feuerart` | int |
| 21 | `LogEvent` | long |
| 22 | `LogTyp` | int |
| 23 | `ZeitSeitJahresbeginn` | long |
| 24 | `Abloesung` | int |
| 25 | `Waffe` | int |
| 26 | `Position` | int |
| 27 | `TargetId` | int |
| 28 | `ExterneNummer` | int |

Dezimalwerte dürfen in der CSV ein Komma oder einen Punkt verwenden. Der Client normalisiert Kommas vor dem JSON-Versand zu Punkten.

Uhrzeiten ohne Datum werden vor dem Versand anhand des gewählten Schiesstags und des SIUS-Jahreszeitzählers ergänzt. V2 verarbeitet diesen Zähler als Hundertstelsekunden seit Jahresbeginn und prüft die Übereinstimmung mit der Uhrzeit. Bei Zähler 0 verwendet V2 das gewählte Datum.

Beim Feld `InsDel` bildet V2 das CSV-Kennzeichen `I` auf die JSON-Zahl `1` ab.
Laut [SiusData.pdf, Seite 39](../docs/reference/SiusData.pdf) bedeutet `1` manuell
eingefügt und zählt als Schuss; `2` bedeutet ignorieren und zählt nicht. `0` zählt
regulär. Der Server bewahrt das Kennzeichen und verwendet für lokale manuelle
Löschungen das separate Feld `manuell_geloescht`. Wiederholte Importe heben eine
lokale Löschung nicht auf. Der Betreiber hat bestätigt, dass sämtliche bestehenden
`1`-Datensätze wieder gewertet werden sollen.


## SIUS-CSV Exportdatei

V2 schreibt beim ersten Live-Start für einen Übertragungskontext die komplette Schützenliste. Neue Schützen werden anschließend angehängt. Schreiben und Anhängen erfolgen durch eine temporäre Datei mit anschließendem atomarem Ersetzen.

**Format**: Kommagetrennt, ohne Spaltenüberschrift, mit elf Feldern: Standblatt-ID, Nachname, Vorname, Kurzname und sieben leere Felder. Der Export beginnt mit einem Dummy-Datensatz mit ID 0. Kommas und Anführungszeichen in Namen werden nach CSV-Regeln maskiert.

**Beispiel**

```csv
0,DummyName,Dummy,D. DummyName,,,,,,,
12,Muster,Max,M. Muster,,,,,,,
```

Vollständiger Export und Anhängen verwenden durchgehend `ISO-8859-1` und CRLF-Zeilenumbrüche. Nicht darstellbare Namen führen zu einer Fehlermeldung; die bestehende Exportdatei bleibt dabei erhalten.

## Datenzuordnung auf dem Server

Beim Schussimport werden die JSON-Felder in die Tabelle `schussdaten` übertragen.

| JSON-Feld | Datenbankfeld |
| --- | --- |
| `AnlassId` oder Pfad-ID | `id_anlass` |
| `StartNr` | `start_nr` |
| `Primaerwertung` | `primaerwertung` |
| `Schussart` | `schussart` |
| `BahnNr` | `bahn_nr` |
| `Sekundaerwertung` | `sekundaerwertung` |
| `Teiler` | `teiler` |
| `Zeit` | `schuss_zeit` |
| `Mouche` | `mouche` |
| `X` | `x_koordinate` |
| `Y` | `y_koordinate` |
| `InTime` | `in_time` |
| `TimeSinceChange` | `time_since_change` |
| `SweepDirection` | `sweep_direction` |
| `Demonstration` | `demonstration` |
| `Match` | `match_index` |
| `Stich` | `stich_index` |
| `InsDel` | `ins_del` |
| `TotalArt` | `total_art` |
| `Gruppe` | `gruppe` |
| `Feuerart` | `feuerart` |
| `LogEvent` | `log_event` |
| `LogTyp` | `log_typ` |
| `ZeitSeitJahresbeginn` | `zeit_seit_jahresanfang` |
| `Abloesung` | `abloesung` |
| `Waffe` | `waffe` |
| `Position` | `position` |
| `TargetId` | `target_id` |
| `ExterneNummer` | `externe_nummer` |

`created_by_user_id` und `updated_by_user_id` werden aus dem angemeldeten API-Benutzer gesetzt.

## Implementierte Routen

Aktuell sind für den SIUS-Client diese API-Routen registriert:

| Methode | Route | Zweck |
| --- | --- | --- |
| POST | `/api/login` | Login und Tokenausgabe |
| GET | `/api/anlaesse` | Anlassliste |
| GET | `/api/anlaesse/{id}/shooters` | Alle Schützen eines Anlasses |
| GET | `/api/anlaesse/{id}/shooters/new?sinceId=...` | Neue Schützen seit letzter ID |
| POST | `/api/anlaesse/{id}/shots/import` | Schussdaten importieren |

Nicht implementiert sind zum Beispiel:

- `POST /api/auth/login`
- `GET /api/anlass`
- `POST /api/anlass`
- `GET /api/schuetze`
- `GET /api/schussdaten`
- `PUT`- oder `DELETE`-Endpunkte für diese API

## Beispiel mit curl

### Login

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"Username":"admin","Password":"secret"}'
```

### Anlässe laden

```bash
curl http://localhost/api/anlaesse \
  -H "Authorization: Bearer TOKEN"
```

### Schützen laden

```bash
curl http://localhost/api/anlaesse/1/shooters \
  -H "Authorization: Bearer TOKEN"
```

### Schüsse importieren

```bash
curl -X POST http://localhost/api/anlaesse/1/shots/import \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"AnlassId":1,"Shots":[{"StartNr":12,"Primaerwertung":10.4,"Schussart":0,"BahnNr":3,"Sekundaerwertung":10,"Teiler":123,"Zeit":"2026-04-28 14:30:15","Mouche":1,"X":0.12,"Y":-0.34,"InTime":1,"TimeSinceChange":2.5,"SweepDirection":0,"Demonstration":0,"Match":1,"Stich":1,"InsDel":0,"TotalArt":0,"Gruppe":0,"Feuerart":0,"LogEvent":123456,"LogTyp":1,"ZeitSeitJahresbeginn":102030,"Abloesung":0,"Waffe":0,"Position":0,"TargetId":3,"ExterneNummer":0}]}'
```
