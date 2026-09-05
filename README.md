# MySkoda für IP-Symcon

IP-Symcon Bibliothek für die offizielle **MySkoda Public API**.

Die Bibliothek enthält das Gerätemodul [MySkoda](MySkoda/README.md). Eine Instanz repräsentiert genau ein Fahrzeug bzw. eine FIN.

## Funktionsumfang

- Offizielle MySkoda Public API mit `X-API-Key`
- Konfigurationsmaske für FIN, API-Token, Abrufintervall und optionale S-PIN
- Bewusst kleiner Standard-Objektbaum
- Optionale Detail- und Diagnosevariablen
- Laden und Klimatisierung über Symcon-Variablen steuerbar
- Ladelimit und Lademodus steuerbar
- Standheizung und aktive Lüftung über Modulbefehle
- Berücksichtigung von Rate-Limit und `Retry-After`
- Neue Variablendarstellungen ab Symcon 8.x und Symcon-Standardvorlagen
- **Keine Legacy-Variablenprofile**
- Deutsch über `locale.json`, Englisch als Quellsprache des Moduls

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- MySkoda API-Key
- 17-stellige FIN/VIN
- Optional: S-PIN für die Standheizung

## Installation

Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Danach eine Instanz **MySkoda** anlegen und FIN, API-Token sowie das gewünschte Abrufintervall konfigurieren.

Die vollständige Modul-Dokumentation befindet sich unter [MySkoda/README.md](MySkoda/README.md).

## Struktur

```text
IPSymconMySkoda/
├── library.json
├── MySkoda/
│   ├── module.php
│   ├── module.json
│   ├── form.json
│   ├── locale.json
│   └── README.md
├── docs/
├── tests/
└── .github/
```

## Symcon Module Store

Das Repository folgt der von Symcon dokumentierten Bibliotheks- und Modulstruktur. Für eine spätere Einreichung im Module Store ist der sichtbare Modulname **MySkoda** vorgesehen. Der Repository-Name darf davon abweichen.

Die vorbereiteten Store-Texte, Versionsinformation und der Dokumentationslink befinden sich unter [docs/STORE.md](docs/STORE.md).

Offizielle Symcon-SDK-Referenzen:

- [Bibliotheken](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/bibliotheken/)
- [Module](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/module/)
- [Struktur](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/struktur/)
- [Lokalisierungen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/lokalisierungen/)
- [Module Store einreichen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/)

## API-Dokumentation

Offizielle MySkoda Public API: <https://public.api.connect.skoda-auto.cz/docs>

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

MIT-Lizenz, siehe [LICENSE](LICENSE).
