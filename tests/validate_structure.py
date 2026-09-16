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

    assert GUID.match(library["id"])
    assert library["name"] == "MySkoda"
    assert library["version"] == "1.3"
    assert library["compatibility"]["version"] >= "8.1"
    assert GUID.match(module["id"])
    assert module["name"] == "MySkoda"
    assert module["prefix"] == "MSKODA"

    for required in [
        ROOT / "README.md",
        ROOT / "CHANGELOG.md",
        ROOT / "LICENSE",
        ROOT / "MySkoda" / "README.md",
        ROOT / "MySkoda" / "README_FIN_VIN.md",
        ROOT / "MySkoda" / "module.php",
        ROOT / "MySkoda" / "module.json",
        ROOT / "MySkoda" / "form.json",
        ROOT / "MySkoda" / "locale.json",
    ]:
        assert required.is_file(), required

    assert isinstance(translations, dict) and translations
    all_form_captions = captions(form.get("elements", [])) | captions(form.get("actions", [])) | captions(form.get("status", []))
    missing = sorted(caption for caption in all_form_captions if caption not in translations)
    assert not missing, f"Missing German form translations: {missing}"

    module_php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    variables = (ROOT / "MySkoda" / "src" / "VariablesTrait.php").read_text(encoding="utf-8")
    history = (ROOT / "MySkoda" / "src" / "HistoryTrait.php").read_text(encoding="utf-8")
    core = (ROOT / "MySkoda" / "src" / "CoreTrait.php").read_text(encoding="utf-8")
    openapi = (ROOT / "MySkoda" / "src" / "OpenApiTrait.php").read_text(encoding="utf-8")
    command = (ROOT / "MySkoda" / "src" / "CommandTrait.php").read_text(encoding="utf-8")
    api = (ROOT / "MySkoda" / "src" / "ApiTrait.php").read_text(encoding="utf-8")
    image = (ROOT / "MySkoda" / "src" / "ImageTrait.php").read_text(encoding="utf-8")
    diagnostics = (ROOT / "MySkoda" / "src" / "DiagnosticsTrait.php").read_text(encoding="utf-8")
    climate_selection = (ROOT / "MySkoda" / "src" / "ClimateSelectionTrait.php").read_text(encoding="utf-8")
    vin_decoder = (ROOT / "MySkoda" / "src" / "VinDecoderTrait.php").read_text(encoding="utf-8")
    vin_integration = (ROOT / "MySkoda" / "src" / "VinIntegrationTrait.php").read_text(encoding="utf-8")
    php_sources = "\n".join(path.read_text(encoding="utf-8") for path in (ROOT / "MySkoda").rglob("*.php"))

    assert "final class MySkoda extends IPSModuleStrict" in module_php
    assert "Symcon-MySkoda/1.3" in module_php
    assert "CommandTrait.php" in module_php
    assert "ImageTrait.php" in module_php
    assert "DiagnosticsTrait.php" in module_php
    assert "ClimateSelectionTrait.php" in module_php
    assert "VinDecoderTrait.php" in module_php
    assert "VinIntegrationTrait.php" in module_php
    assert "CommandConfirmationTrait.php" not in module_php

    assert re.search(r"<\?(?!php)", php_sources) is None
    assert "IPS_LogMessage" not in php_sources
    assert "IPS_SetProperty" not in php_sources
    assert "IPS_ApplyChanges" not in php_sources

    for forbidden in [
        "IPS_CreateInstance", "IPS_CreateCategory", "IPS_CreateLink"
    ]:
        assert forbidden not in php_sources

    for required in [
        "VEHICLE_IMAGE_IDENT = 'VehicleImage'",
        "IPS_CreateMedia(1)",
        "IPS_SetIdent($mediaId, self::VEHICLE_IMAGE_IDENT)",
        "IPS_SetMediaFile",
        "IPS_SetMediaContent",
        "vehicle.renderUrl",
        "RefreshVehicleImage",
        "syncVehicleImage(false)",
    ]:
        assert required in (image + module_php)

    for required in [
        "DiagnosePublicApiData",
        "ReadAttributeString('RawData')",
        "unusedLeafPaths",
        "usedLeafPaths",
        "{redacted-location}",
        "{redacted-license-plate}",
        "This diagnostic performs no additional API request",
    ]:
        assert required in diagnostics
    assert "request(" not in diagnostics

    assert "applyDefaultObjectIcons" in variables
    assert "IPS_SetIcon($variableId, $icon);" in variables
    for ident, icon in {
        "LastUpdate": "clock-rotate-left",
        "FullyChargedAt": "battery-full",
        "ApiKeyExpiresAtVar": "arrow-right-to-line",
    }.items():
        assert f"'{ident}' => '{icon}'" in variables

    for ident in [
        "StateOfCharge", "Range", "Mileage", "Locked", "DoorsOpen", "WindowsOpen",
        "Charging", "ChargePower", "TargetSOC", "ChargeMode", "Climate",
        "TargetTemperature", "ApiKeyWarning", "LastUpdate"
    ]:
        assert f"'{ident}'" in variables

    assert "'NewApiFeatures'" in openapi
    assert "RegisterPropertyBoolean('EnableChargingHistory', false)" in core
    assert "AC_SetLoggingStatus" in history
    assert "'STEP_SIZE' => 10" in variables
    assert "'vehicle.operations'" in core

    for required in [
        "RegisterAttributeString('PendingCommands', '{}')",
        "RegisterAttributeString('LastCommandResult', '')",
        "RegisterTimer('CommandConfirmTimer', 0",
        "executeOptimisticCommand",
        "ConfirmPending",
        "PendingCommands",
        "CommandStatus",
        "unset($pending[$ident]);",
        "$this->SetValue($ident, $desiredValue);",
        "$this->SetValue($ident, $previousValue);",
        "$error = trim($this->ReadAttributeString('LastError'));",
        "$status .= ' - ' . $error;",
    ]:
        assert required in command

    assert "scheduleCommandConfirmation" not in command
    assert "COMMAND_CONFIRM_DELAY_MS" not in command
    assert "commandPendingTimeoutSeconds" not in command
    assert "fetchVehicle(false)" not in command

    for ident in ["Charging", "TargetSOC", "ChargeMode", "Climate", "TargetTemperature"]:
        assert f"'{ident}'" in command

    for required in [
        "RegisterAttributeBoolean('TargetTemperatureOverride', false)",
        "WriteAttributeBoolean('TargetTemperatureOverride', true)",
        "ReadAttributeBoolean('TargetTemperatureOverride')",
        "WriteAttributeBoolean('TargetTemperatureOverride', false)",
        "commandRequestAction($Ident, $Value)",
        "if ((bool) $this->GetValue('Climate'))",
    ]:
        assert required in climate_selection
    assert "fetchVehicle(" not in climate_selection

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
        "NY",
        "Enyaq",
        "Elroq",
        "Karoq",
        "MEX",
        "PA",
        "PB",
        "PC",
        "AA",
        "decodeElroqPower",
        "decodeKaroqEngine",
    ]:
        assert required in (vin_decoder + vin_integration)

    assert "request(" not in vin_decoder
    assert "request(" not in vin_integration
    assert "updateVINDecodeCache($vin)" in vin_integration
    assert "if ($vinChanged)" in vin_integration
    assert "updateVINVariablesFromCache();" in vin_integration
    assert "ReadPropertyBoolean('CreateVINVariables')" in vin_decoder

    vin_panel = next(item for item in form["elements"] if item.get("caption") == "VIN / FIN decoding")
    vin_names = {item.get("name") for item in vin_panel.get("items", [])}
    for required in [
        "VINDecodeStructure", "VINDecodeManufacturer", "VINDecodeModel", "VINDecodeDrive",
        "VINDecodeProduction", "VINDecodeRestraint", "VINDecodeValidation", "CreateVINVariables"
    ]:
        assert required in vin_names

    bold_vin_captions = {
        item.get("caption")
        for item in vin_panel.get("items", [])
        if item.get("type") == "Label" and item.get("bold") is True
    }
    for required in [
        "VIN structure", "Manufacturer and origin", "Vehicle model", "Drive and power",
        "Production data", "Restraint system", "Check digit"
    ]:
        assert required in bold_vin_captions

    assert "The VIN interpretation is not an official Skoda data source." in "\n".join(
        str(item.get("caption", "")) for item in vin_panel.get("items", [])
    )

    assert "private function sendCommand" in api
    assert "if (!$response['ok'])" in api
    assert "$this->setApiError($response);" in api
    assert "sendCommand('POST', $path, null)" in api

    for source, german in {
        "Pending commands": "Ausstehende Befehle",
        "Command status": "Befehlsstatus",
        "Ready": "Bereit",
        "Waiting for confirmation: %s": "Warte auf Bestätigung: %s",
        "Command rejected: %s": "Befehl abgelehnt: %s",
        "Confirmed: %s": "Bestätigt: %s",
        "Diagnose vehicle images": "Fahrzeugbilder diagnostizieren",
        "Refresh vehicle image": "Fahrzeugbild aktualisieren",
        "Vehicle image": "Fahrzeugbild",
        "Diagnose Public API data": "Public-API-Daten diagnostizieren",
        "VIN / FIN decoding": "FIN / VIN entschlüsseln",
        "Manufacturer and origin": "Hersteller / Herkunft",
        "Vehicle model": "Modell / Fahrzeug",
        "Drive and power": "Antrieb / Leistung",
        "Production data": "Produktionsdaten",
        "Create VIN information variables": "FIN-Informationsvariablen anlegen",
        "VIN model year": "FIN Modelljahr",
        "India": "Indien",
        "Front-wheel drive": "Frontantrieb",
    }.items():
        assert translations.get(source) == german

    expected_sources = {
        "ApiTrait.php", "ClimateSelectionTrait.php", "CommandTrait.php", "CoreTrait.php",
        "DiagnosticsTrait.php", "HelpersTrait.php", "HistoryTrait.php", "ImageTrait.php",
        "NotificationTrait.php", "OpenApiTrait.php", "VariablesTrait.php", "VinDecoderTrait.php",
        "VinIntegrationTrait.php"
    }
    source_names = {path.name for path in (ROOT / "MySkoda" / "src").glob("*.php")}
    assert source_names == expected_sources

    root_readme = (ROOT / "README.md").read_text(encoding="utf-8")
    module_readme = (ROOT / "MySkoda" / "README.md").read_text(encoding="utf-8")
    vin_readme = (ROOT / "MySkoda" / "README_FIN_VIN.md").read_text(encoding="utf-8")
    for text in [
        "Befehlsausführung ab Version 1.1",
        "Serverantwort",
        "PendingCommands",
        "CommandStatus",
        "vorherige Wert",
        "README_FIN_VIN.md",
    ]:
        assert text in root_readme

    for text in [
        "Befehlslogik für Remote-Befehle",
        "2xx-Antwort",
        "PendingCommands",
        "CommandStatus",
        "Fehlertext",
        "standardmäßig **aus**",
        "Datenschutz und externe Dienste",
        "README_FIN_VIN.md",
        "MSKODA_GetVINData",
    ]:
        assert text in module_readme

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
    assert "FIN entschlüsseln" in changelog
    assert "MSKODA_GetVINData()" in changelog
    assert "## 1.2 - 2026-09-15" in changelog
    assert "VehicleImage" in changelog
    assert "renderUrl" in changelog
    assert "## 1.1 - 2026-09-14" in changelog
    assert "Serverantwort" in changelog
    assert "## 1.0 - 2026-09-06" in changelog
    assert "## 2." not in changelog

    old_brand = "IP" + "-Symcon"
    text_suffixes = {".md", ".json", ".php", ".py", ".yml", ".yaml"}
    for path in ROOT.rglob("*"):
        if not path.is_file() or path.suffix.lower() not in text_suffixes:
            continue
        text = path.read_text(encoding="utf-8")
        assert old_brand not in text, f"Old product naming in {path.relative_to(ROOT)}"


if __name__ == "__main__":
    main()
