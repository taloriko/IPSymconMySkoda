# Changelog

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
