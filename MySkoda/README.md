# MySkoda

MySkoda ist ein Gerätemodul für Symcon zur Anbindung eines Škoda-Fahrzeugs an die offizielle **MyŠkoda Public API**. Eine Instanz repräsentiert genau eine FIN/VIN.

Der tatsächlich verfügbare Funktionsumfang hängt vom Fahrzeug, dessen Ausstattung und den für das Fahrzeug freigegebenen MyŠkoda-Diensten ab. Das Modul wurde praktisch mit einem **Škoda Enyaq 80** getestet.

## 1. Funktionsumfang

- Fahrzeugdaten über FIN/VIN und MySkoda API-Key
- lokale FIN/VIN-Entschlüsselung in der Instanzkonfiguration
- optionales Anlegen der entschlüsselten FIN-Informationen als reine String-Variablen
- zyklischer Abruf mit einstellbarem Abfrageintervall
- Berücksichtigung der von der API gelieferten Rate-Limit-Informationen und `Retry-After`
- stabile Variablen-Idents als Schnittstelle für Skripte und weitere Module
- optionale Detail-, Standort- und Diagnosevariablen
- automatische Prüfung der öffentlichen OpenAPI-Definition auf neue, noch nicht integrierte API-Operationen
- optionale API-Key-Ablaufwarnung per Symcon-Mitteilung
- optionale Archivierung ausgewählter Fahrzeugwerte nach ausdrücklicher Aktivierung

> **Hinweis zur FIN/VIN-Entschlüsselung:** Die daraus abgeleiteten Angaben sind keine offiziellen Fahrzeugstammdaten von Škoda. Sie werden anhand öffentlich verfügbarer Herstellerinformationen, technischer Unterlagen, Typgenehmigungsdaten und nachvollziehbarer FIN-Beispiele interpretiert und können bei einzelnen Fahrzeugen unvollständig oder mehrdeutig sein. Unbekannte oder nicht eindeutig belegte Codes werden bewusst nicht geraten.

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

- Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MyŠkoda API-Key
- aktive MySkoda/Škoda-Connect-Dienste für die verwendeten Fahrzeugfunktionen
- Internetzugang von Symcon zur MyŠkoda Public API
- optional S-PIN für die Standheizung
- Archive Control nur bei Verwendung der optionalen Archivierung

Der API-Key wird in der MySkoda App unter **Profil → Smart Home → Schlüssel erstellen** erzeugt.

Offizielle API-Dokumentation: <https://public.api.connect.skoda-auto.cz/docs>

## 3. Installation

### Module Store

Im Symcon **Module Store** nach **MySkoda** suchen und das Modul installieren. Anschließend kann über **Instanz hinzufügen** eine Instanz **MySkoda** angelegt werden.

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
6. Optional **FIN-Informationsvariablen anlegen** aktivieren.
7. Optional Archivierung, Benachrichtigungen sowie Detail- und Diagnosevariablen aktivieren.

Das Standard-Abfrageintervall beträgt **300 Sekunden**. In der Konfiguration sind Werte von **180 bis 3600 Sekunden** möglich.

## 5. Konfiguration

| Einstellung | Funktion | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| FIN-Informationsvariablen | legt die lokal entschlüsselten FIN-Informationen als reine String-Variablen an | aus |
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

## 6. FIN / VIN entschlüsseln

Die konfigurierte FIN wird lokal ausgewertet. Dafür ist keine zusätzliche API-Anfrage erforderlich.

### 6.1 Aufbau und Prüfung

Die 17-stellige FIN wird in folgende Bereiche zerlegt:

| Position | Bereich | Verwendung |
|---|---|---|
| 1–3 | WMI | Hersteller-/Herkunftskennung |
| 4–8 | VDS | modellabhängige Fahrzeugbeschreibung |
| 9 | Sicherheits-/Prüfzeichen | Vergleich mit berechneter Prüfziffer |
| 10 | Modelljahr | Modelljahrcode |
| 11 | Werk | Produktionswerk, soweit bekannt |
| 12–17 | Seriennummer | laufende Fahrzeugnummer |

