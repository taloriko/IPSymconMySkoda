<?php

declare(strict_types=1);

trait MySkodaPublicApiVariablesTrait
{
    private function ensurePublicApiVariables(): void
    {
        $initialInformation = $this->Translate('Not retrieved yet');
        $definitions = [
            $this->variable('VIN', 'VIN', VARIABLETYPE_STRING, 30, $this->valuePresentation('barcode')),
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
            $this->variable('APICarType', 'API vehicle type', VARIABLETYPE_STRING, 1300, [], $initialInformation),
            $this->variable('APIPrimaryEngineType', 'API primary engine type', VARIABLETYPE_STRING, 1310, [], $initialInformation),
            $this->variable('APISecondaryEngineType', 'API secondary engine type', VARIABLETYPE_STRING, 1320, [], $initialInformation),
            $this->variable('APISupportedFeatures', 'API supported features', VARIABLETYPE_STRING, 1330, [], $initialInformation),
            $this->variable('APIAvailableChargeModes', 'API available charging modes', VARIABLETYPE_STRING, 1340, [], $initialInformation),
            $this->variable('APIRemoteOperations', 'API remote operations', VARIABLETYPE_STRING, 1350, [], $initialInformation),
            $this->variable('APIAuxiliaryHeatingState', 'API auxiliary heating state', VARIABLETYPE_STRING, 1360, [], $initialInformation),
            $this->variable('APIActiveVentilationState', 'API active ventilation state', VARIABLETYPE_STRING, 1370, [], $initialInformation)
        ];

        foreach ($definitions as $definition) {
            $this->registerVariableOnce($definition);
        }
    }

