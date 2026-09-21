# MySkoda

MySkoda ist ein Gerätemodul für Symcon. Eine Instanz repräsentiert ein Fahrzeug anhand seiner FIN/VIN und verwendet die offizielle MyŠkoda Public API. Das Modul legt eigene Variablen und bei verfügbarer Render-URL ein Bildmedium unterhalb der Instanz an. Kategorien, Links und Dummy-Instanzen werden nicht erzeugt.

## 1. Voraussetzungen und Einrichtung

Erforderlich sind Symcon 8.1 oder neuer, eine 17-stellige FIN/VIN, ein MyŠkoda API-Key und Internetzugang. Der verfügbare Funktionsumfang hängt von Fahrzeug und Diensten ab. Die Verwendung des Moduls allein schaltet keine Fahrzeugfunktionen frei.

Das Repository im Module Control hinzufügen, eine MySkoda-Instanz anlegen und FIN/VIN sowie API-Token eintragen. Beim Übernehmen neuer Zugangsdaten wird die Verbindung automatisch geprüft. Das Ergebnis erscheint im Konfigurationsbereich **Verbindung**. Die Standardeinstellungen erlauben Remote-Steuerung und rufen Fahrzeugdaten alle 300 Sekunden ab.

## 2. Konfiguration

| Eigenschaft | Einstellung | Standard / Verhalten |
|---|---|---|
| `VIN` | FIN/VIN | leer; 17 Zeichen, keine Buchstaben I, O oder Q |
| `APIToken` | API-Token | leer |
| `Interval` | Abfrageintervall | 300 s; Formularbereich 180–3600 s; Laufzeitminimum 180 s |
| `EnableRemote` | Remote-Steuerung | an; steuert Bedienaktionen und die Ausführung von Remote-Befehlen |
| `ClimateWithoutExternalPower` | Klima ohne externe Versorgung | an; wird beim Klimastart nur mitgesendet, wenn das Fahrzeug das entsprechende API-Feld liefert |
| `SPIN` | S-PIN | leer; für den Start der Standheizung |
| `ShowDetails` | Detail- und Diagnosevariablen anlegen | aus; legt bei Aktivierung fehlende Detailvariablen an |
| `CreateVINVariables` | FIN-Informationsvariablen anlegen | aus; legt 17 lokale String-Variablen an |
| `EnableChargingHistory` | Fahrzeugdaten archivieren | aus; richtet Archivierung einmalig ein |
| `NotifyKeyExpiry` | API-Key-Ablaufwarnung | aus; Mitteilung bei höchstens 30 Tagen Restlaufzeit |
| `NotificationInstanceID` | Visualisierung für Mitteilungen | 0; Kachel-Visualisierung oder WebFront auswählen |

Die Schaltflächen **Jetzt aktualisieren**, **Fahrzeugbild aktualisieren**, **Rohe Fahrzeugantwort anzeigen** und **Mitteilung testen** gehören zum Benutzerbetrieb. **Jetzt aktualisieren** ist an die verfügbare Verbindung gebunden und verwendet denselben Fahrzeugabruf wie der zyklische Timer. Es umgeht die API-Wartezeiten nicht.

## 3. Variablen und Objektverwaltung

Die Standardkonfiguration erzeugt 37 Variablen. `ShowDetails` ergänzt 18 Variablen; `CreateVINVariables` ergänzt 17 Variablen. Bei Aktivierung beider Optionen entstehen 72 Variablen. Das optionale Bildmedium zählt nicht als Variable.

Fehlende Variablen erhalten bei der Anlage den vorgesehenen Datentyp, Namen, die Position und Darstellung. Existierende Variablen werden nicht erneut registriert, umbenannt, umsortiert oder automatisch gelöscht. Auch ein nachträglich geleertes Icon bleibt leer. Bei einer Typ- oder Ident-Kollision wird eine Meldung protokolliert; das betroffene Objekt wird nicht automatisch ersetzt.

