# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Škoda-Fahrzeugs an die offizielle **MyŠkoda Public API**. Eine Instanz repräsentiert genau eine FIN/VIN.

## Funktionsumfang

- Fahrzeugstatus und Fahrzeugdaten über die MyŠkoda Public API
- stabile Variablen-Idents für Skripte und weitere Module
- Laden und Klimatisierung über Variablenaktionen
- Ladelimit und Lademodus, sofern vom Fahrzeug unterstützt
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail-, Standort- und Diagnosevariablen
- sofortige lokale Anzeige schreibbarer Werte mit direkter Auswertung der Befehlsantwort
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

Das Standard-Abfrageintervall beträgt 300 Sekunden. Das Modul berücksichtigt die von der API gelieferten Rate-Limit-Informationen und `Retry-After`.

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

Vorhandene Variablen werden bei späteren Modulaktualisierungen grundsätzlich nicht neu angelegt. Das vom Modul gelieferte Ladelimit-Profil wird in Version 1.1 gezielt auf 10-%-Schritte aktualisiert; eine vom Benutzer selbst gesetzte Custom-Darstellung bleibt unberührt.

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
| `TargetSOC` | Ladelimit | Integer | Ja, 50 bis 100 % in 10-%-Schritten |
| `ChargeMode` | Lademodus | Integer | Ja |
| `Climate` | Klimatisierung | Boolean | Ja |
| `TargetTemperature` | Solltemperatur | Float | Ja |
| `ApiKeyWarning` | API-Key Warnung | Boolean | Nein |
| `NewApiFeatures` | Neue API-Funktionen | Integer | Nein |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein |

### Optionale Datenpunkte

Bei aktivierter Option **Detail- und Diagnosevariablen anlegen** werden fehlende zusätzliche Variablen erstellt. Dazu gehören Fahrzeugname, Kennzeichen, Statusdetails, Standortdaten, API-Diagnose sowie ab Version 1.1:

| Ident | Deutsche Anzeige |
|---|---|
| `PendingCommands` | Ausstehende Befehle |
| `CommandStatus` | Befehlsstatus |

Einmal angelegte Detailvariablen bleiben bestehen. Das Deaktivieren der Option löscht keine Variablen.

## Lademodi

Die Public API stellt einen Befehl zum Ändern des Lademodus bereit. Die aktuell öffentlich zugängliche Dokumentation benennt jedoch nicht eindeutig die fachliche Bedeutung jedes einzelnen möglichen Enum-Werts. Deshalb sind die folgenden Erläuterungen dort, wo keine eindeutige offizielle Beschreibung vorliegt, ausdrücklich als **Vermutung** markiert.

| API-Wert | Deutsche Anzeige | Status der Erklärung | Bedeutung |
|---|---|---|---|
| `MANUAL` | Manuell | 🟡 Vermutung | Direktes bzw. manuelles Laden ohne aktive Zeitsteuerung. |
| `TIMER` | Timer | 🟡 Vermutung | Laden nach einem im Fahrzeug oder Ladeprofil hinterlegten Zeitplan. |
| `TIMER_CHARGING_WITH_CLIMATISATION` | Timer + Klimatisierung | 🟡 Vermutung | Zeitgesteuertes Laden zusammen mit vorbereitender Klimatisierung. Die Public API 1.0.0 bietet keine separate Bearbeitung von Klima-Zeitplänen; vermutlich wird ein bereits im Fahrzeug bzw. in der App vorhandener Plan genutzt. |
| `PREFERRED_CHARGING_TIMES` | Bevorzugte Ladezeiten | 🟡 Vermutung | Laden innerhalb bevorzugter Zeitfenster eines Ladeprofils bzw. gespeicherten Ladeorts. |
| `ONLY_OWN_CURRENT` | Nur eigener Strom | 🟡 Vermutung | Vermutlich Laden nur mit eigener Energieerzeugung, z. B. PV-Überschuss, sofern das verwendete Fahrzeug und Energiesystem diese Betriebsart unterstützen. |
| `IMMEDIATE_DISCHARGING` | Sofort entladen | 🟡 Vermutung | Vermutlich sofortiges Entladen bei einem bidirektionalen bzw. Home-Energy-fähigen System. Für Fahrzeuge ohne solche Funktionen nicht relevant. |
| `HOME_STORAGE_CHARGING` | Heimspeicher laden | 🟡 Vermutung | Vermutlich ein Modus für die Kopplung mit einem Heimspeicher. Die genaue Energieflussrichtung und das konkrete Verhalten sind in der öffentlich zugänglichen Dokumentation nicht eindeutig beschrieben. |