    private function updatePublicApiValuesFromRawData(): void
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return;
        }

        $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle']) ? $raw['vehicle'] : [];
        if ($vehicle === []) {
            return;
        }
        $errors = isset($raw['errors']) && is_array($raw['errors']) ? $raw['errors'] : [];

        $this->setPublicApiString('VIN', $this->path($vehicle, 'vin', null));
        $this->setPublicApiString('TargetTemperatureUnit', $this->path($vehicle, 'airConditioning.targetTemperature.unit', null));
        $this->setPublicApiBoolean('AirConditioningAtUnlock', $this->path($vehicle, 'airConditioning.airConditioningAtUnlock', null));
        $this->setPublicApiBoolean('WindowHeatingEnabled', $this->path($vehicle, 'airConditioning.windowHeating.enabled', null));
        $this->setPublicApiString('WindowHeatingFront', $this->path($vehicle, 'airConditioning.windowHeating.front', null), true);
        $this->setPublicApiString('WindowHeatingRear', $this->path($vehicle, 'airConditioning.windowHeating.rear', null), true);
        $this->setPublicApiBoolean('AtSavedChargingLocation', $this->path($vehicle, 'charging.isVehicleInSavedLocation', null));
        $this->setPublicApiString('AutoUnlockPlug', $this->path($vehicle, 'charging.settings.autoUnlockPlugWhenCharged', null), true);
        $this->setPublicApiInteger('BatteryCareTargetSOC', $this->path($vehicle, 'charging.settings.batteryCareModeTargetValueInPercent', null));
        $this->setPublicApiString('BatteryCareMode', $this->path($vehicle, 'charging.settings.chargingCareMode', null), true);
        $this->setPublicApiString('MaxChargeCurrentAC', $this->path($vehicle, 'charging.settings.maxChargeCurrentAc', null), true);
        $this->setPublicApiInteger('RemainingChargingTime', $this->path($vehicle, 'charging.status.remainingTimeToFullyChargedInMinutes', null));
        $this->setPublicApiInformation('APICarType', $this->path($vehicle, 'fuelStatus.carType', null), $errors, 'FUEL_STATUS', true);
        $this->setPublicApiInformation('APIPrimaryEngineType', $this->path($vehicle, 'fuelStatus.primaryEngineRange.engineType', null), $errors, 'FUEL_STATUS', true);
        $this->setPublicApiInformation('APISecondaryEngineType', $this->path($vehicle, 'fuelStatus.secondaryEngineRange.engineType', null), $errors, 'FUEL_STATUS', true);
        $this->setPublicApiInformation('APIAuxiliaryHeatingState', $this->path($vehicle, 'auxiliaryHeating.state', null), $errors, 'AUXILIARY_HEATING', true);
        $this->setPublicApiInformation('APIActiveVentilationState', $this->path($vehicle, 'activeVentilation.state', null), $errors, 'ACTIVE_VENTILATION', true);
        $this->setPublicApiInformation('APIAvailableChargeModes', $this->path($vehicle, 'charging.settings.availableChargeModes', null), $errors, 'CHARGING');

        $operations = $this->path($vehicle, 'operations', $this->path($vehicle, 'remoteOperations', null));
        $this->setPublicApiInformation('APIRemoteOperations', $operations);
        $this->setPublicApiInformation('APISupportedFeatures', $this->publicApiSupportedFeatures($vehicle, $errors));
    }

    /**
     * Stores API information or an explicit, localized explanation in the value.
     * An absent field alone never establishes that a feature is unsupported.
     * Only exact component error types from this response refine that reason.
     */
    private function setPublicApiInformation(
        string $ident,
        mixed $value,
        array $errors = [],
        string $errorPrefix = '',
        bool $uppercase = false
    ): void {
        if (@$this->GetIDForIdent($ident) === false) {
            return;
        }

        $text = $this->publicApiValueAsString($value);
        if ($text !== '') {
            $this->SetValue($ident, $uppercase ? strtoupper($text) : $text);
            return;
        }

        $reason = $value === []
            ? $this->Translate('No entries')
            : $this->Translate('Not provided');
        if ($errorPrefix !== '') {
            $types = [];
            foreach ($errors as $error) {
                if (is_array($error) && is_string($error['type'] ?? null)) {
                    $types[strtoupper(trim($error['type']))] = true;
                }
            }

            if (isset($types[$errorPrefix . '_UNSUPPORTED'])) {
                $reason = $this->Translate('Unsupported');
            } elseif (isset($types[$errorPrefix . '_DISABLED'])) {
                $reason = $this->Translate('Service disabled');
            } elseif (isset($types[$errorPrefix . '_UNAVAILABLE'])) {
                $reason = $this->Translate('Temporarily unavailable');
            }
        }

        $this->SetValue($ident, $reason);
    }

    private function setPublicApiString(string $ident, mixed $value, bool $uppercase = false): void
    {
        if ($value === null || @$this->GetIDForIdent($ident) === false) {
            return;
        }

        $text = trim((string) $value);
        if ($uppercase) {
            $text = strtoupper($text);
        }
        $this->SetValue($ident, $text);
    }

    private function setPublicApiInteger(string $ident, mixed $value): void
    {
        if ($value === null || @$this->GetIDForIdent($ident) === false) {
            return;
        }
        $this->SetValue($ident, (int) $value);
    }

    private function setPublicApiBoolean(string $ident, mixed $value): void
    {
        if ($value === null || @$this->GetIDForIdent($ident) === false) {
            return;
        }
        $this->SetValue($ident, (bool) $value);
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

    private function publicApiSupportedFeatures(array $vehicle, array $errors): string
    {
        $parts = [
            'status' => 'status',
            'fuelStatus' => 'fuelStatus',
            'odometer' => 'odometer',
            'parkingPosition' => 'parkingPosition',
            'airConditioning' => 'airConditioning',
            'auxiliaryHeating' => 'auxiliaryHeating',
            'activeVentilation' => 'activeVentilation',
            'charging' => 'charging',
            'chargingProfiles' => 'chargingProfiles'
        ];

        $supported = [];
        foreach ($parts as $key => $apiName) {
            if (array_key_exists($key, $vehicle)) {
                $supported[$apiName] = true;
            }
        }

        $errorPrefixes = [
            'STATUS' => 'status',
            'FUEL_STATUS' => 'fuelStatus',
            'ODOMETER' => 'odometer',
            'PARKING_POSITION' => 'parkingPosition',
            'AIR_CONDITIONING' => 'airConditioning',
            'AUXILIARY_HEATING' => 'auxiliaryHeating',
            'ACTIVE_VENTILATION' => 'activeVentilation',
            'CHARGING' => 'charging',
            'CHARGING_PROFILES' => 'chargingProfiles'
        ];

        foreach ($errors as $error) {
            if (!is_array($error) || !is_string($error['type'] ?? null)) {
                continue;
            }
            $type = strtoupper(trim($error['type']));
            if ($type === '' || str_ends_with($type, '_UNSUPPORTED')) {
                continue;
            }
            if (!str_ends_with($type, '_DISABLED') && !str_ends_with($type, '_UNAVAILABLE')) {
                continue;
            }
            foreach ($errorPrefixes as $prefix => $apiName) {
                if (str_starts_with($type, $prefix . '_')) {
                    $supported[$apiName] = true;
                    break;
                }
            }
        }

        $ordered = [];
        foreach ($parts as $apiName) {
            if (isset($supported[$apiName])) {
                $ordered[] = $apiName;
            }
        }
        return implode(', ', $ordered);
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
