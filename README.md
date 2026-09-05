# MySkoda für IP-Symcon

IP-Symcon Bibliothek für die offizielle **MySkoda Public API**.

Die Bibliothek enthält das Gerätemodul [MySkoda](MySkoda/README.md). Eine Instanz repräsentiert genau ein Fahrzeug bzw. eine FIN.

## Funktionsumfang

- Offizielle MySkoda Public API mit `X-API-Key`
- übersichtliche Konfigurationsmaske mit klappbaren Bereichen für Verbindung, Visualisierung, Steuerung, Mitteilungen und erweiterte Optionen
- Konfiguration für FIN, API-Token, Abrufintervall und optionale S-PIN
- Bewusst kleiner Standard-Objektbaum
- **Native Symcon-Fahrzeugkachel direkt an der MySkoda-Instanz**
- Smartphone-optimierte Darstellung nach dem kompakten TileVisu-Prinzip
- Wählbare Fahrzeugoptik: **Automatisch, Enyaq, Elroq, Epiq oder Allgemein**
- Automatische Modellauswahl über die von MySkoda gelieferten Modell-/Spezifikationsdaten, mit manueller Übersteuerung
- Fahrzeugzustände für Türen, Fenster, Schiebedach, Licht, Kofferraum und Motorhaube werden direkt am Fahrzeug visualisiert; als Textstatus bleibt nur die Verriegelung
- 4-Balken-Batterie mit SOC-Farbverlauf von Rot über Orange und Hellgrün bis Dunkelgrün
- Laden, Ladelimit, Lademodus, Klima und Kilometerstand kompakt in der Kachel
- robuster Visu-Lifecycle: vollständiger Startzustand in der Kachel, instanzbezogener Cache und erneutes Senden der zwischengespeicherten Fahrzeugdaten nach Wiederanzeige/Scroll-Recycling **ohne zusätzliche MySkoda-API-Abfrage**
- optionaler ausgeblendeter Symcon-Kacheltitel bei Symcon 9.1 oder neuer; der Instanzname im Objektbaum bleibt unverändert
- Warnung 30 Tage vor Ablauf des API-Keys, optional mit Symcon-Mitteilung
- Mitteilungsziel wird per Instanzauswahl gewählt und als Visualisierungsinstanz geprüft
- Verbindungstest mit direkter Rückmeldung in der Konfiguration
- Optionale Detail- und Diagnosevariablen
- Laden und Klimatisierung über Symcon-Variablen steuerbar
- Standheizung und aktive Lüftung über Modulbefehle
- Berücksichtigung von Rate-Limit und `Retry-After`
- Neue Variablendarstellungen ab Symcon 8.x; **keine eigenen Legacy-Variablenprofile**
- Eigene neue Darstellungs-Vorlagen sind eindeutig mit `MySkoda.*` benannt
- Deutsch über `locale.json`, Englisch als Quellsprache des Moduls

## Voraussetzungen

- IP-Symcon **8.1 oder neuer**
- MySkoda API-Key
- 17-stellige FIN/VIN
- Optional: S-PIN für die Standheizung

Hinweis: Das programmgesteuerte Ausblenden des äußeren Symcon-Kacheltitels ist ab **Symcon 9.1** verfügbar. Auf älteren kompatiblen Versionen bleibt der Titel sichtbar und die MySkoda-Kachel reserviert dafür zusätzlichen Platz.

## Version

Aktueller Stand dieser Bibliothek: **1.9**

## API-Key in der MySkoda App erstellen

1. **MySkoda App** öffnen.
2. **Profil** auswählen.
3. **Smart Home** öffnen.
4. **Create Key** wählen.
5. Einen beliebigen Namen vergeben.
6. Die angezeigte **FIN/VIN** und den **API-Token** in die MySkoda-Instanz in Symcon eintragen.

Nach dem Übernehmen prüft das Modul die Verbindung und zeigt direkt an, ob Fahrzeugdaten erfolgreich empfangen wurden.

### Hinweis zu Standortdaten

Die MySkoda API liefert die Fahrzeugposition nur, wenn die Standortfreigabe im jeweiligen MySkoda-Profil aktiviert ist. Die Freigabe muss bei mehreren Profilen für dasselbe Fahrzeug je Profil separat erfolgen. Ohne Standortfreigabe schreibt das Modul `0.0 / 0.0` in die Koordinaten.

### Fahrzeugdarstellung

Unter **Fahrzeugdarstellung** stehen folgende Optionen zur Verfügung:

- Automatisch
- Enyaq
- Elroq
- Epiq
- Allgemein

Bei **Automatisch** wertet das Modul zuerst Modell-/Spezifikationsinformationen der MySkoda-Antwort aus. Die FIN allein wird nicht zur erzwungenen Unterscheidung zwischen Enyaq und Elroq verwendet, da beide Modellfamilien denselben Škoda-Fahrzeugtyp `NY` verwenden können. Wenn keine zuverlässige Zuordnung möglich ist, wird die allgemeine Darstellung verwendet und das Modell kann jederzeit manuell ausgewählt werden.

Details dazu stehen in [docs/VISUALIZATION.md](docs/VISUALIZATION.md).

## Installation

Repository im **Module Control** hinzufügen:

```text
https://github.com/taloriko/IPSymconMySkoda
```

Danach eine Instanz **MySkoda** anlegen und FIN, API-Token sowie das gewünschte Abrufintervall konfigurieren.

Seit Version 1.6 stellt die **Instanz selbst** eine native Kachel-Visualisierung bereit. Beim Hinzufügen der MySkoda-Instanz zur Kachel-Visualisierung ist kein manuelles Umschalten einer Stringvariable auf Webinhalt nötig. Eine alte `VehicleTile`-Variable aus Version 1.1-1.5 bleibt lediglich versteckt zur Abwärtskompatibilität bestehen.

Die vollständige Modul-Dokumentation befindet sich unter [MySkoda/README.md](MySkoda/README.md).

## Struktur

```text
IPSymconMySkoda/
├── library.json
├── MySkoda/
│   ├── module.php
│   ├── module.html
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

## Design-Inspiration

Die native Fahrzeugkachel orientiert sich an den Layout- und Modulprinzipien der [TileVisu-Kachelsammlung von da8ter](https://github.com/da8ter/TileVisu-Kachelsammlung): native Instanzkachel, eine eigene `module.html`, klare Informationshierarchie, kompakte Statusdarstellung und laufende Updates ohne zusätzliche Visualisierungsvariable.

**Es wurden keine Quelltexte oder Grafikdateien aus dem Projekt übernommen.** Die MySkoda-Datenlogik, die modellabhängigen Fahrzeug-SVGs, Batterieanzeige und HTML-/JavaScript-Umsetzung sind eigenständig.

## API-Dokumentation

Offizielle MySkoda Public API: <https://public.api.connect.skoda-auto.cz/docs>

## Hinweis

Dieses Projekt ist eine unabhängige Community-Integration und weder ein offizielles Produkt von Škoda Auto a.s. noch von Škoda Auto a.s. unterstützt.

## Lizenz

MIT-Lizenz, siehe [LICENSE](LICENSE).
