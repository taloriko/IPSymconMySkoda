# MySkoda

MySkoda ist ein Gerätemodul für Symcon zur Anbindung eines Škoda-Fahrzeugs an die offizielle **MyŠkoda Public API**. Eine Instanz repräsentiert genau eine FIN/VIN.

Der tatsächlich verfügbare Funktionsumfang hängt vom Fahrzeug, dessen Ausstattung und den für das Fahrzeug freigegebenen MyŠkoda-Diensten ab. Das Modul wurde praktisch mit einem **Škoda Enyaq 80** getestet.

## 1. Funktionsumfang

- Fahrzeugdaten über FIN/VIN und MySkoda API-Key
- zyklischer Abruf mit einstellbarem Abfrageintervall
- Berücksichtigung der von der API gelieferten Rate-Limit-Informationen und `Retry-After`
- stabile Variablen-Idents als Schnittstelle für Skripte und weitere Module
- optionale Detail-, Standort- und Diagnosevariablen
- lokale FIN/VIN-Entschlüsselung in der Instanzkonfiguration
- optionale FIN-Informationsvariablen als reine String-Variablen
- automatische Prüfung der öffentlichen OpenAPI-Definition auf neue, noch nicht integrierte API-Operationen
- optionale API-Key-Ablaufwarnung per Symcon-Mitteilung
- optionale Archivierung ausgewählter Fahrzeugwerte nach ausdrücklicher Aktivierung

Die Details zur FIN/VIN-Zerlegung, den ausgewerteten Stellen, bekannten Škoda-Codes, Prüflogik und Grenzen der Interpretation sind in [README_FIN_VIN.md](README_FIN_VIN.md) dokumentiert.

> **Hinweis zur FIN/VIN-Entschlüsselung:** Die daraus abgeleiteten Angaben sind keine offiziellen Fahrzeugstammdaten von Škoda. Sie werden anhand öffentlich verfügbarer Informationen interpretiert und können bei einzelnen Fahrzeugen unvollständig oder mehrdeutig sein.

### Lesbare Fahrzeugdaten

Soweit vom Fahrzeug und der API geliefert, werden unter anderem verarbeitet:

- Ladezustand, Reichweite und Kilometerstand
- Verriegelungs-, Tür- und Fensterstatus
- Ladeleistung, Ladelimit und Lademodus
- Klimatisierungsstatus und Solltemperatur
- Fahrzeugname und Kennzeichen
- Standortdaten
- weitere Status- und Diagnoseinformationen

### Steuerbare Funktionen

Soweit vom Fahrzeug und der API unterstützt:

- Laden starten und stoppen
- Ladelimit ändern
- Lademodus ändern
- Klimatisierung starten und stoppen
- Solltemperatur der Klimatisierung ändern
- Ladeprofile über die öffentliche PHP-Schnittstelle aktualisieren
- Standheizung über die öffentliche PHP-Schnittstelle starten und stoppen
- aktive Lüftung über die öffentliche PHP-Schnittstelle starten und stoppen

Die Remote-Steuerung kann in der Instanz vollständig deaktiviert werden.

### In der App verfügbar, aber nicht in der Public API

Folgende Funktionen sind in der MySkoda App verfügbar, aber im offiziellen **Public-API-Vertrag 1.0.0** derzeit nicht enthalten und können deshalb vom Modul nicht bereitgestellt werden:

- Camping Mode
- Klima-Timer / Abfahrtszeiten
- intelligentes Heizen / intelligentes Klimatisieren
- Scheibenheizung im Zusammenhang mit der Klimatisierung
- Sitzheizung Fahrer und Beifahrer im Zusammenhang mit der Klimatisierung
- Battery Care Mode
- reduzierte AC-Ladeleistung / Begrenzung des AC-Ladestroms
- automatisches Entriegeln des AC-Ladekabels

Das Modul verwendet für Fahrzeugdaten und Remote-Funktionen ausschließlich die offizielle MyŠkoda Public API. Private oder interne App-Schnittstellen werden nicht verwendet.

## 2. Voraussetzungen

- Symcon **8.1 oder neuer**
- 17-stellige FIN/VIN
- MySkoda API-Key
- aktive MySkoda/Škoda-Connect-Dienste für die verwendeten Fahrzeugfunktionen
- Internetzugang von Symcon zur MyŠkoda Public API
- optional S-PIN für die Standheizung
- Archive Control nur bei Verwendung der optionalen Archivierung

Der API-Key wird in der MySkoda App unter **Profil → Smart Home → Schlüssel erstellen** erzeugt.

Offizielle API-Dokumentation: <https://public.api.connect.skoda-auto.cz/docs>

## 3. Installation

