<?php

declare(strict_types=1);

trait MySkodaStructureTrait
{
    private const DUMMY_MODULE_ID = '{485D0419-BE97-4548-AA9C-C083EB82E61E}';
    private const LINK_IDENT_PREFIX = 'MSKODA_Link_';

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

    private function ensureObjectGroups(): void
    {
        foreach (array_keys(self::GROUPS) as $groupKey) {
            $this->getGroupId($groupKey);
        }
    }

    private function organizeModuleObjects(): void
    {
        foreach (self::VARIABLE_GROUPS as $ident => $groupKey) {
            $this->placeVariableInGroup($ident);
        }

        $chartId = @IPS_GetObjectIDByIdent('MSKODA_ChargingHistory', $this->InstanceID);
        if ($chartId !== false && IPS_MediaExists((int) $chartId)) {
            $chartsGroupId = $this->getGroupId('charts');
            if ($chartsGroupId > 0) {
                IPS_SetParent((int) $chartId, $chartsGroupId);
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
        if ($variableId <= 0 || $groupId <= 0) {
            return;
        }

        $linkIdent = self::LINK_IDENT_PREFIX . $ident;
        $linkId = @IPS_GetObjectIDByIdent($linkIdent, $groupId);
        if ($linkId !== false && IPS_LinkExists((int) $linkId)) {
            IPS_DeleteLink((int) $linkId);
            IPS_SetHidden($variableId, false);
        }

        if (IPS_GetParent($variableId) !== $groupId) {
            IPS_SetParent($variableId, $groupId);
        }
    }

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

    protected function GetIDForIdent($Ident)
    {
        $directId = @parent::GetIDForIdent($Ident);
        if ($directId !== false) {
            return $directId;
        }

        $foundId = $this->findObjectByIdentRecursive($this->InstanceID, (string) $Ident, 0);
        return $foundId > 0 ? $foundId : false;
    }

    protected function SetValue($Ident, $Value)
    {
        $variableId = $this->GetIDForIdent($Ident);
        if ($variableId === false || !IPS_VariableExists((int) $variableId)) {
            return false;
        }

        SetValue((int) $variableId, $Value);
        return true;
    }

    protected function GetValue($Ident)
    {
        $variableId = $this->GetIDForIdent($Ident);
        if ($variableId === false || !IPS_VariableExists((int) $variableId)) {
            return null;
        }

        return GetValue((int) $variableId);
    }

    private function findVariableByIdent(string $ident): int
    {
        $objectId = $this->GetIDForIdent($ident);
        return $objectId !== false && IPS_VariableExists((int) $objectId) ? (int) $objectId : 0;
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
            if (!in_array((int) ($object['ObjectType'] ?? -1), [0, 1], true)) {
                continue;
            }

            $foundId = $this->findObjectByIdentRecursive((int) $childId, $ident, $depth + 1);
            if ($foundId > 0) {
                return $foundId;
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
