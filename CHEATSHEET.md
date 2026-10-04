# MVC Cheat Sheet

Kompakte Orientierung zur PHP-MVC-Webapp: Welche Schicht wofuer zuständig ist, wo Requests starten und wie Login, Views, Datenbankzugriffe und API-Imports zusammenspielen.

## 1. Projektstruktur

| Bereich | Aufgabe | Wichtige Dateien |
| --- | --- | --- |
| Public Entry | Alle HTTP-Requests landen zuerst hier. | [public/index.php](public/index.php), [public/.htaccess](public/.htaccess) |
| Bootstrap | Konfiguration, Autoloading und Service-Factories. | [app/bootstrap.php](app/bootstrap.php), [config.php](config.php) |
| Core | Technische Basis: Routing, Rendering, Session, Redirects, JSON, DB. | [app/Core](app/Core) |
| Controllers | Nehmen Requests entgegen, prüfen Eingaben/Login und delegieren weiter. | [app/Controllers](app/Controllers) |
| Services | Fachliche Abläufe und wiederverwendbare Business-Logik. | [app/Services](app/Services) |
| Models | SQL-Zugriff und Datenoperationen. | [app/Models](app/Models) |
| Views | PHP/HTML-Templates für Browserseiten. | [app/Views](app/Views) |

## 2. MVC in diesem Projekt

**Model**

- Kapselt Datenbankzugriffe und SQL-Queries.
- Nutzt zentral `App\Core\Database` für PDO-Verbindungen.
- Beispiele: [app/Models/User.php](app/Models/User.php), [app/Models/Standblatt.php](app/Models/Standblatt.php), [app/Models/Schussdaten.php](app/Models/Schussdaten.php).

**View**

- Enthält HTML/PHP-Templates ohne zentrale Geschäftslogik.
- Wird über `App\Core\View::render()` geladen.
- Beispiel: `render('auth/login')` lädt [app/Views/auth/login.php](app/Views/auth/login.php).

**Controller**

- Verbindet Route, Requestdaten, Services/Models und Response.
- Führt Validierung, Login-Prüfungen und Redirects aus.
- Beispiele: [app/Controllers/AuthController.php](app/Controllers/AuthController.php), [app/Controllers/AnlassController.php](app/Controllers/AnlassController.php), [app/Controllers/ClientApiController.php](app/Controllers/ClientApiController.php).

**Service**

- Bündelt fachliche Abläufe, die nicht direkt in Controller oder Model gehören.
- Beispiele: Login/Remember-Me in [app/Services/AuthService.php](app/Services/AuthService.php), Ranglisten in [app/Services/RanglistenService.php](app/Services/RanglistenService.php), API-Import in [app/Services/ClientApiService.php](app/Services/ClientApiService.php).

## 3. Request-Lebenszyklus

1. Browser oder Client ruft eine URL auf, z. B. `GET /login`.
2. Webserver leitet den Request an [public/index.php](public/index.php).
3. `index.php` lädt [app/bootstrap.php](app/bootstrap.php).
4. `bootstrap.php` lädt [config.php](config.php), registriert den Autoloader und stellt Factory-Funktionen wie `app_auth()` bereit.
5. `index.php` erzeugt Controller und registriert Routen im `Router`.
6. `Router::dispatch()` normalisiert die URL, matched die Route und ruft den Handler auf.
7. Der Controller nutzt Services/Models.
8. Die Antwort ist entweder eine View, ein Redirect, JSON oder ein 404.

## 4. Routing

Routen werden zentral in [public/index.php](public/index.php) definiert.

Beispiele:

| Route | Controller-Methode | Zweck |
| --- | --- | --- |
| `GET /` | `HomeController::index()` | Startseite |
| `GET /login` | `AuthController::showLogin()` | Loginformular |
| `POST /login` | `AuthController::login()` | Login verarbeiten |
| `GET /dashboard` | `DashboardController::index()` | Dashboard |
| `GET /anlass/{id}` | `AnlassController::show()` | Anlass anzeigen |
| `POST /api/login` | `ClientApiController::login()` | API-Token erstellen |
| `POST /api/anlaesse/{id}/shots/import` | `ClientApiController::importShots()` | Schussdaten importieren |

Der Router unterstützt Pfadparameter wie `{id}`. Diese werden als Array an die Controller-Methode übergeben, z. B. `['id' => '12']`.

## 5. Login-Flow

### GET `/login`

1. `Router` ruft `AuthController::showLogin()` auf.
2. `AuthController` prüft `AuthService::isLoggedIn()`.
3. Wenn der Benutzer bereits angemeldet ist: Redirect zu `/dashboard`.
4. Sonst rendert der Controller `auth/login`.
5. `View::render('auth/login')` lädt [app/Views/auth/login.php](app/Views/auth/login.php).

### POST `/login`

1. `AuthController::login()` liest `login`, `password` und optional `remember_me` aus `$_POST`.
2. Leere Felder erzeugen eine Flash-Meldung und Redirect zu `/login`.
3. `AuthService::attemptLogin($login, $password, $rememberMe)` sucht den Benutzer über `User::findByLogin()`.
4. Das Passwort wird mit `password_verify()` gegen `password_hash` geprüft.
5. Bei Erfolg ruft der Service `loginUser()` und danach intern `finalizeLogin()` auf.
6. `finalizeLogin()` regeneriert die Session-ID und setzt:
   - `$_SESSION['user_id']`
   - `$_SESSION['username']`
