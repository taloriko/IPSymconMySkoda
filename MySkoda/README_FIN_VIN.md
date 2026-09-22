# FIN / VIN entschlüsseln

Die MySkoda-Instanz kann die konfigurierte 17-stellige FIN/VIN lokal zerlegen und bekannte Škoda-Codes interpretieren. Die Auswertung benötigt keine zusätzliche MySkoda-API-Anfrage.

> **Hinweis:** Die FIN-/VIN-Entschlüsselung ist keine offizielle Škoda-Datenquelle. Die Zuordnungen werden anhand öffentlich verfügbarer Herstellerinformationen, technischer Unterlagen, Typgenehmigungsdaten und nachvollziehbarer FIN-Beispiele interpretiert. Unbekannte oder nicht eindeutig belegte Codes werden bewusst nicht geraten.

## 1. Verarbeitung

Die konfigurierte FIN wird getrimmt und in Großschreibung ausgewertet. Zulässig sind 17 Zeichen aus `A–H`, `J–N`, `P`, `R–Z` und `0–9`. Die Syntaxprüfung schließt I, O und Q aus. Eine Prüfzifferabweichung wird angezeigt, verhindert aber nicht allein den Fahrzeugabruf.

Die Interpretation wird anhand eines Fingerprints der FIN zwischengespeichert. Bei unveränderter FIN und vorhandenem Cache erfolgt keine Neuberechnung. In der Konfiguration werden die verwendeten Codes und ihre Interpretation gemeinsam angezeigt.

| Stellen | Inhalt im Decoder |
|---|---|
| 1–3 | WMI / Herstellerzuordnung |
| 4–9 | VDS / fahrzeugbeschreibender Bereich |
| 10–17 | VIS / fahrzeugunterscheidender Bereich |
| 4 | Karosserie-, Lenkungs- und Antriebszuordnung, soweit modellabhängig hinterlegt |
| 5 | Motor-/Leistungscode, soweit hinterlegt |
| 6 | Rückhaltesystemcode |
| 7–8 | Modell-/Baureihencode |
| 9 | Prüfzeichen |
| 10 | Modelljahrcode |
| 11 | Produktionswerkcode |
| 12–17 | Seriennummer |

## 2. Hersteller und Modelle

Für `TMB` ist Škoda Auto / Tschechien hinterlegt; für `MEX` Škoda Auto Volkswagen India / Indien. Dies ist die lokale Herstellerzuordnung, keine unabhängige Bestätigung eines konkreten Produktionsorts.

| WMI | Modellcode | Hinterlegte Interpretation |
|---|---|---|
| TMB | `6Y`, `5J`, `NJ`, `PJ` | Fabia I, Fabia II / Roomster, Fabia III, Fabia IV |
| TMB | `1U`, `1Z`, `5E`, `NX` | Octavia I, II, III, IV |
| TMB | `3U`, `3T`, `3V`, `NZ` | Superb I, II, III, IV |
| TMB | `5L`, `NU`, `NS`, `PS` | Yeti, Karoq, Kodiaq I, Kodiaq II |
| TMB | `NW`, `AA`, `NH`, `NK` | Scala / Kamiq, Citigo, Rapid, Rapid |
| TMB | `NY` | Enyaq / Elroq; weitere Differenzierung nach Karosseriecode und Modelljahr |
| MEX | `PA`, `PB`, `PC` | Kushaq, Slavia, Kylaq |

Für `NY` werden die Karosseriecodelisten E/F/G/H/J/K/L/M dem Enyaq zugeordnet. N/P/R/S werden ab interpretiertem Modelljahr 2025 dem Elroq zugeordnet. Andernfalls bleibt die Angabe `Enyaq / Elroq` mehrdeutig. Nicht hinterlegte WMI- oder Modellcodes liefern keine sichere Modellzuordnung.

## 3. Modellabhängige Auswertung

Beim Enyaq stehen E/F für Coupé mit Heckantrieb und G/H für Coupé mit Allradantrieb. J/K stehen für SUV mit Heckantrieb, L/M für SUV mit Allradantrieb. Der erste Buchstabe jedes Paares wird als Linkslenker und der zweite als Rechtslenker interpretiert. Beim Elroq gilt dieses Muster für N/P mit Heckantrieb und R/S mit Allradantrieb. Beim Karoq werden J/K dem Frontantrieb und L/M dem Allradantrieb zugeordnet.

