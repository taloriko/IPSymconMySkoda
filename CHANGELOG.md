# Changelog

## Initiale Veröffentlichung

- Anbindung eines Škoda-Fahrzeugs je Instanz über die offizielle MyŠkoda Public API mit FIN/VIN und API-Key.
- Fahrzeug-, Lade-, Kraftstoff-, Klima-, Standort- und Statusdaten als Symcon-Variablen mit festen Idents für bekannte Datenpunkte und deutschen Anzeigen.
- Fahrzeugbezogene Variablen werden angelegt, sobald das zugehörige API-Feld vorhanden und nicht `null` ist. Zusätzliche skalare API-Felder werden automatisch als weitere Lesewerte erfasst.
- Ladezustand, Reichweiten, Ladeleistung, Ladegeschwindigkeit, Ladelimit, Lademodi sowie Anschluss- und Verriegelungszustand des Ladesteckers.
- Tankfüllstand, gemeldeter SoC des primären Antriebs, Kraftstoff- und Gesamtreichweite sowie Fahrzeug- und Antriebstypen, soweit vom Fahrzeug geliefert.
- Klimastatus, Solltemperatur und geschätzter Zeitpunkt zum Erreichen der Zieltemperatur. Bei ausgeschalteter Klimatisierung wird eine gewählte Solltemperatur bis zum nächsten Start lokal vorgemerkt.
- Zyklischer Fahrzeugabruf mit Berücksichtigung von API-Kontingent und Wartezeiten. Fehlende oder mit `null` gelieferte Fahrzeugfelder lassen vorhandene Werte im Regelfall unverändert und sind keine Bestätigung eines aktuellen Fahrzeugzustands.
- Remote-Steuerung von Laden, Ladelimit, Lademodus und Klimatisierung. Variablenaktionen zeigen den gewünschten Wert während der Übertragung und stellen bei Ablehnung den vorherigen Wert wieder her.
- Standheizung als bedienbare Variable bei geliefertem Status, Remote-Freigabe, hinterlegter S-PIN und angebotenen Start-/Stopp-Operationen. Der Start überträgt die S-PIN und nur bei entsprechend gelieferten Fahrzeugdaten eine Zieltemperatur.
- PHP-Funktionen zum Lesen und Übertragen von Ladeprofilen sowie zum Starten und Stoppen von Standheizung und aktiver Lüftung. Die Standheizungsdauer ist ein Lesewert; die PHP-Parameter für Dauer und Modus verändern den Startbefehl nicht.
- Optionale Diagnosevariablen für API-Key-Gültigkeit, Restkontingent, Teilfehler, Befehlsstatus und API-Informationen. Normale Fahrzeugdaten und Bedienung sind von dieser Option unabhängig.
- Lokales Fahrzeugbild sowie bis zu fünf weitere verfügbare Außen- und Innenansichten als Bildmedien unterhalb der Instanz.
- Lokale FIN-/VIN-Interpretation mit 17 optionalen Informationsvariablen; die Auswertung ist keine offizielle Fahrzeugstammdatenquelle.
- Optionale einmalige Archivierung von Ladezustand, Ladelimit, Ladeleistung und Kilometerstand, wenn alle vier Variablen vorhanden sind. Optionale Mitteilungen zum Ablauf des API-Keys.
- Benutzerdiagnose über Instanzstatus, gespeicherte Fahrzeugantwort und Symcon-Debugausgaben. Die API-Annahme eines Befehls ist keine Bestätigung seiner Ausführung im Fahrzeug.
- Vorhandene Variablen werden nicht automatisch gelöscht oder auf einen anderen Typ umgestellt. Die reguläre Registrierung erhält ihre Metadaten; die begrenzte Anpassung generischer API-Namen und Darstellungen ist in der Modul-Dokumentation beschrieben.
- Automatisierte Struktur-, Schnittstellen- und Laufzeitprüfungen sowie eine getrennte Matrix praktischer Fahrzeugnachweise.
