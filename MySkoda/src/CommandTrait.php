<?php

declare(strict_types=1);

trait MySkodaCommandTrait
{
    public function Create(): void
    {
        $this->coreCreate();

        $this->RegisterAttributeString('PendingCommands', '{}');
        $this->RegisterAttributeString('LastCommandResult', '');
        $this->RegisterAttributeString('CommandStatusText', '');
        $this->RegisterAttributeString('CommandConfigFingerprint', '');
    }

    public function ApplyChanges(): void
    {
        $fingerprint = hash(
            'sha256',
            strtoupper(trim($this->ReadPropertyString('VIN'))) . '|' . trim($this->ReadPropertyString('APIToken'))
        );
        $previousFingerprint = $this->ReadAttributeString('CommandConfigFingerprint');

        if ($previousFingerprint !== '' && $previousFingerprint !== $fingerprint) {
            $this->WriteAttributeString('CommandStatusText', '');
        }
        $this->WriteAttributeString('CommandConfigFingerprint', $fingerprint);
        $this->clearPendingCommands();

        $this->coreApplyChanges();
        $this->updatePublicApiValuesFromRawData();
        $this->updateCommandStatusVariables();
    }

    private function registerVariables(): void
    {
        $this->baseRegisterVariables();
        $this->ensurePublicApiVariables();

        if (!$this->ReadPropertyBoolean('ShowDetails')) {
            return;
        }

        $this->registerVariableOnce([
            'ident' => 'PendingCommands',
            'name' => 'Pending commands',
            'type' => VARIABLETYPE_INTEGER,
            'position' => 980,
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
            'position' => 990,
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
            $message = $this->Translate('Remote control is disabled in this instance.');
            $this->WriteAttributeString('CommandStatusText', $message);
            $this->updateCommandStatusVariables();
            throw new RuntimeException($message);
        }

        if (!$this->canRequest(true)) {
            $message = $this->Translate('MySkoda rate limit / waiting period is active.');
            $this->WriteAttributeString('CommandStatusText', $message);
            $this->updateCommandStatusVariables();
            $this->SetStatus(203);
            throw new RuntimeException($message);
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
                    $message = $this->Translate('The selected charging mode is not supported by this vehicle.');
                    $this->WriteAttributeString('CommandStatusText', $message);
                    $this->updateCommandStatusVariables();
                    throw new RuntimeException($message);
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
            $message = $this->Translate('The selected charging mode is not supported by this vehicle.');
            $this->WriteAttributeString('LastError', $message);
            $this->WriteAttributeString('CommandStatusText', $message);
            $this->updateCommandStatusVariables();
            return false;
        }

        return $this->executeOptimisticCommand(
            'ChargeMode',
            (int) $index,
            'Charging mode',
            fn (): bool => $this->sendDiscoveredScalarCommand('mode', $Mode)
        );
    }

    public function UpdateChargingProfile(int $ProfileID, string $ProfileJSON): bool
    {
        $profile = json_decode($ProfileJSON, true);
        if (!is_array($profile)) {
            return $this->rejectDirectCommand(
                'Charging profile',
                $this->Translate('Invalid charging profile JSON')
            );
        }

        $operation = $this->findOperation($this->refreshOpenApi(false), 'profile');
        if ($operation === null) {
            return $this->rejectDirectCommand(
                'Charging profile',
                $this->Translate('Charging profile operation was not found in the OpenAPI definition.')
            );
        }

        $path = $this->replacePathParameters((string) $operation['path'], $ProfileID);
        $body = $this->buildProfilePayload($operation, $profile);

        return $this->executeDirectCommand(
            'UpdateChargingProfile',
            'Charging profile',
            fn (): bool => $this->sendCommand((string) $operation['method'], $path, $body)
        );
    }

    public function StartAuxiliaryHeating(
        float $TargetTemperature = 22.0,
        int $DurationMinutes = 30,
        string $Mode = 'HEATING'
    ): bool {
        $spin = trim($this->ReadPropertyString('SPIN'));
        if ($spin === '') {
            return $this->rejectDirectCommand(
                'Auxiliary heating',
                $this->Translate('S-PIN is missing')
            );
        }

        $body = [
            'spin' => $spin,
            'targetTemperature' => [
                'value' => max(16.0, min(30.0, $TargetTemperature)),
                'unit' => $this->commandTemperatureUnit()
            ],
            'durationInSeconds' => max(60, $DurationMinutes * 60),
            'startMode' => strtoupper($Mode) === 'VENTILATION' ? 'VENTILATION' : 'HEATING'
        ];

        return $this->executeDirectCommand(
            'StartAuxiliaryHeating',
            'Auxiliary heating',
            fn (): bool => $this->sendCommand(
                'POST',
                '/api/v1/vehicles/'
                    . rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN'))))
                    . '/auxiliary-heating/start',
                $body
            )
        );
    }

    public function StopAuxiliaryHeating(): bool
    {
        return $this->executeDirectCommand(
            'StopAuxiliaryHeating',
            'Auxiliary heating',
            fn (): bool => $this->sendSimpleCommand('auxiliary-heating/stop')
        );
    }

    public function StartVentilation(): bool
    {
        return $this->executeDirectCommand(
            'StartVentilation',
            'Ventilation',
            fn (): bool => $this->sendSimpleCommand('active-ventilation/start')
        );
    }

    public function StopVentilation(): bool
    {
        return $this->executeDirectCommand(
            'StopVentilation',
            'Ventilation',
            fn (): bool => $this->sendSimpleCommand('active-ventilation/stop')
        );
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
        $pending[$ident] = [
            'expected' => $desiredValue,
            'previous' => $previousValue,
            'state' => 'sending',
            'label' => $label
        ];

        $this->writePendingCommands($pending);
        $this->SetValue($ident, $desiredValue);
        $this->WriteAttributeString('LastCommandResult', 'sending');
        $this->WriteAttributeString('CommandStatusText', '');
        $this->updateCommandStatusVariables();

        try {
            $ok = $command();
        } catch (Throwable $throwable) {
            $ok = false;
            $this->WriteAttributeString('LastError', $throwable->getMessage());
        }

        $pending = $this->readPendingCommands();
        unset($pending[$ident]);
        $this->writePendingCommands($pending);

        if ($ok) {
            $this->SetValue($ident, $desiredValue);
            $this->WriteAttributeString('LastCommandResult', 'accepted');
            $this->WriteAttributeString(
                'CommandStatusText',
                sprintf($this->Translate('Confirmed: %s'), $this->Translate($label))
            );
            $this->updateCommandStatusVariables();
            return true;
        }

        $this->SetValue($ident, $previousValue);
        $this->WriteAttributeString('LastCommandResult', 'rejected');

        $error = trim($this->ReadAttributeString('LastError'));
        $status = sprintf(
            $this->Translate('Command rejected: %s'),
            $this->Translate($label)
        );
        if ($error !== '') {
            $status .= ' - ' . $error;
        }

        $this->WriteAttributeString('CommandStatusText', $status);
        $this->updateCommandStatusVariables();
        return false;
    }

    private function executeDirectCommand(string $key, string $label, Closure $command): bool
    {
        $pending = $this->readPendingCommands();
        $pending[$key] = [
            'state' => 'sending',
            'label' => $label
        ];

        $this->writePendingCommands($pending);
        $this->WriteAttributeString('LastCommandResult', 'sending');
        $this->WriteAttributeString('CommandStatusText', '');
        $this->updateCommandStatusVariables();

        try {
            $ok = $command();
        } catch (Throwable $throwable) {
            $ok = false;
            $this->WriteAttributeString('LastError', $throwable->getMessage());
        }

        $pending = $this->readPendingCommands();
        unset($pending[$key]);
        $this->writePendingCommands($pending);

        if ($ok) {
            $this->WriteAttributeString('LastCommandResult', 'accepted');
            $this->WriteAttributeString(
                'CommandStatusText',
                sprintf($this->Translate('Confirmed: %s'), $this->Translate($label))
            );
            $this->updateCommandStatusVariables();
            return true;
        }

        $this->WriteAttributeString('LastCommandResult', 'rejected');
        $error = trim($this->ReadAttributeString('LastError'));
        $status = sprintf(
            $this->Translate('Command rejected: %s'),
            $this->Translate($label)
        );
        if ($error !== '') {
            $status .= ' - ' . $error;
        }
        $this->WriteAttributeString('CommandStatusText', $status);
        $this->updateCommandStatusVariables();
        return false;
    }

    private function rejectDirectCommand(string $label, string $message): bool
    {
        $this->WriteAttributeString('LastError', $message);
        $this->WriteAttributeString('LastCommandResult', 'rejected');
        $this->WriteAttributeString(
            'CommandStatusText',
            sprintf($this->Translate('Command rejected: %s'), $this->Translate($label)) . ' - ' . $message
        );
        $this->updateCommandStatusVariables();
        return false;
    }

    private function clearPendingCommands(): void
    {
        $this->WriteAttributeString('PendingCommands', '{}');
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

        $labels = [];
        foreach ($pending as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $labels[] = $this->Translate((string) ($entry['label'] ?? 'Command'));
        }

        $this->setIfExists(
            'CommandStatus',
            $labels !== []
                ? sprintf($this->Translate('Waiting for confirmation: %s'), implode(', ', $labels))
                : $this->Translate('Ready')
        );
    }
}
