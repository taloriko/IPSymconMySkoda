# Fahrzeug-Kompatibilität

Diese Tabelle dokumentiert, welche MySkoda-Datenpunkte und Funktionen mit realen Fahrzeugen geprüft wurden.

**Legende:** ✅ Getestet · 🟡 Vermutet · ❌ Nicht vorhanden

## vehicle

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `name` | Fahrzeugname | ✅ | String | Freitext | |
| `licensePlate` | Kennzeichen | ✅ | String | Freitext | |
| `vin` | FIN / VIN | ✅ | String | 17-stellige VIN | |
| `renderUrl` | Fahrzeugbild | ✅ | String | URL | Wird für das Fahrzeugbild verwendet |

## vehicle.airConditioning

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `state` | Klimatisierung | ✅ | String | `OFF` / `COOLING` / `HEATING` / `HEATING_AUXILIARY` / `VENTILATION` | |
| `airConditioningAtUnlock` | Klimatisierung beim Entriegeln | ✅ | Boolean | `true` / `false` | |
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | Keine eigene Variable |
| `targetTemperature.value` | Solltemperatur | ✅ | Number | 16–30 °C | Modul: 0,5-°C-Schritte |
| `targetTemperature.unit` | Einheit Solltemperatur | ✅ | String | z. B. `CELSIUS` | |
| `windowHeating.enabled` | Scheibenheizung aktiviert | ✅ | Boolean | `true` / `false` | |
| `windowHeating.front` | Frontscheibenheizung | ✅ | String | `ON` / `OFF` / `UNKNOWN` | |
| `windowHeating.rear` | Heckscheibenheizung | ✅ | String | `ON` / `OFF` / `UNKNOWN` | |

## vehicle.charging

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `isVehicleInSavedLocation` | An gespeichertem Ladeort | ✅ | Boolean | `true` / `false` | |
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | Keine eigene Variable |
| `settings.autoUnlockPlugWhenCharged` | Automatische Steckerentriegelung | ✅ | String | `OFF` / `ON` / `PERMANENT` / `UNKNOWN` | |
| `settings.availableChargeModes` | Lademodus | ✅ | Array[String] | `MANUAL` / `TIMER` / `TIMER_CHARGING_WITH_CLIMATISATION` / `PREFERRED_CHARGING_TIMES` / `ONLY_OWN_CURRENT` / `IMMEDIATE_DISCHARGING` / `HOME_STORAGE_CHARGING` | Verfügbare Auswahl |
| `settings.batteryCareModeTargetValueInPercent` | Battery-Care-Ziel | ✅ | Integer | 0–100 % | |
| `settings.chargingCareMode` | Battery Care Mode | ✅ | String | `ACTIVATED` / `DEACTIVATED` / `ACTIVE` / `INACTIVE` / `UNKNOWN` | |
| `settings.maxChargeCurrentAc` | Maximaler AC-Ladestrom | ✅ | String | `MAXIMUM` / `REDUCED` / `UNKNOWN` | |
| `settings.preferredChargeMode` | Lademodus | ✅ | String | `MANUAL` / `TIMER` / `TIMER_CHARGING_WITH_CLIMATISATION` / `PREFERRED_CHARGING_TIMES` / `ONLY_OWN_CURRENT` / `IMMEDIATE_DISCHARGING` / `HOME_STORAGE_CHARGING` | Aktuell gewählter Modus |
| `settings.targetStateOfChargeInPercent` | Ladelimit | ✅ | Integer | 50–100 % | Modul: 10-%-Schritte |
| `status.battery.remainingCruisingRangeInMeters` | Reichweite | ✅ | Integer | ≥ 0 m | Im Modul in km ausgegeben |
| `status.battery.stateOfChargeInPercent` | Ladezustand | ✅ | Integer | 0–100 % | |
| `status.chargePowerInKw` | Ladeleistung | ✅ | Number | ≥ 0 kW | Im Modul in W ausgegeben |
| `status.fullyChargedAt` | Vollgeladen um | ✅ | String | ISO-8601-Zeitstempel | |
| `status.remainingTimeToFullyChargedInMinutes` | Restladezeit | ✅ | Integer | ≥ 0 min | |
| `status.state` | Ladestatus | ✅ | String | `CONNECT_CABLE` / `CHARGING` / `CONSERVING` / `READY_FOR_CHARGING` / `DISCHARGING` / `CHARGING_INTERRUPTED` / `OFF` / `UNKNOWN` | |

