<?php

declare(strict_types=1);

trait MySkodaVariablesTrait
{
    private function registerCoreVariables(): void
    {
        $this->registerBooleanProfiles();
        $this->RegisterVariableInteger('StateOfCharge', $this->Translate('State of charge'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_BATTERY
        ], 10);

        $this->RegisterVariableInteger('Range', $this->Translate('Range'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'SUFFIX' => ' km',
            'DIGITS' => 0
        ], 20);

        $this->RegisterVariableInteger('Mileage', $this->Translate('Mileage'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'SUFFIX' => ' km',
            'DIGITS' => 0,
            'THOUSANDS_SEPARATOR' => '.'
        ], 30);

        $this->RegisterVariableBoolean('Locked', $this->Translate('Locked'), [], 40);
        $this->applyBooleanProfile('Locked', 'MySkoda.YesNo.GoodTrue');
        $this->RegisterVariableBoolean('DoorsOpen', $this->Translate('Doors open'), [], 50);
        $this->applyBooleanProfile('DoorsOpen', 'MySkoda.YesNo.GoodFalse');
        $this->RegisterVariableBoolean('WindowsOpen', $this->Translate('Windows open'), [], 60);
        $this->applyBooleanProfile('WindowsOpen', 'MySkoda.YesNo.GoodFalse');

        $this->RegisterVariableBoolean('Charging', $this->Translate('Charging'), [], 70);
        $this->applyBooleanProfile('Charging', 'MySkoda.YesNo.ActiveTrue');

        $this->RegisterVariableFloat('ChargePower', $this->Translate('Charging power'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
        ], 80);

        $this->RegisterVariableInteger('TargetSOC', $this->Translate('Charging limit'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
            'MIN' => 50,
            'MAX' => 100,
            'STEP_SIZE' => 5,
            'SUFFIX' => ' %',
            'PERCENTAGE' => false,
            'USAGE_TYPE' => 5
        ], 90);

        $this->RegisterVariableInteger('ChargeMode', $this->Translate('Charging mode'), $this->chargeModePresentation([]), 100);

        $this->RegisterVariableBoolean('Climate', $this->Translate('Air conditioning'), [], 110);
        $this->applyBooleanProfile('Climate', 'MySkoda.YesNo.ActiveTrue');

        $this->RegisterVariableFloat('TargetTemperature', $this->Translate('Target temperature'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
            'MIN' => 16,
            'MAX' => 30,
            'STEP_SIZE' => 0.5,
            'GRADIENT_TYPE' => 1,
            'USAGE_TYPE' => 0,
            'SUFFIX' => ' °C',
            'PERCENTAGE' => false,
            'DIGITS' => 1
        ], 120);

        $this->RegisterVariableString('VehicleTile', $this->Translate('Vehicle overview'), [], 130);
        $this->applyHtmlBoxProfile('VehicleTile');

        $this->RegisterVariableBoolean('ApiKeyWarning', $this->Translate('API key warning'), [], 140);
        $this->applyBooleanProfile('ApiKeyWarning', 'MySkoda.YesNo.GoodFalse');

        if ((float) $this->GetValue('TargetTemperature') === 0.0) {
            $this->SetValue('TargetTemperature', 22.0);
        }

        $this->RegisterVariableString('LastUpdateAge', $this->Translate('Last update age'), [], 890);

        $this->RegisterVariableInteger('LastUpdate', $this->Translate('Last update'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
        ], 900);
    }

