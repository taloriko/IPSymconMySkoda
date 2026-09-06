# MySkoda für IP-Symcon

MySkoda ist ein IP-Symcon-Modul zur Anbindung eines Škoda-Fahrzeugs an die offizielle **MyŠkoda Public API**.

Das Modul stellt Fahrzeug-, Lade-, Klima-, Standort- und Diagnosedaten als native IP-Symcon-Variablen bereit und unterstützt – soweit vom Fahrzeug und der API freigegeben – ausgewählte Remote-Funktionen.

## Funktionen

- Fahrzeugdaten über FIN/VIN und MySkoda API-Key
- zyklischer Abruf mit Berücksichtigung von Rate-Limit (20 Abfragen/Stunde)
- Ladezustand, Reichweite, Kilometerstand und Fahrzeugstatus
- Ladeleistung, Ladelimit und Lademodus
- Klimatisierung sowie unterstützte Remote-Funktionen
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail-, Standort- und Diagnosevariablen
- automatische Prüfung der OpenAPI-Definition auf neue, von Version 1.0 noch nicht integrierte API-Funktionen
- optionale API-Key-Ablaufwarnung per Symcon-Mitteilung
- optional und nur nach ausdrücklicher Aktivierung: Archivierung von Ladezustand, Ladelimit, Ladeleistung und Kilometerstand
- Kilometerstand im Archiv als Zähler; ungültige Werte `<= 0` werden nicht übernommen
- stabile Variablen-Idents als Schnittstelle für Skripte und Visualisierungsmodule

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- aktive MySkoda/Škoda-Connect-Dienste für die jeweils verwendete Fahrzeugfunktion
- optional S-PIN für Standheizung
- Archive Control für die optionale Archivierung

Die offizielle MyŠkoda Public API ist unter <https://public.api.connect.skoda-auto.cz/docs> dokumentiert.

## Installation

### Manuell über Git

Das Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend eine Instanz **MySkoda** anlegen.

## Erste Einrichtung

1. In der MySkoda App einen API-Key erstellen: **Profil → Smart Home → Schlüssel erstellen**.
2. FIN/VIN und API-Token in der MySkoda-Instanz eintragen.
3. Konfiguration übernehmen.
4. Mit **Verbindung testen** prüfen, ob Fahrzeugdaten empfangen werden.
5. Optional Detailvariablen, Mitteilungen und Archivierung aktivieren.

Das Standard-Abfrageintervall beträgt 300 Sekunden. Das Modul wertet die von der API gelieferten Rate-Limit-Header aus und berücksichtigt `Retry-After`.

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
├─ Neue API-Funktionen
└─ Letzte Aktualisierung
```

Die fachliche Gruppierung und Darstellung übernimmt der User oder es wird das [IPSymconEVTile](https://github.com/taloriko/IPSymconEVTile) genutzt.

Die technischen Variablen-Idents wie `StateOfCharge`, `Range`, `Mileage`, `Charging`, `TargetSOC` oder `Climate` bleiben stabil und bilden die Schnittstelle für Skripte und weitere Module.

Vorhandene Variablen werden bei späteren Modulaktualisierungen nicht erneut registriert. Name, Position und Darstellung werden deshalb nur bei der Erstanlage gesetzt und danach nicht durch ein Update überschrieben.

## Neue API-Funktionen

Nach einer erfolgreichen Fahrzeugabfrage prüft das Modul zusätzlich die öffentliche OpenAPI-Definition der MyŠkoda Public API. Die Definition wird höchstens einmal innerhalb von 24 Stunden neu geladen und belastet nicht das fahrzeugbezogene API-Kontingent.

Die Variable `NewApiFeatures` wird als **Neue API-Funktionen** angezeigt:

- `0` – die aktuell veröffentlichten API-Operationen sind dem Modul bekannt.
- `> 0` – die API enthält zusätzliche Operationen, die Version 1.0 noch nicht integriert.

Neue API-Funktionen werden **nicht automatisch als IP-Symcon-Variablen angelegt**. Die Variable ist nur ein Hinweis darauf, dass sich die API erweitert hat. Neue Datenpunkte, Datentypen und Darstellungen werden weiterhin ausschließlich über ein definiertes Modulupdate ergänzt.

Für Entwickler werden unbekannte OpenAPI-Operationen beim Neuladen der API-Definition zusätzlich im Debug ausgegeben. Über **API-Definition neu laden** kann die Prüfung manuell angestoßen werden.

## Deutsche Statusdarstellung

Statuswerte der API bleiben technisch unverändert. Die IP-Symcon-Darstellung übersetzt bekannte Werte für die Oberfläche.

Beispiele:

- `PARKED` → **Geparkt**
- `MOVING` → **In Bewegung**
- `DRIVING` → **In Fahrt**
- `CHARGING` → **Laden aktiv**
- `READY_FOR_CHARGING` → **Ladebereit**
- `UNKNOWN` → **Unbekannt**

Dadurch bleiben Skripte unabhängig von der eingestellten Sprache, während die Oberfläche deutsch dargestellt wird.

## Archivierung

Die Archivierung ist **standardmäßig deaktiviert** und wird nur nach ausdrücklicher Aktivierung in der Instanz eingerichtet.

Dabei werden einmalig folgende Variablen für das Logging konfiguriert:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird als **Zähler** archiviert. Ein von der API gelieferter Kilometerstand `<= 0` wird nicht in die Variable geschrieben. Zusätzlich ist für den Archiv-Zähler das Ignorieren von Null- und negativen Werten aktiviert.

Nach der erstmaligen Einrichtung verändert das Modul die Archive-Control-Einstellungen nicht erneut. Benutzeranpassungen bleiben damit erhalten.

## Dokumentation

Die vollständige Modul-Dokumentation befindet sich unter [MySkoda/README.md](MySkoda/README.md).

## Fehler melden

Fehler und nachvollziehbare Verbesserungsvorschläge können über die GitHub-Issues des Projekts gemeldet werden. Bitte keine API-Keys, S-PINs, vollständigen FINs oder andere Zugangsdaten veröffentlichen.

## Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Veröffentlicht unter der [MIT-Lizenz](LICENSE).

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
