<?php

declare(strict_types=1);

trait MySkodaVariablesTrait
{
    private const CHARGE_MODES = [
        0 => 'MANUAL',
        1 => 'TIMER',
        2 => 'TIMER_CHARGING_WITH_CLIMATISATION',
        3 => 'PREFERRED_CHARGING_TIMES',
        4 => 'ONLY_OWN_CURRENT',
        5 => 'IMMEDIATE_DISCHARGING',
        6 => 'HOME_STORAGE_CHARGING',
        7 => 'OTHER',
        8 => 'OFF'
    ];

    private function registerVariables(): void
    {
        foreach ($this->coreVariableDefinitions() as $definition) {
            if (in_array((string) $definition['ident'], ['ApiKeyWarning', 'LastUpdate'], true)) {
                $this->registerVariableOnce($definition);
            }
        }

        if ($this->ReadPropertyBoolean('ShowDetails')) {
            foreach ($this->detailVariableDefinitions() as $definition) {
                if (in_array((string) $definition['ident'], ['ApiKeyExpiresAtVar', 'RequestsRemaining', 'PartialErrors'], true)) {
                    $this->registerVariableOnce($definition);
                }
            }
        }
    }

    private function ensureVehicleVariables(array $vehicle): void
    {
        $definitions = [];
        foreach (array_merge($this->coreVariableDefinitions(), $this->detailVariableDefinitions()) as $definition) {
            $definitions[(string) $definition['ident']] = $definition;
        }

        foreach ($this->vehicleVariableSourcePaths() as $ident => $paths) {
            foreach ($paths as $path) {
                if (!$this->pathHasValue($vehicle, $path)) {
                    continue;
                }
                if (isset($definitions[$ident])) {
                    $this->registerVariableOnce($definitions[$ident]);
                }
                break;
            }
        }
    }

    private function vehicleVariableSourcePaths(): array
    {
        return [
            'StateOfCharge' => ['charging.status.battery.stateOfChargeInPercent'],
            'Range' => ['charging.status.battery.remainingCruisingRangeInMeters'],
            'Mileage' => ['odometer.mileageInKm'],
            'DoorsLocked' => ['status.overall.doorsLocked'],
            'Locked' => ['status.overall.locked'],
            'ReliableLockStatus' => ['status.overall.reliableLockStatus'],
            'DoorsOpen' => ['status.overall.doors'],
            'WindowsOpen' => ['status.overall.windows'],
            'Charging' => ['charging.status.state'],
            'ChargePower' => ['charging.status.chargePowerInKw'],
            'TargetSOC' => ['charging.settings.targetStateOfChargeInPercent'],
            'ChargeMode' => ['charging.settings.preferredChargeMode'],
            'Climate' => ['airConditioning.state'],
            'AuxiliaryHeating' => ['auxiliaryHeating.state'],
            'ClimateState' => ['airConditioning.state'],
            'TargetTemperature' => ['airConditioning.targetTemperature.value'],
            'VehicleName' => ['name'],
            'LicensePlate' => ['licensePlate'],
            'TrunkOpen' => ['status.detail.trunk'],
            'BonnetOpen' => ['status.detail.bonnet'],
            'SunroofOpen' => ['status.detail.sunroof'],
            'LightsOn' => ['status.overall.lights'],
            'ParkingState' => ['parkingPosition.state'],
            'ParkingAddress' => ['parkingPosition.formattedAddress'],
            'ChargingState' => ['charging.status.state'],
            'ChargeType' => ['charging.status.chargeType'],
            'FullyChargedAt' => ['charging.status.fullyChargedAt'],
            'Latitude' => ['parkingPosition.latitude', 'parkingPosition.gpsCoordinates.latitude', 'parkingPosition.gpsCoordinates.lat'],
            'Longitude' => ['parkingPosition.longitude', 'parkingPosition.gpsCoordinates.longitude', 'parkingPosition.gpsCoordinates.lon', 'parkingPosition.gpsCoordinates.lng']
        ];
    }

    private function knownVehicleDataPaths(): array
    {
        $paths = [];
        foreach ($this->vehicleVariableSourcePaths() as $sourcePaths) {
            foreach ($sourcePaths as $path) {
                $paths[$path] = true;
            }
        }
        return array_keys($paths);
    }

