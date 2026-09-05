from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
GUID = re.compile(r"^\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}$")


def load(path: Path):
    with path.open("r", encoding="utf-8") as handle:
        return json.load(handle)


def main() -> None:
    library = load(ROOT / "library.json")
    module = load(ROOT / "MySkoda" / "module.json")
    form = load(ROOT / "MySkoda" / "form.json")
    locale = load(ROOT / "MySkoda" / "locale.json")

    assert GUID.match(library["id"]), "Invalid library GUID"
    assert GUID.match(module["id"]), "Invalid module GUID"
    assert library["compatibility"]["version"] >= "8.1"
    assert library["version"] == "1.6"
    assert module["name"] == "MySkoda"
    assert module["prefix"].isalnum()
    assert isinstance(form.get("elements"), list)
    assert isinstance(form.get("actions"), list)
    assert isinstance(locale.get("translations", {}).get("de"), dict)

    php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    assert "class MySkoda extends IPSModuleStrict" in php

    php_sources = "\n".join(
        source.read_text(encoding="utf-8")
        for source in (ROOT / "MySkoda").rglob("*.php")
    )
    assert "VARIABLE_PRESENTATION_LEGACY" not in php_sources
    assert "IPS_CreateVariableProfile" not in php_sources
    assert "IPS_SetVariableProfileAssociation" not in php_sources
    assert "VARIABLE_PRESENTATION_ENUMERATION" in php_sources
    assert "VARIABLE_PRESENTATION_WEB_CONTENT" in php_sources
    assert "SetVisualizationType(1)" in php_sources
    assert "GetVisualizationTile" in php_sources
    assert "UpdateVisualizationValue" in php_sources
    assert "MSKODA_RefreshVisuals" in php_sources
    assert "VehicleTile" in php_sources  # hidden compatibility path for <= 1.5
    assert "LastUpdateAge" in php_sources

    tile = (ROOT / "MySkoda" / "module.html").read_text(encoding="utf-8")
    assert "handleMessage" in tile
    assert "batteryColor" in tile
    assert 'id="bat1"' in tile and 'id="bat4"' in tile
    assert "TileVisu" not in tile  # no copied branding/source markup


if __name__ == "__main__":
    main()
