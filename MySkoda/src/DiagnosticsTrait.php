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
            'vehicle.name',
            'vehicle.licensePlate',
            'vehicle.renderUrl',
            'vehicle.odometer.mileageInKm',
            'vehicle.status.overall.doorsLocked',
            'vehicle.status.overall.locked',
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
            'vehicle.charging.status.battery.stateOfChargeInPercent',
            'vehicle.charging.status.battery.remainingCruisingRangeInMeters',
            'vehicle.charging.settings.targetStateOfChargeInPercent',
            'vehicle.charging.settings.availableChargeModes[]',
            'vehicle.charging.settings.preferredChargeMode',
            'vehicle.airConditioning.state',
            'vehicle.airConditioning.targetTemperature.value',
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
