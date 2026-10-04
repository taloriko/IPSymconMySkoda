# MySkoda

MySkoda ist ein Gerätemodul für Symcon. Eine Instanz repräsentiert ein Fahrzeug anhand seiner FIN/VIN und verwendet ausschließlich die offizielle MyŠkoda Public API. Das Modul legt eigene Variablen und bei verfügbaren Bildquellen Bildmedien direkt unterhalb der Instanz an.

**Modul-URL:** <https://github.com/taloriko/IPSymconMySkoda/tree/main/MySkoda>

## Funktionsumfang

Das Modul liest Fahrzeug-, Lade-, Kraftstoff-, Klima-, Standort- und Statusdaten. Es unterstützt Lade- und Klimabefehle, Standheizung, aktive Lüftung sowie das Lesen und Übertragen von Ladeprofilen per PHP. Welche Daten und Befehle nutzbar sind, hängt vom Fahrzeug und seinen Diensten ab.

Fahrzeugvariablen entstehen anhand der gelieferten API-Felder. Zusätzliche skalare Felder werden automatisch erfasst. Optionale Funktionen sind Diagnosevariablen, lokale FIN-Informationsvariablen, Archivierung ausgewählter Werte und Mitteilungen zum Ablauf des API-Keys. Fahrzeugbilder werden lokal als Symcon-Medien gespeichert.

## 1. Voraussetzungen und Kompatibilität

- Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN und ein für das Fahrzeug gültiger MySkoda API-Key
- aktive MySkoda/Škoda-Connect-Dienste für die verwendeten Fahrzeugfunktionen
- Internetzugang von Symcon zur MyŠkoda Public API
- S-PIN für den Start der Standheizung
- Archive Control nur bei Verwendung der optionalen Archivierung

Der API-Key wird in der MySkoda App unter **Profil → Smart Home → Schlüssel erstellen** erzeugt. Dort kann auch die FIN/VIN kopiert werden.

### Fahrzeugkompatibilität

Das Fahrzeug muss über die offizielle Public API mit dem eigenen API-Key erreichbar sein. Der Code verarbeitet Elektrofahrzeugdaten sowie die gelieferten Kraftstoff- und Antriebsdaten von Verbrennern und Hybridfahrzeugen. Daraus ergibt sich keine pauschale Freigabe für alle Modelle, Ausstattungen oder Baujahre. Fehlende Dienste und nicht angebotene Remote-Operationen können Funktionen einschränken.

Praktische Einzelwertnachweise für Enyaq 80 von 2022 und 2023 stehen in der [Fahrzeug-Kompatibilitätsmatrix](../tests/README.md). Für den Enyaq 2023 ist insbesondere der Schiebedachzustand `OPEN` bestätigt; andere Felder bleiben gemäß Matrix zu prüfen. Automatisierte Verbrenner-Prüfdaten bestätigen die Datenverarbeitung im Test, nicht die tatsächliche Befehlsausführung an einem weiteren Fahrzeug. Die lokale FIN-Interpretation ist kein Nachweis der API-Kompatibilität oder vollständigen Ausstattung.

## 2. Installation

### Module Store

Im Symcon **Module Store** nach **MySkoda** suchen, das Modul installieren und anschließend eine Instanz **MySkoda** anlegen.

### Manuell über Module Control

Alternativ das Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend kann unter **Instanz hinzufügen** eine Instanz **MySkoda** angelegt werden. Für mehrere Fahrzeuge wird jeweils eine eigene Instanz konfiguriert.

## 3. Konfiguration

| Eigenschaft | Einstellung | Standard / Verhalten |
|---|---|---|
| `VIN` | FIN/VIN | leer; 17 Zeichen, keine Buchstaben I, O oder Q |
| `APIToken` | API-Token | leer; zum Fahrzeug gehörender API-Key |
| `Interval` | Abfrageintervall | 300 s; Formularbereich 180–3600 s; Laufzeitminimum 180 s |
| `EnableRemote` | Remote-Steuerung | an; steuert Standard-Bedienaktionen und die Ausführung von Remote-Befehlen |
| `ClimateWithoutExternalPower` | Klimatisierung ohne externe Stromversorgung erlauben | an; nur sichtbar und beim Klimastart mitgesendet, wenn `airConditioning.airConditioningWithoutExternalPower` vorhanden und nicht `null` ist |
| `SPIN` | S-PIN | leer; für den Standheizungsstart; sichtbar bei geliefertem Standheizungsstatus oder angebotener Startoperation |
| `ShowDetails` | Detail- und Diagnosevariablen anlegen | aus; legt ausschließlich zusätzliche Diagnose- und API-Informationsvariablen an, nicht die normalen Fahrzeugdetails |
| `CreateVINVariables` | FIN-Informationsvariablen anlegen | aus; legt 17 lokale String-Variablen an |
| `EnableChargingHistory` | Fahrzeugdaten archivieren | aus; richtet Logging einmalig ein, sobald alle vier benötigten Variablen vorhanden sind; siehe Abschnitt 10 |
| `NotifyKeyExpiry` | API-Key-Ablaufwarnung | aus; Mitteilung bei höchstens 30 Tagen Restlaufzeit |
| `NotificationInstanceID` | Visualisierung für Mitteilungen | 0; Kachel-Visualisierung oder WebFront auswählen |

### Erste Verbindung und Bedienung

FIN/VIN und API-Token eintragen und die Konfiguration übernehmen. Bei neuen Zugangsdaten oder noch fehlenden gespeicherten Fahrzeugdaten wird die Verbindung automatisch geprüft. Die Rückmeldung steht im Bereich **Verbindung**. Einen gesonderten Button „Verbindung testen“ gibt es nicht.

Die Schaltflächen **Jetzt aktualisieren**, **Fahrzeug Bilder aktualisieren**, **Rohe Fahrzeugantwort anzeigen** und **Mitteilung testen** gehören zum Benutzerbetrieb. **Jetzt aktualisieren** ist an eine erfolgreiche Verbindung gebunden und verwendet denselben Fahrzeugabruf wie der zyklische Timer. Es umgeht weder Wartezeiten noch die für zyklische Abrufe geltende Kontingentreserve. Der Bildbutton lädt gespeicherte Bildquellen erneut; der Rohantwort-Button zeigt den gespeicherten Antworttext an. **Mitteilung testen** prüft das ausgewählte Mitteilungsziel unabhängig von der Ablaufwarnung.

