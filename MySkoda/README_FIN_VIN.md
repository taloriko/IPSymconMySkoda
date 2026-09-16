# FIN / VIN entschlüsseln

Die MySkoda-Instanz kann die konfigurierte 17-stellige FIN/VIN lokal zerlegen und bekannte Škoda-Codes interpretieren. Die Auswertung benötigt keine zusätzliche MySkoda-API-Anfrage.

> **Hinweis:** Die FIN-/VIN-Entschlüsselung ist keine offizielle Škoda-Datenquelle. Die Zuordnungen werden anhand öffentlich verfügbarer Herstellerinformationen, technischer Unterlagen, Typgenehmigungsdaten und nachvollziehbarer FIN-Beispiele interpretiert. Unbekannte oder nicht eindeutig belegte Codes werden bewusst nicht geraten.

## Verhalten im Modul

Nach dem Speichern der Instanzkonfiguration wird die FIN lokal ausgewertet. Die entschlüsselten Werte werden direkt im Abschnitt **FIN / VIN entschlüsseln** angezeigt.

Über **FIN-Informationsvariablen anlegen** können die Ergebnisse zusätzlich als reine String-Variablen unterhalb der Instanz angelegt werden. Es werden keine Icons, Profile oder besonderen Darstellungen verwendet.

Die FIN-Auswertung wird über einen eigenen Fingerprint zwischengespeichert. Sie wird nur beim erstmaligen Anlegen bzw. bei einer geänderten FIN neu berechnet. Der normale zyklische MySkoda-Abruf verändert diese Werte nicht.

Bereits angelegte FIN-Variablen bleiben bestehen, auch wenn die Option später deaktiviert wird. Bei einem späteren FIN-Wechsel werden vorhandene FIN-Variablen weiterhin aktualisiert.

Die entschlüsselten Daten können außerdem mit folgendem Modulaufruf als JSON gelesen werden:

```php
$json = MSKODA_GetVINData(12345);
```

## Aufbau der FIN

Das Modul zerlegt eine 17-stellige FIN in folgende Bereiche:

| Position | Bereich | Verwendung im Modul |
|---|---|---|
| 1–3 | WMI | Hersteller-/Herkunftskennung |
| 4–8 | VDS | modellabhängige Fahrzeugbeschreibung |
| 9 | Sicherheits-/Prüfzeichen | Vergleich mit berechneter Prüfziffer |
| 10 | Modelljahr | Modelljahrcode |
| 11 | Werk | Produktionswerk, soweit bekannt |
| 12–17 | Seriennummer | laufende Fahrzeugnummer |

Škoda beschreibt die Stellen 4–9 als Vehicle Descriptor Section. Nach Škoda können dort je nach Baureihe unter anderem Karosserie, Motor/Antrieb, Rückhaltesystem und Fahrzeugtyp codiert sein. Deshalb werden die Stellen 4–8 im Modul **nicht global**, sondern nur mit baureihenbezogenen Tabellen interpretiert.

## 1. Formale Prüfung

Vor der Interpretation wird die FIN normalisiert und geprüft:

- führende und nachfolgende Leerzeichen werden entfernt
- Buchstaben werden in Großschreibung verarbeitet
- Länge muss genau 17 Zeichen betragen
- zulässig sind `A-H`, `J-N`, `P`, `R-Z` und `0-9`
- die Buchstaben `I`, `O` und `Q` sind nicht zulässig

Verwendetes Muster:

```text
^[A-HJ-NPR-Z0-9]{17}$
```

Bei einer formal ungültigen FIN werden keine Fahrzeugmerkmale geraten.

## 2. Herstellerkennung WMI – Stellen 1 bis 3

Aktuell kennt das Modul insbesondere:

| WMI | Interpretation |
|---|---|
| `TMB` | Škoda Auto, Tschechien |
| `MEX` | Škoda Auto Volkswagen India, Indien |

Ein unbekannter WMI macht die FIN nicht automatisch formal ungültig. Das Modul lässt die Herstellerzuordnung dann leer, statt eine Marke zu erfinden.

## 3. Modell-/Baureihencode – Stellen 7 und 8

Für `TMB` sind aktuell unter anderem folgende Baureihencodes hinterlegt:

| Code | Baureihe |
|---|---|
| `6Y` | Fabia I |
| `5J` | Fabia II / Roomster |
| `NJ` | Fabia III |
| `PJ` | Fabia IV |
| `1U` | Octavia I |
| `1Z` | Octavia II |
| `5E` | Octavia III |
| `NX` | Octavia IV |
| `3U` | Superb I |
| `3T` | Superb II |
| `3V` | Superb III |
| `NZ` | Superb IV |
| `5L` | Yeti |
| `NU` | Karoq |
| `NS` | Kodiaq I |
| `PS` | Kodiaq II |
| `NW` | Scala / Kamiq |
| `AA` | Citigo |
| `NH`, `NK` | Rapid |
| `NY` | Enyaq / Elroq; weitere VDS-Stellen werden zur Unterscheidung verwendet |