## vehicle.chargingProfiles

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `profiles` | — | 🟡 | Array[Object] | leer oder Ladeprofil-Objekte | Über `GetChargingProfiles()` abrufbar; im Test leer |
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | Keine eigene Variable |

## vehicle.odometer

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `mileageInKm` | Kilometerstand | ✅ | Number | ≥ 0 km | Im Modul als Integer ausgegeben |
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | Keine eigene Variable |

## vehicle.operations

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `startCharging` | Laden | 🟡 | String | vorhanden / nicht vorhanden | Start-Befehl von API angeboten |
| `stopCharging` | Laden | 🟡 | String | vorhanden / nicht vorhanden | Stopp-Befehl von API angeboten |
| `setChargingLimit` | Ladelimit | 🟡 | String | vorhanden / nicht vorhanden | Befehl von API angeboten |
| `setChargeMode` | Lademodus | 🟡 | String | vorhanden / nicht vorhanden | Befehl von API angeboten |
| `updateChargingProfile` | — | 🟡 | String | vorhanden / nicht vorhanden | Befehl von API angeboten |
| `startAirConditioning` | Klimatisierung | 🟡 | String | vorhanden / nicht vorhanden | Start-Befehl von API angeboten |
| `stopAirConditioning` | Klimatisierung | 🟡 | String | vorhanden / nicht vorhanden | Stopp-Befehl von API angeboten |

## vehicle.parkingPosition

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `state` | Parkstatus | ✅ | String | `PARKED` / `MOVING` / `IN_MOTION` / `DRIVING` / `UNKNOWN` | |
| `formattedAddress` | Parkadresse | ✅ | String | Freitext | |
| `gpsCoordinates.latitude` | Breitengrad | ✅ | Number | −90 bis +90 | |
| `gpsCoordinates.longitude` | Längengrad | ✅ | Number | −180 bis +180 | |

## vehicle.status.overall

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `doorsLocked` | Türverriegelungsstatus | 🟡 | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | Deutsche Anzeige im Modul |
| `locked` | Fahrzeugverriegelungsstatus | ✅ | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | Deutsche Anzeige im Modul |
| `doors` | Türen offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | Im Modul derzeit als Ja/Nein dargestellt |
| `windows` | Fenster offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | Im Modul derzeit als Ja/Nein dargestellt |
| `lights` | Licht an | ✅ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | Im Modul derzeit als Ja/Nein dargestellt |
| `reliableLockStatus` | Zuverlässiger Verriegelungsstatus | 🟡 | String | `LOCKED` / `UNLOCKED` / `UNKNOWN` | Deutsche Anzeige im Modul |

## vehicle.status.detail

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `sunroof` | Schiebedach offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | Im Modul derzeit als Ja/Nein dargestellt |
| `trunk` | Kofferraum offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | Im Modul derzeit als Ja/Nein dargestellt |
| `bonnet` | Motorhaube offen | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | Im Modul derzeit als Ja/Nein dargestellt |

## vehicle.status

| API | Deutsch | Enyaq 80<br>2022 | Datentyp | Mögliche Werte | Bemerkung |
|---|---|:---:|---|---|---|
| `carCapturedTimestamp` | — | ✅ | String | ISO-8601-Zeitstempel | Keine eigene Variable |

Weitere Fahrzeuge werden jeweils als zusätzliche Spalte rechts ergänzt.