    private function coreVariableDefinitions(): array
    {
        return [
            $this->variable('StateOfCharge', 'State of charge', VARIABLETYPE_INTEGER, 290, [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_BATTERY
            ]),
            $this->variable('Range', 'Range', VARIABLETYPE_INTEGER, 280, $this->valuePresentation('route', ' km', 0)),
            $this->variable('Mileage', 'Mileage', VARIABLETYPE_INTEGER, 400, array_merge(
                $this->valuePresentation('gauge-high', ' km', 0),
                ['THOUSANDS_SEPARATOR' => '.']
            )),
            $this->variable('DoorsLocked', 'Door lock status', VARIABLETYPE_STRING, 700, $this->doorLockStatePresentation()),
            $this->variable('Locked', 'Vehicle lock status', VARIABLETYPE_STRING, 710, $this->doorLockStatePresentation()),
            $this->variable('ReliableLockStatus', 'Reliable lock status', VARIABLETYPE_STRING, 750, $this->reliableLockStatePresentation()),
            $this->variable('DoorsOpen', 'Doors', VARIABLETYPE_STRING, 720, $this->openStatePresentation('door-closed', 'door-open')),
            $this->variable('WindowsOpen', 'Windows', VARIABLETYPE_STRING, 730, $this->openStatePresentation('window-maximize', 'window-maximize')),
            $this->variable('Charging', 'Charging', VARIABLETYPE_BOOLEAN, 910, $this->booleanActionPresentation('plug')),
            $this->variable('ChargePower', 'Charging power', VARIABLETYPE_FLOAT, 300, [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
            ]),
            $this->variable('TargetSOC', 'Charging limit', VARIABLETYPE_INTEGER, 270, [
                'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
                'ICON' => 'battery-half',
                'MIN' => 50,
                'MAX' => 100,
                'STEP_SIZE' => 10,
                'SUFFIX' => ' %',
                'PERCENTAGE' => false,
                'USAGE_TYPE' => 5
            ]),
            $this->variable('ChargeMode', 'Charging mode', VARIABLETYPE_INTEGER, 260, $this->chargeModePresentation()),
            $this->variable('Climate', 'Air conditioning', VARIABLETYPE_BOOLEAN, 900, $this->booleanActionPresentation('fan')),
            $this->variable('AuxiliaryHeating', 'Auxiliary heating control', VARIABLETYPE_BOOLEAN, 905, $this->booleanActionPresentation('fire')),
            $this->variable('ClimateState', 'Air conditioning state', VARIABLETYPE_STRING, 100, $this->climateStatePresentation()),
            $this->variable('TargetTemperature', 'Target temperature', VARIABLETYPE_FLOAT, 120, [
                'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
                'ICON' => 'temperature-half',
                'MIN' => 16,
                'MAX' => 30,
                'STEP_SIZE' => 0.5,
                'GRADIENT_TYPE' => 1,
                'USAGE_TYPE' => 0,
                'SUFFIX' => ' °C',
                'PERCENTAGE' => false,
                'DIGITS' => 1
            ], 22.0),
            $this->variable('ApiKeyWarning', 'API key warning', VARIABLETYPE_BOOLEAN, 930, $this->booleanYesNoPresentation(false, 'key')),
            $this->variable('LastUpdate', 'Last update', VARIABLETYPE_INTEGER, 920, [
                'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
            ])
        ];
    }

