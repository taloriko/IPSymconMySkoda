# Fahrzeug-Kompatibilität und Tests

Diese Tabelle dokumentiert, welche MySkoda-Datenpunkte und Funktionen mit realen Fahrzeugen geprüft wurden. Ein Nachweis bestätigt nicht automatisch jeden denkbaren Zustand eines Datenpunkts und nicht die Ausführung eines nur als verfügbar gemeldeten Befehls.

**Legende:** ✅ Getestet · 🟡 Vermutet · ⚪ Zu prüfen · ❌ Nicht vorhanden

Für den Enyaq 80 von 2023 ist ausdrücklich der Schiebedachzustand `OPEN` bestätigt. Die übrigen Felder dieses Fahrzeugs bleiben zu prüfen. Automatisierte Codeprüfungen ändern diese Fahrzeugnachweise nicht. Zusätzliche Datenpunktzeilen ohne praktischen Nachweis sind mit ⚪ gekennzeichnet.

Die Spalten **Datentyp**, **Mögliche Werte** und **Beispielwert** beziehen sich auf das API-Feld. Abweichende Modul-Datentypen, Einheiten und Verhaltensweisen stehen in der Bemerkung oder in der [technischen Datenpunktreferenz](../MySkoda/README.md). Beispielwerte sind keine zusätzliche Bestätigung eines Fahrzeugtests.

## vehicle

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `name` | Fahrzeugname | ✅ | ⚪ | String | Freitext | `Harry` | Vom Benutzer in der App unter Status → Details zum Fahrzeug → Name des Fahrzeugs festgelegt |
| `licensePlate` | Kennzeichen | ✅ | ⚪ | String | Freitext | `S-AB 123E` | In den Fahrzeugdaten hinterlegtes Kennzeichen |
| `vin` | FIN / VIN | ✅ | ⚪ | String | 17-stellige VIN | `TMB…7448` | Fahrgestellnummer; Beispiel gekürzt |
| `renderUrl` | Fahrzeugbild | ✅ | ⚪ | String | URL | `https://…/vehicle.png` | Von Škoda geliefertes Bild passend zur Fahrzeugkonfiguration; kein Nachweis aller Zusatzansichten |

## vehicle.activeVentilation

Für diese Zeile ist noch kein praktischer Nachweis in der Fahrzeugmatrix hinterlegt.

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `state` | API Lüftungsstatus | ⚪ | ⚪ | String | Modul kennt `OFF` / `ON` / `UNKNOWN`; weitere Strings bleiben auswertbar | `OFF` | `APIActiveVentilationState`; eigene PHP-Start-/Stopp-Funktionen, keine separate Lüftungs-Bedienvariable |

## vehicle.airConditioning

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `state` | Klimatisierung | ✅ | ⚪ | String | `OFF` / `ON` / `COOLING` / `HEATING` / `HEATING_AUXILIARY` / `VENTILATION` / `INVALID` | `OFF` | Genauer Zustand in `ClimateState`; daraus abgeleiteter Bedienwert `Climate` |
| `airConditioningAtUnlock` | Klimatisierung beim Entriegeln | ✅ | ⚪ | Boolean | `true` / `false` | `true` | Beginnt mit der Klimatisierung beim Entriegeln, auch bei Annäherung, wenn aktiviert; im Modul nur lesbar |
| `airConditioningWithoutExternalPower` | Klimatisierung ohne externe Stromversorgung | ⚪ | ⚪ | Boolean | `true` / `false` | `true` | Codeprüfung mit Prüfdaten; wird als zusätzliches API-Feld erfasst. Die Konfigurationsoption erscheint nur bei geliefertem Wert und wird dann beim Klimastart gesendet |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601-Zeitstempel | `2026-09-18T08:49:39Z` | Keine eigene Variable unter der Instanz |
| `estimatedReachOfTargetTemperatureAt` | Voraussichtliche Zieltemperatur erreicht um | ⚪ | ⚪ | String | Zeitstempel | `2026-10-03T17:37:47Z` | Codeprüfung mit Prüfdaten; `APIAirConditioningEstimatedReachOfTargetTemperatureAt` ist ein formatierter String in der PHP-Systemzeitzone, kein Integer-Zeitstempel |
| `targetTemperature.value` | Solltemperatur | ✅ | ⚪ | Number | Bedienbereich 16–30 °C | `22` | Modul: 0,5-°C-Schritte; bei ausgeschalteter Klimatisierung lokal vorgemerkt, bei aktiver Klimatisierung unmittelbar gesendet |
| `targetTemperature.unit` | Einheit Solltemperatur | ✅ | ⚪ | String | `CELSIUS` nachgewiesen | `CELSIUS` | Fahrenheit-Betrieb nicht praktisch bestätigt; das Modul rechnet Temperaturwerte nicht zwischen den Einheiten um |
| `windowHeating.enabled` | Scheibenheizung aktiviert | ✅ | ⚪ | Boolean | `true` / `false` | `true` | Gelieferter Status; Zusammenhang mit intelligentem Klimatisieren; im Modul nur lesbar |
| `windowHeating.front` | Frontscheibenheizung | 🟡 | ⚪ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | `OFF` | Beim geprüften Fahrzeug trotz fehlender Ausstattung als `OFF` gemeldet; erwartet wurde `UNKNOWN` |
| `windowHeating.rear` | Heckscheibenheizung | 🟡 | ⚪ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | `OFF` | Funktion noch nicht praktisch geprüft |

