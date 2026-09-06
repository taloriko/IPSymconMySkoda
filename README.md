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
- optionale API-Key-Ablaufwarnung per Symcon-Mitteilung
- Archivierung von Ladezustand, Ladelimit, Ladeleistung und Kilometerstand
- Kilometerstand im Archiv als Zähler; ungültige Werte `<= 0` werden nicht übernommen
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

**Profil → Smart Home → Schlüssel erstellen → Namen vergeben → FIN/VIN und API-Token in die Symcon-Instanz übernehmen.**

Nach dem Übernehmen prüft das Modul die Verbindung zur MySkoda Public API.

## Objektstruktur

Das Modul hält den Objektbaum bewusst einfach. Unter der MySkoda-Instanz liegen ausschließlich die echten Modulvariablen:

```text
MySkoda
├─ Ladezustand
├─ Reichweite
├─ Kilometerstand
├─ Verriegelt
├─ Türen offen
├─ Fenster offen
├─ Laden
├─ Ladeleistung
├─ Ladelimit
├─ Lademodus
├─ Klimatisierung
├─ Solltemperatur
├─ API-Key Warnung
└─ Letzte Aktualisierung
```

Es werden **keine Dummy-Instanzen, Kategorien oder Links** angelegt. Die fachliche Gruppierung übernimmt die Visualisierung.

Die technischen Variablen-Idents wie `StateOfCharge`, `Range`, `Mileage`, `Charging`, `TargetSOC` oder `Climate` bleiben stabil und sind die vorgesehene Schnittstelle für Skripte und weitere Module. Die sichtbaren Namen sind deutsch.

Eine vorhandene Variable wird bei späteren Modulaktualisierungen nicht erneut registriert. Name, Position und Darstellung werden nur bei der Erstanlage gesetzt und danach nicht durch Updates überschrieben.

## Archivierung

Bei aktivierter Archivierung werden folgende Variablen geloggt:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird als **Zähler** archiviert. Ein von der API gelieferter Kilometerstand `<= 0` wird nicht in die Variable geschrieben. Zusätzlich ist für den Archiv-Zähler das Ignorieren von Null- und negativen Werten aktiviert.

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

Copyright © 2026 **taloriko**.

Veröffentlicht unter der [MIT-Lizenz](LICENSE).
