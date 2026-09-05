<?php

declare(strict_types=1);

trait MySkodaHelpersTrait
{
    private function safeValue(string $ident): mixed
    {
        if (@$this->GetIDForIdent($ident) === false) {
            return null;
        }
        return $this->GetValue($ident);
    }

    private function firstValue(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    private function metersToKm(mixed $meters): ?int
    {
        if (!is_numeric($meters)) {
            return null;
        }
        return (int) round(((float) $meters) / 1000.0);
    }

    private function kwToW(mixed $kw): ?float
    {
        if (!is_numeric($kw)) {
            return null;
        }
        return ((float) $kw) * 1000.0;
    }

    private function coerceBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            return ((int) $value) !== 0;
        }
        if (is_string($value)) {
            $value = strtoupper(trim($value));
            if (in_array($value, ['1', 'TRUE', 'YES', 'ON', 'OPEN', 'LOCKED', 'CHARGING'], true)) {
                return true;
            }
            if (in_array($value, ['0', 'FALSE', 'NO', 'OFF', 'CLOSED', 'UNLOCKED'], true)) {
                return false;
            }
        }
        return null;
    }

    private function configurationValid(): bool
    {
        return $this->isVinValid(strtoupper(trim($this->ReadPropertyString('VIN')))) && trim($this->ReadPropertyString('APIToken')) !== '';
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
        if (@$this->GetIDForIdent($ident) !== false) {
            $this->UnregisterVariable($ident);
        }
    }

}
