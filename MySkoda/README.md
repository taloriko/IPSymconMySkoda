# MySkoda

MySkoda ist ein Gerätemodul für IP-Symcon zur Anbindung eines Škoda-Fahrzeugs an die offizielle **MyŠkoda Public API**. Eine Instanz repräsentiert genau eine FIN/VIN.

Der tatsächlich verfügbare Funktionsumfang hängt vom Fahrzeug, dessen Ausstattung und den für das Fahrzeug freigegebenen MyŠkoda-Diensten ab. Das Modul wurde praktisch mit einem **Škoda Enyaq 80** getestet.

## 1. Funktionsumfang

- Fahrzeugdaten über FIN/VIN und MyŠkoda API-Key
- zyklischer Abruf mit einstellbarem Abfrageintervall
- Berücksichtigung der von der API gelieferten Rate-Limit-Informationen und `Retry-After`
- stabile Variablen-Idents als Schnittstelle für Skripte und weitere Module
- optionale Detail-, Standort- und Diagnosevariablen
- automatische Prüfung der öffentlichen OpenAPI-Definition auf neue, noch nicht integrierte API-Operationen
- optionale API-Key-Ablaufwarnung per Symcon-Mitteilung
- optionale Archivierung ausgewählter Fahrzeugwerte nach ausdrücklicher Aktivierung

### Lesbare Fahrzeugdaten

Soweit vom Fahrzeug und der API geliefert, werden unter anderem folgende Werte verarbeitet:

- Ladezustand
- Reichweite
- Kilometerstand
- Verriegelungs-, Tür- und Fensterstatus
- Ladezustand und Ladeleistung
- Ladelimit und Lademodus
- Klimatisierungsstatus und Solltemperatur
- Fahrzeugname und Kennzeichen
- weitere Statusdetails
- Standortdaten
- API- und Diagnoseinformationen

### Steuerbare Funktionen

Soweit vom Fahrzeug und der API unterstützt:

- Laden starten und stoppen
- Ladelimit ändern
- Lademodus ändern
- Klimatisierung starten und stoppen
- Solltemperatur der Klimatisierung ändern
- Ladeprofile über die öffentliche PHP-Schnittstelle aktualisieren
- Standheizung über die öffentliche PHP-Schnittstelle starten und stoppen
- aktive Lüftung über die öffentliche PHP-Schnittstelle starten und stoppen

Die Remote-Steuerung kann in der Instanz vollständig deaktiviert werden.

### In der App verfügbar, aber nicht in der Public API

Folgende Funktionen sind in der MySkoda App verfügbar, aber im offiziellen **Public-API-Vertrag 1.0.0** derzeit nicht enthalten und können deshalb vom Modul nicht bereitgestellt werden:

- Camping Mode
- Klima-Timer / Abfahrtszeiten
- intelligentes Heizen / intelligentes Klimatisieren
- Scheibenheizung im Zusammenhang mit der Klimatisierung
- Sitzheizung Fahrer und Beifahrer im Zusammenhang mit der Klimatisierung
- Battery Care Mode
- reduzierte AC-Ladeleistung / Begrenzung des AC-Ladestroms
- automatisches Entriegeln des AC-Ladekabels

Das Modul verwendet ausschließlich die offizielle MyŠkoda Public API. Private oder interne App-Schnittstellen werden nicht verwendet.

## 2. Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MyŠkoda API-Key
- aktive MyŠkoda/Škoda-Connect-Dienste für die verwendeten Fahrzeugfunktionen
- Internetzugang von IP-Symcon zur MyŠkoda Public API
- optional S-PIN für die Standheizung
- Archive Control nur bei Verwendung der optionalen Archivierung

Der API-Key wird in der MySkoda App unter **Profil → Smart Home → Schlüssel erstellen** erzeugt.

Offizielle API-Dokumentation: <https://public.api.connect.skoda-auto.cz/docs>

## 3. Installation

### Module Store

Nach Veröffentlichung im Module Store kann das Modul dort direkt installiert werden.

### Manuell über Module Control

Alternativ das Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend kann unter **Instanz hinzufügen** eine Instanz **MySkoda** angelegt werden.

## 4. Einrichten der Instanz

1. In der MySkoda App einen API-Key erstellen.
2. Eine Instanz **MySkoda** anlegen.
3. FIN/VIN und API-Token eintragen.
4. Konfiguration übernehmen.
5. Über **Verbindung testen** prüfen, ob Fahrzeugdaten empfangen werden.
6. Optional Archivierung, Benachrichtigungen sowie Detail- und Diagnosevariablen aktivieren.

Das Standard-Abfrageintervall beträgt **300 Sekunden**. In der Konfiguration sind Werte von **180 bis 3600 Sekunden** möglich.

