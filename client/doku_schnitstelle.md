# SIUS WebServer - Dokumentation für KI-Codegenerator

## Projekt-Übersicht
- **Projekt-Name**: SiusClient WebServer
- **Framework**: ASP.NET Web API (für .NET Framework 4.7.2)
- **Zweck**: REST-API für Schießanlage-Verwaltung (Anlässe, Schützen, Schussdaten)
- **Authentifizierung**: Token-basiert (Bearer Token)

---

## 1. Projektstruktur (zu generieren)

```
WebServerProject/
├── Controllers/
│   ├── AnlassController.cs
│   ├── ScheuetzenController.cs
│   ├── SchussdatenController.cs
│   └── AuthController.cs
├── Models/
│   ├── Anlass.cs
│   ├── Schuetze.cs
│   ├── Schussdaten.cs
│   ├── LoginRequest.cs
│   └── ApiResponse.cs
├── Services/
│   └── TokenService.cs
├── Web.config
└── Global.asax.cs
```

---

## 2. Datenmodelle

### 2.1 Anlass (Veranstaltung)
```json
{
  "id": "int",
  "name": "string",
  "datum": "DateTime",
  "beschreibung": "string",
  "ort": "string"
}
```

### 2.2 Schütze
```json
{
  "id": "int",
  "schuetzennummer": "int",
  "vorname": "string",
  "nachname": "string",
  "anlassId": "int"
}
```

### 2.3 Schussdaten
```json
{
  "id": "long",
  "schuetzennummer": "int",
  "seriennummer": "string",
  "schuss": "int",
  "schussdatum": "DateTime",
  "ergebnis": "int"
}
```

### 2.4 Login Request
```json
{
  "username": "string",
  "password": "string"
}
```

### 2.5 API Response (Standard)
```json
{
  "success": "bool",
  "message": "string",
  "data": "object (optional)"
}
```

---

## 3. API-Endpunkte

### 3.1 Authentifizierung

#### POST /api/auth/login
**Beschreibung**: Benutzer authentifizieren und Token erhalten

**Request**:
```json
{
  "username": "admin",
  "password": "password123"
}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Login erfolgreich",
  "data": {
    "token": "eyJhbGc...",
    "expiresIn": 3600
  }
}
```

**Error Response (401 Unauthorized)**:
```json
{
  "success": false,
  "message": "Ungültige Anmeldedaten"
}
```

---

### 3.2 Anlässe (Veranstaltungen)

#### GET /api/anlass
**Beschreibung**: Alle Anlässe auflisten
**Authentifizierung**: Erforderlich (Bearer Token)
**Response (200 OK)**: Array von Anlass-Objekten

#### GET /api/anlass/{id}
**Beschreibung**: Details eines Anlasses abrufen
**Authentifizierung**: Erforderlich
**Parameter**: id (int)
**Response (200 OK)**: Anlass-Objekt

#### POST /api/anlass
**Beschreibung**: Neuen Anlass anlegen
**Authentifizierung**: Erforderlich
**Request Body**:
```json
{
  "name": "Landesmeisterschaft 2024",
  "datum": "2024-06-15T10:00:00",
  "beschreibung": "Landesmeisterschaft im Schießsport",
  "ort": "Schützenplatz München"
}
```
**Response (201 Created)**: Neu erstelltes Anlass-Objekt

#### PUT /api/anlass/{id}
**Beschreibung**: Anlass aktualisieren
**Authentifizierung**: Erforderlich
**Request Body**: Anlass-Objekt mit aktualisierten Werten
**Response (200 OK)**: Aktualisiertes Anlass-Objekt

#### DELETE /api/anlass/{id}
**Beschreibung**: Anlass löschen
**Authentifizierung**: Erforderlich
**Response (204 No Content)**

---

### 3.3 Schützen

#### GET /api/schuetze?anlassId={id}
**Beschreibung**: Schützen für einen Anlass auflisten
**Authentifizierung**: Erforderlich
**Query-Parameter**: anlassId (int)
**Response (200 OK)**: Array von Schütze-Objekten

#### GET /api/schuetze/{id}
**Beschreibung**: Details eines Schützen abrufen
**Authentifizierung**: Erforderlich
**Response (200 OK)**: Schütze-Objekt