Das Ausschalten einer Erstellungsoption löscht vorhandene Variablen nicht. Laufende API-Werte werden weiterhin aktualisiert. FIN-Variablen werden nur bei ihrer Erstanlage und bei Änderung der konfigurierten FIN beschrieben. Remote-Bedienaktionen folgen weiterhin `EnableRemote`.

Die folgenden Positionen sind die Vorgaben für die **Erstanlage**. Reihenfolge: API-Fahrzeugdaten, abgeleitete Betriebsdaten, lokale FIN-Daten, zusätzliche API-Fahrzeuginformationen. **Standard** bedeutet immer angelegt; **Detail** erfordert `ShowDetails` für die Anlage. Die Anzeige ist deutsch, während die Idents als Programmierschnittstelle unverändert bleiben.

### 3.1 API-Fahrzeugdaten

API-Pfade beziehen sich, sofern nicht anders angegeben, auf `vehicle` der Fahrzeugantwort.

| Position | Ident | Anzeige | Typ | Anlage | Quelle / Bedeutung |
|---:|---|---|---|---|---|
| 10 | `VehicleName` | Fahrzeugname | String | Detail | `name` |
| 20 | `LicensePlate` | Kennzeichen | String | Detail | `licensePlate` |
| 30 | `VIN` | FIN / VIN | String | Standard | `vin` aus der API, nicht aus der lokalen Interpretation |
| 100 | `ClimateState` | Klimastatus | String | Standard | `airConditioning.state` |
| 110 | `AirConditioningAtUnlock` | Klimatisierung beim Entriegeln | Boolean | Standard | `airConditioning.airConditioningAtUnlock` |
| 120 | `TargetTemperature` | Solltemperatur | Float | Standard | `airConditioning.targetTemperature.value`; bedienbar |
| 130 | `TargetTemperatureUnit` | Einheit Solltemperatur | String | Standard | `airConditioning.targetTemperature.unit` |
| 140 | `WindowHeatingEnabled` | Scheibenheizung aktiviert | Boolean | Standard | `airConditioning.windowHeating.enabled` |
| 150 | `WindowHeatingFront` | Frontscheibenheizung | String | Standard | `airConditioning.windowHeating.front` |
| 160 | `WindowHeatingRear` | Heckscheibenheizung | String | Standard | `airConditioning.windowHeating.rear` |
| 200 | `AtSavedChargingLocation` | An gespeichertem Ladeort | Boolean | Standard | `charging.isVehicleInSavedLocation` |
| 210 | `AutoUnlockPlug` | Automatische Steckerentriegelung | String | Standard | `charging.settings.autoUnlockPlugWhenCharged` |
| 230 | `BatteryCareTargetSOC` | Battery-Care-Ziel | Integer | Standard | `charging.settings.batteryCareModeTargetValueInPercent`; % |
| 240 | `BatteryCareMode` | Battery Care Mode | String | Standard | `charging.settings.chargingCareMode` |
| 250 | `MaxChargeCurrentAC` | Maximaler AC-Ladestrom | String | Standard | `charging.settings.maxChargeCurrentAc`; Status, kein Amperewert |
| 260 | `ChargeMode` | Lademodus | Integer | Standard | Index für `charging.settings.preferredChargeMode`; bedienbar |
| 270 | `TargetSOC` | Ladelimit | Integer | Standard | `charging.settings.targetStateOfChargeInPercent`; %; bedienbar |
| 280 | `Range` | Reichweite | Integer | Standard | `charging.status.battery.remainingCruisingRangeInMeters`; gerundet in km |
| 290 | `StateOfCharge` | Ladezustand | Integer | Standard | `charging.status.battery.stateOfChargeInPercent`; % |
| 300 | `ChargePower` | Ladeleistung | Float | Standard | `charging.status.chargePowerInKw`; mit 1000 multipliziert, gespeichert in W |
| 310 | `FullyChargedAt` | Vollgeladen um | Integer | Detail | `charging.status.fullyChargedAt`; Unix-Zeitstempel |
| 320 | `RemainingChargingTime` | Restladezeit | Integer | Standard | `charging.status.remainingTimeToFullyChargedInMinutes`; min |
| 330 | `ChargingState` | Ladestatus | String | Detail | `charging.status.state` |
| 340 | `ChargeType` | Ladeart | String | Detail | `charging.status.chargeType` |
| 400 | `Mileage` | Kilometerstand | Integer | Standard | `odometer.mileageInKm`; gerundet, nur Werte größer 0 übernommen |
| 600 | `ParkingState` | Parkstatus | String | Detail | `parkingPosition.state` |
| 610 | `ParkingAddress` | Parkadresse | String | Detail | `parkingPosition.formattedAddress` |
| 620 | `Latitude` | Breitengrad | Float | Detail | `parkingPosition.gpsCoordinates.latitude`; auch flache Koordinaten und `lat` werden gelesen |
| 630 | `Longitude` | Längengrad | Float | Detail | `parkingPosition.gpsCoordinates.longitude`; auch flache Koordinaten, `lon` und `lng` werden gelesen |
| 700 | `DoorsLocked` | Türverriegelungsstatus | String | Standard | `status.overall.doorsLocked` |
| 710 | `Locked` | Fahrzeugverriegelungsstatus | String | Standard | `status.overall.locked` |
| 720 | `DoorsOpen` | Türen | String | Standard | `status.overall.doors` |
| 730 | `WindowsOpen` | Fenster | String | Standard | `status.overall.windows` |
| 740 | `LightsOn` | Licht | String | Detail | `status.overall.lights` |
| 750 | `ReliableLockStatus` | Zuverlässiger Verriegelungsstatus | String | Standard | `status.overall.reliableLockStatus` |
| 800 | `SunroofOpen` | Schiebedach | String | Detail | `status.detail.sunroof` |
| 810 | `TrunkOpen` | Kofferraum | String | Detail | `status.detail.trunk` |
| 820 | `BonnetOpen` | Motorhaube | String | Detail | `status.detail.bonnet` |