Vor der Interpretation wird geprüft:

- genau 17 Zeichen
- nur `A-H`, `J-N`, `P`, `R-Z` und `0-9`
- die Buchstaben `I`, `O` und `Q` sind nicht zulässig

Verwendetes Muster:

```text
^[A-HJ-NPR-Z0-9]{17}$
```

Bei formal ungültiger FIN werden keine Fahrzeugmerkmale abgeleitet.

### 6.2 Hersteller- und Modellzuordnung

Aktuell werden unter anderem folgende Herstellerkennungen berücksichtigt:

| WMI | Interpretation |
|---|---|
| `TMB` | Škoda Auto, Tschechien |
| `MEX` | Škoda Auto Volkswagen India, Indien |

Für `TMB` sind unter anderem folgende Baureihen hinterlegt:

| Code | Baureihe |
|---|---|
| `6Y` | Fabia I |
| `5J` | Fabia II / Roomster |
| `NJ` | Fabia III |
| `PJ` | Fabia IV |
| `1U` | Octavia I |
| `1Z` | Octavia II |
| `5E` | Octavia III |
| `NX` | Octavia IV |
| `3U` | Superb I |
| `3T` | Superb II |
| `3V` | Superb III |
| `NZ` | Superb IV |
| `5L` | Yeti |
| `NU` | Karoq |
| `NS` | Kodiaq I |
| `PS` | Kodiaq II |
| `NW` | Scala / Kamiq |
| `AA` | Citigo |
| `NH`, `NK` | Rapid |
| `NY` | Enyaq / Elroq; weitere VDS-Stellen werden zur Unterscheidung verwendet |

Für `MEX` sind aktuell Kushaq (`PA`), Slavia (`PB`) und Kylaq (`PC`) hinterlegt.

Ein Modellcode allein ist nicht in jedem Fall eindeutig. Besonders `NY` wird von Enyaq und Elroq verwendet. Das Modul kombiniert deshalb Modellcode, Modelljahr und weitere VDS-Zeichen.

### 6.3 Modelljahr

Stelle 10 wird als Modelljahr interpretiert. Der bekannte 30-Jahres-Zyklus wird mit dem zeitlichen Bereich der jeweiligen Baureihe kombiniert.

Beispiele:

| Code | Modelljahr |
|---|---:|
| `L` | 2020 |
| `M` | 2021 |
| `N` | 2022 |
| `P` | 2023 |
| `R` | 2024 |
| `S` | 2025 |
| `T` | 2026 |
| `V` | 2027 |

**Modelljahr ist nicht gleich Produktionsdatum oder Erstzulassung.**

### 6.4 Produktionswerk und Seriennummer

Für bekannte europäische `TMB`-FINs werden hinterlegte Werkscodes ausgewertet. Wenn eine Zuordnung nicht ausreichend belegt ist, bleibt das Produktionswerk leer.

Die Stellen 12 bis 17 werden als Serien-/Produktionsnummer ausgegeben. Daraus wird kein Produktionsdatum berechnet.

### 6.5 Prüfzeichen

Stelle 9 wird zusätzlich mit der verbreiteten VIN-Prüfziffer nach dem gewichteten Modulo-11-Verfahren verglichen.

Gewichte:

```text
Position: 1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17
Gewicht:  8  7  6  5  4  3  2 10  0  9  8  7  6  5  4  3  2
```

Da Škoda bei europäischen Fahrzeugen von einem Sicherheitscode spricht, wird eine Abweichung bewusst als **nicht bestätigt** und nicht pauschal als ungültige FIN behandelt.

### 6.6 Detaillierte VDS-Auswertung

Für bekannte Baureihen werden weitere VDS-Merkmale ausgewertet, wenn dafür belastbare Zuordnungen vorliegen.

