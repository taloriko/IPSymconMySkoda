<?php

declare(strict_types=1);

trait MySkodaDiagnosticsTrait
{
    public function DiagnosePublicApiData(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return $this->encodePublicApiDiagnostic([
                'ok' => false,
                'message' => 'No vehicle response is cached yet. Test the connection or update the vehicle first.'
            ]);
        }

        $leaves = [];
        $this->flattenPublicApiData($raw, '', $leaves);
        ksort($leaves, SORT_NATURAL | SORT_FLAG_CASE);

        $used = [];
        $unused = [];
        foreach ($leaves as $path => $value) {
            $entry = [
                'path' => $path,
                'type' => $this->publicApiDiagnosticType($value),
                'value' => $this->publicApiDiagnosticValue($path, $value)
            ];

            if ($this->isPublicApiPathUsed($path)) {
                $used[] = $entry;
            } else {
                $unused[] = $entry;
            }
        }

        $diagnostic = [
            'ok' => true,
            'source' => 'MySkoda Public API RawData',
            'note' => 'This diagnostic performs no additional API request. VIN, license plate and location values are masked.',
            'summary' => [
                'totalLeafPaths' => count($leaves),
                'usedLeafPaths' => count($used),
                'unusedLeafPaths' => count($unused)
            ],
            'topLevelKeys' => array_values(array_map('strval', array_keys($raw))),
            'vehicleKeys' => isset($raw['vehicle']) && is_array($raw['vehicle'])
                ? array_values(array_map('strval', array_keys($raw['vehicle'])))
                : [],
            'unused' => $unused,
            'used' => $used
        ];

        $json = $this->encodePublicApiDiagnostic($diagnostic);
        $this->SendDebug('Public API data diagnostic', $json, 0);

