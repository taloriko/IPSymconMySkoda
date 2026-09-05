<?php

declare(strict_types=1);

/**
 * MySkoda 1.7 bootstrap additions.
 */
trait MySkodaBootstrapV17Trait
{
    public function Create(): void
    {
        $this->createBootstrapV16();
        $this->RegisterPropertyString('VehicleDesign', 'auto');
    }

    public function ApplyChanges(): void
    {
        $this->applyChangesBootstrapV16();
        $this->RefreshVisuals();
    }
}
