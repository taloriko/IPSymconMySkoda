<?php

declare(strict_types=1);

trait MySkodaNotificationTrait
{
    private const KEY_WARNING_SECONDS = 30 * 86400;
    private const DISABLED_VEHICLE_SERVICES = [
        'VEHICLE_STATUS_DISABLED' => 'Vehicle status (doors, windows and locking)',
        'ODOMETER_DISABLED' => 'Odometer reading',
        'PARKING_POSITION_DISABLED' => 'Parking position and address'
    ];

    public function TestNotification(): bool
    {
        if (!$this->validateNotificationTarget(true)) {
            return false;
        }

        return $this->sendSymconNotification(
            $this->ReadPropertyInteger('NotificationInstanceID'),
            $this->Translate('MySkoda test notification'),
            $this->Translate('Notifications from the MySkoda module are configured correctly.')
        );
    }

    private function updateKeyExpiryWarning(): void
    {
        $expiry = $this->ReadAttributeInteger('ApiKeyExpiresAt');
        $warning = $expiry > 0 && ($expiry - time()) <= self::KEY_WARNING_SECONDS;

        if (@$this->GetIDForIdent('ApiKeyWarning') !== false) {
            $this->SetValue('ApiKeyWarning', $warning);
        }

        if (!$warning) {
            if ($expiry > time() + self::KEY_WARNING_SECONDS) {
                $this->WriteAttributeInteger('KeyExpiryNotifiedFor', 0);
                $this->WriteAttributeInteger('KeyExpiryNotificationLastAttempt', 0);
            }
            return;
        }

        if (!$this->ReadPropertyBoolean('NotifyKeyExpiry') || !$this->validateNotificationTarget(false)) {
            return;
        }
        if ($this->ReadAttributeInteger('KeyExpiryNotifiedFor') === $expiry) {
            return;
        }

        $lastAttempt = $this->ReadAttributeInteger('KeyExpiryNotificationLastAttempt');
        if ($lastAttempt > 0 && (time() - $lastAttempt) < 86400) {
            return;
        }

        $this->WriteAttributeInteger('KeyExpiryNotificationLastAttempt', time());
        $days = max(0, (int) ceil(($expiry - time()) / 86400));
        $text = sprintf($this->Translate('The MySkoda API key expires in %d days. Please create a new key in the MySkoda app.'), $days);

        if ($this->sendSymconNotification(
            $this->ReadPropertyInteger('NotificationInstanceID'),
            $this->Translate('MySkoda API key warning'),
            $text
        )) {
            $this->WriteAttributeInteger('KeyExpiryNotifiedFor', $expiry);
        }
    }

    /**
     * The Public API can return a valid vehicle response with partial errors.
     * These codes mean Škoda has disabled the corresponding services, not
     * that the local API key itself has expired.
     */
    private function disabledVehicleServiceCodes(array $envelope): array
    {
        $errors = $envelope['errors'] ?? [];
        if (!is_array($errors)) {
            return [];
        }

        $received = [];
        foreach ($errors as $error) {
            if (!is_array($error) || !is_string($error['type'] ?? null)) {
                continue;
            }
            $type = strtoupper(trim($error['type']));
            if (array_key_exists($type, self::DISABLED_VEHICLE_SERVICES)) {
                $received[$type] = true;
            }
        }

        // Stable order is important for one-time notification deduplication.
        return array_values(array_filter(
            array_keys(self::DISABLED_VEHICLE_SERVICES),
            static fn (string $code): bool => isset($received[$code])
        ));
    }

    private function disabledVehicleServiceNoticeText(array $codes): string
    {
        $names = [];
        foreach ($codes as $code) {
            if (isset(self::DISABLED_VEHICLE_SERVICES[$code])) {
                $names[] = $this->Translate(self::DISABLED_VEHICLE_SERVICES[$code]);
            }
        }
        return sprintf(
            $this->Translate('Skoda has disabled these vehicle services: %s. The affected information is not being updated; existing values may be outdated. A possible reason is an expired Car Connect remote access service.'),
            implode(', ', $names)
        );
    }

