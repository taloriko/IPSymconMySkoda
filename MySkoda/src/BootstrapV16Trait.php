<?php

declare(strict_types=1);

/**
 * Native Tile Visualization bootstrap for MySkoda >= 1.6.
 *
 * The pattern follows the public IP-Symcon TileVisu examples from da8ter:
 * the instance itself exposes a visualization tile instead of requiring a
 * separate HTML variable to be selected manually.
 */
trait MySkodaBootstrapV16Trait
{
    public function Create(): void
    {
        $this->createCoreV15();
        $this->SetVisualizationType(1);
    }

    public function ApplyChanges(): void
    {
        // Existing instances do not run Create() again during a module update.
        // Set the visualization type here as well so upgrades from <= 1.5 get
        // the native instance tile automatically.
        $this->SetVisualizationType(1);
        $this->applyChangesCoreV15();

        // VehicleTile was the compatibility path used up to 1.5. Keep an
        // existing variable alive for old visualizations, but hide it from the
        // object tree. New installations use the instance visualization.
        $legacyTileId = @$this->GetIDForIdent('VehicleTile');
        if ($legacyTileId !== false) {
            IPS_SetHidden($legacyTileId, true);
        }

        $this->RefreshVisuals();
    }
}