Die drei Verriegelungswerte werden getrennt aus den jeweiligen Feldern gelesen. Es findet keine Priorisierung oder gegenseitige Ersetzung statt. Der Ident `DoorsOpen` bezeichnet einen String, nicht einen Boolean.

### 3.2 Abgeleitete Betriebs- und Diagnosewerte

| Position | Ident | Anzeige | Typ | Anlage | Bedeutung |
|---:|---|---|---|---|---|
| 900 | `Climate` | Klimatisierung | Boolean | Standard | aus Klimastatus abgeleitet; Start/Stopp bedienbar |
| 910 | `Charging` | Laden | Boolean | Standard | wahr bei `CHARGING` oder `CONSERVING`; Start/Stopp bedienbar |
| 920 | `LastUpdate` | Letzte Aktualisierung | Integer | Standard | lokaler Unix-Zeitstempel des letzten erfolgreichen Fahrzeugabrufs |
| 930 | `ApiKeyWarning` | API-Key Warnung | Boolean | Standard | bekanntes Ablaufdatum erreicht oder höchstens 30 Tage entfernt |
| 940 | `ApiKeyExpiresAtVar` | API-Key gültig bis | Integer | Detail | aus HTTP-Header `x-api-key-expires-at`; Unix-Zeitstempel |
| 950 | `RequestsRemaining` | Verbleibende API-Anfragen | Integer | Detail | gespeichertes Restkontingent beim Fahrzeugabruf |
| 960 | `PartialErrors` | API-Teilfehler | String | Detail | `errors` der Antwort als JSON; leer, wenn keine Teilfehler vorliegen |
| 980 | `PendingCommands` | Ausstehende Befehle | Integer | Detail | Anzahl aktuell laufender Befehlsanfragen |
| 990 | `CommandStatus` | Befehlsstatus | String | Detail | Übertragungsstatus oder Ergebnis des letzten Befehls |

`Climate` ist wahr bei `ON`, `COOLING`, `HEATING`, `HEATING_AUXILIARY` oder `VENTILATION`. Für genaue Zustandsauswertungen ist `ClimateState` zu verwenden. Entsprechend ist `ChargingState` aussagekräftiger als der Bedien-Boolean `Charging`.

### 3.3 Lokale FIN-Informationen

