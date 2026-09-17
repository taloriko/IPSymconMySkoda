# Changelog

## 1.5 - 2026-09-17

- Instanzkonfiguration für den normalen Benutzerbetrieb aufgeräumt; Test- und Entwickler-/Diagnose-Schaltflächen wurden aus der Konfiguration entfernt
- reguläre Aktionen **Jetzt aktualisieren** und **Fahrzeugbild aktualisieren** bleiben erhalten
- neue Benutzerdiagnose **Rohe Fahrzeugantwort anzeigen** gibt den exakten Response-Body der letzten tatsächlich ausgeführten Fahrzeugabfrage aus, ohne eine zusätzliche API-Anfrage auszulösen
- die rohe Antwort wird getrennt vom weiterhin nur für gültige Fahrzeugdaten verwendeten `RawData`-Cache gespeichert; dadurch bleibt der letzte gültige Arbeitsdatensatz auch bei einer späteren API-Fehlerantwort erhalten
- auch ein von MySkoda gelieferter Fehler-Response kann damit für Support und Fehlersuche vollständig aus der Instanz kopiert werden
- bestehende öffentliche PHP-Methoden bleiben aus Kompatibilitätsgründen erhalten; lediglich die Entwicklerbedienung in der Instanz wird entfernt
- nicht mehr vorhandene Form-Referenzen auf den entfernten Button zum Neuladen der API-Definition wurden aus dem Code entfernt
- Versionskennung und User-Agent auf 1.5 angehoben
- bestehende README-Dateien bleiben gegenüber Version 1.4 unverändert

## 1.4 - 2026-09-16

- zusätzliche reine String-Variablen für statische Fahrzeuginformationen und vom Fahrzeug gemeldete Funktions-/Ausstattungsmerkmale der offiziellen MySkoda Public API
- neue Idents direkt hinter den FIN-Informationsvariablen: `APICarType`, `APIPrimaryEngineType`, `APISecondaryEngineType`, `APISupportedFeatures`, `APIAvailableChargeModes`, `APIRemoteOperations`, `APIAuxiliaryHeatingState` und `APIActiveVentilationState`
- `APICarType` sowie primärer und sekundärer Antriebstyp werden aus `fuelStatus` übernommen, sofern das Fahrzeug diese Daten liefert
- `APISupportedFeatures` fasst die vom konkreten Fahrzeug bereitgestellten Public-API-Bereiche zusammen; vorübergehend deaktivierte oder nicht verfügbare Bereiche gelten weiterhin als unterstützt, ausdrücklich als `*_UNSUPPORTED` gemeldete Bereiche nicht
- verfügbare Lademodi und Remote-Operationen werden zusätzlich als kompakte String-Listen bereitgestellt
- Status von Standheizung und aktiver Lüftung wird als zusätzliche Fahrzeug-/Ausstattungsinformation übernommen, sofern die API die jeweiligen Bereiche liefert
- die neuen Informationsvariablen verwenden bewusst keine Icons, Profile oder besonderen Darstellungen
- es werden ausschließlich Daten der offiziellen MySkoda Public API verwendet; nicht im Public-API-Vertrag enthaltene interne App-Daten wie Ausstattungslinie, Farbe, Batterie-Nennkapazität oder Softwarestand werden nicht ergänzt
- bestehende README-Dateien bleiben gegenüber Version 1.3 unverändert

## 1.3 - 2026-09-16

