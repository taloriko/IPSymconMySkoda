<?php

declare(strict_types=1);

trait MySkodaVariablesTrait
{
    private function registerCoreVariables(): void
    {
        // Fahrzeug
        $this->RegisterVariableInteger('StateOfCharge', $this->Translate('State of charge'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_BATTERY
        ], 30);

        $this->RegisterVariableInteger('Range', $this->Translate('Range'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'route',
            'SUFFIX' => ' km',
            'DIGITS' => 0
        ], 40);

        $this->RegisterVariableInteger('Mileage', $this->Translate('Mileage'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'gauge-high',
            'SUFFIX' => ' km',
            'DIGITS' => 0,
            'THOUSANDS_SEPARATOR' => '.'
        ], 50);

        // Fahrzeugstatus
        $this->RegisterVariableBoolean(
            'Locked',
            $this->Translate('Locked'),
            $this->booleanYesNoPresentation(true, 'lock', 'lock-open', 'lock'),
            100
        );
        $this->RegisterVariableBoolean(
            'DoorsOpen',
            $this->Translate('Doors open'),
            $this->booleanYesNoPresentation(false, 'door-closed', 'door-closed', 'door-open'),
            110
        );
        $this->RegisterVariableBoolean(
            'WindowsOpen',
            $this->Translate('Windows open'),
            $this->booleanYesNoPresentation(false, 'window-maximize'),
            120
        );

        // Laden
        $this->RegisterVariableBoolean('Charging', $this->Translate('Charging'), $this->booleanActivePresentation('plug'), 200);

        $this->RegisterVariableFloat('ChargePower', $this->Translate('Charging power'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
        ], 230);

        $this->RegisterVariableInteger('TargetSOC', $this->Translate('Charging limit'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
            'ICON' => 'battery-half',
            'MIN' => 50,
            'MAX' => 100,
            'STEP_SIZE' => 5,
            'SUFFIX' => ' %',
            'PERCENTAGE' => false,
            'USAGE_TYPE' => 5
        ], 240);

        $this->RegisterVariableInteger('ChargeMode', $this->Translate('Charging mode'), $this->chargeModePresentation([]), 250);

        // Klimatisierung
        $this->RegisterVariableBoolean('Climate', $this->Translate('Air conditioning'), $this->booleanActivePresentation('fan'), 300);

        $this->RegisterVariableFloat('TargetTemperature', $this->Translate('Target temperature'), [
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
        ], 310);

        // API & Diagnose
        $this->RegisterVariableBoolean(
            'ApiKeyWarning',
            $this->Translate('API key warning'),
            $this->booleanYesNoPresentation(false, 'key'),
            900
        );
        $this->RegisterVariableInteger('LastUpdate', $this->Translate('Last update'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME,
            'ICON' => 'clock'
        ], 990);

        if ((float) $this->GetValue('TargetTemperature') === 0.0) {
            $this->SetValue('TargetTemperature', 22.0);
        }
    }

    private function registerOptionalVariables(bool $show): void
    {
        $idents = [
            'VehicleName', 'LicensePlate', 'ChargingState', 'ChargeType', 'FullyChargedAt',
            'TrunkOpen', 'BonnetOpen', 'SunroofOpen', 'LightsOn', 'ParkingState', 'Latitude', 'Longitude',
            'ApiKeyExpiresAtVar', 'RequestsRemaining', 'PartialErrors'
        ];

        if (!$show) {
            foreach ($idents as $ident) {
                $this->dropVariable($ident);
            }
            return;
        }

        // Fahrzeug
        $this->RegisterVariableString('VehicleName', $this->Translate('Vehicle name'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'car'
        ], 10);
        $this->RegisterVariableString('LicensePlate', $this->Translate('License plate'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'id-card'
        ], 20);

        // Fahrzeugstatus
        $this->RegisterVariableBoolean(
            'TrunkOpen',
            $this->Translate('Trunk open'),
            $this->booleanYesNoPresentation(false, 'car-rear'),
            130
        );
        $this->RegisterVariableBoolean(
            'BonnetOpen',
            $this->Translate('Bonnet open'),
            $this->booleanYesNoPresentation(false, 'car'),
            140
        );
        $this->RegisterVariableBoolean(
            'SunroofOpen',
            $this->Translate('Sunroof open'),
            $this->booleanYesNoPresentation(false, 'car-side'),
            150
        );
        $this->RegisterVariableBoolean(
            'LightsOn',
            $this->Translate('Lights on'),
            $this->booleanYesNoPresentation(false, 'lightbulb'),
            160
        );
        $this->RegisterVariableString('ParkingState', $this->Translate('Parking state'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'square-parking'
        ], 170);

        // Laden
        $this->RegisterVariableString('ChargingState', $this->Translate('Charging state'), $this->chargingStatePresentation(), 210);
        $this->RegisterVariableString('ChargeType', $this->Translate('Charge type'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'plug'
        ], 220);
        $this->RegisterVariableInteger('FullyChargedAt', $this->Translate('Fully charged at'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME,
            'ICON' => 'clock'
        ], 260);

        // Standort
        $this->RegisterVariableFloat('Latitude', $this->Translate('Latitude'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'location-dot',
            'DIGITS' => 6
        ], 400);
        $this->RegisterVariableFloat('Longitude', $this->Translate('Longitude'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'location-dot',
            'DIGITS' => 6
        ], 410);

        // API & Diagnose
        $this->RegisterVariableInteger('ApiKeyExpiresAtVar', $this->Translate('API key valid until'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME,
            'ICON' => 'key'
        ], 910);
        $this->RegisterVariableInteger('RequestsRemaining', $this->Translate('API requests remaining'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'gauge'
        ], 920);
        $this->RegisterVariableString('PartialErrors', $this->Translate('API partial errors'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'triangle-exclamation'
        ], 930);
    }