## 5. Konfiguration

| Einstellung | Funktion | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| API-Token | MyŠkoda Public API-Key | leer |
| Abfrageintervall | automatischer Fahrzeugabruf in Sekunden | 300 s |
| Remote-Steuerung | erlaubt die vom Modul bereitgestellten Remote-Befehle | an |
| Klima ohne externe Stromversorgung | erlaubt Klimatisierung ohne angeschlossene externe Stromversorgung | an |
| S-PIN | wird für die Standheizung verwendet | leer |
| Fahrzeugdaten archivieren | richtet das Logging ausgewählter Fahrzeugwerte einmalig ein | aus |
| API-Key-Ablaufwarnung | sendet bei höchstens 30 Tagen Restlaufzeit eine Mitteilung | aus |
| Visualisierung für Mitteilungen | Zielinstanz für Symcon-Mitteilungen | keine |
| Detail-/Diagnosevariablen | legt zusätzliche Status- und Diagnosevariablen an | aus |

Die Schaltflächen in der Konfiguration ermöglichen zusätzlich:

- **Verbindung testen**
- **Jetzt aktualisieren**
- **Mitteilung testen**
- **API-Definition neu laden**

## 6. Statusvariablen und Darstellungen

Das Modul legt ausschließlich eigene Variablen unterhalb der MySkoda-Instanz an. Es werden keine Dummy-Instanzen, Kategorien oder Links erzeugt.

Vorhandene Variablen bleiben benutzerverwaltet. Namen, Positionen und andere benutzerseitig veränderbare Objekteigenschaften werden bei späteren Aktualisierungen nicht fortlaufend überschrieben.

### 6.1 Standard-Datenpunkte

| Ident | Deutsche Anzeige | Typ | Bedienbar | Beschreibung |
|---|---|---|---:|---|
| `StateOfCharge` | Ladezustand | Integer | Nein | Batterieladezustand in Prozent |
| `Range` | Reichweite | Integer | Nein | verbleibende Reichweite in km |
| `Mileage` | Kilometerstand | Integer | Nein | Kilometerstand |
| `Locked` | Verriegelt | Boolean | Nein | Verriegelungsstatus |
| `DoorsOpen` | Türen offen | Boolean | Nein | mindestens eine Tür offen |
| `WindowsOpen` | Fenster offen | Boolean | Nein | mindestens ein Fenster offen |
| `Charging` | Laden | Boolean | Ja | Laden starten/stoppen bzw. aktueller Ladezustand |
| `ChargePower` | Ladeleistung | Float | Nein | aktuelle Ladeleistung |
| `TargetSOC` | Ladelimit | Integer | Ja | 50 bis 100 % in 10-%-Schritten |
| `ChargeMode` | Lademodus | Integer | Ja | vom Fahrzeug unterstützter Lademodus |
| `Climate` | Klimatisierung | Boolean | Ja | Klimatisierung starten/stoppen bzw. aktueller Zustand |
| `TargetTemperature` | Solltemperatur | Float | Ja | 16 bis 30 °C in 0,5-°C-Schritten |
| `ApiKeyWarning` | API-Key Warnung | Boolean | Nein | API-Key läuft innerhalb von 30 Tagen ab |
| `NewApiFeatures` | Neue API-Funktionen | Integer | Nein | Anzahl unbekannter Operationen der aktuellen OpenAPI-Definition |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein | Zeitpunkt des letzten erfolgreichen Fahrzeugabrufs |

### 6.2 Optionale Detail- und Diagnosevariablen

Bei aktivierter Option **Detail- und Diagnosevariablen anlegen** werden zusätzlich fehlende Variablen angelegt.

| Ident | Deutsche Anzeige | Inhalt |
|---|---|---|
| `VehicleName` | Fahrzeugname | von der API gelieferter Fahrzeugname |
| `LicensePlate` | Kennzeichen | Kennzeichen |
| `TrunkOpen` | Kofferraum offen | Kofferraumstatus |
| `BonnetOpen` | Motorhaube offen | Haubenstatus |
| `SunroofOpen` | Schiebedach offen | Schiebedachstatus |
| `LightsOn` | Licht an | Lichtstatus |
| `ParkingState` | Parkstatus | Parkstatus der API |
| `ChargingState` | Ladestatus | Ladezustand der API |
| `ChargeType` | Ladeart | Ladeart der API |
| `FullyChargedAt` | Vollgeladen um | von der API berechneter Zeitpunkt |
| `Latitude` | Breitengrad | Standort, sofern verfügbar |
| `Longitude` | Längengrad | Standort, sofern verfügbar |
| `ApiKeyExpiresAtVar` | API-Key gültig bis | Ablaufzeitpunkt des API-Keys |
| `RequestsRemaining` | Verbleibende API-Anfragen | letzter vom Portal gemeldeter Restwert |
| `PartialErrors` | API-Teilfehler | von der Fahrzeugantwort gelieferte Teilfehler |
| `PendingCommands` | Ausstehende Befehle | Anzahl aktuell laufender Befehlsanfragen |
| `CommandStatus` | Befehlsstatus | Ergebnis bzw. Fehlertext des letzten Remote-Befehls |