## vehicle.auxiliaryHeating

Die Verarbeitung von Standheizungsdaten ist mit automatisierten Verbrenner-Prüfdaten abgedeckt. Daraus folgt kein praktischer Nachweis für die beiden Enyaq-Spalten.

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `state` | API Standheizungsstatus | ⚪ | ⚪ | String | Modul kennt `OFF` / `ON` / `HEATING` / `VENTILATION` / `UNKNOWN`; weitere Strings bleiben auswertbar | `OFF` | `APIAuxiliaryHeatingState`; zusätzlich abgeleiteter Boolean `AuxiliaryHeating` |
| `durationInSeconds` | Standheizungsdauer | ⚪ | ⚪ | Integer | Dauer in Sekunden | `2400` | `AuxiliaryHeatingDuration` speichert gerundete Minuten, hier 40; nur lesbar |
| `targetTemperature.value` | Standheizung: Zieltemperatur | ⚪ | ⚪ | Number | gelieferter Zahlenwert | `22` | Bei vorhandenen Temperaturdaten wird der Zielwert beim Start berücksichtigt; kein praktischer Nachweis |
| `targetTemperature.unit` | Standheizung: Temperatureinheit | ⚪ | ⚪ | String | gelieferte Einheit | `CELSIUS` | Beim Start nur zusammen mit geliefertem `value` ausgewertet |

Die Standardaktion der Standheizung benötigt `EnableRemote`, eine S-PIN und angebotene Start-/Stopp-Operationen. Die PHP-Parameter `DurationMinutes` und `Mode` werden nicht gesendet und verändern den Standheizungsstart nicht. Die gemeldete Dauer ist kein einstellbarer Laufzeitwert.

