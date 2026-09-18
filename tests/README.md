# Fahrzeug-Kompatibilität

Diese Tabelle dokumentiert, welche MySkoda-Datenpunkte und Funktionen mit realen Fahrzeugen geprüft wurden.

**Legende:** ✅ Getestet · 🟡 Vermutet · ❌ Nicht vorhanden

## vehicle

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `name` | Fahrzeugname | ✅ | String | Freitext | `Harry` | Wird in der APP festgelegt "Satus" --> "Details zum Fahrzeug" --> "Name des Fahrzeugs" |
| `licensePlate` | Kennzeichen | ✅ | String | Freitext | `S-AB 123E` | Wird in der APP festgelegt "Satus" --> "Details zum Fahrzeug" --> "Name des Fahrzeugs"  |
| `vin` | FIN / VIN | ✅ | String | 17-stellige VIN | `TMB…7448` | Fahrgestellnummer des Fahrzeugs |
| `renderUrl` | Fahrzeugbild | ✅ | String | URL | `https://…/vehicle.png` | Bild von Skoda, optisch passend zur Bestellkonfiguration |

## vehicle.airConditioning

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `state` | Klimatisierung | ✅ | String | `OFF` / `COOLING` / `HEATING` / `HEATING_AUXILIARY` / `VENTILATION` | `OFF` | |
| `airConditioningAtUnlock` | Klimatisierung beim Entriegeln | ✅ | Boolean | `true` / `false` | `true` | Beginnt sofort mit der Klimatisierung beim entriegeln (Auch bei Annäherung, wenn aktiviert)|
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | `2026-09-18T08:49:39Z` | Keine eigene Variable unter der Instanz |
| `targetTemperature.value` | Solltemperatur | ✅ | Number | 16–30 °C | `22` | Modul: 0,5-°C-Schritte (Wird nur gesendet wenn Klima dannach aktiviert wird)|
| `targetTemperature.unit` | Einheit Solltemperatur | ✅ | String | z. B. `CELSIUS` | `CELSIUS` | Ob hier auch Fahrenheit möglich ist konnte ich nicht testen |
| `windowHeating.enabled` | Scheibenheizung aktiviert | 🟡 | Boolean | `true` / `false` | `true` | Noch unklar ob es grundsätzlich bei betrieb gesetzt wird oder ob es zum "Intiligenten Klimatisieren gehört" |
| `windowHeating.front` | Frontscheibenheizung | 🟡 | String | `ON` / `OFF` / `UNKNOWN` | `OFF` | Wir bei mir als `OFF` gemeldet habe dies aber nicht als Austattung hier hätte ich `UNKNOWN` erwartet |
| `windowHeating.rear` | Heckscheibenheizung | 🟡 | String | `ON` / `OFF` / `UNKNOWN` | `OFF` | Noch nicht getestet |

## vehicle.charging (FIXME)

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `isVehicleInSavedLocation` | An gespeichertem Ladeort | ✅ | Boolean | `true` / `false` | `false` | Ist das Fahrzeug an einem Ladeort, der vorherher im Fahrzeug gespeichert und definiert wurde |
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | `2026-09-18T08:53:06Z` | Keine eigene Variable unter der Instanz |
| `settings.autoUnlockPlugWhenCharged` | Automatische Steckerentriegelung | ✅ | String | `OFF` / `ON` / `PERMANENT` / `UNKNOWN` | `OFF` | Wird nach dem Beenden des Ladevorgangs das Kabel an der Seite des Fahrzeuges entriegelt (Wenn aktiviert kann das Kabel gestohlen werden) |
| `settings.availableChargeModes` | Lademodus | ✅ | Array[String] | `MANUAL` / `TIMER` / `TIMER_CHARGING_WITH_CLIMATISATION` / `PREFERRED_CHARGING_TIMES` / `ONLY_OWN_CURRENT` / `IMMEDIATE_DISCHARGING` / `HOME_STORAGE_CHARGING` | `["MANUAL"]` | Verfügbare Auswahl |
| `settings.batteryCareModeTargetValueInPercent` | Battery-Care-Ziel | ✅ | Integer | 0–100 % | `80` | |
| `settings.chargingCareMode` | Battery Care Mode | ✅ | String | `ACTIVATED` / `DEACTIVATED` / `ACTIVE` / `INACTIVE` / `UNKNOWN` | `ACTIVATED` | |
| `settings.maxChargeCurrentAc` | Maximaler AC-Ladestrom | ✅ | String | `MAXIMUM` / `REDUCED` / `UNKNOWN` | `MAXIMUM` | |
| `settings.preferredChargeMode` | Lademodus | ✅ | String | `MANUAL` / `TIMER` / `TIMER_CHARGING_WITH_CLIMATISATION` / `PREFERRED_CHARGING_TIMES` / `ONLY_OWN_CURRENT` / `IMMEDIATE_DISCHARGING` / `HOME_STORAGE_CHARGING` | `MANUAL` | Aktuell gewählter Modus |
| `settings.targetStateOfChargeInPercent` | Ladelimit | ✅ | Integer | 50–100 % | `80` | Modul: 10-%-Schritte |
| `status.battery.remainingCruisingRangeInMeters` | Reichweite | ✅ | Integer | ≥ 0 m | `385000` | Im Modul in km ausgegeben |
| `status.battery.stateOfChargeInPercent` | Ladezustand | ✅ | Integer | 0–100 % | `78` | |
| `status.chargePowerInKw` | Ladeleistung | ✅ | Number | ≥ 0 kW | `0` | Im Modul in W ausgegeben |
| `status.fullyChargedAt` | Vollgeladen um | ✅ | String | ISO-8601-Zeitstempel | `2026-09-18T08:49:38Z` | |
| `status.remainingTimeToFullyChargedInMinutes` | Restladezeit | ✅ | Integer | ≥ 0 min | `0` | |
| `status.state` | Ladestatus | ✅ | String | `CONNECT_CABLE` / `CHARGING` / `CONSERVING` / `READY_FOR_CHARGING` / `DISCHARGING` / `CHARGING_INTERRUPTED` / `OFF` / `UNKNOWN` | `CONNECT_CABLE` | |

