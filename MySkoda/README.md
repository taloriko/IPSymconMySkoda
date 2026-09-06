# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Fahrzeugs an die offizielle MySkoda Public API. Eine Instanz repräsentiert genau eine FIN/VIN.

## Funktionsumfang

- Fahrzeugstatus und Fahrzeugdaten über die MySkoda Public API
- stabile Variablen-Idents für Skripte und weitere Module
- Laden und Klimatisierung über Variablenaktionen
- Ladelimit und Lademodus, sofern vom Fahrzeug unterstützt
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail-, Standort- und Diagnosevariablen
- Archivierung von Ladezustand, Ladelimit, Ladeleistung und Kilometerstand
- API-Key-Ablaufwarnung 30 Tage vor Ablauf
- optionale Symcon-Mitteilung über eine ausgewählte Visualisierungsinstanz

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- optional S-PIN für Standheizung
- Archive Control für die optionale Archivierung

## Konfiguration

| Einstellung | Funktion | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| API-Token | MySkoda Public API-Key | leer |
| Abfrageintervall | automatischer Abruf in Sekunden | 300 |
| Remote-Steuerung | erlaubt Lade- und Klimabefehle | an |
| Klima ohne externe Stromversorgung | Übergabe an die MySkoda-Klimafunktion | an |
| S-PIN | optional für Standheizung | leer |
| API-Key-Ablaufwarnung | Mitteilung bei höchstens 30 Tagen Restlaufzeit | aus |
| Visualisierung für Mitteilungen | Ziel für Symcon-Mitteilungen | keine |
| Detail-/Diagnosevariablen | legt zusätzliche Datenpunkte an | aus |
| Archivierung | archiviert Lade- und Kilometerdaten | an |

## Objektstruktur

Das Modul legt **keine Dummy-Instanzen, Kategorien oder Links** an. Unter der MySkoda-Instanz befinden sich ausschließlich die echten Modulvariablen.

Die fachliche Gruppierung erfolgt in einem separaten Visualisierungsmodul. Dadurch bleibt das Datenmodul klein und die Visualisierung kann die Darstellung unabhängig aufbauen.

Die sichtbaren Namen der Variablen sind deutsch. Die technischen Idents bleiben stabil, damit Skripte und Visualisierungsmodule zuverlässig darauf zugreifen können.

### Standard-Datenpunkte

| Ident | Sichtbarer Name | Datentyp | Bedienbar |
|---|---|---|---:|
| `StateOfCharge` | Ladezustand | Integer | Nein |
| `Range` | Reichweite | Integer | Nein |
| `Mileage` | Kilometerstand | Integer | Nein |
| `Locked` | Verriegelt | Boolean | Nein |
| `DoorsOpen` | Türen offen | Boolean | Nein |
| `WindowsOpen` | Fenster offen | Boolean | Nein |
| `Charging` | Laden | Boolean | Ja |
| `ChargePower` | Ladeleistung | Float | Nein |
| `TargetSOC` | Ladelimit | Integer | Ja |
| `ChargeMode` | Lademodus | Integer | Ja |
| `Climate` | Klimatisierung | Boolean | Ja |
| `TargetTemperature` | Solltemperatur | Float | Ja |
| `ApiKeyWarning` | API-Key Warnung | Boolean | Nein |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein |

### Optionale Datenpunkte

Bei aktivierter Option **Detail- und Diagnosevariablen anlegen** werden fehlende zusätzliche Variablen erstellt:

| Ident | Sichtbarer Name |
|---|---|
| `VehicleName` | Fahrzeugname |
| `LicensePlate` | Kennzeichen |
| `TrunkOpen` | Kofferraum offen |
| `BonnetOpen` | Motorhaube offen |
| `SunroofOpen` | Schiebedach offen |
| `LightsOn` | Licht an |
| `ParkingState` | Parkstatus |
| `ChargingState` | Ladestatus |
| `ChargeType` | Ladeart |
| `FullyChargedAt` | Vollgeladen um |
| `Latitude` | Breitengrad |
| `Longitude` | Längengrad |
| `ApiKeyExpiresAtVar` | API-Key gültig bis |
| `RequestsRemaining` | Verbleibende API-Anfragen |
| `PartialErrors` | API-Teilfehler |

Einmal angelegte Detailvariablen bleiben bestehen und werden weiter mit aktuellen Werten versorgt. Das Deaktivieren der Option löscht keine Variablen.

## Lademodus

`ChargeMode` verwendet feste Integerwerte:

| Wert | Modus | Anzeige |
|---:|---|---|
| 0 | `MANUAL` | Manuell |
| 1 | `TIMER` | Timer |
| 2 | `TIMER_CHARGING_WITH_CLIMATISATION` | Timer + Klimatisierung |
| 3 | `PREFERRED_CHARGING_TIMES` | Bevorzugte Ladezeiten |
| 4 | `ONLY_OWN_CURRENT` | Nur eigener Strom |
| 5 | `IMMEDIATE_DISCHARGING` | Sofort entladen |
| 6 | `HOME_STORAGE_CHARGING` | Heimspeicher laden |

Vor dem Senden prüft das Modul die vom Fahrzeug gemeldeten verfügbaren Lademodi.

## Archivierung

Bei aktivierter Archivierung werden folgende Variablen im Archive Control geloggt:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird mit Aggregationstyp **Zähler** eingerichtet. Werte `<= 0` werden bereits beim Einlesen verworfen und deshalb nicht in die Kilometerstandsvariable geschrieben. Zusätzlich wird für den Zähler das Ignorieren von Null- und negativen Werten im Archive Control aktiviert.

Es wird kein eigenes Diagramm und kein zusätzliches Medienobjekt angelegt. Die Darstellung der Archivdaten ist Aufgabe der Visualisierung.

## Standortdaten

Standortdaten werden nur bereitgestellt, wenn die MySkoda API sie für das verwendete Profil liefert. Fehlen Koordinaten in einer Antwort, werden vorhandene optionale Standortvariablen auf `0.0` gesetzt.

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
