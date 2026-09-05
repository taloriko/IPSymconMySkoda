# Architektur

## Ziel

Eine MySkoda-Instanz repräsentiert genau ein Fahrzeug bzw. eine FIN.

## Symcon-Struktur

Das Repository folgt der Symcon-Bibliotheksstruktur: `library.json` liegt im Repository-Root. Der Modulordner `MySkoda` enthält `module.php`, `module.json`, `form.json` und `locale.json`.

## Datenmodell

Der komplette Fahrzeug-Response wird intern als Instanzattribut gespeichert. Der Objektbaum enthält nur eine kleine, stabile Auswahl alltagstauglicher Variablen. Detailvariablen sind optional.

## Darstellungen

Das Modul setzt Symcon 8.1+ voraus und nutzt `IPSModuleStrict` sowie die neuen Variablendarstellungen. Vorhandene Symcon-Standardvorlagen werden bevorzugt. Für Boolean-Zustände werden gezielt wenige eigene Profile angelegt, weil die Standardanzeige `An/Aus` für Fahrzeugzustände zu unklar ist. Alle modul-eigenen Profile liegen deshalb eindeutig im Namensraum `MySkoda.*`.

Die drei Boolean-Profile bilden unterschiedliche Bedeutungen ab:

- `MySkoda.YesNo.GoodTrue`: Ja = grün, Nein = orange
- `MySkoda.YesNo.GoodFalse`: Nein = grün, Ja = orange
- `MySkoda.YesNo.ActiveTrue`: Ja = grün, Nein = neutral

Rot bleibt bewusst echten Störungen/Fehlerzuständen vorbehalten.

## Rate-Limit

Das Modul liest `RateLimit-Limit`, `RateLimit-Remaining`, `RateLimit-Reset` und `Retry-After`. Automatische Abrufe werden mit einer kleinen Reserve gestoppt, damit Remote-Befehle möglich bleiben.

## Remote-Befehle

Start/Stop für Laden und Klima sowie Standheizung/Lüftung verwenden die Public-API-Pfade. Neuere Ladeoperationen für Limit, Modus und Ladeprofil werden über die aktuelle OpenAPI-Definition und deren Request-Schemas aufgelöst.

## Lokalisierung

Englisch ist die Quellsprache der Modultexte. `MySkoda/locale.json` enthält die deutschen Übersetzungen für Konfigurationsmaske, Variablennamen und relevante Benutzermeldungen.

## Source layout

Ab Version 1.1 bleibt `MySkoda/module.php` bewusst klein und lädt thematisch getrennte Traits aus `MySkoda/src/`:

- `CoreTrait.php` – Lebenszyklus, Aktionen und öffentliche Modulmethoden
- `VariablesTrait.php` – Variablen, Darstellungen, Boolean-Profile und Datenübernahme
- `ApiTrait.php` – HTTP, Rate-Limit und Fehlerbehandlung
- `OpenApiTrait.php` – dynamische OpenAPI-Auswertung
- `VisualizationTrait.php` – kompakte Elektroauto-Kachel und Anzeigeaufbereitung
- `NotificationTrait.php` – API-Key-Ablaufwarnung und optionale Symcon-Push-Mitteilungen
- `HelpersTrait.php` – allgemeine Hilfsfunktionen

Die Aufteilung ist rein intern. Prefix, Modul-ID und öffentliche `MSKODA_*`-Funktionen bleiben unverändert.

## Verbindungsrückmeldung

Die Konfiguration wird über `GetConfigurationForm()` dynamisch aus der `form.json` aufgebaut. Beim erstmaligen Eintragen oder Ändern von FIN/API-Token wird die Verbindung direkt geprüft. Der Status wird als verständliche Rückmeldung im offenen Instanzformular angezeigt und zusätzlich über den normalen Instanzstatus abgebildet.

## API-Key Ablauf

Das Ablaufdatum wird aus `X-API-Key-Expires-At` übernommen. `ApiKeyWarning` wird bei höchstens 30 Tagen Restlaufzeit aktiv. Eine optionale Push-Mitteilung kann über eine konfigurierte Kachel-Visualisierungs- oder WebFront-Instanz gesendet werden. Fehlgeschlagene Sendeversuche werden höchstens einmal täglich wiederholt.

## Standortdaten

Die Fahrzeugposition wird nur verarbeitet, wenn die MySkoda API sie für das verwendete Profil liefert. Die Standortfreigabe ist profilbezogen und muss bei mehreren Profilen für dasselbe Fahrzeug je Profil separat aktiviert werden. Fehlen Koordinaten, schreibt das Modul `0.0 / 0.0`, damit keine alten Standortdaten bestehen bleiben.
