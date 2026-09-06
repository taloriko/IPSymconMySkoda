<?php

declare(strict_types=1);

trait MySkodaHistoryTrait
{
    private const ARCHIVE_CONTROL_MODULE_ID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';
    private const CHARGING_HISTORY_IDENT = 'MSKODA_ChargingHistory';

    /**
     * Initializes logging and the charging chart once.
     *
     * After successful initialization the module does not touch the archive
     * or chart configuration again. User adjustments therefore remain intact.
     */
    private function initializeChargingHistory(): void
    {
        if (!$this->ReadPropertyBoolean('EnableChargingHistory')) {
            return;
        }

        if ($this->ReadAttributeBoolean('ChargingHistoryInitialized')) {
            return;
        }

        $archiveId = $this->getArchiveControlId();
        if ($archiveId <= 0) {
            $this->SendDebug('Charging history', 'Archive Control not found.', 0);
            return;
        }

        $variables = [];
        foreach (['StateOfCharge', 'TargetSOC', 'ChargePower'] as $ident) {
            $variableId = @$this->GetIDForIdent($ident);
            if ($variableId === false || !IPS_VariableExists($variableId)) {
                return;
            }
            $variables[$ident] = (int) $variableId;
        }

        foreach ($variables as $variableId) {
            if (!AC_GetLoggingStatus($archiveId, $variableId)) {
                AC_SetLoggingStatus($archiveId, $variableId, true);
            }
        }

        if (!$this->ensureChargingChart($variables)) {
            return;
        }

        $this->WriteAttributeBoolean('ChargingHistoryInitialized', true);
    }

    private function getArchiveControlId(): int
    {
        $instances = IPS_GetInstanceListByModuleID(self::ARCHIVE_CONTROL_MODULE_ID);
        return isset($instances[0]) ? (int) $instances[0] : 0;
    }

    private function ensureChargingChart(array $variables): bool
    {
        $chartsGroupId = $this->getGroupId('charts');
        if ($chartsGroupId <= 0) {
            return false;
        }

        $existing = @IPS_GetObjectIDByIdent(self::CHARGING_HISTORY_IDENT, $chartsGroupId);
        if ($existing !== false) {
            if (!IPS_MediaExists($existing)) {
                $this->LogMessage(
                    'MySkoda: charging history ident is already used by another object.',
                    KL_WARNING
                );
                return false;
            }
            return true;
        }

        $chart = [
            'datasets' => [
                ['variableID' => (int) $variables['StateOfCharge'], 'fillColor' => 'clear', 'strokeColor' => '#22c55e', 'timeOffset' => 0, 'title' => $this->Translate('State of charge'), 'axis' => 0],
                ['variableID' => (int) $variables['TargetSOC'], 'fillColor' => 'clear', 'strokeColor' => '#94a3b8', 'timeOffset' => 0, 'title' => $this->Translate('Charging limit'), 'axis' => 0],
                ['variableID' => (int) $variables['ChargePower'], 'fillColor' => 'clear', 'strokeColor' => '#f59e0b', 'timeOffset' => 0, 'title' => $this->Translate('Charging power'), 'axis' => 1]
            ],
            'type' => 'line',
            'axes' => [
                ['profile' => '~Intensity.100', 'side' => 'left'],
                ['profile' => '~Power', 'side' => 'right']
            ]
        ];

        $content = json_encode($chart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($content)) {
            return false;
        }

        $chartId = IPS_CreateMedia(4);
        IPS_SetParent($chartId, $chartsGroupId);
        IPS_SetIdent($chartId, self::CHARGING_HISTORY_IDENT);
        IPS_SetName($chartId, $this->Translate('Charging history'));
        IPS_SetIcon($chartId, 'Graph');
        IPS_SetPosition($chartId, 10);
        IPS_SetMediaFile($chartId, 'media/' . $chartId . '.chart', false);
        IPS_SetMediaContent($chartId, base64_encode($content));
        return true;
    }
}