Die 17 optionalen String-Variablen liegen auf den Positionen 1100 bis 1260. Ihre vollständige Tabelle und der lokale Decoder sind in [README_FIN_VIN_new.md](README_FIN_VIN_new.md) beschrieben. Sie verwenden keine Icons oder besonderen Darstellungen.

### 3.4 Zusätzliche API-Fahrzeuginformationen

Diese Standardvariablen sind reine Strings ohne Icons und besondere Darstellungen. Sie werden aus den zuletzt empfangenen API-Daten aktualisiert; sie sind nicht Teil der lokalen FIN-Interpretation.

| Position | Ident | Anzeige | Typ | Anlage | Quelle / Bedeutung |
|---:|---|---|---|---|---|
| 1300 | `APICarType` | API Fahrzeugtyp | String | Standard | `fuelStatus.carType` |
| 1310 | `APIPrimaryEngineType` | API Primärer Antriebstyp | String | Standard | `fuelStatus.primaryEngineRange.engineType` |
| 1320 | `APISecondaryEngineType` | API Sekundärer Antriebstyp | String | Standard | `fuelStatus.secondaryEngineRange.engineType` |
| 1330 | `APISupportedFeatures` | API Unterstützte Funktionen | String | Standard | aus vorhandenen API-Bereichen und bestimmten Fehlerkategorien abgeleitet |
| 1340 | `APIAvailableChargeModes` | API Verfügbare Lademodi | String | Standard | `charging.settings.availableChargeModes` als kompakte Liste |
| 1350 | `APIRemoteOperations` | API Remote-Operationen | String | Standard | `operations`, alternativ `remoteOperations`; Liste oder JSON |
| 1360 | `APIAuxiliaryHeatingState` | API Standheizungsstatus | String | Standard | `auxiliaryHeating.state` |
| 1370 | `APIActiveVentilationState` | API Lüftungsstatus | String | Standard | `activeVentilation.state` |

`APISupportedFeatures` ist ein abgeleiteter Hinweis auf API-Bereiche und kein vollständiger Ausstattungsnachweis. Scalar-Listen werden mit Kommas verbunden, strukturierte Werte als JSON ausgegeben.

## 4. Zustände und Einheiten

| Datenpunktgruppe | In den Darstellungen hinterlegte Werte |
|---|---|
| Türen, Fenster, Schiebedach, Kofferraum, Motorhaube | `CLOSED`, `OPEN`, `UNSUPPORTED`, `UNKNOWN` |
| `DoorsLocked`, `Locked` | `YES`, `NO`, `OPENED`, `TRUNK_OPENED`, `UNKNOWN` |
| `ReliableLockStatus` | `LOCKED`, `UNLOCKED`, `UNKNOWN` |
| Licht und Scheibenheizung | `OFF`, `ON`, `INVALID`, `UNKNOWN` |
| `ClimateState` | `OFF`, `ON`, `COOLING`, `HEATING`, `HEATING_AUXILIARY`, `VENTILATION`, `INVALID`, `UNKNOWN` |
| `ChargingState` | `READY_FOR_CHARGING`, `CHARGING`, `CONSERVING`, `CONNECT_CABLE`, `CHARGING_INTERRUPTED`, `ERROR`, `UNKNOWN` |
| `ParkingState` | `PARKED`, `IN_MOTION`, `MOVING`, `DRIVING`, `UNKNOWN` |
| `ChargeType` | `AC`, `DC`, `OFF` |
| `BatteryCareMode` | `ACTIVATED`, `DEACTIVATED`, `ACTIVE`, `INACTIVE`, `UNKNOWN` |
| `MaxChargeCurrentAC` | `MAXIMUM`, `REDUCED`, `UNKNOWN` |
| `AutoUnlockPlug` | `OFF`, `ON`, `PERMANENT`, `UNKNOWN` |

Dies sind die im Modul bekannten Anzeigeoptionen, keine Zusage, dass ein Fahrzeug alle Werte liefert. Unbekannte String-Zustände bleiben als API-Wert auswertbar. API-Enums werden überwiegend in Großschreibung übernommen.