Einmal angelegte Detailvariablen werden beim Deaktivieren der Option nicht gelöscht.

### 6.3 Profile und Darstellungen

Das Modul verwendet die nativen Darstellungen von IP-Symcon. Es werden keine benutzerdefinierten Variablenprofile angelegt.

Der Lademodus wird nur gesendet, wenn er in der vom Fahrzeug gemeldeten Liste `charging.settings.availableChargeModes` enthalten ist. Die im Modul bekannten API-Werte sind:

- `MANUAL`
- `TIMER`
- `TIMER_CHARGING_WITH_CLIMATISATION`
- `PREFERRED_CHARGING_TIMES`
- `ONLY_OWN_CURRENT`
- `IMMEDIATE_DISCHARGING`
- `HOME_STORAGE_CHARGING`

Nicht jeder dieser Werte muss von jedem Fahrzeug unterstützt werden.

## 7. Befehlslogik für Remote-Befehle

Bei den über Variablen bedienbaren Remote-Funktionen wird der gewünschte Wert beim Absenden sofort lokal angezeigt.

Während der synchronen HTTP-Anfrage wird der betroffene Datenpunkt als Pending geführt. Danach gilt:

- **erfolgreiche 2xx-Antwort**: Der gewünschte Wert bleibt gesetzt und Pending wird beendet.
- **Fehlerantwort oder Übertragungsfehler**: Der vorherige lokale Wert wird wiederhergestellt und Pending wird beendet.

Der normale zyklische Fahrzeugabruf läuft unabhängig davon weiter. Liefert das Portal später einen anderen Fahrzeugzustand, wird dieser beim regulären Abruf übernommen.

`PendingCommands` zeigt die Anzahl der aktuell laufenden Befehlsanfragen. `CommandStatus` zeigt das Ergebnis des letzten Befehls. Bei einem Fehler wird zusätzlich der von API oder Transport gelieferte **Fehlertext** ausgegeben.

Beispiele:

```text
Bestätigt: Ladelimit
Bestätigt: Klimatisierung
Befehl abgelehnt: Ladelimit - HTTP 400: ...
Befehl abgelehnt: Klimatisierung - cURL: ...
```

## 8. API-Diagnose und neue API-Funktionen

Nach einem erfolgreichen Fahrzeugabruf prüft das Modul zusätzlich die öffentliche OpenAPI-Definition der MyŠkoda Public API. Die Definition wird intern bis zu 24 Stunden zwischengespeichert.

`NewApiFeatures` zeigt die Anzahl der API-Operationen, die der aktuellen Modulversion noch nicht bekannt sind:

- `0` – keine unbekannte Operation erkannt
- `> 0` – die öffentliche API enthält zusätzliche, noch nicht integrierte Operationen

Neue Operationen werden nicht automatisch als Variablen oder Befehle angelegt.

Mit `MSKODA_GetRemoteOperations()` kann die vom Fahrzeug gelieferte Liste der verfügbaren Remote-Operationen ausgelesen werden. Das Modul verwendet `vehicle.operations` und `vehicle.remoteOperations` als Fallback.

## 9. Archivierung

Die Archivierung ist standardmäßig **aus** und wird nur nach ausdrücklicher Aktivierung eingerichtet.

Einmalig werden folgende Variablen für das Logging im Archive Control aktiviert:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird als Zähler eingerichtet. Werte `<= 0` werden nicht übernommen. Nach der erstmaligen Einrichtung verändert das Modul spätere Benutzeranpassungen im Archive Control nicht mehr.

## 10. Visualisierung

Die vom Modul angelegten Variablen können direkt in den IP-Symcon-Visualisierungen verwendet werden.

