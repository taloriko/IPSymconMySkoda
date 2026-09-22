# MySkoda für Symcon

MySkoda verbindet ein Škoda-Fahrzeug über die offizielle MyŠkoda Public API mit Symcon. Eine Instanz steht für eine FIN/VIN. Fahrzeugdaten werden zyklisch abgefragt und über feste Variablen-Idents bereitgestellt. Die tatsächlich verfügbaren Daten und Befehle hängen vom Fahrzeug und den freigeschalteten Diensten ab.

## Funktionen

Das Modul stellt Ladezustand, Reichweite, Kilometerstand, Klima-, Lade- und Verriegelungszustände bereit. Zusätzliche Variablen enthalten Standort, Detailzustände und Informationen für die Fehlerdiagnose. API-Zustände wie `OPEN`, `CLOSED`, `YES`, `NO` und `UNKNOWN` bleiben als Strings auswertbar; die Anzeige wird deutsch lokalisiert.

Steuerbar sind Laden und Klimatisierung sowie Ladelimit und Lademodus. Ladeprofile, Standheizung und aktive Lüftung stehen über öffentliche PHP-Funktionen zur Verfügung, soweit Fahrzeug und API diese Operationen unterstützen. Die Remote-Steuerung kann vollständig deaktiviert werden.

Ein Fahrzeugbild wird aus der von der API gelieferten Render-URL als lokales Bildmedium gespeichert. Zusätzlich kann die FIN lokal interpretiert werden. Diese Interpretation ist keine offizielle Škoda-Datenquelle und kein vollständiger Ausstattungsnachweis.

## Voraussetzungen

| Voraussetzung | Verwendung |
|---|---|
| Symcon 8.1 oder neuer | Modulbetrieb und native Variablendarstellungen |
| 17-stellige FIN/VIN und MyŠkoda API-Key | Fahrzeugbezogene API-Anfragen |
| Für das Fahrzeug freigeschaltete MyŠkoda-Dienste | Verfügbarkeit der jeweiligen Daten und Remote-Funktionen |
| Internetzugang von Symcon | Public API, öffentliche OpenAPI-Definition und Fahrzeugbild |
| Archive Control, optional | Archivierung ausgewählter Werte |
| Visualisierungsinstanz, optional | Mitteilungen zum Ablauf des API-Keys |
| S-PIN, optional | Start der Standheizung |

## Installation

Das Repository im Symcon Module Control hinzufügen und eine Instanz **MySkoda** anlegen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

## Erste Einrichtung

FIN/VIN und API-Token in der Instanz eintragen und die Konfiguration übernehmen. Bei neuer oder geänderter Verbindungskonfiguration prüft das Modul den Fahrzeugabruf automatisch. Die Rückmeldung erscheint im Bereich **Verbindung**.

Das Abfrageintervall beträgt standardmäßig 300 Sekunden. Das Formular bietet 180 bis 3600 Sekunden an. Die Optionen für Detailvariablen, FIN-Informationsvariablen, Archivierung und Mitteilungen sind standardmäßig ausgeschaltet. Remote-Steuerung ist standardmäßig eingeschaltet.

Vorhandene Variablen behalten ihre Namen, Positionen, Icons und Darstellungen. Diese Eigenschaften werden nur bei der Anlage vorgegeben. Das Deaktivieren einer Erstellungsoption löscht keine vorhandenen Variablen. Datenwerte und konfigurierte Bedienaktionen werden weiterhin gepflegt.

## Bedienung und Rückmeldungen

Beim Senden eines Befehls wird der gewünschte Wert sofort angezeigt. Während der HTTP-Anfrage ist der Befehl ausstehend. Eine erfolgreiche HTTP-Antwort beendet diesen Zustand und lässt den gewünschten Wert stehen. Bei Fehlern wird der vorherige Wert wiederhergestellt. Die reguläre Fahrzeugabfrage übernimmt anschließend wieder den API-Zustand.

**Die Annahme durch die API bestätigt nicht die tatsächliche Ausführung im Fahrzeug.** Die optionalen Variablen `PendingCommands` und `CommandStatus` zeigen Übertragung und Ergebnis an.

Bei ausgeschalteter Klimatisierung wird eine gewählte Solltemperatur lokal vorgemerkt. Sie wird mit dem nächsten Klimastart gesendet. Ein externer Klimastart stellt die Übernahme der API-Solltemperatur wieder her. Bei bereits aktiver Klimatisierung wird die Temperaturänderung unmittelbar als Klimastart mit Zieltemperatur gesendet.

Battery Care Mode, maximaler AC-Ladestrom, automatische Steckerentriegelung und Scheibenheizungszustände werden, soweit geliefert, **gelesen**. Das Modul bietet dafür keine Schreibbefehle. Ein lesbarer Status ist nicht mit einer steuerbaren Funktion gleichzusetzen.

## Diagnose

**Rohe Fahrzeugantwort anzeigen** zeigt die zuletzt gespeicherte Antwort einer tatsächlich ausgeführten Fahrzeugabfrage, ohne eine weitere Anfrage auszulösen. Gültiges JSON wird für die Anzeige formatiert. Die PHP-Funktion `MSKODA_GetLastVehicleResponseRaw()` liefert den ursprünglichen Antworttext unverändert. Auch eine Fehlerantwort kann enthalten sein; der letzte gültige Arbeitsdatensatz wird separat gespeichert.

Der Symcon-Debugbereich zeigt HTTP-Status sowie API-, Bild- und Mitteilungsfehler. **Mitteilung testen** prüft die ausgewählte Visualisierung. Für den normalen Betrieb stehen außerdem **Jetzt aktualisieren** und **Fahrzeugbild aktualisieren** bereit.

## Daten und Datenschutz

Fahrzeugbezogene Anfragen verwenden FIN/VIN und API-Key. Die öffentliche OpenAPI-Definition und das Fahrzeugbild werden ohne diesen API-Key abgerufen. Die FIN-Interpretation arbeitet lokal.

Die rohe Fahrzeugantwort ist nicht anonymisiert und kann FIN, Kennzeichen, Standort oder weitere persönliche Fahrzeugdaten enthalten. Vor einer Veröffentlichung müssen diese Daten entfernt werden. API-Key und S-PIN dürfen nicht veröffentlicht werden.

## Technische Dokumentation

- [Modul, Konfiguration, Datenpunkte und PHP-Funktionen](MySkoda/README_new.md)
- [Lokale FIN/VIN-Interpretation](MySkoda/README_FIN_VIN_new.md)
- [Fahrzeug-Kompatibilität und Tests](tests/README_new.md)

Die externe [Public-API-Dokumentation](https://public.api.connect.skoda-auto.cz/docs) beschreibt den API-Vertrag. Die dokumentierten Modul-Funktionen ergeben sich aus dem Quellcode dieser Library.

## Lizenz und Markenhinweis

Copyright © 2026 taloriko. Veröffentlicht unter der [MIT-Lizenz](LICENSE).

Dieses Projekt ist eine unabhängige Community-Integration und kein offizielles Produkt von Škoda Auto a.s.
