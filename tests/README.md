# Fahrzeug-Kompatibilität

Diese Tabelle dokumentiert, welche MySkoda-Datenpunkte und Funktionen mit realen Fahrzeugen geprüft wurden.

## Status

| Status | Bedeutung |
|---|---|
| **Getestet** | Mit diesem Fahrzeug real geprüft und im echten API-Datensatz vorhanden |
| **Vermutet** | Von der API angekündigt oder technisch zu erwarten, aber noch nicht abschließend am Fahrzeug geprüft |
| **Nicht vorhanden** | Bei diesem Fahrzeug nicht vorhanden bzw. nicht von der API geliefert |

---

## vehicle

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `name` | Fahrzeugname | Getestet |
| `licensePlate` | Kennzeichen | Getestet |
| `vin` | Fahrzeug-Identifikationsnummer (VIN) | Getestet |
| `renderUrl` | URL des Fahrzeugbilds | Getestet |

## vehicle.airConditioning

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `state` | Status der Klimatisierung | Getestet |
| `airConditioningAtUnlock` | Klimatisierung beim Entriegeln | Getestet |
| `carCapturedTimestamp` | Zeitstempel der Fahrzeugdaten | Getestet |
| `targetTemperature.value` | Solltemperatur | Getestet |
| `targetTemperature.unit` | Einheit der Solltemperatur | Getestet |
| `windowHeating.enabled` | Fensterheizung aktiviert | Getestet |
| `windowHeating.front` | Frontscheibenheizung | Getestet |
| `windowHeating.rear` | Heckscheibenheizung | Getestet |

## vehicle.charging

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `isVehicleInSavedLocation` | Fahrzeug an gespeichertem Ladeort | Getestet |
| `carCapturedTimestamp` | Zeitstempel der Fahrzeugdaten | Getestet |
| `settings.autoUnlockPlugWhenCharged` | Ladestecker nach Ladeende automatisch entriegeln | Getestet |
| `settings.availableChargeModes` | Verfügbare Lademodi | Getestet |
| `settings.batteryCareModeTargetValueInPercent` | Zielladestand des Batterieschonmodus | Getestet |
| `settings.chargingCareMode` | Batterieschonmodus | Getestet |
| `settings.maxChargeCurrentAc` | Maximaler AC-Ladestrom | Getestet |
| `settings.preferredChargeMode` | Bevorzugter Lademodus | Getestet |
| `settings.targetStateOfChargeInPercent` | Ladelimit | Getestet |
| `status.battery.remainingCruisingRangeInMeters` | Verbleibende Reichweite | Getestet |
| `status.battery.stateOfChargeInPercent` | Batterieladestand | Getestet |
| `status.chargePowerInKw` | Ladeleistung | Getestet |
| `status.fullyChargedAt` | Voraussichtlich vollständig geladen um | Getestet |
| `status.remainingTimeToFullyChargedInMinutes` | Restladezeit bis vollständig geladen | Getestet |
| `status.state` | Ladestatus | Getestet |

## vehicle.chargingProfiles

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `profiles` | Ladeprofile | Getestet |
| `carCapturedTimestamp` | Zeitstempel der Fahrzeugdaten | Getestet |

## vehicle.odometer

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `mileageInKm` | Kilometerstand | Getestet |
| `carCapturedTimestamp` | Zeitstempel der Fahrzeugdaten | Getestet |

## vehicle.operations

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `startCharging` | Laden starten | Vermutet |
| `stopCharging` | Laden stoppen | Vermutet |
| `setChargingLimit` | Ladelimit setzen | Vermutet |
| `setChargeMode` | Lademodus setzen | Vermutet |
| `updateChargingProfile` | Ladeprofil aktualisieren | Vermutet |
| `startAirConditioning` | Klimatisierung starten | Vermutet |
| `stopAirConditioning` | Klimatisierung stoppen | Vermutet |

## vehicle.parkingPosition

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `state` | Parkstatus | Getestet |
| `formattedAddress` | Formatierte Parkadresse | Getestet |
| `gpsCoordinates.latitude` | Breitengrad | Getestet |
| `gpsCoordinates.longitude` | Längengrad | Getestet |

## vehicle.status.overall

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `doorsLocked` | Verriegelungsstatus der Türen | Getestet |
| `locked` | Verriegelungsstatus des Fahrzeugs | Getestet |
| `doors` | Türstatus | Getestet |
| `windows` | Fensterstatus | Getestet |
| `lights` | Lichtstatus | Getestet |
| `reliableLockStatus` | Zuverlässiger Verriegelungsstatus | Getestet |

## vehicle.status.detail

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `sunroof` | Schiebedachstatus | Getestet |
| `trunk` | Kofferraumstatus | Getestet |
| `bonnet` | Motorhaubenstatus | Getestet |

## vehicle.status

| Datenpunkt | Deutsch | Škoda Enyaq 80 (2022) |
|---|---|---|
| `carCapturedTimestamp` | Zeitstempel der Fahrzeugdaten | Getestet |

---

Weitere Fahrzeuge werden jeweils als zusätzliche Spalte rechts ergänzt.