Für `MEX` sind aktuell hinterlegt:

| Code | Baureihe |
|---|---|
| `PA` | Kushaq |
| `PB` | Slavia |
| `PC` | Kylaq |

Ein Modellcode allein reicht nicht in jedem Fall für eine eindeutige Fahrzeugbestimmung. Besonders `NY` wird von Enyaq und Elroq verwendet. Das Modul kombiniert deshalb Modellcode, Modelljahr und weitere VDS-Zeichen.

## 4. Modelljahr – Stelle 10

Stelle 10 wird als Modelljahr interpretiert. Der bekannte 30-Jahres-Zyklus wird mit dem zeitlichen Bereich der jeweiligen Baureihe kombiniert, damit beispielsweise `N` bei einem Enyaq als Modelljahr 2022 und nicht als 1992 interpretiert wird.

Aktuelle Folge ab 2010:

| Code | Modelljahr | Code | Modelljahr |
|---|---:|---|---:|
| `A` | 2010 | `L` | 2020 |
| `B` | 2011 | `M` | 2021 |
| `C` | 2012 | `N` | 2022 |
| `D` | 2013 | `P` | 2023 |
| `E` | 2014 | `R` | 2024 |
| `F` | 2015 | `S` | 2025 |
| `G` | 2016 | `T` | 2026 |
| `H` | 2017 | `V` | 2027 |
| `J` | 2018 | `W` | 2028 |
| `K` | 2019 | `X` | 2029 |

Danach folgen `Y` für 2030 und `1` bis `9` für 2031 bis 2039.

**Modelljahr ist nicht gleich Produktionsdatum oder Erstzulassung.** Ein Fahrzeug des Modelljahres 2022 kann beispielsweise bereits 2021 produziert worden sein.

## 5. Produktionswerk – Stelle 11

Für europäische `TMB`-FINs werden aktuell folgende bekannte Zuordnungen verwendet:

| Code | Interpretation |
|---|---|
| `0`–`4` | Mladá Boleslav |
| `5`–`9` | Kvasiny |
| `Y` | Mladá Boleslav |
| `F` bei `NY` | Mladá Boleslav |

Wenn eine Zuordnung nicht hinreichend belegt ist, bleibt das Produktionswerk leer.

## 6. Seriennummer – Stellen 12 bis 17

Die letzten sechs Stellen werden als Serien-/Produktionsnummer ausgegeben. Aus dieser Nummer wird **kein** Produktionsdatum berechnet.

## 7. Sicherheits-/Prüfzeichen – Stelle 9

Škoda bezeichnet Stelle 9 als Sicherheitscode. Das Modul berechnet zusätzlich die verbreitete VIN-Prüfziffer nach dem gewichteten Modulo-11-Verfahren und vergleicht sie mit Stelle 9.

Gewichte:

```text
Position: 1  2  3  4  5  6  7  8  9 10 11 12 13 14 15 16 17
Gewicht:  8  7  6  5  4  3  2 10  0  9  8  7  6  5  4  3  2
```

Der Rest der Summe modulo 11 ergibt `0` bis `9`; der Wert 10 wird als `X` dargestellt.

Wichtig: Dieses Prüfverfahren ist als standardisierte VIN-Prüfziffer insbesondere aus nordamerikanischen Vorgaben dokumentiert. Da Škoda für europäische Fahrzeuge von einem Sicherheitscode spricht, behandelt das Modul eine Abweichung bewusst als **„nicht bestätigt“** und nicht pauschal als Beweis für eine ungültige FIN.

## 8. Detaillierte VDS-Auswertung

### Enyaq

Bei als Enyaq erkannten `NY`-FINs werden aktuell zusätzlich Karosserie, Lenkung und Antriebsart aus Stelle 4 ausgewertet:

| Code | Karosserie | Lenkung | Antrieb |
|---|---|---|---|
| `E` | Coupé | links | Heck |
| `F` | Coupé | rechts | Heck |
| `G` | Coupé | links | Allrad |
| `H` | Coupé | rechts | Allrad |
| `J` | SUV | links | Heck |
| `K` | SUV | rechts | Heck |
| `L` | SUV | links | Allrad |
| `M` | SUV | rechts | Allrad |

Bekannte Leistungskennungen an Stelle 5:

| Code | Leistung |
|---|---:|
| `A` | 109 kW |
| `B` | 132 kW |
| `C` | 150 kW |
| `E` | 195 kW |
| `F` | 220 kW |
| `H` | 210 kW |
| `J` | 250 kW |

Für frühe Modelljahre werden daraus unter anderem die Varianten Enyaq iV 50, 60, 80, 80x und RS abgeleitet. Neuere Zuordnungen werden nur ausgegeben, wenn Code und Antriebsart zusammenpassen.

### Elroq

