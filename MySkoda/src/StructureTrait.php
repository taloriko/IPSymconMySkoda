<?php

declare(strict_types=1);

trait MySkodaStructureTrait
{
    private const DUMMY_MODULE_ID = '{485D0419-BE97-4548-AA9C-C083EB82E61E}';

    private const GROUPS = [
        'vehicle' => ['ident' => 'MSKODA_GroupVehicle', 'name' => 'Vehicle', 'icon' => 'Car', 'position' => 100],
        'status' => ['ident' => 'MSKODA_GroupStatus', 'name' => 'Vehicle status', 'icon' => 'Lock', 'position' => 200],
        'charging' => ['ident' => 'MSKODA_GroupCharging', 'name' => 'Charging', 'icon' => 'Electricity', 'position' => 300],
        'climate' => ['ident' => 'MSKODA_GroupClimate', 'name' => 'Air conditioning', 'icon' => 'Temperature', 'position' => 400],
        'location' => ['ident' => 'MSKODA_GroupLocation', 'name' => 'Location', 'icon' => 'Location', 'position' => 500],
        'diagnostics' => ['ident' => 'MSKODA_GroupDiagnostics', 'name' => 'API and diagnostics', 'icon' => 'Gear', 'position' => 600],
        'charts' => ['ident' => 'MSKODA_GroupCharts', 'name' => 'Charts', 'icon' => 'Graph', 'position' => 700],
        'lastUpdate' => ['ident' => 'MSKODA_GroupLastUpdate', 'name' => 'Last update', 'icon' => 'Clock', 'position' => 800]
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
     * Ensures that all module-owned groups exist.
     * Existing groups are intentionally left untouched so user changes survive.
     */
    private function ensureObjectGroups(): void
    {
        foreach (array_keys(self::GROUPS) as $groupKey) {
            $this->getGroupId($groupKey);
        }
    }

    /**
     * Places only ungrouped module objects below their intended dummy instance.
     * Objects that are already below another container are not moved again.
     */
    private function organizeModuleObjects(): void
    {
        foreach (self::VARIABLE_GROUPS as $ident => $groupKey) {
            $variableId = $this->findVariableByIdent($ident);
            if ($variableId <= 0 || IPS_GetParent($variableId) !== $this->InstanceID) {
                continue;
            }

            $groupId = $this->getGroupId($groupKey);
            if ($groupId > 0) {
                IPS_SetParent($variableId, $groupId);
            }
        }

        $chartId = @IPS_GetObjectIDByIdent('MSKODA_ChargingHistory', $this->InstanceID);
        if ($chartId !== false && IPS_MediaExists((int) $chartId)) {
            $groupId = $this->getGroupId('charts');
            if ($groupId > 0) {
                IPS_SetParent((int) $chartId, $groupId);
            }
        }
    }

    private function placeVariableInGroup(string $ident): void
    {
        $groupKey = self::VARIABLE_GROUPS[$ident] ?? null;
        if (!is_string($groupKey)) {
            return;
        }

        $variableId = $this->findVariableByIdent($ident);
        $groupId = $this->getGroupId($groupKey);
        if ($variableId > 0 && $groupId > 0 && IPS_GetParent($variableId) === $this->InstanceID) {
            IPS_SetParent($variableId, $groupId);
        }
    }

    /**
     * Updates the module action only when its state really changes.
     * Module actions are configured while the variable is a direct child and
     * the original group is restored immediately afterwards.
     */
    private function maintainGroupedAction(string $ident, bool $enabled): void
    {
        $variableId = $this->findVariableByIdent($ident);
        if ($variableId <= 0) {
            return;
        }

        $variable = IPS_GetVariable($variableId);
        $actionEnabled = (int) ($variable['VariableAction'] ?? 0) === $this->InstanceID;
        if ($actionEnabled === $enabled) {
            return;
        }

        $originalParent = IPS_GetParent($variableId);
        if ($originalParent !== $this->InstanceID) {
            IPS_SetParent($variableId, $this->InstanceID);
        }

        try {
            $this->MaintainAction($ident, $enabled);
        } finally {
            if ($originalParent !== $this->InstanceID && IPS_ObjectExists($originalParent)) {
                IPS_SetParent($variableId, $originalParent);
            }
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
        $variableId = $this->GetIDForIdent($Ident);
        if ($variableId === false || !IPS_VariableExists($variableId)) {
            return false;
        }

        SetValue($variableId, $Value);
        return true;
    }

    protected function GetValue(string $Ident): mixed
    {
        $variableId = $this->GetIDForIdent($Ident);
        if ($variableId === false || !IPS_VariableExists($variableId)) {
            return null;
        }

        return GetValue($variableId);
    }

    private function getGroupId(string $groupKey): int
    {
        $definition = self::GROUPS[$groupKey] ?? null;
        if (!is_array($definition)) {
            return 0;
        }

        $ident = (string) $definition['ident'];
        $existingId = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if ($existingId !== false) {
            if ($this->isDummyInstance((int) $existingId)) {
                return (int) $existingId;
            }

            $this->SendDebug('Structure', 'Ident ' . $ident . ' is already used by another object.', 0);
            return 0;
        }

        $groupId = IPS_CreateInstance(self::DUMMY_MODULE_ID);
        IPS_SetParent($groupId, $this->InstanceID);
        IPS_SetIdent($groupId, $ident);
        IPS_SetName($groupId, $this->Translate((string) $definition['name']));
        IPS_SetIcon($groupId, (string) $definition['icon']);
        IPS_SetPosition($groupId, (int) $definition['position']);
        return $groupId;
    }

    private function findVariableByIdent(string $ident): int
    {
        $objectId = $this->GetIDForIdent($ident);
        return $objectId !== false && IPS_VariableExists($objectId) ? $objectId : 0;
    }

    private function findObjectByIdentRecursive(int $parentId, string $ident, int $depth): int
    {
        if ($depth > 4 || !IPS_ObjectExists($parentId)) {
            return 0;
        }

        $children = IPS_GetChildrenIDs($parentId);
        foreach ($children as $childId) {
            $object = IPS_GetObject($childId);
            if ((string) ($object['ObjectIdent'] ?? '') === $ident) {
                return (int) $childId;
            }
        }

        foreach ($children as $childId) {
            $object = IPS_GetObject($childId);
            $objectType = (int) ($object['ObjectType'] ?? -1);
            if (!in_array($objectType, [0, 1], true)) {
                continue;
            }

            $found = $this->findObjectByIdentRecursive((int) $childId, $ident, $depth + 1);
            if ($found > 0) {
                return $found;
            }
        }

        return 0;
    }

    private function isDummyInstance(int $instanceId): bool
    {
        if (!IPS_InstanceExists($instanceId)) {
            return false;
        }

        $instance = IPS_GetInstance($instanceId);
        return (string) ($instance['ModuleInfo']['ModuleID'] ?? '') === self::DUMMY_MODULE_ID;
    }
}