#### POST /api/schuetze
**Beschreibung**: Neuen Schützen anlegen
**Authentifizierung**: Erforderlich
**Request Body**:
```json
{
  "schuetzennummer": 1,
  "vorname": "Max",
  "nachname": "Mustermann",
  "anlassId": 1
}
```
**Response (201 Created)**: Neu erstellter Schütze

#### PUT /api/schuetze/{id}
**Beschreibung**: Schütze aktualisieren
**Authentifizierung**: Erforderlich
**Response (200 OK)**: Aktualisierter Schütze

#### DELETE /api/schuetze/{id}
**Beschreibung**: Schützen löschen
**Authentifizierung**: Erforderlich
**Response (204 No Content)**

---

### 3.4 Schussdaten

#### GET /api/schussdaten?schuetzennummer={num}&anlassId={id}
**Beschreibung**: Schussdaten abrufen (optional gefiltert)
**Authentifizierung**: Erforderlich
**Query-Parameter**: schuetzennummer (optional), anlassId (optional)
**Response (200 OK)**: Array von Schussdaten-Objekten

#### GET /api/schussdaten/{id}
**Beschreibung**: Einzelne Schussdaten abrufen
**Authentifizierung**: Erforderlich
**Response (200 OK)**: Schussdaten-Objekt

#### POST /api/schussdaten
**Beschreibung**: Neue Schussdaten hinzufügen (Batch-Import möglich)
**Authentifizierung**: Erforderlich
**Request Body (Single)**:
```json
{
  "schuetzennummer": 1,
  "seriennummer": "SN001",
  "schuss": 1,
  "schussdatum": "2024-06-15T10:30:00",
  "ergebnis": 10
}
```

**Request Body (Batch - Array)**:
```json
[
  {
    "schuetzennummer": 1,
    "seriennummer": "SN001",
    "schuss": 1,
    "schussdatum": "2024-06-15T10:30:00",
    "ergebnis": 10
  },
  {
    "schuetzennummer": 1,
    "seriennummer": "SN001",
    "schuss": 2,
    "schussdatum": "2024-06-15T10:31:00",
    "ergebnis": 9
  }
]
```

**Response (201 Created)**: Neu erstellte Schussdaten / Array von Objekten

#### PUT /api/schussdaten/{id}
**Beschreibung**: Schussdaten aktualisieren
**Authentifizierung**: Erforderlich
**Response (200 OK)**: Aktualisierte Schussdaten

#### DELETE /api/schussdaten/{id}
**Beschreibung**: Schussdaten löschen
**Authentifizierung**: Erforderlich
**Response (204 No Content)**

---

## 4. Authentifizierung & Autorisierung

### Header-Format
```
Authorization: Bearer <token>
```

### Token-Eigenschaften
- **Typ**: JWT (JSON Web Token)
- **Ablaufzeit**: 1 Stunde (3600 Sekunden)
- **Algorithmus**: HS256

### Beispiel Bearer Token Header
```
GET /api/anlass HTTP/1.1
Host: localhost:5000
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 5. HTTP Status Codes

| Code | Beschreibung |
|------|-------------|
| 200 | OK - Anfrage erfolgreich |
| 201 | Created - Ressource erstellt |
| 204 | No Content - Erfolgreich gelöscht |
| 400 | Bad Request - Ungültige Anfrage |
| 401 | Unauthorized - Authentifizierung erforderlich |
| 403 | Forbidden - Zugriff verweigert |
| 404 | Not Found - Ressource nicht gefunden |
| 409 | Conflict - Datenkonflikt (z.B. Duplikat) |
| 500 | Internal Server Error - Serverfehler |

---

## 6. Fehlerbehandlung

### Standard-Fehler-Response
```json
{
  "success": false,
  "message": "Beschreibung des Fehlers",
  "errorCode": "ERROR_CODE"
}
```

### Häufige Fehler

**Anlass nicht gefunden**:
```json
{
  "success": false,
  "message": "Anlass mit ID 999 nicht gefunden",
  "errorCode": "ANLASS_NOT_FOUND"
}
```

**Ungültige Eingabedaten**:
```json
{
  "success": false,
  "message": "Schützennummer muss eine positive Ganzzahl sein",
  "errorCode": "INVALID_INPUT"
}
```

**Duplikat-Eintrag**:
```json
{
  "success": false,
  "message": "Schütze mit Nummer 1 existiert bereits in diesem Anlass",
  "errorCode": "DUPLICATE_ENTRY"
}
```

---

## 7. Validierungsregeln

### Anlass
- name: erforderlich, max. 200 Zeichen
- datum: erforderlich, zukunftsdatum
- beschreibung: optional, max. 1000 Zeichen
- ort: erforderlich, max. 200 Zeichen

### Schütze
- schuetzennummer: erforderlich, positive Ganzzahl, eindeutig pro Anlass
- vorname: erforderlich, max. 100 Zeichen
- nachname: erforderlich, max. 100 Zeichen
- anlassId: erforderlich, muss existierender Anlass sein

### Schussdaten
- schuetzennummer: erforderlich, positive Ganzzahl
- seriennummer: erforderlich, max. 50 Zeichen
- schuss: erforderlich, positive Ganzzahl
- schussdatum: erforderlich, keine zukünftigen Daten
- ergebnis: erforderlich, 0-10 Punkte

---

## 8. Datenbankschema

```sql
CREATE TABLE Anlass (
  id INT PRIMARY KEY IDENTITY(1,1),
  name NVARCHAR(200) NOT NULL,
  datum DATETIME NOT NULL,
  beschreibung NVARCHAR(1000),
  ort NVARCHAR(200) NOT NULL
);

