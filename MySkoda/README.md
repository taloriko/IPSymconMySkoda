# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Fahrzeugs an die offizielle MySkoda Public API. Eine Instanz repräsentiert genau eine FIN/VIN.

## Funktionsumfang

- Fahrzeugstatus und Fahrzeugdaten über die MySkoda Public API
- stabile Variablen-Idents für Skripte und weitere Module
- Laden und Klimatisierung über Variablenaktionen
- Ladelimit und Lademodus, sofern vom Fahrzeug unterstützt
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail-, Standort- und Diagnosevariablen
- optionaler Ladeverlauf mit Archive Control
- vollständige API-Antwort über `MSKODA_GetRawData()`
- Rate-Limit- und `Retry-After`-Behandlung
- API-Key-Ablaufwarnung 30 Tage vor Ablauf
- optionale Symcon-Mitteilung über eine ausgewählte Visualisierungsinstanz

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- optional S-PIN für Standheizung
- Archive Control für den optionalen Ladeverlauf

### API-Key erstellen

In der **MySkoda App**:

**Profil → Smart Home → Create Key → Namen vergeben → FIN/VIN und API-Token in die Symcon-Instanz übernehmen.**

Nach dem Übernehmen prüft das Modul die Verbindung und zeigt das Ergebnis im Instanzformular.

## Installation

Im **Module Control** folgendes Repository hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend eine Instanz **MySkoda** anlegen.

## Konfiguration

| Einstellung | Funktion | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| API-Token | MySkoda Public API-Key | leer |
| Abrufintervall | automatischer Abruf in Sekunden | 300 |
| Remote-Steuerung | erlaubt Lade- und Klimabefehle | an |
| Klima ohne externe Stromversorgung | Übergabe an die MySkoda-Klimafunktion | an |
| S-PIN | optional für Standheizung | leer |
| API-Key-Ablaufwarnung | Mitteilung bei höchstens 30 Tagen Restlaufzeit | aus |
| Visualisierung für Mitteilungen | Ziel für Symcon-Mitteilungen | keine |
| Detail-/Diagnosevariablen | legt zusätzliche Datenpunkte an | aus |
| Ladeverlauf und Aufzeichnung | initialisiert Diagramm und Logging | an |

## Variablen und stabile Idents

Die Datenvariablen liegen direkt unter der MySkoda-Instanz. Der `Ident` ist die stabile technische Schnittstelle für Skripte und weitere Module.

Eine Variable wird nur registriert, solange ihr Ident noch nicht existiert. Sobald sie angelegt ist, registriert MySkoda deren Name, Position und Darstellung nicht erneut. Dadurch können diese Eigenschaften in IP-Symcon angepasst werden, ohne dass eine spätere Modulaktualisierung sie zurücksetzt.

Die Fahrzeugwerte selbst werden bei jedem erfolgreichen Abruf aktualisiert.

### Standard-Datenpunkte

| Ident | Datentyp | Bedeutung | Bedienbar |
|---|---|---|---:|
| `StateOfCharge` | Integer | Ladezustand in % | Nein |
| `Range` | Integer | Restreichweite in km | Nein |
| `Mileage` | Integer | Kilometerstand in km | Nein |
| `Locked` | Boolean | Fahrzeug verriegelt | Nein |
| `DoorsOpen` | Boolean | mindestens eine Tür offen | Nein |
| `WindowsOpen` | Boolean | mindestens ein Fenster offen | Nein |
| `Charging` | Boolean | Ladevorgang | Ja |
| `ChargePower` | Float | Ladeleistung in W | Nein |
| `TargetSOC` | Integer | Ladelimit in % | Ja |
| `ChargeMode` | Integer | Lademodus | Ja |
| `Climate` | Boolean | Klimatisierung | Ja |
| `TargetTemperature` | Float | Klima-Solltemperatur in °C | Ja |
| `ApiKeyWarning` | Boolean | API-Key läuft in höchstens 30 Tagen ab | Nein |
| `LastUpdate` | Integer | Zeitpunkt der letzten erfolgreichen Abfrage | Nein |