    private function detailVariableDefinitions(): array
    {
        return [
            $this->variable('VehicleName', 'Vehicle name', VARIABLETYPE_STRING, 10, $this->valuePresentation('car')),
            $this->variable('LicensePlate', 'License plate', VARIABLETYPE_STRING, 20, $this->valuePresentation('id-card')),
            $this->variable('TrunkOpen', 'Trunk', VARIABLETYPE_STRING, 810, $this->openStatePresentation('car-rear', 'car-rear')),
            $this->variable('BonnetOpen', 'Bonnet', VARIABLETYPE_STRING, 820, $this->openStatePresentation('car', 'car')),
            $this->variable('SunroofOpen', 'Sunroof', VARIABLETYPE_STRING, 800, $this->openStatePresentation('car-side', 'car-side')),
            $this->variable('LightsOn', 'Lights', VARIABLETYPE_STRING, 740, $this->onOffStatePresentation('lightbulb')),
            $this->variable('ParkingState', 'Parking state', VARIABLETYPE_STRING, 600, $this->parkingStatePresentation()),
            $this->variable('ParkingAddress', 'Parking address', VARIABLETYPE_STRING, 610, $this->valuePresentation('location-dot')),
            $this->variable('ChargingState', 'Charging state', VARIABLETYPE_STRING, 330, $this->chargingStatePresentation(), 'UNKNOWN'),
            $this->variable('ChargeType', 'Charge type', VARIABLETYPE_STRING, 340, $this->chargeTypePresentation()),
            $this->variable('FullyChargedAt', 'Fully charged at', VARIABLETYPE_INTEGER, 310, [
                'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
            ]),
            $this->variable('Latitude', 'Latitude', VARIABLETYPE_FLOAT, 620, $this->valuePresentation('location-dot', '', 6)),
            $this->variable('Longitude', 'Longitude', VARIABLETYPE_FLOAT, 630, $this->valuePresentation('location-dot', '', 6)),
            $this->variable('ApiKeyExpiresAtVar', 'API key valid until', VARIABLETYPE_INTEGER, 940, [
                'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
            ]),
            $this->variable('RequestsRemaining', 'API requests remaining', VARIABLETYPE_INTEGER, 950, $this->valuePresentation('gauge')),
            $this->variable('PartialErrors', 'API partial errors', VARIABLETYPE_STRING, 960, [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'ICON' => 'triangle-exclamation',
                'MULTILINE' => true
            ])
        ];
    }

    private function variable(
        string $ident,
        string $name,
        int $type,
        int $position,
        array $presentation,
        mixed $initialValue = null
    ): array {
        return compact('ident', 'name', 'type', 'position', 'presentation', 'initialValue');
    }

    /**
     * Creates a missing variable with its initial metadata and value.
     * Existing objects are validated but never renamed, reordered, re-registered
     * or deleted. Names, icons and presentations remain user-owned after creation.
     */
    private function registerVariableOnce(array $definition): void
    {
        $ident = (string) $definition['ident'];
        $existingId = @$this->GetIDForIdent($ident);
        if ($existingId !== false) {
            if (!IPS_VariableExists($existingId)) {
                $this->LogMessage(sprintf('MySkoda: ident "%s" is already used by another object.', $ident), KL_WARNING);
                return;
            }

            $variable = IPS_GetVariable($existingId);
            if ((int) ($variable['VariableType'] ?? -1) !== (int) $definition['type']) {
                $this->LogMessage(sprintf('MySkoda: variable "%s" has an unexpected type.', $ident), KL_ERROR);
            }
            return;
        }

        $name = $this->Translate((string) $definition['name']);
        $presentation = (array) $definition['presentation'];
        $position = (int) $definition['position'];

        match ((int) $definition['type']) {
            VARIABLETYPE_BOOLEAN => $this->RegisterVariableBoolean($ident, $name, $presentation, $position),
            VARIABLETYPE_INTEGER => $this->RegisterVariableInteger($ident, $name, $presentation, $position),
            VARIABLETYPE_FLOAT => $this->RegisterVariableFloat($ident, $name, $presentation, $position),
            VARIABLETYPE_STRING => $this->RegisterVariableString($ident, $name, $presentation, $position),
            default => throw new InvalidArgumentException('Unsupported variable type')
        };

        $variableId = @$this->GetIDForIdent($ident);
        if ($variableId === false || !IPS_VariableExists($variableId)) {
            return;
        }

        $icon = match ($ident) {
            'LastUpdate' => 'clock-rotate-left',
            'FullyChargedAt' => 'battery-full',
            'ApiKeyExpiresAtVar' => 'arrow-right-to-line',
            default => ''
        };
        if ($icon !== '') {
            IPS_SetIcon($variableId, $icon);
        }

        if ($definition['initialValue'] !== null) {
            $this->SetValue($ident, $definition['initialValue']);
        }
    }

