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

Die Fahrzeugdaten werden direkt unter thematischen Dummy-Instanzen einsortiert:

```text
MySkoda
├─ Fahrzeug
│  ├─ Ladezustand
│  ├─ Reichweite
│  └─ Kilometerstand
├─ Fahrzeugstatus
├─ Laden
├─ Klimatisierung
├─ Standort
├─ API & Diagnose
├─ Diagramme
│  └─ Ladeverlauf
└─ Letzte Aktualisierung
```

Die Gruppen sind Dummy-Instanzen mit festen technischen Idents, Namen und Icons. Darunter liegen die echten MySkoda-Variablen – keine zusätzlichen Links.

Die Variablen besitzen feste Idents wie `StateOfCharge`, `Range`, `Charging`, `TargetSOC` oder `Climate`. Diese Idents sind die vorgesehene Schnittstelle für Skripte und weitere Module. Ein Visualisierungsmodul kann die benötigten Werte rekursiv unterhalb der MySkoda-Instanz über diese Idents ermitteln.

Eine vorhandene Variable wird bei späteren Modulaktualisierungen nicht erneut registriert. Name, Position und Darstellung werden deshalb nur bei der Erstanlage gesetzt und danach nicht durch Updates überschrieben.

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

Copyright © 2026 **taloriko**.

Veröffentlicht unter der [MIT-Lizenz](LICENSE).
