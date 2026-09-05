# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Fahrzeugs an die offizielle MySkoda Public API. Eine Instanz repräsentiert genau eine FIN.

## Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanz in IP-Symcon](#4-einrichten-der-instanz-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [Visualisierung](#6-visualisierung)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Rate-Limit und Diagnose](#8-rate-limit-und-diagnose)
9. [Bekannte Einschränkungen](#9-bekannte-einschränkungen)
10. [Versionshistorie](#10-versionshistorie)
11. [Lizenz](#11-lizenz)

## 1. Funktionsumfang

- Liest den vollständigen Fahrzeugstatus über die offizielle MySkoda Public API.
- Stellt standardmäßig nur eine kleine, alltagstaugliche Auswahl an Statusvariablen bereit.
- Kann optional zusätzliche Detail- und Diagnosevariablen anlegen.
- Steuert Laden und Klimatisierung direkt über Variablenaktionen.
- Unterstützt Ladelimit und Lademodus, sofern Fahrzeug und API die Funktion anbieten.
- Stellt PHP-Befehle für Standheizung, aktive Lüftung und Ladeprofil-Updates bereit.
- Speichert die vollständige API-Antwort intern und stellt sie über `MSKODA_GetRawData()` zur Verfügung.
- Beachtet die Rate-Limit-Header und `Retry-After` der API.

## 2. Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- MySkoda API-Key
- 17-stellige FIN/VIN
- Optional: S-PIN für die Standheizung
- Für die jeweiligen Daten/Funktionen aktive Škoda-Connect-Dienste am Fahrzeug

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

Die Instanz kann auch ohne FIN oder Token fehlerfrei angelegt werden. Solange Pflichtangaben fehlen, wird der Timer deaktiviert und der Instanzstatus `201` gesetzt.

## 5. Statusvariablen und Profile

Der Standard-Objektbaum ist absichtlich kompakt.

| Ident | Variablenname | Typ | Bedienbar | Darstellung |
|---|---|---|---|---|
| `StateOfCharge` | Ladezustand | Integer | Nein | Symcon-Standardvorlage Batterie |
| `Range` | Reichweite | Integer | Nein | Wertanzeige, km |
| `Mileage` | Kilometerstand | Integer | Nein | Wertanzeige, km |
| `Locked` | Verriegelt | Boolean | Nein | Standard Boolean |
| `DoorsOpen` | Türen offen | Boolean | Nein | Standard Boolean |
| `WindowsOpen` | Fenster offen | Boolean | Nein | Standard Boolean |
| `Charging` | Laden | Boolean | Ja | Schalter |
| `ChargePower` | Ladeleistung | Float | Nein | Symcon-Standardvorlage Leistung |
| `TargetSOC` | Ladelimit | Integer | Ja | Schieberegler 50-100 % |
| `ChargeMode` | Lademodus | Integer | Ja | Aufzählung aus den Fahrzeugmodi |
| `Climate` | Klimatisierung | Boolean | Ja | Schalter |
| `TargetTemperature` | Klima Solltemperatur | Float | Ja | Temperatur-Schieberegler |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein | Symcon-Standardvorlage Datum/Uhrzeit |

Bei aktivierter Option **Detail- und Diagnosevariablen anzeigen** werden zusätzlich unter anderem Fahrzeugname, Kennzeichen, Ladestatus/-art, erwartete Voll-Ladezeit, Kofferraum, Motorhaube, Licht, Parkstatus, Koordinaten, API-Key-Ablaufdatum, verbleibende API-Abfragen und Teilfehler bereitgestellt.

### Variablenprofile

Das Modul erzeugt **keine Legacy-Variablenprofile**. Es verwendet die Darstellungen ab Symcon 8.x sowie vorhandene Symcon-Standardvorlagen. Eigene Darstellungsparameter werden nur verwendet, wenn keine passende Standardvorlage existiert.

Derzeit ist deshalb kein persistentes eigenes Profil notwendig. Sollte zukünftig tatsächlich ein eigenes Profil benötigt werden, wird es auf das Minimum reduziert und im Namensraum `MySkoda.*` angelegt.

## 6. Visualisierung

Es wird keine eigene HTML-/WebFront-Oberfläche benötigt. Die Statusvariablen können direkt in der aktuellen Symcon-Visualisierung verwendet werden. Bedienbare Variablen erhalten nur dann eine Aktion, wenn **Remote-Steuerung aktivieren** gesetzt ist.

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
