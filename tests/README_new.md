# Fahrzeug-Kompatibilität und Tests

Die Fahrzeugmatrix dokumentiert vorhandene praktische Testnachweise. Sie bestätigt nicht automatisch jeden denkbaren Zustand eines Datenpunkts und nicht die Ausführung eines nur als verfügbar gemeldeten Befehls.

**Legende:** ✅ Getestet · 🟡 Vermutet · ⚪ Zu prüfen · ❌ Nicht vorhanden

Für den Enyaq 80 von 2023 ist ausdrücklich der Schiebedachzustand `OPEN` bestätigt. Die übrigen Felder dieses Fahrzeugs bleiben zu prüfen. Automatisierte Codeprüfungen ändern diese Fahrzeugnachweise nicht.

## vehicle

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `name` | Fahrzeugname | ✅ | ⚪ | String | Freitext | `Harry` | Vom Benutzer vergebener Fahrzeugname |
| `licensePlate` | Kennzeichen | ✅ | ⚪ | String | Freitext | `S-AB 123E` | In den Fahrzeugdaten hinterlegtes Kennzeichen |
| `vin` | FIN / VIN | ✅ | ⚪ | String | 17-stellige VIN | `TMB…7448` | Beispiel gekürzt |
| `renderUrl` | Fahrzeugbild | ✅ | ⚪ | String | URL | `https://…/vehicle.png` | Von der API geliefertes Fahrzeugbild |

## vehicle.airConditioning

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `state` | Klimatisierung | ✅ | ⚪ | String | `OFF` / `ON` / `COOLING` / `HEATING` / `HEATING_AUXILIARY` / `VENTILATION` / `INVALID` | `OFF` | |
| `airConditioningAtUnlock` | Klimatisierung beim Entriegeln | ✅ | ⚪ | Boolean | `true` / `false` | `true` | Gelieferter Status; im Modul nicht schreibbar |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601 | `2026-09-18T08:49:39Z` | Keine eigene Variable |
| `targetTemperature.value` | Solltemperatur | ✅ | ⚪ | Number | Bedienbereich 16–30 °C | `22` | Bei ausgeschalteter Klimatisierung lokal vorgemerkt; bei aktiver Klimatisierung unmittelbar gesendet |
| `targetTemperature.unit` | Einheit Solltemperatur | ✅ | ⚪ | String | `CELSIUS` nachgewiesen | `CELSIUS` | Fahrenheit-Betrieb nicht praktisch bestätigt |
| `windowHeating.enabled` | Scheibenheizung aktiviert | ✅ | ⚪ | Boolean | `true` / `false` | `true` | Gelieferter Status |
| `windowHeating.front` | Frontscheibenheizung | 🟡 | ⚪ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | `OFF` | Beim geprüften Fahrzeug trotz fehlender Ausstattung als OFF gemeldet |
| `windowHeating.rear` | Heckscheibenheizung | 🟡 | ⚪ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | `OFF` | Funktion noch nicht praktisch geprüft |

