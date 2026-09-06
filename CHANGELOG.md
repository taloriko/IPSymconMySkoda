# Changelog

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
