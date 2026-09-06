<?php

declare(strict_types=1);

trait MySkodaStructureTrait
{
    private const DUMMY_MODULE_ID = '{485D0419-BE97-4548-AA9C-C083EB82E61E}';
    private const ARCHIVE_CONTROL_MODULE_ID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';

    private const GROUPS = [
        'vehicle' => ['ident' => 'MSKODA_GroupVehicle', 'name' => 'Vehicle', 'icon' => 'Car', 'position' => 100],
        'status' => ['ident' => 'MSKODA_GroupStatus', 'name' => 'Vehicle status', 'icon' => 'Lock', 'position' => 200],
        'charging' => ['ident' => 'MSKODA_GroupCharging', 'name' => 'Charging', 'icon' => 'Electricity', 'position' => 300],
        'climate' => ['ident' => 'MSKODA_GroupClimate', 'name' => 'Air conditioning', 'icon' => 'Temperature', 'position' => 400],
        'location' => ['ident' => 'MSKODA_GroupLocation', 'name' => 'Location', 'icon' => 'Location', 'position' => 500],
        'diagnostics' => ['ident' => 'MSKODA_GroupDiagnostics', 'name' => 'API and diagnostics', 'icon' => 'Gear', 'position' => 600],
        'lastUpdate' => ['ident' => 'MSKODA_GroupLastUpdate', 'name' => 'Last update', 'icon' => 'Clock', 'position' => 700]
    ];

    private const VARIABLE_GROUPS = [
        'VehicleName' => 'vehicle',
        'LicensePlate' => 'vehicle',
        'StateOfCharge' => 'vehicle',
        'Range' => 'vehicle',
        'Mileage' => 'vehicle',

        'Locked' => 'status',
        'DoorsOpen' => 'status',
        'WindowsOpen' => 'status',
        'TrunkOpen' => 'status',
        'BonnetOpen' => 'status',
        'SunroofOpen' => 'status',
        'LightsOn' => 'status',
        'ParkingState' => 'status',

        'Charging' => 'charging',
        'ChargingState' => 'charging',
        'ChargeType' => 'charging',
        'ChargePower' => 'charging',
        'TargetSOC' => 'charging',
        'ChargeMode' => 'charging',
        'FullyChargedAt' => 'charging',

        'Climate' => 'climate',
        'TargetTemperature' => 'climate',

        'Latitude' => 'location',
        'Longitude' => 'location',

        'ApiKeyWarning' => 'diagnostics',
        'ApiKeyExpiresAtVar' => 'diagnostics',
        'RequestsRemaining' => 'diagnostics',
        'PartialErrors' => 'diagnostics',

        'LastUpdate' => 'lastUpdate'
    ];

    /**
     * Status variables must be direct children while RegisterVariable* and
     * MaintainAction are executed. Existing variables are therefore moved to
     * the instance root before registration and grouped again afterwards.
     * Their object IDs and idents remain unchanged.
     */
    private function prepareManagedVariablesForRegistration(): void
    {
        foreach (array_keys(self::VARIABLE_GROUPS) as $ident) {
            $this->moveManagedVariableToRegistrationRoot($ident);
        }
    }

    private function organizeManagedObjects(): void
    {
        $groups = [];
        foreach (self::GROUPS as $key => $spec) {
            $groups[$key] = $this->ensureDummy(
                $this->InstanceID,
                (string) $spec['ident'],
                $this->Translate((string) $spec['name']),
                (string) $spec['icon'],
                (int) $spec['position']
            );
        }

        foreach (self::VARIABLE_GROUPS as $ident => $groupKey) {
            $variableId = $this->findVariableByIdent($ident);
            $groupId = (int) ($groups[$groupKey] ?? 0);
            if ($variableId <= 0 || $groupId <= 0) {
                continue;
            }
            if (IPS_GetParent($variableId) !== $groupId) {
                IPS_SetParent($variableId, $groupId);
            }
        }

        if ($this->ReadPropertyBoolean('EnableChargingHistory')) {
            $chargingGroup = (int) ($groups['charging'] ?? 0);
            if ($chargingGroup > 0) {
                $this->ensureChargingHistory($chargingGroup);
            }
        }
    }

