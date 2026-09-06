<?php

declare(strict_types=1);

trait MySkodaNotificationTrait
{
    private const KEY_WARNING_SECONDS = 30 * 86400;

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
