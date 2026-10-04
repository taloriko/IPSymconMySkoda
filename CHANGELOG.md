# Changelog

## 1.8 - 2026-09-29

- Fahrzeugvariablen werden nur noch angelegt, wenn der zugehörige Wert tatsächlich in einer Fahrzeugantwort geliefert wurde.
- Neu auftauchende, bisher unbekannte skalare API-Felder werden automatisch als zusätzliche API-Variablen angelegt.
- Bereits angelegte Variablen bleiben bestehen; vorhandene Namen, Positionen, Icons und Darstellungen werden nicht überschrieben.
- Verbrennerdaten aus `fuelStatus` ergänzt: Tankfüllstand, primärer Antrieb SoC, Kraftstoff-Reichweite und Gesamtreichweite.
- Fahrzeug- und Antriebstypen erhalten Darstellungen für Benzin, Diesel, Elektro, Hybrid und Plug-in-Hybrid.
- Standheizungsstatus und Standheizungsdauer werden bei vorhandenen API-Daten mit eigener Darstellung angelegt.
- Lade-, Klima- und andere fahrzeugspezifische Variablen erscheinen nicht mehr als leere Platzhalter bei Fahrzeugen, die diese Daten nicht liefern.
- Standheizung als bedienbare Variable ergänzt, wenn das Fahrzeug Start und Stop als Remote-Operationen anbietet.
- Standheizungsstart auf den minimal erforderlichen Request reduziert; optionale Temperaturdaten werden nur gesendet, wenn das Fahrzeug sie selbst liefert.
- Fehlgeschlagene Remote-Befehle setzen eine ansonsten funktionierende Fahrzeug-Instanz nicht mehr auf Fehlerstatus.
- Die Einstellung "Klimatisierung ohne externe Stromversorgung" wird nur angezeigt, wenn das Fahrzeug dieses API-Feld liefert; S-PIN nur bei angebotener Standheizung.
- `chargingRateInKilometersPerHour` wird entsprechend der API-Spezifikation als Float behandelt; ältere Integer-Variablen aus Vorabständen werden ohne wiederkehrende Typfehler weitergeführt.
- Ungültiges Symcon-Statusicon `warning` durch das unterstützte `error` ersetzt.
- Neue bekannte API-Felder mit eigener Darstellung ergänzt: verfügbare Lademodi, maximaler AC-Ladestrom in Ampere, Ladegeschwindigkeit in km/h und voraussichtlicher Zeitpunkt zum Erreichen der Zieltemperatur.
- Bereits generisch angelegte API-Felder werden nur dann auf die neue Standarddarstellung migriert, wenn keine benutzerdefinierte Darstellung vorhanden ist.

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