### Module Store

Im Symcon **Module Store** nach **MySkoda** suchen, das Modul installieren und anschließend eine Instanz **MySkoda** anlegen.

### Manuell über Module Control

Alternativ das Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Anschließend kann unter **Instanz hinzufügen** eine Instanz **MySkoda** angelegt werden.

## 4. Einrichten der Instanz

1. In der MySkoda App einen API-Key erstellen.
2. Eine Instanz **MySkoda** anlegen.
3. FIN/VIN und API-Token eintragen.
4. Konfiguration übernehmen.
5. Über **Verbindung testen** prüfen, ob Fahrzeugdaten empfangen werden.
6. Optional FIN-Informationsvariablen, Archivierung, Mitteilungen sowie Detail- und Diagnosevariablen aktivieren.

Das Standard-Abfrageintervall beträgt **300 Sekunden**. In der Konfiguration sind Werte von **180 bis 3600 Sekunden** möglich.

## 5. Konfiguration

| Einstellung | Funktion | Standard |
|---|---|---:|
| FIN / VIN | 17-stellige Fahrzeug-Identifikationsnummer | leer |
| FIN-Informationsvariablen | legt die lokal entschlüsselten FIN-Informationen als String-Variablen an | aus |
| API-Token | MyŠkoda Public API-Key | leer |
| Abfrageintervall | automatischer Fahrzeugabruf in Sekunden | 300 s |
| Remote-Steuerung | erlaubt die vom Modul bereitgestellten Remote-Befehle | an |
| Klima ohne externe Stromversorgung | erlaubt Klimatisierung ohne angeschlossene externe Stromversorgung | an |
| S-PIN | wird für die Standheizung verwendet | leer |
| Fahrzeugdaten archivieren | richtet das Logging ausgewählter Fahrzeugwerte einmalig ein | aus |
| API-Key-Ablaufwarnung | sendet bei höchstens 30 Tagen Restlaufzeit eine Mitteilung | aus |
| Visualisierung für Mitteilungen | Zielinstanz für Symcon-Mitteilungen | keine |
| Detail-/Diagnosevariablen | legt zusätzliche Status- und Diagnosevariablen an | aus |

Die Schaltflächen in der Konfiguration ermöglichen zusätzlich:

- **Verbindung testen**
- **Jetzt aktualisieren**
- **Mitteilung testen**
- **API-Definition neu laden**
- **Fahrzeugbilder diagnostizieren**
- **Fahrzeugbild aktualisieren**
- **Public-API-Daten diagnostizieren**

## 6. Statusvariablen und Darstellungen

Das Modul legt ausschließlich eigene Variablen unterhalb der MySkoda-Instanz an. Es werden keine Dummy-Instanzen, Kategorien oder Links erzeugt.

Vorhandene Variablen bleiben benutzerverwaltet. Namen, Positionen und andere benutzerseitig veränderbare Objekteigenschaften werden bei späteren Aktualisierungen nicht fortlaufend überschrieben.

### 6.1 Standard-Datenpunkte

| Ident | Deutsche Anzeige | Typ | Bedienbar | Beschreibung |
|---|---|---|---:|---|
| `StateOfCharge` | Ladezustand | Integer | Nein | Batterieladezustand in Prozent |
| `Range` | Reichweite | Integer | Nein | verbleibende Reichweite in km |
| `Mileage` | Kilometerstand | Integer | Nein | Kilometerstand |
| `Locked` | Verriegelt | Boolean | Nein | Verriegelungsstatus |
| `DoorsOpen` | Türen offen | Boolean | Nein | mindestens eine Tür offen |
| `WindowsOpen` | Fenster offen | Boolean | Nein | mindestens ein Fenster offen |
| `Charging` | Laden | Boolean | Ja | Laden starten/stoppen bzw. aktueller Ladezustand |
| `ChargePower` | Ladeleistung | Float | Nein | aktuelle Ladeleistung |
| `TargetSOC` | Ladelimit | Integer | Ja | 50 bis 100 % in 10-%-Schritten |
| `ChargeMode` | Lademodus | Integer | Ja | vom Fahrzeug unterstützter Lademodus |
| `Climate` | Klimatisierung | Boolean | Ja | Klimatisierung starten/stoppen bzw. aktueller Zustand |
| `TargetTemperature` | Solltemperatur | Float | Ja | 16 bis 30 °C in 0,5-°C-Schritten |
| `ApiKeyWarning` | API-Key Warnung | Boolean | Nein | API-Key läuft innerhalb von 30 Tagen ab |
| `NewApiFeatures` | Neue API-Funktionen | Integer | Nein | Anzahl unbekannter Operationen der aktuellen OpenAPI-Definition |
| `LastUpdate` | Letzte Aktualisierung | Integer | Nein | Zeitpunkt des letzten erfolgreichen Fahrzeugabrufs |