    private function registerOptionalVariables(bool $show): void
    {
        if (!$show) {
            foreach ([
                'VehicleName', 'LicensePlate', 'ChargingState', 'ChargeType', 'FullyChargedAt',
                'TrunkOpen', 'BonnetOpen', 'LightsOn', 'ParkingState', 'Latitude', 'Longitude',
                'ApiKeyExpiresAtVar', 'RequestsRemaining', 'PartialErrors'
            ] as $ident) {
                $this->dropVariable($ident);
            }
            return;
        }

        $this->RegisterVariableString('VehicleName', $this->Translate('Vehicle name'), [], 200);
        $this->RegisterVariableString('LicensePlate', $this->Translate('License plate'), [], 210);
        $this->RegisterVariableString('ChargingState', $this->Translate('Charging state'), [], 220);
        $this->RegisterVariableString('ChargeType', $this->Translate('Charge type'), [], 230);
        $this->RegisterVariableInteger('FullyChargedAt', $this->Translate('Fully charged at'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
        ], 240);
        $this->RegisterVariableBoolean('TrunkOpen', $this->Translate('Trunk open'), [], 250);
        $this->applyBooleanProfile('TrunkOpen', 'MySkoda.YesNo.GoodFalse');
        $this->RegisterVariableBoolean('BonnetOpen', $this->Translate('Bonnet open'), [], 260);
        $this->applyBooleanProfile('BonnetOpen', 'MySkoda.YesNo.GoodFalse');
        $this->RegisterVariableBoolean('LightsOn', $this->Translate('Lights on'), [], 270);
        $this->applyBooleanProfile('LightsOn', 'MySkoda.YesNo.GoodFalse');
        $this->RegisterVariableString('ParkingState', $this->Translate('Parking state'), [], 280);
        $this->RegisterVariableFloat('Latitude', $this->Translate('Latitude'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'DIGITS' => 6
        ], 290);
        $this->RegisterVariableFloat('Longitude', $this->Translate('Longitude'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'DIGITS' => 6
        ], 300);
        $this->RegisterVariableInteger('ApiKeyExpiresAtVar', $this->Translate('API key valid until'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
        ], 310);
        $this->RegisterVariableInteger('RequestsRemaining', $this->Translate('API requests remaining'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION
        ], 320);
        $this->RegisterVariableString('PartialErrors', $this->Translate('API partial errors'), [], 330);
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
            // Symcon Standardvorlage Leistung arbeitet mit Watt.
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

        $full = $this->toTimestamp($this->path($vehicle, 'charging.status.fullyChargedAt', null));
        if ($full > 0) {
            $this->setIfExists('FullyChargedAt', $full);
        }

        $this->setIfExists('TrunkOpen', strtoupper((string) $this->path($vehicle, 'status.detail.trunk', 'CLOSED')) === 'OPEN');
        $this->setIfExists('BonnetOpen', strtoupper((string) $this->path($vehicle, 'status.detail.bonnet', 'CLOSED')) === 'OPEN');
        $this->setIfExists('LightsOn', strtoupper((string) $this->path($vehicle, 'status.overall.lights', 'OFF')) === 'ON');
        $this->setIfExists('ParkingState', (string) $this->path($vehicle, 'parkingPosition.state', ''));

        $lat = $this->firstPath($vehicle, ['parkingPosition.latitude', 'parkingPosition.gpsCoordinates.latitude', 'parkingPosition.gpsCoordinates.lat']);
        $lon = $this->firstPath($vehicle, ['parkingPosition.longitude', 'parkingPosition.gpsCoordinates.longitude', 'parkingPosition.gpsCoordinates.lon', 'parkingPosition.gpsCoordinates.lng']);
        // Standortdaten werden von MySkoda nur geliefert, wenn die Standortfreigabe
        // fuer das Profil erteilt wurde. Fehlt die Freigabe, bewusst 0/0 schreiben,
        // damit keine alten Koordinaten aus einem vorherigen Abruf stehen bleiben.
        $this->setIfExists('Latitude', $lat !== null ? (float) $lat : 0.0);
        $this->setIfExists('Longitude', $lon !== null ? (float) $lon : 0.0);

        $expiry = $this->ReadAttributeInteger('ApiKeyExpiresAt');
        if ($expiry > 0) {
            $this->setIfExists('ApiKeyExpiresAtVar', $expiry);
        }
        $remaining = $this->ReadAttributeInteger('RateLimitRemaining');
        if ($remaining >= 0) {
            $this->setIfExists('RequestsRemaining', $remaining);
        }

        $errors = isset($envelope['errors']) && is_array($envelope['errors']) ? $envelope['errors'] : [];
        $this->setIfExists('PartialErrors', $errors === [] ? '' : json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function registerBooleanProfiles(): void
    {
        $yes = $this->Translate('Yes');
        $no = $this->Translate('No');

        // Gruen = erwarteter/guter Zustand, Orange = Aufmerksamkeit.
        // Rot bleibt bewusst echten Stoerungen/Fehlern vorbehalten.
        $this->ensureBooleanProfile('MySkoda.YesNo.GoodTrue', $no, 0xF59E0B, $yes, 0x22C55E);
        $this->ensureBooleanProfile('MySkoda.YesNo.GoodFalse', $no, 0x22C55E, $yes, 0xF59E0B);
        $this->ensureBooleanProfile('MySkoda.YesNo.ActiveTrue', $no, -1, $yes, 0x22C55E);
    }

    private function ensureBooleanProfile(string $name, string $falseText, int $falseColor, string $trueText, int $trueColor): void
    {
        if (!IPS_VariableProfileExists($name)) {
            IPS_CreateVariableProfile($name, 0);
        }

        $profile = IPS_GetVariableProfile($name);
        if ((int) ($profile['ProfileType'] ?? -1) !== 0) {
            $this->SendDebug('Variablenprofil', $name . ' hat nicht den Typ Boolean und wird nicht veraendert.', 0);
            return;
        }

        IPS_SetVariableProfileAssociation($name, 0, $falseText, '', $falseColor);
        IPS_SetVariableProfileAssociation($name, 1, $trueText, '', $trueColor);
    }

    private function applyBooleanProfile(string $ident, string $profile): void
    {
        if (!IPS_VariableProfileExists($profile)) {
            return;
        }
        $id = @$this->GetIDForIdent($ident);
        if ($id !== false) {
            IPS_SetVariableCustomProfile($id, $profile);
        }
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
        $this->RegisterVariableInteger('ChargeMode', $this->Translate('Charging mode'), $this->chargeModePresentation($map), 100);
        $this->MaintainAction('ChargeMode', $this->ReadPropertyBoolean('EnableRemote') && $map !== []);
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
