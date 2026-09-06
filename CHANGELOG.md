# Changelog

## 2.2 - 2026-09-06

- Statusvariablen unter thematischen Dummy-Instanzen gruppiert
- stabile Variablen-Idents und Objekt-IDs für Skripte und externe Visualisierungen beibehalten
- separate Dummy-Instanz `Diagramme` ergänzt
- kombinierten Ladeverlauf aus Ladezustand, Ladelimit und Ladeleistung bereitgestellt
- Ladezustand und Ladelimit auf linker Prozent-Achse, Ladeleistung auf rechter Leistungs-Achse
- Logging für `StateOfCharge`, `TargetSOC` und `ChargePower` wird nur aktiviert, wenn es noch nicht aktiv ist
- bestehende Archiveinstellungen und Archivdaten werden nicht verändert oder gelöscht
- bestehende Lade-Diagrammkonfiguration wird bei Updates nicht überschrieben

## 2.1 - 2026-09-06

- Statusvariablen funktional nach Fahrzeug, Fahrzeugstatus, Laden, Klimatisierung, Standort sowie API/Diagnose sortiert
- native Symcon-/Font-Awesome-Icons für passende Datenpunkte ergänzt
- Ladestatus wird über eine native String-Wertanzeige verständlich lokalisiert, der originale API-Wert bleibt erhalten
- bekannte Ladezustände wie `CONNECT_CABLE`, `CHARGING` und `CHARGING_INTERRUPTED` erhalten lesbare Beschriftungen
- API- und Diagnosewerte stehen gesammelt am Ende der Instanz

## 2.0 - 2026-09-05

- Fahrzeugstatus über die offizielle MySkoda Public API
- native Symcon-Datenpunkte für Fahrzeug-, Lade- und Klimastatus
- Remote-Steuerung für unterstützte Lade- und Klimafunktionen
- Ladelimit und Lademodus
- Standheizung und aktive Lüftung über öffentliche Modulmethoden
- optionale Detail- und Diagnosevariablen
- Standortdaten mit Rücksetzung auf `0.0 / 0.0`, wenn keine Koordinaten geliefert werden
- Rate-Limit- und `Retry-After`-Behandlung
- API-Key-Ablaufwarnung 30 Tage vor Ablauf
- auswählbares und validiertes Symcon-Mitteilungsziel
- native Symcon-8.x-Darstellungen ohne Legacy-Variablenprofile
- deutsche Lokalisierung mit englischer Quellsprache
- CI-Prüfung für PHP-Syntax, JSON und Modulstruktur