**Gesichert** ist: Die API bietet die Operation zum Ändern des Lademodus, und Fahrzeugdaten können verfügbare Lademodi melden. **Nicht gesichert** sind die obigen Detailbeschreibungen der einzelnen Enum-Werte, solange Škoda diese nicht öffentlich genauer dokumentiert.

Nicht jeder in IP-Symcon sichtbare Enum-Wert muss vom konkreten Fahrzeug unterstützt werden. Das Modul liest, sofern vorhanden, `charging.settings.availableChargeModes` und prüft den gewünschten Modus vor dem Senden.

Zur Kontrolle der vom eigenen Fahrzeug gemeldeten Modi:

```php
$raw = json_decode(MSKODA_GetRawData(12345), true);
echo json_encode(
    $raw['vehicle']['charging']['settings']['availableChargeModes'] ?? null,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
```

## Befehlslogik für Remote-Befehle

Version 1.1 verwendet für folgende schreibbare Werte eine sofortige lokale Anzeige:

- `Charging`
- `TargetSOC`
- `ChargeMode`
- `Climate`
- `TargetTemperature`, wenn die Klimatisierung aktiv ist

Zusätzlich verwenden `MSKODA_SetChargingLimit()` und `MSKODA_SetChargeMode()` dieselbe Logik.

### Ablauf

1. Vor dem Senden wird der bisherige lokale Wert gespeichert.
2. Der gewünschte neue Wert wird sofort lokal gesetzt und während der laufenden HTTP-Anfrage kurz als Pending markiert.
3. Der Befehl wird an die MyŠkoda Public API gesendet.
4. Kommt eine erfolgreiche **2xx-Antwort**, gilt der Befehl serverseitig als angenommen. Der gewünschte Wert bleibt gesetzt und Pending wird sofort beendet.
5. Kommt eine Fehlerantwort oder schlägt die Übertragung fehl, wird der vorherige lokale Wert sofort wiederhergestellt und Pending ebenfalls beendet.
6. Eine zusätzliche Bestätigungsabfrage des Fahrzeugzustands wird nicht mehr ausgelöst.

Der normale zyklische Fahrzeugabruf bleibt davon unabhängig. Liefert das Portal bei einem späteren regulären Abruf einen anderen Fahrzeugzustand, wird dieser wie gewohnt übernommen.

### Diagnose

`PendingCommands` zeigt die Anzahl gerade laufender Befehlsanfragen. Da die API-Anfrage synchron ausgeführt wird, ist der Wert normalerweise nur sehr kurz ungleich `0`.

`CommandStatus` zeigt das Ergebnis des letzten Befehls. Beispiele:

```text
Bestätigt: Ladelimit
Bestätigt: Klimatisierung
Befehl abgelehnt: Ladelimit - HTTP 400: ...
Befehl abgelehnt: Klimatisierung - cURL: ...
```

Bei einem Fehler wird damit nicht nur der vorherige Wert wiederhergestellt, sondern im Befehlsstatus zusätzlich der von der API bzw. vom Transport gelieferte **Fehlertext** angezeigt.

## Geprüfte Funktionen der MySkoda-App und Grenzen der Public API

Stand **14.09.2026** verwendet das Modul ausschließlich die offiziell veröffentlichte **MyŠkoda Public API**. Die MySkoda-App und interne Škoda-Schnittstellen können zusätzliche Funktionen besitzen, die im aktuell veröffentlichten Public-API-Vertrag **1.0.0** nicht freigegeben sind.

Folgende Funktionen wurden für Version 1.1 ausdrücklich geprüft:

| Funktion aus der MySkoda-App | Offizielle Public API 1.0.0 | Status im Modul |
|---|---|---|
| Camping Mode | Nein | Nicht integrierbar über die offizielle Public API |
| Klimatisierungspläne / Klima-Timer / Abfahrtszeiten | Nein | Nicht integrierbar über die offizielle Public API |
| Intelligentes Heizen / Smart Heating | Nein | Nicht integrierbar über die offizielle Public API |
| Scheibenheizung im Zusammenhang mit Klimatisierung | Nein | Nicht integrierbar über die offizielle Public API |
| Sitzheizung Fahrer im Zusammenhang mit Klimatisierung | Nein | Nicht integrierbar über die offizielle Public API |
| Sitzheizung Beifahrer im Zusammenhang mit Klimatisierung | Nein | Nicht integrierbar über die offizielle Public API |
| Intelligentes Klimatisieren / Smart-Climate-Einstellungen | Nein | Nicht integrierbar über die offizielle Public API |
| Grundlegendes Klimatisieren Start/Stop | **Ja** | Unterstützt |
| Solltemperatur der Klimatisierung | **Ja** | Unterstützt |
| Battery Care Mode | Nein | Nicht integrierbar über die offizielle Public API |
| Begrenzung / Reduzierung des AC-Ladestroms | Nein | Nicht integrierbar über die offizielle Public API |
| AC-Ladekabel nach Ladeende automatisch entriegeln | Nein | Nicht integrierbar über die offizielle Public API |
| Ladeprofile / gespeicherte Ladeorte | **Ja** | API-Funktion vorhanden; nicht mit Klima-Timern verwechseln |

