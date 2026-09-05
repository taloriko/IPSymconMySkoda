<?php

declare(strict_types=1);

trait MySkodaCoreTrait
{
    public function Create(): void
    {
        parent::Create();

        $this->RegisterPropertyString('VIN', '');
        $this->RegisterPropertyString('APIToken', '');
        $this->RegisterPropertyInteger('Interval', 300);
        $this->RegisterPropertyBoolean('EnableRemote', true);
        $this->RegisterPropertyBoolean('ClimateWithoutExternalPower', true);
        $this->RegisterPropertyString('SPIN', '');
        $this->RegisterPropertyBoolean('ShowDetails', false);
        $this->RegisterPropertyBoolean('NotifyKeyExpiry', false);
        $this->RegisterPropertyInteger('NotificationInstanceID', 0);

        $this->RegisterAttributeString('RawData', '');
        $this->RegisterAttributeString('OpenApiOperations', '');
        $this->RegisterAttributeInteger('OpenApiUpdatedAt', 0);
        $this->RegisterAttributeString('ChargeModeMap', '{}');
        $this->RegisterAttributeInteger('RateLimitLimit', -1);
        $this->RegisterAttributeInteger('RateLimitRemaining', -1);
        $this->RegisterAttributeInteger('RateLimitResetAt', 0);
        $this->RegisterAttributeInteger('BlockedUntil', 0);
        $this->RegisterAttributeInteger('ApiKeyExpiresAt', 0);
        $this->RegisterAttributeString('LastError', '');
        $this->RegisterAttributeString('ConnectionState', 'not_configured');
        $this->RegisterAttributeString('ConnectionMessage', '');
        $this->RegisterAttributeString('ConfigFingerprint', '');
        $this->RegisterAttributeInteger('KeyExpiryNotifiedFor', 0);
        $this->RegisterAttributeInteger('KeyExpiryNotificationLastAttempt', 0);

        $this->RegisterTimer('UpdateTimer', 0, 'MSKODA_Update($_IPS[\'TARGET\']);');
        $this->SetVisualizationType(0);
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->SetVisualizationType(0);
        $this->removeObsoleteVisualizationObjects();

        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $token = trim($this->ReadPropertyString('APIToken'));
        $interval = max(180, $this->ReadPropertyInteger('Interval'));

        $this->SetSummary($vin);
        $this->registerCoreVariables();
        $this->registerOptionalVariables($this->ReadPropertyBoolean('ShowDetails'));
        $this->applyActions();

        if (!$this->isVinValid($vin) || $token === '') {
            $this->SetTimerInterval('UpdateTimer', 0);
            $this->WriteAttributeInteger('ApiKeyExpiresAt', 0);
            $this->WriteAttributeInteger('KeyExpiryNotifiedFor', 0);
            $this->WriteAttributeInteger('KeyExpiryNotificationLastAttempt', 0);
            $this->updateKeyExpiryWarning();
            $this->setConnectionFeedback('not_configured', $this->Translate('Enter VIN and API token, then apply the configuration.'));
            $this->SetStatus(201);
            return;
        }

        $this->SetTimerInterval('UpdateTimer', $interval * 1000);

        $fingerprint = hash('sha256', $vin . '|' . $token);
        $configurationChanged = $fingerprint !== $this->ReadAttributeString('ConfigFingerprint');
        $this->WriteAttributeString('ConfigFingerprint', $fingerprint);
        if ($configurationChanged) {
            $this->WriteAttributeInteger('ApiKeyExpiresAt', 0);
            $this->WriteAttributeInteger('KeyExpiryNotifiedFor', 0);
            $this->WriteAttributeInteger('KeyExpiryNotificationLastAttempt', 0);
        }
        $this->updateKeyExpiryWarning();
        $this->validateNotificationTarget(false);

        if ($configurationChanged || $this->ReadAttributeString('RawData') === '') {
            $this->setConnectionFeedback('checking', $this->Translate('Checking connection to MySkoda...'));
            $this->TestConnection();
            return;
        }

        $this->SetStatus(102);
        $this->refreshConnectionForm();
    }

    public function Update(): void
    {
        $this->fetchVehicle(false);
    }

    public function TestConnection(): bool
    {
        return $this->fetchVehicle(true);
    }