Im Formular ist zunächst nur **Verbindung** aufgeklappt. Weitere Einstellungen befinden sich in **Abfrage und Steuerung**, **Archivierung**, **Mitteilungen** und **Zusätzliche Daten**. Die FIN-Auswertung steht vor **Hilfe und Dokumentation**. Nach dem Empfang der Fahrzeugdaten sind fahrzeugabhängige Einstellungen beim erneuten Öffnen des Formulars sichtbar.

### Bedeutung von „Detail- und Diagnosevariablen anlegen“

`ShowDetails` wird für normale Fahrzeugdaten und Remote-Befehle nicht benötigt. Fahrzeugname, Kennzeichen, Parkadresse, Koordinaten, Türen, Fenster und gelieferte Lade-/Klimadetails werden unabhängig von dieser Option angelegt.

Die Option steuert die Erstanlage von `ApiKeyExpiresAtVar`, `RequestsRemaining`, `PartialErrors`, `PendingCommands` und `CommandStatus`. Sobald ein Fahrzeugdatensatz vorliegt, kommen `APISupportedFeatures` und bei geliefertem `charging.settings.availableChargeModes` zusätzlich `APIAvailableChargeModes` hinzu. Die Daten dienen Diagnose, Befehlsrückmeldung und Übersicht über die API. Die eigentlichen Fahrzeugabfragen, Kontingentverwaltung und Befehlsverarbeitung verwenden interne Attribute und funktionieren auch ohne diese sichtbaren Variablen.

Das Ausschalten der Option entfernt oder versteckt bereits angelegte Variablen nicht. Ihre Werte werden bei den jeweiligen Aktualisierungen weiter gepflegt. `LastUpdate` und `ApiKeyWarning` sind unabhängig von der Option vorhanden. Der normale Datenpunkt `APIChargingSettingsAvailableChargeModes` ist ebenfalls unabhängig von ihr und wird bei geliefertem API-Feld angelegt.

## 4. Variablen und Objektverwaltung

Die Anzahl der Variablen richtet sich nach den gelieferten Fahrzeugdaten und den aktivierten Zusatzoptionen. Ohne Fahrzeugdaten werden zunächst `LastUpdate` und `ApiKeyWarning` angelegt. Fahrzeugbezogene Variablen entstehen, sobald ihr Quellfeld vorhanden und nicht `null` ist; auch `false` und `0` sind gültige gelieferte Werte. Bekannte Felder mit leerem String oder leerer Liste können ebenfalls eine Variable erzeugen. Es gibt keine feste Gesamtzahl pro Fahrzeug. Die optionalen Bildmedien zählen nicht als Variablen.

Fehlende Variablen erhalten bei der Anlage den vorgesehenen Datentyp, Namen, die Position und Darstellung. Bestehende Variablen werden nicht gelöscht oder automatisch auf einen anderen Typ umgestellt. Die reguläre Registrierung lässt Namen, Positionen, Icons und Darstellungen bestehen. Bei einer Typ- oder Ident-Kollision wird grundsätzlich eine Meldung protokolliert; das betroffene Objekt wird nicht automatisch ersetzt. Eine vorhandene Integer-Variable für die Ladegeschwindigkeit wird weitergeführt und mit gerundeten Werten beschrieben.

**Ausnahme bei generischen API-Metadaten:** Bei den vier bekannten Feldern für geschätzten Klimazeitpunkt, AC-Ladestrom in Ampere, verfügbare Lademodi und Ladegeschwindigkeit prüft das Modul, ob noch ein generischer Ident-/API-Pfad-Name oder eine generische Darstellung vorhanden ist. Ein solcher Name kann durch die hinterlegte Bezeichnung ersetzt werden. Eine generische Darstellung ohne Icon und Einheit kann bei leerer benutzerdefinierter Darstellung durch die vorgesehene Darstellung als benutzerdefinierte Darstellung ersetzt werden. Positionen werden dabei nicht geändert. Eine pauschale Zusage, dass keinerlei Metadaten nach der Anlage verändert werden, gilt deshalb nicht.

Das Ausschalten einer Erstellungsoption löscht vorhandene Variablen nicht. Laufende API-Werte werden weiterhin aktualisiert, soweit sie geliefert werden. FIN-Variablen werden bei ihrer Erstanlage und bei Änderung der konfigurierten FIN beschrieben. Die Standard-Bedienaktionen werden nach `EnableRemote` und bei der Standheizung zusätzlich nach deren Voraussetzungen gesetzt.

Die folgenden Positionen sind Vorgaben für die **Erstanlage**. Reihenfolge der bekannten Datenpunkte: API-Fahrzeugdaten, abgeleitete Betriebsdaten, lokale FIN-Daten, zusätzliche API-Fahrzeuginformationen. Automatisch erkannte weitere Felder folgen im Bereich ab 2000. **API-Feld** bedeutet Anlage bei vorhandenem, nicht `null` enthaltendem Quellfeld; **Immer** bedeutet unabhängig von Fahrzeugdaten; **Diagnose** erfordert `ShowDetails` für die Anlage. Bekannte Anzeigen sind deutsch, während die Idents als Programmierschnittstelle erhalten bleiben.

### 4.1 API-Fahrzeugdaten

API-Pfade beziehen sich, sofern nicht anders angegeben, auf `vehicle` der Fahrzeugantwort.

