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

    module_php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    assert "final class MySkoda extends IPSModuleStrict" in module_php
    assert "IP-Symcon-MySkoda/1.0" in module_php
    assert "HistoryTrait.php" in module_php
    assert "StructureTrait.php" not in module_php
    assert "V22" not in module_php

    php_sources = "\n".join(
        source.read_text(encoding="utf-8")
        for source in (ROOT / "MySkoda").rglob("*.php")
    )
    variables = (ROOT / "MySkoda" / "src" / "VariablesTrait.php").read_text(encoding="utf-8")
    history = (ROOT / "MySkoda" / "src" / "HistoryTrait.php").read_text(encoding="utf-8")
    core = (ROOT / "MySkoda" / "src" / "CoreTrait.php").read_text(encoding="utf-8")

    # Module variables are direct children and are identified by stable idents.
    assert "registerVariableOnce" in variables
    assert "GetIDForIdent($ident)" in variables
    assert "RegisterVariableBoolean" in variables
    assert "RegisterVariableInteger" in variables
    assert "RegisterVariableFloat" in variables
    assert "RegisterVariableString" in variables
    assert "IPS_SetParent" not in variables
    assert "UnregisterVariable" not in php_sources
    assert "IPS_DeleteVariable" not in php_sources
    assert "protected function GetIDForIdent" not in php_sources

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

    # Variable definitions are not rebuilt dynamically from API responses.
    assert "CHARGE_MODES" in variables
    assert "AvailableChargeModes" in variables
    assert "updateChargeModePresentation" not in php_sources
    assert "ChargeModeMap" not in php_sources
    assert "moveManagedVariable" not in php_sources

    # No direct custom-presentation writes: module defaults are set on creation.
    for forbidden in [
        "IPS_SetVariableCustomPresentation",
        "IPS_SetVariableCustomProfile",
        "IPS_CreateVariableProfile",
        "IPS_CreateTemplate",
        "IPS_SetTemplate",
    ]:
        assert forbidden not in php_sources

    # Charging history is initialized once and never destructively managed.
    assert "ChargingHistoryInitialized" in core
    assert "ReadAttributeBoolean('ChargingHistoryInitialized')" in history
    assert "WriteAttributeBoolean('ChargingHistoryInitialized', true)" in history
    assert "AC_GetLoggingStatus" in history
    assert "AC_SetLoggingStatus" in history
    assert "MSKODA_ChargingHistory" in history
    assert "IPS_CreateMedia(4)" in history
    for forbidden in [
        "AC_SetAggregationType",
        "AC_DeleteVariableData",
        "AC_ReAggregateVariable",
        "AC_SetCompaction",
        "AC_SetGraphStatus",
        "IPS_DeleteMedia",
    ]:
        assert forbidden not in php_sources

    # No migration or cleanup routines are part of the runtime code.
    for forbidden in [
        "removeObsolete",
        "obsoleteVisualization",
        "Migration",
        "migrate",
        "Legacy",
        "legacy",
    ]:
        assert forbidden not in php_sources

    # Module code must not reconfigure other instances.
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

    changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
    assert "## 1.0 - 2026-09-06" in changelog
    assert "## 2." not in changelog

    assert not (ROOT / "MySkoda" / "src" / "StructureTrait.php").exists()
    assert not (ROOT / "docs").exists()


if __name__ == "__main__":
    main()
