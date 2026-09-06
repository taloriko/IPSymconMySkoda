# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Fahrzeugs an die offizielle MySkoda Public API. Eine Instanz repräsentiert genau eine FIN/VIN.

## 1. Funktionsumfang

- Fahrzeugstatus über die offizielle MySkoda Public API
- thematisch gruppierte Datenpunkte unter Dummy-Instanzen
- stabile Variablen-Idents für Skripte und externe Visualisierungen
- optionale Detail- und Diagnosevariablen
- Laden und Klimatisierung über Variablenaktionen
- Ladelimit und Lademodus, sofern Fahrzeug und API dies unterstützen
- kombinierter Ladeverlauf aus Ladezustand, Ladelimit und Ladeleistung
- automatische Aktivierung der benötigten Archivierung, sofern noch kein Logging besteht
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
- Archive Control für den optionalen Ladeverlauf

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
| Lade-Diagramm und Aufzeichnung | stellt den kombinierten Ladeverlauf bereit | an |

### Ladeverlauf und Archivierung

Für den Ladeverlauf werden genau diese drei Variablen verwendet:

- `StateOfCharge` – Ladezustand, linke Y-Achse in %
- `TargetSOC` – Ladelimit, linke Y-Achse in %
- `ChargePower` – Ladeleistung, rechte Y-Achse

Beim Anwenden der Instanz prüft das Modul für jede dieser Variablen den Logging-Status im Archive Control. **Nur wenn eine Variable noch nicht geloggt wird, wird das Logging aktiviert.**

Bestehende Archiveinstellungen werden nicht verändert. Insbesondere ändert das Modul keine Aggregationsart, Kompaktierung oder sonstige benutzerspezifische Archiveinstellungen. Vorhandene Archivdaten werden weder verändert noch gelöscht. Ein bereits vorhandenes Lade-Diagramm wird ebenfalls nicht überschrieben.

Wird die Option später deaktiviert, löscht das Modul weder Archivdaten noch eine bereits angelegte Diagrammkonfiguration.

### Standortfreigabe

Standortdaten werden von der MySkoda API nur geliefert, wenn im verwendeten **MySkoda-Profil die Standortfreigabe** erteilt wurde. Die Freigabe ist profilbezogen. Wird dasselbe Fahrzeug mit mehreren MySkoda-Profilen verwendet, muss sie in **jedem Profil separat** aktiviert werden.

Fehlen Standortdaten, schreibt das Modul `0.0` für Breitengrad und `0.0` für Längengrad. Dadurch bleiben keine veralteten Koordinaten aus einem früheren Abruf stehen.

## 5. Objektstruktur und Datenpunkte

Die Statusvariablen werden thematisch unter Dummy-Instanzen einsortiert:

```text
MySkoda
├─ Fahrzeug
├─ Fahrzeugstatus
├─ Laden
│  └─ Diagramme
│     └─ Ladeverlauf
├─ Klimatisierung
├─ Standort
├─ API & Diagnose
└─ Letzte Aktualisierung
```

Die Gruppierung ändert weder die Objekt-ID noch den Ident einer bestehenden Statusvariable. Externe Skripte und Visualisierungen können daher weiterhin mit den stabilen Idents arbeiten. Die herstellerunabhängige EV-Tile-Visualisierung kann die Variablen rekursiv unterhalb der MySkoda-Instanz erkennen.

### Standard-Datenpunkte

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

Der API-Rohwert von `ChargingState` bleibt als Variablenwert erhalten. Bekannte Zustände werden über die native Wertanzeige lesbar lokalisiert, beispielsweise `CONNECT_CABLE` als **Ladekabel anschließen** und `CHARGING` als **Lädt**.

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

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der **MIT-Lizenz** veröffentlicht. Der vollständige Lizenztext befindet sich in [../LICENSE](../LICENSE).

Dieses Projekt ist eine unabhängige Community-Integration und nicht mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.
