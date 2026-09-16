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
        if isinstance(item, dict)
        and isinstance(item.get("caption"), str)
        and item.get("caption") != ""
    }


def main() -> None:
    library = load(ROOT / "library.json")
    module = load(ROOT / "MySkoda" / "module.json")
    form = load(ROOT / "MySkoda" / "form.json")
    locale = load(ROOT / "MySkoda" / "locale.json")
    translations = locale.get("translations", {}).get("de", {})

    assert GUID.match(library["id"])
    assert library["name"] == "MySkoda"
    assert library["version"] == "1.3"
    assert library["compatibility"]["version"] >= "8.1"
    assert GUID.match(module["id"])
    assert module["name"] == "MySkoda"
    assert module["prefix"] == "MSKODA"

    required_files = [
        ROOT / "README.md",
        ROOT / "CHANGELOG.md",
        ROOT / "LICENSE",
        ROOT / "MySkoda" / "README.md",
        ROOT / "MySkoda" / "README_FIN_VIN.md",
        ROOT / "MySkoda" / "module.php",
        ROOT / "MySkoda" / "module.json",
        ROOT / "MySkoda" / "form.json",
        ROOT / "MySkoda" / "locale.json",
    ]
    for path in required_files:
        assert path.is_file(), path

    all_form_captions = (
        captions(form.get("elements", []))
        | captions(form.get("actions", []))
        | captions(form.get("status", []))
    )
    missing = sorted(caption for caption in all_form_captions if caption not in translations)
    assert not missing, f"Missing German form translations: {missing}"

    module_php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    variables = (ROOT / "MySkoda" / "src" / "VariablesTrait.php").read_text(encoding="utf-8")
    history = (ROOT / "MySkoda" / "src" / "HistoryTrait.php").read_text(encoding="utf-8")
    core = (ROOT / "MySkoda" / "src" / "CoreTrait.php").read_text(encoding="utf-8")
    command = (ROOT / "MySkoda" / "src" / "CommandTrait.php").read_text(encoding="utf-8")
    climate = (ROOT / "MySkoda" / "src" / "ClimateSelectionTrait.php").read_text(encoding="utf-8")
    image = (ROOT / "MySkoda" / "src" / "ImageTrait.php").read_text(encoding="utf-8")
    diagnostics = (ROOT / "MySkoda" / "src" / "DiagnosticsTrait.php").read_text(encoding="utf-8")
    vin_decoder = (ROOT / "MySkoda" / "src" / "VinDecoderTrait.php").read_text(encoding="utf-8")
    vin_integration = (ROOT / "MySkoda" / "src" / "VinIntegrationTrait.php").read_text(encoding="utf-8")
    php_sources = "\n".join(path.read_text(encoding="utf-8") for path in (ROOT / "MySkoda").rglob("*.php"))

    assert "final class MySkoda extends IPSModuleStrict" in module_php
    assert "Symcon-MySkoda/1.3" in module_php
    assert "VinDecoderTrait.php" in module_php
    assert "VinIntegrationTrait.php" in module_php
    assert re.search(r"<\?(?!php)", php_sources) is None
    assert "IPS_LogMessage" not in php_sources
    assert "IPS_SetProperty" not in php_sources
    assert "IPS_ApplyChanges" not in php_sources

    for forbidden in ["IPS_CreateInstance", "IPS_CreateCategory", "IPS_CreateLink"]:
        assert forbidden not in php_sources

    assert "RegisterPropertyBoolean('EnableChargingHistory', false)" in core
    assert "AC_SetLoggingStatus" in history
    assert "applyDefaultObjectIcons" in variables
    assert "executeOptimisticCommand" in command
    assert "PendingCommands" in command
    assert "CommandStatus" in command
    assert "TargetTemperatureOverride" in climate

    for required in [
        "VEHICLE_IMAGE_IDENT = 'VehicleImage'",
        "vehicle.renderUrl",
        "RefreshVehicleImage",
    ]:
        assert required in (image + module_php)

    for required in [
        "DiagnosePublicApiData",
        "{redacted-location}",
        "{redacted-license-plate}",
    ]:
        assert required in diagnostics

    for required in [
        "GetVINData",
        "decodeVIN",
        "calculateVINCheckDigit",
        "VINDecodeFingerprint",
        "VINDecodeData",
        "CreateVINVariables",
        "VINWMI",
        "VINVDS",
        "VINVIS",
        "VINModel",
        "VINModelYear",
        "VINPlant",
        "VINCheckDigit",
        "Enyaq",
        "Elroq",
        "Karoq",
        "MEX",
    ]:
        assert required in (vin_decoder + vin_integration)

    assert "request(" not in vin_decoder
    assert "request(" not in vin_integration
    assert "updateVINDecodeCache($vin)" in vin_integration
    assert "if ($vinChanged)" in vin_integration
    assert "updateVINVariablesFromCache();" in vin_integration

    vin_panel = next(item for item in form["elements"] if item.get("caption") == "VIN / FIN decoding")
    vin_items = list(walk(vin_panel.get("items", [])))
    vin_names = {item.get("name") for item in vin_items}
    for required in [
        "VINDecodeStructure",
        "VINDecodeManufacturer",
        "VINDecodeModel",
        "VINDecodeDrive",
        "VINDecodeProduction",
        "VINDecodeRestraint",
        "VINDecodeValidation",
        "VINCodeManufacturer",
        "VINCodeModel",
        "VINCodeDrive",
        "VINCodeProduction",
        "VINCodeRestraint",
        "VINCodeValidation",
        "CreateVINVariables",
    ]:
        assert required in vin_names

    code_labels = {
        item.get("name"): item
        for item in vin_items
        if item.get("name", "").startswith("VINCode")
    }
    assert len(code_labels) == 6
    for item in code_labels.values():
        assert item.get("bold") is True
        assert item.get("width") == "120px"

    extra_headings = {
        "VIN structure",
        "Manufacturer and origin",
        "Vehicle model",
        "Drive and power",
        "Production data",
        "Restraint system",
        "Check digit",
    }
    static_bold_captions = {
        item.get("caption")
        for item in vin_panel.get("items", [])
        if item.get("type") == "Label" and item.get("bold") is True
    }
    assert not (static_bold_captions & extra_headings)

    assert translations.get("VIN / FIN decoding") == "FIN / VIN entschlüsseln"
    assert translations.get("Create VIN information variables") == "FIN-Informationsvariablen anlegen"

    root_readme = (ROOT / "README.md").read_text(encoding="utf-8")
    module_readme = (ROOT / "MySkoda" / "README.md").read_text(encoding="utf-8")
    vin_readme = (ROOT / "MySkoda" / "README_FIN_VIN.md").read_text(encoding="utf-8")

    for text in [
        "## Voraussetzungen",
        "## Installation",
        "## Erste Einrichtung",
        "MySkoda/README.md",
        "MySkoda/README_FIN_VIN.md",
        "keine offiziellen Fahrzeugstammdaten von Škoda",
    ]:
        assert text in root_readme

    for text in [
        "## 2. Voraussetzungen",
        "## 3. Installation",
        "## 4. Einrichten der Instanz",
        "## 5. Konfiguration",
        "README_FIN_VIN.md",
        "MSKODA_GetVINData",
        "Datenschutz und externe Dienste",
        "keine offiziellen Fahrzeugstammdaten von Škoda",
    ]:
        assert text in module_readme

    assert "## 6. FIN / VIN entschlüsseln" not in module_readme

    for text in [
        "# FIN / VIN entschlüsseln",
        "keine offizielle Škoda-Datenquelle",
        "WMI",
        "VDS",
        "VIS",
        "Prüfzeichen",
        "Enyaq",
        "Elroq",
        "Karoq",
        "MSKODA_GetVINData",
    ]:
        assert text in vin_readme

    changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
    assert "## 1.3 - 2026-09-16" in changelog
    assert "FIN / VIN entschlüsseln" in changelog
    assert "MSKODA_GetVINData()" in changelog
    assert "README_FIN_VIN.md" in changelog
    assert "## 1.2 - 2026-09-15" in changelog

    old_brand = "IP" + "-Symcon"
    text_suffixes = {".md", ".json", ".php", ".py", ".yml", ".yaml"}
    for path in ROOT.rglob("*"):
        if not path.is_file() or path.suffix.lower() not in text_suffixes:
            continue
        text = path.read_text(encoding="utf-8")
        assert old_brand not in text, f"Old product naming in {path.relative_to(ROOT)}"


if __name__ == "__main__":
    main()
