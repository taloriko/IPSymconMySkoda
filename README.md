# MySkoda für IP-Symcon

IP-Symcon-Modul zur Anbindung eines Fahrzeugs an die offizielle **MySkoda Public API**.

## Funktionen

- Fahrzeugdaten über FIN/VIN und MySkoda API-Key
- zyklischer Abruf mit Berücksichtigung von Rate-Limit und `Retry-After`
- Ladezustand, Reichweite, Kilometerstand und Fahrzeugstatus
- Ladeleistung, Ladelimit und Lademodus
- Klimatisierung sowie unterstützte Remote-Funktionen
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail-, Standort- und Diagnosevariablen
- thematische Objektstruktur über Dummy-Instanzen mit Namen und Icons
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

## Objektstruktur und Visualisierungsschnittstelle

Die Fahrzeugdaten werden in der Oberfläche thematisch gruppiert:

```text
MySkoda
├─ Fahrzeug
├─ Fahrzeugstatus
├─ Laden
├─ Klimatisierung
├─ Standort
├─ API & Diagnose
├─ Diagramme
│  └─ Ladeverlauf
└─ Letzte Aktualisierung
```

Die Gruppen sind Dummy-Instanzen mit festen technischen Idents, Namen und Icons. Darunter liegen Links auf die zugehörigen MySkoda-Statusvariablen. Dadurch bleibt die übersichtliche Gruppierung erhalten, während die eigentlichen Modulvariablen technisch direkt an der MySkoda-Instanz verbleiben.

Die Statusvariablen besitzen feste Idents wie `StateOfCharge`, `Range`, `Charging`, `TargetSOC` oder `Climate`. Diese Idents sind die vorgesehene Schnittstelle für Skripte und weitere Module. Ein Visualisierungsmodul kann die benötigten Werte dadurch direkt über die MySkoda-Instanz ermitteln.

Variablen, Gruppen und Links werden nur bei Bedarf angelegt. Bereits vorhandene Namen, Positionen und Darstellungen werden bei späteren Modulaktualisierungen nicht erneut gesetzt.

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

Copyright © 2026 **taloriko**.

Veröffentlicht unter der [MIT-Lizenz](LICENSE).
