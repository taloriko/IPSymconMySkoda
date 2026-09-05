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
| `Locked` | Verriegelt | Boolean | Nein | Ja/Nein-Aufzählung: **Ja grün**, Nein orange |
| `DoorsOpen` | Türen offen | Boolean | Nein | Ja/Nein-Aufzählung: **Nein grün**, Ja orange |
| `WindowsOpen` | Fenster offen | Boolean | Nein | Ja/Nein-Aufzählung: **Nein grün**, Ja orange |
| `Charging` | Laden | Boolean | Ja | Ja/Nein-Aufzählung: Nein neutral, **Ja grün** |
| `ChargePower` | Ladeleistung | Float | Nein | Symcon-Standardvorlage Leistung |
| `TargetSOC` | Ladelimit | Integer | Ja | Schieberegler 50-100 % |
| `ChargeMode` | Lademodus | Integer | Ja | Aufzählung aus den Fahrzeugmodi |
| `Climate` | Klimatisierung | Boolean | Ja | Ja/Nein-Aufzählung: Nein neutral, **Ja grün** |
| `TargetTemperature` | Klima Solltemperatur | Float | Ja | Temperatur-Schieberegler |
| `VehicleTile` | Fahrzeugübersicht | String | Nein | neue Symcon-Darstellung **Webinhalt** |
| `ApiKeyWarning` | API-Key Warnung | Boolean | Nein | **Nein grün**, Ja orange; wird 30 Tage vor Ablauf aktiv |
| `LastUpdateAge` | Alter letzte Abfrage | String | Nein | Format `hh:mm` |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein | Symcon-Standardvorlage Datum/Uhrzeit |

Bei aktivierter Option **Detail- und Diagnosevariablen anzeigen** werden zusätzlich unter anderem Fahrzeugname, Kennzeichen, Ladestatus/-art, erwartete Voll-Ladezeit, Kofferraum, Motorhaube, Licht, Parkstatus, Koordinaten, API-Key-Ablaufdatum, verbleibende API-Abfragen und Teilfehler bereitgestellt.

### Keine Legacy-Profile

Seit Version **1.4** legt MySkoda **keine eigenen Variablenprofile mehr an**. Die Boolean-Zustände verwenden direkt die neue Symcon-Darstellung **Aufzählung** mit den Werten `Ja` und `Nein` und einer zustandsabhängigen Farbe. Die Fahrzeugkachel nutzt die neue Darstellung **Webinhalt** statt des Legacy-Profils `~HTMLBox`.

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
| Laden | **Ja** | kein Warnzustand; Nein ist neutral |
| Klima | **Ja** | kein Warnzustand; Nein ist neutral |

Damit erscheint ein korrekt abgestelltes und verriegeltes Fahrzeug bei allen sicherheitsrelevanten Zuständen **grün**, obwohl die zugrunde liegenden Werte teils `Ja` und teils `Nein` sind. **Rot wird für diese Hinweise nicht verwendet**; Rot bleibt echten Fehlern und Störungen vorbehalten.

Beim Update von Version 1.3 entfernt das Modul die damals erzeugten `MySkoda.YesNo.*`-Legacy-Profile von seinen Variablen und löscht sie, sofern sie nicht anderweitig verwendet werden.

## 6. Visualisierung

Es wird keine eigene HTML-/WebFront-Oberfläche benötigt. Die Statusvariablen können direkt in der aktuellen Symcon-Visualisierung verwendet werden. Bedienbare Variablen erhalten nur dann eine Aktion, wenn **Remote-Steuerung aktivieren** gesetzt ist.

Zusätzlich legt das Modul die Variable **`VehicleTile`** an. Die Kachel ist in Version 1.2 bewusst **smartphone-orientiert und kompakt** aufgebaut. Statt vieler einzelner Rahmen werden zusammengehörige Informationen in einer flachen Ansicht gebündelt:

- Überschrift aus Fahrzeugname, alternativ Kennzeichen
- kompakte Autoansicht von oben mit SOC und Ladelimit im Fahrzeug
- Ladestecker rechts mit den Zuständen Laden, Ladeunterbrechung, Kabel anschließen, bereit, Ladeziel erreicht und Entladen
- Ladeleistung und Restzeit bis voll im Format `hh:mm`
- Reichweite und Kilometerstand
- Verriegelung, Türen, Fenster und Licht als kompakte Statuszeile
- eigene, platzsparende Zeile für **Laden**
- eigene, platzsparende Zeile für **Klima**
- API-Key-Warnung bei höchstens 30 Tagen Restlaufzeit
- transparente/neutral aufgebaute Darstellung für helle und dunkle Symcon-Themes

Die Variable **`LastUpdateAge`** zeigt das Alter der letzten erfolgreichen API-Abfrage im Format `hh:mm`. Beide Anzeigen werden einmal pro Minute aktualisiert, auch wenn das API-Abrufintervall länger ist.

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
