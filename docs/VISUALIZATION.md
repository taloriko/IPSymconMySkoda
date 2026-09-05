# MySkoda Visualisierung

## Native Symcon-Kachel

Seit Version 1.6 stellt die MySkoda-Instanz selbst eine native Tile-Visualisierung bereit. Die Kachel muss deshalb nicht über eine zusätzliche HTML-Variable konfiguriert werden.

Version **1.8** verwendet einen smartphone-orientierten, bewusst kompakten Aufbau:

- Fahrzeugname, Reichweite und Alter der letzten Abfrage im Kopf
- modellabhängige Fahrzeugansicht von oben
- SOC als 4-Balken-Batterie im Fahrzeug
- ein gebündelter Ladebereich mit Steckerzustand, Leistung, Zeit bis voll, Ladelimit und Lademodus
- Klima und Kilometerstand in einer kompakten, umbrechungsarmen Zeile
- nur noch **Verriegelt / Entriegelt** als eigener Textstatus
- Türen, Fenster, Schiebedach, Licht, Kofferraum und Motorhaube werden direkt am Fahrzeug hervorgehoben

Die Gestaltung orientiert sich an den kompakten Layout- und Modulprinzipien der öffentlichen [TileVisu-Kachelsammlung von da8ter](https://github.com/da8ter/TileVisu-Kachelsammlung). Quellcode und Grafikassets der Kachelsammlung werden nicht übernommen; HTML, CSS und Fahrzeug-SVGs sind eigenständig umgesetzt.

## Verhalten beim Scrollen und Wiederanzeigen

Die eingebettete HTML-Kachel scrollt ab Version 1.8 nicht mehr selbst. Das Scrollen übernimmt ausschließlich die Symcon-Visualisierung.

Damit beim Scrollen, beim Wechsel zwischen Seiten oder bei Größenänderungen keine Werte verloren gehen, hält die Kachel den zuletzt empfangenen vollständigen Zustand clientseitig vor und rendert ihn erneut bei:

- Wiederanzeigen der Seite (`pageshow`)
- Rückkehr in den Vordergrund
- Größenänderungen
- Sichtbarkeitswechsel
- Änderungen der verfügbaren Kachelgröße

Zusätzlich erfolgt regelmäßig ein lokales Re-Render. Dafür wird **keine zusätzliche MySkoda-API-Abfrage** ausgelöst.

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

## Fahrzeugzustände

Die Kachel soll im Normalzustand möglichst ruhig und eindeutig wirken. Deshalb werden die Einzelzustände ab Version 1.8 nicht mehr als lange Liste unterhalb des Fahrzeugs wiederholt.

- Verriegelt: Fahrzeugkontur grün und eigener Textstatus **Fahrzeug verriegelt**
- Entriegelt: Fahrzeugkontur und Verriegelungsstatus orange
- Tür offen: zugehörige Türmarkierung orange
- Fenster offen: Fensterbereich orange
- Schiebedach offen: Dachbereich orange
- Licht an: Leuchten orange
- Kofferraum offen: Heckmarkierung orange
- Motorhaube offen: Frontmarkierung orange

Ein geschlossener bzw. ausgeschalteter Zustand bleibt am Fahrzeug neutral; die grüne Fahrzeugkontur und der grüne Verriegelungsstatus zeigen den normalen abgestellten Zustand. **Orange bedeutet Hinweis/Aufmerksamkeit. Rot ist tatsächlichen Fehlern oder Störungen vorbehalten.**

Fehlt ein Status in der API, wird er nicht als vermeintlich grüner Zustand dargestellt.

## Schiebedach

Der Schiebedachstatus wird aus den von MySkoda gelieferten Statusdaten gelesen. Ist das Feld vorhanden und das Schiebedach geöffnet, wird der Dachbereich orange hervorgehoben. Liefert das Fahrzeug keinen Schiebedachstatus, wird keine falsche Aussage erzeugt.
