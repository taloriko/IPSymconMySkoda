<?php

declare(strict_types=1);

trait MySkodaPublicApiVariablesTrait
{
    private function publicApiVariableDefinitions(): array
    {
        return [
            'VIN' => ['path' => 'vin', 'definition' => $this->variable('VIN', 'VIN', VARIABLETYPE_STRING, 30, $this->valuePresentation('barcode'))],
            'TargetTemperatureUnit' => ['path' => 'airConditioning.targetTemperature.unit', 'definition' => $this->variable('TargetTemperatureUnit', 'Target temperature unit', VARIABLETYPE_STRING, 130, $this->valuePresentation('temperature-half'))],
            'AirConditioningAtUnlock' => ['path' => 'airConditioning.airConditioningAtUnlock', 'definition' => $this->variable('AirConditioningAtUnlock', 'Air conditioning at unlock', VARIABLETYPE_BOOLEAN, 110, $this->booleanYesNoPresentation(true, 'key'))],
            'WindowHeatingEnabled' => ['path' => 'airConditioning.windowHeating.enabled', 'definition' => $this->variable('WindowHeatingEnabled', 'Window heating enabled', VARIABLETYPE_BOOLEAN, 140, $this->booleanYesNoPresentation(true, 'window-maximize'))],
            'WindowHeatingFront' => ['path' => 'airConditioning.windowHeating.front', 'definition' => $this->variable('WindowHeatingFront', 'Front window heating', VARIABLETYPE_STRING, 150, $this->publicApiEnumPresentation('window-maximize', [
                ['OFF', 'Off', 'window-maximize', 0x22C55E],
                ['ON', 'On', 'window-maximize', 0xF59E0B],
                ['INVALID', 'Invalid', 'triangle-exclamation', 0x6B7280],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ]))],
            'WindowHeatingRear' => ['path' => 'airConditioning.windowHeating.rear', 'definition' => $this->variable('WindowHeatingRear', 'Rear window heating', VARIABLETYPE_STRING, 160, $this->publicApiEnumPresentation('car-rear', [
                ['OFF', 'Off', 'car-rear', 0x22C55E],
                ['ON', 'On', 'car-rear', 0xF59E0B],
                ['INVALID', 'Invalid', 'triangle-exclamation', 0x6B7280],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ]))],
            'AtSavedChargingLocation' => ['path' => 'charging.isVehicleInSavedLocation', 'definition' => $this->variable('AtSavedChargingLocation', 'At saved charging location', VARIABLETYPE_BOOLEAN, 200, $this->booleanYesNoPresentation(true, 'house'))],
            'AutoUnlockPlug' => ['path' => 'charging.settings.autoUnlockPlugWhenCharged', 'definition' => $this->variable('AutoUnlockPlug', 'Automatic plug unlock', VARIABLETYPE_STRING, 210, $this->publicApiEnumPresentation('plug', [
                ['OFF', 'Off', 'lock', -1],
                ['ON', 'On', 'lock-open', 0x22C55E],
                ['PERMANENT', 'Permanent', 'lock-open', 0x22C55E],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ]))],
            'BatteryCareTargetSOC' => ['path' => 'charging.settings.batteryCareModeTargetValueInPercent', 'definition' => $this->variable('BatteryCareTargetSOC', 'Battery care target', VARIABLETYPE_INTEGER, 230, $this->valuePresentation('battery-half', ' %', 0))],
            'BatteryCareMode' => ['path' => 'charging.settings.chargingCareMode', 'definition' => $this->variable('BatteryCareMode', 'Battery care mode', VARIABLETYPE_STRING, 240, $this->publicApiEnumPresentation('shield', [
                ['ACTIVATED', 'Activated', 'shield', 0x22C55E],
                ['DEACTIVATED', 'Deactivated', 'shield', 0xF59E0B],
                ['ACTIVE', 'Activated', 'shield', 0x22C55E],
                ['INACTIVE', 'Deactivated', 'shield', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ]))],
            'MaxChargeCurrentAC' => ['path' => 'charging.settings.maxChargeCurrentAc', 'definition' => $this->variable('MaxChargeCurrentAC', 'Maximum AC charging current', VARIABLETYPE_STRING, 250, $this->publicApiEnumPresentation('bolt', [
                ['MAXIMUM', 'Maximum', 'bolt', -1],
                ['REDUCED', 'Reduced', 'gauge', -1],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ]))],
            'PlugConnectionState' => ['path' => 'charging.status.plugConnectionState', 'definition' => $this->variable('PlugConnectionState', 'Plug connection state', VARIABLETYPE_STRING, 312, $this->publicApiEnumPresentation('plug', [
                ['CONNECTED', 'Connected', 'plug-circle-check', 0x22C55E],
                ['DISCONNECTED', 'Disconnected', 'plug', 0x6B7280],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ]))],
            'PlugLockState' => ['path' => 'charging.status.plugLockState', 'definition' => $this->variable('PlugLockState', 'Plug lock state', VARIABLETYPE_STRING, 314, $this->publicApiEnumPresentation('lock', [
                ['LOCKED', 'Locked', 'lock', 0x22C55E],
                ['UNLOCKED', 'Unlocked', 'lock-open', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ]))],
            'RemainingChargingTime' => ['path' => 'charging.status.remainingTimeToFullyChargedInMinutes', 'definition' => $this->variable('RemainingChargingTime', 'Remaining charging time', VARIABLETYPE_INTEGER, 320, $this->valuePresentation('hourglass-half', ' min', 0))],

            'FuelLevelPercent' => ['path' => 'fuelStatus.primaryEngineRange.currentFuelLevelInPercent', 'definition' => $this->variable('FuelLevelPercent', 'Fuel level', VARIABLETYPE_INTEGER, 360, $this->valuePresentation('gas-pump', ' %', 0))],
            'PrimaryEngineSOC' => ['path' => 'fuelStatus.primaryEngineRange.currentSoCInPercent', 'definition' => $this->variable('PrimaryEngineSOC', 'Primary engine state of charge', VARIABLETYPE_INTEGER, 370, $this->valuePresentation('gauge', ' %', 0))],
            'FuelRange' => ['path' => 'fuelStatus.primaryEngineRange.remainingRangeInKm', 'definition' => $this->variable('FuelRange', 'Fuel range', VARIABLETYPE_INTEGER, 380, $this->valuePresentation('gas-pump', ' km', 0))],
            'TotalRange' => ['path' => 'fuelStatus.totalRangeInKm', 'definition' => $this->variable('TotalRange', 'Total range', VARIABLETYPE_INTEGER, 390, $this->valuePresentation('route', ' km', 0))],
            'APICarType' => ['path' => 'fuelStatus.carType', 'definition' => $this->variable('APICarType', 'API vehicle type', VARIABLETYPE_STRING, 1300, $this->engineTypePresentation())],
            'APIPrimaryEngineType' => ['path' => 'fuelStatus.primaryEngineRange.engineType', 'definition' => $this->variable('APIPrimaryEngineType', 'API primary engine type', VARIABLETYPE_STRING, 1310, $this->engineTypePresentation())],
            'APISecondaryEngineType' => ['path' => 'fuelStatus.secondaryEngineRange.engineType', 'definition' => $this->variable('APISecondaryEngineType', 'API secondary engine type', VARIABLETYPE_STRING, 1320, $this->engineTypePresentation())],
            'APIAuxiliaryHeatingState' => ['path' => 'auxiliaryHeating.state', 'definition' => $this->variable('APIAuxiliaryHeatingState', 'API auxiliary heating state', VARIABLETYPE_STRING, 1360, $this->publicApiEnumPresentation('fire', [
                ['OFF', 'Off', 'fire', 0x22C55E],
                ['ON', 'On', 'fire', 0xF59E0B],
                ['HEATING', 'Heating', 'fire', 0xF59E0B],
                ['VENTILATION', 'Ventilation', 'fan', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ]))],
            'AuxiliaryHeatingDuration' => ['path' => 'auxiliaryHeating.durationInSeconds', 'definition' => $this->variable('AuxiliaryHeatingDuration', 'Auxiliary heating duration', VARIABLETYPE_INTEGER, 1380, $this->valuePresentation('clock', ' min', 0))],
            'APIActiveVentilationState' => ['path' => 'activeVentilation.state', 'definition' => $this->variable('APIActiveVentilationState', 'API active ventilation state', VARIABLETYPE_STRING, 1370, $this->publicApiEnumPresentation('fan', [
                ['OFF', 'Off', 'fan', 0x22C55E],
                ['ON', 'On', 'fan', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
            ]))]
        ];
    }

    private function ensurePublicApiVariables(array $vehicle = []): void
    {
        if ($vehicle === []) {
            return;
        }

        foreach ($this->publicApiVariableDefinitions() as $entry) {
            if ($this->pathHasValue($vehicle, (string) $entry['path'])) {
                $this->registerVariableOnce($entry['definition']);
            }
        }

        if (array_key_exists('operations', $vehicle) || array_key_exists('remoteOperations', $vehicle)) {
            $this->registerVariableOnce($this->variable('APIRemoteOperations', 'API remote operations', VARIABLETYPE_STRING, 1350, []));
        }

        if ($this->ReadPropertyBoolean('ShowDetails')) {
            $this->registerVariableOnce($this->variable('APISupportedFeatures', 'API supported features', VARIABLETYPE_STRING, 1330, []));
            if ($this->pathHasValue($vehicle, 'charging.settings.availableChargeModes')) {
                $this->registerVariableOnce($this->variable('APIAvailableChargeModes', 'API available charging modes', VARIABLETYPE_STRING, 1340, []));
            }
        }

        $this->ensureDynamicApiVariables($vehicle);
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
        $this->setPublicApiString('PlugConnectionState', $this->path($vehicle, 'charging.status.plugConnectionState', null), true);
        $this->setPublicApiString('PlugLockState', $this->path($vehicle, 'charging.status.plugLockState', null), true);
        $this->setPublicApiInteger('RemainingChargingTime', $this->path($vehicle, 'charging.status.remainingTimeToFullyChargedInMinutes', null));

        $this->setPublicApiInteger('FuelLevelPercent', $this->path($vehicle, 'fuelStatus.primaryEngineRange.currentFuelLevelInPercent', null));
        $this->setPublicApiInteger('PrimaryEngineSOC', $this->path($vehicle, 'fuelStatus.primaryEngineRange.currentSoCInPercent', null));
        $this->setPublicApiInteger('FuelRange', $this->path($vehicle, 'fuelStatus.primaryEngineRange.remainingRangeInKm', null));
        $this->setPublicApiInteger('TotalRange', $this->path($vehicle, 'fuelStatus.totalRangeInKm', null));
        $this->setPublicApiString('APICarType', $this->path($vehicle, 'fuelStatus.carType', null), true);
        $this->setPublicApiString('APIPrimaryEngineType', $this->path($vehicle, 'fuelStatus.primaryEngineRange.engineType', null), true);
        $this->setPublicApiString('APISecondaryEngineType', $this->path($vehicle, 'fuelStatus.secondaryEngineRange.engineType', null), true);
        $this->setPublicApiString('APIAuxiliaryHeatingState', $this->path($vehicle, 'auxiliaryHeating.state', null), true);

        $duration = $this->path($vehicle, 'auxiliaryHeating.durationInSeconds', null);
        if ($duration !== null) {
            $this->setPublicApiInteger('AuxiliaryHeatingDuration', (int) round((float) $duration / 60));
        }

        $this->setPublicApiString('APIActiveVentilationState', $this->path($vehicle, 'activeVentilation.state', null), true);

        if (@$this->GetIDForIdent('APIAvailableChargeModes') !== false) {
            $this->setPublicApiInformation('APIAvailableChargeModes', $this->path($vehicle, 'charging.settings.availableChargeModes', null), $errors, 'CHARGING');
        }

        if (@$this->GetIDForIdent('APIRemoteOperations') !== false) {
            $operations = $this->path($vehicle, 'operations', $this->path($vehicle, 'remoteOperations', null));
            $this->setPublicApiInformation('APIRemoteOperations', $operations);
        }

        if (@$this->GetIDForIdent('APISupportedFeatures') !== false) {
            $this->setPublicApiInformation('APISupportedFeatures', $this->publicApiSupportedFeatures($vehicle, $errors));
        }

        $this->updateDynamicApiVariables($vehicle);
    }

    private function ensureDynamicApiVariables(array $vehicle): void
    {
        $known = [];
        foreach ($this->knownVehicleDataPaths() as $path) {
            $known[$path] = true;
        }
        foreach ($this->publicApiVariableDefinitions() as $entry) {
            $known[(string) $entry['path']] = true;
        }
        foreach (['renderUrl', 'operations', 'remoteOperations'] as $path) {
            $known[$path] = true;
        }

        foreach ($this->flattenVehicleScalars($vehicle) as $path => $value) {
            if (isset($known[$path]) || str_ends_with($path, '.carCapturedTimestamp')) {
                continue;
            }
            $definition = $this->dynamicApiVariableDefinition($path, $value);
            if ($definition !== null) {
                $this->registerVariableOnce($definition);
            }
        }
    }

    private function updateDynamicApiVariables(array $vehicle): void
    {
        $known = [];
        foreach ($this->knownVehicleDataPaths() as $path) {
            $known[$path] = true;
        }
        foreach ($this->publicApiVariableDefinitions() as $entry) {
            $known[(string) $entry['path']] = true;
        }
        foreach (['renderUrl', 'operations', 'remoteOperations'] as $path) {
            $known[$path] = true;
        }

        foreach ($this->flattenVehicleScalars($vehicle) as $path => $value) {
            if (isset($known[$path]) || str_ends_with($path, '.carCapturedTimestamp')) {
                continue;
            }
            $ident = $this->dynamicApiIdent($path);
            $id = @$this->GetIDForIdent($ident);
            if ($id === false || !IPS_VariableExists($id)) {
                continue;
            }
            if (is_bool($value)) {
                $this->SetValue($ident, $value);
            } elseif (is_int($value)) {
                $this->SetValue($ident, $value);
            } elseif (is_float($value)) {
                $this->SetValue($ident, $value);
            } else {
                $this->SetValue($ident, (string) $value);
            }
        }
    }

    private function flattenVehicleScalars(array $data, string $prefix = ''): array
    {
        $flat = [];
        foreach ($data as $key => $value) {
            $key = (string) $key;
            $path = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                if ($value === []) {
                    continue;
                }
                if (array_is_list($value)) {
                    $allScalar = true;
                    foreach ($value as $item) {
                        if (!is_scalar($item)) {
                            $allScalar = false;
                            break;
                        }
                    }
                    if ($allScalar) {
                        $flat[$path] = implode(', ', array_map(static fn (mixed $v): string => (string) $v, $value));
                    }
                    continue;
                }
                $flat += $this->flattenVehicleScalars($value, $path);
                continue;
            }

            if ($value !== null && is_scalar($value)) {
                $flat[$path] = $value;
            }
        }
        return $flat;
    }

    private function dynamicApiVariableDefinition(string $path, mixed $value): ?array
    {
        $ident = $this->dynamicApiIdent($path);
        $name = 'API ' . implode(' / ', array_map([$this, 'humanizeApiPathPart'], explode('.', $path)));
        $position = 2000 + (abs((int) crc32($path)) % 100000);

        if (is_bool($value)) {
            return $this->variable($ident, $name, VARIABLETYPE_BOOLEAN, $position, $this->booleanYesNoPresentation(true));
        }
        if (is_int($value)) {
            return $this->variable($ident, $name, VARIABLETYPE_INTEGER, $position, $this->valuePresentation());
        }
        if (is_float($value)) {
            return $this->variable($ident, $name, VARIABLETYPE_FLOAT, $position, $this->valuePresentation('', '', 2));
        }
        if (is_string($value)) {
            return $this->variable($ident, $name, VARIABLETYPE_STRING, $position, $this->valuePresentation());
        }
        return null;
    }

    private function dynamicApiIdent(string $path): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', $path) ?: [];
        $ident = 'API';
        foreach ($parts as $part) {
            if ($part !== '') {
                $ident .= ucfirst($part);
            }
        }
        if (strlen($ident) > 60) {
            $ident = substr($ident, 0, 51) . strtoupper(substr(sha1($path), 0, 8));
        }
        return $ident;
    }

    private function humanizeApiPathPart(string $part): string
    {
        $text = preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $part);
        return ucfirst((string) $text);
    }

    private function engineTypePresentation(): array
    {
        return $this->publicApiEnumPresentation('gas-pump', [
            ['GASOLINE', 'Gasoline', 'gas-pump', -1],
            ['DIESEL', 'Diesel', 'gas-pump', -1],
            ['ELECTRIC', 'Electric', 'bolt', -1],
            ['BEV', 'Electric', 'bolt', -1],
            ['HYBRID', 'Hybrid', 'leaf', -1],
            ['PHEV', 'Plug-in hybrid', 'plug', -1],
            ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
        ]);
    }

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
        $this->SetValue($ident, $uppercase ? strtoupper($text) : $text);
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
            foreach ($value as $item) {
                if (!is_scalar($item)) {
                    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return is_string($json) ? $json : '';
                }
                $items[] = (string) $item;
            }
            return implode(', ', $items);
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '';
    }

    private function publicApiSupportedFeatures(array $vehicle, array $errors): string
    {
        $parts = ['status', 'fuelStatus', 'odometer', 'parkingPosition', 'airConditioning', 'auxiliaryHeating', 'activeVentilation', 'charging', 'chargingProfiles'];
        $supported = [];
        foreach ($parts as $part) {
            if (array_key_exists($part, $vehicle)) {
                $supported[] = $part;
            }
        }
        return implode(', ', $supported);
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