### 6.2 Optionale FIN-Informationsvariablen

Wenn **FIN-Informationsvariablen anlegen** aktiviert wird, legt das Modul die entschlüsselten FIN-Daten als einfache String-Variablen an. Es werden keine Icons, Profile oder besonderen Darstellungen verwendet.

Die Variablen werden bei der Erstanlage und anschließend nur bei Änderung der konfigurierten FIN aktualisiert. Der normale zyklische Fahrzeugabruf verändert sie nicht. Bereits angelegte FIN-Variablen bleiben beim späteren Deaktivieren der Option bestehen.

Die genaue Liste der FIN-Variablen und deren Bedeutung ist in [README_FIN_VIN.md](README_FIN_VIN.md) beschrieben.

### 6.3 Optionale Detail- und Diagnosevariablen

Bei aktivierter Option **Detail- und Diagnosevariablen anlegen** werden zusätzliche Status- und Diagnosevariablen angelegt, unter anderem Fahrzeugname, Kennzeichen, Kofferraum-/Haubenstatus, Ladeinformationen, Standortdaten, API-Restkontingent, `PendingCommands` und `CommandStatus`.

Einmal angelegte Detailvariablen werden beim Deaktivieren der Option nicht gelöscht.

### 6.4 Profile und Darstellungen

Das Modul verwendet die nativen Darstellungen von Symcon. Es werden keine benutzerdefinierten Variablenprofile angelegt.

Der Lademodus wird nur gesendet, wenn er in der vom Fahrzeug gemeldeten Liste `charging.settings.availableChargeModes` enthalten ist.

## 7. Befehlslogik für Remote-Befehle

Bei den über Variablen bedienbaren Remote-Funktionen wird der gewünschte Wert beim Absenden sofort lokal angezeigt.

Während der synchronen HTTP-Anfrage wird der betroffene Datenpunkt als Pending geführt. Danach gilt:

- **erfolgreiche 2xx-Antwort**: Der gewünschte Wert bleibt gesetzt und Pending wird beendet.
- **Fehlerantwort oder Übertragungsfehler**: Der vorherige lokale Wert wird wiederhergestellt und Pending wird beendet.

Der normale zyklische Fahrzeugabruf läuft unabhängig davon weiter. Liefert das Portal später einen anderen Fahrzeugzustand, wird dieser beim regulären Abruf übernommen.

`PendingCommands` zeigt die Anzahl der aktuell laufenden Befehlsanfragen. `CommandStatus` zeigt das Ergebnis des letzten Befehls. Bei einem Fehler wird zusätzlich der von API oder Transport gelieferte Fehlertext ausgegeben.

## 8. API-Diagnose und neue API-Funktionen

Nach einer erfolgreichen Fahrzeugabfrage prüft das Modul zusätzlich die öffentliche OpenAPI-Definition der MyŠkoda Public API. Die Definition wird intern bis zu 24 Stunden zwischengespeichert.

`NewApiFeatures` zeigt die Anzahl der API-Operationen, die der aktuellen Modulversion noch nicht bekannt sind:

- `0` – keine unbekannte Operation erkannt
- `> 0` – die öffentliche API enthält zusätzliche, noch nicht integrierte Operationen

Neue Operationen werden nicht automatisch als Variablen oder Befehle angelegt.

## 9. Archivierung

Die Archivierung ist standardmäßig **aus** und wird nur nach ausdrücklicher Aktivierung eingerichtet.

Einmalig werden folgende Variablen für das Logging im Archive Control aktiviert:

- `StateOfCharge` – Ladezustand
- `TargetSOC` – Ladelimit
- `ChargePower` – Ladeleistung
- `Mileage` – Kilometerstand

Der Kilometerstand wird als Zähler eingerichtet. Werte `<= 0` werden nicht übernommen. Nach der erstmaligen Einrichtung verändert das Modul spätere Benutzeranpassungen im Archive Control nicht mehr.

## 10. Visualisierung

Die vom Modul angelegten Variablen können direkt in den Symcon-Visualisierungen verwendet werden.