## vehicle.charging

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `isVehicleInSavedLocation` | An gespeichertem Ladeort | ✅ | ⚪ | Boolean | `true` / `false` | `false` | Ist das Fahrzeug an einem Ladeort, der vorher im Fahrzeug gespeichert und definiert wurde? |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601-Zeitstempel | `2026-09-18T08:53:06Z` | Keine eigene Variable unter der Instanz |
| `settings.autoUnlockPlugWhenCharged` | Automatische Steckerentriegelung | ✅ | ⚪ | String | `OFF` / `ON` / `PERMANENT` | `OFF` | Status der fahrzeugseitigen Entriegelung nach Ladeende; bei Entriegelung kann das Kabel fahrzeugseitig abgezogen werden; nur lesbar |
| `settings.availableChargeModes` | Verfügbare Lademodi | ✅ | ⚪ | Array[String] | `MANUAL` / `TIMER` / `TIMER_CHARGING_WITH_CLIMATISATION` / `PREFERRED_CHARGING_TIMES` / `ONLY_OWN_CURRENT` / `IMMEDIATE_DISCHARGING` / `HOME_STORAGE_CHARGING` / `OTHER` / `OFF` | `["MANUAL"]` | Gemeldete Auswahl; `APIChargingSettingsAvailableChargeModes` unabhängig von Diagnoseoption, `APIAvailableChargeModes` als zusätzliche Diagnoseansicht |
| `settings.batteryCareModeTargetValueInPercent` | Battery-Care-Ziel | ✅ | ⚪ | Integer | 0–100 % | `80` | Nur lesbar |
| `settings.chargingCareMode` | Battery Care Mode | ✅ | ⚪ | String | `ACTIVATED` / `DEACTIVATED` | `ACTIVATED` | Nur lesbar |
| `settings.maxChargeCurrentAc` | Maximaler AC-Ladestrom | ✅ | ⚪ | String | `MAXIMUM` / `REDUCED` | `MAXIMUM` | Status, kein Amperewert; nur lesbar |
| `settings.maxChargeCurrentAcAmpere` | Maximaler AC-Ladestrom in Ampere | ⚪ | ⚪ | Integer | gelieferter Amperewert | `10` | Codeprüfung mit Prüfdaten; `APIChargingSettingsMaxChargeCurrentAcAmpere` mit Einheit A; nur lesbar |
| `settings.preferredChargeMode` | Lademodus | ✅ | ⚪ | String | Bekannte Lademodi wie oben | `MANUAL` | Aktuell gewählter Modus; Modulvariable `ChargeMode` verwendet einen Integer-Index |
| `settings.targetStateOfChargeInPercent` | Ladelimit | ✅ | ⚪ | Integer | Bedienbereich 50–100 % | `80` | Oberfläche mit 10-%-Schritten; PHP-Werte werden nicht auf dieses Raster gerundet |
| `status.battery.remainingCruisingRangeInMeters` | Reichweite | ✅ | ⚪ | Integer | ≥ 0 m | `385000` | Im Modul gerundet in km ausgegeben |
| `status.battery.stateOfChargeInPercent` | Ladezustand | ✅ | ⚪ | Integer | 0–100 % | `78` | |
| `status.chargePowerInKw` | Ladeleistung | ✅ | ⚪ | Number | ≥ 0 kW | `0` | Im Modul in W ausgegeben |
| `status.chargeType` | Ladeart | ⚪ | ⚪ | String | Modul kennt `AC` / `DC` / `OFF` | `AC` | `ChargeType`; kein praktischer Nachweis in dieser Matrix |
| `status.chargingRateInKilometersPerHour` | Ladegeschwindigkeit | ⚪ | ⚪ | Number | gelieferter Wert in km/h | `20.1696` | Codeprüfung mit Prüfdaten; `APIChargingStatusChargingRateInKilometersPerHour` als Float, Anzeige mit einer Nachkommastelle |
| `status.fullyChargedAt` | Vollgeladen um | ✅ | ⚪ | String | ISO-8601-Zeitstempel | `2026-09-18T08:49:38Z` | Im Modul als Integer-Unix-Zeitstempel |
| `status.plugConnectionState` | Ladestecker Anschlussstatus | ✅ | ⚪ | String | `CONNECTED` / `DISCONNECTED`; Modul kennt auch `UNKNOWN` | `DISCONNECTED` | Am Enyaq 80 2022 am 28.09.2026 mit `DISCONNECTED` geprüft; `CONNECTED` noch zu prüfen. Fehlende oder `null` enthaltende Felder überschreiben einen vorhandenen Wert nicht |
| `status.plugLockState` | Ladestecker Verriegelungsstatus | ✅ | ⚪ | String | `LOCKED` / `UNLOCKED`; Modul kennt auch `UNKNOWN` | `UNLOCKED` | Am Enyaq 80 2022 am 28.09.2026 mit `UNLOCKED` geprüft; `LOCKED` noch zu prüfen. Fehlende oder `null` enthaltende Felder überschreiben einen vorhandenen Wert nicht |
| `status.remainingTimeToFullyChargedInMinutes` | Restladezeit | ✅ | ⚪ | Integer | ≥ 0 min | `0` | |
| `status.state` | Ladestatus | ✅ | ⚪ | String | `READY_FOR_CHARGING` / `CONNECT_CABLE` / `CONSERVING` / `CHARGING` / `CHARGING_INTERRUPTED` / `ERROR` | `CONNECT_CABLE` | Unbekannte gelieferte Strings bleiben im Modul auswertbar |

