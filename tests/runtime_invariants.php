<?php

declare(strict_types=1);

// Isolated host double: no Symcon installation, credentials or network required.
if (class_exists('IPSModuleStrict', false)) {
    throw new RuntimeException('Run this test with standalone PHP, not inside Symcon.');
}
$root = dirname(__DIR__);
foreach (['BOOLEAN' => 0, 'INTEGER' => 1, 'FLOAT' => 2, 'STRING' => 3] as $name => $value) {
    define('VARIABLETYPE_' . $name, $value);
}
define('KL_WARNING', 1);
define('KL_ERROR', 2);
foreach (glob($root . '/MySkoda/src/*.php') as $file) {
    preg_match_all('/\bVARIABLE_(?:PRESENTATION|TEMPLATE)_[A-Z_]+\b/', file_get_contents($file), $matches);
    foreach ($matches[0] as $constant) {
        if (!defined($constant)) {
            define($constant, $constant);
        }
    }
}
$GLOBALS['objects'] = [];
$GLOBALS['nextId'] = 1000;
$GLOBALS['metadataWrites'] = 0;
$GLOBALS['checks'] = 0;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$GLOBALS['checks'];
}
function IPS_GetObjectIDByIdent(string $ident, int $parent): int|false
{
    foreach ($GLOBALS['objects'] as $id => $object) {
        if ($object['ParentID'] === $parent && $object['ObjectIdent'] === $ident) {
            return $id;
        }
    }
    return false;
}
function IPS_VariableExists(int $id): bool { return ($GLOBALS['objects'][$id]['ObjectType'] ?? -1) === 2; }
function IPS_GetVariable(int $id): array { return $GLOBALS['objects'][$id]; }
function IPS_GetObject(int $id): array { return $GLOBALS['objects'][$id]; }
function IPS_GetMedia(int $id): array { return $GLOBALS['objects'][$id]; }
function IPS_SetIcon(int $id, string $icon): void { ++$GLOBALS['metadataWrites']; $GLOBALS['objects'][$id]['ObjectIcon'] = $icon; }
function IPS_SetName(int $id, string $name): void { ++$GLOBALS['metadataWrites']; $GLOBALS['objects'][$id]['ObjectName'] = $name; }
function IPS_SetPosition(int $id, int $position): void { ++$GLOBALS['metadataWrites']; $GLOBALS['objects'][$id]['ObjectPosition'] = $position; }
function IPS_DeleteVariable(int $id): void { throw new RuntimeException('Variable deletion is forbidden in this test.'); }
function GetValue(int $id): mixed { return $GLOBALS['objects'][$id]['Value']; }

