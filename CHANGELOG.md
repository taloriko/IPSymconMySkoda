# Changelog

All notable changes to this project are documented here.

## 1.5 - 2026-09-05

- Fixed passive Boolean status variables for the new Symcon presentation system: they now use **Value Presentation** instead of Enumeration, so no variable action is required.
- Added module-owned presentation templates `MySkoda.Status.GoodTrue`, `MySkoda.Status.GoodFalse`, `MySkoda.Status.Normal` and `MySkoda.Control.Switch`. These are new Symcon presentation templates, not Legacy variable profiles.
- Corrected the Boolean option schema to use `ColorActive` / `ColorValue` as required by Symcon Value Presentation.
- Charging and climate use the native Switch presentation when remote control is enabled; with remote control disabled they fall back to a passive green Value Presentation.
- Redesigned the vehicle tile for smartphones: charging information is consolidated in the upper main area and the duplicate charging row was removed.
- Climate is now displayed once with a clear operating mode and target temperature instead of duplicated `Aus` values.
- Reworked the generic top-view vehicle SVG with a more realistic SUV silhouette and visual highlights for lock, doors, windows and lights.
- Charging power and time-to-full are hidden when no charging cable is connected instead of showing misleading `0.0 kW` / `00:00`.
- Visual styling is inspired by the compact layout principles of [da8ter/TileVisu-Kachelsammlung](https://github.com/da8ter/TileVisu-Kachelsammlung). No source code or graphical assets are copied.

## 1.4 - 2026-09-05

- Removed module-owned Legacy variable profiles introduced in 1.3.
- Boolean values now use native Symcon 8.x enumeration presentations with `Ja` / `Nein` captions and per-state colors.
- Vehicle tile now uses the native Symcon Web Content presentation instead of the `~HTMLBox` Legacy profile.
- Added migration cleanup for the former `MySkoda.YesNo.*` profiles and the old `~HTMLBox` assignment.
- Safety-related states are green when the vehicle is in the expected state, regardless of whether that means `Ja` or `Nein`.
- Doors, windows, trunk, bonnet, lights and API-key warning: `Nein` is green and `Ja` is orange.
- Locked: `Ja` is green and `Nein` is orange.
- Charging and climate: both `Ja` and `Nein` are green because neither normal operating state is a warning.
- No red color is used for informational Boolean states; red remains reserved for actual faults/errors.

## 1.3 - 2026-09-05

- Added module-owned Boolean profiles under `MySkoda.*` with clear `Ja` / `Nein` labels.
- Vehicle security/status values use green for the expected state and orange for attention; red remains reserved for actual faults.
- `Locked` is green for `Ja`; doors, windows, trunk, bonnet, lights and API-key warning are green for `Nein`.
- Charging and climate use a neutral `Nein` and green `Ja`, because an inactive function is not a fault.
- Location values are explicitly reset to `0.0 / 0.0` when the MySkoda API does not return a position, preventing stale coordinates.
- Documentation explains that location sharing must be enabled separately for every MySkoda profile, even for the same vehicle.

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