- lokale FIN-/VIN-Entschlüsselung direkt in der Instanzkonfiguration, ohne zusätzliche API-Anfrage
- eigener Konfigurationsblock **FIN / VIN entschlüsseln** mit strukturierter Zerlegung in WMI, VDS und VIS sowie Anzeige der ermittelbaren Fahrzeugdaten
- Auswertung von Hersteller, Herkunftsland, Modell-/Baureihencode, Modelljahr, Produktionswerk, Seriennummer und Prüfziffer, soweit die jeweilige FIN eine belastbare Zuordnung erlaubt
- zusätzliche modellabhängige Auswertung für bekannte Škoda-Baureihen; Enyaq und Elroq werden trotz gemeinsam verwendetem `NY`-Baureihencode anhand weiterer FIN-Merkmale unterschieden
- für bekannte Enyaq- und Elroq-FINs zusätzliche Auswertung von Karosserie, Links-/Rechtslenker, Heck-/Allradantrieb, Leistung, Variante und Rückhaltesystem
- Karoq-VDS wird zusätzlich nach Karosserie-/Lenkungs-/Antriebsart, bekannten Motorcodes und Rückhaltesystem ausgewertet
- europäische `TMB`-FINs und indische `MEX`-FINs werden getrennt behandelt; für Indien sind Kushaq (`PA`), Slavia (`PB`) und Kylaq (`PC`) als eigene Baureihen hinterlegt
- Citigo wird über den in der FIN verwendeten Baureihencode `AA` erkannt
- unbekannte oder nicht eindeutig belegte Codes werden nicht geraten, sondern als unbekannt bzw. mehrdeutig belassen
- optionale reine String-Variablen für die FIN-Informationen mit stabilen Idents; keine Icons, Profile oder besonderen Darstellungen
- der Schalter für FIN-Informationsvariablen steuert nur deren Anlage; bereits vorhandene Variablen bleiben auch nach dem Deaktivieren erhalten und werden bei einem späteren FIN-Wechsel weiter gepflegt
- FIN-Variablen werden nur beim erstmaligen Anlegen und bei Änderung der konfigurierten FIN beschrieben; das normale MySkoda-Polling verändert sie nicht
- öffentlicher Modulaufruf `MSKODA_GetVINData()` liefert die lokal entschlüsselten FIN-Daten als JSON
- FIN-Decodierung wird über einen eigenen Fingerprint gecacht und nur bei geänderter FIN neu berechnet
- eigene Dokumentation `MySkoda/README_FIN_VIN.md` beschreibt FIN-Aufbau, Prüfungen, bekannte Codes, Grenzen und Quellen der Interpretation
- Root- und Modul-README verweisen nur an den fachlich passenden Stellen auf die optionale FIN/VIN-Funktion und die separate Dokumentation
- Root-README für Module-Store-Nutzung mit Voraussetzungen, Installation, Einrichtung und Funktionsübersicht nachgezogen
- deutlicher Hinweis in beiden README-Dateien und der Instanzkonfiguration, dass die FIN-Interpretation keine offizielle Škoda-Fahrzeugdatenquelle ist
- kompakte Instanzdarstellung: vollständige FIN oben zerlegt; darunter steht jeweils der verwendete FIN-Code fett direkt vor der zugehörigen Interpretation, ohne zusätzliche Zwischenüberschriften

## 1.2 - 2026-09-15

- fahrzeugspezifisches Bild wird direkt aus `vehicle.renderUrl` der offiziellen MySkoda Public API übernommen
- das Bild wird einmalig heruntergeladen und als lokales Symcon-Bildmedium unter der MySkoda-Instanz gespeichert
- stabiler Medien-Ident `VehicleImage` für universelle Nutzung durch Visualisierungen und andere Module
- vorhandenes `VehicleImage` bleibt erhalten und wird beim normalen Fahrzeug-Polling nicht erneut heruntergeladen
- bei Wechsel der VIN wird das vorhandene Medium mit dem Bild des neuen Fahrzeugs aktualisiert, die Objekt-ID bleibt dabei erhalten
- manuelle Aktualisierung über `MSKODA_RefreshVehicleImage()` bzw. den Konfigurationsbutton **Fahrzeugbild aktualisieren**
- Download wird auf den von MySkoda gelieferten HTTPS-Renderhost `iprenders.blob.core.windows.net` begrenzt und der Bildinhalt vor dem Speichern geprüft
- erste Bilddiagnose `MSKODA_DiagnoseVehicleImages()` bleibt für die weitere Untersuchung möglicher zukünftiger Bildvarianten erhalten
- VIN wird in der Diagnoseausgabe maskiert
- aktuell liefert das getestete Fahrzeug über die Public API genau ein `renderUrl`; weitere Varianten werden erst integriert, wenn sie tatsächlich über die API verfügbar sind
- neue Diagnose `MSKODA_DiagnosePublicApiData()` listet alle Blattpfade der bereits empfangenen offiziellen Public-API-`RawData`
- die Diagnose trennt bereits verwendete und noch ungenutzte Datenfelder, ohne zusätzliche API-Anfrage auszulösen
- FIN wird maskiert; Kennzeichen und Standortwerte werden in der Diagnoseausgabe geschwärzt
- zusätzliche offizielle Public-API-Daten werden als read-only Symcon-Variablen mit stabilen Idents bereitgestellt: `VIN`, `ReliableLockStatus`, `RemainingChargingTime`, `AtSavedChargingLocation`, `BatteryCareMode`, `BatteryCareTargetSOC`, `MaxChargeCurrentAC`, `AutoUnlockPlug`, `TargetTemperatureUnit`, `AirConditioningAtUnlock`, `WindowHeatingEnabled`, `WindowHeatingFront` und `WindowHeatingRear`
- die neuen Datenpunkte verwenden passende native Symcon-Darstellungen, Icons und deutsch lokalisierte Zustände
- `Locked` priorisiert künftig `status.overall.reliableLockStatus` und verwendet die bisherigen Verriegelungswerte weiterhin als Fallback
- die von der API gelieferten `carCapturedTimestamp`-Felder werden bewusst nicht als Variablen angelegt

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