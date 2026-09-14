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

    assert set(library) == {
        "id", "author", "name", "url", "compatibility", "version", "build", "date"
    }
    assert GUID.match(library["id"])
    assert library["author"] == "taloriko"
    assert library["name"] == "MySkoda"
    assert library["url"] == "https://github.com/taloriko/IPSymconMySkoda"
    assert library["compatibility"]["version"] >= "8.1"
    assert library["version"] == "1.1"
    assert library["build"] == 0
    assert isinstance(library["date"], int) and library["date"] > 0

    assert set(module) == {
        "id", "name", "type", "vendor", "aliases", "parentRequirements",
        "childRequirements", "implemented", "prefix", "url"
    }
    assert GUID.match(module["id"])
    assert module["name"] == "MySkoda"
    assert module["type"] == 3
    assert module["vendor"] == "Škoda"
    assert module["aliases"] == ["MySkoda Fahrzeug", "Škoda Fahrzeug"]
    assert module["prefix"] == "MSKODA"
    assert module["url"].startswith("https://github.com/taloriko/IPSymconMySkoda/")

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
    all_form_captions = set()
    all_form_captions |= captions(form.get("elements", []))
    all_form_captions |= captions(form.get("actions", []))
    all_form_captions |= captions(form.get("status", []))
    missing_translations = sorted(c for c in all_form_captions if c not in translations)
    assert not missing_translations, f"Missing German form translations: {missing_translations}"

    elements = list(walk(form["elements"]))
    names = {element.get("name") for element in elements}
    for required in {
        "VIN", "APIToken", "Interval", "EnableRemote", "ClimateWithoutExternalPower",
        "SPIN", "ShowDetails", "EnableChargingHistory", "NotifyKeyExpiry",
        "NotificationInstanceID"
    }:
        assert required in names

    for source, german in {
        "Connection": "Verbindung",
        "Polling and control": "Abfrage und Steuerung",
        "Archiving": "Archivierung",
        "Archive vehicle data": "Fahrzeugdaten archivieren",
        "Notifications": "Mitteilungen",
        "Additional data": "Zusätzliche Daten",
        "Test connection": "Verbindung testen",
        "Update now": "Jetzt aktualisieren",
        "New API functions": "Neue API-Funktionen",
        "Pending commands": "Ausstehende Befehle",
        "Command status": "Befehlsstatus",
        "Ready": "Bereit",
    }.items():
        assert translations.get(source) == german

    archive_panels = [
        element for element in form.get("elements", [])
        if isinstance(element, dict) and element.get("caption") == "Archiving"
    ]
    assert len(archive_panels) == 1
    assert archive_panels[0].get("expanded") is True
    archive_items = list(walk(archive_panels[0].get("items", [])))
    archive_checkbox = [item for item in archive_items if item.get("name") == "EnableChargingHistory"]
    assert len(archive_checkbox) == 1
    assert archive_checkbox[0].get("type") == "CheckBox"

    module_php = (ROOT / "MySkoda" / "module.php").read_text(encoding="utf-8")
    variables = (ROOT / "MySkoda" / "src" / "VariablesTrait.php").read_text(encoding="utf-8")
    history = (ROOT / "MySkoda" / "src" / "HistoryTrait.php").read_text(encoding="utf-8")
    core = (ROOT / "MySkoda" / "src" / "CoreTrait.php").read_text(encoding="utf-8")
    openapi = (ROOT / "MySkoda" / "src" / "OpenApiTrait.php").read_text(encoding="utf-8")
    command = (ROOT / "MySkoda" / "src" / "CommandTrait.php").read_text(encoding="utf-8")
    php_sources = "\n".join(
        source.read_text(encoding="utf-8")
        for source in (ROOT / "MySkoda").rglob("*.php")
    )

    assert "final class MySkoda extends IPSModuleStrict" in module_php
    assert "IP-Symcon-MySkoda/1.1" in module_php
    assert "CommandTrait.php" in module_php
    assert "MySkodaCommandTrait" in module_php
    assert "CommandTrait::RequestAction insteadof MySkodaCoreTrait" in module_php
    assert "CommandTrait::updateCoreValues insteadof MySkodaVariablesTrait" in module_php
    assert "CommandTrait::sendCommand insteadof MySkodaApiTrait" in module_php
    assert "StructureTrait.php" not in module_php
    assert "$this->refreshOpenApi(false);" in module_php

    assert re.search(r"<\?(?!php)", php_sources) is None
    assert "IPS_LogMessage" not in php_sources

    for forbidden in [
        "IPS_CreateInstance", "IPS_CreateCategory", "IPS_CreateLink", "IPS_CreateMedia",
        "IPS_SetName", "IPS_SetHidden", "IPS_SetIcon", "IPS_SetPosition",
        "MSKODA_Group", "MSKODA_Link_"
    ]:
        assert forbidden not in php_sources
    assert not (ROOT / "MySkoda" / "src" / "StructureTrait.php").exists()

    assert "registerVariableOnce" in variables
    assert "$this->Translate((string) $definition['name'])" in variables
    assert "UnregisterVariable" not in php_sources
    assert "IPS_DeleteVariable" not in php_sources

    for stable_ident in [
        "StateOfCharge", "Range", "Mileage", "Locked", "DoorsOpen", "WindowsOpen",
        "Charging", "ChargePower", "TargetSOC", "ChargeMode", "Climate",
        "TargetTemperature", "ApiKeyWarning", "LastUpdate", "ParkingState",
        "ChargingState"
    ]:
        assert f"'{stable_ident}'" in variables

    assert "'NewApiFeatures'" in openapi
    assert "ensureApiDiscoveryVariable" in openapi
    assert "updateApiDiscoveryStatus" in openapi
    assert "isKnownModuleOperation" in openapi
    assert "RegisterVariable" not in openapi

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
        "Waiting for confirmation: %s": "Warte auf Bestätigung: %s",
        "Transmission uncertain: %s": "Übertragung unklar: %s",
        "Command rejected: %s": "Befehl abgelehnt: %s",
        "Confirmed: %s": "Bestätigt: %s",
        "Confirmation timed out: %s": "Bestätigung abgelaufen: %s",
    }.items():
        assert translations.get(source) == german

    assert "parkingStatePresentation" in variables
    for state in ["PARKED", "MOVING", "DRIVING", "UNKNOWN"]:
        assert f"'{state}'" in variables

    for forbidden in [
        "IPS_SetVariableCustomPresentation", "IPS_SetVariableCustomProfile",
        "IPS_SetVariableCustomAction", "IPS_CreateVariableProfile", "IPS_CreateTemplate",
        "IPS_SetTemplate"
    ]:
        assert forbidden not in php_sources

    assert "RegisterPropertyBoolean('EnableChargingHistory', false)" in core
    assert "ReadPropertyBoolean('EnableChargingHistory')" in history
    assert "ReadAttributeBoolean('ChargingHistoryInitialized')" in history
    for ident in ["StateOfCharge", "TargetSOC", "ChargePower", "Mileage"]:
        assert f"'{ident}'" in history
    assert "AC_SetLoggingStatus" in history
    assert "AC_SetAggregationType($archiveId, $mileageId, 1)" in history
    assert "AC_SetCounterIgnoreZeros($archiveId, $mileageId, true)" in history

    assert "$mileage > 0" in variables
    assert "$this->SetValue('Mileage', $mileage);" in variables

    # Version 1.1 command confirmation architecture.
    for required in [
        "RegisterAttributeString('PendingCommands', '{}')",
        "RegisterAttributeString('LastCommandResult', '')",
        "RegisterTimer('CommandConfirmTimer', 0",
        "COMMAND_CONFIRM_DELAY_MS = 60000",
        "COMMAND_MIN_TIMEOUT_SECONDS = 600",
        "executeOptimisticCommand",
        "applyApiValue",
        "ConfirmPending",
        "scheduleCommandConfirmation",
        "commandPendingTimeoutSeconds",
        "PendingCommands",
        "CommandStatus",
    ]:
        assert required in command

    for ident in ["Charging", "TargetSOC", "ChargeMode", "Climate", "TargetTemperature"]:
        assert f"'{ident}'" in command

    assert "status === 408" in command
    assert "status >= 500" in command
    assert "LastCommandResult', 'uncertain'" not in command  # written through ternary classification
    assert "uncertain ? 'uncertain' : 'rejected'" in command
    assert "API still reports old value; optimistic value is kept." in command
    assert "SetTimerInterval('CommandConfirmTimer', self::COMMAND_CONFIRM_DELAY_MS)" in command
    assert "SetChargingLimit" in command
    assert "SetChargeMode" in command
    assert "ReadPropertyBoolean('ShowDetails')" in command

    # Pending diagnostics are module variables, not helper objects or archive settings.
    assert "'ident' => 'PendingCommands'" in command
    assert "'ident' => 'CommandStatus'" in command
    assert "AC_SetLoggingStatus" not in command
    assert "IPS_Create" not in command

    # User-owned instance properties remain untouched.
    assert "IPS_SetProperty" not in php_sources
    assert "IPS_ApplyChanges" not in php_sources

    for forbidden in [
        "AC_DeleteVariableData", "AC_SetCompaction", "AC_SetGraphStatus", "IPS_DeleteMedia"
    ]:
        assert forbidden not in php_sources

    expected_sources = {
        "ApiTrait.php", "CommandTrait.php", "CoreTrait.php", "HelpersTrait.php",
        "HistoryTrait.php", "NotificationTrait.php", "OpenApiTrait.php", "VariablesTrait.php"
    }
    source_names = {path.name for path in (ROOT / "MySkoda" / "src").glob("*.php")}
    assert source_names == expected_sources

    root_readme = (ROOT / "README.md").read_text(encoding="utf-8")
    module_readme = (ROOT / "MySkoda" / "README.md").read_text(encoding="utf-8")
    for text in [
        "IP-Symcon **8.1 oder neuer**", "https://public.api.connect.skoda-auto.cz/docs",
        "keine Dummy-Instanzen", "stabile Variablen-Idents", "standardmäßig deaktiviert",
        "ausdrücklicher Aktivierung", "Neue API-Funktionen", "Befehlsbestätigung ab Version 1.1",
        "PendingCommands", "CommandStatus", "60 Sekunden", "MIT-Lizenz"
    ]:
        assert text in root_readme

    for text in [
        "Voraussetzungen", "Installation und erste Einrichtung", "Konfiguration",
        "Neue API-Funktionen erkennen", "Pending- und Bestätigungslogik für Remote-Befehle",
        "PendingCommands", "CommandStatus", "HTTP `408`", "600 Sekunden",
        "Archivierung", "standardmäßig **aus**", "Fehlersuche",
        "Datenschutz und externe Dienste", "Öffentliche PHP-Befehle", "MIT-Lizenz"
    ]:
        assert text in module_readme

    changelog = (ROOT / "CHANGELOG.md").read_text(encoding="utf-8")
    assert "## 1.1 - 2026-09-14" in changelog
    assert "## 1.0 - 2026-09-06" in changelog
    assert "PendingCommands" in changelog
    assert "CommandStatus" in changelog
    assert "## 2." not in changelog
    assert not (ROOT / "docs").exists()


if __name__ == "__main__":
    main()
