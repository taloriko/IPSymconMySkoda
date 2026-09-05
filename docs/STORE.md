# Vorbereitung für den Symcon Module Store

Diese Datei sammelt die Angaben, die bei einer späteren Einreichung im Symcon-Entwicklerbereich benötigt werden. Die eigentliche Einreichung erfolgt nicht über Dateien im Repository, sondern über den Entwicklerbereich des Symcon-Kontos.

## Vorgeschlagene Bundle ID

```text
de.taloriko.myskoda
```

Die Bundle ID muss dauerhaft eindeutig bleiben. Vor der ersten Einreichung sollte geprüft werden, ob sie im Symcon-Entwicklerbereich verfügbar ist.

## Deutsch

**Name**

```text
MySkoda
```

**Beschreibung**

```text
Bindet Fahrzeuge über die offizielle MySkoda Public API an IP-Symcon an. Das Modul stellt einen kompakten Fahrzeugstatus und eine native Smartphone-Kachel bereit und unterstützt – sofern vom Fahrzeug angeboten – Laden, Ladelimit, Lademodus, Klimatisierung, Standheizung und aktive Lüftung.
```

**Versionsinformation 1.9**

```text
Robustere native Fahrzeugkachel bei Scrollen und Wiederanzeigen: vollständiger Startzustand, instanzbezogener Browser-Cache und erneutes Senden der bereits gespeicherten Fahrzeugdaten ohne zusätzliche MySkoda-API-Abfrage. Zusätzlich kann der äußere Symcon-Kacheltitel auf Symcon 9.1+ ausgeblendet werden, während der Instanzname erhalten bleibt. Das Mitteilungsziel wird jetzt per Instanzauswahl gewählt und als Visualisierungsinstanz geprüft.
```

**Link zur Dokumentation**

```text
https://github.com/taloriko/IPSymconMySkoda/blob/main/MySkoda/README.md
```

## English

**Name**

```text
MySkoda
```

**Description**

```text
Connects vehicles to IP-Symcon through the official MySkoda Public API. The module exposes a compact vehicle status and a native smartphone tile and, where supported by the vehicle, controls charging, charge limit, charging mode, air conditioning, auxiliary heating and active ventilation.
```

**Version information 1.9**

```text
More robust native vehicle tile after scrolling and reappearing: embedded initial state, per-instance browser cache and re-sending of already cached vehicle data without an additional MySkoda API call. On Symcon 9.1+ the outer tile title can be hidden while preserving the instance name. Notification targets are now selected through an instance chooser and validated as visualization instances.
```

**Documentation link**

```text
https://github.com/taloriko/IPSymconMySkoda/blob/main/MySkoda/README.md
```

## Review-Hinweise

- Der sichtbare Modulname bleibt `MySkoda`; `IPSymcon`, `IPS` oder ähnliche Zusätze werden nicht als Store-Name verwendet.
- Die Instanz erstellt ausschließlich eigene Statusvariablen unterhalb der Instanz.
- Der periodische Abruf nutzt `RegisterTimer`; es werden keine externen Ereignisse erzeugt.
- Konfigurierbare Eigenschaften werden über `form.json` bereitgestellt und in klaren klappbaren Bereichen gruppiert.
- Das Modul verwendet ab Symcon 8.1 `IPSModuleStrict`.
- Das Modul legt keine eigenen Legacy-Variablenprofile an. Passive Boolean-Zustände verwenden native Symcon-Wertanzeigen; bedienbare Boolean-Werte verwenden bei aktiver Remote-Steuerung native Schalter.
- Seit Version 1.6 stellt die Instanz selbst eine native HTML-Kachel über `SetVisualizationType(1)` / `GetVisualizationTile()` bereit. Die Darstellung liegt in `MySkoda/module.html` und wird über `UpdateVisualizationValue()` aktualisiert.
- Modellabhängige, eigenständig gezeichnete Top-View-SVGs stehen für Enyaq, Elroq und Epiq sowie als allgemeine Darstellung zur Verfügung.
- Die Fahrzeugdarstellung kann automatisch aus MySkoda-Metadaten gewählt oder manuell überschrieben werden. Die FIN allein wird nicht als sichere Enyaq-/Elroq-Unterscheidung behandelt.
- Ab Version 1.9 wird der vollständige Visu-Zustand bereits beim Aufbau der Kachel eingebettet. Zusätzlich wird ein instanzbezogener Browser-Cache verwendet und die Kachel kann über das HTML-SDK einen vollständigen Refresh der bereits gespeicherten Fahrzeugdaten anfordern. Dieser Refresh führt keine neue MySkoda-API-Abfrage aus.
- `IPS_SetHiddenTitle()` wird nur verwendet, wenn die Funktion in der installierten Symcon-Version verfügbar ist. Die Mindestkompatibilität bleibt deshalb Symcon 8.1; das automatische Ausblenden des äußeren Kacheltitels benötigt Symcon 9.1+.
- Das Mitteilungsziel wird mit `SelectInstance` ausgewählt. Das Formular schränkt die Auswahl soweit möglich auf vorhandene Visualisierungsmodule ein; zur Laufzeit wird zusätzlich `ModuleType = 6` geprüft.
- Die frühere `VehicleTile`-Webinhaltvariable bleibt beim Upgrade nur als versteckte Abwärtskompatibilität bestehen.
- Grün bedeutet erwarteter Zustand, Orange bedeutet Hinweis/Aufmerksamkeit. Rot wird nur bei echten Fehlern oder Störungen verwendet.
- Deutsch wird über `locale.json` lokalisiert; englische Texte dienen als Quellsprache.
- Die Modul-Dokumentation erklärt Installation, Konfiguration, Statusvariablen, Bedienung, PHP-Befehle, Rate-Limit und Einschränkungen.
- Die Modul-/Kachelarchitektur ist gestalterisch von `da8ter/TileVisu-Kachelsammlung` inspiriert; Quellcode und Grafikassets wurden nicht übernommen.

### Standortdaten

Standortdaten sind profilabhängig. Ohne Standortfreigabe im verwendeten MySkoda-Profil liefert die API keine Koordinaten; das Modul setzt Breitengrad und Längengrad dann auf `0.0`. Bei mehreren Profilen für dasselbe Fahrzeug muss die Freigabe je Profil separat aktiviert werden.
