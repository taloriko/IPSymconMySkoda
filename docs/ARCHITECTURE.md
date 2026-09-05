# Architektur

## Modulzweck

MySkoda ist ein Device-/API-Modul für IP-Symcon. Es liest Fahrzeugdaten aus der offiziellen MySkoda Public API, stellt diese als native Symcon-Variablen bereit und führt freigegebene Remote-Kommandos aus.

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

## Verantwortlichkeiten

- `CoreTrait.php`: Lebenszyklus, Konfiguration, zyklische Abfrage und öffentliche Modulmethoden
- `VariablesTrait.php`: Registrierung der Statusvariablen, Darstellungen und Wertübernahme
- `ApiTrait.php`: HTTPS-Kommunikation, Rate-Limit-Auswertung und Remote-Kommandos
- `OpenApiTrait.php`: Auswertung der OpenAPI-Definition für unterstützte Ladeoperationen
- `NotificationTrait.php`: API-Key-Ablaufwarnung und Versand über eine ausgewählte Symcon-Visualisierungsinstanz
- `HelpersTrait.php`: interne Hilfsfunktionen

## Datenfluss

1. Die Instanzkonfiguration liefert FIN/VIN, API-Key und optionale Steuerungsparameter.
2. Der interne Timer ruft zyklisch den Fahrzeugstatus über die MySkoda Public API ab.
3. Antwort-Header aktualisieren Rate-Limit-, Retry-After- und API-Key-Ablaufinformationen.
4. Fahrzeugdaten werden in native Symcon-Variablen übernommen.
5. Die vollständige API-Antwort wird intern gespeichert und kann über `MSKODA_GetRawData()` abgefragt werden.
6. Bedienaktionen senden die jeweilige Remote-Operation an die API, sofern Remote-Steuerung aktiviert und die Operation verfügbar ist.

## Symcon-Integration

- Basisklasse `IPSModuleStrict`
- zyklische Abfragen über `RegisterTimer`
- keine externen Ereignisse
- keine individuelle Instanzvisualisierung (`SetVisualizationType(0)`)
- keine Legacy-Variablenprofile
- keine vom Modul erzeugten globalen Darstellungs-Templates
- Statusvariablen direkt unterhalb der Modulinstanz
- externe Visualisierungsinstanz nur als explizit gewähltes Ziel für Push-Mitteilungen
- keine Änderung fremder Objekte oder Instanzen

## API-Kommunikation

Die Kommunikation erfolgt direkt per HTTPS zur MySkoda Public API. Der API-Key wird im HTTP-Header `X-API-Key` übertragen. Redirects werden nicht automatisch verfolgt.

Das Modul berücksichtigt unter anderem:

- `RateLimit-Limit`
- `RateLimit-Remaining`
- `RateLimit-Reset`
- `Retry-After`
- `X-API-Key-Expires-At`

Für automatische Statusabfragen wird eine Reserve für manuelle Bedienaktionen berücksichtigt.

## Standortdaten

Standortdaten werden nur verarbeitet, wenn sie von der API geliefert werden. Fehlen Koordinaten, werden `Latitude` und `Longitude` auf `0.0` gesetzt. Die Standortfreigabe erfolgt profilbezogen in MySkoda.
