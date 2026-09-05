<?php

declare(strict_types=1);

final class MySkoda extends IPSModuleStrict
{
    private const API_ROOT = 'https://public.api.connect.skoda-auto.cz';
    private const OPENAPI_URL = self::API_ROOT . '/v3/api-docs';
    private const USER_AGENT = 'IP-Symcon-MySkoda/0.1';
    private const QUOTA_RESERVE = 2;

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
            $this->SetStatus(201);
            return;
        }

        $this->SetTimerInterval('UpdateTimer', $interval * 1000);
        $this->SetStatus(102);

        if ($this->ReadAttributeString('RawData') === '') {
            $this->Update();
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

    private function registerCoreVariables(): void
    {
        $this->RegisterVariableInteger('StateOfCharge', $this->Translate('State of charge'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_BATTERY
        ], 10);

        $this->RegisterVariableInteger('Range', $this->Translate('Range'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'SUFFIX' => ' km',
            'DIGITS' => 0
        ], 20);

        $this->RegisterVariableInteger('Mileage', $this->Translate('Mileage'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'SUFFIX' => ' km',
            'DIGITS' => 0,
            'THOUSANDS_SEPARATOR' => '.'
        ], 30);

        $this->RegisterVariableBoolean('Locked', $this->Translate('Locked'), [], 40);
        $this->RegisterVariableBoolean('DoorsOpen', $this->Translate('Doors open'), [], 50);
        $this->RegisterVariableBoolean('WindowsOpen', $this->Translate('Windows open'), [], 60);

        $this->RegisterVariableBoolean('Charging', $this->Translate('Charging'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH
        ], 70);

        $this->RegisterVariableFloat('ChargePower', $this->Translate('Charging power'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'TEMPLATE' => VARIABLE_TEMPLATE_VALUE_PRESENTATION_POWER
        ], 80);

        $this->RegisterVariableInteger('TargetSOC', $this->Translate('Charging limit'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
            'MIN' => 50,
            'MAX' => 100,
            'STEP_SIZE' => 5,
            'SUFFIX' => ' %',
            'PERCENTAGE' => false,
            'USAGE_TYPE' => 5
        ], 90);

        $this->RegisterVariableInteger('ChargeMode', $this->Translate('Charging mode'), $this->chargeModePresentation([]), 100);

        $this->RegisterVariableBoolean('Climate', $this->Translate('Air conditioning'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH
        ], 110);

        $this->RegisterVariableFloat('TargetTemperature', $this->Translate('Target temperature'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_SLIDER,
            'MIN' => 16,
            'MAX' => 30,
            'STEP_SIZE' => 0.5,
            'GRADIENT_TYPE' => 1,
            'USAGE_TYPE' => 0,
            'SUFFIX' => ' °C',
            'PERCENTAGE' => false,
            'DIGITS' => 1
        ], 120);

        if ((float) $this->GetValue('TargetTemperature') === 0.0) {
            $this->SetValue('TargetTemperature', 22.0);
        }

        $this->RegisterVariableInteger('LastUpdate', $this->Translate('Last update'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
        ], 900);
    }

    private function registerOptionalVariables(bool $show): void
    {
        if (!$show) {
            foreach ([
                'VehicleName', 'LicensePlate', 'ChargingState', 'ChargeType', 'FullyChargedAt',
                'TrunkOpen', 'BonnetOpen', 'LightsOn', 'ParkingState', 'Latitude', 'Longitude',
                'ApiKeyExpiresAtVar', 'RequestsRemaining', 'PartialErrors'
            ] as $ident) {
                $this->dropVariable($ident);
            }
            return;
        }

        $this->RegisterVariableString('VehicleName', $this->Translate('Vehicle name'), [], 200);
        $this->RegisterVariableString('LicensePlate', $this->Translate('License plate'), [], 210);
        $this->RegisterVariableString('ChargingState', $this->Translate('Charging state'), [], 220);
        $this->RegisterVariableString('ChargeType', $this->Translate('Charge type'), [], 230);
        $this->RegisterVariableInteger('FullyChargedAt', $this->Translate('Fully charged at'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
        ], 240);
        $this->RegisterVariableBoolean('TrunkOpen', $this->Translate('Trunk open'), [], 250);
        $this->RegisterVariableBoolean('BonnetOpen', $this->Translate('Bonnet open'), [], 260);
        $this->RegisterVariableBoolean('LightsOn', $this->Translate('Lights on'), [], 270);
        $this->RegisterVariableString('ParkingState', $this->Translate('Parking state'), [], 280);
        $this->RegisterVariableFloat('Latitude', $this->Translate('Latitude'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'DIGITS' => 6
        ], 290);
        $this->RegisterVariableFloat('Longitude', $this->Translate('Longitude'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'DIGITS' => 6
        ], 300);
        $this->RegisterVariableInteger('ApiKeyExpiresAtVar', $this->Translate('API key valid until'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_DATE_TIME,
            'TEMPLATE' => VARIABLE_TEMPLATE_DATE_TIME
        ], 310);
        $this->RegisterVariableInteger('RequestsRemaining', $this->Translate('API requests remaining'), [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION
        ], 320);
        $this->RegisterVariableString('PartialErrors', $this->Translate('API partial errors'), [], 330);
    }

    private function applyActions(): void
    {
        $active = $this->ReadPropertyBoolean('EnableRemote');
        foreach (['Charging', 'TargetSOC', 'ChargeMode', 'Climate', 'TargetTemperature'] as $ident) {
            $this->MaintainAction($ident, $active);
        }
    }

    private function updateCoreValues(array $vehicle): void
    {
        $soc = $this->path($vehicle, 'charging.status.battery.stateOfChargeInPercent', null);
        if ($soc !== null) {
            $this->SetValue('StateOfCharge', (int) $soc);
        }

        $rangeMeters = $this->path($vehicle, 'charging.status.battery.remainingCruisingRangeInMeters', null);
        if ($rangeMeters !== null) {
            $this->SetValue('Range', (int) round(((float) $rangeMeters) / 1000));
        }

        $mileage = $this->path($vehicle, 'odometer.mileageInKm', null);
        if ($mileage !== null) {
            $this->SetValue('Mileage', (int) round((float) $mileage));
        }

        $lock = strtoupper((string) ($this->path($vehicle, 'status.overall.doorsLocked', $this->path($vehicle, 'status.overall.locked', 'UNKNOWN'))));
        $this->SetValue('Locked', $lock === 'YES' || $lock === 'LOCKED');
        $this->SetValue('DoorsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.doors', 'CLOSED')) === 'OPEN');
        $this->SetValue('WindowsOpen', strtoupper((string) $this->path($vehicle, 'status.overall.windows', 'CLOSED')) === 'OPEN');

        $chargeState = strtoupper((string) $this->path($vehicle, 'charging.status.state', ''));
        $this->SetValue('Charging', in_array($chargeState, ['CHARGING', 'CONSERVING'], true));

        $chargePowerKw = $this->path($vehicle, 'charging.status.chargePowerInKw', null);
        if ($chargePowerKw !== null) {
            // Symcon Standardvorlage Leistung arbeitet mit Watt.
            $this->SetValue('ChargePower', ((float) $chargePowerKw) * 1000.0);
        }

        $targetSoc = $this->path($vehicle, 'charging.settings.targetStateOfChargeInPercent', null);
        if ($targetSoc !== null) {
            $this->SetValue('TargetSOC', (int) $targetSoc);
        }

        $mode = $this->path($vehicle, 'charging.settings.preferredChargeMode', null);
        if (is_string($mode) && $mode !== '') {
            $map = json_decode($this->ReadAttributeString('ChargeModeMap'), true);
            if (is_array($map)) {
                $index = array_search($mode, $map, true);
                if ($index !== false) {
                    $this->SetValue('ChargeMode', (int) $index);
                }
            }
        }

        $climateState = strtoupper((string) $this->path($vehicle, 'airConditioning.state', 'OFF'));
        $this->SetValue('Climate', in_array($climateState, ['COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'], true));

        $temperature = $this->path($vehicle, 'airConditioning.targetTemperature.value', null);
        if ($temperature !== null) {
            $this->SetValue('TargetTemperature', (float) $temperature);
        }
    }

    private function updateOptionalValues(array $vehicle, array $envelope): void
    {
        if (!$this->ReadPropertyBoolean('ShowDetails')) {
            return;
        }

        $this->setIfExists('VehicleName', (string) $this->path($vehicle, 'name', ''));
        $this->setIfExists('LicensePlate', (string) $this->path($vehicle, 'licensePlate', ''));
        $this->setIfExists('ChargingState', (string) $this->path($vehicle, 'charging.status.state', ''));
        $this->setIfExists('ChargeType', (string) $this->path($vehicle, 'charging.status.chargeType', ''));

        $full = $this->toTimestamp($this->path($vehicle, 'charging.status.fullyChargedAt', null));
        if ($full > 0) {
            $this->setIfExists('FullyChargedAt', $full);
        }

        $this->setIfExists('TrunkOpen', strtoupper((string) $this->path($vehicle, 'status.detail.trunk', 'CLOSED')) === 'OPEN');
        $this->setIfExists('BonnetOpen', strtoupper((string) $this->path($vehicle, 'status.detail.bonnet', 'CLOSED')) === 'OPEN');
        $this->setIfExists('LightsOn', strtoupper((string) $this->path($vehicle, 'status.overall.lights', 'OFF')) === 'ON');
        $this->setIfExists('ParkingState', (string) $this->path($vehicle, 'parkingPosition.state', ''));

        $lat = $this->firstPath($vehicle, ['parkingPosition.latitude', 'parkingPosition.gpsCoordinates.latitude', 'parkingPosition.gpsCoordinates.lat']);
        $lon = $this->firstPath($vehicle, ['parkingPosition.longitude', 'parkingPosition.gpsCoordinates.longitude', 'parkingPosition.gpsCoordinates.lon', 'parkingPosition.gpsCoordinates.lng']);
        if ($lat !== null) {
            $this->setIfExists('Latitude', (float) $lat);
        }
        if ($lon !== null) {
            $this->setIfExists('Longitude', (float) $lon);
        }

        $expiry = $this->ReadAttributeInteger('ApiKeyExpiresAt');
        if ($expiry > 0) {
            $this->setIfExists('ApiKeyExpiresAtVar', $expiry);
        }
        $remaining = $this->ReadAttributeInteger('RateLimitRemaining');
        if ($remaining >= 0) {
            $this->setIfExists('RequestsRemaining', $remaining);
        }

        $errors = isset($envelope['errors']) && is_array($envelope['errors']) ? $envelope['errors'] : [];
        $this->setIfExists('PartialErrors', $errors === [] ? '' : json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function updateChargeModePresentation(array $vehicle): void
    {
        $modes = $this->path($vehicle, 'charging.settings.availableChargeModes', []);
        if (!is_array($modes)) {
            $modes = [];
        }
        $current = $this->path($vehicle, 'charging.settings.preferredChargeMode', null);
        if (is_string($current) && $current !== '' && !in_array($current, $modes, true)) {
            $modes[] = $current;
        }

        $clean = [];
        foreach ($modes as $mode) {
            if (is_string($mode) && $mode !== '' && !in_array($mode, $clean, true)) {
                $clean[] = $mode;
            }
        }

        $map = [];
        foreach (array_values($clean) as $index => $mode) {
            $map[(string) $index] = $mode;
        }
        $this->WriteAttributeString('ChargeModeMap', json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->RegisterVariableInteger('ChargeMode', $this->Translate('Charging mode'), $this->chargeModePresentation($map), 100);
        $this->MaintainAction('ChargeMode', $this->ReadPropertyBoolean('EnableRemote') && $map !== []);
    }

    private function chargeModePresentation(array $map): array
    {
        $options = [];
        foreach ($map as $index => $mode) {
            $options[] = [
                'Value' => (int) $index,
                'Caption' => $this->humanizeMode((string) $mode),
                'IconActive' => false,
                'IconValue' => '',
                'Color' => -1
            ];
        }
        return [
            'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
            'LAYOUT' => 0,
            'OPTIONS' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    private function humanizeMode(string $mode): string
    {
        $known = [
            'MANUAL' => $this->Translate('Manual'),
            'TIMER' => $this->Translate('Timer'),
            'TIMER_CHARGING_WITH_CLIMATISATION' => $this->Translate('Timer + climate'),
            'PREFERRED_CHARGING_TIMES' => $this->Translate('Preferred charging times'),
            'ONLY_OWN_CURRENT' => $this->Translate('Only own current'),
            'IMMEDIATE_DISCHARGING' => $this->Translate('Immediate discharging'),
            'HOME_STORAGE_CHARGING' => $this->Translate('Home storage charging')
        ];
        return $known[$mode] ?? ucwords(strtolower(str_replace('_', ' ', $mode)));
    }

    private function sendSimpleCommand(string $relative): bool
    {
        $vin = rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN'))));
        return $this->sendCommand('POST', '/api/v1/vehicles/' . $vin . '/' . ltrim($relative, '/'), []);
    }

    private function startClimateInternal(float $temperature): bool
    {
        $vin = rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN'))));
        $body = [
            'targetTemperature' => [
                'value' => max(16.0, min(30.0, $temperature)),
                'unit' => 'CELSIUS'
            ],
            'airConditioningWithoutExternalPower' => $this->ReadPropertyBoolean('ClimateWithoutExternalPower')
        ];
        return $this->sendCommand('POST', '/api/v1/vehicles/' . $vin . '/air-conditioning/start', $body);
    }

    private function sendDiscoveredScalarCommand(string $purpose, int|string $value): bool
    {
        $operation = $this->findOperation($this->refreshOpenApi(false), $purpose);
        if ($operation === null) {
            $this->WriteAttributeString('LastError', 'OpenAPI-Operation nicht gefunden: ' . $purpose);
            $this->SetStatus(202);
            return false;
        }
        $path = $this->replacePathParameters((string) $operation['path']);
        $body = $this->buildScalarPayload($operation, $purpose, $value);
        return $this->sendCommand((string) $operation['method'], $path, $body);
    }

    private function sendCommand(string $method, string $path, ?array $body): bool
    {
        if (!$this->ReadPropertyBoolean('EnableRemote') || !$this->canRequest(true)) {
            return false;
        }
        $response = $this->request($method, $path, $body);
        $this->absorbHeaders($response['headers']);
        if (!$response['ok']) {
            $this->setApiError($response);
            return false;
        }
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(102);
        return true;
    }

    private function request(string $method, string $path, ?array $body = null, bool $withApiKey = true): array
    {
        $headers = [
            'Accept: application/json, application/problem+json',
            'User-Agent: ' . self::USER_AGENT
        ];
        if ($withApiKey) {
            $headers[] = 'X-API-Key: ' . trim($this->ReadPropertyString('APIToken'));
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $responseHeaders = [];
        $url = str_starts_with($path, 'http') ? $path : self::API_ROOT . $path;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$responseHeaders): int {
                $length = strlen($header);
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $length;
            }
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $json = $decoded;
            }
        }

        $this->SendDebug('HTTP', strtoupper($method) . ' ' . $path . ' -> ' . $httpCode, 0);

        return [
            'ok' => $curlError === '' && $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'headers' => $responseHeaders,
            'raw' => is_string($raw) ? $raw : '',
            'json' => $json,
            'curlError' => $curlError
        ];
    }

    private function absorbHeaders(array $headers): void
    {
        if (isset($headers['ratelimit-limit']) && is_numeric($headers['ratelimit-limit'])) {
            $this->WriteAttributeInteger('RateLimitLimit', (int) $headers['ratelimit-limit']);
        }
        if (isset($headers['ratelimit-remaining']) && is_numeric($headers['ratelimit-remaining'])) {
            $this->WriteAttributeInteger('RateLimitRemaining', (int) $headers['ratelimit-remaining']);
        }
        if (isset($headers['ratelimit-reset']) && is_numeric($headers['ratelimit-reset'])) {
            $this->WriteAttributeInteger('RateLimitResetAt', time() + (int) $headers['ratelimit-reset']);
        }
        if (isset($headers['retry-after']) && is_numeric($headers['retry-after'])) {
            $this->WriteAttributeInteger('BlockedUntil', time() + (int) $headers['retry-after']);
        }
        if (isset($headers['x-api-key-expires-at'])) {
            $timestamp = strtotime((string) $headers['x-api-key-expires-at']);
            if ($timestamp !== false) {
                $this->WriteAttributeInteger('ApiKeyExpiresAt', $timestamp);
            }
        }
    }

    private function canRequest(bool $userAction): bool
    {
        $now = time();
        if ($this->ReadAttributeInteger('BlockedUntil') > $now) {
            return false;
        }
        $remaining = $this->ReadAttributeInteger('RateLimitRemaining');
        $resetAt = $this->ReadAttributeInteger('RateLimitResetAt');
        if ($remaining < 0 || $resetAt <= $now) {
            return true;
        }
        if ($userAction) {
            return $remaining > 0;
        }
        return $remaining > self::QUOTA_RESERVE;
    }

    private function setApiError(array $response): void
    {
        $this->absorbHeaders($response['headers'] ?? []);
        $text = $this->problemText($response);
        $this->WriteAttributeString('LastError', $text);
        $this->SendDebug('API-Fehler', $text, 0);
        $this->SetStatus(((int) ($response['status'] ?? 0)) === 429 ? 203 : 202);
    }

    private function problemText(array $response): string
    {
        if (($response['curlError'] ?? '') !== '') {
            return 'cURL: ' . $response['curlError'];
        }
        $json = $response['json'] ?? null;
        if (is_array($json)) {
            $detail = $json['detail'] ?? $json['title'] ?? null;
            $type = $json['type'] ?? null;
            if ($detail !== null) {
                return 'HTTP ' . ((int) ($response['status'] ?? 0)) . ': ' . $detail . ($type ? ' [' . basename((string) $type) . ']' : '');
            }
        }
        return 'HTTP ' . ((int) ($response['status'] ?? 0));
    }

    private function refreshOpenApi(bool $force): array
    {
        $cached = $this->ReadAttributeString('OpenApiOperations');
        $updated = $this->ReadAttributeInteger('OpenApiUpdatedAt');
        if (!$force && $cached !== '' && (time() - $updated) < 86400) {
            $ops = json_decode($cached, true);
            return is_array($ops) ? $ops : [];
        }

        $response = $this->request('GET', self::OPENAPI_URL, null, false);
        if (!$response['ok'] || !is_array($response['json'])) {
            $ops = json_decode($cached, true);
            return is_array($ops) ? $ops : [];
        }

        $ops = $this->extractOpenApiOperations($response['json']);
        $this->WriteAttributeString('OpenApiOperations', json_encode($ops, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->WriteAttributeInteger('OpenApiUpdatedAt', time());
        return $ops;
    }

    private function extractOpenApiOperations(array $spec): array
    {
        $operations = [];
        foreach (($spec['paths'] ?? []) as $path => $pathItem) {
            if (!is_array($pathItem)) {
                continue;
            }
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
                if (!isset($pathItem[$method]) || !is_array($pathItem[$method])) {
                    continue;
                }
                $op = $pathItem[$method];
                $schema = null;
                foreach (($op['requestBody']['content'] ?? []) as $content) {
                    if (is_array($content) && isset($content['schema']) && is_array($content['schema'])) {
                        $schema = $this->resolveSchema($spec, $content['schema']);
                        break;
                    }
                }
                $operations[] = [
                    'operationId' => (string) ($op['operationId'] ?? ''),
                    'summary' => (string) ($op['summary'] ?? ''),
                    'method' => strtoupper($method),
                    'path' => (string) $path,
                    'requestSchema' => $schema
                ];
            }
        }
        return $operations;
    }

    private function resolveSchema(array $spec, array $schema, int $depth = 0): array
    {
        if ($depth > 12) {
            return $schema;
        }
        if (isset($schema['$ref']) && is_string($schema['$ref'])) {
            $resolved = $this->resolveJsonPointer($spec, $schema['$ref']);
            if (is_array($resolved)) {
                return $this->resolveSchema($spec, $resolved, $depth + 1);
            }
        }
        if (isset($schema['allOf']) && is_array($schema['allOf'])) {
            $merged = [];
            foreach ($schema['allOf'] as $part) {
                if (is_array($part)) {
                    $merged = array_replace_recursive($merged, $this->resolveSchema($spec, $part, $depth + 1));
                }
            }
            $schema = array_replace_recursive($merged, $schema);
            unset($schema['allOf']);
        }
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                if (is_array($property)) {
                    $schema['properties'][$key] = $this->resolveSchema($spec, $property, $depth + 1);
                }
            }
        }
        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->resolveSchema($spec, $schema['items'], $depth + 1);
        }
        return $schema;
    }

    private function resolveJsonPointer(array $document, string $pointer): mixed
    {
        if (!str_starts_with($pointer, '#/')) {
            return null;
        }
        $current = $document;
        foreach (explode('/', substr($pointer, 2)) as $part) {
            $part = str_replace(['~1', '~0'], ['/', '~'], $part);
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }
        return $current;
    }

    private function findOperation(array $operations, string $purpose): ?array
    {
        $ids = [
            'limit' => ['setChargingLimit', 'setChargeLimit', 'updateChargingLimit', 'setTargetStateOfCharge', 'setTargetStateOfChargeInPercent'],
            'mode' => ['changeChargeMode', 'setChargeMode', 'changeChargingMode', 'setChargingMode'],
            'profile' => ['updateChargingProfile', 'setChargingProfile', 'updateChargeProfile']
        ];
        foreach ($ids[$purpose] ?? [] as $wanted) {
            foreach ($operations as $operation) {
                if (strcasecmp((string) ($operation['operationId'] ?? ''), $wanted) === 0) {
                    return $operation;
                }
            }
        }

        $best = null;
        $scoreBest = 0;
        foreach ($operations as $operation) {
            if (($operation['method'] ?? '') === 'GET') {
                continue;
            }
            $hay = strtolower(($operation['operationId'] ?? '') . ' ' . ($operation['summary'] ?? '') . ' ' . ($operation['path'] ?? ''));
            $score = 0;
            if (str_contains($hay, 'charg')) {
                $score += 2;
            }
            if ($purpose === 'limit') {
                $score += str_contains($hay, 'limit') ? 6 : 0;
                $score += (str_contains($hay, 'target') && str_contains($hay, 'state')) ? 4 : 0;
                $score += str_contains($hay, 'soc') ? 3 : 0;
            } elseif ($purpose === 'mode') {
                $score += str_contains($hay, 'mode') ? 7 : 0;
            } elseif ($purpose === 'profile') {
                $score += str_contains($hay, 'profile') ? 7 : 0;
                $score += (str_contains($hay, 'update') || str_contains($hay, 'set')) ? 2 : 0;
            }
            if ($score > $scoreBest) {
                $scoreBest = $score;
                $best = $operation;
            }
        }
        return $scoreBest >= 6 ? $best : null;
    }

    private function buildScalarPayload(array $operation, string $purpose, int|string $value): array
    {
        $schema = is_array($operation['requestSchema'] ?? null) ? $operation['requestSchema'] : [];
        if ($purpose === 'limit') {
            $path = $this->findSchemaPropertyPath($schema, ['targetStateOfChargeInPercent', 'targetSoc', 'chargingLimit', 'limit'], ['target']);
            return $this->setNestedValue($path ?? ['targetStateOfChargeInPercent'], (int) $value);
        }
        $path = $this->findSchemaPropertyPath($schema, ['preferredChargeMode', 'chargeMode', 'chargingMode', 'mode'], ['mode']);
        return $this->setNestedValue($path ?? ['preferredChargeMode'], (string) $value);
    }

    private function findSchemaPropertyPath(array $schema, array $exactNames, array $containsWords, array $prefix = []): ?array
    {
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return null;
        }
        foreach ($properties as $name => $property) {
            foreach ($exactNames as $exact) {
                if (strcasecmp((string) $name, (string) $exact) === 0 && !(is_array($property) && isset($property['properties']))) {
                    return array_merge($prefix, [(string) $name]);
                }
            }
        }
        foreach ($properties as $name => $property) {
            if (is_array($property) && isset($property['properties'])) {
                $found = $this->findSchemaPropertyPath($property, $exactNames, $containsWords, array_merge($prefix, [(string) $name]));
                if ($found !== null) {
                    return $found;
                }
            }
        }
        foreach ($properties as $name => $property) {
            if (is_array($property) && isset($property['properties'])) {
                continue;
            }
            $low = strtolower((string) $name);
            $matches = true;
            foreach ($containsWords as $word) {
                if (!str_contains($low, strtolower((string) $word))) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return array_merge($prefix, [(string) $name]);
            }
        }
        return null;
    }

    private function setNestedValue(array $path, int|string $value): array
    {
        $result = [];
        $ref =& $result;
        $last = array_pop($path);
        foreach ($path as $part) {
            $ref[$part] = [];
            $ref =& $ref[$part];
        }
        $ref[$last] = $value;
        return $result;
    }

    private function replacePathParameters(string $path, ?int $profileId = null): string
    {
        $path = str_replace('{vin}', rawurlencode(strtoupper(trim($this->ReadPropertyString('VIN')))), $path);
        if (preg_match_all('/\{([^}]+)\}/', $path, $matches)) {
            foreach ($matches[1] as $parameter) {
                $low = strtolower((string) $parameter);
                if ($profileId !== null && ($low === 'id' || str_contains($low, 'profile'))) {
                    $path = str_replace('{' . $parameter . '}', rawurlencode((string) $profileId), $path);
                }
            }
        }
        return $path;
    }

    private function buildProfilePayload(array $operation, array $profile): array
    {
        $schema = is_array($operation['requestSchema'] ?? null) ? $operation['requestSchema'] : [];
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return $profile;
        }
        foreach (['profile', 'chargingProfile'] as $wrapper) {
            if (isset($properties[$wrapper]) && is_array($properties[$wrapper])) {
                return [$wrapper => $this->filterPayloadToSchema($profile, $properties[$wrapper])];
            }
        }
        $filtered = $this->filterPayloadToSchema($profile, $schema);
        return $filtered !== [] ? $filtered : $profile;
    }

    private function filterPayloadToSchema(array $payload, array $schema): array
    {
        $properties = $schema['properties'] ?? null;
        if (!is_array($properties)) {
            return $payload;
        }
        $result = [];
        foreach ($properties as $key => $propertySchema) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $value = $payload[$key];
            if (is_array($value) && is_array($propertySchema)) {
                if (array_is_list($value) && isset($propertySchema['items']) && is_array($propertySchema['items'])) {
                    $result[$key] = array_map(fn ($item) => is_array($item) ? $this->filterPayloadToSchema($item, $propertySchema['items']) : $item, $value);
                } else {
                    $result[$key] = $this->filterPayloadToSchema($value, $propertySchema);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
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