Fehlt `status.state`, ist dies keine Bestätigung für „Kabel abgezogen“ oder ein Ladeende. Der Bedienwert `Charging` bleibt unverändert; ebenso bleibt bei fehlendem oder `null` enthaltendem Feld ein vorhandener `ChargingState` erhalten. Es erfolgt keine automatische Umsetzung in `UNKNOWN`. Noch nie gelieferte Felder erzeugen keine Platzhaltervariable. Anschluss- und Verriegelungszustand des Steckers sind nicht mit einem laufenden Ladevorgang gleichzusetzen.

## vehicle.chargingProfiles

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `profiles` | Ladeprofile | 🟡 | ⚪ | Array[Object] | leer oder Ladeprofil-Objekte | `[]` | Im vorhandenen Test leer; der komplette Bereich `vehicle.chargingProfiles` ist über `MSKODA_GetChargingProfiles` abrufbar |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601-Zeitstempel | `2026-09-18T08:49:35.868Z` | Keine eigene Variable unter der Instanz |

## vehicle.fuelStatus

Diese Felder werden im Code verarbeitet und teilweise mit automatisierten Verbrenner-Prüfdaten geprüft. Die folgenden Beispielwerte stammen aus diesen Prüfdaten; sie erweitern nicht die praktischen Enyaq-Nachweise.

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `carType` | API Fahrzeugtyp | ⚪ | ⚪ | String | Gelieferter Typ; bekannte Modul-Anzeigen siehe Datenpunktreferenz | `GASOLINE` | `APICarType`; mit Darstellung für Fahrzeug-/Antriebstypen |
| `primaryEngineRange.currentFuelLevelInPercent` | Tankfüllstand | ⚪ | ⚪ | Integer | 0–100 % | `64` | `FuelLevelPercent` |
| `primaryEngineRange.currentSoCInPercent` | Primärer Antrieb SoC | ⚪ | ⚪ | Integer | 0–100 % | `64` | `PrimaryEngineSOC`; nicht pauschal als Elektro-Batteriestand auslegen |
| `primaryEngineRange.engineType` | API Primärer Antriebstyp | ⚪ | ⚪ | String | Gelieferter Antriebstyp | `GASOLINE` | `APIPrimaryEngineType` |
| `primaryEngineRange.remainingRangeInKm` | Kraftstoff-Reichweite | ⚪ | ⚪ | Integer | Reichweite in km | `430` | `FuelRange`; Reichweite des primären Antriebs |
| `secondaryEngineRange.engineType` | API Sekundärer Antriebstyp | ⚪ | ⚪ | String | Gelieferter Antriebstyp | — | `APISecondaryEngineType`; wird nur bei geliefertem Feld angelegt |
| `totalRangeInKm` | Gesamtreichweite | ⚪ | ⚪ | Integer | Reichweite in km | `430` | `TotalRange` |

Weitere skalare Felder, beispielsweise zusätzliche Angaben eines sekundären Antriebs, können als automatisch erkannte API-Variablen erscheinen. `carCapturedTimestamp` wird nicht zusätzlich angelegt.

## vehicle.odometer

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `mileageInKm` | Kilometerstand | ✅ | ⚪ | Number | ≥ 0 km | `123456` | Im Modul gerundeter Integer; nur Werte größer 0 werden übernommen |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601-Zeitstempel | `2026-09-18T08:55:40.132Z` | Keine eigene Variable unter der Instanz |

## vehicle.operations

Eine angebotene Operation ist nicht gleichbedeutend mit einem erfolgreich geprüften Fahrzeugbefehl. Das Modul liest auch die alternative Liste `remoteOperations`.

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `startCharging` | Laden starten | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `startCharging` | Start-Befehl von API angeboten |
| `stopCharging` | Laden stoppen | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `stopCharging` | Stopp-Befehl von API angeboten |
| `setChargingLimit` | Ladelimit | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `setChargingLimit` | Befehl von API angeboten |
| `setChargeMode` | Lademodus | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `setChargeMode` | Befehl von API angeboten |
| `updateChargingProfile` | Ladeprofil | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `updateChargingProfile` | Befehl von API angeboten |
| `startAirConditioning` | Klimatisierung starten | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `startAirConditioning` | Start-Befehl von API angeboten |
| `stopAirConditioning` | Klimatisierung stoppen | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `stopAirConditioning` | Stopp-Befehl von API angeboten |
| `startAuxiliaryHeating` | Standheizung starten | ⚪ | ⚪ | String | vorhanden / nicht vorhanden | `startAuxiliaryHeating` | Verarbeitung im Verbrenner-Test; kein praktischer Enyaq-Nachweis |
| `stopAuxiliaryHeating` | Standheizung stoppen | ⚪ | ⚪ | String | vorhanden / nicht vorhanden | `stopAuxiliaryHeating` | Verarbeitung im Verbrenner-Test; kein praktischer Enyaq-Nachweis |

