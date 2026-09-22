# MySkoda für Symcon

MySkoda verbindet ein Škoda-Fahrzeug über die offizielle MyŠkoda Public API mit Symcon. Eine Instanz steht für eine FIN/VIN. Fahrzeugdaten werden zyklisch abgefragt und über feste Variablen-Idents bereitgestellt. Die tatsächlich verfügbaren Daten und Befehle hängen vom Fahrzeug und den freigeschalteten Diensten ab.

## Funktionen

- Fahrzeugdaten aus der Offiziellen Skoda API (über FIN/VIN und MySkoda API-Key)
- Ladezustand, Reichweite, Kilometerstand und Fahrzeugstatus
- Ladeleistung, Ladelimit und Lademodus
- Klimatisierung und Solltemperatur
- Laden starten/stoppen, Ladelimit und Lademodus ändern
- Klimatisierung, Standheizung und Lüftung steuern
- optionale Detail-, Standort- und Diagnosevariablen
- optionale Archivierung ausgewählter Fahrzeugwerte
- API-Key-Ablaufwarnung per Symcon-Mitteilung
- lokales Fahrzeugbild aus der von MyŠkoda gelieferten Render-URL
- lokale FIN/VIN-Entschlüsselung mit optionalen String-Variablen

Die FIN/VIN-Entschlüsselung ist bewusst nur eine Zusatzfunktion. Details zur Zerlegung, Prüflogik, bekannten Codes und Grenzen stehen separat in [MySkoda/README_FIN_VIN.md](MySkoda/README_FIN_VIN.md).

> **Hinweis zur FIN/VIN-Entschlüsselung:** Die daraus abgeleiteten Angaben sind keine offiziellen Fahrzeugstammdaten von Škoda. Sie werden anhand öffentlich verfügbarer Informationen interpretiert und können bei einzelnen Fahrzeugen unvollständig oder mehrdeutig sein.

## Voraussetzungen

- Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- aktive MySkoda/Škoda-Connect-Dienste für die verwendeten Fahrzeugfunktionen
- Internetzugang von Symcon zur MyŠkoda Public API
- optional S-PIN für die Standheizung
- Archive Control nur bei Verwendung der optionalen Archivierung

Der API-Key wird in der MySkoda App unter **Profil → Smart Home → Schlüssel erstellen** erzeugt.
 - Dort steht auch die FIN/VIN (Fahrzeug-Fahrgestellnummer) zum kopieren

Offizielle API-Dokumentation: <https://public.api.connect.skoda-auto.cz/docs>

## Installation

### Module Store

Im Symcon **Module Store** nach **MySkoda** suchen, das Modul installieren und anschließend eine Instanz **MySkoda** anlegen.

### Manuell über Module Control

Alternativ das Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend eine Instanz **MySkoda** anlegen.

## Erste Einrichtung

1. In der MySkoda App einen API-Key erstellen.
2. FIN/VIN und API-Token in der MySkoda-Instanz eintragen.
3. Konfiguration übernehmen.
4. Mit **Verbindung testen** prüfen, ob Fahrzeugdaten empfangen werden.
5. Optional FIN-Informationsvariablen, Detail-/Diagnosevariablen, Mitteilungen und Archivierung aktivieren.

Das Standard-Abfrageintervall beträgt 300 Sekunden. Das Modul berücksichtigt die von der API gelieferten Rate-Limit-Header und `Retry-After`.

## Verhalten bei Remote-Befehlen

Beim Senden eines Befehls wird der gewünschte Wert sofort angezeigt. Während der HTTP-Anfrage ist der Befehl ausstehend. Eine erfolgreiche HTTP-Antwort beendet diesen Zustand und lässt den gewünschten Wert stehen. Bei Fehlern wird der vorherige Wert wiederhergestellt. Die reguläre Fahrzeugabfrage übernimmt anschließend wieder den API-Zustand.

**Die Annahme durch die API bestätigt nicht die tatsächliche Ausführung im Fahrzeug.** Die optionalen Variablen `PendingCommands` und `CommandStatus` zeigen Übertragung und Ergebnis an.

Bei ausgeschalteter Klimatisierung wird eine gewählte Solltemperatur lokal vorgemerkt. Sie wird mit dem nächsten Klimastart gesendet. Ein externer Klimastart stellt die Übernahme der API-Solltemperatur wieder her. Bei bereits aktiver Klimatisierung wird die Temperaturänderung unmittelbar als Klimastart mit Zieltemperatur gesendet.

Battery Care Mode, maximaler AC-Ladestrom, automatische Steckerentriegelung und Scheibenheizungszustände werden, soweit geliefert, **gelesen**. Das Modul bietet dafür keine Schreibbefehle. Ein lesbarer Status ist nicht mit einer steuerbaren Funktion gleichzusetzen.

## In der App verfügbar, aber nicht in der Public API

Einige Funktionen der MySkoda App sind im öffentlichen API-Vertrag derzeit nicht enthalten und können deshalb vom Modul nicht bereitgestellt werden, darunter Klima-Timer/Abfahrtszeiten, intelligentes Heizen/Klimatisieren, Sitzheizung im Zusammenhang mit der Klimatisierung, Battery Care Mode, reduzierte AC-Ladeleistung und automatisches Entriegeln des AC-Ladekabels.

Das Modul verwendet für Fahrzeugdaten und Remote-Funktionen ausschließlich die offizielle MyŠkoda Public API. Private oder interne App-Schnittstellen werden nicht verwendet.

## Dokumentation

Die vollständige Modul-Dokumentation mit Konfiguration, Variablen, PHP-Befehlen, Fehlersuche und Datenschutz befindet sich unter [MySkoda/README.md](MySkoda/README.md).

Die technische Dokumentation der optionalen FIN/VIN-Entschlüsselung befindet sich unter [MySkoda/README_FIN_VIN.md](MySkoda/README_FIN_VIN.md).

## Fehler melden

Fehler und nachvollziehbare Verbesserungsvorschläge können über die GitHub-Issues des Projekts gemeldet werden. Bitte keine API-Keys, S-PINs, vollständigen FINs oder andere Zugangsdaten veröffentlichen.

## Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Veröffentlicht unter der [MIT-Lizenz](LICENSE).

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