Fehlende Daten sind nicht mit einem bestätigten Fahrzeugzustand gleichzusetzen: Einige Statusfelder erhalten `UNKNOWN`, manche numerischen Werte behalten den letzten Wert, andere Detailwerte werden leer oder 0 gesetzt. Zahlen und Booleans haben keine allgemeine Unbekannt-Darstellung. Für die Einordnung sind `LastUpdate`, `PartialErrors` und die Rohantwort maßgeblich. `LastUpdate` ist nicht der Erfassungszeitpunkt im Fahrzeug. Die API-Felder `carCapturedTimestamp` erhalten keine eigenen Variablen.

Die Bedienoberfläche für Temperatur ist auf 16–30 °C in Schritten von 0,5 eingestellt. Der Code übernimmt die API-Temperatureinheit für Befehle, rechnet den Zahlenwert jedoch nicht zwischen Celsius und Fahrenheit um. Ein durchgängiger Fahrenheit-Betrieb wird damit nicht zugesichert.

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

## 5. Remote-Befehle

Die Bedienvariablen sind `Charging`, `TargetSOC`, `ChargeMode`, `Climate` und `TargetTemperature`. `EnableRemote` muss eingeschaltet sein. Andere Fahrzeugwerte sind nicht bedienbar.

Beim Absenden wird der gewünschte Wert lokal gesetzt. Während der synchronen HTTP-Anfrage wird Pending geführt. Eine erfolgreiche 2xx-Antwort beendet Pending und hält den gewünschten Wert. Bei Übertragungsfehlern, Fehlerantworten oder Ausnahmen wird der vorherige Wert wiederhergestellt. Der nächste reguläre Fahrzeugabruf übernimmt wieder den Zustand der API. **Eine erfolgreiche Übertragung ist keine Bestätigung der Ausführung im Fahrzeug.**

Ist die Klimatisierung aus, wird die Solltemperatur nur lokal vorgemerkt und beim nächsten Start mitgeschickt. Solange die Klimatisierung aus bleibt, überschreibt die Abfrage diese Auswahl nicht. Ein erfolgreicher eigener Klimastart oder eine als aktiv gemeldete Klimatisierung hebt die Vormerkung auf. Bei aktiver Klimatisierung sendet eine Temperaturänderung erneut den Klimastart mit Zieltemperatur.

Das Ladelimit wird auf 50–100 % begrenzt. Die Oberfläche verwendet 10-%-Schritte; programmgesteuerte Werte werden nicht zusätzlich auf dieses Raster gerundet. Entsprechendes gilt für das Temperatur-Raster der Oberfläche.

Beispiel für die Bedienung über eine Variablenaktion:

```php
$instanceId = 12345;
$climateId = IPS_GetObjectIDByIdent('Climate', $instanceId);
RequestAction($climateId, true);
```

Battery Care Mode, reduzierter AC-Ladestrom, automatische Steckerentriegelung und Scheibenheizung sind im Modul lesbare Zustände. Dafür stellt das Modul keine Schreibmethoden bereit. Klima-Timer und Sitzheizungssteuerung gehören ebenfalls nicht zur implementierten Bedienung.

## 6. Öffentliche PHP-Funktionen

`12345` steht in den Beispielen für die Instanz-ID. JSON-Rückgaben sind Strings. Ein `bool`-Ergebnis für einen Remote-Befehl bezieht sich auf die Übertragung beziehungsweise API-Annahme.

