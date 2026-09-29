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
        $this->syncDynamicNode($vehicle, '', $paths, $pathsChanged);
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
        array &$paths,
        bool &$pathsChanged
    ): void {
        if ($value === null) {
            return;
        }

        if (is_array($value)) {
            if ($path !== '' && array_is_list($value)) {
                $this->syncDynamicValue($path, $value, $paths, $pathsChanged);
                return;
            }

            foreach ($value as $key => $child) {
                $childPath = $path === '' ? (string) $key : $path . '.' . (string) $key;
                $this->syncDynamicNode($child, $childPath, $paths, $pathsChanged);
            }
            return;
        }

        if (is_string($value) && trim($value) === '') {
            return;
        }

        if ($path !== '') {
            $this->syncDynamicValue($path, $value, $paths, $pathsChanged);
        }
    }

    private function syncDynamicValue(
        string $path,
        mixed $value,
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
        $keys = array_keys($paths);
        $index = array_search($ident, $keys, true);
        $position = 2000 + (($index === false ? count($keys) : (int) $index) * 10);
        $definition = $this->dynamicGenericDefinition($ident, $path, $value, $position);
        if ($definition === null) {
            return;
        }
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
            'fuelStatus.primaryEngineRange.currentFuelLevelInPercent' => ['ident' => 'FuelLevel'],
            'fuelStatus.primaryEngineRange.remainingRangeInKm' => ['ident' => 'PrimaryEngineRange'],
            'fuelStatus.totalRangeInKm' => ['ident' => 'TotalRange'],
            'auxiliaryHeating.durationInSeconds' => ['ident' => 'AuxiliaryHeatingDuration'],
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
            $this->publicApiVariableDefinitions()
        );

        foreach ($definitions as $definition) {
            if ((string) ($definition['ident'] ?? '') === $ident) {
                return $definition;
            }
        }

        return null;
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