Für eine zusätzliche Fahrzeugdarstellung kann optional das separate Modul [IPSymconEVTile](https://github.com/taloriko/IPSymconEVTile) verwendet werden. MySkoda selbst legt keine zusätzliche Objektstruktur für die Visualisierung an.

Standortdaten werden nur gesetzt, wenn die MyŠkoda Public API sie für das Fahrzeug und den jeweiligen Benutzer liefert.

## 11. PHP-Befehlsreferenz

In den Beispielen ist `12345` die Instanz-ID der MySkoda-Instanz.

| Befehl | Rückgabe | Funktion |
|---|---|---|
| `MSKODA_Update(12345);` | `void` | Fahrzeugdaten sofort aktualisieren |
| `MSKODA_TestConnection(12345);` | `bool` | Verbindung und Fahrzeugabruf testen |
| `MSKODA_GetRawData(12345);` | `string` | letzte vollständige Fahrzeugantwort als JSON ausgeben |
| `MSKODA_GetChargingProfiles(12345);` | `string` | Ladeprofile aus den zuletzt empfangenen Fahrzeugdaten als JSON ausgeben |
| `MSKODA_GetRemoteOperations(12345);` | `string` | vom Fahrzeug gemeldete Remote-Operationen als JSON ausgeben |
| `MSKODA_SetChargingLimit(12345, 80);` | `bool` | Ladelimit setzen |
| `MSKODA_SetChargeMode(12345, 'MANUAL');` | `bool` | Lademodus setzen |
| `MSKODA_UpdateChargingProfile(12345, 1, $profileJson);` | `bool` | Ladeprofil aktualisieren |
| `MSKODA_StartAuxiliaryHeating(12345, 22.0, 30, 'HEATING');` | `bool` | Standheizung starten; S-PIN erforderlich |
| `MSKODA_StopAuxiliaryHeating(12345);` | `bool` | Standheizung stoppen |
| `MSKODA_StartVentilation(12345);` | `bool` | aktive Lüftung starten |
| `MSKODA_StopVentilation(12345);` | `bool` | aktive Lüftung stoppen |
| `MSKODA_RefreshApiDefinition(12345);` | `bool` | öffentliche OpenAPI-Definition neu laden |
| `MSKODA_TestNotification(12345);` | `bool` | konfigurierte Symcon-Mitteilung testen |

Bei Befehlen für fahrzeugabhängige Funktionen liefert `false` einen nicht erfolgreichen Aufruf. Der zugehörige Fehler wird intern in `LastError` geführt und bei den optionalen Diagnosevariablen soweit vorgesehen in `CommandStatus` angezeigt.

## 12. Instanzstatus und Fehlersuche

### Instanzstatus

| Code | Bedeutung |
|---:|---|
| `102` | verbunden / bereit |
| `104` | inaktiv |
| `201` | FIN oder API-Token fehlt oder ist ungültig |
| `202` | API- oder Verbindungsfehler |
| `203` | Rate-Limit / Wartezeit aktiv |

### Fehlersuche

- **Keine Verbindung:** FIN/VIN und API-Token prüfen und anschließend **Verbindung testen** ausführen.
- **Status 203:** Das API-Rate-Limit oder eine von der API vorgegebene Wartezeit ist aktiv.
- **Befehl springt sofort zurück:** Der Remote-Aufruf war nicht erfolgreich. Bei aktivierten Diagnosevariablen `CommandStatus` prüfen.
- **Wert ändert sich nach einem späteren Abruf:** Der reguläre Fahrzeugabruf hat einen anderen Zustand vom Portal geliefert und übernommen.
- **Keine Standortdaten:** Die API liefert für das Fahrzeug bzw. den Benutzer aktuell keine Standortdaten.
- **Neue API-Funktionen > 0:** Die öffentliche OpenAPI-Definition enthält mindestens eine der Modulversion noch unbekannte Operation.

Bei Fehlermeldungen niemals API-Key, S-PIN oder vollständige FIN öffentlich veröffentlichen.

## 13. Datenschutz und externe Dienste

Das Modul kommuniziert direkt mit der offiziellen MyŠkoda Public API. Für fahrzeugbezogene API-Anfragen werden die konfigurierte FIN/VIN und der API-Token verwendet.

Remote-Befehle werden ausschließlich durch eine Benutzeraktion, ein Benutzerskript oder einen Aufruf der dokumentierten öffentlichen Modulmethoden ausgelöst.

Zusätzlich lädt das Modul die öffentliche OpenAPI-Definition von Škoda. Für diesen Abruf wird kein Fahrzeug-API-Key übertragen.

FIN/VIN, API-Token und optional die S-PIN werden als Instanzkonfiguration in IP-Symcon gespeichert. Zugangsdaten sollten nicht in Fehlermeldungen, Screenshots oder öffentlichen Supportbeiträgen veröffentlicht werden.

## 14. Versionshistorie

Die Versionshistorie der Library befindet sich in [CHANGELOG.md](../CHANGELOG.md).

## 15. Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der [MIT-Lizenz](../LICENSE) veröffentlicht.

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.