<?php

declare(strict_types=1);

trait MySkodaCommandTrait
{
    private const COMMAND_CONFIRM_DELAY_MS = 60000;
    private const COMMAND_MIN_TIMEOUT_SECONDS = 600;

    public function Create(): void
    {
        $this->coreCreate();

        $this->RegisterAttributeString('PendingCommands', '{}');
        $this->RegisterAttributeString('LastCommandResult', '');
        $this->RegisterAttributeString('CommandStatusText', '');
        $this->RegisterAttributeString('CommandConfigFingerprint', '');
        $this->RegisterTimer('CommandConfirmTimer', 0, 'MSKODA_ConfirmPending($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        $fingerprint = hash(
            'sha256',
            strtoupper(trim($this->ReadPropertyString('VIN'))) . '|' . trim($this->ReadPropertyString('APIToken'))
        );
        $previousFingerprint = $this->ReadAttributeString('CommandConfigFingerprint');

        if ($previousFingerprint !== '' && $previousFingerprint !== $fingerprint) {
            $this->clearPendingCommands();
            $this->WriteAttributeString('CommandStatusText', '');
        }
        $this->WriteAttributeString('CommandConfigFingerprint', $fingerprint);

        $this->coreApplyChanges();
        $this->updateCommandStatusVariables();
    }

    private function registerVariables(): void
    {
        $this->baseRegisterVariables();

        if (!$this->ReadPropertyBoolean('ShowDetails')) {
            return;
        }

        $this->registerVariableOnce([
            'ident' => 'PendingCommands',
            'name' => 'Pending commands',
            'type' => VARIABLETYPE_INTEGER,
            'position' => 950,
            'presentation' => [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'ICON' => 'hourglass-half'
            ],
            'initialValue' => 0
        ]);

        $this->registerVariableOnce([
            'ident' => 'CommandStatus',
            'name' => 'Command status',
            'type' => VARIABLETYPE_STRING,
            'position' => 960,
            'presentation' => [
                'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
                'ICON' => 'satellite-dish',
                'MULTILINE' => true
            ],
            'initialValue' => $this->Translate('Ready')
        ]);

        $this->updateCommandStatusVariables();
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            throw new RuntimeException(
                $this->Translate('Remote control is disabled in this instance.')
            );
        }

        if (!$this->canRequest(true)) {
            $this->SetStatus(203);
            throw new RuntimeException(
                $this->Translate('MySkoda rate limit / waiting period is active.')
            );
        }

        switch ($Ident) {
            case 'Charging':
                $desired = (bool) $Value;
                $this->executeOptimisticCommand(
                    'Charging',
                    $desired,
                    'Charging',
                    fn (): bool => $this->sendSimpleCommand($desired ? 'charging/start' : 'charging/stop')
                );
                return;

            case 'TargetSOC':
                $percent = max(50, min(100, (int) $Value));
                $this->executeOptimisticCommand(
                    'TargetSOC',
                    $percent,
                    'Charging limit',
                    fn (): bool => $this->sendDiscoveredScalarCommand('limit', $percent)
                );
                return;

            case 'ChargeMode':
                $mode = self::CHARGE_MODES[(int) $Value] ?? null;
                if (!is_string($mode) || !$this->isChargeModeAvailable($mode)) {
                    throw new RuntimeException(
                        $this->Translate('The selected charging mode is not supported by this vehicle.')
                    );
                }

                $this->executeOptimisticCommand(
                    'ChargeMode',
                    (int) $Value,
                    'Charging mode',
                    fn (): bool => $this->sendDiscoveredScalarCommand('mode', $mode)
                );
                return;

            case 'Climate':
                $desired = (bool) $Value;
                $this->executeOptimisticCommand(
                    'Climate',
                    $desired,
                    'Air conditioning',
                    fn (): bool => $desired
                        ? $this->startClimateInternal((float) $this->GetValue('TargetTemperature'))
                        : $this->sendSimpleCommand('air-conditioning/stop')
                );
                return;

            case 'TargetTemperature':
                $temperature = max(16.0, min(30.0, (float) $Value));
                if (!(bool) $this->GetValue('Climate')) {
                    $this->SetValue('TargetTemperature', $temperature);
                    return;
                }

                $this->executeOptimisticCommand(
                    'TargetTemperature',
                    $temperature,
                    'Target temperature',
                    fn (): bool => $this->startClimateInternal($temperature)
                );
                return;
        }