## vehicle.charging

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `isVehicleInSavedLocation` | An gespeichertem Ladeort | ✅ | ⚪ | Boolean | `true` / `false` | `false` | Bezug auf einen im Fahrzeug gespeicherten Ladeort |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601 | `2026-09-18T08:53:06Z` | Keine eigene Variable |
| `settings.autoUnlockPlugWhenCharged` | Automatische Steckerentriegelung | ✅ | ⚪ | String | `OFF` / `ON` / `PERMANENT` | `OFF` | Status der fahrzeugseitigen Entriegelung nach Ladeende |
| `settings.availableChargeModes` | Verfügbare Lademodi | ✅ | ⚪ | Array[String] | `MANUAL` / `TIMER` / `TIMER_CHARGING_WITH_CLIMATISATION` / `PREFERRED_CHARGING_TIMES` / `ONLY_OWN_CURRENT` / `IMMEDIATE_DISCHARGING` / `HOME_STORAGE_CHARGING` / `OTHER` / `OFF` | `["MANUAL"]` | Gemeldete Auswahl |
| `settings.batteryCareModeTargetValueInPercent` | Battery-Care-Ziel | ✅ | ⚪ | Integer | 0–100 % | `80` | Nur lesbar |
| `settings.chargingCareMode` | Battery Care Mode | ✅ | ⚪ | String | `ACTIVATED` / `DEACTIVATED` | `ACTIVATED` | Nur lesbar |
| `settings.maxChargeCurrentAc` | Maximaler AC-Ladestrom | ✅ | ⚪ | String | `MAXIMUM` / `REDUCED` | `MAXIMUM` | Status, kein Amperewert; nur lesbar |
| `settings.preferredChargeMode` | Lademodus | ✅ | ⚪ | String | Bekannte Lademodi wie oben | `MANUAL` | Modulvariable ChargeMode verwendet einen Integer-Index |
| `settings.targetStateOfChargeInPercent` | Ladelimit | ✅ | ⚪ | Integer | 50–100 % | `80` | Bedienoberfläche mit 10-%-Schritten |
| `status.battery.remainingCruisingRangeInMeters` | Reichweite | ✅ | ⚪ | Integer | ≥ 0 m | `385000` | Im Modul in km |
| `status.battery.stateOfChargeInPercent` | Ladezustand | ✅ | ⚪ | Integer | 0–100 % | `78` | |
| `status.chargePowerInKw` | Ladeleistung | ✅ | ⚪ | Number | ≥ 0 kW | `0` | Im Modul in W |
| `status.fullyChargedAt` | Vollgeladen um | ✅ | ⚪ | String | ISO-8601 | `2026-09-18T08:49:38Z` | Im Modul als Unix-Zeitstempel |
| `status.remainingTimeToFullyChargedInMinutes` | Restladezeit | ✅ | ⚪ | Integer | ≥ 0 min | `0` | |
| `status.state` | Ladestatus | ✅ | ⚪ | String | `READY_FOR_CHARGING` / `CONNECT_CABLE` / `CONSERVING` / `CHARGING` / `CHARGING_INTERRUPTED` / `ERROR` | `CONNECT_CABLE` | |

## vehicle.chargingProfiles

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `profiles` | Ladeprofile | 🟡 | ⚪ | Array[Object] | Profilobjekte oder leeres Array | `[]` | Im vorhandenen Test leer; der komplette Bereich ist per GetChargingProfiles abrufbar |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601 | `2026-09-18T08:49:35.868Z` | Keine eigene Variable |

## vehicle.odometer

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `mileageInKm` | Kilometerstand | ✅ | ⚪ | Number | ≥ 0 km | `123456` | Im Modul gerundeter Integer; nur Werte größer 0 übernommen |
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601 | `2026-09-18T08:55:40.132Z` | Keine eigene Variable |

## vehicle.operations

Die angebotene Operation ist nicht gleichbedeutend mit einem erfolgreich geprüften Fahrzeugbefehl.

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `startCharging` | Laden starten | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `startCharging` | Von API angeboten |
| `stopCharging` | Laden stoppen | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `stopCharging` | Von API angeboten |
| `setChargingLimit` | Ladelimit | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `setChargingLimit` | Von API angeboten |
| `setChargeMode` | Lademodus | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `setChargeMode` | Von API angeboten |
| `updateChargingProfile` | Ladeprofil | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `updateChargingProfile` | Von API angeboten |
| `startAirConditioning` | Klimatisierung starten | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `startAirConditioning` | Von API angeboten |
| `stopAirConditioning` | Klimatisierung stoppen | 🟡 | ⚪ | String | vorhanden / nicht vorhanden | `stopAirConditioning` | Von API angeboten |

## vehicle.parkingPosition

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `state` | Parkstatus | ✅ | ⚪ | String | `PARKED` / `IN_MOTION` | `PARKED` | |
| `formattedAddress` | Parkadresse | ✅ | ⚪ | String | Freitext | `Musterstraße 1, 12345 Musterstadt` | Standortfreigabe des Fahrzeugbenutzers beachten |
| `gpsCoordinates.latitude` | Breitengrad | ✅ | ⚪ | Number | −90 bis +90 | `48.123456` | Standortfreigabe beachten |
| `gpsCoordinates.longitude` | Längengrad | ✅ | ⚪ | Number | −180 bis +180 | `9.123456` | Standortfreigabe beachten |

