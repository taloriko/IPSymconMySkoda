# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Škoda-Fahrzeugs an die offizielle **MyŠkoda Public API**. Eine Instanz repräsentiert genau eine FIN/VIN.

## Funktionsumfang

- Fahrzeugstatus und Fahrzeugdaten über die MyŠkoda Public API
- stabile Variablen-Idents für Skripte und weitere Module
- Laden und Klimatisierung über Variablenaktionen
- Ladelimit und Lademodus, sofern vom Fahrzeug unterstützt
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail-, Standort- und Diagnosevariablen
- automatische Erkennung zusätzlicher, noch nicht integrierter OpenAPI-Funktionen
- optionale Archivierung von Ladezustand, Ladelimit, Ladeleistung und Kilometerstand nach ausdrücklicher Aktivierung
- API-Key-Ablaufwarnung 30 Tage vor Ablauf
- optionale Symcon-Mitteilung über eine ausgewählte Visualisierungsinstanz

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- aktive MySkoda/Škoda-Connect-Dienste für die verwendeten Fahrzeugfunktionen
- optional S-PIN für Standheizung
- Archive Control für die optionale Archivierung

Offizielle API-Dokumentation: <https://public.api.connect.skoda-auto.cz/docs>

## Installation und erste Einrichtung

Nach der Installation eine Instanz **MySkoda** anlegen.

1. In der MySkoda App unter **Profil → Smart Home → Schlüssel erstellen** einen API-Key erzeugen.
2. FIN/VIN und API-Token in der Instanz eintragen.
3. Im sichtbaren Abschnitt **Archivierung** bei Bedarf **Fahrzeugdaten archivieren** aktivieren.
4. Konfiguration übernehmen.
5. Über **Verbindung testen** den Datenabruf prüfen.
6. Optional Detailvariablen und Mitteilungen aktivieren.

Das Standard-Abfrageintervall beträgt 300 Sekunden. Das Modul berücksichtigt die von der API gelieferten Rate-Limit-Informationen und `Retry-After`. Dadurch wird ein aktives API-Limit nicht durch weitere unnötige Anfragen belastet.

## Konfiguration

| Einstellung | Funktion | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| API-Token | MySkoda Public API-Key | leer |
| Abfrageintervall | automatischer Abruf in Sekunden | 300 |
| Remote-Steuerung | erlaubt Lade- und Klimabefehle | an |
| Klima ohne externe Stromversorgung | Übergabe an die MySkoda-Klimafunktion | an |
| S-PIN | optional für Standheizung | leer |
| Fahrzeugdaten archivieren | richtet das Logging nach ausdrücklicher Aktivierung einmalig ein | aus |
| API-Key-Ablaufwarnung | Mitteilung bei höchstens 30 Tagen Restlaufzeit | aus |
| Visualisierung für Mitteilungen | Ziel für Symcon-Mitteilungen | keine |
| Detail-/Diagnosevariablen | legt zusätzliche Datenpunkte an | aus |

## Objektstruktur

Das Modul legt **keine Dummy-Instanzen, Kategorien oder Links** an. Unter der MySkoda-Instanz befinden sich ausschließlich die echten Modulvariablen.

Die fachliche Gruppierung erfolgt in der Visualisierung. Dadurch bleibt das Datenmodul klein und andere Module oder Skripte können direkt über stabile Idents auf die Werte zugreifen.

Vorhandene Variablen werden bei späteren Modulaktualisierungen nicht erneut registriert. Vom Benutzer geänderte Namen, Positionen und Darstellungen werden daher nicht bei jedem `ApplyChanges()` überschrieben.

### Standard-Datenpunkte

| Ident | Deutsche Anzeige | Datentyp | Bedienbar |
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
| `NewApiFeatures` | Neue API-Funktionen | Integer | Nein |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein |

### Optionale Datenpunkte

Bei aktivierter Option **Detail- und Diagnosevariablen anlegen** werden fehlende zusätzliche Variablen erstellt:

| Ident | Deutsche Anzeige |
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

Einmal angelegte Detailvariablen bleiben bestehen. Das Deaktivieren der Option löscht keine Variablen.

## Neue API-Funktionen erkennen

Nach einer erfolgreichen Fahrzeugabfrage prüft das Modul zusätzlich die öffentliche OpenAPI-Definition der MyŠkoda Public API. Die Definition wird intern für 24 Stunden zwischengespeichert. Dadurch wird die Prüfung nicht bei jedem Fahrzeugabruf erneut aus dem Internet geladen und sie verbraucht kein fahrzeugbezogenes API-Kontingent.

Die Variable `NewApiFeatures` zeigt an, ob die API Operationen enthält, die Version 1.0 noch nicht als Modul-Funktion kennt:

| Wert | Bedeutung |
|---:|---|
| `0` | keine zusätzlichen API-Operationen erkannt |
| `> 0` | zusätzliche, noch nicht integrierte API-Operationen erkannt |

Das Modul erzeugt aus neu gefundenen API-Funktionen **keine automatischen Variablen** und keine zusätzlichen Objekte. Neue Datenpunkte werden erst mit einer neuen Modulversion definiert. Damit bleiben Ident, Datentyp, Übersetzung, Darstellung und Verhalten der Modulvariablen kontrolliert und versionsfest.

