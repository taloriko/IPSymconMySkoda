<?php

declare(strict_types=1);

/**
 * MySkoda 1.9 bootstrap additions.
 *
 * Keeps the object name in the Symcon console while optionally hiding the
 * visualization tile title on Symcon 9.1+ via IPS_SetHiddenTitle().
 */
trait MySkodaBootstrapV19Trait
{
    public function Create(): void
    {
        $this->createBootstrapV17();
        $this->RegisterPropertyBoolean('HideVisualizationTitle', true);
    }

    public function ApplyChanges(): void
    {
        $this->applyChangesBootstrapV17();
        $this->applyVisualizationTitlePreference();
        $this->validateNotificationTarget(true);
        $this->RefreshVisuals();
    }

    private function applyVisualizationTitlePreference(): void
    {
        if (!function_exists('IPS_SetHiddenTitle')) {
            return;
        }

        try {
            @IPS_SetHiddenTitle($this->InstanceID, $this->ReadPropertyBoolean('HideVisualizationTitle'));
        } catch (Throwable $e) {
            $this->SendDebug('Visualization title', $e->getMessage(), 0);
        }
    }
}
