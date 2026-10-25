# Changelog

## Initiale Veröffentlichung

- Interner Versandmerker für deaktivierte Fahrzeugdienste: nach erfolgreicher Info-Mitteilung gesetzt, erst bei tatsächlicher Wiederkehr aller betroffenen Fahrzeugdaten zurückgesetzt. Vor dem Update bestehende Fehler werden erkannt.
- Info-Mitteilungen zu Škoda-seitig deaktivierten Fahrzeugdiensten werden unabhängig von der API-Key-Ablaufwarnung versendet und beim Modulupdate auch anhand einer gültigen gespeicherten Fahrzeugantwort geprüft.
- Erkennung von Škoda-seitig deaktivierten Fahrzeugdiensten anhand der API-Teilfehler: Warnung direkt beim API-Token und optionale einmalige Info-Mitteilung über die konfigurierte Visualisierung. Die betroffenen Werte werden nicht mehr aktualisiert; ein abgelaufener Car Connect-Fernzugriff wird nur als mögliche Ursache genannt.

- Die Instanzkonfiguration bietet unabhängige Schalter für Diagnose, Ladeorte und FIN-Daten inklusive Variablen-Richtwerten und konkreter Ladeort-Vorschau.
- Kopfzeile mit dynamischer Instanzbezeichnung, geliefertem Fahrzeugnamen und Kennzeichen (ohne FIN); passendes vorhandenes Fahrzeugbild linksbündig mit 240 px Breite (Basisbild zuerst, sonst verfügbare Ansicht).

- Anbindung eines Škoda-Fahrzeugs je Instanz über die offizielle MyŠkoda Public API mit FIN/VIN und API-Key.
- Fahrzeug-, Lade-, Kraftstoff-, Klima-, Standort- und Statusdaten als Symcon-Variablen mit festen Idents für bekannte Datenpunkte und deutschen Anzeigen.
- Fahrzeugbezogene Variablen werden angelegt, sobald das zugehörige API-Feld vorhanden und nicht `null` ist. Zusätzliche skalare API-Felder werden automatisch als weitere Lesewerte erfasst.
- Ladezustand, Reichweiten, Ladeleistung, Ladegeschwindigkeit, Ladelimit, Lademodi sowie Anschluss- und Verriegelungszustand des Ladesteckers.
- Optional erweiterte Ladeortvariablen für gespeicherte Profile, Einstellungen, Ladezeiten und Timer mit eindeutigen Idents und Symcon-Icons/Darstellungen; die Erstanlage wird separat über `ShowChargingLocations` gesteuert.
- Ladeortvariablen verwenden direkte Idents wie `ChargingProfile1Name`; andere dynamische API-Idents bleiben unverändert.
- Kompakte, mobilgeeignete Anzeigenamen für neue Ladeort-Variablen: kurze Ladeortkennung zuerst (z. B. `LO1 · Ziel-SoC`), bevorzugte Ladezeitfenster als `Zeit1` statt `Z1` (z. B. `LO1 · Start Zeit1`) und Timer ausgeschrieben mit `Timer1`. Bestehende Objektnamen bleiben erhalten; Idents und Variablentypen unverändert.
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
