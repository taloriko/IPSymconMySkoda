# Vorbereitung für den Symcon Module Store

## Vorgeschlagene Bundle ID

`de.taloriko.myskoda`

## Deutsch

**Name:** MySkoda

**Beschreibung:** Bindet Fahrzeuge über die offizielle MySkoda Public API an IP-Symcon an. Das Modul stellt Fahrzeug-, Lade- und Klimadaten als native Statusvariablen bereit und unterstützt – sofern vom Fahrzeug angeboten – Remote-Funktionen wie Laden, Ladelimit, Lademodus, Klimatisierung, Standheizung und aktive Lüftung.

**Versionsinformation 2.0:** Harter Architektur-Schnitt: Die frühere integrierte Fahrzeugvisualisierung wurde vollständig entfernt. MySkoda ist jetzt ein reines Daten- und Steuermodul mit bereinigter Modulstruktur, nativen Symcon-Darstellungen ohne Legacy-Profile oder globale Modul-Templates sowie konsolidierter Mitteilungszielprüfung.

**Dokumentation:** https://github.com/taloriko/IPSymconMySkoda/blob/main/MySkoda/README.md

## English

**Name:** MySkoda

**Description:** Connects vehicles to IP-Symcon through the official MySkoda Public API. The module exposes vehicle, charging and climate data as native status variables and supports remote functions where offered by the vehicle.

**Version information 2.0:** Breaking architecture cleanup: the former integrated vehicle visualization has been removed completely. MySkoda is now a data/control-only module with a simplified module structure, native Symcon presentations without Legacy profiles or module-created global templates, and consolidated notification-target validation.

## Review-Hinweise

- sichtbarer Modulname: `MySkoda`
- Basisklasse: `IPSModuleStrict`
- Instanz kann ohne gültige FIN/API-Key fehlerfrei erstellt werden und zeigt den Konfigurationsfehler über Status/Formular
- Kommunikation erfolgt direkt zur offiziellen HTTPS-API; es gibt keinen lokalen Splitter/IO
- zyklische Abfrage ausschließlich über `RegisterTimer`
- keine externen Ereignisse
- keine individuelle Instanzvisualisierung; `SetVisualizationType(0)`
- keine `module.html`
- keine Legacy-Variablenprofile
- keine globalen, vom Modul erzeugten Präsentations-Templates
- externe Visualisierungsinstanz wird ausschließlich als vom Benutzer gewähltes Ziel für Push-Mitteilungen verwendet
- keine fremden Objekte werden verändert
- Modul-Vendor ist `taloriko`; der Markenhinweis stellt klar, dass es keine offizielle Škoda-Integration ist
- Standortdaten werden nur bei profilbezogener Freigabe geliefert; fehlen sie, setzt das Modul Koordinaten auf `0.0 / 0.0`