| Modell | Motorcode → hinterlegte Leistung in kW |
|---|---|
| Enyaq | A → 109; B → 132; C → 150; E → 195; F → 220; H → 210; J → 250 |
| Elroq | G → 125; C → 150; H → 210; F → 220; J → 250 |
| Karoq | E → 140 / 2.0 TSI; G → 85 / 1.6 TDI; J → 110 / 2.0 TDI; M → 140 / 2.0 TDI; P → 85 / 1.0 TSI; R → 110 / 1.5 TSI |

Die PS-Anzeige wird aus der hinterlegten kW-Leistung berechnet. Die Variantenbezeichnung berücksichtigt beim Enyaq zusätzlich Modelljahr und Antrieb: bis 2023 werden die hinterlegten iV-Varianten unterschieden; H steht anschließend für 85 beziehungsweise 85x und J für RS. Beim Elroq werden 50, 60, 85, 85x und RS anhand der hinterlegten Motor- und Antriebskombinationen unterschieden. Nicht eindeutig abgedeckte Kombinationen bleiben leer.

Für Octavia IV sind einzelne Kombinationen hinterlegt: Karosseriecode J, Motorcode R und Rückhaltecode 8. Die Funktion ist kein vollständiger Motoren- oder Ausstattungskatalog.

Rückhaltesysteme werden nur für die ausdrücklich hinterlegten Modell-/Codekombinationen ausgegeben. Beim Enyaq und Elroq werden die Codes 7 und 9 verarbeitet; beim Karoq sind 2, 4, 5, 6, 7, 8 und 9 hinterlegt. Daraus darf keine Aussage über weitere, nicht codierte Ausstattung abgeleitet werden.

## 4. Modelljahr, Werk und Prüfzeichen

Der Modelljahrcode wird mit der Folge `ABCDEFGHJKLMNPRSTVWXY123456789` ausgewertet. Der Decoder prüft daraus resultierende Jahre der Zyklen ab 1980, 2010 und 2040 gegen hinterlegte Baureihen-Zeiträume. Ist keine passende Zuordnung möglich, wird als Rückfall das letzte zulässige Jahr bis zum aktuellen Kalenderjahr plus eins verwendet. Modelljahr und Erstzulassung sind nicht identisch. Ein Rückfallwert ist kein unabhängiger Nachweis des Baujahrs.

Für TMB sind die Werkscodes 0–4 und Y mit Mladá Boleslav sowie 5–9 mit Kvasiny hinterlegt. F wird nur in Verbindung mit Modellcode NY als Mladá Boleslav interpretiert. Andere Werkcodes bleiben unbekannt.

Das Prüfzeichen wird durch Buchstabenabbildung und Gewichtung `8,7,6,5,4,3,2,10,0,9,8,7,6,5,4,3,2` berechnet. Der Rest modulo 11 ergibt das Prüfzeichen; Rest 10 wird als X ausgegeben. Stelle 9 hat Gewicht 0. Der berechnete Wert wird mit dem vorhandenen Prüfzeichen verglichen. Die Anzeige unterscheidet `gültig` und `nicht bestätigt`; sie ersetzt keinen Hersteller- oder Fahrzeugnachweis.

## 5. Optionale FIN-Variablen

Die Option `CreateVINVariables` steuert ausschließlich die Anlage. Die Variablen werden als reine Strings ohne Icons und besondere Darstellungen erstellt. Vorhandene Namen, Positionen und Darstellungen bleiben benutzerverwaltet. Werte werden nur bei Erstanlage oder geänderter FIN beschrieben. Beim Deaktivieren der Option bleiben die Variablen erhalten und werden bei späterem FIN-Wechsel weiter aktualisiert.

