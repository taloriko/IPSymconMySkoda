<?php

declare(strict_types=1);

trait MySkodaPublicApiVariablesTrait
{
    private function publicApiVariableDefinitions(): array
    {
        return [
            $this->variable('VIN', 'VIN', VARIABLETYPE_STRING, 30, $this->valuePresentation('barcode')),
            $this->variable('PlugConnectionState', 'Plug connection state', VARIABLETYPE_STRING, 312, $this->publicApiEnumPresentation('plug', [
                ['CONNECTED', 'Connected', 'plug-circle-check', 0x22C55E],
                ['DISCONNECTED', 'Disconnected', 'plug', 0x6B7280],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ])),
            $this->variable('PlugLockState', 'Plug lock state', VARIABLETYPE_STRING, 314, $this->publicApiEnumPresentation('lock', [
                ['LOCKED', 'Locked', 'lock', 0x22C55E],
                ['UNLOCKED', 'Unlocked', 'lock-open', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ])),
            $this->variable('RemainingChargingTime', 'Remaining charging time', VARIABLETYPE_INTEGER, 320, $this->valuePresentation('hourglass-half', ' min', 0)),
            $this->variable('AtSavedChargingLocation', 'At saved charging location', VARIABLETYPE_BOOLEAN, 200, $this->booleanYesNoPresentation(true, 'house')),
            $this->variable('BatteryCareMode', 'Battery care mode', VARIABLETYPE_STRING, 240, $this->publicApiEnumPresentation('shield', [
                ['ACTIVATED', 'Activated', 'shield', 0x22C55E],
                ['DEACTIVATED', 'Deactivated', 'shield', 0xF59E0B],
                ['ACTIVE', 'Activated', 'shield', 0x22C55E],
                ['INACTIVE', 'Deactivated', 'shield', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('BatteryCareTargetSOC', 'Battery care target', VARIABLETYPE_INTEGER, 230, $this->valuePresentation('battery-half', ' %', 0)),
            $this->variable('MaxChargeCurrentAC', 'Maximum AC charging current', VARIABLETYPE_STRING, 250, $this->publicApiEnumPresentation('bolt', [
                ['MAXIMUM', 'Maximum', 'bolt', -1],
                ['REDUCED', 'Reduced', 'gauge', -1],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('AutoUnlockPlug', 'Automatic plug unlock', VARIABLETYPE_STRING, 210, $this->publicApiEnumPresentation('plug', [
                ['OFF', 'Off', 'lock', -1],
                ['ON', 'On', 'lock-open', 0x22C55E],
                ['PERMANENT', 'Permanent', 'lock-open', 0x22C55E],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('TargetTemperatureUnit', 'Target temperature unit', VARIABLETYPE_STRING, 130, $this->valuePresentation('temperature-half')),
            $this->variable('AirConditioningAtUnlock', 'Air conditioning at unlock', VARIABLETYPE_BOOLEAN, 110, $this->booleanYesNoPresentation(true, 'key')),
            $this->variable('WindowHeatingEnabled', 'Window heating enabled', VARIABLETYPE_BOOLEAN, 140, $this->booleanYesNoPresentation(true, 'window-maximize')),
            $this->variable('WindowHeatingFront', 'Front window heating', VARIABLETYPE_STRING, 150, $this->publicApiEnumPresentation('window-maximize', [
                ['OFF', 'Off', 'window-maximize', 0x22C55E],
                ['ON', 'On', 'window-maximize', 0xF59E0B],
                ['INVALID', 'Invalid', 'triangle-exclamation', 0x6B7280],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ])),
            $this->variable('WindowHeatingRear', 'Rear window heating', VARIABLETYPE_STRING, 160, $this->publicApiEnumPresentation('car-rear', [
                ['OFF', 'Off', 'car-rear', 0x22C55E],
                ['ON', 'On', 'car-rear', 0xF59E0B],
                ['INVALID', 'Invalid', 'triangle-exclamation', 0x6B7280],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ])),
            $this->variable('APICarType', 'API vehicle type', VARIABLETYPE_STRING, 1300, $this->valuePresentation('car')),
            $this->variable('APIPrimaryEngineType', 'API primary engine type', VARIABLETYPE_STRING, 1310, $this->valuePresentation('gear')),
            $this->variable('APISecondaryEngineType', 'API secondary engine type', VARIABLETYPE_STRING, 1320, $this->valuePresentation('gear')),
            $this->variable('APIAvailableChargeModes', 'API available charging modes', VARIABLETYPE_STRING, 1340, $this->valuePresentation('list')),
            $this->variable('APIRemoteOperations', 'API remote operations', VARIABLETYPE_STRING, 1350, [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'ICON' => 'satellite-dish',
                'MULTILINE' => true
            ]),
            $this->variable('APIAuxiliaryHeatingState', 'API auxiliary heating state', VARIABLETYPE_STRING, 1360, $this->valuePresentation('fire')),
            $this->variable('APIActiveVentilationState', 'API active ventilation state', VARIABLETYPE_STRING, 1370, $this->valuePresentation('fan')),
            $this->variable('FuelLevel', 'Fuel level', VARIABLETYPE_INTEGER, 350, $this->valuePresentation('gas-pump', ' %', 0)),
            $this->variable('PrimaryEngineRange', 'Primary engine range', VARIABLETYPE_INTEGER, 360, $this->valuePresentation('route', ' km', 0)),
            $this->variable('TotalRange', 'Total range', VARIABLETYPE_INTEGER, 370, $this->valuePresentation('route', ' km', 0)),
            $this->variable('AuxiliaryHeatingDuration', 'Auxiliary heating duration', VARIABLETYPE_INTEGER, 380, $this->valuePresentation('hourglass-half', ' s', 0))
        ];
    }

    private function publicApiValueAsString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_string($value) || is_int($value) || is_float($value)) {
            return trim((string) $value);
        }
        if (!is_array($value) || $value === []) {
            return '';
        }

        if (array_is_list($value)) {
            $items = [];
            $onlyScalars = true;
            foreach ($value as $item) {
                if (is_string($item) || is_int($item) || is_float($item)) {
                    $text = trim((string) $item);
                    if ($text !== '') {
                        $items[] = $text;
                    }
                    continue;
                }
                $onlyScalars = false;
                break;
            }
            if ($onlyScalars) {
                return implode(', ', $items);
            }
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '';
    }

    private function publicApiEnumPresentation(string $icon, array $states): array
    {
        $options = [];
        foreach ($states as [$value, $caption, $optionIcon, $color]) {
            $options[] = [
                'Value' => $value,
                'Caption' => $this->Translate($caption),
                'IconActive' => $optionIcon !== '',
                'IconValue' => $optionIcon,
                'ColorActive' => $color >= 0,
                'ColorValue' => $color
            ];
        }

        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => $icon,
            'COLOR' => -1,
            'OPTIONS' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }
}