        return $json;
    }

    private function ensurePublicApiVariables(): void
    {
        $definitions = [
            $this->variable('VIN', 'VIN', VARIABLETYPE_STRING, 25, $this->valuePresentation('barcode')),
            $this->variable('ReliableLockStatus', 'Reliable lock status', VARIABLETYPE_STRING, 105, $this->publicApiEnumPresentation('lock', [
                ['LOCKED', 'Locked', 'lock', 0x22C55E],
                ['UNLOCKED', 'Unlocked', 'lock-open', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('RemainingChargingTime', 'Remaining charging time', VARIABLETYPE_INTEGER, 265, $this->valuePresentation('hourglass-half', ' min', 0)),
            $this->variable('AtSavedChargingLocation', 'At saved charging location', VARIABLETYPE_BOOLEAN, 270, $this->booleanYesNoPresentation(true, 'house')),
            $this->variable('BatteryCareMode', 'Battery care mode', VARIABLETYPE_STRING, 280, $this->publicApiEnumPresentation('shield', [
                ['ACTIVATED', 'Activated', 'shield', 0x22C55E],
                ['DEACTIVATED', 'Deactivated', 'shield', 0xF59E0B],
                ['ACTIVE', 'Activated', 'shield', 0x22C55E],
                ['INACTIVE', 'Deactivated', 'shield', 0xF59E0B],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('BatteryCareTargetSOC', 'Battery care target', VARIABLETYPE_INTEGER, 290, $this->valuePresentation('battery-half', ' %', 0)),
            $this->variable('MaxChargeCurrentAC', 'Maximum AC charging current', VARIABLETYPE_STRING, 295, $this->publicApiEnumPresentation('bolt', [
                ['MAXIMUM', 'Maximum', 'bolt', -1],
                ['REDUCED', 'Reduced', 'gauge', -1],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('AutoUnlockPlug', 'Automatic plug unlock', VARIABLETYPE_STRING, 297, $this->publicApiEnumPresentation('plug', [
                ['OFF', 'Off', 'lock', -1],
                ['ON', 'On', 'lock-open', 0x22C55E],
                ['PERMANENT', 'Permanent', 'lock-open', 0x22C55E],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('TargetTemperatureUnit', 'Target temperature unit', VARIABLETYPE_STRING, 315, $this->valuePresentation('temperature-half')),
            $this->variable('AirConditioningAtUnlock', 'Air conditioning at unlock', VARIABLETYPE_BOOLEAN, 320, $this->booleanYesNoPresentation(true, 'key')),
            $this->variable('WindowHeatingEnabled', 'Window heating enabled', VARIABLETYPE_BOOLEAN, 330, $this->booleanYesNoPresentation(true, 'window-maximize')),
            $this->variable('WindowHeatingFront', 'Front window heating', VARIABLETYPE_STRING, 340, $this->publicApiEnumPresentation('window-maximize', [
                ['OFF', 'Off', 'window-maximize', -1],
                ['ON', 'On', 'window-maximize', 0x22C55E],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ])),
            $this->variable('WindowHeatingRear', 'Rear window heating', VARIABLETYPE_STRING, 350, $this->publicApiEnumPresentation('car-rear', [
                ['OFF', 'Off', 'car-rear', -1],
                ['ON', 'On', 'car-rear', 0x22C55E],
                ['UNKNOWN', 'Unknown', 'circle-question', -1]
            ]))
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

        $reliableLock = $this->path($vehicle, 'status.overall.reliableLockStatus', null);
        if ($reliableLock !== null) {
            $lockState = strtoupper(trim((string) $reliableLock));
            $this->SetValue('ReliableLockStatus', $lockState);
            if ($lockState === 'LOCKED') {
                $this->SetValue('Locked', true);
            } elseif ($lockState === 'UNLOCKED') {
                $this->SetValue('Locked', false);
            }
        }
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

    private function flattenPublicApiData(mixed $value, string $path, array &$leaves): void
    {
        if (!is_array($value)) {
            if ($path !== '') {
                $leaves[$path] = $value;
            }
            return;
        }

        if ($value === []) {
            if ($path !== '') {
                $leaves[$path] = [];
            }
            return;
        }

        foreach ($value as $key => $child) {
            if (is_int($key)) {
                $childPath = $path . '[' . $key . ']';
            } else {
                $childPath = $path === '' ? (string) $key : $path . '.' . (string) $key;
            }
            $this->flattenPublicApiData($child, $childPath, $leaves);
        }
    }

    private function isPublicApiPathUsed(string $path): bool
    {
        $normalized = preg_replace('/\[\d+\]/', '[]', $path) ?? $path;

        $exact = [
            'vehicle.vin',
            'vehicle.name',
            'vehicle.licensePlate',
            'vehicle.renderUrl',
            'vehicle.odometer.mileageInKm',
            'vehicle.status.overall.doorsLocked',
            'vehicle.status.overall.locked',
            'vehicle.status.overall.reliableLockStatus',
            'vehicle.status.overall.doors',
            'vehicle.status.overall.windows',
            'vehicle.status.overall.lights',
            'vehicle.status.detail.trunk',
            'vehicle.status.detail.bonnet',
            'vehicle.status.detail.sunroof',
            'vehicle.charging.status.state',
            'vehicle.charging.status.chargePowerInKw',
            'vehicle.charging.status.chargeType',
            'vehicle.charging.status.fullyChargedAt',
            'vehicle.charging.status.remainingTimeToFullyChargedInMinutes',
            'vehicle.charging.status.battery.stateOfChargeInPercent',
            'vehicle.charging.status.battery.remainingCruisingRangeInMeters',
            'vehicle.charging.isVehicleInSavedLocation',
            'vehicle.charging.settings.targetStateOfChargeInPercent',
            'vehicle.charging.settings.availableChargeModes[]',
            'vehicle.charging.settings.preferredChargeMode',
            'vehicle.charging.settings.autoUnlockPlugWhenCharged',
            'vehicle.charging.settings.batteryCareModeTargetValueInPercent',
            'vehicle.charging.settings.chargingCareMode',
            'vehicle.charging.settings.maxChargeCurrentAc',
            'vehicle.airConditioning.state',
            'vehicle.airConditioning.targetTemperature.value',
            'vehicle.airConditioning.targetTemperature.unit',
            'vehicle.airConditioning.airConditioningAtUnlock',
            'vehicle.airConditioning.windowHeating.enabled',
            'vehicle.airConditioning.windowHeating.front',
            'vehicle.airConditioning.windowHeating.rear',
            'vehicle.parkingPosition.state',
            'vehicle.parkingPosition.latitude',
            'vehicle.parkingPosition.longitude',
            'vehicle.parkingPosition.gpsCoordinates.latitude',
            'vehicle.parkingPosition.gpsCoordinates.longitude',
            'vehicle.parkingPosition.gpsCoordinates.lat',
            'vehicle.parkingPosition.gpsCoordinates.lon',
            'vehicle.parkingPosition.gpsCoordinates.lng'
        ];
        if (in_array($normalized, $exact, true)) {
            return true;
        }

        foreach ([
            'vehicle.operations',
            'vehicle.remoteOperations',
            'vehicle.chargingProfiles',
            'errors'
        ] as $prefix) {
            if ($normalized === $prefix
                || str_starts_with($normalized, $prefix . '.')
                || str_starts_with($normalized, $prefix . '[]')) {
                return true;
            }
        }

        return false;
    }

    private function publicApiDiagnosticValue(string $path, mixed $value): mixed
    {
        $lowerPath = strtolower($path);

        if (str_contains($lowerPath, 'latitude')
            || str_contains($lowerPath, 'longitude')
            || str_ends_with($lowerPath, '.lat')
            || str_ends_with($lowerPath, '.lon')
            || str_ends_with($lowerPath, '.lng')) {
            return '{redacted-location}';
        }

        if (str_contains($lowerPath, 'licenseplate')) {
            return '{redacted-license-plate}';
        }

        if (str_contains($lowerPath, '.vin') || $lowerPath === 'vin') {
            return '{vin}';
        }

        if (is_string($value)) {
            $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
            if ($vin !== '') {
                $value = str_ireplace([$vin, rawurlencode($vin)], '{vin}', $value);
            }
        }

        if (is_array($value)) {
            return $value === [] ? '[]' : 'array(' . count($value) . ')';
        }

        return $value;
    }

    private function publicApiDiagnosticType(mixed $value): string
    {
        if (is_array($value)) {
            return $value === [] ? 'empty-array' : 'array';
        }
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_string($value)) {
            return 'string';
        }

        return get_debug_type($value);
    }

    private function encodePublicApiDiagnostic(array $diagnostic): string
    {
        $json = json_encode(
            $diagnostic,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return is_string($json) ? $json : '{}';
    }
}
