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
    assert "final class MySkoda extends IPSModule" in module_php
    assert "extends IPSModuleStrict" not in module_php
    assert "IP-Symcon-MySkoda/1.0" in module_php
    assert "StructureTrait.php" in module_php
    assert "HistoryTrait.php" in module_php
    assert "V22" not in module_php

    php_sources = "\n".join(
        source.read_text(encoding="utf-8")
        for source in (ROOT / "MySkoda").rglob("*.php")
    )
    variables = (ROOT / "MySkoda" / "src" / "VariablesTrait.php").read_text(encoding="utf-8")
    structure = (ROOT / "MySkoda" / "src" / "StructureTrait.php").read_text(encoding="utf-8")
    history = (ROOT / "MySkoda" / "src" / "HistoryTrait.php").read_text(encoding="utf-8")
    core = (ROOT / "MySkoda" / "src" / "CoreTrait.php").read_text(encoding="utf-8")

    # Variables are registered once and keep stable technical idents.
    assert "registerVariableOnce" in variables
    assert "placeVariableInGroup($ident)" in variables
    assert "maintainGroupedAction" in variables
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

    # Dummy instances contain the real variables directly.
    assert "{485D0419-BE97-4548-AA9C-C083EB82E61E}" in structure
    assert "IPS_CreateInstance" in structure
    assert "IPS_SetName" in structure
    assert "IPS_SetIcon" in structure
    assert "IPS_SetParent($variableId, $groupId)" in structure
    assert "protected function GetIDForIdent" in structure
    assert "protected function SetValue" in structure
    assert "protected function GetValue" in structure
    assert "findObjectByIdentRecursive" in structure
    assert "IPS_CreateLink" not in php_sources
    assert "IPS_SetLinkTargetID" not in php_sources
    assert "MSKODA_Link_" not in php_sources

    for group_ident in [
        "MSKODA_GroupVehicle",
        "MSKODA_GroupStatus",
        "MSKODA_GroupCharging",
        "MSKODA_GroupClimate",
        "MSKODA_GroupLocation",
        "MSKODA_GroupDiagnostics",
        "MSKODA_GroupCharts",
        "MSKODA_GroupLastUpdate",
    ]:
        assert group_ident in structure

    # Existing variable metadata is not re-registered dynamically.
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

    # Charging history is initialized once and remains non-destructive.
    assert "ChargingHistoryInitialized" in core
    assert "ReadAttributeBoolean('ChargingHistoryInitialized')" in history
    assert "WriteAttributeBoolean('ChargingHistoryInitialized', true)" in history
    assert "getGroupId('charts')" in history
    assert "IPS_SetParent($chartId, $chartsGroupId)" in history
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

    for forbidden in [
        "removeObsolete",
        "obsoleteVisualization",
        "Migration",
        "migrate",
        "Legacy",
        "legacy",
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
        "StructureTrait.php",
        "VariablesTrait.php",
    }
    source_names = {path.name for path in (ROOT / "MySkoda" / "src").glob("*.php")}
    assert source_names == expected_sources

    root_readme = (ROOT / "README.md").read_text(encoding="utf-8")
    module_readme = (ROOT / "MySkoda" / "README.md").read_text(encoding="utf-8")
    assert "Dummy-Instanzen" in root_readme
    assert "echten MySkoda-Variablen" in root_readme
    assert "MSKODA_GroupVehicle" in module_readme
    assert "rekursiv" in root_readme.lower()

    changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
    assert "## 1.0 - 2026-09-06" in changelog
    assert "## 2." not in changelog
    assert not (ROOT / "docs").exists()


if __name__ == "__main__":
    main()
