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
        if (@$this->GetIDForIdent($ident) !== false) {
            $this->SetValue($ident, $value);
        }
    }

    private function dropVariable(string $ident): void
    {
        $id = @$this->GetIDForIdent($ident);
        if ($id === false || !IPS_VariableExists($id)) {
            return;
        }

        // UnregisterVariable operates on direct module children. Preserve the
        // existing variable ID until the actual unregister operation occurs.
        if (IPS_GetParent($id) !== $this->InstanceID) {
            IPS_SetParent($id, $this->InstanceID);
        }
        $this->UnregisterVariable($ident);
    }
}