| Position | Ident | Anzeige | Typ | Anlage | Quelle / Bedeutung |
|---:|---|---|---|---|---|
| 10 | `VehicleName` | Fahrzeugname | String | API-Feld | `name` |
| 20 | `LicensePlate` | Kennzeichen | String | API-Feld | `licensePlate` |
| 30 | `VIN` | FIN / VIN | String | API-Feld | `vin` aus der API, nicht aus der lokalen Interpretation |
| 100 | `ClimateState` | Klimastatus | String | API-Feld | `airConditioning.state` |
| 110 | `AirConditioningAtUnlock` | Klimatisierung beim Entriegeln | Boolean | API-Feld | `airConditioning.airConditioningAtUnlock` |
| 120 | `TargetTemperature` | Solltemperatur | Float | API-Feld | `airConditioning.targetTemperature.value`; bedienbar |
| 130 | `TargetTemperatureUnit` | Einheit Solltemperatur | String | API-Feld | `airConditioning.targetTemperature.unit` |
| 135 | `APIAirConditioningEstimatedReachOfTargetTemperatureAt` | Voraussichtliche Zieltemperatur erreicht um | String | API-Feld | `airConditioning.estimatedReachOfTargetTemperatureAt`; als `d.m.Y H:i:s` in der PHP-Systemzeitzone formatiert; nicht als Unix-Zeitstempel gespeichert |
| 140 | `WindowHeatingEnabled` | Scheibenheizung aktiviert | Boolean | API-Feld | `airConditioning.windowHeating.enabled` |
| 150 | `WindowHeatingFront` | Frontscheibenheizung | String | API-Feld | `airConditioning.windowHeating.front` |
| 160 | `WindowHeatingRear` | Heckscheibenheizung | String | API-Feld | `airConditioning.windowHeating.rear` |
| 200 | `AtSavedChargingLocation` | An gespeichertem Ladeort | Boolean | API-Feld | `charging.isVehicleInSavedLocation` |
| 210 | `AutoUnlockPlug` | Automatische Steckerentriegelung | String | API-Feld | `charging.settings.autoUnlockPlugWhenCharged` |
| 230 | `BatteryCareTargetSOC` | Battery-Care-Ziel | Integer | API-Feld | `charging.settings.batteryCareModeTargetValueInPercent`; % |
| 240 | `BatteryCareMode` | Battery Care Mode | String | API-Feld | `charging.settings.chargingCareMode` |
| 250 | `MaxChargeCurrentAC` | Maximaler AC-Ladestrom | String | API-Feld | `charging.settings.maxChargeCurrentAc`; Status, kein Amperewert |
| 255 | `APIChargingSettingsMaxChargeCurrentAcAmpere` | Maximaler AC-Ladestrom | Integer | API-Feld | `charging.settings.maxChargeCurrentAcAmpere`; Amperewert mit Einheit A; nur lesbar |
| 258 | `APIChargingSettingsAvailableChargeModes` | Verfügbare Lademodi | String | API-Feld | `charging.settings.availableChargeModes`; durch Kommas getrennte Liste |
| 260 | `ChargeMode` | Lademodus | Integer | API-Feld | Index für `charging.settings.preferredChargeMode`; bedienbar |
| 270 | `TargetSOC` | Ladelimit | Integer | API-Feld | `charging.settings.targetStateOfChargeInPercent`; %; bedienbar |
| 280 | `Range` | Reichweite | Integer | API-Feld | `charging.status.battery.remainingCruisingRangeInMeters`; gerundet in km |
| 290 | `StateOfCharge` | Ladezustand | Integer | API-Feld | `charging.status.battery.stateOfChargeInPercent`; % |
| 300 | `ChargePower` | Ladeleistung | Float | API-Feld | `charging.status.chargePowerInKw`; mit 1000 multipliziert, gespeichert in W |
| 310 | `FullyChargedAt` | Vollgeladen um | Integer | API-Feld | `charging.status.fullyChargedAt`; Unix-Zeitstempel |
| 312 | `PlugConnectionState` | Ladestecker Anschlussstatus | String | API-Feld | `charging.status.plugConnectionState`; Anschlusszustand des Steckers |
| 314 | `PlugLockState` | Ladestecker Verriegelungsstatus | String | API-Feld | `charging.status.plugLockState`; Verriegelungszustand des Steckers |
| 320 | `RemainingChargingTime` | Restladezeit | Integer | API-Feld | `charging.status.remainingTimeToFullyChargedInMinutes`; min |
| 325 | `APIChargingStatusChargingRateInKilometersPerHour` | Ladegeschwindigkeit | Float | API-Feld | `charging.status.chargingRateInKilometersPerHour`; km/h; Speicherung als Float, Anzeige mit einer Nachkommastelle |
| 330 | `ChargingState` | Ladestatus | String | API-Feld | `charging.status.state` |
| 340 | `ChargeType` | Ladeart | String | API-Feld | `charging.status.chargeType` |
| 360 | `FuelLevelPercent` | Tankfüllstand | Integer | API-Feld | `fuelStatus.primaryEngineRange.currentFuelLevelInPercent`; % |
| 370 | `PrimaryEngineSOC` | Primärer Antrieb SoC | Integer | API-Feld | `fuelStatus.primaryEngineRange.currentSoCInPercent`; %; nicht pauschal mit einem Elektro-Batteriestand gleichsetzen |
| 380 | `FuelRange` | Kraftstoff-Reichweite | Integer | API-Feld | `fuelStatus.primaryEngineRange.remainingRangeInKm`; Reichweite des primären Antriebs in km |
| 390 | `TotalRange` | Gesamtreichweite | Integer | API-Feld | `fuelStatus.totalRangeInKm`; km |
| 400 | `Mileage` | Kilometerstand | Integer | API-Feld | `odometer.mileageInKm`; gerundet, nur Werte größer 0 übernommen |
| 600 | `ParkingState` | Parkstatus | String | API-Feld | `parkingPosition.state` |
| 610 | `ParkingAddress` | Parkadresse | String | API-Feld | `parkingPosition.formattedAddress` |
| 620 | `Latitude` | Breitengrad | Float | API-Feld | `parkingPosition.latitude`, alternativ `parkingPosition.gpsCoordinates.latitude` oder `.lat` |
| 630 | `Longitude` | Längengrad | Float | API-Feld | `parkingPosition.longitude`, alternativ `parkingPosition.gpsCoordinates.longitude`, `.lon` oder `.lng` |
| 700 | `DoorsLocked` | Türverriegelungsstatus | String | API-Feld | `status.overall.doorsLocked` |
| 710 | `Locked` | Fahrzeugverriegelungsstatus | String | API-Feld | `status.overall.locked` |
| 720 | `DoorsOpen` | Türen | String | API-Feld | `status.overall.doors` |
| 730 | `WindowsOpen` | Fenster | String | API-Feld | `status.overall.windows` |
| 740 | `LightsOn` | Licht | String | API-Feld | `status.overall.lights` |
| 750 | `ReliableLockStatus` | Zuverlässiger Verriegelungsstatus | String | API-Feld | `status.overall.reliableLockStatus` |
| 800 | `SunroofOpen` | Schiebedach | String | API-Feld | `status.detail.sunroof` |
| 810 | `TrunkOpen` | Kofferraum | String | API-Feld | `status.detail.trunk` |
| 820 | `BonnetOpen` | Motorhaube | String | API-Feld | `status.detail.bonnet` |