Die offizielle Public API bietet derzeit unter anderem Fahrzeugdaten, Laden Start/Stop, Ladelimit, Lademodus, Ladeprofile sowie Klimatisierung, Standheizung und aktive Lüftung. Ob eine konkrete Remote-Funktion für ein Fahrzeug freigegeben ist, kann über `MSKODA_GetRemoteOperations()` geprüft werden. Das Modul liest dafür primär `vehicle.operations` und hält aus Kompatibilitätsgründen einen Fallback auf `vehicle.remoteOperations` vor.

App-Funktionen außerhalb dieses öffentlichen Vertrags werden **nicht über inoffizielle oder private Endpunkte nachgebaut**, damit das Modul stabil, nachvollziehbar und Store-tauglich bleibt.

Wenn Škoda eine der oben genannten Funktionen später in die offizielle Public API aufnimmt, kann `NewApiFeatures` auf neue Operationen hinweisen. Die Funktion wird anschließend bewusst in einer neuen Modulversion ergänzt.

## Neue API-Funktionen erkennen

Nach einer erfolgreichen Fahrzeugabfrage prüft das Modul zusätzlich die öffentliche OpenAPI-Definition der MyŠkoda Public API. Die Definition wird intern für 24 Stunden zwischengespeichert und verbraucht kein fahrzeugbezogenes API-Kontingent.

Die Variable `NewApiFeatures` zeigt an, ob die API zusätzliche Operationen enthält, die die aktuelle Modulversion noch nicht kennt. Das Modul erzeugt aus neu gefundenen API-Funktionen **keine automatischen Variablen**. Neue Datenpunkte werden erst mit einer neuen Modulversion definiert.

## Statusdarstellungen

Die API-Werte bleiben technisch unverändert; nur die Darstellung wird lokalisiert. Bekannte Beispiele sind `PARKED` → **Geparkt**, `CHARGING` → **Laden aktiv** und `READY_FOR_CHARGING` → **Ladebereit**.

## Archivierung

Die Archivierung ist standardmäßig **aus**. Erst nach ausdrücklicher Aktivierung werden einmalig folgende Variablen im Archive Control eingerichtet:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird als **Zähler** eingerichtet. Werte `<= 0` werden nicht übernommen. Spätere Benutzeranpassungen im Archive Control bleiben erhalten.

## Standortdaten

Standortdaten werden nur bereitgestellt, wenn die MySkoda API sie für das Fahrzeug und den jeweiligen Benutzer liefert. Bei Fahrzeugen mit mehreren Benutzern muss der jeweilige Benutzer die Standortfreigabe erteilt haben.

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
- **Status 203:** Das API-Rate-Limit oder eine von der API vorgegebene Wartezeit ist aktiv.
- **Befehl springt sofort zurück:** Der Befehlsaufruf wurde nicht erfolgreich bestätigt; den genauen Grund in `CommandStatus` bzw. `LastError` prüfen.
- **Befehl war erfolgreich, später zeigt die Variable einen anderen Wert:** Ein späterer regulärer Fahrzeugabruf hat einen anderen Portalzustand geliefert.
- **Neue API-Funktionen > 0:** Prüfen, ob eine neuere Modulversion verfügbar ist.
- **Funktion ist in der MySkoda-App vorhanden, aber nicht im Modul:** Abschnitt **Geprüfte Funktionen der MySkoda-App und Grenzen der Public API** prüfen. Das Modul verwendet ausschließlich die offizielle Public API.

Bei Fehlermeldungen niemals API-Key, S-PIN oder vollständige FIN öffentlich posten.

## Datenschutz und externe Dienste

Das Modul kommuniziert direkt mit der offiziellen MyŠkoda Public API. Dafür werden die in der Instanz hinterlegte FIN/VIN und der API-Token für notwendige API-Anfragen verwendet. Remote-Befehle werden nur bei Benutzeraktion oder über die dokumentierten Modulmethoden ausgelöst.

Zusätzlich lädt das Modul die öffentliche OpenAPI-Definition. Für diesen Abruf wird kein Fahrzeug-API-Key übertragen.

## Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der [MIT-Lizenz](../LICENSE) veröffentlicht.

Dieses Projekt ist eine unabhängige Community-Integration und nicht mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
