<?php

declare(strict_types=1);

trait MySkodaCommandConfirmationTrait
{
    private function applyApiValue(string $ident, mixed $apiValue): void
    {
        $pending = $this->readPendingCommands();
        if (!isset($pending[$ident]) || !is_array($pending[$ident])) {
            $this->SetValue($ident, $apiValue);
            return;
        }

        $entry = $pending[$ident];
        $expected = $entry['expected'] ?? null;
        $label = (string) ($entry['label'] ?? $ident);
        $matches = $this->commandValuesEqual($expected, $apiValue);

        $this->SetValue($ident, $apiValue);
        unset($pending[$ident]);
        $this->writePendingCommands($pending);
        if ($pending === []) {
            $this->SetTimerInterval('CommandConfirmTimer', 0);
        }

        if ($matches) {
            $this->WriteAttributeString(
                'CommandStatusText',
                sprintf($this->Translate('Confirmed: %s'), $this->Translate($label))
            );
            $this->SendDebug(
                'Pending command',
                $ident . ': API confirmed the requested value.',
                0
            );
            return;
        }

        $this->WriteAttributeString(
            'CommandStatusText',
            sprintf($this->Translate('Portal value applied: %s'), $this->Translate($label))
        );
        $this->SendDebug(
            'Pending command',
            $ident . ': API returned a different value; portal value is authoritative and was restored.',
            0
        );
    }

    private function sendCommand(string $method, string $path, ?array $body): bool
    {
        $this->WriteAttributeString('LastCommandResult', 'sending');

        if (!$this->ReadPropertyBoolean('EnableRemote')) {
            $this->WriteAttributeString(
                'LastError',
                $this->Translate('Remote control is disabled in this instance.')
            );
            $this->WriteAttributeString('LastCommandResult', 'rejected');
            return false;
        }

        if (!$this->canRequest(true)) {
            $this->WriteAttributeString(
                'LastError',
                $this->Translate('MySkoda rate limit / waiting period is active.')
            );
            $this->WriteAttributeString('LastCommandResult', 'rejected');
            $this->SetStatus(203);
            return false;
        }

        $response = $this->request($method, $path, $body);
        $this->absorbHeaders($response['headers']);

        if (!$response['ok']) {
            $status = (int) ($response['status'] ?? 0);
            $curlError = (string) ($response['curlError'] ?? '');
            $uncertain = $curlError !== '' || $status === 0;
            $this->WriteAttributeString('LastCommandResult', $uncertain ? 'uncertain' : 'rejected');
            $this->setApiError($response);
            return false;
        }

        $this->WriteAttributeString('LastCommandResult', 'accepted');
        $this->WriteAttributeString('LastError', '');
        $this->SetStatus(102);
        return true;
    }
}