Die drei Verriegelungswerte werden getrennt aus den jeweiligen Feldern gelesen. Es findet keine Priorisierung oder gegenseitige Ersetzung statt. Anschluss und Verriegelung des Ladesteckers sind ebenfalls getrennte Zustände und keine Bestätigung eines laufenden Ladevorgangs.

### 4.2 Abgeleitete Betriebs- und Diagnosewerte

| Position | Ident | Anzeige | Typ | Anlage | Bedeutung |
|---:|---|---|---|---|---|
| 900 | `Climate` | Klimatisierung | Boolean | API-Feld | aus `airConditioning.state` abgeleitet; Start/Stopp bedienbar |
| 905 | `AuxiliaryHeating` | Standheizung | Boolean | API-Feld | aus `auxiliaryHeating.state` abgeleitet; Standardaktion nur mit Remote-Freigabe, S-PIN und angebotenen Start-/Stopp-Operationen |
| 910 | `Charging` | Laden | Boolean | API-Feld | aus `charging.status.state` abgeleitet; wahr bei `CHARGING` oder `CONSERVING`; Start/Stopp bedienbar |
| 920 | `LastUpdate` | Letzte Aktualisierung | Integer | Immer | lokaler Unix-Zeitstempel des letzten erfolgreichen Fahrzeugabrufs |
| 930 | `ApiKeyWarning` | API-Key Warnung | Boolean | Immer | bekanntes Ablaufdatum erreicht oder höchstens 30 Tage entfernt |
| 940 | `ApiKeyExpiresAtVar` | API-Key gültig bis | Integer | Diagnose | aus HTTP-Header `x-api-key-expires-at`; Unix-Zeitstempel |
| 950 | `RequestsRemaining` | Verbleibende API-Anfragen | Integer | Diagnose | gespeichertes Restkontingent beim Fahrzeugabruf |
| 960 | `PartialErrors` | API-Teilfehler | String | Diagnose | `errors` der Antwort als JSON; leer, wenn keine Teilfehler vorliegen |
| 980 | `PendingCommands` | Ausstehende Befehle | Integer | Diagnose | Anzahl aktuell geführter Befehlsvorgänge während synchroner Anfragen |
| 990 | `CommandStatus` | Befehlsstatus | String | Diagnose | Übertragungsstatus oder Ergebnis des letzten Befehls |

`Climate` ist wahr bei `ON`, `COOLING`, `HEATING`, `HEATING_AUXILIARY` oder `VENTILATION`. Andere gelieferte, nicht leere Klimazustände ergeben `false`; fehlt der Klimazustand, bleibt der Wert erhalten. Für genaue Zustandsauswertungen ist `ClimateState` zu verwenden.

`Charging` wird bei `READY_FOR_CHARGING`, `CONNECT_CABLE`, `CHARGING_INTERRUPTED` oder `ERROR` auf `false` gesetzt. Ein fehlender oder unbekannter Ladestatus lässt den Bedienwert unverändert. `ChargingState` übernimmt gelieferte Strings; ein fehlender oder mit `null` gelieferter Zustand überschreibt einen vorhandenen Wert nicht. Noch nie gelieferte Ladefelder erhalten keine Platzhaltervariablen.

`AuxiliaryHeating` ist bei `ON`, `HEATING`, `HEATING_AUXILIARY` oder `VENTILATION` wahr und bei `OFF` falsch. Fehlende oder andere Zustände ändern den bisherigen Bedienwert nicht. Der genaue API-Zustand steht getrennt in `APIAuxiliaryHeatingState`.

### 4.3 Lokale FIN-Informationen

Die 17 optionalen String-Variablen liegen auf den Positionen 1100 bis 1260. Ihre vollständige Tabelle und der lokale Decoder sind in [README_FIN_VIN.md](README_FIN_VIN.md) beschrieben. Sie verwenden keine Icons oder besonderen Darstellungen. `CreateVINVariables` steuert ihre Erstanlage unabhängig von `ShowDetails`.

### 4.4 Zusätzliche API-Fahrzeuginformationen

Diese Werte werden aus den zuletzt empfangenen API-Daten aktualisiert; sie sind nicht Teil der lokalen FIN-Interpretation. Fahrzeug- und Antriebstypen sowie Standheizungs- und Lüftungsstatus besitzen eigene Darstellungen. Die Informationslisten `APISupportedFeatures`, `APIAvailableChargeModes` und `APIRemoteOperations` werden als Strings ohne besondere Darstellung angelegt.

| Position | Ident | Anzeige | Typ | Anlage | Quelle / Bedeutung |
|---:|---|---|---|---|---|
| 1300 | `APICarType` | API Fahrzeugtyp | String | API-Feld | `fuelStatus.carType` |
| 1310 | `APIPrimaryEngineType` | API Primärer Antriebstyp | String | API-Feld | `fuelStatus.primaryEngineRange.engineType` |
| 1320 | `APISecondaryEngineType` | API Sekundärer Antriebstyp | String | API-Feld | `fuelStatus.secondaryEngineRange.engineType` |
| 1330 | `APISupportedFeatures` | API Unterstützte Funktionen | String | Diagnose + Fahrzeugdaten | aus vorhandenen API-Bereichen und bestimmten Fehlerkategorien abgeleitet |
| 1340 | `APIAvailableChargeModes` | API Verfügbare Lademodi | String | Diagnose + API-Feld | `charging.settings.availableChargeModes`; zusätzliche Informationsliste mit Hinweisen bei fehlenden Daten |
| 1350 | `APIRemoteOperations` | API Remote-Operationen | String | API-Schlüssel vorhanden | `operations`, alternativ `remoteOperations`; Anlage auch bei leerem oder `null` enthaltendem Schlüssel; Liste oder JSON |
| 1360 | `APIAuxiliaryHeatingState` | API Standheizungsstatus | String | API-Feld | `auxiliaryHeating.state` |
| 1370 | `APIActiveVentilationState` | API Lüftungsstatus | String | API-Feld | `activeVentilation.state` |
| 1380 | `AuxiliaryHeatingDuration` | Standheizungsdauer | Integer | API-Feld | `auxiliaryHeating.durationInSeconds`; durch 60 geteilt und auf ganze Minuten gerundet; nur lesbar |