        throw new InvalidArgumentException('Unknown action: ' . $Ident);
    }

    public function SetChargingLimit(int $Percent): bool
    {
        $Percent = max(50, min(100, $Percent));
        return $this->executeOptimisticCommand(
            'TargetSOC',
            $Percent,
            'Charging limit',
            fn (): bool => $this->sendDiscoveredScalarCommand('limit', $Percent)
        );
    }

    public function SetChargeMode(string $Mode): bool
    {
        $Mode = strtoupper(trim($Mode));
        $index = array_search($Mode, self::CHARGE_MODES, true);
        if ($Mode === '' || $index === false || !$this->isChargeModeAvailable($Mode)) {
            $this->WriteAttributeString(
                'LastError',
                $this->Translate('The selected charging mode is not supported by this vehicle.')
            );
            return false;
        }

        return $this->executeOptimisticCommand(
            'ChargeMode',
            (int) $index,
            'Charging mode',
            fn (): bool => $this->sendDiscoveredScalarCommand('mode', $Mode)
        );
    }

    public function ConfirmPending(): void
    {
        $this->SetTimerInterval('CommandConfirmTimer', 0);

        if ($this->readPendingCommands() === [] || !$this->configurationValid()) {
            $this->updateCommandStatusVariables();
            return;
        }

        if (!$this->canRequest(false)) {
            $this->updateCommandStatusVariables();
            return;
        }

        $this->fetchVehicle(false);
    }

    private function executeOptimisticCommand(
        string $ident,
        mixed $desiredValue,
        string $label,
        Closure $command
    ): bool {
        $id = @$this->GetIDForIdent($ident);
        if ($id === false || !IPS_VariableExists($id)) {
            throw new RuntimeException('Variable not found: ' . $ident);
        }

        $previousValue = GetValue((int) $id);
        $pending = $this->readPendingCommands();
        $previousPending = $pending[$ident] ?? null;

        $pending[$ident] = [
            'expected' => $desiredValue,
            'previous' => $previousValue,
            'createdAt' => time(),
            'state' => 'sending',
            'label' => $label
        ];
        $this->writePendingCommands($pending);
        $this->SetValue($ident, $desiredValue);
        $this->WriteAttributeString('LastCommandResult', 'sending');
        $this->updateCommandStatusVariables();

        $ok = $command();
        $result = $this->ReadAttributeString('LastCommandResult');
        $pending = $this->readPendingCommands();

        if ($ok) {
            if (isset($pending[$ident])) {
                $pending[$ident]['state'] = 'waiting';
                $this->writePendingCommands($pending);
            }
            $this->scheduleCommandConfirmation();
            $this->updateCommandStatusVariables();
            return true;
        }

        if ($result === 'uncertain') {
            if (isset($pending[$ident])) {
                $pending[$ident]['state'] = 'uncertain';
                $this->writePendingCommands($pending);
            }
            $this->scheduleCommandConfirmation();
            $this->updateCommandStatusVariables();
            return false;
        }

        $this->SetValue($ident, $previousValue);
        if (is_array($previousPending)) {
            $pending[$ident] = $previousPending;
        } else {
            unset($pending[$ident]);
        }
        $this->writePendingCommands($pending);
        $this->WriteAttributeString(
            'CommandStatusText',
            sprintf($this->Translate('Command rejected: %s'), $this->Translate($label))
        );
        $this->updateCommandStatusVariables();
        return false;
    }

    private function sendCommand(string $method, string $path, ?array $body): bool
    {
        $this->WriteAttributeString('LastCommandResult', 'sending');

        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            $this->WriteAttributeString(
                'LastError',
                $this->Translate('Remote control is disabled in this instance.')
            );
            $this->WriteAttributeString('LastCommandResult', 'rejected');
            return false;
        }

        if (!$this->canRequest(true)) {
            $this->WriteAttributeString(
                'LastError',
                $this->Translate('MySkoda rate limit / waiting period is active.')
            );
            $this->WriteAttributeString('LastCommandResult', 'rejected');
            $this->SetStatus(203);
            return false;
        }

        $response = $this->request($method, $path, $body);
        $this->absorbHeaders($response['headers']);

        if (!$response['ok']) {
            $status = (int) ($response['status'] ?? 0);
            $curlError = (string) ($response['curlError'] ?? '');
            $uncertain = $curlError !== '' || $status === 0 || $status === 408 || $status >= 500;
            $this->WriteAttributeString('LastCommandResult', $uncertain ? 'uncertain' : 'rejected');
            $this->setApiError($response);
            return false;
        }

        $this->WriteAttributeString('LastCommandResult', 'accepted');
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(102);
        return true;
    }

    private function updateCoreValues(array $vehicle): void
    {
        $this->setPathValue('StateOfCharge', $vehicle, 'charging.status.battery.stateOfChargeInPercent', static fn (mixed $v): int => (int) $v);
        $this->setPathValue('Range', $vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', static fn (mixed $v): int => (int) round((float) $v / 1000));

        $mileage = (int) round((float) $this->path($vehicle, 'odometer.mileageInKm', 0));
        if ($mileage > 0) {
            $this->SetValue('Mileage', $mileage);
        }

        $lock = strtoupper((string) $this->path($vehicle, 'status.overall.doorsLocked', $this->path($vehicle, 'status.overall.locked', 'UNKNOWN')));
        $this->SetValue('Locked', in_array($lock, ['YES', 'LOCKED'], true));
        $this->SetValue('DoorsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.doors', 'CLOSED')) === 'OPEN');
        $this->SetValue('WindowsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.windows', 'CLOSED')) === 'OPEN');

        $chargeState = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $this->applyApiValue('Charging', in_array($chargeState, ['CHARGING', 'CONSERVING'], true));
        $this->setPathValue('ChargePower', $vehicle, 'charging.status.chargePowerInKw', static fn (mixed $v): float => (float) $v * 1000.0);

        $targetSoc = $this->path($vehicle, 'charging.settings.targetStateOfChargeInPercent', null);
        if ($targetSoc !== null) {
            $this->applyApiValue('TargetSOC', (int) $targetSoc);
        }

        $this->updateAvailableChargeModes($vehicle);
        $mode = strtoupper((string) $this->path($vehicle, 'charging.settings.preferredChargeMode', ''));
        if ($mode !== '') {
            $index = array_search($mode, self::CHARGE_MODES, true);
            if ($index !== false) {
                $this->applyApiValue('ChargeMode', (int) $index);
            } else {
                $this->SendDebug('Charge mode', 'Unknown API value: ' . $mode, 0);
            }
        }

        $climateState = strtoupper((string) $this->path($vehicle, 'airConditioning.state', 'OFF'));
        $this->applyApiValue(
            'Climate',
            in_array($climateState, ['COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true)
        );

        $targetTemperature = $this->path($vehicle, 'airConditioning.targetTemperature.value', null);
        if ($targetTemperature !== null) {
            $this->applyApiValue('TargetTemperature', (float) $targetTemperature);
        }

        $this->updateCommandStatusVariables();
    }

    private function applyApiValue(string $ident, mixed $apiValue): void
    {
        $pending = $this->readPendingCommands();
        if (!isset($pending[$ident]) || !is_array($pending[$ident])) {
            $this->SetValue($ident, $apiValue);
            return;
        }

        $entry = $pending[$ident];
        $expected = $entry['expected'] ?? null;
        $label = (string) ($entry['label'] ?? $ident);

        if ($this->commandValuesEqual($expected, $apiValue)) {
            $this->SetValue($ident, $apiValue);
            unset($pending[$ident]);
            $this->writePendingCommands($pending);
            $this->WriteAttributeString(
                'CommandStatusText',
                sprintf($this->Translate('Confirmed: %s'), $this->Translate($label))
            );
            return;
        }

        $createdAt = (int) ($entry['createdAt'] ?? 0);
        if ($createdAt > 0 && (time() - $createdAt) < $this->commandPendingTimeoutSeconds()) {
            $this->SendDebug(
                'Pending command',
                $ident . ': API still reports old value; optimistic value is kept.',
                0
            );
            return;
        }

        $this->SetValue($ident, $apiValue);
        unset($pending[$ident]);
        $this->writePendingCommands($pending);
        $this->WriteAttributeString(
            'CommandStatusText',
            sprintf($this->Translate('Confirmation timed out: %s'), $this->Translate($label))
        );
    }

    private function commandValuesEqual(mixed $expected, mixed $actual): bool
    {
        if ((is_float($expected) || is_float($actual)) && is_numeric($expected) && is_numeric($actual)) {
            return abs((float) $expected - (float) $actual) < 0.05;
        }
        return $expected === $actual;
    }

    private function commandPendingTimeoutSeconds(): int
    {
        return max(
            self::COMMAND_MIN_TIMEOUT_SECONDS,
            max(180, $this->ReadPropertyInteger('Interval')) * 2
        );
    }

    private function scheduleCommandConfirmation(): void
    {
        $this->SetTimerInterval('CommandConfirmTimer', self::COMMAND_CONFIRM_DELAY_MS);
    }

    private function clearPendingCommands(): void
    {
        $this->WriteAttributeString('PendingCommands', '{}');
        $this->SetTimerInterval('CommandConfirmTimer', 0);
    }

    private function readPendingCommands(): array
    {
        $decoded = json_decode($this->ReadAttributeString('PendingCommands'), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writePendingCommands(array $pending): void
    {
        $this->WriteAttributeString(
            'PendingCommands',
            json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function updateCommandStatusVariables(): void
    {
        $pending = $this->readPendingCommands();
        $this->setIfExists('PendingCommands', count($pending));

        if ($pending === []) {
            $text = trim($this->ReadAttributeString('CommandStatusText'));
            $this->setIfExists('CommandStatus', $text !== '' ? $text : $this->Translate('Ready'));
            return;
        }

        $waiting = [];
        $uncertain = [];
        foreach ($pending as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $label = $this->Translate((string) ($entry['label'] ?? 'Command'));
            if (($entry['state'] ?? '') === 'uncertain') {
                $uncertain[] = $label;
            } else {
                $waiting[] = $label;
            }
        }

        $parts = [];
        if ($waiting !== []) {
            $parts[] = sprintf(
                $this->Translate('Waiting for confirmation: %s'),
                implode(', ', $waiting)
            );
        }
        if ($uncertain !== []) {
            $parts[] = sprintf(
                $this->Translate('Transmission uncertain: %s'),
                implode(', ', $uncertain)
            );
        }

        $this->setIfExists(
            'CommandStatus',
            $parts !== [] ? implode(' | ', $parts) : $this->Translate('Ready')
        );
    }
}
