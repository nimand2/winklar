# JSON-Export für die Finalsoftware

Download: `/anlass/{id}/final/{kategorie}/export` (angemeldet). UTF-8, Schema-Version 1. `kategorie` ist `u18` oder `ue18`. Jeder Export enthält genau einen separaten Final.

Der Export ist eine Momentaufnahme der aktuellen Rangliste. Er enthält ausschliesslich Schützen innerhalb der eingestellten Finalplätze, deren gespeicherter Teilnahmewunsch aktiviert ist. Absagen lösen kein Nachrücken aus. Die Reihenfolge innerhalb des gewählten Finals ist der Qualifikationsrang. Diese Reihenfolge vergibt keine Scheiben oder Finalstartplätze.

## Aufbau

- `schema_version`: Version des Exportformats, aktuell `1`.
- `export_typ`: `final_teilnehmer`.
- `final`: Eindeutige Final-ID (`{anlass_id}-{kategorie}`), Kategorie, Name und Zahl der Plätze.
- `erstellt_am`: ISO-8601-Zeitstempel mit Zeitzone Europe/Zurich.
- `anlass`: ID, Name, Kurzname, Start- und Enddatum.
- `final_regeln`: Qualifikationsstich-ID, Plätze U18/Ü18, Wertungs- und Altersregel, Nachrückverhalten.
- `qualifikationsstich`: Konfiguration des gewählten Stiches, inklusive Name, Anzeige-ID, Scheibe, Wertigkeit, Schusszahl und Preis. Diese Konfiguration beschreibt die Qualifikation; das Finalprogramm legt die Finalserien fest.
- `teilnehmer_anzahl`: Anzahl der Einträge in `teilnehmer`.
- `teilnehmer`: Liste mit den unten aufgeführten Feldern.

## Teilnehmerfelder

| Felder | Bedeutung |
| --- | --- |
| `standblatt_nummer`, `start_nummer` | Identische, bestehende Standblatt-ID; für Zuordnung zu den vorhandenen Anlagendaten. |
| `barcode`, `barcode_typ` | SIUS-Barcode als Zeichenfolge, Interleaved 2 of 5, identisch zum gedruckten Standblatt. Bei Nummern über 999998 ist `barcode` null; die Druckansicht zeigt einen Fehler. |
| `adresse_id`, `vorname`, `nachname` | Interne Personenreferenz und Name. |
| `geburtsdatum`, `jahrgang` | Geburtstag als YYYY-MM-DD und Jahrgang. |
| `kategorie`, `kategorie_label` | `u18` oder `ue18` und sichtbare Bezeichnung U18/Ü18. |
| `verein`, `lizenz` | Verein und Lizenzangabe. |
| `strasse`, `postfach`, `plz`, `ort`, `nation`, `email`, `telefon` | Hinterlegte Adress- und Kontaktdaten; fehlende optionale Angaben können null sein. |
| `qualifiziert`, `final_teilnahme` | Beide true für exportierte Teilnehmer. |
| `qualifikationsrang`, `qualifikationssumme` | Rang innerhalb der Alterskategorie und Summe der besten zwei Resultate. |
| `bestes_resultat`, `qualifikationsresultate`, `qualifikationsschuesse` | Bestes Resultat, absteigend sortierte Serienresultate, Zahl der Schüsse. |
| `standblatt_datum`, `standblatt_kosten` | Datum und Kosten des bestehenden Standblatts. |

Es werden keine neuen Standblattnummern erzeugt. Der Sammeldruck gibt die bestehenden Standblätter mit ihren gelösten Stichen und unveränderten Barcodes erneut aus. Passwörter und interne Personennotizen werden nicht exportiert.

Prüfung der Auswahl, Barcodes und Druckvorlagen: `php tests/final.php`.