`APISupportedFeatures` ist ein abgeleiteter Hinweis auf API-Bereiche und kein vollständiger Ausstattungsnachweis. Einfache Listen werden mit Kommas verbunden, strukturierte Informationswerte als JSON ausgegeben. `APIAvailableChargeModes` ist eine zusätzliche Diagnoseansicht desselben API-Feldes, das unabhängig davon in `APIChargingSettingsAvailableChargeModes` bereitsteht.

### 4.5 Zusätzliche automatisch erkannte API-Felder

Nicht bereits zugeordnete skalare API-Felder werden unabhängig von `ShowDetails` als weitere Variablen angelegt. Der Datentyp wird aus dem gelieferten JSON-Wert abgeleitet: Boolean, Integer, Float oder String. Nicht leere Listen aus einfachen Werten werden als durch Kommas getrennter String zusammengefasst. `null`, leere Listen und Listen mit Objekten werden nicht als generische Einzelvariablen übernommen. Verschachtelte Objekte werden bis zu ihren skalaren Feldern durchlaufen.

Der Ident beginnt mit `API` und wird aus den Teilen des API-Pfades zusammengesetzt, beispielsweise `APIAirConditioningAirConditioningWithoutExternalPower`. Sehr lange Idents werden gekürzt und durch einen Hash ergänzt. Der Anzeigename wird aus dem API-Pfad abgeleitet und ist nicht zwangsläufig deutsch. Die Position wird aus dem Pfad berechnet und liegt zwischen 2000 und 101999; diese Zusatzfelder folgen deshalb nicht der Reihenfolge der API-Antwort.

Bereits bekannte Quellfelder sowie `renderUrl`, `operations`, `remoteOperations` und untergeordnete `carCapturedTimestamp`-Felder werden nicht zusätzlich generisch angelegt. Eine automatische Variable ist ein Lesewert und erhält durch ihre Erkennung keinen Schreibbefehl. Ihre Einheit wird nicht automatisch umgerechnet. Listen von Ladeprofil-Objekten bleiben über `GetChargingProfiles` zugänglich.

## 5. Zustände und Einheiten

| Datenpunktgruppe | In den Darstellungen hinterlegte Werte |
|---|---|
| Türen, Fenster, Schiebedach, Kofferraum, Motorhaube | `CLOSED`, `OPEN`, `UNSUPPORTED`, `UNKNOWN` |
| `DoorsLocked`, `Locked` | `YES`, `NO`, `OPENED`, `TRUNK_OPENED`, `UNKNOWN` |
| `ReliableLockStatus` | `LOCKED`, `UNLOCKED`, `UNKNOWN` |
| Licht und Scheibenheizung | `OFF`, `ON`, `INVALID`, `UNKNOWN` |
| `ClimateState` | `OFF`, `ON`, `COOLING`, `HEATING`, `HEATING_AUXILIARY`, `VENTILATION`, `INVALID`, `UNKNOWN` |
| `ChargingState` | `READY_FOR_CHARGING`, `CHARGING`, `CONSERVING`, `CONNECT_CABLE`, `CHARGING_INTERRUPTED`, `ERROR`, `UNKNOWN` |
| `PlugConnectionState` | `CONNECTED`, `DISCONNECTED`, `UNKNOWN` |
| `PlugLockState` | `LOCKED`, `UNLOCKED`, `UNKNOWN` |
| `ParkingState` | `PARKED`, `IN_MOTION`, `MOVING`, `DRIVING`, `UNKNOWN` |
| `ChargeType` | `AC`, `DC`, `OFF` |
| `BatteryCareMode` | `ACTIVATED`, `DEACTIVATED`, `ACTIVE`, `INACTIVE`, `UNKNOWN` |
| `MaxChargeCurrentAC` | `MAXIMUM`, `REDUCED`, `UNKNOWN` |
| `AutoUnlockPlug` | `OFF`, `ON`, `PERMANENT`, `UNKNOWN` |
| Fahrzeug-/Antriebstyp | `GASOLINE`, `DIESEL`, `ELECTRIC`, `BEV`, `HYBRID`, `PHEV`, `UNKNOWN` |
| `APIAuxiliaryHeatingState` | `OFF`, `ON`, `HEATING`, `VENTILATION`, `UNKNOWN` |
| `APIActiveVentilationState` | `OFF`, `ON`, `UNKNOWN` |

Dies sind die im Modul bekannten Anzeigeoptionen, keine Zusage, dass ein Fahrzeug alle Werte liefert. Unbekannte String-Zustände bleiben als API-Wert auswertbar. API-Enums werden überwiegend in Großschreibung übernommen. Eine Anzeigeoption `UNKNOWN` bedeutet nicht, dass fehlende Felder automatisch darauf gesetzt werden.

### Fehlende und veraltete Werte

Ein fehlendes oder mit `null` geliefertes Fahrzeugfeld legt keine zugehörige normale Fahrzeugvariable an. Existiert diese bereits, bleibt der gespeicherte Wert im Regelfall erhalten. Das gilt auch für `ChargingState`, `PlugConnectionState` und `PlugLockState`; fehlende Werte werden nicht automatisch in `UNKNOWN` umgewandelt. Explizit gelieferte leere Strings können einen Stringwert leeren; ein gelieferter ungültiger Zeitpunkt kann bei `FullyChargedAt` den Wert 0 ergeben. Diagnose-/Informationswerte können dagegen leere Inhalte oder Hinweise wie „Nicht geliefert“, „Keine Einträge“ oder einen gemeldeten Dienstzustand erhalten.

Zahlen und Booleans haben keine allgemeine Unbekannt-Darstellung. Für die Einordnung sind `LastUpdate`, die optionalen `PartialErrors` und die Rohantwort maßgeblich. `LastUpdate` ist der lokale Abrufzeitpunkt, nicht der Erfassungszeitpunkt im Fahrzeug und keine Aktualitätsgarantie für jedes einzelne Feld. Die API-Felder `carCapturedTimestamp` erhalten keine eigenen Variablen und können in der Rohantwort geprüft werden.

Die Bedienoberfläche für Temperatur ist auf 16–30 °C in Schritten von 0,5 eingestellt. Der Code übernimmt die API-Temperatureinheit für Klimabefehle, rechnet den Zahlenwert jedoch nicht zwischen Celsius und Fahrenheit um. Ein durchgängiger Fahrenheit-Betrieb wird damit nicht zugesichert.

### Lademodus