7. Bei aktivem Remember-Me wird ein neues Selector/Validator-Token erstellt, in der Datenbank gespeichert und als Cookie gesetzt.
8. Der Controller setzt eine Erfolgsmeldung und leitet zu `/dashboard` weiter.

## 6. Remember-Me

Remember-Me liegt in [app/Services/AuthService.php](app/Services/AuthService.php) und [app/Models/RememberToken.php](app/Models/RememberToken.php).

- Cookie-Format: `selector:validator`.
- Der Selector identifiziert den DB-Eintrag.
- Der Validator wird im Cookie gespeichert, in der Datenbank aber nur als SHA-256-Hash abgelegt.
- Beim automatischen Login wird der Validator mit `hash_equals()` geprüft.
- Nach erfolgreicher Prüfung wird der alte Token gelöscht und ein neuer Token erstellt.
- Beim Logout werden vorhandene Remember-Me-Tokens entfernt und das Cookie gelöscht.

## 7. API-Flow für SIUS-Client

Die externe Schnittstelle ist von der Browser-Session getrennt und nutzt Bearer-Tokens.

1. Client sendet `POST /api/login` mit Benutzername und Passwort.
2. `ClientApiController::login()` prüft die Anmeldedaten über `AuthService::findUserByLogin()`.
3. `ApiTokenService::createToken()` erzeugt einen Token.
4. Weitere API-Requests senden `Authorization: Bearer <token>`.
5. `ClientApiController::requireApiUser()` validiert den Token.
6. `POST /api/anlaesse/{id}/shots/import` gibt Schussdaten an `ClientApiService::importShots()` weiter.
7. `ClientApiService` validiert und speichert Daten über [app/Models/Schussdaten.php](app/Models/Schussdaten.php).

Wichtig: Der SIUS-Client sollte die CSV-Datei erst lesen, wenn SIUS den ersten Datensatz vollständig geschrieben hat. Sonst können Schreib- oder Lesekonflikte entstehen.

## 8. Datenbankzugriff

- [app/Core/Database.php](app/Core/Database.php) erstellt die zentrale PDO-Verbindung.
- Models verwenden vorbereitete SQL-Statements.
- Tabellenstruktur und Startpunkt für die DB liegen in [schema.sql](schema.sql).
- Beispielkette: `AuthService -> User::findByLogin() -> Database::connection() -> MySQL`.

## 9. View-Rendering

1. Controller ruft `$this->render('pfad/view', $data)` auf.
2. `Controller::render()` delegiert an `View::render()`.
3. `View` sucht die Datei unter `app/Views/<pfad/view>.php`.
4. Übergebene Daten werden mit `extract($data, EXTR_SKIP)` als lokale Variablen verfügbar.

Beispiel:

```php
$this->render('auth/login', [
    'flash' => Session::pullFlash(),
]);
```

lädt:

```text
app/Views/auth/login.php
```

## 10. Schnellantworten

| Frage | Antwort |
| --- | --- |
| Wo startet ein Webrequest? | [public/index.php](public/index.php) |
| Wo stehen die Routen? | [public/index.php](public/index.php) |
| Wer matched URL zu Controller? | [app/Core/Router.php](app/Core/Router.php) |
| Wo wird die Session gestartet? | `AuthService::boot()` über `Session::start()` |
| Wo ist die Login-Logik? | [app/Controllers/AuthController.php](app/Controllers/AuthController.php) und [app/Services/AuthService.php](app/Services/AuthService.php) |
| Wo werden Views geladen? | [app/Core/View.php](app/Core/View.php) |
| Wo wird PDO erzeugt? | [app/Core/Database.php](app/Core/Database.php) |
| Wo ist die SIUS/API-Importlogik? | [app/Controllers/ClientApiController.php](app/Controllers/ClientApiController.php) und [app/Services/ClientApiService.php](app/Services/ClientApiService.php) |

## 11. Typische Änderungspunkte

| Aufgabe | Meist relevante Datei |
| --- | --- |
| Neue Webseite/Route hinzufügen | [public/index.php](public/index.php), passender Controller, passende View |
| Login-Verhalten ändern | [app/Services/AuthService.php](app/Services/AuthService.php), [app/Controllers/AuthController.php](app/Controllers/AuthController.php) |
| SQL-Abfrage ändern | Passendes Model in [app/Models](app/Models) |
| Darstellung ändern | Passende View in [app/Views](app/Views) und ggf. [public/assets/css/app.css](public/assets/css/app.css) |
| API-Endpunkt ändern | [app/Controllers/ClientApiController.php](app/Controllers/ClientApiController.php), [app/Services/ClientApiService.php](app/Services/ClientApiService.php) |
| Rangliste/Kasse/Abrechnung anpassen | Passender Service in [app/Services](app/Services) |

## 12. Merksatz

`public/index.php` entscheidet, welcher Controller dran ist. Der Controller versteht den Request. Services erledigen fachliche Abläufe. Models sprechen mit der Datenbank. Views zeigen das Ergebnis.
