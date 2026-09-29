# Changelog

## 1.7 - 2026-09-29

- Fahrzeugvariablen werden aus der tatsächlich gelieferten API-Antwort erzeugt.
- Neue, bisher unbekannte API-Felder werden automatisch als neutrale `API_...`-Variablen ergänzt.
- Fehlt ein Feld bei einem späteren Abruf, bleibt eine bereits vorhandene Variable mit ihrem letzten Wert erhalten.
- Bekannte Felder behalten lesbare Namen und Darstellungen; neu bestätigt sind Tankfüllstand, primäre Reichweite, Gesamtreichweite und Standheizungsdauer.
- EV-spezifische Variablen werden bei Fahrzeugen ohne entsprechende API-Daten nicht mehr vorsorglich angelegt.
- Remote-Aktionen werden nur an vorhandenen Variablen und passend zu den vom Fahrzeug gemeldeten Operationen aktiviert.
- Modulversion auf 1.7 gesetzt.

## 1.6 - 2026-09-28

- Public API 1.1: `plugConnectionState` und `plugLockState` als neue String-Variablen ergänzt.
- Fehlende Steckerzustände werden als `UNKNOWN` dargestellt.
- Fehlender oder unbekannter Ladestatus setzt den Bedienwert `Charging` nicht mehr automatisch auf Aus.
- README-Dateien, geprüfte Variablenliste und automatisierte Tests ergänzt.
- Modulversion auf 1.6 gesetzt, damit die neue Version nach dem Merge in `main` vom Symcon Module Store erkannt werden kann.

## Initiale Veröffentlichung

- Anbindung eines Škoda-Fahrzeugs über die offizielle MyŠkoda Public API.
- Fahrzeug-, Lade-, Klima-, Standort- und Statusdaten als Symcon-Variablen mit festen Idents und deutschen Anzeigen.
- Zyklischer Fahrzeugabruf mit Berücksichtigung von API-Kontingent und Wartezeiten.
- Remote-Steuerung der unterstützten Lade- und Klimafunktionen mit Befehlsstatus und Rücksetzen abgelehnter Befehle.
- Lokales Fahrzeugbild und lokale FIN-/VIN-Interpretation mit optionalen Informationsvariablen.
- Optionale Archivierung ausgewählter Fahrzeugwerte und Mitteilungen zum Ablauf des API-Keys.
- Benutzerdiagnose über Instanzstatus, gespeicherte Fahrzeugantwort und Symcon-Debugausgaben.
- Aussagekräftige Variablenwerte für nicht gelieferte API-Informationen und ausdrücklich gemeldete Dienstzustände.
- Einmalige Einrichtung von Variablennamen, Positionen, Icons und Darstellungen; spätere Benutzeranpassungen bleiben erhalten.