## vehicle.parkingPosition

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `state` | Parkstatus | ✅ | ⚪ | String | `PARKED` / `IN_MOTION` | `PARKED` | |
| `formattedAddress` | Parkadresse | ✅ | ⚪ | String | Freitext | `Musterstraße 1, 12345 Musterstadt` | Nur bei entsprechender Standortfreigabe des Fahrzeugbenutzers; kann je Benutzer unterschiedlich gewählt werden |
| `gpsCoordinates.latitude` | Breitengrad | ✅ | ⚪ | Number | −90 bis +90 | `48.123456` | Standortfreigabe beachten |
| `gpsCoordinates.longitude` | Längengrad | ✅ | ⚪ | Number | −180 bis +180 | `9.123456` | Standortfreigabe beachten |

Standortdaten werden bei gelieferten Feldern unabhängig von `ShowDetails` angelegt. Diese Option ist kein Datenschutzschalter.

## vehicle.status.overall

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `doorsLocked` | Türverriegelungsstatus | ✅ | ⚪ | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | `YES` | Im vorhandenen Test dem Wert „Fahrzeugstatus“ der App zugeordnet |
| `locked` | Fahrzeugverriegelungsstatus | 🟡 | ⚪ | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | `YES` | Unterschiede der Verriegelungsmeldungen noch unklar |
| `doors` | Türen | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |
| `windows` | Fenster | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |
| `lights` | Licht | ✅ | ⚪ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | `OFF` | |
| `reliableLockStatus` | Zuverlässiger Verriegelungsstatus | 🟡 | ⚪ | String | `LOCKED` / `UNLOCKED` / `UNKNOWN` | `LOCKED` | Unterschiede der Verriegelungsmeldungen noch unklar; im Modul eigenständiger API-Wert |

## vehicle.status.detail

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `sunroof` | Schiebedach | ✅ | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | Für Enyaq 80 von 2023 ausdrücklich `OPEN` bestätigt; der Beispielwert ist kein zusätzlicher Testnachweis |
| `trunk` | Kofferraum | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |
| `bonnet` | Motorhaube | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |

## vehicle.status

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601-Zeitstempel | `2026-09-18T08:55:40.122Z` | Keine eigene Variable; `LastUpdate` ist der lokale Abrufzeitpunkt |

## Diagnosevariablen und automatisch erkannte Felder

Die Fahrzeugdaten in der Matrix werden bei vorhandenem API-Feld unabhängig von `ShowDetails` angelegt. Die Option ergänzt `ApiKeyExpiresAtVar`, `RequestsRemaining`, `PartialErrors`, `PendingCommands` und `CommandStatus` sowie bei vorhandenem Fahrzeugdatensatz `APISupportedFeatures` und bei gelieferten Lademodi `APIAvailableChargeModes`. Für den normalen Modulbetrieb werden diese sichtbaren Diagnosevariablen nicht benötigt. Bereits angelegte Diagnosevariablen bleiben beim Ausschalten der Option erhalten.

Weitere unbekannte skalare API-Felder werden automatisch mit aus dem API-Pfad abgeleiteten Idents angelegt. Das ist Codeunterstützung, kein praktischer Fahrzeugnachweis. Nicht jede Objektliste wird in Variablen zerlegt. Details zu Datentypen, Positionen, Einheiten und Ausnahmen stehen in der [Modul-Dokumentation](../MySkoda/README.md).

## Automatisierte Prüfungen