    public function GetConfigurationForm(): string
    {
        $form = json_decode((string) @file_get_contents(__DIR__ . '/../form.json'), true);
        if (!is_array($form)) {
            return '{}';
        }

        $connectionCaption = $this->connectionFeedbackCaption();
        $notificationCaption = $this->notificationTargetFeedbackCaption();
        $validVisualizationModules = $this->getVisualizationModuleIds();

        if (isset($form['elements']) && is_array($form['elements'])) {
            $this->prepareConfigurationElements($form['elements'], $connectionCaption, $notificationCaption, $validVisualizationModules);
        }

        return json_encode($form, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            throw new RuntimeException($this->Translate('Remote control is disabled in this instance.'));
        }
        if (!$this->canRequest(true)) {
            $this->SetStatus(203);
            throw new RuntimeException($this->Translate('MySkoda rate limit / waiting period is active.'));
        }

        switch ($Ident) {
            case 'Charging':
                $ok = $this->sendSimpleCommand((bool) $Value ? 'charging/start' : 'charging/stop');
                if ($ok) {
                    $this->SetValue('Charging', (bool) $Value);
                }
                break;

            case 'TargetSOC':
                $percent = max(50, min(100, (int) $Value));
                $ok = $this->sendDiscoveredScalarCommand('limit', $percent);
                if ($ok) {
                    $this->SetValue('TargetSOC', $percent);
                }
                break;

            case 'ChargeMode':
                $map = json_decode($this->ReadAttributeString('ChargeModeMap'), true);
                $mode = is_array($map) ? ($map[(string) ((int) $Value)] ?? null) : null;
                if (!is_string($mode) || $mode === '') {
                    throw new RuntimeException($this->Translate('Unknown charge mode. Please refresh vehicle data.'));
                }
                $ok = $this->sendDiscoveredScalarCommand('mode', $mode);
                if ($ok) {
                    $this->SetValue('ChargeMode', (int) $Value);
                }
                break;

            case 'Climate':
                $ok = (bool) $Value
                    ? $this->startClimateInternal((float) $this->GetValue('TargetTemperature'))
                    : $this->sendSimpleCommand('air-conditioning/stop');
                if ($ok) {
                    $this->SetValue('Climate', (bool) $Value);
                }
                break;

            case 'TargetTemperature':
                $temperature = max(16.0, min(30.0, (float) $Value));
                $this->SetValue('TargetTemperature', $temperature);
                if ((bool) $this->GetValue('Climate')) {
                    $this->startClimateInternal($temperature);
                }
                break;

            default:
                throw new InvalidArgumentException('Unknown action: ' . $Ident);
        }
    }

    public function GetRawData(): string
    {
        return $this->ReadAttributeString('RawData');
    }

