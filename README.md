# MySkoda für IP-Symcon

IP-Symcon-Modul zur Anbindung eines Fahrzeugs an die offizielle **MySkoda Public API**.

## Funktionen

- Fahrzeugdaten über FIN/VIN und MySkoda API-Key
- zyklischer Abruf mit Berücksichtigung von Rate-Limit und `Retry-After`
- Ladezustand, Reichweite, Kilometerstand und Fahrzeugstatus
- Ladezustand, Ladeleistung, Ladelimit und Lademodus
- Klimatisierung sowie unterstützte Remote-Funktionen
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail-, Standort- und Diagnosevariablen
- optionale API-Key-Ablaufwarnung per Symcon-Mitteilung
- optionaler Ladeverlauf mit Symcon Archive Control
- stabile Variablen-Idents als Schnittstelle für Skripte und Visualisierungsmodule

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- optional S-PIN für Standheizung
- aktive MySkoda/Škoda-Connect-Dienste für die jeweils verwendete Fahrzeugfunktion

## Installation

Das Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Danach eine Instanz **MySkoda** anlegen und FIN/VIN sowie API-Token konfigurieren.

Die vollständige Dokumentation befindet sich unter [MySkoda/README.md](MySkoda/README.md).

## API-Key

In der **MySkoda App**:

**Profil → Smart Home → Create Key → Namen vergeben → FIN/VIN und API-Token in die Symcon-Instanz übernehmen.**

Nach dem Übernehmen prüft das Modul die Verbindung zur MySkoda Public API.

## Schnittstelle für Visualisierungen

Alle Fahrzeugdaten werden als direkte Variablen der MySkoda-Instanz angelegt. Jeder Datenpunkt besitzt einen festen `Ident`, zum Beispiel `StateOfCharge`, `Range`, `Charging`, `TargetSOC` oder `Climate`.

Diese Idents sind die vorgesehene Schnittstelle für Skripte und weitere Module. Ein Visualisierungsmodul kann die benötigten Variablen dadurch direkt über die MySkoda-Instanz ermitteln.

Die Darstellung einer vorhandenen Variable wird bei späteren Modulaktualisierungen nicht erneut registriert. Benutzeranpassungen an Name, Position oder Darstellung bleiben damit erhalten; aktualisiert wird nur der Datenwert.

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

Copyright © 2026 **taloriko**.

Veröffentlicht unter der [MIT-Lizenz](LICENSE).