Die GitHub-Actions-Pipeline führt PHP-Syntaxprüfung, JSON-Prüfung, Struktur-/Schnittstellenprüfung und isolierte Laufzeitprüfungen aus. Die Strukturprüfung vergleicht die bekannten Idents, Datentypen und Positionen sowie die öffentlichen Funktionsnamen mit `public_api_contract.json` und prüft die deutschen Übersetzungen. Der Vertrag enthält 84 bekannte Variablendefinitionen einschließlich optionaler FIN- und Diagnosewerte sowie 14 öffentliche Funktionen. Das ist keine feste Variablenanzahl pro Fahrzeug.

**Die Markdown-Tabellen werden nicht automatisch mit dem Code verglichen.** Der Strukturtest kontrolliert das Vorhandensein der benötigten Dokumentationsdateien, liest aber weder README-Tabellen als Variablenvertrag ein noch bevorzugt er eine Datei anhand ihres Namenszusatzes. Inhaltliche Dokumentationsänderungen müssen daher zusätzlich geprüft werden.

`runtime_invariants.php` lädt die echte Modulklasse gegen eine isolierte Symcon-Nachbildung. Geprüft werden unter anderem Erstanlage, wiederholtes `ApplyChanges`, Erhalt benutzerdefinierter Metadaten in den geprüften Fällen, Deaktivieren optionaler Variablengruppen, Typ-/Objektkonflikte, Rohantwort-Cache, Einheiten, ausstehende Befehle, Rücksetzen bei Fehlern, Temperaturvormerkung, OpenAPI-Cache, Ladegeschwindigkeit, zusätzliche bekannte Lade-/Klimafelder und Wiederverwendung des Fahrzeugbilds. Der Test arbeitet ohne Zugangsdaten; cURL-Aufrufe werden in der Pipeline deaktiviert.

`api_information_values.php` verwendet zusätzlich Verbrenner- und Standheizungs-Prüfdaten. Geprüft werden die feldabhängige Anlage ohne Elektro-Platzhalter, Tank-/Reichweitenwerte, Umrechnung der Standheizungsdauer, der minimale Standheizungs-Startbody, die Standardaktion bei hinterlegter S-PIN und angebotenen Operationen sowie die Erfassung unbekannter skalarer API-Felder. Die Datei bindet die Laufzeitprüfungen mit ein.

Lokal im Repository ausführen, nicht innerhalb einer produktiven Symcon-Instanz:

```sh
find MySkoda tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
python3 -m json.tool library.json > /dev/null
python3 -m json.tool MySkoda/module.json > /dev/null
python3 -m json.tool MySkoda/form.json > /dev/null
python3 -m json.tool MySkoda/locale.json > /dev/null
python3 -m json.tool tests/public_api_contract.json > /dev/null
python3 tests/validate_structure.py
php -d disable_functions=curl_init,curl_exec tests/runtime_invariants.php
php -d disable_functions=curl_init,curl_exec tests/api_information_values.php
```

## Praktische Abnahme

In einer realen Symcon-Umgebung sind eine neue Instanz und eine vorhandene Instanz zu prüfen. Dazu gehören die Darstellung aller gewünschten Variablen, der Erhalt manueller Anpassungen nach Übernehmen und Neustart, Fahrzeugabfragen sowie bewusst ausgelöste Lade-, Klima- und gegebenenfalls Standheizungsbefehle. Außerdem sind Bilder, Mitteilungen und gegebenenfalls Archivierung mit der tatsächlichen Umgebung zu prüfen. Die automatische Archivierung benötigt `StateOfCharge`, `TargetSOC`, `ChargePower` und `Mileage` gemeinsam; ein Verbrenner ohne Ladedaten erhält darüber auch kein automatisches Kilometerstand-Logging.

Ein erfolgreicher automatisierter Test bestätigt weder die aktuelle Erreichbarkeit der Škoda-Dienste noch die Umsetzung eines Befehls im Fahrzeug. Bei neuen Fahrzeugnachweisen sind Modelljahr, geprüfter Zustand und beobachtete Antwort festzuhalten. Rohantworten müssen vor Veröffentlichung von FIN, Kennzeichen, Standort und anderen persönlichen Daten bereinigt werden. Ein aktueller `LastUpdate` bestätigt nicht, dass jedes Fahrzeugfeld neu geliefert wurde.

Die technische Datenpunktreferenz befindet sich in [MySkoda/README.md](../MySkoda/README.md).