| Variablenwert | API-Wert |
|---:|---|
| 0 | `MANUAL` |
| 1 | `TIMER` |
| 2 | `TIMER_CHARGING_WITH_CLIMATISATION` |
| 3 | `PREFERRED_CHARGING_TIMES` |
| 4 | `ONLY_OWN_CURRENT` |
| 5 | `IMMEDIATE_DISCHARGING` |
| 6 | `HOME_STORAGE_CHARGING` |
| 7 | `OTHER` |
| 8 | `OFF` |

Die interne Verfügbarkeitsliste enthält die gemeldeten `availableChargeModes` und den aktuellen Modus. Ist diese Liste nicht leer, werden andere Modi abgelehnt. Bei leerer Liste kann jeder im Modul bekannte Modus angefragt werden; über die tatsächliche Annahme entscheidet die API. Ein unbekannter API-Modus wird im Debug protokolliert und ersetzt den bisherigen Integer-Wert nicht.

## 6. Remote-Befehle und Visualisierung

Die Bedienvariablen sind `Charging`, `TargetSOC`, `ChargeMode`, `Climate`, `TargetTemperature` und `AuxiliaryHeating`, soweit die zugehörigen Daten geliefert und die Variablen angelegt wurden. `EnableRemote` muss eingeschaltet sein. Für die Standardaktion von `AuxiliaryHeating` sind zusätzlich eine hinterlegte S-PIN sowie angebotene `startAuxiliaryHeating`- und `stopAuxiliaryHeating`-Operationen nötig. Die übrigen Bedienvariablen erhalten ihre Standardaktion bei eingeschalteter Remote-Steuerung; dies allein bestätigt keine Fahrzeugunterstützung oder erfolgreiche Ausführung.

### Verwendung in Symcon

Die gewünschten Variablen unterhalb der Instanz in der Symcon-Visualisierung verwenden. Schalter, Auswahllisten und Schieberegler bedienen die jeweilige Standardaktion. Für normale Bedienung ist kein eigenes PHP-Skript notwendig. Das Modul stellt keine eigene HTML-Kachel bereit; die Darstellung erfolgt über die angelegten Variablen und Bildmedien. Reine Lesewerte besitzen keinen Fahrzeug-Schreibbefehl.

Beim Absenden einer Variablenaktion wird der gewünschte Wert lokal gesetzt. Während der synchronen HTTP-Anfrage wird ein ausstehender Befehlsvorgang geführt. Eine erfolgreiche 2xx-Antwort beendet diesen Zustand und hält den gewünschten Wert. Bei Übertragungsfehlern, Fehlerantworten oder Ausnahmen wird der vorherige Wert wiederhergestellt. Der nächste reguläre Fahrzeugabruf übernimmt wieder den gelieferten Zustand der API. **Eine erfolgreiche Übertragung ist keine Bestätigung der Ausführung im Fahrzeug.** Direkte PHP-Befehle ohne Variablenaktion protokollieren ihr Ergebnis, setzen aber nicht zwangsläufig einen sichtbaren Fahrzeugwert vorab.

Ist die Klimatisierung aus, wird die Solltemperatur nur lokal vorgemerkt und beim nächsten Start mitgeschickt. Solange die Klimatisierung aus bleibt, überschreibt die Abfrage diese Auswahl nicht. Ein erfolgreicher eigener Klimastart oder eine als aktiv gemeldete Klimatisierung hebt die Vormerkung auf. Bei aktiver Klimatisierung sendet eine Temperaturänderung erneut den Klimastart mit Zieltemperatur.

Das Ladelimit wird auf 50–100 % begrenzt. Die Oberfläche verwendet 10-%-Schritte; programmgesteuerte Werte werden nicht zusätzlich auf dieses Raster gerundet. Entsprechendes gilt für das Temperatur-Raster der Oberfläche.

Beispiel für die Bedienung über eine Variablenaktion:

```php
$instanceId = 12345;
$climateId = @IPS_GetObjectIDByIdent('Climate', $instanceId);
if ($climateId === false || !IPS_VariableExists($climateId)) {
    throw new RuntimeException('Das Fahrzeug liefert keine Klimatisierungsvariable.');
}
RequestAction($climateId, true);
```

Battery Care Mode, maximaler AC-Ladestrom, automatische Steckerentriegelung, Scheibenheizung und Standheizungsdauer sind im Modul lesbare Werte. Dafür stellt das Modul keine Schreibmethoden bereit. Klima-Timer, intelligentes Klimatisieren und Sitzheizungssteuerung gehören ebenfalls nicht zur implementierten Bedienung. Ladeprofile werden getrennt über die PHP-Schnittstelle verarbeitet.

## 7. Öffentliche PHP-Funktionen

`12345` steht in den Beispielen für die Instanz-ID. JSON-Rückgaben sind Strings. Ein `bool`-Ergebnis für einen Remote-Befehl bezieht sich auf die Übertragung beziehungsweise API-Annahme. `ShowDetails` muss für die Funktionen nicht aktiviert sein. `SetChargingLimit` und `SetChargeMode` benötigen jedoch ihre zuvor aus Fahrzeugdaten angelegten Bedienvariablen; fehlen diese, kann ein Aufruf eine Ausnahme auslösen.

| Aufruf | Rückgabe | Funktion |
|---|---|---|
| `MSKODA_Update(12345);` | void | regulären Fahrzeugabruf ausführen; Rate-Limit und Kontingentreserve beachten |
| `MSKODA_GetLastVehicleResponseRaw(12345);` | string | unveränderten Body der letzten ausgeführten Fahrzeugabfrage lesen |
| `MSKODA_GetVINData(12345);` | string | lokal interpretierte FIN-Daten als JSON lesen |
| `MSKODA_GetChargingProfiles(12345);` | string | kompletten gespeicherten Bereich `vehicle.chargingProfiles` als JSON lesen |
| `MSKODA_GetRemoteOperations(12345);` | string | gespeicherte Remote-Operationen als JSON lesen |
| `MSKODA_SetChargingLimit(12345, 80);` | bool | Ladelimit setzen; Wert auf 50–100 % begrenzt |
| `MSKODA_SetChargeMode(12345, 'MANUAL');` | bool | bekannten und erlaubten Lademodus setzen |
| `MSKODA_UpdateChargingProfile(12345, 1, $profileJson);` | bool | Ladeprofil anhand des OpenAPI-Schemas übertragen |
| `MSKODA_StartAuxiliaryHeating(12345);` | bool | Standheizung starten; S-PIN erforderlich; Zieltemperatur nur bei entsprechend gelieferten Fahrzeugdaten |
| `MSKODA_StopAuxiliaryHeating(12345);` | bool | Standheizung stoppen |
| `MSKODA_StartVentilation(12345);` | bool | aktive Lüftung starten |
| `MSKODA_StopVentilation(12345);` | bool | aktive Lüftung stoppen |
| `MSKODA_TestNotification(12345);` | bool | Mitteilung an die konfigurierte Visualisierung senden |
| `MSKODA_RefreshVehicleImage(12345);` | bool | Hauptbild und ableitbare Zusatzansichten aus gespeicherten URLs erneut laden; Rückgabewert bezieht sich auf das Hauptbild |

