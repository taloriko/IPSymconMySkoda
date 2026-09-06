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
        6 => 'HOME_STORAGE_CHARGING'
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
            $this->variable('Locked', 'Locked', VARIABLETYPE_BOOLEAN, 100, $this->booleanYesNoPresentation(true, 'lock', 'lock-open', 'lock')),
            $this->variable('DoorsOpen', 'Doors open', VARIABLETYPE_BOOLEAN, 110, $this->booleanYesNoPresentation(false, 'door-closed', 'door-closed', 'door-open')),
            $this->variable('WindowsOpen', 'Windows open', VARIABLETYPE_BOOLEAN, 120, $this->booleanYesNoPresentation(false, 'window-maximize')),
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
                'STEP_SIZE' => 5,
                'SUFFIX' => ' %',
                'PERCENTAGE' => false,
                'USAGE_TYPE' => 5
            ]),
            $this->variable('ChargeMode', 'Charging mode', VARIABLETYPE_INTEGER, 250, $this->chargeModePresentation()),
            $this->variable('Climate', 'Air conditioning', VARIABLETYPE_BOOLEAN, 300, $this->booleanActionPresentation('fan')),
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
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME,
                'ICON' => 'clock'
            ])
        ];
    }

    private function detailVariableDefinitions(): array
    {
        return [
            $this->variable('VehicleName', 'Vehicle name', VARIABLETYPE_STRING, 10, $this->valuePresentation('car')),
            $this->variable('LicensePlate', 'License plate', VARIABLETYPE_STRING, 20, $this->valuePresentation('id-card')),
            $this->variable('TrunkOpen', 'Trunk open', VARIABLETYPE_BOOLEAN, 130, $this->booleanYesNoPresentation(false, 'car-rear')),
            $this->variable('BonnetOpen', 'Bonnet open', VARIABLETYPE_BOOLEAN, 140, $this->booleanYesNoPresentation(false, 'car')),
            $this->variable('SunroofOpen', 'Sunroof open', VARIABLETYPE_BOOLEAN, 150, $this->booleanYesNoPresentation(false, 'car-side')),
            $this->variable('LightsOn', 'Lights on', VARIABLETYPE_BOOLEAN, 160, $this->booleanYesNoPresentation(false, 'lightbulb')),
            $this->variable('ParkingState', 'Parking state', VARIABLETYPE_STRING, 170, $this->valuePresentation('square-parking')),
            $this->variable('ChargingState', 'Charging state', VARIABLETYPE_STRING, 210, $this->chargingStatePresentation()),
            $this->variable('ChargeType', 'Charge type', VARIABLETYPE_STRING, 220, $this->valuePresentation('plug')),
            $this->variable('FullyChargedAt', 'Fully charged at', VARIABLETYPE_INTEGER, 260, [
                'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME,
                'ICON' => 'clock'
            ]),
            $this->variable('Latitude', 'Latitude', VARIABLETYPE_FLOAT, 400, $this->valuePresentation('location-dot', '', 6)),
            $this->variable('Longitude', 'Longitude', VARIABLETYPE_FLOAT, 410, $this->valuePresentation('location-dot', '', 6)),
            $this->variable('ApiKeyExpiresAtVar', 'API key valid until', VARIABLETYPE_INTEGER, 910, [
                'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
                'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME,
                'ICON' => 'key'
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
     * Creates a data point only while its ident is missing.
     * Existing metadata belongs to the user and is never registered again.
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

        if ($definition['initialValue'] !== null && @$this->GetIDForIdent($ident) !== false) {
            $this->SetValue($ident, $definition['initialValue']);
        }
    }

    private function applyActions(): void
    {
        $enabled = $this->ReadPropertyBoolean('EnableRemote');
        foreach (['Charging', 'TargetSOC', 'ChargeMode', 'Climate', 'TargetTemperature'] as $ident) {
            if (@$this->GetIDForIdent($ident) !== false) {
                $this->MaintainAction($ident, $enabled);
            }
        }
    }

    private function updateCoreValues(array $vehicle): void
    {
        $this->setPathValue('StateOfCharge', $vehicle, 'charging.status.battery.stateOfChargeInPercent', static fn (mixed $v): int => (int) $v);
        $this->setPathValue('Range', $vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', static fn (mixed $v): int => (int) round((float) $v / 1000));
        $this->setPathValue('Mileage', $vehicle, 'odometer.mileageInKm', static fn (mixed $v): int => (int) round((float) $v));

        $lock = strtoupper((string) $this->path($vehicle, 'status.overall.doorsLocked', $this->path($vehicle, 'status.overall.locked', 'UNKNOWN')));
        $this->SetValue('Locked', in_array($lock, ['YES', 'LOCKED'], true));
        $this->SetValue('DoorsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.doors', 'CLOSED')) === 'OPEN');
        $this->SetValue('WindowsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.windows', 'CLOSED')) === 'OPEN');

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

        $climateState = strtoupper((string) $this->path($vehicle, 'airConditioning.state', 'OFF'));
        $this->SetValue('Climate', in_array($climateState, ['COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true));
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
        $this->setIfExists('ChargingState', (string) $this->path($vehicle, 'charging.status.state', ''));
        $this->setIfExists('ChargeType', (string) $this->path($vehicle, 'charging.status.chargeType', ''));
        $this->setIfExists('FullyChargedAt', $this->toTimestamp($this->path($vehicle, 'charging.status.fullyChargedAt', null)));
        $this->setIfExists('TrunkOpen', strtoupper((string) $this->path($vehicle, 'status.detail.trunk', 'CLOSED')) === 'OPEN');
        $this->setIfExists('BonnetOpen', strtoupper((string) $this->path($vehicle, 'status.detail.bonnet', 'CLOSED')) === 'OPEN');
        $this->setIfExists('SunroofOpen', strtoupper((string) $this->path($vehicle, 'status.detail.sunroof', 'CLOSED')) === 'OPEN');
        $this->setIfExists('LightsOn', strtoupper((string) $this->path($vehicle, 'status.overall.lights', 'OFF')) === 'ON');
        $this->setIfExists('ParkingState', (string) $this->path($vehicle, 'parkingPosition.state', ''));

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

    private function chargingStatePresentation(): array
    {
        $green = 0x22C55E;
        $orange = 0xF59E0B;
        $states = [
            ['CONNECT_CABLE', 'Connect charging cable', 'plug', $orange],
            ['CHARGING', 'Charging active', 'bolt', $green],
            ['CONSERVING', 'Charge conservation', 'battery-full', $green],
            ['READY_FOR_CHARGING', 'Ready for charging', 'plug-circle-check', $green],
            ['DISCHARGING', 'Discharging', 'battery-half', $orange],
            ['CHARGING_INTERRUPTED', 'Charging interrupted', 'triangle-exclamation', $orange],
            ['OFF', 'Off', 'plug', -1],
            ['UNKNOWN', 'Unknown', 'circle-question', -1]
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
            'HOME_STORAGE_CHARGING' => $this->Translate('Home storage charging')
        ];
        return $known[$mode] ?? ucwords(strtolower(str_replace('_', ' ', $mode)));
    }
}
