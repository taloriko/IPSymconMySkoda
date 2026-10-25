<?php

declare(strict_types=1);

trait MySkodaCoreTrait
{
    public function Create(): void
    {
        parent::Create();

        // Configuration
        $this->RegisterPropertyString('VIN', '');
        $this->RegisterPropertyString('APIToken', '');
        $this->RegisterPropertyInteger('Interval', 300);
        $this->RegisterPropertyBoolean('EnableRemote', true);
        $this->RegisterPropertyBoolean('ClimateWithoutExternalPower', true);
        $this->RegisterPropertyString('SPIN', '');
        $this->RegisterPropertyBoolean('ShowDetails', false);
        $this->RegisterPropertyBoolean('ShowChargingLocations', false);
        $this->RegisterPropertyBoolean('EnableChargingHistory', false);
        $this->RegisterPropertyBoolean('NotifyKeyExpiry', false);
        $this->RegisterPropertyBoolean('NotifyDisabledVehicleServices', false);
        $this->RegisterPropertyInteger('NotificationInstanceID', 0);

        // Runtime state
        $this->RegisterAttributeString('RawData', '');
        $this->RegisterAttributeString('LastVehicleResponseRaw', '');
        $this->RegisterAttributeString('OpenApiOperations', '');
        $this->RegisterAttributeInteger('OpenApiUpdatedAt', 0);
        $this->RegisterAttributeString('AvailableChargeModes', '[]');
        $this->RegisterAttributeInteger('RateLimitLimit', -1);
        $this->RegisterAttributeInteger('RateLimitRemaining', -1);
        $this->RegisterAttributeInteger('RateLimitResetAt', 0);
        $this->RegisterAttributeInteger('BlockedUntil', 0);
        $this->RegisterAttributeInteger('ApiKeyExpiresAt', 0);
        $this->RegisterAttributeString('LastError', '');
        $this->RegisterAttributeString('ConnectionState', 'not_configured');
        $this->RegisterAttributeString('ConnectionMessage', '');
        $this->RegisterAttributeString('ConfigFingerprint', '');
        $this->RegisterAttributeString('VehicleImageVariantsFingerprint', '');
        $this->RegisterAttributeInteger('KeyExpiryNotifiedFor', 0);
        $this->RegisterAttributeInteger('KeyExpiryNotificationLastAttempt', 0);
        $this->RegisterAttributeString('DisabledServicesNotifiedSignature', '');
        $this->RegisterAttributeString('DisabledServicesLastAttemptSignature', '');
        $this->RegisterAttributeInteger('DisabledServicesLastAttemptAt', 0);
        $this->RegisterAttributeBoolean('DisabledServicesNotificationSent', false);
        $this->RegisterAttributeString('DisabledServicesPendingRecovery', '');
        $this->RegisterAttributeInteger('DisabledServicesNotificationLastAttempt', 0);
        $this->RegisterAttributeInteger('DisabledServicesNotificationSentAt', 0);
        $this->RegisterAttributeBoolean('ChargingHistoryInitialized', false);

        $this->RegisterTimer('UpdateTimer', 0, 'MSKODA_Update($_IPS[\'TARGET\']);');
        $this->SetVisualizationType(0);
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        $this->SetVisualizationType(0);

        $vin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $token = trim($this->ReadPropertyString('APIToken'));
        $interval = max(180, $this->ReadPropertyInteger('Interval'));

        $this->SetSummary($vin);
        $this->registerVariables();
        $this->applyActions();
        $this->initializeChargingHistory();

        if (!$this->isVinValid($vin) || $token === '') {
            $this->SetTimerInterval('UpdateTimer', 0);
            $this->WriteAttributeInteger('ApiKeyExpiresAt', 0);
            $this->WriteAttributeInteger('KeyExpiryNotifiedFor', 0);
            $this->WriteAttributeInteger('KeyExpiryNotificationLastAttempt', 0);
            $this->WriteAttributeString('DisabledServicesNotifiedSignature', '');
            $this->WriteAttributeString('DisabledServicesLastAttemptSignature', '');
            $this->WriteAttributeInteger('DisabledServicesLastAttemptAt', 0);
            $this->WriteAttributeBoolean('DisabledServicesNotificationSent', false);
            $this->WriteAttributeString('DisabledServicesPendingRecovery', '');
            $this->WriteAttributeInteger('DisabledServicesNotificationLastAttempt', 0);
            $this->WriteAttributeInteger('DisabledServicesNotificationSentAt', 0);
            $this->updateKeyExpiryWarning();
            $this->setConnectionFeedback(
                'not_configured',
                $this->Translate('Enter VIN and API token, then apply the configuration.')
            );
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
            $this->WriteAttributeString('DisabledServicesNotifiedSignature', '');
            $this->WriteAttributeString('DisabledServicesLastAttemptSignature', '');
            $this->WriteAttributeInteger('DisabledServicesLastAttemptAt', 0);
            $this->WriteAttributeBoolean('DisabledServicesNotificationSent', false);
            $this->WriteAttributeString('DisabledServicesPendingRecovery', '');
            $this->WriteAttributeInteger('DisabledServicesNotificationLastAttempt', 0);
            $this->WriteAttributeInteger('DisabledServicesNotificationSentAt', 0);
        }

        $this->updateKeyExpiryWarning();
        $this->validateNotificationTarget(false);

        // Also notify about previously received disabled-service errors on
        // upgrades or when a notification destination is configured later.
        if (!$configurationChanged) {
            $this->updateDisabledVehicleServicesFromStoredData();
        }

        if ($configurationChanged || $this->ReadAttributeString('RawData') === '') {
            $this->setConnectionFeedback(
                'checking',
                $this->Translate('Checking connection to MySkoda...')
            );
            $this->refreshVehicleData(true);
            return;
        }

        $this->SetStatus(102);
        $this->refreshConnectionForm();
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
            $form['elements'] = $this->prependVehicleConfigurationHeader($form['elements']);
            $this->prepareConfigurationElements(
                $form['elements'],
                $connectionCaption,
                $notificationCaption,
                $validVisualizationModules
            );
        }