| Aufruf | Rückgabe | Funktion |
|---|---|---|
| `MSKODA_Update(12345);` | void | regulären Fahrzeugabruf ausführen; Rate-Limit beachten |
| `MSKODA_GetLastVehicleResponseRaw(12345);` | string | unveränderten Body der letzten ausgeführten Fahrzeugabfrage lesen |
| `MSKODA_GetVINData(12345);` | string | lokal interpretierte FIN-Daten als JSON lesen |
| `MSKODA_GetChargingProfiles(12345);` | string | kompletten gespeicherten Bereich `vehicle.chargingProfiles` als JSON lesen |
| `MSKODA_GetRemoteOperations(12345);` | string | gespeicherte Remote-Operationen als JSON lesen |
| `MSKODA_SetChargingLimit(12345, 80);` | bool | Ladelimit setzen |
| `MSKODA_SetChargeMode(12345, 'MANUAL');` | bool | bekannten und erlaubten Lademodus setzen |
| `MSKODA_UpdateChargingProfile(12345, 1, $profileJson);` | bool | Ladeprofil anhand des OpenAPI-Schemas übertragen |
| `MSKODA_StartAuxiliaryHeating(12345, 22.0, 30, 'HEATING');` | bool | Standheizung starten; Temperatur, Dauer in Minuten, Modus; S-PIN erforderlich |
| `MSKODA_StopAuxiliaryHeating(12345);` | bool | Standheizung stoppen |
| `MSKODA_StartVentilation(12345);` | bool | aktive Lüftung starten |
| `MSKODA_StopVentilation(12345);` | bool | aktive Lüftung stoppen |
| `MSKODA_TestNotification(12345);` | bool | Mitteilung an die konfigurierte Visualisierung senden |
| `MSKODA_RefreshVehicleImage(12345);` | bool | Bild aus der gespeicherten Render-URL erneut laden |

Die lesenden Cache-Funktionen verursachen keine Fahrzeuganfrage. `RefreshVehicleImage` lädt das Bild, ruft jedoch nicht selbst neue Fahrzeugdaten ab. `StartAuxiliaryHeating` verwendet standardmäßig 22 °C, 30 Minuten und `HEATING`; als weiterer Modus wird `VENTILATION` gesendet. Die Dauer wird in Sekunden übertragen, mindestens 60 Sekunden.

## 7. API-Anbindung und Zwischenspeicher

API-Basis: `https://public.api.connect.skoda-auto.cz`. Fahrzeugabruf: `GET /api/v1/vehicles/{vin}`. Fahrzeuganfragen tragen den Header `X-API-Key`. Verbindungsaufbau und Gesamtanfrage haben Zeitlimits von 5 beziehungsweise 25 Sekunden.

Die öffentliche OpenAPI-Definition `/v3/api-docs` wird ohne Fahrzeug-Key abgerufen und bis zu 24 Stunden zwischengespeichert. Sie wird für Operationen und Payloads von Ladelimit, Lademodus und Ladeprofilen benötigt. Bei fehlgeschlagenem Neuladen wird ein vorhandener Cache weiterverwendet. Fehlt eine erforderliche Operation, wird der entsprechende Befehl abgelehnt.

`RawData` enthält den zuletzt gültigen Arbeitsdatensatz. `LastVehicleResponseRaw` enthält getrennt davon den ursprünglichen Body der letzten tatsächlich ausgeführten Fahrzeugabfrage, auch bei einer späteren Fehlerantwort. Eine wegen Wartezeit gar nicht ausgeführte Anfrage ersetzt diesen Body nicht.

Das Modul berücksichtigt numerische Rate-Limit-Header und numerisches `Retry-After`. Für zyklische Abrufe werden zwei verbleibende Anfragen reserviert; Befehle können das verbleibende Kontingent nutzen, solange keine Wartezeit aktiv ist. Der sichtbare Restkontingent-Wert wird beim Fahrzeugabruf aktualisiert und ist keine sekundengenau synchronisierte Kontingentanzeige.

## 8. Fahrzeugbild

`vehicle.renderUrl` liefert die Bildquelle. Das Modul akzeptiert HTTPS-URLs des Hosts `iprenders.blob.core.windows.net`, prüft das Bildformat und lehnt Inhalte über 15 MB nach dem Download ab. PNG, JPEG, GIF und ICO werden erkannt. Ein API-Key wird beim Bildabruf nicht mitgesendet.

Das Bildmedium trägt den Ident `VehicleImage` und erhält bei seiner Anlage Position 40. Spätere Namens- oder Positionsänderungen bleiben erhalten. Ein verfügbares Bild mit passendem FIN-Fingerprint wird bei regulären Abrufen wiederverwendet. Bei neuer FIN oder manueller Bildaktualisierung wird der Inhalt des bestehenden Mediums erneuert, soweit eine gültige Render-URL und ein erfolgreicher Download vorliegen.

