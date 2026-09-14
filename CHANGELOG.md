# Changelog

## 1.1 - 2026-09-14

- optimistische Anzeige für schreibbare Lade- und Klimawerte
- getrennte Pending-Zustände für mehrere gleichzeitig ausstehende Befehle
- angenommene Befehle bleiben sichtbar, bis der neue Fahrzeugzustand über die API bestätigt wurde
- klare Ablehnungen rollen sofort auf den vorherigen Wert zurück
- Transportfehler, HTTP 408 und 5xx werden als unklar behandelt und nicht vorschnell zurückgerollt
- einmalige vorgezogene Bestätigungsabfrage etwa 60 Sekunden nach einem angenommenen oder unklaren Befehl
- bei zwei erfolgreichen Fahrzeugantworten mit weiterhin abweichendem Wert wird das Pending beendet und der tatsächliche API-Wert wiederhergestellt
- zusätzliche Diagnosevariablen `PendingCommands` und `CommandStatus`
- `SetChargingLimit()` und `SetChargeMode()` verwenden dieselbe Pending-/Bestätigungslogik wie die Variablenaktionen

## 1.0 - 2026-09-06

- Initiale Veröffentlichung des MySkoda-Moduls für IP-Symcon
- Fahrzeug-, Lade-, Klima-, Standort- und Diagnosedaten über stabile Variablen-Idents
- bewusst einfacher Objektbaum ausschließlich mit Modulvariablen, ohne Dummy-Instanzen, Kategorien oder Links
- deutsche Lokalisierung für Konfiguration, Variablennamen und Statusdarstellungen
- deutsche Darstellung für Lade- und Parkstatus bei unveränderten API-Rohwerten
- Remote-Steuerung für unterstützte Lade- und Klimafunktionen
- automatische Erkennung zusätzlicher, noch nicht integrierter OpenAPI-Funktionen über die Variable `NewApiFeatures`; neue Datenpunkte werden ausschließlich über Modulupdates ergänzt
- Rate-Limit-Behandlung, API-Key-Warnung und optionale Symcon-Mitteilungen
- Archivierung von Ladezustand, Ladelimit, Ladeleistung und Kilometerstand nur nach ausdrücklicher Benutzeraktivierung; spätere Archiveinstellungen bleiben unberührt
- Kilometerstand als Archiv-Zähler; ungültige Werte kleiner oder gleich 0 werden verworfen
- native IP-Symcon-Darstellungen
