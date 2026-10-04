# MySkoda für Symcon

MySkoda verbindet ein Škoda-Fahrzeug über die offizielle MyŠkoda Public API mit Symcon. Eine Instanz steht für eine FIN/VIN. Fahrzeugdaten werden zyklisch abgefragt und als Variablen bereitgestellt. Die verfügbaren Daten und Befehle hängen vom Fahrzeug und den freigeschalteten Diensten ab.

## Enthaltenes Modul

| Modul | Beschreibung |
|---|---|
| [MySkoda](MySkoda/README.md) | Gerätemodul für Fahrzeug-, Lade-, Kraftstoff-, Klima-, Standort- und Statusdaten sowie die unterstützten Remote-Befehle. Die Modul-Dokumentation beschreibt Voraussetzungen, Kompatibilität, Konfiguration, Datenpunkte und PHP-Funktionen. |

## Funktionen

- Fahrzeugdaten aus der offiziellen MyŠkoda Public API über FIN/VIN und API-Key
- Ladezustand, Reichweite, Kilometerstand und Fahrzeugstatus
- Ladeleistung, Ladegeschwindigkeit, Ladelimit, Lademodus sowie Anschluss- und Verriegelungsstatus des Ladesteckers
- Kraftstoffdaten und Reichweiten bei Fahrzeugen, die diese Daten liefern
- Klimatisierung, Solltemperatur und Standheizungsstatus
- Laden starten/stoppen, Ladelimit und Lademodus ändern
- Klimatisierung, Standheizung und aktive Lüftung steuern; Ladeprofile per PHP übertragen
- bedarfsgerechte Anlage von Fahrzeugvariablen und automatische Erfassung zusätzlicher skalarer API-Felder
- optionale Diagnosevariablen, Archivierung ausgewählter Fahrzeugwerte und API-Key-Ablaufwarnung per Symcon-Mitteilung
- lokale Fahrzeugbilder aus der gelieferten Render-URL und daraus abgeleiteten verfügbaren Ansichten
- lokale FIN/VIN-Entschlüsselung mit optionalen String-Variablen

Fahrzeugbezogene Variablen werden angelegt, sobald ihr API-Feld vorhanden und nicht `null` ist. Bereits angelegte Variablen bleiben erhalten. Die Option **Detail- und Diagnosevariablen anlegen** steuert ausschließlich zusätzliche Diagnose- und API-Informationsvariablen, nicht die normalen Fahrzeugdaten oder Standortvariablen. Die genaue Zuordnung steht in der [Modul-Dokumentation](MySkoda/README.md).

Die FIN/VIN-Entschlüsselung ist eine Zusatzfunktion. Details zur Zerlegung, Prüflogik, bekannten Codes und Grenzen stehen separat in [MySkoda/README_FIN_VIN.md](MySkoda/README_FIN_VIN.md).

> **Hinweis zur FIN/VIN-Entschlüsselung:** Die daraus abgeleiteten Angaben sind keine offiziellen Fahrzeugstammdaten von Škoda. Sie werden anhand öffentlich verfügbarer Informationen interpretiert und können bei einzelnen Fahrzeugen unvollständig oder mehrdeutig sein.

## Voraussetzungen und Kompatibilität

- Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN und ein für das Fahrzeug gültiger MySkoda API-Key
- aktive MySkoda/Škoda-Connect-Dienste für die verwendeten Fahrzeugfunktionen
- Internetzugang von Symcon zur MyŠkoda Public API
- S-PIN für den Start der Standheizung
- Archive Control nur bei Verwendung der optionalen Archivierung

Voraussetzung ist, dass das Fahrzeug über die Public API mit dem eigenen API-Key erreichbar ist. Die Unterstützung einzelner Funktionen ergibt sich aus den gelieferten Daten und angebotenen Operationen, nicht allein aus dem Modellnamen oder der lokalen FIN-Auswertung. Praktische Nachweise für Enyaq 80 von 2022 und 2023 sind in der [Fahrzeug-Kompatibilitätsmatrix](tests/README.md) dokumentiert. Die Matrix unterscheidet geprüfte Werte von noch offenen Tests. Automatisierte Verbrenner-Prüfdaten sind keine pauschale Fahrzeugfreigabe.

Der API-Key wird in der MySkoda App unter **Profil → Smart Home → Schlüssel erstellen** erzeugt. Dort kann auch die FIN/VIN kopiert werden.

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
3. Konfiguration übernehmen. Die Verbindung wird bei neuen Zugangsdaten automatisch geprüft.
4. Die Verbindungsrückmeldung kontrollieren. Nach erfolgreicher Verbindung können die Fahrzeugdaten mit **Jetzt aktualisieren** erneut abgefragt werden.
5. Bei Bedarf FIN-Informationsvariablen, Diagnosevariablen, Mitteilungen und Archivierung aktivieren. Für Standheizungsbefehle die fahrzeugabhängig angezeigte S-PIN hinterlegen und die Konfiguration übernehmen.

Das Standard-Abfrageintervall beträgt 300 Sekunden. Das Modul berücksichtigt die von der API gelieferten Rate-Limit-Header und numerisches `Retry-After`. Auch **Jetzt aktualisieren** beachtet die Wartezeiten und die für zyklische Abrufe geltende Kontingentreserve.

