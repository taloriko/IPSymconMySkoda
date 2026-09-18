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

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `name` | Getestet |
| `licensePlate` | Getestet |
| `vin` | Getestet |
| `renderUrl` | Getestet |

## vehicle.airConditioning

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `state` | Getestet |
| `airConditioningAtUnlock` | Getestet |
| `carCapturedTimestamp` | Getestet |
| `targetTemperature.value` | Getestet |
| `targetTemperature.unit` | Getestet |
| `windowHeating.enabled` | Getestet |
| `windowHeating.front` | Getestet |
| `windowHeating.rear` | Getestet |

## vehicle.charging

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `isVehicleInSavedLocation` | Getestet |
| `carCapturedTimestamp` | Getestet |
| `settings.autoUnlockPlugWhenCharged` | Getestet |
| `settings.availableChargeModes` | Getestet |
| `settings.batteryCareModeTargetValueInPercent` | Getestet |
| `settings.chargingCareMode` | Getestet |
| `settings.maxChargeCurrentAc` | Getestet |
| `settings.preferredChargeMode` | Getestet |
| `settings.targetStateOfChargeInPercent` | Getestet |
| `status.battery.remainingCruisingRangeInMeters` | Getestet |
| `status.battery.stateOfChargeInPercent` | Getestet |
| `status.chargePowerInKw` | Getestet |
| `status.fullyChargedAt` | Getestet |
| `status.remainingTimeToFullyChargedInMinutes` | Getestet |
| `status.state` | Getestet |

## vehicle.chargingProfiles

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `profiles` | Getestet |
| `carCapturedTimestamp` | Getestet |

## vehicle.odometer

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `mileageInKm` | Getestet |
| `carCapturedTimestamp` | Getestet |

## vehicle.operations

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `startCharging` | Vermutet |
| `stopCharging` | Vermutet |
| `setChargingLimit` | Vermutet |
| `setChargeMode` | Vermutet |
| `updateChargingProfile` | Vermutet |
| `startAirConditioning` | Vermutet |
| `stopAirConditioning` | Vermutet |

## vehicle.parkingPosition

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `state` | Getestet |
| `formattedAddress` | Getestet |
| `gpsCoordinates.latitude` | Getestet |
| `gpsCoordinates.longitude` | Getestet |

## vehicle.status.overall

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `doorsLocked` | Getestet |
| `locked` | Getestet |
| `doors` | Getestet |
| `windows` | Getestet |
| `lights` | Getestet |
| `reliableLockStatus` | Getestet |

## vehicle.status.detail

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `sunroof` | Getestet |
| `trunk` | Getestet |
| `bonnet` | Getestet |

## vehicle.status

| Datenpunkt | Škoda Enyaq 80 (2022) |
|---|---|
| `carCapturedTimestamp` | Getestet |

---

Weitere Fahrzeuge werden jeweils als zusätzliche Spalte rechts ergänzt.