## 9. Archivierung und Mitteilungen

Archivierung ist standardmäßig aus. Nach ausdrücklicher Aktivierung richtet das Modul einmalig Logging für `StateOfCharge`, `TargetSOC`, `ChargePower` und `Mileage` ein. `Mileage` wird als Zähler mit Ignorieren von Nullwerten konfiguriert; nötigenfalls wird eine Neuberechnung angestoßen. Nach dieser Einrichtung bleiben spätere Archive-Control-Anpassungen unberührt. Ein Ausschalten der Erstellungsoption deaktiviert bestehendes Logging nicht automatisch.

Bei bekanntem API-Key-Ablaufdatum wird ab 30 Tagen Restlaufzeit gewarnt, auch wenn der Key bereits abgelaufen ist. Mitteilungen sind optional. Eine erfolgreich gesendete Ablaufwarnung wird für dasselbe Ablaufdatum nicht erneut gesendet; fehlgeschlagene Versuche werden höchstens einmal pro Tag wiederholt. **Mitteilung testen** ist davon unabhängig.

## 10. Diagnose und Status

| Status | Bedeutung |
|---:|---|
| 102 | verbunden / bereit |
| 104 | als inaktiv definierter Status |
| 201 | FIN/VIN oder API-Token fehlt beziehungsweise ist ungültig |
| 202 | Verbindungs- oder API-Fehler |
| 203 | Rate-Limit beziehungsweise Wartezeit aktiv |

Für eine Fehlerprüfung sind Instanzstatus, Verbindungsrückmeldung, `PartialErrors`, `PendingCommands`, `CommandStatus` und der Symcon-Debugbereich vorgesehen. Die Anzeige **Rohe Fahrzeugantwort anzeigen** formatiert gültiges JSON zur Lesbarkeit. Die zugehörige PHP-Funktion liefert dagegen den exakten gespeicherten Text.

Bei API-Störungen ist der letzte gültige Arbeitsdatensatz weiterhin vorhanden; er ist dadurch nicht automatisch aktuell. Fehlende Fahrzeugbereiche oder nicht bestätigte Zustände dürfen nicht als erfolgreich ausgeführter Befehl interpretiert werden.

## 11. Datenschutz und externe Dienste

Das Modul kommuniziert für Fahrzeugdaten und Remote-Befehle mit der Public API, für das Schema mit deren OpenAPI-Endpunkt und für das Bild mit dem oben genannten Renderhost. Die FIN-Interpretation benötigt keinen externen Dienst. FIN/VIN und API-Key werden für Fahrzeuganfragen verwendet; die S-PIN ist Bestandteil des Standheizungs-Startbefehls.

Die reguläre HTTP-Debugzeile maskiert die konfigurierte FIN im Anfragepfad. Das ist keine vollständige Anonymisierung aller möglichen Fehlertexte. Insbesondere ist die rohe Fahrzeugantwort bewusst **nicht anonymisiert**. Sie kann Kennzeichen, FIN, Standort, Render-URLs und weitere persönliche Daten enthalten. Vor Weitergabe müssen diese Angaben geprüft und entfernt werden. API-Key und S-PIN dürfen niemals veröffentlicht werden.

## 12. Quellcode und Tests

Die Modulklasse in [module.php](module.php) kombiniert die Zuständigkeiten für Konfiguration, Variablen, HTTP, Remote-Befehle, OpenAPI, Bilder, Mitteilungen, Archivierung und FIN-Interpretation. Normale zusätzliche API-Daten werden in [PublicApiVariablesTrait.php](src/PublicApiVariablesTrait.php) verarbeitet.

[Automatisierte Prüfungen und Fahrzeug-Testnachweise](../tests/README_new.md) ergänzen die [externe Public-API-Dokumentation](https://public.api.connect.skoda-auto.cz/docs). Automatisierte Prüfungen ersetzen keinen Test in einer realen Symcon-Instanz mit dem jeweiligen Fahrzeug.
