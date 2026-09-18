# Fahrzeug-Kompatibilität

Diese Tabelle dokumentiert, welche MySkoda-Datenpunkte und Funktionen mit realen Fahrzeugen geprüft wurden.

**Legende:** ✅ Getestet · 🟡 Vermutet · ❌ Nicht vorhanden

## vehicle

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `name` | Fahrzeugname | ✅ |
| `licensePlate` | Kennzeichen | ✅ |
| `vin` | Fahrzeug-Identifikationsnummer | ✅ |
| `renderUrl` | Fahrzeugbild | ✅ |

## vehicle.airConditioning

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `state` | Klimastatus | ✅ |
| `airConditioningAtUnlock` | Klima beim Entriegeln | ✅ |
| `carCapturedTimestamp` | Zeitstempel | ✅ |
| `targetTemperature.value` | Solltemperatur | ✅ |
| `targetTemperature.unit` | Temperatureinheit | ✅ |
| `windowHeating.enabled` | Fensterheizung aktiviert | ✅ |
| `windowHeating.front` | Frontscheibenheizung | ✅ |
| `windowHeating.rear` | Heckscheibenheizung | ✅ |

## vehicle.charging

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `isVehicleInSavedLocation` | Gespeicherter Ladeort | ✅ |
| `carCapturedTimestamp` | Zeitstempel | ✅ |
| `settings.autoUnlockPlugWhenCharged` | Stecker nach Ladeende entriegeln | ✅ |
| `settings.availableChargeModes` | Verfügbare Lademodi | ✅ |
| `settings.batteryCareModeTargetValueInPercent` | Ziel Batterieschonmodus | ✅ |
| `settings.chargingCareMode` | Batterieschonmodus | ✅ |
| `settings.maxChargeCurrentAc` | Max. AC-Ladestrom | ✅ |
| `settings.preferredChargeMode` | Bevorzugter Lademodus | ✅ |
| `settings.targetStateOfChargeInPercent` | Ladelimit | ✅ |
| `status.battery.remainingCruisingRangeInMeters` | Restreichweite | ✅ |
| `status.battery.stateOfChargeInPercent` | Batterieladestand | ✅ |
| `status.chargePowerInKw` | Ladeleistung | ✅ |
| `status.fullyChargedAt` | Vollständig geladen um | ✅ |
| `status.remainingTimeToFullyChargedInMinutes` | Restladezeit | ✅ |
| `status.state` | Ladestatus | ✅ |

## vehicle.chargingProfiles

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `profiles` | Ladeprofile | 🟡 |
| `carCapturedTimestamp` | Zeitstempel | ✅ |

## vehicle.odometer

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `mileageInKm` | Kilometerstand | ✅ |
| `carCapturedTimestamp` | Zeitstempel | ✅ |

## vehicle.operations

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `startCharging` | Laden starten | 🟡 |
| `stopCharging` | Laden stoppen | 🟡 |
| `setChargingLimit` | Ladelimit setzen | 🟡 |
| `setChargeMode` | Lademodus setzen | 🟡 |
| `updateChargingProfile` | Ladeprofil aktualisieren | 🟡 |
| `startAirConditioning` | Klimatisierung starten | 🟡 |
| `stopAirConditioning` | Klimatisierung stoppen | 🟡 |

## vehicle.parkingPosition

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `state` | Parkstatus | ✅ |
| `formattedAddress` | Parkadresse | ✅ |
| `gpsCoordinates.latitude` | Breitengrad | ✅ |
| `gpsCoordinates.longitude` | Längengrad | ✅ |

## vehicle.status.overall

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `doorsLocked` | Türen verriegelt | 🟡 |
| `locked` | Fahrzeug verriegelt | ✅ |
| `doors` | Türstatus | ✅ |
| `windows` | Fensterstatus | ✅ |
| `lights` | Lichtstatus | ✅ |
| `reliableLockStatus` | Zuverlässiger Verriegelungsstatus | 🟡 |

## vehicle.status.detail

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `sunroof` | Schiebedach | ✅ |
| `trunk` | Kofferraum | ✅ |
| `bonnet` | Motorhaube | ✅ |

## vehicle.status

| API | Deutsch | Enyaq 80<br>2022 |
|---|---|:---:|
| `carCapturedTimestamp` | Zeitstempel | ✅ |

Weitere Fahrzeuge werden jeweils als zusätzliche Spalte rechts ergänzt.