Beim **Enyaq** werden beispielsweise Karosserie, Links-/Rechtslenker, Heck-/Allradantrieb, Leistung, Variante und Rückhaltesystem interpretiert. Beim **Elroq** wird die gemeinsame `NY`-Baureihenkennung anhand weiterer Zeichen unterschieden. Beim **Karoq** werden bekannte Karosserie-/Antriebs-, Motor- und Rückhaltesystemcodes ausgewertet.

Unbekannte Kombinationen bleiben leer oder mehrdeutig und werden nicht geraten.

### 6.7 Optionale FIN-Informationsvariablen

Wenn **FIN-Informationsvariablen anlegen** aktiviert ist, können folgende reine String-Variablen entstehen:

| Ident | Inhalt |
|---|---|
| `VINWMI` | WMI |
| `VINVDS` | VDS |
| `VINVIS` | VIS |
| `VINManufacturer` | Hersteller |
| `VINCountry` | Herkunftsland |
| `VINModel` | Modell/Baureihe |
| `VINModelCode` | Modellcode |
| `VINBody` | Karosserie |
| `VINSteering` | Links-/Rechtslenker |
| `VINDrive` | Antriebsart |
| `VINPower` | Leistung |
| `VINVariant` | Modellvariante |
| `VINRestraint` | Rückhaltesystem |
| `VINModelYear` | Modelljahr |
| `VINPlant` | Produktionswerk |
| `VINSerialNumber` | Seriennummer |
| `VINCheckDigit` | Prüfzeichen und Prüfergebnis |

Alle FIN-Variablen sind Strings ohne Icon und ohne spezielle Darstellung. Sie werden nur beim erstmaligen Anlegen oder bei Änderung der konfigurierten FIN aktualisiert. Das normale MySkoda-Polling verändert sie nicht. Bereits angelegte Variablen bleiben bestehen, auch wenn die Option später deaktiviert wird.

### 6.8 Beispiel

Beispiel-FIN:

```text
TMBJC7NY5NF017514
```

Aktuelle Interpretation des Moduls:

```text
Hersteller: Škoda Auto
Land: Tschechien
Modell: Enyaq
Karosserie: SUV
Lenkung: Linkslenker
Antrieb: Heckantrieb
Leistung: 150 kW / 204 PS
Variante: Enyaq iV 80
Modelljahr: 2022
Produktionswerk: Mladá Boleslav
Seriennummer: 017514
Prüfzeichen: 5, Berechnung: 5
```

Nicht aus der FIN abgeleitet werden unter anderem exaktes Produktionsdatum, Erstzulassung, komplette Ausstattung/PR-Codes, Wärmepumpe, Canton, HUD, Anhängerkupplung, DCC, Softwareversion, Batteriezellhersteller, SOH, Servicehistorie oder TPI-Anwendbarkeit.

## 7. Statusvariablen und Darstellungen

Das Modul legt ausschließlich eigene Variablen unterhalb der MySkoda-Instanz an. Es werden keine Dummy-Instanzen, Kategorien oder Links erzeugt.

Vorhandene Variablen bleiben benutzerverwaltet. Namen, Positionen und andere benutzerseitig veränderbare Objekteigenschaften werden bei späteren Aktualisierungen nicht fortlaufend überschrieben.

### 7.1 Standard-Datenpunkte

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

### 7.2 Optionale Detail- und Diagnosevariablen

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

### 7.3 Profile und Darstellungen

Das Modul verwendet die nativen Darstellungen von Symcon. Es werden keine benutzerdefinierten Variablenprofile angelegt.

Der Lademodus wird nur gesendet, wenn er in der vom Fahrzeug gemeldeten Liste `charging.settings.availableChargeModes` enthalten ist. Die im Modul bekannten API-Werte sind:

- `MANUAL`
- `TIMER`
- `TIMER_CHARGING_WITH_CLIMATISATION`
- `PREFERRED_CHARGING_TIMES`
- `ONLY_OWN_CURRENT`
- `IMMEDIATE_DISCHARGING`
- `HOME_STORAGE_CHARGING`

