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

**Versionsinformation 1.6**

```text
Native Fahrzeugkachel direkt an der MySkoda-Instanz, 4-Balken-SOC-Batterie mit Farbverlauf, kompaktere Lade-/Klimaansicht und weiterhin ausschließlich neue Symcon-8.x-Darstellungen ohne Legacy-Profile.
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

**Version information 1.6**

```text
Native vehicle tile directly on the MySkoda instance, four-segment SOC battery with colour gradient, denser charging/climate layout and only modern Symcon 8.x presentations without legacy profiles.
```

**Documentation link**

```text
https://github.com/taloriko/IPSymconMySkoda/blob/main/MySkoda/README.md
```

## Review-Hinweise

- Der sichtbare Modulname bleibt `MySkoda`; `IPSymcon`, `IPS` oder ähnliche Zusätze werden nicht als Store-Name verwendet.
- Die Instanz erstellt ausschließlich eigene Statusvariablen unterhalb der Instanz.
- Der periodische Abruf nutzt `RegisterTimer`; es werden keine externen Ereignisse erzeugt.
- Konfigurierbare Eigenschaften werden über `form.json` bereitgestellt.
- Das Modul verwendet ab Symcon 8.1 `IPSModuleStrict`.
- Das Modul legt keine eigenen Legacy-Variablenprofile an. Passive Boolean-Zustände verwenden native Symcon-Wertanzeigen; bedienbare Boolean-Werte verwenden bei aktiver Remote-Steuerung native Schalter.
- Ab Version 1.6 stellt die Instanz selbst eine native HTML-Kachel über `SetVisualizationType(1)` / `GetVisualizationTile()` bereit. Die Darstellung liegt in `MySkoda/module.html` und wird über `UpdateVisualizationValue()` aktualisiert.
- Die frühere `VehicleTile`-Webinhaltvariable bleibt beim Upgrade nur als versteckte Abwärtskompatibilität bestehen.
- Grün bedeutet erwarteter Zustand, Orange bedeutet Hinweis/Aufmerksamkeit. Rot wird nur bei echten Fehlern oder Störungen verwendet.
- Deutsch wird über `locale.json` lokalisiert; englische Texte dienen als Quellsprache.
- Die Modul-Dokumentation erklärt Installation, Konfiguration, Statusvariablen, Bedienung, PHP-Befehle, Rate-Limit und Einschränkungen.
- Die Modul-/Kachelarchitektur ist gestalterisch von `da8ter/TileVisu-Kachelsammlung` inspiriert; Quellcode und Grafikassets wurden nicht übernommen.

### Standortdaten

Standortdaten sind profilabhängig. Ohne Standortfreigabe im verwendeten MySkoda-Profil liefert die API keine Koordinaten; das Modul setzt Breitengrad und Längengrad dann auf `0.0`. Bei mehreren Profilen für dasselbe Fahrzeug muss die Freigabe je Profil separat aktiviert werden.
