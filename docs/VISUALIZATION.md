# MySkoda Visualisierung

## Native Symcon-Kachel

Seit Version 1.6 stellt die MySkoda-Instanz selbst eine native Tile-Visualisierung bereit. Die Kachel muss deshalb nicht über eine zusätzliche HTML-Variable konfiguriert werden.

Version 1.7 verwendet einen smartphone-orientierten Aufbau:

- Fahrzeugname, Reichweite und Alter der letzten Abfrage im Kopf
- Modellabhängige Fahrzeugansicht von oben
- SOC als 4-Balken-Batterie im Fahrzeug
- ein gebündelter Ladebereich mit Steckerzustand, Leistung, Zeit bis voll, Ladelimit und Lademodus
- Klima und Kilometerstand direkt darunter
- Statuszeilen für Verriegelung, Türen, Fenster, Schiebedach, Licht, Kofferraum und Motorhaube

Die Gestaltung orientiert sich an den kompakten Layout-Prinzipien der öffentlichen [TileVisu-Kachelsammlung von da8ter](https://github.com/da8ter/TileVisu-Kachelsammlung). Quellcode und Grafikassets der Kachelsammlung werden nicht übernommen; HTML, CSS und Fahrzeug-SVGs sind eigenständig umgesetzt.

## Fahrzeugdarstellung

In der Instanz kann unter **Fahrzeugdarstellung** gewählt werden:

- Automatisch
- Enyaq
- Elroq
- Epiq
- Allgemein

### Automatische Auswahl

Die automatische Erkennung wertet in dieser Reihenfolge aus:

1. Modell-/Spezifikationsfelder aus der MySkoda-Antwort
2. bekannte `systemModelId`-Präfixe
3. als letzte schwache Hilfe den in MySkoda vergebenen Fahrzeugnamen
4. falls keine eindeutige Zuordnung möglich ist: allgemeine Fahrzeugdarstellung

Die FIN allein wird absichtlich **nicht** zur erzwungenen Unterscheidung zwischen Enyaq und Elroq verwendet. Beide Modellfamilien können den Škoda-Fahrzeugtyp `NY` verwenden. Dadurch wäre eine reine FIN-Heuristik nicht zuverlässig genug. Die manuelle Auswahl überschreibt die automatische Erkennung jederzeit.

## SOC-Batterie

Die Batterie besitzt vier Segmente. Die Farbe wird kontinuierlich zwischen folgenden Referenzpunkten berechnet:

- bis 10 %: rot
- 25 %: orange
- 80 %: hellgrün
- über 80 %: zunehmend dunkelgrün

Der Prozentwert und das konfigurierte Ladelimit werden direkt im Fahrzeug angezeigt.

## Statusfarben

- Grün: erwarteter / sicherer Zustand
- Orange: Hinweis oder Aufmerksamkeit erforderlich
- Rot: ausschließlich tatsächliche Fehler oder Störungen

Beispiele:

- Verriegelt = grün
- Türen geschlossen = grün
- Fenster geschlossen = grün
- Schiebedach geschlossen = grün
- Licht aus = grün
- Kofferraum geschlossen = grün
- Motorhaube geschlossen = grün

Fehlt ein Status in der API, wird er nicht als vermeintlich grüner Zustand dargestellt.

## Schiebedach

Der Schiebedachstatus wird aus den von MySkoda gelieferten Statusdaten gelesen. Ist das Feld vorhanden, erscheint die Statuszeile in der Kachel und ein geöffnetes Schiebedach wird orange hervorgehoben. Liefert das Fahrzeug keinen Schiebedachstatus, wird keine falsche Aussage erzeugt.
