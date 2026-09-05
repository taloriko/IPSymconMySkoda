# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Fahrzeugs an die offizielle MySkoda Public API. Eine Instanz repräsentiert genau eine FIN/VIN.

## 1. Funktionsumfang

- Fahrzeugstatus über die offizielle MySkoda Public API
- kompakter Standard-Datenpunktbestand
- optionale Detail- und Diagnosevariablen
- Laden und Klimatisierung über Variablenaktionen
- Ladelimit und Lademodus, sofern Fahrzeug und API dies unterstützen
- PHP-Befehle für Standheizung, aktive Lüftung und Ladeprofil-Updates
- vollständige API-Antwort über `MSKODA_GetRawData()`
- Rate-Limit- und `Retry-After`-Behandlung
- Verbindungstest nach der Ersteinrichtung
- API-Key-Ablaufwarnung 30 Tage vor Ablauf
- optionale Push-Mitteilung über eine ausgewählte Symcon-Visualisierungsinstanz

## 2. Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- optional S-PIN für Standheizung
- für die gewünschte Funktion aktive MySkoda/Škoda-Connect-Dienste

### API-Key erstellen

In der **MySkoda App**:

**Profil → Smart Home → Create Key → beliebigen Namen vergeben → FIN/VIN und API-Token in die Symcon-Instanz übernehmen.**

Nach dem Übernehmen prüft das Modul die Verbindung und zeigt das Ergebnis im Instanzformular.

## 3. Installation

Im **Module Control** folgendes Repository hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend eine Instanz **MySkoda** anlegen.

## 4. Konfiguration

| Einstellung | Beschreibung | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| API-Token | MySkoda Public API-Key | leer |
| Abrufintervall | automatischer Abruf in Sekunden | 300 |
| Remote-Steuerung | aktiviert Lade- und Klimafunktionen | an |
| Klima ohne externe Stromversorgung | Übergabe an die MySkoda-Klimafunktion | an |
| S-PIN | optional, für Standheizung | leer |
| API-Key Ablaufwarnung | Push bei höchstens 30 Tagen Restlaufzeit | aus |
| Visualisierung für Mitteilungen | ausgewählte Symcon-Visualisierungsinstanz | keine |
| Detail-/Diagnosevariablen | zusätzliche Fahrzeug- und API-Daten | aus |

### Standortfreigabe

Standortdaten werden von der MySkoda API nur geliefert, wenn im verwendeten **MySkoda-Profil die Standortfreigabe** erteilt wurde. Die Freigabe ist profilbezogen. Wird dasselbe Fahrzeug mit mehreren MySkoda-Profilen verwendet, muss sie in **jedem Profil separat** aktiviert werden.

Fehlen Standortdaten, schreibt das Modul `0.0` für Breitengrad und `0.0` für Längengrad. Dadurch bleiben keine veralteten Koordinaten aus einem früheren Abruf stehen.

## 5. Standard-Datenpunkte

| Ident | Bedeutung | Bedienbar |
|---|---|---:|
| `StateOfCharge` | Ladezustand | Nein |
| `Range` | Restreichweite | Nein |
| `Mileage` | Kilometerstand | Nein |
| `Locked` | Fahrzeug verriegelt | Nein |
| `DoorsOpen` | Türen offen | Nein |
| `WindowsOpen` | Fenster offen | Nein |
| `Charging` | Laden | Ja |
| `ChargePower` | Ladeleistung | Nein |
| `TargetSOC` | Ladelimit | Ja |
| `ChargeMode` | Lademodus | Ja |
| `Climate` | Klimatisierung | Ja |
| `TargetTemperature` | Klima-Solltemperatur | Ja |
| `ApiKeyWarning` | API-Key läuft in höchstens 30 Tagen ab | Nein |
| `LastUpdate` | Zeitpunkt der letzten erfolgreichen Abfrage | Nein |

Bei aktivierten Detailvariablen werden zusätzlich unter anderem Fahrzeugname, Kennzeichen, Ladestatus, Ladeart, erwarteter Voll-Ladezeitpunkt, Kofferraum, Motorhaube, Schiebedach, Licht, Parkstatus, Standort, API-Key-Ablauf und API-Diagnose bereitgestellt.

### Darstellungen

Das Modul legt keine Legacy-Variablenprofile und keine globalen eigenen Darstellungs-Vorlagen an. Die Darstellungen werden direkt über native Symcon-8.x-Darstellungen an den moduleigenen Variablen registriert.

Für Hinweiszustände gilt: erwarteter Zustand grün, Hinweis/Aufmerksamkeit orange. Rot wird für normale Statushinweise nicht verwendet.

## 6. Mitteilungen

Das Mitteilungsziel wird über einen Symcon-Instanzauswahldialog gewählt. Vor dem Versand prüft das Modul, ob die gewählte Instanz ein Visualisierungsmodul ist.

## 7. PHP-Befehle

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

## 8. Rate-Limit und Fehler

Das Modul wertet die von MySkoda gelieferten Rate-Limit-Header und `Retry-After` aus. Für automatische Abfragen wird eine Reserve für Bedienaktionen freigehalten.

Instanzstatus:

- `102` verbunden / bereit
- `201` FIN oder API-Token ungültig
- `202` API- oder Verbindungsfehler
- `203` Rate-Limit / Wartezeit

## 9. Lizenz und Markenhinweis

MIT-Lizenz. Dieses Projekt ist eine unabhängige Community-Integration und nicht mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
