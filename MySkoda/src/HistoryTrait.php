<?php

declare(strict_types=1);

trait MySkodaHistoryTrait
{
    private const ARCHIVE_CONTROL_MODULE_ID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';

    /**
     * Initializes archive logging once after explicit user opt-in.
     * Later user changes in Archive Control remain untouched.
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
            $this->SendDebug('Archive', 'Archive Control not found.', 0);
            return;
        }

        $variables = [];
        foreach (['StateOfCharge', 'TargetSOC', 'ChargePower', 'Mileage'] as $ident) {
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

        $mileageId = $variables['Mileage'];
        $reaggregate = false;

        if (AC_GetAggregationType($archiveId, $mileageId) !== 1) {
            AC_SetAggregationType($archiveId, $mileageId, 1);
            $reaggregate = true;
        }

        if (!AC_GetCounterIgnoreZeros($archiveId, $mileageId)) {
            AC_SetCounterIgnoreZeros($archiveId, $mileageId, true);
            $reaggregate = true;
        }

        if ($reaggregate) {
            AC_ReAggregateVariable($archiveId, $mileageId);
        }

        $this->WriteAttributeBoolean('ChargingHistoryInitialized', true);
    }

    private function getArchiveControlId(): int
    {
        $instances = IPS_GetInstanceListByModuleID(self::ARCHIVE_CONTROL_MODULE_ID);
        return isset($instances[0]) ? (int) $instances[0] : 0;
    }
}