class MySkodaTestHost
{
    public int $InstanceID = 10;
    public array $properties = [];
    public array $attributes = [];
    public array $logs = [];
    public int $registrations = 0;
    public int $status = 0;
    public function Create(): void {}
    public function ApplyChanges(): void {}
    public function RegisterPropertyString(string $key, string $value): void { $this->properties[$key] ??= $value; }
    public function RegisterPropertyInteger(string $key, int $value): void { $this->properties[$key] ??= $value; }
    public function RegisterPropertyBoolean(string $key, bool $value): void { $this->properties[$key] ??= $value; }
    public function RegisterAttributeString(string $key, string $value): void { $this->attributes[$key] ??= $value; }
    public function RegisterAttributeInteger(string $key, int $value): void { $this->attributes[$key] ??= $value; }
    public function RegisterAttributeBoolean(string $key, bool $value): void { $this->attributes[$key] ??= $value; }
    public function ReadPropertyString(string $key): string { return $this->properties[$key]; }
    public function ReadPropertyInteger(string $key): int { return $this->properties[$key]; }
    public function ReadPropertyBoolean(string $key): bool { return $this->properties[$key]; }
    public function ReadAttributeString(string $key): string { return $this->attributes[$key]; }
    public function ReadAttributeInteger(string $key): int { return $this->attributes[$key]; }
    public function ReadAttributeBoolean(string $key): bool { return $this->attributes[$key]; }
    public function WriteAttributeString(string $key, string $value): void { $this->attributes[$key] = $value; }
    public function WriteAttributeInteger(string $key, int $value): void { $this->attributes[$key] = $value; }
    public function WriteAttributeBoolean(string $key, bool $value): void { $this->attributes[$key] = $value; }
    public function RegisterTimer(string $ident, int $interval, string $script): void {}
    public function SetTimerInterval(string $ident, int $interval): void {}
    public function SetVisualizationType(int $type): void {}
    public function SetSummary(string $summary): void {}
    public function SetStatus(int $status): void { $this->status = $status; }
    public function UpdateFormField(string $name, string $field, mixed $value): void {}
    public function Translate(string $text): string { return $text; }
    public function SendDebug(string $label, mixed $data, int $format): void {}
    public function LogMessage(string $message, int $severity): void { $this->logs[] = [$message, $severity]; }
    public function GetIDForIdent(string $ident): int|false { return IPS_GetObjectIDByIdent($ident, $this->InstanceID); }
    public function GetValue(string $ident): mixed { return GetValue($this->GetIDForIdent($ident)); }
    public function SetValue(string $ident, mixed $value): void
    {
        $id = $this->GetIDForIdent($ident);
        check($id !== false && IPS_VariableExists($id), 'Value target must be a variable: ' . $ident);
        $valid = match ($GLOBALS['objects'][$id]['VariableType']) {
            0 => is_bool($value), 1 => is_int($value), 2 => is_float($value), 3 => is_string($value)
        };
        check($valid, 'Unexpected value type: ' . $ident);
        $GLOBALS['objects'][$id]['Value'] = $value;
    }
    public function MaintainAction(string $ident, bool $enabled): void
    {
        $id = $this->GetIDForIdent($ident);
        if ($id !== false) { $GLOBALS['objects'][$id]['Action'] = $enabled; }
    }
    private function register(string $ident, string $name, array $presentation, int $position, int $type): void
    {
        check($this->GetIDForIdent($ident) === false, 'Existing variable re-registered: ' . $ident);
        ++$this->registrations;
        $GLOBALS['objects'][$GLOBALS['nextId']++] = [
            'ParentID' => $this->InstanceID, 'ObjectIdent' => $ident, 'ObjectType' => 2,
            'ObjectName' => $name, 'ObjectPosition' => $position, 'ObjectIcon' => '',
            'VariableType' => $type, 'Presentation' => $presentation,
            'Value' => match ($type) { 0 => false, 1 => 0, 2 => 0.0, 3 => '' }
        ];
    }
    public function RegisterVariableBoolean(string $ident, string $name, array $presentation, int $position): void { $this->register($ident, $name, $presentation, $position, 0); }
    public function RegisterVariableInteger(string $ident, string $name, array $presentation, int $position): void { $this->register($ident, $name, $presentation, $position, 1); }
    public function RegisterVariableFloat(string $ident, string $name, array $presentation, int $position): void { $this->register($ident, $name, $presentation, $position, 2); }
    public function RegisterVariableString(string $ident, string $name, array $presentation, int $position): void { $this->register($ident, $name, $presentation, $position, 3); }
}
class_alias(MySkodaTestHost::class, 'IPSModuleStrict');
require $root . '/MySkoda/module.php';
function invoke(MySkoda $module, string $method, mixed ...$args): mixed
{
    return (new ReflectionMethod(MySkoda::class, $method))->invoke($module, ...$args);
}
$m = new MySkoda();
$m->Create();
$m->ApplyChanges();
check($m->status === 201, 'An empty configuration must be reported as unconfigured.');
check($m->registrations === 37, 'Expected 37 standard variables.');
check($m->GetIDForIdent('NewApiFeatures') === false, 'No developer discovery variable.');
check($m->GetValue('TargetTemperature') === 22.0, 'Initial temperature.');
$m->properties['ShowDetails'] = true;
$m->properties['CreateVINVariables'] = true;
$m->properties['VIN'] = 'TMBJC7NY0N0000001'; // Synthetic syntax-valid fixture, no real vehicle.
$m->ApplyChanges();
check($m->registrations === 72, 'Expected 72 variables with both optional groups.');
check(count($GLOBALS['objects']) === 72, 'No duplicate identifiers.');
check(json_decode($m->GetConfigurationForm(), true) !== null, 'Configuration form must remain valid JSON.');