| Position | Ident | Anzeige | Typ | Bedeutung |
|---:|---|---|---|---|
| 1100 | `VINWMI` | FIN WMI | String | Stellen 1–3 |
| 1110 | `VINVDS` | FIN VDS | String | Stellen 4–9 |
| 1120 | `VINVIS` | FIN VIS | String | Stellen 10–17 |
| 1130 | `VINManufacturer` | FIN Hersteller | String | hinterlegte Herstellerzuordnung |
| 1140 | `VINCountry` | FIN Land | String | Land der Herstellerzuordnung |
| 1150 | `VINModel` | FIN Modell | String | interpretierte Baureihe, gegebenenfalls mehrdeutig |
| 1160 | `VINModelCode` | FIN Modellcode | String | Stellen 7–8 |
| 1170 | `VINBody` | FIN Karosserie | String | modellabhängige Karosseriezuordnung |
| 1180 | `VINSteering` | FIN Lenkung | String | Links-/Rechtslenker, soweit hinterlegt |
| 1190 | `VINDrive` | FIN Antrieb | String | Antriebsart, soweit hinterlegt |
| 1200 | `VINPower` | FIN Leistung | String | interpretierte Leistung in kW und PS |
| 1210 | `VINVariant` | FIN Variante | String | Variantenbezeichnung, soweit hinterlegt |
| 1220 | `VINRestraint` | FIN Rückhaltesystem | String | Rückhaltecode-Interpretation |
| 1230 | `VINModelYear` | FIN Modelljahr | String | interpretiertes Modelljahr |
| 1240 | `VINPlant` | FIN Produktionswerk | String | Werkcode-Interpretation |
| 1250 | `VINSerialNumber` | FIN Seriennummer | String | Stellen 12–17 |
| 1260 | `VINCheckDigit` | FIN Prüfziffer | String | vorhandenes Prüfzeichen und Vergleichsergebnis |

Bei ungültiger Syntax werden ableitbare WMI/VDS/VIS-Abschnitte separat behandelt und Interpretationsfelder leer ausgegeben. Bei syntaktisch gültiger, aber nicht abgedeckter FIN bleiben nicht interpretierbare Felder leer oder mehrdeutig.

## 6. PHP-Schnittstelle

```php
$json = MSKODA_GetVINData(12345);
$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
```

Die Funktion liefert die zwischengespeicherte Interpretation und löst keine API-Anfrage aus. Das JSON enthält unter anderem `vin`, `validSyntax`, `wmi`, `vds` und `vis`. Bei gültiger Syntax kommen Codes, interpretierte Felder, `modelYear`, `checkDigit`, `calculatedCheckDigit`, `checkDigitValid` und die zerlegten `parts` hinzu. Vor der Verarbeitung einzelner Felder ist ihre Verfügbarkeit zu prüfen.

## 7. Grenzen und Quellen

Die implementierten Tabellen sind in [VinDecoderTrait.php](src/VinDecoderTrait.php) und die Einbindung in [VinIntegrationTrait.php](src/VinIntegrationTrait.php) nachvollziehbar. Das ist die technische Quelle für das Verhalten dieser Modulversion. Nicht hinterlegte Fahrzeuge, Modelljahre und Codes werden dadurch nicht automatisch unterstützt. Änderungen in Herstellerzuordnungen werden nicht aus dem Internet nachgeladen.

Aktuell verwendete bzw. zur Gegenprüfung geeignete Quellen sind unter anderem:

- Škoda Storyboard – **What can VIN codes tell you?**  
  <https://www.skoda-storyboard.com/en/skoda-world/what-can-vin-codes-tell-you/>
- Škoda Storyboard – technische Daten der Enyaq-Antriebsvarianten  
  <https://www.skoda-storyboard.com/en/press-kits/skoda-enyaq-iv-press-kit-2/electric-powertrain-three-battery-sizes-and-five-power-levels/>
- 49 CFR § 565.15 – Referenz für Modelljahrcodes und das gewichtete Prüfzifferverfahren  
  <https://www.law.cornell.edu/cfr/text/49/565.15>
- Škoda-/Rettungs- und Typgenehmigungsunterlagen sowie öffentlich zugängliche nationale Typgenehmigungsdaten für die Gegenprüfung einzelner Baureihen

## 8. Was kann nicht aus der FIN abgeleitet wird

Beispiele:
- exaktes Produktionsdatum
- Erstzulassung
- PR-Codes oder komplette Werksausstattung
- Wärmepumpe
- Canton
- Head-up-Display
- Anhängerkupplung
- Panoramadach
- DCC
- Softwareversion
- Batteriezellhersteller oder SOH
- Servicehistorie
- offene Rückrufe oder TPI-Anwendbarkeit

Diese Informationen können zu einer FIN in Hersteller- oder Servicedatenbanken vorhanden sein, sind aber nicht zwangsläufig direkt in den 17 FIN-Zeichen codiert.