Die lesenden Cache-Funktionen verursachen keine Fahrzeuganfrage. `RefreshVehicleImage` lädt Bilder, ruft jedoch nicht selbst neue Fahrzeugdaten ab. Ein erfolgreicher Rückgabewert bedeutet nicht, dass alle Zusatzansichten existieren oder erfolgreich geladen wurden.

### Standheizungsstart

Die vollständige Modulsignatur lautet:

```php
public function StartAuxiliaryHeating(
    float $TargetTemperature = 22.0,
    int $DurationMinutes = 30,
    string $Mode = 'HEATING'
): bool
```

Für den üblichen Start genügt `MSKODA_StartAuxiliaryHeating(12345);`. Der Request enthält die konfigurierte S-PIN. Nur wenn `vehicle.auxiliaryHeating.targetTemperature` sowohl `value` als auch `unit` liefert, wird zusätzlich eine Zieltemperatur übertragen. Der Parameter `TargetTemperature` wird dabei auf 16–30 begrenzt; die Einheit stammt aus den Standheizungsdaten des Fahrzeugs.

**`DurationMinutes` und `Mode` werden vom aktuellen Modul nicht ausgewertet und nicht gesendet.** Ihre Übergabe setzt daher weder Laufzeit noch Betriebsart. `AuxiliaryHeatingDuration` zeigt lediglich eine vom Fahrzeug gelieferte Dauer an. Die separate aktive Lüftung verwendet `StartVentilation` und `StopVentilation`, nicht den wirkungslosen Modusparameter des Standheizungsstarts.

## 8. API-Anbindung und Zwischenspeicher

API-Basis: `https://public.api.connect.skoda-auto.cz`. Fahrzeugabruf: `GET /api/v1/vehicles/{vin}`. Fahrzeuganfragen tragen den Header `X-API-Key`. Verbindungsaufbau und Gesamtanfrage haben Zeitlimits von 5 beziehungsweise 25 Sekunden.

Die öffentliche OpenAPI-Definition `/v3/api-docs` wird ohne Fahrzeug-Key abgerufen; die daraus ermittelten Operationen werden für 24 Stunden zwischengespeichert. Sie werden für Operationen und Payloads von Ladelimit, Lademodus und Ladeprofilen benötigt. Bei fehlgeschlagenem Neuladen wird ein vorhandener Cache weiterverwendet. Fehlt eine erforderliche Operation, wird der entsprechende Befehl abgelehnt.

`RawData` enthält den zuletzt gültigen Arbeitsdatensatz. `LastVehicleResponseRaw` enthält getrennt davon den ursprünglichen Body der letzten tatsächlich ausgeführten Fahrzeugabfrage, auch bei einer späteren Fehlerantwort. Eine wegen Wartezeit gar nicht ausgeführte Anfrage ersetzt diesen Body nicht.

Das Modul berücksichtigt numerische Rate-Limit-Header und numerisches `Retry-After`. Für zyklische Abrufe und **Jetzt aktualisieren** werden zwei verbleibende Anfragen reserviert; Befehle können das verbleibende Kontingent nutzen, solange keine Wartezeit aktiv ist. Der sichtbare Restkontingent-Wert wird beim Fahrzeugabruf aktualisiert und ist keine sekundengenau synchronisierte Kontingentanzeige.

## 9. Fahrzeugbilder

`vehicle.renderUrl` liefert die Bildquelle. Das Modul akzeptiert HTTPS-URLs des Hosts `iprenders.blob.core.windows.net`, prüft das Bildformat und lehnt Inhalte über 15 MB nach dem Download ab. PNG, JPEG, GIF und ICO werden erkannt. Ein API-Key wird beim Bildabruf nicht mitgesendet.

Das direkt von der Public API gelieferte Bildmedium trägt den Ident `VehicleImage` und erhält bei seiner Anlage Position 40. Spätere Namens- oder Positionsänderungen bleiben erhalten. Ein verfügbares Hauptbild mit passendem FIN-Fingerprint wird bei regulären Abrufen wiederverwendet. Bei neuer FIN oder manueller Bildaktualisierung wird der Inhalt des bestehenden Mediums erneuert, soweit eine gültige Render-URL und ein erfolgreicher Download vorliegen.

Zusätzlich prüft das Modul beim erkannten `iprenders`-Namensschema weitere Fahrzeugansichten durch Ableitung ihrer URLs. Ein zusätzliches Medium wird nur angelegt, wenn die abgeleitete URL tatsächlich ein gültiges Bild liefert:

| Position | Ident | Ansicht |
|---:|---|---|
| 41 | `VehicleImageFront` | Außen vorn |
| 42 | `VehicleImageRear` | Außen hinten |
| 43 | `VehicleImageInteriorFront` | Innenraum vorn |
| 44 | `VehicleImageInteriorSide` | Innenraum Seite |
| 45 | `VehicleImageInteriorBoot` | Kofferraum |

Die Außen-Seitenansicht ist das reguläre `VehicleImage`, sofern `vehicle.renderUrl` diese Ansicht liefert. Andere Ansichten oder zusammengesetzte App-Darstellungen werden nicht als eigenständige Medien angelegt. Es besteht keine Zusage, dass alle fünf Zusatzansichten für ein Fahrzeug verfügbar sind. Bei gleicher FIN und Render-URL werden die Zusatzansichten nach der ersten Prüfung nicht bei jedem regulären Abruf erneut angefragt. Eine geänderte FIN oder Render-URL sowie die Schaltfläche **Fahrzeug Bilder aktualisieren** lösen eine erneute Prüfung aus.

Die Dateien werden im Symcon-Medienverzeichnis gespeichert und über Medienobjekte unterhalb der Instanz bereitgestellt. Das Modul aktualisiert dabei den Medieninhalt und seine Verwaltungsinformationen im Beschreibungsfeld des Mediums. `MSKODA_RefreshVehicleImage` meldet den Erfolg des Hauptbildes; Fehler einzelner Zusatzansichten erscheinen im Debug.

