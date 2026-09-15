# Changelog

## 1.2 - 2026-09-15

- erster Entwicklungsstand für fahrzeugspezifische MySkoda-Bilder
- neue Diagnose `MSKODA_DiagnoseVehicleImages()`
- Diagnose durchsucht die bereits geladene Fahrzeugantwort nach Bild-/Render-Hinweisen
- passende GET-Operationen aus der offiziellen Public-API-OpenAPI werden erkannt und mit maximal drei gezielten Probeabfragen getestet
- VIN wird in der Diagnoseausgabe maskiert
- noch keine Medienobjekte und keine fest verdrahteten Bildtypen; zunächst wird der reale API-Umfang des Fahrzeugs ermittelt

## 1.1 - 2026-09-14

- schreibbare Lade- und Klimawerte werden beim Absenden sofort lokal gesetzt
- Pending besteht nur noch während der laufenden HTTP-Befehlsanfrage
- die **Serverantwort** des Befehls entscheidet direkt über Erfolg oder Fehler; eine verzögerte Fahrzeugrückmeldung wird nicht mehr für die Befehlsbestätigung verwendet
- bei erfolgreicher 2xx-Antwort bleibt der gewünschte Wert gesetzt und Pending wird sofort beendet
- bei Fehlerantwort oder Übertragungsfehler wird sofort auf den vorherigen Wert zurückgerollt und Pending ebenfalls beendet
- `CommandStatus` zeigt bei Fehlern zusätzlich den tatsächlichen API-/Transportfehler aus `LastError`
- die zusätzliche Bestätigungsabfrage nach 60 Sekunden wurde entfernt
- zusätzliche Diagnosevariablen `PendingCommands` und `CommandStatus`
- `SetChargingLimit()` und `SetChargeMode()` verwenden dieselbe direkte Befehlslogik wie die Variablenaktionen
- Ladelimit-Darstellung auf 50 bis 100 % in 10-%-Schritten korrigiert
- `MSKODA_GetRemoteOperations()` liest die aktuelle API-Liste aus `vehicle.operations` und verwendet `vehicle.remoteOperations` nur noch als Fallback
- dokumentierte Prüfung von App-Funktionen, die im offiziellen Public-API-Vertrag 1.0.0 derzeit nicht verfügbar sind: Camping Mode, Klima-Timer/Abfahrtszeiten, intelligentes Heizen/Klimatisieren, Scheiben- und Sitzheizung mit Klima, Battery Care Mode, reduzierte AC-Ladeleistung und automatisches Entriegeln des AC-Ladekabels
- Produktbezeichnung in Dokumentation und technischen Kennungen auf **Symcon** vereinheitlicht

## 1.0 - 2026-09-06

- Initiale Veröffentlichung des MySkoda-Moduls für Symcon
- Fahrzeug-, Lade-, Klima-, Standort- und Diagnosedaten über stabile Variablen-Idents
- bewusst einfacher Objektbaum ausschließlich mit Modulvariablen, ohne Dummy-Instanzen, Kategorien oder Links
- deutsche Lokalisierung für Konfiguration, Variablennamen und Statusdarstellungen
- deutsche Darstellung für Lade- und Parkstatus bei unveränderten API-Rohwerten
- Remote-Steuerung für unterstützte Lade- und Klimafunktionen
- automatische Erkennung zusätzlicher, noch nicht integrierter OpenAPI-Funktionen über die Variable `NewApiFeatures`; neue Datenpunkte werden ausschließlich über Modulupdates ergänzt
- Rate-Limit-Behandlung, API-Key-Warnung und optionale Symcon-Mitteilungen
- Archivierung von Ladezustand, Ladelimit, Ladeleistung und Kilometerstand nur nach ausdrücklicher Benutzeraktivierung; spätere Archiveinstellungen bleiben unberührt
- Kilometerstand als Archiv-Zähler; ungültige Werte kleiner oder gleich 0 werden verworfen
- native Symcon-Darstellungen
