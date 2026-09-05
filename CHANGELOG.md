# Changelog

All notable changes to this project are documented here.

## 1.9 - 2026-09-05

- Hardened the native HTML-SDK tile against Symcon scroll/recycle behavior: the complete vehicle state is embedded directly into the tile HTML on creation.
- Added an instance-specific `sessionStorage` fallback so multiple MySkoda vehicles cannot overwrite each other's cached visualization state.
- The tile now requests a full cached-state refresh through `requestAction('VisualizationRefresh', ...)` after load, page show, focus and visibility changes. This refresh uses the module's stored `RawData` and does **not** consume a MySkoda API request.
- Added an optional **Hide Symcon tile title** setting. On Symcon 9.1+ the outer tile title is hidden with `IPS_SetHiddenTitle()` while the instance/object name in the console remains unchanged. Older compatible Symcon versions keep the title and the tile reserves additional top space.
- Replaced the numeric notification visualization ID field with a `SelectInstance` chooser.
- Notification chooser is dynamically restricted to installed visualization module types where possible and the selected instance is additionally checked for Symcon `ModuleType = 6` before notifications are sent.
- Added clear feedback showing the selected notification target and improved notification test/error messages.
- Updated documentation, localization and structure tests for version 1.9.

## 1.8 - 2026-09-05

- Reworked the instance configuration into clear collapsible sections for connection/vehicle, polling/control, notifications, advanced settings and help/documentation.
- Moved the GitHub/documentation link into a collapsed help section so it is still available without dominating the setup page.
- Fixed the native tile layout so the embedded HTML itself no longer scrolls; scrolling is left to Symcon.
- Added client-side state caching and automatic re-render on page show, focus, visibility changes, resize and periodic refresh to prevent values disappearing after scrolling or returning to the tile.
- Removed the individual text rows for doors, windows, sunroof, lights, trunk and bonnet from the bottom of the tile. These states are now indicated directly on the vehicle graphic; only the lock state remains as a text status.
- Improved visual highlighting on the vehicle for open doors/windows/sunroof, bonnet/trunk and active lights.
- Combined climate mode and target temperature into one non-wrapping smartphone line.
- Made mileage use a compact mobile label to avoid poor line wrapping on narrow screens.
- Kept the consolidated charging panel and model-specific Enyaq/Elroq/Epiq/generic top views from 1.7.

## 1.7 - 2026-09-05

- Complete redesign of the native vehicle tile following the compact visual hierarchy of `da8ter/TileVisu-Kachelsammlung` more closely while keeping the MySkoda implementation and SVG artwork original.
- Added selectable vehicle designs: **Automatic**, **Enyaq**, **Elroq**, **Epiq** and **Generic**.
- Automatic design selection prefers model/specification metadata returned by MySkoda, then known system-model identifiers, and finally a conservative vehicle-name hint.
- VIN-only detection intentionally does not force Enyaq vs. Elroq because both vehicle families can use the Skoda type code `NY`; manual override is always available.
- Added distinct top-view silhouettes for Enyaq, Elroq and Epiq.
- Added panoramic/sliding-roof visualization and a dedicated **Sunroof** status row when the API reports that status.
- Vehicle tile now follows the approved mockup structure: vehicle left, one consolidated charging panel right, climate and mileage below it, then clear status rows for lock, doors, windows, sunroof, lights, trunk and bonnet.
- SOC remains a four-segment battery in the vehicle with the 10% red / 25% orange / 80% light-green / >80% dark-green colour progression.
- Exterior lights remain neutral when the API reports lights off; orange highlighting is used only when lights are on.
- Missing sunroof/trunk/bonnet status is not shown as a false green state.

## 1.6 - 2026-09-05

- The MySkoda **instance itself** now exposes a native Symcon Tile Visualization through `SetVisualizationType(1)`, `GetVisualizationTile()` and `UpdateVisualizationValue()`.
- Added `MySkoda/module.html` and moved the active smartphone tile to the native instance-visualization pattern used by modern TileVisu modules.
- Existing instances receive the visualization type during `ApplyChanges()`, so an update from 1.5 does not require recreating the module instance.
- The former `VehicleTile` Web-Content variable remains hidden only for backward compatibility with existing visualizations from versions 1.1-1.5.
- Added a 4-segment SOC battery in the centre of the vehicle.
- Battery colour follows a continuous SOC gradient with reference points at 10% red, 25% orange, 80% light green and above 80% progressively darker green.
- Reworked the vehicle top view with a more automotive silhouette, wheels, mirrors, glass and state highlights while remaining model-neutral.
- Charging remains consolidated in one main area with connector state, AC/DC type, power, time to full, target SOC and charging mode.
- Climate remains a single compact line with operating mode and target temperature.
- The tile is responsive for narrow smartphone layouts and keeps transparent/theme-neutral surfaces for light, dark and custom Symcon themes.
- Module architecture and visual layout are more closely inspired by [da8ter/TileVisu-Kachelsammlung](https://github.com/da8ter/TileVisu-Kachelsammlung); no source code or graphical assets are copied.

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
