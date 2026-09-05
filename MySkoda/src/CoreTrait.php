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

        $this->RegisterTimer('UpdateTimer', 0, 'MSKODA_Update($_IPS[\'TARGET\']);');
        $this->RegisterTimer('VisualTimer', 0, 'MSKODA_RefreshVisuals($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $token = trim($this->ReadPropertyString('APIToken'));
        $interval = max(180, $this->ReadPropertyInteger('Interval'));

        $this->SetSummary($vin);
        $this->registerCoreVariables();
        $this->registerOptionalVariables($this->ReadPropertyBoolean('ShowDetails'));
        $this->applyActions();

        if (!$this->isVinValid($vin) || $token === '') {
            $this->SetTimerInterval('UpdateTimer', 0);
            $this->SetTimerInterval('VisualTimer', $this->ReadAttributeString('RawData') === '' ? 0 : 60000);
            $this->RefreshVisuals();
            $this->SetStatus(201);
            return;
        }

        $this->SetTimerInterval('UpdateTimer', $interval * 1000);
        $this->SetTimerInterval('VisualTimer', 60000);
        $this->SetStatus(102);

        if ($this->ReadAttributeString('RawData') === '') {
            $this->Update();
        } else {
            $this->RefreshVisuals();
        }
    }

    public function Update(): void
    {
        if (!$this->configurationValid()) {
            $this->SetStatus(201);
            return;
        }

        if (!$this->canRequest(false)) {
            $this->SetStatus(203);
            return;
        }

        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $response = $this->request('GET', '/api/v1/vehicles/' . rawurlencode($vin));
        $this->absorbHeaders($response['headers']);

        if (!$response['ok'] || !is_array($response['json'])) {
            $this->setApiError($response);
            return;
        }

        $envelope = $response['json'];
        $vehicle = isset($envelope['vehicle']) && is_array($envelope['vehicle']) ? $envelope['vehicle'] : [];
        if ($vehicle === []) {
            $this->WriteAttributeString('LastError', $this->Translate('Vehicle data is missing in the API response.'));
            $this->SetStatus(202);
            return;
        }

        $this->WriteAttributeString('RawData', json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->updateChargeModePresentation($vehicle);
        $this->updateCoreValues($vehicle);
        $this->updateOptionalValues($vehicle, $envelope);
        $this->WriteAttributeString('LastError', '');
        $this->SetValue('LastUpdate', time());
        $this->refreshVisualValues($vehicle);
        $this->SetStatus(102);
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
                $ok = (bool) $Value ? $this->startClimateInternal((float) $this->GetValue('TargetTemperature')) : $this->sendSimpleCommand('air-conditioning/stop');
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
                throw new InvalidArgumentException('Unbekannte Aktion: ' . $Ident);
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

    public function RefreshVisuals(): void
    {
        $this->refreshVisualValues();
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
            $this->WriteAttributeString('LastError', 'OpenAPI-Operation fuer Ladeprofil nicht gefunden');
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
        return $this->sendCommand('POST', '/api/v1/vehicles/' . rawurlencode(strtoupper($this->ReadPropertyString('VIN'))) . '/auxiliary-heating/start', $body);
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

}
