# MySkoda für IP-Symcon

IP-Symcon Bibliothek für die offizielle **MySkoda Public API**.

**Version 2.0 ist bewusst ein harter Schnitt:** Dieses Repository enthält nur noch die MySkoda-Datenanbindung und Fahrzeugsteuerung. Eine Fahrzeug-Visualisierung gehört nicht mehr in das herstellerspezifische API-Modul.

## Funktionsumfang

- Fahrzeugstatus über die offizielle MySkoda Public API
- FIN/VIN und API-Key über die Instanzkonfiguration
- zyklischer Abruf mit Rate-Limit-Beachtung
- SOC, Reichweite, Kilometerstand und Fahrzeugstatus
- Laden, Ladelimit und Lademodus
- Klimatisierung sowie optionale Remote-Funktionen
- API-Key-Ablaufwarnung
- optionale Symcon-Push-Mitteilung über eine explizit ausgewählte Visualisierungsinstanz
- optionale Detail- und Diagnosevariablen
- native Symcon-8.x-Darstellungen ohne Legacy-Variablenprofile
- keine eigene HTML-/Tile-Visualisierung

## Voraussetzungen

- IP-Symcon 8.1 oder neuer
- 17-stellige FIN/VIN
- MySkoda API-Key
- optional S-PIN für Standheizung

## Installation

Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Danach eine Instanz **MySkoda** anlegen.

Die vollständige Dokumentation befindet sich unter [MySkoda/README.md](MySkoda/README.md).

## Trennung von Daten und Visualisierung

MySkoda stellt ausschließlich Datenpunkte und Aktionen bereit. Eine allgemeine Elektroauto-Visualisierung sollte als separates, herstellerunabhängiges Symcon-Modul umgesetzt werden und kann dann ebenso Daten anderer Fahrzeughersteller verwenden.

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

MIT-Lizenz, siehe [LICENSE](LICENSE).
