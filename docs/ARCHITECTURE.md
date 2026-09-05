# Architektur

## Ziel

Eine MySkoda-Instanz repräsentiert genau ein Fahrzeug bzw. eine FIN.

## Symcon-Struktur

Das Repository folgt der Symcon-Bibliotheksstruktur: `library.json` liegt im Repository-Root. Der Modulordner `MySkoda` enthält `module.php`, `module.json`, `form.json` und `locale.json`.

## Datenmodell

Der komplette Fahrzeug-Response wird intern als Instanzattribut gespeichert. Der Objektbaum enthält nur eine kleine, stabile Auswahl alltagstauglicher Variablen. Detailvariablen sind optional.

## Darstellungen

Das Modul setzt Symcon 8.1+ voraus und nutzt `IPSModuleStrict` sowie die neuen Variablendarstellungen. Vorhandene Symcon-Standardvorlagen werden bevorzugt. Legacy-Profile werden nicht angelegt. Sollten zukünftig persistente eigene Profile notwendig sein, werden diese minimiert und mit `MySkoda.*` benannt.

## Rate-Limit

Das Modul liest `RateLimit-Limit`, `RateLimit-Remaining`, `RateLimit-Reset` und `Retry-After`. Automatische Abrufe werden mit einer kleinen Reserve gestoppt, damit Remote-Befehle möglich bleiben.

## Remote-Befehle

Start/Stop für Laden und Klima sowie Standheizung/Lüftung verwenden die Public-API-Pfade. Neuere Ladeoperationen für Limit, Modus und Ladeprofil werden über die aktuelle OpenAPI-Definition und deren Request-Schemas aufgelöst.

## Lokalisierung

Englisch ist die Quellsprache der Modultexte. `MySkoda/locale.json` enthält die deutschen Übersetzungen für Konfigurationsmaske, Variablennamen und relevante Benutzermeldungen.

## Source layout

Ab Version 1.1 bleibt `MySkoda/module.php` bewusst klein und lädt thematisch getrennte Traits aus `MySkoda/src/`:

- `CoreTrait.php` – Lebenszyklus, Aktionen und öffentliche Modulmethoden
- `VariablesTrait.php` – Variablen, Darstellungen und Datenübernahme
- `ApiTrait.php` – HTTP, Rate-Limit und Fehlerbehandlung
- `OpenApiTrait.php` – dynamische OpenAPI-Auswertung
- `VisualizationTrait.php` – Elektroauto-Kachel und Anzeigeaufbereitung
- `HelpersTrait.php` – allgemeine Hilfsfunktionen

Die Aufteilung ist rein intern. Prefix, Modul-ID und öffentliche `MSKODA_*`-Funktionen bleiben unverändert.
