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


def captions(items):
    return {
        item["caption"]
        for item in walk(items)
        if isinstance(item, dict) and isinstance(item.get("caption"), str)
    }


def main() -> None:
    library = load(ROOT / "library.json")
    module = load(ROOT / "MySkoda" / "module.json")
    form = load(ROOT / "MySkoda" / "form.json")
    locale = load(ROOT / "MySkoda" / "locale.json")
    translations = locale.get("translations", {}).get("de", {})

    # Store/library metadata.
    assert set(library) == {
        "id",
        "author",
        "name",
        "url",
        "compatibility",
        "version",
        "build",
        "date",
    }
    assert GUID.match(library["id"])
    assert library["author"] == "taloriko"
    assert library["name"] == "MySkoda"
    assert library["url"] == "https://github.com/taloriko/IPSymconMySkoda"
    assert library["compatibility"]["version"] >= "8.1"
    assert library["version"] == "1.0"
    assert library["build"] == 0
    assert isinstance(library["date"], int) and library["date"] > 0

    # Module metadata and documented repository layout.
    assert set(module) == {
        "id",
        "name",
        "type",
        "vendor",
        "aliases",
        "parentRequirements",
        "childRequirements",
        "implemented",
        "prefix",
        "url",
    }
    assert GUID.match(module["id"])
    assert module["name"] == "MySkoda"
    assert module["type"] == 3
    assert module["vendor"] == "taloriko"
    assert module["prefix"] == "MSKODA"
    assert module["url"].startswith("https://github.com/taloriko/IPSymconMySkoda/")
    assert (ROOT / module["name"]).is_dir()
    assert (ROOT / "MySkoda" / "module.php").is_file()
    assert (ROOT / "MySkoda" / "module.json").is_file()
    assert (ROOT / "MySkoda" / "form.json").is_file()
    assert (ROOT / "MySkoda" / "locale.json").is_file()
    assert (ROOT / "README.md").is_file()
    assert (ROOT / "MySkoda" / "README.md").is_file()
    assert (ROOT / "LICENSE").is_file()

    # Configuration form: all visible static captions are localizable to German.
    assert isinstance(translations, dict) and translations
    all_form_captions = set()
    all_form_captions |= captions(form.get("elements", []))
    all_form_captions |= captions(form.get("actions", []))
    all_form_captions |= captions(form.get("status", []))
    missing_translations = sorted(caption for caption in all_form_captions if caption not in translations)
    assert not missing_translations, f"Missing German form translations: {missing_translations}"

    elements = list(walk(form["elements"]))
    names = {element.get("name") for element in elements}
    for required in {
        "VIN",
        "APIToken",
        "Interval",
        "EnableRemote",
        "ClimateWithoutExternalPower",
        "SPIN",
        "ShowDetails",
        "EnableChargingHistory",
        "NotifyKeyExpiry",
        "NotificationInstanceID",
    }:
        assert required in names

    for required_translation, expected in {
        "Connection": "Verbindung",
        "Polling and control": "Abfrage und Steuerung",
        "Notifications": "Mitteilungen",
        "Additional data": "Zusätzliche Daten",
        "Enable archiving": "Archivierung aktivieren",
        "Test connection": "Verbindung testen",
        "Update now": "Jetzt aktualisieren",
    }.items():
        assert translations.get(required_translation) == expected

    module_php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    assert "final class MySkoda extends IPSModuleStrict" in module_php
    assert "IP-Symcon-MySkoda/1.0" in module_php
    assert "StructureTrait.php" not in module_php
    assert "HistoryTrait.php" in module_php

    php_sources = "\n".join(
        source.read_text(encoding="utf-8")
        for source in (ROOT / "MySkoda").rglob("*.php")
    )
    variables = (ROOT / "MySkoda" / "src" / "VariablesTrait.php").read_text(encoding="utf-8")
    history = (ROOT / "MySkoda" / "src" / "HistoryTrait.php").read_text(encoding="utf-8")
    core = (ROOT / "MySkoda" / "src" / "CoreTrait.php").read_text(encoding="utf-8")

    # Clean data module: no grouping/helper objects are created.
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

    # Variables are registered only when missing and keep stable technical idents.
    assert "registerVariableOnce" in variables
    assert "$this->Translate((string) $definition['name'])" in variables
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
        "ParkingState",
        "ChargingState",
    ]:
        assert f"'{stable_ident}'" in variables

    # German presentation remains complete while API/raw values stay stable.
    for source, german in {
        "State of charge": "Ladezustand",
        "Range": "Reichweite",
        "Mileage": "Kilometerstand",
        "Parking state": "Parkstatus",
        "Charging state": "Ladestatus",
        "Parked": "Geparkt",
        "Moving": "In Bewegung",
        "Driving": "In Fahrt",
        "Connect charging cable": "Ladekabel anschließen",
        "Charging active": "Laden aktiv",
        "Ready for charging": "Ladebereit",
        "Unknown": "Unbekannt",
    }.items():
        assert translations.get(source) == german

    assert "parkingStatePresentation" in variables
    for state in ["PARKED", "MOVING", "DRIVING", "UNKNOWN"]:
        assert f"'{state}'" in variables
    assert "$this->Translate($caption)" in variables

    # Existing variable presentations are not dynamically overwritten.
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

    # Archive: SoC, target SoC, charging power and mileage counter.
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

    # Invalid mileage never reaches the variable/archive as a normal update.
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

    # Documentation required for a useful Store review and safe setup.
    root_readme = (ROOT / "README.md").read_text(encoding="utf-8")
    module_readme = (ROOT / "MySkoda" / "README.md").read_text(encoding="utf-8")
    for text in [
        "IP-Symcon **8.1 oder neuer**",
        "https://public.api.connect.skoda-auto.cz/docs",
        "Installation",
        "Erste Einrichtung",
        "keine Dummy-Instanzen",
        "stabile Variablen-Idents",
        "Kilometerstand",
        "Zähler",
        "Geparkt",
        "Laden aktiv",
        "MIT-Lizenz",
    ]:
        assert text in root_readme

    for text in [
        "Voraussetzungen",
        "Installation und erste Einrichtung",
        "Konfiguration",
        "Statusdarstellungen",
        "Parkstatus",
        "Ladestatus",
        "Archivierung",
        "Fehlersuche",
        "Datenschutz und externe Dienste",
        "Öffentliche PHP-Befehle",
        "MIT-Lizenz",
    ]:
        assert text in module_readme

    changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
    assert "## 1.0 - 2026-09-06" in changelog
    assert "## 2." not in changelog
    assert "Parkstatus" in changelog
    assert not (ROOT / "docs").exists()


if __name__ == "__main__":
    main()
