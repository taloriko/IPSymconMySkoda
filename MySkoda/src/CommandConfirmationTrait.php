<?php

declare(strict_types=1);

trait MySkodaCommandConfirmationTrait
{
    private const COMMAND_MAX_MISMATCH_RESPONSES = 2;
    private const COMMAND_MIN_MISMATCH_ROLLBACK_SECONDS = 60;

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

        if ($this->commandValuesEqual($expected, $apiValue)) {
            $this->SetValue($ident, $apiValue);
            unset($pending[$ident]);
            $this->writePendingCommands($pending);
            if ($pending === []) {
                $this->SetTimerInterval('CommandConfirmTimer', 0);
            }
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

        $mismatchCount = max(0, (int) ($entry['mismatchCount'] ?? 0)) + 1;
        $pending[$ident]['mismatchCount'] = $mismatchCount;
        $pending[$ident]['lastMismatchAt'] = time();
        $pending[$ident]['lastApiValue'] = $apiValue;

        $createdAt = (int) ($entry['createdAt'] ?? 0);
        $elapsed = $createdAt > 0 ? max(0, time() - $createdAt) : 0;
        $timedOut = $createdAt > 0 && $elapsed >= $this->commandPendingTimeoutSeconds();
        $enoughMismatches = $mismatchCount >= self::COMMAND_MAX_MISMATCH_RESPONSES
            && $elapsed >= self::COMMAND_MIN_MISMATCH_ROLLBACK_SECONDS;

        if (!$timedOut && !$enoughMismatches) {
            $this->writePendingCommands($pending);
            $this->SendDebug(
                'Pending command',
                sprintf(
                    '%s: API still reports a different value (%d/%d confirmations); optimistic value is kept.',
                    $ident,
                    $mismatchCount,
                    self::COMMAND_MAX_MISMATCH_RESPONSES
                ),
                0
            );
            return;
        }

        $this->SetValue($ident, $apiValue);
        unset($pending[$ident]);
        $this->writePendingCommands($pending);
        if ($pending === []) {
            $this->SetTimerInterval('CommandConfirmTimer', 0);
        }

        $this->WriteAttributeString(
            'CommandStatusText',
            sprintf($this->Translate('Not confirmed: %s'), $this->Translate($label))
        );
        $this->SendDebug(
            'Pending command',
            sprintf(
                '%s: command not confirmed after %d mismatching vehicle responses; API value restored.',
                $ident,
                $mismatchCount
            ),
            0
        );
    }
}
