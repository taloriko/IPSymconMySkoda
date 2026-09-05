<?php

declare(strict_types=1);

/**
 * MySkoda 1.9 notification target selection and validation.
 */
trait MySkodaNotificationV19Trait
{
    public function TestNotification(): bool
    {
        if (!$this->validateNotificationTarget(true)) {
            return false;
        }

        $notificationId = $this->ReadPropertyInteger('NotificationInstanceID');
        return $this->sendSymconNotification(
            $notificationId,
            $this->Translate('MySkoda test notification'),
            $this->Translate('Notifications from the MySkoda module are configured correctly.')
        );
    }

    private function getVisualizationModuleIds(): array
    {
        if (!function_exists('IPS_GetInstanceListByModuleType')) {
            return [];
        }

        $moduleIds = [];
        try {
            foreach ((array) @IPS_GetInstanceListByModuleType(6) as $instanceId) {
                $instance = @IPS_GetInstance((int) $instanceId);
                $moduleId = (string) ($instance['ModuleInfo']['ModuleID'] ?? '');
                if ($moduleId !== '') {
                    $moduleIds[$moduleId] = true;
                }
            }
        } catch (Throwable $e) {
            $this->SendDebug('Notification target modules', $e->getMessage(), 0);
        }

        return array_keys($moduleIds);
    }

    private function validateNotificationTarget(bool $updateForm = true): bool
    {
        $info = $this->notificationTargetInfo($this->ReadPropertyInteger('NotificationInstanceID'));

        if ($updateForm) {
            try {
                $this->UpdateFormField('NotificationTargetFeedback', 'caption', $this->notificationTargetCaption($info));
            } catch (Throwable $e) {
                // Configuration form is probably closed.
            }
        }

        return (bool) ($info['valid'] ?? false);
    }

    private function notificationTargetFeedbackCaption(): string
    {
        return $this->notificationTargetCaption(
            $this->notificationTargetInfo($this->ReadPropertyInteger('NotificationInstanceID'))
        );
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
            return [
                'valid' => false,
                'reason' => $this->Translate('Select a Tile Visualization or WebFront for notifications.')
            ];
        }

        if (!function_exists('IPS_InstanceExists') || !@IPS_InstanceExists($instanceId)) {
            return [
                'valid' => false,
                'reason' => $this->Translate('The selected notification target does not exist.')
            ];
        }

        try {
            $instance = @IPS_GetInstance($instanceId);
            $moduleInfo = is_array($instance['ModuleInfo'] ?? null) ? $instance['ModuleInfo'] : [];
            $moduleType = (int) ($moduleInfo['ModuleType'] ?? -1);
            if ($moduleType !== 6) {
                return [
                    'valid' => false,
                    'reason' => $this->Translate('The selected object is not a Symcon visualization instance.')
                ];
            }

            return [
                'valid' => true,
                'name' => function_exists('IPS_GetName') ? (string) @IPS_GetName($instanceId) : (string) $instanceId,
                'moduleName' => (string) ($moduleInfo['ModuleName'] ?? $this->Translate('Visualization')),
                'moduleId' => (string) ($moduleInfo['ModuleID'] ?? '')
            ];
        } catch (Throwable $e) {
            return [
                'valid' => false,
                'reason' => $this->Translate('The selected notification target could not be validated.')
            ];
        }
    }

    private function sendSymconNotification(int $instanceId, string $title, string $text): bool
    {
        $info = $this->notificationTargetInfo($instanceId);
        if (($info['valid'] ?? false) !== true) {
            $this->LogMessage($this->Translate('The configured notification target is not a valid visualization instance.'), KL_WARNING);
            return false;
        }

        $moduleName = strtoupper((string) ($info['moduleName'] ?? ''));
        $preferWebFront = str_contains($moduleName, 'WEBFRONT');

        $sendTile = function () use ($instanceId, $title, $text): bool {
            try {
                if (function_exists('VISU_PostNotification')) {
                    return @VISU_PostNotification($instanceId, $title, $text, 'Info', 0) !== false;
                }
            } catch (Throwable $e) {
                $this->SendDebug('Notification VISU', $e->getMessage(), 0);
            }
            return false;
        };

        $sendWebFront = function () use ($instanceId, $title, $text): bool {
            try {
                if (function_exists('WFC_PushNotification')) {
                    return @WFC_PushNotification($instanceId, $title, $text, '', 0) !== false;
                }
            } catch (Throwable $e) {
                $this->SendDebug('Notification WFC', $e->getMessage(), 0);
            }
            return false;
        };

        if ($preferWebFront) {
            if ($sendWebFront() || $sendTile()) {
                return true;
            }
        } elseif ($sendTile() || $sendWebFront()) {
            return true;
        }

        $this->LogMessage($this->Translate('Notification could not be sent. Check the selected visualization and registered mobile devices.'), KL_WARNING);
        return false;
    }
}
