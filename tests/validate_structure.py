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
    assert library["version"] == "1.1"
    assert library["compatibility"]["version"] >= "8.1"
    assert GUID.match(module["id"])
    assert module["name"] == "MySkoda"
    assert module["prefix"] == "MSKODA"

    for required in [
        ROOT / "README.md",
        ROOT / "CHANGELOG.md",
        ROOT / "LICENSE",
        ROOT / "MySkoda" / "README.md",
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
    confirmation = (ROOT / "MySkoda" / "src" / "CommandConfirmationTrait.php").read_text(encoding="utf-8")
    php_sources = "\n".join(path.read_text(encoding="utf-8") for path in (ROOT / "MySkoda").rglob("*.php"))

    assert "final class MySkoda extends IPSModuleStrict" in module_php
    assert "IP-Symcon-MySkoda/1.1" in module_php
    assert "CommandTrait.php" in module_php
    assert "CommandConfirmationTrait.php" in module_php
    assert "MySkodaCommandConfirmationTrait::applyApiValue insteadof MySkodaCommandTrait" in module_php
    assert "MySkodaCommandConfirmationTrait::sendCommand insteadof MySkodaCommandTrait, MySkodaApiTrait" in module_php

    assert re.search(r"<\?(?!php)", php_sources) is None
    assert "IPS_LogMessage" not in php_sources
    assert "IPS_SetProperty" not in php_sources
    assert "IPS_ApplyChanges" not in php_sources

    for forbidden in [
        "IPS_CreateInstance", "IPS_CreateCategory", "IPS_CreateLink", "IPS_CreateMedia",
        "IPS_SetName", "IPS_SetHidden", "IPS_SetIcon", "IPS_SetPosition"
    ]:
        assert forbidden not in php_sources

    for ident in [
        "StateOfCharge", "Range", "Mileage", "Locked", "DoorsOpen", "WindowsOpen",
        "Charging", "ChargePower", "TargetSOC", "ChargeMode", "Climate",
        "TargetTemperature", "ApiKeyWarning", "LastUpdate"
    ]:
        assert f"'{ident}'" in variables

    assert "'NewApiFeatures'" in openapi
    assert "RegisterPropertyBoolean('EnableChargingHistory', false)" in core
    assert "AC_SetLoggingStatus" in history

    # Version 1.1 optimistic command handling.
    for required in [
        "RegisterAttributeString('PendingCommands', '{}')",
        "RegisterAttributeString('LastCommandResult', '')",
        "RegisterTimer('CommandConfirmTimer', 0",
        "COMMAND_CONFIRM_DELAY_MS = 60000",
        "executeOptimisticCommand",
        "ConfirmPending",
        "scheduleCommandConfirmation",
        "PendingCommands",
        "CommandStatus",
    ]:
        assert required in command

    for ident in ["Charging", "TargetSOC", "ChargeMode", "Climate", "TargetTemperature"]:
        assert f"'{ident}'" in command

    # The next successful portal response is authoritative.
    assert "private function applyApiValue" in confirmation
    assert "$matches = $this->commandValuesEqual($expected, $apiValue);" in confirmation
    assert "$this->SetValue($ident, $apiValue);" in confirmation
    assert "unset($pending[$ident]);" in confirmation
    assert "API returned a different value; portal value is authoritative and was restored." in confirmation
    assert "mismatchCount" not in confirmation
    assert "COMMAND_MAX_MISMATCH_RESPONSES" not in confirmation
    assert "COMMAND_MIN_MISMATCH_ROLLBACK_SECONDS" not in confirmation

    # HTTP error responses are definite rejections; only missing transport status stays uncertain.
    assert "$uncertain = $curlError !== '' || $status === 0;" in confirmation
    assert "status === 408" not in confirmation
    assert "status >= 500" not in confirmation
    assert "uncertain ? 'uncertain' : 'rejected'" in confirmation

    for source, german in {
        "Pending commands": "Ausstehende Befehle",
        "Command status": "Befehlsstatus",
        "Ready": "Bereit",
        "Waiting for confirmation: %s": "Warte auf Bestätigung: %s",
        "Transmission uncertain: %s": "Übertragung unklar: %s",
        "Command rejected: %s": "Befehl abgelehnt: %s",
        "Confirmed: %s": "Bestätigt: %s",
        "Not confirmed: %s": "Nicht bestätigt: %s",
    }.items():
        assert translations.get(source) == german

    expected_sources = {
        "ApiTrait.php", "CommandTrait.php", "CommandConfirmationTrait.php", "CoreTrait.php",
        "HelpersTrait.php", "HistoryTrait.php", "NotificationTrait.php", "OpenApiTrait.php",
        "VariablesTrait.php"
    }
    source_names = {path.name for path in (ROOT / "MySkoda" / "src").glob("*.php")}
    assert source_names == expected_sources

    root_readme = (ROOT / "README.md").read_text(encoding="utf-8")
    module_readme = (ROOT / "MySkoda" / "README.md").read_text(encoding="utf-8")
    for text in [
        "Befehlsbestätigung ab Version 1.1",
        "erste erfolgreiche Fahrzeugantwort",
        "Portalwert sofort übernommen",
        "PendingCommands",
        "CommandStatus",
        "60 Sekunden",
    ]:
        assert text in root_readme

    for text in [
        "Pending- und Bestätigungslogik für Remote-Befehle",
        "erste erfolgreiche Fahrzeugabfrage",
        "gilt **immer der Portalwert**",
        "PendingCommands",
        "CommandStatus",
        "60 Sekunden",
        "standardmäßig **aus**",
        "Datenschutz und externe Dienste",
    ]:
        assert text in module_readme

    changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
    assert "## 1.1 - 2026-09-14" in changelog
    assert "Portalwert" in changelog
    assert "## 1.0 - 2026-09-06" in changelog
    assert "## 2." not in changelog


if __name__ == "__main__":
    main()
