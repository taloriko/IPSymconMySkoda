# Changelog

All notable changes to this project are documented here.

## 1.2 - 2026-09-05

- Improved first-time setup with direct connection feedback after applying VIN and API token.
- Added a connection test button to the configuration form.
- Added clear MySkoda app instructions for creating an API key: Profile -> Smart Home -> Create Key.
- Added a clickable link to the GitHub module and documentation in the configuration form.
- Added `ApiKeyWarning`, which becomes true 30 days before the API key expires.
- Added optional Symcon push notification for expiring API keys via Tile Visualization or WebFront instance ID.
- Added a test-notification button.
- Redesigned `VehicleTile` for smartphones with a much denser, flatter layout and fewer visual frames.
- Expanded charging plug visualization for charging, interrupted charging, connect-cable, ready, target-reached and discharging states.
- Vehicle tile now shows API-key expiry warning when active.

## 1.1 - 2026-09-05

- Added `LastUpdateAge` as a compact `hh:mm` age indicator since the last successful query.
- Added `VehicleTile` as a generic electric-vehicle visualization tile for Symcon Visualisation.
- Tile headline now uses vehicle name, alternatively license plate, and finally VIN as fallback.
- Tile shows lock state, doors, windows, lights, charging connector state, charging power, time to full, SOC, charging limit, mileage and remaining range.
- Tile contains dedicated charging and climate sections with a simple, dark-mode friendly design.
- Added a separate minute-based refresh timer so age/tile data stay current between API polls.
- Documentation updated for version 1.1.

## 0.1 - 2026-09-05

- Initial MySkoda IP-Symcon module.
- Configuration form for VIN, API token, polling interval and optional S-PIN.
- Compact standard variable set plus optional diagnostics.
- Symcon 8.x presentations without legacy profiles.
- Charging and climate control.
- Charging limit, charging mode and charging-profile operations via current OpenAPI definition.
- Auxiliary heating and active ventilation commands.
- Rate-limit, Retry-After and API-key expiry handling.
- German localization with English source strings.
- Documentation aligned with common Symcon module documentation sections.
