# MySkoda für IP-Symcon

IP-Symcon Bibliothek zur Anbindung von Fahrzeugen über die offizielle **MySkoda Public API**.

## Funktionsumfang

- Fahrzeugstatus über die offizielle MySkoda Public API
- Konfiguration über FIN/VIN und API-Key
- zyklischer Abruf mit Berücksichtigung der API-Rate-Limits
- Ladezustand, Reichweite, Kilometerstand und Fahrzeugstatus
- Ladezustand, Ladeleistung, Ladelimit und Lademodus
- Klimatisierung und unterstützte Remote-Funktionen
- optionale Standheizung und aktive Lüftung
- API-Key-Ablaufwarnung 30 Tage vor Ablauf
- optionale Symcon-Push-Mitteilung über eine ausgewählte Visualisierungsinstanz
- optionale Detail- und Diagnosevariablen
- native Symcon-8.x-Darstellungen ohne Legacy-Variablenprofile

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- optional S-PIN für Standheizung
- für die gewünschte Funktion aktive MySkoda/Škoda-Connect-Dienste

## Installation

Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Danach eine Instanz **MySkoda** anlegen und FIN/VIN sowie API-Token konfigurieren.

Die vollständige Modul-Dokumentation befindet sich unter [MySkoda/README.md](MySkoda/README.md).

## API-Key erstellen

In der **MySkoda App**:

1. **Profil**
2. **Smart Home**
3. **Create Key**
4. beliebigen Namen vergeben
5. FIN/VIN und API-Token in die Symcon-Instanz übernehmen

Nach dem Übernehmen prüft das Modul die Verbindung zur MySkoda Public API.

## Daten und Steuerung

Das Modul stellt Fahrzeugdaten als native Symcon-Variablen bereit. Unterstützte Lade- und Klimafunktionen können direkt über Variablenaktionen oder die öffentlichen Modulmethoden ausgeführt werden.

Die vollständige API-Antwort kann über `MSKODA_GetRawData()` abgerufen werden.

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der **MIT-Lizenz** veröffentlicht. Der vollständige Lizenztext befindet sich in [LICENSE](LICENSE).
