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
Bindet Fahrzeuge über die offizielle MySkoda Public API an IP-Symcon an. Das Modul stellt einen kompakten Fahrzeugstatus bereit und unterstützt – sofern vom Fahrzeug angeboten – Laden, Ladelimit, Lademodus, Klimatisierung, Standheizung und aktive Lüftung.
```

**Versionsinformation 1.3**

```text
Klarere Ja/Nein-Statusanzeigen mit MySkoda-eigenen Profilen, grün/orange Farblogik für Fahrzeugzustände sowie dokumentiertes Standortverhalten mit 0/0 bei fehlender Profilfreigabe.
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
Connects vehicles to IP-Symcon through the official MySkoda Public API. The module exposes a compact vehicle status and, where supported by the vehicle, controls charging, charge limit, charging mode, air conditioning, auxiliary heating and active ventilation.
```

**Version information 1.3**

```text
Clear Yes/No vehicle-state profiles with green/orange semantics and documented location behavior, including 0/0 coordinates when location sharing is unavailable for the profile.
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
- Eigene Variablenprofile werden nur für Boolean-Zustände angelegt und eindeutig mit `MySkoda.*` benannt; andere Werte nutzen weiterhin Symcon-Standarddarstellungen bzw. neue Präsentationen.
- Deutsch wird über `locale.json` lokalisiert; englische Texte dienen als Quellsprache.
- Die Modul-Dokumentation erklärt Installation, Konfiguration, Statusvariablen, Bedienung, PHP-Befehle, Rate-Limit und Einschränkungen.

### Standortdaten

Standortdaten sind profilabhängig. Ohne Standortfreigabe im verwendeten MySkoda-Profil liefert die API keine Koordinaten; das Modul setzt Breitengrad und Längengrad dann auf `0.0`. Bei mehreren Profilen für dasselbe Fahrzeug muss die Freigabe je Profil separat aktiviert werden.
