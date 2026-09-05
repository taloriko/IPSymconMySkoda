# MySkoda

Gerätemodul für IP-Symcon zur Anbindung eines Fahrzeugs an die offizielle MySkoda Public API. Eine Instanz repräsentiert genau eine FIN/VIN.

## 1. Funktionsumfang

- Fahrzeugstatus über die offizielle MySkoda Public API
- native, smartphone-optimierte Symcon-Kachel direkt an der Instanz
- automatische oder manuelle Fahrzeugdarstellung für Enyaq, Elroq, Epiq und allgemeine Fahrzeuge
- SOC, Reichweite, Kilometerstand, Ladezustand, Ladeleistung, Zeit bis voll, Ladelimit und Lademodus
- Verriegelung als Textstatus; Türen, Fenster, Schiebedach, Licht, Kofferraum und Motorhaube direkt am Fahrzeug visualisiert
- Klimatisierung, Laden, Ladelimit und Lademodus steuerbar, soweit Fahrzeug/API dies unterstützen
- Standheizung und aktive Lüftung über PHP-Befehle
- Verbindungstest nach Ersteinrichtung
- API-Key-Ablaufwarnung 30 Tage vor Ablauf, optional als Symcon-Mitteilung
- Rate-Limit- und `Retry-After`-Behandlung
- moderne Symcon-Darstellungen ohne eigene Legacy-Variablenprofile

## 2. Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- optional S-PIN für Standheizung
- für die gewünschte Funktion aktive MySkoda/Škoda-Connect-Dienste

Das programmgesteuerte Ausblenden des äußeren Symcon-Kacheltitels benötigt **Symcon 9.1 oder neuer**. Das Modul bleibt auf älteren kompatiblen Versionen funktionsfähig.

## 3. Installation

Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Danach eine Instanz **MySkoda** anlegen.

### API-Key erstellen

In der **MySkoda App**:

1. **Profil**
2. **Smart Home**
3. **Create Key**
4. beliebigen Namen vergeben
5. FIN/VIN und API-Token in die Symcon-Instanz übernehmen

Nach dem Übernehmen prüft das Modul die Verbindung und zeigt das Ergebnis direkt im Instanzformular.

## 4. Konfiguration

Die Einstellungen sind in übersichtliche Bereiche aufgeteilt.

| Bereich | Einstellung | Beschreibung |
|---|---|---|
| Verbindung & Fahrzeug | FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer |
| Verbindung & Fahrzeug | API-Token | MySkoda Public API-Key |
| Visualisierung | Fahrzeugdarstellung | Automatisch, Enyaq, Elroq, Epiq oder Allgemein |
| Visualisierung | Symcon-Kacheltitel ausblenden | blendet ab Symcon 9.1 den äußeren Kacheltitel aus; Instanzname bleibt erhalten |
| Abruf & Steuerung | Abrufintervall | Standard 300 s, Minimum 180 s |
| Abruf & Steuerung | Remote-Steuerung | aktiviert bedienbare Lade-/Klimafunktionen |
| Abruf & Steuerung | Klima ohne externe Stromversorgung | Übergabe an die MySkoda-Klimafunktion |
| Abruf & Steuerung | S-PIN | optional für Standheizung |
| Mitteilungen | API-Key-Ablaufwarnung | Push-Mitteilung bei höchstens 30 Tagen Restlaufzeit |
| Mitteilungen | Visualisierung für Mitteilungen | Auswahl per Symcon-Instanzdialog statt manueller ID-Eingabe |
| Erweitert | Detail-/Diagnosevariablen | zusätzliche Fahrzeug- und API-Daten |

### Mitteilungsziel

Das Mitteilungsziel wird über **SelectInstance** ausgewählt. Das Formular schränkt die Auswahl soweit möglich auf vorhandene Symcon-Visualisierungsmodule ein. Zusätzlich prüft das Modul den gewählten Eintrag zur Laufzeit als Symcon-Visualisierungsinstanz (`ModuleType = 6`).

Mit **Mitteilung testen** kann die Konfiguration direkt geprüft werden. Das Modul verwendet je nach Ziel `VISU_PostNotification` bzw. `WFC_PushNotification`.

### Standortfreigabe

Standortdaten werden nur geliefert, wenn im verwendeten **MySkoda-Profil die Standortfreigabe** aktiviert wurde. Bei mehreren Profilen für dasselbe Fahrzeug muss die Freigabe **je Profil separat** erfolgen.

Liefert die API keine Position, setzt das Modul Breitengrad und Längengrad bewusst auf **`0.0 / 0.0`**, damit keine alten Koordinaten stehen bleiben.

## 5. Statusvariablen und Darstellungen

Der Standard-Objektbaum bleibt absichtlich klein.

| Ident | Variable | Typ | Bedienbar |
|---|---|---|---|
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
| `TargetTemperature` | Klima Solltemperatur | Float | Ja |
| `ApiKeyWarning` | API-Key Warnung | Boolean | Nein |
| `LastUpdateAge` | Alter letzte Abfrage | String | Nein |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein |

### Darstellungen statt Legacy-Profile