## vehicle.chargingProfiles

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `profiles` | — | 🟡 | Array[Object] | leer oder Ladeprofil-Objekte | `[]` | Über `GetChargingProfiles()` abrufbar; im Test leer |
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | `2026-09-18T08:49:35.868Z` | Keine eigene Variable unter der Instanz |

## vehicle.odometer

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `mileageInKm` | Kilometerstand | ✅ | Number | ≥ 0 km | `123456` | Im Modul als Integer ausgegeben |
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | `2026-09-18T08:55:40.132Z` | Keine eigene Variable unter der Instanz |

## vehicle.operations

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `startCharging` | Laden | 🟡 | String | vorhanden / nicht vorhanden | `startCharging` | Start-Befehl von API angeboten |
| `stopCharging` | Laden | 🟡 | String | vorhanden / nicht vorhanden | `stopCharging` | Stopp-Befehl von API angeboten |
| `setChargingLimit` | Ladelimit | 🟡 | String | vorhanden / nicht vorhanden | `setChargingLimit` | Befehl von API angeboten |
| `setChargeMode` | Lademodus | 🟡 | String | vorhanden / nicht vorhanden | `setChargeMode` | Befehl von API angeboten |
| `updateChargingProfile` | — | 🟡 | String | vorhanden / nicht vorhanden | `updateChargingProfile` | Befehl von API angeboten |
| `startAirConditioning` | Klimatisierung | 🟡 | String | vorhanden / nicht vorhanden | `startAirConditioning` | Start-Befehl von API angeboten |
| `stopAirConditioning` | Klimatisierung | 🟡 | String | vorhanden / nicht vorhanden | `stopAirConditioning` | Stopp-Befehl von API angeboten |

## vehicle.parkingPosition

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `state` | Parkstatus | ✅ | String | `PARKED` / `MOVING` / `IN_MOTION` / `DRIVING` / `UNKNOWN` | `PARKED` | |
| `formattedAddress` | Parkadresse | ✅ | String | Freitext | `Musterstraße 1, 12345 Musterstadt` | Nur wenn im Fahrzeug am Benutzer die "Standortfreigabe" aktiviert wurde (Kann je Benutzer unterschiedlich gewählt werden)|
| `gpsCoordinates.latitude` | Breitengrad | ✅ | Number | −90 bis +90 | `48.123456` | " |
| `gpsCoordinates.longitude` | Längengrad | ✅ | Number | −180 bis +180 | `9.123456` | " |

## vehicle.status.overall

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `doorsLocked` | Türverriegelungsstatus | 🟡 | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | `YES` | Unterschiede der Verriegelungsmeldungen unklar |
| `locked` | Fahrzeugverriegelungsstatus | 🟡 | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | `YES` | Unterschiede der Verriegelungsmeldungen unklar |
| `doors` | Türen offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` |  |
| `windows` | Fenster offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |
| `lights` | Licht an | ✅ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | `OFF` |  |
| `reliableLockStatus` | Zuverlässiger Verriegelungsstatus | 🟡 | String | `LOCKED` / `UNLOCKED` / `UNKNOWN` | `LOCKED` | Unterschiede der Verriegelungsmeldungen unklar  |

## vehicle.status.detail

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `sunroof` | Schiebedach offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` |  |
| `trunk` | Kofferraum offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` |  |
| `bonnet` | Motorhaube offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |

## vehicle.status

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|---|---|---|---|
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | `2026-09-18T08:55:40.122Z` | eine eigene Variable unter der Instanz |
