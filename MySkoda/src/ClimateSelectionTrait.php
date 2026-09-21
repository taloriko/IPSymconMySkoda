<?php

declare(strict_types=1);

trait MySkodaClimateSelectionTrait
{
    public function Create(): void
    {
        $this->commandCreate();
        $this->RegisterAttributeBoolean('TargetTemperatureOverride', false);
    }

    public function ApplyChanges(): void
    {
        $previousFingerprint = $this->ReadAttributeString('CommandConfigFingerprint');
        $currentFingerprint = hash(
            'sha256',
            strtoupper(trim($this->ReadPropertyString('VIN'))) . '|' . trim($this->ReadPropertyString('APIToken'))
        );

        $this->commandApplyChanges();

        if ($previousFingerprint !== '' && $previousFingerprint !== $currentFingerprint) {
            $this->WriteAttributeBoolean('TargetTemperatureOverride', false);
        }
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        $climateWasActive = false;
        if ($Ident === 'TargetTemperature') {
            $climateWasActive = (bool) $this->GetValue('Climate');
        }

        $this->commandRequestAction($Ident, $Value);

        if ($Ident === 'TargetTemperature' && !$climateWasActive) {
            // The Public API cannot set a target temperature while climate is off.
            // Keep the user's local selection until it is sent with the next start.
            $this->WriteAttributeBoolean('TargetTemperatureOverride', true);
            return;
        }

        $commandAccepted = $this->ReadAttributeString('LastCommandResult') === 'accepted';
        if (!$commandAccepted) {
            return;
        }

        if (($Ident === 'Climate' && (bool) $Value)
            || ($Ident === 'TargetTemperature' && $climateWasActive)) {
            $this->WriteAttributeBoolean('TargetTemperatureOverride', false);
        }
    }

    private function setPathValue(string $ident, array $source, string $path, Closure $convert): void
    {
        if ($ident === 'TargetTemperature' && $this->ReadAttributeBoolean('TargetTemperatureOverride')) {
            // updateCoreValues() writes the API climate state before the target
            // temperature. An external climate start restores API authority.
            if ((bool) $this->GetValue('Climate')) {
                $this->WriteAttributeBoolean('TargetTemperatureOverride', false);
            } else {
                return;
            }
        }

        $this->baseSetPathValue($ident, $source, $path, $convert);
    }
}
