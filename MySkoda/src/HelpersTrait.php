<?php

declare(strict_types=1);

trait MySkodaHelpersTrait
{
    private function configurationValid(): bool
    {
        return $this->isVinValid(strtoupper(trim($this->ReadPropertyString('VIN'))))
            && trim($this->ReadPropertyString('APIToken')) !== '';
    }

    private function isVinValid(string $vin): bool
    {
        return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin) === 1;
    }

    private function path(mixed $data, string $path, mixed $default = null): mixed
    {
        $current = $data;
        foreach (explode('.', $path) as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }
        return $current;
    }

    private function pathHasValue(array $data, string $path): bool
    {
        $sentinel = new stdClass();
        $value = $this->path($data, $path, $sentinel);
        return $value !== $sentinel && $value !== null;
    }

    private function firstPath(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $sentinel = new stdClass();
            $value = $this->path($data, $path, $sentinel);
            if ($value !== $sentinel && $value !== null) {
                return $value;
            }
        }
        return null;
    }

    private function vehicleOperationAvailable(string $wanted): bool
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return false;
        }

        $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle']) ? $raw['vehicle'] : [];
        $operations = $this->path(
            $vehicle,
            'operations',
            $this->path($vehicle, 'remoteOperations', [])
        );
        if (!is_array($operations)) {
            return false;
        }

        foreach ($operations as $operation) {
            $name = is_array($operation)
                ? (string) ($operation['name'] ?? $operation['operationId'] ?? '')
                : (string) $operation;
            if ($name !== '' && strcasecmp($name, $wanted) === 0) {
                return true;
            }
        }
        return false;
    }

    private function toTimestamp(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $timestamp = strtotime((string) $value);
        return $timestamp === false ? 0 : $timestamp;
    }

    private function setIfExists(string $ident, mixed $value): void
    {
        $id = @$this->GetIDForIdent($ident);
        if ($id !== false && IPS_VariableExists($id)) {
            $this->SetValue($ident, $value);
        }
    }
}
