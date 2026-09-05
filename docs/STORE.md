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

**Versionsinformation 1.2**

```text
Kompakte Smartphone-Fahrzeugkachel, direkte Verbindungsrückmeldung bei der Einrichtung sowie API-Key-Ablaufwarnung mit optionaler Symcon-Push-Mitteilung.
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

**Version information 1.2**

```text
Compact smartphone vehicle tile, direct connection feedback during setup, and API-key expiry warning with optional Symcon push notification.
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
- Es werden keine Legacy-Variablenprofile erzeugt.
- Deutsch wird über `locale.json` lokalisiert; englische Texte dienen als Quellsprache.
- Die Modul-Dokumentation erklärt Installation, Konfiguration, Statusvariablen, Bedienung, PHP-Befehle, Rate-Limit und Einschränkungen.
