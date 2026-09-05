# Architektur

## Ziel

Eine MySkoda-Instanz repräsentiert genau ein Fahrzeug bzw. eine FIN.

## Symcon-Struktur

Das Repository folgt der Symcon-Bibliotheksstruktur: `library.json` liegt im Repository-Root. Der Modulordner `MySkoda` enthält `module.php`, `module.html`, `module.json`, `form.json` und `locale.json`.

## Datenmodell

Der komplette Fahrzeug-Response wird intern als Instanzattribut gespeichert. Der Objektbaum enthält nur eine kleine, stabile Auswahl alltagstauglicher Variablen. Detailvariablen sind optional.

## Darstellungen

Das Modul setzt Symcon 8.1+ voraus und nutzt `IPSModuleStrict` sowie die neuen Variablendarstellungen. Vorhandene Symcon-Standardvorlagen werden bevorzugt.

Passive Boolean-Zustände verwenden die neue Darstellung **Wertanzeige** mit Modulvorlagen im Namensraum `MySkoda.*`. Bedienbare Boolean-Werte wie Laden und Klima verwenden bei aktiver Remote-Steuerung die Darstellung **Schalter**. Eigene Legacy-Variablenprofile werden nicht angelegt.

Die Farblogik ist semantisch: Grün bedeutet erwarteter Zustand, Orange bedeutet Hinweis/Aufmerksamkeit. Rot ist nur echten Fehlern oder Störungen vorbehalten.

## Native Tile-Visualisierung ab 1.6

Ab Version 1.6 besitzt die MySkoda-Instanz selbst eine native Symcon-Kachel:

- `SetVisualizationType(1)` aktiviert die HTML-Visualisierung an der Instanz.
- `GetVisualizationTile()` liefert die statische `MySkoda/module.html` plus Initialzustand.
- `UpdateVisualizationValue()` überträgt Statusänderungen laufend an `handleMessage()` im Browser.
- Der Visual-Timer aktualisiert unter anderem das Alter der letzten Abfrage einmal pro Minute, ohne dafür eine zusätzliche Fahrzeug-API-Abfrage auszulösen.

`SetVisualizationType(1)` wird sowohl in `Create()` als auch in `ApplyChanges()` gesetzt. Damit erhalten auch bestehende Instanzen nach einem Modulupdate die native Kachel, ohne neu angelegt werden zu müssen.

Die bis Version 1.5 verwendete `VehicleTile`-Stringvariable bleibt nur für bestehende Visualisierungen als versteckte Kompatibilität bestehen. Neue Visualisierungen verwenden die Instanz direkt.

### Kachel-Datenfluss

`VisualizationV16Trait.php` erzeugt aus dem letzten Fahrzeug-Response einen kompakten JSON-Zustand. `module.html` enthält ausschließlich die Darstellung und setzt diesen Zustand per JavaScript um. Dadurch bleiben API-/Fahrzeuglogik und UI getrennt.

Die SOC-Batterie wird als vier Segmente im Fahrzeug-SVG dargestellt. Die Farbe wird in JavaScript zwischen festgelegten Referenzpunkten interpoliert: Rot bis 10 %, Orange bei 25 %, Hellgrün bei 80 % und darüber bis Dunkelgrün.

### Design-Inspiration

Die Modulstruktur und die kompakte Kachelaufteilung orientieren sich an der öffentlichen [TileVisu-Kachelsammlung von da8ter](https://github.com/da8ter/TileVisu-Kachelsammlung), insbesondere am Muster aus `module.html`, `GetVisualizationTile()` und `UpdateVisualizationValue()`.

Es werden keine Quelltexte oder Grafikassets aus dem Projekt übernommen. Fahrzeug-SVG, Datenmodell und HTML-/JavaScript-Code sind eigenständig.

## Migration von 1.3

Version 1.3 hatte kurzzeitig eigene `MySkoda.YesNo.*`-Legacy-Profile verwendet. Version 1.4 entfernt diese Zuweisungen beim Anwenden der Instanz wieder und löscht die Profile, sofern sie nirgendwo sonst verwendet werden. Auch die frühere `~HTMLBox`-Zuweisung der Fahrzeugkachel wird entfernt.

## Rate-Limit

Das Modul liest `RateLimit-Limit`, `RateLimit-Remaining`, `RateLimit-Reset` und `Retry-After`. Automatische Abrufe werden mit einer kleinen Reserve gestoppt, damit Remote-Befehle möglich bleiben.

## Remote-Befehle

Start/Stop für Laden und Klima sowie Standheizung/Lüftung verwenden die Public-API-Pfade. Neuere Ladeoperationen für Limit, Modus und Ladeprofil werden über die aktuelle OpenAPI-Definition und deren Request-Schemas aufgelöst.

## Lokalisierung

Englisch ist die Quellsprache der Modultexte. `MySkoda/locale.json` enthält die deutschen Übersetzungen für Konfigurationsmaske, Variablennamen und relevante Benutzermeldungen.

## Source layout

`MySkoda/module.php` bleibt bewusst klein und lädt thematisch getrennte Traits aus `MySkoda/src/`:

- `CoreTrait.php` – Lebenszyklus, Aktionen und öffentliche Modulmethoden
- `BootstrapV16Trait.php` – native Visualisierung für neue und bestehende Instanzen
- `VariablesTrait.php` – Variablen und Datenübernahme
- `PresentationTrait.php` – neue Symcon-Darstellungsvorlagen
- `ApiTrait.php` – HTTP, Rate-Limit und Fehlerbehandlung
- `OpenApiTrait.php` – dynamische OpenAPI-Auswertung
- `VisualizationTrait.php` – gemeinsame Visualisierungs-/Formatierhilfen
- `VisualizationV15Trait.php` – Kompatibilitätsdarstellung für die frühere `VehicleTile`-Variable
- `VisualizationV16Trait.php` – Datenmodell und Updates für die native Instanz-Kachel
- `NotificationTrait.php` – API-Key-Ablaufwarnung und optionale Symcon-Push-Mitteilungen
- `HelpersTrait.php` – allgemeine Hilfsfunktionen

Die Aufteilung ist rein intern. Prefix, Modul-ID und öffentliche `MSKODA_*`-Funktionen bleiben unverändert.

## Verbindungsrückmeldung

Die Konfiguration wird über `GetConfigurationForm()` dynamisch aus der `form.json` aufgebaut. Beim erstmaligen Eintragen oder Ändern von FIN/API-Token wird die Verbindung direkt geprüft. Der Status wird als verständliche Rückmeldung im offenen Instanzformular angezeigt und zusätzlich über den normalen Instanzstatus abgebildet.

## API-Key Ablauf

Das Ablaufdatum wird aus `X-API-Key-Expires-At` übernommen. `ApiKeyWarning` wird bei höchstens 30 Tagen Restlaufzeit aktiv. Eine optionale Push-Mitteilung kann über eine konfigurierte Kachel-Visualisierungs- oder WebFront-Instanz gesendet werden. Fehlgeschlagene Sendeversuche werden höchstens einmal täglich wiederholt.

## Standortdaten

Die Fahrzeugposition wird nur verarbeitet, wenn die MySkoda API sie für das verwendete Profil liefert. Die Standortfreigabe ist profilbezogen und muss bei mehreren Profilen für dasselbe Fahrzeug je Profil separat aktiviert werden. Fehlen Koordinaten, schreibt das Modul `0.0 / 0.0`, damit keine alten Standortdaten bestehen bleiben.
