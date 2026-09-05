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

    assert GUID.match(library["id"]), "Invalid library GUID"
    assert GUID.match(module["id"]), "Invalid module GUID"
    assert library["compatibility"]["version"] >= "8.1"
    assert library["version"] == "1.8"
    assert module["name"] == "MySkoda"
    assert module["prefix"].isalnum()
    assert isinstance(form.get("elements"), list)
    assert isinstance(form.get("actions"), list)
    assert isinstance(locale.get("translations", {}).get("de"), dict)

    all_elements = list(walk(form["elements"]))
    vehicle_design = next((element for element in all_elements if element.get("name") == "VehicleDesign"), None)
    assert vehicle_design is not None, "VehicleDesign configuration is missing"
    assert vehicle_design.get("type") == "Select"
    design_values = {option.get("value") for option in vehicle_design.get("options", [])}
    assert {"auto", "enyaq", "elroq", "epiq", "generic"}.issubset(design_values)
    panels = {element.get("caption") for element in form["elements"] if element.get("type") == "ExpansionPanel"}
    assert {"Connection and vehicle", "Polling and control", "Notifications", "Advanced", "Help and documentation"}.issubset(panels)

    php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    assert "class MySkoda extends IPSModuleStrict" in php
    assert "BootstrapV17Trait.php" in php
    assert "VisualizationV17Trait.php" in php
    assert "IP-Symcon-MySkoda/1.8" in php

    php_sources = "\n".join(source.read_text(encoding="utf-8") for source in (ROOT / "MySkoda").rglob("*.php"))
    assert "VARIABLE_PRESENTATION_LEGACY" not in php_sources
    assert "IPS_CreateVariableProfile" not in php_sources
    assert "IPS_SetVariableProfileAssociation" not in php_sources
    assert "VARIABLE_PRESENTATION_ENUMERATION" in php_sources
    assert "VARIABLE_PRESENTATION_WEB_CONTENT" in php_sources
    assert "SetVisualizationType(1)" in php_sources
    assert "GetVisualizationTile" in php_sources
    assert "UpdateVisualizationValue" in php_sources
    assert "MSKODA_RefreshVisuals" in php_sources
    assert "VehicleTile" in php_sources
    assert "LastUpdateAge" in php_sources
    assert "VehicleDesign" in php_sources
    assert "status.detail.sunroof" in php_sources

    tile = (ROOT / "MySkoda" / "module.html").read_text(encoding="utf-8")
    assert "handleMessage" in tile
    assert "batteryColor" in tile
    assert 'id="bat1"' in tile and 'id="bat4"' in tile
    assert "CAR_DESIGNS" in tile
    assert "enyaq:" in tile and "elroq:" in tile and "epiq:" in tile and "generic:" in tile
    assert 'id="lockLine"' in tile
    assert 'id="rowSunroof"' not in tile and 'id="rowTrunk"' not in tile and 'id="rowBonnet"' not in tile
    assert "sessionStorage" in tile
    assert "visibilitychange" in tile and "ResizeObserver" in tile
    assert "overflow:hidden" in tile
    assert "TileVisu" not in tile

    visu_doc = (ROOT / "docs" / "VISUALIZATION.md").read_text(encoding="utf-8")
    assert "Enyaq" in visu_doc and "Elroq" in visu_doc and "Epiq" in visu_doc
    assert "FIN allein" in visu_doc


if __name__ == "__main__":
    main()