## vehicle.status.overall

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `doorsLocked` | Türverriegelungsstatus | ✅ | ⚪ | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | `YES` | Im vorhandenen Test dem Fahrzeugstatus der App zugeordnet |
| `locked` | Fahrzeugverriegelungsstatus | 🟡 | ⚪ | String | `YES` / `NO` / `OPENED` / `TRUNK_OPENED` / `UNKNOWN` | `YES` | Unterschiede zwischen Verriegelungsmeldungen noch zu klären |
| `doors` | Türen | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |
| `windows` | Fenster | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |
| `lights` | Licht | ✅ | ⚪ | String | `ON` / `OFF` / `INVALID` / `UNKNOWN` | `OFF` | |
| `reliableLockStatus` | Zuverlässiger Verriegelungsstatus | 🟡 | ⚪ | String | `LOCKED` / `UNLOCKED` / `UNKNOWN` | `LOCKED` | Eigenständiger API-Wert |

## vehicle.status.detail

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `sunroof` | Schiebedach | ✅ | ✅ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | Für Enyaq 80 von 2023 ausdrücklich OPEN bestätigt |
| `trunk` | Kofferraum | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |
| `bonnet` | Motorhaube | ✅ | ⚪ | String | `OPEN` / `CLOSED` / `UNSUPPORTED` / `UNKNOWN` | `CLOSED` | |

## vehicle.status

| API | Deutsch | Enyaq 80<br>2022 | Enyaq 80<br>2023 | Datentyp | Mögliche Werte | Beispielwert | Bemerkung |
|---|---|:---:|:---:|---|---|---|---|
| `carCapturedTimestamp` | Erfassungszeitpunkt | ✅ | ⚪ | String | ISO-8601 | `2026-09-18T08:55:40.122Z` | Keine eigene Variable; LastUpdate ist der lokale Abrufzeitpunkt |

## Automatisierte Prüfungen

Die GitHub-Actions-Pipeline führt PHP-Syntaxprüfung, JSON-Prüfung, Struktur-/Dokumentationsprüfung und isolierte Laufzeitprüfungen aus. Die Strukturprüfung vergleicht die dokumentierten Idents, Datentypen und Positionen mit den Definitionen und prüft die öffentliche PHP-Schnittstelle sowie deutsche Übersetzungen.

`runtime_invariants.php` lädt die echte Modulklasse gegen eine isolierte Symcon-Nachbildung. Geprüft werden Erstanlage, wiederholtes ApplyChanges, Erhalt benutzerdefinierter Metadaten, Deaktivieren optionaler Variablengruppen, Typ-/Objektkonflikte, Rohantwort-Cache, Statuszuordnung, Einheiten, Pending, Rücksetzen bei Fehlern, Temperaturvormerkung, OpenAPI-Cache und Wiederverwendung des Fahrzeugbilds. Der Test arbeitet ohne Zugangsdaten. cURL-Aufrufe werden in der Pipeline deaktiviert.

Lokal im Repository ausführen, nicht innerhalb einer produktiven Symcon-Instanz:

```sh
find MySkoda tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
python3 tests/validate_structure.py
php -d disable_functions=curl_init,curl_exec tests/runtime_invariants.php
```

Die Dokumentationsprüfung verwendet die `_new.md`-Fassung, solange sie vorhanden ist; ansonsten die gleichnamige README ohne Suffix. Die vorhandenen README-Dateien müssen dafür nicht automatisch ersetzt werden.

## Praktische Abnahme

Vor der produktiven Übernahme sind Neuinstallation und vorhandene Instanz in Symcon zu prüfen. Dazu gehören die Darstellung aller gewünschten Variablen, der Erhalt manueller Anpassungen nach Übernehmen und Neustart, Fahrzeugabfragen sowie bewusst ausgelöste Lade- und Klimabefehle. Außerdem sind Bild, Mitteilungen und gegebenenfalls Archivierung mit der tatsächlichen Umgebung zu prüfen.

Ein erfolgreicher automatisierter Test bestätigt weder die aktuelle Erreichbarkeit der Škoda-Dienste noch die Umsetzung eines Befehls im Fahrzeug. Bei neuen Fahrzeugnachweisen sind Modelljahr, geprüfter Zustand und beobachtete Antwort festzuhalten. Rohantworten müssen vor Veröffentlichung von FIN, Kennzeichen, Standort und anderen persönlichen Daten bereinigt werden.

Die technische Datenpunktreferenz befindet sich in [MySkoda/README_new.md](../MySkoda/README_new.md).