## 10. Archivierung und Mitteilungen

Archivierung ist standardmäßig aus. Nach ausdrücklicher Aktivierung richtet das Modul einmalig Logging für `StateOfCharge`, `TargetSOC`, `ChargePower` und `Mileage` ein. **Alle vier Variablen und eine Archive-Control-Instanz müssen vorhanden sein.** Fehlt eine der Variablen, wird die gesamte Einrichtung zurückgestellt, nicht nur diese Variable übersprungen. Bei einem Verbrenner ohne Ladedaten wird somit auch der Kilometerstand über diese Option nicht automatisch archiviert. Gewünschte Einzelwerte können unabhängig davon im Archive Control manuell zur Aufzeichnung ausgewählt werden.

`Mileage` wird bei der automatischen Einrichtung als Zähler mit Ignorieren von Nullwerten konfiguriert; nötigenfalls wird eine Neuberechnung angestoßen. Nach dieser Einrichtung bleiben spätere Archive-Control-Anpassungen unberührt. Ein Ausschalten der Erstellungsoption deaktiviert bestehendes Logging nicht automatisch.

Bei bekanntem API-Key-Ablaufdatum wird ab 30 Tagen Restlaufzeit gewarnt, auch wenn der Key bereits abgelaufen ist. Die Variable `ApiKeyWarning` ist unabhängig von `ShowDetails` vorhanden. Mitteilungen sind optional und benötigen das ausgewählte Visualisierungsziel. Eine erfolgreich gesendete Ablaufwarnung wird für dasselbe Ablaufdatum nicht erneut gesendet; fehlgeschlagene Versuche werden höchstens einmal innerhalb von 24 Stunden wiederholt. **Mitteilung testen** ist davon unabhängig.

## 11. Diagnose und Status

| Status | Bedeutung |
|---:|---|
| 102 | verbunden / bereit |
| 104 | als inaktiv definierter Status |
| 201 | FIN/VIN fehlt oder ist syntaktisch ungültig, oder API-Token ist leer |
| 202 | Verbindungs-/API-Fehler beim Fahrzeugabruf oder fehlende OpenAPI-Operation für einen skalaren Ladebefehl |
| 203 | Rate-Limit beziehungsweise Wartezeit aktiv |

Ein von der API abgelehnter, nicht leerer API-Key kann beim Abruf zu 202 führen; 201 ist die lokale Konfigurationsprüfung. Ein HTTP-Fehler eines Remote-Befehls setzt eine zuvor verbundene Instanz nicht automatisch auf Fehler. Wartezeiten und fehlende OpenAPI-Operationen können den Instanzstatus trotzdem beeinflussen. Deshalb immer auch die konkrete Befehlsrückmeldung beachten.

Für eine Fehlerprüfung sind Instanzstatus, Verbindungsrückmeldung, die optionalen `PartialErrors`, `PendingCommands`, `CommandStatus` und der Symcon-Debugbereich vorgesehen. Die Anzeige **Rohe Fahrzeugantwort anzeigen** formatiert gültiges JSON zur Lesbarkeit. Die zugehörige PHP-Funktion liefert dagegen den exakten gespeicherten Text.

Bei API-Störungen ist der letzte gültige Arbeitsdatensatz weiterhin vorhanden; er ist dadurch nicht automatisch aktuell. Fehlende Fahrzeugbereiche oder nicht bestätigte Zustände dürfen nicht als erfolgreich ausgeführter Befehl interpretiert werden. Auch die Rückmeldung „Bestätigt“ im Befehlsstatus bezeichnet die API-Annahme, nicht eine separate Ausführungsbestätigung aus dem Fahrzeug.

## 12. Datenschutz und externe Dienste

Das Modul kommuniziert für Fahrzeugdaten und Remote-Befehle mit der Public API, für das Schema mit deren OpenAPI-Endpunkt und für die Bilder mit dem genannten Renderhost. Die FIN-Interpretation benötigt keinen externen Dienst. FIN/VIN und API-Key werden für Fahrzeuganfragen verwendet; die S-PIN ist Bestandteil des Standheizungs-Startbefehls.

Die reguläre HTTP-Debugzeile maskiert die konfigurierte FIN im Anfragepfad. Das ist keine vollständige Anonymisierung aller möglichen Fehlertexte. Insbesondere ist die rohe Fahrzeugantwort bewusst **nicht anonymisiert**. Sie kann Kennzeichen, FIN, Standort, Render-URLs und weitere persönliche Daten enthalten. Vor Weitergabe müssen diese Angaben geprüft und entfernt werden. API-Key und S-PIN dürfen niemals veröffentlicht werden.

Die Option `ShowDetails` unterbindet weder das Abrufen noch das Anlegen gelieferter Standortvariablen. Sie ist kein Datenschutzschalter. Die Freigabe von Standortdaten muss in den Fahrzeug-/Kontoeinstellungen und die Sichtbarkeit in der eigenen Symcon-Visualisierung berücksichtigt werden.

## 13. Quellcode und Tests

Die Modulklasse in [module.php](module.php) kombiniert die Zuständigkeiten für Konfiguration, Variablen, HTTP, Remote-Befehle, OpenAPI, Bilder, Mitteilungen, Archivierung und FIN-Interpretation. Zusätzliche bekannte und automatisch erkannte API-Daten werden in [PublicApiVariablesTrait.php](src/PublicApiVariablesTrait.php) verarbeitet.

[Automatisierte Prüfungen und Fahrzeug-Testnachweise](../tests/README.md) ergänzen die [externe Public-API-Dokumentation](https://public.api.connect.skoda-auto.cz/docs). Die Strukturprüfung vergleicht Variablendefinitionen und öffentliche Funktionsnamen mit [public_api_contract.json](../tests/public_api_contract.json), nicht mit den Markdown-Tabellen. Automatisierte Prüfungen ersetzen keinen Test in einer realen Symcon-Instanz mit dem jeweiligen Fahrzeug.

Der Funktionsumfang der initialen Veröffentlichung steht im [Changelog](../CHANGELOG.md). Lizenz und Markenhinweis sind in der [Haupt-README](../README.md#lizenz-und-markenhinweis) und der [LICENSE](../LICENSE) beschrieben.