    /**
     * Use only the last successfully fetched response for the configured VIN.
     * A VIN change must not show the previous vehicle's warning.
     */
    private function disabledVehicleServiceWarningCaption(): string
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw)) {
            return '';
        }

        $configuredVin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $receivedVin = strtoupper(trim((string) ($raw['vehicle']['vin'] ?? '')));
        if ($configuredVin === '' || $receivedVin !== $configuredVin) {
            return '';
        }

        $codes = $this->disabledVehicleServiceCodes($raw);
        return $codes === [] ? '' : '⚠️ ' . $this->disabledVehicleServiceNoticeText($codes);
    }

    /**
     * A successful API response is required before clearing a previous alert.
     * The message is sent once per disabled-service combination, and a failed
     * delivery is retried no more often than once per day.
     */
    private function updateDisabledVehicleServices(array $envelope): void
    {
        $codes = $this->disabledVehicleServiceCodes($envelope);
        $signature = implode('|', $codes);
        if ($signature === '') {
            $this->WriteAttributeString('DisabledServicesNotifiedSignature', '');
            $this->WriteAttributeString('DisabledServicesLastAttemptSignature', '');
            $this->WriteAttributeInteger('DisabledServicesLastAttemptAt', 0);
            return;
        }

        // Service availability alerts use the configured visualization. The
        // API-key expiry checkbox must not suppress unrelated service alerts.
        if (!$this->validateNotificationTarget(false)) {
            return;
        }
        if ($this->ReadAttributeString('DisabledServicesNotifiedSignature') === $signature) {
            return;
        }

        $lastAttemptSignature = $this->ReadAttributeString('DisabledServicesLastAttemptSignature');
        $lastAttemptAt = $this->ReadAttributeInteger('DisabledServicesLastAttemptAt');
        if ($lastAttemptSignature === $signature && $lastAttemptAt > 0 && time() - $lastAttemptAt < 86400) {
            return;
        }

        $this->WriteAttributeString('DisabledServicesLastAttemptSignature', $signature);
        $this->WriteAttributeInteger('DisabledServicesLastAttemptAt', time());

        if ($this->sendSymconNotification(
            $this->ReadPropertyInteger('NotificationInstanceID'),
            $this->Translate('MySkoda: Vehicle services disabled'),
            $this->disabledVehicleServiceNoticeText($codes)
        )) {
            $this->WriteAttributeString('DisabledServicesNotifiedSignature', $signature);
        }
    }

    /**
     * Recheck the last valid response after module/configuration updates.
     * The API may already have reported disabled services before this
     * feature or notification target was configured. Never send a warning
     * for cached data belonging to a different configured vehicle.
     */
    private function updateDisabledVehicleServicesFromStoredData(): void
    {
        $raw = json_decode($this->ReadAttributeString('RawData'), true);
        if (!is_array($raw) || !is_array($raw['vehicle'] ?? null)) {
            return;
        }

        $configuredVin = strtoupper(trim($this->ReadPropertyString('VIN')));
        $responseVin = strtoupper(trim((string) ($raw['vehicle']['vin'] ?? '')));
        if ($configuredVin === '' || $responseVin !== $configuredVin) {
            return;
        }

        $this->updateDisabledVehicleServices($raw);
    }

    private function getVisualizationModuleIds(): array
    {
        if (!function_exists('IPS_GetInstanceListByModuleType')) {
            return [];
        }

        $moduleIds = [];
        foreach ((array) @IPS_GetInstanceListByModuleType(6) as $instanceId) {
            $instance = @IPS_GetInstance((int) $instanceId);
            $moduleId = (string) ($instance['ModuleInfo']['ModuleID'] ?? '');
            if ($moduleId !== '') {
                $moduleIds[$moduleId] = true;
            }
        }
        return array_keys($moduleIds);
    }

    private function validateNotificationTarget(bool $updateForm): bool
    {
        $info = $this->notificationTargetInfo($this->ReadPropertyInteger('NotificationInstanceID'));
        if ($updateForm) {
            try {
                $this->UpdateFormField('NotificationTargetFeedback', 'caption', $this->notificationTargetCaption($info));
            } catch (Throwable) {
            }
        }
        return (bool) ($info['valid'] ?? false);
    }

    private function notificationTargetFeedbackCaption(): string
    {
        return $this->notificationTargetCaption($this->notificationTargetInfo($this->ReadPropertyInteger('NotificationInstanceID')));
    }

    private function notificationTargetCaption(array $info): string
    {
        if (($info['valid'] ?? false) === true) {
            return '✅ ' . sprintf(
                $this->Translate('Notification target: %s (%s)'),
                (string) ($info['name'] ?? ''),
                (string) ($info['moduleName'] ?? $this->Translate('Visualization'))
            );
        }

        $reason = trim((string) ($info['reason'] ?? ''));
        if ($reason === '') {
            $reason = $this->Translate('Select a Tile Visualization or WebFront for notifications.');
        }
        return 'ℹ️ ' . $reason;
    }

    private function notificationTargetInfo(int $instanceId): array
    {
        if ($instanceId <= 0) {
            return ['valid' => false, 'reason' => $this->Translate('Select a Tile Visualization or WebFront for notifications.')];
        }
        if (!function_exists('IPS_InstanceExists') || !@IPS_InstanceExists($instanceId)) {
            return ['valid' => false, 'reason' => $this->Translate('The selected notification target does not exist.')];
        }

        $instance = @IPS_GetInstance($instanceId);
        $moduleInfo = is_array($instance['ModuleInfo'] ?? null) ? $instance['ModuleInfo'] : [];
        if ((int) ($moduleInfo['ModuleType'] ?? -1) !== 6) {
            return ['valid' => false, 'reason' => $this->Translate('The selected object is not a Symcon visualization instance.')];
        }

        return [
            'valid' => true,
            'name' => function_exists('IPS_GetName') ? (string) @IPS_GetName($instanceId) : (string) $instanceId,
            'moduleName' => (string) ($moduleInfo['ModuleName'] ?? $this->Translate('Visualization'))
        ];
    }

    private function sendSymconNotification(int $instanceId, string $title, string $text): bool
    {
        if (($this->notificationTargetInfo($instanceId)['valid'] ?? false) !== true) {
            $this->LogMessage($this->Translate('The configured notification target is not a valid visualization instance.'), KL_WARNING);
            return false;
        }

        try {
            if (function_exists('VISU_PostNotification') && @VISU_PostNotification($instanceId, $title, $text, 'Info', 0) !== false) {
                return true;
            }
        } catch (Throwable $e) {
            $this->SendDebug('Notification VISU', $e->getMessage(), 0);
        }

        try {
            if (function_exists('WFC_PushNotification') && @WFC_PushNotification($instanceId, $title, $text, '', 0) !== false) {
                return true;
            }
        } catch (Throwable $e) {
            $this->SendDebug('Notification WFC', $e->getMessage(), 0);
        }

        $this->LogMessage($this->Translate('Notification could not be sent. Check the selected visualization and registered mobile devices.'), KL_WARNING);
        return false;
    }
}