        if (isset($form['actions']) && is_array($form['actions'])) {
            $this->prepareConfigurationActions(
                $form['actions'],
                $this->instanceActionsAvailable()
            );
        }

        return json_encode($form, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function GetLastVehicleResponseRaw(): string
    {
        return $this->ReadAttributeString('LastVehicleResponseRaw');
    }

    public function GetChargingProfiles(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $profiles = $this->path($raw, 'vehicle.chargingProfiles', []);

        return json_encode(
            $profiles,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    public function GetRemoteOperations(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $operations = $this->path(
            $raw,
            'vehicle.operations',
            $this->path($raw, 'vehicle.remoteOperations', [])
        );

        return json_encode(
            $operations,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    private function fetchVehicle(bool $userAction): bool
    {
        if (!$this->configurationValid()) {
            $this->setConnectionFeedback(
                'not_configured',
                $this->Translate('Enter VIN and API token, then apply the configuration.')
            );
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
        $response = $this->request(
            'GET',
            '/api/v1/vehicles/' . rawurlencode($vin)
        );

        $this->WriteAttributeString(
            'LastVehicleResponseRaw',
            (string) ($response['raw'] ?? '')
        );
        $this->absorbHeaders($response['headers']);

        if (!$response['ok'] || !is_array($response['json'])) {
            $this->setApiError($response);
            $this->setConnectionFeedback('error', $this->ReadAttributeString('LastError'));
            return false;
        }

        $envelope = $response['json'];
        $vehicle = isset($envelope['vehicle']) && is_array($envelope['vehicle'])
            ? $envelope['vehicle']
            : [];

        if ($vehicle === []) {
            $message = $this->Translate('Vehicle data is missing in the API response.');
            $this->WriteAttributeString('LastError', $message);
            $this->setConnectionFeedback('error', $message);
            $this->SetStatus(202);
            return false;
        }

        $this->WriteAttributeString(
            'RawData',
            json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // Refresh core values before optional API data. An unsupported dynamic
        // variable must not prevent odometer, location or status updates.
        $this->ensureVehicleVariables($vehicle);
        $this->applyActions();
        $this->initializeChargingHistory();
        $this->updateCoreValues($vehicle);
        $this->updateDetailValues($vehicle, $envelope);
        $this->SetValue('LastUpdate', time());

        $this->ensurePublicApiVariables($vehicle);
        $this->WriteAttributeString('LastError', '');
        $this->updateKeyExpiryWarning();
        $this->updateDisabledVehicleServices($envelope);
        $this->setConnectionFeedback(
            'success',
            $this->Translate('Connection successful. Vehicle data was received from MySkoda.')
        );
        $this->SetStatus(102);

        return true;
    }

    /**
     * Uses existing, vehicle-matched media only; opening the configuration
     * never downloads images or creates new media objects.
     */
    private function prependVehicleConfigurationHeader(array $elements): array
    {
        // The instance name belongs to this individual Symcon instance.
        $instanceName = trim((string) IPS_GetName($this->InstanceID));
        if ($instanceName === '') {
            $instanceName = (string) $this->InstanceID;
        }

        $caption = 'MySkoda - ' . $instanceName;
        $configuredVIN = strtoupper(trim($this->ReadPropertyString('VIN')));
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $vehicle = is_array($raw) && is_array($raw['vehicle'] ?? null) ? $raw['vehicle'] : [];
        $receivedVIN = strtoupper(trim((string) ($vehicle['vin'] ?? '')));
        if ($configuredVIN !== '' && $configuredVIN === $receivedVIN) {
            $vehicleName = trim((string) ($vehicle['name'] ?? ''));
            $licensePlate = trim((string) ($vehicle['licensePlate'] ?? ''));

            if ($vehicleName !== '' && strcasecmp($vehicleName, $instanceName) !== 0) {
                $caption .= ' | ' . $vehicleName;
            }
            if ($licensePlate !== '') {
                $caption .= ' | ' . $licensePlate;
            }
        }

        $header = [];
        $mediaId = $this->vehicleConfigurationMediaId();
        if ($mediaId !== false) {
            // Keep the image left-aligned above the header label.
            $header[] = [
                'type' => 'Image',
                'name' => 'VehicleConfigurationImage',
                'mediaID' => $mediaId,
                'center' => false,
                'width' => '240px'
            ];
        }
        $header[] = ['type' => 'Label', 'caption' => $caption, 'bold' => true];

        array_unshift($elements, ...$header);
        return $elements;
    }

    private function vehicleConfigurationMediaId(): int|false
    {
        $idents = [self::VEHICLE_IMAGE_IDENT];
        foreach (self::VEHICLE_IMAGE_VARIANTS as $variant) {
            $idents[] = (string) $variant['ident'];
        }

        foreach ($idents as $ident) {
            $mediaId = $this->vehicleImageMediaId($ident);
            if ($mediaId !== false && $this->vehicleImageBelongsToCurrentVehicle($mediaId)) {
                return $mediaId;
            }
        }
        return false;
    }

    private function chargingLocationVariableEstimateCaption(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        $vehicle = is_array($raw) && is_array($raw['vehicle'] ?? null) ? $raw['vehicle'] : [];
        $profiles = $this->path($vehicle, 'chargingProfiles.profiles', null);
        if (!is_array($profiles)) {
            return $this->Translate('No charging locations have been received yet; variable count is unknown.');
        }

        $ids = [];
        foreach ($profiles as $profile) {
            if (is_array($profile) && $this->validChargingProfileId($profile['id'] ?? null)) {
                $ids[(string) (int) $profile['id']] = true;
            }
        }

        $scalars = $this->flattenChargingProfileScalars($vehicle);
        $missing = 0;
        foreach ($scalars as $path => $value) {
            if (str_ends_with($path, '.carCapturedTimestamp')) {
                continue;
            }
            if (@$this->GetIDForIdent($this->dynamicApiIdent($path)) === false) {
                $missing++;
            }
        }

        return sprintf(
            $this->Translate('Last API response: %d locations, %d possible variables, %d not yet created.'),
            count($ids),
            count($scalars),
            $missing
        );
    }

    private function optionalVariableOverview(): array
    {
        return [
            [
                'Area' => $this->Translate('Diagnosis and API status'),
                'Quantity' => '5-7',
                'Content' => $this->Translate('API key, quota, partial errors, remote commands')
            ],
            [
                'Area' => $this->Translate('Saved charging locations'),
                'Quantity' => $this->Translate('approx. 1-40 per location'),
                'Content' => $this->Translate('Name, charge settings, preferred times, timers')
            ],
            [
                'Area' => $this->Translate('VIN information'),
                'Quantity' => '17',
                'Content' => $this->Translate('Locally decoded vehicle information')
            ]
        ];
    }

    private function prepareConfigurationElements(
        array &$elements,
        string $connectionCaption,
        string $notificationCaption,
        array $validVisualizationModules
    ): void {
        foreach ($elements as &$element) {
            if (!is_array($element)) {
                continue;
            }

            $name = (string) ($element['name'] ?? '');
            if ($name === 'ConnectionFeedback') {
                $element['caption'] = $connectionCaption;
            } elseif ($name === 'DisabledVehicleServicesWarning') {
                $warning = $this->disabledVehicleServiceWarningCaption();
                $element['caption'] = $warning;
                $element['visible'] = $warning !== '';
            } elseif ($name === 'NotificationInstanceID') {
                $element['validModules'] = $validVisualizationModules;
            } elseif ($name === 'NotificationTargetFeedback') {
                $element['caption'] = $notificationCaption;
            } elseif ($name === 'ChargingLocationVariableEstimate') {
                $element['caption'] = $this->chargingLocationVariableEstimateCaption();
            } elseif (($element['type'] ?? '') === 'List'
                && ($element['caption'] ?? '') === 'Optional variable overview') {
                $element['values'] = $this->optionalVariableOverview();
            } elseif ($name === 'ClimateWithoutExternalPower') {
                $element['visible'] = $this->vehicleProvidesValue('airConditioning.airConditioningWithoutExternalPower');
            } elseif ($name === 'SPIN') {
                $element['visible'] = $this->vehicleProvidesValue('auxiliaryHeating.state')
                    || $this->vehicleOperationAvailable('startAuxiliaryHeating');
            }

            if (isset($element['items']) && is_array($element['items'])) {
                $this->prepareConfigurationElements(
                    $element['items'],
                    $connectionCaption,
                    $notificationCaption,
                    $validVisualizationModules
                );
            }
        }
        unset($element);
    }

    private function vehicleProvidesValue(string $path): bool
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return false;
        }
        $vehicle = isset($raw['vehicle']) && is_array($raw['vehicle']) ? $raw['vehicle'] : [];
        return $vehicle !== [] && $this->pathHasValue($vehicle, $path);
    }

    private function prepareConfigurationActions(array &$actions, bool $enabled): void
    {
        foreach ($actions as &$action) {
            if (!is_array($action)) {
                continue;
            }

            if ((string) ($action['name'] ?? '') === 'UpdateNowButton') {
                $action['enabled'] = $enabled;
            }

            if (isset($action['items']) && is_array($action['items'])) {
                $this->prepareConfigurationActions($action['items'], $enabled);
            }
        }
        unset($action);
    }

    private function instanceActionsAvailable(): bool
    {
        return $this->configurationValid()
            && $this->ReadAttributeString('ConnectionState') === 'success'
            && trim($this->ReadAttributeString('RawData')) !== '';
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
            $this->UpdateFormField(
                'ConnectionFeedback',
                'caption',
                $this->connectionFeedbackCaption()
            );

            $this->UpdateFormField(
                'UpdateNowButton',
                'enabled',
                $this->instanceActionsAvailable()
            );
            $warning = $this->disabledVehicleServiceWarningCaption();
            $this->UpdateFormField('DisabledVehicleServicesWarning', 'caption', $warning);
            $this->UpdateFormField('DisabledVehicleServicesWarning', 'visible', $warning !== '');
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
}
