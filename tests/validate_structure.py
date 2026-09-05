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
    assert library["version"] == "2.0"
    assert module["name"] == "MySkoda"
    assert module["vendor"] == "taloriko"
    assert module["prefix"].isalnum()
    assert isinstance(locale.get("translations", {}).get("de"), dict)

    all_elements = list(walk(form["elements"]))
    names = {element.get("name") for element in all_elements}
    assert "VehicleDesign" not in names
    assert "HideVisualizationTitle" not in names
    target = next(element for element in all_elements if element.get("name") == "NotificationInstanceID")
    assert target.get("type") == "SelectInstance"

    panels = {element.get("caption") for element in form["elements"] if element.get("type") == "ExpansionPanel"}
    assert {"Connection", "Polling and control", "Notifications", "Advanced", "Help and documentation"}.issubset(panels)
    assert "Visualization settings" not in panels

    php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    assert "class MySkoda extends IPSModuleStrict" in php
    assert "IP-Symcon-MySkoda/2.0" in php
    for legacy in ["BootstrapV", "CoreV19Trait", "Visualization", "NotificationV19Trait", "PresentationTrait"]:
        assert legacy not in php

    php_sources = "\n".join(source.read_text(encoding="utf-8") for source in (ROOT / "MySkoda").rglob("*.php"))
    assert "SetVisualizationType(1)" not in php_sources
    assert "SetVisualizationType(0)" in php_sources
    assert "GetVisualizationTile" not in php_sources
    assert "UpdateVisualizationValue" not in php_sources
    assert "RefreshVisuals" not in php_sources
    assert "VehicleDesign" not in php_sources
    assert "IPS_SetHiddenTitle" not in php_sources
    assert "VARIABLE_PRESENTATION_WEB_CONTENT" not in php_sources
    assert "VARIABLE_PRESENTATION_LEGACY" not in php_sources
    assert "IPS_CreateVariableProfile" not in php_sources
    assert "IPS_CreateTemplate" not in php_sources
    assert "IPS_SetTemplate" not in php_sources
    assert "IPS_LogMessage" not in php_sources
    assert "IPS_SetProperty" not in php_sources
    assert "IPS_ApplyChanges" not in php_sources
    assert "SunroofOpen" in php_sources
    assert "IPS_GetInstanceListByModuleType(6)" in php_sources

    assert not (ROOT / "MySkoda" / "module.html").exists()
    assert not (ROOT / "docs" / "VISUALIZATION.md").exists()

    src_names = {path.name for path in (ROOT / "MySkoda" / "src").glob("*.php")}
    assert src_names == {
        "ApiTrait.php",
        "CoreTrait.php",
        "HelpersTrait.php",
        "NotificationTrait.php",
        "OpenApiTrait.php",
        "VariablesTrait.php",
    }


if __name__ == "__main__":
    main()