MySkoda legt **keine eigenen Legacy-Variablenprofile** an. Boolean-Status verwenden moderne Symcon-Wertanzeigen, bedienbare Werte moderne Schalter-/Aufzählungsdarstellungen.

Die Statuslogik bewertet den tatsächlichen Fahrzeugzustand:

- **grün** = erwarteter Zustand
- **orange** = Hinweis / Aufmerksamkeit
- **rot** = nur echte Fehler / Störungen

Beispiele: Verriegelt = grün, Türen geschlossen = grün, Fenster geschlossen = grün, Licht aus = grün.

## 6. Visualisierung

Die Instanz stellt die native Kachel über `SetVisualizationType(1)`, `GetVisualizationTile()` und `UpdateVisualizationValue()` bereit. Für neue Visualisierungen soll deshalb die **MySkoda-Instanz selbst** eingefügt werden.

### Scroll- und Wiederanzeige-Verhalten ab 1.9

Symcon kann HTML-SDK-Kacheln beim Scrollen aus dem sichtbaren Bereich recyceln. Version 1.9 stellt den Zustand deshalb mehrstufig wieder her:

- vollständiger Fahrzeugzustand ist bereits in der initial gelieferten HTML-Kachel enthalten
- Browser-Cache ist **instanzbezogen**, damit mehrere Fahrzeuge getrennt bleiben
- beim Wiederanzeigen fordert die Kachel über `requestAction('VisualizationRefresh', ...)` den vollständigen Zustand erneut an
- das Modul antwortet dabei nur aus dem gespeicherten `RawData`
- diese Wiederherstellung löst **keinen neuen MySkoda-API-Aufruf** aus

Zusätzlich wird bei `pageshow`, Fokus, Sichtbarkeits- und Größenänderungen erneut gerendert.

### Kacheltitel

Die Kachel besitzt bereits einen eigenen Kopf mit Fahrzeugname. Mit **Symcon-Kacheltitel ausblenden** wird auf Symcon 9.1+ der äußere Symcon-Titel ausgeblendet, ohne den Instanznamen im Objektbaum zu verändern.

Auf Symcon 8.1 bis 9.0 bleibt der äußere Titel sichtbar; die Kachel reserviert dafür zusätzlichen oberen Platz.

### Fahrzeugdarstellung

Bei **Automatisch** wertet das Modul zuerst Modell-/Spezifikationsdaten der MySkoda-Antwort aus und nutzt danach konservative Modellhinweise. Die FIN allein wird nicht als sichere Enyaq-/Elroq-Unterscheidung verwendet. Die manuelle Auswahl hat immer Vorrang.

Die Batterie besitzt vier Segmente. Der SOC-Farbverlauf orientiert sich an:

- bis 10 % rot
- 25 % orange
- 80 % hellgrün
- darüber zunehmend dunkelgrün

Weitere Details: [../docs/VISUALIZATION.md](../docs/VISUALIZATION.md)

## 7. PHP-Befehle

```php
MSKODA_Update(12345);
MSKODA_TestConnection(12345);
MSKODA_TestNotification(12345);
MSKODA_RefreshVisuals(12345);
MSKODA_RefreshApiDefinition(12345);
```

Daten auslesen:

```php
$json = MSKODA_GetRawData(12345);
$profiles = MSKODA_GetChargingProfiles(12345);
$operations = MSKODA_GetRemoteOperations(12345);
```

Laden:

```php
MSKODA_SetChargingLimit(12345, 80);
MSKODA_SetChargeMode(12345, 'MANUAL');
MSKODA_UpdateChargingProfile(12345, 1, $profileJson);
```

Standheizung / Lüftung:

```php
MSKODA_StartAuxiliaryHeating(12345, 22.0, 30, 'HEATING');
MSKODA_StopAuxiliaryHeating(12345);
MSKODA_StartVentilation(12345);
MSKODA_StopVentilation(12345);
```

## 8. Rate-Limit und Diagnose

Das Modul wertet die von der API gelieferten Rate-Limit-Header und `Retry-After` aus. Das empfohlene Abrufintervall beträgt **300 Sekunden**. Visualisierungs-Refreshes nach Scrollen/Wiederanzeigen arbeiten aus bereits gespeicherten Daten und verbrauchen keine zusätzliche Fahrzeugabfrage.

Mit aktivierten Detail-/Diagnosevariablen stehen unter anderem API-Key-Ablauf, verbleibende Requests, Teilfehler und weitere Fahrzeugdaten zur Verfügung.

## 9. Bekannte Einschränkungen

- unterstützte Remote-Funktionen sind fahrzeug- und API-abhängig
- einige Statusfelder, z. B. Schiebedach, können bei einzelnen Modellen fehlen
- Standort ist profilabhängig freizugeben
- automatisches Ausblenden des äußeren Symcon-Kacheltitels benötigt Symcon 9.1+
- das Modul ist eine unabhängige Community-Integration und kein offizielles Škoda-Produkt

## 10. Versionshistorie

Siehe [../CHANGELOG.md](../CHANGELOG.md).

## 11. Lizenz

MIT-Lizenz, siehe [../LICENSE](../LICENSE).
