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
            $this->registerVariableOnce($definition);
        }

        if ($this->ReadPropertyBoolean('ShowDetails')) {
            foreach ($this->detailVariableDefinitions() as $definition) {
                $this->registerVariableOnce($definition);
            }
        }

        $this->applyDefaultObjectIcons();
    }

    private function coreVariableDefinitions(): array
    {
        return [
            $this->variable('StateOfCharge', 'State of charge', VARIABLETYPE_INTEGER, 30, [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_BATTERY
            ]),
            $this->variable('Range', 'Range', VARIABLETYPE_INTEGER, 40, $this->valuePresentation('route', ' km', 0)),
            $this->variable('Mileage', 'Mileage', VARIABLETYPE_INTEGER, 50, array_merge(
                $this->valuePresentation('gauge-high', ' km', 0),
                ['THOUSANDS_SEPARATOR' => '.']
            )),
            $this->variable('DoorsLocked', 'Door lock status', VARIABLETYPE_STRING, 100, $this->doorLockStatePresentation()),
            $this->variable('Locked', 'Vehicle lock status', VARIABLETYPE_STRING, 101, $this->doorLockStatePresentation()),
            $this->variable('ReliableLockStatus', 'Reliable lock status', VARIABLETYPE_STRING, 102, $this->reliableLockStatePresentation()),
            $this->variable('DoorsOpen', 'Doors', VARIABLETYPE_STRING, 110, $this->openStatePresentation('door-closed', 'door-open')),
            $this->variable('WindowsOpen', 'Windows', VARIABLETYPE_STRING, 120, $this->openStatePresentation('window-maximize', 'window-maximize')),
            $this->variable('Charging', 'Charging', VARIABLETYPE_BOOLEAN, 200, $this->booleanActionPresentation('plug')),
            $this->variable('ChargePower', 'Charging power', VARIABLETYPE_FLOAT, 230, [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
            ]),
            $this->variable('TargetSOC', 'Charging limit', VARIABLETYPE_INTEGER, 240, [
                'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
                'ICON' => 'battery-half',
                'MIN' => 50,
                'MAX' => 100,
                'STEP_SIZE' => 10,
                'SUFFIX' => ' %',
                'PERCENTAGE' => false,
                'USAGE_TYPE' => 5
            ]),
            $this->variable('ChargeMode', 'Charging mode', VARIABLETYPE_INTEGER, 250, $this->chargeModePresentation()),
            $this->variable('Climate', 'Air conditioning', VARIABLETYPE_BOOLEAN, 300, $this->booleanActionPresentation('fan')),
            $this->variable('ClimateState', 'Air conditioning state', VARIABLETYPE_STRING, 305, $this->climateStatePresentation()),
            $this->variable('TargetTemperature', 'Target temperature', VARIABLETYPE_FLOAT, 310, [
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
            $this->variable('ApiKeyWarning', 'API key warning', VARIABLETYPE_BOOLEAN, 900, $this->booleanYesNoPresentation(false, 'key')),
            $this->variable('LastUpdate', 'Last update', VARIABLETYPE_INTEGER, 990, [
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
            $this->variable('TrunkOpen', 'Trunk', VARIABLETYPE_STRING, 130, $this->openStatePresentation('car-rear', 'car-rear')),
            $this->variable('BonnetOpen', 'Bonnet', VARIABLETYPE_STRING, 140, $this->openStatePresentation('car', 'car')),
            $this->variable('SunroofOpen', 'Sunroof', VARIABLETYPE_STRING, 150, $this->openStatePresentation('car-side', 'car-side')),
            $this->variable('LightsOn', 'Lights', VARIABLETYPE_STRING, 160, $this->onOffStatePresentation('lightbulb')),
            $this->variable('ParkingState', 'Parking state', VARIABLETYPE_STRING, 170, $this->parkingStatePresentation()),
            $this->variable('ParkingAddress', 'Parking address', VARIABLETYPE_STRING, 171, $this->valuePresentation('location-dot')),
            $this->variable('ChargingState', 'Charging state', VARIABLETYPE_STRING, 210, $this->chargingStatePresentation()),
            $this->variable('ChargeType', 'Charge type', VARIABLETYPE_STRING, 220, $this->chargeTypePresentation()),
            $this->variable('FullyChargedAt', 'Fully charged at', VARIABLETYPE_INTEGER, 260, [
                'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
            ]),
            $this->variable('Latitude', 'Latitude', VARIABLETYPE_FLOAT, 400, $this->valuePresentation('location-dot', '', 6)),
            $this->variable('Longitude', 'Longitude', VARIABLETYPE_FLOAT, 410, $this->valuePresentation('location-dot', '', 6)),
            $this->variable('ApiKeyExpiresAtVar', 'API key valid until', VARIABLETYPE_INTEGER, 910, [
                'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
            ]),
            $this->variable('RequestsRemaining', 'API requests remaining', VARIABLETYPE_INTEGER, 920, $this->valuePresentation('gauge')),
            $this->variable('PartialErrors', 'API partial errors', VARIABLETYPE_STRING, 930, [
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
     * Creates variables only while their ident is missing. Existing names and positions
     * remain user-owned. Selected variables are re-registered to refresh module-provided
     * presentations; a user-defined custom presentation remains untouched.
     */
    private function registerVariableOnce(array $definition): void
    {
        $ident = (string) $definition['ident'];
        $existingId = @$this->GetIDForIdent($ident);
        $breakingTypeIdents = [
            'Locked',
            'DoorsOpen',
            'WindowsOpen',
            'TrunkOpen',
            'BonnetOpen',
            'SunroofOpen',
            'LightsOn'
        ];
        if ($existingId !== false && in_array($ident, $breakingTypeIdents, true) && IPS_VariableExists($existingId)) {
            $existingVariable = IPS_GetVariable($existingId);
            if ((int) ($existingVariable['VariableType'] ?? -1) !== (int) $definition['type']) {
                IPS_DeleteVariable($existingId);
                $existingId = false;
            }
        }
        if ($existingId !== false) {
            if (!IPS_VariableExists($existingId)) {
                $this->LogMessage(sprintf('MySkoda: ident "%s" is already used by another object.', $ident), KL_WARNING);
                return;
            }

            $variable = IPS_GetVariable($existingId);
            if ((int) ($variable['VariableType'] ?? -1) !== (int) $definition['type']) {
                $this->LogMessage(sprintf('MySkoda: variable "%s" has an unexpected type.', $ident), KL_ERROR);
                return;
            }

            if (in_array($ident, [
                'DoorsLocked',
                'Locked',
                'ReliableLockStatus',
                'DoorsOpen',
                'WindowsOpen',
                'TrunkOpen',
                'BonnetOpen',
                'SunroofOpen',
                'LightsOn',
                'ParkingAddress',
                'ClimateState'
            ], true)) {
                IPS_SetName($existingId, $this->Translate((string) $definition['name']));
            }

            if ($ident === 'TargetSOC') {
                $this->RegisterVariableInteger(
                    $ident,
                    $this->Translate((string) $definition['name']),
                    (array) $definition['presentation'],
                    (int) $definition['position']
                );
            }
            if (in_array($ident, ['LastUpdate', 'FullyChargedAt', 'ApiKeyExpiresAtVar'], true)) {
                $this->RegisterVariableInteger(
                    $ident,
                    $this->Translate((string) $definition['name']),
                    (array) $definition['presentation'],
                    (int) $definition['position']
                );
            }
            if (in_array($ident, [
                'ParkingState',
                'ParkingAddress',
                'ChargeType',
                'DoorsLocked',
                'Locked',
                'ReliableLockStatus',
                'DoorsOpen',
                'WindowsOpen',
                'TrunkOpen',
                'BonnetOpen',
                'SunroofOpen',
                'LightsOn',
                'ClimateState'
            ], true)) {
                $this->RegisterVariableString(
                    $ident,
                    $this->Translate((string) $definition['name']),
                    (array) $definition['presentation'],
                    (int) $definition['position']
                );
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

        if ($definition['initialValue'] !== null && @$this->GetIDForIdent($ident) !== false) {
            $this->SetValue($ident, $definition['initialValue']);
        }
    }

    private function applyDefaultObjectIcons(): void
    {
        $icons = [
            'LastUpdate' => 'clock-rotate-left',
            'FullyChargedAt' => 'battery-full',
            'ApiKeyExpiresAtVar' => 'arrow-right-to-line'
        ];

        foreach ($icons as $ident => $icon) {
            $variableId = @$this->GetIDForIdent($ident);
            if ($variableId === false || !IPS_VariableExists($variableId)) {
                continue;
            }

            $object = IPS_GetObject($variableId);
            if ((string) ($object['ObjectIcon'] ?? '') === '') {
                IPS_SetIcon($variableId, $icon);
            }
        }
    }

    private function applyActions(): void
    {
        $enabled = $this->ReadPropertyBoolean('EnableRemote');
        foreach (['Charging', 'TargetSOC', 'ChargeMode', 'Climate', 'TargetTemperature'] as $ident) {
            $this->MaintainAction($ident, $enabled);
        }
    }

    private function updateCoreValues(array $vehicle): void
    {
        $this->setPathValue('StateOfCharge', $vehicle, 'charging.status.battery.stateOfChargeInPercent', static fn (mixed $v): int => (int) $v);
        $this->setPathValue('Range', $vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', static fn (mixed $v): int => (int) round((float) $v / 1000));

        $mileage = (int) round((float) $this->path($vehicle, 'odometer.mileageInKm', 0));
        if ($mileage > 0) {
            $this->SetValue('Mileage', $mileage);
        }

        $this->SetValue('DoorsLocked', strtoupper((string) $this->path($vehicle, 'status.overall.doorsLocked', 'UNKNOWN')));
        $this->SetValue('Locked', strtoupper((string) $this->path($vehicle, 'status.overall.locked', 'UNKNOWN')));
        $this->SetValue('ReliableLockStatus', strtoupper((string) $this->path($vehicle, 'status.overall.reliableLockStatus', 'UNKNOWN')));
        $this->SetValue('DoorsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.doors', 'UNKNOWN')));
        $this->SetValue('WindowsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.windows', 'UNKNOWN')));

        $chargeState = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $this->SetValue('Charging', in_array($chargeState, ['CHARGING', 'CONSERVING'], true));
        $this->setPathValue('ChargePower', $vehicle, 'charging.status.chargePowerInKw', static fn (mixed $v): float => (float) $v * 1000.0);
        $this->setPathValue('TargetSOC', $vehicle, 'charging.settings.targetStateOfChargeInPercent', static fn (mixed $v): int => (int) $v);

        $this->updateAvailableChargeModes($vehicle);
        $mode = strtoupper((string) $this->path($vehicle, 'charging.settings.preferredChargeMode', ''));
        if ($mode !== '') {
            $index = array_search($mode, self::CHARGE_MODES, true);
            if ($index !== false) {
                $this->SetValue('ChargeMode', (int) $index);
            } else {
                $this->SendDebug('Charge mode', 'Unknown API value: ' . $mode, 0);
            }
        }

        $climateState = strtoupper((string) $this->path($vehicle, 'airConditioning.state', 'UNKNOWN'));
        $this->SetValue('Climate', in_array($climateState, ['ON', 'COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true));
        $this->SetValue('ClimateState', $climateState);
        $this->setPathValue('TargetTemperature', $vehicle, 'airConditioning.targetTemperature.value', static fn (mixed $v): float => (float) $v);
    }

    private function setPathValue(string $ident, array $source, string $path, Closure $convert): void
    {
        $value = $this->path($source, $path, null);
        if ($value !== null) {
            $this->SetValue($ident, $convert($value));
        }
    }

    private function updateDetailValues(array $vehicle, array $envelope): void
    {
        $this->setIfExists('VehicleName', (string) $this->path($vehicle, 'name', ''));
        $this->setIfExists('LicensePlate', (string) $this->path($vehicle, 'licensePlate', ''));
        $this->setIfExists('ChargingState', strtoupper((string) $this->path($vehicle, 'charging.status.state', 'UNKNOWN')));
        $this->setIfExists('ChargeType', strtoupper((string) $this->path($vehicle, 'charging.status.chargeType', 'OFF')));
        $this->setIfExists('FullyChargedAt', $this->toTimestamp($this->path($vehicle, 'charging.status.fullyChargedAt', null)));
        $this->setIfExists('TrunkOpen', strtoupper((string) $this->path($vehicle, 'status.detail.trunk', 'UNKNOWN')));
        $this->setIfExists('BonnetOpen', strtoupper((string) $this->path($vehicle, 'status.detail.bonnet', 'UNKNOWN')));
        $this->setIfExists('SunroofOpen', strtoupper((string) $this->path($vehicle, 'status.detail.sunroof', 'UNKNOWN')));
        $this->setIfExists('LightsOn', strtoupper((string) $this->path($vehicle, 'status.overall.lights', 'UNKNOWN')));
        $this->setIfExists('ParkingState', strtoupper((string) $this->path($vehicle, 'parkingPosition.state', 'UNKNOWN')));
        $this->setIfExists('ParkingAddress', (string) $this->path($vehicle, 'parkingPosition.formattedAddress', ''));

        $latitude = $this->firstPath($vehicle, ['parkingPosition.latitude', 'parkingPosition.gpsCoordinates.latitude', 'parkingPosition.gpsCoordinates.lat']);
        $longitude = $this->firstPath($vehicle, ['parkingPosition.longitude', 'parkingPosition.gpsCoordinates.longitude', 'parkingPosition.gpsCoordinates.lon', 'parkingPosition.gpsCoordinates.lng']);
        $this->setIfExists('Latitude', $latitude !== null ? (float) $latitude : 0.0);
        $this->setIfExists('Longitude', $longitude !== null ? (float) $longitude : 0.0);
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
