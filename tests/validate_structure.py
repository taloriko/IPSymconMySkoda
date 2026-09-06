from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
GUID = re.compile(r"^\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}$")


def load(path: Path):
    with path.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def walk(items):
    for item in items:
        yield item
        nested = item.get("items")
        if isinstance(nested, list):
            yield from walk(nested)


def main() -> None:
    library = load(ROOT / "library.json")
    module = load(ROOT / "MySkoda" / "module.json")
    form = load(ROOT / "MySkoda" / "form.json")
    locale = load(ROOT / "MySkoda" / "locale.json")

    assert GUID.match(library["id"])
    assert GUID.match(module["id"])
    assert library["compatibility"]["version"] >= "8.1"
    assert library["version"] == "1.0"
    assert module["name"] == "MySkoda"
    assert module["vendor"] == "taloriko"
    assert module["prefix"] == "MSKODA"
    assert isinstance(locale.get("translations", {}).get("de"), dict)

    elements = list(walk(form["elements"]))
    names = {element.get("name") for element in elements}
    for required in {
        "VIN",
        "APIToken",
        "Interval",
        "EnableRemote",
        "ShowDetails",
        "EnableChargingHistory",
        "NotifyKeyExpiry",
        "NotificationInstanceID",
    }:
        assert required in names

    form_text = (ROOT / "MySkoda" / "form.json").read_text(encoding="utf-8")
    for german_text in [
        "Verbindung",
        "Abfrage und Steuerung",
        "Mitteilungen",
        "Zusätzliche Daten",
        "Archivierung aktivieren",
        "Verbindung testen",
        "Jetzt aktualisieren",
    ]:
        assert german_text in form_text

    module_php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    assert "final class MySkoda extends IPSModuleStrict" in module_php
    assert "final class MySkoda extends IPSModule\n" not in module_php
    assert "IP-Symcon-MySkoda/1.0" in module_php
    assert "StructureTrait.php" not in module_php
    assert "HistoryTrait.php" in module_php
    assert "V22" not in module_php

    php_sources = "\n".join(
        source.read_text(encoding="utf-8")
        for source in (ROOT / "MySkoda").rglob("*.php")
    )
    variables = (ROOT / "MySkoda" / "src" / "VariablesTrait.php").read_text(encoding="utf-8")
    history = (ROOT / "MySkoda" / "src" / "HistoryTrait.php").read_text(encoding="utf-8")
    core = (ROOT / "MySkoda" / "src" / "CoreTrait.php").read_text(encoding="utf-8")

    # Das Datenmodul erzeugt ausschließlich Variablen.
    for forbidden in [
        "IPS_CreateInstance",
        "IPS_CreateCategory",
        "IPS_CreateLink",
        "IPS_CreateMedia",
        "MSKODA_Group",
        "MSKODA_Link_",
        "getGroupId(",
        "placeVariableInGroup",
        "maintainGroupedAction",
    ]:
        assert forbidden not in php_sources

    assert not (ROOT / "MySkoda" / "src" / "StructureTrait.php").exists()

    # Variablen werden nur einmal registriert und behalten stabile technische Idents.
    assert "registerVariableOnce" in variables
    assert "UnregisterVariable" not in php_sources
    assert "IPS_DeleteVariable" not in php_sources

    for stable_ident in [
        "StateOfCharge",
        "Range",
        "Mileage",
        "Locked",
        "DoorsOpen",
        "WindowsOpen",
        "Charging",
        "ChargePower",
        "TargetSOC",
        "ChargeMode",
        "Climate",
        "TargetTemperature",
        "ApiKeyWarning",
        "LastUpdate",
    ]:
        assert f"'{stable_ident}'" in variables

    # Sichtbare Namen und Bedienbezeichnungen sind deutsch.
    for german_name in [
        "Ladezustand",
        "Reichweite",
        "Kilometerstand",
        "Verriegelt",
        "Türen offen",
        "Fenster offen",
        "Laden",
        "Ladeleistung",
        "Ladelimit",
        "Lademodus",
        "Klimatisierung",
        "Solltemperatur",
        "API-Key Warnung",
        "Letzte Aktualisierung",
        "Fahrzeugname",
        "Kennzeichen",
        "Schiebedach offen",
        "Ladekabel anschließen",
        "Bevorzugte Ladezeiten",
    ]:
        assert german_name in variables

    assert "'Caption' => 'Nein'" in variables
    assert "'Caption' => 'Ja'" in variables

    # Vorhandene Variablen-Darstellungen werden nicht dynamisch überschrieben.
    assert "CHARGE_MODES" in variables
    assert "AvailableChargeModes" in variables
    assert "updateChargeModePresentation" not in php_sources
    assert "ChargeModeMap" not in php_sources

    for forbidden in [
        "IPS_SetVariableCustomPresentation",
        "IPS_SetVariableCustomProfile",
        "IPS_CreateVariableProfile",
        "IPS_CreateTemplate",
        "IPS_SetTemplate",
    ]:
        assert forbidden not in php_sources

    # Archivierung: Ladezustand, Ladelimit, Ladeleistung und Kilometerstand.
    for ident in ["StateOfCharge", "TargetSOC", "ChargePower", "Mileage"]:
        assert f"'{ident}'" in history

    assert "AC_GetLoggingStatus" in history
    assert "AC_SetLoggingStatus" in history
    assert "AC_GetAggregationType" in history
    assert "AC_SetAggregationType($archiveId, $mileageId, 1)" in history
    assert "AC_GetCounterIgnoreZeros" in history
    assert "AC_SetCounterIgnoreZeros($archiveId, $mileageId, true)" in history
    assert "AC_ReAggregateVariable" in history
    assert "MSKODA_ChargingHistory" not in php_sources

    # Ein API-Kilometerstand <= 0 wird nicht in die Variable geschrieben.
    assert "$mileage > 0" in variables
    assert "$this->SetValue('Mileage', $mileage);" in variables
    assert "setPathValue('Mileage'" not in variables

    assert "ChargingHistoryInitialized" in core
    assert "WriteAttributeBoolean('ChargingHistoryInitialized', true)" in history

    for forbidden in [
        "AC_DeleteVariableData",
        "AC_SetCompaction",
        "AC_SetGraphStatus",
        "IPS_DeleteMedia",
    ]:
        assert forbidden not in php_sources

    assert "IPS_SetProperty" not in php_sources
    assert "IPS_ApplyChanges" not in php_sources

    expected_sources = {
        "ApiTrait.php",
        "CoreTrait.php",
        "HelpersTrait.php",
        "HistoryTrait.php",
        "NotificationTrait.php",
        "OpenApiTrait.php",
        "VariablesTrait.php",
    }
    source_names = {path.name for path in (ROOT / "MySkoda" / "src").glob("*.php")}
    assert source_names == expected_sources

    root_readme = (ROOT / "README.md").read_text(encoding="utf-8")
    module_readme = (ROOT / "MySkoda" / "README.md").read_text(encoding="utf-8")
    assert "keine Dummy-Instanzen" in root_readme
    assert "ausschließlich die echten Modulvariablen" in root_readme
    assert "keine Dummy-Instanzen, Kategorien oder Links" in module_readme
    assert "Kilometerstand" in root_readme
    assert "Zähler" in root_readme

    changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
    assert "## 1.0 - 2026-09-06" in changelog
    assert "## 2." not in changelog
    assert not (ROOT / "docs").exists()


if __name__ == "__main__":
    main()
