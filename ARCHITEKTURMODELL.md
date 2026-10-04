# Architekturmodell der Webapp

```mermaid
flowchart LR
    user[Benutzer im Browser]
    client[Externer Schiess-Client]

    subgraph publicLayer["Public Entry Layer"]
        index["public/index.php"]
        htaccess["public/.htaccess"]
    end

    subgraph bootstrapLayer["Bootstrap & Routing"]
        bootstrap["app/bootstrap.php<br/>Autoloading und Service-Factories"]
        router["App\\Core\\Router<br/>Routen-Matching"]
    end

    subgraph coreLayer["Core"]
        controllerBase["App\\Core\\Controller"]
        view["App\\Core\\View"]
        response["App\\Core\\Response"]
        jsonResponse["App\\Core\\JsonResponse"]
        session["App\\Core\\Session"]
        url["App\\Core\\Url"]
        database["App\\Core\\Database"]
    end

    subgraph controllerLayer["Controller Layer"]
        authController["AuthController"]
        dashboardController["DashboardController"]
        homeController["HomeController"]
        anlassController["AnlassController"]
        adressenController["AdressenController"]
        loesenController["LoesenController"]
        abrechnenController["AbrechnenController"]
        apiController["ClientApiController"]
    end

    subgraph serviceLayer["Service Layer"]
        authService["AuthService"]
        anlassService["AnlassService"]
        tokenService["ApiTokenService"]
        clientApiService["ClientApiService"]
        ranglistenService["RanglistenService"]
        kassenService["KassenService"]
        abrechnungsService["AbrechnungsService"]
    end

    subgraph modelLayer["Model Layer"]
        userModel["User"]
        rememberTokenModel["RememberToken"]
        anlassModel["Anlass"]
        adressenModel["Adressen"]
        plzModel["Plz"]
        standblattModel["Standblatt"]
        stichModel["Stich"]
        gabenModel["Gaben"]
        schussdatenModel["Schussdaten"]
        limitsModel["Auszeichnungslimitten"]
    end

    subgraph viewLayer["View Layer"]
        views["app/Views/**/*.php<br/>HTML/PHP-Templates"]
        partials["app/Views/partials/*.php"]
    end

    subgraph dataLayer["Data Layer"]
        mysql[("MySQL-Datenbank")]
        csv["AMTOVZ_CSV_LV95.csv<br/>PLZ/Adressdaten"]
    end

    user -->|HTTP Request| htaccess
    htaccess --> index
    client -->|JSON API| index

    index --> bootstrap
    bootstrap --> router
    router -->|dispatch| controllerLayer

    controllerLayer --> controllerBase
    controllerBase --> view
    controllerLayer --> response
    apiController --> jsonResponse

    controllerLayer --> serviceLayer
    controllerLayer --> modelLayer

    authController --> session
    authService --> session
    response --> url
    views --> url

    serviceLayer --> modelLayer
    modelLayer --> database
    database --> mysql
    plzModel -. optional import/reference .-> csv

    view --> views
    views --> partials

    authService --> userModel
    authService --> rememberTokenModel
    anlassService --> anlassModel
    clientApiService --> anlassModel
    clientApiService --> standblattModel
    clientApiService --> schussdatenModel
    ranglistenService --> stichModel
    ranglistenService --> standblattModel
    ranglistenService --> schussdatenModel
    kassenService --> standblattModel
    kassenService --> gabenModel
    abrechnungsService --> standblattModel
    abrechnungsService --> schussdatenModel
    abrechnungsService --> gabenModel

    anlassController --> ranglistenService
    anlassController --> kassenService
    loesenController --> standblattModel
    abrechnenController --> abrechnungsService
```

## Kurzbeschreibung

Die Anwendung ist als klassische PHP-MVC-Webapp aufgebaut:

- `public/index.php` ist der zentrale Einstiegspunkt fuer alle Web- und API-Anfragen.
- `app/bootstrap.php` registriert Autoloading und erzeugt wiederverwendete Services.
- `Router` ordnet HTTP-Routen den passenden Controllern zu.
- Controller verarbeiten Requests, pruefen Login/Parameter und delegieren Logik an Services oder Models.
- Services buendeln fachliche Ablaufe wie Authentifizierung, Ranglisten, Kasse, API-Import und Abrechnung.
- Models kapseln SQL-Zugriffe und nutzen zentral `App\Core\Database`.
- Views sind PHP-Templates und werden ueber `App\Core\View` gerendert.

## 5.4 Datenfluss SIUS-Schnittstelle

Der Datenaustausch mit der Schiessanlage erfolgt ueber CSV-Dateien, die von der SIUS-Software Si-usData erzeugt werden. Fuer jeden Schuss schreibt SIUS einen eigenen Datensatz in die Exportdatei. Dieser Datensatz enthaelt unter anderem die Startnummer, die Primaerwertung, den Zeitpunkt des Schusses sowie die externe Nummer des geschossenen Stichs.

Der SIUS-Client ueberwacht die CSV-Datei laufend. Sobald neue Schussdaten vorhanden sind, liest der Client die relevanten Felder aus und uebertraegt sie ueber die JSON-Schnittstelle an die Webapplikation. Im Backend nimmt der `ClientApiController` die Daten entgegen und gibt sie an den `ClientApiService` weiter. Dort werden die Schussdaten validiert, fuer den Import vorbereitet und ueber das Model `Schussdaten` in der Datenbank gespeichert. Anschliessend koennen die Daten ueber Startnummer und externe Nummer dem passenden Standblatt, Schuetzen und Stich zugeordnet werden.

Wichtig ist, dass die CSV-Datei erst gelesen wird, nachdem SIUS mindestens den ersten Datensatz vollstaendig geschrieben hat. Wird die Datei zu frueh geoeffnet oder gelesen, kann es zu Schreibkonflikten mit der SIUS-Software kommen. Diese Problematik wurde bereits praktisch an einem Volksschiessen in Lachen beobachtet und muss beim Betrieb des Clients beruecksichtigt werden.

```mermaid
flowchart TD
    sius["SIUS-Schiessanlage"]
    siusData["SIUS-Software<br/>Si-usData"]
    csvFile[("CSV-Exportdatei")]
    waitCheck{"Erster Datensatz<br/>vollstaendig geschrieben?"}
    siusClient["SIUS-Client<br/>Dateiueberwachung"]
    parse["CSV-Datensatz lesen<br/>StartNr, Primaerwertung,<br/>Schusszeit, externe Nummer"]
    api["POST /api/anlaesse/{id}/shots/import<br/>JSON-Request"]
    controller["ClientApiController<br/>API-Endpunkt"]
    service["ClientApiService<br/>Validierung und Importlogik"]
    model["Schussdaten Model"]
    database[("MySQL<br/>Tabelle schussdaten")]
    assignment["Zuordnung im Backend<br/>Startnummer -> Standblatt/Schuetze<br/>externe Nummer -> Stich"]
    views["Webapp-Auswertungen<br/>Abrechnung und Rangliste"]
    conflict["Risiko: Schreibkonflikt<br/>wenn zu frueh gelesen wird"]

    sius -->|Schussereignis| siusData
    siusData -->|schreibt pro Schuss<br/>einen CSV-Datensatz| csvFile
    csvFile --> waitCheck
    waitCheck -->|Nein| conflict
    conflict -->|warten und erneut pruefen| waitCheck
    waitCheck -->|Ja| siusClient
    siusClient --> parse
    parse --> api
    api --> controller
    controller --> service
    service --> model
    model --> database
    service --> assignment
    database --> assignment
    assignment --> views
```

Der fachliche Nutzen dieses Datenflusses liegt darin, dass Resultate nicht manuell in der Webapplikation erfasst werden muessen. Die von SIUS gelieferten Schussdaten werden automatisiert importiert und stehen danach fuer die Funktionen FA-01 und FA-04 zur Verfuegung, insbesondere fuer die Zuordnung zu geloesten Standblaettern, die Abrechnung und die Ranglistenauswertung.
