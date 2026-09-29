<?php

declare(strict_types=1);

/**
 * Builds vehicle variables from the API response itself.
 *
 * Known paths keep their established identifiers and presentations. Unknown
 * paths are exposed automatically with a stable API_* identifier and a neutral
 * presentation. Missing paths never delete variables or overwrite their last
 * value.
 */
trait MySkodaDynamicVariablesTrait
{
    private function syncVehicleVariablesFromRawData(): void
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return;
        }

        $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle'])
            ? $raw['vehicle']
            : [];
        if ($vehicle === []) {
            return;
        }

        $this->syncDynamicVehicleVariables($vehicle, $raw);
    }

    private function syncDynamicVehicleVariables(array $vehicle, array $envelope): void
    {
        $paths = json_decode($this->ReadAttributeString('DynamicVariablePaths'), true);
        $paths = is_array($paths) ? $paths : [];
        $pathsChanged = false;
        $position = 2000;

        $this->syncDynamicNode($vehicle, '', $position, $paths, $pathsChanged);
        $this->syncDerivedVehicleVariables($vehicle);
        $this->syncEnvelopeErrors($envelope);
        $this->syncRemoteOperationCache($vehicle);

        if ($pathsChanged) {
            $this->WriteAttributeString(
                'DynamicVariablePaths',
                json_encode($paths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }

        if ($this->path($vehicle, 'charging.settings.availableChargeModes', null) !== null
            || $this->path($vehicle, 'charging.settings.preferredChargeMode', null) !== null) {
            $this->updateAvailableChargeModes($vehicle);
        }

        $this->setIfExists('ApiKeyExpiresAtVar', $this->ReadAttributeInteger('ApiKeyExpiresAt'));
        $this->setIfExists('RequestsRemaining', $this->ReadAttributeInteger('RateLimitRemaining'));
        $this->applyActions();
        $this->initializeChargingHistory();
    }

    private function syncDynamicNode(
        mixed $value,
        string $path,
        int &$position,
        array &$paths,
        bool &$pathsChanged
    ): void {
        if ($value === null) {
            return;
        }

        if (is_array($value)) {
            if ($path !== '' && array_is_list($value)) {
                $this->syncDynamicValue($path, $value, $position, $paths, $pathsChanged);
                return;
            }

            foreach ($value as $key => $child) {
                $childPath = $path === '' ? (string) $key : $path . '.' . (string) $key;
                $this->syncDynamicNode($child, $childPath, $position, $paths, $pathsChanged);
            }
            return;
        }

        if ($path !== '') {
            $this->syncDynamicValue($path, $value, $position, $paths, $pathsChanged);
        }
    }

    private function syncDynamicValue(
        string $path,
        mixed $value,
        int &$position,
        array &$paths,
        bool &$pathsChanged
    ): void {
        $spec = $this->knownVehiclePathSpec($path);
        if ($spec !== null) {
            $definition = $this->dynamicKnownDefinition((string) $spec['ident']);
            if ($definition === null) {
                return;
            }

            $this->registerVariableOnce($definition);
            $this->setDynamicKnownValue($definition, $spec, $value);
            return;
        }

        $ident = $this->dynamicIdentForPath($path, $paths, $pathsChanged);
        $definition = $this->dynamicGenericDefinition($ident, $path, $value, $position);
        if ($definition === null) {
            return;
        }

        $position += 10;
        $this->registerVariableOnce($definition);
        $this->setIfExists($ident, $this->dynamicGenericValue($value));
    }

    private function knownVehiclePathSpec(string $path): ?array
    {
        return match ($path) {
            'name' => ['ident' => 'VehicleName'],
            'licensePlate' => ['ident' => 'LicensePlate'],
            'vin' => ['ident' => 'VIN'],
            'airConditioning.state' => ['ident' => 'ClimateState', 'transform' => 'upper'],
            'airConditioning.airConditioningAtUnlock' => ['ident' => 'AirConditioningAtUnlock'],
            'airConditioning.targetTemperature.value' => ['ident' => 'TargetTemperature'],
            'airConditioning.targetTemperature.unit' => ['ident' => 'TargetTemperatureUnit', 'transform' => 'upper'],
            'airConditioning.windowHeating.enabled' => ['ident' => 'WindowHeatingEnabled'],
            'airConditioning.windowHeating.front' => ['ident' => 'WindowHeatingFront', 'transform' => 'upper'],
            'airConditioning.windowHeating.rear' => ['ident' => 'WindowHeatingRear', 'transform' => 'upper'],
            'charging.isVehicleInSavedLocation' => ['ident' => 'AtSavedChargingLocation'],
            'charging.settings.autoUnlockPlugWhenCharged' => ['ident' => 'AutoUnlockPlug', 'transform' => 'upper'],
            'charging.settings.batteryCareModeTargetValueInPercent' => ['ident' => 'BatteryCareTargetSOC'],
            'charging.settings.chargingCareMode' => ['ident' => 'BatteryCareMode', 'transform' => 'upper'],
            'charging.settings.maxChargeCurrentAc' => ['ident' => 'MaxChargeCurrentAC', 'transform' => 'upper'],
            'charging.settings.availableChargeModes' => ['ident' => 'APIAvailableChargeModes', 'transform' => 'text'],
            'charging.settings.preferredChargeMode' => ['ident' => 'ChargeMode', 'transform' => 'chargeMode'],
            'charging.settings.targetStateOfChargeInPercent' => ['ident' => 'TargetSOC'],
            'charging.status.battery.remainingCruisingRangeInMeters' => ['ident' => 'Range', 'transform' => 'metersToKm'],
            'charging.status.battery.stateOfChargeInPercent' => ['ident' => 'StateOfCharge'],
            'charging.status.chargePowerInKw' => ['ident' => 'ChargePower', 'transform' => 'kwToW'],
            'charging.status.fullyChargedAt' => ['ident' => 'FullyChargedAt', 'transform' => 'timestamp'],
            'charging.status.plugConnectionState' => ['ident' => 'PlugConnectionState', 'transform' => 'upper'],
            'charging.status.plugLockState' => ['ident' => 'PlugLockState', 'transform' => 'upper'],
            'charging.status.remainingTimeToFullyChargedInMinutes' => ['ident' => 'RemainingChargingTime'],
            'charging.status.state' => ['ident' => 'ChargingState', 'transform' => 'upper'],
            'charging.status.chargeType' => ['ident' => 'ChargeType', 'transform' => 'upper'],
            'odometer.mileageInKm' => ['ident' => 'Mileage'],
            'parkingPosition.state' => ['ident' => 'ParkingState', 'transform' => 'upper'],
            'parkingPosition.formattedAddress' => ['ident' => 'ParkingAddress'],
            'parkingPosition.latitude',
            'parkingPosition.gpsCoordinates.latitude',
            'parkingPosition.gpsCoordinates.lat' => ['ident' => 'Latitude'],
            'parkingPosition.longitude',
            'parkingPosition.gpsCoordinates.longitude',
            'parkingPosition.gpsCoordinates.lon',
            'parkingPosition.gpsCoordinates.lng' => ['ident' => 'Longitude'],
            'status.overall.doorsLocked' => ['ident' => 'DoorsLocked', 'transform' => 'upper'],
            'status.overall.locked' => ['ident' => 'Locked', 'transform' => 'upper'],
            'status.overall.doors' => ['ident' => 'DoorsOpen', 'transform' => 'upper'],
            'status.overall.windows' => ['ident' => 'WindowsOpen', 'transform' => 'upper'],
            'status.overall.lights' => ['ident' => 'LightsOn', 'transform' => 'upper'],
            'status.overall.reliableLockStatus' => ['ident' => 'ReliableLockStatus', 'transform' => 'upper'],
            'status.detail.sunroof' => ['ident' => 'SunroofOpen', 'transform' => 'upper'],
            'status.detail.trunk' => ['ident' => 'TrunkOpen', 'transform' => 'upper'],
            'status.detail.bonnet' => ['ident' => 'BonnetOpen', 'transform' => 'upper'],
            'fuelStatus.carType' => ['ident' => 'APICarType', 'transform' => 'upper'],
            'fuelStatus.primaryEngineRange.engineType' => ['ident' => 'APIPrimaryEngineType', 'transform' => 'upper'],
            'fuelStatus.secondaryEngineRange.engineType' => ['ident' => 'APISecondaryEngineType', 'transform' => 'upper'],
            'operations',
            'remoteOperations' => ['ident' => 'APIRemoteOperations', 'transform' => 'text'],
            'auxiliaryHeating.state' => ['ident' => 'APIAuxiliaryHeatingState', 'transform' => 'upper'],
            'activeVentilation.state' => ['ident' => 'APIActiveVentilationState', 'transform' => 'upper'],
            default => null
        };
    }

    private function dynamicKnownDefinition(string $ident): ?array
    {
        $definitions = array_merge(
            $this->coreVariableDefinitions(),
            $this->detailVariableDefinitions(),
            $this->dynamicAdditionalKnownDefinitions()
        );

        foreach ($definitions as $definition) {
            if ((string) ($definition['ident'] ?? '') === $ident) {
                return $definition;
            }
        }

        return null;
    }

    private function dynamicAdditionalKnownDefinitions(): array
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
            $this->variable('AtSavedChargingLocation', 'At saved charging location', VARIABLETYPE_BOOLEAN, 200, $this->valuePresentation('house')),
            $this->variable('BatteryCareMode', 'Battery care mode', VARIABLETYPE_STRING, 240, $this->valuePresentation('shield')),
            $this->variable('BatteryCareTargetSOC', 'Battery care target', VARIABLETYPE_INTEGER, 230, $this->valuePresentation('battery-half', ' %', 0)),
            $this->variable('MaxChargeCurrentAC', 'Maximum AC charging current', VARIABLETYPE_STRING, 250, $this->valuePresentation('bolt')),
            $this->variable('AutoUnlockPlug', 'Automatic plug unlock', VARIABLETYPE_STRING, 210, $this->valuePresentation('plug')),
            $this->variable('TargetTemperatureUnit', 'Target temperature unit', VARIABLETYPE_STRING, 130, $this->valuePresentation('temperature-half')),
            $this->variable('AirConditioningAtUnlock', 'Air conditioning at unlock', VARIABLETYPE_BOOLEAN, 110, $this->valuePresentation('key')),
            $this->variable('WindowHeatingEnabled', 'Window heating enabled', VARIABLETYPE_BOOLEAN, 140, $this->valuePresentation('window-maximize')),
            $this->variable('WindowHeatingFront', 'Front window heating', VARIABLETYPE_STRING, 150, $this->onOffStatePresentation('window-maximize')),
            $this->variable('WindowHeatingRear', 'Rear window heating', VARIABLETYPE_STRING, 160, $this->onOffStatePresentation('car-rear')),
            $this->variable('APICarType', 'API vehicle type', VARIABLETYPE_STRING, 1300, $this->valuePresentation()),
            $this->variable('APIPrimaryEngineType', 'API primary engine type', VARIABLETYPE_STRING, 1310, $this->valuePresentation()),
            $this->variable('APISecondaryEngineType', 'API secondary engine type', VARIABLETYPE_STRING, 1320, $this->valuePresentation()),
            $this->variable('APIAvailableChargeModes', 'API available charging modes', VARIABLETYPE_STRING, 1340, $this->valuePresentation()),
            $this->variable('APIRemoteOperations', 'API remote operations', VARIABLETYPE_STRING, 1350, [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'MULTILINE' => true
            ]),
            $this->variable('APIAuxiliaryHeatingState', 'API auxiliary heating state', VARIABLETYPE_STRING, 1360, $this->valuePresentation('fire')),
            $this->variable('APIActiveVentilationState', 'API active ventilation state', VARIABLETYPE_STRING, 1370, $this->valuePresentation('fan'))
        ];
    }

    private function setDynamicKnownValue(array $definition, array $spec, mixed $value): void
    {
        $ident = (string) $definition['ident'];
        $transform = (string) ($spec['transform'] ?? '');

        if ($ident === 'TargetTemperature' && $this->ReadAttributeBoolean('TargetTemperatureOverride')) {
            $climateId = @$this->GetIDForIdent('Climate');
            if ($climateId === false || !(bool) $this->GetValue('Climate')) {
                return;
            }
            $this->WriteAttributeBoolean('TargetTemperatureOverride', false);
        }

        $converted = match ($transform) {
            'upper' => strtoupper(trim((string) $value)),
            'metersToKm' => (int) round((float) $value / 1000),
            'kwToW' => (float) $value * 1000.0,
            'timestamp' => $this->toTimestamp($value),
            'text' => $this->publicApiValueAsString($value),
            'chargeMode' => $this->dynamicChargeModeValue($value),
            default => $this->dynamicKnownValueByType((int) $definition['type'], $value)
        };

        if ($converted === null) {
            return;
        }

        $this->setIfExists($ident, $converted);
    }

    private function dynamicKnownValueByType(int $type, mixed $value): bool|int|float|string|null
    {
        return match ($type) {
            VARIABLETYPE_BOOLEAN => (bool) $value,
            VARIABLETYPE_INTEGER => is_numeric($value) ? (int) round((float) $value) : null,
            VARIABLETYPE_FLOAT => is_numeric($value) ? (float) $value : null,
            VARIABLETYPE_STRING => is_array($value)
                ? $this->publicApiValueAsString($value)
                : (string) $value,
            default => null
        };
    }

    private function dynamicChargeModeValue(mixed $value): ?int
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $mode = strtoupper(trim($value));
        $index = array_search($mode, self::CHARGE_MODES, true);
        if ($index === false) {
            $this->SendDebug('Charge mode', 'Unknown API value: ' . $mode, 0);
            return null;
        }

        return (int) $index;
    }

    private function syncDerivedVehicleVariables(array $vehicle): void
    {
        $climateState = $this->path($vehicle, 'airConditioning.state', null);
        if (is_string($climateState) && trim($climateState) !== '') {
            $definition = $this->dynamicKnownDefinition('Climate');
            if ($definition !== null) {
                $this->registerVariableOnce($definition);
                $state = strtoupper(trim($climateState));
                if (in_array($state, ['ON', 'COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true)) {
                    $this->setIfExists('Climate', true);
                } elseif ($state === 'OFF') {
                    $this->setIfExists('Climate', false);
                }
            }
        }

        $chargeState = $this->path($vehicle, 'charging.status.state', null);
        if (is_string($chargeState) && trim($chargeState) !== '') {
            $definition = $this->dynamicKnownDefinition('Charging');
            if ($definition !== null) {
                $this->registerVariableOnce($definition);
                $state = strtoupper(trim($chargeState));
                if (in_array($state, ['CHARGING', 'CONSERVING'], true)) {
                    $this->setIfExists('Charging', true);
                } elseif (in_array($state, ['READY_FOR_CHARGING', 'CONNECT_CABLE', 'CHARGING_INTERRUPTED', 'ERROR'], true)) {
                    $this->setIfExists('Charging', false);
                }
            }
        }
    }

    private function syncEnvelopeErrors(array $envelope): void
    {
        if (!array_key_exists('errors', $envelope) || !is_array($envelope['errors'])) {
            return;
        }

        $definition = $this->dynamicKnownDefinition('PartialErrors');
        if ($definition === null) {
            return;
        }

        $this->registerVariableOnce($definition);
        $this->setIfExists(
            'PartialErrors',
            $envelope['errors'] === []
                ? ''
                : (string) json_encode($envelope['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function syncRemoteOperationCache(array $vehicle): void
    {
        $sentinel = new stdClass();
        $operations = $this->path($vehicle, 'operations', $sentinel);
        if ($operations === $sentinel) {
            $operations = $this->path($vehicle, 'remoteOperations', $sentinel);
        }
        if ($operations === $sentinel || !is_array($operations)) {
            return;
        }

        $names = [];
        foreach ($operations as $operation) {
            if (is_string($operation) && trim($operation) !== '') {
                $names[] = trim($operation);
                continue;
            }
            if (is_array($operation) && is_string($operation['name'] ?? null) && trim($operation['name']) !== '') {
                $names[] = trim($operation['name']);
            }
        }

        $this->WriteAttributeString(
            'AvailableRemoteOperations',
            json_encode(array_values(array_unique($names)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function dynamicGenericDefinition(
        string $ident,
        string $path,
        mixed $value,
        int $position
    ): ?array {
        $type = match (true) {
            is_bool($value) => VARIABLETYPE_BOOLEAN,
            is_int($value) => VARIABLETYPE_INTEGER,
            is_float($value) => VARIABLETYPE_FLOAT,
            is_string($value), is_array($value) => VARIABLETYPE_STRING,
            default => null
        };
        if ($type === null) {
            return null;
        }

        $presentation = ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION];
        if (is_array($value)) {
            $presentation['MULTILINE'] = true;
        }

        return $this->variable($ident, $path, $type, $position, $presentation);
    }

    private function dynamicGenericValue(mixed $value): bool|int|float|string
    {
        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($encoded) ? $encoded : '';
        }

        return $value;
    }

    private function dynamicIdentForPath(string $path, array &$paths, bool &$pathsChanged): string
    {
        $segments = preg_split('/[^A-Za-z0-9]+/', $path) ?: [];
        $segments = array_values(array_filter($segments, static fn (string $segment): bool => $segment !== ''));
        $base = 'API_' . implode('_', array_map(static fn (string $segment): string => ucfirst($segment), $segments));
        if ($base === 'API_') {
            $base = 'API_Value';
        }

        if (strlen($base) > 64) {
            $base = substr($base, 0, 55) . '_' . substr(sha1($path), 0, 8);
        }

        $ident = $base;
        if (isset($paths[$ident]) && $paths[$ident] !== $path) {
            $suffix = '_' . substr(sha1($path), 0, 8);
            $ident = substr($base, 0, max(1, 64 - strlen($suffix))) . $suffix;
        }

        if (($paths[$ident] ?? null) !== $path) {
            $paths[$ident] = $path;
            $pathsChanged = true;
        }

        return $ident;
    }
}