    private function moveManagedVariableToRegistrationRoot(string $ident): void
    {
        $variableId = $this->findVariableByIdent($ident);
        if ($variableId <= 0) {
            return;
        }
        if (IPS_GetParent($variableId) !== $this->InstanceID) {
            IPS_SetParent($variableId, $this->InstanceID);
        }
    }

    private function placeManagedVariable(string $ident): void
    {
        $groupKey = self::VARIABLE_GROUPS[$ident] ?? null;
        if (!is_string($groupKey) || !isset(self::GROUPS[$groupKey])) {
            return;
        }

        $spec = self::GROUPS[$groupKey];
        $groupId = $this->ensureDummy(
            $this->InstanceID,
            (string) $spec['ident'],
            $this->Translate((string) $spec['name']),
            (string) $spec['icon'],
            (int) $spec['position']
        );
        $variableId = $this->findVariableByIdent($ident);
        if ($groupId > 0 && $variableId > 0 && IPS_GetParent($variableId) !== $groupId) {
            IPS_SetParent($variableId, $groupId);
        }
    }

    protected function GetIDForIdent(string $Ident): int|false
    {
        $direct = @parent::GetIDForIdent($Ident);
        if ($direct !== false) {
            return $direct;
        }

        $found = $this->findObjectByIdentRecursive($this->InstanceID, $Ident, 0);
        return $found > 0 ? $found : false;
    }

    protected function SetValue(string $Ident, mixed $Value): bool
    {
        $id = $this->GetIDForIdent($Ident);
        if ($id === false || !IPS_VariableExists($id)) {
            return false;
        }
        SetValue($id, $Value);
        return true;
    }

    protected function GetValue(string $Ident): mixed
    {
        $id = $this->GetIDForIdent($Ident);
        if ($id === false || !IPS_VariableExists($id)) {
            return null;
        }
        return GetValue($id);
    }

    private function findVariableByIdent(string $ident): int
    {
        $id = $this->GetIDForIdent($ident);
        return $id !== false && IPS_VariableExists($id) ? $id : 0;
    }

    private function findObjectByIdentRecursive(int $parentId, string $ident, int $depth): int
    {
        if ($depth > 4 || !IPS_ObjectExists($parentId)) {
            return 0;
        }

        foreach (IPS_GetChildrenIDs($parentId) as $childId) {
            $object = IPS_GetObject($childId);
            if ((string) ($object['ObjectIdent'] ?? '') === $ident) {
                return (int) $childId;
            }
        }

        foreach (IPS_GetChildrenIDs($parentId) as $childId) {
            $object = IPS_GetObject($childId);
            $type = (int) ($object['ObjectType'] ?? -1);
            if (!in_array($type, [0, 1, 3], true)) {
                continue;
            }
            $found = $this->findObjectByIdentRecursive((int) $childId, $ident, $depth + 1);
            if ($found > 0) {
                return $found;
            }
        }

        return 0;
    }

    private function ensureDummy(int $parentId, string $ident, string $name, string $icon, int $position): int
    {
        $existing = @IPS_GetObjectIDByIdent($ident, $parentId);
        if ($existing !== false && $this->isDummyInstance((int) $existing)) {
            return (int) $existing;
        }

        $recursive = $this->findObjectByIdentRecursive($this->InstanceID, $ident, 0);
        if ($recursive > 0 && $this->isDummyInstance($recursive)) {
            return $recursive;
        }

        if ($existing !== false) {
            $this->SendDebug('Structure', 'Ident ' . $ident . ' is already used by a non-dummy object.', 0);
            return 0;
        }

        $id = IPS_CreateInstance(self::DUMMY_MODULE_ID);
        IPS_SetParent($id, $parentId);
        IPS_SetIdent($id, $ident);
        IPS_SetName($id, $name);
        IPS_SetIcon($id, $icon);
        IPS_SetPosition($id, $position);
        return $id;
    }