// User-owned metadata must survive repeated configuration and data updates.
foreach ($GLOBALS['objects'] as &$object) {
    $object['ObjectName'] = 'User ' . $object['ObjectIdent'];
    $object['ObjectPosition'] += 5000;
    $object['ObjectIcon'] = '';
    $object['Presentation'] = ['custom' => true];
}
unset($object);
$before = $GLOBALS['objects'];
$writes = $GLOBALS['metadataWrites'];
$m->ApplyChanges();
$m->ApplyChanges();
check($m->registrations === 72, 'Repeated ApplyChanges must not register existing variables.');
check($GLOBALS['metadataWrites'] === $writes, 'Existing metadata must not be written.');
foreach ($before as $id => $old) {
    foreach (['ObjectName', 'ObjectPosition', 'ObjectIcon', 'Presentation'] as $key) {
        check($GLOBALS['objects'][$id][$key] === $old[$key], 'Metadata changed: ' . $old['ObjectIdent'] . '/' . $key);
    }
}
$m->properties['ShowDetails'] = false;
$m->properties['CreateVINVariables'] = false;
$m->properties['EnableRemote'] = false;
$m->ApplyChanges();
check(count($GLOBALS['objects']) === 72, 'Disabling creation options must not delete objects.');
check($GLOBALS['objects'][$m->GetIDForIdent('Climate')]['Action'] === false, 'Remote actions follow configuration.');
$m->properties['EnableRemote'] = true;
$m->ApplyChanges();
$definition = invoke($m, 'variable', 'Locked', 'Replacement', VARIABLETYPE_BOOLEAN, 1, [], true);
$locked = $m->GetIDForIdent('Locked');
$saved = $GLOBALS['objects'][$locked];
$logCount = count($m->logs);
invoke($m, 'registerVariableOnce', $definition);
check($GLOBALS['objects'][$locked] === $saved, 'Wrong-type existing variables must not be deleted or modified.');
check(count($m->logs) === $logCount + 1, 'A type collision must be reported.');
$GLOBALS['objects'][$locked]['ObjectType'] = 0;
$saved = $GLOBALS['objects'][$locked];
$logCount = count($m->logs);
invoke($m, 'registerVariableOnce', $definition);
check($GLOBALS['objects'][$locked] === $saved, 'Non-variable ident collision must not be modified.');
check(count($m->logs) === $logCount + 1, 'An object collision must be reported.');
$GLOBALS['objects'][$locked]['ObjectType'] = 2;

$vehicle = [
    'vin' => $m->properties['VIN'],
    'airConditioning' => ['state' => 'OFF', 'targetTemperature' => ['value' => 20.0, 'unit' => 'CELSIUS']],
    'charging' => ['settings' => ['targetStateOfChargeInPercent' => 80, 'preferredChargeMode' => 'MANUAL', 'availableChargeModes' => ['MANUAL']], 'status' => ['state' => 'CHARGING', 'chargePowerInKw' => 4.2, 'battery' => ['stateOfChargeInPercent' => 71, 'remainingCruisingRangeInMeters' => 321000]]],
    'status' => ['overall' => ['doorsLocked' => 'YES', 'locked' => 'NO', 'reliableLockStatus' => 'UNKNOWN', 'doors' => 'CLOSED', 'windows' => 'OPEN']],
    'odometer' => ['mileageInKm' => 12345]
];
invoke($m, 'updateCoreValues', $vehicle);
check($m->GetValue('DoorsLocked') === 'YES' && $m->GetValue('Locked') === 'NO', 'Lock API fields remain independent strings.');
check($m->GetValue('WindowsOpen') === 'OPEN' && $m->GetValue('ReliableLockStatus') === 'UNKNOWN', 'Status strings are preserved.');
check($m->GetValue('ChargePower') === 4200.0 && $m->GetValue('Range') === 321, 'Power/range conversions.');
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
invoke($m, 'updatePublicApiValuesFromRawData');
$m->WriteAttributeString('LastVehicleResponseRaw', "{\n  \"detail\": \"fixture error\"\n}\n");
$workingData = $m->ReadAttributeString('RawData');
check($m->GetLastVehicleResponseRaw() === "{\n  \"detail\": \"fixture error\"\n}\n", 'Raw response must remain byte-exact.');
check($m->ReadAttributeString('RawData') === $workingData, 'Reading an error response must not replace valid cached data.');