    public function GetChargingProfiles(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $profiles = $this->path($raw, 'vehicle.chargingProfiles', []);
        return json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function GetRemoteOperations(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $operations = $this->path($raw, 'vehicle.remoteOperations', []);
        return json_encode($operations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function RefreshApiDefinition(): bool
    {
        return $this->refreshOpenApi(true) !== [];
    }

    public function SetChargingLimit(int $Percent): bool
    {
        $Percent = max(50, min(100, $Percent));
        return $this->sendDiscoveredScalarCommand('limit', $Percent);
    }

    public function SetChargeMode(string $Mode): bool
    {
        return $this->sendDiscoveredScalarCommand('mode', strtoupper(trim($Mode)));
    }

    public function UpdateChargingProfile(int $ProfileID, string $ProfileJSON): bool
    {
        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            return false;
        }
        $profile = json_decode($ProfileJSON, true);
        if (!is_array($profile)) {
            $this->WriteAttributeString('LastError', $this->Translate('Invalid charging profile JSON'));
            return false;
        }

        $operation = $this->findOperation($this->refreshOpenApi(false), 'profile');
        if ($operation === null) {
            $this->WriteAttributeString('LastError', $this->Translate('Charging profile operation was not found in the OpenAPI definition.'));
            return false;
        }

        $path = $this->replacePathParameters((string) $operation['path'], $ProfileID);
        $body = $this->buildProfilePayload($operation, $profile);
        return $this->sendCommand((string) $operation['method'], $path, $body);
    }

    public function StartAuxiliaryHeating(float $TargetTemperature = 22.0, int $DurationMinutes = 30, string $Mode = 'HEATING'): bool
    {
        $spin = trim($this->ReadPropertyString('SPIN'));
        if ($spin === '') {
            $this->WriteAttributeString('LastError', $this->Translate('S-PIN is missing'));
            return false;
        }
        $body = [
            'spin' => $spin,
            'targetTemperature' => [
                'value' => max(16.0, min(30.0, $TargetTemperature)),
                'unit' => 'CELSIUS'
            ],
            'durationInSeconds' => max(60, $DurationMinutes * 60),
            'startMode' => strtoupper($Mode) === 'VENTILATION' ? 'VENTILATION' : 'HEATING'
        ];
        return $this->sendCommand(
            'POST',
            '/api/v1/vehicles/' . rawurlencode(strtoupper($this->ReadPropertyString('VIN'))) . '/auxiliary-heating/start',
            $body
        );
    }

    public function StopAuxiliaryHeating(): bool
    {
        return $this->sendSimpleCommand('auxiliary-heating/stop');
    }

    public function StartVentilation(): bool
    {
        return $this->sendSimpleCommand('active-ventilation/start');
    }

    public function StopVentilation(): bool
    {
        return $this->sendSimpleCommand('active-ventilation/stop');
    }

    private function fetchVehicle(bool $userAction): bool
    {
        if (!$this->configurationValid()) {
            $this->setConnectionFeedback('not_configured', $this->Translate('Enter VIN and API token, then apply the configuration.'));
            $this->SetStatus(201);
            return false;
        }

        if (!$this->canRequest($userAction)) {
            $message = $this->Translate('MySkoda rate limit / waiting period is active.');
            $this->setConnectionFeedback('error', $message);
            $this->SetStatus(203);
            return false;
        }

        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $response = $this->request('GET', '/api/v1/vehicles/' . rawurlencode($vin));
        $this->absorbHeaders($response['headers']);

        if (!$response['ok'] || !is_array($response['json'])) {
            $this->setApiError($response);
            $this->setConnectionFeedback('error', $this->ReadAttributeString('LastError'));
            return false;
        }

        $envelope = $response['json'];
        $vehicle = isset($envelope['vehicle']) && is_array($envelope['vehicle']) ? $envelope['vehicle'] : [];
        if ($vehicle === []) {
            $message = $this->Translate('Vehicle data is missing in the API response.');
            $this->WriteAttributeString('LastError', $message);
            $this->setConnectionFeedback('error', $message);
            $this->SetStatus(202);
            return false;
        }

        $this->WriteAttributeString('RawData', json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->updateChargeModePresentation($vehicle);
        $this->updateCoreValues($vehicle);
        $this->updateOptionalValues($vehicle, $envelope);
        $this->WriteAttributeString('LastError', '');
        $this->SetValue('LastUpdate', time());
        $this->updateKeyExpiryWarning();
        $this->setConnectionFeedback('success', $this->Translate('Connection successful. Vehicle data was received from MySkoda.'));
        $this->SetStatus(102);
        return true;
    }

    private function prepareConfigurationElements(array &$elements, string $connectionCaption, string $notificationCaption, array $validVisualizationModules): void
    {
        foreach ($elements as &$element) {
            if (!is_array($element)) {
                continue;
            }

            $name = (string) ($element['name'] ?? '');
            if ($name === 'ConnectionFeedback') {
                $element['caption'] = $connectionCaption;
            } elseif ($name === 'NotificationInstanceID') {
                $element['validModules'] = $validVisualizationModules;
            } elseif ($name === 'NotificationTargetFeedback') {
                $element['caption'] = $notificationCaption;
            }

            if (isset($element['items']) && is_array($element['items'])) {
                $this->prepareConfigurationElements($element['items'], $connectionCaption, $notificationCaption, $validVisualizationModules);
            }
        }
        unset($element);
    }

    private function setConnectionFeedback(string $state, string $message): void
    {
        $this->WriteAttributeString('ConnectionState', $state);
        $this->WriteAttributeString('ConnectionMessage', $message);
        $this->refreshConnectionForm();
    }

    private function refreshConnectionForm(): void
    {
        try {
            $this->UpdateFormField('ConnectionFeedback', 'caption', $this->connectionFeedbackCaption());
        } catch (Throwable) {
        }
    }

    private function connectionFeedbackCaption(): string
    {
        $state = $this->ReadAttributeString('ConnectionState');
        $message = trim($this->ReadAttributeString('ConnectionMessage'));
        if ($message === '') {
            $message = $this->Translate('Connection has not been tested yet.');
        }

        return match ($state) {
            'success' => '✅ ' . $message,
            'error' => '❌ ' . $message,
            'checking' => '⏳ ' . $message,
            default => 'ℹ️ ' . $message
        };
    }

    private function removeObsoleteVisualizationObjects(): void
    {
        $this->dropVariable('VehicleTile');
        $this->dropVariable('LastUpdateAge');

        $timerId = @IPS_GetObjectIDByIdent('VisualTimer', $this->InstanceID);
        if ($timerId !== false && @IPS_EventExists($timerId)) {
            @IPS_DeleteEvent($timerId);
        }
    }
}