    private function applyActions(): void
    {
        $enabled = $this->ReadPropertyBoolean('EnableRemote');
        foreach (['Charging', 'TargetSOC', 'ChargeMode', 'Climate', 'TargetTemperature'] as $ident) {
            $id = @$this->GetIDForIdent($ident);
            if ($id !== false && IPS_VariableExists($id)) {
                $this->MaintainAction($ident, $enabled);
            }
        }

        $auxiliaryHeatingId = @$this->GetIDForIdent('AuxiliaryHeating');
        if ($auxiliaryHeatingId !== false && IPS_VariableExists($auxiliaryHeatingId)) {
            $auxiliaryHeatingEnabled = $enabled
                && trim($this->ReadPropertyString('SPIN')) !== ''
                && $this->vehicleOperationAvailable('startAuxiliaryHeating')
                && $this->vehicleOperationAvailable('stopAuxiliaryHeating');
            $this->MaintainAction('AuxiliaryHeating', $auxiliaryHeatingEnabled);
        }
    }

    private function updateCoreValues(array $vehicle): void
    {
        $this->setPathValue('StateOfCharge', $vehicle, 'charging.status.battery.stateOfChargeInPercent', static fn (mixed $v): int => (int) $v);
        $this->setPathValue('Range', $vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', static fn (mixed $v): int => (int) round((float) $v / 1000));

        $mileage = $this->path($vehicle, 'odometer.mileageInKm', null);
        if ($mileage !== null && (float) $mileage > 0) {
            $this->setIfExists('Mileage', (int) round((float) $mileage));
        }

        $this->setPathValue('DoorsLocked', $vehicle, 'status.overall.doorsLocked', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('Locked', $vehicle, 'status.overall.locked', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('ReliableLockStatus', $vehicle, 'status.overall.reliableLockStatus', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('DoorsOpen', $vehicle, 'status.overall.doors', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('WindowsOpen', $vehicle, 'status.overall.windows', static fn (mixed $v): string => strtoupper((string) $v));

        $chargeStateValue = $this->path($vehicle, 'charging.status.state', null);
        if (is_string($chargeStateValue) && trim($chargeStateValue) !== '') {
            $chargeState = strtoupper(trim($chargeStateValue));
            if (in_array($chargeState, ['CHARGING', 'CONSERVING'], true)) {
                $this->setIfExists('Charging', true);
            } elseif (in_array($chargeState, ['READY_FOR_CHARGING', 'CONNECT_CABLE', 'CHARGING_INTERRUPTED', 'ERROR'], true)) {
                $this->setIfExists('Charging', false);
            }
        }

        $this->setPathValue('ChargePower', $vehicle, 'charging.status.chargePowerInKw', static fn (mixed $v): float => (float) $v * 1000.0);
        $this->setPathValue('TargetSOC', $vehicle, 'charging.settings.targetStateOfChargeInPercent', static fn (mixed $v): int => (int) $v);

        $this->updateAvailableChargeModes($vehicle);
        $mode = strtoupper((string) $this->path($vehicle, 'charging.settings.preferredChargeMode', ''));
        if ($mode !== '') {
            $index = array_search($mode, self::CHARGE_MODES, true);
            if ($index !== false) {
                $this->setIfExists('ChargeMode', (int) $index);
            } else {
                $this->SendDebug('Charge mode', 'Unknown API value: ' . $mode, 0);
            }
        }

        $auxiliaryHeatingStateValue = $this->path($vehicle, 'auxiliaryHeating.state', null);
        if (is_string($auxiliaryHeatingStateValue) && trim($auxiliaryHeatingStateValue) !== '') {
            $auxiliaryHeatingState = strtoupper(trim($auxiliaryHeatingStateValue));
            if (in_array($auxiliaryHeatingState, ['ON', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true)) {
                $this->setIfExists('AuxiliaryHeating', true);
            } elseif ($auxiliaryHeatingState === 'OFF') {
                $this->setIfExists('AuxiliaryHeating', false);
            }
        }

        $climateStateValue = $this->path($vehicle, 'airConditioning.state', null);
        if (is_string($climateStateValue) && trim($climateStateValue) !== '') {
            $climateState = strtoupper(trim($climateStateValue));
            $this->setIfExists('Climate', in_array($climateState, ['ON', 'COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true));
            $this->setIfExists('ClimateState', $climateState);
        }

        $this->setPathValue('TargetTemperature', $vehicle, 'airConditioning.targetTemperature.value', static fn (mixed $v): float => (float) $v);
    }

    private function chargingStatusValue(array $vehicle, string $field): string
    {
        $value = $this->path($vehicle, 'charging.status.' . $field, null);
        return is_string($value) && trim($value) !== '' ? strtoupper(trim($value)) : 'UNKNOWN';
    }

    private function setPathValue(string $ident, array $source, string $path, Closure $convert): void
    {
        $value = $this->path($source, $path, null);
        $id = @$this->GetIDForIdent($ident);
        if ($value !== null && $id !== false && IPS_VariableExists($id)) {
            $this->SetValue($ident, $convert($value));
        }
    }

    private function updateDetailValues(array $vehicle, array $envelope): void
    {
        $this->setPathValue('VehicleName', $vehicle, 'name', static fn (mixed $v): string => (string) $v);
        $this->setPathValue('LicensePlate', $vehicle, 'licensePlate', static fn (mixed $v): string => (string) $v);
        $this->setPathValue('ChargingState', $vehicle, 'charging.status.state', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('ChargeType', $vehicle, 'charging.status.chargeType', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('FullyChargedAt', $vehicle, 'charging.status.fullyChargedAt', fn (mixed $v): int => $this->toTimestamp($v));
        $this->setPathValue('TrunkOpen', $vehicle, 'status.detail.trunk', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('BonnetOpen', $vehicle, 'status.detail.bonnet', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('SunroofOpen', $vehicle, 'status.detail.sunroof', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('LightsOn', $vehicle, 'status.overall.lights', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('ParkingState', $vehicle, 'parkingPosition.state', static fn (mixed $v): string => strtoupper((string) $v));
        $this->setPathValue('ParkingAddress', $vehicle, 'parkingPosition.formattedAddress', static fn (mixed $v): string => (string) $v);

        $latitude = $this->firstPath($vehicle, ['parkingPosition.latitude', 'parkingPosition.gpsCoordinates.latitude', 'parkingPosition.gpsCoordinates.lat']);
        $longitude = $this->firstPath($vehicle, ['parkingPosition.longitude', 'parkingPosition.gpsCoordinates.longitude', 'parkingPosition.gpsCoordinates.lon', 'parkingPosition.gpsCoordinates.lng']);
        if ($latitude !== null) {
            $this->setIfExists('Latitude', (float) $latitude);
        }
        if ($longitude !== null) {
            $this->setIfExists('Longitude', (float) $longitude);
        }

        $this->setIfExists('ApiKeyExpiresAtVar', $this->ReadAttributeInteger('ApiKeyExpiresAt'));
        $this->setIfExists('RequestsRemaining', $this->ReadAttributeInteger('RateLimitRemaining'));

        $errors = isset($envelope['errors']) && is_array($envelope['errors']) ? $envelope['errors'] : [];
        $this->setIfExists('PartialErrors', $errors === [] ? '' : json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function updateAvailableChargeModes(array $vehicle): void
    {
        $modes = $this->path($vehicle, 'charging.settings.availableChargeModes', []);
        $modes = is_array($modes) ? $modes : [];

        $current = $this->path($vehicle, 'charging.settings.preferredChargeMode', null);
        if (is_string($current) && trim($current) !== '') {
            $modes[] = $current;
        }

        $clean = [];
        foreach ($modes as $mode) {
            if (is_string($mode) && trim($mode) !== '') {
                $clean[] = strtoupper(trim($mode));
            }
        }

        $this->WriteAttributeString('AvailableChargeModes', json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function isChargeModeAvailable(string $mode): bool
    {
        $available = json_decode($this->ReadAttributeString('AvailableChargeModes'), true);
        return !is_array($available) || $available === [] || in_array($mode, $available, true);
    }

    private function valuePresentation(string $icon = '', string $suffix = '', ?int $digits = null): array
    {
        $presentation = ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION];
        if ($icon !== '') {
            $presentation['ICON'] = $icon;
        }
        if ($suffix !== '') {
            $presentation['SUFFIX'] = $suffix;
        }
        if ($digits !== null) {
            $presentation['DIGITS'] = $digits;
        }
        return $presentation;
    }

    private function booleanYesNoPresentation(bool $goodValue, string $icon = '', string $falseIcon = '', string $trueIcon = ''): array
    {
        $green = 0x22C55E;
        $orange = 0xF59E0B;
        return $this->booleanValuePresentation(
            $goodValue ? $orange : $green,
            $goodValue ? $green : $orange,
            $icon,
            $falseIcon !== '' ? $falseIcon : $icon,
            $trueIcon !== '' ? $trueIcon : $icon
        );
    }

    private function booleanActionPresentation(string $icon): array
    {
        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
            'USE_ICON_FALSE' => true,
            'ICON_TRUE' => $icon,
            'ICON_FALSE' => $icon,
            'GLOW_COLOR' => 0x22C55E,
            'GLOW_INTENSITY' => 35,
            'USAGE_TYPE' => 2
        ];
    }

    private function booleanValuePresentation(int $falseColor, int $trueColor, string $icon, string $falseIcon, string $trueIcon): array
    {
        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => $icon,
            'COLOR' => -1,
            'OPTIONS' => json_encode([
                ['Value' => false, 'Caption' => $this->Translate('No'), 'IconActive' => $falseIcon !== '', 'IconValue' => $falseIcon, 'ColorActive' => true, 'ColorValue' => $falseColor],
                ['Value' => true, 'Caption' => $this->Translate('Yes'), 'IconActive' => $trueIcon !== '', 'IconValue' => $trueIcon, 'ColorActive' => true, 'ColorValue' => $trueColor]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function stateValuePresentation(string $icon, array $states): array
    {
        $options = [];
        foreach ($states as [$value, $caption, $optionIcon, $color]) {
            $options[] = [
                'Value' => $value,
                'Caption' => $this->Translate($caption),
                'IconActive' => $optionIcon !== '',
                'IconValue' => $optionIcon,
                'ColorActive' => true,
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

    private function openStatePresentation(string $closedIcon, string $openIcon): array
    {
        return $this->stateValuePresentation($closedIcon, [
            ['CLOSED', 'Closed', $closedIcon, 0x22C55E],
            ['OPEN', 'Open', $openIcon, 0xF59E0B],
            ['UNSUPPORTED', 'Unsupported', 'circle-minus', 0x6B7280],
            ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
        ]);
    }

    private function onOffStatePresentation(string $icon): array
    {
        return $this->stateValuePresentation($icon, [
            ['OFF', 'Off', $icon, 0x22C55E],
            ['ON', 'On', $icon, 0xF59E0B],
            ['INVALID', 'Invalid', 'triangle-exclamation', 0x6B7280],
            ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
        ]);
    }

    private function doorLockStatePresentation(): array
    {
        return $this->stateValuePresentation('lock', [
            ['YES', 'Locked', 'lock', 0x22C55E],
            ['NO', 'Unlocked', 'lock-open', 0xF59E0B],
            ['OPENED', 'Door opened', 'door-open', 0xF59E0B],
            ['TRUNK_OPENED', 'Trunk opened', 'car-rear', 0xF59E0B],
            ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
        ]);
    }

    private function reliableLockStatePresentation(): array
    {
        return $this->stateValuePresentation('lock', [
            ['LOCKED', 'Locked', 'lock', 0x22C55E],
            ['UNLOCKED', 'Unlocked', 'lock-open', 0xF59E0B],
            ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
        ]);
    }

    private function climateStatePresentation(): array
    {
        return $this->stateValuePresentation('fan', [
            ['OFF', 'Off', 'power-off', 0x22C55E],
            ['ON', 'On', 'fan', 0xF59E0B],
            ['COOLING', 'Cooling', 'snowflake', 0xF59E0B],
            ['HEATING', 'Heating', 'fire', 0xF59E0B],
            ['HEATING_AUXILIARY', 'Auxiliary heating', 'fire', 0xF59E0B],
            ['VENTILATION', 'Ventilation', 'fan', 0xF59E0B],
            ['INVALID', 'Invalid', 'triangle-exclamation', 0x6B7280],
            ['UNKNOWN', 'Unknown', 'circle-question', 0x6B7280]
        ]);
    }

    private function parkingStatePresentation(): array
    {
        $green = 0x22C55E;
        $orange = 0xF59E0B;
        $gray = 0x6B7280;
        $states = [
            ['PARKED', 'Parked', 'square-parking', $green],
            ['IN_MOTION', 'Moving', 'car-side', $orange],
            ['MOVING', 'Moving', 'car-side', $orange],
            ['DRIVING', 'Driving', 'car-side', $orange],
            ['UNKNOWN', 'Unknown', 'circle-question', $gray]
        ];

        $options = [];
        foreach ($states as [$value, $caption, $icon, $color]) {
            $options[] = ['Value' => $value, 'Caption' => $this->Translate($caption), 'IconActive' => true, 'IconValue' => $icon, 'ColorActive' => $color >= 0, 'ColorValue' => $color];
        }

        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'square-parking',
            'COLOR' => -1,
            'OPTIONS' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function chargeTypePresentation(): array
    {
        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'plug',
            'COLOR' => -1,
            'OPTIONS' => json_encode([
                ['Value' => 'AC', 'Caption' => 'AC', 'IconActive' => true, 'IconValue' => 'wave-sine', 'ColorActive' => false, 'ColorValue' => -1],
                ['Value' => 'DC', 'Caption' => 'DC', 'IconActive' => true, 'IconValue' => 'equals', 'ColorActive' => false, 'ColorValue' => -1],
                ['Value' => 'OFF', 'Caption' => $this->Translate('Off'), 'IconActive' => true, 'IconValue' => 'plug', 'ColorActive' => true, 'ColorValue' => 0x22C55E]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function chargingStatePresentation(): array
    {
        $green = 0x22C55E;
        $orange = 0xF59E0B;
        $gray = 0x6B7280;
        $red = 0xEF4444;
        $states = [
            ['READY_FOR_CHARGING', 'Ready for charging', 'plug-circle-check', $green],
            ['CHARGING', 'Charging active', 'bolt', $green],
            ['CONSERVING', 'Charge conservation', 'battery-full', $green],
            ['CONNECT_CABLE', 'Connect charging cable', 'plug', $orange],
            ['CHARGING_INTERRUPTED', 'Charging interrupted', 'triangle-exclamation', $orange],
            ['ERROR', 'Error', 'circle-exclamation', $red],
            ['UNKNOWN', 'Unknown', 'circle-question', $gray]
        ];

        $options = [];
        foreach ($states as [$value, $caption, $icon, $color]) {
            $options[] = ['Value' => $value, 'Caption' => $this->Translate($caption), 'IconActive' => true, 'IconValue' => $icon, 'ColorActive' => $color >= 0, 'ColorValue' => $color];
        }

        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'plug',
            'COLOR' => -1,
            'OPTIONS' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function chargeModePresentation(): array
    {
        $options = [];
        foreach (self::CHARGE_MODES as $index => $mode) {
            $options[] = ['Value' => $index, 'Caption' => $this->humanizeMode($mode), 'IconActive' => false, 'IconValue' => '', 'Color' => -1];
        }

        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
            'ICON' => 'gear',
            'LAYOUT' => 0,
            'OPTIONS' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function humanizeMode(string $mode): string
    {
        $known = [
            'MANUAL' => $this->Translate('Manual'),
            'TIMER' => $this->Translate('Timer'),
            'TIMER_CHARGING_WITH_CLIMATISATION' => $this->Translate('Timer + climate'),
            'PREFERRED_CHARGING_TIMES' => $this->Translate('Preferred charging times'),
            'ONLY_OWN_CURRENT' => $this->Translate('Only own current'),
            'IMMEDIATE_DISCHARGING' => $this->Translate('Immediate discharging'),
            'HOME_STORAGE_CHARGING' => $this->Translate('Home storage charging'),
            'OTHER' => $this->Translate('Other'),
            'OFF' => $this->Translate('Off')
        ];
        return $known[$mode] ?? ucwords(strtolower(str_replace('_', ' ', $mode)));
    }
}