Für eine zusätzliche Fahrzeugdarstellung kann optional das separate Modul [IPSymconEVTile](https://github.com/taloriko/IPSymconEVTile) verwendet werden. MySkoda selbst legt keine zusätzliche Objektstruktur für die Visualisierung an.

## 11. PHP-Befehlsreferenz

In den Beispielen ist `12345` die Instanz-ID der MySkoda-Instanz.

| Befehl | Rückgabe | Funktion |
|---|---|---|
| `MSKODA_Update(12345);` | `void` | Fahrzeugdaten sofort aktualisieren |
| `MSKODA_TestConnection(12345);` | `bool` | Verbindung und Fahrzeugabruf testen |
| `MSKODA_GetRawData(12345);` | `string` | letzte vollständige Fahrzeugantwort als JSON ausgeben |
| `MSKODA_GetVINData(12345);` | `string` | lokal entschlüsselte FIN-Informationen als JSON ausgeben |
| `MSKODA_GetChargingProfiles(12345);` | `string` | Ladeprofile aus den zuletzt empfangenen Fahrzeugdaten als JSON ausgeben |
| `MSKODA_GetRemoteOperations(12345);` | `string` | vom Fahrzeug gemeldete Remote-Operationen als JSON ausgeben |
| `MSKODA_SetChargingLimit(12345, 80);` | `bool` | Ladelimit setzen |
| `MSKODA_SetChargeMode(12345, 'MANUAL');` | `bool` | Lademodus setzen |
| `MSKODA_UpdateChargingProfile(12345, 1, $profileJson);` | `bool` | Ladeprofil aktualisieren |
| `MSKODA_StartAuxiliaryHeating(12345, 22.0, 30, 'HEATING');` | `bool` | Standheizung starten; S-PIN erforderlich |
| `MSKODA_StopAuxiliaryHeating(12345);` | `bool` | Standheizung stoppen |
| `MSKODA_StartVentilation(12345);` | `bool` | aktive Lüftung starten |
| `MSKODA_StopVentilation(12345);` | `bool` | aktive Lüftung stoppen |
| `MSKODA_RefreshApiDefinition(12345);` | `bool` | öffentliche OpenAPI-Definition neu laden |
| `MSKODA_TestNotification(12345);` | `bool` | konfigurierte Symcon-Mitteilung testen |
| `MSKODA_DiagnoseVehicleImages(12345);` | `string` | Diagnose der von der API gelieferten Fahrzeugbilder |
| `MSKODA_RefreshVehicleImage(12345);` | `bool` | lokales Fahrzeugbild aktualisieren |
| `MSKODA_DiagnosePublicApiData(12345);` | `string` | genutzte und ungenutzte Public-API-Datenpfade ausgeben |

## 12. Instanzstatus und Fehlersuche

| Code | Bedeutung |
|---:|---|
| `102` | verbunden / bereit |
| `104` | inaktiv |
| `201` | FIN oder API-Token fehlt oder ist ungültig |
| `202` | API- oder Verbindungsfehler |
| `203` | Rate-Limit / Wartezeit aktiv |

Typische Prüfungen:

- FIN/VIN und API-Token prüfen und anschließend **Verbindung testen** ausführen.
- Bei Status `203` das API-Rate-Limit bzw. die Wartezeit abwarten.
- Bei zurückspringenden Befehlswerten `CommandStatus` prüfen.
- Standortdaten sind nur verfügbar, wenn die MyŠkoda Public API sie für Fahrzeug und Benutzer liefert.

Bei Fehlermeldungen niemals API-Key, S-PIN oder vollständige FIN öffentlich veröffentlichen.

## 13. Datenschutz und externe Dienste

Das Modul kommuniziert direkt mit der offiziellen MyŠkoda Public API. Für fahrzeugbezogene API-Anfragen werden die konfigurierte FIN/VIN und der API-Token verwendet.

Remote-Befehle werden ausschließlich durch eine Benutzeraktion, ein Benutzerskript oder einen Aufruf der dokumentierten öffentlichen Modulmethoden ausgelöst.

Zusätzlich lädt das Modul die öffentliche OpenAPI-Definition von Škoda. Für diesen Abruf wird kein Fahrzeug-API-Key übertragen.

Das Fahrzeugbild wird ausschließlich von der URL geladen, die von der offiziellen MyŠkoda Public API für das konfigurierte Fahrzeug geliefert wird, und lokal in Symcon gespeichert.

FIN/VIN, API-Token und optional die S-PIN werden als Instanzkonfiguration in Symcon gespeichert. Zugangsdaten sollten nicht in Fehlermeldungen, Screenshots oder öffentlichen Supportbeiträgen veröffentlicht werden.

## 14. Versionshistorie

Die Versionshistorie der Library befindet sich in [CHANGELOG.md](../CHANGELOG.md).

## 15. Lizenz und Markenhinweis

Copyright © 2026 **taloriko**.

Dieses Projekt wird unter der [MIT-Lizenz](../LICENSE) veröffentlicht.

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch mit Škoda Auto a.s. verbunden oder von Škoda Auto a.s. unterstützt.