# Changelog

All notable changes to this project are documented here.

## 2.0 - 2026-09-05

- **Breaking release:** MySkoda is now a pure data and control module.
- Removed the complete custom vehicle visualization, `module.html`, all visualization traits and all model-specific graphics.
- Removed vehicle-design configuration, tile-title configuration, visualization refresh actions and the visualization timer.
- Removed the former `VehicleTile` and `LastUpdateAge` variables. Existing module-owned visualization artifacts are deleted when the instance is applied.
- Removed all version-specific compatibility traits (`BootstrapV*`, `CoreV*`, `VisualizationV*`, `NotificationV*`).
- Consolidated notification target selection and validation into the normal notification implementation.
- Removed module-created global presentation templates. Boolean status variables now use direct native Symcon 8.x presentations; no Legacy profiles and no module-created global template objects are required.
- Kept the notification destination as an explicitly selected Symcon visualization instance and validate its module type before sending.
- Added `SunroofOpen` to optional vehicle status data.
- Changed module vendor metadata from `Škoda Auto` to `taloriko` to avoid implying official affiliation.
- Reworked documentation and validation tests for a Store-oriented, data-only module structure.
- A manufacturer-independent electric-vehicle visualization is intentionally outside the scope of this repository and can be developed as a separate module.

## 1.9 - 2026-09-05

- Final release of the former integrated visualization line. See Git history for details.

## 0.1 - 1.8

- Historical development releases. See Git history for detailed changes.