Die angelegten Variablen können in der Symcon-Visualisierung verwendet werden. Bedienbare Variablen besitzen eine Standardaktion; es ist kein zusätzliches Steuerskript erforderlich. Umfang und Voraussetzungen der Bedienung sind in der [Modul-Dokumentation](MySkoda/README.md) beschrieben.

## Verhalten bei Remote-Befehlen

Bei einer Variablenaktion wird der gewünschte Wert während der Übertragung sofort angezeigt. Während der HTTP-Anfrage ist der Befehl ausstehend. Eine erfolgreiche HTTP-Antwort beendet diesen Zustand und lässt den gewünschten Wert stehen. Bei Fehlern wird der vorherige Wert wiederhergestellt. Die reguläre Fahrzeugabfrage übernimmt anschließend wieder den gelieferten API-Zustand.

**Die Annahme durch die API bestätigt nicht die tatsächliche Ausführung im Fahrzeug.** Die optionalen Variablen `PendingCommands` und `CommandStatus` zeigen Übertragung und Ergebnis an. Reine PHP-Befehle ohne Variablenaktion führen ebenfalls einen Befehlsstatus, ändern aber nicht zwangsläufig den sichtbaren Fahrzeugzustand vor dem nächsten Abruf.

Ein fehlender oder unbekannter Wert von `charging.status.state` schaltet den Bedienwert `Charging` nicht automatisch auf `false`. Fehlende oder mit `null` gelieferte Felder lassen bereits gespeicherte Fahrzeugwerte im Regelfall unverändert; sie werden nicht automatisch zu `UNKNOWN`. Noch nie gelieferte Felder erhalten keine Platzhaltervariable. `PlugConnectionState` und `PlugLockState` beschreiben Anschluss und Verriegelung des Ladesteckers, nicht den laufenden Ladevorgang. Ein aktueller `LastUpdate` bestätigt den letzten erfolgreichen Abruf, nicht die Aktualität jedes einzelnen Datenpunkts.

Bei ausgeschalteter Klimatisierung wird eine gewählte Solltemperatur lokal vorgemerkt. Sie wird mit dem nächsten Klimastart gesendet. Ein externer Klimastart stellt die Übernahme der API-Solltemperatur wieder her. Bei bereits aktiver Klimatisierung wird die Temperaturänderung unmittelbar als Klimastart mit Zieltemperatur gesendet.

## Grenzen der Steuerung

Battery Care Mode, maximaler AC-Ladestrom, automatische Steckerentriegelung und Scheibenheizungszustände werden angezeigt, soweit das Fahrzeug diese Werte liefert. Das Modul bietet dafür keine Schreibbefehle. Ein lesbarer Status ist nicht mit einer steuerbaren Funktion gleichzusetzen.

Klima-Timer/Abfahrtszeiten, intelligentes Heizen/Klimatisieren und Sitzheizungssteuerung gehören nicht zur implementierten Bedienung. Ladeprofile sind davon getrennt zu betrachten: Ihr gespeicherter API-Bereich kann gelesen und ein Profil über die dokumentierte PHP-Funktion übertragen werden.

Der Standheizungsstart sendet die S-PIN und nur bei entsprechend gelieferten Fahrzeugdaten eine Zieltemperatur. Die PHP-Parameter für Dauer und Modus verändern den Startbefehl nicht. Die gemeldete Standheizungsdauer ist ein Lesewert. Die genaue Signatur und die Bedingungen stehen in der [PHP-Befehlsreferenz](MySkoda/README.md#7-öffentliche-php-funktionen).

Das Modul verwendet für Fahrzeugdaten und Remote-Funktionen ausschließlich die offizielle MyŠkoda Public API. Private oder interne App-Schnittstellen werden nicht verwendet.

## Technische Dokumentation

- [Modul, Konfiguration, Datenpunkte und PHP-Funktionen](MySkoda/README.md)
- [Lokale FIN/VIN-Interpretation](MySkoda/README_FIN_VIN.md)
- [Fahrzeug-Kompatibilität und Tests](tests/README.md)
- [Funktionsumfang der initialen Veröffentlichung](CHANGELOG.md)

Die externe [Public-API-Dokumentation](https://public.api.connect.skoda-auto.cz/docs) beschreibt den API-Vertrag. Die dokumentierten Modul-Funktionen ergeben sich aus dem Quellcode dieser Library.

## Fehler melden

Fehler und nachvollziehbare Verbesserungsvorschläge können über die GitHub-Issues des Projekts gemeldet werden. Bitte keine API-Keys, S-PINs, vollständigen FINs oder andere Zugangsdaten veröffentlichen. Rohantworten können auch Kennzeichen und Standortdaten enthalten und müssen vor der Weitergabe bereinigt werden.

## Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Dieses Projekt steht unter der [IPSymconMySkoda Non-Commercial License](LICENSE).

Private und sonstige nicht-kommerzielle Nutzung, Weiterentwicklung, Forks und Änderungen sind ausdrücklich erlaubt und erwünscht. Eine kommerzielle Nutzung ist nur mit vorheriger schriftlicher Genehmigung des Urheberrechtsinhabers erlaubt.

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
