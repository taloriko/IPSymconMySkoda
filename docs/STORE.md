# Vorbereitung für den Symcon Module Store

## Vorgeschlagene Bundle ID

`de.taloriko.myskoda`

## Deutsch

**Name:** MySkoda

**Beschreibung:** Bindet Fahrzeuge über die offizielle MySkoda Public API an IP-Symcon an. Das Modul stellt Fahrzeug-, Lade- und Klimadaten als native Statusvariablen bereit und unterstützt – sofern vom Fahrzeug angeboten – Remote-Funktionen wie Laden, Ladelimit, Lademodus, Klimatisierung, Standheizung und aktive Lüftung.

**Versionsinformation 2.1:** Funktional gruppierte Statusvariablen, native Symcon-Icons für passende Datenpunkte und lokalisierte, lesbare Ladezustände bei unverändert erhaltenem API-Rohwert.

**Dokumentation:** https://github.com/taloriko/IPSymconMySkoda/blob/main/MySkoda/README.md

## English

**Name:** MySkoda

**Description:** Connects vehicles to IP-Symcon through the official MySkoda Public API. The module exposes vehicle, charging and climate data as native status variables and supports remote functions such as charging, charging limit, charging mode, air conditioning, auxiliary heating and active ventilation where offered by the vehicle.

**Version information 2.1:** Functionally grouped status variables, native Symcon icons for suitable datapoints and localized, readable charging states while preserving the original API value.

**Documentation:** https://github.com/taloriko/IPSymconMySkoda/blob/main/MySkoda/README.md

## Review-Hinweise

- sichtbarer Modulname: `MySkoda`
- Basisklasse: `IPSModuleStrict`
- Instanz kann ohne gültige FIN/API-Key fehlerfrei erstellt werden und zeigt den Konfigurationsstatus im Formular
- Kommunikation direkt zur offiziellen HTTPS-API; kein lokaler Splitter/IO erforderlich
- zyklische Abfrage über `RegisterTimer`
- keine externen Ereignisse
- keine individuelle Instanzvisualisierung; `SetVisualizationType(0)`
- keine `module.html`
- keine Legacy-Variablenprofile
- keine globalen, vom Modul erzeugten Präsentations-Templates
- externe Visualisierungsinstanz ausschließlich als vom Benutzer gewähltes Ziel für Push-Mitteilungen
- keine fremden Objekte werden verändert
- Modul-Vendor `taloriko`
- Markenhinweis in der Dokumentation weist auf die unabhängige Community-Integration hin
- Standortdaten werden nur verarbeitet, wenn sie von der API geliefert werden; fehlende Koordinaten werden auf `0.0 / 0.0` gesetzt