$m->SetValue('TargetSOC', 80);
$ok = invoke($m, 'executeOptimisticCommand', 'TargetSOC', 90, 'Charging limit', function () use ($m): bool {
    check($m->GetValue('TargetSOC') === 90, 'Optimistic value during request.');
    check($m->GetValue('PendingCommands') === 1, 'Pending during request only.');
    return true;
});
check($ok && $m->GetValue('PendingCommands') === 0 && $m->GetValue('TargetSOC') === 90, 'Accepted command keeps desired value.');
check($m->ReadAttributeString('LastCommandResult') === 'accepted', 'Accepted result.');
$ok = invoke($m, 'executeOptimisticCommand', 'TargetSOC', 100, 'Charging limit', function () use ($m): bool {
    $m->WriteAttributeString('LastError', 'fixture rejection');
    return false;
});
check(!$ok && $m->GetValue('TargetSOC') === 90 && $m->GetValue('PendingCommands') === 0, 'Rejected command rolls back.');
$ok = invoke($m, 'executeOptimisticCommand', 'TargetSOC', 100, 'Charging limit', static function (): bool {
    throw new RuntimeException('fixture transport error');
});
check(!$ok && $m->GetValue('TargetSOC') === 90 && $m->GetValue('PendingCommands') === 0, 'Exception rolls back and clears pending.');
$m->RequestAction('TargetTemperature', 23.0);
invoke($m, 'updateCoreValues', $vehicle);
check($m->GetValue('TargetTemperature') === 23.0, 'Local temperature selection survives polling while climate is off.');
$vehicle['airConditioning']['state'] = 'ON';
invoke($m, 'updateCoreValues', $vehicle);
check($m->GetValue('TargetTemperature') === 20.0 && !$m->ReadAttributeBoolean('TargetTemperatureOverride'), 'Active climate restores API authority.');

$ops = [['operationId' => 'setChargingLimit', 'method' => 'PUT', 'path' => '/api/v1/vehicles/{vin}/charging/limit', 'requestSchema' => ['properties' => ['targetStateOfChargeInPercent' => ['type' => 'integer']]]]];
$m->WriteAttributeString('OpenApiOperations', json_encode($ops, JSON_THROW_ON_ERROR));
$m->WriteAttributeInteger('OpenApiUpdatedAt', time());
check(invoke($m, 'refreshOpenApi', false) === $ops, 'OpenAPI cache is still usable for commands.');
$operation = invoke($m, 'findOperation', $ops, 'limit');
check(invoke($m, 'buildScalarPayload', $operation, 'limit', 80) === ['targetStateOfChargeInPercent' => 80], 'Charging payload construction.');

// Cached media must not be moved or renamed by regular vehicle polling.
$vehicle['renderUrl'] = 'https://iprenders.blob.core.windows.net/test/fixture.png';
$m->WriteAttributeString('RawData', json_encode(['vehicle' => $vehicle], JSON_THROW_ON_ERROR));
$mediaId = $GLOBALS['nextId']++;
$GLOBALS['objects'][$mediaId] = [
    'ParentID' => $m->InstanceID, 'ObjectIdent' => 'VehicleImage', 'ObjectType' => 5,
    'ObjectName' => 'User image', 'ObjectPosition' => 8765, 'MediaType' => 1, 'MediaIsAvailable' => true,
    'ObjectInfo' => json_encode(['vehicleFingerprint' => hash('sha256', $m->properties['VIN'])], JSON_THROW_ON_ERROR)
];
$saved = $GLOBALS['objects'][$mediaId];
check(invoke($m, 'syncVehicleImage', false) === true, 'Valid image cache should be reused.');
check($GLOBALS['objects'][$mediaId] === $saved, 'Cached image metadata remains unchanged.');
check($GLOBALS['metadataWrites'] === $writes, 'No metadata repair writes after initial creation.');
echo 'Runtime invariants passed (' . $GLOBALS['checks'] . " checks).\n";