### Optionale Datenpunkte

Bei aktivierter Option **Detail- und Diagnosevariablen anlegen** werden fehlende zusätzliche Variablen erstellt:

`VehicleName`, `LicensePlate`, `TrunkOpen`, `BonnetOpen`, `SunroofOpen`, `LightsOn`, `ParkingState`, `ChargingState`, `ChargeType`, `FullyChargedAt`, `Latitude`, `Longitude`, `ApiKeyExpiresAtVar`, `RequestsRemaining`, `PartialErrors`.

Einmal angelegte Detailvariablen bleiben bestehen und werden weiter mit aktuellen Werten versorgt. Das Deaktivieren der Option löscht keine Variablen.

### Lademodus

`ChargeMode` verwendet feste Integerwerte, damit der Datenpunkt unabhängig von der Reihenfolge der API-Antwort stabil bleibt:

| Wert | Modus |
|---:|---|
| 0 | `MANUAL` |
| 1 | `TIMER` |
| 2 | `TIMER_CHARGING_WITH_CLIMATISATION` |
| 3 | `PREFERRED_CHARGING_TIMES` |
| 4 | `ONLY_OWN_CURRENT` |
| 5 | `IMMEDIATE_DISCHARGING` |
| 6 | `HOME_STORAGE_CHARGING` |

Vor dem Senden prüft das Modul die vom Fahrzeug gemeldeten verfügbaren Lademodi.

## Ladeverlauf und Archivierung

Wenn **Ladeverlauf und Aufzeichnung** aktiviert ist, initialisiert das Modul einmalig:

- Logging für `StateOfCharge`
- Logging für `TargetSOC`
- Logging für `ChargePower`
- das Diagramm `MSKODA_ChargingHistory`

Bereits aktives Logging wird nicht neu konfiguriert. Nach erfolgreicher Initialisierung verändert das Modul weder die Archiv-Einstellungen noch die Diagrammkonfiguration erneut.

Im Diagramm liegen Ladezustand und Ladelimit auf der linken Prozent-Achse; die Ladeleistung liegt auf der rechten Leistungs-Achse.

## Standortdaten

Standortdaten werden nur bereitgestellt, wenn die MySkoda API sie für das verwendete Profil liefert. Fehlen Koordinaten in einer Antwort, werden vorhandene optionale Standortvariablen auf `0.0` gesetzt.

## Mitteilungen

Für API-Key-Warnungen kann eine Symcon-Visualisierungsinstanz als Mitteilungsziel ausgewählt werden. Das Modul prüft das gewählte Ziel vor dem Versand.

## PHP-Befehle

```php
MSKODA_Update(12345);
$ok = MSKODA_TestConnection(12345);
$json = MSKODA_GetRawData(12345);
$json = MSKODA_GetChargingProfiles(12345);
$json = MSKODA_GetRemoteOperations(12345);
$ok = MSKODA_SetChargingLimit(12345, 80);
$ok = MSKODA_SetChargeMode(12345, 'MANUAL');
$ok = MSKODA_UpdateChargingProfile(12345, 1, $profileJson);
$ok = MSKODA_StartAuxiliaryHeating(12345, 22.0, 30, 'HEATING');
$ok = MSKODA_StopAuxiliaryHeating(12345);
$ok = MSKODA_StartVentilation(12345);
$ok = MSKODA_StopVentilation(12345);
$ok = MSKODA_RefreshApiDefinition(12345);
$ok = MSKODA_TestNotification(12345);
```

## Instanzstatus

| Code | Bedeutung |
|---:|---|
| `102` | verbunden / bereit |
| `201` | FIN oder API-Token fehlt oder ist ungültig |
| `202` | API- oder Verbindungsfehler |
| `203` | Rate-Limit / Wartezeit aktiv |

## Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der [MIT-Lizenz](../LICENSE) veröffentlicht.

Dieses Projekt ist eine unabhängige Community-Integration und nicht mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