    private function isDummyInstance(int $id): bool
    {
        if (!IPS_InstanceExists($id)) {
            return false;
        }
        $instance = IPS_GetInstance($id);
        return (string) ($instance['ModuleInfo']['ModuleID'] ?? '') === self::DUMMY_MODULE_ID;
    }

    private function ensureChargingHistory(int $chargingGroup): void
    {
        $chartGroup = $this->ensureDummy(
            $chargingGroup,
            'MSKODA_GroupCharts',
            $this->Translate('Charts'),
            'Graph',
            900
        );
        if ($chartGroup <= 0) {
            return;
        }

        $archiveId = $this->getArchiveControlId();
        if ($archiveId <= 0) {
            $this->SendDebug('Charging history', 'Archive Control not found.', 0);
            return;
        }

        $variableIds = [];
        foreach (['StateOfCharge', 'TargetSOC', 'ChargePower'] as $ident) {
            $variableId = $this->findVariableByIdent($ident);
            if ($variableId <= 0) {
                continue;
            }
            $variableIds[$ident] = $variableId;
            $this->enableLoggingIfMissing($archiveId, $variableId);
        }

        if (count($variableIds) !== 3) {
            return;
        }
        $this->ensureChargingChart($chartGroup, $variableIds);
    }

    private function getArchiveControlId(): int
    {
        $instances = IPS_GetInstanceListByModuleID(self::ARCHIVE_CONTROL_MODULE_ID);
        return isset($instances[0]) ? (int) $instances[0] : 0;
    }

    private function enableLoggingIfMissing(int $archiveId, int $variableId): void
    {
        try {
            if (!AC_GetLoggingStatus($archiveId, $variableId)) {
                AC_SetLoggingStatus($archiveId, $variableId, true);
            }
        } catch (Throwable $e) {
            $this->SendDebug('Archive logging', $e->getMessage(), 0);
        }
    }

    private function ensureChargingChart(int $chartGroup, array $variableIds): void
    {
        $existing = @IPS_GetObjectIDByIdent('MSKODA_ChargingHistory', $chartGroup);
        if ($existing !== false) {
            // Existing chart configuration belongs to the user. Never overwrite it.
            return;
        }

        $chartId = IPS_CreateMedia(4);
        IPS_SetParent($chartId, $chartGroup);
        IPS_SetIdent($chartId, 'MSKODA_ChargingHistory');
        IPS_SetName($chartId, $this->Translate('Charging history'));
        IPS_SetIcon($chartId, 'Graph');
        IPS_SetPosition($chartId, 10);
        IPS_SetMediaFile($chartId, 'media/' . $chartId . '.chart', false);

        $datasets = [
            [
                'variableID' => (int) $variableIds['StateOfCharge'],
                'fillColor' => '#22c55e',
                'strokeColor' => '#22c55e',
                'timeOffset' => 0,
                'visible' => true,
                'title' => $this->Translate('State of charge'),
                'type' => 'line',
                'side' => 'left'
            ],
            [
                'variableID' => (int) $variableIds['TargetSOC'],
                'fillColor' => '#94a3b8',
                'strokeColor' => '#94a3b8',
                'timeOffset' => 0,
                'visible' => true,
                'title' => $this->Translate('Charging limit'),
                'type' => 'line',
                'side' => 'left'
            ],
            [
                'variableID' => (int) $variableIds['ChargePower'],
                'fillColor' => '#f59e0b',
                'strokeColor' => '#f59e0b',
                'timeOffset' => 0,
                'visible' => true,
                'title' => $this->Translate('Charging power'),
                'type' => 'line',
                'side' => 'right'
            ]
        ];

        IPS_SetMediaContent(
            $chartId,
            base64_encode(json_encode(['datasets' => $datasets], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
        );
    }
}
