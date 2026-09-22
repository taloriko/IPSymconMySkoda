from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
GUID = re.compile(r"^\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}$")
TYPES = {"BOOLEAN": "Boolean", "INTEGER": "Integer", "FLOAT": "Float", "STRING": "String"}


def load(path: Path):
    return json.loads(path.read_text(encoding="utf-8"))


def require(condition: bool, message: str) -> None:
    if not condition:
        raise AssertionError(message)


def walk(items):
    for item in items:
        if not isinstance(item, dict):
            continue
        yield item
        if isinstance(item.get("items"), list):
            yield from walk(item["items"])


def main() -> None:
    for path in ["README.md", "MySkoda/README.md", "MySkoda/README_FIN_VIN.md", "tests/README.md", "LICENSE", "CHANGELOG.md", "tests/public_api_contract.json"]:
        require((ROOT / path).is_file(), f"Missing file: {path}")
    library = load(ROOT / "library.json")
    module = load(ROOT / "MySkoda/module.json")
    form = load(ROOT / "MySkoda/form.json")
    translations = load(ROOT / "MySkoda/locale.json")["translations"]["de"]
    require(bool(GUID.fullmatch(library["id"])), "Invalid library GUID")
    require(bool(GUID.fullmatch(module["id"])), "Invalid module GUID")
    require(library["name"] == module["name"] == "MySkoda", "Invalid module name")
    require(module["prefix"] == "MSKODA", "Unexpected public prefix")
    require(tuple(map(int, library["compatibility"]["version"].split("."))) >= (8, 1), "Symcon 8.1 required")
    require(bool(re.fullmatch(r"\d+\.\d+(?:\.\d+)?", library["version"])), "Invalid version")

    php = {path.name: path.read_text(encoding="utf-8") for path in (ROOT / "MySkoda").rglob("*.php")}
    sources = "\n".join(php.values())
    entry = php["module.php"]
    require("final class MySkoda extends IPSModuleStrict" in entry, "Strict module class missing")
    require(f"Symcon-MySkoda/{library['version']}" in entry, "User-Agent/version mismatch")
    for relative in re.findall(r"require_once __DIR__ \. '([^']+)'", entry):
        require((ROOT / "MySkoda" / relative.lstrip("/")).is_file(), f"Missing include: {relative}")
    require("PublicApiVariablesTrait.php" in entry, "Public API data trait missing")
    for forbidden in ["IPS_DeleteVariable(", "IPS_CreateInstance(", "IPS_CreateCategory(", "IPS_CreateLink(", "IPS_SetProperty(", "IPS_ApplyChanges(", "breakingTypeIdents", "NewApiFeatures", "ensureApiDiscoveryVariable", "DiagnosticsTrait"]:
        require(forbidden not in sources, f"Unexpected runtime code: {forbidden}")
    require(re.search(r"<\?(?!php)", sources) is None, "Use complete PHP tags")
    for forbidden in ["IPS_SetName(", "IPS_SetPosition(", "applyDefaultObjectIcons", "applyManagedObjectPositions"]:
        require(forbidden not in php["VariablesTrait.php"], f"Unexpected variable metadata writer: {forbidden}")
    require(php["ImageTrait.php"].count("IPS_SetPosition(") == 1, "Image position is assigned only at creation")

    public = set(re.findall(r"public function\s+(\w+)\s*\(", sources))
    lifecycle = {"Create", "ApplyChanges", "RequestAction", "GetConfigurationForm"}
    api = public - lifecycle
    form_text = json.dumps(form, ensure_ascii=False)
    for name in re.findall(r"MSKODA_(\w+)\(", form_text):
        require(name in api, f"Form references missing function: {name}")
    expected_actions = {"Update now", "Refresh vehicle image", "Show raw vehicle response"}
    actual_actions = {item.get("caption") for item in walk(form["actions"]) if item.get("type") == "Button"}
    require(actual_actions == expected_actions, "Unexpected action buttons")
    require("Test notification" in {item.get("caption") for item in walk(form["elements"])}, "Notification test missing")
    for section in ["elements", "actions", "status"]:
        for item in walk(form[section]):
            caption = item.get("caption")
            if caption:
                require(caption in translations, f"Missing German caption: {caption}")
    for text in re.findall(r"\$this->Translate\('([^']+)'\)", sources):
        require(text in translations, f"Missing runtime translation: {text}")
    for name in ["GetLastVehicleResponseRaw", "TestNotification", "RefreshVehicleImage", "GetVINData", "SetChargingLimit", "SetChargeMode"]:
        require(name in api, f"Public function missing: {name}")
    for name in ["TestConnection", "GetRawData", "DiagnoseVehicleImages", "DiagnosePublicApiData", "RefreshApiDefinition"]:
        require(name not in api, f"Unexpected developer function: {name}")
    for marker in ["LastVehicleResponseRaw", "RawData", "PendingCommands", "TargetTemperatureOverride", "VINDecodeFingerprint", "SendDebug("]:
        require(marker in sources, f"Required runtime/support feature missing: {marker}")
    require("request(" not in php["VinDecoderTrait.php"], "VIN decoding must be local")
    require("request(" not in php["VinIntegrationTrait.php"], "VIN integration must be local")

    # Assert the public contract independently of manually maintained README layout.
    definitions = {}
    for ident, caption, kind, position in re.findall(r"\$this->variable\('([^']+)',\s*'([^']+)',\s*VARIABLETYPE_(\w+),\s*(\d+)", sources):
        require(ident not in definitions, f"Duplicate definition: {ident}")
        require(caption in translations, f"Missing variable translation: {caption}")
        definitions[ident] = (int(position), TYPES[kind])
    for ident, caption, kind, position in re.findall(r"'ident'\s*=>\s*'([^']+)',\s*'name'\s*=>\s*'([^']+)',\s*'type'\s*=>\s*VARIABLETYPE_(\w+),\s*'position'\s*=>\s*(\d+)", php["CommandTrait.php"]):
        require(ident not in definitions, f"Duplicate definition: {ident}")
        require(caption in translations, f"Missing variable translation: {caption}")
        definitions[ident] = (int(position), TYPES[kind])
    for ident, caption, position in re.findall(r"\['(VIN\w+)',\s*'([^']+)',\s*(\d+)\]", php["VinDecoderTrait.php"]):
        require(ident not in definitions, f"Duplicate definition: {ident}")
        require(caption in translations, f"Missing variable translation: {caption}")
        definitions[ident] = (int(position), "String")
    require(len(definitions) == 72, f"Expected 72 variable definitions, got {len(definitions)}")
    contract = load(ROOT / "tests" / "public_api_contract.json")
    expected_variables = {ident: tuple(spec) for ident, spec in contract["variables"].items()}
    require(definitions == expected_variables, f"Variable contract mismatch: {set(definitions.items()) ^ set(expected_variables.items())}")
    expected_functions = set(contract["public_functions"])
    require(len(expected_functions) == len(contract["public_functions"]), "Duplicate public function in contract")
    require(api == expected_functions, f"Public API contract mismatch: {api ^ expected_functions}")
    print(f"Structure validated: {len(definitions)} variables, {len(api)} public functions.")


if __name__ == "__main__":
    main()