    private function applyActions(): void
    {
        $active = $this->ReadPropertyBoolean('EnableRemote');
        foreach (['Charging', 'TargetSOC', 'ChargeMode', 'Climate', 'TargetTemperature'] as $ident) {
            $this->MaintainAction($ident, $active);
        }
    }

    private function updateCoreValues(array $vehicle): void
    {
        $soc = $this->path($vehicle, 'charging.status.battery.stateOfChargeInPercent', null);
        if ($soc !== null) {
            $this->SetValue('StateOfCharge', (int) $soc);
        }

        $rangeMeters = $this->path($vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', null);
        if ($rangeMeters !== null) {
            $this->SetValue('Range', (int) round(((float) $rangeMeters) / 1000));
        }

        $mileage = $this->path($vehicle, 'odometer.mileageInKm', null);
        if ($mileage !== null) {
            $this->SetValue('Mileage', (int) round((float) $mileage));
        }

        $lock = strtoupper((string) ($this->path($vehicle, 'status.overall.doorsLocked', $this->path($vehicle, 'status.overall.locked', 'UNKNOWN'))));
        $this->SetValue('Locked', $lock === 'YES' || $lock === 'LOCKED');
        $this->SetValue('DoorsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.doors', 'CLOSED')) === 'OPEN');
        $this->SetValue('WindowsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.windows', 'CLOSED')) === 'OPEN');

        $chargeState = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $this->SetValue('Charging', in_array($chargeState, ['CHARGING', 'CONSERVING'], true));

        $chargePowerKw = $this->path($vehicle, 'charging.status.chargePowerInKw', null);
        if ($chargePowerKw !== null) {
            $this->SetValue('ChargePower', ((float) $chargePowerKw) * 1000.0);
        }

        $targetSoc = $this->path($vehicle, 'charging.settings.targetStateOfChargeInPercent', null);
        if ($targetSoc !== null) {
            $this->SetValue('TargetSOC', (int) $targetSoc);
        }

        $mode = $this->path($vehicle, 'charging.settings.preferredChargeMode', null);
        if (is_string($mode) && $mode !== '') {
            $map = json_decode($this->ReadAttributeString('ChargeModeMap'), true);
            if (is_array($map)) {
                $index = array_search($mode, $map, true);
                if ($index !== false) {
                    $this->SetValue('ChargeMode', (int) $index);
                }
            }
        }

        $climateState = strtoupper((string) $this->path($vehicle, 'airConditioning.state', 'OFF'));
        $this->SetValue('Climate', in_array($climateState, ['COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true));

        $temperature = $this->path($vehicle, 'airConditioning.targetTemperature.value', null);
        if ($temperature !== null) {
            $this->SetValue('TargetTemperature', (float) $temperature);
        }
    }

    private function updateOptionalValues(array $vehicle, array $envelope): void
    {
        if (!$this->ReadPropertyBoolean('ShowDetails')) {
            return;
        }

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

        $lat = $this->firstPath($vehicle, ['parkingPosition.latitude', 'parkingPosition.gpsCoordinates.latitude', 'parkingPosition.gpsCoordinates.lat']);
        $lon = $this->firstPath($vehicle, ['parkingPosition.longitude', 'parkingPosition.gpsCoordinates.longitude', 'parkingPosition.gpsCoordinates.lon', 'parkingPosition.gpsCoordinates.lng']);
        $this->setIfExists('Latitude', $lat !== null ? (float) $lat : 0.0);
        $this->setIfExists('Longitude', $lon !== null ? (float) $lon : 0.0);
        $this->setIfExists('ApiKeyExpiresAtVar', $this->ReadAttributeInteger('ApiKeyExpiresAt'));
        $this->setIfExists('RequestsRemaining', $this->ReadAttributeInteger('RateLimitRemaining'));

        $errors = isset($envelope['errors']) && is_array($envelope['errors']) ? $envelope['errors'] : [];
        $this->setIfExists('PartialErrors', $errors === [] ? '' : json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function booleanYesNoPresentation(
        bool $goodValue,
        string $icon = '',
        string $falseIcon = '',
        string $trueIcon = ''
    ): array {
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

    private function booleanActivePresentation(string $icon = ''): array
    {
        if ($this->ReadPropertyBoolean('EnableRemote')) {
            return [
                'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
                'USE_ICON_FALSE' => $icon !== '',
                'ICON_TRUE' => $icon,
                'ICON_FALSE' => $icon,
                'GLOW_COLOR' => 0x22C55E,
                'GLOW_INTENSITY' => 35,
                'USAGE_TYPE' => 2
            ];
        }
        return $this->booleanValuePresentation(0x22C55E, 0x22C55E, $icon, $icon, $icon);
    }

    private function booleanValuePresentation(
        int $falseColor,
        int $trueColor,
        string $icon = '',
        string $falseIcon = '',
        string $trueIcon = ''
    ): array {
        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => $icon,
            'COLOR' => -1,
            'OPTIONS' => json_encode([
                [
                    'Value' => false,
                    'Caption' => $this->Translate('No'),
                    'IconActive' => $falseIcon !== '',
                    'IconValue' => $falseIcon,
                    'ColorActive' => true,
                    'ColorValue' => $falseColor
                ],
                [
                    'Value' => true,
                    'Caption' => $this->Translate('Yes'),
                    'IconActive' => $trueIcon !== '',
                    'IconValue' => $trueIcon,
                    'ColorActive' => true,
                    'ColorValue' => $trueColor
                ]
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
            $options[] = [
                'Value' => $value,
                'Caption' => $this->Translate($caption),
                'IconActive' => true,
                'IconValue' => $icon,
                'ColorActive' => $color >= 0,
                'ColorValue' => $color
            ];
        }

        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'ICON' => 'plug',
            'COLOR' => -1,
            'OPTIONS' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function updateChargeModePresentation(array $vehicle): void
    {
        $modes = $this->path($vehicle, 'charging.settings.availableChargeModes', []);
        if (!is_array($modes)) {
            $modes = [];
        }
        $current = $this->path($vehicle, 'charging.settings.preferredChargeMode', null);
        if (is_string($current) && $current !== '' && !in_array($current, $modes, true)) {
            $modes[] = $current;
        }

        $clean = [];
        foreach ($modes as $mode) {
            if (is_string($mode) && $mode !== '' && !in_array($mode, $clean, true)) {
                $clean[] = $mode;
            }
        }

        $map = [];
        foreach (array_values($clean) as $index => $mode) {
            $map[(string) $index] = $mode;
        }

        $this->WriteAttributeString('ChargeModeMap', json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // Dynamic presentation updates still use RegisterVariable*/MaintainAction,
        // therefore temporarily place only this variable at the instance root.
        $this->moveManagedVariableToRegistrationRoot('ChargeMode');
        $this->RegisterVariableInteger('ChargeMode', $this->Translate('Charging mode'), $this->chargeModePresentation($map), 250);
        $this->MaintainAction('ChargeMode', $this->ReadPropertyBoolean('EnableRemote') && $map !== []);
        $this->placeManagedVariable('ChargeMode');
    }

    private function chargeModePresentation(array $map): array
    {
        $options = [];
        foreach ($map as $index => $mode) {
            $options[] = [
                'Value' => (int) $index,
                'Caption' => $this->humanizeMode((string) $mode),
                'IconActive' => false,
                'IconValue' => '',
                'Color' => -1
            ];
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
            'AC' => 'AC',
            'DC' => 'DC',
            'OFF' => $this->Translate('Off'),
            'COOLING' => $this->Translate('Cooling'),
            'HEATING' => $this->Translate('Heating'),
            'HEATING_AUXILIARY' => $this->Translate('Auxiliary heating'),
            'VENTILATION' => $this->Translate('Ventilation')
        ];
        return $known[$mode] ?? ucwords(strtolower(str_replace('_', ' ', $mode)));
    }
}
