<?php

declare(strict_types=1);

/**
 * MySkoda 1.9 visualization lifecycle hardening.
 *
 * Symcon may recycle HTML-SDK tiles while scrolling. The initial vehicle state
 * is therefore embedded directly into the returned HTML and the tile can ask
 * the module for a fresh full state without triggering a vehicle API request.
 */
trait MySkodaVisualizationV19Trait
{
    public function GetVisualizationTile(): string
    {
        $module = @file_get_contents(__DIR__ . '/../module.html');
        if ($module === false) {
            return '<div>MySkoda visualization file is missing.</div>';
        }

        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle']) ? $raw['vehicle'] : [];
        $lastUpdate = @$this->GetIDForIdent('LastUpdate') !== false ? (int) $this->GetValue('LastUpdate') : 0;
        $state = $this->getVisualizationState($vehicle, $lastUpdate);

        $stateJson = json_encode(
            $state,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
        if (!is_string($stateJson)) {
            $stateJson = '{}';
        }

        return str_replace(
            ['__MYSKODA_INSTANCE_ID__', '__MYSKODA_INITIAL_STATE__'],
            [(string) $this->InstanceID, $stateJson],
            $module
        );
    }

    private function getVisualizationState(array $vehicle, int $lastUpdate): array
    {
        $state = $this->getVisualizationStateV17($vehicle, $lastUpdate);
        $state['instanceId'] = $this->InstanceID;
        $state['tileTitleHidden'] = $this->ReadPropertyBoolean('HideVisualizationTitle')
            && function_exists('IPS_SetHiddenTitle');
        return $state;
    }
}
