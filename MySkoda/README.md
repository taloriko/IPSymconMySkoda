# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Fahrzeugs an die offizielle MySkoda Public API. Eine Instanz repräsentiert genau eine FIN.

## Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanz in IP-Symcon](#4-einrichten-der-instanz-in-ip-symcon)
5. [Statusvariablen und Darstellungen](#5-statusvariablen-und-darstellungen)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Rate-Limit und Diagnose](#8-rate-limit-und-diagnose)
9. [Bekannte Einschränkungen](#9-bekannte-einschränkungen)
10. [Versionshistorie](#10-versionshistorie)
11. [Lizenz](#11-lizenz)

## 1. Funktionsumfang

- Liest den vollständigen Fahrzeugstatus über die offizielle MySkoda Public API.
- Stellt standardmäßig nur eine kleine, alltagstaugliche Auswahl an Statusvariablen bereit.
- Enthält eine generische Elektroauto-Kachel für die Symcon-Visualisierung.
- Aktualisiert die Visualisierungskachel und das Alter der letzten Abfrage minütlich, auch zwischen zwei API-Abfragen.
- Kann optional zusätzliche Detail- und Diagnosevariablen anlegen.
- Steuert Laden und Klimatisierung direkt über Variablenaktionen.
- Unterstützt Ladelimit und Lademodus, sofern Fahrzeug und API die Funktion anbieten.
- Stellt PHP-Befehle für Standheizung, aktive Lüftung und Ladeprofil-Updates bereit.
- Speichert die vollständige API-Antwort intern und stellt sie über `MSKODA_GetRawData()` zur Verfügung.
- Beachtet die Rate-Limit-Header und `Retry-After` der API.
- Prüft nach der Ersteinrichtung automatisch die Verbindung und zeigt das Ergebnis im Instanzformular an.
- Warnt 30 Tage vor Ablauf des API-Keys und kann optional eine Symcon-Mitteilung versenden.

## 2. Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- MySkoda API-Key
- 17-stellige FIN/VIN
- Optional: S-PIN für die Standheizung
- Für die jeweiligen Daten/Funktionen aktive Škoda-Connect-Dienste am Fahrzeug

### API-Key erstellen

Der API-Key kommt direkt aus der **MySkoda App**:

1. **Profil**
2. **Smart Home**
3. **Create Key**
4. Beliebigen Namen eingeben
5. FIN/VIN und API-Token aus der App in das Modul eintragen

Projekt und Dokumentation: <https://github.com/taloriko/IPSymconMySkoda>

## 3. Software-Installation

Über **Module Control** folgende Repository-URL hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend kann unter **Instanz hinzufügen** das Modul **MySkoda** gefunden und angelegt werden.

## 4. Einrichten der Instanz in IP-Symcon

Die Konfigurationsseite enthält folgende Einstellungen:

| Einstellung | Beschreibung | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| API-Token | MySkoda Public API-Key | leer |
| Abrufintervall | Automatischer Abruf in Sekunden | 300 |
| Remote-Steuerung aktivieren | Aktiviert Aktionen und Remote-Befehle | aktiv |
| Klimatisierung ohne externe Stromversorgung | Wird beim Start der Klimatisierung an die API übergeben | aktiv |
| S-PIN | Optional; zum Starten der Standheizung erforderlich | leer |
| Detail- und Diagnosevariablen | Blendet zusätzliche Fahrzeug- und API-Daten ein | aus |
| API-Key Ablaufwarnung als Mitteilung | Sendet bei höchstens 30 Tagen Restlaufzeit einmalig eine Push-Mitteilung | aus |
| Visualisierungs-ID | ID einer Kachel-Visualisierung oder eines WebFronts für die Mitteilung | 0 |

Die Instanz kann auch ohne FIN oder Token fehlerfrei angelegt werden. Solange Pflichtangaben fehlen, wird der Timer deaktiviert und der Instanzstatus `201` gesetzt.

### Standortfreigabe in MySkoda

Standortdaten werden von der MySkoda API nur geliefert, wenn im verwendeten **MySkoda-Profil die Standortfreigabe erteilt** wurde. Diese Freigabe gilt profilbezogen: Wird dasselbe Fahrzeug mit mehreren MySkoda-Profilen verwendet, muss die Standortfreigabe **in jedem Profil separat** aktiviert werden.

Ist für das Profil keine Standortfreigabe vorhanden oder liefert die API keine Position, schreibt das Modul bewusst **`0.0` für Breitengrad und `0.0` für Längengrad**. Dadurch bleiben keine veralteten Koordinaten aus einem früheren Abruf stehen.

## 5. Statusvariablen und Darstellungen

Der Standard-Objektbaum ist absichtlich kompakt.

| Ident | Variablenname | Typ | Bedienbar | Darstellung |
|---|---|---|---|---|
| `StateOfCharge` | Ladezustand | Integer | Nein | Symcon-Standardvorlage Batterie |
| `Range` | Reichweite | Integer | Nein | Wertanzeige, km |
| `Mileage` | Kilometerstand | Integer | Nein | Wertanzeige, km |
| `Locked` | Verriegelt | Boolean | Nein | **Wertanzeige** `MySkoda.Status.GoodTrue`: Ja grün, Nein orange |
| `DoorsOpen` | Türen offen | Boolean | Nein | **Wertanzeige** `MySkoda.Status.GoodFalse`: Nein grün, Ja orange |
| `WindowsOpen` | Fenster offen | Boolean | Nein | **Wertanzeige** `MySkoda.Status.GoodFalse`: Nein grün, Ja orange |
| `Charging` | Laden | Boolean | Ja | Schalter bei Remote-Steuerung; sonst passive Wertanzeige |
| `ChargePower` | Ladeleistung | Float | Nein | Symcon-Standardvorlage Leistung |
| `TargetSOC` | Ladelimit | Integer | Ja | Schieberegler 50-100 % |
| `ChargeMode` | Lademodus | Integer | Ja | Aufzählung aus den Fahrzeugmodi |
| `Climate` | Klimatisierung | Boolean | Ja | Schalter bei Remote-Steuerung; sonst passive Wertanzeige |
| `TargetTemperature` | Klima Solltemperatur | Float | Ja | Temperatur-Schieberegler |
| `VehicleTile` | Fahrzeugübersicht | String | Nein | neue Symcon-Darstellung **Webinhalt** |
| `ApiKeyWarning` | API-Key Warnung | Boolean | Nein | **Wertanzeige**: Nein grün, Ja orange; wird 30 Tage vor Ablauf aktiv |
| `LastUpdateAge` | Alter letzte Abfrage | String | Nein | Format `hh:mm` |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein | Symcon-Standardvorlage Datum/Uhrzeit |

Bei aktivierter Option **Detail- und Diagnosevariablen anzeigen** werden zusätzlich unter anderem Fahrzeugname, Kennzeichen, Ladestatus/-art, erwartete Voll-Ladezeit, Kofferraum, Motorhaube, Licht, Parkstatus, Koordinaten, API-Key-Ablaufdatum, verbleibende API-Abfragen und Teilfehler bereitgestellt.

### Neue Darstellungs-Vorlagen statt Legacy-Profile

MySkoda legt **keine Legacy-Variablenprofile** an. Seit Version 1.5 werden für die neuen Symcon-Darstellungen eigene Vorlagen verwendet. Sie sind eindeutig dem Modul zugeordnet:

- `MySkoda.Status.GoodTrue` – `Ja` ist der erwartete Zustand: Ja grün, Nein orange
- `MySkoda.Status.GoodFalse` – `Nein` ist der erwartete Zustand: Nein grün, Ja orange
- `MySkoda.Status.Normal` – beide Boolean-Zustände sind normale Betriebszustände und werden grün dargestellt
- `MySkoda.Control.Switch` – Schalterdarstellung für bedienbare Boolean-Werte

Die Status-Vorlagen verwenden die passive Symcon-Darstellung **Wertanzeige**. Damit funktionieren sie korrekt ohne Variablenaktion. Bedienbare Werte wie Laden und Klima verwenden bei aktivierter Remote-Steuerung dagegen die Darstellung **Schalter**, da diese eine Variablenaktion besitzt.

Die Farblogik bewertet den **Zustand**, nicht den reinen Boolean-Wert:

| Zustand | Grün | Orange |
|---|---|---|
| Verriegelt | **Ja** | Nein |
| Türen offen | **Nein** | Ja |
| Fenster offen | **Nein** | Ja |
| Kofferraum offen | **Nein** | Ja |
| Motorhaube offen | **Nein** | Ja |
| Licht an | **Nein** | Ja |
| API-Key Warnung | **Nein** | Ja |

Damit erscheint ein korrekt abgestelltes und verriegeltes Fahrzeug bei allen sicherheitsrelevanten Zuständen **grün**. **Rot wird für diese Hinweise nicht verwendet**; Rot bleibt echten Fehlern und Störungen vorbehalten.

Beim Update von Version 1.3 entfernt das Modul die damals erzeugten `MySkoda.YesNo.*`-Legacy-Profile von seinen Variablen und löscht sie, sofern sie nicht anderweitig verwendet werden.

## 6. Visualisierung

Zusätzlich zu den Statusvariablen stellt das Modul die Variable **`VehicleTile`** mit der neuen Symcon-Darstellung **Webinhalt** bereit. Die Kachel ist für Smartphones optimiert und bündelt die Informationen bewusst ohne viele einzelne Rahmen.

Version 1.5 ordnet die Anzeige neu:

- Kopfzeile: Fahrzeugname, alternativ Kennzeichen, Reichweite und Alter der letzten Abfrage
- realistischere, aber modellneutrale Elektroauto-Ansicht von oben
- SOC und Ladelimit direkt im Fahrzeug
- Verriegelungszustand über die Fahrzeugkontur: grün = verriegelt, orange = entriegelt
- offene Türen/Fenster und eingeschaltetes Licht werden am Fahrzeug orange hervorgehoben
- **Laden nur einmal** im oberen Hauptbereich: Steckerzustand, AC/DC, Ladeleistung, Zeit bis voll, Ladelimit und Lademodus
- bei nicht angeschlossenem Ladekabel werden Ladeleistung und Zeit bis voll als `—` statt irreführend als `0,0 kW` / `00:00` angezeigt
- kompakte Statuszeile für Verriegelung, Türen, Fenster und Licht
- **Klima nur einmal** als eindeutige Zeile mit Betriebsart (`Aus`, `Kühlen`, `Heizen`, `Standheizung`, `Lüften`) und Solltemperatur
- Kilometerstand unten rechts
- API-Key-Warnung bei höchstens 30 Tagen Restlaufzeit
- transparente und theme-neutrale Gestaltung für helle, dunkle und individuell eingefärbte Symcon-Designs

### Design-Inspiration

Für die kompakte Kachelgestaltung dient die [TileVisu-Kachelsammlung von da8ter](https://github.com/da8ter/TileVisu-Kachelsammlung) als gestalterische Orientierung. Übernommen wurden keine Quelltexte oder Grafikdateien. Die MySkoda-Kachel und das Fahrzeug-SVG sind eigenständig umgesetzt.

Die Variable **`LastUpdateAge`** zeigt das Alter der letzten erfolgreichen API-Abfrage im Format `hh:mm`. Kachel und Altersanzeige werden einmal pro Minute aktualisiert, auch wenn das API-Abrufintervall länger ist.

## 7. PHP-Befehlsreferenz

Das Modul verwendet den Prefix `MSKODA`.

### Fahrzeugdaten aktualisieren

```php
MSKODA_Update(12345);
```

### Vollständige API-Rohdaten auslesen

```php
$json = MSKODA_GetRawData(12345);
```

### Ladeprofile auslesen

```php
$json = MSKODA_GetChargingProfiles(12345);
```

### Vom Fahrzeug gemeldete Remote-Funktionen auslesen

```php
$json = MSKODA_GetRemoteOperations(12345);
```

### Ladelimit setzen

```php
$ok = MSKODA_SetChargingLimit(12345, 80);
```

### Lademodus setzen

```php
$ok = MSKODA_SetChargeMode(12345, 'MANUAL');
```

Die tatsächlich verfügbaren Modi sind fahrzeugabhängig.

### Ladeprofil aktualisieren

```php
$ok = MSKODA_UpdateChargingProfile(12345, 1, $profileJson);
```

### Standheizung starten

```php
$ok = MSKODA_StartAuxiliaryHeating(12345, 22.0, 30, 'HEATING');
```

Parameter: Zieltemperatur in °C, Dauer in Minuten und Modus (`HEATING` oder `VENTILATION`). Die konfigurierte S-PIN wird automatisch verwendet.

### Standheizung stoppen

```php
$ok = MSKODA_StopAuxiliaryHeating(12345);
```

### Aktive Lüftung starten/stoppen

```php
MSKODA_StartVentilation(12345);
MSKODA_StopVentilation(12345);
```

### OpenAPI-Definition neu laden

```php
$ok = MSKODA_RefreshApiDefinition(12345);
```

Die OpenAPI-Definition wird für neuere Ladeoperationen wie Ladelimit, Lademodus und Ladeprofil verwendet.

### Visualisierungskachel und Zeitangaben sofort aktualisieren

```php
MSKODA_RefreshVisuals(12345);
```

Normalerweise ist dieser Aufruf nicht nötig, da das Modul die Anzeigen minütlich selbst aktualisiert. Er ist aber praktisch nach manuellen Änderungen oder zum Testen.

### API-Key Ablaufwarnung

Die API liefert das Ablaufdatum des Keys über den Header `X-API-Key-Expires-At`. Sobald die Restlaufzeit **30 Tage oder weniger** beträgt, setzt das Modul `ApiKeyWarning` auf `true`.

Optional kann zusätzlich eine Push-Mitteilung versendet werden. Dazu in der Instanz eine **Kachel-Visualisierung** oder ein **WebFront** als Instanz-ID eintragen und die Benachrichtigung aktivieren. Das Modul versucht automatisch `VISU_PostNotification` und anschließend `WFC_PushNotification`. Für einen Funktionstest steht im Instanzformular die Schaltfläche **Mitteilung testen** bereit.

## 8. Rate-Limit und Diagnose

Das Modul wertet, sofern von der API geliefert, folgende Header aus:

- `RateLimit-Limit`
- `RateLimit-Remaining`
- `RateLimit-Reset`
- `Retry-After`
- `X-API-Key-Expires-At`

Automatische Abfragen halten eine kleine Reserve für manuell ausgelöste Remote-Befehle frei. Fordert die API eine Wartezeit, unterdrückt das Modul vorübergehend weitere Abfragen und setzt den Instanzstatus `203`.

Die komplette letzte API-Antwort bleibt als Instanzattribut gespeichert, statt für jedes API-Feld eine eigene Variable zu erzeugen.

## 9. Bekannte Einschränkungen

- Die Public API liefert je nach Fahrzeug, Ausstattung, Lizenz und aktivierten Škoda-Connect-Diensten nur die jeweils verfügbaren Daten.
- Nicht unterstützte Bereiche können in der API-Antwort fehlen oder als Teilfehler gemeldet werden.
- Remote-Befehle können vom Fahrzeug abgelehnt werden, etwa bei fehlender Berechtigung, deaktivierter Funktion oder temporärer Fahrzeugsperre.
- Das Modul kann den von Škoda vorgegebenen API-Request-Rahmen nicht erhöhen und hält deshalb bewusst Reserve für manuelle Befehle.

## 10. Versionshistorie

Siehe [CHANGELOG.md](../CHANGELOG.md) der Bibliothek.

## 11. Lizenz

MIT-Lizenz, siehe [LICENSE](../LICENSE).