Nicht jeder dieser Werte muss von jedem Fahrzeug unterstützt werden.

## 8. Befehlslogik für Remote-Befehle

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

## 9. API-Diagnose und neue API-Funktionen

Nach einem erfolgreichen Fahrzeugabruf prüft das Modul zusätzlich die öffentliche OpenAPI-Definition der MyŠkoda Public API. Die Definition wird intern bis zu 24 Stunden zwischengespeichert.

`NewApiFeatures` zeigt die Anzahl der API-Operationen, die der aktuellen Modulversion noch nicht bekannt sind:

- `0` – keine unbekannte Operation erkannt
- `> 0` – die öffentliche API enthält zusätzliche, noch nicht integrierte Operationen

Neue Operationen werden nicht automatisch als Variablen oder Befehle angelegt.

Mit `MSKODA_GetRemoteOperations()` kann die vom Fahrzeug gelieferte Liste der verfügbaren Remote-Operationen ausgelesen werden. Das Modul verwendet `vehicle.operations` und `vehicle.remoteOperations` als Fallback.

## 10. Archivierung

Die Archivierung ist standardmäßig **aus** und wird nur nach ausdrücklicher Aktivierung eingerichtet.

Einmalig werden folgende Variablen für das Logging im Archive Control aktiviert:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird als Zähler eingerichtet. Werte `<= 0` werden nicht übernommen. Nach der erstmaligen Einrichtung verändert das Modul spätere Benutzeranpassungen im Archive Control nicht mehr.

## 11. Visualisierung

Die vom Modul angelegten Variablen können direkt in den Symcon-Visualisierungen verwendet werden.

Für eine zusätzliche Fahrzeugdarstellung kann optional das separate Modul [IPSymconEVTile](https://github.com/taloriko/IPSymconEVTile) verwendet werden. MySkoda selbst legt keine zusätzliche Objektstruktur für die Visualisierung an.

Standortdaten werden nur gesetzt, wenn die MyŠkoda Public API sie für das Fahrzeug und den jeweiligen Benutzer liefert.

## 12. PHP-Befehlsreferenz

In den Beispielen ist `12345` die Instanz-ID der MySkoda-Instanz.

| Befehl | Rückgabe | Funktion |
|---|---|---|
| `MSKODA_Update(12345);` | `void` | Fahrzeugdaten sofort aktualisieren |
| `MSKODA_TestConnection(12345);` | `bool` | Verbindung und Fahrzeugabruf testen |
| `MSKODA_GetRawData(12345);` | `string` | letzte vollständige Fahrzeugantwort als JSON ausgeben |
| `MSKODA_GetVINData(12345);` | `string` | lokal entschlüsselte FIN-Informationen als JSON ausgeben |
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

## 13. Instanzstatus und Fehlersuche

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

## 14. Datenschutz und externe Dienste

Das Modul kommuniziert direkt mit der offiziellen MyŠkoda Public API. Für fahrzeugbezogene API-Anfragen werden die konfigurierte FIN/VIN und der API-Token verwendet.

Remote-Befehle werden ausschließlich durch eine Benutzeraktion, ein Benutzerskript oder einen Aufruf der dokumentierten öffentlichen Modulmethoden ausgelöst.

Zusätzlich lädt das Modul die öffentliche OpenAPI-Definition von Škoda. Für diesen Abruf wird kein Fahrzeug-API-Key übertragen.

Die FIN/VIN-Entschlüsselung erfolgt vollständig lokal innerhalb der Instanz und verursacht keine zusätzliche externe Anfrage.

FIN/VIN, API-Token und optional die S-PIN werden als Instanzkonfiguration in Symcon gespeichert. Zugangsdaten sollten nicht in Fehlermeldungen, Screenshots oder öffentlichen Supportbeiträgen veröffentlicht werden.

## 15. Versionshistorie

Die Versionshistorie der Library befindet sich in [CHANGELOG.md](../CHANGELOG.md).

## 16. Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der [MIT-Lizenz](../LICENSE) veröffentlicht.

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.