CREATE TABLE Schuetze (
  id INT PRIMARY KEY IDENTITY(1,1),
  schuetzennummer INT NOT NULL,
  vorname NVARCHAR(100) NOT NULL,
  nachname NVARCHAR(100) NOT NULL,
  anlassId INT NOT NULL,
  FOREIGN KEY (anlassId) REFERENCES Anlass(id),
  UNIQUE (schuetzennummer, anlassId)
);

CREATE TABLE Schussdaten (
  id BIGINT PRIMARY KEY IDENTITY(1,1),
  schuetzennummer INT NOT NULL,
  seriennummer NVARCHAR(50) NOT NULL,
  schuss INT NOT NULL,
  schussdatum DATETIME NOT NULL,
  ergebnis INT NOT NULL,
  CHECK (ergebnis >= 0 AND ergebnis <= 10)
);

CREATE TABLE Benutzer (
  id INT PRIMARY KEY IDENTITY(1,1),
  username NVARCHAR(100) NOT NULL UNIQUE,
  passwordHash NVARCHAR(MAX) NOT NULL
);
```

---

## 9. CORS-Konfiguration

**Erlaubte Origins**:
- http://localhost:3000
- http://localhost:5000

**Erlaubte Methoden**: GET, POST, PUT, DELETE, OPTIONS

**Erlaubte Header**: Content-Type, Authorization

---

## 10. Logging & Monitoring

- Alle API-Calls protokollieren (Timestamp, Benutzer, Methode, Pfad, Status)
- Performance-Metriken erfassen (Response-Zeit)
- Fehler in Error-Log speichern

---

## 11. Konfiguration (Web.config)

```xml
<configuration>
  <appSettings>
    <add key="JwtSecret" value="your-secret-key-min-32-chars"/>
    <add key="JwtExpireMinutes" value="60"/>
    <add key="DatabaseConnectionString" value="Server=localhost;Database=SiusDB;..."/>
  </appSettings>
  <system.webServer>
    <httpProtocol>
      <customHeaders>
        <add name="X-Content-Type-Options" value="nosniff"/>
      </customHeaders>
    </httpProtocol>
  </system.webServer>
</configuration>
```

---

## 12. Verwendungsbeispiele (cURL)

### Login
```bash
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```

### Alle Anlässe abrufen
```bash
curl -X GET http://localhost:5000/api/anlass \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Neuen Anlass erstellen
```bash
curl -X POST http://localhost:5000/api/anlass \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "name":"Landesmeisterschaft 2024",
    "datum":"2024-06-15T10:00:00",
    "beschreibung":"Landesmeisterschaft",
    "ort":"Schützenplatz München"
  }'
```

### Schussdaten batch-importieren
```bash
curl -X POST http://localhost:5000/api/schussdaten \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '[
    {"schuetzennummer":1,"seriennummer":"SN001","schuss":1,"schussdatum":"2024-06-15T10:30:00","ergebnis":10},
    {"schuetzennummer":1,"seriennummer":"SN001","schuss":2,"schussdatum":"2024-06-15T10:31:00","ergebnis":9}
  ]'
```

---

Diese Dokumentation enthält **alles**, was eine KI benötigt, um einen lauffähigen WebServer zu generieren!
