<?php

declare(strict_types=1);

trait MySkodaNotificationTrait
{
    private const KEY_WARNING_SECONDS = 30 * 86400;

    public function TestNotification(): bool
    {
        $notificationId = $this->ReadPropertyInteger('NotificationInstanceID');
        if ($notificationId <= 0) {
            return false;
        }
        return $this->sendSymconNotification(
            $notificationId,
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

        if (!$this->ReadPropertyBoolean('NotifyKeyExpiry')) {
            return;
        }

        $notificationId = $this->ReadPropertyInteger('NotificationInstanceID');
        if ($notificationId <= 0) {
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

        if ($this->sendSymconNotification($notificationId, $this->Translate('MySkoda API key warning'), $text)) {
            $this->WriteAttributeInteger('KeyExpiryNotifiedFor', $expiry);
        }
    }

    private function sendSymconNotification(int $instanceId, string $title, string $text): bool
    {
        try {
            if (function_exists('VISU_PostNotification')) {
                $result = @VISU_PostNotification($instanceId, $title, $text, 'Info', 0);
                if ($result !== false) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            $this->SendDebug('Notification VISU', $e->getMessage(), 0);
        }

        try {
            if (function_exists('WFC_PushNotification')) {
                $result = @WFC_PushNotification($instanceId, $title, $text, '', 0);
                if ($result !== false) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            $this->SendDebug('Notification WFC', $e->getMessage(), 0);
        }

        $this->LogMessage($this->Translate('Key expiry notification could not be sent. Check the configured visualization instance ID.'), KL_WARNING);
        return false;
    }

    private function keyExpiryDays(): ?int
    {
        $expiry = $this->ReadAttributeInteger('ApiKeyExpiresAt');
        if ($expiry <= 0) {
            return null;
        }
        return max(0, (int) ceil(($expiry - time()) / 86400));
    }
}
