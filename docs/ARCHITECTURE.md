# Architektur

## Zielbild ab 2.0

MySkoda ist ausschließlich ein **Device-/API-Modul**. Es liest Fahrzeugdaten, stellt Statusvariablen bereit und führt freigegebene Remote-Kommandos aus. Es enthält bewusst keine eigene Fahrzeugvisualisierung.

## Modulstruktur

```text
MySkoda/
├── module.php
├── module.json
├── form.json
├── locale.json
└── src/
    ├── CoreTrait.php
    ├── VariablesTrait.php
    ├── ApiTrait.php
    ├── OpenApiTrait.php
    ├── NotificationTrait.php
    └── HelpersTrait.php
```

Die früheren versionsbezogenen Traits und Visualisierungsschichten wurden in 2.0 entfernt.

## Verantwortlichkeiten

- `CoreTrait`: Lebenszyklus, Konfiguration, Abfrage, öffentliche Modulmethoden
- `VariablesTrait`: Statusvariablen, native Darstellungen, Wertübernahme
- `ApiTrait`: HTTP, Rate-Limit, Remote-Kommandos
- `OpenApiTrait`: dynamische Auflösung neuerer API-Operationen
- `NotificationTrait`: API-Key-Warnung und explizit ausgewähltes Symcon-Mitteilungsziel
- `HelpersTrait`: kleine Hilfsfunktionen

## Symcon-Konformität

- `IPSModuleStrict`
- keine externen Ereignisse; zyklische Abfrage über `RegisterTimer`
- keine eigene HTML-SDK-Visualisierung (`SetVisualizationType(0)`)
- keine Legacy-Variablenprofile
- keine vom Modul erzeugten globalen Darstellungs-Templates
- externe Mitteilungsinstanz nur nach expliziter Auswahl im Konfigurationsformular
- keine Manipulation fremder Objekte
- Statusvariablen liegen direkt unterhalb der Instanz

## Visualisierung

Eine Elektroauto-Visualisierung soll als separates Modul entwickelt werden. Dadurch ist die Darstellung nicht an MySkoda gebunden und kann Datenpunkte anderer Fahrzeugmodule ebenfalls verwenden.