Beim frischen Laden der OpenAPI-Definition werden unbekannte Operationen zusätzlich unter **Debug → API discovery** ausgegeben. Der Button **API-Definition neu laden** stößt die Prüfung unabhängig vom 24-Stunden-Cache manuell an.

## Statusdarstellungen

Die API-Werte werden in den Variablen unverändert gespeichert. Nur die Darstellung wird lokalisiert. Dadurch können Skripte weiterhin mit den originalen API-Werten arbeiten.

### Parkstatus

| API-Wert | Deutsche Anzeige |
|---|---|
| `PARKED` | Geparkt |
| `MOVING` | In Bewegung |
| `DRIVING` | In Fahrt |
| `UNKNOWN` | Unbekannt |

Nicht bekannte zukünftige API-Werte bleiben als Rohwert erhalten.

### Ladestatus

| API-Wert | Deutsche Anzeige |
|---|---|
| `CONNECT_CABLE` | Ladekabel anschließen |
| `CHARGING` | Laden aktiv |
| `CONSERVING` | Ladeerhaltung |
| `READY_FOR_CHARGING` | Ladebereit |
| `DISCHARGING` | Entladen |
| `CHARGING_INTERRUPTED` | Laden unterbrochen |
| `OFF` | Aus |
| `UNKNOWN` | Unbekannt |

## Lademodus

`ChargeMode` verwendet feste Integerwerte:

| Wert | API-Modus | Deutsche Anzeige |
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

Die Archivierung ist standardmäßig **aus**. Der Abschnitt **Archivierung** ist bei der Einrichtung sichtbar. Erst wenn **Fahrzeugdaten archivieren** vom Benutzer bewusst aktiviert und die Konfiguration übernommen wird, konfiguriert das Modul einmalig folgende Variablen im Archive Control:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird mit Aggregationstyp **Zähler** eingerichtet. Werte `<= 0` werden bereits beim Einlesen verworfen und nicht in die Kilometerstandsvariable geschrieben. Zusätzlich wird für den Zähler das Ignorieren von Null- und negativen Werten im Archive Control aktiviert.

Nach erfolgreicher erstmaliger Einrichtung greift das Modul nicht erneut in die Archive-Control-Konfiguration ein. Spätere Benutzeranpassungen bleiben erhalten.

Es wird kein eigenes Diagramm und kein zusätzliches Medienobjekt angelegt. Die Darstellung der Archivdaten ist Aufgabe der Visualisierung.

## Standortdaten

Standortdaten werden nur bereitgestellt, wenn die MySkoda API sie für das Fahrzeug und den jeweiligen Benutzer liefert. Bei Fahrzeugen mit mehreren Benutzern sind Standortdaten nur sichtbar, wenn der jeweilige Benutzer die Standortfreigabe erteilt hat. Fehlen Koordinaten in einer Antwort, werden vorhandene optionale Standortvariablen auf `0.0` gesetzt.

## Öffentliche PHP-Befehle

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
| `104` | inaktiv |
| `201` | FIN oder API-Token fehlt oder ist ungültig |
| `202` | API- oder Verbindungsfehler |
| `203` | Rate-Limit / Wartezeit aktiv |

## Fehlersuche

- **Keine Verbindung:** FIN/VIN und API-Token prüfen und anschließend **Verbindung testen** ausführen.
- **Status 203:** Das API-Rate-Limit oder eine von der API vorgegebene Wartezeit ist aktiv. Das Modul wartet automatisch.
- **Einzelne Werte fehlen:** Nicht jedes Fahrzeug bzw. jeder MySkoda-Dienst liefert alle API-Funktionen. Optionale Werte werden nur dargestellt, wenn die API sie bereitstellt.
- **Neue API-Funktionen > 0:** Die öffentliche API enthält neue Operationen. Prüfen, ob eine neuere Modulversion verfügbar ist; Entwickler können die unbekannten Operationen im Debug unter `API discovery` sehen.
- **Remote-Befehl nicht verfügbar:** Fahrzeugfähigkeiten, MySkoda-Dienste und ggf. S-PIN prüfen.
- **Breiten-/Längengrad zeigen 0:** Ein Fahrzeug kann mehrere Benutzer haben. Standortdaten sind nur sichtbar, wenn der jeweilige Benutzer die Standortfreigabe erteilt hat.

Bei Fehlermeldungen niemals API-Key, S-PIN oder vollständige FIN öffentlich posten.

## Datenschutz und externe Dienste

Das Modul kommuniziert direkt mit der offiziellen MyŠkoda Public API. Dafür werden die in der Instanz hinterlegte FIN/VIN und der API-Token für die notwendigen API-Anfragen verwendet. Remote-Befehle werden nur bei Benutzeraktion oder über die dokumentierten Modulmethoden ausgelöst.

Zusätzlich lädt das Modul die öffentliche OpenAPI-Definition der MyŠkoda Public API, um neue API-Funktionen erkennen zu können. Für diesen Abruf wird kein Fahrzeug-API-Key übertragen.

## Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der [MIT-Lizenz](../LICENSE) veröffentlicht.

Dieses Projekt ist eine unabhängige Community-Integration und nicht mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
