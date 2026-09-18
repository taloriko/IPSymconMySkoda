# Fahrzeug-Kompatibilität

Diese Tabelle dokumentiert, welche MySkoda-Datenpunkte und Funktionen mit realen Fahrzeugen geprüft wurden.

**Legende:** ✅ Getestet · 🟡 Vermutet · ❌ Nicht vorhanden

## vehicle

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `name` | Fahrzeugname | ✅ | |
| `licensePlate` | Kennzeichen | ✅ | |
| `vin` | FIN / VIN | ✅ | |
| `renderUrl` | Fahrzeugbild | ✅ | Wird für das Fahrzeugbild verwendet |

## vehicle.airConditioning

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `state` | Klimatisierung | ✅ | |
| `airConditioningAtUnlock` | Klimatisierung beim Entriegeln | ✅ | |
| `carCapturedTimestamp` | — | ✅ | Keine eigene Variable |
| `targetTemperature.value` | Solltemperatur | ✅ | |
| `targetTemperature.unit` | Einheit Solltemperatur | ✅ | |
| `windowHeating.enabled` | Scheibenheizung aktiviert | ✅ | |
| `windowHeating.front` | Frontscheibenheizung | ✅ | |
| `windowHeating.rear` | Heckscheibenheizung | ✅ | |

## vehicle.charging

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `isVehicleInSavedLocation` | An gespeichertem Ladeort | ✅ | |
| `carCapturedTimestamp` | — | ✅ | Keine eigene Variable |
| `settings.autoUnlockPlugWhenCharged` | Automatische Steckerentriegelung | ✅ | |
| `settings.availableChargeModes` | Lademodus | ✅ | Verfügbare Auswahl |
| `settings.batteryCareModeTargetValueInPercent` | Battery-Care-Ziel | ✅ | |
| `settings.chargingCareMode` | Battery Care Mode | ✅ | |
| `settings.maxChargeCurrentAc` | Maximaler AC-Ladestrom | ✅ | |
| `settings.preferredChargeMode` | Lademodus | ✅ | Aktuell gewählter Modus |
| `settings.targetStateOfChargeInPercent` | Ladelimit | ✅ | |
| `status.battery.remainingCruisingRangeInMeters` | Reichweite | ✅ | |
| `status.battery.stateOfChargeInPercent` | Ladezustand | ✅ | |
| `status.chargePowerInKw` | Ladeleistung | ✅ | |
| `status.fullyChargedAt` | Vollgeladen um | ✅ | |
| `status.remainingTimeToFullyChargedInMinutes` | Restladezeit | ✅ | |
| `status.state` | Ladestatus | ✅ | |

## vehicle.chargingProfiles

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `profiles` | — | 🟡 | Über `GetChargingProfiles()` abrufbar; im Test leer |
| `carCapturedTimestamp` | — | ✅ | Keine eigene Variable |

## vehicle.odometer

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `mileageInKm` | Kilometerstand | ✅ | |
| `carCapturedTimestamp` | — | ✅ | Keine eigene Variable |

## vehicle.operations

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `startCharging` | Laden | 🟡 | Start-Befehl von API angeboten |
| `stopCharging` | Laden | 🟡 | Stopp-Befehl von API angeboten |
| `setChargingLimit` | Ladelimit | 🟡 | Befehl von API angeboten |
| `setChargeMode` | Lademodus | 🟡 | Befehl von API angeboten |
| `updateChargingProfile` | — | 🟡 | Befehl von API angeboten |
| `startAirConditioning` | Klimatisierung | 🟡 | Start-Befehl von API angeboten |
| `stopAirConditioning` | Klimatisierung | 🟡 | Stopp-Befehl von API angeboten |

## vehicle.parkingPosition

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `state` | Parkstatus | ✅ | |
| `formattedAddress` | Parkadresse | ✅ | |
| `gpsCoordinates.latitude` | Breitengrad | ✅ | |
| `gpsCoordinates.longitude` | Längengrad | ✅ | |

## vehicle.status.overall

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `doorsLocked` | Türverriegelungsstatus | 🟡 | `YES / NO / OPENED / TRUNK_OPENED / UNKNOWN` |
| `locked` | Fahrzeugverriegelungsstatus | ✅ | `YES / NO / OPENED / TRUNK_OPENED / UNKNOWN` |
| `doors` | Türen offen | ✅ | Im Modul als Ja/Nein dargestellt |
| `windows` | Fenster offen | ✅ | Im Modul als Ja/Nein dargestellt |
| `lights` | Licht an | ✅ | Im Modul als Ja/Nein dargestellt |
| `reliableLockStatus` | Zuverlässiger Verriegelungsstatus | 🟡 | `LOCKED / UNLOCKED / UNKNOWN` |

## vehicle.status.detail

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `sunroof` | Schiebedach offen | ✅ | Im Modul als Ja/Nein dargestellt |
| `trunk` | Kofferraum offen | ✅ | Im Modul als Ja/Nein dargestellt |
| `bonnet` | Motorhaube offen | ✅ | Im Modul als Ja/Nein dargestellt |

## vehicle.status

| API | Deutsch | Enyaq 80<br>2022 | Bemerkung |
|---|---|:---:|---|
| `carCapturedTimestamp` | — | ✅ | Keine eigene Variable |

Weitere Fahrzeuge werden jeweils als zusätzliche Spalte rechts ergänzt.
