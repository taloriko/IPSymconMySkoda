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

        // Kept for compatibility with existing 1.1 instances. No delayed confirmation
        // request is used anymore; the timer always remains disabled.
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
            $this->WriteAttributeString('CommandStatusText', '');
        }
        $this->WriteAttributeString('CommandConfigFingerprint', $fingerprint);

        // Remove pending states left by an earlier 1.1 build. Commands are now
        // resolved directly by the HTTP response from the MySkoda server.
        $this->clearPendingCommands();

        $this->coreApplyChanges();
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
                $previous = (float) $this->GetValue('TargetTemperature');
                $this->SetValue('TargetTemperature', $temperature);

                if (!(bool) $this->GetValue('Climate')) {
                    $this->WriteAttributeString('CommandStatusText', '');
                    $this->updateCommandStatusVariables();
                    return;
                }

                $this->executeOptimisticCommand(
                    'TargetTemperature',
                    $temperature,
                    'Target temperature',
                    fn (): bool => $this->startClimateInternal($temperature),
                    $previous
                );
                return;
        }

        throw new InvalidArgumentException('Unknown action: ' . $Ident);
    }

    public function ConfirmPending(): void
    {
        // Compatibility endpoint for timers/scripts from early 1.1 builds.
        // Confirmation is resolved synchronously now, so there is nothing to poll.
        $this->clearPendingCommands();
        $this->updateCommandStatusVariables();
    }

    public function SetChargingLimit(int $Percent): bool
    {
        $Percent = max(50, min(100, $Percent));
        return $this->executeDirectMethodCommand(
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
        if ($index === false || !$this->isChargeModeAvailable($Mode)) {
            $message = $this->Translate('The selected charging mode is not supported by this vehicle.');
            $this->WriteAttributeString('CommandStatusText', $message);
            $this->WriteAttributeString('LastError', $message);
            $this->updateCommandStatusVariables();
            return false;
        }

        return $this->executeDirectMethodCommand(
            'ChargeMode',
            (int) $index,
            'Charging mode',
            fn (): bool => $this->sendDiscoveredScalarCommand('mode', $Mode)
        );
    }

    private function executeOptimisticCommand(
        string $ident,
        mixed $desiredValue,
        string $caption,
        Closure $request,
        mixed $previousValue = null
    ): bool {
        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            $message = $this->Translate('Remote control is disabled in this instance.');
            $this->WriteAttributeString('CommandStatusText', $message);
            $this->updateCommandStatusVariables();
            throw new RuntimeException($message);
        }

        if ($previousValue === null) {
            $previousValue = $this->GetValue($ident);
        }

        $pending = $this->pendingCommands();
        $pending[$ident] = [
            'desired' => $desiredValue,
            'previous' => $previousValue,
            'caption' => $caption,
            'startedAt' => time()
        ];
        $this->writePendingCommands($pending);
        $this->WriteAttributeString(
            'CommandStatusText',
            sprintf($this->Translate('Waiting for confirmation: %s'), $this->Translate($caption))
        );
        $this->updateCommandStatusVariables();
        $this->SetValue($ident, $desiredValue);

        $ok = false;
        try {
            $ok = $request();
        } catch (Throwable $error) {
            $this->WriteAttributeString('LastError', $error->getMessage());
            $ok = false;
        }

        $pending = $this->pendingCommands();
        unset($pending[$ident]);
        $this->writePendingCommands($pending);

        if ($ok) {
            $this->SetValue($ident, $desiredValue);
            $this->WriteAttributeString('LastCommandResult', 'success');
            $this->WriteAttributeString(
                'CommandStatusText',
                sprintf($this->Translate('Confirmed: %s'), $this->Translate($caption))
            );
            $this->updateCommandStatusVariables();
            return true;
        }

        $this->SetValue($ident, $previousValue);
        $this->WriteAttributeString('LastCommandResult', 'error');
        $status = sprintf($this->Translate('Command rejected: %s'), $this->Translate($caption));
        $error = trim($this->ReadAttributeString('LastError'));
        if ($error !== '') {
            $status .= ' - ' . $error;
        }
        $this->WriteAttributeString('CommandStatusText', $status);
        $this->updateCommandStatusVariables();
        return false;
    }

    private function executeDirectMethodCommand(
        string $ident,
        mixed $desiredValue,
        string $caption,
        Closure $request
    ): bool {
        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            $message = $this->Translate('Remote control is disabled in this instance.');
            $this->WriteAttributeString('LastError', $message);
            $this->WriteAttributeString('CommandStatusText', $message);
            $this->updateCommandStatusVariables();
            return false;
        }

        if (!$this->canRequest(true)) {
            $message = $this->Translate('MySkoda rate limit / waiting period is active.');
            $this->WriteAttributeString('LastError', $message);
            $this->WriteAttributeString('CommandStatusText', $message);
            $this->updateCommandStatusVariables();
            $this->SetStatus(203);
            return false;
        }

        $previousValue = $this->GetValue($ident);
        return $this->executeOptimisticCommand(
            $ident,
            $desiredValue,
            $caption,
            $request,
            $previousValue
        );
    }

    private function pendingCommands(): array
    {
        $pending = json_decode($this->ReadAttributeString('PendingCommands'), true);
        return is_array($pending) ? $pending : [];
    }

    private function writePendingCommands(array $pending): void
    {
        $this->WriteAttributeString(
            'PendingCommands',
            json_encode($pending, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function clearPendingCommands(): void
    {
        $this->WriteAttributeString('PendingCommands', '{}');
        $this->SetTimerInterval('CommandConfirmTimer', 0);
    }

    private function updateCommandStatusVariables(): void
    {
        $pending = $this->pendingCommands();
        $this->setIfExists('PendingCommands', count($pending));

        $status = trim($this->ReadAttributeString('CommandStatusText'));
        if ($status === '') {
            $status = $this->Translate('Ready');
        }
        $this->setIfExists('CommandStatus', $status);
    }
}