Elroq und Enyaq teilen den Modellcode `NY`. Für Elroq werden aktuell die Karosserie-/Antriebscodes `N`, `P`, `R` und `S` in Verbindung mit neueren Modelljahren ausgewertet.

Bekannte Leistungskennungen:

| Code | Leistung | mögliche Variante |
|---|---:|---|
| `G` | 125 kW | Elroq 50 |
| `C` | 150 kW | Elroq 60 |
| `H` | 210 kW | Elroq 85 / 85x abhängig vom Antrieb |
| `F` | 220 kW | Elroq 85x bei passender Allradkennung |
| `J` | 250 kW | Elroq RS bei passender Allradkennung |

### Karoq

Für Karoq `NU` werden aktuell folgende Karosserie-/Antriebscodes ausgewertet:

| Code | Lenkung | Antrieb |
|---|---|---|
| `J` | links | Front |
| `K` | rechts | Front |
| `L` | links | Allrad |
| `M` | rechts | Allrad |

Bekannte Motorcodes:

| Code | Leistung | Motor |
|---|---:|---|
| `E` | 140 kW | 2.0 TSI |
| `G` | 85 kW | 1.6 TDI |
| `J` | 110 kW | 2.0 TDI |
| `M` | 140 kW | 2.0 TDI |
| `P` | 85 kW | 1.0 TSI |
| `R` | 110 kW | 1.5 TSI |

Zusätzlich werden bekannte Rückhaltesystemcodes ausgewertet.

### Octavia IV

Für Octavia IV `NX` ist derzeit nur eine begrenzte, konkret belegte Teilmenge der VDS-Auswertung hinterlegt. Nicht dokumentierte Kombinationen werden bewusst nicht ergänzt.

## 9. Optionale Variablen

Wenn **FIN-Informationsvariablen anlegen** aktiviert ist, können folgende String-Variablen entstehen:

| Ident | Inhalt |
|---|---|
| `VINWMI` | WMI |
| `VINVDS` | VDS |
| `VINVIS` | VIS |
| `VINManufacturer` | Hersteller |
| `VINCountry` | Herkunftsland |
| `VINModel` | Modell/Baureihe |
| `VINModelCode` | Modellcode |
| `VINBody` | Karosserie |
| `VINSteering` | Links-/Rechtslenker |
| `VINDrive` | Antriebsart |
| `VINPower` | Leistung |
| `VINVariant` | Modellvariante |
| `VINRestraint` | Rückhaltesystem |
| `VINModelYear` | Modelljahr |
| `VINPlant` | Produktionswerk |
| `VINSerialNumber` | Seriennummer |
| `VINCheckDigit` | Prüfzeichen und Prüfergebnis |

Alle Variablen sind reine Strings ohne Icon und ohne spezielle Darstellung.

## 10. Beispiel

Beispiel-FIN:

```text
TMBJC7NY5NF000001
```

Zerlegung:

```text
TMB | J | C | 7 | NY | 5 | N | F | 000001
```

Aktuelle Interpretation des Moduls:

```text
Hersteller: Škoda Auto
Land: Tschechien
Modell: Enyaq
Karosserie: SUV
Lenkung: Linkslenker
Antrieb: Heckantrieb
Leistung: 150 kW / 204 PS
Variante: Enyaq iV 80
Modelljahr: 2022
Produktionswerk: Mladá Boleslav
Seriennummer: 000001
Prüfzeichen: 5, Berechnung: 5
```

## 11. Was bewusst nicht aus der FIN abgeleitet wird

Ohne zusätzliche offizielle Fahrzeugdaten werden insbesondere nicht behauptet:

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

## 12. Quellen und Pflege der Zuordnungen

Die Tabellen werden konservativ gepflegt. Bevor ein neuer Code aufgenommen wird, sollte er möglichst durch mehrere voneinander unabhängige Hinweise oder durch eine belastbare Hersteller-/Typgenehmigungsquelle bestätigt werden.

Aktuell verwendete bzw. zur Gegenprüfung geeignete Quellen sind unter anderem:

- Škoda Storyboard – **What can VIN codes tell you?**  
  <https://www.skoda-storyboard.com/en/skoda-world/what-can-vin-codes-tell-you/>
- Škoda Storyboard – technische Daten der Enyaq-Antriebsvarianten  
  <https://www.skoda-storyboard.com/en/press-kits/skoda-enyaq-iv-press-kit-2/electric-powertrain-three-battery-sizes-and-five-power-levels/>
- 49 CFR § 565.15 – Referenz für Modelljahrcodes und das gewichtete Prüfzifferverfahren  
  <https://www.law.cornell.edu/cfr/text/49/565.15>
- Škoda-/Rettungs- und Typgenehmigungsunterlagen sowie öffentlich zugängliche nationale Typgenehmigungsdaten für die Gegenprüfung einzelner Baureihen
- dokumentierte reale FIN-Beispiele aus Fahrzeugforen nur ergänzend; solche Beispiele werden nicht allein als Herstellerbeleg behandelt
